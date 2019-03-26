<?php

namespace Box\Spout\Writer\Common\Creator;

use Box\Spout\Common\Creator\HelperFactory;
use Box\Spout\Common\Entity\Cell;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Entity\Style\Style;
use Box\Spout\Common\Helper\GlobalFunctionsHelper;
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
 * Class WriterEntityFactory
 * Factory to create external entities
 */
class WriterEntityFactory
{
    /**
     * This creates an instance of the appropriate writer, given the type of the file to be written
     *
     * @param  string $writerType Type of the writer to instantiate
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @return WriterInterface
     */
    public static function createWriter($writerType)
    {
        return (new WriterFactory())->create($writerType);
    }

    /**
     * @return \Box\Spout\Writer\CSV\Writer
     */
    public static function createCSVWriter()
    {
        $optionsManager = new CSVOptionsManager();
        $globalFunctionsHelper = new GlobalFunctionsHelper();

        $helperFactory = new HelperFactory();

        return new CSVWriter($optionsManager, $globalFunctionsHelper, $helperFactory);
    }

    /**
     * @return \Box\Spout\Writer\XLSX\Writer
     */
    public static function createXLSXWriter()
    {
        $styleBuilder = new StyleBuilder();
        $optionsManager = new XLSXOptionsManager($styleBuilder);
        $globalFunctionsHelper = new GlobalFunctionsHelper();

        $helperFactory = new XLSXHelperFactory();
        $managerFactory = new XLSXManagerFactory(new InternalEntityFactory(), $helperFactory);

        return new XLSXWriter($optionsManager, $globalFunctionsHelper, $helperFactory, $managerFactory);
    }

    /**
     * @return \Box\Spout\Writer\ODS\Writer
     */
    public static function createODSWriter()
    {
        $styleBuilder = new StyleBuilder();
        $optionsManager = new ODSOptionsManager($styleBuilder);
        $globalFunctionsHelper = new GlobalFunctionsHelper();

        $helperFactory = new ODSHelperFactory();
        $managerFactory = new ODSManagerFactory(new InternalEntityFactory(), $helperFactory);

        return new ODSWriter($optionsManager, $globalFunctionsHelper, $helperFactory, $managerFactory);
    }


    /**
     * @param Cell[] $cells
     * @param Style|null $rowStyle
     * @return Row
     */
    public static function createRow(array $cells = [], Style $rowStyle = null)
    {
        return new Row($cells, $rowStyle);
    }

    /**
     * @param array $cellValues
     * @param Style|null $rowStyle
     * @return Row
     */
    public static function createRowFromArray(array $cellValues = [], Style $rowStyle = null)
    {
        $cells = array_map(function ($cellValue) {
            return new Cell($cellValue);
        }, $cellValues);

        return new Row($cells, $rowStyle);
    }

    /**
     * @param mixed $cellValue
     * @param Style|null $cellStyle
     * @return Cell
     */
    public static function createCell($cellValue, Style $cellStyle = null)
    {
        return new Cell($cellValue, $cellStyle);
    }
}
