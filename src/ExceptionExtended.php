<?php
/**
 * Exception class allowing to store and retrieve user data.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core;

/**
 * Exception class allowing to store and retrieve user data.
 */
class ExceptionExtended extends \Exception
{
    /**
     * User data
     *
     * @var mixed
     */
    protected $data;

    /**
     * Sets user data.
     *
     * @param  mixed $data  User data
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Returns user data.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
