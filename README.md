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

To fire it up,

    cd C:\path\to\app
    bin\cake import {importName}

If you leave out `importName` (i.e. just enter `bin\cake import`), a menu of available imports will be
presented for you to select from.

The selected import proceeds thusly:

1. Data is pulled from the API
2. This data is checked for errors and it's determined whether this is data that
it needs to **insert** into the database, data that needs to **update** existing records,
or data that is already present in the database and can be **ignored**
3. Assuming there's data to import, the script asks for confirmation to proceed and
for permission to overwrite existing records if appropriate.
4. *MAGIC*

Adding new import methods
-------------------------

Add `import{CategoryName}()` to `src/Shell/ImportShell.php` (e.g. `importPopulationAge()`).
This should set the class properties `locationTypeId`, `surveyDate`, `sourceId`, and `categoryIds`,
then output `'Retrieving data from Census API...'`, then populate `apiCallResults` with the
result of a call to a [CBER Data Grabber](https://github.com/BallStateCBER/cber-data-grabber)
method. The import method should then finish up with a call to `$this->import();`.
