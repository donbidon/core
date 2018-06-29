<?php
/**
 * Logging method abstract class.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core\Log\Method;

use donbidon\Core\Event\Args;
use donbidon\Core\Event\Manager;
use donbidon\Core\Registry\I_Registry;

/**
 * Logging method abstract class.
 *
 * {@see \donbidon\Core\Log\T_Logger Usage description}.
 */
abstract class A_Method
{
    /**
     * @var string
     */
    const DEFAULT_LEVEL  = "E_ERROR_WARNING";

    /**
     * @var string
     */
    const DEFAULT_FORMAT =
        "[ %DATE% %TIME% ] [ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%";

    /**
     * Reverse int level to string
     *
     * @var array
     */
    protected static $reverseLevel = [
        E_NOTICE  => "E_NOTICE",
        E_WARNING => "E_WARNING",
        E_ERROR   => "E_ERROR",
    ];

    /**
     * Level to string for message
     *
     * @var array
     */
    protected static $levelToString = [
        E_NOTICE  => "note",
        E_WARNING => "WARN",
        E_ERROR   => "ERR ",
    ];

    /**
     * Part of registry related to method
     *
     * @var I_Registry
     */
    protected $registry;

    /**
     * Event manager instance.
     *
     * @var Manager
     */
    protected $evtManager;

    /**
     * Initializes logger according to environment.
     *
     * @param I_Registry $registry    Part of registry related to method
     * @param Manager    $evtManager
     */
    public function __construct(I_Registry $registry, Manager $evtManager)
    {
        $this->registry = $registry;
        $this->init($evtManager);
    }

    /**
     * Logging handler.
     *
     * @param  string $name
     * @param  Args   $args
     * @return void
     */
    public function handler($name, Args $args)
    {
        if (
            !$this->checkLevel($args->get('level')) ||
            !$this->checkSource($args->get('source'))
        ) {
            return;
        }

        switch ($name) {
            /*
            case ':log:':
                $this->log($args);
                break; // case 'log'

            */
            case ':onAddHandler:':
                $args->set('message', sprintf(
                    "Adding handler %s, %s",
                    $this->handlerToString(
                        $args->get('handler'), $args->get('name')
                    ),
                    $args->get('added', false)
                        ? "successfully" :
                        "failed (duplicate handler)"
                ));
                break; // case ':onAddHandler:'

            case ':onEventStart:':
                $args->set('message', sprintf(
                    "{ '%s' (%s), args [%s]",
                    $args->get('name'),
                    $args->get('uid'),
                    implode(', ', array_keys($args->get()))
                ));
                break; // case ':onEventStart:'

            case ':onHandlerFound:':
                $args->set('message', sprintf(
                    "Handler %s found",
                    $this->handlerToString($args->get('handler'))
                ));
                break; // case ':onAddHandler:'

            case ':onEventEnd:':
                $args->set('message', sprintf(
                    "} '%s' (%s), args [%s]",
                    $args->get('name'),
                    $args->get('uid'),
                    implode(', ', array_keys($args->get()))
                ));
                break; // case ':onEventEnd:'

            case ':onDropHandlers:':
                $args->set('message', sprintf(
                    "Dropping handler %s",
                    $this->handlerToString(
                        $args->get('handler'), $args->get('name')
                    )
                ));
                break; // case ':onDropHandlers:'

            case ':onDisableHandler:':
                $args->set('message', sprintf(
                    "Disabling handler %s",
                    $this->handlerToString(
                        $args->get('handler'), $args->get('name')
                    )
                ));
                break; // case ':onDropHandlers:'

            case ':onEnableHandler:':
                $args->set('message', sprintf(
                    "Enabling handler %s",
                    $this->handlerToString(
                        $args->get('handler'), $args->get('name')
                    )
                ));
                break; // case ':onDropHandlers:'
        }
        $this->log($args);
    }

