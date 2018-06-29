<?php
/**
 * Stream logging method abstract class.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core\Log\Method;

use donbidon\Core\Event\Args;

/**
 * Stream logging method abstract class.
 *
 * {@see \donbidon\Core\Log\T_Logger Usage description}.
 * {@see \donbidon\Core\Log\Stream Example of stream logging method}.
 */
abstract class A_Stream extends A_Method
{
    /**
     * Returns stream to write log.
     *
     * @return string
     */
    protected abstract function getStream();

    /**
     * {@inheritdoc}
     *
     * @param Args $args
     */
    protected function log(Args $args)
    {
        $message = sprintf("%s%s", $this->render($args), PHP_EOL);
        file_put_contents($this->getStream(), $message);
    }
}
