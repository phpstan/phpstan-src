<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends RuleTestCase<VarTagChangedExpressionTypeRule>
 */
class VarTagChangedExpressionTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$container = self::getContainer();
		return new VarTagChangedExpressionTypeRule(new VarTagTypeRuleHelper(
			$container->getByType(TypeNodeResolver::class),
			$container->getByType(FileTypeMapper::class),
			self::createReflectionProvider(),
			true,
			true,
		));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/var-tag-changed-expr-type.php'], [
			[
				'PHPDoc tag @var with type string is not subtype of native type int.',
				17,
			],
			[
				'PHPDoc tag @var with type string is not subtype of type int.',
				37,
			],
			[
				'PHPDoc tag @var with type string is not subtype of native type int.',
				54,
			],
			[
				'PHPDoc tag @var with type string is not subtype of native type int.',
				73,
			],
		]);
	}

	public function testAssignOfDifferentVariable(): void
	{
		$this->analyse([__DIR__ . '/data/wrong-var-native-type.php'], [
			[
				'PHPDoc tag @var with type string is not subtype of type int.',
				95,
			],
		]);
	}

	public function testBug10130(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10130.php'], [
			[
				'PHPDoc tag @var with type array<mixed> is not subtype of type array<int>.',
				14,
			],
			[
				'PHPDoc tag @var with type array<mixed> is not subtype of type list<int>.',
				17,
			],
			[
				'PHPDoc tag @var with type array<mixed> is not subtype of type array{id: int}.',
				20,
			],
			[
				'PHPDoc tag @var with type array<mixed> is not subtype of type list<array{id: int}>.',
				23,
			],
		]);
	}

	public function testBug12708(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12708.php'], [
			[
				"PHPDoc tag @var with type list<string> is not subtype of native type array{1: 'b', 2: 'c'}.",
				12,
			],
			[
				"PHPDoc tag @var with type list<string> is not subtype of native type array{0: 'a', 2: 'c'}.",
				18,
			],
			[
				"PHPDoc tag @var with type list<string> is not subtype of native type array{-1: 'z', 0: 'a', 1: 'b', 2: 'c'}.",
				24,
			],
			[
				"PHPDoc tag @var with type list<string> is not subtype of native type array{0: 'a', -1: 'z', 1: 'b', 2: 'c'}.",
				30,
			],
		]);
	}

}
