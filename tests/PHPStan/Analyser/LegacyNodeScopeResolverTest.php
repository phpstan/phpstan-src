<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Expr\Exit_;
use PHPStan\Node\Printer\Printer;
use PHPStan\Node\VirtualNode;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use SomeNodeScopeResolverNamespace\Foo;
use function define;
use function function_exists;
use function sprintf;
use function str_replace;

class LegacyNodeScopeResolverTest extends TypeInferenceTestCase
{

	/** @var Scope[][] */
	private static array $assertTypesCache = [];

	public function testClassMethodScope(): void
	{
		self::processFile(__DIR__ . '/data/class.php', function (Node $node, Scope $scope): void {
			if (!($node instanceof Exit_)) {
				return;
			}

			$this->assertSame('SomeNodeScopeResolverNamespace', $scope->getNamespace());
			$this->assertTrue($scope->isInClass());
			$this->assertSame(Foo::class, $scope->getClassReflection()->getName());
			$this->assertSame('doFoo', $scope->getFunctionName());
			$this->assertSame('$this(SomeNodeScopeResolverNamespace\Foo)', $scope->getVariableType('this')->describe(VerbosityLevel::precise()));
			$this->assertTrue($scope->hasVariableType('baz')->yes());
			$this->assertTrue($scope->hasVariableType('lorem')->yes());
			$this->assertFalse($scope->hasVariableType('ipsum')->yes());
			$this->assertTrue($scope->hasVariableType('i')->yes());
			$this->assertTrue($scope->hasVariableType('val')->yes());
			$this->assertSame('SomeNodeScopeResolverNamespace\InvalidArgumentException', $scope->getVariableType('exception')->describe(VerbosityLevel::precise()));
			$this->assertTrue($scope->hasVariableType('staticVariable')->yes());
			$this->assertSame($scope->getVariableType('staticVariable')->describe(VerbosityLevel::precise()), 'mixed');
			$this->assertTrue($scope->hasVariableType('staticVariableWithPhpDocType')->yes());
			$this->assertSame($scope->getVariableType('staticVariableWithPhpDocType')->describe(VerbosityLevel::precise()), 'string');
			$this->assertTrue($scope->hasVariableType('staticVariableWithPhpDocType2')->yes());
			$this->assertSame($scope->getVariableType('staticVariableWithPhpDocType2')->describe(VerbosityLevel::precise()), 'int');
			$this->assertTrue($scope->hasVariableType('staticVariableWithPhpDocType3')->yes());
			$this->assertSame($scope->getVariableType('staticVariableWithPhpDocType3')->describe(VerbosityLevel::precise()), 'float');
		});
	}

	private static function getFileScope(string $filename): Scope
	{
		$testScope = null;
		self::processFile($filename, static function (Node $node, Scope $scope) use (&$testScope): void {
			if (!($node instanceof Exit_)) {
				return;
			}

			$testScope = $scope;
		});

		/** @var Scope */
		return $testScope;
	}

	public static function dataAssignInIf(): array
	{
		$testScope = self::getFileScope(__DIR__ . '/data/if.php');

		return [
			[
				$testScope,
				'nonexistentVariable',
				TrinaryLogic::createNo(),
			],
			[
				$testScope,
				'foo',
				TrinaryLogic::createMaybe(),
				'bool', // mixed?
			],
			[
				$testScope,
				'lorem',
				TrinaryLogic::createYes(),
				'1',
			],
			[
				$testScope,
				'callParameter',
				TrinaryLogic::createYes(),
				'3',
			],
			[
				$testScope,
				'arrOne',
				TrinaryLogic::createYes(),
				'array{\'one\'}',
			],
			[
				$testScope,
				'arrTwo',
				TrinaryLogic::createYes(),
				'array{test: \'two\', 0: Foo}',
			],
			[
				$testScope,
				'arrThree',
				TrinaryLogic::createYes(),
				'array{\'three\'}',
			],
			[
				$testScope,
				'inArray',
				TrinaryLogic::createYes(),
				'1',
			],
			[
				$testScope,
				'i',
				TrinaryLogic::createYes(),
				'int<0, 4>',
			],
			[
				$testScope,
				'f',
				TrinaryLogic::createMaybe(),
				'int<1, max>',
			],
			[
				$testScope,
				'anotherF',
				TrinaryLogic::createYes(),
				'int<1, max>',
			],
			[
				$testScope,
				'matches',
				TrinaryLogic::createYes(),
				'array{0?: string}',
			],
			[
				$testScope,
				'anotherArray',
				TrinaryLogic::createYes(),
				'array{test: array{\'another\'}}',
			],
			[
				$testScope,
				'ifVar',
				TrinaryLogic::createYes(),
				'1|2|3',
			],
			[
				$testScope,
				'ifNotVar',
				TrinaryLogic::createMaybe(),
				'1|2',
			],
			[
				$testScope,
				'ifNestedVar',
				TrinaryLogic::createYes(),
				'1|2|3',
			],
			[
				$testScope,
				'ifNotNestedVar',
				TrinaryLogic::createMaybe(),
				'1|2|3',
			],
			[
				$testScope,
				'variableOnlyInEarlyTerminatingElse',
				TrinaryLogic::createNo(),
			],
			[
				$testScope,
				'matches2',
				TrinaryLogic::createMaybe(),
				'array{0?: string}',
			],
			[
				$testScope,
				'inTry',
				TrinaryLogic::createYes(),
				'1',
			],
			[
				$testScope,
				'matches3',
				TrinaryLogic::createYes(),
				'array{}|array{string}',
			],
			[
				$testScope,
				'matches4',
				TrinaryLogic::createMaybe(),
				'array{}|array{string}',
			],
			[
				$testScope,
				'issetFoo',
				TrinaryLogic::createYes(),
				'Foo',
			],
			[
				$testScope,
				'issetBar',
				TrinaryLogic::createYes(),
				'mixed~null',
			],
			[
				$testScope,
				'issetBaz',
				TrinaryLogic::createYes(),
				'mixed~null',
			],
			[
				$testScope,
				'doWhileVar',
				TrinaryLogic::createYes(),
				'1',
			],
			[
				$testScope,
				'switchVar',
				TrinaryLogic::createYes(),
				'1|2|3|4',
			],
			[
				$testScope,
				'noSwitchVar',
				TrinaryLogic::createMaybe(),
				'1',
			],
			[
				$testScope,
				'anotherNoSwitchVar',
				TrinaryLogic::createMaybe(),
				'1',
			],
			[
				$testScope,
				'inTryTwo',
				TrinaryLogic::createYes(),
				'1',
			],
			[
				$testScope,
				'ternaryMatches',
				TrinaryLogic::createYes(),
				'array{string}',
			],
			[
				$testScope,
				'previousI',
				TrinaryLogic::createYes(),
				'int<1, max>',
			],
			[
				$testScope,
				'previousJ',
				TrinaryLogic::createYes(),
				'0',
			],
			[
				$testScope,
				'frame',
				TrinaryLogic::createYes(),
				'mixed~null',
			],
			[
				$testScope,
				'listOne',
				TrinaryLogic::createYes(),
				'1',
			],
			[
				$testScope,
				'listTwo',
				TrinaryLogic::createYes(),
				'2',
			],
			[
				$testScope,
				'e',
				TrinaryLogic::createYes(),
				'Exception',
			],
			[
				$testScope,
				'exception',
				TrinaryLogic::createYes(),
				'Exception',
			],
			[
				$testScope,
				'inTryNotInCatch',
				TrinaryLogic::createMaybe(),
				'1',
			],
			[
				$testScope,
				'fooObjectFromTryCatch',
				TrinaryLogic::createYes(),
				'InTryCatchFoo',
			],
			[
				$testScope,
				'mixedVarFromTryCatch',
				TrinaryLogic::createYes(),
				'1|1.0',
			],
			[
				$testScope,
				'nullableIntegerFromTryCatch',
				TrinaryLogic::createYes(),
				'1|null',
			],
			[
				$testScope,
				'anotherNullableIntegerFromTryCatch',
				TrinaryLogic::createYes(),
				'1|null',
			],
			[
				$testScope,
				'nullableIntegers',
				TrinaryLogic::createYes(),
				'array{1, 2, 3, null}',
			],
			[
				$testScope,
				'union',
				TrinaryLogic::createYes(),
				'array{1, 2, 3, \'foo\'}',
				'1|2|3|\'foo\'',
			],
			[
				$testScope,
				'trueOrFalse',
				TrinaryLogic::createYes(),
				'bool',
			],
			[
				$testScope,
				'falseOrTrue',
				TrinaryLogic::createYes(),
				'bool',
			],
			[
				$testScope,
				'true',
				TrinaryLogic::createYes(),
				'true',
			],
			[
				$testScope,
				'false',
				TrinaryLogic::createYes(),
				'false',
			],
			[
				$testScope,
				'trueOrFalseFromSwitch',
				TrinaryLogic::createYes(),
				'bool',
			],
			[
				$testScope,
				'trueOrFalseInSwitchWithDefault',
				TrinaryLogic::createYes(),
				'bool',
			],
			[
				$testScope,
				'trueOrFalseInSwitchInAllCases',
				TrinaryLogic::createYes(),
				'bool',
			],
			[
				$testScope,
				'trueOrFalseInSwitchInAllCasesWithDefault',
				TrinaryLogic::createYes(),
				'bool',
			],
			[
				$testScope,
				'trueOrFalseInSwitchInAllCasesWithDefaultCase',
				TrinaryLogic::createYes(),
				'true',
			],
			[
				$testScope,
				'variableDefinedInSwitchWithOtherCasesWithEarlyTermination',
				TrinaryLogic::createYes(),
				'true',
			],
			[
				$testScope,
				'anotherVariableDefinedInSwitchWithOtherCasesWithEarlyTermination',
				TrinaryLogic::createYes(),
				'true',
			],
			[
				$testScope,
				'variableDefinedOnlyInEarlyTerminatingSwitchCases',
				TrinaryLogic::createNo(),
			],
			[
				$testScope,
				'nullableTrueOrFalse',
				TrinaryLogic::createYes(),
				'bool|null',
			],
			[
				$testScope,
				'nonexistentVariableOutsideFor',
				TrinaryLogic::createYes(),
				'1',
			],
			[
				$testScope,
				'integerOrNullFromFor',
				TrinaryLogic::createYes(),
				'1',
			],
			[
				$testScope,
				'nonexistentVariableOutsideWhile',
				TrinaryLogic::createMaybe(),
				'1',
			],
			[
				$testScope,
				'integerOrNullFromWhile',
				TrinaryLogic::createYes(),
				'1|null',
			],
			[
				$testScope,
				'nonexistentVariableOutsideForeach',
				TrinaryLogic::createMaybe(),
				'null',
			],
			[
				$testScope,
				'integerOrNullFromForeach',
				TrinaryLogic::createYes(),
				'1|null',
			],
			[
				$testScope,
				'notNullableString',
				TrinaryLogic::createYes(),
				'string',
			],
			[
				$testScope,
				'anotherNotNullableString',
				TrinaryLogic::createYes(),
				'string',
			],
			[
				$testScope,
				'notNullableObject',
				TrinaryLogic::createYes(),
				'Foo',
			],
			[
				$testScope,
				'nullableString',
				TrinaryLogic::createYes(),
				'string|null',
			],
			[
				$testScope,
				'alsoNotNullableString',
				TrinaryLogic::createYes(),
				'string',
			],
			[
				$testScope,
				'integerOrString',
				TrinaryLogic::createYes(),
				'\'str\'|int',
			],
			[
				$testScope,
				'nullableIntegerAfterNeverCondition',
				TrinaryLogic::createYes(),
				'int|null',
			],
			[
				$testScope,
				'stillNullableInteger',
				TrinaryLogic::createYes(),
				'2|null',
			],
			[
				$testScope,
				'arrayOfIntegers',
				TrinaryLogic::createYes(),
				'array{1, 2, 3}',
			],
			[
				$testScope,
				'arrayAccessObject',
				TrinaryLogic::createYes(),
				\ObjectWithArrayAccess\Foo::class,
			],
			[
				$testScope,
				'width',
				TrinaryLogic::createYes(),
				'2.0',
			],
			[
				$testScope,
				'someVariableThatWillGetOverrideInFinally',
				TrinaryLogic::createYes(),
				'\'foo\'',
			],
			[
				$testScope,
				'maybeDefinedButLaterCertainlyDefined',
				TrinaryLogic::createYes(),
				'2|3',
			],
			[
				$testScope,
				'mixed',
				TrinaryLogic::createYes(),
				'mixed~bool',
			],
			[
				$testScope,
				'variableDefinedInSwitchWithoutEarlyTermination',
				TrinaryLogic::createMaybe(),
				'false',
			],
			[
				$testScope,
				'anotherVariableDefinedInSwitchWithoutEarlyTermination',
				TrinaryLogic::createMaybe(),
				'bool',
			],
			[
				$testScope,
				'alwaysDefinedFromSwitch',
				TrinaryLogic::createYes(),
				'1|null',
			],
			[
				$testScope,
				'exceptionFromTryCatch',
				TrinaryLogic::createYes(),
				'(AnotherException&Throwable)|(Throwable&YetAnotherException)|null',
			],
			[
				$testScope,
				'nullOverwrittenInSwitchToOne',
				TrinaryLogic::createYes(),
				'1',
			],
			[
				$testScope,
				'variableFromSwitchShouldBeBool',
				TrinaryLogic::createYes(),
				'bool',
			],
		];
	}

