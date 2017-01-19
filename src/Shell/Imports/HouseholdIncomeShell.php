<?php
namespace App\Shell\Imports;

use App\Location\Location;
use App\Shell\ImportShell;
use CBERDataGrabber\Updater\AcsUpdater;

class HouseholdIncomeShell extends ImportShell
{
    /**
     * Run method
     *
     * @return void
     */
    public function run()
    {
        $this->year = $this->in('What year do you want to import data for?', null, 2015);
        $this->stateId = '18'; // Indiana
        $this->locationTypeId = 2; // County
        $this->surveyDate = $this->year . '0000';
        $this->sourceId = 60; // 'American Community Survey (ACS) (https://www.census.gov/programs-surveys/acs/)'
        $this->categoryIds = [
            'Number of Households' => 11,
            'Less than $10K' => 135,
            '$10K to $14,999' => 14,
            '$15K to $24,999' => 15,
            '$25K to $34,999' => 16,
            '$35K to $49,999' => 17,
            '$50K to $74,999' => 18,
            '$75K to $99,999' => 19,
            '$100K to $149,999' => 20,
            '$150K to $199,999' => 136,
            '$200K or more' => 137,
            'Percent: Less than $10K' => 223,
            'Percent: $10K to $14,999' => 224,
            'Percent: $15K to $24,999' => 225,
            'Percent: $25K to $34,999' => 226,
            'Percent: $35K to $49,999' => 227,
            'Percent: $50K to $74,999' => 228,
            'Percent: $75K to $99,999' => 229,
            'Percent: $100K to $149,999' => 230,
            'Percent: $150K to $199,999' => 231,
            'Percent: $200K or more' => 232
        ];

        $this->out('Retrieving data from Census API...');
        AcsUpdater::setAPIKey($this->apiKey);
        $this->makeApiCall(function () {
            // Calculate "percentage of households" values
            $categoryNames = array_keys($this->categoryIds);
            $householdCountCategory = array_shift($categoryNames);
            $results = AcsUpdater::getCountyData($this->year, $this->stateId, AcsUpdater::$HOUSEHOLD_INCOME);
            foreach ($results as $fips => &$data) {
                $householdCount = $data[$householdCountCategory];
                foreach ($data as $category => $count) {
                    if ($category != 'Number of Households') {
                        $percent = ($count / $householdCount) * 100;
                        $data["Percent: $category"] = round($percent, 2);
                    }
                }
            }

            return $results;
        });

        $this->import();
    }
}
