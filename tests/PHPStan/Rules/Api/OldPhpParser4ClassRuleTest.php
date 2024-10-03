<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<OldPhpParser4ClassRule>
 */
class OldPhpParser4ClassRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new OldPhpParser4ClassRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/old-php-parser-4-class.php'], [
			[
				'Class PhpParser\Node\Expr\ArrayItem not found. It has been renamed to PhpParser\Node\ArrayItem in PHP-Parser v5.',
				24,
			],
		]);
	}

}
