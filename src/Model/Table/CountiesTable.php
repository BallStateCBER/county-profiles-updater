<?php
namespace App\Model\Table;

use App\Model\Entity\County;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Counties Model
 *
 * @property \Cake\ORM\Association\BelongsTo $States
 * @property \Cake\ORM\Association\BelongsTo $CountySeats
 * @property \Cake\ORM\Association\HasMany $Cities
 * @property \Cake\ORM\Association\HasMany $CountyDescriptionSources
 * @property \Cake\ORM\Association\HasMany $CountyPicCaptions
 * @property \Cake\ORM\Association\HasMany $CountyWebsites
 * @property \Cake\ORM\Association\HasMany $IbtDetail
 * @property \Cake\ORM\Association\HasMany $Photos
 * @property \Cake\ORM\Association\HasMany $RptecMultipliers
 * @property \Cake\ORM\Association\HasMany $RptemploymentMultipliers
 * @property \Cake\ORM\Association\HasMany $RptibtMultipliers
 * @property \Cake\ORM\Association\HasMany $RptoutputMultipliers
 * @property \Cake\ORM\Association\HasMany $SchoolCorps
 * @property \Cake\ORM\Association\HasMany $TaxDistricts
 * @property \Cake\ORM\Association\HasMany $Townships
 */
class CountiesTable extends Table
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

        $this->setTable('counties');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('States', [
            'foreignKey' => 'state_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('CountySeats', [
            'foreignKey' => 'county_seat_id',
            'joinType' => 'INNER'
        ]);
        $this->hasMany('Cities', [
            'foreignKey' => 'county_id'
        ]);
        $this->hasMany('CountyDescriptionSources', [
            'foreignKey' => 'county_id'
        ]);
        $this->hasMany('CountyPicCaptions', [
            'foreignKey' => 'county_id'
        ]);
        $this->hasMany('CountyWebsites', [
            'foreignKey' => 'county_id'
        ]);
        $this->hasMany('IbtDetail', [
            'foreignKey' => 'county_id'
        ]);
        $this->hasMany('Photos', [
            'foreignKey' => 'county_id'
        ]);
        $this->hasMany('RptecMultipliers', [
            'foreignKey' => 'county_id'
        ]);
        $this->hasMany('RptemploymentMultipliers', [
            'foreignKey' => 'county_id'
        ]);
        $this->hasMany('RptibtMultipliers', [
            'foreignKey' => 'county_id'
        ]);
        $this->hasMany('RptoutputMultipliers', [
            'foreignKey' => 'county_id'
        ]);
        $this->hasMany('SchoolCorps', [
            'foreignKey' => 'county_id'
        ]);
        $this->hasMany('TaxDistricts', [
            'foreignKey' => 'county_id'
        ]);
        $this->hasMany('Townships', [
            'foreignKey' => 'county_id'
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
            ->requirePresence('county_seat', 'create')
            ->notEmpty('county_seat');

        $validator
            ->add('fips', 'valid', ['rule' => 'numeric'])
            ->requirePresence('fips', 'create')
            ->notEmpty('fips');

        $validator
            ->requirePresence('founded', 'create')
            ->notEmpty('founded');

        $validator
            ->add('square_miles', 'valid', ['rule' => 'numeric'])
            ->requirePresence('square_miles', 'create')
            ->notEmpty('square_miles');

        $validator
            ->requirePresence('description', 'create')
            ->notEmpty('description');

        $validator
            ->requirePresence('slug', 'create')
            ->notEmpty('slug');

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
        $rules->add($rules->existsIn(['state_id'], 'States'));
        $rules->add($rules->existsIn(['county_seat_id'], 'CountySeats'));

        return $rules;
    }
}
