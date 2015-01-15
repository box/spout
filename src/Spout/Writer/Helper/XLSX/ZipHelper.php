<?php

namespace Box\Spout\Writer\Helper\XLSX;

/**
 * Class ZipHelper
 * This class provides helper functions to create zip files
 *
 * @package Box\Spout\Writer\Helper\XLSX
 */
class ZipHelper
{
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
        $folderRealPath = realpath($folderPath) . DIRECTORY_SEPARATOR;
        $itemIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($itemIterator as $itemInfo) {
            $itemRealPath = realpath($itemInfo->getPathname());
            $itemLocalPath = str_replace($folderRealPath, '', $itemRealPath);

            if ($itemInfo->isFile()) {
                $zip->addFile($itemInfo->getPathname(), $itemLocalPath);
            } else if ($itemInfo->isDir()) {
                $zip->addEmptyDir($itemLocalPath);
            }
        }
    }
}
