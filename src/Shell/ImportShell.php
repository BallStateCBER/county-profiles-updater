<?php
namespace App\Shell;

use App\Location\Location;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Network\Exception\InternalErrorException;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use CBERDataGrabber\ACSUpdater;

class ImportShell extends Shell
{
    public $apiCallResults = [];
    public $categoryIds = [];
    public $ignoreCount = 0;
    public $locationTypeId = null;
    public $overwrite = null;
    public $sourceId = null;
    public $surveyDate = null;
    public $toInsert = [];
    public $toOverwrite = [];
    public $stepCount = 0;

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
        $this->out(pr($errors));
        $this->abort();
    }

    private function menu()
    {
        $msg = "Available imports:";

        $methods = get_class_methods($this);
        $imports = [];
        foreach ($methods as $method) {
            if (strpos($method, 'import') === 0 && $method != 'import') {
                $importName = str_replace('import', '', $method);
                $imports[] = lcfirst($importName);
            }
        }

        $msg .= empty($imports) ? " (none)" : "\n- ".implode($imports, "\n- ");
        $this->out($msg);
    }

    public function main($importName = null)
    {
        if (empty($importName)) {
            $this->menu();
            $importName = $this->in('Please select an import to run:');
        }

        $methodName = 'import'.ucwords($importName);
        if (! method_exists($this, $methodName)) {
            $this->out("Import \"$importName\" not recognized.\n");
            $this->menu();
            $importName = $this->in('Please select a valid import to run:');
        }

        $apiKey = Configure::read('census_api_key');
        ACSUpdater::setAPIKey($apiKey);
        $this->$methodName();
    }

    private function prepareImport()
    {
        if (empty($this->apiCallResults)) {
            $msg = $this->helper('Colorful')->error('No data returned');
            $this->abort($msg);
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
                $msg = $this->helper('Colorful')->error("FIPS code $fips does not correspond to any known county.");
                $this->abort($msg);
            }
            foreach ($data as $category => $value) {
                $step++;
                $percentDone = $this->getProgress($step, $dataPointCount);
                $msg = "Preparing import: $percentDone";
                $this->_io->overwrite($msg, 0);

                // Look for matching records
                if (! isset($this->categoryIds[$category])) {
                    $msg = $this->helper('Colorful')->error("Unrecognized category: $category");
                    $this->abort($msg);
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
                    $msg = "Problem: More than one statistics record found matching ".print_r($conditions, true);
                    $msg = $this->helper('Colorful')->error($msg);
                    $this->abort($msg);
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
            exit();
        }

        $begin = $this->in('Begin import?', ['y', 'n'], 'y');
        if ($begin == 'n') {
            exit();
        }
    }

    private function import()
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

    /**
     * @throws NotFoundException
     */
    public function importPopulationAge()
    {
        $year = '2013';
        $stateId = '18'; // Indiana
        $this->locationTypeId = 2; // County
        $this->surveyDate = $year.'0000';
        $this->sourceId = 60; // 'American Community Survey (ACS) (https://www.census.gov/programs-surveys/acs/)'
        $this->categoryIds = [
            'Total Population' => 1,
            'Under 5' => 272,
            '5 to 9' => 273,
            '10 to 14' => 274,
            '15 to 19' => 275,
            '20 to 24' => 276,
            '25 to 34' => 277,
            '35 to 44' => 278,
            '45 to 54' => 279,
            '55 to 59' => 280,
            '60 to 64' => 281,
            '65 to 74' => 282,
            '75 to 84' => 283,
            '85 and over' => 284
        ];

        $this->out('Retrieving data from Census API...');
        $this->apiCallResults = ACSUpdater::getCountyData($year, $stateId, ACSUpdater::$POPULATION_AGE, false);

        $this->import();
    }
}
