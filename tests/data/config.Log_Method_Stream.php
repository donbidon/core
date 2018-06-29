; <?php die; __halt_compiler();

[defaults.log.format]
E_ERROR   = "[ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"
E_WARNING = "[ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"
E_NOTICE  = "[ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"

[core.log.Stream.E_ALL]
stream = "php://output"
format.CLI = "~~> defaults/log/format"
source[] = "*"
