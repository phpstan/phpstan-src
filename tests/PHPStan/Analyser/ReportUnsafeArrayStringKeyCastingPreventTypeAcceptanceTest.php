<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<CallMethodsRule>
 */
class ReportUnsafeArrayStringKeyCastingPreventTypeAcceptanceTest extends RuleTestCase
{

	public function getRule(): Rule
	{
		return self::getContainer()->getByType(CallMethodsRule::class);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/report-unsafe-array-string-key-casting-accepts.php'], [
			[
				'Parameter #1 $a of method ReportUnsafeArrayStringKeyCastingAccepts\Foo::doFoo() expects array<non-decimal-int-string, stdClass>, non-empty-array<int|non-decimal-int-string, stdClass> given.',
				31,
			],
			[
				'Parameter #1 $a of method ReportUnsafeArrayStringKeyCastingAccepts\Foo::doBaz() expects array<non-decimal-int-string, stdClass>, non-empty-array<int|non-decimal-int-string, stdClass> given.',
				33,
			],
			[
				'Parameter #1 $a of method ReportUnsafeArrayStringKeyCastingAccepts\Foo::doFoo() expects array<non-decimal-int-string, stdClass>, non-empty-array<int|non-decimal-int-string, stdClass> given.',
				37,
			],
			[
				'Parameter #1 $a of method ReportUnsafeArrayStringKeyCastingAccepts\Foo::doBaz() expects array<non-decimal-int-string, stdClass>, non-empty-array<int|non-decimal-int-string, stdClass> given.',
				39,
			],
			[
				'Parameter #1 $a of method ReportUnsafeArrayStringKeyCastingAccepts\UnsealedArrayShape::doFoo() expects array{stdClass, ...<non-decimal-int-string, stdClass>}, array{stdClass, ...<int<min, -1>|int<1, max>|non-decimal-int-string, stdClass>} given.',
				77,
				'Unsealed array key type non-decimal-int-string does not accept unsealed array key type int<min, -1>|int<1, max>|non-decimal-int-string.',
			],
			[
				'Parameter #1 $a of method ReportUnsafeArrayStringKeyCastingAccepts\UnsealedArrayShape::doBaz() expects array{stdClass, ...<non-decimal-int-string, stdClass>}, array{stdClass, ...<int<min, -1>|int<1, max>|non-decimal-int-string, stdClass>} given.',
				79,
				'Unsealed array key type non-decimal-int-string does not accept unsealed array key type int<min, -1>|int<1, max>|non-decimal-int-string.',
			],
			[
				'Parameter #1 $a of method ReportUnsafeArrayStringKeyCastingAccepts\UnsealedArrayShape::doFoo() expects array{stdClass, ...<non-decimal-int-string, stdClass>}, array{stdClass, ...<int<min, -1>|int<1, max>|non-decimal-int-string, stdClass>} given.',
				83,
				'Unsealed array key type non-decimal-int-string does not accept unsealed array key type int<min, -1>|int<1, max>|non-decimal-int-string.',
			],
			[
				'Parameter #1 $a of method ReportUnsafeArrayStringKeyCastingAccepts\UnsealedArrayShape::doBaz() expects array{stdClass, ...<non-decimal-int-string, stdClass>}, array{stdClass, ...<int<min, -1>|int<1, max>|non-decimal-int-string, stdClass>} given.',
				85,
				'Unsealed array key type non-decimal-int-string does not accept unsealed array key type int<min, -1>|int<1, max>|non-decimal-int-string.',
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[
				__DIR__ . '/reportUnsafeArrayStringKeyCastingPrevent.neon',
			],
		);
	}

}
