<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<AbstractPrivateMethodRule> */
class AbstractPrivateMethodRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new AbstractPrivateMethodRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/abstract-private-method.php'], [
			[
				'Private method PrivateAbstractMethod\HelloWorld::sayPrivate() cannot be abstract.',
				12,
			],
			[
				'Private method PrivateAbstractMethod\fooInterface::sayPrivate() cannot be abstract.',
				24,
			],
		]);
	}

}
