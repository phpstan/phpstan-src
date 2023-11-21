<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<AbstractProtectedMethodRule> */
class AbstractProtectedMethodRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new AbstractProtectedMethodRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/abstract-methods.php'], [
			[
				'Protected method AbstractMethods\fooInterface::sayProtected() cannot be abstract.',
				25,
			],
		]);
	}

}
