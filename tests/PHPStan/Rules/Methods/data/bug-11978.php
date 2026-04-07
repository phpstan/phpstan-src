<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug11978;

interface ViewA {
	public function render(): string;
}
interface ViewB {
	public function render(string $foo = ''): string;
}

class Foo
{
	public function __construct(
		private readonly ViewA&ViewB $view1,
		private readonly ViewB&ViewA $view2,
	) {}

	public function renderFoo(string $foo): string
	{
		$a = $this->view1->render($foo);
		$b = $this->view2->render($foo);
		$c = $this->view1->render($foo, $foo);
		$d = $this->view2->render($foo, $foo);
		$e = $this->view1->render();
		$f = $this->view2->render();

		return $a . $b;
	}
}
