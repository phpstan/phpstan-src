<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CastObjectToStringRule>
 */
class CastObjectToStringRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CastObjectToStringRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/cast-object-to-string.php'], [
			[
				'Casting object to string might result in an error.',
				11,
			],
		]);
	}

}
