<?php
/**
 * Event manager.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core\Event;

use InvalidArgumentException;
use RuntimeException;
use donbidon\Core\Registry\I_Registry;
use donbidon\Lib\Arrays;

/**
 * Event manager.
 *
 * ```php
 * use donbidon\Core\Event\Manager;
 * use donbidon\Core\Event\Args;
 *
 * function firstEventHandler($name, I_Registry $args)
 * {
 *     echo sprintf("--- %s(%s)\n", __FUNCTION__, $name);
 *     $args->set(__FUNCTION__, true);
 *     print_r($args->get());
 * }
 *
 * function secondEventHandler($name, I_Registry $args)
 * {
 *     echo sprintf("--- %s(%s)\n", __FUNCTION__, $name);
 *     $args->set(__FUNCTION__, true);
 *     print_r($args->get());
 * }
 *
 * function thirdEventHandler($name, I_Registry $args)
 * {
 *     echo sprintf("--- %s(%s)\n", __FUNCTION__, $name);
 *     $args->set(__FUNCTION__, true);
 *     print_r($args->get());
 * }
 *
 * function breakingEventHandler($name, I_Registry $args)
 * {
 *     $args->set(__FUNCTION__, true);
 *     $args->set(':break:', true);
 *     echo sprintf("--- %s(%s)\n", __FUNCTION__, $name);
 *     print_r($args->get());
 * }
 *
 *
 * $evtManager = new Manager;
 * $evtManager->addHandler('event', 'firstEventHandler');
 * $evtManager->addHandler('event', 'breakingEventHandler');
 * $evtManager->addHandler('event', 'secondEventHandler');
 * $evtManager->addHandler('event', 'thirdEventHandler', Manager::PRIORITY_HIGH);
 * $evtManager->addHandler('otherEvent', 'thirdEventHandler');
 * $evtManager->addHandler('otherEvent', 'firstEventHandler');
 * $ars = new Args(['arg' => 'value']);
 * $evtManager->fire('event', $ars);
 * $ars = new Args;
 * $evtManager->fire('otherEvent', $ars);
 *
 * ```
 * outputs
 * ```
 * --- thirdEventHandler(event)
 * Array
 * (
 *     [arg] => value
 *     [thirdEventHandler] => 1
 * )
 * --- firstEventHandler(event)
 * Array
 * (
 *     [arg] => value
 *     [thirdEventHandler] => 1
 *     [firstEventHandler] => 1
 * )
 * --- breakingEventHandler(event)
 * Array
 * (
 *     [arg] => value
 *     [thirdEventHandler] => 1
 *     [firstEventHandler] => 1
 *     [breakingEventHandler] => 1
 *     [:break:] => 1
 * )
 * --- thirdEventHandler(otherEvent)
 * Array
 * (
 *     [thirdEventHandler] => 1
 * )
 * --- firstEventHandler(otherEvent)
 * Array
 * (
 *     [thirdEventHandler] => 1
 *     [firstEventHandler] => 1
 * )
 * ```
 */
class Manager
{
    /**
     * Minimum event handler priority
     *
     * @var int
     * @see Manager::addHandler()
     */
    const PRIORITY_MIN = 99;

    /**
     * Low event handler priority
     *
     * @var int
     * @see Manager::addHandler()
     */
    const PRIORITY_LOW = 75;

    /**
     * Default event handler priority
     *
     * @var int
     * @see Manager::addHandler()
     */
    const PRIORITY_DEFAULT = 50;

    /**
     * High event handler priority
     *
     * @var int
     * @see Manager::addHandler()
     */
    const PRIORITY_HIGH = 25;

    /**
     * Maximum event handler priority
     *
     * @var int
     * @see Manager::addHandler()
     */
    const PRIORITY_MAX = 0;

