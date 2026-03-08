<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr\Exit_;
use PHPStan\Node\Printer\Printer;
use PHPStan\Node\VirtualNode;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use SomeNodeScopeResolverNamespace\Foo;
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
		];
	}

}
