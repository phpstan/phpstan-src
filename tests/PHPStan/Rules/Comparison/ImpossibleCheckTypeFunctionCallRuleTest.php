<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use stdClass;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ImpossibleCheckTypeFunctionCallRule>
 */
class ImpossibleCheckTypeFunctionCallRuleTest extends RuleTestCase
{

	private bool $checkAlwaysTrueCheckTypeFunctionCall;

	private bool $treatPhpDocTypesAsCertain;

	protected function getRule(): Rule
	{
		return new ImpossibleCheckTypeFunctionCallRule(
			new ImpossibleCheckTypeHelper(
				$this->createReflectionProvider(),
				$this->getTypeSpecifier(),
				[stdClass::class],
				$this->treatPhpDocTypesAsCertain,
			),
			$this->checkAlwaysTrueCheckTypeFunctionCall,
			$this->treatPhpDocTypesAsCertain,
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testImpossibleCheckTypeFunctionCall(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse(
			[__DIR__ . '/data/check-type-function-call.php'],
			[
				[
					'Call to function is_int() with int will always evaluate to true.',
					25,
				],
				[
					'Call to function is_int() with string will always evaluate to false.',
					31,
				],
				[
					'Call to function is_callable() with array<int> will always evaluate to false.',
					44,
					'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
				],
				[
					'Call to function assert() with false will always evaluate to false.',
					48,
				],
				[
					'Call to function is_callable() with \'date\' will always evaluate to true.',
					84,
				],
				[
					'Call to function is_callable() with \'nonexistentFunction\' will always evaluate to false.',
					87,
				],
				[
					'Call to function is_numeric() with \'123\' will always evaluate to true.',
					102,
				],
				[
					'Call to function is_numeric() with \'blabla\' will always evaluate to false.',
					105,
				],
				[
					'Call to function is_numeric() with 123|float will always evaluate to true.',
					118,
				],
				[
					'Call to function is_string() with string will always evaluate to true.',
					140,
				],
				[
					'Call to function method_exists() with CheckTypeFunctionCall\Foo and \'doFoo\' will always evaluate to true.',
					179,
				],
				[
					'Call to function method_exists() with $this(CheckTypeFunctionCall\FinalClassWithMethodExists) and \'doFoo\' will always evaluate to true.',
					191,
				],
				[
					'Call to function method_exists() with $this(CheckTypeFunctionCall\FinalClassWithMethodExists) and \'doBar\' will always evaluate to false.',
					194,
				],
				[
					'Call to function property_exists() with $this(CheckTypeFunctionCall\FinalClassWithPropertyExists) and \'fooProperty\' will always evaluate to true.',
					209,
				],
				[
					'Call to function in_array() with arguments int, array{\'foo\', \'bar\'} and true will always evaluate to false.',
					235,
				],
				[
					'Call to function in_array() with arguments \'bar\'|\'foo\', array{\'baz\', \'lorem\'} and true will always evaluate to false.',
					244,
				],
				[
					'Call to function in_array() with arguments \'bar\'|\'foo\', array{\'foo\', \'bar\'} and true will always evaluate to true.',
					248,
				],
				[
					'Call to function in_array() with arguments \'foo\', array{\'foo\'} and true will always evaluate to true.',
					252,
				],
				[
					'Call to function in_array() with arguments \'foo\', array{\'foo\', \'bar\'} and true will always evaluate to true.',
					256,
				],
				[
					'Call to function in_array() with arguments \'bar\', array{}|array{\'foo\'} and true will always evaluate to false.',
					320,
				],
				[
					'Call to function in_array() with arguments \'baz\', array{0: \'bar\', 1?: \'foo\'} and true will always evaluate to false.',
					336,
				],
				[
					'Call to function in_array() with arguments \'foo\', array{} and true will always evaluate to false.',
					343,
				],
				[
					'Call to function array_key_exists() with \'a\' and array{a: 1, b?: 2} will always evaluate to true.',
					360,
				],
				[
					'Call to function array_key_exists() with \'c\' and array{a: 1, b?: 2} will always evaluate to false.',
					366,
				],
				[
					'Call to function is_string() with mixed will always evaluate to false.',
					560,
				],
				[
					'Call to function is_callable() with mixed will always evaluate to false.',
					571,
				],
				[
					'Call to function method_exists() with \'CheckTypeFunctionCall\\\\MethodExists\' and \'testWithStringFirst…\' will always evaluate to true.',
					585,
				],
				[
					'Call to function method_exists() with \'UndefinedClass\' and string will always evaluate to false.',
					594,
				],
				[
					'Call to function method_exists() with \'UndefinedClass\' and \'test\' will always evaluate to false.',
					597,
				],
				[
					'Call to function method_exists() with CheckTypeFunctionCall\MethodExists and \'testWithNewObjectIn…\' will always evaluate to true.',
					609,
				],
				[
					'Call to function method_exists() with $this(CheckTypeFunctionCall\MethodExistsWithTrait) and \'method\' will always evaluate to true.',
					624,
				],
				[
					'Call to function method_exists() with $this(CheckTypeFunctionCall\MethodExistsWithTrait) and \'someAnother\' will always evaluate to true.',
					627,
				],
				[
					'Call to function method_exists() with $this(CheckTypeFunctionCall\MethodExistsWithTrait) and \'unknown\' will always evaluate to false.',
					630,
				],
				[
					'Call to function method_exists() with \'CheckTypeFunctionCall\\\\MethodExistsWithTrait\' and \'method\' will always evaluate to true.',
					633,
				],
				[
					'Call to function method_exists() with \'CheckTypeFunctionCall\\\\MethodExistsWithTrait\' and \'someAnother\' will always evaluate to true.',
					636,
				],
				[
					'Call to function method_exists() with \'CheckTypeFunctionCall\\\\MethodExistsWithTrait\' and \'unknown\' will always evaluate to false.',
					639,
				],
				[
					'Call to function method_exists() with \'CheckTypeFunctionCall\\\\MethodExistsWithTrait\' and \'method\' will always evaluate to true.',
					642,
				],
				[
					'Call to function method_exists() with \'CheckTypeFunctionCall\\\\MethodExistsWithTrait\' and \'someAnother\' will always evaluate to true.',
					645,
				],
				[
					'Call to function method_exists() with \'CheckTypeFunctionCall\\\\MethodExistsWithTrait\' and \'unknown\' will always evaluate to false.',
					648,
				],
				[
					'Call to function is_string() with string will always evaluate to true.',
					677,
					'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
				],
				[
					'Call to function assert() with true will always evaluate to true.',
					692,
				],
				[
					'Call to function is_numeric() with \'123\' will always evaluate to true.',
					692,
				],
				[
					'Call to function assert() with false will always evaluate to false.',
					693,
				],
				[
					'Call to function is_numeric() with \'blabla\' will always evaluate to false.',
					693,
				],
				[
					'Call to function assert() with true will always evaluate to true.',
					700,
				],
				[
					'Call to function is_numeric() with 123|float will always evaluate to true.',
					700,
				],
				[
					'Call to function property_exists() with CheckTypeFunctionCall\Bug2221 and \'foo\' will always evaluate to true.',
					782,
				],
				[
					'Call to function property_exists() with CheckTypeFunctionCall\Bug2221 and \'foo\' will always evaluate to true.',
					786,
				],
				[
					'Call to function in_array() with arguments \'foo\', non-empty-array<string> and true will always evaluate to true.',
					890,
				],
				[
					'Call to function in_array() with arguments \'foo\', array{\'foo\'} and true will always evaluate to true.',
					896,
				],
				[
					'Call to function in_array() with arguments \'foo\', array and true will always evaluate to false.',
					900,
				],
			],
		);
	}

	public function testImpossibleCheckTypeFunctionCallWithoutAlwaysTrue(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = false;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse(
			[__DIR__ . '/data/check-type-function-call.php'],
			[
				[
					'Call to function is_int() with string will always evaluate to false.',
					31,
				],
				[
					'Call to function is_callable() with array<int> will always evaluate to false.',
					44,
					'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
				],
				[
					'Call to function assert() with false will always evaluate to false.',
					48,
				],
				[
					'Call to function is_callable() with \'nonexistentFunction\' will always evaluate to false.',
					87,
				],
				[
					'Call to function is_numeric() with \'blabla\' will always evaluate to false.',
					105,
				],
				[
					'Call to function method_exists() with $this(CheckTypeFunctionCall\FinalClassWithMethodExists) and \'doBar\' will always evaluate to false.',
					194,
				],
				[
					'Call to function in_array() with arguments int, array{\'foo\', \'bar\'} and true will always evaluate to false.',
					235,
				],
				[
					'Call to function in_array() with arguments \'bar\'|\'foo\', array{\'baz\', \'lorem\'} and true will always evaluate to false.',
					244,
				],
				[
					'Call to function in_array() with arguments \'bar\', array{}|array{\'foo\'} and true will always evaluate to false.',
					320,
				],
				[
					'Call to function in_array() with arguments \'baz\', array{0: \'bar\', 1?: \'foo\'} and true will always evaluate to false.',
					336,
				],
				[
					'Call to function in_array() with arguments \'foo\', array{} and true will always evaluate to false.',
					343,
				],
				[
					'Call to function array_key_exists() with \'c\' and array{a: 1, b?: 2} will always evaluate to false.',
					366,
				],
				[
					'Call to function is_string() with mixed will always evaluate to false.',
					560,
				],
				[
					'Call to function is_callable() with mixed will always evaluate to false.',
					571,
				],
				[
					'Call to function method_exists() with \'UndefinedClass\' and string will always evaluate to false.',
					594,
				],
				[
					'Call to function method_exists() with \'UndefinedClass\' and \'test\' will always evaluate to false.',
					597,
				],
				[
					'Call to function method_exists() with $this(CheckTypeFunctionCall\MethodExistsWithTrait) and \'unknown\' will always evaluate to false.',
					630,
				],
				[
					'Call to function method_exists() with \'CheckTypeFunctionCall\\\\MethodExistsWithTrait\' and \'unknown\' will always evaluate to false.',
					639,
				],
				[
					'Call to function method_exists() with \'CheckTypeFunctionCall\\\\MethodExistsWithTrait\' and \'unknown\' will always evaluate to false.',
					648,
				],
				[
					'Call to function assert() with false will always evaluate to false.',
					693,
				],
				[
					'Call to function is_numeric() with \'blabla\' will always evaluate to false.',
					693,
				],
				[
					'Call to function in_array() with arguments \'foo\', array and true will always evaluate to false.',
					900,
				],
			],
		);
	}

	public function testDoNotReportTypesFromPhpDocs(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/check-type-function-call-not-phpdoc.php'], [
			[
				'Call to function is_int() with int will always evaluate to true.',
				16,
			],
		]);
	}

