<?php
/**
 * Event arguments.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core\Event;

/**
 * Event arguments.
 *
 * @see Manager
 */
class Args implements \donbidon\Core\Registry\I_Registry
{
    /**
     * Scope of arguments
     *
     * @var array
     */
    protected $scope;

    /**
     * Constructor.
     *
     * @param array $scope  Initial arguments
     */
    public function __construct(array $scope = [])
    {
        $this->scope = $scope;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function set($key, $value)
    {
        $this->scope[$key] = $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $key
     * @return bool
     */
    public function exists($key)
    {
        $result = array_key_exists($key, $this->scope);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $key
     * @return bool
     * @link   http://php.net/manual/en/function.empty.php
     */
    public function isEmpty($key)
    {
        $result = empty($this->scope[$key]);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $key     If not passed, all arguments will be returned
     * @param  mixed  $default
     * @param  bool   $throw   Throw exception if no default value passed and
     *                         key doesn't exist
     * @return mixed
     * @throws \RuntimeException  If key doesn't exist.
     */
    public function get($key = null, $default = null, $throw = false)
    {
        $result = $default;
        if (is_null($key)) {
            $result = $this->scope;
        } else if ($this->exists($key)) {
            $result = $this->scope[$key];
        } else if (is_null($default) && $throw) {
            throw new \RuntimeException(sprintf(
                "Missing arg '%s'", $key
            ));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $key
     * @return void
     */
    public function delete($key)
    {
        unset($this->scope[$key]);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $key
     * @return static
     */
    public function newFromKey($key)
    {
        $result = new static($this->get($key));

        return $result;
    }
}
