<?php
/**
 * Recursive registry class extension for unit testing.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

 namespace donbidon\Core\Registry;

 use donbidon\Lib\PHPUnit\T_ResetInstance;

/**
 * Recursive registry class extension for unit testing.
 *
 * <!-- donbidon.skip -->
 *
 * @see T_ResetInstance
 */
class UT_Recursive extends Recursive
{
    use T_ResetInstance;

    /**
     * {@inheritdoc}
     *
     * @param array  $scope
     * @param int    $options
     * @param string $delimiter  Key delimiter
     */
    public function __construct(
        array $scope = [],
        $options = self::ALL_INCLUSIVE,
        $delimiter = '/'
    )
    {
        parent::__construct($scope, $options);
        $this->allowToResetInstance();
    }
}
