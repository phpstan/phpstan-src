<?php declare(strict_types = 1);

namespace NameResolutionMemoQualification;

use function PHPStan\Testing\assertType;

const PHP_EOL = 'namespaced';

function strlen(string $s): string
{
	return 'namespaced';
}

function test(string $s): void
{
	// the fully qualified name is asked first: its answer must not be
	// remembered for the unqualified name written the same way
	assertType('"\n"|"\r\n"', \PHP_EOL);
	assertType("'namespaced'", PHP_EOL);

	assertType('int<0, max>', \strlen($s));
	assertType('string', strlen($s));
}
