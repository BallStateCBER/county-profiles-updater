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
    public $overwrite = null;
    public $toInsert = [];
    public $toOverwrite = [];
    public $ignoreCount = 0;

    /**
     * Returns the County Profiles DataCategory ID for
     * a given category name as provided by CBERDataGrabber
     *
     * @param string $categoryName
     * @return int
     * @throws NotFoundException
     */
    private function getCategoryId($categoryName) {
        switch ($categoryName) {
            case 'Total Population':
                return 1;
            case 'Under 5':
                return 272;
            case '5 to 9':
                return 273;
            case '10 to 14':
                return 274;
            case '15 to 19':
                return 275;
            case '20 to 24':
                return 276;
            case '25 to 34':
                return 277;
            case '35 to 44':
                return 278;
            case '45 to 54':
                return 279;
            case '55 to 59':
                return 280;
            case '60 to 64':
                return 281;
            case '65 to 74':
                return 282;
            case '75 to 84':
                return 283;
            case '85 and over':
                return 284;
            default:
                throw new NotFoundException("Unrecognized category: $categoryName");
        }
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

    private function menu()
    {
        $msg = "Available imports:\n";
        $msg .= "- populationAge";
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

    /**
     * @throws NotFoundException
     */
    public function importPopulationAge() {
        $year = '2013';
        $stateId = '18'; // Indiana
        $locationTypeId = 2; // County
        $surveyDate = $year.'0000';
        $sourceId = 60; // 'American Community Survey (ACS) (https://www.census.gov/programs-surveys/acs/)'

        $this->out('Retrieving data from Census API...');
        $results = ACSUpdater::getCountyData($year, $stateId, ACSUpdater::$POPULATION_AGE, false);

        if (empty($results)) {
            $msg = $this->helper('Colorful')->error('No data returned');
            $this->abort($msg);
        }

        // Get totals for what was returned
        $dataPointCount = 0;
        foreach ($results as $fips => $data) {
            $dataPointCount += count($data);
        }
        $locationCount = count($results);
        $msg = number_format($dataPointCount).__n(' data point ', ' data points ', $dataPointCount);
        $msg .= 'found for '.number_format($locationCount).' locations';
        $this->out($msg, 2);

        // Break down insert / overwrite / ignore and catch errors
        $Location = new Location();
        $statisticsTable = TableRegistry::get('Statistics');
        $this->out('Preparing import...', 0);
        $step = 0;
        foreach ($results as $fips => $data) {
            $locationId = $Location->getIdFromCode($fips, $locationTypeId);
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
                $categoryId = $this->getCategoryId($category);
                $conditions = [
                    'loc_type_id' => $locationTypeId,
                    'loc_id' => $locationId,
                    'survey_date' => $surveyDate,
                    'category_id' => $categoryId
                ];
                $results = $statisticsTable->find('all')
                    ->select(['id', 'value'])
                    ->where($conditions);
                $count = $results->count();
                if ($count > 1) {
                    $msg = "Problem: More than one statistics record found matching ".print_r($conditions, true);
                    $msg = $this->helper('Colorful')->error($msg);
                    $this->abort($msg);
                }

                // Prepare record for inserting / overwriting
                $newRecord = $conditions;
                $newRecord['value'] = $value;
                $newRecord['source_id'] = $sourceId;

                // Mark for insertion
                if ($count == 0) {
                    $this->toInsert[] = $newRecord;
                    continue;
                }

                // Increment ignore count
                if ($results[0]['value']) {
                    $this->ignoreCount++;
                    continue;
                }

                // Mark for overwriting
                $recordId = $results[0]['id'];
                $this->toOverwrite[$recordId] = $newRecord;
            }
        }
        $this->out();

        $stepCount = 0;
        if ($this->ignoreCount) {
            $insertCount = count($this->ignoreCount);
            $msg = number_format($insertCount).' '.__n('statistic', 'statistics', $insertCount);
            $msg .= ' have already been recorded and will be '.$this->helper('Colorful')->importRedundant('ignored');
            $this->out($msg);
        }
        if (! empty($this->toInsert)) {
            $insertCount = count($this->toInsert);
            $msg = number_format($insertCount).' '.__n('statistic', 'statistics', $insertCount);
            $msg .= ' will be '.$this->helper('Colorful')->importInsert('added');
            $this->out($msg);
            $stepCount += $insertCount;
        }
        if (! empty($this->toOverwrite)) {
            $overwriteCount = count($this->toOverwriteO);
            $msg = number_format($overwriteCount).' existing '.__n('statistic', 'statistics', $overwriteCount);
            $msg .= ' will be '.$this->helper('Colorful')->importOverwrite('overwritten');
            $this->out($msg);
            if ($this->getOverwrite()) {
                $stepCount += $overwriteCount;
            }
        }
        $this->out();

        if ($stepCount == 0) {
            $this->out('Nothing to import');
            exit();
        }

        $begin = $this->in('Begin import?', ['y', 'n'], 'y');
        if ($begin == 'n') {
            exit();
        }
        $step = 0;

        $percentDone = $this->getProgress($step, $stepCount);
        $msg = "Importing: $percentDone";
        $this->out($msg, 0);

        // Insert
        if (! empty($this->toInsert)) {
            foreach ($this->toInsert as $i => $record) {
                $step++;
                $percentDone = $this->getProgress($step, $stepCount);
                $msg = "Importing: $percentDone";
                $this->_io->overwrite($msg, 0);
            }
        }

        // Overwrite
        if (! empty($this->toOverwrite)) {
            if ($this->getOverwrite()) {
                foreach ($this->toOverwrite as $i => $record) {
                    $step++;
                    $percentDone = $this->getProgress($step, $stepCount);
                    $msg = "Importing: $percentDone";
                    $this->_io->overwrite($msg, 0);
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
}
