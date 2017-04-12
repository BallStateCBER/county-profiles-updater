<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * DataCategory Entity.
 *
 * @property int $id
 * @property string $name
 * @property string $store_type
 * @property string $display_type
 * @property int $display_precision
 * @property int $parent_id
 * @property int $lft
 * @property int $rght
 * @property bool $is_group
 * @property string $notes
 */
class DataCategory extends Entity
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
