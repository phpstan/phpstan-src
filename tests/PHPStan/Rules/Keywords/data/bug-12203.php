<?php

namespace Bug12203;

require_once '../bug-12203-sure-does-not-exist.php';
require_once __DIR__ . '/../bug-12203-sure-does-not-exist.php';

$path = '..';
$file = 'bug-12203-sure-does-not-exist.php';
require_once __DIR__ . '/'. $path .'/'. $file;

require_once __DIR__ . "$path/$file";

require_once __DIR__ . DIRECTORY_SEPARATOR. $path .'/'. $file;
require_once '..'. \DIRECTORY_SEPARATOR .'bug-12203-sure-does-not-exist.php';
