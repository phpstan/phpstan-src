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
			])
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
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-5651.php'], []);
	}

}
