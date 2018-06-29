<?php
/**
 * Core initialization.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Core;

use InvalidArgumentException;
use RuntimeException;
use donbidon\Core\Registry\I_Registry;
use donbidon\Core\Registry\Recursive;

defined('E_ERROR_WARNING')  ?:
    define('E_ERROR_WARNING',  E_ERROR   | E_WARNING);
defined('E_ERROR_NOTICE')   ?:
    define('E_ERROR_NOTICE',   E_ERROR   | E_NOTICE);
defined('E_WARNING_NOTICE') ?:
    define('E_WARNING_NOTICE', E_WARNING | E_NOTICE);

/**
 * Core environment initialization.
 *
 * <!-- move: index.html -->
 * <h1>Small library implementing registry, logging and events</h1>
 * <h3>Core environment initialization</h3>
 * "config.php":
 * ```
 * ; <?php die; __halt_compiler();
 *
 * [core]
 *
 * ; By default: Off
 * event[debug] = On
 *
 *
 * ;;; Log section {
 * ;
 * ; Supported methods (%METHOD%): Stream, File.
 * ; Supported levels (%LEVEL%): E_NOTICE, E_WARNING, E_ERROR, E_ERROR_WARNING,
 * ;                             E_ERROR_NOTICE, E_WARNING_NOTICE, E_ALL.
 * ;
 * ; [core.log.%METHOD%.%LEVEL%]
 * ; Class name including namespace to use own loggers, not set by default.
 * ; class = "\\own\\namespace\\Logger"
 * ;
 * ; Supported variables for format:
 * ;  * %DATE%    -- current date,
 * ;  * %TIME%    -- current time,
 * ;  * %LEVEL%   -- string representation of message level,
 * ;  * %SOURCE%  -- message source,
 * ;  * %FILE%    -- path ro file,
 * ;  * %LINE%    -- line number,
 * ;  * %MESSAGE% -- message.
 * ; Default format:
 * ; format.CLI.E_ERROR = "[ %DATE% %TIME% ] [ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"
 * ;
 * ; No sources by default.
 * ; source[] = "*" ; Means to log from all sources
 * ;
 * ;
 * ; Extra arguments for methods:
 * ;
 * ; See http://php.net/manual/en/wrappers.php
 * ; [core.log.Stream.%LEVEL%]
 * ; stream = "php://output"
 * ;
 * ;
 * ; See donbidon\Lib\FileSystem\Logger.
 * ; [core.log.File.%LEVEL%]
 * ; path     = "/path/to/file"
 * ; maxSize  = ... ; (int)
 * ; rotation = ... ; (int)
 * ; rights   = ... ; (int)
 * ;
 * ;;; }
 *
 * [core.log.Stream.E_ALL]
 * stream = "php://output"
 * source[] = "*"
 * [core.log.Stream.E_ALL.format.CLI]
 * E_NOTICE  = "[ %DATE% %TIME% ] [ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"
 * E_WARNING = "[ %DATE% %TIME% ] [ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"
 * E_ERROR   = "[ %DATE% %TIME% ] [ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"
 *
 * [core.log.Stream.E_ALL.format.web]
 * E_NOTICE  = "[ <b>%DATE% %TIME%</b> ] [ <b>%LEVEL%</b> ] [ %SOURCE% ] ~ %MESSAGE%<br />"
 * E_WARNING = "[ <b>%DATE% %TIME%</b> ] [ <b style="color: yellow;">%LEVEL%</b> ] [ %SOURCE% ] ~ <span style="color: yellow;">%MESSAGE%</span><br />"
 * E_ERROR   = "[ <b>%DATE% %TIME%</b> ] [ <b style="color: red;">%LEVEL%</b> ] [ %SOURCE% ] ~ <span style="color: red;">%MESSAGE%</span><br />"
 * ```
 * ```php
 * $registry = \donbidon\Core\Bootstrap::initByPath("/path/to/config.php");
 * ```
 * <!-- /move -->
 */
class Bootstrap
{
    /**
     * Exception code
     *
     * @see self::initByPath()
     */
    const EX_CANNOT_OPEN_CONFIG  = 0x01;

    /**
     * Exception code
     *
     * @see self::initByPath()
     */
    const EX_CANNOT_PARSE_CONFIG = 0x02;

    /**
     * Exception code
     *
     * @see self::initByArray()
     */
    const EX_INVALID_ARG         = 0x04;

    /**
     * Initializes environment by config file path.
     *
     * @param  string $path
     * @param  array $options  Array of options:
     *         - (int)registry - see Recursive::__construct().
     * @return I_Registry
     * @throws RuntimeException If passed file doesn't exist or cannot be read.
     * @throws RuntimeException If cannot parse config file.
     */
    public static function initByPath(
        $path, array $options = [
            'registry' => Recursive::ALL_INCLUSIVE,
        ]
    )
    {
        if (!file_exists($path) || !is_readable($path)) {
            throw new RuntimeException(
                sprintf("Cannot open config file \"%s\"", $path),
                self::EX_CANNOT_OPEN_CONFIG
            );
        }
        $config = \donbidon\Lib\Config\Ini::parse(
            file_get_contents($path), true
        );
        if (!is_array($config)) {
            throw new RuntimeException(
                sprintf("Cannot parse config file \"%s\"", $path),
                self::EX_CANNOT_PARSE_CONFIG
            );
        }
        $registry = static::initByArray($config, $options);

        return $registry;
    }

    /**
     * Initializes environment by config array.
     *
     * @param  array $config
     * @param  array $options  Array of options:
     *         - (int)registry - see Recursive::__construct().
     * @return I_Registry
     * @throws InvalidArgumentException
     */
    public static function initByArray(
        array $config, array $options = [
            'registry' => Recursive::ALL_INCLUSIVE,
        ]
    )
    {
        if (!is_array($config)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Passed argument isn't array (%s)",
                    gettype($config)
                ),
                self::EX_INVALID_ARG
            );
        }
        $registry = static::getRegistry($config, $options['registry']);
        // static::modifyRegistryOnStart($registry);
        $registry->set(
            'core/env', isset($_SERVER['DOCUMENT_URI']) ? "web" : "CLI"
        );
        $evtManager = new Event\Manager;
        $evtManager->setDebug($registry->get(
            'core/event/debug', false
        ));
        Log\Factory::run($registry, $evtManager);
        $registry->set('core/event/manager', $evtManager);

        return $registry;
    }

    /**
     * Returns registry instance.
     *
     * Called from {@see static::initByArray() here}.
     *
     * @param  array $config
     * @param  int   $options
     * @return I_Registry
     * @see    self::initByArray()
     */
    protected static function getRegistry(array $config, $options)
    {
//        $result = \donbidon\Core\Registry\Recursive::getInstance(
        $result = Recursive::getInstance(
            $config,
            $options
        );

        return $result;
    }

    /**
     * Stub method called after getting registry from config.
     *
     * @param  I_Registry $registry
     * @return void
     * @see    self::initByArray()
     */
    /*
    protected static function modifyRegistryOnStart(I_Registry $registry)
    {
    }
    */
}
