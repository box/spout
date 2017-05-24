<?php

namespace Box\Spout\Writer\CSV\Manager;

use Box\Spout\Writer\Common\Options;

/**
 * Class OptionsManager
 * CSV Writer options manager
 *
 * @package Box\Spout\Writer\CSV\Manager
 */
class OptionsManager extends \Box\Spout\Writer\Common\Manager\OptionsManager
{
    /**
     * @inheritdoc
     */
    protected function getSupportedOptions()
    {
        return [
            Options::FIELD_DELIMITER,
            Options::FIELD_ENCLOSURE,
            Options::SHOULD_ADD_BOM,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function setDefaultOptions()
    {
        $this->setOption(Options::FIELD_DELIMITER, ',');
        $this->setOption(Options::FIELD_ENCLOSURE, '"');
        $this->setOption(Options::SHOULD_ADD_BOM, true);
    }
}