	#[DataProvider('dataAssignInIf')]
	public function testAssignInIf(
		Scope $scope,
		string $variableName,
		TrinaryLogic $expectedCertainty,
		?string $typeDescription = null,
		?string $iterableValueTypeDescription = null,
	): void
	{
		$this->assertVariables(
			$scope,
			$variableName,
			$expectedCertainty,
			$typeDescription,
			$iterableValueTypeDescription,
		);
	}

	public static function dataConstantTypes(): array
	{
		$testScope = self::getFileScope(__DIR__ . '/data/constantTypes.php');

		return [
			[
				$testScope,
				'postIncrement',
				'2',
			],
			[
				$testScope,
				'postDecrement',
				'4',
			],
			[
				$testScope,
				'preIncrement',
				'2',
			],
			[
				$testScope,
				'preDecrement',
				'4',
			],
			[
				$testScope,
				'literalArray',
				'array{a: 2, b: 4, c: 2, d: 4}',
			],
			[
				$testScope,
				'nullIncremented',
				'1',
			],
			[
				$testScope,
				'nullDecremented',
				'null',
			],
			[
				$testScope,
				'incrementInIf',
				'1|2|3',
			],
			[
				$testScope,
				'anotherIncrementInIf',
				'2|3',
			],
			[
				$testScope,
				'valueOverwrittenInIf',
				'1|2',
			],
			[
				$testScope,
				'incrementInForLoop',
				'int<2, max>',
			],
			[
				$testScope,
				'valueOverwrittenInForLoop',
				'2',
			],
			[
				$testScope,
				'arrayOverwrittenInForLoop',
				'array{a: int<2, max>, b: \'bar\'}',
			],
			[
				$testScope,
				'anotherValueOverwrittenInIf',
				'5|10',
			],
			[
				$testScope,
				'intProperty',
				'int<2, max>',
			],
			[
				$testScope,
				'staticIntProperty',
				'int<2, max>',
			],
			[
				$testScope,
				'anotherIntProperty',
				'1|2',
			],
			[
				$testScope,
				'anotherStaticIntProperty',
				'1|2',
			],
			[
				$testScope,
				'variableIncrementedInClosurePassedByReference',
				'int<0, max>',
			],
			[
				$testScope,
				'anotherVariableIncrementedInClosure',
				'0',
			],
			[
				$testScope,
				'yetAnotherVariableInClosurePassedByReference',
				'0|1',
			],
			[
				$testScope,
				'variableIncrementedInFinally',
				'1',
			],
		];
	}

	#[DataProvider('dataConstantTypes')]
	public function testConstantTypes(
		Scope $scope,
		string $variableName,
		string $typeDescription,
	): void
	{
		$this->assertVariables(
			$scope,
			$variableName,
			TrinaryLogic::createYes(),
			$typeDescription,
			null,
		);
	}

