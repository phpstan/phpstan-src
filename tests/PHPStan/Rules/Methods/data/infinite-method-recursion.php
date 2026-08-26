<?php declare(strict_types = 1); // lint >= 8.1

namespace InfiniteMethodRecursion;

class HelloWorld
{

	/** @var string */
	private $world = '';

	public function getWorld(): string
	{
		return $this->getWorld();
	}

	public function withSideEffect(): string
	{
		echo 'x';

		return $this->withSideEffect();
	}

	public function concat(): string
	{
		return $this->concat() . 'x';
	}

	public function coalesceLeft(): ?string
	{
		return $this->coalesceLeft() ?? 'x';
	}

	public function assignThenReturn(): string
	{
		$x = $this->assignThenReturn();

		return $x;
	}

	public function insideArgument(): string
	{
		return strtoupper($this->insideArgument());
	}

	public static function staticSelf(): string
	{
		return self::staticSelf();
	}

	public static function staticLateSelf(): string
	{
		return static::staticLateSelf();
	}

	public static function staticClassName(): string
	{
		return HelloWorld::staticClassName();
	}

	public function withBaseCase(int $i): int
	{
		if ($i <= 0) {
			return 0;
		}

		return $this->withBaseCase($i - 1);
	}

	public function ternaryBaseCase(int $i): int
	{
		return $i <= 0 ? 0 : $this->ternaryBaseCase($i - 1);
	}

	public function coalesceRight(?string $other): string
	{
		return $other ?? $this->coalesceRight($other);
	}

	public function callsOtherMethod(): string
	{
		return $this->getWorld();
	}

	public function insideClosure(): string
	{
		$cb = function (): string {
			return $this->insideClosure();
		};

		return $cb();
	}

	public function firstClassCallable(): callable
	{
		return $this->firstClassCallable(...);
	}

	public function throwsFirst(): string
	{
		throw new \Exception();
	}

	public function __construct()
	{
		new self();
	}

	public function notConstructorNewSelf(): self
	{
		return new self();
	}

	/**
	 * @return \Generator<int, string>
	 */
	public function generator(): \Generator
	{
		yield $this->getWorld();
	}

}
