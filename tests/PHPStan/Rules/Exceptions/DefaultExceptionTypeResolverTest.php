<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use DomainException;
use InvalidArgumentException;
use LogicException;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Testing\PHPStanTestCase;

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
	 * @dataProvider dataIsCheckedException
	 * @param string[] $uncheckedExceptionRegexes
	 * @param string[] $uncheckedExceptionClasses
	 * @param string[] $checkedExceptionRegexes
	 * @param string[] $checkedExceptionClasses
	 */
	public function testIsCheckedException(
		array $uncheckedExceptionRegexes,
		array $uncheckedExceptionClasses,
		array $checkedExceptionRegexes,
		array $checkedExceptionClasses,
		string $className,
		bool $expectedResult,
	): void
	{
		$resolver = new DefaultExceptionTypeResolver($this->createReflectionProvider(), $uncheckedExceptionRegexes, $uncheckedExceptionClasses, $checkedExceptionRegexes, $checkedExceptionClasses);
		$this->assertSame($expectedResult, $resolver->isCheckedException($className, self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create(__DIR__))));
	}

}
