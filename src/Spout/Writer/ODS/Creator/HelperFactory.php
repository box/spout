<?php

namespace Box\Spout\Writer\ODS\Creator;

use Box\Spout\Common\Helper\StringHelper;
use Box\Spout\Writer\Common\Helper\ZipHelper;
use Box\Spout\Common\Manager\OptionsManagerInterface;
use Box\Spout\Writer\Common\Entity\Options;
use Box\Spout\Writer\Common\Creator\EntityFactory;
use Box\Spout\Writer\ODS\Helper\FileSystemHelper;
use Box\Spout\Common\Helper\Escaper;

/**
 * Class HelperFactory
 * Factory for helpers needed by the ODS Writer
 *
 * @package Box\Spout\Writer\ODS\Creator
 */
class HelperFactory extends \Box\Spout\Common\Creator\HelperFactory
{
    /**
     * @param OptionsManagerInterface $optionsManager
     * @param EntityFactory $entityFactory
     * @return FileSystemHelper
     */
    public function createSpecificFileSystemHelper(OptionsManagerInterface $optionsManager, EntityFactory $entityFactory)
    {
        $tempFolder = $optionsManager->getOption(Options::TEMP_FOLDER);
        $zipHelper = $this->createZipHelper($entityFactory);

        return new FileSystemHelper($tempFolder, $zipHelper);
    }

    /**
     * @param $entityFactory
     * @return ZipHelper
     */
    private function createZipHelper($entityFactory)
    {
        return new ZipHelper($entityFactory);
    }

    /**
     * @return Escaper\ODS
     */
    public function createStringsEscaper()
    {
        return new Escaper\ODS();
    }

    /**
     * @return StringHelper
     */
    public function createStringHelper()
    {
        return new StringHelper();
    }
}