    /**
     * Array of event debugger hidden events
     *
     * @var array
     */
    protected static $debugEvents =
        [
            ':log:',
            ':onAddHandler:',
            ':onEventStart:',
            ':onHandlerFound:',
            ':onEventEnd:',
            ':onDisableHandler:',
            ':onEnableHandler:',
            ':onDropHandlers:',
        ];

    /**
     * Event handlers
     *
     * @var array
     * @internal
     */
    protected $handlers = [];

    /**
     * Contains ordered by priority handlers
     *
     * @var array
     * @internal
     */
    protected $orderedHandlers = [];

    /**
     * Contains disabled events.
     *
     * @var array
     * @internal
     */
    protected $disabledEvents = [];

    /**
     * Contains fired events names && target module name
     * to avoid recurring firing during its execution
     *
     * @var array
     * @internal
     */
    protected $firedEvents = [];

    /**
     * Flag containing debug events enabled or not
     *
     * @var bool
     * @see self::setDebug()
     * @see self::$debugEvents
     * @internal
     */
    protected $debug = false;

    /**
     * Pattern to filter handlers
     *
     * @var string
     * @see self::addHandler()
     * @see self::filterHandlers()
     * @internal
     */
    protected $key;

    /**
     * Adds event handler.
     *
     * To break event handling handler must call $args->set(':break:', true).<br /><br />
     *
     * @param  string   $name      Event name
     * @param  callback $handler   Event handler callback
     * @param  int      $priority  Event priority:
     *                             Manager::PRIORITY_LOW, Manager::PRIORITY_DEFAULT or
     *                             Manager::PRIORITY_HIGH, lower number means higher priority
     * @return void
     * @throws InvalidArgumentException  In case of invalid priority.
     */
    public function addHandler($name, $handler, $priority = self::PRIORITY_DEFAULT)
    {
        $priority = (int)$priority;
        if ($priority >= self::PRIORITY_MIN || $priority < self::PRIORITY_MAX) {
            throw new InvalidArgumentException("Invalid event priority");
        }
        $isObject = false;
        if (is_array($handler)) {
            $isObject = is_object($handler[0]);
            $key = sprintf(
                "%s%s%s",
                $isObject ? get_class($handler[0]) : $handler[0],
                $isObject ? "->" : "::",
                $handler[1]
            );
        } else {
            $key = $handler;
        }
        $addHandler = true;
        if (empty($this->handlers[$name])) {
            $this->handlers[$name] = [];
        } else if (isset($this->handlers[$name][$key])) {
            if ($isObject) {
                // Detect different instances and try to add handler.
                $this->key = $key . " ";
                $handlers = array_filter(
                    $this->handlers[$name],
                    [$this, 'filterHandlers'],
                    ARRAY_FILTER_USE_KEY
                );
                $handlers[$key] = $this->handlers[$name][$key];
                foreach ($handlers as $existing) {
                    if ($handler[0] === $existing[1][0]) {
                        $addHandler = false;
                        break;
                    }
                    if ($addHandler) {
                        $key = sprintf("%s #%d", $key, sizeof($handlers) + 1);
                    }
                }
            } else {
                $addHandler = false;
            }
        }
        if ($addHandler) {
            $this->handlers[$name][$key] = [$priority, $handler];
            unset($this->orderedHandlers[$name]);
        }
        if ($this->debug && !in_array($name, self::$debugEvents)) {
            $debugArgs = new Args([
                'name'    => $name,
                'handler' => $handler,
                'added'   => $addHandler,
                'source'  => 'core:event:debug',
                'level'   => $addHandler ? E_NOTICE : E_WARNING,
            ]);
            $this->fire(':onAddHandler:', $debugArgs);
        }
    }

