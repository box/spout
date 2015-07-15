<?php

namespace Box\Spout\Reader;

/**
 * Interface ReaderInterface2
 *
 * @package Box\Spout\Reader
 */
interface ReaderInterface2
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
     * Returns an iterator to iterate over sheets.
     *
     * @return \Iterator To iterate over sheets
     */
    public function getSheetIterator();

    /**
     * Closes the reader, preventing any additional reading
     *
     * @return void
     */
    public function close();
}
