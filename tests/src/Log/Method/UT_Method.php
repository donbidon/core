<?php
/**
 * Logging method class for unit testing.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core\Log\Method;

use donbidon\Core\Event\Args;
use donbidon\Core\Event\Manager;

/**
 * Logging method class for unit testing.
 *
 * <!-- donbidon.skip -->
 */
class UT_Method extends A_Method
{
    /**
     * "log" method arguments
     *
     * @var array
     */
    protected $log = [];

    /**
     * Handler returning log records.
     *
     * @param  string $name
     * @param  Args   $args
     *
     * @return void
     */
    public function onGetLogRecords(
        /** @noinspection PhpUnusedParameterInspection */ $name, Args $args
    )
    {
        $args->set('records', $this->log);
        $args->set(':break:', true);
    }

    /**
     * Initializes event handler to log messages.
     *
     * @param  Manager $evtManager
     *
     * @return void
     */
    protected function init(Manager $evtManager)
    {
        parent::init($evtManager);
        $this->evtManager->addHandler(
            'unitTests/getLogRecords', [$this, 'onGetLogRecords']
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param Args $args
     */
    protected function log(Args $args)
    {
        $this->log[] = $args->get();
    }
}
