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

	public static function dataTypeFromMethodPhpDocsNoReplace(): array
	{
		return [
			[
				'MethodPhpDocsNamespace\FooParent',
				'$parent->doLorem()',
			],
			[
				'MethodPhpDocsNamespace\FooParent',
				'$parent->doIpsum()',
			],
			[
				'MethodPhpDocsNamespace\FooParent',
				'$this->returnParent()',
			],
			[
				'MethodPhpDocsNamespace\FooParent',
				'$this->returnPhpDocParent()',
			],
		];
	}

	#[DataProvider('dataTypeFromMethodPhpDocsNoReplace')]
	public function testTypeFromMethodPhpDocsPsalmPrefix(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/nsrt/methodPhpDocs-psalmPrefix.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataTypeFromMethodPhpDocsNoReplace')]
	public function testTypeFromMethodPhpDocsPhpstanPrefix(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/nsrt/methodPhpDocs-phpstanPrefix.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataTypeFromMethodPhpDocsNoReplace')]
	public function testTypeFromMethodPhpDocsPhanPrefix(
		string $description,
		string $expression,
		bool $replaceClass = true,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/nsrt/methodPhpDocs-phanPrefix.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataTypeFromMethodPhpDocsNoReplace')]
	public function testTypeFromTraitPhpDocs(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/nsrt/methodPhpDocs-trait.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataTypeFromMethodPhpDocsNoReplace')]
	public function testTypeFromMethodPhpDocsInheritDocWithoutCurlyBraces(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/nsrt/method-phpDocs-inheritdoc-without-curly-braces.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataTypeFromMethodPhpDocsNoReplace')]
	public function testTypeFromRecursiveTraitPhpDocs(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/nsrt/methodPhpDocs-recursiveTrait.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataTypeFromMethodPhpDocsNoReplace')]
	public function testTypeFromMethodPhpDocsInheritDoc(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/nsrt/method-phpDocs-inheritdoc.php',
			$description,
			$expression,
		);
	}

	#[DataProvider('dataTypeFromMethodPhpDocsNoReplace')]
	public function testTypeFromMethodPhpDocsImplicitInheritance(
		string $description,
		string $expression,
	): void
	{
		$this->assertTypes(
			__DIR__ . '/nsrt/methodPhpDocs-implicitInheritance.php',
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
			__DIR__ . '/data/methodPhpDocs-recursive-trait-defined.php',
		];
	}

}
