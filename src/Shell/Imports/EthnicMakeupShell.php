<?php
namespace App\Shell\Imports;

use App\Shell\ImportShell;
use CBERDataGrabber\Updater\AcsUpdater;

class EthnicMakeupShell extends ImportShell
{
    /**
     * Run method
     *
     * @return void
     */
    public function run()
    {
        $this->year = $this->in("\nWhat year do you want to import data for?", null, 2015);
        $this->stateId = '18'; // Indiana
        $this->locationTypeId = 2; // County
        $this->surveyDate = $this->year . '0000';
        $this->sourceId = 61; // 'U.S. Census Bureau'

        /*
         * Note: The category 'Hispanic or Latino' was available in the 2010
         * census but is not returned in API calls for 2011
         */
        $this->categoryIds = [
            'Percent: White' => 385,
            'Percent: Black' => 386,
            'Percent: Native American' => 387,
            'Percent: Asian' => 388,
            'Percent: Pacific Islander' => 396,
            'Percent: Other' => 401,
            'Percent: Two or more' => 402,
            'White' => 295,
            'Black' => 296,
            'Native American' => 297,
            'Asian' => 298,
            'Pacific Islander' => 306,
            'Other' => 311,
            'Two or more' => 312
        ];

        $this->out("\nRetrieving data from Census API...");
        AcsUpdater::setAPIKey($this->apiKey);
        $this->makeApiCall(function () {
            $results = AcsUpdater::getCountyData($this->year, $this->stateId, AcsUpdater::$ETHNIC_MAKEUP);
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
