<?php
namespace App\Shell\Imports;

use App\Location\Location;
use App\Shell\ImportShell;
use CBERDataGrabber\ACSUpdater;

class HouseholdIncomeShell extends ImportShell
{
    public function run()
    {
        $this->year = $this->in('What year do you want to import data for?', null, 2011);
        $this->stateId = '18'; // Indiana
        $this->locationTypeId = 2; // County
        $this->surveyDate = $this->year.'0000';
        $this->sourceId = 60; // 'American Community Survey (ACS) (https://www.census.gov/programs-surveys/acs/)'
        $this->categoryIds = [
            'Number of Households' => 11,
            'Less than $10K' => 223,
            '$10K to $14,999' => 224,
            '$15K to $24,999' => 225,
            '$25K to $34,999' => 226,
            '$35K to $49,999' => 227,
            '$50K to $74,999' => 228,
            '$75K to $99,999' => 229,
            '$100K to $149,999' => 230,
            '$150K to $199,999' => 231,
            '$200K or more' => 232
        ];

        $this->out('Retrieving data from Census API...');
        ACSUpdater::setAPIKey($this->apiKey);
        $this->makeApiCall(function () {
            // Convert from count of households to percentage of households
            $categoryNames = array_keys($this->categoryIds);
            $householdCountCategory = array_shift($categoryNames);
            $results = ACSUpdater::getCountyData($this->year, $this->stateId, ACSUpdater::$HOUSEHOLD_INCOME, false);
            $convertedResults = [];
            foreach ($results as $fips => $data) {
                $householdCount = $data[$householdCountCategory];
                unset($data[$householdCountCategory]);
                $countyData = [$householdCountCategory => $householdCount];
                foreach ($data as $category => $count) {
                    $countyData[$category] = ($count / $householdCount) * 100;
                }
                $convertedResults[$fips] = $countyData;
            }
            return $convertedResults;
        });

        $this->import();
    }
}
