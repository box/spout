<?php

namespace Box\Spout\Reader\Common\Creator;

use Box\Spout\Common\Exception\UnsupportedTypeException;
use PHPUnit\Framework\TestCase;

/**
 * Class ReaderFactoryTest
 */
class ReaderFactoryTest extends TestCase
{
    /**
     * @return void
     */
    public function testCreateReaderShouldThrowWithUnsupportedType()
    {
        $this->expectException(UnsupportedTypeException::class);

        ReaderFactory::create('unsupportedType');
    }
}
