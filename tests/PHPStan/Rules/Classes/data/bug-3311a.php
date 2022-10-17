<?php declare(strict_types = 1); // lint >= 7.4

namespace Bug3311a;

final class Foo
{
	/**
	 * @var array<int, string>
	 * @psalm-var list<string>
	 */
	public array $bar = [];

	/**
	 * @param array<int, string> $bar
	 * @psalm-param list<string> $bar
	 */
	public function __construct(array $bar)
	{
		$this->bar = $bar;
	}
}

function () {
	$instance = new Foo([1 => 'baz']);
};
