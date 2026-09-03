<?php declare(strict_types = 1);

namespace Bug15170;

class A
{

	/** @var resource|null */
	private $process = null;

	public function open(): void
	{
		$process = fopen('php://memory', 'r');
		if ($process === false) {
			return;
		}

		$this->process = $process;
	}

	public function withAssert(): void
	{
		if (!$this->impureAssert()) {
			return;
		}

		if ($this->impureAssert()) {
			echo 'x';
		}
	}

	public function withAssertLoop(): void
	{
		if (!$this->impureAssert()) {
			return;
		}

		$timeout = microtime(true) + 5.0;

		while ($this->impureAssert() && microtime(true) < $timeout) {
			usleep(100000);
		}
	}

	// unaffected: the same shape without the assert tag
	public function withoutAssert(): void
	{
		if (!$this->impurePlain()) {
			return;
		}

		if ($this->impurePlain()) {
			echo 'x';
		}
	}

	/**
	 * @phpstan-impure
	 * @phpstan-assert-if-true !null $this->process
	 */
	private function impureAssert(): bool
	{
		return $this->process !== null && (bool) microtime(true);
	}

	/** @phpstan-impure */
	private function impurePlain(): bool
	{
		return (bool) microtime(true);
	}

}

class B
{

	/** @var resource|null */
	public $process = null;

	public static function withAssert(self $b): void
	{
		if (!self::impureAssert($b)) {
			return;
		}

		if (self::impureAssert($b)) {
			echo 'x';
		}
	}

	public static function withAssertLoop(self $b): void
	{
		if (!self::impureAssert($b)) {
			return;
		}

		$timeout = microtime(true) + 5.0;

		while (self::impureAssert($b) && microtime(true) < $timeout) {
			usleep(100000);
		}
	}

	/**
	 * @phpstan-impure
	 * @phpstan-assert-if-true !null $b->process
	 */
	private static function impureAssert(self $b): bool
	{
		return $b->process !== null && (bool) microtime(true);
	}

}

class D
{

	/** @var resource|null */
	private $process = null;

	public function withAssert(?self $d): void
	{
		if (!$d?->impureAssert()) {
			return;
		}

		if ($d->impureAssert()) {
			echo 'x';
		}

		if ($d?->impureAssert()) {
			echo 'y';
		}
	}

	/**
	 * @phpstan-impure
	 * @phpstan-assert-if-true !null $this->process
	 */
	private function impureAssert(): bool
	{
		return $this->process !== null && (bool) microtime(true);
	}

}
