<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Statistic Entity.
 *
 * @property int $id
 * @property int $loc_type_id
 * @property \App\Model\Entity\LocationType $location_type
 * @property int $loc_id
 * @property int $survey_date
 * @property int $category_id
 * @property \App\Model\Entity\DataCategory $data_category
 * @property float $value
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $modified
 * @property int $source_id
 * @property \App\Model\Entity\Source $source
 */
class Statistic extends Entity
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
