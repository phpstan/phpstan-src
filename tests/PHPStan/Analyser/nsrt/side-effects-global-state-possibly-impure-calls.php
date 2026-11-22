<?php // lint >= 8.0

declare(strict_types = 1);

namespace SideEffectsGlobalStatePossiblyImpureCalls;

use function PHPStan\Testing\assertType;

function possiblyImpure(): int
{
	return 1;
}

/** @phpstan-impure */
function impure(): int
{
	return 1;
}

class Service
{

	public function get(): int
	{
		return 1;
	}

	/** @phpstan-impure */
	public function change(): int
	{
		return 1;
	}

	public static function create(): self
	{
		return new self();
	}

	/** @phpstan-impure */
	public static function reset(): int
	{
		return 1;
	}

}

class PossiblyImpureConstructor
{

	public function __construct(public int $value)
	{
	}

}

class ImpureConstructor
{

	/** @phpstan-impure */
	public function __construct()
	{
	}

}

class Foo
{

	/** like the results of possibly impure calls, those of global state getters stay remembered */
	public function possiblyImpureCall(Service $service, string $file): void
	{
		if (ini_get('precision') === '14' && filesize($file) > 0) {
			possiblyImpure();
			assertType("'14'", ini_get('precision'));
			assertType('int<1, max>', filesize($file));
		}
		if (ini_get('precision') === '14' && filesize($file) > 0) {
			$service->get();
			assertType("'14'", ini_get('precision'));
			assertType('int<1, max>', filesize($file));
		}
		if (ini_get('precision') === '14' && filesize($file) > 0) {
			Service::create();
			assertType("'14'", ini_get('precision'));
			assertType('int<1, max>', filesize($file));
		}
		if (ini_get('precision') === '14' && filesize($file) > 0) {
			new PossiblyImpureConstructor(1);
			assertType("'14'", ini_get('precision'));
			assertType('int<1, max>', filesize($file));
		}
	}

	public function impureCall(Service $service, string $file): void
	{
		if (ini_get('precision') === '14' && filesize($file) > 0) {
			impure();
			assertType('numeric-string', ini_get('precision'));
			assertType('int<0, max>|false', filesize($file));
		}
		if (ini_get('precision') === '14' && filesize($file) > 0) {
			$service->change();
			assertType('numeric-string', ini_get('precision'));
			assertType('int<0, max>|false', filesize($file));
		}
		if (ini_get('precision') === '14' && filesize($file) > 0) {
			Service::reset();
			assertType('numeric-string', ini_get('precision'));
			assertType('int<0, max>|false', filesize($file));
		}
		if (ini_get('precision') === '14' && filesize($file) > 0) {
			new ImpureConstructor();
			assertType('numeric-string', ini_get('precision'));
			assertType('int<0, max>|false', filesize($file));
		}
	}

}
