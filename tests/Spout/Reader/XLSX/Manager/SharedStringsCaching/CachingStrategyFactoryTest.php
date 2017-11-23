<?php

namespace Box\Spout\Reader\XLSX\Manager\SharedStringsCaching;

use Box\Spout\Reader\XLSX\Creator\HelperFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class CachingStrategyFactoryTest
 */
class CachingStrategyFactoryTest extends TestCase
{
    /**
     * @return array
     */
    public function dataProviderForTestCreateBestCachingStrategy()
    {
        return [
            [null, -1, 'FileBasedStrategy'],
            [CachingStrategyFactory::MAX_NUM_STRINGS_PER_TEMP_FILE, -1, 'FileBasedStrategy'],
            [CachingStrategyFactory::MAX_NUM_STRINGS_PER_TEMP_FILE + 10, -1, 'FileBasedStrategy'],
            [CachingStrategyFactory::MAX_NUM_STRINGS_PER_TEMP_FILE - 10, -1, 'InMemoryStrategy'],
            [10, CachingStrategyFactory::AMOUNT_MEMORY_NEEDED_PER_STRING_IN_KB * 10, 'FileBasedStrategy'],
            [15, CachingStrategyFactory::AMOUNT_MEMORY_NEEDED_PER_STRING_IN_KB * 10, 'FileBasedStrategy'],
            [5, CachingStrategyFactory::AMOUNT_MEMORY_NEEDED_PER_STRING_IN_KB * 10, 'InMemoryStrategy'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestCreateBestCachingStrategy
     *
     * @param int|null $sharedStringsUniqueCount
     * @param int $memoryLimitInKB
     * @param string $expectedStrategyClassName
     * @return void
     */
    public function testCreateBestCachingStrategy($sharedStringsUniqueCount, $memoryLimitInKB, $expectedStrategyClassName)
    {
        /** @var CachingStrategyFactory|\PHPUnit_Framework_MockObject_MockObject $factoryStub */
        $factoryStub = $this
            ->getMockBuilder('\Box\Spout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactory')
            ->disableOriginalConstructor()
            ->setMethods(['getMemoryLimitInKB'])
            ->getMock();

        $factoryStub->method('getMemoryLimitInKB')->willReturn($memoryLimitInKB);

        $tempFolder = sys_get_temp_dir();
        $helperFactory = new HelperFactory($factoryStub);
        $strategy = $factoryStub->createBestCachingStrategy($sharedStringsUniqueCount, $tempFolder, $helperFactory);

        $fullExpectedStrategyClassName = 'Box\Spout\Reader\XLSX\Manager\SharedStringsCaching\\' . $expectedStrategyClassName;
        $this->assertEquals($fullExpectedStrategyClassName, get_class($strategy));

        $strategy->clearCache();
    }

    /**
     * @return array
     */
    public function dataProviderForTestGetMemoryLimitInKB()
    {
        return [
            ['-1', -1],
            ['invalid', -1],
            ['1024B', 1],
            ['128K', 128],
            ['256KB', 256],
            ['512M', 512 * 1024],
            ['2MB', 2 * 1024],
            ['1G', 1 * 1024 * 1024],
            ['10GB', 10 * 1024 * 1024],
            ['2T', 2 * 1024 * 1024 * 1024],
            ['5TB', 5 * 1024 * 1024 * 1024],
        ];
    }

    /**
     * @dataProvider dataProviderForTestGetMemoryLimitInKB
     *
     * @param string $memoryLimitFormatted
     * @param float $expectedMemoryLimitInKB
     * @return void
     */
    public function testGetMemoryLimitInKB($memoryLimitFormatted, $expectedMemoryLimitInKB)
    {
        /** @var CachingStrategyFactory|\PHPUnit_Framework_MockObject_MockObject $factoryStub */
        $factoryStub = $this
            ->getMockBuilder('\Box\Spout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactory')
            ->disableOriginalConstructor()
            ->setMethods(['getMemoryLimitFromIni'])
            ->getMock();

        $factoryStub->method('getMemoryLimitFromIni')->willReturn($memoryLimitFormatted);

        $memoryLimitInKB = \ReflectionHelper::callMethodOnObject($factoryStub, 'getMemoryLimitInKB');

        $this->assertEquals($expectedMemoryLimitInKB, $memoryLimitInKB);
    }
}
