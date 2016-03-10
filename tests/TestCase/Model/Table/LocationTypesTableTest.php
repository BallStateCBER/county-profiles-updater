<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\LocationTypesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\LocationTypesTable Test Case
 */
class LocationTypesTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \App\Model\Table\LocationTypesTable
     */
    public $LocationTypes;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.location_types'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('LocationTypes') ? [] : ['className' => 'App\Model\Table\LocationTypesTable'];
        $this->LocationTypes = TableRegistry::get('LocationTypes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->LocationTypes);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
