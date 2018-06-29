<?php
/**
 * Logger trait.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core\Log;

use donbidon\Core\ExceptionExtended;
use donbidon\Core\Event\Args;
use donbidon\Core\Event\Manager;

/**
 * Logger trait.
 *
 * See <a href="../">API index page</a> for "config.php".
 * ```php
 * class Foo
 * {
 *     use \donbidon\Core\Log\T_Logger;
 *
 *     public function __construct(\donbidon\Core\Registry\I_Registry $registry)
 *     {
 *         $this->evtManager = $registry->get('core/event/manager');
 *         // $this->evtManager = \donbidon\Core\Registry\Recursive::_get(
 *         //    'core/event/manager'
 *         // );
 *     }
 *
 *     public function someMethod()
 *     {
 *         $this->log("Notice",  'Foo::someMethod()', E_NOTICE);
 *         $this->log("Warning", 'Foo::someMethod()', E_WARNING);
 *         $this->log("Error",   'Foo::someMethod()', E_ERROR);
 *     }
 *
 *     public function otherMethod()
 *     {
 *         $this->log("Warning from other method",  'Foo::otherMethod()', E_WARNING);
 *     }
 * }
 *
 * $registry = \donbidon\Core\Bootstrap::initByPath("/path/to/config.php");
 *
 * $foo = new Foo($registry);
 * echo
 * "Now will be echoed notices/warnings/errors only from 'Foo::someMethod()' source:",
 * PHP_EOL;
 * $foo->someMethod();
 * $foo->otherMethod();
 * $registry->delete('core/log/E_ALL/level');
 *
 * echo
 * PHP_EOL,
 * "Now will be echoed warnings/errors (default level) only from 'Foo::someMethod()' source:",
 * PHP_EOL;
 * $foo->someMethod();
 *
 * echo
 * PHP_EOL,
 * "Now will be echoed warnings/errors from all sources:",
 * PHP_EOL;
 * $source = $registry->get('core/log/E_ALL/source');
 * $source[] = "*";
 * $registry->set('core/log/E_ALL/source', $source);
 * $foo->someMethod();
 * $foo->otherMethod();
 * ```
 * outputs
 * ```
 * Now will be echoed notices/warnings/errors only from 'Foo::someMethod()' source:
 * [ YYYY-MM-DD **:**:** ] [ note ] [ Foo::someMethod() ] ~ Notice
 * [ YYYY-MM-DD **:**:** ] [ WARN ] [ Foo::someMethod() ] ~ Warning
 * [ YYYY-MM-DD **:**:** ] [ ERR  ] [ Foo::someMethod() ] ~ Error
 *
 * Now will be echoed warnings/errors (default level) only from 'Foo::someMethod()' source:
 * [ YYYY-MM-DD **:**:** ] [ WARN ] [ Foo::someMethod() ] ~ Warning
 * [ YYYY-MM-DD **:**:** ] [ ERR  ] [ Foo::someMethod() ] ~ Error
 *
 * Now will be echoed warnings/errors from all sources:
 * [ YYYY-MM-DD **:**:** ] [ WARN ] [ Foo::someMethod() ] ~ Warning
 * [ YYYY-MM-DD **:**:** ] [ ERR  ] [ Foo::someMethod() ] ~ Error
 * [ YYYY-MM-DD **:**:** ] [ WARN ] [ Foo::otherMethod() ] ~ Warning from other method
 * ```
 */
trait T_Logger
{
    /**
     * Event manager instance.
     *
     * @var Manager
     */
    protected $evtManager;

    /**
     * Fires event to log message.
     *
     * @param  string $message
     * @param  string $source
     * @param  int    $level
     * @param  mixed  $data     Exception data
     * @return void
     * @throws ExceptionExtended  If no event manager present.
     */
    protected function log(
        $message, $source = null, $level = E_NOTICE, $data = null
    )
    {
        if (!($this->evtManager instanceof Manager)) {
            $e = new ExceptionExtended(sprintf(
                "%s::\$evtManager isn't instance of %s",
                get_class($this), Manager::class
            ));
            $e->setData($data);
            throw $e;
        }
        if (is_null($source)) {
            $source = ":missed:";
        }
        $args = new Args([
            'message' => $message,
            'source'  => $source,
            'level'   => (int)$level,
        ]);
        $this->evtManager->fire(':log:', $args);
    }
}
