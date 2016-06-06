<?php
namespace App\Shell;

use App\Location\Location;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Filesystem\Folder;
use Cake\Network\Exception\InternalErrorException;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class ImportShell extends Shell
{
    public $apiCallResults = [];
    public $apiKey = null;
    public $availableImports = [];
    public $categoryIds = [];
    public $ignoreCount = 0;
    public $locationTypeId = null;
    public $overwrite = null;
    public $sourceId = null;
    public $stepCount = 0;
    public $surveyDate = null;
    public $toInsert = [];
    public $toOverwrite = [];
    public $year = null;
    public $stateId = null;

    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->description('CBER County Profiles Data Importer');
        $parser->addArgument('import name', [
            'help' => 'The name of an import to run, such as PopulationAge',
            'required' => false
        ]);
        return $parser;
    }

    public function abort($message = null, $exitCode = self::CODE_ERROR)
    {
        if ($message) {
            $message = $this->helper('Colorful')->error($message);
        }
        return parent::abort($message);
    }

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

    private function getProgress($step, $stepCount)
    {
        $percentDone = round(($step/$stepCount) * 100);
        $percentDone = str_pad($percentDone, 3, ' ', STR_PAD_LEFT);
        return $percentDone.'%';
    }

    private function abortWithEntityError($errors)
    {
        $count = count($errors);
        $msg = __n('Error', 'Errors', $count).' creating a statistic entity: ';
        $msg = $this->helper('Colorful')->error($msg);
        $this->out($msg);
        $this->out(print_r($errors, true));
        $this->abort();
    }

    /**
     * Menu of available imports
     *
     * @return int Key for $this->availableImports()
     */
    private function menu()
    {
        $this->out('Available imports:');
        $available = $this->availableImports();
        foreach ($available as $k => $import) {
            $this->out("[$k] $import");
        }
        $this->out('');
        $msg = 'Please select an import to run: ';
        if (count($available) > 1) {
            $msg .= '[0-'.(count($available) - 1).']';
        } else {
            $msg .= '[0]';
        }
        $importNum = $this->in($msg);
        if ($this->availableImports($importNum)) {
            return $importNum;
        }
        $this->out('Invalid selection', 2);
        return $this->menu();
    }

    public function main($importName = null)
    {
        // Process $importName parameter (e.g. "bin\cake import PopulationAge")
        $importNum = false;
        if ($importName) {
            $importNum = array_search($importName, $this->availableImports());
            if ($importNum === false) {
                $this->out("Import \"$importName\" not found", 2);
            }
        }

        // Display menu of available imports
        if ($importNum === false) {
            $importNum = $this->menu();
        }

        // Run import
        $importName = $this->availableImports($importNum);
        $importClass = "App\\Shell\\Imports\\{$importName}Shell";
        $importObj = new $importClass();
        $importObj->apiKey = Configure::read('census_api_key');
        $importObj->run();
    }

    private function prepareImport()
    {
        if (empty($this->apiCallResults)) {
            $this->abort('No data returned');
        }

        // Get totals for what was returned
        $dataPointCount = 0;
        foreach ($this->apiCallResults as $fips => $data) {
            $dataPointCount += count($data);
        }
        $locationCount = count($this->apiCallResults);
        $msg = number_format($dataPointCount).__n(' data point ', ' data points ', $dataPointCount);
        $msg .= 'found for '.number_format($locationCount).' locations';
        $this->out($msg, 2);

        // Break down insert / overwrite / ignore and catch errors
        $Location = new Location();
        $statisticsTable = TableRegistry::get('Statistics');
        $this->out('Preparing import...', 0);
        $step = 0;
        foreach ($this->apiCallResults as $fips => $data) {
            $locationId = $Location->getIdFromCode($fips, $this->locationTypeId);
            if (! $locationId) {
                $this->abort("FIPS code $fips does not correspond to any known county.");
            }
            foreach ($data as $category => $value) {
                $step++;
                $percentDone = $this->getProgress($step, $dataPointCount);
                $msg = "Preparing import: $percentDone";
                $this->_io->overwrite($msg, 0);

                // Look for matching records
                if (! isset($this->categoryIds[$category])) {
                    $this->abort("Unrecognized category: $category");
                }
                $categoryId = $this->categoryIds[$category];
                $conditions = [
                    'loc_type_id' => $this->locationTypeId,
                    'loc_id' => $locationId,
                    'survey_date' => $this->surveyDate,
                    'category_id' => $categoryId
                ];
                $results = $statisticsTable->find('all')
                    ->select(['id', 'value'])
                    ->where($conditions)
                    ->toArray();
                $count = count($results);
                if ($count > 1) {
                    $this->abort("Problem: More than one statistics record found matching ".print_r($conditions, true));
                }

                // Prepare record for inserting / overwriting
                $newRecord = $conditions;
                $newRecord['value'] = $value;
                $newRecord['source_id'] = $this->sourceId;

                // Mark for insertion
                if ($count == 0) {
                    $statEntity = $statisticsTable->newEntity($newRecord);
                    $errors = $statEntity->errors();
                    if (! empty($errors)) {
                        $this->abortWithEntityError($errors);
                    }
                    $this->toInsert[] = $statEntity;
                    continue;
                }

                // Increment ignore count
                $recordedValue = $results[0]['value'];
                if ($recordedValue == $value) {
                    $this->ignoreCount++;
                    continue;
                }

                // Mark for overwriting
                $recordId = $results[0]['id'];
                $statEntity = $statisticsTable->get($recordId);
                $statEntity = $statisticsTable->patchEntity($statEntity, $newRecord);
                $errors = $statEntity->errors();
                if (! empty($errors)) {
                    $this->abortWithEntityError($errors);
                }
                $this->toOverwrite[] = $statEntity;
            }
        }
        $this->out();

        $this->stepCount = 0;
        if ($this->ignoreCount) {
            $ignoreCount = $this->ignoreCount;
            $msg = number_format($ignoreCount).' '.__n('statistic has', 'statistics have', $ignoreCount);
            $msg .= ' already been recorded and will be '.$this->helper('Colorful')->importRedundant('ignored');
            $this->out($msg);
        }
        if (! empty($this->toInsert)) {
            $insertCount = count($this->toInsert);
            $msg = number_format($insertCount).' '.__n('statistic', 'statistics', $insertCount);
            $msg .= ' will be '.$this->helper('Colorful')->importInsert('added');
            $this->out($msg);
            $this->stepCount += $insertCount;
        }
        if (! empty($this->toOverwrite)) {
            $overwriteCount = count($this->toOverwrite);
            $msg = number_format($overwriteCount).' existing '.__n('statistic', 'statistics', $overwriteCount);
            $msg .= ' will be '.$this->helper('Colorful')->importOverwrite('overwritten');
            $this->out($msg);
            if ($this->getOverwrite()) {
                $this->stepCount += $overwriteCount;
            }
        }
        $this->out();

        if ($this->stepCount == 0) {
            $this->out('Nothing to import');
            $this->_stop();
        }

        $begin = $this->in('Begin import?', ['y', 'n'], 'y');
        if ($begin == 'n') {
            $this->_stop();
        }
    }

    protected function import()
    {
        $this->prepareImport();

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
            } else {
                $this->out();
                $msg = $overwriteCount.' updated '.__n('statistic', 'statistics', $overwriteCount).' ignored';
                $msg = $this->helper('Colorful')->importOverwriteBlocked($msg);
                $this->out($msg);
            }
        }

        $this->out();
        $msg = $this->helper('Colorful')->success('Import complete');
        $this->out($msg);
    }

    public function makeApiCall($callable)
    {
        try {
            $this->apiCallResults = $callable();
        } catch (\Exception $e) {
            $this->abort('Error: '.$e->getMessage());
        }
    }

    /**
     * Returns array of names of available imports, or a specific
     * import name if $key is provided. Returns FALSE if $key is
     * invalid. Attempts to populate $this->availableImports
     * when called for the first time and aborts program if it cannot.
     *
     * @param int $key
     * @return string|boolean
     */
    private function availableImports($key = null)
    {
        if (empty($this->availableImports)) {
            $dir = new Folder(APP.'Shell'.DS.'Imports');
            $files = $dir->find('.*Shell\.php');
            if (empty($files)) {
                $this->abort('No imports are available to run');
            }
            sort($files);
            foreach ($files as $k => $filename) {
                $name = str_replace('Shell.php', '', $filename);
                $this->availableImports[] = $name;
            }
        }

        if ($key !== null) {
            return isset($this->availableImports[$key]) ? $this->availableImports[$key] : false;
        }

        return $this->availableImports;
    }
}
