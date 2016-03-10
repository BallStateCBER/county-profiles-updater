<?php
namespace App\Model\Table;

use App\Model\Entity\DataCategory;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * DataCategories Model
 *
 * @property \Cake\ORM\Association\BelongsTo $ParentDataCategories
 * @property \Cake\ORM\Association\HasMany $ChildDataCategories
 */
class DataCategoriesTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('data_categories');
        $this->displayField('name');
        $this->primaryKey('id');

        $this->addBehavior('Tree');

        $this->belongsTo('ParentDataCategories', [
            'className' => 'DataCategories',
            'foreignKey' => 'parent_id'
        ]);
        $this->hasMany('ChildDataCategories', [
            'className' => 'DataCategories',
            'foreignKey' => 'parent_id'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->add('id', 'valid', ['rule' => 'numeric'])
            ->allowEmpty('id', 'create');

        $validator
            ->requirePresence('name', 'create')
            ->notEmpty('name');

        $validator
            ->requirePresence('store_type', 'create')
            ->notEmpty('store_type');

        $validator
            ->requirePresence('display_type', 'create')
            ->notEmpty('display_type');

        $validator
            ->add('display_precision', 'valid', ['rule' => 'numeric'])
            ->requirePresence('display_precision', 'create')
            ->notEmpty('display_precision');

        $validator
            ->add('lft', 'valid', ['rule' => 'numeric'])
            ->requirePresence('lft', 'create')
            ->notEmpty('lft');

        $validator
            ->add('rght', 'valid', ['rule' => 'numeric'])
            ->requirePresence('rght', 'create')
            ->notEmpty('rght');

        $validator
            ->add('is_group', 'valid', ['rule' => 'boolean'])
            ->requirePresence('is_group', 'create')
            ->notEmpty('is_group');

        $validator
            ->requirePresence('notes', 'create')
            ->notEmpty('notes');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['parent_id'], 'ParentDataCategories'));
        return $rules;
    }
}
