<?php declare(strict_types = 1);

namespace Bug6589;

class Field {}

class Field2 extends Field {}

/**
 * @template TField of Field2
 */
class HelloWorldTemplated
{
	/** @return TField */
	public function getField()
	{
		return new Field();
	}

	/** @return TField */
	public function getField2()
	{
		return new Field2();
	}
}

class HelloWorldSimple
{
    public function getField(string $name): Field2
    {
        return new Field();
    }
}
