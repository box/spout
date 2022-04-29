<?php

namespace Box\Spout\Writer\XLSX;


use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Reader\XLSX\Sheet;
use Box\Spout\TestUsingResource;
use Box\Spout\Writer\WriterFactory;

class FormulaTest extends \PHPUnit_Framework_TestCase
{

    use TestUsingResource;

    /**
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     */
    public function testGetXml()
    {
        $fileName = 'test_formula.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterFactory::create(Type::XLSX);
        $writer->openToFile($resourcePath);

        $writer->addRows([
            [1],
            [1],
            [1],
            [1],
        ]);

        $writer->addRow([
            new Formula('SUM(A1:A4)', 4)
        ]);
        $writer->close();


        $reader = ReaderFactory::create(Type::XLSX);
        $reader->open($resourcePath);

        $i = 0;
        /** @var Sheet $sheet */
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $item) {
                $i++;
                if ($i == 5) {
                    static::assertEquals(4, $item[0]);
                }
            }
        }
    }
}