<?php
/**
 * Static and non-static registry functionality.
 *
 * Supports plain access rights and storing values by references.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core\Registry;

use RuntimeException;
use donbidon\Core\Friendlification;

/**
 * Static and non-static registry functionality.
 *
 * Supports plain access rights and storing values by references.
 * ```php
 * use \donbidon\Core\Registry\Common;
 *
 * $registry = new Common[
 *     'key_1'     => "value_1",
 *     'key_2'     => "value_2",
 *     'ref_1'     => "~~> ref_2",
 *     'ref_2'     => "~~> ref_value",
 *     'ref_value' => "final reference value",
 * ], Common::ACTION_ALL & ~Common::ACTION_MODIFY);
 * var_dump($registry->exists('key_1'));
 * var_dump($registry->exists('key_3'));
 * var_dump($registry->get('key_3', "default value"));
 * var_dump($registry->get('ref_1'));
 * var_dump($registry->get('ref_2'));
 * $registry->set('key_3', "value_3");
 * $registry->set('key_1', "value_11");
 * ```
 * outputs
 * ```
 * bool(true)
 * bool(false)
 * string(13) "default value"
 * string(21) "final reference value"
 * string(21) "final reference value"
 *
 * Fatal error: Uncaught RuntimeException: ACTION_MODIFY: no permissions for key 'key_1'
 * ```
 */
class Common implements I_Registry
{
    /**
     * Disallow all actions
     *
     * @see self::__construct()
     */
    const ACTION_NONE = 0;

    /**
     * Allow to create new keys
     *
     * @see self::__construct()
     */
    const ACTION_CREATE   = 0x0001;

    /**
     * Allow to modify existing keys
     *
     * @see self::__construct()
     */
    const ACTION_MODIFY   = 0x0002;

    /**
     * Allow to delete keys
     *
     * @see self::__construct()
     */
    const ACTION_DELETE   = 0x0004;

    /**
     * Allow to override scope
     *
     * @see self::__construct()
     */
    const ACTION_OVERRIDE = 0x0008;

    /**
     * Allow all actions
     *
     * @see self::__construct()
     */
    const ACTION_ALL      = 0x000F;

    /**
     * Support references
     *
     * @see self::__construct()
     */
    const REFERENCES      = 0x0100;

    /**
     * All actions amd references allowed.
     *
     * @see self::__construct()
     */
    const ALL_INCLUSIVE   = 0x010F;

    /**
     * Static registry instance
     *
     * @var self
     */
    protected static $instance;

    /**
     * Scope
     *
     * @var array
     */
    protected $scope;

    /**
     * Initial options
     *
     * @var int
     */
    protected $options;

    /**
     * Original key
     *
     * @var string
     * @internal
     */
    protected $key;

    /**
     * Flag specifying to check references
     *
     * @var bool
     */
    protected $checkRefs;

    /**
     * Reference regexp pattern
     *
     * @var string
     */
    protected $refPattern = "/^~~> ([A-Za-z_0-9.]+)$/";

    /**
     * Returns static registry instance.
     *
     * @param  array $scope
     * @param  int   $options
     * @return static
     */
    public static function getInstance(array $scope = [], $options = self::ACTION_ALL)
    {
        if (!is_object(self::$instance)) {
            self::$instance = new static($scope, $options);
        }

        return self::$instance;
    }

    /**
     * Short alias for self::getInstance()->set().
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     * @throws \ReflectionException  Risen from self::set()
     * @see self::set()
     */
    public static function _set($key, $value)
    {
        self::getInstance()->set($key, $value);
    }

    /**
     * Short alias for self::getInstance()->exists().
     *
     * @param  string $key
     * @return bool
     * @see self::exists()
     */
    public static function _exists($key)
    {
        return self::getInstance()->exists($key);
    }

    /**
     * Short alias for self::getInstance()->isEmpty().
     *
     * @param  string $key
     * @return bool
     * @see self::exists()
     */
    public static function _isEmpty($key)
    {
        return self::getInstance()->isEmpty($key);
    }

    /**
     * Short alias for self::getInstance()->get().
     *
     * @param  string $key
     * @param  mixed  $default
     * @param  bool   $throw   Throw exception if no default value passed and
     *                         key doesn't exist
     * @return mixed
     * @throws \ReflectionException  Risen from self::get().
     * @see self::get()
     */
    public static function _get($key = null, $default = null, $throw = true)
    {
        return static::getInstance()->get($key, $default, $throw);
    }

    /**
     * Short alias for self::getInstance()->delete().
     *
     * @param  string $key
     * @return void
     * @throws \ReflectionException  Risen from self::delete().
     * @see self::delete()
     */
    public static function _delete($key)
    {
        self::getInstance()->delete($key);
    }

    /**
     * Short alias for self::getInstance()->override().
     *
     * @param  array $scope
     * @return void
     * @throws \ReflectionException  Risen from self::override().
     * @see self::override()
     */
    public static function _override(array $scope)
    {
        self::getInstance()->override($scope);
    }