    /**
     * Drops event handlers.
     *
     * Example:
     * ```
     * // drop all 'someEventName' event handlers
     * Manager::dropHandler('someEventName');
     *
     * // drop all 'someEventName' event handlers processing by $object methods only
     * Manager::dropHandler('someEventName', $object);
     * ```
     *
     * @param  string $name     Event name
     * @param  mixed  $handler  Handler or its part criteria
     * @return void
     * @throws InvalidArgumentException  In case of dropping debug event.
     */
    public function dropHandlers($name = '', $handler = null)
    {
        if (in_array($name, self::$debugEvents)) {
            throw new InvalidArgumentException("Cannot drop debug event");
        }
        if ($this->debug) {
            $debugArgs = new Args([
                'name'    => $name,
                'handler' => $handler,
                'source'  => 'core:event:debug',
                'level'   => E_NOTICE,
            ]);
            $this->fire(':onDropHandlers:', $debugArgs);
        }
        $this->runByHandler($name, $handler, [$this, 'deleteHandler']);
    }

    /**
     * Disables handler.
     *
     * @param  string $name  Event name
     * @return void
     * @throws InvalidArgumentException  In case of disabling debug event.
     */
    public function disableHandler($name)
    {
        if (in_array($name, self::$debugEvents)) {
            throw new InvalidArgumentException("Cannot disable debug event");
        }
        if ($this->debug) {
            $debugArgs = new Args([
                'name'    => $name,
                'source'  => 'core:event:debug',
                'level'   => E_NOTICE,
            ]);
            $this->fire(':onDisableHandler:', $debugArgs);
        }
        $this->disabledEvents[$name] = true;
    }

    /**
     * Enables handler.
     *
     * @param  string $name  Event name
     * @return void
     */
    public function enableHandler($name)
    {
        if ($this->debug) {
            $debugArgs = new Args([
                'name'    => $name,
                'source'  => 'core:event:debug',
                'level'   => E_NOTICE,
            ]);
            $this->fire(':onEnableHandler:', $debugArgs);
        }
        unset($this->disabledEvents[$name]);
    }

    /**
     * Returns true if there are any handlers for specified event.
     *
     * @param  string $name     Event name
     * @param  mixed  $handler  Handler or its part criteria
     * @return bool
     */
    public function hasHandlers($name, $handler = null)
    {
        $args = new Args;
        $this->runByHandler($name, $handler, [$this, 'hasHandler'], $args);
        $result = $args->get('hasHandler');

        return $result;
    }

    /**
     * Returns handlers for passed event or all.
     *
     * @param  string $name  Event name
     * @return array
     */
    public function getHandlers($name = null)
    {
        $result = is_null($name) ? $this->handlers : $this->handlers[$name];

        return $result;
    }

