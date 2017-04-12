<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Statistics Model
 *
 * @property \Cake\ORM\Association\BelongsTo $LocationTypes
 * @property \Cake\ORM\Association\BelongsTo $DataCategories
 * @property \Cake\ORM\Association\BelongsTo $Sources
 */
class StatisticsTable extends Table
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

        $this->setTable('statistics');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('LocationTypes', [
            'foreignKey' => 'loc_type_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('DataCategories', [
            'foreignKey' => 'category_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('Sources', [
            'foreignKey' => 'source_id',
            'joinType' => 'INNER'
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
            ->add('survey_date', 'valid', ['rule' => 'numeric'])
            ->requirePresence('survey_date', 'create')
            ->notEmpty('survey_date');

        $validator
            ->add('value', 'valid', ['rule' => 'decimal'])
            ->requirePresence('value', 'create')
            ->notEmpty('value');

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
        $rules->add($rules->existsIn(['loc_type_id'], 'LocationTypes'));
        $rules->add($rules->existsIn(['category_id'], 'DataCategories'));
        $rules->add($rules->existsIn(['source_id'], 'Sources'));

        return $rules;
    }
}
