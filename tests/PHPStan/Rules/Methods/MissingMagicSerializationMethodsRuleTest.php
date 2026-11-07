<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<MissingMagicSerializationMethodsRule>
 */
class MissingMagicSerializationMethodsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingMagicSerializationMethodsRule(true);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
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
