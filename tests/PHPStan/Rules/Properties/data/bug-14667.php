<?php // lint >= 8.0

namespace Bug14667;

/** @param mixed $row */
function testImplicitMixed($row): void
{
	if (property_exists($row, 'prop')) {
		echo $row->prop;
	}
}

function testExplicitMixed(mixed $row): void
{
	if (property_exists($row, 'prop')) {
		echo $row->prop;
	}
}

/** @param object|string $row */
function testObjectOrString($row): void
{
	if (property_exists($row, 'prop')) {
		echo $row->prop;
	}
}

function testObject(object $row): void
{
	if (property_exists($row, 'prop')) {
		echo $row->prop;
	}
}

final class Foo
{
	public function testThis(): void
	{
		if (property_exists($this, 'default')) {
			echo $this->default;
		}
	}

	/** @param self $obj */
	public function testSelf(self $obj): void
	{
		if (property_exists($obj, 'default')) {
			echo $obj->default;
		}
	}

	/** @param mixed $x */
	public function testMixedChained($x): void
	{
		if (property_exists($x, 'name') && $x->name !== null) {
			echo $x->name;
		}
	}
}

/** @param class-string $row */
function testClassString(string $row): void
{
	if (property_exists($row, 'prop')) {
		echo $row->prop; // error: Cannot access property $prop on class-string.
	}
}
