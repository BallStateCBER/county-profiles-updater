<?php
namespace App\Shell;

use App\Import\ApiImportDefinitions;
use App\Import\CsvImportDefinitions;
use App\Import\Import;
use App\Shell\Import\ApiImportShell;
use App\Shell\Import\CsvImportShell;
use Cake\Console\Shell;
use Cake\Network\Exception\InternalErrorException;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

class ImportShell extends Shell
{
    public $availableImports = [];
    public $categoryIds = [];
    public $geography;
    public $ignoreCount = 0;
    public $import;
    public $locationTypeId;
    public $overwrite;
    public $sourceId;
    public $stateId = 18;
    public $statisticsTable;
    public $stepCount = 0;
    public $surveyDate;
    public $toInsert = [];
    public $toOverwrite = [];
    public $year;

    /**
     * Modifies the standard output of running 'cake import --help'
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->setDescription('CBER County Profiles Data Importer');

        return $parser;
    }

    /**
     * Aborts the script with a styled error message
     *
     * @param null|string $message Message
     * @param int $exitCode Exit code
     * @return void
     */
    public function abort($message = null, $exitCode = self::CODE_ERROR)
    {
        if ($message) {
            $message = $this->helper('Colorful')->error($message);
        }
        parent::abort($message);
    }

    /**
     * Gets the value for $this->overwrite and prompts for
     * input if it has not been set
     *
     * @return bool
     */
    private function getOverwrite()
    {
        if ($this->overwrite == 'y') {
            return true;
        }
        if ($this->overwrite == 'n') {
            return false;
        }
        $this->overwrite = $this->in('Overwrite existing database records?', ['y', 'n'], 'y');

        return $this->getOverwrite();
    }

    /**
     * Returns a string containing the percentage of a task that is done
     *
     * @param int $step Current step number
     * @param int $stepCount Total number of steps
     * @return string
     */
    protected function getProgress($step, $stepCount)
    {
        $percentDone = round(($step / $stepCount) * 100);
        $percentDone = str_pad($percentDone, 3, ' ', STR_PAD_LEFT);

        return $percentDone . '%';
    }

    /**
     * Aborts the script with information about an error concerning
     * a Statistic entity
     *
     * @param array $errors Errors
     * @return void
     */
    protected function abortWithEntityError($errors)
    {
        $count = count($errors);
        $msg = __n('Error', 'Errors', $count) . ' creating a statistic entity: ';
        $msg = $this->helper('Colorful')->error($msg);
        $this->out($msg);
        $this->out(print_r($errors, true));
        $this->abort();
    }

    /**
     * Menu of available imports, returns an import definition array
     *
     * @return Import
     */
    private function menu()
    {
        // Get available imports
        $this->out('Available imports:');
        try {
            $imports = $this->getAvailableImports();
        } catch (\Exception $e) {
            $this->abort('Error: ' . $e->getMessage());
        }

        // Display a menu
        $importNames = array_keys($imports);
        foreach ($importNames as $key => $importName) {
            $option = "[$key] " . $this->helper('Colorful')->menuOption($importName);
            $this->out($option);
        }
        $this->out('');

        // Collect a menu selection
        $msg = 'Please select an import to run: ';
        if (count($imports) > 1) {
            $msg .= '[0-' . (count($imports) - 1) . ']';
        } else {
            $msg .= '[0]';
        }
        $importNum = $this->in($msg);

        // Return import object
        try {
            return $this->getImportObject($importNum);
        } catch (\Exception $e) {
            $this->out($this->helper('Colorful')->error('Invalid selection'), 2);

            return $this->menu();
        }
    }

    /**
     * Main method
     *
     * @return void
     */
    public function main()
    {
        // Have user select from a list of available imports
        $import = $this->menu();

        // Run import
        switch ($import->type) {
            case 'api':
                $importHandler = new ApiImportShell();
                break;
            case 'csv':
                $importHandler = new CsvImportShell();
                break;
            default:
                $this->abort('Unrecognized import type: ' . $import->type);
        }

        $importHandler->import($import);
    }

    /**
     * Returns an array of records that match the current data location, date, and category
     *
     * @param int $locationTypeId LocationType id
     * @param int $locationId Location id
     * @param int $surveyDate Survey date (YYYYMMDD)
     * @param int $categoryId Category ID
     * @return array
     */
    protected function getMatchingRecords($locationTypeId, $locationId, $surveyDate, $categoryId)
    {
        $conditions = [
            'loc_type_id' => $locationTypeId,
            'loc_id' => $locationId,
            'survey_date' => $surveyDate,
            'category_id' => $categoryId
        ];
        $results = $this->statisticsTable->find('all')
            ->select(['id', 'value'])
            ->where($conditions)
            ->toArray();
        if (count($results) > 1) {
            $msg = 'Problem: More than one statistics record found matching ' . print_r($conditions, true);
            $this->abort($msg);
        }

        return $results;
    }

    /**
     * Outputs a message about data that will be ignored
     *
     * @return void
     */
    protected function reportIgnored()
    {
        if (! $this->ignoreCount) {
            return;
        }

        $ignoreCount = $this->ignoreCount;
        $msg = number_format($ignoreCount) .
            ' ' .
            __n('statistic has', 'statistics have', $ignoreCount) .
            ' already been recorded and will be ' .
            $this->helper('Colorful')->importRedundant('ignored');
        $this->out($msg);
    }

