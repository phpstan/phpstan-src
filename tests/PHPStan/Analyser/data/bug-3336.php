<?php

namespace Bug3336;

function (array $arr, string $str, $mixed): void {
	\PHPStan\Analyser\assertType('array<int, string>', mb_convert_encoding($arr));
	\PHPStan\Analyser\assertType('string', mb_convert_encoding($str));
	\PHPStan\Analyser\assertType('array<int, string>|string|false', mb_convert_encoding($mixed));
	\PHPStan\Analyser\assertType('array<int, string>|string|false', mb_convert_encoding());
};
