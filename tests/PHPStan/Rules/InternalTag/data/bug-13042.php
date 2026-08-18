<?php declare(strict_types = 1);

namespace Bug13042;

trait SomeTrait
{
	/** @internal don't use it directly */
	private ?string $text = null;

	public function setText(string $text): void {
		$this->text = $text;
	}
}

class HelloWorld
{
	public function sayHello(): object
	{
		return new class {
			use SomeTrait;
		};
	}
}
