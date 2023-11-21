<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<MethodVisibitiliyInInterfaceRule> */
class MethodVisibitiliyInInterfaceRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MethodVisibitiliyInInterfaceRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/visibility-in-interace.php'], [
			[
				'Method VisibilityInInterface\FooInterface::sayPrivate() cannot use non-public visibility in interface.',
				7,
			],
			[
				'Method VisibilityInInterface\FooInterface::sayProtected() cannot use non-public visibility in interface.',
				8,
			],
		]);
	}

}
