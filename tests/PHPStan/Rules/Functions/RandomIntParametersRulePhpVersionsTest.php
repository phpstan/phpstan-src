<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RandomIntParametersRule>
 */
class RandomIntParametersRulePhpVersionsTest extends RuleTestCase
{

	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/../data/php-version-range.neon'];
	}

	protected function getRule(): Rule
	{
		return new RandomIntParametersRule(self::createReflectionProvider(), new PhpVersion(80000), true);
	}

	public function testPhpVersionNarrowedScope(): void
	{
		$this->analyse([__DIR__ . '/data/random-int-php-versions.php'], [
			[
				'Parameter #1 $min (1) of function random_int expects lower number than parameter #2 $max (0).',
				8,
			],
			[
				'Parameter #1 $min (1) of function random_int expects lower number than parameter #2 $max (0).',
				11,
			],
		]);
	}

}
