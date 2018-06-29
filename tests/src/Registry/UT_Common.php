<?php
/**
 * Common registry class extension for unit testing.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

 namespace donbidon\Core\Registry;

 use donbidon\Lib\PHPUnit\T_ResetInstance;

/**
 * Common registry class extension for unit testing.
 *
 * <!-- donbidon.skip -->
 *
 * @see T_ResetInstance
 */
class UT_Common extends Common
{
    use T_ResetInstance;

    /**
    * {@inheritdoc}
    *
    * @param array $scope
    * @param int   $options
    */
    public function __construct(
        array $scope = [],
        $options = self::ALL_INCLUSIVE
    )
    {
        parent::__construct($scope, $options);
        $this->allowToResetInstance();
    }
}
