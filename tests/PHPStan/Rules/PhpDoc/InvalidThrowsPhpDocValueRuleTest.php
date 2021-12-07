<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use InvalidThrowsPhpDocMergeInherited\Four;
use InvalidThrowsPhpDocMergeInherited\Three;
use InvalidThrowsPhpDocMergeInherited\Two;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\VerbosityLevel;

/**
 * @extends RuleTestCase<InvalidThrowsPhpDocValueRule>
 */
class InvalidThrowsPhpDocValueRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidThrowsPhpDocValueRule(self::getContainer()->getByType(FileTypeMapper::class));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-throws.php'], [
			[
				'PHPDoc tag @throws with type Undefined is not subtype of Throwable',
				54,
			],
			[
				'PHPDoc tag @throws with type bool is not subtype of Throwable',
				61,
			],
			[
				'PHPDoc tag @throws with type DateTimeImmutable is not subtype of Throwable',
				68,
			],
			[
				'PHPDoc tag @throws with type DateTimeImmutable|Throwable is not subtype of Throwable',
				75,
			],
			[
				'PHPDoc tag @throws with type DateTimeImmutable&IteratorAggregate is not subtype of Throwable',
				82,
			],
			[
				'PHPDoc tag @throws with type Throwable|void is not subtype of Throwable',
				96,
			],
			[
				'PHPDoc tag @throws with type stdClass|void is not subtype of Throwable',
				103,
			],
			[
				'PHPDoc tag @throws with type stdClass is not subtype of Throwable',
				118,
			],
		]);
	}

	public function testInheritedPhpDocs(): void
	{
		$this->analyse([__DIR__ . '/data/merge-inherited-throws.php'], [
			[
				'PHPDoc tag @throws with type InvalidThrowsPhpDocMergeInherited\A is not subtype of Throwable',
				13,
			],
			[
				'PHPDoc tag @throws with type InvalidThrowsPhpDocMergeInherited\B is not subtype of Throwable',
				19,
			],
			[
				'PHPDoc tag @throws with type InvalidThrowsPhpDocMergeInherited\C|InvalidThrowsPhpDocMergeInherited\D is not subtype of Throwable',
				28,
			],
		]);
	}

	public function dataMergeInheritedPhpDocs(): array
	{
		return [
			[
				Two::class,
				'method',
				'InvalidThrowsPhpDocMergeInherited\C|InvalidThrowsPhpDocMergeInherited\D',
			],
			[
				Three::class,
				'method',
				'InvalidThrowsPhpDocMergeInherited\C|InvalidThrowsPhpDocMergeInherited\D',
			],
			[
				Four::class,
				'method',
				'InvalidThrowsPhpDocMergeInherited\C|InvalidThrowsPhpDocMergeInherited\D',
			],
		];
	}

	/**
	 * @dataProvider dataMergeInheritedPhpDocs
	 */
	public function testMergeInheritedPhpDocs(
		string $className,
		string $method,
		string $expectedType
	): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$reflection = $reflectionProvider->getClass($className);
		$method = $reflection->getNativeMethod($method);
		$throwsType = $method->getThrowType();
		$this->assertNotNull($throwsType);
		$this->assertSame($expectedType, $throwsType->describe(VerbosityLevel::precise()));
	}

}
