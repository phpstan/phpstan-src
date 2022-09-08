<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<MissingMagicSerializationMethodsRule>
 */
class MissingMagicSerializationMethodsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingMagicSerializationMethodsRule(new PhpVersion(PHP_VERSION_ID));
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/missing-serialization.php'], [
			[
				'Non-abstract class MissingMagicSerializationMethods\myObj implements the Serializable interface, but does not implement __serialize().',
				14,
				'See https://wiki.php.net/rfc/phase_out_serializable',
			],
			[
				'Non-abstract class MissingMagicSerializationMethods\myObj implements the Serializable interface, but does not implement __unserialize().',
				14,
				'See https://wiki.php.net/rfc/phase_out_serializable',
			],
		]);
	}

}
