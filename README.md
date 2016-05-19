County Profiles Updater
=======================

Uses the [CBER Data Grabber](https://github.com/BallStateCBER/cber-data-grabber) in a shell script
to pull US federal data from various APIs and update the [County Profiles](http://profiles.cberdata.org)
website, produced by [Ball State University](http://bsu.edu)'s
[Center for Business and Economic Research](http://cberdata.org).

Usage
-----

When installed on the same server as County Profiles and `config/app.php` is set up with the correct
database connection settings, this app interfaces with the County Profiles database.

To view the menu and select an import:

    cd C:\path\to\app
    bin\cake import

To skip the menu and run a specific import:

    cd C:\path\to\app
    bin\cake import {importName}

The selected import proceeds thusly:

1. Data is pulled from the API
2. This data is checked for errors and it's determined whether this is data that
it needs to **insert** into the database, data that needs to **update** existing records,
or data that is already present in the database and can be **ignored**
3. Assuming there's data to import, the script asks for confirmation to proceed and
for permission to overwrite existing records if appropriate.
4. *MAGIC*

Adding new imports
-------------------------
To set up a means to update `Foo` data through this Shell, create the file `src/Shell/Imports/FooShell.php`, changing `ACSUpdater` to a different class if needed.

    <?php
    namespace App\Shell\Imports;

    use App\Location\Location;
    use App\Shell\ImportShell;
    use CBERDataGrabber\ACSUpdater;

    class FooShell extends ImportShell
    {
        public function run()
        {
            $defaultYear = 2014;
            $this->year = $this->in('What year do you want to import data for?', null, $defaultYear);
            $this->stateId = '18'; // Indiana
            $this->locationTypeId = 2; // County
            $this->surveyDate = $this->year.'0000';
            $this->sourceId = 60; // 'American Community Survey (ACS) (https://www.census.gov/programs-surveys/acs/)'
            $this->categoryIds = [
                'First data category name' => 123,
                'Another data category name' => 456
            ];

            $this->out('Retrieving data from Census API...');
            ACSUpdater::setAPIKey($this->apiKey);
            $this->makeApiCall(function () {
                return ACSUpdater::getCountyData($this->year, $this->stateId, ACSUpdater::$FOO, false);
            });

            $this->import();
        }
    }

The method `run()` must

1. Set the object properties `locationTypeId`, `surveyDate`, `sourceId`, and `categoryIds`
   (the County Profiles class `/Model/SegmentData.php` and the [Data Categories Manager](http://profiles.cberdata.org/admin/data_categories) will help you determine the right category IDs to use)
2. Output `'Retrieving data from {data source}...'`
3. Set any API key necessary
4. Call `$this->apiCallResults($callable)` with a function that returns the result of a a call to a [CBER Data Grabber](https://github.com/BallStateCBER/cber-data-grabber) method
5. Call `$this->import();`

After creating this class, `Foo` will appear in the list of available imports.

After import
------------

After an import completes, update the relevant method in `/Model/SegmentData.php` in County Profiles with the appropriate new year.