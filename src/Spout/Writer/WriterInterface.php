<?php

namespace Box\Spout\Writer;

use Box\Spout\Writer\Common\Entity\Style\Style;

/**
 * Interface WriterInterface
 *
 * @package Box\Spout\Writer
 */
interface WriterInterface
{
    /**
     * Inits the writer and opens it to accept data.
     * By using this method, the data will be written to a file.
     *
     * @param  string $outputFilePath Path of the output file that will contain the data
     * @return WriterInterface
     * @throws \Box\Spout\Common\Exception\IOException If the writer cannot be opened or if the given path is not writable
     */
    public function openToFile($outputFilePath);

    /**
     * Inits the writer and opens it to accept data.
     * By using this method, the data will be outputted directly to the browser.
     *
     * @param  string $outputFileName Name of the output file that will contain the data. If a path is passed in, only the file name will be kept
     * @return WriterInterface
     * @throws \Box\Spout\Common\Exception\IOException If the writer cannot be opened
     */
    public function openToBrowser($outputFileName);

    /**
     * Write given data to the output. New data will be appended to end of stream.
     *
     * @param array|\Box\Spout\Writer\Common\Entity\Row $row The row to be appended to the stream
     * @return WriterInterface
     * @internal param array $row Array containing data to be streamed.
     *          Example $row= ['data1', 1234, null, '', 'data5'];
     * @internal param \Box\Spout\Writer\Common\Entity\Row $row A Row object with cells and styles
     *          Example $row = (new Row())->addCell('data1');
     */
    public function addRow($row);

    /**
     * Write given data to the output and apply the given style.
     * @see addRow
     *
     * @param array|\Box\Spout\Writer\Common\Entity\Row $row The row to be appended to the stream
     * @param Style $style Style to be applied to the row.
     * @return WriterInterface
     * @internal param array $row Array containing data to be streamed.
     *          Example $row= ['data1', 1234, null, '', 'data5'];
     * @internal param \Box\Spout\Writer\Common\Entity\Row $row A Row object with cells and styles
     *          Example $row = (new Row())->addCell('data1');
     */
    public function addRowWithStyle($row, $style);

    /**
     * Write given data to the output with a closure funtion. New data will be appended to end of stream.
     *
     * @param \Closure $callback A callback returning a Row object. A new Row object is injected into the callback.
     * @return WriterInterface
     * @internal param \Closure $callback
     *          Example withRow(function(Row $row) { return $row->addCell('data1'); })
     */
    public function withRow(\Closure $callback);

    /**
     * Write given data to the output. New data will be appended to end of stream.
     *
     * @param  array $dataRows Array of array containing data to be streamed.
     *          Example $dataRow = [
     *              ['data11', 12, , '', 'data13'],
     *              ['data21', 'data22', null],
     *          ];
     * @return WriterInterface
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException If the input param is not valid
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException If the writer has not been opened yet
     * @throws \Box\Spout\Common\Exception\IOException If unable to write data
     */
    public function addRows(array $dataRows);

    /**
     * Write given data to the output and apply the given style.
     * @see addRows
     *
     * @param array $dataRows Array of array containing data to be streamed.
     * @param Style $style Style to be applied to the rows.
     * @return WriterInterface
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException If the input param is not valid
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException If this function is called before opening the writer
     * @throws \Box\Spout\Common\Exception\IOException If unable to write data
     */
    public function addRowsWithStyle(array $dataRows, $style);

    /**
     * Closes the writer. This will close the streamer as well, preventing new data
     * to be written to the file.
     *
     * @return void
     */
    public function close();
}
