; <?php die; __halt_compiler();

[defaults.log.format]
E_ERROR   = "[ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"
E_WARNING = "[ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"
E_NOTICE  = "[ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"


[core.log.File.E_ALL]
format.CLI = "~~> defaults/log/format"
source[] = "*"
; 'path' will be set from test
maxSize  = 1024
rotation = 0
rights    = 0666
