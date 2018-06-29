<?php
/**
 * Recursive registry functionality.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core\Registry;

/**
 * Recursive registry functionality.
 *
 * ```php
 * $registry = new \donbidon\Core\Registry\Recursive([
 *     'key_1' => "value_1",
 *     'key_2' => [
 *         'key_2_1' => "value_2_1",
 *         'key_2_2' => "value_2_2",
 *     ],
 *     'key_3' => "~~> key_2/key_2_2",
 * ]);
 * var_dump($registry->exists('key_1'));
 * var_dump($registry->exists('key_2/key_2_3'));
 * var_dump($registry->get('key_3'));
 * ```
 * outputs
 * ```
 * bool(true)
 * bool(false)
 * string(9) "value_2_2"
 * ```
 */
class Recursive extends Common
{
    /**
     * Key delimiter
     *
     * @var string
     */
    protected $delimiter;

    /**
     * Full scope, temporary scope according to complex key
     * will be stored in self::$scope
     *
     * @var array
     * @internal
     */
    protected $fullScope;

    /**
     * Constructor.
     *
     * @param array  $scope
     * @param int    $options
     * @param string $delimiter  Key delimiter
     */
    public function __construct(
        array $scope = [],
        $options = self::ALL_INCLUSIVE,
        $delimiter = '/'
    )
    {
        $this->fullScope  = $scope;
        $this->delimiter  = $delimiter;
        $this->refPattern = sprintf(
            "/^~~> ([A-Za-z_0-9.%s]+)$/",
            preg_quote($delimiter, "/")
        );
        parent::__construct([], $options);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $key
     * @param  mixed  $value
     */
    public function set($key, $value)
    {
        $this->setScope($key, true);
        parent::set($key, $value);
        /** @noinspection PhpInternalEntityUsedInspection */
        $this->key = null;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $key
     */
    public function exists($key)
    {
        $this->setScope($key);
        $result = is_array($this->scope) ? parent::exists($key) : false;
        /** @noinspection PhpInternalEntityUsedInspection */
        $this->key = null;

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $key
     */
    public function isEmpty($key)
    {
        $this->setScope($key);
        $result = parent::isEmpty($key);
        /** @noinspection PhpInternalEntityUsedInspection */
        $this->key = null;

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $key
     * @param  mixed  $default
     * @param  bool   $throw   Throw exception if no default value passed and
     *                         key doesn't exist
     */
    public function get($key = null, $default = null, $throw = true)
    {
        $this->setScope($key);
        $result = parent::get($key, $default, $throw);
        /** @noinspection PhpInternalEntityUsedInspection */
        $this->key = null;

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $key
     */
    public function delete($key)
    {
        $this->setScope($key);
        parent::delete($key);
        /** @noinspection PhpInternalEntityUsedInspection */
        $this->key = null;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * {@inheritdoc}
     *
     * @param  string $key
     * @return static
     * @throws \ReflectionException  Risen from self::get().
     */
    public function newFromKey($key)
    {
        $scope = $this->get($key);
        if ($this->checkRefs && is_array($scope)) {
            $this->replaceRefs($key, $scope);
        }
        $result = new static($scope);

        return $result;
    }

    /**
     * Replaces references recursively.
     *
     * @param  string $key
     * @param  mixed  $scope
     * @return void
     * @throws \ReflectionException
     * @internal
     */
    protected function replaceRefs($key, &$scope)
    {
        if (is_array($scope)) {
            foreach (array_keys($scope) as $subkey) {
                $this->replaceRefs(
                    implode($this->delimiter, [$key, $subkey]),
                    $scope[$subkey]
                );
            }
        } else {
            $this->getByRef($key, $scope);
        }
    }

    /**
     * Shifts scope according to complex key.
     *
     * @param    string $key    <b>[by ref]</b>
     * @param    bool   $create
     * @return   void
     * @internal
     */
    protected function setScope(&$key, $create = false)
    {
        $this->scope = &$this->fullScope;
        if (false === strpos($key, $this->delimiter)) {

            return;
        }
        /** @noinspection PhpInternalEntityUsedInspection */
        $this->key = $key;
        $keys = explode($this->delimiter, $key);
        $lastKey = array_pop($keys);
        $lastIndex = sizeof($keys) - 1;
        foreach ($keys as $index => $key) {
            if (!isset($this->scope[$key]) || !is_array($this->scope[$key])) {
                if ($create) {
                    $this->scope[$key] = [];
                } else if (!isset($this->scope[$key]) && $index == $lastIndex) {
                    return;
                }
            }
            if (
                $this->checkRefs &&
                $this->isRef($this->scope[$key],false)
            ) {
                $key = $this->scope[$key];
                $this->isRef($key);
                $this->setScope($key, $create);
            }
            $this->scope = &$this->scope[$key];
        }
        $key = $lastKey;
    }
}
