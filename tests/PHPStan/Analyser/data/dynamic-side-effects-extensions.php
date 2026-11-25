<?php // lint >= 8.0

namespace DynamicSideEffectsExtensions;

use function PHPStan\Testing\assertType;

// TestDynamicSideEffectsExtensions: a call with arguments has exactly the
// effects its arguments name ('none' for none), a call without arguments has
// the declared ones

function impureFunction(string ...$effects): void
{
}

/** @phpstan-pure */
function pureFunction(string ...$effects): int
{
	return 1;
}

/** @phpstan-impure */
function impureIntFunction(string ...$effects): int
{
	return 1;
}

class Box
{

	public int $v = 0;

	public static int $s = 0;

	public function impure(string ...$effects): void
	{
	}

	/** @phpstan-pure */
	public function pure(string ...$effects): int
	{
		return 1;
	}

	/** @phpstan-impure */
	public function impureInt(string ...$effects): int
	{
		return 1;
	}

	public static function impureStatic(string ...$effects): void
	{
	}

	/** @phpstan-pure */
	public static function pureStatic(string ...$effects): int
	{
		return 1;
	}

	/** @phpstan-impure */
	public static function impureIntStatic(string ...$effects): int
	{
		return 1;
	}

	public function staticCallWritesClass(): void
	{
		self::$s = 1;
		self::impureStatic('none');
		assertType('1', self::$s);
		self::impureStatic();
		assertType('int', self::$s);

		self::$s = 1;
		self::pureStatic('writes-this');
		assertType('int', self::$s);
		self::$s = 1;
		self::pureStatic();
		assertType('1', self::$s);
	}

}

class Made
{

	public function __construct(string ...$effects)
	{
	}

}

class PureMade extends Made
{

	/** @phpstan-pure */
	public function __construct(string ...$effects)
	{
	}

}

function methodCallWritesReceiver(Box $box): void
{
	$box->v = 1;
	$box->impure('none');
	assertType('1', $box->v);
	$box->impure();
	assertType('int', $box->v);

	$box->v = 1;
	$box->pure('writes-this');
	assertType('int', $box->v);
	$box->v = 1;
	$box->pure();
	assertType('1', $box->v);
}

function resultsRemembered(Box $box): void
{
	if (impureIntFunction('none') === 1) {
		assertType('1', impureIntFunction('none'));
	}
	if (impureIntFunction() === 1) {
		assertType('int', impureIntFunction());
	}
	if (pureFunction('nondeterministic') === 1) {
		assertType('int', pureFunction('nondeterministic'));
	}
	if (pureFunction() === 1) {
		assertType('1', pureFunction());
	}

	if ($box->impureInt('none') === 1) {
		assertType('1', $box->impureInt('none'));
	}
	if ($box->impureInt() === 1) {
		assertType('int', $box->impureInt());
	}
	if ($box->pure('nondeterministic') === 1) {
		assertType('int', $box->pure('nondeterministic'));
	}
	if ($box->pure() === 1) {
		assertType('1', $box->pure());
	}

	if (Box::impureIntStatic('none') === 1) {
		assertType('1', Box::impureIntStatic('none'));
	}
	if (Box::impureIntStatic() === 1) {
		assertType('int', Box::impureIntStatic());
	}
	if (Box::pureStatic('nondeterministic') === 1) {
		assertType('int', Box::pureStatic('nondeterministic'));
	}
	if (Box::pureStatic() === 1) {
		assertType('1', Box::pureStatic());
	}
}

function globalStateForgotten(Box $box): void
{
	if (ini_get('precision') !== '14') {
		return;
	}

	impureFunction('io.output');
	assertType("'14'", ini_get('precision'));
	$box->impure('writes-this');
	assertType("'14'", ini_get('precision'));
	Box::impureStatic('none');
	assertType("'14'", ini_get('precision'));
	new Made('none');
	assertType("'14'", ini_get('precision'));
	error_reporting();
	assertType("'14'", ini_get('precision'));

	pureFunction('writes-global');
	assertType('numeric-string', ini_get('precision'));
}

function globalStateForgottenByMethod(Box $box): void
{
	if (ini_get('precision') === '14') {
		$box->pure('writes-global');
		assertType('numeric-string', ini_get('precision'));
	}
	if (ini_get('precision') === '14') {
		Box::pureStatic('writes-global');
		assertType('numeric-string', ini_get('precision'));
	}
	if (ini_get('precision') === '14') {
		new PureMade('writes-global');
		assertType('numeric-string', ini_get('precision'));
	}
}

function globalStateForgottenWithoutExtension(Box $box): void
{
	if (ini_get('precision') === '14') {
		impureFunction();
		assertType('numeric-string', ini_get('precision'));
	}
	if (ini_get('precision') === '14') {
		$box->impure();
		assertType('numeric-string', ini_get('precision'));
	}
	if (ini_get('precision') === '14') {
		Box::impureStatic();
		assertType('numeric-string', ini_get('precision'));
	}
	if (ini_get('precision') === '14') {
		// a constructor that only may have side effects keeps the results of global state getters
		new Made();
		assertType("'14'", ini_get('precision'));
	}
	if (ini_get('precision') === '14') {
		error_reporting(E_ALL);
		assertType('numeric-string', ini_get('precision'));
	}
}
