<?php
namespace App\Import;

class CsvImportDefinitions
{
    /**
     * Returns the definitions of each supported import
     *
     * @return array
     */
    public static function getDefinitions()
    {
        $imports = [];

        foreach ($imports as $key => $params) {
            $imports[$key]['type'] = 'csv';
        }

        return $imports;
    }
}
