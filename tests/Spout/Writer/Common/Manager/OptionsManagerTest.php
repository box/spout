<?php

namespace Box\Spout\Writer\Common\Manager;

/**
 * Class OptionsManagerTest
 *
 * @package Box\Spout\Writer\Common\Manager
 */
class OptionsManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return void
     */
    public function testOptionsManagerShouldReturnDefaultOptionsIfNothingSet()
    {
        $optionsManager = new FakeOptionsManager();
        $this->assertEquals('foo-val', $optionsManager->getOption('foo'));
        $this->assertEquals(false, $optionsManager->getOption('bar'));
    }

    /**
     * @return void
     */
    public function testOptionsManagerShouldReturnUpdatedOptionValue()
    {
        $optionsManager = new FakeOptionsManager();
        $optionsManager->setOption('foo', 'new-val');
        $this->assertEquals('new-val', $optionsManager->getOption('foo'));
    }

    /**
     * @return void
     */
    public function testOptionsManagerShouldReturnNullIfNoDefaultValueSet()
    {
        $optionsManager = new FakeOptionsManager();
        $this->assertNull($optionsManager->getOption('baz'));
    }

    /**
     * @return void
     */
    public function testOptionsManagerShouldReturnNullIfNoOptionNotSupported()
    {
        $optionsManager = new FakeOptionsManager();
        $optionsManager->setOption('not-supported', 'something');
        $this->assertNull($optionsManager->getOption('not-supported'));
    }
}


// TODO: Convert this to anonymous class when PHP < 7 support is dropped
class FakeOptionsManager extends OptionsManager
{
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
}