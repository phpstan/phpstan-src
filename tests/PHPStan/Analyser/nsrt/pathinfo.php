<?php

namespace pathinfoInference;

use function PHPStan\Testing\assertType;

function doFoo(string $s,  int $i) {
	assertType('array{dirname?: string, basename: string, extension?: string, filename: string}|string', pathinfo($s,  $i));
	assertType('array{dirname?: string, basename: string, extension?: string, filename: string}', pathinfo($s));

	assertType('string', pathinfo($s, PATHINFO_DIRNAME));
	assertType('string', pathinfo($s, PATHINFO_BASENAME));
	assertType('string', pathinfo($s, PATHINFO_EXTENSION));
	assertType('string', pathinfo($s, PATHINFO_FILENAME));
}
