<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Testing\TestCase;

class ExceptionTypeResolverTest extends TestCase
{

	public function dataIsCheckedException(): array
	{
		return [
			[
				[],
				[],
				\InvalidArgumentException::class,
				true,
			],
			[
				[
					'#^InvalidArgumentException$#',
				],
				[],
				\InvalidArgumentException::class,
				false,
			],
			[
				[],
				[
					\InvalidArgumentException::class,
				],
				\InvalidArgumentException::class,
				false,
			],
			[
				[],
				[
					\LogicException::class,
				],
				\LogicException::class,
				false,
			],
			[
				[],
				[
					\LogicException::class,
				],
				\DomainException::class,
				false,
			],
			[
				[],
				[
					\DomainException::class,
				],
				\LogicException::class,
				true,
			],
		];
	}

	/**
	 * @dataProvider dataIsCheckedException
	 * @param string[] $uncheckedExceptionRegexes
	 * @param string[] $uncheckedExceptionClasses
	 * @param string $className
	 * @param bool $expectedResult
	 */
	public function testIsCheckedException(
		array $uncheckedExceptionRegexes,
		array $uncheckedExceptionClasses,
		string $className,
		bool $expectedResult
	): void
	{
		$resolver = new ExceptionTypeResolver($this->createBroker(), $uncheckedExceptionRegexes, $uncheckedExceptionClasses);
		$this->assertSame($expectedResult, $resolver->isCheckedException($className));
	}

}
