<?php
/**
 * Common registry class unit tests.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

/** @noinspection PhpIllegalPsrClassPathInspection */

namespace donbidon\Core\Registry;

use RuntimeException;

/**
 * Common registry class unit tests.
*/
class Registry_Common_Test extends \donbidon\Lib\PHPUnit\TestCase
{
    /**
     * Initial scope
     *
     * @var array
     */
    protected $initialScope = [
        'key_1'       => "value_1",
        'empty_key_1' => "",
        'empty_key_2' => "0",
        'empty_key_3' => 0,
        'empty_key_4' => null,
        'key_2'       => "value_2",
        'ref_value'   => "final reference value",
        'ref_1'       => "~~> ref_2",
        'ref_3'       => "~~> ref_value",
        'ref_2'       => "~~> ref_3",
        'ref_4'       => "~~> array",
        'array'       => [
            'key_1_1' => "value_1_1",
        ]
    ];

    /**
     * Registry instance
     *
     * @var Common
     */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->registry = new Common($this->initialScope);
    }

    /**
     * Tests forbidden create action.
     *
     * @return void
     * @throws \ReflectionException  Risen from Common::set().
     * @covers \donbidon\Core\Registry\Common::set
     * @covers \donbidon\Core\Registry\Common::checkPermissions
     */
    public function testForbiddenCreate()
    {
        $this->initForbiddenActions();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("ACTION_CREATE: no permissions for key 'new key'");
        $this->expectExceptionCode(Common::ACTION_CREATE);

        $this->registry->set('new key', "some value");
    }

    /**
     * Tests forbidden modify action.
     *
     * @return void
     * @throws \ReflectionException  Risen from Common::set().
     * @covers \donbidon\Core\Registry\Common::set
     * @covers \donbidon\Core\Registry\Common::checkPermissions
     */
    public function testForbiddenModify()
    {
        $this->initForbiddenActions();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("ACTION_MODIFY: no permissions for key 'key'");
        $this->expectExceptionCode(Common::ACTION_MODIFY);
        $this->registry->set('key', "other value");
    }

    /**
     * Tests forbidden delete action.
     *
     * @return void
     * @throws \ReflectionException  Risen from Common::delete().
     * @covers \donbidon\Core\Registry\Common::delete
     * @covers \donbidon\Core\Registry\Common::checkPermissions
     */
    public function testForbiddenDelete()
    {
        $this->initForbiddenActions();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("ACTION_DELETE: no permissions for key 'key'");
        $this->expectExceptionCode(Common::ACTION_DELETE);
        $this->registry->delete('key');
    }

    /**
     * Tests forbidden override action.
     *
     * @return void
     * @throws \ReflectionException  Risen from Common::override().
     * @covers \donbidon\Core\Registry\Common::override
     * @covers \donbidon\Core\Registry\Common::checkPermissions
     */
    public function testForbiddenOverride()
    {
        $this->initForbiddenActions();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("ACTION_OVERRIDE: no permissions");
        $this->expectExceptionCode(Common::ACTION_OVERRIDE);
        $this->registry->override([]);
    }

    /**
     * Tests exception when missing key and no default value passed.
     *
     * @return void
     * @throws \ReflectionException  Risen from Common::get().
     * @covers \donbidon\Core\Registry\Common::get
     */
    public function testNonexistentKey()
    {
        $this->initForbiddenActions();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Missing key 'nonexistent_key'");
        $this->registry->get('nonexistent_key');
    }

    /**
     * Tests common functionality.
     *
     * @return void
     * @throws \ReflectionException  Risen from Common::get().
     * @throws \ReflectionException  Risen from Common::set().
     * @throws \ReflectionException  Risen from Common::delete().
     * @covers \donbidon\Core\Registry\Common::get
     * @covers \donbidon\Core\Registry\Common::set
     * @covers \donbidon\Core\Registry\Common::delete
     * @covers \donbidon\Core\Registry\Common::exists
     * @covers \donbidon\Core\Registry\Common::isEmpty
     */
    public function testCommonFunctionality()
    {
        self::assertEquals("value_1",   $this->registry->get('key_1'));
        self::assertEquals(100500,      $this->registry->get('key_3', 100500));
        self::assertEquals("",          $this->registry->get('empty_key_1'));
        self::assertEquals("0",         $this->registry->get('empty_key_2'));
        self::assertEquals(0,           $this->registry->get('empty_key_3'));
        self::assertEquals(null,        $this->registry->get('empty_key_4'));
        self::assertEquals($this->initialScope, $this->registry->get());

        $this->registry->set('key_1', "value_1_1");
        self::assertEquals("value_1_1", $this->registry->get('key_1'));

        $this->registry->delete('key_1');
        self::assertFalse($this->registry->exists('key_1'));
        self::assertTrue($this->registry->isEmpty('key_1'));

        self::assertTrue($this->registry->isEmpty('key_3'));
        self::assertTrue($this->registry->isEmpty('empty_key_1'));
        self::assertTrue($this->registry->isEmpty('empty_key_2'));
        self::assertTrue($this->registry->isEmpty('empty_key_3'));
        self::assertTrue($this->registry->isEmpty('empty_key_4'));
    }

    /**
     * Tests override.
     *
     * @return void
     * @throws \ReflectionException  Risen from Common::override().
     * @covers \donbidon\Core\Registry\Common::override
     */
    public function testOverride()
    {
        $this->registry->override(['key_1' => "value_1*"]);
        self::assertEquals("value_1*", $this->registry->get('key_1'));
    }

    /**
     * Tests new registry creation from value of passed key.
     *
     * @return void
     * @throws \ReflectionException  Risen from Common::newFromKey().
     * @covers \donbidon\Core\Registry\Common::newFromKey
     */
    public function testNewFromKey()
    {
        $registry = $this->registry->newFromKey('ref_4');
        self::assertEquals(
            [
                'key_1_1' => "value_1_1",
            ],
            $registry->get()
        );
    }

    /**
     * Tests references.
     *
     * @return void
     * @throws \ReflectionException  Risen from Common::get().
     * @covers \donbidon\Core\Registry\Common::get
     * @covers \donbidon\Core\Registry\Common::getByRef
     */
    public function testReferences()
    {
        self::assertEquals(
            "final reference value",
            $this->registry->get('ref_1'),
            "Invalid reference value"
        );
        self::assertEquals(
            "final reference value",
            $this->registry->get('ref_2'),
            "Invalid reference value"
        );
    }

    /**
     * Tests invalid references.
     *
     * @return void
     * @throws \ReflectionException  Risen from Common::delete().
     * @throws \ReflectionException  Risen from Common::get().
     * @covers \donbidon\Core\Registry\Common::get
     * @covers \donbidon\Core\Registry\Common::getByRef
     */
    public function testInvalidReference()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Invalid reference detected: ref_1 ~~> ref_2 ~~> ref_3 ~~> ref_value (missing key)"
        );
        $this->registry->delete('ref_value');
        $this->registry->get('ref_1');
    }

    /**
     * Tests cyclic references.
     *
     * @return void
     * @throws \ReflectionException  Risen from Common::set().
     * @throws \ReflectionException  Risen from Common::get().
     * @covers \donbidon\Core\Registry\Common::get
     * @covers \donbidon\Core\Registry\Common::getByRef
     */
    public function testCyclicReference()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Cyclic reference detected: ref_1 ~~> ref_2 ~~> ref_3 ~~> ref_value ~~> ref_2"
        );
        $this->registry->set('ref_value', "~~> ref_2");
        $this->registry->get('ref_1');
    }

    /**
     * Tests cyclic references to itself.
     *
     * @return void
     * @throws \ReflectionException  Risen from Common::set().
     * @throws \ReflectionException  Risen from Common::get().
     * @covers \donbidon\Core\Registry\Common::get
     * @covers \donbidon\Core\Registry\Common::getByRef
     */
    public function testCyclicReferenceToItself()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Cyclic reference detected: ref_1 ~~> ref_1"
        );
        $this->registry->set('ref_1', "~~> ref_1");
        $this->registry->get('ref_1');
    }

    /**
     * Tests static instance.
     *
     * @return void
     * @throws \ReflectionException  Risen from Common::get().
     * @covers \donbidon\Core\Registry\Common::getInstance
     */
    public function testStaticInstance()
    {
        UT_Common::resetInstance();

        $registry = UT_Common::getInstance(['key' => "static instance"]);

        self::assertTrue(
            $registry instanceof Common,
            sprintf("Class %s isn't instance of Registry", get_class($registry))
        );
        self::assertEquals(
            "static instance",
            $registry->get('key'),
            "Static instance contains wrong scope"
        );

        UT_Common::resetInstance();
    }

    /**
     * Initializes registry instance having no actions allowed.
     *
     * @return   void
     * @internal
     */
    protected function initForbiddenActions()
    {
        self::checkGroupIfSkipped('all');
        $this->registry = new Common(
            [
                'key' => "value",
            ],
            Common::ACTION_NONE
        );
    }
}
