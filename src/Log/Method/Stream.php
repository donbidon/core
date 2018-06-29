<?php
/**
 * Logging method getting stream from registry.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core\Log\Method;

/**
 * Logging method getting stream from registry.
 *
 * {@see \donbidon\Core\Log\T_Logger Usage description}.
 */
class Stream extends A_Stream
{
    /**
     * {@inheritdoc}
     */
    protected function getStream()
    {
        $result = $this->registry->get('stream');

        return $result;
    }
}
