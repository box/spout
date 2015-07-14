<?php

/**
 * Utility class for making PHP reflection easier to use.
 */
class ReflectionHelper
{
    private static $privateVarsToReset = array();

    /**
     * Resets any static vars that were set to their
     * original values (to not screw up later unit test runs).
     *
     * @return void
     */
    public static function reset()
    {
        foreach (self::$privateVarsToReset as $class => $valueNames) {
            foreach ($valueNames as $valueName => $originalValue) {
                self::setStaticValue($class, $valueName, $originalValue, $saveOriginalValue = false);
            }
        }
        self::$privateVarsToReset = array();
    }

    /**
     * Get the value of a static private or public class property.
     * Used to test internals of class without having to make the property public
     *
     * @param string $class
     * @param string $valueName
     * @return mixed|null
     */
    public static function getStaticValue($class, $valueName)
    {
        $reflectionClass = new ReflectionClass($class);
        $reflectionProperty = $reflectionClass->getProperty($valueName);
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue();

        // clean up
        $reflectionProperty->setAccessible(false);

        return $value;
    }

    /**
     * Set the value of a static private or public class property.
     * Used to test internals of class without having to make the property public
     *
     * @param string $class
     * @param string $valueName
     * @param mixed|null $value
     * @param bool|void $saveOriginalValue
     * @return void
     */
    public static function setStaticValue($class, $valueName, $value, $saveOriginalValue = true)
    {
        $reflectionClass = new ReflectionClass($class);
        $reflectionProperty = $reflectionClass->getProperty($valueName);
        $reflectionProperty->setAccessible(true);

        // to prevent side-effects in later tests, we need to remember the original value and reset it on tear down
        // @NOTE: we need to check isset in case the original value was null or array()
        if ($saveOriginalValue && (!isset(self::$privateVarsToReset[$class]) || !isset(self::$privateVarsToReset[$class][$name]))) {
            self::$privateVarsToReset[$class][$valueName] = $reflectionProperty->getValue();
        }
        $reflectionProperty->setValue($value);

        // clean up
        $reflectionProperty->setAccessible(false);
    }

    /**
     * @param object $object
     * @param string $valueName
     *
     * @return mixed|null
     */
    public static function getValueOnObject($object, $valueName)
    {
        $reflectionObject = new ReflectionObject($object);
        $reflectionProperty = $reflectionObject->getProperty($valueName);
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($object);

        // clean up
        $reflectionProperty->setAccessible(false);

        return $value;
    }

    /**
     * Invoke a the given public or protected method on the given object.
     *
     * @param object $object
     * @param string $methodName
     * @param *mixed|null $params
     *
     * @return mixed|null
     */
    public static function callMethodOnObject($object, $methodName)
    {
        $params = func_get_args();
        array_shift($params); // object
        array_shift($params); // methodName

        $className = get_class($object);
        $class = new ReflectionClass($className);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $params);
    }
}