    /**
     * Constructor.
     *
     * Example:
     * ```php
     * use donbidon\Core\Registry\Common;
     *
     * // Create registry allowing to add new keys only
     * $registry = new Common(
     *     [
     *         'key_1' => "value_1",
     *     ],
     *     Common::ACTION_CREATE
     * );
     *
     * $registry->set('key_2', "value_2"); // Ok
     *
     * // RuntimeException having Registry:ACTION_CREATE
     * // code will be thrown.
     * $registry->set('key_2', "value_2*");
     * ```
     *
     * @param array $scope
     * @param int   $options  Combination of the following flags:
     *              - self::ACTION_CREATE,
     *              - self::ACTION_DELETE,
     *              - self::ACTION_MODIFY,
     *              - self::ACTION_OVERRIDE.
     */
    public function __construct(
        array $scope = [],
        $options = self::ALL_INCLUSIVE
    )
    {
        $this->scope     = (array)$scope;
        $this->options   = (int)$options;
        $this->checkRefs = self::REFERENCES & $options;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     * @throws \ReflectionException  Risen from self::checkPermissions().
     */
    public function set($key, $value)
    {
        $this->checkPermissions(
            $key,
            array_key_exists($key, $this->scope)
                ? self::ACTION_MODIFY
                : self::ACTION_CREATE
        );
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
     */
    public function isEmpty($key)
    {
        $result = empty($this->scope[$key]);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $key     If not passed, whole scope will be returned
     * @param  mixed  $default
     * @param  bool   $throw   Throw exception if no default value passed and
     *                         key doesn't exist
     * @return mixed
     * @throws \ReflectionException  Risen from self::checkPermissions().
     * @throws RuntimeException  If key doesn't exist.
     */
    public function get($key = null, $default = null, $throw = true)
    {
        $result = $default;
        $originalKey = is_null($this->key) ? $key : $this->key;
        if (is_null($key)) {
            $result = $this->scope;
        } else if (is_array($this->scope) && array_key_exists($key, $this->scope)) {
            $result = $this->scope[$key];
            $this->getByRef($originalKey, $result);
        } else if (is_null($default) && $throw) {
            $this->key = null;
            throw new RuntimeException(sprintf(
                "Missing key '%s'", $originalKey
            ));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $key
     * @return void
     * @throws \ReflectionException  Risen from self::checkPermissions().
     */
    public function delete($key)
    {
        $this->checkPermissions($key, self::ACTION_DELETE);
        unset($this->scope[$key]);
    }

    /**
     * Overrides scope.
     *
     * @param  array $scope
     * @return void
     * @throws \ReflectionException  Risen from self::checkPermissions().
     */
    public function override(array $scope)
    {
        $this->checkPermissions(null, self::ACTION_OVERRIDE);
        $this->scope = $scope;
    }

    /**
     * {@inheritdoc}
     *
     * Replaces all references by its values.
     *
     * @param  string $key
     * @return static
     * @throws \ReflectionException  Risen from self::get().
     */
    public function newFromKey($key)
    {
        $result = new static($this->get($key));

        return $result;
    }

    /**
     * Check action permissions.
     *
     * @param  string $key
     * @param  int $action
     * @return void
     * @throws \ReflectionException  Risen from Friendlification::getConstNameByValue().
     * @throws RuntimeException  If doesn't have permissions for passed action.
     */
    protected function checkPermissions($key, $action)
    {
        if (!($action & $this->options)) {
            $originalKey = is_null($this->key) ? $key : $this->key;
            $this->key = null;
            $text = Friendlification::getConstNameByValue(
                __CLASS__, $action
            );
            throw new RuntimeException(
                is_null($key)
                    ? sprintf("%s: no permissions", $text)
                    : sprintf(
                        "%s: no permissions for key '%s'", $text,
                        $originalKey
                    ),
                $action
            );
        }
    }

    /**
     * Gets value by reference if possible.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     * @throws \ReflectionException  Risen from self::get().
     * @throws RuntimeException  If cyclic or invalid reference detected.
     */
    protected function getByRef($key, &$value)
    {
        if (!$this->checkRefs ||!is_string($value)) {
            return;
        }

        $this->checkRefs = false;
        $references = [$key];
        $key = $value;
        while ($this->isRef($key)) {
            if (in_array($key, $references)) {
                $this->checkRefs = true;
                $this->key = null;
                $references[] = $key;
                throw new RuntimeException(sprintf(
                    "Cyclic reference detected: %s",
                    implode(" ~~> ", $references)
                ));
            }
            $references[] = $key;
            $previous = $key;
            $key = $this->get($key, null, false);
        };
        $this->checkRefs = true;
        if (isset($previous)) {
            if ($this->exists($previous)) {
                $value = $key;
            } else {
                $this->key = null;
                $references[sizeof($references) - 1] .= " (missing key)";
                throw new RuntimeException(sprintf(
                    "Invalid reference detected: %s",
                    implode(" ~~> ", $references)
                ));
            }
        }
    }

    /**
     * Validates if passed value is reference.
     *
     * @param  mixed &$value
     * @param  bool  $modify  Flag specifying to modify $value
     *         (to cut "~~> " prefix)
     *
     * @return bool
     */
    protected function isRef(&$value, $modify = true)
    {
        $matches = null;
        $result =
            is_string($value) &&
            preg_match($this->refPattern, $value, $matches);
        if ($result && $modify) {
            $value = $matches[1];
        }

        return $result;
    }
}
