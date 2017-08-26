<?php

namespace Box\Spout\Reader\XLSX\Helper;

use Box\Spout\Reader\XLSX\Creator\EntityFactory;
use Box\Spout\Reader\XLSX\Creator\HelperFactory;
use Box\Spout\Reader\XLSX\Helper\SharedStringsCaching\CachingStrategyFactory;
use Box\Spout\Reader\XLSX\Helper\SharedStringsCaching\FileBasedStrategy;
use Box\Spout\Reader\XLSX\Helper\SharedStringsCaching\InMemoryStrategy;
use Box\Spout\TestUsingResource;

/**
 * Class SharedStringsHelperTest
 *
 * @package Box\Spout\Reader\XLSX\Helper
 */
class SharedStringsHelperTest extends \PHPUnit_Framework_TestCase
{
    use TestUsingResource;

    /** @var SharedStringsHelper */
    private $sharedStringsHelper;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->sharedStringsHelper = null;
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        if ($this->sharedStringsHelper !== null) {
            $this->sharedStringsHelper->cleanup();
        }
    }

    /**
     * @param string $resourceName
     * @return SharedStringsHelper
     */
    private function createSharedStringsHelper($resourceName = 'one_sheet_with_shared_strings.xlsx')
    {
        $resourcePath = $this->getResourcePath($resourceName);
        $tempFolder = sys_get_temp_dir();
        $cachingStrategyFactory = new CachingStrategyFactory();
        $helperFactory = new HelperFactory($cachingStrategyFactory);
        $entityFactory = new EntityFactory($helperFactory);

        $this->sharedStringsHelper = new SharedStringsHelper($resourcePath, $tempFolder, $entityFactory, $helperFactory, $cachingStrategyFactory);

        return $this->sharedStringsHelper;
    }

    /**
     * @expectedException \Box\Spout\Reader\Exception\SharedStringNotFoundException
     * @return void
     */
    public function testGetStringAtIndexShouldThrowExceptionIfStringNotFound()
    {
        $sharedStringsHelper = $this->createSharedStringsHelper();
        $sharedStringsHelper->extractSharedStrings();
        $sharedStringsHelper->getStringAtIndex(PHP_INT_MAX);
    }

    /**
     * @return void
     */
    public function testGetStringAtIndexShouldReturnTheCorrectStringIfFound()
    {
        $sharedStringsHelper = $this->createSharedStringsHelper();
        $sharedStringsHelper->extractSharedStrings();

        $sharedString = $sharedStringsHelper->getStringAtIndex(0);
        $this->assertEquals('s1--A1', $sharedString);

        $sharedString = $sharedStringsHelper->getStringAtIndex(24);
        $this->assertEquals('s1--E5', $sharedString);

        $usedCachingStrategy = \ReflectionHelper::getValueOnObject($sharedStringsHelper, 'cachingStrategy');
        $this->assertTrue($usedCachingStrategy instanceof InMemoryStrategy);
    }

    /**
     * @return void
     */
    public function testGetStringAtIndexShouldWorkWithMultilineStrings()
    {
        $sharedStringsHelper = $this->createSharedStringsHelper('one_sheet_with_shared_multiline_strings.xlsx');

        $sharedStringsHelper->extractSharedStrings();

        $sharedString = $sharedStringsHelper->getStringAtIndex(0);
        $this->assertEquals("s1\nA1", $sharedString);

        $sharedString = $sharedStringsHelper->getStringAtIndex(24);
        $this->assertEquals("s1\nE5", $sharedString);
    }

    /**
     * @return void
     */
    public function testGetStringAtIndexShouldWorkWithStringsContainingTextAndHyperlinkInSameCell()
    {
        $sharedStringsHelper = $this->createSharedStringsHelper('one_sheet_with_shared_strings_containing_text_and_hyperlink_in_same_cell.xlsx');

        $sharedStringsHelper->extractSharedStrings();

        $sharedString = $sharedStringsHelper->getStringAtIndex(0);
        $this->assertEquals('go to https://github.com please', $sharedString);
    }

    /**
     * @return void
     */
    public function testGetStringAtIndexShouldNotDoubleDecodeHTMLEntities()
    {
        $sharedStringsHelper = $this->createSharedStringsHelper('one_sheet_with_pre_encoded_html_entities.xlsx');

        $sharedStringsHelper->extractSharedStrings();

        $sharedString = $sharedStringsHelper->getStringAtIndex(0);
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

        $sharedStringsHelper = $this->createSharedStringsHelper('sheet_with_lots_of_shared_strings.xlsx');

        $sharedStringsHelper->extractSharedStrings();

        $sharedString = $sharedStringsHelper->getStringAtIndex(0);
        $this->assertEquals('str', $sharedString);

        $sharedString = $sharedStringsHelper->getStringAtIndex(CachingStrategyFactory::MAX_NUM_STRINGS_PER_TEMP_FILE + 1);
        $this->assertEquals('str', $sharedString);

        $usedCachingStrategy = \ReflectionHelper::getValueOnObject($sharedStringsHelper, 'cachingStrategy');
        $this->assertTrue($usedCachingStrategy instanceof FileBasedStrategy);

        ini_set('memory_limit', $originalMemoryLimit);
    }
}
