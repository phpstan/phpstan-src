<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;

/**
 * @extends \PHPStan\Testing\RuleTestCase<ExistingClassInTraitUseRule>
 */
class ExistingClassInTraitUseRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new ExistingClassInTraitUseRule(
			new ClassCaseSensitivityCheck($broker),
			$broker
		);
	}

	public function testClassWithWrongCase(): void
	{
		require_once __DIR__ . '/data/trait-use.php';
		$this->analyse([__DIR__ . '/data/trait-use.php'], [
			[
				'Trait TraitUseCase\FooTrait referenced with incorrect case: TraitUseCase\FOOTrait.',
				13,
			],
		]);
	}

	public function testTraitUseError(): void
	{
		$this->analyse([__DIR__ . '/data/trait-use-error.php'], [
			[
				'Class TraitUseError\Foo uses unknown trait TraitUseError\FooTrait.',
				8,
			],
			/*[
				'Trait TraitUseError\BarTrait uses class TraitUseError\Foo.',
				15,
			],
			[
				'Trait TraitUseError\BarTrait uses unknown trait TraitUseError\FooTrait. ',
				15,
			],*/
			[
				'Interface TraitUseError\Baz uses trait TraitUseError\BarTrait.',
				22,
			],
			[
				'Anonymous class uses unknown trait TraitUseError\FooTrait.',
				27,
			],
			[
				'Anonymous class uses interface TraitUseError\Baz.',
				28,
			],
		]);
	}

}
