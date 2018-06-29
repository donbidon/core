<?php
/**
 * Friendlification class unit tests.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core;

/**
 * Friendlification class unit tests.
 */
class Friendlification_Test extends \PHPUnit\Framework\TestCase
{
    /**
     * Const for testing
     *
     * @see self::testGetConstNameByValue()
     */
    const TEST_ONE = 0x1001;

    /**
     * Const for testing
     *
     * @see self::testGetConstNameByValue()
     */
    const TEST_TWO = 0x1002;

    /**
     * Tests getting class constants names by its values.
     *
     * @return void
     * @covers \donbidon\Core\Friendlification::getConstNameByValue
     * @throws \ReflectionException
     */
    public function testGetConstNameByValue()
    {
        self::assertEquals(
            'TEST_ONE',
            Friendlification::getConstNameByValue(__CLASS__, 0x1001)
        );
        self::assertEquals(
            'TEST_TWO',
            Friendlification::getConstNameByValue(__CLASS__, 0x1002)
        );
        self::assertEquals(
            false,
            Friendlification::getConstNameByValue(__CLASS__, 0x1003)
        );
    }
}
