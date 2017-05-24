<?php

namespace Box\Spout\Writer\Common\Manager;

/**
 * Interface OptionsManagerInterface
 * Writer' options interface
 *
 * @package Box\Spout\Writer\Common\Manager
 */
interface OptionsManagerInterface
{
    /**
     * @param string $optionName
     * @param mixed $optionValue
     * @return void
     */
    public function setOption($optionName, $optionValue);

    /**
     * @param string $optionName
     * @return mixed|null The set option or NULL if no option with given name found
     */
    public function getOption($optionName);
}
