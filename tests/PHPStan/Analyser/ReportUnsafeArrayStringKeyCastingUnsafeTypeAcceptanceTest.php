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
		]);
	}

}