	private function assertVariables(
		Scope $scope,
		string $variableName,
		TrinaryLogic $expectedCertainty,
		?string $typeDescription = null,
		?string $iterableValueTypeDescription = null,
	): void
	{
		$certainty = $scope->hasVariableType($variableName);
		$this->assertTrue(
			$expectedCertainty->equals($certainty),
			sprintf(
				'Certainty of %s is %s, expected %s',
				$variableName,
				$certainty->describe(),
				$expectedCertainty->describe(),
			),
		);
		if (!$expectedCertainty->no()) {
			if ($typeDescription === null) {
				$this->fail(sprintf('Missing expected type for defined variable $%s.', $variableName));
			}

			$this->assertSame(
				$typeDescription,
				$scope->getVariableType($variableName)->describe(VerbosityLevel::precise()),
				sprintf('Type of variable $%s does not match the expected one.', $variableName),
			);

			if ($iterableValueTypeDescription !== null) {
				$this->assertSame(
					$iterableValueTypeDescription,
					$scope->getVariableType($variableName)->getIterableValueType()->describe(VerbosityLevel::precise()),
					sprintf('Iterable value type of variable $%s does not match the expected one.', $variableName),
				);
			}
		} elseif ($typeDescription !== null) {
			$this->fail(
				sprintf(
					'No type should be asserted for an undefined variable $%s, %s given.',
					$variableName,
					$typeDescription,
				),
			);
		}
	}

	public static function dataLiteralArraysKeys(): array
	{
		define('STRING_ONE', '1');
		define('INT_ONE', 1);
		define('STRING_FOO', 'foo');

		return [
			[
				'0|1|2',
				"'NoKeysArray'",
			],
			[
				'0|1|2',
				"'IntegersAndNoKeysArray'",
			],
			[
				'0|1|\'foo\'',
				"'StringsAndNoKeysArray'",
			],
			[
				'1|2|3',
				"'IntegersAsStringsAndNoKeysArray'",
			],
			[
				'1|2',
				"'IntegersAsStringsArray'",
			],
			[
				'1|2',
				"'IntegersArray'",
			],
			[
				'1|2|3',
				"'IntegersWithFloatsArray'",
			],
			[
				'\'bar\'|\'foo\'',
				"'StringsArray'",
			],
			[
				'\'\'|\'bar\'|\'baz\'',
				"'StringsWithNullArray'",
			],
			[
				'1|2|string',
				"'IntegersWithStringFromMethodArray'",
			],
			[
				'1|2|\'foo\'',
				"'IntegersAndStringsArray'",
			],
			[
				'0|1',
				"'BooleansArray'",
			],
			[
				'(int|string)',
				"'UnknownConstantArray'",
			],
		];
	}

