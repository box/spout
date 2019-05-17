<?php

namespace Box\Spout\Writer\Common\Creator;

use Box\Spout\Common\Creator\HelperFactory;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Helper\GlobalFunctionsHelper;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\CSV\Manager\OptionsManager as CSVOptionsManager;
use Box\Spout\Writer\CSV\Writer as CSVWriter;
use Box\Spout\Writer\ODS\Creator\HelperFactory as ODSHelperFactory;
use Box\Spout\Writer\ODS\Creator\ManagerFactory as ODSManagerFactory;
use Box\Spout\Writer\ODS\Manager\OptionsManager as ODSOptionsManager;
use Box\Spout\Writer\ODS\Writer as ODSWriter;
use Box\Spout\Writer\WriterInterface;
use Box\Spout\Writer\XLSX\Creator\HelperFactory as XLSXHelperFactory;
use Box\Spout\Writer\XLSX\Creator\ManagerFactory as XLSXManagerFactory;
use Box\Spout\Writer\XLSX\Manager\OptionsManager as XLSXOptionsManager;
use Box\Spout\Writer\XLSX\Writer as XLSXWriter;

/**
 * Class WriterFactory
 * This factory is used to create writers, based on the type of the file to be read.
 * It supports CSV, XLSX and ODS formats.
 */
class WriterFactory
{
    /**
     * Map file extensions to reader types
     * @var array
     */
    private static $extensionReaderMap = [
        'csv' => Type::CSV,
        'ods' => Type::ODS,
        'xlsx' => Type::XLSX,
    ];

    /**
     * This creates an instance of the appropriate writer, given the type of the file to be read
     *
     * @param  string $writerType Type of the writer to instantiate
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @return WriterInterface
     */
    public static function create($writerType)
    {
        switch ($writerType) {
            case Type::CSV: return self::getCSVWriter();
            case Type::XLSX: return self::getXLSXWriter();
            case Type::ODS: return self::getODSWriter();
            default:
                throw new UnsupportedTypeException('No writers supporting the given type: ' . $writerType);
        }
    }

    /**
     * This creates an instance of the appropriate writer, given the extension of the file to be written
     *
     * @param string $path The path to the spreadsheet file. Supported extensions are .csv,.ods and .xlsx
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @return WriterInterface
     */
    public static function createFromFile(string $path)
    {
        if (!is_file($path)) {
            throw new IOException(
                sprintf('Could not open "%s" for reading! File does not exist.', $path)
            );
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        $readerType = self::$extensionReaderMap[$ext] ?? null;
        if ($readerType === null) {
            throw new UnsupportedTypeException(
                sprintf('No readers supporting the file extension "%s".', $ext)
            );
        }

        return self::create($readerType);
    }

    /**
     * @return CSVWriter
     */
    private static function getCSVWriter()
    {
        $optionsManager = new CSVOptionsManager();
        $globalFunctionsHelper = new GlobalFunctionsHelper();

        $helperFactory = new HelperFactory();

        return new CSVWriter($optionsManager, $globalFunctionsHelper, $helperFactory);
    }

    /**
     * @return XLSXWriter
     */
    private static function getXLSXWriter()
    {
        $styleBuilder = new StyleBuilder();
        $optionsManager = new XLSXOptionsManager($styleBuilder);
        $globalFunctionsHelper = new GlobalFunctionsHelper();

        $helperFactory = new XLSXHelperFactory();
        $managerFactory = new XLSXManagerFactory(new InternalEntityFactory(), $helperFactory);

        return new XLSXWriter($optionsManager, $globalFunctionsHelper, $helperFactory, $managerFactory);
    }

    /**
     * @return ODSWriter
     */
    private static function getODSWriter()
    {
        $styleBuilder = new StyleBuilder();
        $optionsManager = new ODSOptionsManager($styleBuilder);
        $globalFunctionsHelper = new GlobalFunctionsHelper();

        $helperFactory = new ODSHelperFactory();
        $managerFactory = new ODSManagerFactory(new InternalEntityFactory(), $helperFactory);

        return new ODSWriter($optionsManager, $globalFunctionsHelper, $helperFactory, $managerFactory);
    }
}
