<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TownshipsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TownshipsTable Test Case
 */
class TownshipsTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \App\Model\Table\TownshipsTable
     */
    public $Townships;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.townships',
        'app.counties',
        'app.states',
        'app.county_seats',
        'app.cities',
        'app.county_description_sources',
        'app.county_pic_captions',
        'app.county_websites',
        'app.ibt_detail',
        'app.photos',
        'app.rptec_multipliers',
        'app.rptemployment_multipliers',
        'app.rptibt_multipliers',
        'app.rptoutput_multipliers',
        'app.school_corps',
        'app.tax_districts',
        'app.dlgf_districts'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Townships') ? [] : ['className' => 'App\Model\Table\TownshipsTable'];
        $this->Townships = TableRegistry::get('Townships', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Townships);

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
