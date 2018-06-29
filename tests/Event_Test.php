<?php
/**
 * Registry class unit tests.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

/** @noinspection PhpIllegalPsrClassPathInspection */

namespace donbidon\Core\Event;

use InvalidArgumentException;
use RuntimeException;
use donbidon\Core\Registry\I_Registry;

/**
 * Registry class unit tests.
 */
class Event_Test extends \donbidon\Lib\PHPUnit\TestCase
{
    /**
     * Event manager instance
     *
     * @var Manager
     */
    protected $evtManager;

    /**
     * Debug event results
     *
     * @var array
     */
    protected $debugEventsResults = [];

    /**
     * Fired event uid
     *
     * @var string
     */
    protected $uid;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->evtManager = new Manager;
    }

    /**
     * Tests args functionality.
     *
     * @return void
     * @covers \donbidon\Core\Event\Args::__construct
     * @covers \donbidon\Core\Event\Args::set
     * @covers \donbidon\Core\Event\Args::exists
     * @covers \donbidon\Core\Event\Args::isEmpty
     * @covers \donbidon\Core\Event\Args::get
     * @covers \donbidon\Core\Event\Args::delete
     */
    public function testArgs()
    {
        $scope = ['arg' => 'value', 'null' => null, 'empty' => false];
        $args = new Args($scope);

        self::assertEquals($scope, $args->get());
        self::assertEquals('value', $args->get('arg'));
        self::assertEquals('value', $args->get('arg', 'otherValue'));
        self::assertEquals(null, $args->get('null', 'otherValue'));
        self::assertEquals(false, $args->get('empty', 'otherValue'));
        self::assertEquals('missingValue', $args->get('missingArg', 'missingValue'));

        self::assertFalse($args->isEmpty('arg'));
        self::assertTrue($args->isEmpty('null'));
        self::assertTrue($args->isEmpty('empty'));
        self::assertTrue($args->isEmpty('missingArg'));

        self::assertTrue($args->exists('arg'));
        self::assertTrue($args->exists('null'));
        self::assertTrue($args->exists('empty'));
        self::assertFalse($args->exists('missingArg'));

        $args->delete('arg');
        self::assertEquals('missingValue', $args->get('arg', 'missingValue'));
        self::assertTrue($args->isEmpty('arg'));
        self::assertFalse($args->exists('arg'));

        $args->set('newArg', 'newValue');
        self::assertFalse($args->isEmpty('newArg'));
        self::assertTrue($args->exists('newArg'));
    }

    /**
     * Tests exception when adding handler.
     *
     * @return void
     * @covers \donbidon\Core\Event\Manager::addHandler
     */
    public function testExceptionWhenAddingHandler()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid event priority");
        $this->evtManager->addHandler('event', 'foo', -1);
    }

    /**
     * Tests exception when dropping debug event.
     *
     * @return void
     * @covers \donbidon\Core\Event\Manager::dropHandlers
     */
    public function testExceptionWhenDroppingDebugEvent()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot drop debug event");
        $this->evtManager->dropHandlers(':log:');
    }

    /**
     * Tests exception when disabling debug event.
     *
     * @return void
     * @covers \donbidon\Core\Event\Manager::disableHandler
     */
    public function testExceptionWhenDisablingDebugEvent()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot disable debug event");
        $this->evtManager->disableHandler(':log:');
    }

    /**
     * Tests exception when invalid handler passed.
     *
     * @return void
     * @covers \donbidon\Core\Event\Manager::addHandler
     * @covers \donbidon\Core\Event\Manager::fire
     */
    public function testExceptionWhenInvalidHandlerPassed()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Invalid event handler function someNonexistentFunction() added to process 'event' event"
        );
        /** @noinspection PhpUndefinedCallbackInspection */
        $this->evtManager->addHandler('event', 'someNonexistentFunction');
        $this->evtManager->fire('event', new Args);
    }

    /**
     * Tests exception when event fired already.
     *
     * @return void
     * @covers \donbidon\Core\Event\Manager::addHandler
     * @covers \donbidon\Core\Event\Manager::fire
     */
    public function testExceptionWhenEventFiredAlready()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Event 'event' is fired already");
        $this->evtManager->addHandler('event', [$this, 'onFiringSameEvent']);
        $this->evtManager->fire('event', new Args);
    }

    /**
     * Handler firing already fired event.
     *
     * @param  string     $name
     * @param  I_Registry $args
     * @return void
     * @see    self::testExceptionWhenEventFiredAlready()
     */
    public function onFiringSameEvent($name, I_Registry $args)
    {
        $this->evtManager->fire($name, $args);
    }

    /**
     * Tests event handling.
     *
     * @return void
     * @covers \donbidon\Core\Event\Manager::addHandler
     * @covers \donbidon\Core\Event\Manager::fire
     */
    public function testEventHandling()
    {
        $this->evtManager->addHandler('first', [$this, 'onB']);
        $this->evtManager->addHandler(
            'first', [$this, 'onLowPriorityB'], Manager::PRIORITY_LOW
        );
        $this->evtManager->addHandler(
            'first', [$this, 'onLowPriorityA'], Manager::PRIORITY_LOW
        );
        $this->evtManager->addHandler('first', [$this, 'onA']);
        $this->evtManager->addHandler(
            'first', [$this, 'onHighPriority'], Manager::PRIORITY_HIGH
        );
        $this->evtManager->addHandler('second', [$this, 'onA']);
        $this->evtManager->addHandler('second', [$this, 'onBreakingEvent']);
        $this->evtManager->addHandler('second', [$this, 'onB']);

        $args = new Args;
        $this->evtManager->fire('first', $args);
        $expected = [
            'donbidon\Core\Event\Event_Test::onHighPriority' => true,
            'donbidon\Core\Event\Event_Test::onB'            => true,
            'donbidon\Core\Event\Event_Test::onA'            => true,
            'donbidon\Core\Event\Event_Test::onLowPriorityB' => true,
            'donbidon\Core\Event\Event_Test::onLowPriorityA' => true,
        ];
        self::assertEquals($expected, $args->get());

        $args = new Args;
        $this->evtManager->fire('second', $args);
        $expected = [
            'donbidon\Core\Event\Event_Test::onA' => true,
            ':break:'                             => true,
        ];
        self::assertEquals($expected, $args->get());
    }

    /**
     * Tests debugging events.
     *
     * @return void
     * @covers \donbidon\Core\Event\Manager::addHandler
     * @covers \donbidon\Core\Event\Manager::fire
     * @covers \donbidon\Core\Event\Manager::disableHandler
     * @covers \donbidon\Core\Event\Manager::enableHandler
     * @covers \donbidon\Core\Event\Manager::dropHandlers
     */
    public function testDebuggingEvents()
    {
        $this->evtManager->addHandler(':onAddHandler:', [$this, 'onAddHandler']);
        $this->evtManager->addHandler(':onEventStart:', [$this, 'onEventStart']);
        $this->evtManager->addHandler(':onHandlerFound:', [$this, 'onHandlerFound']);
        $this->evtManager->addHandler(':onEventEnd:', [$this, 'onEventEnd']);
        $this->evtManager->addHandler(':onDisableHandler:', [$this, 'onDisableHandler']);
        $this->evtManager->addHandler(':onEnableHandler:', [$this, 'onEnableHandler']);
        $this->evtManager->addHandler(':onDropHandlers:', [$this, 'onDropHandlers']);
        $this->evtManager->setDebug(true);

        $this->evtManager->addHandler('first', [$this, 'onA']);
        $this->evtManager->addHandler('first', [$this, 'onA']);
        if (empty($this->debugEventsResults['onAddHandler'])) {
            self::assertNotEmpty($this->debugEventsResults['onAddHandler']);
        } else {
            $expected = [
                0 => [
                    'name'    => 'first',
                    'handler' => [$this, 'onA'],
                    'added'   => true,
                    'source'  => 'core:event:debug',
                    'level'   => E_NOTICE,
                ],
                1 => [
                    'name'    => 'first',
                    'handler' => [$this, 'onA'],
                    'added'   => false,
                    'source'  => 'core:event:debug',
                    'level'   => E_WARNING,
                ],
            ];
            self::assertEquals($expected, $this->debugEventsResults['onAddHandler']);
        }

        $args = new Args;
        $this->evtManager->fire('first', $args);
        if (empty($this->debugEventsResults['onEventStart'])) {
            self::assertNotEmpty($this->debugEventsResults['onEventStart']);
        } else {
            $expected = [
                'name'   => 'first',
                'args'   => new Args(['donbidon\Core\Event\Event_Test::onA' => true]),
                'source' => 'core:event:debug',
                'level'  => E_NOTICE,
            ];
            self::assertEquals($expected, $this->debugEventsResults['onEventStart']);
        }
        if (empty($this->debugEventsResults['onHandlerFound'])) {
            self::assertNotEmpty($this->debugEventsResults['onHandlerFound']);
        } else {
            $expected = [
                'uid'     => $this->uid,
                'name'    => 'first',
                'handler' => [$this, 'onA'],
                'args'    => new Args(['donbidon\Core\Event\Event_Test::onA' => true]),
                'source'  => 'core:event:debug',
                'level'   => E_NOTICE,
            ];
            self::assertEquals($expected, $this->debugEventsResults['onHandlerFound']);
        }
        if (empty($this->debugEventsResults['onEventEnd'])) {
            self::assertNotEmpty($this->debugEventsResults['onEventEnd']);
        } else {
            $expected = [
                'uid'     => $this->uid,
                'name'    => 'first',
                'args'    => new Args(['donbidon\Core\Event\Event_Test::onA' => true]),
                'source'  => 'core:event:debug',
                'level'   => E_NOTICE,
            ];
            self::assertEquals($expected, $this->debugEventsResults['onEventEnd']);
        }

        $args->delete('donbidon\Core\Event\Event_Test::onA');
        $this->evtManager->disableHandler('first');
        $this->evtManager->fire('first', $args);
        if (empty($this->debugEventsResults['onDisableHandler'])) {
            self::assertNotEmpty($this->debugEventsResults['onDisableHandler']);
        } else {
            $expected = [
                'name'    => 'first',
                'source'  => 'core:event:debug',
                'level'   => E_NOTICE,
            ];
            self::assertEquals($expected, $this->debugEventsResults['onDisableHandler']);
        }
        $this->evtManager->fire('first', $args);
        self::assertEquals([], $args->get());

        $this->evtManager->enableHandler('first');
        $this->evtManager->fire('first', $args);
        if (empty($this->debugEventsResults['onEnableHandler'])) {
            self::assertNotEmpty($this->debugEventsResults['onEnableHandler']);
        } else {
            $expected = [
                'name'    => 'first',
                'source'  => 'core:event:debug',
                'level'   => E_NOTICE,
            ];
            self::assertEquals($expected, $this->debugEventsResults['onEnableHandler']);
        }
        self::assertEquals(
            ['donbidon\Core\Event\Event_Test::onA' => true],
            $args->get()
        );

        $this->evtManager->dropHandlers('first');
        $args->delete('donbidon\Core\Event\Event_Test::onA');
        $this->evtManager->fire('first', $args);
        if (empty($this->debugEventsResults['onDropHandlers'])) {
            self::assertNotEmpty($this->debugEventsResults['onDropHandlers']);
        } else {
            $expected = [
                'name'    => 'first',
                'source'  => 'core:event:debug',
                'level'   => E_NOTICE,
            ];
            self::assertEquals($expected, $this->debugEventsResults['onEnableHandler']);
        }
        self::assertEquals([], $args->get());
    }

    /**
     * Handler for testing event handling.
     *
     * @param  string    $name
     * @param  I_Registry $args
     * @return void
     * @see    self::testExceptionWhenEventFiredAlready()
     */
    public function onA(/** @noinspection PhpUnusedParameterInspection */ $name, I_Registry $args)
    {
        $args->set(__METHOD__, true);
    }

    /**
     * Handler for testing event handling.
     *
     * @param  string    $name
     * @param  I_Registry $args
     * @return void
     * @see    self::testExceptionWhenEventFiredAlready()
     */
    public function onB(/** @noinspection PhpUnusedParameterInspection */
        $name, I_Registry $args)
    {
        $args->set(__METHOD__, true);
    }

    /**
     * Handler for testing event handling.
     *
     * @param  string    $name
     * @param  I_Registry $args
     * @return void
     * @see    self::testExceptionWhenEventFiredAlready()
     */
    public function onLowPriorityA(/** @noinspection PhpUnusedParameterInspection */
        $name, I_Registry $args)
    {
        $args->set(__METHOD__, true);
    }

    /**
     * Handler for testing event handling.
     *
     * @param  string    $name
     * @param  I_Registry $args
     * @return void
     * @see    self::testExceptionWhenEventFiredAlready()
     */
    public function onLowPriorityB(/** @noinspection PhpUnusedParameterInspection */
        $name, I_Registry $args)
    {
        $args->set(__METHOD__, true);
    }

    /**
     * Handler for testing event handling.
     *
     * @param  string    $name
     * @param  I_Registry $args
     * @return void
     * @see    self::testExceptionWhenEventFiredAlready()
     */
    public function onHighPriority(/** @noinspection PhpUnusedParameterInspection */
        $name, I_Registry $args)
    {
        $args->set(__METHOD__, true);
    }

    /**
     * Handler for testing event handling.
     *
     * @param  string    $name
     * @param  I_Registry $args
     * @return void
     * @see    self::testExceptionWhenEventFiredAlready()
     */
    public function onBreakingEvent(/** @noinspection PhpUnusedParameterInspection */
        $name, I_Registry $args)
    {
        $args->set(':break:', true);
    }

    /**
     * Handler for testing debugging events.
     *
     * @param  string    $name
     * @param  I_Registry $args
     * @return void
     * @see    self::testDebuggingEvents()
     */
    public function onAddHandler(/** @noinspection PhpUnusedParameterInspection */
        $name, I_Registry $args)
    {
        if (!isset($this->debugEventsResults['onAddHandler'])) {
            $this->debugEventsResults['onAddHandler'] = [];
        }
        $this->debugEventsResults['onAddHandler'][] = $args->get();
    }

    /**
     * Handler for testing debugging events.
     *
     * @param  string    $name
     * @param  I_Registry $args
     * @return void
     * @see    self::testDebuggingEvents()
     */
    public function onEventStart(/** @noinspection PhpUnusedParameterInspection */
        $name, I_Registry $args)
    {
        $this->uid = $args->get('uid');
        $args->delete('uid');
        $this->debugEventsResults['onEventStart'] = $args->get();
    }

    /**
     * Handler for testing debugging events.
     *
     * @param  string    $name
     * @param  I_Registry $args
     * @return void
     * @see    self::testDebuggingEvents()
     */
    public function onHandlerFound(/** @noinspection PhpUnusedParameterInspection */
        $name, I_Registry $args)
    {
        $this->debugEventsResults['onHandlerFound'] = $args->get();
    }

    /**
     * Handler for testing debugging events.
     *
     * @param  string    $name
     * @param  I_Registry $args
     * @return void
     * @see    self::testDebuggingEvents()
     */
    public function onEventEnd(/** @noinspection PhpUnusedParameterInspection */
        $name, I_Registry $args)
    {
        $this->debugEventsResults['onEventEnd'] = $args->get();
    }

    /**
     * Handler for testing debugging events.
     *
     * @param  string    $name
     * @param  I_Registry $args
     * @return void
     * @see    self::testDebuggingEvents()
     */
    public function onDisableHandler(/** @noinspection PhpUnusedParameterInspection */
        $name, I_Registry $args)
    {
        $this->debugEventsResults['onDisableHandler'] = $args->get();
    }

    /**
     * Handler for testing debugging events.
     *
     * @param  string    $name
     * @param  I_Registry $args
     * @return void
     * @see    self::testDebuggingEvents()
     */
    public function onEnableHandler(/** @noinspection PhpUnusedParameterInspection */
        $name, I_Registry $args)
    {
        $this->debugEventsResults['onEnableHandler'] = $args->get();
    }

    /**
     * Handler for testing debugging events.
     *
     * @param  string    $name
     * @param  I_Registry $args
     * @return void
     * @see    self::testDebuggingEvents()
     */
    public function onDropHandlers(/** @noinspection PhpUnusedParameterInspection */
        $name, I_Registry $args)
    {
        $this->debugEventsResults['onDropHandlers'] = $args->get();
    }
}