    /**
     * Outputs a message about data that will be inserted
     *
     * @return void
     */
    protected function reportToInsert()
    {
        if (empty($this->toInsert)) {
            return;
        }

        $insertCount = count($this->toInsert);
        $msg = number_format($insertCount) .
            ' ' .
            __n('statistic', 'statistics', $insertCount) .
            ' will be ' .
            $this->helper('Colorful')->importInsert('added');
        $this->out($msg);
        $this->stepCount += $insertCount;
    }

    /**
     * Outputs a message about data that will be overwritten
     *
     * @return void
     */
    protected function reportToOverwrite()
    {
        if (empty($this->toOverwrite)) {
            return;
        }

        $overwriteCount = count($this->toOverwrite);
        $msg = number_format($overwriteCount) .
            ' existing ' .
            __n('statistic', 'statistics', $overwriteCount) .
            ' will be ' .
            $this->helper('Colorful')->importOverwrite('overwritten');
        $this->out($msg);
        if ($this->getOverwrite()) {
            $this->stepCount += $overwriteCount;
        }
    }

    /**
     * Returns an array of the definitions of available imports, keyed by import names.
     *
     * @return array
     * @throws InternalErrorException
     * @throws NotFoundException
     */
    private function getAvailableImports()
    {
        if (empty($this->availableImports)) {
            $apiImportDefinitions = ApiImportDefinitions::getDefinitions();
            $csvImportDefinitions = CsvImportDefinitions::getDefinitions();

            // Check for conflicting import definitions
            foreach ($apiImportDefinitions as $key => $params) {
                if (isset($csvImportDefinitions[$key])) {
                    $msg = 'Error: Multiple imports found for "' . $key . '"';
                    throw new InternalErrorException($msg);
                }
            }

            $imports = array_merge($apiImportDefinitions, $csvImportDefinitions);
            if (empty($imports)) {
                throw new NotFoundException('No imports are available to run');
            }

            ksort($imports);

            $this->availableImports = $imports;
        }

        return $this->availableImports;
    }

    /**
     * Returns the import object corresponding to the provided key
     *
     * @param int $key Numerical or string key
     * @return Import
     * @throws NotFoundException
     */
    private function getImportObject($key)
    {
        $importDefinitions = $this->getAvailableImports();

        if (is_numeric($key)) {
            $importNames = array_keys($importDefinitions);
            if (isset($importNames[$key])) {
                return new Import($importDefinitions[$importNames[$key]]);
            }
        }

        if (isset($importDefinitions[$key])) {
            return new Import($importDefinitions[$key]);
        }

        $msg = 'No import definition found for selection #' . $key;
        throw new NotFoundException($msg);
    }

    /**
     * Returns the name of the geographic scope (county, state, etc.) for this import,
     * prompting the user for input if necessary
     *
     * @param string|string[] $options Either a string ('county') or array (['county', 'state'])
     * @return string
     */
    protected function getGeography($options)
    {
        if ($this->geography) {
            return $this->geography;
        }

        if (is_string($options)) {
            return $options;
        }

        if (count($options) == 1) {
            return $options[0];
        }

        $this->out("\nAvailable geographic scopes:");
        foreach ($options as $k => $option) {
            $this->out("[$k] " . $this->helper('Colorful')->menuOption($option));
        }
        $msg = "\nPlease select a geographic scope: ";
        $optionKey = $this->in($msg, array_keys($options), 0);

        return $options[$optionKey];
    }

    /**
     * Prepares an import and conducts inserts and updates where appropriate
     *
     * @param Import $import Import object
     * @return bool
     */
    protected function import($import)
    {
        $this->prepareImport($import);

        $step = 0;
        $percentDone = $this->getProgress($step, $this->stepCount);
        $msg = "Importing: $percentDone";
        $this->out($msg, 0);
        $statisticsTable = TableRegistry::get('Statistics');

        // Insert
        if (! empty($this->toInsert)) {
            foreach ($this->toInsert as $i => $statEntity) {
                $step++;
                $percentDone = $this->getProgress($step, $this->stepCount);
                $msg = "Importing: $percentDone";
                $this->_io->overwrite($msg, 0);
                $statisticsTable->save($statEntity);
            }
        }

        // Overwrite
        if (! empty($this->toOverwrite)) {
            if ($this->getOverwrite()) {
                foreach ($this->toOverwrite as $i => $statEntity) {
                    $step++;
                    $percentDone = $this->getProgress($step, $this->stepCount);
                    $msg = "Importing: $percentDone";
                    $this->_io->overwrite($msg, 0);
                    $statisticsTable->save($statEntity);
                }
            }
        }

        $msg = "Preparing import: 100%";
        $this->_io->overwrite($msg, 0);

        $this->out();
        $msg = $this->helper('Colorful')->success('Import complete');
        $this->out($msg);

        if (! empty($this->toOverwrite) && ! $this->getOverwrite()) {
            $overwriteCount = count($this->toOverwrite);
            $msg = $overwriteCount . ' updated ' . __n('statistic', 'statistics', $overwriteCount) . ' ignored';
            $msg = $this->helper('Colorful')->importOverwriteBlocked($msg);
            $this->out($msg);
        }

        return true;
    }
}
