<?php
/**
 * Logger factory.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core\Log;

use donbidon\Core\Registry\I_Registry;
use donbidon\Core\Event\Manager;

/**
 * Logger factory.
 *
 * {@see T_Logger Usage description}.
 */
class Factory
{
    /**
     * Factory.
     *
     * Creates and returns logger instance.
     *
     * @param  I_Registry $registry
     * @param  Manager    $evtManager
     * @return array      Array of A_Logger instances
     * @throws \RuntimeException  If one of passed methods isn't instance of
     *         \donbidon\Core\Log\A_Logger.
     */
    public static function run(I_Registry $registry, Manager $evtManager)
    {
        $instances = [];
        $methods = array_keys($registry->get('core/log', []));
        foreach ($methods as $method) {
            $levels = $registry->get(
                sprintf('core/log/%s', $method),
                []
            );
            foreach (array_keys($levels) as $level) {
                $methodRegistry = $registry->newFromKey(
                    sprintf('core/log/%s/%s', $method, $level)
                );
                $methodRegistry->set('name',  $method);
                $methodRegistry->set('level', $level);
                $methodRegistry->set('env',   $registry->get('core/env'));
                $class = $methodRegistry->get('class', null, false);
                if (is_null($class)) {
                    $class = sprintf(
                        "\\donbidon\\Core\\Log\\Method\\%s",
                        $method
                    );
                }
                $instance = new $class($methodRegistry, $evtManager);
                if (!($instance instanceof Method\A_Method)) {
                    throw new \RuntimeException(sprintf(
                        "Class '%s' has to be instance of %s",
                        $class,Method\A_Method::class
                    ));
                }
                $instances[] = $instance;
            }
        }

        return $instances;
    }
}
