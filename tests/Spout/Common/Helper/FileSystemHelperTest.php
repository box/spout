<?php

namespace Box\Spout\Common\Helper;

use Box\Spout\Common\Exception\IOException;
use PHPUnit\Framework\TestCase;

/**
 * Class FileSystemHelperTest
 */
class FileSystemHelperTest extends TestCase
{
    /** @var \Box\Spout\Writer\XLSX\Helper\FileSystemHelper */
    protected $fileSystemHelper;

    /**
     * @return void
     */
    public function setUp() : void
    {
        $baseFolder = \sys_get_temp_dir();
        $this->fileSystemHelper = new FileSystemHelper($baseFolder);
    }

    /**
     * @return void
     */
    public function testCreateFolderShouldThrowExceptionIfOutsideOfBaseFolder()
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Cannot perform I/O operation outside of the base folder');
        $this->fileSystemHelper->createFolder('/tmp/folder_outside_base_folder', 'folder_name');
    }

    /**
     * @return void
     */
    public function testCreateFileWithContentsShouldThrowExceptionIfOutsideOfBaseFolder()
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Cannot perform I/O operation outside of the base folder');
        $this->fileSystemHelper->createFileWithContents('/tmp/folder_outside_base_folder', 'file_name', 'contents');
    }

    /**
     * @return void
     */
    public function testDeleteFileShouldThrowExceptionIfOutsideOfBaseFolder()
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Cannot perform I/O operation outside of the base folder');
        $this->fileSystemHelper->deleteFile('/tmp/folder_outside_base_folder/file_name');
    }

    /**
     * @return void
     */
    public function testDeleteFolderRecursivelyShouldThrowExceptionIfOutsideOfBaseFolder()
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Cannot perform I/O operation outside of the base folder');

        $this->fileSystemHelper->deleteFolderRecursively('/tmp/folder_outside_base_folder');
    }
}
