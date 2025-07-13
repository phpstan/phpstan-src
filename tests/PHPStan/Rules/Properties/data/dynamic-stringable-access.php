<?php declare(strict_types = 1);

namespace DynamicStringableAccess;

use Stringable;

final class Foo
{
	private self $var;

	public function testProperties(string $name, Stringable $stringable, object $object, array $array): void
	{
		echo $this->{$this}->name;
		echo $this->var->$this;
		echo $this->$this->$name;
		echo $this->$this->name;
		echo $this->$object;
		echo $this->$array;

		echo $this->$name; // valid
		echo $this->$stringable; // valid
		echo $this->{1111}; // valid
		echo $this->{true}; // valid
		echo $this->{false}; // valid
		echo $this->{null}; // valid
	}

	public function testPropertyAssignments(string $name, Stringable $stringable, object $object): void
	{
		$this->{$this} = $name;
		$this->var->{$this} = $name;
		$this->$object = $name;

		$this->$name = $name; // valid
		$this->$stringable = $name; // valid
		$this->{1111} = $name; // valid
		$this->{true} = $name; // valid
		$this->{false} = $name; // valid
		$this->{null} = $name; // valid
	}

}
