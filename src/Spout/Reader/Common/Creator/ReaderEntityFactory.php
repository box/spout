<?php

namespace Box\Spout\Reader\Common\Creator;

use Box\Spout\Reader\ReaderInterface;

/**
 * Class ReaderEntityFactory
 * Factory to create external entities
 */
class ReaderEntityFactory
{
    /**
     * This creates an instance of the appropriate reader, given the type of the file to be read
     *
     * @param  string $readerType Type of the reader to instantiate
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @return ReaderInterface
     */
    public static function createReader($readerType)
    {
        return (new ReaderFactory())->create($readerType);
    }

    /**
     * Creates a reader by file extension
     *
     * @param string The path to the spreadsheet file. Supported extensions are .csv,.ods and .xlsx
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @return ReaderInterface
     */
    public static function createReaderFromFile(string $path)
    {
        return (new ReaderFactory())->createFromFile($path);
    }
}