    /**
     * Fires event.
     *
     * @param  string     $name       Event name
     * @param  I_Registry $args       Event arguments
     * @param  bool       $fireAgain  Allow to fire event again
     * @return void
     * @throws RuntimeException  When invalid handler passed or event already fired.
     */
    public function fire($name, I_Registry $args, $fireAgain = false)
    {
        $uid = uniqid('');
        if ($this->debug && !in_array($name, self::$debugEvents)) {
            $debugArgs = new Args([
                'uid'    => $uid,
                'name'   => $name,
                'args'   => $args,
                'source' => 'core:event:debug',
                'level'  => E_NOTICE,
            ]);
            $this->fire(':onEventStart:', $debugArgs);
            unset($debugArgs);
        }
        if (isset($this->handlers[$name]) && empty($this->disabledEvents[$name])) {
            if (!$fireAgain && isset($this->firedEvents[$name])) {
                throw new RuntimeException(
                    sprintf(
                        "Event '%s' is fired already",
                        $name
                    )
                );
            } else {
                $this->firedEvents[$name] = true;
                if (!isset($this->orderedHandlers[$name])) {
                    $this->orderedHandlers[$name] = $this->handlers[$name];
                    $this->sortEvents($name);
                }
                foreach (array_keys($this->orderedHandlers[$name]) as $index) {
                    if (empty($this->orderedHandlers[$name][$index][1])) {
                        // Targeted event, not for this target
                        continue;
                    }
                    // Call handler
                    $callback = $this->orderedHandlers[$name][$index][1];
                    if (!is_callable($callback)) {
                        unset($this->firedEvents[$name]);
                        if (is_array($callback)) {
                            $callback =
                                (
                                    is_object($callback[0])
                                        ? get_class($callback[0]) . '->'
                                        : $callback[0] . '::'
                                ) .
                                $callback[1];
                        } else {
                            $callback = sprintf("function %s", $callback);
                        }

                        throw new RuntimeException(
                            sprintf(
                                "Invalid event handler %s() added to process '%s' event",
                                $callback,
                                $name
                            )
                        );
                    }

                    if ($this->debug && !in_array($name, self::$debugEvents)) {
                        $debugArgs = new Args([
                            'uid'     => $uid,
                            'name'    => $name,
                            'handler' => $this->orderedHandlers[$name][$index][1],
                            'args'    => $args,
                            'source'  => 'core:event:debug',
                            'level'   => E_NOTICE,
                        ]);
                        $this->fire(':onHandlerFound:', $debugArgs);
                    }
                    call_user_func(
                        $this->orderedHandlers[$name][$index][1],
                        $name,
                        $args
                    );
                    if (!$args->isEmpty(':break:')) {
                        break;
                    }
                }
                unset($this->firedEvents[$name]);
            }
        }
        if ($this->debug && !in_array($name, self::$debugEvents)) {
            $debugArgs = new Args([
                'uid'    => $uid,
                'name'   => $name,
                'args'   => $args,
                'source' => 'core:event:debug',
                'level'  => $args->get(':break:', false) ? E_WARNING : E_NOTICE,
            ]);
            $this->fire(':onEventEnd:', $debugArgs);
        }
    }

    /**
     * Enables/disables debug events.
     *
     * @param  bool $debug  Enable/disable flag
     * @return void
     * @see    self::$debugEvents
     */
    public function setDebug($debug)
    {
        $this->debug = (bool)$debug;
    }

    /**
     * Returns list of debug events.
     *
     * @return array
     * @see    self::$debugEvents
     */
    public function getDebugEvents()
    {
        return self::$debugEvents;
    }

    /**
     * Searches handlers by name and callback, runs task for its.
     *
     * @param    string     $name     Event name, empty string for all event names
     * @param    mixed      $handler  Event handler ot part of handler
     * @param    callback   $task     Task to run
     * @param    I_Registry $args     Arguments passed to task
     * @return   void
     * @throws   InvalidArgumentException  When $task parameter isn't callback.
     * @see      self::dropHandler() code for usage exaple
     * @internal
     */
    protected function runByHandler($name, $handler, $task, I_Registry $args = null)
    {
        if (!is_callable($task)) {
            throw new InvalidArgumentException(
                "Passed \$task parameter isn't callback"
            );
        }
        // Detect handler type
        if (is_object($handler)) {
            $handlerType = 'object';
        } else if (is_string($handler)) {
            $handlerType = function_exists($handler) ? 'function' : 'class';
        } else if (is_array($handler)) {
            $handlerType = 'callback';
        } else {
            $handlerType = '';
        }
        $names = $name === '' ? array_keys($this->handlers) : [$name];
        if (is_null($args)) {
            $args = new Args;
        }
        foreach ($names as $name) {
            if (empty($this->handlers[$name])) {
                // There aren't handlers for specified event name
                continue;
            }
            $indices = array_keys($this->handlers[$name]);
            switch($handlerType) {
                case 'object':
                case 'function':
                    // Run task for specified object / specified functions
                    foreach ($indices as $index) {
                        if (
                            is_array($this->handlers[$name][$index]) &&
                            $this->handlers[$name][$index][1][0] === $handler
                        ) {
                            call_user_func_array($task, [$args, $name, $index]);
                            if ($args->get(':break:')) {
                                return;
                            }
                        }
                    }
                    break; // case 'object', case 'function'

                case 'class':
                    // Run task for specified classes methods
                    foreach ($indices as $index) {
                        if (
                            is_array($this->handlers[$name][$index]) &&
                            (
                                $this->handlers[$name][$index][1][0] == $handler ||
                                get_class($this->handlers[$name][$index][1][0]) == $handler
                            )
                        ) {
                            call_user_func_array($task, [$args, $name, $index]);
                            if ($args->get(':break:')) {
                                return;
                            }
                        }
                    }
                    break; // case 'class'

                case 'callback':
                    // Run task for specified callbacks
                    foreach ($indices as $index) {
                        if (
                            is_array($this->handlers[$name][$index]) &&
                            (
                                (
                                    $this->handlers[$name][$index][1][0] == $handler[0] ||
                                    (
                                        is_object($this->handlers[$name][$index][1][0]) &&
                                        is_object($handler[0])
                                            ? get_class($this->handlers[$name][$index][1][0]) == get_class($handler[0])
                                            : $this->handlers[$name][$index][1][0] == $handler[0]
                                    )
                                ) &&
                                $this->handlers[$name][$index][1][1] == $handler[1]
                            )
                        ) {
                            call_user_func_array($task, [$args, $name, $index]);
                            if ($args->get(':break:')) {
                                return;
                            }
                        }
                    }
                    break; // case 'callback'

                default:
                    call_user_func_array($task, [$args, $name, null]);
                    if ($args->get(':break:')) {
                        return;
                    }
            }
        }
    }

