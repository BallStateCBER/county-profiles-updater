<?php
namespace App\Location;

use Cake\Cache\Cache;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

/**
 * A shared interface for the various location classes (city, county, state, etc.)
 */
class Location
{

    /**
     * Returns the ID of a location (city, county, etc.)
     * based on the location type and code (FIPS, district ID, etc.)
     *
     * @param string $locCode Location code (FIPS, district ID, etc.)
     * @param string $locTypeId Location type ID (2: county, 3: state, etc.)
     * @return int
     * @throws NotFoundException
     */
    public function getIdFromCode($locCode, $locTypeId)
    {
        $cacheKey = 'getIdFromCode(';
        $cacheKey .= is_array($locCode) ? implode(',', $locCode) : $locCode;
        $cacheKey .= ", $locTypeId)";
        $cached = Cache::read($cacheKey);
        if ($cached) {
            return $cached;
        }

        switch ($locTypeId) {
            case 2: // county
                $tableName = 'Counties';
                $conditions = [
                    'fips' => $locCode
                ];
                break;
            case 3: // state
                $tableName = 'States';
                $conditions = [
                    'fips' => $locCode
                ];
                break;
            case 4: // country, assumed to be USA
                Cache::write($cacheKey, 1);

                return 1;
            case 5: // tax district
                list($dlgfFistrictId, $countyFips) = $locCode;
                $countiesTables = TableRegistry::get('Counties');
                $result = $countiesTables->find('all')
                    ->select(['id'])
                    ->where(['fips' => $countyFips])
                    ->first();
                $countyId = $result ? $result->id : null;
                $tableName = 'TaxDistricts';
                $conditions = [
                    'dlgf_districtId' => $dlgfFistrictId,
                    'countyId' => $countyId
                ];
                break;
            case 6: // school corporation
                $tableName = 'SchoolCorps';
                $conditions = [
                    'corp_no' => $locCode,
                ];
                break;
            default:
                throw new NotFoundException("Location type ID $locTypeId not recognized");
        }

        $table = TableRegistry::get($tableName);
        $result = $table->find('all')
            ->select(['id'])
            ->where($conditions)
            ->first();

        if (empty($result)) {
            $msg = 'Location matching conditions ' . print_r($conditions, true) .
                ' not found in ' . $tableName . ' table';
            throw new NotFoundException($msg);
        }

        $locId = $result->id;

        if ($locId) {
            Cache::write($cacheKey, $locId);

            return $locId;
        }

        throw new NotFoundException("Location with type $locTypeId and code $locCode not found");
    }
}
