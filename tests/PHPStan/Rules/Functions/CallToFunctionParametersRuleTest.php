<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\RuleLevelHelper;
use const PHP_VERSION_ID;

/**
 * @extends \PHPStan\Testing\RuleTestCase<CallToFunctionParametersRule>
 */
class CallToFunctionParametersRuleTest extends \PHPStan\Testing\RuleTestCase
{

	/** @var bool */
	private $checkExplicitMixed = false;

	protected function getRule(): \PHPStan\Rules\Rule
	{
		$broker = $this->createReflectionProvider();
		return new CallToFunctionParametersRule(
			$broker,
			new FunctionCallParametersCheck(new RuleLevelHelper($broker, true, false, true, $this->checkExplicitMixed), new NullsafeCheck(), new PhpVersion(80000), new UnresolvableTypeHelper(true), true, true, true, true, true)
		);
	}

	public function testCallToFunctionWithoutParameters(): void
	{
		require_once __DIR__ . '/data/existing-function-definition.php';
		$this->analyse([__DIR__ . '/data/existing-function.php'], []);
	}

	public function testCallToFunctionWithIncorrectParameters(): void
	{
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
				'Parameter #1 $callback of function set_error_handler expects (callable(int, string, string, int, array): bool)|null, Closure(mixed, mixed, mixed, mixed): void given.',
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

	/**
	 * @requires PHP 7.1.1
	 */
	public function testUnpackOnAfter711(): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 70101) {
			$this->markTestSkipped('This test requires PHP >= 7.1.1');
		}
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
		]);
	}

	public function testImplodeOnPhp74(): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

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
		if (PHP_VERSION_ID < 70400) {
			$errors = [];
		}
		if (PHP_VERSION_ID >= 80000) {
			$errors = [
				[
					'Parameter #2 $array of function implode expects array|null, string given.',
					8,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/implode-74.php'], $errors);
	}

	public function testImplodeOnLessThanPhp74(): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID >= 70400) {
			$this->markTestSkipped('Test skipped on 7.4.');
		}

		$errors = [];
		if (PHP_VERSION_ID >= 80000) {
			$errors = [
				[
					'Parameter #2 $array of function implode expects array|null, string given.',
					8,
				],
			];
		} elseif (PHP_VERSION_ID >= 70400) {
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


	public function testPutCsvWithStringable(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test skipped on lower version than 8.0 (needs Stringable interface, added in PHP8)');
		}

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

	public function testNamedArguments(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

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
		];
		if (PHP_VERSION_ID < 80000) {
			$errors[] = [
				'Missing parameter $arr1 (array) in call to function array_merge.',
				14,
			];
		}

		require_once __DIR__ . '/data/named-arguments-define.php';
		$this->analyse([__DIR__ . '/data/named-arguments.php'], $errors);
	}

	public function testBug4514(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4514.php'], []);
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

	public function testBug3920(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3920.php'], []);
	}

	public function testBugNumberFormatNamedArguments(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->analyse([__DIR__ . '/data/number-format-named-arguments.php'], []);
	}

	public function testArrayReduceCallback(): void
	{
		$this->analyse([__DIR__ . '/data/array_reduce.php'], [
			[
				'Parameter #2 $callback of function array_reduce expects callable(string, int): string, Closure(string, string): string given.',
				5,
			],
			[
				'Parameter #2 $callback of function array_reduce expects callable(string|null, int): string|null, Closure(string, int): non-empty-string given.',
				13,
			],
			[
				'Parameter #2 $callback of function array_reduce expects callable(string|null, int): string|null, Closure(string, int): non-empty-string given.',
				22,
			],
		]);
	}

	public function testArrayReduceArrowFunctionCallback(): void
	{
		if (PHP_VERSION_ID < 70400 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
		$this->analyse([__DIR__ . '/data/array_reduce_arrow.php'], [
			[
				'Parameter #2 $callback of function array_reduce expects callable(string, int): string, Closure(string, string): string given.',
				5,
			],
			[
				'Parameter #2 $callback of function array_reduce expects callable(string|null, int): string|null, Closure(string, int): non-empty-string given.',
				11,
			],
			[
				'Parameter #2 $callback of function array_reduce expects callable(string|null, int): string|null, Closure(string, int): non-empty-string given.',
				18,
			],
		]);
	}

	public function testArrayWalkCallback(): void
	{
		$this->analyse([__DIR__ . '/data/array_walk.php'], [
			[
				'Parameter #2 $callback of function array_walk expects callable(int, string, mixed): mixed, Closure(stdClass, float): \'\' given.',
				6,
			],
			[
				'Parameter #2 $callback of function array_walk expects callable(int, string, string): mixed, Closure(int, string, int): \'\' given.',
				14,
			],
		]);
	}

	public function testArrayWalkArrowFunctionCallback(): void
	{
		if (PHP_VERSION_ID < 70400 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
		$this->analyse([__DIR__ . '/data/array_walk_arrow.php'], [
			[
				'Parameter #2 $callback of function array_walk expects callable(int, string, mixed): mixed, Closure(stdClass, float): \'\' given.',
				6,
			],
			[
				'Parameter #2 $callback of function array_walk expects callable(int, string, string): mixed, Closure(int, string, int): \'\' given.',
				12,
			],
		]);
	}

	public function testUasortCallback(): void
	{
		$paramTwoName = PHP_VERSION_ID >= 80000
			? 'callback'
			: 'cmp_function';

		$this->analyse([__DIR__ . '/data/uasort.php'], [
			[
				sprintf(
					'Parameter #2 $%s of function uasort expects callable(int, int): int, Closure(string, string): 1 given.',
					$paramTwoName
				),
				7,
			],
		]);
	}

	public function testUasortArrowFunctionCallback(): void
	{
		if (PHP_VERSION_ID < 70400 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
		$paramTwoName = PHP_VERSION_ID >= 80000
			? 'callback'
			: 'cmp_function';

		$this->analyse([__DIR__ . '/data/uasort_arrow.php'], [
			[
				sprintf(
					'Parameter #2 $%s of function uasort expects callable(int, int): int, Closure(string, string): 1 given.',
					$paramTwoName
				),
				7,
			],
		]);
	}

	public function testUsortCallback(): void
	{
		$paramTwoName = PHP_VERSION_ID >= 80000
			? 'callback'
			: 'cmp_function';

		$this->analyse([__DIR__ . '/data/usort.php'], [
			[
				sprintf(
					'Parameter #2 $%s of function usort expects callable(int, int): int, Closure(string, string): 1 given.',
					$paramTwoName
				),
				14,
			],
		]);
	}

	public function testUsortArrowFunctionCallback(): void
	{
		if (PHP_VERSION_ID < 70400 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$paramTwoName = PHP_VERSION_ID >= 80000
			? 'callback'
			: 'cmp_function';

		$this->analyse([__DIR__ . '/data/usort_arrow.php'], [
			[
				sprintf(
					'Parameter #2 $%s of function usort expects callable(int, int): int, Closure(string, string): 1 given.',
					$paramTwoName
				),
				14,
			],
		]);
	}

	public function testUksortCallback(): void
	{
		$paramTwoName = PHP_VERSION_ID >= 80000
			? 'callback'
			: 'cmp_function';

		$this->analyse([__DIR__ . '/data/uksort.php'], [
			[
				sprintf(
					'Parameter #2 $%s of function uksort expects callable(string, string): int, Closure(stdClass, stdClass): 1 given.',
					$paramTwoName
				),
				14,
			],
			[
				sprintf('Parameter #2 $%s of function uksort expects callable(int, int): int, Closure(string, string): 1 given.', $paramTwoName),
				50,
			],
		]);
	}

	public function testUksortArrowFunctionCallback(): void
	{
		if (PHP_VERSION_ID < 70400 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$paramTwoName = PHP_VERSION_ID >= 80000
			? 'callback'
			: 'cmp_function';

		$this->analyse([__DIR__ . '/data/uksort_arrow.php'], [
			[
				sprintf(
					'Parameter #2 $%s of function uksort expects callable(string, string): int, Closure(stdClass, stdClass): 1 given.',
					$paramTwoName
				),
				14,
			],
			[
				sprintf('Parameter #2 $%s of function uksort expects callable(int, int): int, Closure(string, string): 1 given.', $paramTwoName),
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
		if (PHP_VERSION_ID < 70400 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

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

	public function testExplode(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

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
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->analyse([__DIR__ . '/data/proc_open.php'], [
			[
				'Parameter #1 $command of function proc_open expects array<int, string>|string, array<string, string> given.',
				6,
			],
		]);
	}

	public function testBug5609(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5609.php'], []);
	}

}
