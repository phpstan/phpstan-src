<?php declare(strict_types = 1);

trait Bug13042SomeTrait
{
	/** @internal don't use it directly */
	private ?string $text = null;

	public function setText(string $text): void {
		$this->text = $text;
	}
}

class Bug13042HelloWorld
{
	public function sayHello(): object
	{
		return new class {
			use Bug13042SomeTrait;
		};
	}
}
