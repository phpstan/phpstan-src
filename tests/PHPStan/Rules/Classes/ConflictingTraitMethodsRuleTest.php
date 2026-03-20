<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ConflictingTraitMethodsRule>
 */
class ConflictingTraitMethodsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ConflictingTraitMethodsRule(
			self::getContainer()->getByType(ReflectionProvider::class),
		);
	}

	public function testBug14332(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14332.php'], [
			[
				'Trait method Bug14332\MyTrait2::doSomething() has not been applied as Bug14332\FooWithMultipleConflictingTraits::doSomething(), because of collision with Bug14332\MyTrait1::doSomething().',
				20,
			],
			[
				'Trait method Bug14332\MyTrait4::doSomething() has not been applied as Bug14332\FooWithMultipleConflicts::doSomething(), because of collision with Bug14332\MyTrait1::doSomething().',
				75,
			],
			[
				'Trait method Bug14332\MyTrait5::anotherMethod() has not been applied as Bug14332\FooWithMultipleConflicts::anotherMethod(), because of collision with Bug14332\MyTrait4::anotherMethod().',
				75,
			],
			[
				'Trait method Bug14332\MyTrait5::anotherMethod() has not been applied as Bug14332\FooWithPartialResolution::anotherMethod(), because of collision with Bug14332\MyTrait4::anotherMethod().',
				81,
			],
		]);
	}

	public function testBug14332Abstract(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14332-abstract.php'], []);
	}

}
