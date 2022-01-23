<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ExistingClassInTraitUseRule>
 */
class ExistingClassInTraitUseRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new ExistingClassInTraitUseRule(
			new ClassCaseSensitivityCheck($broker, true),
			$broker,
		);
	}

	public function testClassWithWrongCase(): void
	{
		$this->analyse([__DIR__ . '/data/trait-use.php'], [
			[
				'Trait TraitUseCase\FooTrait referenced with incorrect case: TraitUseCase\FOOTrait.',
				13,
			],
		]);
	}

	public function testTraitUseError(): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('This test needs static reflection');
		}

		$this->analyse([__DIR__ . '/data/trait-use-error.php'], [
			[
				'Class TraitUseError\Foo uses unknown trait TraitUseError\FooTrait.',
				8,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
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
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Anonymous class uses interface TraitUseError\Baz.',
				28,
			],
		]);
	}

	public function testEnums(): void
	{
		if (!self::$useStaticReflectionProvider || PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('This test needs static reflection and PHP 8.1');
		}

		$this->analyse([__DIR__ . '/data/trait-use-enum.php'], [
			[
				'Class TraitUseEnum\Foo uses enum TraitUseEnum\FooEnum.',
				13,
			],
			[
				'Anonymous class uses enum TraitUseEnum\FooEnum.',
				20,
			],
		]);
	}

}
