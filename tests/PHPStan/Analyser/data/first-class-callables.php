<?php // lint >= 8.1

namespace FirstClassCallables;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(string $foo): void
	{
		assertType('Closure(string): void', $this->doFoo(...));
		assertType('Closure(): void', self::doBar(...));
		assertType('Closure', self::$foo(...));
		assertType('Closure', $this->nonexistent(...));
		assertType('Closure', $this->$foo(...));
		assertType('Closure(string): int<0, max>', strlen(...));
		assertType('Closure(string): int<0, max>', 'strlen'(...));
		assertType('Closure', 'nonexistent'(...));
		assertType('*ERROR*', new self(...));
	}

	public static function doBar(): void
	{

	}

}
