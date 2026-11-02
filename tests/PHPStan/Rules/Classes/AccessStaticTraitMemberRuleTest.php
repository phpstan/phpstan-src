<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<AccessStaticTraitMemberRule>
 */
#[RequiresPhp('>= 8.1.0')]
class AccessStaticTraitMemberRuleTest extends RuleTestCase
{

	private int $phpVersionId = 80100;

	protected function getRule(): Rule
	{
		return new AccessStaticTraitMemberRule(new PhpVersion($this->phpVersionId));
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

	public function testBeforePhp81(): void
	{
		$this->phpVersionId = 80000;
		$this->analyse([__DIR__ . '/data/access-static-trait-member.php'], []);
	}

}
