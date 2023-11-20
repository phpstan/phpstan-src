<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoPhpCodeRule>
 */
class NoPhpCodeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NoPhpCodeRule();
	}

	public function testEmptyFile(): void
	{
		$this->analyse([__DIR__ . '/data/empty.php'], []);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/no-php-code.php'], [
			[
				'The example does not contain any PHP code. Did you forget the opening <?php tag?',
				1,
			],
		]);
	}

}
