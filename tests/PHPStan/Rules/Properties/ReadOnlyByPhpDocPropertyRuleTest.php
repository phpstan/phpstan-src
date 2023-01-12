<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

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
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

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

	public function testRuleIgnoresNativeReadonly(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/read-only-property-phpdoc-and-native.php'], []);
	}

	public function testRuleAllowedPrivateMutation(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/read-only-property-phpdoc-allowed-private-mutation.php'], [
			[
				'@readonly property cannot have a default value.',
				9,
			],
		]);
	}

}