    /**
     * Updating method registry handler.
     *
     * ```php
     * $evtManager->fire(':updateLogRegistry:', new Args([
     *     'conditions' => [
     *         'name'   => "File",
     *         'level'  => "E_ALL",
     *     ],
     *     'changes' => [
     *         'path' => $path,
     *     ],
     * ]));
     * ```
     *
     * @param  string $name
     * @param  Args   $args
     *
     * @return void
     */
    public function onUpdateRegistry(
        /** @noinspection PhpUnusedParameterInspection */ $name, Args $args
    )
    {
        $conditions = $args->get('conditions');
        foreach ($conditions as $key => $expected) {
            $actual = $this->registry->get($key, null, false);
            if (
                "/" !== substr($expected, 0, 1)
                    ? $expected !== $actual
                    : !preg_match($expected, $actual)
            ) {
                return;
            }
        }
        $changes = $args->get('changes');
        foreach ($changes as $key => $value) {
            $this->registry->set($key, $value);
        }
    }

    /**
     * Logger.
     *
     * @param  Args   $args
     * @return void
     */
    protected abstract function log(Args $args);

    /**
     * Adds event handlers.
     *
     * @param  Manager $evtManager
     * @return void
     */
    protected function init(Manager $evtManager)
    {
        $this->evtManager = $evtManager;
        $events = $this->evtManager->getDebugEvents();
        foreach ($events as $event) {
            $this->evtManager->addHandler($event, [$this, 'handler']);
        }
        $this->evtManager->addHandler(
            ':updateLogRegistry:', [$this, 'onUpdateRegistry']
        );
    }

    /**
     * Returns true if passed source published in config file.
     *
     * @param  string $source
     * @return bool
     */
    protected function checkSource($source)
    {
        /** @var array */
        $sources = $this->registry->get('source', []);
        $result  = in_array($source, $sources) || in_array('*', $sources);

        return $result;
    }

    /**
     * Returns true if passed level published in config file.
     *
     * @param  int $level
     * @return bool
     * @throws \RuntimeException  If invalid level passed.
     */
    protected function checkLevel($level)
    {
        $cfgLevel = $this->registry->get(
            'level', self::DEFAULT_LEVEL
        );
        if (is_numeric($cfgLevel)) {
            $cfgLevel = (int)$cfgLevel;
        } else if (
            is_string($cfgLevel) &&
            preg_match('/^[A-Z_&|^ ]+$/', $cfgLevel)
        ) {
            $cfgLevel = eval(sprintf("return %s;", $cfgLevel));
        } else {
            throw new \RuntimeException(sprintf(
                "Invalid log level '%s' passed!", $cfgLevel
            ));
        }
        $result = (bool)($cfgLevel & $level);

        return $result;
    }

    /**
    * Returns stringified event handler.
    *
    * @param  callable $handler
    * @param  string   $name
    * @return string
    */
    protected function handlerToString($handler, $name = '')
    {
        if (is_array($handler)) {
            if (is_object($handler[0])) {
                $class = get_class($handler[0]);
                $call = '->';

            } else {
                $class = $handler[0];
                $call = '::';
            }
            $result = "{$class}{$call}{$handler[1]}";
        } else {
            $result = 'function' . ('' != $handler ? " {$handler}" : '');
        }
        $result .= "({$name})";

        return $result;
    }

    /**
     * Renders message according ro format.
     *
     * @param  Args $args
     * @return string
     */
    protected function render(Args $args)
    {
        $format = $this->registry->get(
            sprintf(
                'format/%s/%s',
                $this->registry->get('env'),
                self::$reverseLevel[$args->get('level')]
            ),
            self::DEFAULT_FORMAT
        );
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $message = str_replace(
            [
                '%DATE%',
                '%TIME%',
                '%LEVEL%',
                '%SOURCE%',
                '%FILE%',
                '%LINE%',
                '%MESSAGE%',
            ],
            [
                date('Y-m-d'),
                date('H:i:s'),
                self::$levelToString[$args->get('level')],
                $args->get('source'),
                $backtrace[4]['file'],
                $backtrace[4]['line'],
                $args->get('message')
            ],
            $format
        );

        return $message;
    }
}
