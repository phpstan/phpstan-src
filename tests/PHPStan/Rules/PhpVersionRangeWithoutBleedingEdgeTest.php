<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Rules\Arrays\InvalidKeyInArrayDimFetchRule;
use PHPStan\Rules\Arrays\InvalidKeyInArrayItemRule;
use PHPStan\Rules\Functions\DefineParametersRule;
use PHPStan\Rules\Functions\ExistingClassesInTypehintsRule;
use PHPStan\Rules\Operators\BacktickRule;
use PHPStan\Rules\Operators\InvalidIncDecOperationRule;
use PHPStan\Rules\Variables\UnsetRule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;

/**
 * Without bleeding edge, a configured PHP version range is checked against
 * its minimum - the problems that only its newer versions have are not
 * reported. The *ConfigPhpTest tests run the same files with bleeding edge.
 *
 * @extends RuleTestCase<CompositeRule>
 */
class PhpVersionRangeWithoutBleedingEdgeTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$container = self::getContainer();

		// @phpstan-ignore argument.type
		return new CompositeRule([
			$container->getByType(BacktickRule::class),
			$container->getByType(DefineParametersRule::class),
			$container->getByType(ExistingClassesInTypehintsRule::class),
			$container->getByType(InvalidIncDecOperationRule::class),
			$container->getByType(InvalidKeyInArrayDimFetchRule::class),
			$container->getByType(InvalidKeyInArrayItemRule::class),
			$container->getByType(UnsetRule::class),
		]);
	}

	public function testRule(): void
	{
		$this->analyse([
			__DIR__ . '/Arrays/data/invalid-key-php-version-range.php',
			__DIR__ . '/Functions/data/call-to-define.php',
			__DIR__ . '/Functions/data/function-definition-php-version-range.php',
			__DIR__ . '/Operators/data/backtick.php',
			__DIR__ . '/Operators/data/dec-non-numeric-string.php',
			__DIR__ . '/Operators/data/inc-non-numeric-string.php',
			__DIR__ . '/Variables/data/unset-php-version-range.php',
		], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/php-version-range-without-bleeding-edge.neon',
		];
	}

}
