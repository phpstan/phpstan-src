<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<Bug13983Rule>
 */
class Bug13983Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new Bug13983Rule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/Bug13983Rule.php'], [
			[
				'Dumped: 1|null',
				39,
			],
		]);
	}

}
