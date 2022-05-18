<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ReadOnlyByPhpDocPropertyAssignRefRule>
 */
class ReadOnlyByPhpDocPropertyAssignRefRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReadOnlyByPhpDocPropertyAssignRefRule(new PropertyReflectionFinder());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/readonly-assign-ref-phpdoc.php'], [
			[
				'Readonly property ReadOnlyPropertyAssignRefPhpDoc\Foo::$foo is assigned by reference.',
				22,
			],
			[
				'Readonly property ReadOnlyPropertyAssignRefPhpDoc\Foo::$bar is assigned by reference.',
				23,
			],
			[
				'Readonly property ReadOnlyPropertyAssignRefPhpDoc\Foo::$bar is assigned by reference.',
				34,
			],
		]);
	}

}