	#[DataProvider('dataLiteralArraysKeys')]
	public function testLiteralArraysKeys(
		string $description,
		string $evaluatedPointExpressionType,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/literal-arrays-keys.php',
			$description,
			'$key',
			$evaluatedPointExpressionType,
		);
	}

	public static function dataTypeFromFunctionPhpDocs(): array
	{
		return [
			[
				'mixed',
				'$mixedParameter',
			],
			[
				'MethodPhpDocsNamespace\Bar|MethodPhpDocsNamespace\Foo',
				'$unionTypeParameter',
			],
			[
				'int',
				'$anotherMixedParameter',
			],
			[
				'mixed',
				'$yetAnotherMixedParameter',
			],
			[
				'int',
				'$integerParameter',
			],
			[
				'int',
				'$anotherIntegerParameter',
			],
			[
				'array',
				'$arrayParameterOne',
			],
			[
				'array<mixed>',
				'$arrayParameterOther',
			],
			[
				'MethodPhpDocsNamespace\\Lorem',
				'$objectRelative',
			],
			[
				'SomeOtherNamespace\\Ipsum',
				'$objectFullyQualified',
			],
			[
				'SomeNamespace\\Amet',
				'$objectUsed',
			],
			[
				'*ERROR*',
				'$nonexistentParameter',
			],
			[
				'int|null',
				'$nullableInteger',
			],
			[
				'SomeNamespace\Amet|null',
				'$nullableObject',
			],
			[
				'SomeNamespace\Amet|null',
				'$anotherNullableObject',
			],
			[
				'null',
				'$nullType',
			],
			[
				'MethodPhpDocsNamespace\Bar',
				'$barObject->doBar()',
			],
			[
				'MethodPhpDocsNamespace\Bar',
				'$conflictedObject',
			],
			[
				'MethodPhpDocsNamespace\Baz',
				'$moreSpecifiedObject',
			],
			[
				'MethodPhpDocsNamespace\Baz',
				'$moreSpecifiedObject->doFluent()',
			],
			[
				'MethodPhpDocsNamespace\Baz|null',
				'$moreSpecifiedObject->doFluentNullable()',
			],
			[
				'MethodPhpDocsNamespace\Baz',
				'$moreSpecifiedObject->doFluentArray()[0]',
			],
			[
				'iterable<MethodPhpDocsNamespace\Baz>&MethodPhpDocsNamespace\Collection',
				'$moreSpecifiedObject->doFluentUnionIterable()',
			],
			[
				'MethodPhpDocsNamespace\Baz',
				'$fluentUnionIterableBaz',
			],
			[
				'resource',
				'$resource',
			],
			[
				'mixed',
				'$yetAnotherAnotherMixedParameter',
			],
			[
				'mixed',
				'$yetAnotherAnotherAnotherMixedParameter',
			],
			[
				'void',
				'$voidParameter',
			],
			[
				'SomeNamespace\Consecteur',
				'$useWithoutAlias',
			],
			[
				'true',
				'$true',
			],
			[
				'false',
				'$false',
			],
			[
				'true',
				'$boolTrue',
			],
			[
				'false',
				'$boolFalse',
			],
			[
				'bool',
				'$trueBoolean',
			],
			[
				'bool',
				'$parameterWithDefaultValueFalse',
			],
		];
	}

	public static function dataTypeFromMethodPhpDocs(): array
	{
		return [
			[
				'MethodPhpDocsNamespace\\Foo',
				'$selfType',
			],
			[
				'static(MethodPhpDocsNamespace\Foo)',
				'$staticType',
			],
			[
				'MethodPhpDocsNamespace\Foo',
				'$this->doFoo()',
			],
			[
				'MethodPhpDocsNamespace\Bar',
				'static::doSomethingStatic()',
			],
			[
				'static(MethodPhpDocsNamespace\Foo)',
				'parent::doLorem()',
			],
			[
				'MethodPhpDocsNamespace\FooParent',
				'$parent->doLorem()',
				false,
			],
			[
				'static(MethodPhpDocsNamespace\Foo)',
				'$this->doLorem()',
			],
			[
				'MethodPhpDocsNamespace\Foo',
				'$differentInstance->doLorem()',
			],
			[
				'static(MethodPhpDocsNamespace\Foo)',
				'parent::doIpsum()',
			],
			[
				'MethodPhpDocsNamespace\FooParent',
				'$parent->doIpsum()',
				false,
			],
			[
				'MethodPhpDocsNamespace\Foo',
				'$differentInstance->doIpsum()',
			],
			[
				'static(MethodPhpDocsNamespace\Foo)',
				'$this->doIpsum()',
			],
			[
				'MethodPhpDocsNamespace\Foo',
				'$this->doBar()[0]',
			],
			[
				'MethodPhpDocsNamespace\Bar',
				'self::doSomethingStatic()',
			],
			[
				'MethodPhpDocsNamespace\Bar',
				'\MethodPhpDocsNamespace\Foo::doSomethingStatic()',
			],
			[
				'$this(MethodPhpDocsNamespace\Foo)',
				'parent::doThis()',
			],
			[
				'$this(MethodPhpDocsNamespace\Foo)|null',
				'parent::doThisNullable()',
			],
			[
				'$this(MethodPhpDocsNamespace\Foo)|MethodPhpDocsNamespace\Bar|null',
				'parent::doThisUnion()',
			],
			[
				'MethodPhpDocsNamespace\FooParent',
				'$this->returnParent()',
				false,
			],
			[
				'MethodPhpDocsNamespace\FooParent',
				'$this->returnPhpDocParent()',
				false,
			],
			[
				'array<null>',
				'$this->returnNulls()',
			],
			[
				'object',
				'$objectWithoutNativeTypehint',
			],
			[
				'object',
				'$objectWithNativeTypehint',
			],
			[
				'object',
				'$this->returnObject()',
			],
			[
				'MethodPhpDocsNamespace\FooParent',
				'new parent()',
			],
			[
				'MethodPhpDocsNamespace\Foo',
				'$inlineSelf',
			],
			[
				'MethodPhpDocsNamespace\Bar',
				'$inlineBar',
			],
			[
				'MethodPhpDocsNamespace\Foo',
				'$this->phpDocVoidMethod()',
			],
			[
				'MethodPhpDocsNamespace\Foo',
				'$this->phpDocVoidMethodFromInterface()',
			],
			[
				'MethodPhpDocsNamespace\Foo',
				'$this->phpDocVoidParentMethod()',
			],
			[
				'MethodPhpDocsNamespace\Foo',
				'$this->phpDocWithoutCurlyBracesVoidParentMethod()',
			],
			[
				'array<string>',
				'$this->returnsStringArray()',
			],
			[
				'mixed',
				'$this->privateMethodWithPhpDoc()',
			],
		];
	}

	#[DataProvider('dataTypeFromFunctionPhpDocs')]
	#[DataProvider('dataTypeFromMethodPhpDocs')]
	public function testTypeFromMethodPhpDocsPsalmPrefix(
		string $description,
		string $expression,
		bool $replaceClass = true,
	): void
	{
		$description = str_replace('static(MethodPhpDocsNamespace\Foo)', 'static(MethodPhpDocsNamespace\FooPsalmPrefix)', $description);

		if ($replaceClass && $expression !== '$this->doFoo()') {
			$description = str_replace('$this(MethodPhpDocsNamespace\Foo)', '$this(MethodPhpDocsNamespace\FooPsalmPrefix)', $description);
			if ($description === 'MethodPhpDocsNamespace\Foo') {
				$description = 'MethodPhpDocsNamespace\FooPsalmPrefix';
			}
		}
		$this->assertTypes(
			__DIR__ . '/data/methodPhpDocs-psalmPrefix.php',
			$description,
			$expression,
		);
	}

	/**
	 * @param bool $replaceClass = true
	 */
	#[DataProvider('dataTypeFromFunctionPhpDocs')]
	#[DataProvider('dataTypeFromMethodPhpDocs')]
	public function testTypeFromMethodPhpDocsPhpstanPrefix(
		string $description,
		string $expression,
		bool $replaceClass = true,
	): void
	{
		$description = str_replace('static(MethodPhpDocsNamespace\Foo)', 'static(MethodPhpDocsNamespace\FooPhpstanPrefix)', $description);

		if ($replaceClass && $expression !== '$this->doFoo()') {
			$description = str_replace('$this(MethodPhpDocsNamespace\Foo)', '$this(MethodPhpDocsNamespace\FooPhpstanPrefix)', $description);
			if ($description === 'MethodPhpDocsNamespace\Foo') {
				$description = 'MethodPhpDocsNamespace\FooPhpstanPrefix';
			}
		}
		$this->assertTypes(
			__DIR__ . '/data/methodPhpDocs-phpstanPrefix.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataTypeFromFunctionPhpDocs')]
	#[DataProvider('dataTypeFromMethodPhpDocs')]
	public function testTypeFromMethodPhpDocsPhanPrefix(
		string $description,
		string $expression,
		bool $replaceClass = true,
	): void
	{
		$description = str_replace('static(MethodPhpDocsNamespace\Foo)', 'static(MethodPhpDocsNamespace\FooPhanPrefix)', $description);

		if ($replaceClass && $expression !== '$this->doFoo()') {
			$description = str_replace('$this(MethodPhpDocsNamespace\Foo)', '$this(MethodPhpDocsNamespace\FooPhanPrefix)', $description);
			if ($description === 'MethodPhpDocsNamespace\Foo') {
				$description = 'MethodPhpDocsNamespace\FooPhanPrefix';
			}
		}
		$this->assertTypes(
			__DIR__ . '/data/methodPhpDocs-phanPrefix.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataTypeFromFunctionPhpDocs')]
	#[DataProvider('dataTypeFromMethodPhpDocs')]
	public function testTypeFromTraitPhpDocs(
		string $description,
		string $expression,
		bool $replaceClass = true,
	): void
	{
		$description = str_replace('static(MethodPhpDocsNamespace\Foo)', 'static(MethodPhpDocsNamespace\FooWithTrait)', $description);

		if ($replaceClass && $expression !== '$this->doFoo()') {
			$description = str_replace('$this(MethodPhpDocsNamespace\Foo)', '$this(MethodPhpDocsNamespace\FooWithTrait)', $description);
			if ($description === 'MethodPhpDocsNamespace\Foo') {
				$description = 'MethodPhpDocsNamespace\FooWithTrait';
			}
		}
		$this->assertTypes(
			__DIR__ . '/data/methodPhpDocs-trait.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataTypeFromFunctionPhpDocs')]
	#[DataProvider('dataTypeFromMethodPhpDocs')]
	public function testTypeFromMethodPhpDocsInheritDocWithoutCurlyBraces(
		string $description,
		string $expression,
		bool $replaceClass = true,
	): void
	{
		if ($replaceClass) {
			$description = str_replace('$this(MethodPhpDocsNamespace\Foo)', '$this(MethodPhpDocsNamespace\FooInheritDocChildWithoutCurly)', $description);
			$description = str_replace('static(MethodPhpDocsNamespace\Foo)', 'static(MethodPhpDocsNamespace\FooInheritDocChildWithoutCurly)', $description);
			$description = str_replace('MethodPhpDocsNamespace\FooParent', 'MethodPhpDocsNamespace\Foo', $description);
			if ($expression === '$inlineSelf') {
				$description = 'MethodPhpDocsNamespace\FooInheritDocChildWithoutCurly';
			}
		}
		$this->assertTypes(
			__DIR__ . '/data/method-phpDocs-inheritdoc-without-curly-braces.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataTypeFromFunctionPhpDocs')]
	#[DataProvider('dataTypeFromMethodPhpDocs')]
	public function testTypeFromRecursiveTraitPhpDocs(
		string $description,
		string $expression,
		bool $replaceClass = true,
	): void
	{
		$description = str_replace('static(MethodPhpDocsNamespace\Foo)', 'static(MethodPhpDocsNamespace\FooWithRecursiveTrait)', $description);

		if ($replaceClass && $expression !== '$this->doFoo()') {
			$description = str_replace('$this(MethodPhpDocsNamespace\Foo)', '$this(MethodPhpDocsNamespace\FooWithRecursiveTrait)', $description);
			if ($description === 'MethodPhpDocsNamespace\Foo') {
				$description = 'MethodPhpDocsNamespace\FooWithRecursiveTrait';
			}
		}
		$this->assertTypes(
			__DIR__ . '/data/methodPhpDocs-recursiveTrait.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataTypeFromFunctionPhpDocs')]
	#[DataProvider('dataTypeFromMethodPhpDocs')]
	public function testTypeFromMethodPhpDocsInheritDoc(
		string $description,
		string $expression,
		bool $replaceClass = true,
	): void
	{
		if ($replaceClass) {
			$description = str_replace('$this(MethodPhpDocsNamespace\Foo)', '$this(MethodPhpDocsNamespace\FooInheritDocChild)', $description);
			$description = str_replace('static(MethodPhpDocsNamespace\Foo)', 'static(MethodPhpDocsNamespace\FooInheritDocChild)', $description);
			$description = str_replace('MethodPhpDocsNamespace\FooParent', 'MethodPhpDocsNamespace\Foo', $description);
			if ($expression === '$inlineSelf') {
				$description = 'MethodPhpDocsNamespace\FooInheritDocChild';
			}
		}
		$this->assertTypes(
			__DIR__ . '/data/method-phpDocs-inheritdoc.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataTypeFromFunctionPhpDocs')]
	#[DataProvider('dataTypeFromMethodPhpDocs')]
	public function testTypeFromMethodPhpDocsImplicitInheritance(
		string $description,
		string $expression,
		bool $replaceClass = true,
	): void
	{
		if ($replaceClass) {
			$description = str_replace('$this(MethodPhpDocsNamespace\Foo)', '$this(MethodPhpDocsNamespace\FooPhpDocsImplicitInheritanceChild)', $description);
			$description = str_replace('static(MethodPhpDocsNamespace\Foo)', 'static(MethodPhpDocsNamespace\FooPhpDocsImplicitInheritanceChild)', $description);
			$description = str_replace('MethodPhpDocsNamespace\FooParent', 'MethodPhpDocsNamespace\Foo', $description);
			if ($expression === '$inlineSelf') {
				$description = 'MethodPhpDocsNamespace\FooPhpDocsImplicitInheritanceChild';
			}
		}
		$this->assertTypes(
			__DIR__ . '/data/methodPhpDocs-implicitInheritance.php',
			$description,
			$expression,
		);
	}

	public function testNotSwitchInstanceof(): void
	{
		$this->assertTypes(
			__DIR__ . '/data/switch-instanceof-not.php',
			'*NEVER*',
			'$foo',
		);
	}

	public static function dataSwitchGetClass(): array
	{
		return [
			[
				'SwitchGetClass\Lorem',
				'$lorem',
				"'normalName'",
			],
			[
				'SwitchGetClass\Foo',
				'$lorem',
				"'selfReferentialName'",
			],
		];
	}

	#[DataProvider('dataSwitchGetClass')]
	public function testSwitchGetClass(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/switch-get-class.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	public static function dataOverwritingVariable(): array
	{
		return [
			[
				'mixed',
				'$var',
				'new \OverwritingVariable\Bar()',
			],
			[
				'OverwritingVariable\Bar',
				'$var',
				'$var->methodFoo()',
			],
			[
				'OverwritingVariable\Foo',
				'$var',
				'die',
			],
		];
	}

	#[DataProvider('dataOverwritingVariable')]
	public function testOverwritingVariable(
		string $description,
		string $expression,
		string $evaluatedPointExpressionType,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/overwritingVariable.php',
			$description,
			$expression,
			$evaluatedPointExpressionType,
		);
	}

	public static function dataForeachArrayType(): array
	{
		return [
			[
				__DIR__ . '/data/foreach/array-object-type.php',
				'AnotherNamespace\Foo',
				'$foo',
			],
			[
				__DIR__ . '/data/foreach/array-object-type.php',
				'AnotherNamespace\Foo',
				'$foos[0]',
			],
			[
				__DIR__ . '/data/foreach/array-object-type.php',
				'0',
				'self::ARRAY_CONSTANT[0]',
			],
			[
				__DIR__ . '/data/foreach/array-object-type.php',
				'\'foo\'',
				'self::MIXED_CONSTANT[1]',
			],
			[
				__DIR__ . '/data/foreach/nested-object-type.php',
				'AnotherNamespace\Foo',
				'$foo',
			],
			[
				__DIR__ . '/data/foreach/nested-object-type.php',
				'AnotherNamespace\Foo',
				'$foos[0]',
			],
			[
				__DIR__ . '/data/foreach/nested-object-type.php',
				'AnotherNamespace\Foo',
				'$fooses[0][0]',
			],
			[
				__DIR__ . '/data/foreach/integer-type.php',
				'int',
				'$integer',
			],
			[
				__DIR__ . '/data/foreach/reusing-specified-variable.php',
				'1|2|3',
				'$business',
			],
			[
				__DIR__ . '/data/foreach/type-in-comment-variable-first.php',
				'mixed',
				'$value',
			],
			[
				__DIR__ . '/data/foreach/type-in-comment-variable-second.php',
				'stdClass',
				'$value',
			],
			[
				__DIR__ . '/data/foreach/type-in-comment-no-variable.php',
				'bool',
				'$value',
			],
			[
				__DIR__ . '/data/foreach/type-in-comment-no-variable-2.php',
				'*ERROR*',
				'$value',
			],
			[
				__DIR__ . '/data/foreach/type-in-comment-wrong-variable.php',
				'mixed',
				'$value',
			],
			[
				__DIR__ . '/data/foreach/type-in-comment-variable-with-reference.php',
				'string',
				'$value',
			],
			[
				__DIR__ . '/data/foreach/foreach-with-specified-key-type.php',
				'non-empty-array<string, float|int|string>',
				'$list',
			],
			[
				__DIR__ . '/data/foreach/foreach-with-specified-key-type.php',
				'string',
				'$key',
			],
			[
				__DIR__ . '/data/foreach/foreach-with-specified-key-type.php',
				'float|int|string',
				'$value',
			],
			[
				__DIR__ . '/data/foreach/foreach-with-complex-value-type.php',
				'float|ForeachWithComplexValueType\Foo',
				'$value',
			],
			[
				__DIR__ . '/data/foreach/foreach-iterable-with-specified-key-type.php',
				'ForeachWithGenericsPhpDocIterable\Bar|ForeachWithGenericsPhpDocIterable\Foo',
				'$key',
			],
			[
				__DIR__ . '/data/foreach/foreach-iterable-with-specified-key-type.php',
				'float|int|string',
				'$value',
			],
			[
				__DIR__ . '/data/foreach/foreach-iterable-with-complex-value-type.php',
				'float|ForeachIterableWithComplexValueType\Foo',
				'$value',
			],
			[
				__DIR__ . '/data/foreach/type-in-comment-key.php',
				'int',
				'$key',
			],
		];
	}

	#[DataProvider('dataForeachArrayType')]
	public function testForeachArrayType(
		string $file,
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			$file,
			$description,
			$expression,
		);
	}

	public static function dataOverridingSpecifiedType(): array
	{
		return [
			[
				__DIR__ . '/data/catch-specified-variable.php',
				'TryCatchWithSpecifiedVariable\FooException',
				'$foo',
			],
		];
	}

	#[DataProvider('dataOverridingSpecifiedType')]
	public function testOverridingSpecifiedType(
		string $file,
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			$file,
			$description,
			$expression,
		);
	}

	public static function dataForeachObjectType(): array
	{
		return [
			[
				__DIR__ . '/data/foreach/object-type.php',
				'ObjectType\\MyKey',
				'$keyFromIterator',
				"'insideFirstForeach'",
			],
			[
				__DIR__ . '/data/foreach/object-type.php',
				'ObjectType\\MyValue',
				'$valueFromIterator',
				"'insideFirstForeach'",
			],
			[
				__DIR__ . '/data/foreach/object-type.php',
				'ObjectType\\MyKey',
				'$keyFromAggregate',
				"'insideSecondForeach'",
			],
			[
				__DIR__ . '/data/foreach/object-type.php',
				'ObjectType\\MyValue',
				'$valueFromAggregate',
				"'insideSecondForeach'",
			],
			[
				__DIR__ . '/data/foreach/object-type.php',
				'mixed',
				'$keyFromRecursiveAggregate',
				"'insideThirdForeach'",
			],
			[
				__DIR__ . '/data/foreach/object-type.php',
				'mixed',
				'$valueFromRecursiveAggregate',
				"'insideThirdForeach'",
			],
		];
	}

	#[DataProvider('dataForeachObjectType')]
	public function testForeachObjectType(
		string $file,
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			$file,
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	public static function dataDioFunctions(): array
	{
		return [
			[
				'array{device: int, inode: int, mode: int, nlink: int, uid: int, gid: int, device_type: int, size: int, blocksize: int, blocks: int, atime: int, mtime: int, ctime: int}|null',
				'$stat',
			],
		];
	}

	#[DataProvider('dataDioFunctions')]
	public function testDioFunctions(
		string $description,
		string $expression,
	): void
	{
		if (!function_exists('dio_stat')) {
			$this->markTestSkipped('This test requires DIO extension.');
		}
		$this->assertTypes(
			__DIR__ . '/data/dio-functions.php',
			$description,
			$expression,
		);
	}

	public static function dataTypeElimination(): array
	{
		return [
			[
				'null',
				'$foo',
				"'nullForSure'",
			],
			[
				'TypeElimination\Foo',
				'$foo',
				"'notNullForSure'",
			],
			[
				'TypeElimination\Foo',
				'$foo',
				"'notNullForSure2'",
			],
			[
				'null',
				'$foo',
				"'nullForSure2'",
			],
			[
				'null',
				'$foo',
				"'nullForSure3'",
			],
			[
				'TypeElimination\Foo',
				'$foo',
				"'notNullForSure3'",
			],
			[
				'null',
				'$foo',
				"'yodaNullForSure'",
			],
			[
				'TypeElimination\Foo',
				'$foo',
				"'yodaNotNullForSure'",
			],
			[
				'false',
				'$intOrFalse',
				"'falseForSure'",
			],
			[
				'int',
				'$intOrFalse',
				"'intForSure'",
			],
			[
				'false',
				'$intOrFalse',
				"'yodaFalseForSure'",
			],
			[
				'int',
				'$intOrFalse',
				"'yodaIntForSure'",
			],
			[
				'true',
				'$intOrTrue',
				"'trueForSure'",
			],
			[
				'int',
				'$intOrTrue',
				"'anotherIntForSure'",
			],
			[
				'true',
				'$intOrTrue',
				"'yodaTrueForSure'",
			],
			[
				'int',
				'$intOrTrue',
				"'yodaAnotherIntForSure'",
			],
			[
				'TypeElimination\Foo',
				'$fooOrBarOrBaz',
				"'fooForSure'",
			],
			[
				'TypeElimination\Bar|TypeElimination\Baz',
				'$fooOrBarOrBaz',
				"'barOrBazForSure'",
			],
			[
				'TypeElimination\Bar',
				'$fooOrBarOrBaz',
				"'barForSure'",
			],
			[
				'TypeElimination\Baz',
				'$fooOrBarOrBaz',
				"'bazForSure'",
			],
			[
				'TypeElimination\Bar|TypeElimination\Baz',
				'$fooOrBarOrBaz',
				"'anotherBarOrBazForSure'",
			],
			[
				'TypeElimination\Foo',
				'$fooOrBarOrBaz',
				"'anotherFooForSure'",
			],
			[
				'string|null',
				'$result',
				"'stringOrNullForSure'",
			],
			[
				'int',
				'$intOrFalse',
				"'yetAnotherIntForSure'",
			],
			[
				'int',
				'$intOrTrue',
				"'yetYetAnotherIntForSure'",
			],
			[
				'TypeElimination\Foo|null',
				'$fooOrStringOrNull',
				"'fooOrNull'",
			],
			[
				'string',
				'$fooOrStringOrNull',
				"'stringForSure'",
			],
			[
				'string',
				'$fooOrStringOrNull',
				"'anotherStringForSure'",
			],
			[
				'null',
				'$this->bar',
				"'propertyNullForSure'",
			],
			[
				'TypeElimination\Bar',
				'$this->bar',
				"'propertyNotNullForSure'",
			],
		];
	}

	#[DataProvider('dataTypeElimination')]
	public function testTypeElimination(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/type-elimination.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	public static function dataLoopVariables(): array
	{
		return [
			[
				'LoopVariables\Foo|LoopVariables\Lorem|null',
				'$foo',
				"'begin'",
			],
			[
				'LoopVariables\Foo',
				'$foo',
				"'afterAssign'",
			],
			[
				'LoopVariables\Foo',
				'$foo',
				"'end'",
			],
			[
				'int<1, max>|null',
				'$nullableVal',
				"'begin'",
			],
			[
				'null',
				'$nullableVal',
				"'nullableValIf'",
			],
			[
				'int<10, max>',
				'$nullableVal',
				"'nullableValElse'",
			],
			[
				'LoopVariables\Foo|false',
				'$falseOrObject',
				"'begin'",
			],
			[
				'LoopVariables\Foo',
				'$falseOrObject',
				"'end'",
			],
		];
	}

	public static function dataForeachLoopVariables(): array
	{
		return [
			[
				'1|2|3',
				'$val',
				"'begin'",
			],
			[
				'0|1|2',
				'$key',
				"'begin'",
			],
			[
				'1|2|3|null',
				'$val',
				"'afterLoop'",
			],
			[
				'0|1|2|null',
				'$key',
				"'afterLoop'",
			],
			[
				'1|2|3|null',
				'$emptyForeachVal',
				"'afterLoop'",
			],
			[
				'0|1|2|null',
				'$emptyForeachKey',
				"'afterLoop'",
			],
			[
				'1|2|3',
				'$nullableInt',
				"'end'",
			],
			[
				'non-empty-list<1|2|3>',
				'$integers',
				"'end'",
			],
			[
				'list<1|2|3>',
				'$integers',
				"'afterLoop'",
			],
			[
				'array<string, 1|2|3>',
				'$this->property',
				"'begin'",
			],
			[
				'non-empty-array<string, 1|2|3>',
				'$this->property',
				"'end'",
			],
			[
				'array<string, 1|2|3>',
				'$this->property',
				"'afterLoop'",
			],
			[
				'int<0, max>',
				'$i',
				"'begin'",
			],
			[
				'int<0, max>',
				'$i',
				"'end'",
			],
			[
				'int<0, max>',
				'$i',
				"'afterLoop'",
			],
			[
				'LoopVariables\Bar|LoopVariables\Foo|LoopVariables\Lorem|null',
				'$foo',
				"'afterLoop'",
			],
			[
				'1|int<10, max>|null',
				'$nullableVal',
				"'afterLoop'",
			],
			[
				'LoopVariables\Foo|false',
				'$falseOrObject',
				"'afterLoop'",
			],
		];
	}

	public static function dataWhileLoopVariables(): array
	{
		return [
			[
				'int<1, 10>',
				'$i',
				"'begin'",
			],
			[
				'int<1, 10>',
				'$i',
				"'end'",
			],
			[
				'int<0, 10>',
				'$i',
				"'afterLoop'",
			],
			[
				'LoopVariables\Bar|LoopVariables\Foo|LoopVariables\Lorem|null',
				'$foo',
				"'afterLoop'",
			],
			[
				'1|int<10, max>|null',
				'$nullableVal',
				"'afterLoop'",
			],
			[
				'LoopVariables\Foo|false',
				'$falseOrObject',
				"'afterLoop'",
			],
		];
	}

	public static function dataForLoopVariables(): array
	{
		return [
			[
				'int<0, 9>',
				'$i',
				"'begin'",
			],
			[
				'int<0, 9>',
				'$i',
				"'end'",
			],
			[
				'int<0, max>',
				'$i',
				"'afterLoop'",
			],
			[
				'LoopVariables\Bar|LoopVariables\Foo|LoopVariables\Lorem',
				'$foo',
				"'afterLoop'",
			],
			[
				'1|int<10, max>',
				'$nullableVal',
				"'afterLoop'",
			],
			[
				'LoopVariables\Foo',
				'$falseOrObject',
				"'afterLoop'",
			],
		];
	}

	#[DataProvider('dataLoopVariables')]
	#[DataProvider('dataForeachLoopVariables')]
	public function testForeachLoopVariables(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/foreach-loop-variables.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	#[DataProvider('dataLoopVariables')]
	#[DataProvider('dataWhileLoopVariables')]
	public function testWhileLoopVariables(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/while-loop-variables.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	#[DataProvider('dataLoopVariables')]
	#[DataProvider('dataForLoopVariables')]
	public function testForLoopVariables(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/for-loop-variables.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	public static function dataDoWhileLoopVariables(): array
	{
		return [
			[
				'LoopVariables\Foo|LoopVariables\Lorem|null',
				'$foo',
				"'begin'",
			],
			[
				'LoopVariables\Foo',
				'$foo',
				"'afterAssign'",
			],
			[
				'LoopVariables\Foo',
				'$foo',
				"'end'",
			],
			[
				'LoopVariables\Bar|LoopVariables\Foo|LoopVariables\Lorem',
				'$foo',
				"'afterLoop'",
			],
			[
				'int<0, max>',
				'$i',
				"'begin'",
			],
			[
				'int<1, max>',
				'$i',
				"'end'",
			],
			[
				'int<0, max>',
				'$i',
				"'afterLoop'",
			],
			[
				'int<1, max>|null',
				'$nullableVal',
				"'begin'",
			],
			[
				'null',
				'$nullableVal',
				"'nullableValIf'",
			],
			[
				'int<10, max>',
				'$nullableVal',
				"'nullableValElse'",
			],
			[
				'1|int<10, max>',
				'$nullableVal',
				"'afterLoop'",
			],
			[
				'LoopVariables\Foo|false',
				'$falseOrObject',
				"'begin'",
			],
			[
				'LoopVariables\Foo',
				'$falseOrObject',
				"'end'",
			],
			[
				'LoopVariables\Foo|false',
				'$falseOrObject',
				"'afterLoop'",
			],
			[
				'LoopVariables\Foo|false',
				'$anotherFalseOrObject',
				"'begin'",
			],
			[
				'LoopVariables\Foo',
				'$anotherFalseOrObject',
				"'end'",
			],
			[
				'LoopVariables\Foo',
				'$anotherFalseOrObject',
				"'afterLoop'",
			],

		];
	}

	#[DataProvider('dataDoWhileLoopVariables')]
	public function testDoWhileLoopVariables(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/do-while-loop-variables.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	public static function dataMultipleClassesInOneFile(): array
	{
		return [
			[
				'MultipleClasses\Foo',
				'$self',
				"'Foo'",
			],
			[
				'MultipleClasses\Bar',
				'$self',
				"'Bar'",
			],
		];
	}

	#[DataProvider('dataMultipleClassesInOneFile')]
	public function testMultipleClassesInOneFile(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/multiple-classes-per-file.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	/**
	 * @return Generator<string|int, array{string,string}>
	 */

	public static function dataClosureWithUsePassedByReference(): array
	{
		return [
			[
				'false',
				'$progressStarted',
				"'beforeCallback'",
			],
			[
				'false',
				'$anotherVariable',
				"'beforeCallback'",
			],
			[
				'1|bool',
				'$progressStarted',
				"'inCallbackBeforeAssign'",
			],
			[
				'false',
				'$anotherVariable',
				"'inCallbackBeforeAssign'",
			],
			[
				'null',
				'$untouchedPassedByRef',
				"'inCallbackBeforeAssign'",
			],
			[
				'1|true',
				'$progressStarted',
				"'inCallbackAfterAssign'",
			],
			[
				'true',
				'$anotherVariable',
				"'inCallbackAfterAssign'",
			],
			[
				'1|bool',
				'$progressStarted',
				"'afterCallback'",
			],
			[
				'false',
				'$anotherVariable',
				"'afterCallback'",
			],
			[
				'null',
				'$untouchedPassedByRef',
				"'afterCallback'",
			],
			[
				'1',
				'$incrementedInside',
				"'beforeCallback'",
			],
			[
				'int<1, max>',
				'$incrementedInside',
				"'inCallbackBeforeAssign'",
			],
			[
				'int<2, max>',
				'$incrementedInside',
				"'inCallbackAfterAssign'",
			],
			[
				'int<1, max>',
				'$incrementedInside',
				"'afterCallback'",
			],
			[
				'null',
				'$fooOrNull',
				"'beforeCallback'",
			],
			[
				'ClosurePassedByReference\Foo|null',
				'$fooOrNull',
				"'inCallbackBeforeAssign'",
			],
			[
				'ClosurePassedByReference\Foo',
				'$fooOrNull',
				"'inCallbackAfterAssign'",
			],
			[
				'ClosurePassedByReference\Foo|null',
				'$fooOrNull',
				"'afterCallback'",
			],
		];
	}

	#[DataProvider('dataClosureWithUsePassedByReference')]
	public function testClosureWithUsePassedByReference(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/closure-passed-by-reference.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	public static function dataClosureWithUsePassedByReferenceReturn(): array
	{
		return [
			[
				'null',
				'$fooOrNull',
				"'beforeCallback'",
			],
			[
				'ClosurePassedByReference\Foo|null',
				'$fooOrNull',
				"'inCallbackBeforeAssign'",
			],
			[
				'ClosurePassedByReference\Foo',
				'$fooOrNull',
				"'inCallbackAfterAssign'",
			],
			[
				'ClosurePassedByReference\Foo|null',
				'$fooOrNull',
				"'afterCallback'",
			],
		];
	}

	#[DataProvider('dataClosureWithUsePassedByReferenceReturn')]
	public function testClosureWithUsePassedByReferenceReturn(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/closure-passed-by-reference-return.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	public static function dataSpecifiedFunctionCall(): array
	{
		return [
			[
				'true',
				'is_file($autoloadFile)',
				"'first'",
			],
			[
				'true',
				'is_file($autoloadFile)',
				"'second'",
			],
			[
				'true',
				'is_file($autoloadFile)',
				"'third'",
			],
			[
				'bool',
				'is_file($autoloadFile)',
				"'fourth'",
			],
			[
				'true',
				'is_file($autoloadFile)',
				"'fifth'",
			],
		];
	}

	#[DataProvider('dataSpecifiedFunctionCall')]
	public function testSpecifiedFunctionCall(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/specified-function-call.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	public static function dataConstantTypeAfterDuplicateCondition(): array
	{
		return [
			[
				'0',
				'$a',
				"'inCondition'",
			],
			[
				'0',
				'$b',
				"'inCondition'",
			],
			[
				'0',
				'$c',
				"'inCondition'",
			],
			[
				'int',
				'$a',
				"'afterFirst'",
			],
			[
				'int',
				'$b',
				"'afterFirst'",
			],
			[
				'0',
				'$c',
				"'afterFirst'",
			],
			[
				'int<min, -1>|int<1, max>',
				'$a',
				"'afterSecond'",
			],
			[
				'int',
				'$b',
				"'afterSecond'",
			],
			[
				'0',
				'$c',
				"'afterSecond'",
			],
			[
				'int<min, -1>|int<1, max>',
				'$a',
				"'afterThird'",
			],
			[
				'int<min, -1>|int<1, max>',
				'$b',
				"'afterThird'",
			],
			[
				'0',
				'$c',
				"'afterThird'",
			],
		];
	}

	#[DataProvider('dataConstantTypeAfterDuplicateCondition')]
	public function testConstantTypeAfterDuplicateCondition(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/constant-types-duplicate-condition.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	public static function dataAnonymousClass(): array
	{
		return [
			[
				'$this(AnonymousClass3301acd9e9d13ba9bbce9581cdb00699)',
				'$this',
				"'inside'",
			],
			[
				'AnonymousClass3301acd9e9d13ba9bbce9581cdb00699',
				'$foo',
				"'outside'",
			],
			[
				'AnonymousClassName\Foo',
				'$this->fooProperty',
				"'inside'",
			],
			[
				'AnonymousClassName\Foo',
				'$foo->fooProperty',
				"'outside'",
			],
			[
				'AnonymousClassName\Foo',
				'$this->doFoo()',
				"'inside'",
			],
			[
				'AnonymousClassName\Foo',
				'$foo->doFoo()',
				"'outside'",
			],
		];
	}

	#[DataProvider('dataAnonymousClass')]
	public function testAnonymousClassName(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/anonymous-class-name.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	public static function dataAnonymousClassInTrait(): array
	{
		return [
			[
				'$this(AnonymousClass3de0a9734314db9dec21ba308363ff9a)',
				'$this',
			],
		];
	}

	#[DataProvider('dataAnonymousClassInTrait')]
	public function testAnonymousClassNameInTrait(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/anonymous-class-name-in-trait.php',
			$description,
			$expression,
		);
	}

	public static function dataAnonymousClassNameSameLine(): array
	{
		return [
			[
				'AnonymousClass0d7d08272ba2f0a6ef324bb65c679e02',
				'$foo',
				'$bar',
			],
			[
				'AnonymousClass464f64cbdca25b4af842cae65615bca9',
				'$bar',
				'$baz',
			],
			[
				'AnonymousClassa9fb472ec9acc5cae3bee4355c296bfa',
				'$baz',
				'die',
			],
		];
	}

	#[DataProvider('dataAnonymousClassNameSameLine')]
	public function testAnonymousClassNameSameLine(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/anonymous-class-name-same-line.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	public static function dataDynamicConstants(): array
	{
		return [
			[
				'string',
				'DynamicConstants\DynamicConstantClass::DYNAMIC_CONSTANT_IN_CLASS',
			],
			[
				'string|null',
				'DynamicConstants\DynamicConstantClass::DYNAMIC_CONSTANT_WITH_EXPLICIT_TYPES_IN_CLASS',
			],
			[
				"'abc123def'",
				'DynamicConstants\DynamicConstantClass::PURE_CONSTANT_IN_CLASS',
			],
			[
				"'xyz'",
				'DynamicConstants\NoDynamicConstantClass::DYNAMIC_CONSTANT_IN_CLASS',
			],
			[
				'bool',
				'GLOBAL_DYNAMIC_CONSTANT',
			],
			[
				'123',
				'GLOBAL_PURE_CONSTANT',
			],
			[
				'string|null',
				'GLOBAL_DYNAMIC_CONSTANT_WITH_EXPLICIT_TYPES',
			],
		];
	}

	#[DataProvider('dataDynamicConstants')]
	public function testDynamicConstants(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/dynamic-constant.php',
			$description,
			$expression,
			'die',
			[
				0 => 'DynamicConstants\\DynamicConstantClass::DYNAMIC_CONSTANT_IN_CLASS',
				1 => 'GLOBAL_DYNAMIC_CONSTANT',
				'DynamicConstants\\DynamicConstantClass::DYNAMIC_CONSTANT_WITH_EXPLICIT_TYPES_IN_CLASS' => 'string|null',
				'GLOBAL_DYNAMIC_CONSTANT_WITH_EXPLICIT_TYPES' => 'string|null',
			],
		);
	}

	public static function dataDynamicConstantsWithNativeTypes(): array
	{
		return [
			[
				'int',
				'DynamicConstantNativeTypes\Foo::FOO',
			],
			[
				'int|string',
				'DynamicConstantNativeTypes\Foo::BAR',
			],
			[
				'int',
				'$foo::FOO',
			],
			[
				'int|string',
				'$foo::BAR',
			],
		];
	}

	#[RequiresPhp('>= 8.3')]
	#[DataProvider('dataDynamicConstantsWithNativeTypes')]
	public function testDynamicConstantsWithNativeTypes(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/dynamic-constant-native-types.php',
			$description,
			$expression,
			'die',
			[
				'DynamicConstantNativeTypes\Foo::FOO',
				'DynamicConstantNativeTypes\Foo::BAR',
			],
		);
	}

	public static function dataPropertyArrayAssignment(): array
	{
		return [
			[
				'mixed',
				'$this->property',
				"'start'",
			],
			[
				'array{}',
				'$this->property',
				"'emptyArray'",
			],
			[
				'*ERROR*',
				'$this->property[\'foo\']',
				"'emptyArray'",
			],
			[
				'array{foo: 1}',
				'$this->property',
				"'afterAssignment'",
			],
			[
				'1',
				'$this->property[\'foo\']',
				"'afterAssignment'",
			],
		];
	}

	#[DataProvider('dataPropertyArrayAssignment')]
	public function testPropertyArrayAssignment(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/property-array.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	public static function dataGetParentClass(): array
	{
		return [
			[
				'false',
				'get_parent_class()',
			],
			[
				'class-string|false',
				'get_parent_class($s)',
			],
			[
				'false',
				'get_parent_class(\ParentClass\Foo::class)',
			],
			[
				'class-string|false',
				'get_parent_class(NonexistentClass::class)',
			],
			[
				'class-string|false',
				'get_parent_class(1)',
			],
			[
				"'ParentClass\\\\Foo'",
				'get_parent_class(\ParentClass\Bar::class)',
			],
			[
				'false',
				'get_parent_class()',
				"'inParentClass'",
			],
			[
				'false',
				'get_parent_class($this)',
				"'inParentClass'",
			],
			[
				'class-string<ParentClass\Foo>',
				'get_class($this)',
				"'inParentClass'",
			],
			[
				'\'ParentClass\\\\Foo\'',
				'get_class()',
				"'inParentClass'",
			],
			[
				'false',
				'get_class()',
			],
			[
				"'ParentClass\\\\Foo'",
				'get_parent_class()',
				"'inChildClass'",
			],
			[
				"'ParentClass\\\\Foo'",
				'get_parent_class($this)',
				"'inChildClass'",
			],
			[
				'class-string|false',
				'get_parent_class()',
				"'inTrait'",
			],
			[
				'class-string|false',
				'get_parent_class($this)',
				"'inTrait'",
			],
		];
	}

	#[DataProvider('dataGetParentClass')]
	public function testGetParentClass(
		string $description,
		string $expression,
		string $evaluatedPointExpression = 'die',
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/get-parent-class.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	public static function dataIsCountable(): array
	{
		return [
			[
				'array|Countable',
				'$union',
				"'is'",
			],
			[
				'string',
				'$union',
				"'is_not'",
			],
		];
	}

	#[DataProvider('dataIsCountable')]
	public function testIsCountable(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/is_countable.php',
			$description,
			$expression,
			$evaluatedPointExpression,
		);
	}

	public static function dataTryCatchScope(): array
	{
		return [
			[
				'TryCatchScope\Foo',
				'$resource',
				"'first'",
			],
			[
				'TryCatchScope\Foo|null',
				'$resource',
				"'second'",
			],
			[
				'TryCatchScope\Foo|null',
				'$resource',
				"'third'",
			],
		];
	}

	#[DataProvider('dataTryCatchScope')]
	public function testTryCatchScope(
		string $description,
		string $expression,
		string $evaluatedPointExpression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/try-catch-scope.php',
			$description,
			$expression,
			$evaluatedPointExpression,
			[],
			false,
		);
	}

	/**
	 * @param string[] $dynamicConstantNames
	 */
	private function assertTypes(
		string $file,
		string $description,
		string $expression,
		string $evaluatedPointExpression = 'die',
		array $dynamicConstantNames = [],
		bool $useCache = true,
	): void
	{
		$assertType = function (Scope $scope) use ($expression, $description, $evaluatedPointExpression): void {
			/** @var Node\Stmt\Expression $expressionNode */
			$expressionNode = $this->getParser()->parseString(sprintf('<?php %s;', $expression))[0];
			$type = $scope->getType($expressionNode->expr);
			$this->assertTypeDescribe(
				$description,
				$type,
				sprintf('%s at %s', $expression, $evaluatedPointExpression),
			);
		};
		if ($useCache && isset(self::$assertTypesCache[$file][$evaluatedPointExpression])) {
			$assertType(self::$assertTypesCache[$file][$evaluatedPointExpression]);
			return;
		}

		self::processFile(
			$file,
			static function (Node $node, Scope $scope) use ($file, $evaluatedPointExpression, $assertType): void {
				if ($node instanceof VirtualNode) {
					return;
				}
				$printer = new Printer();
				$printedNode = $printer->prettyPrint([$node]);
				if ($printedNode !== $evaluatedPointExpression) {
					return;
				}

				self::$assertTypesCache[$file][$evaluatedPointExpression] = $scope->toMutatingScope();

				$assertType($scope);
			},
			$dynamicConstantNames,
		);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../conf/bleedingEdge.neon',
			__DIR__ . '/typeAliases.neon',
		];
	}

	public static function dataDeclareStrictTypes(): array
	{
		return [
			[
				__DIR__ . '/data/declareWeakTypes.php',
				false,
			],
			[
				__DIR__ . '/data/noDeclare.php',
				false,
			],
			[
				__DIR__ . '/data/declareStrictTypes.php',
				true,
			],
		];
	}

	#[DataProvider('dataDeclareStrictTypes')]
	public function testDeclareStrictTypes(string $file, bool $result): void
	{
		self::processFile($file, function (Node $node, Scope $scope) use ($result): void {
			if (!($node instanceof Exit_)) {
				return;
			}

			$this->assertSame($result, $scope->isDeclareStrictTypes());
		});
	}

	public function testEarlyTermination(): void
	{
		self::processFile(__DIR__ . '/data/early-termination.php', function (Node $node, Scope $scope): void {
			if (!($node instanceof Exit_)) {
				return;
			}

			$this->assertTrue($scope->hasVariableType('something')->yes());
			$this->assertTrue($scope->hasVariableType('var')->yes());
			$this->assertTrue($scope->hasVariableType('foo')->no());
		});
	}

	protected static function getEarlyTerminatingMethodCalls(): array
	{
		return [
			\EarlyTermination\Foo::class => [
				'doFoo',
				'doBar',
			],
		];
	}

	protected static function getEarlyTerminatingFunctionCalls(): array
	{
		return ['baz'];
	}

	private function assertTypeDescribe(
		string $expectedDescription,
		Type $actualType,
		string $label = '',
	): void
	{
		$actualDescription = $actualType->describe(VerbosityLevel::precise());
		$this->assertSame(
			$expectedDescription,
			$actualDescription,
			$label,
		);
	}

	/** @return string[] */
	protected static function getAdditionalAnalysedFiles(): array
	{
		return [
			__DIR__ . '/data/methodPhpDocs-trait-defined.php',
			__DIR__ . '/data/anonymous-class-name-in-trait-trait.php',
		];
	}

}
