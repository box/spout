<?php

namespace Box\Spout;

/**
 * Trait TestUsingResource
 */
trait TestUsingResource
{
    /** @var string Path to the test resources folder */
    private $resourcesPath = 'tests/resources';

    /** @var string Path to the test generated resources folder */
    private $generatedResourcesPath = 'tests/resources/generated';

    /** @var string Path to the test resources folder, that does not have writing permissions */
    private $generatedUnwritableResourcesPath = 'tests/resources/generated/unwritable';

    /** @var string Path to the test temp folder */
    private $tempFolderPath = 'tests/resources/generated/temp';

    /**
     * @param string $resourceName
     * @return string|null Path of the resource who matches the given name or null if resource not found
     */
    protected function getResourcePath($resourceName)
    {
        $resourceType = pathinfo($resourceName, PATHINFO_EXTENSION);
        $resourcePath = realpath($this->resourcesPath) . '/' . strtolower($resourceType) . '/' . $resourceName;

        return (file_exists($resourcePath) ? $resourcePath : null);
    }

    /**
     * @param string $resourceName
     * @return string Path of the generated resource for the given name
     */
    protected function getGeneratedResourcePath($resourceName)
    {
        $resourceType = pathinfo($resourceName, PATHINFO_EXTENSION);
        $generatedResourcePath = realpath($this->generatedResourcesPath) . '/' . strtolower($resourceType) . '/' . $resourceName;

        return $generatedResourcePath;
    }

    /**
     * @param string $resourceName
     * @return void
     */
    protected function createGeneratedFolderIfNeeded($resourceName)
    {
        $resourceType = pathinfo($resourceName, PATHINFO_EXTENSION);
        $generatedResourcePathForType = $this->generatedResourcesPath . '/' . strtolower($resourceType);

        if (!file_exists($generatedResourcePathForType)) {
            mkdir($generatedResourcePathForType, 0777, true);
        }
    }

    /**
     * @param string $resourceName
     * @return string Path of the generated unwritable (because parent folder is read only) resource for the given name
     */
    protected function getGeneratedUnwritableResourcePath($resourceName)
    {
        return realpath($this->generatedUnwritableResourcesPath) . '/' . $resourceName;
    }

    /**
     * @return void
     */
    protected function createUnwritableFolderIfNeeded()
    {
        // On Windows, chmod() or the mkdir's mode is ignored
        if ($this->isWindows()) {
            $this->markTestSkipped('Skipping because Windows cannot create read-only folders through PHP');
        }

        if (!file_exists($this->generatedUnwritableResourcesPath)) {
            // Make sure generated folder exists first
            if (!file_exists($this->generatedResourcesPath)) {
                mkdir($this->generatedResourcesPath, 0777, true);
            }

            // 0444 = read only
            mkdir($this->generatedUnwritableResourcesPath, 0444, true);
        }
    }

    /**
     * @return string Path of the temp folder
     */
    protected function getTempFolderPath()
    {
        return realpath($this->tempFolderPath);
    }

    /**
     * @return void
     */
    protected function recreateTempFolder()
    {
        if (file_exists($this->tempFolderPath)) {
            $this->deleteFolderRecursively($this->tempFolderPath);
        }

        mkdir($this->tempFolderPath, 0777, true);
    }

    /**
     * @param string $folderPath
     * @return void
     */
    private function deleteFolderRecursively($folderPath)
    {
        $itemIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($itemIterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($folderPath);
    }

    /**
     * @return bool Whether the OS on which PHP is installed is Windows
     */
    protected function isWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}
