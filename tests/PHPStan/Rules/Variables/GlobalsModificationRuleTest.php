<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<GlobalsModificationRule>
 */
class GlobalsModificationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new GlobalsModificationRule();
	}

	public function testRule(): void
	{
		$message = '$GLOBALS can only be modified using the $GLOBALS[$name] = $value syntax.';
		$this->analyse([__DIR__ . '/data/globals-modification.php'], [
			[$message, 10],
			[$message, 11],
			[$message, 12],
			[$message, 13],
			[$message, 14],
			[$message, 15],
			[$message, 17],
			[$message, 19],
			[$message, 20],
			[$message, 21],
			[$message, 22],
			['Cannot acquire reference to $GLOBALS.', 23],
		]);
	}

	public function testPhpVersionNarrowedScope(): void
	{
		$this->analyse([__DIR__ . '/data/globals-modification-php-version.php'], [
			['$GLOBALS can only be modified using the $GLOBALS[$name] = $value syntax.', 15],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../data/php-version-range-80000-80100.neon',
		];
	}

}