    /**
     * Deletes handler.
     *
     * @param  I_Registry $args   Any arguments, can be used to return something
     * @param  string     $name   Event name
     * @param  int        $index  Index in self::$handlers[$name] array or null
     *                              if $handler parameter not callback or its part
     * @return void
     * @see Manager::dropHandler()
     * @internal
     */
    protected function deleteHandler(/** @noinspection PhpUnusedParameterInspection */ I_Registry $args, $name, $index = null)
    {
        $cleanupAllHandlers = is_null($index);
        if (!$cleanupAllHandlers) {
            unset($this->handlers[$name][$index]);
            if (sizeof($this->handlers[$name])) {
                ksort($this->handlers[$name]);
                unset($this->orderedHandlers[$name]);
            } else {
                $cleanupAllHandlers = true;
            }
        }
        if ($cleanupAllHandlers) {
            // Cleanup all handlers with specified name
            unset($this->handlers[$name], $this->orderedHandlers[$name]);
        }
    }

    /**
     * Sets up 'hasHandler' flag.
     *
     * @param    I_Registry $args   Call $args->get('hasHandler') to detect result
     * @param    string     $name   Event name
     * @param    int        $index  Index in self::$handlers[$name] array or null
     *                              if $handler parameter not callback or its part
     * @return   bool               true to continue, false to interrupt execution
     * @see      self::hasHandlers()
     * @internal
     */
    protected function hasHandler(I_Registry $args, $name, $index = null)
    {
        $result = is_null($index) ? !empty($this->handlers[$name]) : true;
        $args->set('hasHandler', $result);

        return $result;
    }

    /**
     * Filters handlers starting same key (instances of same class).
     *
     * @param  string $key
     * @return bool
     * @see    self::addHandler()
     * @internal
     */
    protected function filterHandlers($key)
    {
        return 0 === strpos($key, $this->key);
    }

    /**
     * Modifies priorities and sorts events.
     *
     * @param    string $name  Event name
     * @return   void
     * @see      self::fire()
     * @internal
     */
    protected function sortEvents($name)
    {
        $priorities = [];
        foreach ($this->orderedHandlers[$name] as $key => $row) {
            if (is_array($row)) {
                $priorities[$key] = $row[0];
            }
        }

        Arrays::adaptOrderCol($priorities);

        foreach ($priorities as $key => $priority) {
            $this->orderedHandlers[$name][$key][0] = $priority;
        }

        Arrays::sortByCol(
            $this->orderedHandlers[$name],
            0,
            SORT_NUMERIC,
            SORT_ASC
        );
    }
}
