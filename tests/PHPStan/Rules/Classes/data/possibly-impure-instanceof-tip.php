<?php declare(strict_types = 1);

namespace PossiblyImpureInstanceofTip;

class Dog {}
class Cat extends Dog {}

class Holder
{
	/** @phpstan-pure */
	public function getAnimal(): Dog
	{
		return new Dog();
	}

	public function maybeImpureMethod(): int
	{
		return rand(1, 100);
	}

	/** @phpstan-pure */
	public function pureMethod(): int
	{
		return 42;
	}

	/** @phpstan-impure */
	public function impureMethod(): int
	{
		echo 'hello';
		return rand(1, 100);
	}
}

function testMaybeImpure(Holder $holder): void
{
	if ($holder->getAnimal() instanceof Cat) {
		$holder->maybeImpureMethod();

		// tip expected: maybeImpureMethod() might have changed the object
		if ($holder->getAnimal() instanceof Cat) {
			return;
		}
	}
}

function testPure(Holder $holder): void
{
	if ($holder->getAnimal() instanceof Cat) {
		$holder->pureMethod();

		// no tip - pureMethod() cannot change anything
		if ($holder->getAnimal() instanceof Cat) {
			return;
		}
	}
}

function testImpure(Holder $holder): void
{
	if ($holder->getAnimal() instanceof Cat) {
		$holder->impureMethod();

		// no error - $holder invalidated by impure call
		if ($holder->getAnimal() instanceof Cat) {
			return;
		}
	}
}
