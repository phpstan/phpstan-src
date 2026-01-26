<?php

namespace DegradeClosures;

use function PHPStan\Testing\assertType;

$arr = [];
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function ():int {return 1;};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function ():bool {return true;};
assertType('array{Closure(): void, Closure(): void, Closure(): void, Closure(): void, Closure(): void, Closure(): void, Closure(): void, Closure(): 1, Closure(): void, Closure(): void, Closure(): void, Closure(): void, Closure(): void, Closure(): void, Closure(): true}', $arr);

$arr[] = static function () {};
assertType('non-empty-list<callable(): (1|void|true)>&oversized-array', $arr);
