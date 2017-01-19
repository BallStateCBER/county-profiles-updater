<?php
namespace App\Shell\Imports;

use App\Shell\ImportShell;
use Cake\Network\Exception\InternalErrorException;
use CBERDataGrabber\Updater\AcsUpdater;

class EducationalAttainmentShell extends ImportShell
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
        $this->sourceId = 62; // 'U.S. Census Bureau'
        $this->categoryIds = [
            'Less than 9th grade' => 5711,
            'Percent: Less than 9th grade' => 5712,
            '9th to 12th grade, no diploma' => 456,
            'Percent: 9th to 12th grade, no diploma' => 468,
            'High school graduate (incl. equivalency)' => 457,
            'Percent: High school graduate (incl. equivalency)' => 469,
            'Some college, no degree' => 5713,
            'Percent: Some college, no degree' => 5714,
            'Associate degree' => 460,
            'Percent: Associate degree' => 472,
            'Bachelor\'s degree' => 461,
            'Percent: Bachelor\'s degree' => 473,
            'Graduate or professional degree' => 5725,
            'Percent: Graduate or professional degree' => 5726
        ];

        $this->out('Retrieving data from Census API...');
        AcsUpdater::setAPIKey($this->apiKey);
        $this->makeApiCall(function () {
            if ($this->geography == 'county') {
                $results = AcsUpdater::getCountyData(
                    $this->year,
                    $this->stateId,
                    AcsUpdater::$EDUCATIONAL_ATTAINMENT
                );
            } else {
                $results = AcsUpdater::getStateData(
                    $this->year,
                    $this->stateId,
                    AcsUpdater::$EDUCATIONAL_ATTAINMENT
                );
            }

            $retval = [];
            foreach ($results as $fips => &$data) {
                $totalPopulation = $data['Total'];
                foreach ($data as $category => $count) {
                    if ($category == 'Total') {
                        continue;
                    }
                    $retval[$fips][$category] = $count;
                    $percent = ($count / $totalPopulation) * 100;
                    $retval[$fips]["Percent: $category"] = round($percent, 2);
                }
            }

            return $retval;
        });

        $this->import();
    }
}
