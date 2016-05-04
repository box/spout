<?php

include_once 'src/Spout/Autoloader/autoload.php';

use Box\Spout\Common\Type;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\Style\Color;

# UNIX_DATE = (EXCEL_DATE - 25569) * 86400              : Excel -> unix
# EXCEL_DATE = 25569 + (UNIX_DATE / 86400)              : Unix -> excel

$test = function ($name, $sheets, $rows, $cols, $groupedRows = FALSE) {
    $start = microtime(TRUE);
    $writer = WriterFactory::create(Type::XLSX); // for XLSX files
    $writer->openToFile('__'.$name.'.xlsx');

    $styleDef = (new StyleBuilder())->setNumberFormat('0.00000')->build();
    $styleRed = (new StyleBuilder())->setNumberFormat('[Red]0.00000')->build();
    $styleDate = (new StyleBuilder())->setNumberFormat('d-mmm-YY HH:mm:ss')->build();
    $writer->registerStyle($styleDef);
    $writer->registerStyle($styleRed);
    $writer->registerStyle($styleDate);

    for ($sheetNum = 0; $sheetNum < $sheets; $sheetNum++) {
        if ($sheetNum != 0) {
            $sheet = $writer->addNewSheetAndMakeItCurrent();
        } else {
            $sheet = $writer->getCurrentSheet();
        }
        $sheet->setName('data'.$sheetNum);

        $rowsArr = array();
        for ($rowNum = 0; $rowNum < $rows; $rowNum++) {
            $row = array();
            for ($colNum = 0; $colNum < $cols; $colNum++) {
                switch ($colNum % 6) {
                    default:
                        $row[] = $colNum;
                    break;
                    case 1:
                    case 3:
                        $row[] = array($colNum, $styleDef);
                    break;
                    case 2:
                    case 4:
                        $row[] = array($colNum, $styleRed);
                    break;
                    case 5:
                        $row[] = array(25569 + (time() / 86400), $styleDate);
                    break;
                }
            }

            $rowsArr[] = $row;
            if (!is_int($groupedRows)) {
                $writer->addRow($row);
                $rowsArr = array();
            } else if (count($rowsArr) >= $groupedRows) {
                $writer->addRows($rowsArr);
                $rowsArr = array();
            }
        }

        if (count($rowsArr)) {
            $writer->addRows($rowsArr);
        }
    }

    $writer->close();

    $duration = number_format(microtime(TRUE) - $start, 2);
    $str = sprintf(' %s took %s seconds to run (%d r/%d c in %d sheets)', $name, $duration, $rows, $cols, $sheets);
    echo $str . PHP_EOL;
};

/**
 * Bottleneck ATM: Disk usage
 */

$start_mem = memory_get_usage(TRUE);
echo PHP_EOL."Memory Consumption is ";
echo round($start_mem/1048576,2).''.' MB'.PHP_EOL;

$test('1_mini_grouped_00000', 1, 50, 25, FALSE);

exit (0);

$cur_mem = memory_get_usage(TRUE);
echo " Current Consumption is ";
echo round($cur_mem/1048576,2).''.' MB'.PHP_EOL;

$test('2_small_grouped_00000', 10, 7500, 50, FALSE);

$cur_mem = memory_get_usage(TRUE);
echo " Current Consumption is ";
echo round($cur_mem/1048576,2).''.' MB'.PHP_EOL;

$test('3_medium_grouped_00000', 10, 7500, 500, FALSE);

$cur_mem = memory_get_usage(TRUE);
echo " Current Consumption is ";
echo round($cur_mem/1048576,2).''.' MB'.PHP_EOL;

$test('4_large_grouped_00000', 10, 150000, 10000, FALSE);

$cur_mem = memory_get_usage(TRUE);
echo " Current Consumption is ";
echo round($cur_mem/1048576,2).''.' MB'.PHP_EOL;

echo " Peak Consumption is ";
echo round(memory_get_peak_usage(TRUE)/1048576,2).''.' MB'.PHP_EOL;

exit(0);