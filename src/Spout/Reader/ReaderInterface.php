<?php

namespace Box\Spout\Reader;

/**
 * Interface ReaderInterface
 *
 * @package Box\Spout\Reader
 */
interface ReaderInterface
{
    /**
     * Prepares the reader to read the given file. It also makes sure
     * that the file exists and is readable.
     *
     * @param  string $filePath Path of the file to be read
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException
     */
    public function open($filePath);

    /**
     * Returns whether all rows have been read (i.e. if we are at the end of the file).
     * To know if the end of file has been reached, it uses a buffer. If the buffer is
     * empty (meaning, nothing has been read or previous read line has been consumed), then
     * it reads the next line, store it in the buffer for the next time or flip a variable if
     * the end of file has been reached.
     *
     * @return bool
     * @throws \Box\Spout\Common\Exception\IOException If the stream was not opened first
     */
    public function hasNextRow();

    /**
     * Returns next row if available. The row is either retrieved from the buffer if it is not empty or fetched by
     * actually reading the file.
     *
     * @return array Array that contains the data for the read row
     * @throws \Box\Spout\Common\Exception\IOException If the stream was not opened first
     * @throws \Box\Spout\Reader\Exception\EndOfFileReachedException
     */
    public function nextRow();

    /**
     * Closes the reader, preventing any additional reading
     *
     * @return void
     */
    public function close();
}
