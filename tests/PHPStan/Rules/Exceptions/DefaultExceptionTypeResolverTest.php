<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use ArgumentCountError;
use ArithmeticError;
use AssertionError;
use DivisionByZeroError;
use DomainException;
use Error;
use ErrorException;
use Exception;
use FiberError;
use InvalidArgumentException;
use JsonException;
use LogicException;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Throwable;
use TypeError;
use UnhandledMatchError;
use ValueError;

class DefaultExceptionTypeResolverTest extends PHPStanTestCase
{

	public static function dataIsCheckedException(): array
	{
		return [
			[
				[],
				[],
				[],
				[],
				InvalidArgumentException::class,
				true,
			],
			[
				[
					'#^InvalidArgumentException$#',
				],
				[],
				[],
				[],
				InvalidArgumentException::class,
				false,
			],
			[
				[],
				[
					InvalidArgumentException::class,
				],
				[],
				[],
				InvalidArgumentException::class,
				false,
			],
			[
				[],
				[
					LogicException::class,
				],
				[],
				[],
				LogicException::class,
				false,
			],
			[
				[],
				[
					LogicException::class,
				],
				[],
				[],
				DomainException::class,
				false,
			],
			[
				[],
				[
					DomainException::class,
				],
				[],
				[],
				LogicException::class,
				true,
			],
			[
				[],
				[],
				[
					'#^Exception$#',
				],
				[],
				InvalidArgumentException::class,
				false,
			],
			[
				[],
				[],
				[
					'#^InvalidArgumentException#',
				],
				[],
				InvalidArgumentException::class,
				true,
			],
			[
				[],
				[],
				[],
				[
					DomainException::class,
				],
				InvalidArgumentException::class,
				false,
			],
			[
				[],
				[],
				[],
				[
					InvalidArgumentException::class,
				],
				InvalidArgumentException::class,
				true,
			],
			[
				[],
				[],
				[],
				[
					LogicException::class,
				],
				InvalidArgumentException::class,
				true,
			],
		];
	}

	/**
	 * @param string[] $uncheckedExceptionRegexes
	 * @param string[] $uncheckedExceptionClasses
	 * @param string[] $checkedExceptionRegexes
	 * @param string[] $checkedExceptionClasses
	 */
	#[DataProvider('dataIsCheckedException')]
	public function testIsCheckedException(
		array $uncheckedExceptionRegexes,
		array $uncheckedExceptionClasses,
		array $checkedExceptionRegexes,
		array $checkedExceptionClasses,
		string $className,
		bool $expectedResult,
	): void
	{
		$resolver = new DefaultExceptionTypeResolver(self::createReflectionProvider(), $uncheckedExceptionRegexes, $uncheckedExceptionClasses, $checkedExceptionRegexes, $checkedExceptionClasses);
		$this->assertSame($expectedResult, $resolver->isCheckedException($className, self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create(__DIR__))));
	}

	public static function dataIsCheckedExceptionWithDefaultConfiguration(): iterable
	{
		yield [Error::class, false];
		yield [ArgumentCountError::class, false];
		yield [ArithmeticError::class, false];
		yield [AssertionError::class, false];
		yield [DivisionByZeroError::class, false];
		yield [FiberError::class, false];
		yield [TypeError::class, false];
		yield [UnhandledMatchError::class, false];
		yield [ValueError::class, false];

		yield [Throwable::class, true];
		yield [Exception::class, true];
		yield [ErrorException::class, true];
		yield [JsonException::class, true];
		yield [LogicException::class, true];
		yield [RuntimeException::class, true];
		yield [InvalidArgumentException::class, true];
	}

	#[DataProvider('dataIsCheckedExceptionWithDefaultConfiguration')]
	public function testIsCheckedExceptionWithDefaultConfiguration(string $className, bool $expectedResult): void
	{
		$resolver = self::getContainer()->getByType(DefaultExceptionTypeResolver::class);
		$this->assertSame($expectedResult, $resolver->isCheckedException($className, self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create(__DIR__))));
	}

}
