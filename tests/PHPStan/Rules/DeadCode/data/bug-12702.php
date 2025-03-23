<?php // lint >= 8.4

namespace Bug12702;

class Foo
{
	/**
	 * @var string[]
	 */
	public array $x = [];
	private ?string $i { get => $this->x[$this->k] ?? null; }
	private int $k = 0;

	public function x(): void {
		echo $this->i;
	}
}

class Bar
{
	/**
	 * @var string[]
	 */
	public array $x = [];
	private ?string $i {
		set {
			$this->x[$this->k] = $value;
		}
	}
	private int $k = 0;

	public function x(): void {
		$this->i = 'foo';
	}
}

class Foo2
{
	/**
	 * @var string[]
	 */
	public array $x = [];
	private ?string $i { get => $this->x[$this->k] ?? null; }
	private int $k = 0;

}

class Bar2
{
	/**
	 * @var string[]
	 */
	public array $x = [];
	private ?string $i {
		set {
			$this->x[$this->k] = $value;
		}
	}
	private int $k = 0;

}
