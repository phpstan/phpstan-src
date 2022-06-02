<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ReadOnlyByPhpDocPropertyRule>
 */
class ReadOnlyByPhpDocPropertyRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReadOnlyByPhpDocPropertyRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/read-only-property-phpdoc.php'], [
			[
				'@readonly property cannot have a default value.',
				21,
			],
		]);
	}

}
