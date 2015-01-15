<?php

namespace Box\Spout\Common\Helper;

/**
 * Class FileSystemHelperTest
 *
 * @package Box\Spout\Common\Helper
 */
class FileSystemHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Box\Spout\Writer\Helper\XLSX\FileSystemHelper */
    protected $fileSystemHelper;

    /**
     * @return void
     */
    public function setUp()
    {
        $baseFolder = '/tmp/base_folder';
        $this->fileSystemHelper = new FileSystemHelper($baseFolder);
    }

    /**
     * @expectedException \Box\Spout\Common\Exception\IOException
     * @return void
     */
    public function testCreateFolderShouldThrowExceptionIfOutsideOfBaseFolder()
    {
        $this->fileSystemHelper->createFolder('/tmp/folder_outside_base_folder', 'folder_name');
    }

    /**
     * @expectedException \Box\Spout\Common\Exception\IOException
     * @return void
     */
    public function testCreateFileWithContentsShouldThrowExceptionIfOutsideOfBaseFolder()
    {
        $this->fileSystemHelper->createFileWithContents('/tmp/folder_outside_base_folder', 'file_name', 'contents');
    }

    /**
     * @expectedException \Box\Spout\Common\Exception\IOException
     * @return void
     */
    public function testDeleteFileShouldThrowExceptionIfOutsideOfBaseFolder()
    {
        $this->fileSystemHelper->deleteFile('/tmp/folder_outside_base_folder/file_name');
    }

    /**
     * @expectedException \Box\Spout\Common\Exception\IOException
     * @return void
     */
    public function testDeleteFolderRecursivelyShouldThrowExceptionIfOutsideOfBaseFolder()
    {
        $this->fileSystemHelper->deleteFolderRecursively('/tmp/folder_outside_base_folder');
    }
}
