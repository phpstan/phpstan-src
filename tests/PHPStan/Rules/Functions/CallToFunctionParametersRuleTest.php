<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function sprintf;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CallToFunctionParametersRule>
 */
class CallToFunctionParametersRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	private bool $checkImplicitMixed = false;

	protected function getRule(): Rule
	{
		$broker = self::createReflectionProvider();
		return new CallToFunctionParametersRule(
			$broker,
			new FunctionCallParametersCheck(new RuleLevelHelper($broker, true, false, true, $this->checkExplicitMixed, $this->checkImplicitMixed, false, true), new NullsafeCheck(), new UnresolvableTypeHelper(), new PropertyReflectionFinder(), true, true, true, true),
		);
	}

	public function testCallToFunctionWithoutParameters(): void
	{
		require_once __DIR__ . '/data/existing-function-definition.php';
		$this->analyse([__DIR__ . '/data/existing-function.php'], []);
	}

	public function testCallToFunctionWithIncorrectParameters(): void
	{
		$setErrorHandlerError = PHP_VERSION_ID < 80000
			? 'Parameter #1 $callback of function set_error_handler expects (callable(int, string, string, int, array): bool)|null, Closure(mixed, mixed, mixed, mixed): void given.'
			: 'Parameter #1 $callback of function set_error_handler expects (callable(int, string, string, int): bool)|null, Closure(mixed, mixed, mixed, mixed): void given.';

		require_once __DIR__ . '/data/incorrect-call-to-function-definition.php';
		$this->analyse([__DIR__ . '/data/incorrect-call-to-function.php'], [
			[
				'Function IncorrectCallToFunction\foo invoked with 1 parameter, 2 required.',
				5,
			],
			[
				'Function IncorrectCallToFunction\foo invoked with 3 parameters, 2 required.',
				7,
			],
			[
				'Parameter #1 $foo of function IncorrectCallToFunction\bar expects int, string given.',
				14,
			],
			[
				$setErrorHandlerError,
				16,
			],
		]);
	}

	public function testcallToFunctionWithCorrectParameters(): void
	{
		$this->analyse([__DIR__ . '/data/call-functions.php'], []);
	}

	public function testCallToFunctionWithOptionalParameters(): void
	{
		require_once __DIR__ . '/data/call-to-function-with-optional-parameters-definition.php';
		$this->analyse([__DIR__ . '/data/call-to-function-with-optional-parameters.php'], [
			[
				'Function CallToFunctionWithOptionalParameters\foo invoked with 3 parameters, 1-2 required.',
				9,
			],
			[
				'Parameter #1 $object of function get_class expects object, null given.',
				12,
			],
			[
				'Parameter #1 $object of function get_class expects object, object|null given.',
				16,
			],
		]);
	}

	public function testCallToFunctionWithDynamicParameters(): void
	{
		require_once __DIR__ . '/data/function-with-variadic-parameters-definition.php';
		$this->analyse([__DIR__ . '/data/function-with-variadic-parameters.php'], [
			[
				'Function FunctionWithVariadicParameters\foo invoked with 0 parameters, at least 1 required.',
				6,
			],
			[
				'Parameter #3 ...$foo of function FunctionWithVariadicParameters\foo expects int, null given.',
				12,
			],
			[
				'Function FunctionWithVariadicParameters\bar invoked with 0 parameters, at least 1 required.',
				18,
			],
		]);
	}

	public function testCallToFunctionWithNullableDynamicParameters(): void
	{
		require_once __DIR__ . '/data/function-with-nullable-variadic-parameters-definition.php';
		$this->analyse([__DIR__ . '/data/function-with-nullable-variadic-parameters.php'], [
			[
				'Function FunctionWithNullableVariadicParameters\foo invoked with 0 parameters, at least 1 required.',
				6,
			],
		]);
	}

	public function testCallToFunctionWithDynamicIterableParameters(): void
	{
		require_once __DIR__ . '/data/function-with-variadic-parameters-definition.php';
		$this->analyse([__DIR__ . '/data/function-with-variadic-parameters-7.1.php'], [
			[
				'Parameter #2 ...$foo of function FunctionWithVariadicParameters\foo expects int, string given.',
				16,
			],
		]);
	}

	public function testCallToArrayUnique(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-array-unique.php'], [
			[
				'Function array_unique invoked with 3 parameters, 1-2 required.',
				3,
			],
		]);
	}

	public function testCallToArrayMapVariadic(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-array-map-unique.php'], []);
	}

	public function testCallToWeirdFunctions(): void
	{
		if (PHP_VERSION_ID >= 80000) {
			$errors = [
				[
					'Function implode invoked with 0 parameters, 1-2 required.',
					3,
				],
				[
					'Function implode invoked with 3 parameters, 1-2 required.',
					6,
				],
				[
					'Function strtok invoked with 0 parameters, 1-2 required.',
					8,
				],
				[
					'Function strtok invoked with 3 parameters, 1-2 required.',
					11,
				],
				[
					sprintf('Function fputcsv invoked with 1 parameter, 2-%d required.', PHP_VERSION_ID >= 80100 ? 6 : 5),
					12,
				],
				[
					'Function imagepng invoked with 0 parameters, 1-4 required.',
					16,
				],
				[
					'Function imagepng invoked with 5 parameters, 1-4 required.',
					19,
				],
				[
					'Function locale_get_display_language invoked with 3 parameters, 1-2 required.',
					30,
				],
				[
					'Function mysqli_fetch_all invoked with 0 parameters, 1-2 required.',
					32,
				],
				[
					'Function mysqli_fetch_all invoked with 3 parameters, 1-2 required.',
					35,
				],
				[
					'Function openssl_open invoked with 4 parameters, 5-6 required.',
					38,
				],
				[
					'Function openssl_open invoked with 7 parameters, 5-6 required.',
					39,
				],
				[
					'Function openssl_x509_parse invoked with 3 parameters, 1-2 required.',
					43,
				],
				[
					'Function openssl_pkcs12_export invoked with 6 parameters, 4-5 required.',
					49,
				],
				[
					'Parameter #1 $depth of function xdebug_call_class expects int, string given.',
					51,
				],
			];
		} else {
			$errors = [
				[
					'Function implode invoked with 0 parameters, 1-2 required.',
					3,
				],
				[
					'Function implode invoked with 3 parameters, 1-2 required.',
					6,
				],
				[
					'Function strtok invoked with 0 parameters, 1-2 required.',
					8,
				],
				[
					'Function strtok invoked with 3 parameters, 1-2 required.',
					11,
				],
				[
					'Function fputcsv invoked with 1 parameter, 2-5 required.',
					12,
				],
				[
					'Function imagepng invoked with 0 parameters, 1-4 required.',
					16,
				],
				[
					'Function imagepng invoked with 5 parameters, 1-4 required.',
					19,
				],
				[
					'Function locale_get_display_language invoked with 3 parameters, 1-2 required.',
					30,
				],
				[
					'Function mysqli_fetch_all invoked with 0 parameters, 1-2 required.',
					32,
				],
				[
					'Function mysqli_fetch_all invoked with 3 parameters, 1-2 required.',
					35,
				],
				[
					'Function openssl_open invoked with 7 parameters, 4-6 required.',
					39,
				],
				[
					'Function openssl_x509_parse invoked with 3 parameters, 1-2 required.',
					43,
				],
				[
					'Function openssl_pkcs12_export invoked with 6 parameters, 4-5 required.',
					49,
				],
				[
					'Parameter #1 $depth of function xdebug_call_class expects int, string given.',
					51,
				],
			];
		}
		$this->analyse([__DIR__ . '/data/call-to-weird-functions.php'], $errors);
	}

	public function testUnpackOnAfter711(): void
	{
		$this->analyse([__DIR__ . '/data/unpack.php'], [
			[
				'Function unpack invoked with 0 parameters, 2-3 required.',
				3,
			],
		]);
	}

	public function testPassingNonVariableToParameterPassedByReference(): void
	{
		require_once __DIR__ . '/data/passed-by-reference.php';
		$this->analyse([__DIR__ . '/data/passed-by-reference.php'], [
			[
				'Parameter #1 $foo of function PassedByReference\foo is passed by reference, so it expects variables only.',
				32,
			],
			[
				'Parameter #1 $foo of function PassedByReference\foo is passed by reference, so it expects variables only.',
				33,
			],
			[
				'Parameter #1 $array of function reset expects array|object, null given.',
				39,
			],
			[
				'Parameter #1 $s of function PassedByReference\bar expects string, int given.',
				48,
			],
		]);
	}

	public function testImplodeOnPhp74(): void
	{
		$errors = [
			[
				'Parameter #1 $glue of function implode expects string, array given.',
				8,
			],
			[
				'Parameter #2 $pieces of function implode expects array, string given.',
				8,
			],
		];
		if (PHP_VERSION_ID >= 80000) {
			$errors = [
				[
					'Parameter #1 $separator of function implode expects string, array given.',
					8,
				],
				[
					'Parameter #2 $array of function implode expects array, string given.',
					8,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/implode-74.php'], $errors);
	}

	public function testImplodeOnLessThanPhp74(): void
	{
		if (PHP_VERSION_ID >= 80000) {
			$errors = [
				[
					'Parameter #1 $separator of function implode expects string, array given.',
					8,
				],
				[
					'Parameter #2 $array of function implode expects array, string given.',
					8,
				],
			];
		} else {
			$errors = [
				[
					'Parameter #1 $glue of function implode expects string, array given.',
					8,
				],
				[
					'Parameter #2 $pieces of function implode expects array, string given.',
					8,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/implode-74.php'], $errors);
	}

	#[RequiresPhp('>= 8.0')]
	public function testImplodeNamedParameters(): void
	{
		$this->analyse([__DIR__ . '/data/implode-named-parameters.php'], [
			[
				'Missing parameter $separator (string) in call to function implode.',
				6,
			],
			[
				'Missing parameter $separator (string) in call to function join.',
				7,
			],
		]);
	}

	public function testVariableIsNotNullAfterSeriesOfConditions(): void
	{
		require_once __DIR__ . '/data/variable-is-not-null-after-conditions.php';
		$this->analyse([__DIR__ . '/data/variable-is-not-null-after-conditions.php'], []);
	}

	public function testUnionIterableTypeShouldAcceptTypeFromOtherTypes(): void
	{
		require_once __DIR__ . '/data/union-iterable-type-issue.php';
		$this->analyse([__DIR__ . '/data/union-iterable-type-issue.php'], []);
	}

	public function testCallToFunctionInForeachCondition(): void
	{
		require_once __DIR__ . '/data/foreach-condition.php';
		$this->analyse([__DIR__ . '/data/foreach-condition.php'], [
			[
				'Parameter #1 $i of function CallToFunctionInForeachCondition\takesString expects string, int given.',
				20,
			],
		]);
	}

	public function testCallToFunctionInDoWhileLoop(): void
	{
		require_once __DIR__ . '/data/do-while-loop.php';
		$this->analyse([__DIR__ . '/data/do-while-loop.php'], []);
	}

	public function testRemoveArrayFromIterable(): void
	{
		require_once __DIR__ . '/data/remove-array-from-iterable.php';
		$this->analyse([__DIR__ . '/data/remove-array-from-iterable.php'], []);
	}

	public function testUnpackOperator(): void
	{
		$this->analyse([__DIR__ . '/data/unpack-operator.php'], [
			[
				'Parameter #2 ...$values of function sprintf expects bool|float|int|string|null, array<string> given.',
				18,
			],
			[
				'Parameter #2 ...$values of function sprintf expects bool|float|int|string|null, array<int, string> given.',
				19,
			],
			[
				'Parameter #2 ...$values of function sprintf expects bool|float|int|string|null, UnpackOperator\Foo given.',
				22,
			],
			[
				'Parameter #2 ...$values of function printf expects bool|float|int|string|null, UnpackOperator\Foo given.',
				24,
			],
		]);
	}

	public function testFputCsv(): void
	{
		$this->analyse([__DIR__ . '/data/fputcsv-fields-parameter.php'], [
			[
				'Parameter #2 $fields of function fputcsv expects array<int|string, bool|float|int|string|null>, array<int, Fputcsv\Person> given.',
				35,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testPutCsvWithStringable(): void
	{
		$this->analyse([__DIR__ . '/data/fputcsv-fields-parameter-php8.php'], [
			// No issues expected
		]);
	}

	public function testFunctionWithNumericParameterThatCreatedByAddition(): void
	{
		$this->analyse([__DIR__ . '/data/function-with-int-parameter-that-created-by-addition.php'], [
			[
				'Parameter #1 $num of function dechex expects int, float|int given.',
				40,
			],
		]);
	}

	public function testWhileLoopLookForAssignsInBranchesVariableExistence(): void
	{
		$this->analyse([__DIR__ . '/data/while-loop-look-for-assigns.php'], []);
	}

	public function testCallableOrClosureProblem(): void
	{
		require_once __DIR__ . '/data/callable-or-closure-problem.php';
		$this->analyse([__DIR__ . '/data/callable-or-closure-problem.php'], []);
	}

	public function testGenericFunction(): void
	{
		require_once __DIR__ . '/data/call-generic-function.php';
		$this->analyse([__DIR__ . '/data/call-generic-function.php'], [
			[
				'Unable to resolve the template type A in call to function CallGenericFunction\f',
				15,
				'See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type',
			],
			[
				'Parameter #1 $a of function CallGenericFunction\g expects DateTime, DateTimeImmutable given.',
				26,
			],
			[
				'Unable to resolve the template type A in call to function CallGenericFunction\g',
				26,
				'See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type',
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testNamedArguments(): void
	{
		$errors = [
			[
				'Missing parameter $j (int) in call to function FunctionNamedArguments\foo.',
				7,
			],
			[
				'Unknown parameter $z in call to function FunctionNamedArguments\foo.',
				8,
			],
			[
				'Unknown parameter $a in call to function array_merge.',
				14,
			],
			[
				'Named parameter $var overwrites previous argument.',
				19,
			],
		];

		require_once __DIR__ . '/data/named-arguments-define.php';
		$this->analyse([__DIR__ . '/data/named-arguments.php'], $errors);
	}

	#[RequiresPhp('>= 8.1')]
	public function testNamedArgumentsAfterUnpacking(): void
	{
		$this->analyse([__DIR__ . '/data/named-arguments-after-unpacking.php'], [
			[
				'Argument for parameter $b has already been passed.',
				14,
			],
		]);
	}

	public function testBug4514(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4514.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug13719(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13719.php'], [
			[
				'Unknown parameter $greetings in call to function Bug13719\non_variadic.',
				20,
			],
			[
				'Unknown parameter $greetings in call to function Bug13719\implicit_variadic.',
				27,
			],
		]);
	}

	public function testBug4530(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4530.php'], []);
	}

	public function testBug2268(): void
	{
		require_once __DIR__ . '/data/bug-2268.php';
		$this->analyse([__DIR__ . '/data/bug-2268.php'], []);
	}

	public function testBug2434(): void
	{
		require_once __DIR__ . '/data/bug-2434.php';
		$this->analyse([__DIR__ . '/data/bug-2434.php'], []);
	}

	public function testBug2846(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2846.php'], []);
	}

	public function testBug3608(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3608.php'], []);
	}

	public function testBug3631(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3631.php'], []);
	}

	public function testBug3920(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3920.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBugNumberFormatNamedArguments(): void
	{
		$this->analyse([__DIR__ . '/data/number-format-named-arguments.php'], []);
	}

	public function testArrayReduceCallback(): void
	{
		$this->analyse([__DIR__ . '/data/array_reduce.php'], [
			[
				'Parameter #2 $callback of function array_reduce expects callable(string, 1|2|3): string, Closure(string, string): string given.',
				5,
			],
			[
				'Parameter #2 $callback of function array_reduce expects callable(non-empty-string|null, 1|2|3): (non-empty-string|null), Closure(string, int): non-falsy-string given.',
				13,
				'Type string of parameter #1 $foo of passed callable needs to be same or wider than parameter type string|null of accepting callable.',
			],
			[
				'Parameter #2 $callback of function array_reduce expects callable(non-empty-string|null, 1|2|3): (non-empty-string|null), Closure(string, int): non-falsy-string given.',
				22,
				'Type string of parameter #1 $foo of passed callable needs to be same or wider than parameter type string|null of accepting callable.',
			],
		]);
	}

	public function testArrayReduceArrowFunctionCallback(): void
	{
		$this->analyse([__DIR__ . '/data/array_reduce_arrow.php'], [
			[
				'Parameter #2 $callback of function array_reduce expects callable(string, 1|2|3): string, Closure(string, string): string given.',
				5,
			],
			[
				'Parameter #2 $callback of function array_reduce expects callable(non-empty-string|null, 1|2|3): (non-empty-string|null), Closure(string, int): non-falsy-string given.',
				11,
				'Type string of parameter #1 $foo of passed callable needs to be same or wider than parameter type string|null of accepting callable.',
			],
			[
				'Parameter #2 $callback of function array_reduce expects callable(non-empty-string|null, 1|2|3): (non-empty-string|null), Closure(string, int): non-falsy-string given.',
				18,
				'Type string of parameter #1 $foo of passed callable needs to be same or wider than parameter type string|null of accepting callable.',
			],
		]);
	}

	public function testArrayWalkCallback(): void
	{
		$this->analyse([__DIR__ . '/data/array_walk.php'], [
			[
				'Parameter #2 $callback of function array_walk expects callable(1|2, \'bar\'|\'foo\'): mixed, Closure(stdClass, float): \'\' given.',
				6,
			],
			[
				'Parameter #2 $callback of function array_walk expects callable(1|2, \'bar\'|\'foo\', \'extra\'): mixed, Closure(int, string, int): \'\' given.',
				14,
			],
			[
				'Parameter #2 $callback of function array_walk expects callable(1|2, \'bar\'|\'foo\'): mixed, Closure(int, string, int): \'\' given.',
				23,
				'Parameter #3 $extra of passed callable is required but accepting callable does not have that parameter. It will be called without it.',
			],
		]);
	}

	public function testArrayWalkArrowFunctionCallback(): void
	{
		$this->analyse([__DIR__ . '/data/array_walk_arrow.php'], [
			[
				'Parameter #2 $callback of function array_walk expects callable(1|2, \'bar\'|\'foo\'): mixed, Closure(stdClass, float): \'\' given.',
				6,
			],
			[
				'Parameter #2 $callback of function array_walk expects callable(1|2, \'bar\'|\'foo\', \'extra\'): mixed, Closure(int, string, int): \'\' given.',
				12,
			],
			[
				'Parameter #2 $callback of function array_walk expects callable(1|2, \'bar\'|\'foo\'): mixed, Closure(int, string, int): \'\' given.',
				19,
				'Parameter #3 $extra of passed callable is required but accepting callable does not have that parameter. It will be called without it.',
			],
		]);
	}

	public function testArrayUdiffCallback(): void
	{
		$this->analyse([__DIR__ . '/data/array_udiff.php'], [
			[
				'Parameter #3 $data_comp_func of function array_udiff expects callable(1|2|3|4|5|6, 1|2|3|4|5|6): int, Closure(string, string): string given.',
				6,
			],
			[
				"Parameter #3 \$data_comp_func of function array_udiff expects callable(1|2|3|4|5|6, 1|2|3|4|5|6): int, Closure(int, int): ('11'|'12'|'13'|'14'|'15'|'16'|'21'|'22'|'23'|'24'|'25'|'26'|'31'|'32'|'33'|'34'|'35'|'36'|'41'|'42'|'43'|'44'|'45'|'46'|'51'|'52'|'53'|'54'|'55'|'56'|'61'|'62'|'63'|'64'|'65'|'66') given.",
				14,
			],
			[
				'Parameter #1 $arr1 of function array_udiff expects array<(int&TK)|(string&TK), string>, null given.',
				20,
			],
			[
				'Parameter #2 $arr2 of function array_udiff expects array<(int&TK)|(string&TK), string>, null given.',
				21,
			],
			[
				'Parameter #3 $data_comp_func of function array_udiff expects callable(string, string): int, Closure(string, int): non-empty-string given.',
				22,
			],
		]);
	}

	public function testPregReplaceCallback(): void
	{
		$this->analyse([__DIR__ . '/data/preg_replace_callback.php'], [
			[
				'Parameter #2 $callback of function preg_replace_callback expects callable(array<string>): string, Closure(string): string given.',
				6,
			],
			[
				'Parameter #2 $callback of function preg_replace_callback expects callable(array<string>): string, Closure(string): string given.',
				13,
			],
			[
				'Parameter #2 $callback of function preg_replace_callback expects callable(array<string>): string, Closure(array): void given.',
				20,
			],
			[
				'Parameter #2 $callback of function preg_replace_callback expects callable(array<string>): string, Closure(): void given.',
				25,
			],
		]);
	}

	public function testMbEregReplaceCallback(): void
	{
		$this->analyse([__DIR__ . '/data/mb_ereg_replace_callback.php'], [
			[
				'Parameter #2 $callback of function mb_ereg_replace_callback expects callable(array<int|string, string>): string, Closure(string): string given.',
				6,
			],
			[
				'Parameter #2 $callback of function mb_ereg_replace_callback expects callable(array<int|string, string>): string, Closure(string): string given.',
				13,
			],
			[
				'Parameter #2 $callback of function mb_ereg_replace_callback expects callable(array<int|string, string>): string, Closure(array): void given.',
				20,
			],
			[
				'Parameter #2 $callback of function mb_ereg_replace_callback expects callable(array<int|string, string>): string, Closure(): void given.',
				25,
			],
		]);
	}

	public function testUasortCallback(): void
	{
		$this->analyse([__DIR__ . '/data/uasort.php'], [
			[
				'Parameter #2 $callback of function uasort expects callable(1|2|3, 1|2|3): int, Closure(string, string): 1 given.',
				7,
			],
		]);
	}

	public function testUasortArrowFunctionCallback(): void
	{
		$this->analyse([__DIR__ . '/data/uasort_arrow.php'], [
			[
				'Parameter #2 $callback of function uasort expects callable(1|2|3, 1|2|3): int, Closure(string, string): 1 given.',
				7,
			],
		]);
	}

	public function testUsortCallback(): void
	{
		$this->analyse([__DIR__ . '/data/usort.php'], [
			[
				'Parameter #2 $callback of function usort expects callable(1|2|3, 1|2|3): int, Closure(string, string): 1 given.',
				14,
			],
		]);
	}

	public function testUsortArrowFunctionCallback(): void
	{
		$this->analyse([__DIR__ . '/data/usort_arrow.php'], [
			[
				'Parameter #2 $callback of function usort expects callable(1|2|3, 1|2|3): int, Closure(string, string): 1 given.',
				14,
			],
		]);
	}

	public function testUksortCallback(): void
	{
		$this->analyse([__DIR__ . '/data/uksort.php'], [
			[
				'Parameter #2 $callback of function uksort expects callable(\'one\'|\'three\'|\'two\', \'one\'|\'three\'|\'two\'): int, Closure(stdClass, stdClass): 1 given.',
				14,
			],
			[
				'Parameter #2 $callback of function uksort expects callable(int, int): int, Closure(string, string): 1 given.',
				50,
			],
		]);
	}

	public function testUksortArrowFunctionCallback(): void
	{
		$this->analyse([__DIR__ . '/data/uksort_arrow.php'], [
			[
				'Parameter #2 $callback of function uksort expects callable(\'one\'|\'three\'|\'two\', \'one\'|\'three\'|\'two\'): int, Closure(stdClass, stdClass): 1 given.',
				14,
			],
			[
				'Parameter #2 $callback of function uksort expects callable(int, int): int, Closure(string, string): 1 given.',
				44,
			],
		]);
	}

	public function testVaryingAcceptor(): void
	{
		require_once __DIR__ . '/data/varying-acceptor.php';
		$this->analyse([__DIR__ . '/data/varying-acceptor.php'], [
			[
				'Parameter #1 $closure of function VaryingAcceptor\bar expects callable(callable(): string): string, callable(callable(): int): string given.',
				17,
			],
		]);
	}

	public function testBug3660(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3660.php'], [
			[
				'Parameter #1 $string of function strlen expects string, int given.',
				7,
			],
			[
				'Parameter #1 $string of function strlen expects string, int given.',
				8,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testExplode(): void
	{
		$this->analyse([__DIR__ . '/data/explode-80.php'], [
			[
				'Parameter #1 $separator of function explode expects non-empty-string, string given.',
				14,
			],
			[
				'Parameter #1 $separator of function explode expects non-empty-string, \'\' given.',
				16,
			],
			[
				'Parameter #1 $separator of function explode expects non-empty-string, 1 given.',
				17,
			],
		]);
	}

	public function testProcOpen(): void
	{
		$this->analyse([__DIR__ . '/data/proc_open.php'], [
			[
				"Parameter #1 \$command of function proc_open expects list<string>|string, array{something: 'bogus', in: 'here'} given.",
				6,
				"Type #1 from the union: array{something: 'bogus', in: 'here'} is not a list.",
			],
		]);
	}

	public function testBug5609(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5609.php'], []);
	}

	public static function dataArrayMapMultiple(): array
	{
		return [
			[true],
			[false],
		];
	}

	#[DataProvider('dataArrayMapMultiple')]
	public function testArrayMapMultiple(bool $checkExplicitMixed): void
	{
		$this->checkExplicitMixed = $checkExplicitMixed;
		$this->analyse([__DIR__ . '/data/array_map_multiple.php'], [
			[
				'Parameter #1 $callback of function array_map expects (callable(1|2, \'bar\'|\'foo\'): mixed)|null, Closure(int, int): void given.',
				58,
			],
		]);
	}

	public static function dataArrayFilterCallback(): array
	{
		return [
			[true],
			[false],
		];
	}

	#[DataProvider('dataArrayFilterCallback')]
	public function testArrayFilterCallback(bool $checkExplicitMixed): void
	{
		$this->checkExplicitMixed = $checkExplicitMixed;
		$errors = [
			[
				'Parameter #2 $callback of function array_filter expects (callable(int): bool)|null, Closure(string): true given.',
				17,
			],
		];
		if ($checkExplicitMixed) {
			$errors[] = [
				'Parameter #2 $callback of function array_filter expects (callable(mixed): bool)|null, Closure(int): true given.',
				20,
				'Type #1 from the union: Type int of parameter #1 $i of passed callable needs to be same or wider than parameter type mixed of accepting callable.',
			];
		}
		$this->analyse([__DIR__ . '/data/array_filter_callback.php'], $errors);
	}

	#[RequiresPhp('>= 8.4')]
	public function testArrayAllCallback(): void
	{
		$this->analyse([__DIR__ . '/data/array_all.php'], [
			[
				'Parameter #2 $callback of function array_all expects callable(1|2, \'bar\'|\'foo\'): bool, Closure(string, int): bool given.',
				22,
			],
			[
				'Parameter #2 $callback of function array_all expects callable(1|2, \'bar\'|\'foo\'): bool, Closure(string, int): bool given.',
				30,
			],
			[
				'Parameter #2 $callback of function array_all expects callable(1|2, \'bar\'|\'foo\'): bool, Closure(int, string): (\'bar\'|\'foo\') given.',
				36,
			],
			[
				'Parameter #2 $callback of function array_all expects callable(mixed, int|string): bool, Closure(string, array): false given.',
				52,
			],
			[
				'Parameter #2 $callback of function array_all expects callable(mixed, int|string): bool, Closure(string, int): array{} given.',
				55,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testArrayAnyCallback(): void
	{
		$this->analyse([__DIR__ . '/data/array_any.php'], [
			[
				'Parameter #2 $callback of function array_any expects callable(1|2, \'bar\'|\'foo\'): bool, Closure(string, int): bool given.',
				22,
			],
			[
				'Parameter #2 $callback of function array_any expects callable(1|2, \'bar\'|\'foo\'): bool, Closure(string, int): bool given.',
				30,
			],
			[
				'Parameter #2 $callback of function array_any expects callable(1|2, \'bar\'|\'foo\'): bool, Closure(int, string): (\'bar\'|\'foo\') given.',
				36,
			],
			[
				'Parameter #2 $callback of function array_any expects callable(mixed, int|string): bool, Closure(string, array): false given.',
				52,
			],
			[
				'Parameter #2 $callback of function array_any expects callable(mixed, int|string): bool, Closure(string, int): array{} given.',
				55,
			],
		]);
	}

	public function testArrayFindCallback(): void
	{
		$this->analyse([__DIR__ . '/data/array_find.php'], [
			[
				'Parameter #2 $callback of function array_find expects callable(1|2, \'bar\'|\'foo\'): bool, Closure(string, int): bool given.',
				22,
			],
			[
				'Parameter #2 $callback of function array_find expects callable(1|2, \'bar\'|\'foo\'): bool, Closure(string, int): bool given.',
				30,
			],
			[
				'Parameter #2 $callback of function array_find expects callable(1|2, \'bar\'|\'foo\'): bool, Closure(int, string): (\'bar\'|\'foo\') given.',
				36,
			],
			[
				'Parameter #2 $callback of function array_find expects callable(mixed, int|string): bool, Closure(string, array): false given.',
				52,
			],
			[
				'Parameter #2 $callback of function array_find expects callable(mixed, int|string): bool, Closure(string, int): array{} given.',
				55,
			],
		]);
	}

	public function testArrayFindKeyCallback(): void
	{
		$this->analyse([__DIR__ . '/data/array_find_key.php'], [
			[
				'Parameter #2 $callback of function array_find_key expects callable(1|2, \'bar\'|\'foo\'): bool, Closure(string, int): bool given.',
				22,
			],
			[
				'Parameter #2 $callback of function array_find_key expects callable(1|2, \'bar\'|\'foo\'): bool, Closure(string, int): bool given.',
				30,
			],
			[
				'Parameter #2 $callback of function array_find_key expects callable(1|2, \'bar\'|\'foo\'): bool, Closure(int, string): (\'bar\'|\'foo\') given.',
				36,
			],
			[
				'Parameter #2 $callback of function array_find_key expects callable(mixed, int|string): bool, Closure(string, array): false given.',
				52,
			],
			[
				'Parameter #2 $callback of function array_find_key expects callable(mixed, int|string): bool, Closure(string, int): array{} given.',
				55,
			],
		]);
	}

	public function testBug5356(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5356.php'], [
			[
				'Parameter #1 $callback of function array_map expects (callable(string): mixed)|null, Closure(array): \'a\' given.',
				13,
			],
			[
				'Parameter #1 $callback of function array_map expects (callable(string): mixed)|null, Closure(array): \'a\' given.',
				21,
			],
		]);
	}

	public function testBug1954(): void
	{
		$this->analyse([__DIR__ . '/data/bug-1954.php'], [
			[
				'Parameter #1 $callback of function array_map expects (callable(1|stdClass): mixed)|null, Closure(string): string given.',
				7,
			],
		]);
	}

	public function testBug2782(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2782.php'], [
			[
				'Parameter #2 $callback of function usort expects callable(stdClass, stdClass): int, Closure(int, int): (-1|1) given.',
				13,
			],
		]);
	}

	public function testBug5661(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5661.php'], []);
	}

	public function testBug5872(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5872.php'], [
			[
				'Parameter #2 $array of function array_map expects array, mixed given.',
				12,
			],
		]);
	}

	public function testBug5834(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5834.php'], []);
	}

	public function testBug5881(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5881.php'], []);
	}

	public function testBug5861(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5861.php'], []);
	}

	public function testCallUserFuncArray(): void
	{
		if (PHP_VERSION_ID >= 80000) {
			$errors = [];
		} else {
			$errors = [
				[
					'Parameter #2 $parameters of function call_user_func_array expects array<int, mixed>, array<string, array<string, int>> given.',
					3,
				],
			];
		}
		$this->analyse([__DIR__ . '/data/call-user-func-array.php'], $errors);
	}

	public function testFirstClassCallables(): void
	{
		// handled by a different rule
		$this->analyse([__DIR__ . '/data/first-class-callables.php'], []);
	}

	public function testBug4413(): void
	{
		require_once __DIR__ . '/data/bug-4413.php';
		$this->analyse([__DIR__ . '/data/bug-4413.php'], [
			[
				'Parameter #1 $date of function Bug4413\takesDate expects class-string<DateTime>, string given.',
				18,
			],
		]);
	}

	public function testBug6383(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6383.php'], []);
	}

	public function testBug6448(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80100) {
			$errors[] = [
				'Function fputcsv invoked with 6 parameters, 2-5 required.',
				28,
			];
		}
		$this->analyse([__DIR__ . '/data/bug-6448.php'], $errors);
	}

	public function testBug7017(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80100) {
			$errors[] = [
				'Parameter #1 $finfo of function finfo_close expects resource, finfo given.',
				7,
			];
		}
		$this->analyse([__DIR__ . '/data/bug-7017.php'], $errors);
	}

	public function testBug4371(): void
	{
		$errors = [
			[
				'Parameter #1 $object_or_class of function is_a expects object, string given.',
				14,
			],
			[
				'Parameter #1 $object_or_class of function is_a expects object, string given.',
				22,
			],
		];

		if (PHP_VERSION_ID < 80000) {
			// php 7.x had different parameter names
			$errors = [
				[
					'Parameter #1 $object_or_string of function is_a expects object, string given.',
					14,
				],
				[
					'Parameter #1 $object_or_string of function is_a expects object, string given.',
					22,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/bug-4371.php'], $errors);
	}

	public function testIsSubclassAllowString(): void
	{
		$errors = [
			[
				'Parameter #1 $object_or_class of function is_subclass_of expects object, string given.',
				11,
			],
			[
				'Parameter #1 $object_or_class of function is_subclass_of expects object, string given.',
				14,
			],
			[
				'Parameter #1 $object_or_class of function is_subclass_of expects object, string given.',
				17,
			],
		];

		if (PHP_VERSION_ID < 80000) {
			// php 7.x had different parameter names
			$errors = [
				[
					'Parameter #1 $object_or_string of function is_subclass_of expects object, string given.',
					11,
				],
				[
					'Parameter #1 $object_or_string of function is_subclass_of expects object, string given.',
					14,
				],
				[
					'Parameter #1 $object_or_string of function is_subclass_of expects object, string given.',
					17,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/is-subclass-allow-string.php'], $errors);
	}

	public function testBug6987(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6987.php'], []);
	}

	public function testDiscussion7450WithoutCheckExplicitMixed(): void
	{
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/discussion-7450.php'], []);
	}

	public function testDiscussion7450WithCheckExplicitMixed(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/discussion-7450.php'], [
			[
				'Parameter #1 $foo of function Discussion7450\foo expects array{policy: non-empty-string, entitlements: array<non-empty-string>}, array{policy: mixed, entitlements: mixed} given.',
				18,
				"• Offset 'policy' (non-empty-string) does not accept type mixed.
• Offset 'entitlements' (array<non-empty-string>) does not accept type mixed.",
			],
			[
				'Parameter #1 $foo of function Discussion7450\foo expects array{policy: non-empty-string, entitlements: array<non-empty-string>}, array{policy: mixed, entitlements: mixed} given.',
				28,
				"• Offset 'policy' (non-empty-string) does not accept type mixed.
• Offset 'entitlements' (array<non-empty-string>) does not accept type mixed.",
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug7211(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7211.php'], []);
	}

	public function testBug5474(): void
	{
		$this->analyse([__DIR__ . '/../Comparison/data/bug-5474.php'], []);
	}

	public function testBug6261(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6261.php'], []);
	}

	public function testBug6781(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6781.php'], []);
	}

	public function testBug2343(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2343.php'], []);
	}

	public function testBug7676(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7676.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug7138(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7138.php'], []);
	}

	public function testBug2911(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2911.php'], [
			[
				'Parameter #1 $array of function Bug2911\bar expects array{bar: string}, non-empty-array given.',
				23,
			],
		]);
	}

	public function testBug7156(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7156.php'], []);
	}

	public function testBug7973(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7973.php'], []);
	}

	public function testBug7562(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7562.php'], []);
	}

	public function testBug7823(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7823.php'], [
			[
				'Parameter #1 $s of function Bug7823\sayHello expects literal-string, class-string given.',
				34,
			],
		]);
	}

	public function testCurlSetOpt(): void
	{
		$this->analyse([__DIR__ . '/data/curl_setopt.php'], [
			[
				'Parameter #3 $value of function curl_setopt expects 0|2, bool given.',
				10,
			],
			[
				'Parameter #3 $value of function curl_setopt expects non-empty-string, int given.',
				16,
			],
			[
				'Parameter #3 $value of function curl_setopt expects array<int, string>, int given.',
				17,
			],
			[
				'Parameter #3 $value of function curl_setopt expects non-empty-string, null given.',
				18,
			],
			[
				'Parameter #3 $value of function curl_setopt expects string, int given.',
				19,
			],
			[
				'Parameter #3 $value of function curl_setopt expects string, int given.',
				20,
			],
			[
				'Parameter #3 $value of function curl_setopt expects bool, int given.',
				22,
			],
			[
				'Parameter #3 $value of function curl_setopt expects bool, string given.',
				23,
			],
			[
				'Parameter #3 $value of function curl_setopt expects int, string given.',
				25,
			],
			[
				'Parameter #3 $value of function curl_setopt expects array, string given.',
				27,
			],
			[
				'Parameter #3 $value of function curl_setopt expects resource, string given.',
				29,
			],
			[
				'Parameter #3 $value of function curl_setopt expects array|string, int given.',
				31,
			],
			[
				'Parameter #3 $value of function curl_setopt expects non-empty-string, \'\' given.',
				33,
			],
			[
				'Parameter #3 $value of function curl_setopt expects non-empty-string|null, \'\' given.',
				34,
			],
			[
				'Parameter #3 $value of function curl_setopt expects array<int, string>, array<string, string> given.',
				77,
			],
			[
				'Parameter #3 $value of function curl_setopt expects bool|int, int|string given.',
				96,
			],
		]);
	}

	public function testCurlSetOptInvalidShare(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$errors = [
				['Parameter #3 $value of function curl_setopt expects resource, string given.', 8],
			];
		} elseif (PHP_VERSION_ID < 80500) {
			$errors = [
				['Parameter #3 $value of function curl_setopt expects CurlShareHandle, string given.', 8],
			];
		} else {
			$errors = [
				['Parameter #3 $value of function curl_setopt expects CurlShareHandle|CurlSharePersistentHandle, string given.', 8],
			];
		}

		$this->analyse([__DIR__ . '/data/curl_setopt_share.php'], $errors);
	}

	#[RequiresPhp('>= 8.1')]
	public function testCurlSetOptArray(): void
	{
		$this->analyse([__DIR__ . '/data/curl-setopt-array.php'], [
			[
				"Parameter #2 \$options of function curl_setopt_array expects array{19913: bool, 10102: string, 68: int, 13: int, 84: int, 10036: non-empty-string|null}, array{19913: true, 10102: '', 68: 10, 13: 30, 84: 2, 10036: CurlSetOptArray\RequestMethod::POST} given.",
				30,
				'Offset 10036 (non-empty-string|null) does not accept type CurlSetOptArray\RequestMethod::POST.',
			],
			[
				"Parameter #2 \$options of function curl_setopt_array expects array{19913: bool, 10102: string, 68: int, 13: int, 84: int, 10036: non-empty-string|null}, array{19913: true, 10102: '', 68: 10, 13: 30, 84: 2, 10036: CurlSetOptArray\BackedRequestMethod::POST} given.",
				42,
				'Offset 10036 (non-empty-string|null) does not accept type CurlSetOptArray\BackedRequestMethod::POST.',
			],
			[
				"Parameter #2 \$options of function curl_setopt_array expects array{19913: bool, 10102: string, 68: int, 13: int, 84: int}, array{19913: '123', 10102: '', 68: 10, 13: 30, 84: false} given.",
				54,
				'Offset 19913 (bool) does not accept type string.',
			],

		]);
	}

	public function testBug8280(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8280.php'], []);
	}

	public function testBug8389(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8389.php'], []);
	}

	public function testBug8449(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8449.php'], []);
	}

	public function testBug5288(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5288.php'], []);
	}

	public function testBug5986(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5986.php'], [
			[
				'Parameter #1 $data of function Bug5986\test2 expects array{mov?: int, appliesTo?: string, expireDate?: string|null, effectiveFrom?: int, merchantId?: int, link?: string, channel?: string, voucherExternalId?: int}, array{mov?: int, appliesTo?: string, expireDate?: string|null, effectiveFrom?: string, merchantId?: int, link?: string, channel?: string, voucherExternalId?: int} given.',
				18,
				"Offset 'effectiveFrom' (int) does not accept type string.",
			],
		]);
	}

	public function testBug7239(): void
	{
		$tipText = 'array{} is empty.';
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-7239.php'], [
			[
				'Parameter #1 ...$arg1 of function max expects non-empty-array, array{} given.',
				16,
				$tipText,
			],
			[
				'Parameter #1 ...$arg1 of function min expects non-empty-array, array{} given.',
				17,
				$tipText,
			],
			[
				'Parameter #1 ...$arg1 of function max expects non-empty-array, array{} given.',
				23,
				$tipText,
			],
			[
				'Parameter #1 ...$arg1 of function min expects non-empty-array, array{} given.',
				24,
				$tipText,
			],
			[
				'Parameter #1 ...$arg1 of function max expects non-empty-array, array{} given.',
				34,
				$tipText,
			],
			[
				'Parameter #1 ...$arg1 of function min expects non-empty-array, array{} given.',
				35,
				$tipText,
			],
		]);
	}

	public function testFilterInputType(): void
	{
		$errors = [
			[
				'Parameter #1 $type of function filter_input expects 0|1|2|4|5, -1 given.',
				16,
			],
			[
				'Parameter #1 $type of function filter_input expects 0|1|2|4|5, int given.',
				17,
			],
			[
				'Parameter #1 $type of function filter_input_array expects 0|1|2|4|5, -1 given.',
				28,
			],
			[
				'Parameter #1 $type of function filter_input_array expects 0|1|2|4|5, int given.',
				29,
			],
		];

		if (PHP_VERSION_ID < 80000) {
			$errors = [];
		}

		$this->analyse([__DIR__ . '/data/filter-input-type.php'], $errors);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug9283(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9283.php'], []);
	}

	public function testBug9380(): void
	{
		$errors = [
			[
				'Parameter #2 $message_type of function error_log expects 0|1|3|4, 2 given.',
				7,
			],
		];

		if (PHP_VERSION_ID < 80000) {
			$errors = [];
		}

		$this->analyse([__DIR__ . '/data/bug-9380.php'], $errors);
	}

	public function testBenevolentSuperglobalKeys(): void
	{
		$this->analyse([__DIR__ . '/data/benevolent-superglobal-keys.php'], []);
	}

	public function testFileParams(): void
	{
		$this->analyse([__DIR__ . '/data/file.php'], [
			[
				'Parameter #2 $flags of function file expects 0|1|2|3|4|5|6|7|16|17|18|19|20|21|22|23, 8 given.',
				16,
			],
		]);
	}

	public function testFlockParams(): void
	{
		$this->analyse([__DIR__ . '/data/flock.php'], [
			[
				'Parameter #2 $operation of function flock expects int<0, 7>, 8 given.',
				45,
			],
		]);
	}

	#[RequiresPhp('>= 8.3')]
	public function testJsonValidate(): void
	{
		$this->analyse([__DIR__ . '/data/json_validate.php'], [
			[
				'Parameter #2 $depth of function json_validate expects int<1, max>, 0 given.',
				6,
			],
			[
				'Parameter #3 $flags of function json_validate expects 0|1048576, 2 given.',
				7,
			],
		]);
	}

	public function testBug4612(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4612.php'], []);
	}

	public function testBug2508(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2508.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug6175(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6175.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug9699(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9699.php'], [
			[
				'Parameter #1 $f of function Bug9699\int_int_int_string expects Closure(int, int, int, string): int, Closure(int, int, int ...): int given.',
				19,
			],
		]);
	}

	public function testBug9133(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9133.php'], [
			[
				'Parameter #1 $value of function Bug9133\assertNever expects never, int given.',
				29,
			],
		]);
	}

	public function testBug9803(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9803.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug9018(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9018.php'], [
			[
				'Unknown parameter $str1 in call to function levenshtein.',
				13,
			],
			[
				'Unknown parameter $str2 in call to function levenshtein.',
				13,
			],
			[
				'Missing parameter $string1 (string) in call to function levenshtein.',
				13,
			],
			[
				'Missing parameter $string2 (string) in call to function levenshtein.',
				13,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug9399(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9399.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug9559(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9559.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug9923(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9923.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug9823(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9823.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testNamedParametersForMultiVariantFunctions(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-function-named-params-multivariant.php'], []);
	}

	public function testBug9793(): void
	{
		$errors = [];

		if (PHP_VERSION_ID < 80200) {
			$errors = [
				[
					'Parameter #1 $iterator of function iterator_to_array expects Traversable, array<stdClass> given.',
					13,
				],
				[
					'Parameter #1 $iterator of function iterator_to_array expects Traversable, array<stdClass>|Iterator<mixed, stdClass> given.',
					14,
				],
				[
					'Parameter #1 $iterator of function iterator_count expects Traversable, array<stdClass> given.',
					15,
				],
				[
					'Parameter #1 $iterator of function iterator_count expects Traversable, array<stdClass>|Iterator<mixed, stdClass> given.',
					16,
				],
			];
		}

		$errors[] = [
			'Parameter #1 $iterator of function iterator_apply expects Traversable, array<stdClass> given.',
			17,
		];
		$errors[] = [
			'Parameter #1 $iterator of function iterator_apply expects Traversable, array<stdClass>|Iterator<mixed, stdClass> given.',
			18,
		];

		$this->analyse([__DIR__ . '/data/bug-9793.php'], $errors);
	}

	#[RequiresPhp('>= 8.0')]
	public function testCallToArrayFilterWithNullCallback(): void
	{
		$this->analyse([__DIR__ . '/data/array_filter_null_callback.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug10171(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10171.php'], [
			[
				'Unknown parameter $samesite in call to function setcookie.',
				12,
			],
			[
				'Function setcookie invoked with 9 parameters, 1-7 required.',
				13,
			],
			[
				'Unknown parameter $samesite in call to function setrawcookie.',
				25,
			],
			[
				'Function setrawcookie invoked with 9 parameters, 1-7 required.',
				26,
			],
		]);
	}

	public function testBug6720(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6720.php'], []);
	}

	public function testBug8659(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8659.php'], []);
	}

	public function testBug9580(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9580.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug7283(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7283.php'], []);
	}

	public function testBug9697(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9697.php'], []);
	}

	public function testDiscussion10454(): void
	{
		$this->analyse([__DIR__ . '/data/discussion-10454.php'], [
			[
				"Parameter #2 \$callback of function array_filter expects (callable('bar'|'baz'|'foo'|'quux'|'qux'): bool)|null, Closure(string): stdClass given.",
				13,
			],
			[
				"Parameter #2 \$callback of function array_filter expects (callable('bar'|'baz'|'foo'|'quux'|'qux'): bool)|null, Closure(string): stdClass given.",
				23,
			],
		]);
	}

	public function testBug10527(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10527.php'], []);
	}

	public function testBug10626(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10626.php'], [
			[
				'Parameter #1 $value of function Bug10626\intByValue expects int, string given.',
				16,
			],
			[
				'Parameter #1 $value of function Bug10626\intByReference expects int, string given.',
				17,
			],
		]);
	}

	public function testArgon2PasswordHash(): void
	{
		$this->analyse([__DIR__ . '/data/argon2id-password-hash.php'], []);
	}

	public function testBug4960(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4960.php'], []);
	}

	public function testParamClosureThis(): void
	{
		$this->analyse([__DIR__ . '/data/function-call-param-closure-this.php'], [
			[
				'Parameter #1 $cb of function FunctionCallParamClosureThis\acceptClosure expects bindable closure, static closure given.',
				18,
			],
			[
				'Parameter #1 $cb of function FunctionCallParamClosureThis\acceptClosure expects bindable closure, static closure given.',
				23,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug10297(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10297.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug10974(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10974.php'], []);
	}

	public function testCountArrayShift(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$errors = [
				[
					'Parameter #1 $var of function count expects array|Countable, array<mixed>|false given.',
					8,
				],
				[
					'Parameter #1 $var of function count expects array|Countable, array<mixed>|false given.',
					16,
				],
			];
		} else {
			$errors = [
				[
					'Parameter #1 $value of function count expects array|Countable, array<mixed>|false given.',
					8,
				],
				[
					'Parameter #1 $value of function count expects array|Countable, array<mixed>|false given.',
					16,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/count-array-shift.php'], $errors);
	}

	public function testArrayDiffUassoc(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/array_diff_uassoc.php'], [
			[
				'Parameter #3 $data_comp_func of function array_diff_uassoc expects callable(\'a\'|\'b\'|\'c\'|\'d\', \'a\'|\'b\'|\'c\'|\'d\'): int, Closure(int, int): int<-1, 1> given.',
				22,
			],
			[
				'Parameter #3 $data_comp_func of function array_diff_uassoc expects callable(0|1|2|3, 0|1|2|3): int, Closure(string, string): int<-1, 1> given.',
				30,
			],
		]);
	}

	public function testArrayDiffUkey(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/array_diff_ukey.php'], [
			[
				'Parameter #3 $key_comp_func of function array_diff_ukey expects callable(\'a\'|\'b\'|\'c\'|\'d\', \'a\'|\'b\'|\'c\'|\'d\'): int, Closure(int, int): int<-1, 1> given.',
				22,
			],
			[
				'Parameter #3 $key_comp_func of function array_diff_ukey expects callable(0|1|2|3, 0|1|2|3): int, Closure(string, string): int<-1, 1> given.',
				30,
			],
		]);
	}

	public function testArrayIntersectUassoc(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/array_intersect_uassoc.php'], [
			[
				'Parameter #3 $key_compare_func of function array_intersect_uassoc expects callable(\'a\'|\'b\'|\'c\'|\'d\', \'a\'|\'b\'|\'c\'|\'d\'): int, Closure(int, int): int<-1, 1> given.',
				22,
			],
			[
				'Parameter #3 $key_compare_func of function array_intersect_uassoc expects callable(0|1|2|3, 0|1|2|3): int, Closure(string, string): int<-1, 1> given.',
				30,
			],
		]);
	}

	public function testArrayIntersectUkey(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/array_intersect_ukey.php'], [
			[
				'Parameter #3 $key_compare_func of function array_intersect_ukey expects callable(\'a\'|\'b\'|\'c\'|\'d\', \'a\'|\'b\'|\'c\'|\'d\'): int, Closure(int, int): int<-1, 1> given.',
				22,
			],
			[
				'Parameter #3 $key_compare_func of function array_intersect_ukey expects callable(0|1|2|3, 0|1|2|3): int, Closure(string, string): int<-1, 1> given.',
				30,
			],
		]);
	}

	public function testArrayUdiffAssoc(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/array_udiff_assoc.php'], [
			[
				'Parameter #3 $key_comp_func of function array_udiff_assoc expects callable(1|2, 1|2): int, Closure(string, string): int<-1, 1> given.',
				22,
			],
			[
				'Parameter #3 $key_comp_func of function array_udiff_assoc expects callable(1|2|3|4|5, 1|2|3|4|5): int, Closure(string, string): int<-1, 1> given.',
				30,
			],
		]);
	}

	public function testArrayUdiffUasssoc(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/array_udiff_uassoc.php'], [
			[
				'Parameter #3 $data_comp_func of function array_udiff_uassoc expects callable(\'a\'|\'b\'|\'c\'|\'d\', \'a\'|\'b\'|\'c\'|\'d\'): int, Closure(int, int): int<-1, 1> given.',
				28,
			],
			[
				'Parameter #4 $key_comp_func of function array_udiff_uassoc expects callable(\'a\'|\'b\'|\'c\'|\'d\', \'a\'|\'b\'|\'c\'|\'d\'): int, Closure(int, int): int<-1, 1> given.',
				31,
			],
			[
				'Parameter #3 $data_comp_func of function array_udiff_uassoc expects callable(1|2|3|4|5, 1|2|3|4|5): int, Closure(string, string): int<-1, 1> given.',
				39,
			],
			[
				'Parameter #4 $key_comp_func of function array_udiff_uassoc expects callable(0|1|2|3, 0|1|2|3): int, Closure(string, string): int<-1, 1> given.',
				42,
			],
		]);
	}

	public function testArrayUintersectAssoc(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/array_uintersect_assoc.php'], [
			[
				'Parameter #3 $data_compare_func of function array_uintersect_assoc expects callable(\'a\'|\'b\'|\'c\'|\'d\', \'a\'|\'b\'|\'c\'|\'d\'): int, Closure(int, int): int<-1, 1> given.',
				22,
			],
			[
				'Parameter #3 $data_compare_func of function array_uintersect_assoc expects callable(1|2|3|4, 1|2|3|4): int, Closure(string, string): int<-1, 1> given.',
				30,
			],
		]);
	}

	public function testArrayUintersectUassoc(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/array_uintersect_uassoc.php'], [
			[
				'Parameter #3 $data_compare_func of function array_uintersect_uassoc expects callable(\'a\'|\'b\'|\'c\'|\'d\', \'a\'|\'b\'|\'c\'|\'d\'): int, Closure(int, int): int<-1, 1> given.',
				28,
			],
			[
				'Parameter #4 $key_compare_func of function array_uintersect_uassoc expects callable(\'a\'|\'b\'|\'c\'|\'d\', \'a\'|\'b\'|\'c\'|\'d\'): int, Closure(int, int): int<-1, 1> given.',
				31,
			],
			[
				'Parameter #3 $data_compare_func of function array_uintersect_uassoc expects callable(1|2|3|4|5, 1|2|3|4|5): int, Closure(string, string): int<-1, 1> given.',
				39,
			],
			[
				'Parameter #4 $key_compare_func of function array_uintersect_uassoc expects callable(0|1|2|3, 0|1|2|3): int, Closure(string, string): int<-1, 1> given.',
				42,
			],
		]);
	}

	public function testArrayUintersect(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/array_uintersect.php'], [
			[
				'Parameter #3 $data_compare_func of function array_uintersect expects callable(\'a\'|\'b\'|\'c\'|\'d\', \'a\'|\'b\'|\'c\'|\'d\'): int, Closure(int, int): int<-1, 1> given.',
				22,
			],
			[
				'Parameter #3 $data_compare_func of function array_uintersect expects callable(1|2|3|4, 1|2|3|4): int, Closure(string, string): int<-1, 1> given.',
				30,
			],
		]);
	}

	public function testBug7707(): void
	{
		$this->checkExplicitMixed = true;

		$this->analyse([__DIR__ . '/data/bug-7707.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testNoNamedArguments(): void
	{
		$this->analyse([__DIR__ . '/data/no-named-arguments.php'], [
			[
				'Function NoNamedArgumentsFunction\\foo invoked with named argument $i, but it\'s not allowed because of @no-named-arguments.',
				14,
			],
			[
				'Function NoNamedArgumentsFunction\foo invoked with unpacked array with string key, but it\'s not allowed because of @no-named-arguments.',
				24,
			],
			[
				'Function NoNamedArgumentsFunction\foo invoked with unpacked array with possibly string key, but it\'s not allowed because of @no-named-arguments.',
				25,
			],
			[
				'Function NoNamedArgumentsFunction\\foo invoked with named argument $i, but it\'s not allowed because of @no-named-arguments.',
				29,
			],
		]);
	}

	public function testBug11056(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-11056.php'], []);
	}

	public function testBug11506(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11506.php'], []);
	}

	public function testBug11559(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11559.php'], []);
	}

	public function testBug10499(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10499.php'], []);
	}

	public function testBug11559b(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11559b.php'], [
			[
				'Function Bug11559b\maybe_variadic_fn invoked with 5 parameters, 0 required.',
				14,
			],
			[
				'Function Bug11559b\maybe_variadic_fn4 invoked with 2 parameters, 0 required.',
				65,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug9224(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9224.php'], []);
	}

	public function testBug7082(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7082.php'], [
			[
				'Parameter #1 $val of function Bug7082\takesStr expects string, mixed given.',
				11,
			],
		]);
	}

	public function testBug11759(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11759.php'], []);
	}

	public function testBug12051(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-12051.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug8046(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8046.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug11942(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11942.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug11418(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11418.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug9167(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9167.php'], []);
	}

	public function testBug3107(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3107.php'], []);
	}

	public function testBug12676(): void
	{
		$errors = [
			[
				'Parameter #1 $array is passed by reference so it does not accept @readonly property Bug12676\A::$a.',
				15,
			],
			[
				'Parameter #1 $array is passed by reference so it does not accept @readonly property Bug12676\B::$readonlyArr.',
				25,
			],
			[
				'Parameter #1 $array is passed by reference so it does not accept static @readonly property Bug12676\C::$readonlyArr.',
				35,
			],
		];

		if (PHP_VERSION_ID < 80000) {
			$errors = [
				[
					'Parameter #1 $array_arg is passed by reference so it does not accept @readonly property Bug12676\A::$a.',
					15,
				],
				[
					'Parameter #1 $array_arg is passed by reference so it does not accept @readonly property Bug12676\B::$readonlyArr.',
					25,
				],
				[
					'Parameter #1 $array_arg is passed by reference so it does not accept static @readonly property Bug12676\C::$readonlyArr.',
					35,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/bug-12676.php'], $errors);
	}

	public function testBug12499(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12499.php'], []);
	}

	public function testBug7522(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7522.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug12847(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;

		$this->analyse([__DIR__ . '/data/bug-12847.php'], [
			[
				'Parameter #1 $array of function Bug12847\doSomething expects non-empty-array<mixed>, mixed given.',
				32,
				'mixed is empty.',
			],
			[
				'Parameter #1 $array of function Bug12847\doSomething expects non-empty-array<mixed>, mixed given.',
				39,
				'mixed is empty.',
			],
			[
				'Parameter #1 $array of function Bug12847\doSomethingWithInt expects non-empty-array<int>, non-empty-array given.',
				61,
			],
			[
				'Parameter #1 $array of function Bug12847\doSomethingWithInt expects non-empty-array<int>, non-empty-array given.',
				67,
			],
		]);
	}

	public function testBug12954(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-12954.php'], []);
	}

	public function testBug8922(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80000) {
			$errors[] = [
				'Parameter #1 $encoding_list of function mb_detect_order expects non-empty-list<non-falsy-string>|non-falsy-string, null given.',
				15,
				'• Type #1 from the union: null is not a list.
• Type #1 from the union: null is empty.',
			];
		}

		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-8922.php'], $errors);
	}

	public function testBug10020(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10020.php'], []);
	}

	public function testBug8506(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8506.php'], []);
	}

	public function testBug7772(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80000) {
			$errors[] = [
				'Parameter #2 $path of function session_set_cookie_params expects string, string|null given.',
				12,
			];
			$errors[] = [
				'Parameter #3 $domain of function session_set_cookie_params expects string, string|null given.',
				12,
			];
			$errors[] = [
				'Parameter #4 $secure of function session_set_cookie_params expects bool, bool|null given.',
				12,
			];
			$errors[] = [
				'Parameter #5 $httponly of function session_set_cookie_params expects bool, bool|null given.',
				12,
			];
		}

		$this->analyse([__DIR__ . '/data/bug-7772.php'], $errors);
	}

	public function testBug13065(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80000) {
			$errors[] = [
				'Parameter #1 $varname of function getenv expects string, null given.',
				10,
			];
		}

		$this->analyse([__DIR__ . '/data/bug-13065.php'], $errors);
	}

	public function testBug3506(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-3506.php'], []);
	}

	public function testBug5760(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$param1Name = '$glue';
			$param2Name = '$pieces';
		} else {
			$param1Name = '$separator';
			$param2Name = '$array';
		}

		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5760.php'], [
			[
				sprintf('Parameter #2 %s of function join expects array, list<int>|null given.', $param2Name),
				10,
			],
			[
				sprintf('Parameter #1 %s of function join expects array, list<int>|null given.', $param1Name),
				11,
			],
			[
				sprintf('Parameter #2 %s of function implode expects array, list<int>|null given.', $param2Name),
				13,
			],
			[
				sprintf('Parameter #1 %s of function implode expects array, list<int>|null given.', $param1Name),
				14,
			],
			[
				sprintf('Parameter #2 %s of function join expects array, array<string>|string given.', $param2Name),
				22,
			],
			[
				sprintf('Parameter #1 %s of function join expects array, array<string>|string given.', $param1Name),
				23,
			],
			[
				sprintf('Parameter #2 %s of function implode expects array, array<string>|string given.', $param2Name),
				25,
			],
			[
				sprintf('Parameter #1 %s of function implode expects array, array<string>|string given.', $param1Name),
				26,
			],
		]);
	}

	public function testBug9970(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9970.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug12317(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-12317.php'], [
			[
				'Parameter #1 $callback of function array_map expects (callable(Bug12317\Uuid): mixed)|null, Closure(string): string given.',
				28,
			],
			[
				'Parameter $callback of function array_map expects (callable(Bug12317\Uuid): mixed)|null, Closure(string): string given.',
				29,
			],
		]);
	}

	public function testBug13197(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-13197.php'], []);
	}

	public function testBug13434(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13434.php'], []);
	}

	public function testBug11863(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11863.php'], []);
	}

	public function testBug13784(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13784.php'], []);
	}

	public function testBug13556(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-13556.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testArrayRand(): void
	{
		$this->analyse([__DIR__ . '/data/array_rand.php'], [
			[
				'Parameter #1 $input of function array_rand expects non-empty-array, array{} given.',
				7,
				'array{} is empty.',
			],
			[
				'Parameter #1 $input of function array_rand expects non-empty-array, array{} given.',
				8,
				'array{} is empty.',
			],
			[
				'Parameter #2 $num_req of function array_rand expects int<1, max>, int given.',
				8,
			],
			[
				'Parameter #2 $num_req of function array_rand expects int<1, max>, -5 given.',
				13,
			],
			[
				'Parameter #2 $num_req of function array_rand expects int<1, max>, 0 given.',
				14,
			],
		]);
	}

	#[RequiresPhp('< 8.0')]
	public function testArrayRandPhp7(): void
	{
		$this->analyse([__DIR__ . '/data/array_rand.php'], []);
	}

	#[RequiresPhp('>= 8.5')]
	public function testPipeOperator(): void
	{
		$this->analyse([__DIR__ . '/data/func-call-pipe.php'], [
			[
				'Function FuncCallPipe\doFoo invoked with 1 parameter, 2 required.',
				20,
			],
			[
				'Parameter #1 $i of function FuncCallPipe\doBar expects int, string given.',
				24,
			],
			[
				'Parameter #1 $i of function FuncCallPipe\doBar expects int, null given.',
				26,
			],
			[
				'Function FuncCallPipe\doFoo invoked with 1 parameter, 2 required.',
				26,
			],
			[
				'Result of function FuncCallPipe\doFoo (void) is used.',
				26,
			],
			[
				'Result of function FuncCallPipe\doBar (void) is used.',
				28,
			],
			[
				'Parameter #1 $separator of function implode expects array, string given.',
				38,
			],
			[
				'Parameter #1 $separator of function implode expects array, string given.',
				40,
			],
		]);
	}

	#[RequiresPhp('>= 8.5')]
	public function testClone(): void
	{
		$this->analyse([__DIR__ . '/data/clone-function.php'], [
			[
				'Parameter #2 $withProperties of function clone expects array, int given.',
				12,
			],
			[
				'Function clone invoked with 3 parameters, 1-2 required.',
				13,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug13862(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13862.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug13862b(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13862b.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug13862c(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13862c.php'], [
			[
				'Parameter #2 $options of function curl_setopt_array expects array{10022?: non-empty-string}, array{}|array{10022: 123} given.',
				12,
				'Offset 10022 (non-empty-string) does not accept type 123.',
			],
		]);
	}

}
