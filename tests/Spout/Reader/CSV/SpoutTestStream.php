<?php

namespace Box\Spout\Reader\CSV;

/**
 * Class SpoutTestStream
 * Custom stream that reads CSV files located in the tests/resources/csv folder.
 * For example: spout://foobar will point to tests/resources/csv/foobar.csv
 */
class SpoutTestStream
{
    const CLASS_NAME = __CLASS__;

    const PATH_TO_CSV_RESOURCES = 'tests/resources/csv/';
    const CSV_EXTENSION = '.csv';

    /** @var int */
    private $position;

    /** @var resource */
    private $fileHandle;

    /**
     * @param string $path
     * @param int $flag
     * @return array
     */
    public function url_stat($path, $flag)
    {
        $filePath = $this->getFilePathFromStreamPath($path);

        return stat($filePath);
    }

    /**
     * @param string $streamPath
     * @return string
     */
    private function getFilePathFromStreamPath($streamPath)
    {
        $fileName = parse_url($streamPath, PHP_URL_HOST);

        return self::PATH_TO_CSV_RESOURCES . $fileName . self::CSV_EXTENSION;
    }

    /**
     * @param string $path
     * @param string $mode
     * @param int $options
     * @param string $opened_path
     * @return bool
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->position = 0;

        // the path is like "spout://csv_name" so the actual file name correspond the name of the host.
        $filePath = $this->getFilePathFromStreamPath($path);
        $this->fileHandle = fopen($filePath, $mode);

        return true;
    }

    /**
     * @param int $numBytes
     * @return string
     */
    public function stream_read($numBytes)
    {
        $this->position += $numBytes;

        return fread($this->fileHandle, $numBytes);
    }

    /**
     * @return int
     */
    public function stream_tell()
    {
        return $this->position;
    }

    /**
     * @param int $offset
     * @param int $whence
     * @return bool
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        $result = fseek($this->fileHandle, $offset, $whence);
        if ($result === -1) {
            return false;
        }

        if ($whence === SEEK_SET) {
            $this->position = $offset;
        } elseif ($whence === SEEK_CUR) {
            $this->position += $offset;
        } else {
            // not implemented
        }

        return true;
    }

    /**
     * @return bool
     */
    public function stream_close()
    {
        return fclose($this->fileHandle);
    }

    /**
     * @return bool
     */
    public function stream_eof()
    {
        return feof($this->fileHandle);
    }
}
