<?php

namespace Bug3336;

function (array $arr, string $str, $mixed): void {
	\PHPStan\Testing\assertType('array|false', mb_convert_encoding($arr));
	\PHPStan\Testing\assertType('string|false', mb_convert_encoding($str));
	\PHPStan\Testing\assertType('array<array<bool|float|int|string|null>|bool|float|int|string|null>|string|false', mb_convert_encoding($mixed));
	\PHPStan\Testing\assertType('array<array<bool|float|int|string|null>|bool|float|int|string|null>|string|false', mb_convert_encoding());
};
