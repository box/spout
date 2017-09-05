<?php

namespace Box\Spout\Reader\XLSX\Manager\SharedStringsCaching;

/**
 * Interface CachingStrategyInterface
 */
interface CachingStrategyInterface
{
    /**
     * Adds the given string to the cache.
     *
     * @param string $sharedString The string to be added to the cache
     * @param int $sharedStringIndex Index of the shared string in the sharedStrings.xml file
     * @return void
     */
    public function addStringForIndex($sharedString, $sharedStringIndex);

    /**
     * Closes the cache after the last shared string was added.
     * This prevents any additional string from being added to the cache.
     *
     * @return void
     */
    public function closeCache();

    /**
     * Returns the string located at the given index from the cache.
     *
     * @param int $sharedStringIndex Index of the shared string in the sharedStrings.xml file
     * @throws \Box\Spout\Reader\Exception\SharedStringNotFoundException If no shared string found for the given index
     * @return string The shared string at the given index
     */
    public function getStringAtIndex($sharedStringIndex);

    /**
     * Destroys the cache, freeing memory and removing any created artifacts
     *
     * @return void
     */
    public function clearCache();
}
