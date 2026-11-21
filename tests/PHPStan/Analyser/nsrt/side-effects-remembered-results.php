<?php // lint >= 8.0

declare(strict_types = 1);

namespace SideEffectsRememberedResults;

use SplFileObject;
use SplFixedArray;
use function PHPStan\Testing\assertType;

/** @phpstan-assert-if-true =3 $v */
function isThree(mixed $v): bool
{
	return $v === 3;
}

/** @return ($v is 3 ? true : false) */
function isThreeConditional(mixed $v): bool
{
	return $v === 3;
}

class Foo
{

	public function outputOnly(): void
	{
		// io.output: the call has effects, but the same arguments give the same result
		if (printf('%s', 'abc') === 3) {
			assertType('3', printf('%s', 'abc'));
		}
	}

	public function nondeterministic(): void
	{
		if (time() === 5) {
			assertType('int<1, max>', time());
		}
	}

	/** @param SplFixedArray<int> $a */
	public function writesThis(SplFixedArray $a): void
	{
		if ($a->setSize(3)) {
			assertType('true', $a->setSize(3));
		}
	}

	public function nondeterministicMethod(SplFileObject $f): void
	{
		if ($f->fgets() === 'abc') {
			assertType('string', $f->fgets());
		}
	}

	public function writesGlobal(): void
	{
		if (\Locale::setDefault('cs')) {
			assertType('true', \Locale::setDefault('cs'));
		}
	}

	public function nondeterministicStatic(): void
	{
		if (\IntlCalendar::getNow() === 1.0) {
			assertType('float', \IntlCalendar::getNow());
		}
	}

	/** @param array<int, string|null> $arr */
	public function nestedOutputOnly(array $arr): void
	{
		if (isThree(printf('%s', 'abc'))) {
			assertType('3', printf('%s', 'abc'));
		}
		if (isThreeConditional(printf('%s', 'abc'))) {
			assertType('3', printf('%s', 'abc'));
		}
		if (isset($arr[printf('%s', 'abc')])) {
			assertType('string', $arr[printf('%s', 'abc')]);
		}
	}

	/** @param array<int, string|null> $arr */
	public function nestedNondeterministic(array $arr): void
	{
		if (isThree(time())) {
			assertType('int<1, max>', time());
		}
		if (isset($arr[time()])) {
			assertType('string|null', $arr[time()]);
		}
	}

}
