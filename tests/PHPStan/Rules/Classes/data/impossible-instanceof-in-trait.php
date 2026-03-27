<?php

namespace ImpossibleInstanceofInTrait;

class Dog {}
class Cat {}

trait FooTrait
{

	/** @var Dog|Cat */
	protected $animal;

	public function doFoo(): void
	{
		// sometimes true, sometimes false
		if ($this->animal instanceof Dog) {

		}
	}

	public function doFoo2(): void
	{
		// always false
		if ($this->animal instanceof \stdClass) {

		}
	}

}

class Foo
{

	/** @use FooTrait */
	use FooTrait;

	/** @var Dog */
	protected $animal;

}

class FooAnother
{

	/** @use FooTrait */
	use FooTrait;

	/** @var Cat */
	protected $animal;

}
