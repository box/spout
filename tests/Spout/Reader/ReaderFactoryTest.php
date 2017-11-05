<?php

namespace Box\Spout\Reader;

use Box\Spout\Common\Exception\UnsupportedTypeException;

/**
 * Class ReaderFactoryTest
 */
class ReaderFactoryTest extends \PHPUnit_Framework_TestCase
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
