<?php
namespace App\Shell\Import;

use App\Import\Import;
use App\Location\Location;
use App\Shell\ImportShell;
use Cake\Core\Configure;
use Cake\Network\Exception\InternalErrorException;
use Cake\ORM\TableRegistry;
use CBERDataGrabber\Updater\AcsUpdater;

class ApiImportShell extends ImportShell
{
    public $apiCallResults = [];
    public $apiKey;
    public $year;

    /**
     * Analyzes data returned by the CBER Data Grabber, reports on errors,
     * reports on actions that will be taken by the import process, and
     * prompts to user to begin the import
     *
     * @param Import $import Import object
     * @return void
     */
    protected function prepareImport($import)
    {
        $this->import = $import;

        // Prompt selection of geography if appropriate
        $this->geography = $this->getGeography($this->import->geography);

        // Prompt selection of year
        $this->year = $this->getYear($this->import->defaultYear);

        // Make API call
        $apiKey = Configure::read('census_api_key');
        AcsUpdater::setAPIKey($apiKey);
        try {
            $this->apiCallResults = $this->makeApiCall();
        } catch (\Exception $e) {
            $this->abort('Error: ' . $e->getMessage());
        }
        if (empty($this->apiCallResults)) {
            $this->abort('No data returned');
        }

        // Run optional callback
        if ($this->import->callback) {
            $callback = $this->import->callback;
            $this->apiCallResults = $callback($this->apiCallResults);
        }

        // Get totals for what was returned
        $dataPointCount = 0;
        foreach ($this->apiCallResults as $fips => $data) {
            $dataPointCount += count($data);
        }
        $locationCount = count($this->apiCallResults);
        $msg = number_format($dataPointCount) .
            __n(' data point ', ' data points ', $dataPointCount) .
            'found for ' .
            number_format($locationCount) .
            __n(' location', ' locations', $locationCount);
        $this->out($msg, 2);

        // Break down insert / overwrite / ignore and catch errors
        $this->statisticsTable = TableRegistry::get('Statistics');
        $Location = new Location();
        $locationTypeId = $Location->getLocationTypeId($this->geography);
        $step = 0;
        $this->out('Preparing import...', 0);
        $previousPercentDone = 0;
        foreach ($this->apiCallResults as $fips => $data) {
            $locationId = $Location->getIdFromCode($fips, $locationTypeId);
            if (! $locationId) {
                $this->abort("FIPS code $fips does not correspond to any known area.");
            }
            foreach ($data as $category => $value) {
                $step++;

                // Report progress
                $percentDone = $this->getProgress($step, $dataPointCount);
                if ($percentDone != $previousPercentDone) {
                    $msg = "Preparing import: $percentDone";
                    $this->_io->overwrite($msg, 0);
                    $previousPercentDone = $percentDone;
                }

                // Prepare record for inserting / overwriting
                $surveyDate = (int)($this->year . '0000');
                $categoryId = $this->import->categoryIds[$category];
                $newRecord = [
                    'loc_type_id' => $locationTypeId,
                    'loc_id' => $locationId,
                    'survey_date' => $surveyDate,
                    'category_id' => $categoryId,
                    'value' => $value,
                    'source_id' => $this->import->sourceId
                ];

                // Look for matching records
                $matchingRecords = $this->getMatchingRecords(
                    $locationTypeId,
                    $locationId,
                    $surveyDate,
                    $categoryId
                );

                // Mark for insertion
                if (empty($matchingRecords)) {
                    $statEntity = $this->statisticsTable->newEntity($newRecord);
                    $errors = $statEntity->errors();
                    if (! empty($errors)) {
                        $this->abortWithEntityError($errors);
                    }
                    $this->toInsert[] = $statEntity;
                    continue;
                }

                // Or increment ignore count
                $recordedValue = $matchingRecords[0]['value'];
                if ($recordedValue == $value) {
                    $this->ignoreCount++;
                    continue;
                }

                // Or mark for overwriting
                $recordId = $matchingRecords[0]['id'];
                $statEntity = $this->statisticsTable->get($recordId);
                $statEntity = $this->statisticsTable->patchEntity($statEntity, $newRecord);
                $errors = $statEntity->errors();
                if (! empty($errors)) {
                    $this->abortWithEntityError($errors);
                }
                $this->toOverwrite[] = $statEntity;
            }
        }
        $msg = "Preparing import: 100%";
        $this->_io->overwrite($msg, 0);
        $this->out();

        $this->stepCount = 0;
        $this->reportIgnored();
        $this->reportToInsert();
        $this->reportToOverwrite();
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

    /**
     * Return the results of an API call
     *
     * @return array
     * @throws InternalErrorException
     */
    private function makeApiCall()
    {
        if ($this->geography == 'county') {
            $method = 'getCountyData';
        } elseif ($this->geography == 'state') {
            $method = 'getStateData';
        } else {
            throw new InternalErrorException('Unsupported geography type: ' . $this->geography);
        }

        $dataGrabberClass = 'CBERDataGrabber\\Updater\\' . $this->import->dataGrabber;

        return $dataGrabberClass::$method(
            $this->year,
            $this->stateId,
            $this->import->mapName
        );
    }

    /**
     * Prompts input for a year
     *
     * @param int $default Default year
     * @return int
     */
    private function getYear($default)
    {
        return (int)$this->in(
            "\nWhat year do you want to import data for?",
            null,
            $default
        );
    }
}
