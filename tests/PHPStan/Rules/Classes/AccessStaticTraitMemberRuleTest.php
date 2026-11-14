<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<AccessStaticTraitMemberRule>
 */
#[RequiresPhp('>= 8.1.0')]
class AccessStaticTraitMemberRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new AccessStaticTraitMemberRule();
	}

	public function testRule(): void
	{
		$method = 'Calling static trait method AccessStaticTraitMember\FooTrait::create() is deprecated in PHP 8.1, it should only be called on a class using the trait.';
		$property = 'Accessing static trait property AccessStaticTraitMember\FooTrait::$counter is deprecated in PHP 8.1, it should only be accessed on a class using the trait.';
		$this->analyse([__DIR__ . '/data/access-static-trait-member.php'], [
			[$method, 16],
			[$property, 17],
			[$method, 49],
			[$property, 50],
			[$property, 51],
			[$method, 52],
			[$method, 54],
			[$property, 55],
		]);
	}

	public function testPhpVersionNarrowedScope(): void
	{
		$this->analyse([__DIR__ . '/data/access-static-trait-member-php-version.php'], [
			['Calling static trait method AccessStaticTraitMemberPhpVersion\FooTrait::create() is deprecated in PHP 8.1, it should only be called on a class using the trait.', 21],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../data/php-version-range-80000-80100.neon',
		];
	}

}
