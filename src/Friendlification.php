<?php
/**
 * "Friendlification" functionality.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 *
 */

namespace donbidon\Core;

use ReflectionClass;

/**
 * "Friendlification" functionality.
 *
 * @static
 */
class Friendlification
{
    /**
     * Classes reflections cache
     *
     * @var      array
     * @internal
     */
    protected static $reflections = [];

    /**
     * Returns class const name by its value.
     *
     * ```php
     * use donbidon\Core\Friendlification;
     *
     * class Foo
     * {
     *     const ONE = 1;
     *     const TWO = 2;
     * }
     *
     * var_dump(Friendlification::getConstNameByValue('Foo', 1));
     * var_dump(Friendlification::getConstNameByValue('Foo', 2));
     * var_dump(Friendlification::getConstNameByValue('Foo', 3));
     * ```
     * will output:
     * ```
     * string(3) "ONE"
     * string(3) "TWO"
     * bool(false)
     * ```
     *
     * @param  string $class
     * @param  mixed $value
     * @return string|false
     * @throws \ReflectionException  Risen from static::getReflection().
     */
    public static function getConstNameByValue($class, $value)
    {
        $constants = static::getReflection($class)->getConstants();
        $result = array_search($value, $constants);

        return $result;
    }

    /**
     * Returns reflection by class name.
     *
     * @param  string $class
     * @return ReflectionClass
     * @throws \ReflectionException  Risen from ReflectionClass::__construct().
     * @internal
     */
    protected static function getReflection($class)
    {
        if (!isset(self::$reflections[$class])) {
            self::$reflections[$class] = new ReflectionClass($class);
        }
        $result = self::$reflections[$class];

        return $result;
    }
}
