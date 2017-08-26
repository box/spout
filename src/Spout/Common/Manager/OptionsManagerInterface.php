<?php

namespace Box\Spout\Common\Manager;

/**
 * Interface OptionsManagerInterface
 *
 * @package Box\Spout\Common\Manager
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
