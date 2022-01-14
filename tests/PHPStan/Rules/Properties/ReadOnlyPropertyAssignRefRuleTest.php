<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ReadOnlyPropertyAssignRefRule>
 */
class ReadOnlyPropertyAssignRefRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReadOnlyPropertyAssignRefRule(new PropertyReflectionFinder());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/readonly-assign-ref.php'], [
			[
				'Readonly property ReadOnlyPropertyAssignRef\Foo::$foo is assigned by reference.',
				14,
			],
			[
				'Readonly property ReadOnlyPropertyAssignRef\Foo::$bar is assigned by reference.',
				15,
			],
			[
				'Readonly property ReadOnlyPropertyAssignRef\Foo::$bar is assigned by reference.',
				26,
			],
		]);
	}

}
