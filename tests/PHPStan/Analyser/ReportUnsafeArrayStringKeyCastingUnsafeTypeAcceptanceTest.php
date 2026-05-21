<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallMethodsRule>
 */
class ReportUnsafeArrayStringKeyCastingUnsafeTypeAcceptanceTest extends RuleTestCase
{

	public function getRule(): Rule
	{
		return self::getContainer()->getByType(CallMethodsRule::class);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/report-unsafe-array-string-key-casting-accepts.php'], [
			[
				'Parameter #1 $a of method ReportUnsafeArrayStringKeyCastingAccepts\Foo::doBaz() expects array<non-decimal-int-string, stdClass>, non-empty-array<string, stdClass> given.',
				33,
			],
			[
				'Parameter #1 $a of method ReportUnsafeArrayStringKeyCastingAccepts\Foo::doBaz() expects array<non-decimal-int-string, stdClass>, non-empty-array<string, stdClass> given.',
				39,
			],
			[
				'Parameter #1 $a of method ReportUnsafeArrayStringKeyCastingAccepts\UnsealedArrayShape::doBaz() expects array{stdClass, ...<non-decimal-int-string, stdClass>}, array{stdClass, ...<string, stdClass>} given.',
				79,
				'Unsealed array key type non-decimal-int-string does not accept unsealed array key type string.',
			],
			[
				'Parameter #1 $a of method ReportUnsafeArrayStringKeyCastingAccepts\UnsealedArrayShape::doBaz() expects array{stdClass, ...<non-decimal-int-string, stdClass>}, array{stdClass, ...<string, stdClass>} given.',
				85,
				'Unsealed array key type non-decimal-int-string does not accept unsealed array key type string.',
			],
		]);
	}

}
