<?php
/**
 * Bootstrap class extension for unit testing.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core;

use donbidon\Core\Registry\UT_Recursive;

/**
 * Bootstrap class extension for unit testing.
 *
 * <!-- donbidon.skip -->
 */
class UT_Bootstrap extends Bootstrap
{
    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * {@inheritDoc}
     *
     * @param  array $config
     * @param  int   $options
     * @return UT_Recursive
     */
    protected static function getRegistry(array $config, $options)
    {
        UT_Recursive::resetInstance();
        $result = UT_Recursive::getInstance($config, $options);

        return $result;
    }
}
