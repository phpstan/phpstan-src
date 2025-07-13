<?php declare(strict_types = 1); // lint >= 8.0

namespace DynamicStringableNullsafeAccess;

use Stringable;

final class Foo
{
	private self $var;

	public function testNullsafePropertyFetch(string $name, Stringable $stringable, object $object): void
	{
		echo $this?->{$this}?->name;
		echo $this?->var?->$this;
		echo $this?->$this?->$name;
		echo $this?->$this?->name;
		echo $this?->$object;

		echo $this?->$name; // valid
		echo $this?->$stringable; // valid
		echo $this?->{1111}; // valid
		echo $this?->{true}; // valid
		echo $this?->{false}; // valid
		echo $this?->{null}; // valid
	}

}
