<?php

namespace Box\Spout\Writer\XLSX\Creator;

use Box\Spout\Common\Helper\Escaper;
use Box\Spout\Common\Helper\StringHelper;
use Box\Spout\Common\Manager\OptionsManagerInterface;
use Box\Spout\Writer\Common\Creator\EntityFactory;
use Box\Spout\Writer\Common\Entity\Options;
use Box\Spout\Writer\Common\Helper\ZipHelper;
use Box\Spout\Writer\XLSX\Helper\FileSystemHelper;

/**
 * Class HelperFactory
 * Factory for helpers needed by the XLSX Writer
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
        $escaper = $this->createStringsEscaper();

        return new FileSystemHelper($tempFolder, $zipHelper, $escaper);
    }

    /**
     * @param EntityFactory $entityFactory
     * @return ZipHelper
     */
    private function createZipHelper(EntityFactory $entityFactory)
    {
        return new ZipHelper($entityFactory);
    }

    /**
     * @return Escaper\XLSX
     */
    public function createStringsEscaper()
    {
        return new Escaper\XLSX();
    }

    /**
     * @return StringHelper
     */
    public function createStringHelper()
    {
        return new StringHelper();
    }
}
