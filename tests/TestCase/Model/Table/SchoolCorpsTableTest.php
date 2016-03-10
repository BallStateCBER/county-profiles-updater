<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SchoolCorpsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SchoolCorpsTable Test Case
 */
class SchoolCorpsTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \App\Model\Table\SchoolCorpsTable
     */
    public $SchoolCorps;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.school_corps',
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
        'app.tax_districts',
        'app.townships'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('SchoolCorps') ? [] : ['className' => 'App\Model\Table\SchoolCorpsTable'];
        $this->SchoolCorps = TableRegistry::get('SchoolCorps', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->SchoolCorps);

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
