<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TaxDistrict Entity.
 *
 * @property int $id
 * @property string $name
 * @property string $corrected_name
 * @property int $county_id
 * @property \App\Model\Entity\County $county
 * @property int $dlgf_district_id
 * @property \App\Model\Entity\DlgfDistrict $dlgf_district
 */
class TaxDistrict extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
