<?php
/**
 * File logger.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core\Log\Method;

use donbidon\Core\Registry\I_Registry;
use donbidon\Core\Event\Args;
use donbidon\Core\Event\Manager;
use donbidon\Lib\FileSystem\Logger;

/**
 * File logger.
 *
 * {@see \donbidon\Core\Log\T_Logger Usage description}.
 */
class File extends A_Method
{
    /**
     * Logger instance
     *
     * @var Logger
     */
    protected $logger;

    /**
     * {@inheritdoc}
     *
     * @param I_Registry $registry Part of registry related to method
     * @param Manager    $evtManager
     */
    public function __construct(I_Registry $registry, Manager $evtManager)
    {
        parent::__construct($registry, $evtManager);
        $this->logger = new Logger($this->registry->get());
    }

    /**
     * {@inheritdoc}
     *
     * @param  Args   $args
     */
    protected function log(Args $args)
    {
        $message = sprintf("%s%s", $this->render($args), PHP_EOL);
        $this->logger->log($message, null, $this->registry->get());
    }
}
