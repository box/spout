<?php

namespace Box\Spout\Reader;

/**
 * Class ReaderFactoryTest
 *
 * @package Box\Spout\Writer
 */
class ReaderFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Box\Spout\Common\Exception\UnsupportedTypeException
     *
     * @return void
     */
    public function testCreateReaderShouldThrowWithUnsupportedType()
    {
        ReaderFactory::create('unsupportedType');
    }
}
