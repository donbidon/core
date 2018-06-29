; <?php die; __halt_compiler();

[defaults.log.format]
E_ERROR   = "[ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"
E_WARNING = "[ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"
E_NOTICE  = "[ %LEVEL% ] [ %SOURCE% ] ~ %MESSAGE%"


[core.log.Stream.E_ERROR]
format.CLI = "~~> defaults/log/format"
stream = "php://output"
source[] = "*"

[core.log.Stream.E_WARNING]
stream = "php://output"
format.CLI = "~~> defaults/log/format"
source[] = "LogTest::otherMethod()"

[core.log.File.E_WARNING]
format.CLI = "~~> defaults/log/format"
rights = 0666
source[] = "*"
