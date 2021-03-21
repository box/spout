<?php

namespace Box\Spout\Common\Manager;

use PHPUnit\Framework\TestCase;

/**
 * Class OptionsManagerTest
 */
class OptionsManagerTest extends TestCase
{
    /**
     * @var OptionsManagerAbstract
     */
    protected $optionsManager;

    protected function setUp(): void
    {
        $this->optionsManager = new class() extends OptionsManagerAbstract {
            protected function getSupportedOptions()
            {
                return [
                    'foo',
                    'bar',
                    'baz',
                ];
            }

            protected function setDefaultOptions()
            {
                $this->setOption('foo', 'foo-val');
                $this->setOption('bar', false);
            }
        };
        parent::setUp();
    }

    /**
     * @return void
     */
    public function testOptionsManagerShouldReturnDefaultOptionsIfNothingSet()
    {
        $optionsManager = $this->optionsManager;
        $this->assertEquals('foo-val', $optionsManager->getOption('foo'));
        $this->assertFalse($optionsManager->getOption('bar'));
    }

    /**
     * @return void
     */
    public function testOptionsManagerShouldReturnUpdatedOptionValue()
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->setOption('foo', 'new-val');
        $this->assertEquals('new-val', $optionsManager->getOption('foo'));
    }

    /**
     * @return void
     */
    public function testOptionsManagerShouldReturnNullIfNoDefaultValueSet()
    {
        $optionsManager = $this->optionsManager;
        $this->assertNull($optionsManager->getOption('baz'));
    }

    /**
     * @return void
     */
    public function testOptionsManagerShouldReturnNullIfNoOptionNotSupported()
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->setOption('not-supported', 'something');
        $this->assertNull($optionsManager->getOption('not-supported'));
    }

    /**
     * @return void
     */
    public function testOptionManagerShouldReturnArrayIfListOptionsAdded()
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->addOption('bar', 'something');
        $optionsManager->addOption('bar', 'something-else');
        $this->assertIsArray($optionsManager->getOption('bar'));
        $this->assertCount(2, $optionsManager->getOption('bar'));
        $this->assertEquals('something', $optionsManager->getOption('bar')[0]);
        $this->assertEquals('something-else', $optionsManager->getOption('bar')[1]);
    }

    /**
     * @return void
     */
    public function testOptionsManagerShouldReturnNullIfListOptionNotSupported()
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->addOption('not-supported', 'something');
        $this->assertNull($optionsManager->getOption('not-supported'));
    }
}
