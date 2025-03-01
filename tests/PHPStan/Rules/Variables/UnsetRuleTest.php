<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<UnsetRule>
 */
class UnsetRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnsetRule(
			self::getContainer()->getByType(PropertyReflectionFinder::class),
			self::getContainer()->getByType(PhpVersion::class),
		);
	}

	public function testUnsetRule(): void
	{
		require_once __DIR__ . '/data/unset.php';
		$this->analyse([__DIR__ . '/data/unset.php'], [
			[
				'Call to function unset() contains undefined variable $notSetVariable.',
				6,
			],
			[
				'Cannot unset offset \'a\' on 3.',
				10,
			],
			[
				'Cannot unset offset \'b\' on 1.',
				14,
			],
			[
				'Cannot unset offset \'c\' on 1.',
				18,
			],
			[
				'Cannot unset offset \'string\' on iterable<int, int>.',
				31,
			],
			[
				'Call to function unset() contains undefined variable $notSetVariable.',
				36,
			],
		]);
	}

	public function testBug2752(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2752.php'], []);
	}

	public function testBug4289(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->analyse([__DIR__ . '/data/bug-4289.php'], []);
		} else {
			$this->analyse([__DIR__ . '/data/bug-4289.php'], [
				[
					'Cannot unset Bug4289\BaseClass::$fields property which might get hooked in subclass.',
					25,
				],
			]);
		}
	}

	public function testBug5223(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-5223.php'], [
			[
				'Cannot unset offset \'page\' on array{categoryKeys: array<string>, tagNames: array<string>}.',
				20,
			],
			[
				'Cannot unset offset \'limit\' on array{categoryKeys: array<string>, tagNames: array<string>}.',
				23,
			],
		]);
	}

	public function testBug3391(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3391.php'], []);
	}

	public function testBug7417(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7417.php'], []);
	}

	public function testBug8113(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8113.php'], []);
	}

	public function testBug4565(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-4565.php'], []);
	}

	public function testBug12421(): void
	{
		$errors = [];
		if (PHP_VERSION_ID >= 80400) {
			$errors[] = [
				'Cannot unset Bug12421\RegularProperty::$y property which might get hooked in subclass.',
				7,
			];
		}

		$errors = array_merge($errors, [
			[
				'Cannot unset readonly Bug12421\NativeReadonlyClass::$y property.',
				11,
			],
			[
				'Cannot unset readonly Bug12421\NativeReadonlyProperty::$y property.',
				15,
			],
			[
				'Cannot unset @readonly Bug12421\PhpdocReadonlyClass::$y property.',
				19,
			],
			[
				'Cannot unset @readonly Bug12421\PhpdocReadonlyProperty::$y property.',
				23,
			],
			[
				'Cannot unset @readonly Bug12421\PhpdocImmutableClass::$y property.',
				27,
			],
			[
				'Cannot unset readonly Bug12421\NativeReadonlyProperty::$y property.',
				34,
			],
		]);

		$this->analyse([__DIR__ . '/data/bug-12421.php'], $errors);
	}

	public function testUnsetHookedProperty(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4 or later.');
		}

		$this->analyse([__DIR__ . '/data/unset-hooked-property.php'], [
			[
				'Cannot unset hooked UnsetHookedProperty\User::$name property.',
				6,
			],
			[
				'Cannot unset hooked UnsetHookedProperty\User::$fullName property.',
				7,
			],
			[
				'Cannot unset hooked UnsetHookedProperty\Foo::$ii property.',
				9,
			],
			[
				'Cannot unset hooked UnsetHookedProperty\Foo::$iii property.',
				10,
			],
			[
				'Cannot unset UnsetHookedProperty\NonFinalClass::$publicProperty property which might get hooked in subclass.',
				13,
			],
		]);
	}

}
