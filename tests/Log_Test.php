<?php
/**
 * Logger classes unit tests.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

/** @noinspection PhpIllegalPsrClassPathInspection */

namespace donbidon\Core\Log;

use donbidon\Core\Event\Args;
use donbidon\Core\Registry\UT_Recursive;
use donbidon\Core\ExceptionExtended as ExceptionExtended;
use \donbidon\Lib\FileSystem\Tools;

/**
 * Logger classes unit tests.
 */
class Log_Test extends \donbidon\Lib\PHPUnit\TestCase
{
    use T_Logger;

    /**
     * Log directory access rights
     *
     * @vat int
     */
    const LOG_DIRECTORY_RIGHTS = 0666;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        UT_Recursive::resetInstance();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->evtManager = null;
        UT_Recursive::resetInstance();

        parent::tearDown();
    }

    /**
     * Tests invalid logging method.
     *
     * @return void
     * @covers \donbidon\Core\Log\Factory::run
     */
    public function testInvalidMethod()
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage(
            "Class '\\donbidon\\Core\\Log\\Method\\Invalid' not found"
        );

        $registry = \donbidon\Core\UT_Bootstrap::initByPath(sprintf(
            "%s/data/config.Log_Method_Invalid.php",
            __DIR__
        ));
        Factory::run($registry, $registry->get('core/event/manager'));
    }

    /**
     * Tests logging using own method.
     *
     * @return void
     * @throws ExceptionExtended  Risen from self::someMethod().
     * @throws ExceptionExtended  Risen from self::otherMethod().
     * @covers \donbidon\Core\Log\A_Method
     * @covers \donbidon\Core\Log\T_Logger::log
     */
    public function testLogger()
    {
        $registry = \donbidon\Core\UT_Bootstrap::initByPath(sprintf(
            "%s/data/config.Log.php",
            __DIR__
        ));
        $this->evtManager = $registry->get('core/event/manager');
        $this->someMethod();
        $this->otherMethod();
        $args = new Args([]);
        $this->evtManager->fire('unitTests/getLogRecords', $args);
        $actual = $args->get('records');
        $expected = [
            [
                'message' => "Notice",
                'source'  => "LogTest::someMethod()",
                'level'   => E_NOTICE,
            ],
            [
                'message' => "Warning",
                'source'  => "LogTest::someMethod()",
                'level'   => E_WARNING,
            ],
            [
                'message' => "Error",
                'source'  => "LogTest::someMethod()",
                'level'   => E_ERROR,
            ],
        ];
        self::assertEquals($expected, $actual);
    }

    /**
     * Tests streaming logging method.
     *
     * @return void
     * @throws ExceptionExtended  Risen from self::someMethod().
     * @covers \donbidon\Core\Log\Method\Stream::log
     * @covers \donbidon\Core\Log\T_Logger::log
     */
    public function testStreamLogger()
    {
        $registry = \donbidon\Core\UT_Bootstrap::initByPath(sprintf(
            "%s/data/config.Log_Method_Stream.php",
            __DIR__
        ));
        $this->evtManager = $registry->get('core/event/manager');

        ob_start();
        $this->someMethod();
        $actual = ob_get_clean();
        $expected = implode(PHP_EOL, [
            "[ note ] [ LogTest::someMethod() ] ~ Notice",
            "[ WARN ] [ LogTest::someMethod() ] ~ Warning",
            "[ ERR  ] [ LogTest::someMethod() ] ~ Error",
            "",
        ]);
        self::assertEquals($expected, $actual);
    }

    /**
     * Tests file logging method.
     *
     * @return void
     * @throws ExceptionExtended  Risen from self::someMethod().
     * @covers \donbidon\Core\Log\Method\File::__construct
     * @covers \donbidon\Core\Log\Method\File::log
     * @covers \donbidon\Core\Log\T_Logger::log
     */
    public function testFileLogger()
    {
        $registry = \donbidon\Core\UT_Bootstrap::initByPath(sprintf(
            "%s/data/config.Log_Method_File.php",
            __DIR__
        ));
        $this->evtManager = $registry->get('core/event/manager');
        $path = implode(
            DIRECTORY_SEPARATOR,
            [
                sys_get_temp_dir(), "donbidon", "tests", "core", uniqid(),
                "FileLogger.log"
            ]
        );
        $this->evtManager->fire(':updateLogRegistry:', new Args([
            'conditions' => [
                'name'   => "File",
                'level'  => "E_ALL",
            ],
            'changes' => [
                'path' => $path,
            ],
        ]));
        $dir  = dirname($path);
        mkdir($dir, self::LOG_DIRECTORY_RIGHTS, true);

        $this->someMethod();
        $actual = file_get_contents($path);
        $expected = implode(PHP_EOL, [
            "[ note ] [ LogTest::someMethod() ] ~ Notice",
            "[ WARN ] [ LogTest::someMethod() ] ~ Warning",
            "[ ERR  ] [ LogTest::someMethod() ] ~ Error",
            "",
        ]);
        self::assertEquals($expected, $actual);

        Tools::removeDir($dir);
    }

    /**
     * Tests multiple levels and logging methods.
     *
     * @return void
     * @throws ExceptionExtended  Risen from self::someMethod().
     * @throws ExceptionExtended  Risen from self::otherMethod().
     * @covers \donbidon\Core\Log\Method\File::__construct
     * @covers \donbidon\Core\Log\Method\File::log
     * @covers \donbidon\Core\Log\T_Logger::log
     */
    public function testMultiple()
    {
        $registry = \donbidon\Core\UT_Bootstrap::initByPath(sprintf(
            "%s/data/config.Log_Multiple.php",
            __DIR__
        ));
        $this->evtManager = $registry->get('core/event/manager');
        $path = implode(
            DIRECTORY_SEPARATOR,
            [sys_get_temp_dir(), "donbidon", "tests", "core", uniqid(), "FileLogger.log"]
        );
        $this->evtManager->fire(':updateLogRegistry:', new Args([
            'conditions' => [
                'name'  => "File",
                'level' => "E_WARNING",
            ],
            'changes' => [
                'path' => $path,
            ],
        ]));

        $dir  = dirname($path);
        mkdir($dir, self::LOG_DIRECTORY_RIGHTS, true);

        ob_start();
        $this->someMethod();
        $this->otherMethod();
        $actual = ob_get_clean();
        $expected = implode(PHP_EOL, [
            "[ ERR  ] [ LogTest::someMethod() ] ~ Error",
            "[ WARN ] [ LogTest::otherMethod() ] ~ Warning from other method",
            "",
        ]);
        self::assertEquals($expected, $actual);

        $actual = file_get_contents($path);
        $expected = implode(PHP_EOL, [
            "[ WARN ] [ LogTest::someMethod() ] ~ Warning",
            "[ WARN ] [ LogTest::otherMethod() ] ~ Warning from other method",
            "",
        ]);
        self::assertEquals($expected, $actual);

        Tools::removeDir($dir);
    }

    /**
     * Sends log messages.
     *
     * @return void
     * @throws ExceptionExtended  Risen from T_Logger::log().
     */
    protected function someMethod()
    {
        $this->log("Notice",  'LogTest::someMethod()', E_NOTICE);
        $this->log("Warning", 'LogTest::someMethod()', E_WARNING);
        $this->log("Error",   'LogTest::someMethod()', E_ERROR);
    }

    /**
     * Sends log message.
     *
     * @return void
     * @throws ExceptionExtended  Risen from T_Logger::log().
     */
    protected function otherMethod()
    {
        $this->log("Warning from other method",  'LogTest::otherMethod()', E_WARNING);
    }
}
