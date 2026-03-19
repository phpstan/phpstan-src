<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug14328;

$callback = fn (): never => throw new \Exception();

class Foo {
	public function returnThis(mixed $value): self {
		return $this;
	}
}

$x = new Foo()->returnThis($callback())->returnThis('x');
$y = 'this will never run';
