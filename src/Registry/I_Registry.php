<?php
/**
 * Registry interface.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core\Registry;

/**
 * Registry interface.
 */
interface I_Registry
{
    /**
     * Sets scope value.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function set($key, $value);

    /**
     * Returns true if scope exists, false otherwise.
     *
     * @param  string $key
     * @return bool
     */
    public function exists($key);

    /**
     * Returns true if scope value is empty, false otherwise.
     *
     * @param  string $key
     * @return bool
     * @link   http://php.net/manual/en/function.empty.php
     */
    public function isEmpty($key);
    /**
     * Returns scope value.
     *
     * @param  string $key     If not passed, whole scope will be returned
     * @param  mixed  $default
     * @param  bool   $throw   Throw exception if no default value passed and
     *                         key doesn't exist
     * @return mixed
     */
    public function get($key = null, $default = null, $throw = true);

    /**
     * Deletes scope key.
     *
     * @param  string $key
     * @return void
     */
    public function delete($key);

    /**
     * Returns new registry from value of key.
     *
     * @param  string $key
     * @return static
     */
    public function newFromKey($key);
}
