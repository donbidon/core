<?php
/**
 * Recursive registry class unit tests.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

/** @noinspection PhpIllegalPsrClassPathInspection */

namespace donbidon\Core\Registry;

use RuntimeException;

/**
 * Recursive registry class unit tests.
 */
class Registry_Recursive_Test extends \donbidon\Lib\PHPUnit\TestCase
{
    /**
     * Initial scope
     *
     * @var array
     */
    protected $initialScope = [
        'key_1'       => "value_1",
        'key_2'       => [
            'key_2_1'       => "value_2_1",
            'key_2_2'       => "value_2_2",
            'empty_key_2_1' => null,
        ],
        'empty_key_3' => "",
        'ref_1'       => "~~> key_2/empty_key_2_1",
        'ref_2'       => [
            'ref_2_1' => "~~> invalid_reference",
            'ref_2_3' => "~~> ref_2/ref_2_2",
            'ref_2_2' => "~~> ref_2/ref_2_3",
            'ref_2_4' => "~~> ref_2/ref_2_4",
        ],
        'key_5'       => [
            "key_5_1" => "~~> key_2",
            "key_5_2" => "~~> ref_1",
        ],
    ];

    /**
     * Registry instance
     *
     * @var Recursive
     */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->registry = new Recursive($this->initialScope);
    }

    /**
     * Tests common functionality.
     *
     * @return void
     * @throws \ReflectionException  Risen from Recursive::set().
     * @throws \ReflectionException  Risen from Recursive::get().
     * @throws \ReflectionException  Risen from Recursive::delete().
     * @covers \donbidon\Core\Registry\Recursive::get
     * @covers \donbidon\Core\Registry\Recursive::set
     * @covers \donbidon\Core\Registry\Recursive::delete
     * @covers \donbidon\Core\Registry\Recursive::exists
     * @covers \donbidon\Core\Registry\Recursive::isEmpty
     */
    public function testCommonFunctionality()
    {
        $this->registry->set('key_1/key_1_1', "value_1_1");
        $this->registry->set('key_2/key_2_3', "value_2_3");

        self::assertEquals(
            ['key_1_1' => "value_1_1", ],
            $this->registry->get('key_1')
        );
        self::assertEquals(
            [
                'key_2_1'       => "value_2_1",
                'key_2_2'       => "value_2_2",
                'key_2_3'       => "value_2_3",
                'empty_key_2_1' => null,
            ],
            $this->registry->get('key_2')
        );
        self::assertEquals(
            100500,
            $this->registry->get('key_3', 100500)
        );

        self::assertTrue($this->registry->exists('key_1'));
        self::assertTrue($this->registry->exists('key_1/key_1_1'));
        self::assertTrue($this->registry->exists('key_2/empty_key_2_1'));
        self::assertFalse($this->registry->exists('key_1/key_1_2'));
        self::assertFalse($this->registry->exists('key_2/key_2_4'));
        self::assertFalse($this->registry->exists('key_3'));
        self::assertFalse($this->registry->exists('key_4/key_4_1'));
        self::assertFalse($this->registry->exists('key_5/key_5_1/key_5_1_1'));

        self::assertFalse($this->registry->isEmpty('key_1'));
        self::assertFalse($this->registry->isEmpty('key_1/key_1_1'));
        self::assertTrue($this->registry->isEmpty('key_1/key_1_2'));
        self::assertTrue($this->registry->isEmpty('key_2/empty_key_2_1'));
        self::assertTrue($this->registry->isEmpty('key_2/key_2_4'));
        self::assertTrue($this->registry->isEmpty('key_3'));
        self::assertTrue($this->registry->isEmpty('key_4/key_4_1'));
        self::assertTrue($this->registry->isEmpty('key_5/key_5_1/key_5_1_1'));

        $this->registry->delete('key_1/key_1_1');
        self::assertFalse($this->registry->exists('key_1/key_1_1'));
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
        $registry = $this->registry->newFromKey('key_5');
        self::assertEquals(
            [
                'key_5_1' => [
                    'key_2_1'       => "value_2_1",
                    'key_2_2'       => "value_2_2",
                    'empty_key_2_1' => null,
                ],
                'key_5_2' => null,
            ],
            $registry->get()
        );
    }

    /**
     * Tests references.
     *
     * @return void
     * @throws \ReflectionException  Risen from Recursive::get().
     * @covers \donbidon\Core\Recursive::get
     * @covers \donbidon\Core\Common::getByRef
     */
    public function testReferences()
    {
        self::assertNull(
            $this->registry->get('ref_1'),
            "Invalid reference value"
        );

        self::assertEquals(
            "value_2_1",
            $this->registry->get('key_5/key_5_1/key_2_1'),
            "Invalid reference value"
        );
    }

    /**
     * Tests invalid references.
     *
     * @return void
     * @throws \ReflectionException  Risen from Recursive::get().
     * @covers \donbidon\Core\Recursive::get
     * @covers \donbidon\Core\Common::getByRef
     */
    public function testInvalidReference()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Invalid reference detected: ref_2/ref_2_1 ~~> invalid_reference (missing key)"
        );
        $this->registry->get('ref_2/ref_2_1');
    }

    /**
     * Tests cyclic references.
     *
     * @return void
     * @throws \ReflectionException  Risen from Recursive::get().
     * @covers \donbidon\Core\Recursive::get
     * @covers \donbidon\Core\Common::getByRef
     */
    public function testCyclicReference()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Cyclic reference detected: ref_2/ref_2_2 ~~> ref_2/ref_2_3 ~~> ref_2/ref_2_2"
        );
        $this->registry->get('ref_2/ref_2_2');
    }

    /**
     * Tests cyclic references to itself.
     *
     * @return void
     * @throws \ReflectionException  Risen from Recursive::set().
     * @throws \ReflectionException  Risen from Recursive::get().
     * @covers \donbidon\Core\Recursive::set
     * @covers \donbidon\Core\Recursive::get
     * @covers \donbidon\Core\Common::getByRef
     */
    public function testCyclicReferenceToItself()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Cyclic reference detected: ref_2/ref_2_4 ~~> ref_2/ref_2_4"
        );
        $this->registry->set('ref_1', "~~> ref_1");
        $this->registry->get('ref_2/ref_2_4');
    }

    /**
     * Tests static instance.
     *
     * @return void
     * @throws \ReflectionException  Risen from Recursive::_get().
     * @covers \donbidon\Core\Registry\Recursive::override
     */
    public function testStaticInstance()
    {
        UT_Recursive::resetInstance();

        $registry = UT_Recursive::getInstance(['key' => "static instance"]);

        self::assertTrue(
            $registry instanceof Recursive,
            sprintf("Class %s isn't instance of Registry", get_class($registry))
        );
        self::assertEquals(
            "static instance",
            UT_Recursive::_get('key'),
            "Static instance contains wrong scope"
        );

        UT_Recursive::resetInstance();
    }
}
