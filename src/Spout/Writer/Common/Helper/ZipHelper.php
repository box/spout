<?php

namespace Box\Spout\Writer\Common\Helper;

/**
 * Class ZipHelper
 * This class provides helper functions to create zip files
 *
 * @package Box\Spout\Writer\Common\Helper
 */
class ZipHelper
{
    const ZIP_EXTENSION = '.zip';

    /**
     * Zips the root folder and streams the contents of the zip into the given stream
     *
     * @param string $folderPath Path to the folder to be zipped
     * @param resource $streamPointer Pointer to the stream to copy the zip
     * @return void
     */
    public function zipFolderAndCopyToStream($folderPath, $streamPointer)
    {
        $zipFilePath = $this->getZipFilePath($folderPath);
        $this->zipFolder($folderPath, $zipFilePath);
        $this->copyZipToStream($zipFilePath, $streamPointer);
    }

    /**
     * @param string $folderPathToZip Path to the folder to be zipped
     * @return string Path where the zip file of the given folder will be created
     */
    public function getZipFilePath($folderPathToZip)
    {
        return $folderPathToZip . self::ZIP_EXTENSION;
    }

    /**
     * Zips the given folder
     *
     * @param string $folderPath Path of the folder to be zipped
     * @param string $destinationPath Path where the zip file will be created
     * @return void
     */
    public function zipFolder($folderPath, $destinationPath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($destinationPath, \ZipArchive::CREATE)) {
            $this->addFolderToZip($zip, $folderPath);
            $zip->close();
        }
    }

    /**
     * @param \ZipArchive $zip
     * @param string $folderPath Path of the folder to add to the zip
     * @return void
     */
    protected function addFolderToZip($zip, $folderPath)
    {
        $folderRealPath = $this->getNormalizedRealPath($folderPath) . '/';
        $itemIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($itemIterator as $itemInfo) {
            $itemRealPath = $this->getNormalizedRealPath($itemInfo->getPathname());
            $itemLocalPath = str_replace($folderRealPath, '', $itemRealPath);

            if ($itemInfo->isFile()) {
                $zip->addFile($itemRealPath, $itemLocalPath);
            } else if ($itemInfo->isDir()) {
                $zip->addEmptyDir($itemLocalPath);
            }
        }
    }

    /**
     * Returns canonicalized absolute pathname, containing only forward slashes.
     *
     * @param string $path Path to normalize
     * @return string Normalized and canonicalized path
     */
    protected function getNormalizedRealPath($path)
    {
        $realPath = realpath($path);
        return str_replace(DIRECTORY_SEPARATOR, '/', $realPath);
    }

    /**
     * Streams the contents of the zip file into the given stream
     *
     * @param string $zipFilePath Path to the zip file
     * @param resource $pointer Pointer to the stream to copy the zip
     * @return void
     */
    protected function copyZipToStream($zipFilePath, $pointer)
    {
        $zipFilePointer = fopen($zipFilePath, 'r');
        stream_copy_to_stream($zipFilePointer, $pointer);
        fclose($zipFilePointer);
    }
}
