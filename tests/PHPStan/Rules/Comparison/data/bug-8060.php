<?php declare(strict_types = 1);

namespace Bug8060;

trait ExampleTrait
{
	public function doSomething(): void
	{
		$anything = $this->getAnything();

		if ($anything !== null) {
			return;
		}

		echo 'foo';
	}

	abstract protected function getAnything(): ?string;
}

class Example
{
	use ExampleTrait;

	protected function getAnything(): string
	{
		return 'foo';
	}
}

class Example2
{
	use ExampleTrait;

	protected function getAnything(): ?string
	{
		return 'foo';
	}
}
