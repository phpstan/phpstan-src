<?php

namespace PregReplaceCallbackMatchShapes72;

use function PHPStan\Testing\assertType;

function (string $s): void {
	preg_replace_callback(
		$s,
		function ($matches) {
			assertType('array<int|string, string>', $matches);
			return '';
		},
		$s
	);
};

function (string $s): void {
	preg_replace_callback(
		'|<p>(\s*)\w|',
		function ($matches) {
			assertType('array{string, string}', $matches);
			return '';
		},
		$s
	);
};

// The flags parameter was added in PHP 7.4
