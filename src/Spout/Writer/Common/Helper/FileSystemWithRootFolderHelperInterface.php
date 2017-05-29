<?php

namespace Box\Spout\Writer\Common\Helper;

use \Box\Spout\Common\Helper\FileSystemHelperInterface;

/**
 * Class FileSystemHelperInterface
 * This interface describes helper functions to help with the file system operations
 * like files/folders creation & deletion
 *
 * @package Box\Spout\Writer\Common\Helper
 */
interface FileSystemWithRootFolderHelperInterface extends FileSystemHelperInterface
{
    /**
     * Creates all the folders needed to create a spreadsheet, as well as the files that won't change.
     *
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If unable to create at least one of the base folders
     */
    public function createBaseFilesAndFolders();

    /**
     * @return string
     */
    public function getRootFolder();
}
