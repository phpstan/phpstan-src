<?php declare(strict_types = 1);

namespace Bug14349;

class Foo
{

	/** @param array<int> $a */
	public function doFoo(array $a): void
	{
		foreach ($a as $this) {
			var_dump($this);
		}

		foreach ($a as &$this) {
			var_dump($this);
		}

		foreach ($a as $this => $v) {
			var_dump($this);
		}

		foreach ($a as $ok) {
			var_dump($ok);
		}

		$this = 1;
		$this = new self();
		$this .= 'foo';
		[$this] = [1];
	}

	public static function doBar(): void
	{
		$this = 1;
	}

}

function baz(): void
{
	$this = 1;
}
