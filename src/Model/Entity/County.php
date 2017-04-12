<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * County Entity.
 *
 * @property int $id
 * @property int $state_id
 * @property \App\Model\Entity\State $state
 * @property string $name
 * @property int $fips
 * @property string $founded
 * @property int $square_miles
 * @property string $description
 * @property string $slug
 * @property int $county_seat_id
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $modified
 * @property \App\Model\Entity\City[] $cities
 * @property \App\Model\Entity\SchoolCorp[] $school_corps
 * @property \App\Model\Entity\TaxDistrict[] $tax_districts
 * @property \App\Model\Entity\Township[] $townships
 */
class County extends Entity
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
