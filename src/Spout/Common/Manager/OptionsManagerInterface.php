<?php

namespace Box\Spout\Common\Manager;

/**
 * Interface OptionsManagerInterface
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

    /**
     * Add an option to the internal list of options
     * Used only for mergeCells() for now
     * @param mixed $optionName
     * @param mixed $optionValue
     * @return void
     */
    public function addOption($optionName, $optionValue);
}
