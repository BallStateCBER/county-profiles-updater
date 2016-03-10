<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\StatisticsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\StatisticsTable Test Case
 */
class StatisticsTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \App\Model\Table\StatisticsTable
     */
    public $Statistics;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.statistics',
        'app.loc_types',
        'app.locs',
        'app.categories',
        'app.sources'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Statistics') ? [] : ['className' => 'App\Model\Table\StatisticsTable'];
        $this->Statistics = TableRegistry::get('Statistics', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Statistics);

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

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
