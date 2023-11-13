<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ConflictingTraitConstantsRule>
 */
class ConflictingTraitConstantsRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new ConflictingTraitConstantsRule(self::getContainer()->getByType(InitializerExprTypeResolver::class));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/conflicting-trait-constants.php'], [
			[
				'Protected constant ConflictingTraitConstants\Bar::PUBLIC_CONSTANT overriding public constant ConflictingTraitConstants\Foo::PUBLIC_CONSTANT should also be public.',
				23,
			],
			[
				'Private constant ConflictingTraitConstants\Bar2::PUBLIC_CONSTANT overriding public constant ConflictingTraitConstants\Foo::PUBLIC_CONSTANT should also be public.',
				32,
			],
			[
				'Public constant ConflictingTraitConstants\Bar3::PROTECTED_CONSTANT overriding protected constant ConflictingTraitConstants\Foo::PROTECTED_CONSTANT should also be protected.',
				41,
			],
			[
				'Private constant ConflictingTraitConstants\Bar4::PROTECTED_CONSTANT overriding protected constant ConflictingTraitConstants\Foo::PROTECTED_CONSTANT should also be protected.',
				50,
			],
			[
				'Protected constant ConflictingTraitConstants\Bar5::PRIVATE_CONSTANT overriding private constant ConflictingTraitConstants\Foo::PRIVATE_CONSTANT should also be private.',
				59,
			],
			[
				'Public constant ConflictingTraitConstants\Bar5::PRIVATE_CONSTANT overriding private constant ConflictingTraitConstants\Foo::PRIVATE_CONSTANT should also be private.',
				68,
			],
			[
				'Non-final constant ConflictingTraitConstants\Bar6::PUBLIC_FINAL_CONSTANT overriding final constant ConflictingTraitConstants\Foo::PUBLIC_FINAL_CONSTANT should also be final.',
				77,
			],
			[
				'Final constant ConflictingTraitConstants\Bar7::PUBLIC_CONSTANT overriding non-final constant ConflictingTraitConstants\Foo::PUBLIC_CONSTANT should also be non-final.',
				86,
			],
			[
				'Constant ConflictingTraitConstants\Bar8::PUBLIC_CONSTANT with value 2 overriding constant ConflictingTraitConstants\Foo::PUBLIC_CONSTANT with different value 1 should have the same value.',
				96,
			],
		]);
	}

	public function testNativeTypes(): void
	{
		if (PHP_VERSION_ID < 80300) {
			$this->markTestSkipped('Test requires PHP 8.3.');
		}

		$this->analyse([__DIR__ . '/data/conflicting-trait-constants-types.php'], [
			[
				'Constant ConflictingTraitConstantsTypes\Baz::FOO_CONST (int) overriding constant ConflictingTraitConstantsTypes\Foo::FOO_CONST (int|string) should have the same native type int|string.',
				28,
			],
			[
				'Constant ConflictingTraitConstantsTypes\Baz::BAR_CONST (int) overriding constant ConflictingTraitConstantsTypes\Foo::BAR_CONST should not have a native type.',
				30,
			],
			[
				'Constant ConflictingTraitConstantsTypes\Lorem::FOO_CONST overriding constant ConflictingTraitConstantsTypes\Foo::FOO_CONST (int|string) should also have native type int|string.',
				39,
			],
		]);
	}

}
