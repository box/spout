<?php

namespace Box\Spout\Reader\XLSX\Helper\SharedStringsCaching;

/**
 * Class CachingStrategyFactoryTest
 *
 * @package Box\Spout\Reader\XLSX\Helper\SharedStringsCaching
 */
class CachingStrategyFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dataProviderForTestGetBestCachingStrategy()
    {
        return [
            [CachingStrategyFactory::MAX_NUM_STRINGS_PER_TEMP_FILE, -1, 'FileBasedStrategy'],
            [CachingStrategyFactory::MAX_NUM_STRINGS_PER_TEMP_FILE + 10, -1, 'FileBasedStrategy'],
            [CachingStrategyFactory::MAX_NUM_STRINGS_PER_TEMP_FILE - 10, -1, 'InMemoryStrategy'],
            [10 , CachingStrategyFactory::AMOUNT_MEMORY_NEEDED_PER_STRING_IN_KB * 10, 'FileBasedStrategy'],
            [15, CachingStrategyFactory::AMOUNT_MEMORY_NEEDED_PER_STRING_IN_KB * 10, 'FileBasedStrategy'],
            [5 , CachingStrategyFactory::AMOUNT_MEMORY_NEEDED_PER_STRING_IN_KB * 10, 'InMemoryStrategy'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestGetBestCachingStrategy
     *
     * @param int $sharedStringsUniqueCount
     * @param int $memoryLimitInKB
     * @param string $expectedStrategyClassName
     * @return void
     */
    public function testGetBestCachingStrategy($sharedStringsUniqueCount, $memoryLimitInKB, $expectedStrategyClassName)
    {
        /** @var CachingStrategyFactory|\PHPUnit_Framework_MockObject_MockObject $factoryStub */
        $factoryStub = $this
            ->getMockBuilder('\Box\Spout\Reader\XLSX\Helper\SharedStringsCaching\CachingStrategyFactory')
            ->disableOriginalConstructor()
            ->setMethods(['getMemoryLimitInKB'])
            ->getMock();

        $factoryStub->method('getMemoryLimitInKB')->willReturn($memoryLimitInKB);

        \ReflectionHelper::setStaticValue('\Box\Spout\Reader\XLSX\Helper\SharedStringsCaching\CachingStrategyFactory', 'instance', $factoryStub);

        $strategy = $factoryStub->getBestCachingStrategy($sharedStringsUniqueCount, null);

        $fullExpectedStrategyClassName = 'Box\Spout\Reader\XLSX\Helper\SharedStringsCaching\\' . $expectedStrategyClassName;
        $this->assertEquals($fullExpectedStrategyClassName, get_class($strategy));

        $strategy->clearCache();
        \ReflectionHelper::reset();
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
            ->getMockBuilder('\Box\Spout\Reader\XLSX\Helper\SharedStringsCaching\CachingStrategyFactory')
            ->disableOriginalConstructor()
            ->setMethods(['getMemoryLimitFromIni'])
            ->getMock();

        $factoryStub->method('getMemoryLimitFromIni')->willReturn($memoryLimitFormatted);

        $memoryLimitInKB = \ReflectionHelper::callMethodOnObject($factoryStub, 'getMemoryLimitInKB');

        $this->assertEquals($expectedMemoryLimitInKB, $memoryLimitInKB);
    }
}
