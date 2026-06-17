<?php // lint >= 8.0

namespace ConstantParameterCheck;

$a = [];
$fp = fopen('foo', 'r');
if (!is_resource($fp)) {
	throw new \Exception();
}

// wrong constant for json_encode $flags
json_encode([], SORT_REGULAR);

// correct constant
json_encode([], JSON_PRETTY_PRINT);

// correct bitmask
json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

// wrong constant in bitmask
json_encode([], JSON_PRETTY_PRINT | SORT_REGULAR);

// sort: correct bitmask with modifier
sort($a, SORT_STRING | SORT_FLAG_CASE);

// sort: exclusive group violation
sort($a, SORT_NUMERIC | SORT_STRING);

// sort: exclusive group violation with modifier
sort($a, SORT_NUMERIC | SORT_STRING | SORT_FLAG_CASE);

// htmlspecialchars: two exclusive groups violated
htmlspecialchars('foo', ENT_QUOTES | ENT_NOQUOTES | ENT_HTML401 | ENT_HTML5);

// htmlspecialchars: one from each group is fine
htmlspecialchars('foo', ENT_QUOTES | ENT_HTML5);

// filter_var: wrong constant for $filter
filter_var('foo', SORT_REGULAR);

// filter_var: correct constant
filter_var('foo', FILTER_VALIDATE_EMAIL);

// json_decode: correct constant in $flags
json_decode('{}', true, 512, JSON_THROW_ON_ERROR);

// json_decode: correct bitmask in $flags
json_decode('{}', true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);

// json_decode: wrong constant in $flags
json_decode('{}', true, 512, JSON_PRETTY_PRINT);

// flock: exclusive group violation
flock($fp, LOCK_SH | LOCK_EX);

// flock: correct - lock type + modifier
flock($fp, LOCK_SH | LOCK_NB);

// non-constant argument - should not report
$flags = 0;
json_encode([], $flags);

// integer literal - should not report
json_encode([], 0);

// named argument with correct constant
json_decode('{}', flags: JSON_THROW_ON_ERROR);

// named argument with wrong constant
json_encode('{}', flags: SORT_REGULAR);

// array_unique: single-value parameter - correct single constant
array_unique($a, SORT_STRING);

// array_unique: single-value parameter - bitmask not allowed
array_unique($a, SORT_REGULAR | SORT_NUMERIC);

// filter_var: single-value parameter - bitmask not allowed
filter_var('foo', FILTER_VALIDATE_EMAIL | FILTER_VALIDATE_URL);

// round: single-value parameter - correct
round(1.5, 0, PHP_ROUND_HALF_UP);

class Foo
{
	private const PASSWORD_ALGORITHM = PASSWORD_ARGON2ID;

	// user-defined class constant wrapping a valid constant - should not report
	public function hashPassword(string $password): string
	{
		return password_hash($password, self::PASSWORD_ALGORITHM);
	}
}

// user-defined global constant wrapping a valid constant - should not report
define('MY_JSON_FLAGS', JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
json_encode([], MY_JSON_FLAGS);

json_decode('{}', null, JSON_THROW_ON_ERROR);

// passing true/false/null should not report
json_decode($json, true);
json_decode($json, null);
json_decode($json, false);

// PHP_OS passed to $subject of preg_match - should not report
preg_match('/foo/', PHP_OS);
