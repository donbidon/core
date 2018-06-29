<?php
/**
 * Bootstrap class unit tests.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core;

use donbidon\Core\Registry\UT_Recursive;

/**
 * Bootstrap class unit tests.
 */
class Bootstrap_Test extends \donbidon\Lib\PHPUnit\TestCase
{
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
    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        UT_Recursive::resetInstance();
    }

    /**
     * Tests exception when passed wrong path.
     *
     * @return void
     * @covers \donbidon\Core\Bootstrap::initByPath
     */
    public function testInitByWrongPath()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(
            Bootstrap::EX_CANNOT_OPEN_CONFIG
        );
        $this->expectExceptionMessage(
            "Cannot open config file \"/nonexistent/path\""
        );
        UT_Bootstrap::initByPath("/nonexistent/path");
    }

    /**
     * Tests exception when passed wrong path.
     *
     * @return void
     * @covers \donbidon\Core\Bootstrap::initByPath
     */
    public function testInitByPathContainingInvalidConfig()
    {
        $this->expectException(\PHPUnit\Framework\Error\Warning::class);
        $this->expectExceptionMessage(
            "syntax error, unexpected '^' in Unknown on line 3"
        );
        UT_Bootstrap::initByPath(sprintf(
            "%s/data/config.Bootstrap.invalid,php",
            __DIR__
        ));
    }

    /**
     * Tests functionality.
     *
     * @return void
     * @throws \ReflectionException  Risen from UT_Recursive::_get().
     * @throws \ReflectionException  Risen from UT_Recursive::_delete().`
     * @covers \donbidon\Core\Bootstrap::initByPath
     * @covers \donbidon\Core\Bootstrap::initByArray
     */
    public function testFunctionality()
    {
        UT_Bootstrap::initByPath(sprintf(
            "%s/data/config.Bootstrap,php",
            __DIR__
        ));
        self::assertTrue(
            UT_Recursive::_get('core/event/manager') instanceof
            Event\Manager
        );
        UT_Recursive::_delete('core/event/manager');

        $expected = [
            'core' => [
                'env'   => "CLI",
                'event' => [
                    'debug' => "1",
                ],
            ],
        ];
        self::assertEquals($expected, UT_Recursive::_get());
    }
}
