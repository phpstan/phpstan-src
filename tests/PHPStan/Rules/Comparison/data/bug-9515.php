<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug9515;

trait Foo
{
	abstract public function getFoo(): ?string;

	public function getName(): string
	{
		$str = 'Hello';

		if ($this->getFoo() !== null) {
			$str .= ' World';
		}

		return $str;
	}
}

class Bar
{
	use Foo;

	public function getFoo(): string
	{
		return "Bar";
	}
}

class Zar
{
	use Foo;

	public function getFoo(): null
	{
		return null;
	}
}
