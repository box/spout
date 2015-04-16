<?php

namespace Box\Spout\Writer;

/**
 * Class WriterFactoryTest
 *
 * @package Box\Spout\Writer
 */
class WriterFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Box\Spout\Common\Exception\UnsupportedTypeException
     *
     * @return void
     */
    public function testCreateWriterShouldThrowWithUnsupportedType()
    {
        WriterFactory::create('unsupportedType');
    }
}
