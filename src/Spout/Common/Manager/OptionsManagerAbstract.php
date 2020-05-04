<?php

namespace Box\Spout\Common\Manager;

/**
 * Class OptionsManager
 */
abstract class OptionsManagerAbstract implements OptionsManagerInterface
{
    const PREFIX_OPTION = 'OPTION_';

    /** @var string[] List of all supported option names */
    private $supportedOptions = [];

    /** @var array Associative array [OPTION_NAME => OPTION_VALUE] */
    private $options = [];

    /**
     * OptionsManagerAbstract constructor.
     */
    public function __construct()
    {
        $this->supportedOptions = $this->getSupportedOptions();
        $this->setDefaultOptions();
    }

    /**
     * @return array List of supported options
     */
    abstract protected function getSupportedOptions();

    /**
     * Sets the default options.
     * To be overriden by child classes
     *
     * @return void
     */
    abstract protected function setDefaultOptions();

    /**
     * Sets the given option, if this option is supported.
     *
     * @param string $optionName
     * @param mixed $optionValue
     * @return void
     */
    public function setOption($optionName, $optionValue)
    {
        if (\in_array($optionName, $this->supportedOptions)) {
            $this->options[$optionName] = $optionValue;
        }
    }

    /**
     * Add an option to the internal list of options
     * Used only for mergeCells() for now
     * @return void
     */
    public function addOption($optionName, $optionValue)
    {
        if (\in_array($optionName, $this->supportedOptions)) {
            if (!isset($this->options[$optionName])) {
                $this->options[$optionName] = [];
            }
            $this->options[$optionName][] = $optionValue;
        }
    }

    /**
     * @param string $optionName
     * @return mixed|null The set option or NULL if no option with given name found
     */
    public function getOption($optionName)
    {
        $optionValue = null;

        if (isset($this->options[$optionName])) {
            $optionValue = $this->options[$optionName];
        }

        return $optionValue;
    }
}
