<?php declare(strict_types=1);

proc_open('echo "hello world"', [], $pipes);
proc_open(['echo', 'hello world'], [], $pipes);

proc_open(['something' => 'bogus', 'in' => 'here'], [], $pipes);
