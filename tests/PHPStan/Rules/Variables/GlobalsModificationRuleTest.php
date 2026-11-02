<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<GlobalsModificationRule>
 */
class GlobalsModificationRuleTest extends RuleTestCase
{

	private int $phpVersionId = 80100;

	protected function getRule(): Rule
	{
		return new GlobalsModificationRule(new PhpVersion($this->phpVersionId));
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

	public function testBeforePhp81(): void
	{
		$this->phpVersionId = 80000;
		$this->analyse([__DIR__ . '/data/globals-modification.php'], []);
	}

}