	public function testReportTypesFromPhpDocs(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/check-type-function-call-not-phpdoc.php'], [
			[
				'Call to function is_int() with int will always evaluate to true.',
				16,
			],
			[
				'Call to function is_int() with int will always evaluate to true.',
				19,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	public function testBug2550(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-2550.php'], []);
	}

	public function testBug3994(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-3994.php'], []);
	}

	public function testBug1613(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-1613.php'], []);
	}

	public function testBug2714(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-2714.php'], []);
	}

	public function testBug4657(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-4657.php'], []);
	}

	public function testBug4999(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-4999.php'], []);
	}

	public function testArrayIsList(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/array-is-list.php'], [
			[
				'Call to function array_is_list() with array<string, int> will always evaluate to false.',
				13,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Call to function array_is_list() with array{foo: \'bar\', bar: \'baz\'} will always evaluate to false.',
				40,
			],
			[
				'Call to function array_is_list() with array{0: \'foo\', foo: \'bar\', bar: \'baz\'} will always evaluate to false.',
				44,
			],
		]);
	}

	public function testBug3766(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-3766.php'], []);
	}

	public function testBug6305(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-6305.php'], [
			[
				'Call to function is_subclass_of() with Bug6305\B and \'Bug6305\\\A\' will always evaluate to true.',
				11,
			],
			[
				'Call to function is_subclass_of() with Bug6305\B and \'Bug6305\\\B\' will always evaluate to false.',
				14,
			],
		]);
	}

	public function testBug6698(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-6698.php'], []);
	}

	public function testBug5369(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-5369.php'], []);
	}

	public function testBugInArrayDateFormat(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/in-array-date-format.php'], [
			[
				'Call to function in_array() with arguments \'a\', non-empty-array<int, \'a\'> and true will always evaluate to true.',
				39,
			],
			[
				'Call to function in_array() with arguments \'b\', non-empty-array<int, \'a\'> and true will always evaluate to false.',
				43,
			],
			[
				'Call to function in_array() with arguments int, array{} and true will always evaluate to false.',
				47,
			],
			[
				'Call to function in_array() with arguments int, array<int, string> and true will always evaluate to false.',
				61,
			],
		]);
	}

	public function testBug5496(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-5496.php'], []);
	}

	public function testBug3892(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-3892.php'], []);
	}

	public function testBug3314(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-3314.php'], []);
	}

	public function testBug2870(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-2870.php'], []);
	}

	public function testBug5354(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-5354.php'], []);
	}

	public function testSlevomatCsInArrayBug(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/slevomat-cs-in-array.php'], []);
	}

	public function testNonEmptySpecifiedString(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/non-empty-string-impossible-type.php'], []);
	}

	public function testBug2755(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-2755.php'], []);
	}

	public function testBug7079(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-7079.php'], []);
	}

	public function testConditionalTypesInference(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/../../Analyser/data/conditional-types-inference.php'], [
			[
				'Call to function testIsInt() with string will always evaluate to false.',
				49,
			],
			[
				'Call to function testIsNotInt() with string will always evaluate to true.',
				55,
			],
			[
				'Call to function testIsInt() with int will always evaluate to true.',
				66,
			],
			[
				'Call to function testIsNotInt() with int will always evaluate to false.',
				72,
			],
		]);
	}

	public function testBug6697(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-6697.php'], []);
	}

	public function testBug6443(): void
	{
		$this->checkAlwaysTrueCheckTypeFunctionCall = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-6443.php'], []);
	}

}
