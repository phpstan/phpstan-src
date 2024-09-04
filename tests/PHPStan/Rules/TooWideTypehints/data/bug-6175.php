<?php

namespace Bug6175TooWide;

trait SomeTrait {
	private function sayHello(): ?string // @phpstan-ignore-line
	{
		return $this->value;
	}
}

class HelloWorld2
{
	use SomeTrait;
	private string $value = '';
	public function sayIt(): void
	{
		echo $this->sayHello();
	}
}
