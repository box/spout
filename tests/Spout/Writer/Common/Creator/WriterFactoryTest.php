<?php

namespace Box\Spout\Writer\Common\Creator;

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

        (new WriterFactory())->create('unsupportedType');
    }
}
