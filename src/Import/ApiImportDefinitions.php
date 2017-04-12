<?php
namespace App\Import;

use CBERDataGrabber\Updater\AcsUpdater;

class ApiImportDefinitions
{
    /**
     * Returns the definitions of each supported import
     *
     * @return array
     */
    public static function getDefinitions()
    {
        $imports = [];

        $imports['Educational attainment'] = [
            'geography' => ['county', 'state'],
            'sourceId' => 62,
            'categoryIds' => [
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
            ],
            'dataGrabber' => 'AcsUpdater',
            'mapName' => AcsUpdater::EDUCATIONAL_ATTAINMENT,
            'callback' => function ($results) {
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
            }
        ];

        $imports['Ethnic makeup'] = [
            'geography' => 'county',
            'sourceId' => 61,
            'categoryIds' => [
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
            ],
            'dataGrabber' => 'AcsUpdater',
            'mapName' => AcsUpdater::ETHNIC_MAKEUP,
            'callback' => function ($results) {
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
            }
        ];

        $imports['Household income'] = [
            'geography' => 'county',
            'sourceId' => 60,
            'categoryIds' => [
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
            ],
            'dataGrabber' => 'AcsUpdater',
            'mapName' => AcsUpdater::HOUSEHOLD_INCOME,
            'callback' => function ($results) {
                foreach ($results as $fips => &$data) {
                    $householdCount = $data['Number of Households'];
                    foreach ($data as $category => $count) {
                        if ($category != 'Number of Households') {
                            $percent = ($count / $householdCount) * 100;
                            $data["Percent: $category"] = round($percent, 2);
                        }
                    }
                }

                return $results;
            }
        ];

        $imports['Income inequality'] = [
            'geography' => ['county', 'state'],
            'sourceId' => 63,
            'categoryIds' => [
                'GINI Index' => 5668
            ],
            'dataGrabber' => 'AcsUpdater',
            'mapName' => AcsUpdater::INEQUALITY_INDEX
        ];

        $imports['Population by age'] = [
            'geography' => 'county',
            'sourceId' => 60,
            'categoryIds' => [
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
            ],
            'dataGrabber' => 'AcsUpdater',
            'mapName' => AcsUpdater::POPULATION_AGE
        ];

        foreach ($imports as $key => $params) {
            $imports[$key]['type'] = 'api';
        }

        return $imports;
    }
}
