<?php

namespace Box\Spout\Reader\Common;

use Box\Spout\Reader\Wrapper\XMLReader;

/**
 * Class XMLProcessor
 * Helps process XML files
 */
class XMLProcessor
{
    /* Node types */
    const NODE_TYPE_START = XMLReader::ELEMENT;
    const NODE_TYPE_END = XMLReader::END_ELEMENT;

    /* Keys associated to reflection attributes to invoke a callback */
    const CALLBACK_REFLECTION_METHOD = 'reflectionMethod';
    const CALLBACK_REFLECTION_OBJECT = 'reflectionObject';

    /* Values returned by the callbacks to indicate what the processor should do next */
    const PROCESSING_CONTINUE = 1;
    const PROCESSING_STOP = 2;

    /** @var \Box\Spout\Reader\Wrapper\XMLReader The XMLReader object that will help read sheet's XML data */
    protected $xmlReader;

    /** @var array Registered callbacks */
    private $callbacks = [];

    /**
     * @param \Box\Spout\Reader\Wrapper\XMLReader $xmlReader XMLReader object
     */
    public function __construct($xmlReader)
    {
        $this->xmlReader = $xmlReader;
    }

    /**
     * @param string $nodeName A callback may be triggered when a node with this name is read
     * @param int $nodeType Type of the node [NODE_TYPE_START || NODE_TYPE_END]
     * @param callable $callback Callback to execute when the read node has the given name and type
     * @return XMLProcessor
     */
    public function registerCallback($nodeName, $nodeType, $callback)
    {
        $callbackKey = $this->getCallbackKey($nodeName, $nodeType);
        $this->callbacks[$callbackKey] = $this->getInvokableCallbackData($callback);

        return $this;
    }

    /**
     * @param string $nodeName Name of the node
     * @param int $nodeType Type of the node [NODE_TYPE_START || NODE_TYPE_END]
     * @return string Key used to store the associated callback
     */
    private function getCallbackKey($nodeName, $nodeType)
    {
        return "$nodeName$nodeType";
    }

    /**
     * Because the callback can be a "protected" function, we don't want to use call_user_func() directly
     * but instead invoke the callback using Reflection. This allows the invocation of "protected" functions.
     * Since some functions can be called a lot, we pre-process the callback to only return the elements that
     * will be needed to invoke the callback later.
     *
     * @param callable $callback Array reference to a callback: [OBJECT, METHOD_NAME]
     * @return array Associative array containing the elements needed to invoke the callback using Reflection
     */
    private function getInvokableCallbackData($callback)
    {
        $callbackObject = $callback[0];
        $callbackMethodName = $callback[1];
        $reflectionMethod = new \ReflectionMethod(\get_class($callbackObject), $callbackMethodName);
        $reflectionMethod->setAccessible(true);

        return [
            self::CALLBACK_REFLECTION_METHOD => $reflectionMethod,
            self::CALLBACK_REFLECTION_OBJECT => $callbackObject,
        ];
    }

    /**
     * Resumes the reading of the XML file where it was left off.
     * Stops whenever a callback indicates that reading should stop or at the end of the file.
     *
     * @throws \Box\Spout\Reader\Exception\XMLProcessingException
     * @return void
     */
    public function readUntilStopped()
    {
        while ($this->xmlReader->read()) {
            $nodeType = $this->xmlReader->nodeType;
            $nodeNamePossiblyWithPrefix = $this->xmlReader->name;
            $nodeNameWithoutPrefix = $this->xmlReader->localName;

            $callbackData = $this->getRegisteredCallbackData($nodeNamePossiblyWithPrefix, $nodeNameWithoutPrefix, $nodeType);

            if ($callbackData !== null) {
                $callbackResponse = $this->invokeCallback($callbackData, [$this->xmlReader]);

                if ($callbackResponse === self::PROCESSING_STOP) {
                    // stop reading
                    break;
                }
            }
        }
    }

    /**
     * @param string $nodeNamePossiblyWithPrefix Name of the node, possibly prefixed
     * @param string $nodeNameWithoutPrefix Name of the same node, un-prefixed
     * @param int $nodeType Type of the node [NODE_TYPE_START || NODE_TYPE_END]
     * @return array|null Callback data to be used for execution when a node of the given name/type is read or NULL if none found
     */
    private function getRegisteredCallbackData($nodeNamePossiblyWithPrefix, $nodeNameWithoutPrefix, $nodeType)
    {
        // With prefixed nodes, we should match if (by order of preference):
        //  1. the callback was registered with the prefixed node name (e.g. "x:worksheet")
        //  2. the callback was registered with the un-prefixed node name (e.g. "worksheet")
        $callbackKeyForPossiblyPrefixedName = $this->getCallbackKey($nodeNamePossiblyWithPrefix, $nodeType);
        $callbackKeyForUnPrefixedName = $this->getCallbackKey($nodeNameWithoutPrefix, $nodeType);
        $hasPrefix = ($nodeNamePossiblyWithPrefix !== $nodeNameWithoutPrefix);

        $callbackKeyToUse = $callbackKeyForUnPrefixedName;
        if ($hasPrefix && isset($this->callbacks[$callbackKeyForPossiblyPrefixedName])) {
            $callbackKeyToUse = $callbackKeyForPossiblyPrefixedName;
        }

        // Using isset here because it is way faster than array_key_exists...
        return isset($this->callbacks[$callbackKeyToUse]) ? $this->callbacks[$callbackKeyToUse] : null;
    }

    /**
     * @param array $callbackData Associative array containing data to invoke the callback using Reflection
     * @param array $args Arguments to pass to the callback
     * @return int Callback response
     */
    private function invokeCallback($callbackData, $args)
    {
        $reflectionMethod = $callbackData[self::CALLBACK_REFLECTION_METHOD];
        $callbackObject = $callbackData[self::CALLBACK_REFLECTION_OBJECT];

        return $reflectionMethod->invokeArgs($callbackObject, $args);
    }
}
