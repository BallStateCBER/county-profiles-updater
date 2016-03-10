<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SourcesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SourcesTable Test Case
 */
class SourcesTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \App\Model\Table\SourcesTable
     */
    public $Sources;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.sources',
        'app.statistics'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Sources') ? [] : ['className' => 'App\Model\Table\SourcesTable'];
        $this->Sources = TableRegistry::get('Sources', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Sources);

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
