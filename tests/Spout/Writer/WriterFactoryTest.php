<?php

namespace Box\Spout\Writer;

use Box\Spout\Common\Exception\UnsupportedTypeException;

/**
 * Class WriterFactoryTest
 */
class WriterFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return void
     */
    public function testCreateWriterShouldThrowWithUnsupportedType()
    {
        $this->expectException(UnsupportedTypeException::class);

        WriterFactory::create('unsupportedType');
    }
}
