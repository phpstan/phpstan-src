<?php declare(strict_types = 1);

namespace Bug11201;

use function PHPStan\Testing\assertType;

/** @return array<string> */
function returnsArray(){
	return [];
}

/** @return non-empty-string */
function returnsNonEmptyString(): string
{
	return 'a';
}

/** @return non-falsy-string */
function returnsNonFalsyString(): string
{
	return '1';
}

/** @return string */
function returnsJustString(): string
{
	return rand(0,1) === 1 ? 'foo' : '';
}

$s = sprintf("%s", returnsNonEmptyString());
assertType('non-empty-string', $s);

$s = sprintf("%s", returnsNonFalsyString());
assertType('non-falsy-string', $s);

$s = sprintf("%s", returnsJustString());
assertType('string', $s);

$s = sprintf("%s", implode(', ', array_map('intval', returnsArray())));
assertType('string', $s);

$s = sprintf('%2$s', 1234, returnsNonFalsyString());
assertType('non-falsy-string', $s);
