<?php

use function PHPStan\Analyser\assertType;

$image = imagecreatetruecolor(1, 1);

assertType('bool', imagegd($image, 'php://memory'));
assertType('string', imagegd($image, null));
