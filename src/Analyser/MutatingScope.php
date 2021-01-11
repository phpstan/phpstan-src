<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\Cast\Bool_;
use PhpParser\Node\Expr\Cast\Double;
use PhpParser\Node\Expr\Cast\Int_;
use PhpParser\Node\Expr\Cast\Object_;
use PhpParser\Node\Expr\Cast\Unset_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\Dummy\DummyConstructorReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\ConstantType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\DynamicReturnTypeExtensionRegistry;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\GenericTypeVariableResolver;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\OperatorTypeSpecifyingExtensionRegistry;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;

class MutatingScope implements Scope
{

	private const OPERATOR_SIGIL_MAP = [
		Node\Expr\AssignOp\Plus::class => '+',
		Node\Expr\AssignOp\Minus::class => '-',
		Node\Expr\AssignOp\Mul::class => '*',
		Node\Expr\AssignOp\Pow::class => '^',
		Node\Expr\AssignOp\Div::class => '/',
	];

	private \PHPStan\Analyser\ScopeFactory $scopeFactory;

	private \PHPStan\Reflection\ReflectionProvider $reflectionProvider;

	private \PHPStan\Type\DynamicReturnTypeExtensionRegistry $dynamicReturnTypeExtensionRegistry;

	private OperatorTypeSpecifyingExtensionRegistry $operatorTypeSpecifyingExtensionRegistry;

	private \PhpParser\PrettyPrinter\Standard $printer;

	private \PHPStan\Analyser\TypeSpecifier $typeSpecifier;

	private \PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder;

	private Parser $parser;

	private \PHPStan\Analyser\ScopeContext $context;

	/** @var \PHPStan\Type\Type[] */
	private array $resolvedTypes = [];

	private bool $declareStrictTypes;

	/** @var array<string, Type> */
	private array $constantTypes;

	/** @var \PHPStan\Reflection\FunctionReflection|MethodReflection|null */
	private $function;

	private ?string $namespace;

	/** @var \PHPStan\Analyser\VariableTypeHolder[] */
	private array $variableTypes;

	/** @var \PHPStan\Analyser\VariableTypeHolder[] */
	private array $moreSpecificTypes;

	/** @var array<string, ConditionalExpressionHolder[]> */
	private array $conditionalExpressions;

	private ?string $inClosureBindScopeClass;

	private ?ParametersAcceptor $anonymousFunctionReflection;

	private bool $inFirstLevelStatement;

	/** @var array<string, true> */
	private array $currentlyAssignedExpressions;

	/** @var array<MethodReflection|FunctionReflection> */
	private array $inFunctionCallsStack;

	/** @var array<string, Type> */
	private array $nativeExpressionTypes;

	/** @var string[] */
	private array $dynamicConstantNames;

	private bool $treatPhpDocTypesAsCertain;

	private bool $afterExtractCall;

	private ?Scope $parentScope;

	/**
	 * @param \PHPStan\Analyser\ScopeFactory $scopeFactory
	 * @param ReflectionProvider $reflectionProvider
	 * @param \PHPStan\Type\DynamicReturnTypeExtensionRegistry $dynamicReturnTypeExtensionRegistry
	 * @param \PHPStan\Type\OperatorTypeSpecifyingExtensionRegistry $operatorTypeSpecifyingExtensionRegistry
	 * @param \PhpParser\PrettyPrinter\Standard $printer
	 * @param \PHPStan\Analyser\TypeSpecifier $typeSpecifier
	 * @param \PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder
	 * @param Parser $parser
	 * @param \PHPStan\Analyser\ScopeContext $context
	 * @param bool $declareStrictTypes
	 * @param array<string, Type> $constantTypes
	 * @param \PHPStan\Reflection\FunctionReflection|MethodReflection|null $function
	 * @param string|null $namespace
	 * @param \PHPStan\Analyser\VariableTypeHolder[] $variablesTypes
	 * @param \PHPStan\Analyser\VariableTypeHolder[] $moreSpecificTypes
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @param string|null $inClosureBindScopeClass
	 * @param \PHPStan\Reflection\ParametersAcceptor|null $anonymousFunctionReflection
	 * @param bool $inFirstLevelStatement
	 * @param array<string, true> $currentlyAssignedExpressions
	 * @param array<string, Type> $nativeExpressionTypes
	 * @param array<MethodReflection|FunctionReflection> $inFunctionCallsStack
	 * @param string[] $dynamicConstantNames
	 * @param bool $treatPhpDocTypesAsCertain
	 * @param bool $afterExtractCall
	 * @param Scope|null $parentScope
	 */
	public function __construct(
		ScopeFactory $scopeFactory,
		ReflectionProvider $reflectionProvider,
		DynamicReturnTypeExtensionRegistry $dynamicReturnTypeExtensionRegistry,
		OperatorTypeSpecifyingExtensionRegistry $operatorTypeSpecifyingExtensionRegistry,
		\PhpParser\PrettyPrinter\Standard $printer,
		TypeSpecifier $typeSpecifier,
		PropertyReflectionFinder $propertyReflectionFinder,
		Parser $parser,
		ScopeContext $context,
		bool $declareStrictTypes = false,
		array $constantTypes = [],
		$function = null,
		?string $namespace = null,
		array $variablesTypes = [],
		array $moreSpecificTypes = [],
		array $conditionalExpressions = [],
		?string $inClosureBindScopeClass = null,
		?ParametersAcceptor $anonymousFunctionReflection = null,
		bool $inFirstLevelStatement = true,
		array $currentlyAssignedExpressions = [],
		array $nativeExpressionTypes = [],
		array $inFunctionCallsStack = [],
		array $dynamicConstantNames = [],
		bool $treatPhpDocTypesAsCertain = true,
		bool $afterExtractCall = false,
		?Scope $parentScope = null
	)
	{
		if ($namespace === '') {
			$namespace = null;
		}

		$this->scopeFactory = $scopeFactory;
		$this->reflectionProvider = $reflectionProvider;
		$this->dynamicReturnTypeExtensionRegistry = $dynamicReturnTypeExtensionRegistry;
		$this->operatorTypeSpecifyingExtensionRegistry = $operatorTypeSpecifyingExtensionRegistry;
		$this->printer = $printer;
		$this->typeSpecifier = $typeSpecifier;
		$this->propertyReflectionFinder = $propertyReflectionFinder;
		$this->parser = $parser;
		$this->context = $context;
		$this->declareStrictTypes = $declareStrictTypes;
		$this->constantTypes = $constantTypes;
		$this->function = $function;
		$this->namespace = $namespace;
		$this->variableTypes = $variablesTypes;
		$this->moreSpecificTypes = $moreSpecificTypes;
		$this->conditionalExpressions = $conditionalExpressions;
		$this->inClosureBindScopeClass = $inClosureBindScopeClass;
		$this->anonymousFunctionReflection = $anonymousFunctionReflection;
		$this->inFirstLevelStatement = $inFirstLevelStatement;
		$this->currentlyAssignedExpressions = $currentlyAssignedExpressions;
		$this->nativeExpressionTypes = $nativeExpressionTypes;
		$this->inFunctionCallsStack = $inFunctionCallsStack;
		$this->dynamicConstantNames = $dynamicConstantNames;
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
		$this->afterExtractCall = $afterExtractCall;
		$this->parentScope = $parentScope;
	}

	public function getFile(): string
	{
		return $this->context->getFile();
	}

	public function getFileDescription(): string
	{
		if ($this->context->getTraitReflection() === null) {
			return $this->getFile();
		}

		/** @var ClassReflection $classReflection */
		$classReflection = $this->context->getClassReflection();

		$className = sprintf('class %s', $classReflection->getDisplayName());
		if ($classReflection->isAnonymous()) {
			$className = 'anonymous class';
		}

		$traitReflection = $this->context->getTraitReflection();
		if ($traitReflection->getFileName() === false) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return sprintf(
			'%s (in context of %s)',
			$traitReflection->getFileName(),
			$className
		);
	}

	public function isDeclareStrictTypes(): bool
	{
		return $this->declareStrictTypes;
	}

	public function enterDeclareStrictTypes(): self
	{
		return $this->scopeFactory->create(
			$this->context,
			true
		);
	}

	public function isInClass(): bool
	{
		return $this->context->getClassReflection() !== null;
	}

	public function isInTrait(): bool
	{
		return $this->context->getTraitReflection() !== null;
	}

	public function getClassReflection(): ?ClassReflection
	{
		return $this->context->getClassReflection();
	}

	public function getTraitReflection(): ?ClassReflection
	{
		return $this->context->getTraitReflection();
	}

	/**
	 * @return \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\MethodReflection|null
	 */
	public function getFunction()
	{
		return $this->function;
	}

	public function getFunctionName(): ?string
	{
		return $this->function !== null ? $this->function->getName() : null;
	}

	public function getNamespace(): ?string
	{
		return $this->namespace;
	}

	public function getParentScope(): ?Scope
	{
		return $this->parentScope;
	}

	/**
	 * @return array<string, \PHPStan\Analyser\VariableTypeHolder>
	 */
	private function getVariableTypes(): array
	{
		return $this->variableTypes;
	}

	private function canAnyVariableExist(): bool
	{
		return ($this->function === null && !$this->isInAnonymousFunction()) || $this->afterExtractCall;
	}

