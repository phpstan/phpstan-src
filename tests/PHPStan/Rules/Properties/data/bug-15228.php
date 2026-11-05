<?php declare(strict_types = 1);

namespace Bug15228;

class CoalesceAssignDemo
{
	public ?int $a = null;

	/** @var list<int>|null */
	public $b;

	/** @return list<string> */
	private function strings(): array
	{
		return ['x'];
	}

	public function coalesceInvalid(): void
	{
		$this->a ??= 'boom';
	}

	public function ifInvalid(): void
	{
		if (null === $this->a) {
			$this->a = 'boom';
		}
	}

	public function coalesceValid(): void
	{
		$this->b = $this->strings();
		$this->b ??= [1, 2];
	}

	public function ifValid(): void
	{
		$this->b = $this->strings();
		if (null === $this->b) {
			$this->b = [1, 2];
		}
	}
}

class StaticCoalesceAssignDemo
{
	public static ?int $a = null;

	/** @var list<int>|null */
	public static $b;

	/** @return list<string> */
	private static function strings(): array
	{
		return ['x'];
	}

	public static function coalesceInvalid(): void
	{
		self::$a ??= 'boom';
	}

	public static function coalesceValid(): void
	{
		self::$b = self::strings();
		self::$b ??= [1, 2];
	}
}

class OffsetCoalesceAssignDemo
{
	/** @var array<string, int> */
	public array $a = [];

	/** @var array<string, list<int>|null> */
	public array $b = [];

	public function coalesceInvalid(): void
	{
		$this->a['x'] ??= 'boom';
	}

	public function coalesceValid(): void
	{
		$this->b['x'] ??= [1, 2];
	}
}

class OtherAssignOpsDemo
{
	public ?int $a = null;

	/** @var int<0, 10> */
	public int $r = 0;

	public function concat(): void
	{
		$this->a .= 'x';
	}

	public function concatExpanded(): void
	{
		$this->a = $this->a . 'x';
	}

	public function plus(): void
	{
		$this->r += 5;
	}

	public function plusExpanded(): void
	{
		$this->r = $this->r + 5;
	}
}
