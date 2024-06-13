<?php // onlyif PHP_VERSION_ID < 80000

namespace Bug3336;

function (array $arr, string $str, $mixed): void {
	\PHPStan\Testing\assertType('array<int, string>', mb_convert_encoding($arr));
	\PHPStan\Testing\assertType('string', mb_convert_encoding($str));
	\PHPStan\Testing\assertType('array<int, string>|string|false', mb_convert_encoding($mixed));
	\PHPStan\Testing\assertType('array<int, string>|string|false', mb_convert_encoding());
};
