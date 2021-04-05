<?php

namespace Box\Spout\Reader\XLSX\Manager;

use Box\Spout\Reader\Exception\SharedStringNotFoundException;
use Box\Spout\Reader\XLSX\Creator\HelperFactory;
use Box\Spout\Reader\XLSX\Creator\InternalEntityFactory;
use Box\Spout\Reader\XLSX\Creator\ManagerFactory;
use Box\Spout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactory;
use Box\Spout\Reader\XLSX\Manager\SharedStringsCaching\FileBasedStrategy;
use Box\Spout\Reader\XLSX\Manager\SharedStringsCaching\InMemoryStrategy;
use Box\Spout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * Class SharedStringsManagerTest
 */
class SharedStringsManagerTest extends TestCase
{
    use TestUsingResource;

    /** @var SharedStringsManager */
    private $sharedStringsManager;

    /**
     * @return void
     */
    public function setUp() : void
    {
        $this->sharedStringsManager = null;
    }

    /**
     * @return void
     */
    public function tearDown() : void
    {
        if ($this->sharedStringsManager !== null) {
            $this->sharedStringsManager->cleanup();
        }
    }

    /**
     * @param string $resourceName
     * @return SharedStringsManager
     */
    private function createSharedStringsManager($resourceName = 'one_sheet_with_shared_strings.xlsx')
    {
        $resourcePath = $this->getResourcePath($resourceName);
        $tempFolder = sys_get_temp_dir();
        $cachingStrategyFactory = new CachingStrategyFactory();
        $helperFactory = new HelperFactory();
        $managerFactory = new ManagerFactory($helperFactory, $cachingStrategyFactory);
        $entityFactory = new InternalEntityFactory($managerFactory, $helperFactory);
        $workbookRelationshipsManager = new WorkbookRelationshipsManager($resourcePath, $entityFactory);

        $this->sharedStringsManager = new SharedStringsManager(
            $resourcePath,
            $tempFolder,
            $workbookRelationshipsManager,
            $entityFactory,
            $helperFactory,
            $cachingStrategyFactory
        );

        return $this->sharedStringsManager;
    }

    /**
     * @return void
     */
    public function testGetStringAtIndexShouldThrowExceptionIfStringNotFound()
    {
        $this->expectException(SharedStringNotFoundException::class);

        $sharedStringsManager = $this->createSharedStringsManager();
        $sharedStringsManager->extractSharedStrings();
        $sharedStringsManager->getStringAtIndex(PHP_INT_MAX);
    }

    /**
     * @return void
     */
    public function testGetStringAtIndexShouldReturnTheCorrectStringIfFound()
    {
        $sharedStringsManager = $this->createSharedStringsManager();
        $sharedStringsManager->extractSharedStrings();

        $sharedString = $sharedStringsManager->getStringAtIndex(0);
        $this->assertEquals('s1--A1', $sharedString);

        $sharedString = $sharedStringsManager->getStringAtIndex(24);
        $this->assertEquals('s1--E5', $sharedString);

        $usedCachingStrategy = \ReflectionHelper::getValueOnObject($sharedStringsManager, 'cachingStrategy');
        $this->assertTrue($usedCachingStrategy instanceof InMemoryStrategy);
    }

    /**
     * @return void
     */
    public function testGetStringAtIndexShouldWorkWithMultilineStrings()
    {
        $sharedStringsManager = $this->createSharedStringsManager('one_sheet_with_shared_multiline_strings.xlsx');

        $sharedStringsManager->extractSharedStrings();

        $sharedString = $sharedStringsManager->getStringAtIndex(0);
        $this->assertEquals("s1\nA1", $sharedString);

        $sharedString = $sharedStringsManager->getStringAtIndex(24);
        $this->assertEquals("s1\nE5", $sharedString);
    }

    /**
     * @return void
     */
    public function testGetStringAtIndexShouldWorkWithStringsContainingTextAndHyperlinkInSameCell()
    {
        $sharedStringsManager = $this->createSharedStringsManager('one_sheet_with_shared_strings_containing_text_and_hyperlink_in_same_cell.xlsx');

        $sharedStringsManager->extractSharedStrings();

        $sharedString = $sharedStringsManager->getStringAtIndex(0);
        $this->assertEquals('go to https://github.com please', $sharedString);
    }

    /**
     * @return void
     */
    public function testGetStringAtIndexShouldNotDoubleDecodeHTMLEntities()
    {
        $sharedStringsManager = $this->createSharedStringsManager('one_sheet_with_pre_encoded_html_entities.xlsx');

        $sharedStringsManager->extractSharedStrings();

        $sharedString = $sharedStringsManager->getStringAtIndex(0);
        $this->assertEquals('quote: &#34; - ampersand: &amp;', $sharedString);
    }

    /**
     * @return void
     */
    public function testGetStringAtIndexWithFileBasedStrategy()
    {
        // force the file-based strategy by setting no memory limit
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '-1');

        $sharedStringsManager = $this->createSharedStringsManager('sheet_with_lots_of_shared_strings.xlsx');

        $sharedStringsManager->extractSharedStrings();

        $sharedString = $sharedStringsManager->getStringAtIndex(0);
        $this->assertEquals('str', $sharedString);

        $sharedString = $sharedStringsManager->getStringAtIndex(CachingStrategyFactory::MAX_NUM_STRINGS_PER_TEMP_FILE + 1);
        $this->assertEquals('str', $sharedString);

        $usedCachingStrategy = \ReflectionHelper::getValueOnObject($sharedStringsManager, 'cachingStrategy');
        $this->assertTrue($usedCachingStrategy instanceof FileBasedStrategy);

        ini_set('memory_limit', $originalMemoryLimit);
    }
}
