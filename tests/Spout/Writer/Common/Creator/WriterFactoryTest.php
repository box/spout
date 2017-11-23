<?php

namespace Box\Spout\Writer\Common\Creator;

use Box\Spout\Common\Exception\UnsupportedTypeException;
use PHPUnit\Framework\TestCase;

/**
 * Class WriterFactoryTest
 */
class WriterFactoryTest extends TestCase
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
