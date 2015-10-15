<?php

namespace Box\Spout;

/**
 * Trait TestUsingResource
 *
 * @package Box\Spout
 */
trait TestUsingResource {

    /** @var string Path to the test resources folder */
    private $resourcesPath = 'tests/resources';

    /** @var string Path to the test generated resources folder */
    private $generatedResourcesPath = 'tests/resources/generated';

    /** @var string Path to the test resources folder, that does not have writing permissions */
    private $generatedUnwritableResourcesPath = 'tests/resources/generated/unwritable';

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
        if (!file_exists($this->generatedUnwritableResourcesPath)) {
            // Make sure generated folder exists first
            if (!file_exists($this->generatedResourcesPath)) {
                mkdir($this->generatedResourcesPath, 0777, true);
            }

            // 0444 = read only
            mkdir($this->generatedUnwritableResourcesPath, 0444, true);
        }
    }
}
