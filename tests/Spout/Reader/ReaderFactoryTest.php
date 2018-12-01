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

    public function testCreateCsvReaderShouldProvideACsvReader()
    {
        $reader = ReaderFactory::createCsvReader();

        $this->assertInstanceOf('Box\Spout\Reader\CSV\Reader', $reader);
    }

    public function testCreateXlsxReaderShouldProvideAXlsxReader()
    {
        $reader = ReaderFactory::createXlsxReader();

        $this->assertInstanceOf('Box\Spout\Reader\XLSX\Reader', $reader);
    }

    public function testCreateOdsReaderShouldProvideAOdsReader()
    {
        $reader = ReaderFactory::createOdsReader();

        $this->assertInstanceOf('Box\Spout\Reader\ODS\Reader', $reader);
    }
}
