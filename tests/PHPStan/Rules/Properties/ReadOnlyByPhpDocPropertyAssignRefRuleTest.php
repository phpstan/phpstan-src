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
				'@readonly property ReadOnlyPropertyAssignRefPhpDoc\Foo::$foo is assigned by reference.',
				22,
			],
			[
				'@readonly property ReadOnlyPropertyAssignRefPhpDoc\Foo::$bar is assigned by reference.',
				23,
			],
			[
				'@readonly property ReadOnlyPropertyAssignRefPhpDoc\Foo::$bar is assigned by reference.',
				34,
			],
			[
				'@readonly property ReadOnlyPropertyAssignRefPhpDoc\Immutable::$foo is assigned by reference.',
				51,
			],
			[
				'@readonly property ReadOnlyPropertyAssignRefPhpDoc\Immutable::$bar is assigned by reference.',
				52,
			],
		]);
	}

}
