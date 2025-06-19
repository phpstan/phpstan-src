<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ReadOnlyByPhpDocPropertyRule>
 */
class ReadOnlyByPhpDocPropertyRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReadOnlyByPhpDocPropertyRule();
	}

	#[RequiresPhp('>= 8.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/read-only-property-phpdoc.php'], [
			[
				'@readonly property cannot have a default value.',
				21,
			],
			[
				'@readonly property cannot have a default value.',
				39,
			],
			[
				'@readonly property cannot have a default value.',
				46,
			],
			[
				'@readonly property cannot have a default value.',
				53,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRuleIgnoresNativeReadonly(): void
	{
		$this->analyse([__DIR__ . '/data/read-only-property-phpdoc-and-native.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testRuleAllowedPrivateMutation(): void
	{
		$this->analyse([__DIR__ . '/data/read-only-property-phpdoc-allowed-private-mutation.php'], [
			[
				'@readonly property cannot have a default value.',
				9,
			],
		]);
	}

}
