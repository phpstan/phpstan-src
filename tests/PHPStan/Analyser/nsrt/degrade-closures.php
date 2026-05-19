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
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
$arr[] = static function () {};
assertType('array{static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void, static-Closure(): void}', $arr);

$arr[] = static function () {};
assertType('non-empty-list<callable(): mixed>&oversized-array', $arr);
