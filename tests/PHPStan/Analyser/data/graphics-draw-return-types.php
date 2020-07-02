<?php

use function PHPStan\Analyser\assertType;

$image = imagecreatetruecolor(1, 1);
$memoryHandle = fopen('php://memory', 'w');

assertType('bool', imagegd($image, 'php://memory'));
assertType('bool', imagegd($image, $memoryHandle));
assertType('string|false', imagegd($image, null));
