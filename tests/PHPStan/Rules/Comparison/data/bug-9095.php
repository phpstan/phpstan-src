<?php declare(strict_types = 1);

namespace Bug9095;

class HelloWorld
{
	use SomeTrait;

	public string $message = 'Hello';

	public function foo(): void
	{
		$this->bar();
	}
}

class EmptyClass
{
	use SomeTrait;
}

trait SomeTrait
{
	public function bar(): void
	{
		if (property_exists($this, 'message')) {
			if (!is_string($this->message)) {
				return;
			}

			echo $this->message . "\n";
		}
	}
}
