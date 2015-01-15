<?php

namespace Box\Spout\Common\Helper;

/**
 * Class GlobalFunctionsHelper
 * This class wraps global functions to facilitate testing
 *
 * @package Box\Spout\Common\Helper
 */
class GlobalFunctionsHelper
{
    /**
     * Wrapper around global function fopen()
     * @see fopen()
     *
     * @param string $fileName
     * @param string $mode
     * @return resource|bool
     */
    public function fopen($fileName, $mode)
    {
        return fopen($fileName, $mode);
    }

    /**
     * Wrapper around global function fgets()
     * @see fgets()
     *
     * @param resource $handle
     * @param int|void $length
     * @return string
     */
    public function fgets($handle, $length = null)
    {
        return fgets($handle, $length);
    }

    /**
     * Wrapper around global function fputs()
     * @see fputs()
     *
     * @param resource $handle
     * @param string $string
     * @return int
     */
    public function fputs($handle, $string)
    {
        return fputs($handle, $string);
    }

    /**
     * Wrapper around global function fflush()
     * @see fflush()
     *
     * @param resource $handle
     * @return bool
     */
    public function fflush($handle)
    {
        return fflush($handle);
    }

    /**
     * Wrapper around global function fseek()
     * @see fseek()
     *
     * @param resource $handle
     * @param int $offset
     * @return int
     */
    public function fseek($handle, $offset)
    {
        return fseek($handle, $offset);
    }

    /**
     * Wrapper around global function fgetcsv()
     * @see fgetcsv()
     *
     * @param resource $handle
     * @param int|void $length
     * @param string|void $delimiter
     * @param string|void $enclosure
     * @return array
     */
    public function fgetcsv($handle, $length = null, $delimiter = null, $enclosure = null)
    {
        return fgetcsv($handle, $length, $delimiter, $enclosure);
    }

    /**
     * Wrapper around global function fclose()
     * @see fclose()
     *
     * @param resource $handle
     * @return bool
     */
    public function fclose($handle)
    {
        return fclose($handle);
    }

    /**
     * Wrapper around global function rewind()
     * @see rewind()
     *
     * @param resource $handle
     * @return bool
     */
    public function rewind($handle)
    {
        return rewind($handle);
    }

    /**
     * Wrapper around global function file_exists()
     * @see file_exists()
     *
     * @param string $filename
     * @return bool
     */
    public function file_exists($fileName)
    {
        return file_exists($fileName);
    }

    /**
     * Wrapper around global function is_readable()
     * @see is_readable()
     *
     * @param string $filename
     * @return bool
     */
    public function is_readable($fileName)
    {
        return is_readable($fileName);
    }

    /**
     * Wrapper around global function basename()
     * @see basename()
     *
     * @param string $path
     * @return string
     */
    public function basename($path)
    {
        return basename($path);
    }

    /**
     * Wrapper around global function header()
     * @see header()
     *
     * @param string $string
     * @return void
     */
    public function header($string)
    {
        header($string);
    }
}
