<?php

namespace AccessPropertiesClassExists;

use function class_exists;

class Foo
{

	/** @var Bar|Baz */
	private $union;

	public function doFoo(): void
	{
		echo $this->union->lorem;

		if (class_exists(Bar::class)) {
			echo $this->union->lorem;
		}

		if (class_exists(Baz::class)) {
			echo $this->union->lorem;
		}

		if (class_exists(Bar::class) && class_exists(Baz::class)) {
			echo $this->union->lorem;
		}
	}

	public function doBar($arg): void
	{
		if (class_exists(Bar::class) && class_exists(Baz::class)) {
			if (is_int($arg->foo)) {
				echo $this->union->lorem;
			}
		}
	}

}
