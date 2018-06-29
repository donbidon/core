# Core

Look [API documentation](https://donbidon.github.io/docs/packages/core/).

## Installing
Run `composer require donbidon/core 0.1.0` or add following code to your "composer.json" file:
```json
    "require": {
        "donbidon/core": "0.1.0"
    }
```
and run `composer update`.

## Usage

### Starting up full core environment
"config.php" file:
```ini
; <?php die; __halt_compiler();

[core]

; By default: Off
event[debug] = On

;;; Log section {
;
; Supported methods (%METHOD%): Stream, File.
; Supported levels (%LEVEL%): E_NOTICE, E_WARNING, E_ERROR, E_ERROR_WARNING,
;                             E_ERROR_NOTICE, E_WARNING_NOTICE, E_ALL.
;
; [core.log.%METHOD%.%LEVEL%]
; Class name including namespace to use own loggers, not set by default.
; class = "\\own\\namespace\\Logger"
;
; Supported variables for format:
;  * %DATE%    -- current date,
;  * %TIME%    -- current time,
;  * %LEVEL%   -- string representation of message level,
;  * %SOURCE%  -- message source,
;  * %FILE%    -- path ro file,
;  * %LINE%    -- line number,
;  * %MESSAGE% -- message.
; Default format:
; format.CLI.E_ERROR = "[ %DATE% %TIME% ] [ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"
;
; No sources by default.
; source[] = "*" ; Means to log from all sources
;
;
; Extra arguments for methods:
;
; See http://php.net/manual/en/wrappers.php
; [core.log.Stream.%LEVEL%]
; stream = "php://output"
;
;
; See donbidon\Lib\FileSystem\Logger.
; [core.log.File.%LEVEL%]
; path     = "/path/to/file"
; maxSize  = ... ; (int)
; rotation = ... ; (int)
; rights   = ... ; (int)
;
;;; }

[core.log.Stream.E_ALL]
stream = "php://output"
source[] = "*"

[core.log.Stream.E_ALL.format.CLI]
E_NOTICE  = "[ %DATE% %TIME% ] [ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"
E_WARNING = "[ %DATE% %TIME% ] [ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"
E_ERROR   = "[ %DATE% %TIME% ] [ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"

[core.log.Stream.E_ALL.format.web]
E_NOTICE  = "[ <b>%DATE% %TIME%</b> ] [ <b>%LEVEL%</b> ] [ %SOURCE% ] ~ %MESSAGE%<br />"
E_WARNING = "[ <b>%DATE% %TIME%</b> ] [ <b style="color: yellow;">%LEVEL%</b> ] [ %SOURCE% ] ~ <span style="color: yellow;">%MESSAGE%</span><br />"
E_ERROR   = "[ <b>%DATE% %TIME%</b> ] [ <b style="color: red;">%LEVEL%</b> ] [ %SOURCE% ] ~ <span style="color: red;">%MESSAGE%</span><br />"
```
```php
$registry = \donbidon\Core\Bootstrap::initByPath("/path/to/config.php");
```
