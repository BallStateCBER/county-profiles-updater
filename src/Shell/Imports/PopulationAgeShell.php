<?php
namespace App\Shell\Imports;

use App\Location\Location;
use App\Shell\ImportShell;
use CBERDataGrabber\ACSUpdater;

class PopulationAgeShell extends ImportShell
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
        ACSUpdater::setAPIKey($this->apiKey);
        $this->makeApiCall(function () {
            return ACSUpdater::getCountyData($this->year, $this->stateId, ACSUpdater::$POPULATION_AGE);
        });

        $this->import();
    }
}
