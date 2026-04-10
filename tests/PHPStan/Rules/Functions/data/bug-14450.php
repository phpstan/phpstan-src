<?php

// Built-in PHP functions called with optional parameters omitted.
// These should not report errors even when jetbrains/phpstorm-stubs
// is present in vendor/ with incorrect signatures (missing defaults).

$a = str_replace('foo', 'bar', 'foobar');
$b = substr('hello', 1);
$c = array_keys(['a' => 1, 'b' => 2]);
$d = strtotime('now');
$e = array_filter([0, 1, 2, '', null]);
$f = phpversion();
$g = ['a' => 1, 'b' => 2];
array_walk_recursive($g, function(&$v) { $v = ''; });
