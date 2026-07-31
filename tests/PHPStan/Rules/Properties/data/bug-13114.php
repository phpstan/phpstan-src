<?php declare(strict_types = 1);

namespace Bug13114Property;

class C {
	static function f(): void {}
}

class Holder
{

	/** @var callable-array */
	private $implicit;

	/** @var callable&array<mixed> */
	private $explicit;

	public function doFoo(): void
	{
		$this->implicit = [new C, 'h'];
		$this->explicit = [new C, 'h'];
		$this->implicit = 42;
	}

	public function doBar(): void
	{
		$this->implicit = [C::class, 'f'];
		$this->explicit = [C::class, 'f'];
	}

}
