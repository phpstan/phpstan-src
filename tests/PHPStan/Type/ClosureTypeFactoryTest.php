<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Closure;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use const PHP_VERSION_ID;

/**
 * @phpstan-consistent-constructor
 */
class ClosureTypeFactoryTest extends PHPStanTestCase
{

	public static function dataFromClosureObjectReturnType(): iterable
	{
		yield [static function (): void {
		}, 'void'];
		yield [static function () { // @phpcs:ignore
		}, 'mixed'];
		yield [static fn (): int => 5, 'int'];
		yield [
			static fn (): self => new self('name'),
			self::class,
		];

		if (PHP_VERSION_ID < 80000) {
			return;
		}

		yield [
			static fn (): static => new static('name'),
			'static(' . self::class . ')',
		];
	}

	/**
	 * @param Closure(): mixed $closure
	 */
	#[DataProvider('dataFromClosureObjectReturnType')]
	public function testFromClosureObjectReturnType(Closure $closure, string $returnType): void
	{
		$closureType = $this->getClosureType($closure);

		$this->assertSame($returnType, $closureType->getReturnType()->describe(VerbosityLevel::precise()));
	}

	public static function dataFromClosureObjectParameter(): array
	{
		return [
			[static function (string $foo): void {
			}, 0, 'string'],
			[static function (string $foo = 'boo'): void {
			}, 0, 'string'],
			[static function (string $foo = 'foo', int $bar = 5): void {
			}, 1, 'int'],
			[static function (array $foo): void {
			}, 0, 'array'],
			[static function (array $foo = [1]): void {
			}, 0, 'array'],
		];
	}

	/**
	 * @param Closure(mixed): mixed $closure
	 */
	#[DataProvider('dataFromClosureObjectParameter')]
	public function testFromClosureObjectParameter(Closure $closure, int $index, string $type): void
	{
		$closureType = $this->getClosureType($closure);

		$this->assertArrayHasKey($index, $closureType->getParameters());
		$this->assertSame($type, $closureType->getParameters()[$index]->getType()->describe(VerbosityLevel::precise()));
	}

	/**
	 * @param Closure(mixed): mixed $closure
	 */
	private function getClosureType(Closure $closure): ClosureType
	{
		return self::getContainer()->getByType(ClosureTypeFactory::class)->fromClosureObject($closure);
	}

}
