<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Reflection\ConstantReflection;
use PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtension;
use PHPStan\Rules\Constants\DirectAlwaysUsedClassConstantsExtensionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use UnusedPrivateConstant\TestExtension;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<UnusedPrivateConstantRule>
 */
class UnusedPrivateConstantRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedPrivateConstantRule(
			new DirectAlwaysUsedClassConstantsExtensionProvider([
				new class() implements AlwaysUsedClassConstantsExtension {

					public function isAlwaysUsed(ConstantReflection $constant): bool
					{
						return $constant->getDeclaringClass()->getName() === TestExtension::class
							&& $constant->getName() === 'USED';
					}

				},
			]),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unused-private-constant.php'], [
			[
				'Constant UnusedPrivateConstant\Foo::BAR_CONST is unused.',
				10,
				'See: https://phpstan.org/developing-extensions/always-used-class-constants',
			],
			[
				'Constant UnusedPrivateConstant\TestExtension::UNUSED is unused.',
				23,
				'See: https://phpstan.org/developing-extensions/always-used-class-constants',
			],
		]);
	}

	public function testBug5651(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5651.php'], []);
	}

	public function testEnums(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('This test needs PHP 8.1');
		}

		$this->analyse([__DIR__ . '/data/unused-private-constant-enum.php'], [
			[
				'Constant UnusedPrivateConstantEnum\Foo::TEST_2 is unused.',
				9,
				'See: https://phpstan.org/developing-extensions/always-used-class-constants',
			],
		]);
	}

	public function testBug6758(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6758.php'], []);
	}

	public function testBug8204(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-8204.php'], []);
	}

	public function testBug9005(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-9005.php'], []);
	}

	public function testProtectedFinal(): void
	{
		$this->analyse([__DIR__ . '/data/protected-final-const.php'], [
			[
				'Constant ProtectedFinalConst\FinalClass::FIELD is unused.',
				7,
				'See: https://phpstan.org/developing-extensions/always-used-class-constants',
			],
		]);
	}

}
