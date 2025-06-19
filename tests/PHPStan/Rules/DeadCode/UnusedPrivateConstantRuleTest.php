<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtension;
use PHPStan\Rules\Constants\DirectAlwaysUsedClassConstantsExtensionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use UnusedPrivateConstant\TestExtension;

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

					public function isAlwaysUsed(ClassConstantReflection $constant): bool
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

	#[RequiresPhp('>= 8.1')]
	public function testEnums(): void
	{
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

	#[RequiresPhp('>= 8.0')]
	public function testBug8204(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8204.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug9005(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9005.php'], []);
	}

	public function testBug9765(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9765.php'], []);
	}

	#[RequiresPhp('>= 8.3')]
	public function testDynamicConstantFetch(): void
	{
		$this->analyse([__DIR__ . '/data/unused-private-constant-dynamic-fetch.php'], [
			[
				'Constant UnusedPrivateConstantDynamicFetch\Baz::FOO is unused.',
				32,
				'See: https://phpstan.org/developing-extensions/always-used-class-constants',
			],
		]);
	}

}
