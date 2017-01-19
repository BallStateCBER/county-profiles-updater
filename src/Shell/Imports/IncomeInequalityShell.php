<?php
namespace App\Shell\Imports;

use App\Shell\ImportShell;
use Cake\Network\Exception\InternalErrorException;
use CBERDataGrabber\Updater\AcsUpdater;

class IncomeInequalityShell extends ImportShell
{
    /**
     * Run method
     *
     * @return void
     */
    public function run()
    {
        $this->geography = $this->in('Do you want to import county or state data?', ['county', 'state'], 'county');
        $this->year = $this->in('What year do you want to import data for?', null, 2015);
        $this->stateId = '18'; // Indiana
        if ($this->geography == 'county') {
            $this->locationTypeId = 2; // County
        } elseif ($this->geography == 'state') {
            $this->locationTypeId = 3; // State
        } else {
            throw new InternalErrorException('Unrecognized geography type "' . $this->geography . '"');
        }
        $this->surveyDate = $this->year . '0000';
        $this->sourceId = 63; // 'U.S. Census Bureau'
        $this->categoryIds = [
            'GINI Index' => 5668
        ];

        $this->out('Retrieving data from Census API...');
        AcsUpdater::setAPIKey($this->apiKey);
        $this->makeApiCall(function () {
            if ($this->geography == 'county') {
                return AcsUpdater::getCountyData(
                    $this->year,
                    $this->stateId,
                    AcsUpdater::$INEQUALITY_INDEX
                );
            }

            return AcsUpdater::getStateData(
                $this->year,
                $this->stateId,
                AcsUpdater::$INEQUALITY_INDEX
            );
        });

        $this->import();
    }
}
