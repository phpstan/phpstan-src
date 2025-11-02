<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Expr\Exit_;
use PHPStan\Node\Printer\Printer;
use PHPStan\Node\VirtualNode;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use SomeNodeScopeResolverNamespace\Foo;
use function define;
use function defined;
use function function_exists;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function sprintf;
use function str_replace;
use const PHP_VERSION_ID;

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

	public static function dataUnionInCatch(): array
	{
		return [
			[
				'CatchUnion\BarException|CatchUnion\FooException',
				'$e',
			],
		];
	}

	#[DataProvider('dataUnionInCatch')]
	public function testUnionInCatch(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/catch-union.php',
			$description,
			$expression,
		);
	}

	public static function dataUnionAndIntersection(): array
	{
		return [
			[
				'UnionIntersection\AnotherFoo|UnionIntersection\Foo',
				'$this->union->foo',
			],
			[
				'UnionIntersection\Bar',
				'$this->union->bar',
			],
			[
				'UnionIntersection\Foo',
				'$foo->foo',
			],
			[
				'*ERROR*',
				'$foo->bar',
			],
			[
				'UnionIntersection\AnotherFoo|UnionIntersection\Foo',
				'$this->union->doFoo()',
			],
			[
				'UnionIntersection\Bar',
				'$this->union->doBar()',
			],
			[
				'UnionIntersection\Foo',
				'$foo->doFoo()',
			],
			[
				'*ERROR*',
				'$foo->doBar()',
			],
			[
				'UnionIntersection\AnotherFoo&UnionIntersection\Foo',
				'$foobar->doFoo()',
			],
			[
				'UnionIntersection\Bar',
				'$foobar->doBar()',
			],
			[
				'1',
				'$this->union::FOO_CONSTANT',
			],
			[
				'1',
				'$this->union::BAR_CONSTANT',
			],
			[
				'1',
				'$foo::FOO_CONSTANT',
			],
			[
				'*ERROR*',
				'$foo::BAR_CONSTANT',
			],
			[
				'1',
				'$foobar::FOO_CONSTANT',
			],
			[
				'1',
				'$foobar::BAR_CONSTANT',
			],
			[
				'\'foo\'',
				'self::IPSUM_CONSTANT',
			],
			[
				'array{1, 2, 3}',
				'parent::PARENT_CONSTANT',
			],
			[
				'UnionIntersection\Foo',
				'$foo::doStaticFoo()',
			],
			[
				'*ERROR*',
				'$foo::doStaticBar()',
			],
			[
				'UnionIntersection\AnotherFoo&UnionIntersection\Foo',
				'$foobar::doStaticFoo()',
			],
			[
				'UnionIntersection\Bar',
				'$foobar::doStaticBar()',
			],
			[
				'UnionIntersection\AnotherFoo|UnionIntersection\Foo',
				'$this->union::doStaticFoo()',
			],
			[
				'UnionIntersection\Bar',
				'$this->union::doStaticBar()',
			],
			[
				'object',
				'$this->objectUnion',
			],
			[
				'UnionIntersection\SomeInterface',
				'$object',
			],
		];
	}

	#[DataProvider('dataUnionAndIntersection')]
	public function testUnionAndIntersection(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/union-intersection.php',
			$description,
			$expression,
		);
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

	public static function dataArrayDestructuring(): array
	{
		return [
			[
				'mixed',
				'$a',
			],
			[
				'mixed',
				'$b',
			],
			[
				'mixed',
				'$c',
			],
			[
				'mixed',
				'$aList',
			],
			[
				'mixed',
				'$bList',
			],
			[
				'mixed',
				'$cList',
			],
			[
				'1',
				'$int',
			],
			[
				'\'foo\'',
				'$string',
			],
			[
				'true',
				'$bool',
			],
			[
				'*ERROR*',
				'$never',
			],
			[
				'*ERROR*',
				'$nestedNever',
			],
			[
				'1',
				'$intList',
			],
			[
				'\'foo\'',
				'$stringList',
			],
			[
				'true',
				'$boolList',
			],
			[
				'*ERROR*',
				'$neverList',
			],
			[
				'*ERROR*',
				'$nestedNeverList',
			],
			[
				'1',
				'$foreachInt',
			],
			[
				'false',
				'$foreachBool',
			],
			[
				'*ERROR*',
				'$foreachNever',
			],
			[
				'*ERROR*',
				'$foreachNestedNever',
			],
			[
				'1',
				'$foreachIntList',
			],
			[
				'false',
				'$foreachBoolList',
			],
			[
				'*ERROR*',
				'$foreachNeverList',
			],
			[
				'*ERROR*',
				'$foreachNestedNeverList',
			],
			[
				'1|4',
				'$u1',
			],
			[
				'2|\'bar\'',
				'$u2',
			],
			[
				'3',
				'$u3',
			],
			[
				'1|4',
				'$foreachU1',
			],
			[
				'2|\'bar\'',
				'$foreachU2',
			],
			[
				'3',
				'$foreachU3',
			],
			[
				'string',
				'$firstStringArray',
			],
			[
				'string',
				'$secondStringArray',
			],
			[
				'non-empty-string',
				'$thirdStringArray',
			],
			[
				'string',
				'$fourthStringArray',
			],
			[
				'string',
				'$firstStringArrayList',
			],
			[
				'string',
				'$secondStringArrayList',
			],
			[
				'non-empty-string',
				'$thirdStringArrayList',
			],
			[
				'string',
				'$fourthStringArrayList',
			],
			[
				'non-empty-string',
				'$firstStringArrayForeach',
			],
			[
				'non-empty-string',
				'$secondStringArrayForeach',
			],
			[
				'non-empty-string',
				'$thirdStringArrayForeach',
			],
			[
				'non-empty-string',
				'$fourthStringArrayForeach',
			],
			[
				'non-empty-string',
				'$firstStringArrayForeachList',
			],
			[
				'non-empty-string',
				'$secondStringArrayForeachList',
			],
			[
				'non-empty-string',
				'$thirdStringArrayForeachList',
			],
			[
				'non-empty-string',
				'$fourthStringArrayForeachList',
			],
			[
				'lowercase-string&uppercase-string',
				'$dateArray[\'Y\']',
			],
			[
				'lowercase-string&uppercase-string',
				'$dateArray[\'m\']',
			],
			[
				'int',
				'$dateArray[\'d\']',
			],
			[
				'lowercase-string&uppercase-string',
				'$intArrayForRewritingFirstElement[0]',
			],
			[
				'int',
				'$intArrayForRewritingFirstElement[1]',
			],
			[
				'ArrayAccess&stdClass',
				'$obj',
			],
			[
				'stdClass',
				'$newArray[\'newKey\']',
			],
			[
				'true',
				'$assocKey',
			],
			[
				'\'foo\'',
				'$assocFoo',
			],
			[
				'1',
				'$assocOne',
			],
			[
				'*ERROR*',
				'$assocNonExistent',
			],
			[
				'true',
				'$dynamicAssocKey',
			],
			[
				'\'123\'|true',
				'$dynamicAssocStrings',
			],
			[
				'1|\'123\'|\'foo\'|true',
				'$dynamicAssocMixed',
			],
			[
				'true',
				'$dynamicAssocKeyForeach',
			],
			[
				'\'123\'|true',
				'$dynamicAssocStringsForeach',
			],
			[
				'1|\'123\'|\'foo\'|true',
				'$dynamicAssocMixedForeach',
			],
			[
				'string',
				'$stringFromIterable',
			],
			[
				'string',
				'$stringWithVarAnnotation',
			],
			[
				'string',
				'$stringWithVarAnnotationInForeach',
			],
		];
	}

	#[DataProvider('dataArrayDestructuring')]
	public function testArrayDestructuring(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/array-destructuring.php',
			$description,
			$expression,
		);
	}

	public static function dataParameterTypes(): array
	{
		return [
			[
				'int',
				'$integer',
			],
			[
				'bool',
				'$boolean',
			],
			[
				'string',
				'$string',
			],
			[
				'float',
				'$float',
			],
			[
				'TypesNamespaceTypehints\Lorem',
				'$loremObject',
			],
			[
				'mixed',
				'$mixed',
			],
			[
				'array',
				'$array',
			],
			[
				'bool|null',
				'$isNullable',
			],
			[
				'TypesNamespaceTypehints\Lorem',
				'$loremObjectRef',
			],
			[
				'TypesNamespaceTypehints\Bar',
				'$barObject',
			],
			[
				'TypesNamespaceTypehints\Foo',
				'$fooObject',
			],
			[
				'TypesNamespaceTypehints\Bar',
				'$anotherBarObject',
			],
			[
				'callable(): mixed',
				'$callable',
			],
			[
				PHP_VERSION_ID < 80000 ? 'list<string>' : 'array<int|string, string>',
				'$variadicStrings',
			],
			[
				'string',
				'$variadicStrings[0]',
			],
		];
	}

	#[DataProvider('dataParameterTypes')]
	public function testTypehints(
		string $typeClass,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/typehints.php',
			$typeClass,
			$expression,
		);
	}

	public static function dataAnonymousFunctionParameterTypes(): array
	{
		return [
			[
				'int',
				'$integer',
			],
			[
				'bool',
				'$boolean',
			],
			[
				'string',
				'$string',
			],
			[
				'float',
				'$float',
			],
			[
				'TypesNamespaceTypehints\Lorem',
				'$loremObject',
			],
			[
				'mixed',
				'$mixed',
			],
			[
				'array',
				'$array',
			],
			[
				'bool|null',
				'$isNullable',
			],
			[
				'callable(): mixed',
				'$callable',
			],
			[
				'TypesNamespaceTypehints\FooWithAnonymousFunction',
				'$self',
			],
		];
	}

	#[DataProvider('dataAnonymousFunctionParameterTypes')]
	public function testAnonymousFunctionTypehints(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/typehints-anonymous-function.php',
			$description,
			$expression,
		);
	}

	public static function dataVarAnnotations(): array
	{
		return [
			[
				'int',
				'$integer',
			],
			[
				'bool',
				'$boolean',
			],
			[
				'string',
				'$string',
			],
			[
				'float',
				'$float',
			],
			[
				'VarAnnotations\Lorem',
				'$loremObject',
			],
			[
				'AnotherNamespace\Bar',
				'$barObject',
			],
			[
				'mixed',
				'$mixed',
			],
			[
				'array',
				'$array',
			],
			[
				'bool|null',
				'$isNullable',
			],
			[
				'callable(): mixed',
				'$callable',
			],
			[
				'callable(int, string ...): void',
				'$callableWithTypes',
			],
			[
				'Closure(int, string ...): void',
				'$closureWithTypes',
			],
			[
				'VarAnnotations\Foo',
				'$self',
			],
			[
				'float',
				'$invalidInteger',
			],
			[
				'static(VarAnnotations\Foo)',
				'$static',
			],
		];
	}

	#[DataProvider('dataVarAnnotations')]
	public function testVarAnnotations(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/var-annotations.php',
			$description,
			$expression,
			'die',
			[],
			false,
		);
	}

	public static function dataCasts(): array
	{
		return [
			[
				'int',
				'$castedInteger',
			],
			[
				'bool',
				'$castedBoolean',
			],
			[
				'float',
				'$castedFloat',
			],
			[
				'string',
				'$castedString',
			],
			[
				'array',
				'$castedArray',
			],
			[
				'stdClass',
				'$castedObject',
			],
			[
				'TypesNamespaceCasts\Foo',
				'$castedFoo',
			],
			[
				'stdClass|TypesNamespaceCasts\Foo',
				'$castedArrayOrObject',
			],
			[
				'0|1',
				'(int) $bool',
			],
			[
				'0.0|1.0',
				'(float) $bool',
			],
			[
				'*ERROR*',
				'(int) $foo',
			],
			[
				'true',
				'(bool) $foo',
			],
			[
				'1',
				'(int) true',
			],
			[
				'0',
				'(int) false',
			],
			[
				'5',
				'(int) 5.25',
			],
			[
				'5.0',
				'(float) 5',
			],
			[
				'5',
				'(int) "5"',
			],
			[
				'5.0',
				'(float) "5"',
			],
			[
				'0',
				'(int) "blabla"',
			],
			[
				'0.0',
				'(float) "blabla"',
			],
			[
				'0',
				'(int) null',
			],
			[
				'0.0',
				'(float) null',
			],
			[
				'int',
				'(int) $str',
			],
			[
				'float',
				'(float) $str',
			],
			[
				"array{'\0TypesNamespaceCasts\\Foo\0foo': TypesNamespaceCasts\\Foo, '\0TypesNamespaceCasts\\Foo\0int': int, '\0*\0protectedInt': int, publicInt: int, '\0TypesNamespaceCasts\\Bar\0barProperty': TypesNamespaceCasts\\Bar}",
				'(array) $foo',
			],
			[
				'array{1, 2, 3}',
				'(array) [1, 2, 3]',
			],
			[
				'array{1}',
				'(array) 1',
			],
			[
				'array{1.0}',
				'(array) 1.0',
			],
			[
				'array{true}',
				'(array) true',
			],
			[
				'array{\'blabla\'}',
				'(array) "blabla"',
			],
			[
				'array{int}',
				'(array) $castedInteger',
			],
			[
				'array<string, DateTimeImmutable>',
				'(array) $iterable',
			],
			[
				'array',
				'(array) new stdClass()',
			],
		];
	}

	#[DataProvider('dataCasts')]
	public function testCasts(
		string $desciptiion,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/casts.php',
			$desciptiion,
			$expression,
		);
	}

	public static function dataDeductedTypes(): array
	{
		return [
			[
				'1',
				'$integerLiteral',
			],
			[
				'true',
				'$booleanLiteral',
			],
			[
				'false',
				'$anotherBooleanLiteral',
			],
			[
				'\'foo\'',
				'$stringLiteral',
			],
			[
				'1.0',
				'$floatLiteral',
			],
			[
				'1.0',
				'$floatAssignedByRef',
			],
			[
				'null',
				'$nullLiteral',
			],
			[
				'TypesNamespaceDeductedTypes\Lorem',
				'$loremObjectLiteral',
			],
			[
				'object',
				'$mixedObjectLiteral',
			],
			[
				'static(TypesNamespaceDeductedTypes\Foo)',
				'$newStatic',
			],
			[
				'array{}',
				'$arrayLiteral',
			],
			[
				'string',
				'$stringFromFunction',
			],
			[
				'TypesNamespaceFunctions\Foo',
				'$fooObjectFromFunction',
			],
			[
				'mixed',
				'$mixedFromFunction',
			],
			[
				'1',
				'\TypesNamespaceDeductedTypes\Foo::INTEGER_CONSTANT',
			],
			[
				'1',
				'self::INTEGER_CONSTANT',
			],
			[
				'1.0',
				'self::FLOAT_CONSTANT',
			],
			[
				'\'foo\'',
				'self::STRING_CONSTANT',
			],
			[
				'array{}',
				'self::ARRAY_CONSTANT',
			],
			[
				'true',
				'self::BOOLEAN_CONSTANT',
			],
			[
				'null',
				'self::NULL_CONSTANT',
			],
			[
				'1',
				'$foo::INTEGER_CONSTANT',
			],
			[
				'1.0',
				'$foo::FLOAT_CONSTANT',
			],
			[
				'\'foo\'',
				'$foo::STRING_CONSTANT',
			],
			[
				'array{}',
				'$foo::ARRAY_CONSTANT',
			],
			[
				'true',
				'$foo::BOOLEAN_CONSTANT',
			],
			[
				'null',
				'$foo::NULL_CONSTANT',
			],
		];
	}

	#[DataProvider('dataDeductedTypes')]
	public function testDeductedTypes(
		string $description,
		string $expression,
	): void
	{
		require_once __DIR__ . '/data/function-definitions.php';
		$this->assertTypes(
			__DIR__ . '/data/deducted-types.php',
			$description,
			$expression,
		);
	}

	public static function dataProperties(): array
	{
		return [
			[
				'mixed',
				'$this->mixedProperty',
			],
			[
				'mixed',
				'$this->anotherMixedProperty',
			],
			[
				'mixed',
				'$this->yetAnotherMixedProperty',
			],
			[
				'int',
				'$this->integerProperty',
			],
			[
				'int',
				'$this->anotherIntegerProperty',
			],
			[
				'array',
				'$this->arrayPropertyOne',
			],
			[
				'array<mixed>',
				'$this->arrayPropertyOther',
			],
			[
				'PropertiesNamespace\\Lorem',
				'$this->objectRelative',
			],
			[
				'SomeOtherNamespace\\Ipsum',
				'$this->objectFullyQualified',
			],
			[
				'SomeNamespace\\Amet',
				'$this->objectUsed',
			],
			[
				'*ERROR*',
				'$this->nonexistentProperty',
			],
			[
				'int|null',
				'$this->nullableInteger',
			],
			[
				'SomeNamespace\Amet|null',
				'$this->nullableObject',
			],
			[
				'PropertiesNamespace\\Foo',
				'$this->selfType',
			],
			[
				'static(PropertiesNamespace\Foo)',
				'$this->staticType',
			],
			[
				'null',
				'$this->nullType',
			],
			[
				'SomeNamespace\Sit',
				'$this->inheritedProperty',
			],
			[
				'PropertiesNamespace\Bar',
				'$this->barObject->doBar()',
			],
			[
				'mixed',
				'$this->invalidTypeProperty',
			],
			[
				'resource',
				'$this->resource',
			],
			[
				'mixed',
				'$this->yetAnotherAnotherMixedParameter',
			],
			[
				'mixed',
				'$this->yetAnotherAnotherAnotherMixedParameter',
			],
			[
				'string',
				'self::$staticStringProperty',
			],
			[
				'SomeGroupNamespace\One',
				'$this->groupUseProperty',
			],
			[
				'SomeGroupNamespace\Two',
				'$this->anotherGroupUseProperty',
			],
			[
				'PropertiesNamespace\Bar',
				'$this->inheritDocProperty',
			],
			[
				'PropertiesNamespace\Bar',
				'$this->inheritDocWithoutCurlyBracesProperty',
			],
			[
				'PropertiesNamespace\Bar',
				'$this->implicitInheritDocProperty',
			],
			[
				'int',
				'$this->readOnlyProperty',
			],
			[
				'string',
				'$this->overriddenReadOnlyProperty',
			],
			[
				PHP_VERSION_ID < 80100 ? 'string' : 'DOMElement|null',
				'$this->documentElement',
			],
		];
	}

	#[DataProvider('dataProperties')]
	public function testProperties(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/properties.php',
			$description,
			$expression,
		);
	}

	public static function dataBinaryOperations(): array
	{
		$typeCallback = static function ($value): string {
			if (is_int($value)) {
				return (new ConstantIntegerType($value))->describe(VerbosityLevel::precise());
			} elseif (is_float($value)) {
				return (new ConstantFloatType($value))->describe(VerbosityLevel::precise());
			} elseif (is_bool($value)) {
				return (new ConstantBooleanType($value))->describe(VerbosityLevel::precise());
			} elseif (is_string($value)) {
				return (new ConstantStringType($value))->describe(VerbosityLevel::precise());
			}

			throw new ShouldNotHappenException();
		};

		return [
			[
				'false',
				'true && false',
			],
			[
				'true',
				'true || false',
			],
			[
				'true',
				'true xor false',
			],
			[
				'true',
				'false xor true',
			],
			[
				'false',
				'true xor true',
			],
			[
				'false',
				'true xor true',
			],
			[
				'bool',
				'$bool xor true',
			],
			[
				'bool',
				'$bool xor false',
			],
			[
				'false',
				'true and false',
			],
			[
				'true',
				'true or false',
			],
			[
				'false',
				'!true',
			],
			[
				$typeCallback(-1),
				'-1',
			],
			[
				$typeCallback(+1),
				'+1',
			],
			[
				'*ERROR*',
				'+"blabla"',
			],
			[
				'123.2',
				'+"123.2"',
			],
			[
				'*ERROR*',
				'-"blabla"',
			],
			[
				'-5',
				'-5',
			],
			[
				'5',
				'-(-5)',
			],
			[
				'int',
				'-$integer',
			],
			[
				'-2|-1',
				'-$conditionalInt',
			],
			[
				'*ERROR*',
				'-$string',
			],
			// integer + integer
			[
				$typeCallback(1 + 1),
				'1 + 1',
			],
			[
				$typeCallback(1 - 1),
				'1 - 1',
			],
			[
				$typeCallback(1 / 2),
				'1 / 2',
			],
			[
				$typeCallback(1 * 1),
				'1 * 1',
			],
			[
				$typeCallback(1 ** 1),
				'1 ** 1',
			],
			[
				$typeCallback(1 % 1),
				'1 % 1',
			],
			[
				'(float|int)',
				'$integer /= 2',
			],
			[
				'int',
				'$integer *= 1',
			],
			// float + float
			[
				$typeCallback(1.2 + 1.4),
				'1.2 + 1.4',
			],
			[
				$typeCallback(1.2 - 1.4),
				'1.2 - 1.4',
			],
			[
				$typeCallback(1.2 / 2.4),
				'1.2 / 2.4',
			],
			[
				$typeCallback(1.2 * 1.4),
				'1.2 * 1.4',
			],
			[
				$typeCallback(1.2 ** 1.4),
				'1.2 ** 1.4',
			],
			[
				'1',
				'3.2 % 2.4',
			],
			[
				'float',
				'$float /= 2.4',
			],
			[
				'float',
				'$float *= 2.4',
			],
			// integer + float
			[
				$typeCallback(1 + 1.4),
				'1 + 1.4',
			],
			[
				$typeCallback(1 - 1.4),
				'1 - 1.4',
			],
			[
				$typeCallback(1 / 2.4),
				'1 / 2.4',
			],
			[
				$typeCallback(1 * 1.4),
				'1 * 1.4',
			],
			[
				$typeCallback(1 ** 1.4),
				'1 ** 1.4',
			],
			[
				'1',
				'3 % 2.4',
			],
			[
				'float',
				'$integer /= 2.4',
			],
			[
				'float',
				'$integer *= 2.4',
			],
			[
				'int',
				'$otherInteger + 1',
			],
			[
				'float',
				'$otherInteger + 1.0',
			],
			// float + integer
			[
				$typeCallback(1.2 + 1),
				'1.2 + 1',
			],
			[
				$typeCallback(1.2 - 1),
				'1.2 - 1',
			],
			[
				$typeCallback(1.2 / 2),
				'1.2 / 2',
			],
			[
				$typeCallback(1.2 * 1),
				'1.2 * 1',
			],
			[
				'int',
				'$integer * 10',
			],
			[
				$typeCallback(1.2 ** 1),
				'1.2 ** 1',
			],
			[
				'(float|int)',
				'$integer ** $integer',
			],
			[
				'1',
				'3.2 % 2',
			],
			[
				'int',
				'$float %= 2.4',
			],
			[
				'float',
				'$float **= 2.4',
			],
			[
				'float',
				'$float /= 2.4',
			],
			[
				'float',
				'$float *= 2',
			],
			// boolean
			[
				'1',
				'true + false',
			],
			// string
			[
				"'ab'",
				"'a' . 'b'",
			],
			[
				$typeCallback(1 . 'b'),
				"1 . 'b'",
			],
			[
				$typeCallback(1.0 . 'b'),
				"1.0 . 'b'",
			],
			[
				$typeCallback(1.0 . 2.0),
				'1.0 . 2.0',
			],
			[
				$typeCallback('foo' <=> 'bar'),
				"'foo' <=> 'bar'",
			],
			[
				'(float|int)',
				'1 + $mixed',
			],
			[
				'float|int',
				'1 + $number',
			],
			[
				'float|int',
				'$integer + $number',
			],
			[
				'float',
				'$float + $float',
			],
			[
				'float',
				'$float + $number',
			],
			[
				'(float|int)',
				'1 / $mixed',
			],
			[
				'float|int',
				'1 / $number',
			],
			[
				'float',
				'1.0 / $mixed',
			],
			[
				'float',
				'1.0 / $number',
			],
			[
				'(float|int)',
				'$mixed / 1',
			],
			[
				'float|int',
				'$number / 1',
			],
			[
				'float',
				'$mixed / 1.0',
			],
			[
				'float',
				'$number / 1.0',
			],
			[
				'float',
				'1.0 + $mixed',
			],
			[
				'float',
				'1.0 + $number',
			],
			[
				'(float|int)',
				'$mixed + 1',
			],
			[
				'float|int',
				'$number + 1',
			],
			[
				'float',
				'$mixed + 1.0',
			],
			[
				'float',
				'$number + 1.0',
			],
			[
				'\'foo\'|null',
				'$mixed ? "foo" : null',
			],
			[
				'12',
				'12 ?: null',
			],
			[
				'1',
				'true ? 1 : 2',
			],
			[
				'2',
				'false ? 1 : 2',
			],
			[
				'12|non-falsy-string',
				'$string ?: 12',
			],
			[
				'12|non-falsy-string',
				'$stringOrNull ?: 12',
			],
			[
				'12|non-falsy-string',
				'@$stringOrNull ?: 12',
			],
			[
				'int<min, -1>|int<1, max>',
				'$integer ?: 12',
			],
			[
				'\'foo\'',
				"'foo' ?? null", // "else" never gets executed
			],
			[
				'string|null',
				'$stringOrNull ?? null',
			],
			[
				'\'bar\'|\'foo\'',
				'$maybeDefinedVariable ?? \'bar\'',
			],
			[
				'string',
				'$string ?? \'foo\'',
			],
			[
				'string',
				'$stringOrNull ?? \'foo\'',
			],
			[
				'string',
				'$string ?? $integer',
			],
			[
				'int|string',
				'$stringOrNull ?? $integer',
			],
			[
				'\'Foo\'',
				'\Foo::class',
			],
			[
				'74',
				'$line',
			],
			[
				'literal-string&non-falsy-string',
				'$dir',
			],
			[
				'literal-string&non-falsy-string',
				'$file',
			],
			[
				'\'BinaryOperations\\\\NestedNamespace\'',
				'$namespace',
			],
			[
				'\'BinaryOperations\\\\NestedNamespace\\\\Foo\'',
				'$class',
			],
			[
				'\'BinaryOperations\\\\NestedNamespace\\\\Foo::doFoo\'',
				'$method',
			],
			[
				'\'doFoo\'',
				'$function',
			],
			[
				'1',
				'min([1, 2, 3])',
			],
			[
				'array{1, 2, 3}',
				'min([1, 2, 3], [4, 5, 5])',
			],
			[
				'1',
				'min(...[1, 2, 3])',
			],
			[
				'1',
				'min(...[2, 3, 4], ...[5, 1, 8])',
			],
			[
				'0',
				'min(0, ...[1, 2, 3])',
			],
			[
				'array{5, 6, 9}',
				'max([1, 10, 8], [5, 6, 9])',
			],
			[
				'array{1, 1, 1, 1}',
				'max(array(2, 2, 2), array(1, 1, 1, 1))',
			],
			[
				'array<int>',
				'max($arrayOfUnknownIntegers, $arrayOfUnknownIntegers)',
			],
			[
				'array{1, 1, 1, 1}',
				'max(array(2, 2, 2), 5, array(1, 1, 1, 1))',
			],
			[
				'array{int, int, int}',
				'max($arrayOfIntegers, 5)',
			],
			[
				'array<int>',
				'max($arrayOfUnknownIntegers, 5)',
			],
			[
				'array<int>|int', // could be array<int>
				'max($arrayOfUnknownIntegers, $integer, $arrayOfUnknownIntegers)',
			],
			[
				'array<int>',
				'max($arrayOfUnknownIntegers, $conditionalInt)',
			],
			[
				'5',
				'min($arrayOfIntegers, 5)',
			],
			[
				'5',
				'min($arrayOfUnknownIntegers, 5)',
			],
			[
				'1|2',
				'min($arrayOfUnknownIntegers, $conditionalInt)',
			],
			[
				'5',
				'min(array(2, 2, 2), 5, array(1, 1, 1, 1))',
			],
			[
				'1.1',
				'min(...[1.1, 2.2, 3.3])',
			],
			[
				'1.1',
				'min(...[1.1, 2, 3])',
			],
			[
				'3',
				'max(...[1, 2, 3])',
			],
			[
				'3.3',
				'max(...[1.1, 2.2, 3.3])',
			],
			[
				'1',
				'min(1, 2, 3)',
			],
			[
				'3',
				'max(1, 2, 3)',
			],
			[
				'1.1',
				'min(1.1, 2.2, 3.3)',
			],
			[
				'3.3',
				'max(1.1, 2.2, 3.3)',
			],
			[
				'1',
				'min(1, 1)',
			],
			[
				'*ERROR*',
				'min(1)',
			],
			[
				'int|string',
				'min($integer, $string)',
			],
			[
				'int|string',
				'min([$integer, $string])',
			],
			[
				'int|string',
				'min(...[$integer, $string])',
			],
			[
				'\'a\'',
				'min(\'a\', \'b\')',
			],
			[
				'DateTimeImmutable',
				'max(new \DateTimeImmutable("today"), new \DateTimeImmutable("tomorrow"))',
			],
			[
				'1',
				'min(1, 2.2, 3.3)',
			],
			[
				'non-falsy-string',
				'"Hello $world"',
			],
			[
				'non-falsy-string',
				'$string .= "str"',
			],
			[
				'int',
				'$integer <<= 2.2',
			],
			[
				'int',
				'$float >>= 2.2',
			],
			[
				'3',
				'count($arrayOfIntegers)',
			],
			[
				'3',
				'count($arrayOfIntegers, \COUNT_RECURSIVE)',
			],
			[
				'3',
				'count($arrayOfIntegers, 5)',
			],
			[
				'6',
				'count($arrayOfIntegers) + count($arrayOfIntegers)',
			],
			[
				'bool',
				'$string === "foo"',
			],
			[
				'true',
				'$fooString === "foo"',
			],
			[
				'bool',
				'$string !== "foo"',
			],
			[
				'false',
				'$fooString !== "foo"',
			],
			[
				'bool',
				'$string == "foo"',
			],
			[
				'bool',
				'$string != "foo"',
			],
			[
				'true',
				'$foo instanceof \BinaryOperations\NestedNamespace\Foo',
			],
			[
				'bool',
				'$foo instanceof Bar',
			],
			[
				'true',
				'isset($foo)',
			],
			[
				'true',
				'isset($foo, $one)',
			],
			[
				'false',
				'isset($null)',
			],
			[
				'false',
				'isset($undefinedVariable)',
			],
			[
				'false',
				'isset($foo, $undefinedVariable)',
			],
			[
				'bool',
				'isset($stringOrNull)',
			],
			[
				'false',
				'isset($stringOrNull, $null)',
			],
			[
				'false',
				'isset($stringOrNull, $undefinedVariable)',
			],
			[
				'bool',
				'isset($foo, $stringOrNull)',
			],
			[
				'bool',
				'isset($foo, $stringOrNull)',
			],
			[
				'true',
				'isset($array[\'0\'])',
			],
			[
				'bool',
				'isset($array[$integer])',
			],
			[
				'false',
				'isset($array[$integer], $array[1000])',
			],
			[
				'false',
				'isset($array[$integer], $null)',
			],
			[
				'bool',
				'isset($array[\'0\'], $array[$integer])',
			],
			[
				'bool',
				'isset($foo, $array[$integer])',
			],
			[
				'false',
				'isset($foo, $array[1000])',
			],
			[
				'false',
				'isset($foo, $array[1000])',
			],
			[
				'false',
				'!isset($foo)',
			],
			[
				'false',
				'empty($foo)',
			],
			[
				'true',
				'!empty($foo)',
			],
			[
				'array{int, int, int}',
				'$arrayOfIntegers + $arrayOfIntegers',
			],
			[
				'array{int, int, int}',
				'$arrayOfIntegers += $arrayOfIntegers',
			],
			[
				'array{1, 1, 1, 1, 1, 2, 3}|array{1, 1, 1, 1, 1}|array{1, 1, 1, 2, 3, 2, 3}|array{1, 1, 1, 2, 3}',
				'$conditionalArray + $unshiftedConditionalArray',
			],
			[
				'array{\'lorem\', stdClass, 1, 1, 1, 2, 3}|array{\'lorem\', stdClass, 1, 1, 1}',
				'$unshiftedConditionalArray + $conditionalArray',
			],
			[
				'array{int, int, int}',
				'$arrayOfIntegers += ["foo"]',
			],
			[
				'*ERROR*',
				'$arrayOfIntegers += "foo"',
			],
			[
				'3',
				'@count($arrayOfIntegers)',
			],
			[
				'array{int, int, int}',
				'$anotherArray = $arrayOfIntegers',
			],
			[
				'1',
				'$one++',
			],
			[
				'1',
				'$one--',
			],
			[
				'2',
				'++$one',
			],
			[
				'0',
				'--$one',
			],
			[
				'*ERROR*',
				'$preIncArray[0]',
			],
			[
				'1',
				'$preIncArray[1]',
			],
			[
				'2',
				'$preIncArray[2]',
			],
			[
				'*ERROR*',
				'$preIncArray[3]',
			],
			[
				'array{1: 1, 2: 2}',
				'$preIncArray',
			],
			[
				'array{0: 1, 2: 3}',
				'$postIncArray',
			],
			[
				'array{0: array{1: array{2: 3}}, 4: array{5: array{6: 7}}}',
				'$anotherPostIncArray',
			],
			[
				'3',
				'count($array)',
			],
			[
				'int<0, max>',
				'count()',
			],
			[
				'int<0, max>',
				'count($appendingToArrayInBranches)',
			],
			[
				'3|5',
				'count($conditionalArray)',
			],
			[
				'2',
				'$array[1]',
			],
			[
				'(float|int)',
				'$integer / $integer',
			],
			[
				'(float|int)',
				'$otherInteger / $integer',
			],
			[
				'(array|float|int)',
				'$mixed + $mixed',
			],
			[
				'(float|int)',
				'$mixed - $mixed',
			],
			[
				'array',
				'$mixed + []',
			],
			[
				'array|int',
				'$intOrArray + $intOrArray',
			],
			[
				'float|int',
				'$intOrFloat + $intOrFloat',
			],
			[
				'array|float',
				'$floatOrArray + $floatOrArray',
			],
			[
				'array|bool|float|int|string',
				'$plusable + $plusable',
			],
			[
				'array',
				'$mixedNoFloat + []',
			],
			[
				'(float|int)',
				'$mixedNoFloat + 5',
			],
			[
				'(float|int)',
				'$mixedNoInt + 5',
			],
			[
				'*ERROR*',
				'$mixedNoArray + []',
			],
			[
				'*ERROR*',
				'$mixedNoArrayOrInt + []',
			],
			[
				'*ERROR*',
				'$integer + []',
			],
			[
				'124',
				'1 + "123"',
			],
			[
				'124.2',
				'1 + "123.2"',
			],
			[
				'*ERROR*',
				'1 + $string',
			],
			[
				'*ERROR*',
				'1 + "blabla"',
			],
			[
				'array{1, 2, 3}',
				'[1, 2, 3] + [4, 5, 6]',
			],
			[
				'non-empty-array<int>',
				'$arrayOfUnknownIntegers + [1, 2, 3]',
			],
			[
				'(float|int)',
				'$sumWithStaticConst',
			],
			[
				'(float|int)',
				'$severalSumWithStaticConst1',
			],
			[
				'(float|int)',
				'$severalSumWithStaticConst2',
			],
			[
				'(float|int)',
				'$severalSumWithStaticConst3',
			],
			[
				'1',
				'5 & 3',
			],
			[
				'int<0, 3>',
				'$integer & 3',
			],
			[
				'int<0, 7>',
				'7 & $integer',
			],
			[
				'int',
				'$integer & $integer',
			],
			[
				'\'x\'',
				'"x" & "y"',
			],
			[
				'string',
				'$string & "x"',
			],
			[
				'*ERROR*',
				'"bla" & 3',
			],
			[
				'1',
				'"5" & 3',
			],
			[
				'7',
				'5 | 3',
			],
			[
				'int',
				'$integer | 3',
			],
			[
				'\'y\'',
				'"x" | "y"',
			],
			[
				'string',
				'$string | "x"',
			],
			[
				'*ERROR*',
				'"bla" | 3',
			],
			[
				'7',
				'"5" | 3',
			],
			[
				'6',
				'5 ^ 3',
			],
			[
				'int',
				'$integer ^ 3',
			],
			[
				'"\001"',
				'"x" ^ "y"',
			],
			[
				'string',
				'$string ^ "x"',
			],
			[
				'*ERROR*',
				'"bla" ^ 3',
			],
			[
				'6',
				'"5" ^ 3',
			],
			[
				'int<0, 3>',
				'$integer &= 3',
			],
			[
				'*ERROR*',
				'$string &= 3',
			],
			[
				'string',
				'$string &= "x"',
			],
			[
				'int',
				'$integer |= 3',
			],
			[
				'*ERROR*',
				'$string |= 3',
			],
			[
				'string',
				'$string |= "x"',
			],
			[
				'int',
				'$integer ^= 3',
			],
			[
				'*ERROR*',
				'$string ^= 3',
			],
			[
				'string',
				'$string ^= "x"',
			],
			[
				'\'f\'',
				'$fooString[0]',
			],
			[
				'*ERROR*',
				'$fooString[4]',
			],
			[
				"''|'f'|'o'",
				'$fooString[$integer]',
			],
			[
				'\'foo   bar\'',
				'$foobarString',
			],
			[
				'\'foo bar\'',
				'"$fooString bar"',
			],
			[
				'non-falsy-string',
				'"$std bar"',
			],
			[
				'non-empty-array<\'foo\'|int|stdClass>',
				'$arrToPush',
			],
			[
				'non-empty-array<\'foo\'|int|stdClass>',
				'$arrToPush2',
			],
			[
				'array{0: \'lorem\', 1: 5, foo: stdClass, 2: \'test\'}',
				'$arrToUnshift',
			],
			[
				'non-empty-array<\'lorem\'|int|stdClass>',
				'$arrToUnshift2',
			],
			[
				'array{\'lorem\', stdClass, 1, 1, 1, 2, 3}|array{\'lorem\', stdClass, 1, 1, 1}',
				'$unshiftedConditionalArray',
			],
			[
				'array{dirname?: string, basename: string, extension?: string, filename: string}',
				'pathinfo($string)',
			],
			[
				'string',
				'pathinfo($string, PATHINFO_DIRNAME)',
			],
			[
				'string',
				'$string++',
			],
			[
				'string',
				'$string--',
			],
			[
				'(float|int|string)',
				'++$string',
			],
			[
				'(float|int|string)',
				'--$string',
			],
			[
				'(float|int|string)',
				'$incrementedString',
			],
			[
				'(float|int|string)',
				'$decrementedString',
			],
			[
				'\'foo\'',
				'$fooString++',
			],
			[
				'\'foo\'',
				'$fooString--',
			],
			[
				'\'fop\'',
				'++$fooString',
			],
			[
				'\'fon\'',
				'--$fooString',
			],
			[
				'\'fop\'',
				'$incrementedFooString',
			],
			[
				'\'fon\'',
				'$decrementedFooString',
			],
			[
				"'barbar'|'barfoo'|'foobar'|'foofoo'",
				'$conditionalString . $conditionalString',
			],
			[
				"'baripsum'|'barlorem'|'fooipsum'|'foolorem'",
				'$conditionalString . $anotherConditionalString',
			],
			[
				"'ipsumbar'|'ipsumfoo'|'lorembar'|'loremfoo'",
				'$anotherConditionalString . $conditionalString',
			],
			[
				'6|8',
				'count($conditionalArray) + count($array)',
			],
			[
				'bool',
				'is_numeric($string)',
			],
			[
				'false',
				'is_numeric($fooString)',
			],
			[
				'bool',
				'is_int($mixed)',
			],
			[
				'true',
				'is_int($integer)',
			],
			[
				'false',
				'is_int($string)',
			],
			[
				'bool',
				'in_array(\'foo\', [\'foo\', \'bar\'])',
			],
			[
				'true',
				'in_array(\'foo\', [\'foo\', \'bar\'], true)',
			],
			[
				'false',
				'in_array(\'baz\', [\'foo\', \'bar\'], true)',
			],
			[
				'array{2, 3}',
				'$arrToShift',
			],
			[
				'array{1, 2}',
				'$arrToPop',
			],
			[
				'class-string<static(BinaryOperations\NestedNamespace\Foo)>',
				'static::class',
			],
			[
				'\'NonexistentClass\'',
				'NonexistentClass::class',
			],
			[
				'class-string',
				'parent::class',
			],
			[
				'true',
				'array_key_exists(0, $array)',
			],
			[
				'false',
				'array_key_exists(3, $array)',
			],
			[
				'bool',
				'array_key_exists(3, $conditionalArray)',
			],
			[
				'bool',
				'array_key_exists(\'foo\', $generalArray)',
			],
			[
				'string',
				'sprintf($string, $string, 1)',
			],
			[
				'\'foo bar\'',
				"sprintf('%s %s', 'foo', 'bar')",
			],
			[
				'array{}|array{\'password\'}|array{0: \'username\', 1?: \'password\'}',
				'$coalesceArray',
			],
			[
				'array{1, 2, 3}',
				'$arrayToBeUnset',
			],
			[
				'array{1, 2, 3}',
				'$arrayToBeUnset2',
			],
			[
				'array{0?: 1, 1?: 2, 2?: 3}',
				'$arrayToBeUnset3',
			],
			[
				'array{0?: 1, 1?: 2, 2?: 3}',
				'$arrayToBeUnset4',
			],
			[
				'array',
				'$shiftedNonEmptyArray',
			],
			[
				'non-empty-array',
				'$unshiftedArray',
			],
			[
				'array',
				'$poppedNonEmptyArray',
			],
			[
				'non-empty-array',
				'$pushedArray',
			],
			[
				'string|false',
				'$simpleXMLReturningXML',
			],
			[
				'non-falsy-string',
				'$xmlString',
			],
			[
				'bool',
				'$simpleXMLWritingXML',
			],
			[
				'array<SimpleXMLElement>|null',
				'$simpleXMLRightXpath',
			],
			[
				'array<SimpleXMLElement>|false|null',
				'$simpleXMLWrongXpath',
			],
			[
				'array<SimpleXMLElement>|false|null',
				'$simpleXMLUnknownXpath',
			],
			[
				'array<SimpleXMLElement>|false|null',
				'$namespacedXpath',
			],
		];
	}

	#[DataProvider('dataBinaryOperations')]
	public function testBinaryOperations(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/binary.php',
			$description,
			$expression,
		);
	}

	public static function dataVarStatementAnnotation(): array
	{
		return [
			[
				'VarStatementAnnotation\Foo',
				'$object',
			],
		];
	}

	#[DataProvider('dataVarStatementAnnotation')]
	public function testVarStatementAnnotation(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/var-stmt-annotation.php',
			$description,
			$expression,
		);
	}

	public static function dataCloneOperators(): array
	{
		return [
			[
				'CloneOperators\Foo',
				'clone $fooObject',
			],
		];
	}

	#[DataProvider('dataCloneOperators')]
	public function testCloneOperators(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/clone.php',
			$description,
			$expression,
		);
	}

	public static function dataLiteralArrays(): array
	{
		return [
			[
				'0',
				'$integers[0]',
			],
			[
				'1',
				'$integers[1]',
			],
			[
				'\'foo\'',
				'$strings[0]',
			],
			[
				'*ERROR*',
				'$emptyArray[0]',
			],
			[
				'0',
				'$mixedArray[0]',
			],
			[
				'true',
				'$integers[0] >= $integers[1] - 1',
			],
			[
				'array{foo: array{foo: array{foo: \'bar\'}}, bar: array{}, baz: array{lorem: array{}}}',
				'$nestedArray',
			],
			[
				'0',
				'$integers[\'0\']',
			],
		];
	}

	#[DataProvider('dataLiteralArrays')]
	public function testLiteralArrays(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/literal-arrays.php',
			$description,
			$expression,
		);
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

	public static function dataStringArrayAccess(): array
	{
		return [
			[
				'*ERROR*',
				'$stringFalse',
			],
			[
				'*ERROR*',
				'$stringObject',
			],
			[
				'*ERROR*',
				'$stringFloat',
			],
			[
				'*ERROR*',
				'$stringString',
			],
			[
				'*ERROR*',
				'$stringArray',
			],
		];
	}

	#[DataProvider('dataStringArrayAccess')]
	public function testStringArrayAccess(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/string-array-access.php',
			$description,
			$expression,
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

	public static function dataTypeFromFunctionFunctionPhpDocs(): array
	{
		return [
			[
				'MethodPhpDocsNamespace\Foo',
				'$fooFunctionResult',
			],
			[
				'MethodPhpDocsNamespace\Bar',
				'$barFunctionResult',
			],
		];
	}

	#[DataProvider('dataTypeFromFunctionPhpDocs')]
	#[DataProvider('dataTypeFromFunctionFunctionPhpDocs')]
	public function testTypeFromFunctionPhpDocs(
		string $description,
		string $expression,
	): void
	{
		require_once __DIR__ . '/data/functionPhpDocs.php';
		$this->assertTypes(
			__DIR__ . '/data/functionPhpDocs.php',
			$description,
			$expression,
		);
	}

	public static function dataTypeFromFunctionPrefixedPhpDocs(): array
	{
		return [
			[
				'MethodPhpDocsNamespace\Foo',
				'$fooFunctionResult',
			],
		];
	}

	#[DataProvider('dataTypeFromFunctionPhpDocs')]
	#[DataProvider('dataTypeFromFunctionPrefixedPhpDocs')]
	public function testTypeFromFunctionPhpDocsPsalmPrefix(
		string $description,
		string $expression,
	): void
	{
		require_once __DIR__ . '/data/functionPhpDocs-psalmPrefix.php';
		$this->assertTypes(
			__DIR__ . '/data/functionPhpDocs-psalmPrefix.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataTypeFromFunctionPhpDocs')]
	#[DataProvider('dataTypeFromFunctionPrefixedPhpDocs')]
	public function testTypeFromFunctionPhpDocsPhpstanPrefix(
		string $description,
		string $expression,
	): void
	{
		require_once __DIR__ . '/data/functionPhpDocs-phpstanPrefix.php';
		$this->assertTypes(
			__DIR__ . '/data/functionPhpDocs-phpstanPrefix.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataTypeFromFunctionPhpDocs')]
	#[DataProvider('dataTypeFromFunctionPrefixedPhpDocs')]
	public function testTypeFromFunctionPhpDocsPhanPrefix(
		string $description,
		string $expression,
	): void
	{
		require_once __DIR__ . '/data/functionPhpDocs-phanPrefix.php';
		$this->assertTypes(
			__DIR__ . '/data/functionPhpDocs-phanPrefix.php',
			$description,
			$expression,
		);
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
	public function testTypeFromMethodPhpDocs(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/methodPhpDocs.php',
			$description,
			$expression,
		);
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

	public static function dataTypeFromTraitPhpDocsInSameFile(): array
	{
		return [
			[
				'string',
				'$this->getFoo()',
			],
		];
	}

	#[DataProvider('dataTypeFromTraitPhpDocsInSameFile')]
	public function testTypeFromTraitPhpDocsInSameFile(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/methodPhpDocs-traitInSameFileAsClass.php',
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

	public static function dataSwitchInstanceOf(): array
	{
		return [
			[
				'*ERROR*',
				'$foo',
			],
			[
				'*ERROR*',
				'$bar',
			],
			[
				'SwitchInstanceOf\Baz',
				'$baz',
			],
		];
	}

	#[DataProvider('dataSwitchInstanceOf')]
	public function testSwitchInstanceof(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/switch-instanceof.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataSwitchInstanceOf')]
	public function testSwitchInstanceofTruthy(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/switch-instanceof-truthy.php',
			$description,
			$expression,
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

	public static function dataSwitchInstanceOfFallthrough(): array
	{
		return [
			[
				'SwitchInstanceOfFallthrough\A|SwitchInstanceOfFallthrough\B',
				'$object',
			],
		];
	}

	#[DataProvider('dataSwitchInstanceOfFallthrough')]
	public function testSwitchInstanceOfFallthrough(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/switch-instanceof-fallthrough.php',
			$description,
			$expression,
		);
	}

	public static function dataSwitchTypeElimination(): array
	{
		return [
			[
				'string',
				'$stringOrInt',
			],
		];
	}

	#[DataProvider('dataSwitchTypeElimination')]
	public function testSwitchTypeElimination(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/switch-type-elimination.php',
			$description,
			$expression,
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

	public static function dataNegatedInstanceof(): array
	{
		return [
			[
				'NegatedInstanceOf\Foo',
				'$foo',
			],
			[
				'NegatedInstanceOf\Bar',
				'$bar',
			],
			[
				'mixed',
				'$lorem',
			],
			[
				'mixed~NegatedInstanceOf\Dolor',
				'$dolor',
			],
			[
				'mixed~NegatedInstanceOf\Sit',
				'$sit',
			],
			[
				'mixed',
				'$mixedFoo',
			],
			[
				'mixed',
				'$mixedBar',
			],
			[
				'NegatedInstanceOf\Foo',
				'$self',
			],
			[
				'static(NegatedInstanceOf\Foo)',
				'$static',
			],
			[
				'NegatedInstanceOf\Foo',
				'$anotherFoo',
			],
			[
				'NegatedInstanceOf\Bar&NegatedInstanceOf\Foo',
				'$fooAndBar',
			],
		];
	}

	#[DataProvider('dataNegatedInstanceof')]
	public function testNegatedInstanceof(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/negated-instanceof.php',
			$description,
			$expression,
		);
	}

	public static function dataAnonymousFunction(): array
	{
		return [
			[
				'string',
				'$str',
			],
			[
				PHP_VERSION_ID < 80000 ? 'list' : 'array<int|string, mixed>',
				'$arr',
			],
			[
				'1',
				'$integer',
			],
			[
				'*ERROR*',
				'$bar',
			],
		];
	}

	#[DataProvider('dataAnonymousFunction')]
	public function testAnonymousFunction(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/anonymous-function.php',
			$description,
			$expression,
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

	public static function dataArrayFunctions(): array
	{
		return [
			[
				'1',
				'$integers[0]',
			],
			[
				'array{string, string, string}',
				'$mappedStrings',
			],
			[
				'string',
				'$mappedStrings[0]',
			],
			[
				'1|2|3',
				'$filteredIntegers[0]',
			],
			[
				'*ERROR*',
				'$filteredMixed[0]',
			],
			[
				'123',
				'$filteredMixed[1]',
			],
			[
				'non-empty-array<0|1|2, 1|2|3>',
				'$uniquedIntegers',
			],
			[
				'1|2|3',
				'$uniquedIntegers[1]',
			],
			[
				'string',
				'$reducedIntegersToString',
			],
			[
				'string|null',
				'$reducedIntegersToStringWithNull',
			],
			[
				'string',
				'$reducedIntegersToStringAnother',
			],
			[
				'null',
				'$reducedToNull',
			],
			[
				'1|string',
				'$reducedIntegersToStringWithInt',
			],
			[
				'1',
				'$reducedToInt',
			],
			[
				'array{1, 2, 3}',
				'array_change_key_case($integers)',
			],
			[
				PHP_VERSION_ID < 80000 ? 'array|false' : 'array',
				'array_combine($array, $array2)',
			],
			[
				'array{1: 2}',
				'array_combine([1], [2])',
			],
			[
				PHP_VERSION_ID < 80000 ? 'false' : '*NEVER*',
				'array_combine([1, 2], [3])',
			],
			[
				'array{a: \'d\', b: \'e\', c: \'f\'}',
				'array_combine([\'a\', \'b\', \'c\'], [\'d\', \'e\', \'f\'])',
			],
			[
				PHP_VERSION_ID < 80000 ? 'array<1|2|3, mixed>|false' : 'array<1|2|3, mixed>',
				'array_combine([1, 2, 3], $array)',
			],
			[
				PHP_VERSION_ID < 80000 ? 'array<1|2|3>|false' : 'array<1|2|3>',
				'array_combine($array, [1, 2, 3])',
			],
			[
				'array',
				'array_combine($array, $array)',
			],
			[
				'array<string, string>',
				'array_combine($stringArray, $stringArray)',
			],
			[
				'array<0|1|2, 1|2|3>',
				'array_diff_assoc($integers, [])',
			],
			[
				'array<0|1|2, 1|2|3>',
				'array_diff_key($integers, [])',
			],
			[
				'array<0|1|2, 1|2|3>',
				'array_diff_uassoc($integers, [])',
			],
			[
				'array<0|1|2, 1|2|3>',
				'array_diff_ukey($integers, [])',
			],
			[
				'array<0|1|2, 1|2|3>',
				'array_diff($integers, [])',
			],
			[
				'array<0|1|2, 1|2|3>',
				'array_udiff_assoc($integers, [])',
			],
			[
				'array<0|1|2, 1|2|3>',
				'array_udiff_uassoc($integers, [])',
			],
			[
				'array<0|1|2, 1|2|3>',
				'array_udiff($integers, [])',
			],
			[
				'array<0|1|2, 1|2|3>',
				'array_intersect_assoc($integers, [])',
			],
			[
				'array{}',
				'array_intersect_key($integers, [])',
			],
			[
				'array{1, 2, 3}|array{4, 5, 6}',
				'array_intersect_key(...[$integers, [4, 5, 6]])',
			],
			[
				'array<int>',
				'array_intersect_key(...$generalIntegersInAnotherArray)',
			],
			[
				'array<0|1|2, 1|2|3>',
				'array_intersect_uassoc($integers, [])',
			],
			[
				'array<0|1|2, 1|2|3>',
				'array_intersect_ukey($integers, [])',
			],
			[
				'array<0|1|2, 1|2|3>',
				'array_intersect($integers, [])',
			],
			[
				'array<0|1|2, 1|2|3>',
				'array_uintersect_assoc($integers, [])',
			],
			[
				'array<0|1|2, 1|2|3>',
				'array_uintersect_uassoc($integers, [])',
			],
			[
				'array<0|1|2, 1|2|3>',
				'array_uintersect($integers, [])',
			],
			[
				'array{1, 1, 1, 1, 1}',
				'$filledIntegers',
			],
			[
				'array{}',
				'$emptyFilled',
			],
			[
				'array{1}',
				'$filledIntegersWithKeys',
			],
			[
				'non-empty-list<\'foo\'>',
				'$filledNonEmptyArray',
			],
			[
				PHP_VERSION_ID < 80000 ? 'false' : '*NEVER*',
				'$filledAlwaysFalse',
			],
			[
				PHP_VERSION_ID < 80000 ? 'false' : '*NEVER*',
				'$filledNegativeConstAlwaysFalse',
			],
			[
				PHP_VERSION_ID < 80000 ? 'list<1>|false' : 'list<1>',
				'$filledByMaybeNegativeRange',
			],
			[
				'non-empty-list<1>',
				'$filledByPositiveRange',
			],
			[
				'array{1, 2}',
				'array_keys($integerKeys)',
			],
			[
				'array{\'foo\', \'bar\'}',
				'array_keys($stringKeys)',
			],
			[
				'array{\'foo\', 1}',
				'array_keys($stringOrIntegerKeys)',
			],
			[
				'list<string>',
				'array_keys($generalStringKeys)',
			],
			[
				'array{\'foo\', stdClass}',
				'array_values($integerKeys)',
			],
			[
				'list<int>',
				'array_values($generalStringKeys)',
			],
			[
				'array{foo: stdClass, 0: stdClass}',
				'array_merge($stringOrIntegerKeys)',
			],
			[
				'array<int|string, DateTimeImmutable|int>',
				'array_merge($generalStringKeys, $generalDateTimeValues)',
			],
			[
				'non-empty-array<1|string, int|stdClass>',
				'array_merge($generalStringKeys, $stringOrIntegerKeys)',
			],
			[
				'non-empty-array<1|string, int|stdClass>',
				'array_merge($stringOrIntegerKeys, $generalStringKeys)',
			],
			[
				'array{foo: stdClass, bar: stdClass, 0: stdClass}',
				'array_merge($stringKeys, $stringOrIntegerKeys)',
			],
			[
				"array{foo: 'foo', 0: stdClass, bar: stdClass}",
				'array_merge($stringOrIntegerKeys, $stringKeys)',
			],
			[
				'array{foo: 1, bar: 2, 0: 2, 1: 3}',
				"array_merge(['foo' => 4, 'bar' => 5], ...[['foo' => 1, 'bar' => 2], [2, 3]])",
			],
			[
				'array{foo: 1, foo2: stdClass}',
				'array_merge([\'foo\' => new stdClass()], ...[[\'foo2\' => new stdClass()], [\'foo\' => 1]])',
			],

			[
				'array{foo: 1, foo2: stdClass}',
				'array_merge([\'foo\' => new stdClass()], ...[[\'foo2\' => new stdClass()], [\'foo\' => 1]])',
			],
			[
				"array{color: 'green', 0: 2, 1: 4, 2: 'a', 3: 'b', shape: 'trapezoid', 4: 4}",
				'array_merge(array("color" => "red", 2, 4), array("a", "b", "color" => "green", "shape" => "trapezoid", 4))',
			],
			[
				'array<int|string, DateTimeImmutable|int>',
				'array_merge(...[$generalStringKeys, $generalDateTimeValues])',
			],
			[
				'array<int>',
				'$mergedInts',
			],
			[
				'array{5: \'banana\', 6: \'banana\', 7: \'banana\', 8: \'banana\', 9: \'banana\', 10: \'banana\'}',
				'array_fill(5, 6, \'banana\')',
			],
			[
				'non-empty-list<\'apple\'>',
				'array_fill(0, 101, \'apple\')',
			],
			[
				'array{-2: \'pear\', 0: \'pear\', 1: \'pear\', 2: \'pear\'}',
				'array_fill(-2, 4, \'pear\')',
			],
			[
				'non-empty-array<int, stdClass>',
				'array_fill($integer, 2, new \stdClass())',
			],
			[
				PHP_VERSION_ID < 80000 ? 'array<int, stdClass>|false' : 'array<int, stdClass>',
				'array_fill(2, $integer, new \stdClass())',
			],
			[
				'array<int, stdClass>',
				'array_fill_keys($generalStringKeys, new \stdClass())',
			],
			[
				'array{foo: \'banana\', 5: \'banana\', 10: \'banana\', bar: \'banana\'}',
				'array_fill_keys([\'foo\', 5, 10, \'bar\'], \'banana\')',
			],
			[
				'array<string, stdClass>',
				'$mappedStringKeys',
			],
			[
				'array<string, mixed>',
				'$mappedStringKeysWithUnknownClosureType',
			],
			[
				'array<string>',
				'$mappedWrongArray',
			],
			[
				'array',
				'$unknownArray',
			],
			[
				'array{foo: \'banana\', bar: \'banana\', baz: \'banana\', lorem: \'banana\'}|array{foo: \'banana\', bar: \'banana\'}',
				'array_fill_keys($conditionalArray, \'banana\')',
			],
			[
				'array{foo: stdClass, bar: stdClass, baz: stdClass, lorem: stdClass}|array{foo: stdClass, bar: stdClass}',
				'array_map(function (): \stdClass {}, $conditionalKeysArray)',
			],
			[
				'\'foo\'|stdClass',
				'array_pop($stringKeys)',
			],
			[
				'non-empty-array<stdClass>&hasOffsetValue(\'baz\', stdClass)',
				'$stdClassesWithIsset',
			],
			[
				'stdClass',
				'array_pop($stdClassesWithIsset)',
			],
			[
				'\'foo\'|stdClass',
				'array_shift($stringKeys)',
			],
			[
				'int|null',
				'array_pop($generalStringKeys)',
			],
			[
				'int|null',
				'array_shift($generalStringKeys)',
			],
			[
				'null',
				'array_pop([])',
			],
			[
				'null',
				'array_shift([])',
			],
			[
				'array{null, \'\', 1}',
				'$constantArrayWithFalseyValues',
			],
			[
				'array{2: 1}',
				'$constantTruthyValues',
			],
			[
				'array<int, false|null>',
				'$falsey',
			],
			[
				'array{}',
				'array_filter($falsey)',
			],
			[
				'array<int, bool|null>',
				'$withFalsey',
			],
			[
				'array<int, true>',
				'array_filter($withFalsey)',
			],
			[
				'array{a: 1}',
				'array_filter($union)',
			],
			[
				'array{0?: true, 1?: int<min, -1>|int<1, max>}',
				'array_filter($withPossiblyFalsey)',
			],
			[
				PHP_VERSION_ID < 80000 ? '(array|null)' : 'array',
				'array_filter($mixed)',
			],
			[
				'1|\'foo\'|false',
				'array_search(new stdClass, $stringOrIntegerKeys, true)',
			],
			[
				'\'foo\'',
				'array_search(\'foo\', $stringKeys, true)',
			],
			[
				'int|false',
				'array_search(new DateTimeImmutable(), $generalDateTimeValues, true)',
			],
			[
				'string|false',
				'array_search(9, $generalStringKeys, true)',
			],
			[
				'string|false',
				'array_search(9, $generalStringKeys, false)',
			],
			[
				'string|false',
				'array_search(9, $generalStringKeys)',
			],
			[
				PHP_VERSION_ID < 80000 ? 'null' : '*NEVER*',
				'array_search(999, $integer, true)',
			],
			[
				'false',
				'array_search(new stdClass, $generalStringKeys, true)',
			],
			[
				'int|string|false',
				'array_search($mixed, $array, true)',
			],
			[
				'int|string|false',
				'array_search($mixed, $array, false)',
			],
			[
				'\'a\'|\'b\'|false',
				'array_search($string, [\'a\' => \'A\', \'b\' => \'B\'], true)',
			],
			[
				'false',
				'array_search($integer, [\'a\' => \'A\', \'b\' => \'B\'], true)',
			],
			[
				'\'foo\'|false',
				'array_search($generalIntegerOrString, $stringKeys, true)',
			],
			[
				'int|false',
				'array_search($generalIntegerOrString, $generalArrayOfIntegersOrStrings, true)',
			],
			[
				'int|false',
				'array_search($generalIntegerOrString, $clonedConditionalArray, true)',
			],
			[
				'int|string|false',
				'array_search($generalIntegerOrString, $generalIntegerOrStringKeys, false)',
			],
			[
				'false',
				'array_search(\'id\', $generalIntegerOrStringKeys, true)',
			],
			[
				'int|string|false',
				'array_search(\'id\', $generalIntegerOrStringKeysMixedValues, true)',
			],
			[
				'*ERROR*',
				'array_search(\'id\', doFoo() ? $generalIntegerOrStringKeys : false, true)',
			],
			[
				'*ERROR*',
				'array_search(\'id\', doFoo() ? [] : false, true)',
			],
			[
				PHP_VERSION_ID < 80000 ? 'null' : '*NEVER*',
				'array_search(\'id\', false, true)',
			],
			[
				PHP_VERSION_ID < 80000 ? 'null' : '*NEVER*',
				'array_search(\'id\', false)',
			],
			[
				'int|string|false',
				'array_search(\'id\', $thisDoesNotExistAndIsMixed, true)',
			],
			[
				'int|string|false',
				'array_search(\'id\', doFoo() ? $thisDoesNotExistAndIsMixedInUnion : false, true)',
			],
			[
				'int|string|false',
				'array_search(1, $generalIntegers, true)',
			],
			[
				'int|string|false',
				'array_search(1, $generalIntegers, false)',
			],
			[
				'int|string|false',
				'array_search(1, $generalIntegers)',
			],
			[
				'array<string, int>',
				'array_slice($generalStringKeys, 0)',
			],
			[
				'array<string, int>',
				'array_slice($generalStringKeys, 1)',
			],
			[
				'array<string, int>',
				'array_slice($generalStringKeys, 1, null, true)',
			],
			[
				'array<string, int>',
				'array_slice($generalStringKeys, 1, 2)',
			],
			[
				'array<string, int>',
				'array_slice($generalStringKeys, 1, 2, true)',
			],
			[
				'array<string, int>',
				'array_slice($generalStringKeys, 1, -1)',
			],
			[
				'array<string, int>',
				'array_slice($generalStringKeys, 1, -1, true)',
			],
			[
				'array<string, int>',
				'array_slice($generalStringKeys, -2)',
			],
			[
				'array<string, int>',
				'array_slice($generalStringKeys, -2, 1, true)',
			],
			[
				'array',
				'array_slice($unknownArray, 0)',
			],
			[
				'array',
				'array_slice($unknownArray, 1)',
			],
			[
				'array',
				'array_slice($unknownArray, 1, null, true)',
			],
			[
				'array',
				'array_slice($unknownArray, 1, 2)',
			],
			[
				'array',
				'array_slice($unknownArray, 1, 2, true)',
			],
			[
				'array',
				'array_slice($unknownArray, 1, -1)',
			],
			[
				'array',
				'array_slice($unknownArray, 1, -1, true)',
			],
			[
				'array',
				'array_slice($unknownArray, -2)',
			],
			[
				'array',
				'array_slice($unknownArray, -2, 1, true)',
			],
			[
				'array{0: bool, 1: int, 2: \'\', a: 0}',
				'array_slice($withPossiblyFalsey, 0)',
			],
			[
				'array{0: int, 1: \'\', a: 0}',
				'array_slice($withPossiblyFalsey, 1)',
			],
			[
				'array{1: int, 2: \'\', a: 0}',
				'array_slice($withPossiblyFalsey, 1, null, true)',
			],
			[
				'array{0: \'\', a: 0}',
				'array_slice($withPossiblyFalsey, 2, 3)',
			],
			[
				'array{2: \'\', a: 0}',
				'array_slice($withPossiblyFalsey, 2, 3, true)',
			],
			[
				'array{int, \'\'}',
				'array_slice($withPossiblyFalsey, 1, -1)',
			],
			[
				'array{1: int, 2: \'\'}',
				'array_slice($withPossiblyFalsey, 1, -1, true)',
			],
			[
				'array{0: \'\', a: 0}',
				'array_slice($withPossiblyFalsey, -2, null)',
			],
			[
				'array{2: \'\', a: 0}',
				'array_slice($withPossiblyFalsey, -2, null, true)',
			],
			[
				'array{0: \'\', a: 0}|array{baz: \'qux\'}',
				'array_slice($unionArrays, 1)',
			],
			[
				'array{a: 0}|array{baz: \'qux\'}',
				'array_slice($unionArrays, -1, null, true)',
			],
			[
				'array{0: \'foo\', 1: \'bar\', baz: \'qux\', 2: \'quux\', quuz: \'corge\', 3: \'grault\'}',
				'$slicedOffset',
			],
			[
				'array{4: \'foo\', 1: \'bar\', baz: \'qux\', 0: \'quux\', quuz: \'corge\', 5: \'grault\'}',
				'$slicedOffsetWithKeys',
			],
			[
				'0|1',
				'key($mixedValues)',
			],
			[
				'int|null',
				'key($falsey)',
			],
			[
				'string|null',
				'key($generalStringKeys)',
			],
			[
				'int|string|null',
				'key($generalIntegerOrStringKeysMixedValues)',
			],
			[
				'\'foo\'',
				'$poppedFoo',
			],
			[
				'int',
				'array_rand([1 => 1, 2 => "2"])',
			],
			[
				'string',
				'array_rand(["a" => 1, "b" => "2"])',
			],
			[
				'int|string',
				'array_rand(["a" => 1, 2 => "b"])',
			],
			[
				'int|string',
				'array_rand([1 => 1, 2 => "b", $mixed => $mixed])',
			],
			[
				'int',
				'array_rand([1 => 1, 2 => "b"], 1)',
			],
			[
				'string',
				'array_rand(["a" => 1, "b" => "b"], 1)',
			],
			[
				'int|string',
				'array_rand(["a" => 1, 2 => "b"], 1)',
			],
			[
				'int|string',
				'array_rand([1 => 1, 2 => "b", $mixed => $mixed], 1)',
			],
			[
				'array<int, int>',
				'array_rand([1 => 1, 2 => "b"], 2)',
			],
			[
				'array<int, string>',
				'array_rand(["a" => 1, "b" => "b"], 2)',
			],
			[
				'array<int, int|string>',
				'array_rand(["a" => 1, 2 => "b"], 2)',
			],
			[
				'array<int, int|string>',
				'array_rand([1 => 1, 2 => "2", $mixed => $mixed], 2)',
			],
			[
				'array<int, int>|int',
				'array_rand([1 => 1, 2 => "b"], $mixed)',
			],
			[
				'array<int, string>|string',
				'array_rand(["a" => 1, "b" => "b"], $mixed)',
			],
			[
				'array<int, int|string>|int|string',
				'array_rand(["a" => 1, 2 => "b"], $mixed)',
			],
			[
				'array<int, int|string>|int|string',
				'array_rand([1 => 1, 2 => "b", $mixed => $mixed], $mixed)',
			],
		];
	}

	#[DataProvider('dataArrayFunctions')]
	public function testArrayFunctions(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/array-functions.php',
			$description,
			$expression,
		);
	}

	public static function dataFunctions(): array
	{
		return [
			[
				'string',
				'$microtimeStringWithoutArg',
			],
			[
				'string',
				'$microtimeString',
			],
			[
				'float',
				'$microtimeFloat',
			],
			[
				'float|string',
				'$microtimeDefault',
			],
			[
				'(float|string)',
				'$microtimeBenevolent',
			],
			[
				'-1',
				'$versionCompare1',
			],
			[
				'-1|1',
				'$versionCompare2',
			],
			[
				'-1|0|1',
				'$versionCompare3',
			],
			[
				'-1|0|1',
				'$versionCompare4',
			],
			[
				'true',
				'$versionCompare5',
			],
			[
				'bool',
				'$versionCompare6',
			],
			[
				PHP_VERSION_ID < 80000 ? '(bool|null)' : 'bool',
				'$versionCompare7',
			],
			[
				PHP_VERSION_ID < 80000 ? '(bool|null)' : 'bool',
				'$versionCompare8',
			],
			[
				'string',
				'$mbHttpOutputWithoutEncoding',
			],
			[
				'true',
				'$mbHttpOutputWithValidEncoding',
			],
			[
				'false',
				'$mbHttpOutputWithInvalidEncoding',
			],
			[
				'bool',
				'$mbHttpOutputWithValidAndInvalidEncoding',
			],
			[
				'bool',
				'$mbHttpOutputWithUnknownEncoding',
			],
			[
				'string',
				'$mbRegexEncodingWithoutEncoding',
			],
			[
				'true',
				'$mbRegexEncodingWithValidEncoding',
			],
			[
				'false',
				'$mbRegexEncodingWithInvalidEncoding',
			],
			[
				'bool',
				'$mbRegexEncodingWithValidAndInvalidEncoding',
			],
			[
				'bool',
				'$mbRegexEncodingWithUnknownEncoding',
			],
			[
				'string',
				'$mbInternalEncodingWithoutEncoding',
			],
			[
				'true',
				'$mbInternalEncodingWithValidEncoding',
			],
			[
				'false',
				'$mbInternalEncodingWithInvalidEncoding',
			],
			[
				'bool',
				'$mbInternalEncodingWithValidAndInvalidEncoding',
			],
			[
				'bool',
				'$mbInternalEncodingWithUnknownEncoding',
			],
			[
				'list<non-falsy-string>',
				'$mbEncodingAliasesWithValidEncoding',
			],
			[
				PHP_VERSION_ID < 80000 ? 'false' : '*NEVER*',
				'$mbEncodingAliasesWithInvalidEncoding',
			],
			[
				PHP_VERSION_ID < 80000 ? 'list<non-falsy-string>|false' : 'list<non-falsy-string>',
				'$mbEncodingAliasesWithValidAndInvalidEncoding',
			],
			[
				PHP_VERSION_ID < 80000 ? 'list<non-falsy-string>|false' : 'list<non-falsy-string>',
				'$mbEncodingAliasesWithUnknownEncoding',
			],
			[
				'string',
				'$mbChrWithoutEncoding',
			],
			[
				'string',
				'$mbChrWithValidEncoding',
			],
			[
				PHP_VERSION_ID < 80000 ? 'false' : '*NEVER*',
				'$mbChrWithInvalidEncoding',
			],
			[
				'string|false',
				'$mbChrWithValidAndInvalidEncoding',
			],
			[
				'string|false',
				'$mbChrWithUnknownEncoding',
			],
			[
				'int',
				'$mbOrdWithoutEncoding',
			],
			[
				'int',
				'$mbOrdWithValidEncoding',
			],
			[
				PHP_VERSION_ID < 80000 ? 'false' : '*NEVER*',
				'$mbOrdWithInvalidEncoding',
			],
			[
				'int|false',
				'$mbOrdWithValidAndInvalidEncoding',
			],
			[
				'int|false',
				'$mbOrdWithUnknownEncoding',
			],
			[
				'array{sec: int, usec: int, minuteswest: int, dsttime: int}',
				'$gettimeofdayArrayWithoutArg',
			],
			[
				'array{sec: int, usec: int, minuteswest: int, dsttime: int}',
				'$gettimeofdayArray',
			],
			[
				'float',
				'$gettimeofdayFloat',
			],
			[
				'array{sec: int, usec: int, minuteswest: int, dsttime: int}|float',
				'$gettimeofdayDefault',
			],
			[
				'(array{sec: int, usec: int, minuteswest: int, dsttime: int}|float)',
				'$gettimeofdayBenevolent',
			],
			// parse_url
			[
				'array|int|string|false|null',
				'$parseUrlWithoutParameters',
			],
			[
				'array{scheme: \'http\', host: \'abc.def\'}',
				'$parseUrlConstantUrlWithoutComponent1',
			],
			[
				'array{scheme: \'http\', host: \'def.abc\'}',
				'$parseUrlConstantUrlWithoutComponent2',
			],
			[
				'array{scheme?: lowercase-string, host?: lowercase-string, port?: int<0, 65535>, user?: lowercase-string, pass?: lowercase-string, path?: lowercase-string, query?: lowercase-string, fragment?: lowercase-string}|int<0, 65535>|lowercase-string|false|null',
				'$parseUrlConstantUrlUnknownComponent',
			],
			[
				'null',
				'$parseUrlConstantUrlWithComponentNull',
			],
			[
				"'this-is-fragment'",
				'$parseUrlConstantUrlWithComponentSet',
			],
			[
				'false',
				'$parseUrlConstantUrlWithComponentInvalid',
			],
			[
				'false',
				'$parseUrlStringUrlWithComponentInvalid',
			],
			[
				'int<0, 65535>|false|null',
				'$parseUrlStringUrlWithComponentPort',
			],
			[
				'array{scheme?: string, host?: string, port?: int<0, 65535>, user?: string, pass?: string, path?: string, query?: string, fragment?: string}|false',
				'$parseUrlStringUrlWithoutComponent',
			],
			[
				'array{path: \'abc.def\'}',
				"parse_url('abc.def')",
			],
			[
				'null',
				"parse_url('abc.def', PHP_URL_SCHEME)",
			],
			[
				"'http'",
				"parse_url('http://abc.def', PHP_URL_SCHEME)",
			],
			[
				'array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int, 9: int, 10: int, 11: int, 12: int, dev: int, ino: int, mode: int, nlink: int, uid: int, gid: int, rdev: int, size: int, atime: int, mtime: int, ctime: int, blksize: int, blocks: int}|false',
				'$stat',
			],
			[
				'array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int, 9: int, 10: int, 11: int, 12: int, dev: int, ino: int, mode: int, nlink: int, uid: int, gid: int, rdev: int, size: int, atime: int, mtime: int, ctime: int, blksize: int, blocks: int}|false',
				'$lstat',
			],
			[
				'array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int, 9: int, 10: int, 11: int, 12: int, dev: int, ino: int, mode: int, nlink: int, uid: int, gid: int, rdev: int, size: int, atime: int, mtime: int, ctime: int, blksize: int, blocks: int}|false',
				'$fstat',
			],
			[
				'array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int, 9: int, 10: int, 11: int, 12: int, dev: int, ino: int, mode: int, nlink: int, uid: int, gid: int, rdev: int, size: int, atime: int, mtime: int, ctime: int, blksize: int, blocks: int}',
				'$fileObjectStat',
			],
			[
				'string',
				'$base64DecodeWithoutStrict',
			],
			[
				'string',
				'$base64DecodeWithStrictDisabled',
			],
			[
				'string|false',
				'$base64DecodeWithStrictEnabled',
			],
			[
				'string',
				'$base64DecodeDefault',
			],
			[
				'(string|false)',
				'$base64DecodeBenevolent',
			],
			[
				'*ERROR*',
				'$strWordCountWithoutParameters',
			],
			[
				'*ERROR*',
				'$strWordCountWithTooManyParams',
			],
			[
				'int',
				'$strWordCountStr',
			],
			[
				'int',
				'$strWordCountStrType0',
			],
			[
				'array<int, string>',
				'$strWordCountStrType1',
			],
			[
				'array<int, string>',
				'$strWordCountStrType1Extra',
			],
			[
				'array<int, string>',
				'$strWordCountStrType2',
			],
			[
				'array<int, string>',
				'$strWordCountStrType2Extra',
			],
			[
				'array<int, string>|int|false',
				'$strWordCountStrTypeIndeterminant',
			],
		];
	}

	#[DataProvider('dataFunctions')]
	public function testFunctions(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/functions.php',
			$description,
			$expression,
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

	public static function dataSsh2Functions(): array
	{
		return [
			[
				'array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int, 9: int, 10: int, 11: int, 12: int, dev: int, ino: int, mode: int, nlink: int, uid: int, gid: int, rdev: int, size: int, atime: int, mtime: int, ctime: int, blksize: int, blocks: int}|false',
				'$ssh2SftpStat',
			],
		];
	}

	#[DataProvider('dataSsh2Functions')]
	public function testSsh2Functions(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/ssh2-functions.php',
			$description,
			$expression,
		);
	}

	public static function dataRangeFunction(): array
	{
		return [
			[
				'array{2, 3, 4, 5}',
				'range(2, 5)',
			],
			[
				'array{2, 4}',
				'range(2, 5, 2)',
			],
			[
				'array{2, 0}',
				"range(2, '', 2)",
			],
			[
				PHP_VERSION_ID < 80300 ? 'array{2.0, 3.0, 4.0, 5.0}' : 'array{2, 3, 4, 5}',
				'range(2, 5, 1.0)',
			],
			[
				'array{2.1, 3.1, 4.1}',
				'range(2.1, 5)',
			],
			[
				'list<int>',
				'range(2, 5, $integer)',
			],
			[
				'list<float|int>',
				'range($float, 5, $integer)',
			],
			[
				'list<(float|int|string)>',
				'range($float, $mixed, $integer)',
			],
			[
				'list<(float|int|string)>',
				'range($integer, $mixed)',
			],
			[
				'array{0: 1, 1?: 2}',
				'range(1, doFoo() ? 1 : 2)',
			],
			[
				'array{0: -1, 1: 0, 2: 1, 3?: 2}|array{0: 1, 1?: 2}',
				'range(doFoo() ? -1 : 1, doFoo() ? 1 : 2)',
			],
			[
				'array{3, 2, 1, 0, -1}',
				'range(3, -1)',
			],
			[
				'non-empty-list<int<0, 50>>',
				'range(0, 50)',
			],
		];
	}

	#[DataProvider('dataRangeFunction')]
	public function testRangeFunction(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/range-function.php',
			$description,
			$expression,
		);
	}

	public static function dataSpecifiedTypesUsingIsFunctions(): array
	{
		return [
			[
				'int',
				'$integer',
			],
			[
				'int',
				'$anotherInteger',
			],
			[
				'int',
				'$longInteger',
			],
			[
				'float',
				'$float',
			],
			[
				'float',
				'$doubleFloat',
			],
			[
				'float',
				'$realFloat',
			],
			[
				'null',
				'$null',
			],
			[
				'array<mixed, mixed>',
				'$array',
			],
			[
				'bool',
				'$bool',
			],
			[
				'callable(): mixed',
				'$callable',
			],
			[
				'resource',
				'$resource',
			],
			[
				'int',
				'$yetAnotherInteger',
			],
			[
				'*ERROR*',
				'$mixedInteger',
			],
			[
				'string',
				'$string',
			],
			[
				'object',
				'$object',
			],
			[
				'int',
				'$intOrStdClass',
			],
			[
				'Foo',
				'$foo',
			],
			[
				'Foo',
				'$anotherFoo',
			],
			[
				'class-string<Foo>|Foo',
				'$subClassOfFoo',
			],
			[
				'Foo',
				'$subClassOfFoo2',
			],
			[
				'class-string|object',
				'$subClassOfFoo3',
			],
			[
				'object',
				'$subClassOfFoo4',
			],
			[
				'class-string<Foo>|Foo',
				'$subClassOfFoo5',
			],
			[
				'class-string|object',
				'$subClassOfFoo6',
			],
			[
				'Foo',
				'$subClassOfFoo7',
			],
			[
				'object',
				'$subClassOfFoo8',
			],
			[
				'object',
				'$subClassOfFoo9',
			],
			[
				'object',
				'$subClassOfFoo10',
			],
			[
				'Foo',
				'$subClassOfFoo11',
			],
			[
				'Foo',
				'$subClassOfFoo12',
			],
			[
				'Foo',
				'$subClassOfFoo13',
			],
			[
				'object',
				'$subClassOfFoo14',
			],
			[
				'class-string<Foo>|Foo',
				'$subClassOfFoo15',
			],
			[
				'Bar|class-string|Foo',
				'$subClassOfFoo16',
			],
		];
	}

	#[DataProvider('dataSpecifiedTypesUsingIsFunctions')]
	public function testSpecifiedTypesUsingIsFunctions(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/specifiedTypesUsingIsFunctions.php',
			$description,
			$expression,
		);
	}

	public static function dataIterable(): array
	{
		return [
			[
				'iterable',
				'$this->iterableProperty',
			],
			[
				'iterable',
				'$iterableSpecifiedLater',
			],
			[
				'iterable',
				'$iterableWithoutTypehint',
			],
			[
				'mixed',
				'$iterableWithoutTypehint[0]',
			],
			[
				'iterable',
				'$iterableWithIterableTypehint',
			],
			[
				'mixed',
				'$iterableWithIterableTypehint[0]',
			],
			[
				'mixed',
				'$mixed',
			],
			[
				'iterable<Iterables\Bar>',
				'$iterableWithConcreteTypehint',
			],
			[
				'mixed',
				'$iterableWithConcreteTypehint[0]',
			],
			[
				'Iterables\Bar',
				'$bar',
			],
			[
				'iterable',
				'$this->doBar()',
			],
			[
				'iterable<Iterables\Baz>',
				'$this->doBaz()',
			],
			[
				'Iterables\Baz',
				'$baz',
			],
			[
				'array',
				'$arrayWithIterableTypehint',
			],
			[
				'mixed',
				'$arrayWithIterableTypehint[0]',
			],
			[
				'iterable<Iterables\Bar>&Iterables\Collection',
				'$unionIterableType',
			],
			[
				'Iterables\Bar',
				'$unionBar',
			],
			[
				'non-empty-array',
				'$mixedUnionIterableType',
			],
			[
				'iterable<Iterables\Bar>&Iterables\Collection',
				'$unionIterableIterableType',
			],
			[
				'mixed',
				'$mixedBar',
			],
			[
				'Iterables\Bar',
				'$iterableUnionBar',
			],
			[
				'Iterables\Bar',
				'$unionBarFromMethod',
			],
			[
				'iterable<string>',
				'$this->stringIterableProperty',
			],
			[
				'iterable',
				'$this->mixedIterableProperty',
			],
			[
				'iterable<int>',
				'$integers',
			],
			[
				'iterable',
				'$mixeds',
			],
			[
				'iterable',
				'$this->returnIterableMixed()',
			],
			[
				'iterable<string>',
				'$this->returnIterableString()',
			],
			[
				'int|iterable<string>',
				'$this->iterablePropertyAlsoWithSomethingElse',
			],
			[
				'int|iterable<int|string>',
				'$this->iterablePropertyWithTwoItemTypes',
			],
			[
				'array<string>|Iterables\CollectionOfIntegers',
				'$this->collectionOfIntegersOrArrayOfStrings',
			],
			[
				'Generator<mixed, Iterables\Foo, mixed, mixed>',
				'$generatorOfFoos',
			],
			[
				'Iterables\Foo',
				'$fooFromGenerator',
			],
			[
				'ArrayObject<int, string>',
				'$arrayObject',
			],
			[
				'int',
				'$arrayObjectKey',
			],
			[
				'string',
				'$arrayObjectValue',
			],
		];
	}

	#[DataProvider('dataIterable')]
	public function testIterable(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/iterable.php',
			$description,
			$expression,
		);
	}

	public static function dataArrayAccess(): array
	{
		return [
			[
				'string',
				'$this->returnArrayOfStrings()[0]',
			],
			[
				'mixed',
				'$this->returnMixed()[0]',
			],
			[
				'int',
				'$this->returnSelfWithIterableInt()[0]',
			],
			[
				'int',
				'$this[0]',
			],
		];
	}

	#[DataProvider('dataArrayAccess')]
	public function testArrayAccess(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/array-accessable.php',
			$description,
			$expression,
		);
	}

	public static function dataVoid(): array
	{
		return [
			[
				'null',
				'$this->doFoo()',
			],
			[
				'null',
				'$this->doBar()',
			],
			[
				'null',
				'$this->doConflictingVoid()',
			],
		];
	}

	#[DataProvider('dataVoid')]
	public function testVoid(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/void.php',
			$description,
			$expression,
		);
	}

	public static function dataNullableReturnTypes(): array
	{
		return [
			[
				'int|null',
				'$this->doFoo()',
			],
			[
				'int|null',
				'$this->doBar()',
			],
			[
				'int|null',
				'$this->doConflictingNullable()',
			],
			[
				'int',
				'$this->doAnotherConflictingNullable()',
			],
		];
	}

	#[DataProvider('dataNullableReturnTypes')]
	public function testNullableReturnTypes(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/nullable-returnTypes.php',
			$description,
			$expression,
		);
	}

	public static function dataTernary(): array
	{
		return [
			[
				'bool|null',
				'$boolOrNull',
			],
			[
				'bool',
				'$boolOrNull !== null ? $boolOrNull : false',
			],
			[
				'bool',
				'$bool',
			],
			[
				'true|null',
				'$short',
			],
			[
				'bool',
				'$c',
			],
			[
				'bool',
				'$isQux',
			],
		];
	}

	#[DataProvider('dataTernary')]
	public function testTernary(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/ternary.php',
			$description,
			$expression,
		);
	}

	public static function dataHeredoc(): array
	{
		return [
			[
				'\'foo\'',
				'$heredoc',
			],
			[
				'\'bar\'',
				'$nowdoc',
			],
		];
	}

	#[DataProvider('dataHeredoc')]
	public function testHeredoc(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/heredoc.php',
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

	public static function dataMisleadingTypes(): array
	{
		return [
			[
				'MisleadingTypes\boolean',
				'$foo->misleadingBoolReturnType()',
			],
			[
				'MisleadingTypes\integer',
				'$foo->misleadingIntReturnType()',
			],
			[
				PHP_VERSION_ID >= 80000 ? 'mixed' : 'MisleadingTypes\mixed',
				'$foo->misleadingMixedReturnType()',
			],
		];
	}

	#[DataProvider('dataMisleadingTypes')]
	public function testMisleadingTypes(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/misleading-types.php',
			$description,
			$expression,
		);
	}

	public static function dataMisleadingTypesWithoutNamespace(): array
	{
		return [
			[
				'boolean', // would have been "bool" for a real boolean
				'$foo->misleadingBoolReturnType()',
			],
			[
				'integer',
				'$foo->misleadingIntReturnType()',
			],
		];
	}

	#[DataProvider('dataMisleadingTypesWithoutNamespace')]
	public function testMisleadingTypesWithoutNamespace(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/misleading-types-without-namespace.php',
			$description,
			$expression,
		);
	}

	public static function dataUnresolvableTypes(): array
	{
		return [
			[
				'mixed',
				'$arrayWithTooManyArgs',
			],
			[
				'mixed',
				'$iterableWithTooManyArgs',
			],
			[
				'Foo<int>',
				'$genericFoo',
			],
		];
	}

	#[DataProvider('dataUnresolvableTypes')]
	public function testUnresolvableTypes(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/unresolvable-types.php',
			$description,
			$expression,
		);
	}

	public static function dataCombineTypes(): array
	{
		return [
			[
				'string|null',
				'$x',
			],
			[
				'1|null',
				'$y',
			],
		];
	}

	#[DataProvider('dataCombineTypes')]
	public function testCombineTypes(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/combine-types.php',
			$description,
			$expression,
		);
	}

	public static function dataConstants(): array
	{
		define('ConstantsForNodeScopeResolverTest\\FOO_CONSTANT', 1);

		return [
			[
				'1',
				'$foo',
			],
			[
				'*ERROR*',
				'NONEXISTENT_CONSTANT',
			],
			[
				"'bar'",
				'\\BAR_CONSTANT',
			],
			[
				'mixed',
				'\\BAZ_CONSTANT',
			],
		];
	}

	#[DataProvider('dataConstants')]
	public function testConstants(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/constants.php',
			$description,
			$expression,
		);
	}

	public static function dataFinally(): array
	{
		return [
			[
				'1|\'foo\'',
				'$integerOrString',
			],
			[
				'FinallyNamespace\BarException|FinallyNamespace\FooException|null',
				'$fooOrBarException',
			],
		];
	}

	#[DataProvider('dataFinally')]
	public function testFinally(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/finally.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataFinally')]
	public function testFinallyWithEarlyTermination(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/finally-with-early-termination.php',
			$description,
			$expression,
		);
	}

	public static function dataInheritDocFromInterface(): array
	{
		return [
			[
				'string',
				'$string',
			],
		];
	}

	#[DataProvider('dataInheritDocFromInterface')]
	public function testInheritDocFromInterface(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/inheritdoc-from-interface.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataInheritDocFromInterface')]
	public function testInheritDocWithoutCurlyBracesFromInterface(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/inheritdoc-without-curly-braces-from-interface.php',
			$description,
			$expression,
		);
	}

	public static function dataInheritDocFromInterface2(): array
	{
		return [
			[
				'int',
				'$int',
			],
		];
	}

	#[DataProvider('dataInheritDocFromInterface2')]
	public function testInheritDocFromInterface2(
		string $description,
		string $expression,
	): void
	{
		require_once __DIR__ . '/data/inheritdoc-from-interface2-definition.php';
		$this->assertTypes(
			__DIR__ . '/data/inheritdoc-from-interface2.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataInheritDocFromInterface2')]
	public function testInheritDocWithoutCurlyBracesFromInterface2(
		string $description,
		string $expression,
	): void
	{
		require_once __DIR__ . '/data/inheritdoc-without-curly-braces-from-interface2-definition.php';
		$this->assertTypes(
			__DIR__ . '/data/inheritdoc-without-curly-braces-from-interface2.php',
			$description,
			$expression,
		);
	}

	public static function dataInheritDocFromTrait(): array
	{
		return [
			[
				'string',
				'$string',
			],
		];
	}

	#[DataProvider('dataInheritDocFromTrait')]
	public function testInheritDocFromTrait(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/inheritdoc-from-trait.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataInheritDocFromTrait')]
	public function testInheritDocWithoutCurlyBracesFromTrait(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/inheritdoc-without-curly-braces-from-trait.php',
			$description,
			$expression,
		);
	}

	public static function dataInheritDocFromTrait2(): array
	{
		return [
			[
				'string',
				'$string',
			],
		];
	}

	#[DataProvider('dataInheritDocFromTrait2')]
	public function testInheritDocFromTrait2(
		string $description,
		string $expression,
	): void
	{
		require_once __DIR__ . '/data/inheritdoc-from-trait2-definition.php';
		require_once __DIR__ . '/data/inheritdoc-from-trait2-definition2.php';
		$this->assertTypes(
			__DIR__ . '/data/inheritdoc-from-trait2.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataInheritDocFromTrait2')]
	public function testInheritDocWithoutCurlyBracesFromTrait2(
		string $description,
		string $expression,
	): void
	{
		require_once __DIR__ . '/data/inheritdoc-without-curly-braces-from-trait2-definition.php';
		require_once __DIR__ . '/data/inheritdoc-without-curly-braces-from-trait2-definition2.php';
		$this->assertTypes(
			__DIR__ . '/data/inheritdoc-without-curly-braces-from-trait2.php',
			$description,
			$expression,
		);
	}

	public static function dataResolveStatic(): array
	{
		return [
			[
				'ResolveStatic\Foo',
				'\ResolveStatic\Foo::create()',
			],
			[
				'ResolveStatic\Bar',
				'\ResolveStatic\Bar::create()',
			],
			[
				'array{foo: ResolveStatic\\Bar}',
				'$bar->returnConstantArray()',
			],
			[
				'ResolveStatic\Bar|null',
				'$bar->nullabilityNotInSync()',
			],
			[
				'ResolveStatic\Bar',
				'$bar->anotherNullabilityNotInSync()',
			],
		];
	}

	#[DataProvider('dataResolveStatic')]
	public function testResolveStatic(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/resolve-static.php',
			$description,
			$expression,
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

	public static function dataCallingMultipleClassesInOneFile(): array
	{
		return [
			[
				'MultipleClasses\Foo',
				'$foo->returnSelf()',
			],
			[
				'MultipleClasses\Bar',
				'$bar->returnSelf()',
			],
		];
	}

	#[DataProvider('dataCallingMultipleClassesInOneFile')]
	public function testCallingMultipleClassesInOneFile(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/calling-multiple-classes-per-file.php',
			$description,
			$expression,
		);
	}

	public static function dataExplode(): array
	{
		return [
			[
				'non-empty-list<string>',
				'$sureArray',
			],
			[
				PHP_VERSION_ID < 80000 ? 'false' : '*NEVER*',
				'$sureFalse',
			],
			[
				PHP_VERSION_ID < 80000 ? 'non-empty-list<string>|false' : 'non-empty-list<string>',
				'$arrayOrFalse',
			],
			[
				PHP_VERSION_ID < 80000 ? 'non-empty-list<string>|false' : 'non-empty-list<string>',
				'$anotherArrayOrFalse',
			],
			[
				PHP_VERSION_ID < 80000 ? '(non-empty-list<string>|false)' : 'non-empty-list<string>',
				'$benevolentArrayOrFalse',
			],
		];
	}

	#[DataProvider('dataExplode')]
	public function testExplode(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/explode.php',
			$description,
			$expression,
		);
	}

	public static function dataArrayPointerFunctions(): array
	{
		return [
			[
				'mixed',
				'reset()',
			],
			[
				'stdClass|false',
				'reset($generalArray)',
			],
			[
				'mixed',
				'reset($somethingElse)',
			],
			[
				'false',
				'reset($emptyConstantArray)',
			],
			[
				'1|2',
				'reset($constantArray)',
			],
			[
				'\'bar\'|\'baz\'|\'foo\'',
				'reset($conditionalArray)',
			],
			[
				'0|1|2',
				'reset($constantArrayOptionalKeys1)',
			],
			[
				'0|1|2',
				'reset($constantArrayOptionalKeys2)',
			],
			[
				'0|1|2',
				'reset($constantArrayOptionalKeys3)',
			],
			[
				'mixed',
				'end()',
			],
			[
				'stdClass|false',
				'end($generalArray)',
			],
			[
				'mixed',
				'end($somethingElse)',
			],
			[
				'false',
				'end($emptyConstantArray)',
			],
			[
				'1|2',
				'end($constantArray)',
			],
			[
				'\'bar\'|\'baz\'|\'foo\'',
				'end($secondConditionalArray)',
			],
			[
				'0|1|2',
				'end($constantArrayOptionalKeys1)',
			],
			[
				'0|1|2',
				'end($constantArrayOptionalKeys2)',
			],
			[
				'0|1|2',
				'end($constantArrayOptionalKeys3)',
			],
		];
	}

	#[DataProvider('dataArrayPointerFunctions')]
	public function testArrayPointerFunctions(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/array-pointer-functions.php',
			$description,
			$expression,
		);
	}

	public static function dataReplaceFunctions(): array
	{
		return [
			[
				'lowercase-string&non-falsy-string',
				'$expectedString',
			],
			[
				'string|null',
				'$expectedString2',
			],
			[
				'(lowercase-string&non-falsy-string)|null',
				'$anotherExpectedString',
			],
			[
				'array{a: lowercase-string&non-falsy-string, b: lowercase-string&non-falsy-string}',
				'$expectedArray',
			],
			[
				'array{a?: string, b?: string}',
				'$expectedArray2',
			],
			[
				'array{a?: lowercase-string&non-falsy-string, b?: lowercase-string&non-falsy-string}',
				'$anotherExpectedArray',
			],
			[
				'array{}|(lowercase-string&non-falsy-string)',
				'$expectedArrayOrString',
			],
			[
				'(array<string>|string)',
				'$expectedBenevolentArrayOrString',
			],
			[
				'array{}|string|null',
				'$expectedArrayOrString2',
			],
			[
				'array{}|(lowercase-string&non-falsy-string)|null',
				'$anotherExpectedArrayOrString',
			],
			[
				'array{a?: string, b?: string}',
				'preg_replace_callback_array($callbacks, $array)',
			],
			[
				'string|null',
				'preg_replace_callback_array($callbacks, $string)',
			],
			[
				'string',
				'str_replace(\'.\', \':\', $intOrStringKey)',
			],
			[
				'string',
				'str_ireplace(\'.\', \':\', $intOrStringKey)',
			],
		];
	}

	#[DataProvider('dataReplaceFunctions')]
	public function testReplaceFunctions(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/replaceFunctions.php',
			$description,
			$expression,
		);
	}

	/**
	 * @return Generator<string|int, array{string,string}>
	 */
	public static function dataFilterVar(): Generator
	{
		$typesAndFilters = [
			'string' => [
				'FILTER_DEFAULT',
				'FILTER_UNSAFE_RAW',
				'FILTER_SANITIZE_EMAIL',
				'FILTER_SANITIZE_ENCODED',
				'FILTER_SANITIZE_NUMBER_FLOAT',
				'FILTER_SANITIZE_NUMBER_INT',
				'FILTER_SANITIZE_SPECIAL_CHARS',
				'FILTER_SANITIZE_STRING',
				'FILTER_SANITIZE_URL',
				'FILTER_VALIDATE_REGEXP',
			],
			'non-falsy-string' => [
				'FILTER_VALIDATE_EMAIL',
				'FILTER_VALIDATE_IP',
				'$filterIp',
				'FILTER_VALIDATE_MAC',
				'FILTER_VALIDATE_URL',
			],
			'int' => ['FILTER_VALIDATE_INT'],
			'float' => ['FILTER_VALIDATE_FLOAT'],
		];

		if (defined('FILTER_SANITIZE_MAGIC_QUOTES')) {
			$typesAndFilters['string'][] = 'FILTER_SANITIZE_MAGIC_QUOTES';
		}

		if (defined('FILTER_SANITIZE_ADD_SLASHES')) {
			$typesAndFilters['string'][] = 'FILTER_SANITIZE_ADD_SLASHES';
		}

		$typeAndFlags = [
			['%s|false', ''],
			['mixed', ', $mixed'],
			['mixed', ', ["flags" => $mixed]'],
			['%s|null', ', FILTER_NULL_ON_FAILURE'],
			['%s|null', ', ["flags" => FILTER_NULL_ON_FAILURE]'],
			['%s|null', ', ["flags" => FILTER_NULL_ON_FAILURE | FILTER_FLAG_IPV4]'],
			['%s|null', ', ["flags" => $nullFilter]'],
			['Analyser|%s', ', ["options" => ["default" => new Analyser]]'],
			['array<%s|null>', ', FILTER_NULL_ON_FAILURE | FILTER_FORCE_ARRAY'],
			['array<%s|null>', ', FILTER_NULL_ON_FAILURE | FILTER_FORCE_ARRAY | FILTER_FLAG_IPV4'],
			['array<%s|false>', ', FILTER_FORCE_ARRAY'],
			['array<%s|null>', ', ["flags" => FILTER_NULL_ON_FAILURE | FILTER_FORCE_ARRAY]'],
			['array<%s|false>', ', ["flags" => FILTER_FORCE_ARRAY | FILTER_FLAG_IPV4]'],
			['array<%s|false>', ', ["flags" => $forceArrayFilter]'],
			['array<Analyser|%s>',', ["options" => ["default" => new Analyser], "flags" => FILTER_FORCE_ARRAY]'],
			['array<Analyser|%s>',', ["options" => ["default" => new Analyser], "flags" => FILTER_NULL_ON_FAILURE | FILTER_FORCE_ARRAY]'],
		];

		foreach ($typesAndFilters as $filterType => $filters) {
			foreach ($filters as $filter) {
				foreach ($typeAndFlags as [$type, $flag]) {
					yield [
						sprintf($type, $filterType),
						sprintf('filter_var($mixed, %s %s)', $filter, $flag),
					];
				}
			}
		}

		$boolFlags = [
			['bool', ''],
			['mixed', ', $mixed'],
			['mixed', ', ["flags" => $mixed]'],
			['bool|null', ', FILTER_NULL_ON_FAILURE'],
			['bool|null', ', ["flags" => FILTER_NULL_ON_FAILURE]'],
			['bool|null', ', ["flags" => FILTER_NULL_ON_FAILURE | FILTER_FLAG_IPV4]'],
			['bool|null', ', ["flags" => $nullFilter]'],
			['Analyser|bool', ', ["options" => ["default" => new Analyser]]'],
			['bool', ', ["options" => ["default" => true]]'],
			['array<bool|null>', ', FILTER_NULL_ON_FAILURE | FILTER_FORCE_ARRAY'],
			['array<bool>', ', FILTER_FORCE_ARRAY'],
			['array<bool|null>', ', ["flags" => FILTER_NULL_ON_FAILURE | FILTER_FORCE_ARRAY]'],
			['array<bool>', ', ["flags" => $forceArrayFilter]'],
			['array<Analyser|bool>',', ["options" => ["default" => new Analyser], "flags" => FILTER_FORCE_ARRAY]'],
			['array<bool>',', ["options" => ["default" => false], "flags" => FILTER_FORCE_ARRAY]'],
			['array<Analyser|bool>',', ["options" => ["default" => new Analyser], "flags" => FILTER_NULL_ON_FAILURE | FILTER_FORCE_ARRAY]'],
		];

		foreach ($boolFlags as [$type, $flags]) {
			yield [
				$type,
				sprintf('filter_var($mixed, FILTER_VALIDATE_BOOLEAN %s)', $flags),
			];
		}

		//edge cases
		yield 'unknown filter' => [
			'mixed',
			'filter_var($mixed, $mixed)',
		];

		yield 'default that is the same type as result' => [
			'string',
			'filter_var($mixed, FILTER_SANITIZE_URL, ["options" => ["default" => "foo"]])',
		];

		yield 'no second variable' => [
			'string|false',
			'filter_var($mixed)',
		];
	}

	public static function dataFilterVarUnchanged(): array
	{
		return [
			[
				'12',
				'filter_var(12, FILTER_VALIDATE_INT)',
			],
			[
				'false',
				'filter_var(false, FILTER_VALIDATE_BOOLEAN)',
			],
			[
				'array<false>',
				'filter_var(false, FILTER_VALIDATE_BOOLEAN, FILTER_FORCE_ARRAY)',
			],
			[
				'array<false>',
				'filter_var(false, FILTER_VALIDATE_BOOLEAN, FILTER_FORCE_ARRAY | FILTER_NULL_ON_FAILURE)',
			],
			[
				'3.27',
				'filter_var(3.27, FILTER_VALIDATE_FLOAT)',
			],
			[
				'3.27',
				'filter_var(3.27, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE)',
			],
			[
				'int<0, max>',
				'filter_var(rand(), FILTER_VALIDATE_INT)',
			],
			[
				'12.0',
				'filter_var(12, FILTER_VALIDATE_FLOAT)',
			],
		];
	}

	#[DataProvider('dataFilterVar')]
	#[DataProvider('dataFilterVarUnchanged')]
	public function testFilterVar(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/filterVar.php',
			$description,
			$expression,
		);
	}

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

	public static function dataClosureWithUsePassedByReferenceInMethodCall(): array
	{
		return [
			[
				'int|null',
				'$five',
			],
		];
	}

	#[DataProvider('dataClosureWithUsePassedByReferenceInMethodCall')]
	public function testClosureWithUsePassedByReferenceInMethodCall(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/closure-passed-by-reference-in-call.php',
			$description,
			$expression,
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

	public static function dataStaticClosure(): array
	{
		return [
			[
				'*ERROR*',
				'$this',
			],
		];
	}

	#[DataProvider('dataStaticClosure')]
	public function testStaticClosure(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/static-closure.php',
			$description,
			$expression,
		);
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

	public static function dataClosureWithInferredTypehint(): array
	{
		return [
			[
				'DateTime|stdClass',
				'$foo',
			],
			[
				'mixed',
				'$bar',
			],
		];
	}

	#[DataProvider('dataClosureWithInferredTypehint')]
	public function testClosureWithInferredTypehint(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/closure-inferred-typehint.php',
			$description,
			$expression,
			'die',
			[],
			false,
		);
	}

	public static function dataTraitsPhpDocs(): array
	{
		return [
			[
				'mixed',
				'$this->propertyWithoutPhpDoc',
			],
			[
				'TraitPhpDocsTwo\TraitPropertyType',
				'$this->traitProperty',
			],
			[
				'TraitPhpDocs\PropertyTypeFromClass',
				'$this->conflictingProperty',
			],
			/*[
				'TraitPhpDocsTwo\AmbiguousPropertyType',
				'$this->bogusProperty',
			],*/
			[
				'TraitPhpDocs\BogusPropertyType',
				'$this->anotherBogusProperty',
			],
			[
				'TraitPhpDocsTwo\BogusPropertyType',
				'$this->differentBogusProperty',
			],
			[
				'string',
				'$this->methodWithoutPhpDoc()',
			],
			[
				'TraitPhpDocsTwo\TraitMethodType',
				'$this->traitMethod()',
			],
			[
				'TraitPhpDocs\MethodTypeFromClass',
				'$this->conflictingMethod()',
			],
			[
				'TraitPhpDocs\AmbiguousMethodType',
				'$this->bogusMethod()',
			],
			[
				'TraitPhpDocs\BogusMethodType',
				'$this->anotherBogusMethod()',
			],
			[
				'TraitPhpDocsTwo\BogusMethodType',
				'$this->differentBogusMethod()',
			],
			[
				'TraitPhpDocsTwo\DuplicateMethodType',
				'$this->methodInMoreTraits()',
			],
			[
				'TraitPhpDocsThree\AnotherDuplicateMethodType',
				'$this->anotherMethodInMoreTraits()',
			],
			[
				'TraitPhpDocsTwo\YetAnotherDuplicateMethodType',
				'$this->yetAnotherMethodInMoreTraits()',
			],
			[
				'TraitPhpDocsThree\YetAnotherDuplicateMethodType',
				'$this->aliasedYetAnotherMethodInMoreTraits()',
			],
			[
				'TraitPhpDocsThree\YetYetAnotherDuplicateMethodType',
				'$this->yetYetAnotherMethodInMoreTraits()',
			],
			[
				'TraitPhpDocsTwo\YetYetAnotherDuplicateMethodType',
				'$this->aliasedYetYetAnotherMethodInMoreTraits()',
			],
			[
				'int',
				'$this->propertyFromTraitUsingTrait',
			],
			[
				'string',
				'$this->methodFromTraitUsingTrait()',
			],
			[
				'TraitPhpDocsThree\Foo',
				'$this->loremTraitProperty',
			],
		];
	}

	#[DataProvider('dataTraitsPhpDocs')]
	public function testTraitsPhpDocs(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/traits/traits.php',
			$description,
			$expression,
		);
	}

	public static function dataPassedByReference(): array
	{
		return [
			[
				'array{1, 2, 3}',
				'$arr',
			],
			[
				'array<string>',
				'$matches',
			],
			[
				'string',
				'$s',
			],
		];
	}

	#[DataProvider('dataPassedByReference')]
	public function testPassedByReference(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/passed-by-reference.php',
			$description,
			$expression,
		);
	}

	public static function dataCallables(): array
	{
		return [
			[
				'int',
				'$foo()',
			],
			[
				'string',
				'$closure()',
			],
			[
				PHP_VERSION_ID < 80000 ? 'Callables\\Bar' : '*ERROR*',
				'$arrayWithStaticMethod()',
			],
			[
				PHP_VERSION_ID < 80000 ? 'float' : '*ERROR*',
				'$stringWithStaticMethod()',
			],
			[
				'float',
				'$arrayWithInstanceMethod()',
			],
			[
				'mixed',
				'$closureObject()',
			],
		];
	}

	#[DataProvider('dataCallables')]
	public function testCallables(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/callables.php',
			$description,
			$expression,
		);
	}

	public static function dataArrayKeysInBranches(): array
	{
		return [
			[
				'array{i: int<1, max>, j: int, k: int<1, max>, l: 1, m: 5, key: DateTimeImmutable, n?: \'str\'}',
				'$array',
			],
			[
				'non-empty-array&hasOffsetValue(\'key\', mixed~null)',
				'$generalArray',
			],
			[
				'mixed~null',
				'$generalArray[\'key\']',
			],
			[
				'array{0: \'foo\', 1: \'bar\', 2?: \'baz\'}',
				'$arrayAppendedInIf',
			],
			[
				'non-empty-list<\'bar\'|\'baz\'|\'foo\'>',
				'$arrayAppendedInForeach',
			],
			[
				'non-empty-array<int<0, max>, literal-string&lowercase-string&non-falsy-string>', // could be 'array<int<0, max>, \'bar\'|\'baz\'|\'foo\'>'
				'$anotherArrayAppendedInForeach',
			],
			[
				'\'str\'',
				'$array[\'n\']',
			],
			[
				'int<0, max>',
				'$incremented',
			],
			[
				'0|1',
				'$setFromZeroToOne',
			],
		];
	}

	#[DataProvider('dataArrayKeysInBranches')]
	public function testArrayKeysInBranches(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/array-keys-branches.php',
			$description,
			$expression,
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

	public static function dataElementsOnMixed(): array
	{
		return [
			[
				'mixed',
				'$mixed->foo',
			],
			[
				'mixed',
				'$mixed->foo->bar',
			],
			[
				'mixed',
				'$mixed->foo()',
			],
			[
				'mixed',
				'$mixed->foo()->bar()',
			],
			[
				'mixed',
				'$mixed::TEST_CONSTANT',
			],
		];
	}

	#[DataProvider('dataElementsOnMixed')]
	public function testElementsOnMixed(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/mixed-elements.php',
			$description,
			$expression,
		);
	}

	public static function dataCaseInsensitivePhpDocTypes(): array
	{
		return [
			[
				'Foo\Bar',
				'$this->bar',
			],
			[
				'Foo\Baz',
				'$this->lorem',
			],
		];
	}

	#[DataProvider('dataCaseInsensitivePhpDocTypes')]
	public function testCaseInsensitivePhpDocTypes(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/case-insensitive-phpdocs.php',
			$description,
			$expression,
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

	public static function dataIsset(): array
	{
		return [
			[
				'2|3',
				'$array[\'b\']',
			],
			[
				'array{a: 1, b: 2}|array{a: 3, b: 3, c: 4}',
				'$array',
			],
			[
				'array{a: 1, b: 2}|array{a: 3, b: 3, c: 4}|array{a: 3, b: null}',
				'$arrayCopy',
			],
			[
				'array{a: 2}',
				'$anotherArrayCopy',
			],
			[
				'array{a: 1, b: 2}|array{a: 2}|array{a: 3, b: 3, c: 4}|array{a: 3, b: null}',
				'$yetAnotherArrayCopy',
			],
			[
				'mixed~null',
				'$mixedIsset',
			],
			[
				'non-empty-array&hasOffset(\'a\')',
				'$mixedArrayKeyExists',
			],
			[
				'non-empty-array<int>&hasOffsetValue(\'a\', int)',
				'$integers',
			],
			[
				'int',
				'$integers[\'a\']',
			],
			[
				'false',
				'$lookup[\'derp\'] ?? false',
			],
			[
				'true',
				'$lookup[\'foo\'] ?? false',
			],
			[
				'bool',
				'$lookup[$a] ?? false',
			],
			[
				'\'foo\'',
				'$nullableArray[\'a\'] ?? false',
			],
			[
				'\'bar\'',
				'$nullableArray[\'b\'] ?? false',
			],
			[
				'\'baz\'',
				'$nullableArray[\'c\'] ?? false',
			],
		];
	}

	#[DataProvider('dataIsset')]
	public function testIsset(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/isset.php',
			$description,
			$expression,
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

	public static function dataPhp73Functions(): array
	{
		return [
			[
				'non-empty-string|false',
				'json_encode($mixed)',
			],
			[
				'non-empty-string',
				'json_encode($mixed,  JSON_THROW_ON_ERROR)',
			],
			[
				'non-empty-string',
				'json_encode($mixed,  JSON_THROW_ON_ERROR | JSON_NUMERIC_CHECK)',
			],
			[
				'non-empty-string',
				'json_encode($mixed,  $integer | JSON_THROW_ON_ERROR | JSON_NUMERIC_CHECK)',
			],
			[
				'mixed',
				'json_decode($mixed)',
			],
			[
				'mixed',
				'json_decode($mixed, false, 512, JSON_THROW_ON_ERROR | JSON_NUMERIC_CHECK)',
			],
			[
				'mixed',
				'json_decode($mixed, false, 512, $integer | JSON_THROW_ON_ERROR | JSON_NUMERIC_CHECK)',
			],
			[
				'int|string|null',
				'array_key_first($mixedArray)',
			],
			[
				'int|string|null',
				'array_key_last($mixedArray)',
			],
			[
				'(int|string)',
				'array_key_first($nonEmptyArray)',
			],
			[
				'(int|string)',
				'array_key_last($nonEmptyArray)',
			],
			[
				'string|null',
				'array_key_first($arrayWithStringKeys)',
			],
			[
				'string|null',
				'array_key_last($arrayWithStringKeys)',
			],
			[
				'null',
				'array_key_first($emptyArray)',
			],
			[
				'null',
				'array_key_last($emptyArray)',
			],
			[
				'0|1|2',
				'array_key_first($literalArray)',
			],
			[
				'0|1|2',
				'array_key_last($literalArray)',
			],
			[
				'0|1|2|3',
				'array_key_first($anotherLiteralArray)',
			],
			[
				'0|1|2|3',
				'array_key_last($anotherLiteralArray)',
			],
			[
				"'a'|'b'|'c'",
				'array_key_first($constantArrayOptionalKeys1)',
			],
			[
				"'a'|'b'|'c'",
				'array_key_last($constantArrayOptionalKeys1)',
			],
			[
				"'a'|'b'|'c'",
				'array_key_first($constantArrayOptionalKeys2)',
			],
			[
				"'a'|'b'|'c'",
				'array_key_last($constantArrayOptionalKeys2)',
			],
			[
				"'a'|'b'|'c'",
				'array_key_first($constantArrayOptionalKeys3)',
			],
			[
				"'a'|'b'|'c'",
				'array_key_last($constantArrayOptionalKeys3)',
			],
			[
				'array{int, int}',
				'$hrtime1',
			],
			[
				'array{int, int}',
				'$hrtime2',
			],
			[
				'(float|int)',
				'$hrtime3',
			],
			[
				'array{int, int}|float|int',
				'$hrtime4',
			],
		];
	}

	#[DataProvider('dataPhp73Functions')]
	public function testPhp73Functions(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/php73_functions.php',
			$description,
			$expression,
		);
	}

	public static function dataUnionMethods(): array
	{
		return [
			[
				'UnionMethods\Bar|UnionMethods\Foo',
				'$something->doSomething()',
			],
			[
				'UnionMethods\Bar|UnionMethods\Foo',
				'$something::doSomething()',
			],
		];
	}

	#[DataProvider('dataUnionMethods')]
	public function testUnionMethods(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/union-methods.php',
			$description,
			$expression,
		);
	}

	public static function dataUnionProperties(): array
	{
		return [
			[
				'UnionProperties\Bar|UnionProperties\Foo',
				'$something->doSomething',
			],
		];
	}

	#[DataProvider('dataUnionProperties')]
	public function testUnionProperties(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/union-properties.php',
			$description,
			$expression,
		);
	}

	public static function dataAssignmentInCondition(): array
	{
		return [
			[
				'AssignmentInCondition\Foo',
				'$bar',
			],
		];
	}

	#[DataProvider('dataAssignmentInCondition')]
	public function testAssignmentInCondition(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/assignment-in-condition.php',
			$description,
			$expression,
		);
	}

	public static function dataGeneralizeScope(): array
	{
		return [
			[
				'array<string, non-empty-array<array{saveCount: int<0, max>, removeCount: int<0, max>, loadCount: int<0, max>, hitCount: int<0, max>}>>',
				'$statistics',
			],
		];
	}

	#[DataProvider('dataGeneralizeScope')]
	public function testGeneralizeScope(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/generalize-scope.php',
			$description,
			$expression,
		);
	}

	public static function dataGeneralizeScopeRecursiveType(): array
	{
		return [
			[
				'array{}|array{foo?: array}',
				'$data',
			],
		];
	}

	#[DataProvider('dataGeneralizeScopeRecursiveType')]
	public function testGeneralizeScopeRecursiveType(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/generalize-scope-recursive.php',
			$description,
			$expression,
		);
	}

	public static function dataArrayShapesInPhpDoc(): array
	{
		return [
			[
				'array{0: string, 1: ArrayShapesInPhpDoc\\Foo, foo: ArrayShapesInPhpDoc\\Bar, 2: ArrayShapesInPhpDoc\\Baz}',
				'$one',
			],
			[
				'array{0: string, 1?: ArrayShapesInPhpDoc\\Foo, foo?: ArrayShapesInPhpDoc\\Bar}',
				'$two',
			],
			[
				'array{0?: string, 1?: ArrayShapesInPhpDoc\\Foo, foo?: ArrayShapesInPhpDoc\\Bar}',
				'$three',
			],
		];
	}

	#[DataProvider('dataArrayShapesInPhpDoc')]
	public function testArrayShapesInPhpDoc(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/array-shapes.php',
			$description,
			$expression,
		);
	}

	public static function dataInferPrivatePropertyTypeFromConstructor(): array
	{
		return [
			[
				'int',
				'$this->intProp',
			],
			[
				'string',
				'$this->stringProp',
			],
			[
				'InferPrivatePropertyTypeFromConstructor\Bar|InferPrivatePropertyTypeFromConstructor\Foo',
				'$this->unionProp',
			],
			[
				'stdClass',
				'$this->stdClassProp',
			],
			[
				'stdClass',
				'$this->unrelatedDocComment',
			],
			[
				'mixed',
				'$this->explicitMixed',
			],
			[
				'bool',
				'$this->bool',
			],
			[
				'array<mixed, mixed>',
				'$this->array',
			],
		];
	}

	#[DataProvider('dataInferPrivatePropertyTypeFromConstructor')]
	public function testInferPrivatePropertyTypeFromConstructor(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/infer-private-property-type-from-constructor.php',
			$description,
			$expression,
		);
	}

	public static function dataPropertyNativeTypes(): array
	{
		return [
			[
				'string',
				'$this->stringProp',
			],
			[
				'PropertyNativeTypes\Foo',
				'$this->selfProp',
			],
			[
				'array<int>',
				'$this->integersProp',
			],
		];
	}

	#[DataProvider('dataPropertyNativeTypes')]
	public function testPropertyNativeTypes(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/property-native-types.php',
			$description,
			$expression,
		);
	}

	public static function dataArrowFunctions(): array
	{
		return [
			[
				'Closure(string): 1',
				'$x',
			],
			[
				'1',
				'$x()',
			],
			[
				'array{a: 1, b: 2}',
				'$y()',
			],
		];
	}

	#[DataProvider('dataArrowFunctions')]
	public function testArrowFunctions(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/arrow-functions.php',
			$description,
			$expression,
		);
	}

	public static function dataArrowFunctionsInside(): array
	{
		return [
			[
				'int',
				'$i',
			],
			[
				'string',
				'$s',
			],
			[
				'*ERROR*',
				'$t',
			],
		];
	}

	#[DataProvider('dataArrowFunctionsInside')]
	public function testArrowFunctionsInside(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/arrow-functions-inside.php',
			$description,
			$expression,
		);
	}

	public static function dataCoalesceAssign(): array
	{
		return [
			[
				'string',
				'$string ??= 1',
			],
			[
				'1|string',
				'$nullableString ??= 1',
			],
			[
				'\'foo\'',
				'$emptyArray[\'foo\'] ??= \'foo\'',
			],
			[
				'\'foo\'',
				'$arrayWithFoo[\'foo\'] ??= \'bar\'',
			],
			[
				'\'bar\'|\'foo\'',
				'$arrayWithMaybeFoo[\'foo\'] ??= \'bar\'',
			],
			[
				'array{foo: \'foo\'}',
				'$arrayAfterAssignment',
			],
			[
				'array{foo: \'foo\'}',
				'$arrayWithFooAfterAssignment',
			],
			[
				'\'foo\'',
				'$nonexistentVariableAfterAssignment',
			],
			[
				'\'bar\'|\'foo\'',
				'$maybeNonexistentVariableAfterAssignment',
			],
		];
	}

	#[DataProvider('dataCoalesceAssign')]
	public function testCoalesceAssign(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/coalesce-assign.php',
			$description,
			$expression,
		);
	}

	public static function dataArraySpread(): array
	{
		return [
			[
				'non-empty-list<int>',
				'$integersOne',
			],
			[
				'non-empty-list<int>',
				'$integersTwo',
			],
			[
				'array{1, 2, 3, 4, 5, 6, 7}',
				'$integersThree',
			],
			[
				'non-empty-list<int>',
				'$integersFour',
			],
			[
				'non-empty-list<int>',
				'$integersFive',
			],
			[
				'array{1, 2, 3, 4, 5, 6, 7}',
				'$integersSix',
			],
			[
				'array{1, 2, 3, 4, 5, 6, 7}',
				'$integersSeven',
			],
		];
	}

	#[DataProvider('dataArraySpread')]
	public function testArraySpread(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/array-spread.php',
			$description,
			$expression,
		);
	}

	public static function dataPhp74FunctionsIn74(): array
	{
		return [
			[
				'list<string>',
				'password_algos()',
			],
		];
	}

	#[DataProvider('dataPhp74FunctionsIn74')]
	public function testPhp74FunctionsIn74(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/data/die-74.php',
			$description,
			$expression,
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

				self::$assertTypesCache[$file][$evaluatedPointExpression] = $scope;

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
