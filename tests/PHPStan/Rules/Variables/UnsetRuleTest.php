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
		$errors = [];

		if (PHP_VERSION_ID >= 80400) {
			$errors = [
				[
					'Cannot unset property Bug4289\BaseClass::$fields because it might have hooks in a subclass.',
					25,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/bug-4289.php'], $errors);
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
				'Cannot unset property Bug12421\RegularProperty::$y because it might have hooks in a subclass.',
				6,
			];
			$errors[] = [
				'Cannot unset property Bug12421\RegularProperty::$y because it might have hooks in a subclass.',
				9,
			];
		}

		$errors = array_merge($errors, [
			[
				'Cannot unset readonly Bug12421\NativeReadonlyClass::$y property.',
				13,
			],
			[
				'Cannot unset readonly Bug12421\NativeReadonlyProperty::$y property.',
				17,
			],
			[
				'Cannot unset @readonly Bug12421\PhpdocReadonlyClass::$y property.',
				21,
			],
			[
				'Cannot unset @readonly Bug12421\PhpdocReadonlyProperty::$y property.',
				25,
			],
			[
				'Cannot unset @readonly Bug12421\PhpdocImmutableClass::$y property.',
				29,
			],
			[
				'Cannot unset readonly Bug12421\NativeReadonlyProperty::$y property.',
				36,
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
				'Cannot unset property UnsetHookedProperty\NonFinalClass::$publicProperty because it might have hooks in a subclass.',
				13,
			],
		]);
	}

}
