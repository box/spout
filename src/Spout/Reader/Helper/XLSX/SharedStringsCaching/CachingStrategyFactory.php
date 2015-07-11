<?php

namespace Box\Spout\Reader\Helper\XLSX\SharedStringsCaching;

/**
 * Class CachingStrategyFactory
 *
 * @package Box\Spout\Reader\Helper\XLSX\SharedStringsCaching
 */
class CachingStrategyFactory
{
    /**
     * To avoid running out of memory when extracting the shared strings, they will be saved to temporary files
     * instead of in memory. Then, when accessing a string, the corresponding file contents will be loaded in memory
     * and the string will be quickly retrieved.
     * The performance bottleneck is not when creating these temporary files, but rather when loading their content.
     * Because the contents of the last loaded file stays in memory until another file needs to be loaded, it works
     * best when the indexes of the shared strings are sorted in the sheet data.
     * 10,000 was chosen because it creates small files that are fast to be loaded in memory.
     */
    const MAX_NUM_STRINGS_PER_TEMP_FILE = 10000;

    /**
     * Returns the best caching strategy, given the number of unique shared strings
     * and the amount of memory available.
     *
     * @param int $sharedStringsUniqueCount Number of unique shared strings
     * @param string|void $tempFolder Temporary folder where the temporary files to store shared strings will be stored
     * @return CachingStrategyInterface The best caching strategy
     */
    public function getBestCachingStrategy($sharedStringsUniqueCount, $tempFolder = null)
    {
        // TODO add in-memory strategy
        return new FileBasedStrategy($tempFolder, self::MAX_NUM_STRINGS_PER_TEMP_FILE);
    }
}