	public function afterExtractCall(): self
	{
		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$this->getVariableTypes(),
			$this->moreSpecificTypes,
			[],
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$this->nativeExpressionTypes,
			$this->inFunctionCallsStack,
			true,
			$this->parentScope
		);
	}

	public function hasVariableType(string $variableName): TrinaryLogic
	{
		if ($this->isGlobalVariable($variableName)) {
			return TrinaryLogic::createYes();
		}

		if (!isset($this->variableTypes[$variableName])) {
			if ($this->canAnyVariableExist()) {
				return TrinaryLogic::createMaybe();
			}

			return TrinaryLogic::createNo();
		}

		return $this->variableTypes[$variableName]->getCertainty();
	}

	public function getVariableType(string $variableName): Type
	{
		if ($this->isGlobalVariable($variableName)) {
			return new ArrayType(new StringType(), new MixedType());
		}

		if ($this->hasVariableType($variableName)->no()) {
			throw new \PHPStan\Analyser\UndefinedVariableException($this, $variableName);
		}

		if (!array_key_exists($variableName, $this->variableTypes)) {
			return new MixedType();
		}

		return $this->variableTypes[$variableName]->getType();
	}

	/**
	 * @return array<int, string>
	 */
	public function getDefinedVariables(): array
	{
		$variables = [];
		foreach ($this->variableTypes as $variableName => $holder) {
			if (!$holder->getCertainty()->yes()) {
				continue;
			}

			$variables[] = $variableName;
		}

		return $variables;
	}

	private function isGlobalVariable(string $variableName): bool
	{
		return in_array($variableName, [
			'GLOBALS',
			'_SERVER',
			'_GET',
			'_POST',
			'_FILES',
			'_COOKIE',
			'_SESSION',
			'_REQUEST',
			'_ENV',
		], true);
	}

	public function hasConstant(Name $name): bool
	{
		$isCompilerHaltOffset = $name->toString() === '__COMPILER_HALT_OFFSET__';
		if ($isCompilerHaltOffset) {
			return $this->fileHasCompilerHaltStatementCalls();
		}
		if ($name->isFullyQualified()) {
			if (array_key_exists($name->toCodeString(), $this->constantTypes)) {
				return true;
			}
		}

		if ($this->getNamespace() !== null) {
			$constantName = new FullyQualified([$this->getNamespace(), $name->toString()]);
			if (array_key_exists($constantName->toCodeString(), $this->constantTypes)) {
				return true;
			}
		}

		$constantName = new FullyQualified($name->toString());
		if (array_key_exists($constantName->toCodeString(), $this->constantTypes)) {
			return true;
		}

		if (!$this->reflectionProvider->hasConstant($name, $this)) {
			return false;
		}

		$constantReflection = $this->reflectionProvider->getConstant($name, $this);

		return $constantReflection->getFileName() !== $this->getFile();
	}

	private function fileHasCompilerHaltStatementCalls(): bool
	{
		$nodes = $this->parser->parseFile($this->getFile());
		foreach ($nodes as $node) {
			if ($node instanceof \PhpParser\Node\Stmt\HaltCompiler) {
				return true;
			}
		}

		return false;
	}

	public function isInAnonymousFunction(): bool
	{
		return $this->anonymousFunctionReflection !== null;
	}

	public function getAnonymousFunctionReflection(): ?ParametersAcceptor
	{
		return $this->anonymousFunctionReflection;
	}

	public function getAnonymousFunctionReturnType(): ?\PHPStan\Type\Type
	{
		if ($this->anonymousFunctionReflection === null) {
			return null;
		}

		return $this->anonymousFunctionReflection->getReturnType();
	}

	public function getType(Expr $node): Type
	{
		$key = $this->getNodeKey($node);

		if (!array_key_exists($key, $this->resolvedTypes)) {
			$this->resolvedTypes[$key] = $this->resolveType($node);
		}
		return $this->resolvedTypes[$key];
	}

	private function getNodeKey(Expr $node): string
	{
		/** @var string|null $key */
		$key = $node->getAttribute('phpstan_cache_printer');
		if ($key === null) {
			$key = $this->printer->prettyPrintExpr($node);
			$node->setAttribute('phpstan_cache_printer', $key);
		}

		return $key;
	}

	private function resolveType(Expr $node): Type
	{
		if ($node instanceof Expr\Exit_ || $node instanceof Expr\Throw_) {
			return new NeverType(true);
		}

		if ($node instanceof Expr\BinaryOp\Smaller) {
			return $this->getType($node->left)->isSmallerThan($this->getType($node->right))->toBooleanType();
		}

		if ($node instanceof Expr\BinaryOp\SmallerOrEqual) {
			return $this->getType($node->left)->isSmallerThanOrEqual($this->getType($node->right))->toBooleanType();
		}

		if ($node instanceof Expr\BinaryOp\Greater) {
			return $this->getType($node->right)->isSmallerThan($this->getType($node->left))->toBooleanType();
		}

		if ($node instanceof Expr\BinaryOp\GreaterOrEqual) {
			return $this->getType($node->right)->isSmallerThanOrEqual($this->getType($node->left))->toBooleanType();
		}

		if (
			$node instanceof Expr\BinaryOp\Equal
			|| $node instanceof Expr\BinaryOp\NotEqual
			|| $node instanceof Expr\Empty_
		) {
			return new BooleanType();
		}

		if ($node instanceof Expr\Isset_) {
			$result = new ConstantBooleanType(true);
			foreach ($node->vars as $var) {
				if ($var instanceof Expr\ArrayDimFetch && $var->dim !== null) {
					$variableType = $this->getType($var->var);
					$dimType = $this->getType($var->dim);
					$hasOffset = $variableType->hasOffsetValueType($dimType);
					$offsetValueType = $variableType->getOffsetValueType($dimType);
					$offsetValueIsNotNull = (new NullType())->isSuperTypeOf($offsetValueType)->negate();
					$isset = $hasOffset->and($offsetValueIsNotNull)->toBooleanType();
					if ($isset instanceof ConstantBooleanType) {
						if (!$isset->getValue()) {
							return $isset;
						}

						continue;
					}

					$result = $isset;
					continue;
				}

				if ($var instanceof Expr\Variable && is_string($var->name)) {
					$variableType = $this->getType($var);
					$isNullSuperType = (new NullType())->isSuperTypeOf($variableType);
					$has = $this->hasVariableType($var->name);
					if ($has->no() || $isNullSuperType->yes()) {
						return new ConstantBooleanType(false);
					}

					if ($has->maybe() || !$isNullSuperType->no()) {
						$result = new BooleanType();
					}
					continue;
				}

				return new BooleanType();
			}

			return $result;
		}

		if ($node instanceof \PhpParser\Node\Expr\BooleanNot) {
			if ($this->treatPhpDocTypesAsCertain) {
				$exprBooleanType = $this->getType($node->expr)->toBoolean();
			} else {
				$exprBooleanType = $this->getNativeType($node->expr)->toBoolean();
			}
			if ($exprBooleanType instanceof ConstantBooleanType) {
				return new ConstantBooleanType(!$exprBooleanType->getValue());
			}

			return new BooleanType();
		}

		if ($node instanceof \PhpParser\Node\Expr\BitwiseNot) {
			$exprType = $this->getType($node->expr);
			return TypeTraverser::map($exprType, static function (Type $type, callable $traverse): Type {
				if ($type instanceof UnionType || $type instanceof IntersectionType) {
					return $traverse($type);
				}
				if ($type instanceof ConstantStringType) {
					return new ConstantStringType(~$type->getValue());
				}
				if ($type instanceof StringType) {
					return new StringType();
				}
				if ($type instanceof IntegerType || $type instanceof FloatType) {
					return new IntegerType(); //no const types here, result depends on PHP_INT_SIZE
				}
				return new ErrorType();
			});
		}

		if (
			$node instanceof \PhpParser\Node\Expr\BinaryOp\BooleanAnd
			|| $node instanceof \PhpParser\Node\Expr\BinaryOp\LogicalAnd
		) {
			if ($this->treatPhpDocTypesAsCertain) {
				$leftBooleanType = $this->getType($node->left)->toBoolean();
			} else {
				$leftBooleanType = $this->getNativeType($node->left)->toBoolean();
			}

			if (
				$leftBooleanType instanceof ConstantBooleanType
				&& !$leftBooleanType->getValue()
			) {
				return new ConstantBooleanType(false);
			}

			if ($this->treatPhpDocTypesAsCertain) {
				$rightBooleanType = $this->filterByTruthyValue($node->left)->getType($node->right)->toBoolean();
			} else {
				$rightBooleanType = $this->promoteNativeTypes()->filterByTruthyValue($node->left)->getType($node->right)->toBoolean();
			}

			if (
				$rightBooleanType instanceof ConstantBooleanType
				&& !$rightBooleanType->getValue()
			) {
				return new ConstantBooleanType(false);
			}

			if (
				$leftBooleanType instanceof ConstantBooleanType
				&& $leftBooleanType->getValue()
				&& $rightBooleanType instanceof ConstantBooleanType
				&& $rightBooleanType->getValue()
			) {
				return new ConstantBooleanType(true);
			}

			return new BooleanType();
		}

		if (
			$node instanceof \PhpParser\Node\Expr\BinaryOp\BooleanOr
			|| $node instanceof \PhpParser\Node\Expr\BinaryOp\LogicalOr
		) {
			if ($this->treatPhpDocTypesAsCertain) {
				$leftBooleanType = $this->getType($node->left)->toBoolean();
			} else {
				$leftBooleanType = $this->getNativeType($node->left)->toBoolean();
			}
			if (
				$leftBooleanType instanceof ConstantBooleanType
				&& $leftBooleanType->getValue()
			) {
				return new ConstantBooleanType(true);
			}

			if ($this->treatPhpDocTypesAsCertain) {
				$rightBooleanType = $this->filterByFalseyValue($node->left)->getType($node->right)->toBoolean();
			} else {
				$rightBooleanType = $this->promoteNativeTypes()->filterByFalseyValue($node->left)->getType($node->right)->toBoolean();
			}

			if (
				$rightBooleanType instanceof ConstantBooleanType
				&& $rightBooleanType->getValue()
			) {
				return new ConstantBooleanType(true);
			}

			if (
				$leftBooleanType instanceof ConstantBooleanType
				&& !$leftBooleanType->getValue()
				&& $rightBooleanType instanceof ConstantBooleanType
				&& !$rightBooleanType->getValue()
			) {
				return new ConstantBooleanType(false);
			}

			return new BooleanType();
		}

		if ($node instanceof \PhpParser\Node\Expr\BinaryOp\LogicalXor) {
			if ($this->treatPhpDocTypesAsCertain) {
				$leftBooleanType = $this->getType($node->left)->toBoolean();
				$rightBooleanType = $this->getType($node->right)->toBoolean();
			} else {
				$leftBooleanType = $this->getNativeType($node->left)->toBoolean();
				$rightBooleanType = $this->getNativeType($node->right)->toBoolean();
			}

			if (
				$leftBooleanType instanceof ConstantBooleanType
				&& $rightBooleanType instanceof ConstantBooleanType
			) {
				return new ConstantBooleanType(
					$leftBooleanType->getValue() xor $rightBooleanType->getValue()
				);
			}

			return new BooleanType();
		}

		if ($node instanceof Expr\BinaryOp\Identical) {
			$leftType = $this->getType($node->left);
			$rightType = $this->getType($node->right);

			if (
				(
					$node->left instanceof Node\Expr\PropertyFetch
					|| $node->left instanceof Node\Expr\StaticPropertyFetch
				)
				&& $rightType instanceof NullType
				&& !$this->hasPropertyNativeType($node->left)
			) {
				return new BooleanType();
			}

			if (
				(
					$node->right instanceof Node\Expr\PropertyFetch
					|| $node->right instanceof Node\Expr\StaticPropertyFetch
				)
				&& $leftType instanceof NullType
				&& !$this->hasPropertyNativeType($node->right)
			) {
				return new BooleanType();
			}

			$isSuperset = $leftType->isSuperTypeOf($rightType);
			if ($isSuperset->no()) {
				return new ConstantBooleanType(false);
			} elseif (
				$isSuperset->yes()
				&& $leftType instanceof ConstantScalarType
				&& $rightType instanceof ConstantScalarType
				&& $leftType->getValue() === $rightType->getValue()
			) {
				return new ConstantBooleanType(true);
			}

			return new BooleanType();
		}

		if ($node instanceof Expr\BinaryOp\NotIdentical) {
			$leftType = $this->getType($node->left);
			$rightType = $this->getType($node->right);

			if (
				(
					$node->left instanceof Node\Expr\PropertyFetch
					|| $node->left instanceof Node\Expr\StaticPropertyFetch
				)
				&& $rightType instanceof NullType
				&& !$this->hasPropertyNativeType($node->left)
			) {
				return new BooleanType();
			}

			if (
				(
					$node->right instanceof Node\Expr\PropertyFetch
					|| $node->right instanceof Node\Expr\StaticPropertyFetch
				)
				&& $leftType instanceof NullType
				&& !$this->hasPropertyNativeType($node->right)
			) {
				return new BooleanType();
			}

			$isSuperset = $leftType->isSuperTypeOf($rightType);
			if ($isSuperset->no()) {
				return new ConstantBooleanType(true);
			} elseif (
				$isSuperset->yes()
				&& $leftType instanceof ConstantScalarType
				&& $rightType instanceof ConstantScalarType
				&& $leftType->getValue() === $rightType->getValue()
			) {
				return new ConstantBooleanType(false);
			}

			return new BooleanType();
		}

		if ($node instanceof Expr\Instanceof_) {
			if ($this->treatPhpDocTypesAsCertain) {
				$expressionType = $this->getType($node->expr);
			} else {
				$expressionType = $this->getNativeType($node->expr);
			}
			if (
				$this->isInTrait()
				&& TypeUtils::findThisType($expressionType) !== null
			) {
				return new BooleanType();
			}
			if ($expressionType instanceof NeverType) {
				return new ConstantBooleanType(false);
			}

			$uncertainty = false;

			if ($node->class instanceof Node\Name) {
				$className = $this->resolveName($node->class);
				$classType = new ObjectType($className);
			} else {
				$classType = $this->getType($node->class);
				$classType = TypeTraverser::map($classType, static function (Type $type, callable $traverse) use (&$uncertainty): Type {
					if ($type instanceof UnionType || $type instanceof IntersectionType) {
						return $traverse($type);
					}
					if ($type instanceof TypeWithClassName) {
						$uncertainty = true;
						return $type;
					}
					if ($type instanceof GenericClassStringType) {
						$uncertainty = true;
						return $type->getGenericType();
					}
					if ($type instanceof ConstantStringType) {
						return new ObjectType($type->getValue());
					}
					return new MixedType();
				});
			}

			if ($classType->isSuperTypeOf(new MixedType())->yes()) {
				return new BooleanType();
			}

			$isSuperType = $classType->isSuperTypeOf($expressionType);

			if ($isSuperType->no()) {
				return new ConstantBooleanType(false);
			} elseif ($isSuperType->yes() && !$uncertainty) {
				return new ConstantBooleanType(true);
			}

			return new BooleanType();
		}

		if ($node instanceof Node\Expr\UnaryPlus) {
			return $this->getType($node->expr)->toNumber();
		}

		if ($node instanceof Expr\ErrorSuppress
			|| $node instanceof Expr\Assign
		) {
			return $this->getType($node->expr);
		}

		if ($node instanceof Node\Expr\UnaryMinus) {
			$type = $this->getType($node->expr)->toNumber();
			$scalarValues = TypeUtils::getConstantScalars($type);
			if (count($scalarValues) > 0) {
				$newTypes = [];
				foreach ($scalarValues as $scalarValue) {
					if ($scalarValue instanceof ConstantIntegerType) {
						$newTypes[] = new ConstantIntegerType(-$scalarValue->getValue());
					} elseif ($scalarValue instanceof ConstantFloatType) {
						$newTypes[] = new ConstantFloatType(-$scalarValue->getValue());
					}
				}

				return TypeCombinator::union(...$newTypes);
			}

			return $type;
		}

		if ($node instanceof Expr\BinaryOp\Concat || $node instanceof Expr\AssignOp\Concat) {
			if ($node instanceof Node\Expr\AssignOp) {
				$left = $node->var;
				$right = $node->expr;
			} else {
				$left = $node->left;
				$right = $node->right;
			}

			$leftStringType = $this->getType($left)->toString();
			$rightStringType = $this->getType($right)->toString();
			if (TypeCombinator::union(
				$leftStringType,
				$rightStringType
			) instanceof ErrorType) {
				return new ErrorType();
			}

			if ($leftStringType instanceof ConstantStringType && $leftStringType->getValue() === '') {
				return $rightStringType;
			}

			if ($rightStringType instanceof ConstantStringType && $rightStringType->getValue() === '') {
				return $leftStringType;
			}

			if ($leftStringType instanceof ConstantStringType && $rightStringType instanceof ConstantStringType) {
				return $leftStringType->append($rightStringType);
			}

			return new StringType();
		}

		if (
			$node instanceof Node\Expr\BinaryOp\Div
			|| $node instanceof Node\Expr\AssignOp\Div
			|| $node instanceof Node\Expr\BinaryOp\Mod
			|| $node instanceof Node\Expr\AssignOp\Mod
		) {
			if ($node instanceof Node\Expr\AssignOp) {
				$right = $node->expr;
			} else {
				$right = $node->right;
			}

			$rightTypes = TypeUtils::getConstantScalars($this->getType($right)->toNumber());
			foreach ($rightTypes as $rightType) {
				if (
					$rightType->getValue() === 0
					|| $rightType->getValue() === 0.0
				) {
					return new ErrorType();
				}
			}
		}

		if (
			(
				$node instanceof Node\Expr\BinaryOp
				|| $node instanceof Node\Expr\AssignOp
			) && !$node instanceof Expr\BinaryOp\Coalesce && !$node instanceof Expr\AssignOp\Coalesce
		) {
			if ($node instanceof Node\Expr\AssignOp) {
				$left = $node->var;
				$right = $node->expr;
			} else {
				$left = $node->left;
				$right = $node->right;
			}

			$leftTypes = TypeUtils::getConstantScalars($this->getType($left));
			$rightTypes = TypeUtils::getConstantScalars($this->getType($right));

			if (count($leftTypes) > 0 && count($rightTypes) > 0) {
				$resultTypes = [];
				foreach ($leftTypes as $leftType) {
					foreach ($rightTypes as $rightType) {
						$resultTypes[] = $this->calculateFromScalars($node, $leftType, $rightType);
					}
				}
				return TypeCombinator::union(...$resultTypes);
			}
		}

		if ($node instanceof Node\Expr\BinaryOp\Mod || $node instanceof Expr\AssignOp\Mod) {
			return new IntegerType();
		}

		if ($node instanceof Expr\BinaryOp\Spaceship) {
			return IntegerRangeType::fromInterval(-1, 1);
		}

		if ($node instanceof Expr\AssignOp\Coalesce) {
			return $this->getType(new BinaryOp\Coalesce($node->var, $node->expr, $node->getAttributes()));
		}

		if ($node instanceof Expr\BinaryOp\Coalesce) {
			if ($node->left instanceof Expr\ArrayDimFetch && $node->left->dim !== null) {
				$dimType = $this->getType($node->left->dim);
				$varType = $this->getType($node->left->var);
				$hasOffset = $varType->hasOffsetValueType($dimType);
				$leftType = $this->getType($node->left);
				$rightType = $this->getType($node->right);
				if ($hasOffset->no()) {
					return $rightType;
				} elseif ($hasOffset->yes()) {
					$offsetValueType = $varType->getOffsetValueType($dimType);
					if ($offsetValueType->isSuperTypeOf(new NullType())->no()) {
						return TypeCombinator::removeNull($leftType);
					}
				}

				return TypeCombinator::union(
					TypeCombinator::removeNull($leftType),
					$rightType
				);
			}

			$leftType = $this->getType($node->left);
			$rightType = $this->getType($node->right);
			if ($leftType instanceof ErrorType || $leftType instanceof NullType) {
				return $rightType;
			}

			if (
				TypeCombinator::containsNull($leftType)
				|| $node->left instanceof PropertyFetch
				|| (
					$node->left instanceof Variable
					&& is_string($node->left->name)
					&& !$this->hasVariableType($node->left->name)->yes()
				)
			) {
				return TypeCombinator::union(
					TypeCombinator::removeNull($leftType),
					$rightType
				);
			}

			return TypeCombinator::removeNull($leftType);
		}

		if ($node instanceof Expr\Clone_) {
			return $this->getType($node->expr);
		}

		if (
			$node instanceof Expr\AssignOp\ShiftLeft
			|| $node instanceof Expr\BinaryOp\ShiftLeft
			|| $node instanceof Expr\AssignOp\ShiftRight
			|| $node instanceof Expr\BinaryOp\ShiftRight
		) {
			if ($node instanceof Node\Expr\AssignOp) {
				$left = $node->var;
				$right = $node->expr;
			} else {
				$left = $node->left;
				$right = $node->right;
			}

			if (TypeCombinator::union(
				$this->getType($left)->toNumber(),
				$this->getType($right)->toNumber()
			) instanceof ErrorType) {
				return new ErrorType();
			}

			return new IntegerType();
		}

		if (
			$node instanceof Expr\AssignOp\BitwiseAnd
			|| $node instanceof Expr\BinaryOp\BitwiseAnd
			|| $node instanceof Expr\AssignOp\BitwiseOr
			|| $node instanceof Expr\BinaryOp\BitwiseOr
			|| $node instanceof Expr\AssignOp\BitwiseXor
			|| $node instanceof Expr\BinaryOp\BitwiseXor
		) {
			if ($node instanceof Node\Expr\AssignOp) {
				$left = $node->var;
				$right = $node->expr;
			} else {
				$left = $node->left;
				$right = $node->right;
			}

			$leftType = $this->getType($left);
			$rightType = $this->getType($right);
			$stringType = new StringType();

			if ($stringType->isSuperTypeOf($leftType)->yes() && $stringType->isSuperTypeOf($rightType)->yes()) {
				return $stringType;
			}

			if (TypeCombinator::union($leftType->toNumber(), $rightType->toNumber()) instanceof ErrorType) {
				return new ErrorType();
			}

			return new IntegerType();
		}

		if (
			$node instanceof Node\Expr\BinaryOp\Plus
			|| $node instanceof Node\Expr\BinaryOp\Minus
			|| $node instanceof Node\Expr\BinaryOp\Mul
			|| $node instanceof Node\Expr\BinaryOp\Pow
			|| $node instanceof Node\Expr\BinaryOp\Div
			|| $node instanceof Node\Expr\AssignOp\Plus
			|| $node instanceof Node\Expr\AssignOp\Minus
			|| $node instanceof Node\Expr\AssignOp\Mul
			|| $node instanceof Node\Expr\AssignOp\Pow
			|| $node instanceof Node\Expr\AssignOp\Div
		) {
			if ($node instanceof Node\Expr\AssignOp) {
				$left = $node->var;
				$right = $node->expr;
			} else {
				$left = $node->left;
				$right = $node->right;
			}

			$leftType = $this->getType($left);
			$rightType = $this->getType($right);

			$operatorSigil = null;

			if ($node instanceof BinaryOp) {
				$operatorSigil = $node->getOperatorSigil();
			}

			if ($operatorSigil === null) {
				$operatorSigil = self::OPERATOR_SIGIL_MAP[get_class($node)] ?? null;
			}

			if ($operatorSigil !== null) {
				$operatorTypeSpecifyingExtensions = $this->operatorTypeSpecifyingExtensionRegistry->getOperatorTypeSpecifyingExtensions($operatorSigil, $leftType, $rightType);

				/** @var Type[] $extensionTypes */
				$extensionTypes = [];

				foreach ($operatorTypeSpecifyingExtensions as $extension) {
					$extensionTypes[] = $extension->specifyType($operatorSigil, $leftType, $rightType);
				}

				if (count($extensionTypes) > 0) {
					return TypeCombinator::union(...$extensionTypes);
				}
			}

			if ($node instanceof Expr\AssignOp\Plus || $node instanceof Expr\BinaryOp\Plus) {
				$leftConstantArrays = TypeUtils::getConstantArrays($leftType);
				$rightConstantArrays = TypeUtils::getConstantArrays($rightType);

				if (count($leftConstantArrays) > 0 && count($rightConstantArrays) > 0) {
					$resultTypes = [];
					foreach ($rightConstantArrays as $rightConstantArray) {
						foreach ($leftConstantArrays as $leftConstantArray) {
							$newArrayBuilder = ConstantArrayTypeBuilder::createFromConstantArray($rightConstantArray);
							foreach ($leftConstantArray->getKeyTypes() as $leftKeyType) {
								$newArrayBuilder->setOffsetValueType(
									$leftKeyType,
									$leftConstantArray->getOffsetValueType($leftKeyType)
								);
							}
							$resultTypes[] = $newArrayBuilder->getArray();
						}
					}

					return TypeCombinator::union(...$resultTypes);
				}
				$arrayType = new ArrayType(new MixedType(), new MixedType());

				if ($arrayType->isSuperTypeOf($leftType)->yes() && $arrayType->isSuperTypeOf($rightType)->yes()) {
					if ($leftType->getIterableKeyType()->equals($rightType->getIterableKeyType())) {
						// to preserve BenevolentUnionType
						$keyType = $leftType->getIterableKeyType();
					} else {
						$keyTypes = [];
						foreach ([
							$leftType->getIterableKeyType(),
							$rightType->getIterableKeyType(),
						] as $keyType) {
							$keyTypes[] = $keyType;
						}
						$keyType = TypeCombinator::union(...$keyTypes);
					}
					return new ArrayType(
						$keyType,
						TypeCombinator::union($leftType->getIterableValueType(), $rightType->getIterableValueType())
					);
				}

				if ($leftType instanceof MixedType && $rightType instanceof MixedType) {
					return new BenevolentUnionType([
						new FloatType(),
						new IntegerType(),
						new ArrayType(new MixedType(), new MixedType()),
					]);
				}
			}

			$types = TypeCombinator::union($leftType, $rightType);
			if (
				$leftType instanceof ArrayType
				|| $rightType instanceof ArrayType
				|| $types instanceof ArrayType
			) {
				return new ErrorType();
			}

			$leftNumberType = $leftType->toNumber();
			$rightNumberType = $rightType->toNumber();

			if (
				(new FloatType())->isSuperTypeOf($leftNumberType)->yes()
				|| (new FloatType())->isSuperTypeOf($rightNumberType)->yes()
			) {
				return new FloatType();
			}

			if ($node instanceof Expr\AssignOp\Pow || $node instanceof Expr\BinaryOp\Pow) {
				return new BenevolentUnionType([
					new FloatType(),
					new IntegerType(),
				]);
			}

			$resultType = TypeCombinator::union($leftNumberType, $rightNumberType);
			if ($node instanceof Expr\AssignOp\Div || $node instanceof Expr\BinaryOp\Div) {
				if ($types instanceof MixedType || $resultType instanceof IntegerType) {
					return new BenevolentUnionType([new IntegerType(), new FloatType()]);
				}

				return new UnionType([new IntegerType(), new FloatType()]);
			}

			if ($types instanceof MixedType
				|| $leftType instanceof BenevolentUnionType
				|| $rightType instanceof BenevolentUnionType
			) {
				return TypeUtils::toBenevolentUnion($resultType);
			}

			return $resultType;
		}

		if ($node instanceof LNumber) {
			return new ConstantIntegerType($node->value);
		} elseif ($node instanceof String_) {
			return new ConstantStringType($node->value);
		} elseif ($node instanceof Node\Scalar\Encapsed) {
			$constantString = new ConstantStringType('');
			foreach ($node->parts as $part) {
				if ($part instanceof EncapsedStringPart) {
					$partStringType = new ConstantStringType($part->value);
				} else {
					$partStringType = $this->getType($part)->toString();
					if ($partStringType instanceof ErrorType) {
						return new ErrorType();
					}
					if (!$partStringType instanceof ConstantStringType) {
						return new StringType();
					}
				}

				$constantString = $constantString->append($partStringType);
			}
			return $constantString;
		} elseif ($node instanceof DNumber) {
			return new ConstantFloatType($node->value);
		} elseif ($node instanceof Expr\Closure || $node instanceof Expr\ArrowFunction) {
			$parameters = [];
			$isVariadic = false;
			$firstOptionalParameterIndex = null;
			foreach ($node->params as $i => $param) {
				$isOptionalCandidate = $param->default !== null || $param->variadic;

				if ($isOptionalCandidate) {
					if ($firstOptionalParameterIndex === null) {
						$firstOptionalParameterIndex = $i;
					}
				} else {
					$firstOptionalParameterIndex = null;
				}
			}

			foreach ($node->params as $i => $param) {
				if ($param->variadic) {
					$isVariadic = true;
				}
				if (!$param->var instanceof Variable || !is_string($param->var->name)) {
					throw new \PHPStan\ShouldNotHappenException();
				}
				$parameters[] = new NativeParameterReflection(
					$param->var->name,
					$firstOptionalParameterIndex !== null && $i >= $firstOptionalParameterIndex,
					$this->getFunctionType($param->type, $param->type === null, false),
					$param->byRef
						? PassedByReference::createCreatesNewVariable()
						: PassedByReference::createNo(),
					$param->variadic,
					$param->default !== null ? $this->getType($param->default) : null
				);
			}

			if ($node instanceof Expr\ArrowFunction) {
				$returnType = $this->enterArrowFunctionWithoutReflection($node)->getType($node->expr);
				if ($node->returnType !== null) {
					$returnType = TypehintHelper::decideType($this->getFunctionType($node->returnType, false, false), $returnType);
				}
			} else {
				$returnType = $this->getFunctionType($node->returnType, $node->returnType === null, false);
			}

			return new ClosureType(
				$parameters,
				$returnType,
				$isVariadic
			);
		} elseif ($node instanceof New_) {
			if ($node->class instanceof Name) {
				$type = $this->exactInstantiation($node, $node->class->toString());
				if ($type !== null) {
					return $type;
				}

				$lowercasedClassName = strtolower($node->class->toString());
				if ($lowercasedClassName === 'static') {
					if (!$this->isInClass()) {
						return new ErrorType();
					}

					return new StaticType($this->getClassReflection()->getName());
				}
				if ($lowercasedClassName === 'parent') {
					return new NonexistentParentClassType();
				}

				return new ObjectType($node->class->toString());
			}
			if ($node->class instanceof Node\Stmt\Class_) {
				$anonymousClassReflection = $this->reflectionProvider->getAnonymousClassReflection($node->class, $this);

				return new ObjectType($anonymousClassReflection->getName());
			}

			$exprType = $this->getType($node->class);
			return $this->getTypeToInstantiateForNew($exprType);

		} elseif ($node instanceof Array_) {
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			if (count($node->items) > 256) {
				$arrayBuilder->degradeToGeneralArray();
			}
			foreach ($node->items as $arrayItem) {
				if ($arrayItem === null) {
					continue;
				}

				$valueType = $this->getType($arrayItem->value);
				if ($arrayItem->unpack) {
					if ($valueType instanceof ConstantArrayType) {
						foreach ($valueType->getValueTypes() as $innerValueType) {
							$arrayBuilder->setOffsetValueType(null, $innerValueType);
						}
					} else {
						$arrayBuilder->degradeToGeneralArray();
						$arrayBuilder->setOffsetValueType(new IntegerType(), $valueType->getIterableValueType());
					}
				} else {
					$arrayBuilder->setOffsetValueType(
						$arrayItem->key !== null ? $this->getType($arrayItem->key) : null,
						$valueType
					);
				}
			}
			return $arrayBuilder->getArray();

		} elseif ($node instanceof Int_) {
			return $this->getType($node->expr)->toInteger();
		} elseif ($node instanceof Bool_) {
			return $this->getType($node->expr)->toBoolean();
		} elseif ($node instanceof Double) {
			return $this->getType($node->expr)->toFloat();
		} elseif ($node instanceof \PhpParser\Node\Expr\Cast\String_) {
			return $this->getType($node->expr)->toString();
		} elseif ($node instanceof \PhpParser\Node\Expr\Cast\Array_) {
			return $this->getType($node->expr)->toArray();
		} elseif ($node instanceof Node\Scalar\MagicConst\Line) {
			return new ConstantIntegerType($node->getLine());
		} elseif ($node instanceof Node\Scalar\MagicConst\Class_) {
			if (!$this->isInClass()) {
				return new ConstantStringType('');
			}

			return new ConstantStringType($this->getClassReflection()->getName(), true);
		} elseif ($node instanceof Node\Scalar\MagicConst\Dir) {
			return new ConstantStringType(dirname($this->getFile()));
		} elseif ($node instanceof Node\Scalar\MagicConst\File) {
			return new ConstantStringType($this->getFile());
		} elseif ($node instanceof Node\Scalar\MagicConst\Namespace_) {
			return new ConstantStringType($this->namespace ?? '');
		} elseif ($node instanceof Node\Scalar\MagicConst\Method) {
			if ($this->isInAnonymousFunction()) {
				return new ConstantStringType('{closure}');
			}

			$function = $this->getFunction();
			if ($function === null) {
				return new ConstantStringType('');
			}
			if ($function instanceof MethodReflection) {
				return new ConstantStringType(
					sprintf('%s::%s', $function->getDeclaringClass()->getName(), $function->getName())
				);
			}

			return new ConstantStringType($function->getName());
		} elseif ($node instanceof Node\Scalar\MagicConst\Function_) {
			if ($this->isInAnonymousFunction()) {
				return new ConstantStringType('{closure}');
			}
			$function = $this->getFunction();
			if ($function === null) {
				return new ConstantStringType('');
			}

			return new ConstantStringType($function->getName());
		} elseif ($node instanceof Node\Scalar\MagicConst\Trait_) {
			if (!$this->isInTrait()) {
				return new ConstantStringType('');
			}
			return new ConstantStringType($this->getTraitReflection()->getName(), true);
		} elseif ($node instanceof Object_) {
			$castToObject = static function (Type $type): Type {
				if ((new ObjectWithoutClassType())->isSuperTypeOf($type)->yes()) {
					return $type;
				}

				return new ObjectType('stdClass');
			};

			$exprType = $this->getType($node->expr);
			if ($exprType instanceof UnionType) {
				return TypeCombinator::union(...array_map($castToObject, $exprType->getTypes()));
			}

			return $castToObject($exprType);
		} elseif ($node instanceof Unset_) {
			return new NullType();
		} elseif ($node instanceof Expr\PostInc || $node instanceof Expr\PostDec) {
			return $this->getType($node->var);
		} elseif ($node instanceof Expr\PreInc || $node instanceof Expr\PreDec) {
			$varType = $this->getType($node->var);
			$varScalars = TypeUtils::getConstantScalars($varType);
			if (count($varScalars) > 0) {
				$newTypes = [];

				foreach ($varScalars as $scalar) {
					$varValue = $scalar->getValue();
					if ($node instanceof Expr\PreInc) {
						++$varValue;
					} else {
						--$varValue;
					}

					$newTypes[] = $this->getTypeFromValue($varValue);
				}
				return TypeCombinator::union(...$newTypes);
			} elseif ($varType instanceof IntegerRangeType) {
				return $varType->shift($node instanceof Expr\PreInc ? +1 : -1);
			}

			$stringType = new StringType();
			if ($stringType->isSuperTypeOf($varType)->yes()) {
				return $stringType;
			}

			return $varType->toNumber();
		} elseif ($node instanceof Expr\Yield_) {
			$functionReflection = $this->getFunction();
			if ($functionReflection === null) {
				return new MixedType();
			}

			$returnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
			if (!$returnType instanceof TypeWithClassName) {
				return new MixedType();
			}

			$generatorSendType = GenericTypeVariableResolver::getType($returnType, \Generator::class, 'TSend');
			if ($generatorSendType === null) {
				return new MixedType();
			}

			return $generatorSendType;
		} elseif ($node instanceof Expr\YieldFrom) {
			$yieldFromType = $this->getType($node->expr);

			if (!$yieldFromType instanceof TypeWithClassName) {
				return new MixedType();
			}

			$generatorReturnType = GenericTypeVariableResolver::getType($yieldFromType, \Generator::class, 'TReturn');
			if ($generatorReturnType === null) {
				return new MixedType();
			}

			return $generatorReturnType;
		} elseif ($node instanceof Expr\Match_) {
			$cond = $node->cond;
			$types = [];

			$matchScope = $this;
			foreach ($node->arms as $arm) {
				if ($arm->conds === null) {
					$types[] = $matchScope->getType($arm->body);
					continue;
				}

				if (count($arm->conds) === 0) {
					throw new \PHPStan\ShouldNotHappenException();
				}

				$filteringExpr = null;
				foreach ($arm->conds as $armCond) {
					$armCondExpr = new BinaryOp\Identical($cond, $armCond);
					if ($filteringExpr === null) {
						$filteringExpr = $armCondExpr;
						continue;
					}

					$filteringExpr = new BinaryOp\BooleanOr($filteringExpr, $armCondExpr);
				}

				$truthyScope = $matchScope->filterByTruthyValue($filteringExpr);
				$types[] = $truthyScope->getType($arm->body);

				$matchScope = $matchScope->filterByFalseyValue($filteringExpr);
			}

			return TypeCombinator::union(...$types);
		}

		$exprString = $this->getNodeKey($node);
		if (isset($this->moreSpecificTypes[$exprString]) && $this->moreSpecificTypes[$exprString]->getCertainty()->yes()) {
			return $this->moreSpecificTypes[$exprString]->getType();
		}

		if ($node instanceof ConstFetch) {
			$constName = (string) $node->name;
			$loweredConstName = strtolower($constName);
			if ($loweredConstName === 'true') {
				return new \PHPStan\Type\Constant\ConstantBooleanType(true);
			} elseif ($loweredConstName === 'false') {
				return new \PHPStan\Type\Constant\ConstantBooleanType(false);
			} elseif ($loweredConstName === 'null') {
				return new NullType();
			}

			if ($node->name->isFullyQualified()) {
				if (array_key_exists($node->name->toCodeString(), $this->constantTypes)) {
					return $this->resolveConstantType($node->name->toString(), $this->constantTypes[$node->name->toCodeString()]);
				}
			}

			if ($this->getNamespace() !== null) {
				$constantName = new FullyQualified([$this->getNamespace(), $constName]);
				if (array_key_exists($constantName->toCodeString(), $this->constantTypes)) {
					return $this->resolveConstantType($constantName->toString(), $this->constantTypes[$constantName->toCodeString()]);
				}
			}

			$constantName = new FullyQualified($constName);
			if (array_key_exists($constantName->toCodeString(), $this->constantTypes)) {
				return $this->resolveConstantType($constantName->toString(), $this->constantTypes[$constantName->toCodeString()]);
			}

			if ($this->reflectionProvider->hasConstant($node->name, $this)) {
				/** @var string $resolvedConstantName */
				$resolvedConstantName = $this->reflectionProvider->resolveConstantName($node->name, $this);
				if ($resolvedConstantName === 'DIRECTORY_SEPARATOR') {
					return new UnionType([
						new ConstantStringType('/'),
						new ConstantStringType('\\'),
					]);
				}
				if ($resolvedConstantName === 'PATH_SEPARATOR') {
					return new UnionType([
						new ConstantStringType(':'),
						new ConstantStringType(';'),
					]);
				}
				if ($resolvedConstantName === 'PHP_EOL') {
					return new UnionType([
						new ConstantStringType("\n"),
						new ConstantStringType("\r\n"),
					]);
				}
				if ($resolvedConstantName === '__COMPILER_HALT_OFFSET__') {
					return new IntegerType();
				}

				$constantType = $this->reflectionProvider->getConstant($node->name, $this)->getValueType();

				return $this->resolveConstantType($resolvedConstantName, $constantType);
			}

			return new ErrorType();
		} elseif ($node instanceof Node\Expr\ClassConstFetch && $node->name instanceof Node\Identifier) {
			$constantName = $node->name->name;
			if ($node->class instanceof Name) {
				$constantClass = (string) $node->class;
				$constantClassType = new ObjectType($constantClass);
				$namesToResolve = [
					'self',
					'parent',
				];
				if ($this->isInClass()) {
					if ($this->getClassReflection()->isFinal()) {
						$namesToResolve[] = 'static';
					} elseif (strtolower($constantClass) === 'static') {
						if (strtolower($constantName) === 'class') {
							return new GenericClassStringType(new StaticType($this->getClassReflection()->getName()));
						}
						return new MixedType();
					}
				}
				if (in_array(strtolower($constantClass), $namesToResolve, true)) {
					$resolvedName = $this->resolveName($node->class);
					if ($resolvedName === 'parent' && strtolower($constantName) === 'class') {
						return new ClassStringType();
					}
					$constantClassType = new ObjectType($resolvedName);
				}

				if (strtolower($constantName) === 'class') {
					return new ConstantStringType($constantClassType->getClassName(), true);
				}
			} else {
				$constantClassType = $this->getType($node->class);
			}

			$referencedClasses = TypeUtils::getDirectClassNames($constantClassType);
			if (strtolower($constantName) === 'class') {
				if (count($referencedClasses) === 0) {
					return new ErrorType();
				}
				$classTypes = [];
				foreach ($referencedClasses as $referencedClass) {
					$classTypes[] = new GenericClassStringType(new ObjectType($referencedClass));
				}

				return TypeCombinator::union(...$classTypes);
			}
			$types = [];
			foreach ($referencedClasses as $referencedClass) {
				if (!$this->reflectionProvider->hasClass($referencedClass)) {
					continue;
				}

				$propertyClassReflection = $this->reflectionProvider->getClass($referencedClass);
				if (!$propertyClassReflection->hasConstant($constantName)) {
					continue;
				}

				$constantType = $propertyClassReflection->getConstant($constantName)->getValueType();
				if (
					$constantType instanceof ConstantType
					&& in_array(sprintf('%s::%s', $propertyClassReflection->getName(), $constantName), $this->dynamicConstantNames, true)
				) {
					$constantType = $constantType->generalize();
				}
				$types[] = $constantType;
			}

			if (count($types) > 0) {
				return TypeCombinator::union(...$types);
			}

			if (!$constantClassType->hasConstant($constantName)->yes()) {
				return new ErrorType();
			}

			return $constantClassType->getConstant($constantName)->getValueType();
		}

		if ($node instanceof Expr\Ternary) {
			if ($node->if === null) {
				$conditionType = $this->getType($node->cond);
				$booleanConditionType = $conditionType->toBoolean();
				if ($booleanConditionType instanceof ConstantBooleanType) {
					if ($booleanConditionType->getValue()) {
						return $this->filterByTruthyValue($node->cond, true)->getType($node->cond);
					}

					return $this->filterByFalseyValue($node->cond, true)->getType($node->else);
				}
				return TypeCombinator::union(
					$this->filterByTruthyValue($node->cond, true)->getType($node->cond),
					$this->filterByFalseyValue($node->cond, true)->getType($node->else)
				);
			}

			$booleanConditionType = $this->getType($node->cond)->toBoolean();
			if ($booleanConditionType instanceof ConstantBooleanType) {
				if ($booleanConditionType->getValue()) {
					return $this->filterByTruthyValue($node->cond)->getType($node->if);
				}

				return $this->filterByFalseyValue($node->cond)->getType($node->else);
			}

			return TypeCombinator::union(
				$this->filterByTruthyValue($node->cond)->getType($node->if),
				$this->filterByFalseyValue($node->cond)->getType($node->else)
			);
		}

		if ($node instanceof Variable && is_string($node->name)) {
			if ($this->hasVariableType($node->name)->no()) {
				return new ErrorType();
			}

			return $this->getVariableType($node->name);
		}

		if ($node instanceof Expr\ArrayDimFetch && $node->dim !== null) {
			return $this->getNullsafeShortCircuitingType(
				$node->var,
				$this->getTypeFromArrayDimFetch(
					$node,
					$this->getType($node->dim),
					$this->getType($node->var)
				)
			);
		}

		if ($node instanceof MethodCall && $node->name instanceof Node\Identifier) {
			$typeCallback = function () use ($node): Type {
				$methodCalledOnType = $this->getType($node->var);
				$methodName = $node->name->name;
				$map = function (Type $type, callable $traverse) use ($methodName, $node): Type {
					if ($type instanceof UnionType) {
						return $traverse($type);
					}
					if ($type instanceof IntersectionType) {
						$returnTypes = [];
						foreach ($type->getTypes() as $innerType) {
							$returnType = $this->methodCallReturnType(
								$type,
								$innerType,
								$methodName,
								$node
							);
							if ($returnType === null) {
								continue;
							}

							$returnTypes[] = $returnType;
						}
						if (count($returnTypes) === 0) {
							return new NeverType();
						}
						return TypeCombinator::intersect(...$returnTypes);
					}
					return $this->methodCallReturnType(
						$type,
						$type,
						$methodName,
						$node
					) ?? new NeverType();
				};
				$returnType = TypeTraverser::map($methodCalledOnType, $map);
				if ($returnType instanceof NeverType && !$returnType->isExplicit()) {
					return new ErrorType();
				}
				return $returnType;
			};

			return $this->getNullsafeShortCircuitingType($node->var, $typeCallback());
		}

		if ($node instanceof Expr\NullsafeMethodCall) {
			return TypeCombinator::union(
				$this->filterByTruthyValue(new BinaryOp\NotIdentical($node->var, new ConstFetch(new Name('null'))))
					->getType(new MethodCall($node->var, $node->name, $node->args)),
				new NullType()
			);
		}

		if ($node instanceof Expr\StaticCall && $node->name instanceof Node\Identifier) {
			$typeCallback = function () use ($node): Type {
				if ($node->class instanceof Name) {
					$staticMethodCalledOnType = new ObjectType($this->resolveName($node->class));
				} else {
					$staticMethodCalledOnType = $this->getType($node->class);
					if ($staticMethodCalledOnType instanceof GenericClassStringType) {
						$staticMethodCalledOnType = $staticMethodCalledOnType->getGenericType();
					}
				}

				$methodName = $node->name->toString();
				$map = function (Type $type, callable $traverse) use ($methodName, $node): Type {
					if ($type instanceof UnionType) {
						return $traverse($type);
					}
					if ($type instanceof IntersectionType) {
						$returnTypes = [];
						foreach ($type->getTypes() as $innerType) {
							$returnType = $this->methodCallReturnType(
								$type,
								$innerType,
								$methodName,
								$node
							);
							if ($returnType === null) {
								continue;
							}

							$returnTypes[] = $returnType;
						}
						if (count($returnTypes) === 0) {
							return new NeverType();
						}
						return TypeCombinator::intersect(...$returnTypes);
					}
					return $this->methodCallReturnType(
						$type,
						$type,
						$methodName,
						$node
					) ?? new NeverType();
				};
				$returnType = TypeTraverser::map($staticMethodCalledOnType, $map);
				if ($returnType instanceof NeverType && !$returnType->isExplicit()) {
					return new ErrorType();
				}
				return $returnType;
			};

			$callType = $typeCallback();
			if ($node->class instanceof Expr) {
				return $this->getNullsafeShortCircuitingType($node->class, $callType);
			}

			return $callType;
		}

		if ($node instanceof PropertyFetch && $node->name instanceof Node\Identifier) {
			$typeCallback = function () use ($node): Type {
				$propertyFetchedOnType = $this->getType($node->var);
				$propertyName = $node->name->name;
				$map = function (Type $type, callable $traverse) use ($propertyName, $node): Type {
					if ($type instanceof UnionType) {
						return $traverse($type);
					}
					if ($type instanceof IntersectionType) {
						$returnTypes = [];
						foreach ($type->getTypes() as $innerType) {
							$returnType = $this->propertyFetchType(
								$innerType,
								$propertyName,
								$node
							);
							if ($returnType === null) {
								continue;
							}

							$returnTypes[] = $returnType;
						}
						if (count($returnTypes) === 0) {
							return new NeverType();
						}
						return TypeCombinator::intersect(...$returnTypes);
					}
					return $this->propertyFetchType(
						$type,
						$propertyName,
						$node
					) ?? new NeverType();
				};

				$returnType = TypeTraverser::map($propertyFetchedOnType, $map);
				if ($returnType instanceof NeverType) {
					return new ErrorType();
				}
				return $returnType;
			};

			return $this->getNullsafeShortCircuitingType($node->var, $typeCallback());
		}

		if ($node instanceof Expr\NullsafePropertyFetch) {
			return TypeCombinator::union(
				$this->filterByTruthyValue(new BinaryOp\NotIdentical($node->var, new ConstFetch(new Name('null'))))
					->getType(new PropertyFetch($node->var, $node->name)),
				new NullType()
			);
		}

		if (
			$node instanceof Expr\StaticPropertyFetch
			&& $node->name instanceof Node\VarLikeIdentifier
		) {
			$typeCallback = function () use ($node): Type {
				if ($node->class instanceof Name) {
					$staticPropertyFetchedOnType = new ObjectType($this->resolveName($node->class));
				} else {
					$staticPropertyFetchedOnType = $this->getType($node->class);
					if ($staticPropertyFetchedOnType instanceof GenericClassStringType) {
						$staticPropertyFetchedOnType = $staticPropertyFetchedOnType->getGenericType();
					}
				}

				$staticPropertyName = $node->name->toString();
				$map = function (Type $type, callable $traverse) use ($staticPropertyName, $node): Type {
					if ($type instanceof UnionType) {
						return $traverse($type);
					}
					if ($type instanceof IntersectionType) {
						$returnTypes = [];
						foreach ($type->getTypes() as $innerType) {
							$returnType = $this->propertyFetchType(
								$innerType,
								$staticPropertyName,
								$node
							);
							if ($returnType === null) {
								continue;
							}

							$returnTypes[] = $returnType;
						}
						if (count($returnTypes) === 0) {
							return new NeverType();
						}
						return TypeCombinator::intersect(...$returnTypes);
					}
					return $this->propertyFetchType(
						$type,
						$staticPropertyName,
						$node
					) ?? new NeverType();
				};

				$returnType = TypeTraverser::map($staticPropertyFetchedOnType, $map);
				if ($returnType instanceof NeverType) {
					return new ErrorType();
				}
				return $returnType;
			};

			$fetchType = $typeCallback();
			if ($node->class instanceof Expr) {
				return $this->getNullsafeShortCircuitingType($node->class, $fetchType);
			}

			return $fetchType;
		}

		if ($node instanceof FuncCall) {
			if ($node->name instanceof Expr) {
				$calledOnType = $this->getType($node->name);
				if ($calledOnType->isCallable()->no()) {
					return new ErrorType();
				}

				return ParametersAcceptorSelector::selectFromArgs(
					$this,
					$node->args,
					$calledOnType->getCallableParametersAcceptors($this)
				)->getReturnType();
			}

			if (!$this->reflectionProvider->hasFunction($node->name, $this)) {
				return new ErrorType();
			}

			$functionReflection = $this->reflectionProvider->getFunction($node->name, $this);
			foreach ($this->dynamicReturnTypeExtensionRegistry->getDynamicFunctionReturnTypeExtensions() as $dynamicFunctionReturnTypeExtension) {
				if (!$dynamicFunctionReturnTypeExtension->isFunctionSupported($functionReflection)) {
					continue;
				}

				return $dynamicFunctionReturnTypeExtension->getTypeFromFunctionCall($functionReflection, $node, $this);
			}

			return ParametersAcceptorSelector::selectFromArgs(
				$this,
				$node->args,
				$functionReflection->getVariants()
			)->getReturnType();
		}

		return new MixedType();
	}

	private function getNullsafeShortCircuitingType(Expr $var, Type $type): Type
	{
		if ($var instanceof Expr\NullsafePropertyFetch || $var instanceof Expr\NullsafeMethodCall) {
			return TypeCombinator::addNull($type);
		}

		if ($var instanceof Expr\ArrayDimFetch) {
			return $this->getNullsafeShortCircuitingType($var->var, $type);
		}

		if ($var instanceof PropertyFetch) {
			return $this->getNullsafeShortCircuitingType($var->var, $type);
		}

		if ($var instanceof Expr\StaticPropertyFetch && $var->class instanceof Expr) {
			return $this->getNullsafeShortCircuitingType($var->class, $type);
		}

		if ($var instanceof MethodCall) {
			return $this->getNullsafeShortCircuitingType($var->var, $type);
		}

		if ($var instanceof Expr\StaticCall && $var->class instanceof Expr) {
			return $this->getNullsafeShortCircuitingType($var->class, $type);
		}

		return $type;
	}

	private function resolveConstantType(string $constantName, Type $constantType): Type
	{
		if ($constantType instanceof ConstantType && in_array($constantName, $this->dynamicConstantNames, true)) {
			return $constantType->generalize();
		}

		return $constantType;
	}

	public function getNativeType(Expr $expr): Type
	{
		$key = $this->getNodeKey($expr);

		if (array_key_exists($key, $this->nativeExpressionTypes)) {
			return $this->nativeExpressionTypes[$key];
		}

		if ($expr instanceof Expr\ArrayDimFetch && $expr->dim !== null) {
			return $this->getNullsafeShortCircuitingType(
				$expr->var,
				$this->getTypeFromArrayDimFetch(
					$expr,
					$this->getNativeType($expr->dim),
					$this->getNativeType($expr->var)
				)
			);
		}

		return $this->getType($expr);
	}

	public function doNotTreatPhpDocTypesAsCertain(): Scope
	{
		if (!$this->treatPhpDocTypesAsCertain) {
			return $this;
		}

		return new self(
			$this->scopeFactory,
			$this->reflectionProvider,
			$this->dynamicReturnTypeExtensionRegistry,
			$this->operatorTypeSpecifyingExtensionRegistry,
			$this->printer,
			$this->typeSpecifier,
			$this->propertyReflectionFinder,
			$this->parser,
			$this->context,
			$this->declareStrictTypes,
			$this->constantTypes,
			$this->function,
			$this->namespace,
			$this->variableTypes,
			$this->moreSpecificTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			$this->currentlyAssignedExpressions,
			$this->nativeExpressionTypes,
			$this->inFunctionCallsStack,
			$this->dynamicConstantNames,
			false,
			$this->afterExtractCall,
			$this->parentScope
		);
	}

	private function promoteNativeTypes(): self
	{
		$variableTypes = $this->variableTypes;
		foreach ($this->nativeExpressionTypes as $expressionType => $type) {
			if (substr($expressionType, 0, 1) !== '$') {
				throw new \PHPStan\ShouldNotHappenException();
			}

			$variableName = substr($expressionType, 1);
			$has = $this->hasVariableType($variableName);
			if ($has->no()) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			$variableTypes[$variableName] = new VariableTypeHolder($type, $has);
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->declareStrictTypes,
			$this->constantTypes,
			$this->function,
			$this->namespace,
			$variableTypes,
			$this->moreSpecificTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			$this->currentlyAssignedExpressions,
			[]
		);
	}

	/**
	 * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch
	 * @return bool
	 */
	private function hasPropertyNativeType($propertyFetch): bool
	{
		$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($propertyFetch, $this);
		if ($propertyReflection === null) {
			return false;
		}

		if (!$propertyReflection->isNative()) {
			return false;
		}

		return !$propertyReflection->getNativeType() instanceof MixedType;
	}

	protected function getTypeFromArrayDimFetch(
		Expr\ArrayDimFetch $arrayDimFetch,
		Type $offsetType,
		Type $offsetAccessibleType
	): Type
	{
		if ($arrayDimFetch->dim === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		if ((new ObjectType(\ArrayAccess::class))->isSuperTypeOf($offsetAccessibleType)->yes()) {
			return $this->getType(
				new MethodCall(
					$arrayDimFetch->var,
					new Node\Identifier('offsetGet'),
					[
						new Node\Arg($arrayDimFetch->dim),
					]
				)
			);
		}

		return $offsetAccessibleType->getOffsetValueType($offsetType);
	}

	private function calculateFromScalars(Expr $node, ConstantScalarType $leftType, ConstantScalarType $rightType): Type
	{
		if ($leftType instanceof StringType && $rightType instanceof StringType) {
			/** @var string $leftValue */
			$leftValue = $leftType->getValue();
			/** @var string $rightValue */
			$rightValue = $rightType->getValue();

			if ($node instanceof Expr\BinaryOp\BitwiseAnd || $node instanceof Expr\AssignOp\BitwiseAnd) {
				return $this->getTypeFromValue($leftValue & $rightValue);
			}

			if ($node instanceof Expr\BinaryOp\BitwiseOr || $node instanceof Expr\AssignOp\BitwiseOr) {
				return $this->getTypeFromValue($leftValue | $rightValue);
			}

			if ($node instanceof Expr\BinaryOp\BitwiseXor || $node instanceof Expr\AssignOp\BitwiseXor) {
				return $this->getTypeFromValue($leftValue ^ $rightValue);
			}
		}

		$leftValue = $leftType->getValue();
		$rightValue = $rightType->getValue();

		if ($node instanceof Node\Expr\BinaryOp\Spaceship) {
			return $this->getTypeFromValue($leftValue <=> $rightValue);
		}

		$leftNumberType = $leftType->toNumber();
		$rightNumberType = $rightType->toNumber();
		if (TypeCombinator::union($leftNumberType, $rightNumberType) instanceof ErrorType) {
			return new ErrorType();
		}

		if (!$leftNumberType instanceof ConstantScalarType || !$rightNumberType instanceof ConstantScalarType) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		/** @var float|int $leftNumberValue */
		$leftNumberValue = $leftNumberType->getValue();

		/** @var float|int $rightNumberValue */
		$rightNumberValue = $rightNumberType->getValue();

		if ($node instanceof Node\Expr\BinaryOp\Plus || $node instanceof Node\Expr\AssignOp\Plus) {
			return $this->getTypeFromValue($leftNumberValue + $rightNumberValue);
		}

		if ($node instanceof Node\Expr\BinaryOp\Minus || $node instanceof Node\Expr\AssignOp\Minus) {
			return $this->getTypeFromValue($leftNumberValue - $rightNumberValue);
		}

		if ($node instanceof Node\Expr\BinaryOp\Mul || $node instanceof Node\Expr\AssignOp\Mul) {
			return $this->getTypeFromValue($leftNumberValue * $rightNumberValue);
		}

		if ($node instanceof Node\Expr\BinaryOp\Pow || $node instanceof Node\Expr\AssignOp\Pow) {
			return $this->getTypeFromValue($leftNumberValue ** $rightNumberValue);
		}

		if ($node instanceof Node\Expr\BinaryOp\Div || $node instanceof Node\Expr\AssignOp\Div) {
			return $this->getTypeFromValue($leftNumberValue / $rightNumberValue);
		}

		if ($node instanceof Node\Expr\BinaryOp\Mod || $node instanceof Node\Expr\AssignOp\Mod) {
			return $this->getTypeFromValue($leftNumberValue % $rightNumberValue);
		}

		if ($node instanceof Expr\BinaryOp\ShiftLeft || $node instanceof Expr\AssignOp\ShiftLeft) {
			return $this->getTypeFromValue($leftNumberValue << $rightNumberValue);
		}

		if ($node instanceof Expr\BinaryOp\ShiftRight || $node instanceof Expr\AssignOp\ShiftRight) {
			return $this->getTypeFromValue($leftNumberValue >> $rightNumberValue);
		}

		if ($node instanceof Expr\BinaryOp\BitwiseAnd || $node instanceof Expr\AssignOp\BitwiseAnd) {
			return $this->getTypeFromValue($leftNumberValue & $rightNumberValue);
		}

		if ($node instanceof Expr\BinaryOp\BitwiseOr || $node instanceof Expr\AssignOp\BitwiseOr) {
			return $this->getTypeFromValue($leftNumberValue | $rightNumberValue);
		}

		if ($node instanceof Expr\BinaryOp\BitwiseXor || $node instanceof Expr\AssignOp\BitwiseXor) {
			return $this->getTypeFromValue($leftNumberValue ^ $rightNumberValue);
		}

		return new MixedType();
	}

	private function resolveExactName(Name $name): ?string
	{
		$originalClass = (string) $name;

		switch (strtolower($originalClass)) {
			case 'self':
				if (!$this->isInClass()) {
					return null;
				}
				return $this->getClassReflection()->getName();
			case 'parent':
				if (!$this->isInClass()) {
					return null;
				}
				$currentClassReflection = $this->getClassReflection();
				if ($currentClassReflection->getParentClass() !== false) {
					return $currentClassReflection->getParentClass()->getName();
				}
				return null;
			case 'static':
				return null;
		}

		return $originalClass;
	}

	public function resolveName(Name $name): string
	{
		$originalClass = (string) $name;
		if ($this->isInClass()) {
			if (in_array(strtolower($originalClass), [
				'self',
				'static',
			], true)) {
				return $this->getClassReflection()->getName();
			} elseif ($originalClass === 'parent') {
				$currentClassReflection = $this->getClassReflection();
				if ($currentClassReflection->getParentClass() !== false) {
					return $currentClassReflection->getParentClass()->getName();
				}
			}
		}

		return $originalClass;
	}

	/**
	 * @param mixed $value
	 */
	public function getTypeFromValue($value): Type
	{
		return ConstantTypeHelper::getTypeFromValue($value);
	}

	public function isSpecified(Expr $node): bool
	{
		$exprString = $this->getNodeKey($node);

		return isset($this->moreSpecificTypes[$exprString])
			&& $this->moreSpecificTypes[$exprString]->getCertainty()->yes();
	}

	/**
	 * @param MethodReflection|FunctionReflection $reflection
	 * @return self
	 */
	public function pushInFunctionCall($reflection): self
	{
		$stack = $this->inFunctionCallsStack;
		$stack[] = $reflection;

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$this->getVariableTypes(),
			$this->moreSpecificTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$this->nativeExpressionTypes,
			$stack,
			$this->afterExtractCall,
			$this->parentScope
		);
	}

	public function popInFunctionCall(): self
	{
		$stack = $this->inFunctionCallsStack;
		array_pop($stack);

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$this->getVariableTypes(),
			$this->moreSpecificTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$this->nativeExpressionTypes,
			$stack,
			$this->afterExtractCall,
			$this->parentScope
		);
	}

	public function isInClassExists(string $className): bool
	{
		foreach ($this->inFunctionCallsStack as $inFunctionCall) {
			if (!$inFunctionCall instanceof FunctionReflection) {
				continue;
			}

			if (in_array($inFunctionCall->getName(), [
				'class_exists',
				'interface_exists',
				'trait_exists',
			], true)) {
				return true;
			}
		}
		$expr = new FuncCall(new FullyQualified('class_exists'), [
			new Arg(new String_(ltrim($className, '\\'))),
		]);

		return (new ConstantBooleanType(true))->isSuperTypeOf($this->getType($expr))->yes();
	}

	public function enterClass(ClassReflection $classReflection): self
	{
		return $this->scopeFactory->create(
			$this->context->enterClass($classReflection),
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			null,
			$this->getNamespace(),
			[
				'this' => VariableTypeHolder::createYes(new ThisType($classReflection)),
			]
		);
	}

	public function enterTrait(ClassReflection $traitReflection): self
	{
		return $this->scopeFactory->create(
			$this->context->enterTrait($traitReflection),
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$this->getVariableTypes(),
			$this->moreSpecificTypes,
			[],
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection
		);
	}

	/**
	 * @param Node\Stmt\ClassMethod $classMethod
	 * @param TemplateTypeMap $templateTypeMap
	 * @param Type[] $phpDocParameterTypes
	 * @param Type|null $phpDocReturnType
	 * @param Type|null $throwType
	 * @param string|null $deprecatedDescription
	 * @param bool $isDeprecated
	 * @param bool $isInternal
	 * @param bool $isFinal
	 * @param bool|null $isPure
	 * @return self
	 */
	public function enterClassMethod(
		Node\Stmt\ClassMethod $classMethod,
		TemplateTypeMap $templateTypeMap,
		array $phpDocParameterTypes,
		?Type $phpDocReturnType,
		?Type $throwType,
		?string $deprecatedDescription,
		bool $isDeprecated,
		bool $isInternal,
		bool $isFinal,
		?bool $isPure = null
	): self
	{
		if (!$this->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return $this->enterFunctionLike(
			new PhpMethodFromParserNodeReflection(
				$this->getClassReflection(),
				$classMethod,
				$templateTypeMap,
				$this->getRealParameterTypes($classMethod),
				array_map(static function (Type $type): Type {
					return TemplateTypeHelper::toArgument($type);
				}, $phpDocParameterTypes),
				$this->getRealParameterDefaultValues($classMethod),
				$this->getFunctionType($classMethod->returnType, $classMethod->returnType === null, false),
				$phpDocReturnType !== null ? TemplateTypeHelper::toArgument($phpDocReturnType) : null,
				$throwType,
				$deprecatedDescription,
				$isDeprecated,
				$isInternal,
				$isFinal,
				$isPure
			),
			!$classMethod->isStatic()
		);
	}

	/**
	 * @param Node\FunctionLike $functionLike
	 * @return Type[]
	 */
	private function getRealParameterTypes(Node\FunctionLike $functionLike): array
	{
		$realParameterTypes = [];
		foreach ($functionLike->getParams() as $parameter) {
			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			$realParameterTypes[$parameter->var->name] = $this->getFunctionType(
				$parameter->type,
				$this->isParameterValueNullable($parameter),
				false
			);
		}

		return $realParameterTypes;
	}

	/**
	 * @param Node\FunctionLike $functionLike
	 * @return Type[]
	 */
	private function getRealParameterDefaultValues(Node\FunctionLike $functionLike): array
	{
		$realParameterDefaultValues = [];
		foreach ($functionLike->getParams() as $parameter) {
			if ($parameter->default === null) {
				continue;
			}
			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			$realParameterDefaultValues[$parameter->var->name] = $this->getType($parameter->default);
		}

		return $realParameterDefaultValues;
	}

	/**
	 * @param Node\Stmt\Function_ $function
	 * @param TemplateTypeMap $templateTypeMap
	 * @param Type[] $phpDocParameterTypes
	 * @param Type|null $phpDocReturnType
	 * @param Type|null $throwType
	 * @param string|null $deprecatedDescription
	 * @param bool $isDeprecated
	 * @param bool $isInternal
	 * @param bool $isFinal
	 * @param bool|null $isPure
	 * @return self
	 */
	public function enterFunction(
		Node\Stmt\Function_ $function,
		TemplateTypeMap $templateTypeMap,
		array $phpDocParameterTypes,
		?Type $phpDocReturnType,
		?Type $throwType,
		?string $deprecatedDescription,
		bool $isDeprecated,
		bool $isInternal,
		bool $isFinal,
		?bool $isPure = null
	): self
	{
		return $this->enterFunctionLike(
			new PhpFunctionFromParserNodeReflection(
				$function,
				$templateTypeMap,
				$this->getRealParameterTypes($function),
				array_map(static function (Type $type): Type {
					return TemplateTypeHelper::toArgument($type);
				}, $phpDocParameterTypes),
				$this->getRealParameterDefaultValues($function),
				$this->getFunctionType($function->returnType, $function->returnType === null, false),
				$phpDocReturnType !== null ? TemplateTypeHelper::toArgument($phpDocReturnType) : null,
				$throwType,
				$deprecatedDescription,
				$isDeprecated,
				$isInternal,
				$isFinal,
				$isPure
			),
			false
		);
	}

	private function enterFunctionLike(
		PhpFunctionFromParserNodeReflection $functionReflection,
		bool $preserveThis
	): self
	{
		$variableTypes = $this->getVariableTypes();
		$nativeExpressionTypes = [];
		foreach (ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getParameters() as $parameter) {
			$parameterType = $parameter->getType();
			if ($parameter->isVariadic()) {
				$parameterType = new ArrayType(new IntegerType(), $parameterType);
			}
			$variableTypes[$parameter->getName()] = VariableTypeHolder::createYes($parameterType);
			$nativeExpressionTypes[sprintf('$%s', $parameter->getName())] = $parameter->getNativeType();
		}

		if (!$preserveThis && array_key_exists('this', $variableTypes)) {
			unset($variableTypes['this']);
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$functionReflection,
			$this->getNamespace(),
			$variableTypes,
			[],
			[],
			null,
			null,
			true,
			[],
			$nativeExpressionTypes
		);
	}

	public function enterNamespace(string $namespaceName): self
	{
		return $this->scopeFactory->create(
			$this->context->beginFile(),
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			null,
			$namespaceName
		);
	}

	public function enterClosureBind(?Type $thisType, string $scopeClass): self
	{
		$variableTypes = $this->getVariableTypes();

		if ($thisType !== null) {
			$variableTypes['this'] = VariableTypeHolder::createYes($thisType);
		} else {
			unset($variableTypes['this']);
		}

		if ($scopeClass === 'static' && $this->isInClass()) {
			$scopeClass = $this->getClassReflection()->getName();
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$variableTypes,
			$this->moreSpecificTypes,
			$this->conditionalExpressions,
			$scopeClass,
			$this->anonymousFunctionReflection
		);
	}

	public function restoreOriginalScopeAfterClosureBind(self $originalScope): self
	{
		$variableTypes = $this->getVariableTypes();
		if (isset($originalScope->variableTypes['this'])) {
			$variableTypes['this'] = $originalScope->variableTypes['this'];
		} else {
			unset($variableTypes['this']);
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$variableTypes,
			$this->moreSpecificTypes,
			$this->conditionalExpressions,
			$originalScope->inClosureBindScopeClass,
			$this->anonymousFunctionReflection
		);
	}

	public function enterClosureCall(Type $thisType): self
	{
		$variableTypes = $this->getVariableTypes();
		$variableTypes['this'] = VariableTypeHolder::createYes($thisType);

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$variableTypes,
			$this->moreSpecificTypes,
			$this->conditionalExpressions,
			$thisType instanceof TypeWithClassName ? $thisType->getClassName() : null,
			$this->anonymousFunctionReflection
		);
	}

	public function isInClosureBind(): bool
	{
		return $this->inClosureBindScopeClass !== null;
	}

	/**
	 * @param \PhpParser\Node\Expr\Closure $closure
	 * @param \PHPStan\Reflection\ParameterReflection[]|null $callableParameters
	 * @return self
	 */
	public function enterAnonymousFunction(
		Expr\Closure $closure,
		?array $callableParameters = null
	): self
	{
		$variableTypes = [];
		foreach ($closure->params as $i => $parameter) {
			if ($callableParameters === null || $parameter->type !== null) {
				$isNullable = $this->isParameterValueNullable($parameter);
				$parameterType = $this->getFunctionType($parameter->type, $isNullable, $parameter->variadic);
			} elseif (isset($callableParameters[$i])) {
				$parameterType = $callableParameters[$i]->getType();
			} elseif (count($callableParameters) > 0) {
				$lastParameter = $callableParameters[count($callableParameters) - 1];
				if ($lastParameter->isVariadic()) {
					$parameterType = $lastParameter->getType();
				} else {
					$parameterType = new MixedType();
				}
			} else {
				$parameterType = new MixedType();
			}

			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			$variableTypes[$parameter->var->name] = VariableTypeHolder::createYes(
				$parameterType
			);
		}

		$nativeTypes = [];
		$moreSpecificTypes = [];
		foreach ($closure->uses as $use) {
			if (!is_string($use->var->name)) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			if ($use->byRef) {
				continue;
			}
			$variableName = $use->var->name;
			if ($this->hasVariableType($variableName)->no()) {
				$variableType = new ErrorType();
			} else {
				$variableType = $this->getVariableType($variableName);
				$nativeTypes[sprintf('$%s', $variableName)] = $this->getNativeType($use->var);
			}
			$variableTypes[$variableName] = VariableTypeHolder::createYes($variableType);
			foreach ($this->moreSpecificTypes as $exprString => $moreSpecificType) {
				$matches = \Nette\Utils\Strings::matchAll((string) $exprString, '#^\$([a-zA-Z_\x7f-\xff][a-zA-Z_0-9\x7f-\xff]*)#');
				if ($matches === []) {
					continue;
				}

				$matches = array_column($matches, 1);
				if (!in_array($variableName, $matches, true)) {
					continue;
				}

				$moreSpecificTypes[$exprString] = $moreSpecificType;
			}
		}

		if ($this->hasVariableType('this')->yes() && !$closure->static) {
			$variableTypes['this'] = VariableTypeHolder::createYes($this->getVariableType('this'));
		}

		$anonymousFunctionReflection = $this->getType($closure);
		if (!$anonymousFunctionReflection instanceof ClosureType) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$variableTypes,
			$moreSpecificTypes,
			[],
			$this->inClosureBindScopeClass,
			$anonymousFunctionReflection,
			true,
			[],
			$nativeTypes,
			[],
			false,
			$this
		);
	}

	public function enterArrowFunction(Expr\ArrowFunction $arrowFunction): self
	{
		$anonymousFunctionReflection = $this->getType($arrowFunction);
		if (!$anonymousFunctionReflection instanceof ClosureType) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$scope = $this->enterArrowFunctionWithoutReflection($arrowFunction);

		return $this->scopeFactory->create(
			$scope->context,
			$scope->isDeclareStrictTypes(),
			$scope->constantTypes,
			$scope->getFunction(),
			$scope->getNamespace(),
			$scope->variableTypes,
			$scope->moreSpecificTypes,
			$scope->conditionalExpressions,
			$scope->inClosureBindScopeClass,
			$anonymousFunctionReflection,
			true,
			[],
			[],
			[],
			$scope->afterExtractCall,
			$scope->parentScope
		);
	}

	private function enterArrowFunctionWithoutReflection(Expr\ArrowFunction $arrowFunction): self
	{
		$variableTypes = $this->variableTypes;
		$mixed = new MixedType();
		$parameterVariables = [];
		foreach ($arrowFunction->params as $parameter) {
			if ($parameter->type === null) {
				$parameterType = $mixed;
			} else {
				$isNullable = $this->isParameterValueNullable($parameter);
				$parameterType = $this->getFunctionType($parameter->type, $isNullable, $parameter->variadic);
			}

			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			$variableTypes[$parameter->var->name] = VariableTypeHolder::createYes($parameterType);
			$parameterVariables[] = $parameter->var->name;
		}

		if ($arrowFunction->static) {
			unset($variableTypes['this']);
		}

		$conditionalExpressions = [];
		foreach ($this->conditionalExpressions as $conditionalExprString => $holders) {
			$newHolders = [];
			foreach ($parameterVariables as $parameterVariable) {
				$exprString = '$' . $parameterVariable;
				if ($exprString === $conditionalExprString) {
					continue 2;
				}
			}

			foreach ($holders as $holder) {
				foreach ($parameterVariables as $parameterVariable) {
					$exprString = '$' . $parameterVariable;
					foreach (array_keys($holder->getConditionExpressionTypes()) as $conditionalExprString2) {
						if ($exprString === $conditionalExprString2) {
							continue 3;
						}
					}
				}

				$newHolders[] = $holder;
			}

			if (count($newHolders) === 0) {
				continue;
			}

			$conditionalExpressions[$conditionalExprString] = $newHolders;
		}
		foreach ($parameterVariables as $parameterVariable) {
			$exprString = '$' . $parameterVariable;
			foreach ($this->conditionalExpressions as $conditionalExprString => $holders) {
				if ($exprString === $conditionalExprString) {
					continue;
				}

				$newHolders = [];
				foreach ($holders as $holder) {
					foreach (array_keys($holder->getConditionExpressionTypes()) as $conditionalExprString2) {
						if ($exprString === $conditionalExprString2) {
							continue 2;
						}
					}

					$newHolders[] = $holder;
				}

				if (count($newHolders) === 0) {
					continue;
				}

				$conditionalExpressions[$conditionalExprString] = $newHolders;
			}
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$variableTypes,
			$this->moreSpecificTypes,
			$conditionalExpressions,
			$this->inClosureBindScopeClass,
			null,
			true,
			[],
			[],
			[],
			$this->afterExtractCall,
			$this->parentScope
		);
	}

	public function isParameterValueNullable(Node\Param $parameter): bool
	{
		if ($parameter->default instanceof ConstFetch) {
			return strtolower((string) $parameter->default->name) === 'null';
		}

		return false;
	}

	/**
	 * @param \PhpParser\Node\Name|\PhpParser\Node\Identifier|\PhpParser\Node\NullableType|\PhpParser\Node\UnionType|null $type
	 * @param bool $isNullable
	 * @param bool $isVariadic
	 * @return Type
	 */
	public function getFunctionType($type, bool $isNullable, bool $isVariadic): Type
	{
		if ($isNullable) {
			return TypeCombinator::addNull(
				$this->getFunctionType($type, false, $isVariadic)
			);
		}
		if ($isVariadic) {
			return new ArrayType(new IntegerType(), $this->getFunctionType(
				$type,
				false,
				false
			));
		}

		if ($type instanceof Name) {
			$className = (string) $type;
			$lowercasedClassName = strtolower($className);
			if ($lowercasedClassName === 'parent') {
				if ($this->isInClass() && $this->getClassReflection()->getParentClass() !== false) {
					return new ObjectType($this->getClassReflection()->getParentClass()->getName());
				}

				return new NonexistentParentClassType();
			}
		}

		return ParserNodeTypeToPHPStanType::resolve($type, $this->isInClass() ? $this->getClassReflection()->getName() : null);
	}

	public function enterForeach(Expr $iteratee, string $valueName, ?string $keyName): self
	{
		$iterateeType = $this->getType($iteratee);
		$nativeIterateeType = $this->getNativeType($iteratee);
		$scope = $this->assignVariable($valueName, $iterateeType->getIterableValueType());
		$scope->nativeExpressionTypes[sprintf('$%s', $valueName)] = $nativeIterateeType->getIterableValueType();

		if ($keyName !== null) {
			$scope = $scope->enterForeachKey($iteratee, $keyName);
		}

		return $scope;
	}

	public function enterForeachKey(Expr $iteratee, string $keyName): self
	{
		$iterateeType = $this->getType($iteratee);
		$nativeIterateeType = $this->getNativeType($iteratee);
		$scope = $this->assignVariable($keyName, $iterateeType->getIterableKeyType());
		$scope->nativeExpressionTypes[sprintf('$%s', $keyName)] = $nativeIterateeType->getIterableKeyType();

		return $scope;
	}

	/**
	 * @param \PhpParser\Node\Name[] $classes
	 * @param string|null $variableName
	 * @return self
	 */
	public function enterCatch(array $classes, ?string $variableName): self
	{
		if ($variableName === null) {
			return $this;
		}

		$type = TypeCombinator::union(...array_map(static function (string $class): ObjectType {
			return new ObjectType($class);
		}, $classes));

		return $this->assignVariable(
			$variableName,
			TypeCombinator::intersect($type, new ObjectType(\Throwable::class))
		);
	}

	public function enterExpressionAssign(Expr $expr): self
	{
		$exprString = $this->getNodeKey($expr);
		$currentlyAssignedExpressions = $this->currentlyAssignedExpressions;
		$currentlyAssignedExpressions[$exprString] = true;

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$this->getVariableTypes(),
			$this->moreSpecificTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$currentlyAssignedExpressions,
			$this->nativeExpressionTypes,
			[],
			$this->afterExtractCall,
			$this->parentScope
		);
	}

	public function exitExpressionAssign(Expr $expr): self
	{
		$exprString = $this->getNodeKey($expr);
		$currentlyAssignedExpressions = $this->currentlyAssignedExpressions;
		unset($currentlyAssignedExpressions[$exprString]);

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$this->getVariableTypes(),
			$this->moreSpecificTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$currentlyAssignedExpressions,
			$this->nativeExpressionTypes,
			[],
			$this->afterExtractCall,
			$this->parentScope
		);
	}

	public function isInExpressionAssign(Expr $expr): bool
	{
		$exprString = $this->getNodeKey($expr);
		return array_key_exists($exprString, $this->currentlyAssignedExpressions);
	}

	public function assignVariable(string $variableName, Type $type, ?TrinaryLogic $certainty = null): self
	{
		if ($certainty === null) {
			$certainty = TrinaryLogic::createYes();
		} elseif ($certainty->no()) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		$variableTypes = $this->getVariableTypes();
		$variableTypes[$variableName] = new VariableTypeHolder($type, $certainty);

		$nativeTypes = $this->nativeExpressionTypes;
		$nativeTypes[sprintf('$%s', $variableName)] = $type;

		$variableString = $this->printer->prettyPrintExpr(new Variable($variableName));
		$moreSpecificTypeHolders = $this->moreSpecificTypes;
		foreach (array_keys($moreSpecificTypeHolders) as $key) {
			$matches = \Nette\Utils\Strings::matchAll((string) $key, '#\$[a-zA-Z_\x7f-\xff][a-zA-Z_0-9\x7f-\xff]*#');

			if ($matches === []) {
				continue;
			}

			$matches = array_column($matches, 0);

			if (!in_array($variableString, $matches, true)) {
				continue;
			}

			unset($moreSpecificTypeHolders[$key]);
		}

		$conditionalExpressions = [];
		foreach ($this->conditionalExpressions as $exprString => $holders) {
			$exprVariableName = '$' . $variableName;
			if ($exprString === $exprVariableName) {
				continue;
			}

			foreach ($holders as $holder) {
				foreach (array_keys($holder->getConditionExpressionTypes()) as $conditionExprString) {
					if ($conditionExprString === $exprVariableName) {
						continue 3;
					}
				}
			}

			$conditionalExpressions[$exprString] = $holders;
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$variableTypes,
			$moreSpecificTypeHolders,
			$conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			$this->currentlyAssignedExpressions,
			$nativeTypes,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope
		);
	}

	public function unsetExpression(Expr $expr): self
	{
		if ($expr instanceof Variable && is_string($expr->name)) {
			if ($this->hasVariableType($expr->name)->no()) {
				return $this;
			}
			$variableTypes = $this->getVariableTypes();
			unset($variableTypes[$expr->name]);
			$nativeTypes = $this->nativeExpressionTypes;

			$exprString = sprintf('$%s', $expr->name);
			unset($nativeTypes[$exprString]);

			$conditionalExpressions = $this->conditionalExpressions;
			unset($conditionalExpressions[$exprString]);

			return $this->scopeFactory->create(
				$this->context,
				$this->isDeclareStrictTypes(),
				$this->constantTypes,
				$this->getFunction(),
				$this->getNamespace(),
				$variableTypes,
				$this->moreSpecificTypes,
				$conditionalExpressions,
				$this->inClosureBindScopeClass,
				$this->anonymousFunctionReflection,
				$this->inFirstLevelStatement,
				[],
				$nativeTypes,
				[],
				$this->afterExtractCall,
				$this->parentScope
			);
		} elseif ($expr instanceof Expr\ArrayDimFetch && $expr->dim !== null) {
			$varType = $this->getType($expr->var);
			$constantArrays = TypeUtils::getConstantArrays($varType);
			if (count($constantArrays) > 0) {
				$unsetArrays = [];
				$dimType = $this->getType($expr->dim);
				foreach ($constantArrays as $constantArray) {
					$unsetArrays[] = $constantArray->unsetOffset($dimType);
				}
				return $this->specifyExpressionType(
					$expr->var,
					TypeCombinator::union(...$unsetArrays)
				);
			}

			$args = [new Node\Arg($expr->var)];

			$arrays = TypeUtils::getArrays($varType);
			$scope = $this;
			if (count($arrays) > 0) {
				$scope = $scope->specifyExpressionType($expr->var, TypeCombinator::union(...$arrays));
			}

			return $scope->invalidateExpression($expr->var)
				->invalidateExpression(new FuncCall(new Name\FullyQualified('count'), $args))
				->invalidateExpression(new FuncCall(new Name('count'), $args));
		}

		return $this;
	}

	public function specifyExpressionType(Expr $expr, Type $type, ?Type $nativeType = null): self
	{
		if ($expr instanceof Node\Scalar || $expr instanceof Array_) {
			return $this;
		}

		if ($expr instanceof ConstFetch) {
			$constantTypes = $this->constantTypes;
			$constantName = new FullyQualified($expr->name->toString());
			$constantTypes[$constantName->toCodeString()] = $type;

			return $this->scopeFactory->create(
				$this->context,
				$this->isDeclareStrictTypes(),
				$constantTypes,
				$this->getFunction(),
				$this->getNamespace(),
				$this->getVariableTypes(),
				$this->moreSpecificTypes,
				$this->conditionalExpressions,
				$this->inClosureBindScopeClass,
				$this->anonymousFunctionReflection,
				$this->inFirstLevelStatement,
				$this->currentlyAssignedExpressions,
				$this->nativeExpressionTypes,
				$this->inFunctionCallsStack,
				$this->afterExtractCall,
				$this->parentScope
			);
		}

		$exprString = $this->getNodeKey($expr);

		$scope = $this;

		if ($expr instanceof Variable && is_string($expr->name)) {
			$variableName = $expr->name;

			$variableTypes = $this->getVariableTypes();
			$variableTypes[$variableName] = VariableTypeHolder::createYes($type);

			if ($nativeType === null) {
				$nativeType = $type;
			}

			$nativeTypes = $this->nativeExpressionTypes;
			$exprString = sprintf('$%s', $variableName);
			$nativeTypes[$exprString] = $nativeType;

			return $this->scopeFactory->create(
				$this->context,
				$this->isDeclareStrictTypes(),
				$this->constantTypes,
				$this->getFunction(),
				$this->getNamespace(),
				$variableTypes,
				$this->moreSpecificTypes,
				$this->conditionalExpressions,
				$this->inClosureBindScopeClass,
				$this->anonymousFunctionReflection,
				$this->inFirstLevelStatement,
				$this->currentlyAssignedExpressions,
				$nativeTypes,
				$this->inFunctionCallsStack,
				$this->afterExtractCall,
				$this->parentScope
			);
		} elseif ($expr instanceof Expr\ArrayDimFetch && $expr->dim !== null) {
			$constantArrays = TypeUtils::getConstantArrays($this->getType($expr->var));
			if (count($constantArrays) > 0) {
				$setArrays = [];
				$dimType = $this->getType($expr->dim);
				foreach ($constantArrays as $constantArray) {
					$setArrays[] = $constantArray->setOffsetValueType($dimType, $type);
				}
				$scope = $this->specifyExpressionType(
					$expr->var,
					TypeCombinator::union(...$setArrays)
				);
			}
		}

		return $scope->addMoreSpecificTypes([
			$exprString => $type,
		]);
	}

	public function assignExpression(Expr $expr, Type $type): self
	{
		$scope = $this;
		if ($expr instanceof PropertyFetch || $expr instanceof Expr\StaticPropertyFetch) {
			$scope = $this->invalidateExpression($expr);
		}

		return $scope->specifyExpressionType($expr, $type);
	}

	public function invalidateExpression(Expr $expressionToInvalidate, bool $requireMoreCharacters = false): self
	{
		$exprStringToInvalidate = $this->getNodeKey($expressionToInvalidate);
		$moreSpecificTypeHolders = $this->moreSpecificTypes;
		$nativeExpressionTypes = $this->nativeExpressionTypes;
		foreach (array_keys($moreSpecificTypeHolders) as $exprString) {
			$exprString = (string) $exprString;
			if (Strings::startsWith($exprString, $exprStringToInvalidate)) {
				if ($exprString === $exprStringToInvalidate && $requireMoreCharacters) {
					continue;
				}
				$nextLetter = substr($exprString, strlen($exprStringToInvalidate), 1);
				if (Strings::match($nextLetter, '#[a-zA-Z_0-9\x7f-\xff]#') === null) {
					unset($moreSpecificTypeHolders[$exprString]);
					unset($nativeExpressionTypes[$exprString]);
					continue;
				}
			}
			$matches = \Nette\Utils\Strings::matchAll($exprString, '#\$[a-zA-Z_\x7f-\xff][a-zA-Z_0-9\x7f-\xff]*#');
			if ($matches === []) {
				continue;
			}

			$matches = array_column($matches, 0);

			if (!in_array($exprStringToInvalidate, $matches, true)) {
				continue;
			}

			unset($moreSpecificTypeHolders[$exprString]);
			unset($nativeExpressionTypes[$exprString]);
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$this->getVariableTypes(),
			$moreSpecificTypeHolders,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			$this->currentlyAssignedExpressions,
			$nativeExpressionTypes,
			[],
			$this->afterExtractCall,
			$this->parentScope
		);
	}

	public function removeTypeFromExpression(Expr $expr, Type $typeToRemove): self
	{
		$exprType = $this->getType($expr);
		$typeAfterRemove = TypeCombinator::remove($exprType, $typeToRemove);
		if (
			!$expr instanceof Variable
			&& $exprType->equals($typeAfterRemove)
			&& !$exprType instanceof ErrorType
			&& !$exprType instanceof NeverType
		) {
			return $this;
		}
		$scope = $this->specifyExpressionType(
			$expr,
			$typeAfterRemove
		);
		if ($expr instanceof Variable && is_string($expr->name)) {
			$scope->nativeExpressionTypes[sprintf('$%s', $expr->name)] = TypeCombinator::remove($this->getNativeType($expr), $typeToRemove);
		}

		return $scope;
	}

	/**
	 * @param \PhpParser\Node\Expr $expr
	 * @param bool $defaultHandleFunctions
	 * @return \PHPStan\Analyser\MutatingScope
	 */
	public function filterByTruthyValue(Expr $expr, bool $defaultHandleFunctions = false): Scope
	{
		$specifiedTypes = $this->typeSpecifier->specifyTypesInCondition($this, $expr, TypeSpecifierContext::createTruthy(), $defaultHandleFunctions);
		return $this->filterBySpecifiedTypes($specifiedTypes);
	}

	/**
	 * @param \PhpParser\Node\Expr $expr
	 * @param bool $defaultHandleFunctions
	 * @return \PHPStan\Analyser\MutatingScope
	 */
	public function filterByFalseyValue(Expr $expr, bool $defaultHandleFunctions = false): Scope
	{
		$specifiedTypes = $this->typeSpecifier->specifyTypesInCondition($this, $expr, TypeSpecifierContext::createFalsey(), $defaultHandleFunctions);
		return $this->filterBySpecifiedTypes($specifiedTypes);
	}

	public function filterBySpecifiedTypes(SpecifiedTypes $specifiedTypes): self
	{
		$typeSpecifications = [];
		foreach ($specifiedTypes->getSureTypes() as $exprString => [$expr, $type]) {
			$typeSpecifications[] = [
				'sure' => true,
				'exprString' => $exprString,
				'expr' => $expr,
				'type' => $type,
			];
		}
		foreach ($specifiedTypes->getSureNotTypes() as $exprString => [$expr, $type]) {
			$typeSpecifications[] = [
				'sure' => false,
				'exprString' => $exprString,
				'expr' => $expr,
				'type' => $type,
			];
		}

		usort($typeSpecifications, static function (array $a, array $b): int {
			$length = strlen((string) $a['exprString']) - strlen((string) $b['exprString']);
			if ($length !== 0) {
				return $length;
			}

			return $b['sure'] - $a['sure'];
		});

		$scope = $this;
		$typeGuards = [];
		$skipVariables = [];
		$saveConditionalVariables = [];
		foreach ($typeSpecifications as $typeSpecification) {
			$expr = $typeSpecification['expr'];
			$type = $typeSpecification['type'];
			$originalExprType = $this->getType($expr);
			if ($typeSpecification['sure']) {
				$scope = $scope->specifyExpressionType($expr, $specifiedTypes->shouldOverwrite() ? $type : TypeCombinator::intersect($type, $originalExprType));

				if ($expr instanceof Variable && is_string($expr->name)) {
					$scope->nativeExpressionTypes[sprintf('$%s', $expr->name)] = $specifiedTypes->shouldOverwrite() ? $type : TypeCombinator::intersect($type, $this->getNativeType($expr));
				}
			} else {
				$scope = $scope->removeTypeFromExpression($expr, $type);
			}

			if (
				!$expr instanceof Variable
				|| !is_string($expr->name)
				|| $specifiedTypes->shouldOverwrite()
			) {
				$match = \Nette\Utils\Strings::match((string) $typeSpecification['exprString'], '#^\$([a-zA-Z_\x7f-\xff][a-zA-Z_0-9\x7f-\xff]*)#');
				if ($match !== null) {
					$skipVariables[$match[1]] = true;
				}
				continue;
			}

			if ($scope->hasVariableType($expr->name)->no()) {
				continue;
			}

			$saveConditionalVariables[$expr->name] = $scope->getVariableType($expr->name);
		}

		foreach ($saveConditionalVariables as $variableName => $typeGuard) {
			if (array_key_exists($variableName, $skipVariables)) {
				continue;
			}

			$typeGuards['$' . $variableName] = $typeGuard;
		}

		$newConditionalExpressions = [];
		foreach ($this->conditionalExpressions as $variableExprString => $conditionalExpressions) {
			if (array_key_exists($variableExprString, $typeGuards)) {
				continue;
			}

			$typeHolder = null;

			$variableName = substr($variableExprString, 1);
			foreach ($conditionalExpressions as $conditionalExpression) {
				$matchingConditions = [];
				foreach ($conditionalExpression->getConditionExpressionTypes() as $conditionExprString => $conditionalType) {
					if (!array_key_exists($conditionExprString, $typeGuards)) {
						continue;
					}

					if (!$typeGuards[$conditionExprString]->equals($conditionalType)) {
						continue 2;
					}

					$matchingConditions[$conditionExprString] = $conditionalType;
				}

				if (count($matchingConditions) === 0) {
					$newConditionalExpressions[$variableExprString][$conditionalExpression->getKey()] = $conditionalExpression;
					continue;
				}

				if (count($matchingConditions) < count($conditionalExpression->getConditionExpressionTypes())) {
					$filteredConditions = $conditionalExpression->getConditionExpressionTypes();
					foreach (array_keys($matchingConditions) as $conditionExprString) {
						unset($filteredConditions[$conditionExprString]);
					}

					$holder = new ConditionalExpressionHolder($filteredConditions, $conditionalExpression->getTypeHolder());
					$newConditionalExpressions[$variableExprString][$holder->getKey()] = $holder;
					continue;
				}

				$typeHolder = $conditionalExpression->getTypeHolder();
				break;
			}

			if ($typeHolder === null) {
				continue;
			}

			if ($typeHolder->getCertainty()->no()) {
				unset($scope->variableTypes[$variableName]);
			} else {
				$scope->variableTypes[$variableName] = $typeHolder;
			}
		}

		$scope->conditionalExpressions = $newConditionalExpressions;

		return $scope;
	}

	/**
	 * @param string $exprString
	 * @param ConditionalExpressionHolder[] $conditionalExpressionHolders
	 * @return self
	 */
	public function addConditionalExpressions(string $exprString, array $conditionalExpressionHolders): self
	{
		$conditionalExpressions = $this->conditionalExpressions;
		$conditionalExpressions[$exprString] = $conditionalExpressionHolders;
		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$this->variableTypes,
			$this->moreSpecificTypes,
			$conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			$this->currentlyAssignedExpressions,
			$this->nativeExpressionTypes,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope
		);
	}

	public function exitFirstLevelStatements(): self
	{
		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$this->getVariableTypes(),
			$this->moreSpecificTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			false,
			$this->currentlyAssignedExpressions,
			$this->nativeExpressionTypes,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope
		);
	}

	public function isInFirstLevelStatement(): bool
	{
		return $this->inFirstLevelStatement;
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod
	 * @param Type[] $types
	 * @return self
	 */
	private function addMoreSpecificTypes(array $types): self
	{
		$moreSpecificTypeHolders = $this->moreSpecificTypes;
		foreach ($types as $exprString => $type) {
			$moreSpecificTypeHolders[$exprString] = VariableTypeHolder::createYes($type);
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$this->getVariableTypes(),
			$moreSpecificTypeHolders,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			$this->currentlyAssignedExpressions,
			$this->nativeExpressionTypes,
			[],
			$this->afterExtractCall,
			$this->parentScope
		);
	}

	public function mergeWith(?self $otherScope): self
	{
		if ($otherScope === null) {
			return $this;
		}

		$variableHolderToType = static function (VariableTypeHolder $holder): Type {
			return $holder->getType();
		};
		$typeToVariableHolder = static function (Type $type): VariableTypeHolder {
			return new VariableTypeHolder($type, TrinaryLogic::createYes());
		};

		$ourVariableTypes = $this->getVariableTypes();
		$theirVariableTypes = $otherScope->getVariableTypes();
		if ($this->canAnyVariableExist()) {
			foreach (array_keys($theirVariableTypes) as $name) {
				if (array_key_exists($name, $ourVariableTypes)) {
					continue;
				}

				$ourVariableTypes[$name] = VariableTypeHolder::createMaybe(new MixedType());
			}

			foreach (array_keys($ourVariableTypes) as $name) {
				if (array_key_exists($name, $theirVariableTypes)) {
					continue;
				}

				$theirVariableTypes[$name] = VariableTypeHolder::createMaybe(new MixedType());
			}
		}

		$mergedVariableHolders = $this->mergeVariableHolders($ourVariableTypes, $theirVariableTypes);
		$conditionalExpressions = $this->intersectConditionalExpressions($otherScope->conditionalExpressions);
		$conditionalExpressions = $this->createConditionalExpressions(
			$conditionalExpressions,
			$ourVariableTypes,
			$theirVariableTypes,
			$mergedVariableHolders
		);
		$conditionalExpressions = $this->createConditionalExpressions(
			$conditionalExpressions,
			$theirVariableTypes,
			$ourVariableTypes,
			$mergedVariableHolders
		);
		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			array_map($variableHolderToType, $this->generalizeVariableTypeHolders(
				array_map($typeToVariableHolder, $this->constantTypes),
				array_map($typeToVariableHolder, $otherScope->constantTypes)
			)),
			$this->getFunction(),
			$this->getNamespace(),
			$mergedVariableHolders,
			$this->mergeVariableHolders($this->moreSpecificTypes, $otherScope->moreSpecificTypes),
			$conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			[],
			array_map($variableHolderToType, array_filter($this->mergeVariableHolders(
				array_map($typeToVariableHolder, $this->nativeExpressionTypes),
				array_map($typeToVariableHolder, $otherScope->nativeExpressionTypes)
			), static function (VariableTypeHolder $holder): bool {
				return $holder->getCertainty()->yes();
			})),
			[],
			$this->afterExtractCall && $otherScope->afterExtractCall,
			$this->parentScope
		);
	}

	/**
	 * @param array<string, ConditionalExpressionHolder[]> $otherConditionalExpressions
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function intersectConditionalExpressions(array $otherConditionalExpressions): array
	{
		$newConditionalExpressions = [];
		foreach ($this->conditionalExpressions as $exprString => $holders) {
			if (!array_key_exists($exprString, $otherConditionalExpressions)) {
				continue;
			}

			$otherHolders = $otherConditionalExpressions[$exprString];
			foreach (array_keys($holders) as $key) {
				if (!array_key_exists($key, $otherHolders)) {
					continue 2;
				}
			}

			$newConditionalExpressions[$exprString] = $holders;
		}

		return $newConditionalExpressions;
	}

	/**
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @param array<string, VariableTypeHolder> $variableTypes
	 * @param array<string, VariableTypeHolder> $theirVariableTypes
	 * @param array<string, VariableTypeHolder> $mergedVariableHolders
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function createConditionalExpressions(
		array $conditionalExpressions,
		array $variableTypes,
		array $theirVariableTypes,
		array $mergedVariableHolders
	): array
	{
		$newVariableTypes = $variableTypes;
		foreach ($theirVariableTypes as $name => $holder) {
			if (!array_key_exists($name, $mergedVariableHolders)) {
				continue;
			}

			if (!$mergedVariableHolders[$name]->getType()->equals($holder->getType())) {
				continue;
			}

			unset($newVariableTypes[$name]);
		}

		$typeGuards = [];
		foreach ($newVariableTypes as $name => $holder) {
			if (!$holder->getCertainty()->yes()) {
				continue;
			}
			if (!array_key_exists($name, $mergedVariableHolders)) {
				continue;
			}
			if ($mergedVariableHolders[$name]->getType()->equals($holder->getType())) {
				continue;
			}

			$typeGuards['$' . $name] = $holder->getType();
		}

		if (count($typeGuards) === 0) {
			return $conditionalExpressions;
		}

		foreach ($newVariableTypes as $name => $holder) {
			if (
				array_key_exists($name, $mergedVariableHolders)
				&& $mergedVariableHolders[$name]->equals($holder)
			) {
				continue;
			}

			$exprString = '$' . $name;
			$variableTypeGuards = $typeGuards;
			unset($variableTypeGuards[$exprString]);

			if (count($variableTypeGuards) === 0) {
				continue;
			}

			$conditionalExpression = new ConditionalExpressionHolder($variableTypeGuards, $holder);
			$conditionalExpressions[$exprString][$conditionalExpression->getKey()] = $conditionalExpression;
		}

		foreach (array_keys($mergedVariableHolders) as $name) {
			if (array_key_exists($name, $variableTypes)) {
				continue;
			}

			$conditionalExpression = new ConditionalExpressionHolder($typeGuards, new VariableTypeHolder(new ErrorType(), TrinaryLogic::createNo()));
			$conditionalExpressions['$' . $name][$conditionalExpression->getKey()] = $conditionalExpression;
		}

		return $conditionalExpressions;
	}

	/**
	 * @param VariableTypeHolder[] $ourVariableTypeHolders
	 * @param VariableTypeHolder[] $theirVariableTypeHolders
	 * @return VariableTypeHolder[]
	 */
	private function mergeVariableHolders(array $ourVariableTypeHolders, array $theirVariableTypeHolders): array
	{
		$intersectedVariableTypeHolders = [];
		foreach ($ourVariableTypeHolders as $name => $variableTypeHolder) {
			if (isset($theirVariableTypeHolders[$name])) {
				$intersectedVariableTypeHolders[$name] = $variableTypeHolder->and($theirVariableTypeHolders[$name]);
			} else {
				$intersectedVariableTypeHolders[$name] = VariableTypeHolder::createMaybe($variableTypeHolder->getType());
			}
		}

		foreach ($theirVariableTypeHolders as $name => $variableTypeHolder) {
			if (isset($intersectedVariableTypeHolders[$name])) {
				continue;
			}

			$intersectedVariableTypeHolders[$name] = VariableTypeHolder::createMaybe($variableTypeHolder->getType());
		}

		return $intersectedVariableTypeHolders;
	}

	public function processFinallyScope(self $finallyScope, self $originalFinallyScope): self
	{
		$variableHolderToType = static function (VariableTypeHolder $holder): Type {
			return $holder->getType();
		};
		$typeToVariableHolder = static function (Type $type): VariableTypeHolder {
			return new VariableTypeHolder($type, TrinaryLogic::createYes());
		};

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			array_map($variableHolderToType, $this->processFinallyScopeVariableTypeHolders(
				array_map($typeToVariableHolder, $this->constantTypes),
				array_map($typeToVariableHolder, $finallyScope->constantTypes),
				array_map($typeToVariableHolder, $originalFinallyScope->constantTypes)
			)),
			$this->getFunction(),
			$this->getNamespace(),
			$this->processFinallyScopeVariableTypeHolders(
				$this->getVariableTypes(),
				$finallyScope->getVariableTypes(),
				$originalFinallyScope->getVariableTypes()
			),
			$this->processFinallyScopeVariableTypeHolders(
				$this->moreSpecificTypes,
				$finallyScope->moreSpecificTypes,
				$originalFinallyScope->moreSpecificTypes
			),
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			[],
			array_map($variableHolderToType, $this->processFinallyScopeVariableTypeHolders(
				array_map($typeToVariableHolder, $this->nativeExpressionTypes),
				array_map($typeToVariableHolder, $finallyScope->nativeExpressionTypes),
				array_map($typeToVariableHolder, $originalFinallyScope->nativeExpressionTypes)
			)),
			[],
			$this->afterExtractCall,
			$this->parentScope
		);
	}

	/**
	 * @param VariableTypeHolder[] $ourVariableTypeHolders
	 * @param VariableTypeHolder[] $finallyVariableTypeHolders
	 * @param VariableTypeHolder[] $originalVariableTypeHolders
	 * @return VariableTypeHolder[]
	 */
	private function processFinallyScopeVariableTypeHolders(
		array $ourVariableTypeHolders,
		array $finallyVariableTypeHolders,
		array $originalVariableTypeHolders
	): array
	{
		foreach ($finallyVariableTypeHolders as $name => $variableTypeHolder) {
			if (
				isset($originalVariableTypeHolders[$name])
				&& !$originalVariableTypeHolders[$name]->getType()->equals($variableTypeHolder->getType())
			) {
				$ourVariableTypeHolders[$name] = $variableTypeHolder;
				continue;
			}

			if (isset($originalVariableTypeHolders[$name])) {
				continue;
			}

			$ourVariableTypeHolders[$name] = $variableTypeHolder;
		}

		return $ourVariableTypeHolders;
	}

	/**
	 * @param self $closureScope
	 * @param self|null $prevScope
	 * @param Expr\ClosureUse[] $byRefUses
	 * @return self
	 */
	public function processClosureScope(
		self $closureScope,
		?self $prevScope,
		array $byRefUses
	): self
	{
		$variableTypes = $this->variableTypes;
		if (count($byRefUses) === 0) {
			return $this;
		}

		foreach ($byRefUses as $use) {
			if (!is_string($use->var->name)) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			$variableName = $use->var->name;

			if (!$closureScope->hasVariableType($variableName)->yes()) {
				$variableTypes[$variableName] = VariableTypeHolder::createYes(new NullType());
				continue;
			}

			$variableType = $closureScope->getVariableType($variableName);

			if ($prevScope !== null) {
				$prevVariableType = $prevScope->getVariableType($variableName);
				if (!$variableType->equals($prevVariableType)) {
					$variableType = TypeCombinator::union($variableType, $prevVariableType);
					$variableType = self::generalizeType($variableType, $prevVariableType);
				}
			}

			$variableTypes[$variableName] = VariableTypeHolder::createYes($variableType);
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$variableTypes,
			$this->moreSpecificTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			[],
			$this->nativeExpressionTypes,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope
		);
	}

	public function processAlwaysIterableForeachScopeWithoutPollute(self $finalScope): self
	{
		$variableTypeHolders = $this->variableTypes;
		$nativeTypes = $this->nativeExpressionTypes;
		foreach ($finalScope->variableTypes as $name => $variableTypeHolder) {
			$nativeTypes[sprintf('$%s', $name)] = $variableTypeHolder->getType();
			if (!isset($variableTypeHolders[$name])) {
				$variableTypeHolders[$name] = VariableTypeHolder::createMaybe($variableTypeHolder->getType());
				continue;
			}

			$variableTypeHolders[$name] = new VariableTypeHolder(
				$variableTypeHolder->getType(),
				$variableTypeHolder->getCertainty()->and($variableTypeHolders[$name]->getCertainty())
			);
		}

		$moreSpecificTypes = $this->moreSpecificTypes;
		foreach ($finalScope->moreSpecificTypes as $exprString => $variableTypeHolder) {
			if (!isset($moreSpecificTypes[$exprString])) {
				$moreSpecificTypes[$exprString] = VariableTypeHolder::createMaybe($variableTypeHolder->getType());
				continue;
			}

			$moreSpecificTypes[$exprString] = new VariableTypeHolder(
				$variableTypeHolder->getType(),
				$variableTypeHolder->getCertainty()->and($moreSpecificTypes[$exprString]->getCertainty())
			);
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$variableTypeHolders,
			$moreSpecificTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			[],
			$nativeTypes,
			[],
			$this->afterExtractCall,
			$this->parentScope
		);
	}

	public function generalizeWith(self $otherScope): self
	{
		$variableTypeHolders = $this->generalizeVariableTypeHolders(
			$this->getVariableTypes(),
			$otherScope->getVariableTypes()
		);

		$moreSpecificTypes = $this->generalizeVariableTypeHolders(
			$this->moreSpecificTypes,
			$otherScope->moreSpecificTypes
		);

		$variableHolderToType = static function (VariableTypeHolder $holder): Type {
			return $holder->getType();
		};
		$typeToVariableHolder = static function (Type $type): VariableTypeHolder {
			return new VariableTypeHolder($type, TrinaryLogic::createYes());
		};
		$nativeTypes = array_map($variableHolderToType, $this->generalizeVariableTypeHolders(
			array_map($typeToVariableHolder, $this->nativeExpressionTypes),
			array_map($typeToVariableHolder, $otherScope->nativeExpressionTypes)
		));

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			array_map($variableHolderToType, $this->generalizeVariableTypeHolders(
				array_map($typeToVariableHolder, $this->constantTypes),
				array_map($typeToVariableHolder, $otherScope->constantTypes)
			)),
			$this->getFunction(),
			$this->getNamespace(),
			$variableTypeHolders,
			$moreSpecificTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			[],
			$nativeTypes,
			[],
			$this->afterExtractCall,
			$this->parentScope
		);
	}

	/**
	 * @param VariableTypeHolder[] $variableTypeHolders
	 * @param VariableTypeHolder[] $otherVariableTypeHolders
	 * @return VariableTypeHolder[]
	 */
	private function generalizeVariableTypeHolders(
		array $variableTypeHolders,
		array $otherVariableTypeHolders
	): array
	{
		foreach ($variableTypeHolders as $name => $variableTypeHolder) {
			if (!isset($otherVariableTypeHolders[$name])) {
				continue;
			}

			$variableTypeHolders[$name] = new VariableTypeHolder(
				self::generalizeType($variableTypeHolder->getType(), $otherVariableTypeHolders[$name]->getType()),
				$variableTypeHolder->getCertainty()
			);
		}

		return $variableTypeHolders;
	}

	private static function generalizeType(Type $a, Type $b): Type
	{
		if ($a->equals($b)) {
			return $a;
		}

		$constantIntegers = ['a' => [], 'b' => []];
		$constantFloats = ['a' => [], 'b' => []];
		$constantBooleans = ['a' => [], 'b' => []];
		$constantStrings = ['a' => [], 'b' => []];
		$constantArrays = ['a' => [], 'b' => []];
		$generalArrays = ['a' => [], 'b' => []];
		$otherTypes = [];

		foreach ([
			'a' => TypeUtils::flattenTypes($a),
			'b' => TypeUtils::flattenTypes($b),
		] as $key => $types) {
			foreach ($types as $type) {
				if ($type instanceof ConstantIntegerType) {
					$constantIntegers[$key][] = $type;
					continue;
				}
				if ($type instanceof ConstantFloatType) {
					$constantFloats[$key][] = $type;
					continue;
				}
				if ($type instanceof ConstantBooleanType) {
					$constantBooleans[$key][] = $type;
					continue;
				}
				if ($type instanceof ConstantStringType) {
					$constantStrings[$key][] = $type;
					continue;
				}
				if ($type instanceof ConstantArrayType) {
					$constantArrays[$key][] = $type;
					continue;
				}
				if ($type->isArray()->yes()) {
					$generalArrays[$key][] = $type;
					continue;
				}

				$otherTypes[] = $type;
			}
		}

		$resultTypes = [];
		foreach ([
			$constantIntegers,
			$constantFloats,
			$constantBooleans,
			$constantStrings,
		] as $constantTypes) {
			if (count($constantTypes['a']) === 0) {
				continue;
			}
			if (count($constantTypes['b']) === 0) {
				$resultTypes[] = TypeCombinator::union(...$constantTypes['a']);
				continue;
			}

			$aTypes = TypeCombinator::union(...$constantTypes['a']);
			$bTypes = TypeCombinator::union(...$constantTypes['b']);
			if ($aTypes->equals($bTypes)) {
				$resultTypes[] = $aTypes;
				continue;
			}

			$resultTypes[] = TypeUtils::generalizeType($constantTypes['a'][0]);
		}

		if (count($constantArrays['a']) > 0) {
			if (count($constantArrays['b']) === 0) {
				$resultTypes[] = TypeCombinator::union(...$constantArrays['a']);
			} else {
				$constantArraysA = TypeCombinator::union(...$constantArrays['a']);
				$constantArraysB = TypeCombinator::union(...$constantArrays['b']);
				if ($constantArraysA->getIterableKeyType()->equals($constantArraysB->getIterableKeyType())) {
					$resultArrayBuilder = ConstantArrayTypeBuilder::createEmpty();
					foreach (TypeUtils::flattenTypes($constantArraysA->getIterableKeyType()) as $keyType) {
						$resultArrayBuilder->setOffsetValueType(
							$keyType,
							self::generalizeType(
								$constantArraysA->getOffsetValueType($keyType),
								$constantArraysB->getOffsetValueType($keyType)
							)
						);
					}

					$resultTypes[] = $resultArrayBuilder->getArray();
				} else {
					$resultTypes[] = new ArrayType(
						TypeCombinator::union(self::generalizeType($constantArraysA->getIterableKeyType(), $constantArraysB->getIterableKeyType())),
						TypeCombinator::union(self::generalizeType($constantArraysA->getIterableValueType(), $constantArraysB->getIterableValueType()))
					);
				}
			}
		}

		if (count($generalArrays['a']) > 0) {
			if (count($generalArrays['b']) === 0) {
				$resultTypes[] = TypeCombinator::union(...$generalArrays['a']);
			} else {
				$generalArraysA = TypeCombinator::union(...$generalArrays['a']);
				$generalArraysB = TypeCombinator::union(...$generalArrays['b']);

				$aValueType = $generalArraysA->getIterableValueType();
				$bValueType = $generalArraysB->getIterableValueType();
				$aArrays = TypeUtils::getAnyArrays($aValueType);
				$bArrays = TypeUtils::getAnyArrays($bValueType);
				if (
					count($aArrays) === 1
					&& !$aArrays[0] instanceof ConstantArrayType
					&& count($bArrays) === 1
					&& !$bArrays[0] instanceof ConstantArrayType
				) {
					$aDepth = self::getArrayDepth($aArrays[0]);
					$bDepth = self::getArrayDepth($bArrays[0]);
					if (
						($aDepth > 2 || $bDepth > 2)
						&& abs($aDepth - $bDepth) > 0
					) {
						$aValueType = new MixedType();
						$bValueType = new MixedType();
					}
				}

				$resultTypes[] = new ArrayType(
					TypeCombinator::union(self::generalizeType($generalArraysA->getIterableKeyType(), $generalArraysB->getIterableKeyType())),
					TypeCombinator::union(self::generalizeType($aValueType, $bValueType))
				);
			}
		}

		return TypeCombinator::union(...$resultTypes, ...$otherTypes);
	}

	private static function getArrayDepth(ArrayType $type): int
	{
		$depth = 0;
		while ($type instanceof ArrayType) {
			$temp = $type->getIterableValueType();
			$arrays = TypeUtils::getAnyArrays($temp);
			if (count($arrays) === 1) {
				$type = $arrays[0];
			} else {
				$type = $temp;
			}
			$depth++;
		}

		return $depth;
	}

	public function equals(self $otherScope): bool
	{
		if (!$this->context->equals($otherScope->context)) {
			return false;
		}

		if (!$this->compareVariableTypeHolders($this->variableTypes, $otherScope->variableTypes)) {
			return false;
		}

		if (!$this->compareVariableTypeHolders($this->moreSpecificTypes, $otherScope->moreSpecificTypes)) {
			return false;
		}

		$typeToVariableHolder = static function (Type $type): VariableTypeHolder {
			return new VariableTypeHolder($type, TrinaryLogic::createYes());
		};

		$nativeExpressionTypesResult = $this->compareVariableTypeHolders(
			array_map($typeToVariableHolder, $this->nativeExpressionTypes),
			array_map($typeToVariableHolder, $otherScope->nativeExpressionTypes)
		);

		if (!$nativeExpressionTypesResult) {
			return false;
		}

		return $this->compareVariableTypeHolders(
			array_map($typeToVariableHolder, $this->constantTypes),
			array_map($typeToVariableHolder, $otherScope->constantTypes)
		);
	}

	/**
	 * @param VariableTypeHolder[] $variableTypeHolders
	 * @param VariableTypeHolder[] $otherVariableTypeHolders
	 * @return bool
	 */
	private function compareVariableTypeHolders(array $variableTypeHolders, array $otherVariableTypeHolders): bool
	{
		foreach ($variableTypeHolders as $name => $variableTypeHolder) {
			if (!isset($otherVariableTypeHolders[$name])) {
				return false;
			}

			if (!$variableTypeHolder->getCertainty()->equals($otherVariableTypeHolders[$name]->getCertainty())) {
				return false;
			}

			if (!$variableTypeHolder->getType()->equals($otherVariableTypeHolders[$name]->getType())) {
				return false;
			}

			unset($otherVariableTypeHolders[$name]);
		}

		return count($otherVariableTypeHolders) === 0;
	}

	public function canAccessProperty(PropertyReflection $propertyReflection): bool
	{
		return $this->canAccessClassMember($propertyReflection);
	}

	public function canCallMethod(MethodReflection $methodReflection): bool
	{
		if ($this->canAccessClassMember($methodReflection)) {
			return true;
		}

		return $this->canAccessClassMember($methodReflection->getPrototype());
	}

	public function canAccessConstant(ConstantReflection $constantReflection): bool
	{
		return $this->canAccessClassMember($constantReflection);
	}

	private function canAccessClassMember(ClassMemberReflection $classMemberReflection): bool
	{
		if ($classMemberReflection->isPublic()) {
			return true;
		}

		if ($this->inClosureBindScopeClass !== null && $this->reflectionProvider->hasClass($this->inClosureBindScopeClass)) {
			$currentClassReflection = $this->reflectionProvider->getClass($this->inClosureBindScopeClass);
		} elseif ($this->isInClass()) {
			$currentClassReflection = $this->getClassReflection();
		} else {
			return false;
		}

		$classReflectionName = $classMemberReflection->getDeclaringClass()->getName();
		if ($classMemberReflection->isPrivate()) {
			return $currentClassReflection->getName() === $classReflectionName;
		}

		// protected

		if (
			$currentClassReflection->getName() === $classReflectionName
			|| $currentClassReflection->isSubclassOf($classReflectionName)
		) {
			return true;
		}

		return $classMemberReflection->getDeclaringClass()->isSubclassOf($currentClassReflection->getName());
	}

	/**
	 * @return string[]
	 */
	public function debug(): array
	{
		$descriptions = [];
		foreach ($this->getVariableTypes() as $name => $variableTypeHolder) {
			$key = sprintf('$%s (%s)', $name, $variableTypeHolder->getCertainty()->describe());
			$descriptions[$key] = $variableTypeHolder->getType()->describe(VerbosityLevel::precise());
		}
		foreach ($this->moreSpecificTypes as $exprString => $typeHolder) {
			$key = sprintf(
				'%s-specified (%s)',
				$exprString,
				$typeHolder->getCertainty()->describe()
			);
			$descriptions[$key] = $typeHolder->getType()->describe(VerbosityLevel::precise());
		}
		foreach ($this->constantTypes as $name => $type) {
			$key = sprintf('const %s', $name);
			$descriptions[$key] = $type->describe(VerbosityLevel::precise());
		}
		foreach ($this->nativeExpressionTypes as $exprString => $nativeType) {
			$key = sprintf('native %s', $exprString);
			$descriptions[$key] = $nativeType->describe(VerbosityLevel::precise());
		}

		return $descriptions;
	}

	private function exactInstantiation(New_ $node, string $className): ?Type
	{
		$resolvedClassName = $this->resolveExactName(new Name($className));
		if ($resolvedClassName === null) {
			return null;
		}

		if (!$this->reflectionProvider->hasClass($resolvedClassName)) {
			return null;
		}

		$classReflection = $this->reflectionProvider->getClass($resolvedClassName);
		if ($classReflection->hasConstructor()) {
			$constructorMethod = $classReflection->getConstructor();
		} else {
			$constructorMethod = new DummyConstructorReflection($classReflection);
		}

		$resolvedTypes = [];
		$methodCall = new Expr\StaticCall(
			new Name($resolvedClassName),
			new Node\Identifier($constructorMethod->getName()),
			$node->args
		);

		foreach ($this->dynamicReturnTypeExtensionRegistry->getDynamicStaticMethodReturnTypeExtensionsForClass($classReflection->getName()) as $dynamicStaticMethodReturnTypeExtension) {
			if (!$dynamicStaticMethodReturnTypeExtension->isStaticMethodSupported($constructorMethod)) {
				continue;
			}

			$resolvedTypes[] = $dynamicStaticMethodReturnTypeExtension->getTypeFromStaticMethodCall($constructorMethod, $methodCall, $this);
		}

		if (count($resolvedTypes) > 0) {
			return TypeCombinator::union(...$resolvedTypes);
		}

		if (!$classReflection->isGeneric()) {
			return new ObjectType($resolvedClassName);
		}

		if ($constructorMethod instanceof DummyConstructorReflection || $constructorMethod->getDeclaringClass()->getName() !== $classReflection->getName()) {
			return new GenericObjectType(
				$resolvedClassName,
				$classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds())
			);
		}

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$this,
			$methodCall->args,
			$constructorMethod->getVariants()
		);

		return new GenericObjectType(
			$resolvedClassName,
			$classReflection->typeMapToList($parametersAcceptor->getResolvedTemplateTypeMap())
		);
	}

	private function getTypeToInstantiateForNew(Type $type): Type
	{
		$decideType = static function (Type $type): ?Type {
			if ($type instanceof TypeWithClassName) {
				return $type;
			}
			if ($type instanceof GenericClassStringType) {
				return $type->getGenericType();
			}
			return null;
		};

		if ($type instanceof UnionType) {
			$types = [];
			foreach ($type->getTypes() as $innerType) {
				$decidedType = $decideType($innerType);
				if ($decidedType === null) {
					return new MixedType();
				}

				$types[] = $decidedType;
			}

			return TypeCombinator::union(...$types);
		}

		$decidedType = $decideType($type);
		if ($decidedType === null) {
			return new MixedType();
		}

		return $decidedType;
	}

	/**
	 * @param \PHPStan\Type\Type $calledOnType
	 * @param \PHPStan\Type\Type $typeWithMethod
	 * @param string $methodName
	 * @param MethodCall|\PhpParser\Node\Expr\StaticCall $methodCall
	 * @return \PHPStan\Type\Type|null
	 */
	private function methodCallReturnType(Type $calledOnType, Type $typeWithMethod, string $methodName, Expr $methodCall): ?Type
	{
		if (!$typeWithMethod->hasMethod($methodName)->yes()) {
			return null;
		}

		$methodReflection = $typeWithMethod->getMethod($methodName, $this);

		if ($typeWithMethod instanceof TypeWithClassName) {
			$resolvedTypes = [];

			if ($methodCall instanceof MethodCall) {
				foreach ($this->dynamicReturnTypeExtensionRegistry->getDynamicMethodReturnTypeExtensionsForClass($typeWithMethod->getClassName()) as $dynamicMethodReturnTypeExtension) {
					if (!$dynamicMethodReturnTypeExtension->isMethodSupported($methodReflection)) {
						continue;
					}

					$resolvedTypes[] = $dynamicMethodReturnTypeExtension->getTypeFromMethodCall($methodReflection, $methodCall, $this);
				}
			} else {
				foreach ($this->dynamicReturnTypeExtensionRegistry->getDynamicStaticMethodReturnTypeExtensionsForClass($typeWithMethod->getClassName()) as $dynamicStaticMethodReturnTypeExtension) {
					if (!$dynamicStaticMethodReturnTypeExtension->isStaticMethodSupported($methodReflection)) {
						continue;
					}

					$resolvedTypes[] = $dynamicStaticMethodReturnTypeExtension->getTypeFromStaticMethodCall($methodReflection, $methodCall, $this);
				}
			}
			if (count($resolvedTypes) > 0) {
				return TypeCombinator::union(...$resolvedTypes);
			}
		}

		$methodReturnType = ParametersAcceptorSelector::selectFromArgs(
			$this,
			$methodCall->args,
			$methodReflection->getVariants()
		)->getReturnType();

		if ($methodCall instanceof MethodCall) {
			$calledOnThis = $calledOnType instanceof StaticType && $this->isInClass();
		} else {
			if (!$methodCall->class instanceof Name) {
				$calledOnThis = false;
			} else {
				$calledOnThis = in_array(strtolower($methodCall->class->toString()), ['self', 'static', 'parent'], true) && $this->isInClass();
			}
		}

		$transformedCalledOnType = TypeTraverser::map($calledOnType, function (Type $type, callable $traverse) use ($calledOnThis): Type {
			if ($type instanceof StaticType) {
				if ($calledOnThis && $this->isInClass()) {
					return $traverse($type->changeBaseClass($this->getClassReflection()));
				}
				if ($this->isInClass()) {
					return $traverse($type->changeBaseClass($this->getClassReflection())->getStaticObjectType());
				}
			}

			return $traverse($type);
		});

		return TypeTraverser::map($methodReturnType, function (Type $returnType, callable $traverse) use ($transformedCalledOnType, $calledOnThis): Type {
			if ($returnType instanceof StaticType) {
				if ($calledOnThis && $this->isInClass()) {
					return $traverse($returnType->changeBaseClass($this->getClassReflection()));
				}

				return $traverse($transformedCalledOnType);
			}

			return $traverse($returnType);
		});
	}

	/**
	 * @param \PHPStan\Type\Type $fetchedOnType
	 * @param string $propertyName
	 * @param PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch
	 * @return \PHPStan\Type\Type|null
	 */
	private function propertyFetchType(Type $fetchedOnType, string $propertyName, Expr $propertyFetch): ?Type
	{
		if (!$fetchedOnType->hasProperty($propertyName)->yes()) {
			return null;
		}

		$propertyReflection = $fetchedOnType->getProperty($propertyName, $this);

		if ($this->isInExpressionAssign($propertyFetch)) {
			$propertyType = $propertyReflection->getWritableType();
		} else {
			$propertyType = $propertyReflection->getReadableType();
		}

		if ($propertyFetch instanceof PropertyFetch) {
			$fetchedOnThis = $fetchedOnType instanceof StaticType && $this->isInClass();
		} else {
			if (!$propertyFetch->class instanceof Name) {
				$fetchedOnThis = false;
			} else {
				$fetchedOnThis = in_array(strtolower($propertyFetch->class->toString()), ['self', 'static', 'parent'], true) && $this->isInClass();
			}
		}

		$transformedFetchedOnType = TypeTraverser::map($fetchedOnType, function (Type $type, callable $traverse) use ($fetchedOnThis): Type {
			if ($type instanceof StaticType) {
				if ($fetchedOnThis && $this->isInClass()) {
					return $traverse($type->changeBaseClass($this->getClassReflection()));
				}
				if ($this->isInClass()) {
					return $traverse($type->changeBaseClass($this->getClassReflection())->getStaticObjectType());
				}
			}

			return $traverse($type);
		});

		return TypeTraverser::map($propertyType, function (Type $propertyType, callable $traverse) use ($transformedFetchedOnType, $fetchedOnThis): Type {
			if ($propertyType instanceof StaticType) {
				if ($fetchedOnThis && $this->isInClass()) {
					return $traverse($propertyType->changeBaseClass($this->getClassReflection()));
				}

				return $traverse($transformedFetchedOnType);
			}

			return $traverse($propertyType);
		});
	}

}
