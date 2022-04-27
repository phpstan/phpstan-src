<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use ArrayAccess;
use Closure;
use Generator;
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
use PhpParser\NodeFinder;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\Expr\GetIterableKeyTypeExpr;
use PHPStan\Node\Expr\GetIterableValueTypeExpr;
use PHPStan\Node\Expr\GetOffsetValueTypeExpr;
use PHPStan\Node\Expr\OriginalPropertyTypeExpr;
use PHPStan\Node\Expr\SetOffsetValueTypeExpr;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\Dummy\DummyConstructorReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
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
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\DynamicReturnTypeExtensionRegistry;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\GenericTypeVariableResolver;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\OperatorTypeSpecifyingExtensionRegistry;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StaticTypeFactory;
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
use PHPStan\Type\VoidType;
use Throwable;
use function abs;
use function array_column;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_pop;
use function count;
use function dirname;
use function get_class;
use function in_array;
use function is_float;
use function is_int;
use function is_string;
use function ltrim;
use function max;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function usort;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

class MutatingScope implements Scope
{

	public const CALCULATE_SCALARS_LIMIT = 128;

	private const OPERATOR_SIGIL_MAP = [
		Node\Expr\AssignOp\Plus::class => '+',
		Node\Expr\AssignOp\Minus::class => '-',
		Node\Expr\AssignOp\Mul::class => '*',
		Node\Expr\AssignOp\Pow::class => '^',
		Node\Expr\AssignOp\Div::class => '/',
	];

	/** @var Type[] */
	private array $resolvedTypes = [];

	/** @var array<string, self> */
	private array $truthyScopes = [];

	/** @var array<string, self> */
	private array $falseyScopes = [];

	private ?string $namespace;

	/**
	 * @param array<string, Type> $constantTypes
	 * @param VariableTypeHolder[] $variableTypes
	 * @param VariableTypeHolder[] $moreSpecificTypes
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @param array<string, true> $currentlyAssignedExpressions
	 * @param array<string, true> $currentlyAllowedUndefinedExpressions
	 * @param array<string, Type> $nativeExpressionTypes
	 * @param array<MethodReflection|FunctionReflection> $inFunctionCallsStack
	 */
	public function __construct(
		private ScopeFactory $scopeFactory,
		private ReflectionProvider $reflectionProvider,
		private DynamicReturnTypeExtensionRegistry $dynamicReturnTypeExtensionRegistry,
		private OperatorTypeSpecifyingExtensionRegistry $operatorTypeSpecifyingExtensionRegistry,
		private Standard $printer,
		private TypeSpecifier $typeSpecifier,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private Parser $parser,
		private NodeScopeResolver $nodeScopeResolver,
		private ConstantResolver $constantResolver,
		private ScopeContext $context,
		private PhpVersion $phpVersion,
		private bool $declareStrictTypes = false,
		private array $constantTypes = [],
		private FunctionReflection|MethodReflection|null $function = null,
		?string $namespace = null,
		private array $variableTypes = [],
		private array $moreSpecificTypes = [],
		private array $conditionalExpressions = [],
		private ?string $inClosureBindScopeClass = null,
		private ?ParametersAcceptor $anonymousFunctionReflection = null,
		private bool $inFirstLevelStatement = true,
		private array $currentlyAssignedExpressions = [],
		private array $currentlyAllowedUndefinedExpressions = [],
		private array $nativeExpressionTypes = [],
		private array $inFunctionCallsStack = [],
		private bool $treatPhpDocTypesAsCertain = true,
		private bool $afterExtractCall = false,
		private ?Scope $parentScope = null,
		private bool $explicitMixedInUnknownGenericNew = false,
	)
	{
		if ($namespace === '') {
			$namespace = null;
		}

		$this->namespace = $namespace;
	}

	/** @api */
	public function getFile(): string
	{
		return $this->context->getFile();
	}

	/** @api */
	public function getFileDescription(): string
	{
		if ($this->context->getTraitReflection() === null) {
			return $this->getFile();
		}

		/** @var ClassReflection $classReflection */
		$classReflection = $this->context->getClassReflection();

		$className = $classReflection->getDisplayName();
		if (!$classReflection->isAnonymous()) {
			$className = sprintf('class %s', $className);
		}

		$traitReflection = $this->context->getTraitReflection();
		if ($traitReflection->getFileName() === null) {
			throw new ShouldNotHappenException();
		}

		return sprintf(
			'%s (in context of %s)',
			$traitReflection->getFileName(),
			$className,
		);
	}

	/** @api */
	public function isDeclareStrictTypes(): bool
	{
		return $this->declareStrictTypes;
	}

	public function enterDeclareStrictTypes(): self
	{
		return $this->scopeFactory->create(
			$this->context,
			true,
			[],
			null,
			null,
			$this->variableTypes,
		);
	}

	/** @api */
	public function isInClass(): bool
	{
		return $this->context->getClassReflection() !== null;
	}

	/** @api */
	public function isInTrait(): bool
	{
		return $this->context->getTraitReflection() !== null;
	}

	/** @api */
	public function getClassReflection(): ?ClassReflection
	{
		return $this->context->getClassReflection();
	}

	/** @api */
	public function getTraitReflection(): ?ClassReflection
	{
		return $this->context->getTraitReflection();
	}

	/**
	 * @api
	 * @return FunctionReflection|MethodReflection|null
	 */
	public function getFunction()
	{
		return $this->function;
	}

	/** @api */
	public function getFunctionName(): ?string
	{
		return $this->function !== null ? $this->function->getName() : null;
	}

	/** @api */
	public function getNamespace(): ?string
	{
		return $this->namespace;
	}

	/** @api */
	public function getParentScope(): ?Scope
	{
		return $this->parentScope;
	}

	/**
	 * @return array<string, VariableTypeHolder>
	 */
	private function getVariableTypes(): array
	{
		return $this->variableTypes;
	}

	/** @api */
	public function canAnyVariableExist(): bool
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
			$this->currentlyAllowedUndefinedExpressions,
			$this->nativeExpressionTypes,
			$this->inFunctionCallsStack,
			true,
			$this->parentScope,
		);
	}

	public function afterClearstatcacheCall(): self
	{
		$moreSpecificTypes = $this->moreSpecificTypes;
		foreach (array_keys($moreSpecificTypes) as $exprString) {
			// list from https://www.php.net/manual/en/function.clearstatcache.php

			// stat(), lstat(), file_exists(), is_writable(), is_readable(), is_executable(), is_file(), is_dir(), is_link(), filectime(), fileatime(), filemtime(), fileinode(), filegroup(), fileowner(), filesize(), filetype(), and fileperms().
			foreach ([
				'stat',
				'lstat',
				'file_exists',
				'is_writable',
				'is_writeable',
				'is_readable',
				'is_executable',
				'is_file',
				'is_dir',
				'is_link',
				'filectime',
				'fileatime',
				'filemtime',
				'fileinode',
				'filegroup',
				'fileowner',
				'filesize',
				'filetype',
				'fileperms',
			] as $functionName) {
				if (!str_starts_with((string) $exprString, $functionName . '(') && !str_starts_with((string) $exprString, '\\' . $functionName . '(')) {
					continue;
				}

				unset($moreSpecificTypes[$exprString]);
				continue 2;
			}
		}
		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$this->getVariableTypes(),
			$moreSpecificTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$this->nativeExpressionTypes,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
		);
	}

	/** @api */
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

	/** @api */
	public function getVariableType(string $variableName): Type
	{
		if ($this->isGlobalVariable($variableName)) {
			return new ArrayType(new StringType(), new MixedType());
		}

		if ($this->hasVariableType($variableName)->no()) {
			throw new UndefinedVariableException($this, $variableName);
		}

		if (!array_key_exists($variableName, $this->variableTypes)) {
			return new MixedType();
		}

		return $this->variableTypes[$variableName]->getType();
	}

	/**
	 * @api
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

	/** @api */
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
			if ($node instanceof Node\Stmt\HaltCompiler) {
				return true;
			}
		}

		return false;
	}

	/** @api */
	public function isInAnonymousFunction(): bool
	{
		return $this->anonymousFunctionReflection !== null;
	}

	/** @api */
	public function getAnonymousFunctionReflection(): ?ParametersAcceptor
	{
		return $this->anonymousFunctionReflection;
	}

	/** @api */
	public function getAnonymousFunctionReturnType(): ?Type
	{
		if ($this->anonymousFunctionReflection === null) {
			return null;
		}

		return $this->anonymousFunctionReflection->getReturnType();
	}

	/** @api */
	public function getType(Expr $node): Type
	{
		if ($node instanceof GetIterableKeyTypeExpr) {
			return $this->getType($node->getExpr())->getIterableKeyType();
		}
		if ($node instanceof GetIterableValueTypeExpr) {
			return $this->getType($node->getExpr())->getIterableValueType();
		}
		if ($node instanceof GetOffsetValueTypeExpr) {
			return $this->getType($node->getVar())->getOffsetValueType($this->getType($node->getDim()));
		}
		if ($node instanceof SetOffsetValueTypeExpr) {
			return $this->getType($node->getVar())->setOffsetValueType(
				$node->getDim() !== null ? $this->getType($node->getDim()) : null,
				$this->getType($node->getValue()),
			);
		}
		if ($node instanceof OriginalPropertyTypeExpr) {
			$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($node->getPropertyFetch(), $this);
			if ($propertyReflection === null) {
				return new ErrorType();
			}

			return $propertyReflection->getReadableType();
		}

		$key = $this->getNodeKey($node);

		if (!array_key_exists($key, $this->resolvedTypes)) {
			$this->resolvedTypes[$key] = $this->resolveLateResolvableTypes($this->resolveType($node));
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

		if ($node instanceof Expr\BinaryOp\Equal) {
			if (
				$node->left instanceof Variable
				&& is_string($node->left->name)
				&& $node->right instanceof Variable
				&& is_string($node->right->name)
				&& $node->left->name === $node->right->name
			) {
				return new ConstantBooleanType(true);
			}

			$leftType = $this->getType($node->left);
			$rightType = $this->getType($node->right);

			$stringType = new StringType();
			$integerType = new IntegerType();
			$floatType = new FloatType();
			if (
				($stringType->isSuperTypeOf($leftType)->yes() && $stringType->isSuperTypeOf($rightType)->yes())
				|| ($integerType->isSuperTypeOf($leftType)->yes() && $integerType->isSuperTypeOf($rightType)->yes())
				|| ($floatType->isSuperTypeOf($leftType)->yes() && $floatType->isSuperTypeOf($rightType)->yes())
			) {
				return $this->getType(new Expr\BinaryOp\Identical($node->left, $node->right));
			}

			return new BooleanType();
		}

		if ($node instanceof Expr\BinaryOp\NotEqual) {
			return $this->getType(new Expr\BooleanNot(new BinaryOp\Equal($node->left, $node->right)));
		}

		if ($node instanceof Expr\Empty_) {
			$result = $this->issetCheck($node->expr, static function (Type $type): ?bool {
				$isNull = (new NullType())->isSuperTypeOf($type);
				$isFalsey = (new ConstantBooleanType(false))->isSuperTypeOf($type->toBoolean());
				if ($isNull->maybe()) {
					return null;
				}
				if ($isFalsey->maybe()) {
					return null;
				}

				if ($isNull->yes()) {
					if ($isFalsey->yes()) {
						return false;
					}
					if ($isFalsey->no()) {
						return true;
					}

					return false;
				}

				return !$isFalsey->yes();
			});
			if ($result === null) {
				return new BooleanType();
			}

			return new ConstantBooleanType(!$result);
		}

		if ($node instanceof Node\Expr\BooleanNot) {
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

		if ($node instanceof Node\Expr\BitwiseNot) {
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
			$node instanceof Node\Expr\BinaryOp\BooleanAnd
			|| $node instanceof Node\Expr\BinaryOp\LogicalAnd
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
			$node instanceof Node\Expr\BinaryOp\BooleanOr
			|| $node instanceof Node\Expr\BinaryOp\LogicalOr
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

		if ($node instanceof Node\Expr\BinaryOp\LogicalXor) {
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
					$leftBooleanType->getValue() xor $rightBooleanType->getValue(),
				);
			}

			return new BooleanType();
		}

		if ($node instanceof Expr\BinaryOp\Identical) {
			if (
				$node->left instanceof Variable
				&& is_string($node->left->name)
				&& $node->right instanceof Variable
				&& is_string($node->right->name)
				&& $node->left->name === $node->right->name
			) {
				return new ConstantBooleanType(true);
			}

			if ($this->treatPhpDocTypesAsCertain) {
				$leftType = $this->getType($node->left);
				$rightType = $this->getType($node->right);
			} else {
				$leftType = $this->getNativeType($node->left);
				$rightType = $this->getNativeType($node->right);
			}

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
			return $this->getType(new Expr\BooleanNot(new BinaryOp\Identical($node->left, $node->right)));
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
				$unresolvedClassName = $node->class->toString();
				if (
					strtolower($unresolvedClassName) === 'static'
					&& $this->isInClass()
				) {
					$classType = new StaticType($this->getClassReflection());
				} else {
					$className = $this->resolveName($node->class);
					$classType = new ObjectType($className);
				}
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
						/** @var int|float $newValue */
						$newValue = -$scalarValue->getValue();
						if (!is_int($newValue)) {
							return $type;
						}
						$newTypes[] = new ConstantIntegerType($newValue);
					} elseif ($scalarValue instanceof ConstantFloatType) {
						$newTypes[] = new ConstantFloatType(-$scalarValue->getValue());
					}
				}

				return TypeCombinator::union(...$newTypes);
			}

			if ($type instanceof IntegerRangeType) {
				return $this->resolveType(new Node\Expr\BinaryOp\Mul($node->expr, new LNumber(-1)));
			}

			return $type;
		}

		if ($node instanceof Expr\BinaryOp\Concat || $node instanceof Expr\AssignOp\Concat) {
			return $this->resolveConcatType($node);
		}

		if (
			$node instanceof Node\Expr\BinaryOp\Mul
			|| $node instanceof Node\Expr\AssignOp\Mul
		) {
			if ($node instanceof Node\Expr\AssignOp) {
				$leftType = $this->getType($node->var)->toNumber();
				$rightType = $this->getType($node->expr)->toNumber();
			} else {
				$leftType = $this->getType($node->left)->toNumber();
				$rightType = $this->getType($node->right)->toNumber();
			}

			$floatType = new FloatType();

			if ($leftType instanceof ConstantIntegerType && $leftType->getValue() === 0) {
				if ($floatType->isSuperTypeOf($rightType)->yes()) {
					return new ConstantFloatType(0.0);
				}
				return new ConstantIntegerType(0);
			}
			if ($rightType instanceof ConstantIntegerType && $rightType->getValue() === 0) {
				if ($floatType->isSuperTypeOf($leftType)->yes()) {
					return new ConstantFloatType(0.0);
				}
				return new ConstantIntegerType(0);
			}
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
			$rightType = $this->getType($right);

			$integerType = $rightType->toInteger();
			if (
				$node instanceof Node\Expr\BinaryOp\Mod
				|| $node instanceof Node\Expr\AssignOp\Mod
			) {
				if ($integerType instanceof ConstantIntegerType && $integerType->getValue() === 1) {
					return new ConstantIntegerType(0);
				}
			}

			$rightScalarTypes = TypeUtils::getConstantScalars($rightType->toNumber());
			foreach ($rightScalarTypes as $scalarType) {

				if (
					$scalarType->getValue() === 0
					|| $scalarType->getValue() === 0.0
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

			$leftTypesCount = count($leftTypes);
			$rightTypesCount = count($rightTypes);
			if ($leftTypesCount > 0 && $rightTypesCount > 0) {
				$resultTypes = [];
				$generalize = $leftTypesCount * $rightTypesCount > self::CALCULATE_SCALARS_LIMIT;
				foreach ($leftTypes as $leftType) {
					foreach ($rightTypes as $rightType) {
						$resultType = $this->calculateFromScalars($node, $leftType, $rightType);
						if ($generalize) {
							$resultType = $resultType->generalize(GeneralizePrecision::lessSpecific());
						}
						$resultTypes[] = $resultType;
					}
				}
				return TypeCombinator::union(...$resultTypes);
			}
		}

		if ($node instanceof Node\Expr\BinaryOp\Mod || $node instanceof Expr\AssignOp\Mod) {
			if ($node instanceof Node\Expr\AssignOp) {
				$left = $node->var;
				$right = $node->expr;
			} else {
				$left = $node->left;
				$right = $node->right;
			}

			$leftType = $this->getType($left);
			$rightType = $this->getType($right);

			$integer = new IntegerType();
			$positiveInt = IntegerRangeType::fromInterval(0, null);
			if ($integer->isSuperTypeOf($rightType)->yes()) {
				$rangeMin = null;
				$rangeMax = null;

				if ($rightType instanceof IntegerRangeType) {
					$rangeMax = $rightType->getMax() !== null ? $rightType->getMax() - 1 : null;
				} elseif ($rightType instanceof ConstantIntegerType) {
					$rangeMax = $rightType->getValue() - 1;
				} elseif ($rightType instanceof UnionType) {
					foreach ($rightType->getTypes() as $type) {
						if ($type instanceof IntegerRangeType) {
							if ($type->getMax() === null) {
								$rangeMax = null;
							} else {
								$rangeMax = max($rangeMax, $type->getMax());
							}
						} elseif ($type instanceof ConstantIntegerType) {
							$rangeMax = max($rangeMax, $type->getValue() - 1);
						}
					}
				}

				if ($positiveInt->isSuperTypeOf($leftType)->yes()) {
					$rangeMin = 0;
				} elseif ($rangeMax !== null) {
					$rangeMin = $rangeMax * -1;
				}

				return IntegerRangeType::fromInterval($rangeMin, $rangeMax);
			} elseif ($positiveInt->isSuperTypeOf($leftType)->yes()) {
				return IntegerRangeType::fromInterval(0, null);
			}

			return new IntegerType();
		}

		if ($node instanceof Expr\BinaryOp\Spaceship) {
			return IntegerRangeType::fromInterval(-1, 1);
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
				$this->getType($right)->toNumber(),
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

			if ($node instanceof Expr\AssignOp\Plus || $node instanceof Expr\BinaryOp\Plus) {
				$leftConstantArrays = TypeUtils::getOldConstantArrays($leftType);
				$rightConstantArrays = TypeUtils::getOldConstantArrays($rightType);

				$leftCount = count($leftConstantArrays);
				$rightCount = count($rightConstantArrays);
				if ($leftCount > 0 && $rightCount > 0
					&& ($leftCount + $rightCount < ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT)) {
					$resultTypes = [];
					foreach ($rightConstantArrays as $rightConstantArray) {
						foreach ($leftConstantArrays as $leftConstantArray) {
							$newArrayBuilder = ConstantArrayTypeBuilder::createFromConstantArray($rightConstantArray);
							foreach ($leftConstantArray->getKeyTypes() as $i => $leftKeyType) {
								$optional = $leftConstantArray->isOptionalKey($i);
								$valueType = $leftConstantArray->getOffsetValueType($leftKeyType);
								if (!$optional) {
									if ($rightConstantArray->hasOffsetValueType($leftKeyType)->maybe()) {
										$valueType = TypeCombinator::union($valueType, $rightConstantArray->getOffsetValueType($leftKeyType));
									}
								}
								$newArrayBuilder->setOffsetValueType(
									$leftKeyType,
									$valueType,
									$optional,
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

					$arrayType = new ArrayType(
						$keyType,
						TypeCombinator::union($leftType->getIterableValueType(), $rightType->getIterableValueType()),
					);

					if ($leftType->isIterableAtLeastOnce()->yes() || $rightType->isIterableAtLeastOnce()->yes()) {
						return TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
					}
					return $arrayType;
				}

				if ($leftType instanceof MixedType && $rightType instanceof MixedType) {
					return new BenevolentUnionType([
						new FloatType(),
						new IntegerType(),
						new ArrayType(new MixedType(), new MixedType()),
					]);
				}
			}

			if (($leftType instanceof IntegerRangeType || $leftType instanceof ConstantIntegerType || $leftType instanceof UnionType) &&
				($rightType instanceof IntegerRangeType || $rightType instanceof ConstantIntegerType || $rightType instanceof UnionType) &&
				!($node instanceof Node\Expr\BinaryOp\Pow || $node instanceof Node\Expr\AssignOp\Pow)) {

				if ($leftType instanceof ConstantIntegerType) {
					return $this->integerRangeMath(
						$leftType,
						$node,
						$rightType,
					);
				} elseif ($leftType instanceof UnionType) {

					$unionParts = [];

					foreach ($leftType->getTypes() as $type) {
						if ($type instanceof IntegerRangeType || $type instanceof ConstantIntegerType) {
							$unionParts[] = $this->integerRangeMath($type, $node, $rightType);
						} else {
							$unionParts[] = $type;
						}
					}

					$union = TypeCombinator::union(...$unionParts);
					if ($leftType instanceof BenevolentUnionType) {
						return TypeUtils::toBenevolentUnion($union)->toNumber();
					}

					return $union->toNumber();
				}

				return $this->integerRangeMath($leftType, $node, $rightType);
			}

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
			if ($leftNumberType instanceof ErrorType || $rightNumberType instanceof ErrorType) {
				return new ErrorType();
			}

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
			$parts = [];
			foreach ($node->parts as $part) {
				if ($part instanceof EncapsedStringPart) {
					$parts[] = new ConstantStringType($part->value);
					continue;
				}

				$partStringType = $this->getType($part)->toString();
				if ($partStringType instanceof ErrorType) {
					return new ErrorType();
				}

				$parts[] = $partStringType;
			}

			$constantString = new ConstantStringType('');
			foreach ($parts as $part) {
				if ($part instanceof ConstantStringType) {
					$constantString = $constantString->append($part);
					continue;
				}

				$isNonEmpty = false;
				$isLiteralString = true;
				foreach ($parts as $partType) {
					if ($partType->isNonEmptyString()->yes()) {
						$isNonEmpty = true;
					}
					if ($partType->isLiteralString()->yes()) {
						continue;
					}
					$isLiteralString = false;
				}

				$accessoryTypes = [];
				if ($isNonEmpty === true) {
					$accessoryTypes[] = new AccessoryNonEmptyStringType();
				}
				if ($isLiteralString === true) {
					$accessoryTypes[] = new AccessoryLiteralStringType();
				}
				if (count($accessoryTypes) > 0) {
					$accessoryTypes[] = new StringType();
					return new IntersectionType($accessoryTypes);
				}

				return new StringType();
			}

			return $constantString;
		} elseif ($node instanceof DNumber) {
			return new ConstantFloatType($node->value);
		} elseif ($node instanceof Expr\CallLike && $node->isFirstClassCallable()) {
			if ($node instanceof FuncCall) {
				if ($node->name instanceof Name) {
					if ($this->reflectionProvider->hasFunction($node->name, $this)) {
						return $this->createFirstClassCallable(
							$this->reflectionProvider->getFunction($node->name, $this)->getVariants(),
						);
					}

					return new ObjectType(Closure::class);
				}

				$callableType = $this->getType($node->name);
				if (!$callableType->isCallable()->yes()) {
					return new ObjectType(Closure::class);
				}

				return $this->createFirstClassCallable(
					$callableType->getCallableParametersAcceptors($this),
				);
			}

			if ($node instanceof MethodCall) {
				if (!$node->name instanceof Node\Identifier) {
					return new ObjectType(Closure::class);
				}

				$varType = $this->getType($node->var);
				$method = $this->getMethodReflection($varType, $node->name->toString());
				if ($method === null) {
					return new ObjectType(Closure::class);
				}

				return $this->createFirstClassCallable($method->getVariants());
			}

			if ($node instanceof Expr\StaticCall) {
				if (!$node->class instanceof Name) {
					return new ObjectType(Closure::class);
				}

				$classType = $this->resolveTypeByName($node->class);
				if (!$node->name instanceof Node\Identifier) {
					return new ObjectType(Closure::class);
				}

				$methodName = $node->name->toString();
				if (!$classType->hasMethod($methodName)->yes()) {
					return new ObjectType(Closure::class);
				}

				return $this->createFirstClassCallable($classType->getMethod($methodName, $this)->getVariants());
			}

			if ($node instanceof New_) {
				return new ErrorType();
			}

			throw new ShouldNotHappenException();
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
					throw new ShouldNotHappenException();
				}
				$parameters[] = new NativeParameterReflection(
					$param->var->name,
					$firstOptionalParameterIndex !== null && $i >= $firstOptionalParameterIndex,
					$this->getFunctionType($param->type, $this->isParameterValueNullable($param), false),
					$param->byRef
						? PassedByReference::createCreatesNewVariable()
						: PassedByReference::createNo(),
					$param->variadic,
					$param->default !== null ? $this->getType($param->default) : null,
				);
			}

			$callableParameters = null;
			$arrayMapArgs = $node->getAttribute('arrayMapArgs');
			if ($arrayMapArgs !== null) {
				$callableParameters = [];
				foreach ($arrayMapArgs as $funcCallArg) {
					$callableParameters[] = new DummyParameter('item', $this->getType($funcCallArg->value)->getIterableValueType(), false, PassedByReference::createNo(), false, null);
				}
			}

			if ($node instanceof Expr\ArrowFunction) {
				$returnType = $this->enterArrowFunctionWithoutReflection($node, $callableParameters)->getType($node->expr);
				if ($node->returnType !== null) {
					$returnType = TypehintHelper::decideType($this->getFunctionType($node->returnType, false, false), $returnType);
				}
			} else {
				$closureScope = $this->enterAnonymousFunctionWithoutReflection($node, $callableParameters);
				$closureReturnStatements = [];
				$closureYieldStatements = [];
				$closureExecutionEnds = [];
				$this->nodeScopeResolver->processStmtNodes($node, $node->stmts, $closureScope, static function (Node $node, Scope $scope) use ($closureScope, &$closureReturnStatements, &$closureYieldStatements, &$closureExecutionEnds): void {
					if ($scope->getAnonymousFunctionReflection() !== $closureScope->getAnonymousFunctionReflection()) {
						return;
					}

					if ($node instanceof ExecutionEndNode) {
						if ($node->getStatementResult()->isAlwaysTerminating()) {
							foreach ($node->getStatementResult()->getExitPoints() as $exitPoint) {
								if ($exitPoint->getStatement() instanceof Node\Stmt\Return_) {
									continue;
								}

								$closureExecutionEnds[] = $node;
								break;
							}

							if (count($node->getStatementResult()->getExitPoints()) === 0) {
								$closureExecutionEnds[] = $node;
							}
						}

						return;
					}

					if ($node instanceof Node\Stmt\Return_) {
						$closureReturnStatements[] = [$node, $scope];
					}

					if (!$node instanceof Expr\Yield_ && !$node instanceof Expr\YieldFrom) {
						return;
					}

					$closureYieldStatements[] = [$node, $scope];
				});

				$returnTypes = [];
				$hasNull = false;
				foreach ($closureReturnStatements as [$returnNode, $returnScope]) {
					if ($returnNode->expr === null) {
						$hasNull = true;
						continue;
					}

					$returnTypes[] = $returnScope->getType($returnNode->expr);
				}

				if (count($returnTypes) === 0) {
					if (count($closureExecutionEnds) > 0 && !$hasNull) {
						$returnType = new NeverType(true);
					} else {
						$returnType = new VoidType();
					}
				} else {
					if (count($closureExecutionEnds) > 0) {
						$returnTypes[] = new NeverType(true);
					}
					if ($hasNull) {
						$returnTypes[] = new NullType();
					}
					$returnType = TypeCombinator::union(...$returnTypes);
				}

				if (count($closureYieldStatements) > 0) {
					$keyTypes = [];
					$valueTypes = [];
					foreach ($closureYieldStatements as [$yieldNode, $yieldScope]) {
						if ($yieldNode instanceof Expr\Yield_) {
							if ($yieldNode->key === null) {
								$keyTypes[] = new IntegerType();
							} else {
								$keyTypes[] = $yieldScope->getType($yieldNode->key);
							}

							if ($yieldNode->value === null) {
								$valueTypes[] = new NullType();
							} else {
								$valueTypes[] = $yieldScope->getType($yieldNode->value);
							}

							continue;
						}

						$yieldFromType = $yieldScope->getType($yieldNode->expr);
						$keyTypes[] = $yieldFromType->getIterableKeyType();
						$valueTypes[] = $yieldFromType->getIterableValueType();
					}

					$returnType = new GenericObjectType(Generator::class, [
						TypeCombinator::union(...$keyTypes),
						TypeCombinator::union(...$valueTypes),
						new MixedType(),
						$returnType,
					]);
				} else {
					$returnType = TypehintHelper::decideType($this->getFunctionType($node->returnType, false, false), $returnType);
				}
			}

			return new ClosureType(
				$parameters,
				$returnType,
				$isVariadic,
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

					return new StaticType($this->getClassReflection());
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
			if (count($node->items) > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
				$arrayBuilder->degradeToGeneralArray();
			}
			foreach ($node->items as $arrayItem) {
				if ($arrayItem === null) {
					continue;
				}

				$valueType = $this->getType($arrayItem->value);
				if ($arrayItem->unpack) {
					if ($valueType instanceof ConstantArrayType) {
						$hasStringKey = false;
						foreach ($valueType->getKeyTypes() as $keyType) {
							if ($keyType instanceof ConstantStringType) {
								$hasStringKey = true;
								break;
							}
						}

						foreach ($valueType->getValueTypes() as $i => $innerValueType) {
							if ($hasStringKey && $this->phpVersion->supportsArrayUnpackingWithStringKeys()) {
								$arrayBuilder->setOffsetValueType($valueType->getKeyTypes()[$i], $innerValueType);
							} else {
								$arrayBuilder->setOffsetValueType(null, $innerValueType);
							}
						}
					} else {
						$arrayBuilder->degradeToGeneralArray();

						if (! (new StringType())->isSuperTypeOf($valueType->getIterableKeyType())->no() && $this->phpVersion->supportsArrayUnpackingWithStringKeys()) {
							$arrayBuilder->setOffsetValueType($valueType->getIterableKeyType(), $valueType->getIterableValueType());
						} else {
							$arrayBuilder->setOffsetValueType(new IntegerType(), $valueType->getIterableValueType(), !$valueType->isIterableAtLeastOnce()->yes() && !$valueType->getIterableValueType()->isIterableAtLeastOnce()->yes());
						}
					}
				} else {
					$arrayBuilder->setOffsetValueType(
						$arrayItem->key !== null ? $this->getType($arrayItem->key) : null,
						$valueType,
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
		} elseif ($node instanceof Node\Expr\Cast\String_) {
			return $this->getType($node->expr)->toString();
		} elseif ($node instanceof Node\Expr\Cast\Array_) {
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
					sprintf('%s::%s', $function->getDeclaringClass()->getName(), $function->getName()),
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
			$stringType = new StringType();
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
			} elseif ($stringType->isSuperTypeOf($varType)->yes()) {
				if ($varType->isLiteralString()->yes()) {
					return new IntersectionType([$stringType, new AccessoryLiteralStringType()]);
				}
				return $stringType;
			}

			if ($node instanceof Expr\PreInc) {
				return $this->getType(new BinaryOp\Plus($node->var, new LNumber(1)));
			}

			return $this->getType(new BinaryOp\Minus($node->var, new LNumber(1)));
		} elseif ($node instanceof Expr\Yield_) {
			$functionReflection = $this->getFunction();
			if ($functionReflection === null) {
				return new MixedType();
			}

			$returnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
			if (!$returnType instanceof TypeWithClassName) {
				return new MixedType();
			}

			$generatorSendType = GenericTypeVariableResolver::getType($returnType, Generator::class, 'TSend');
			if ($generatorSendType === null) {
				return new MixedType();
			}

			return $generatorSendType;
		} elseif ($node instanceof Expr\YieldFrom) {
			$yieldFromType = $this->getType($node->expr);

			if (!$yieldFromType instanceof TypeWithClassName) {
				return new MixedType();
			}

			$generatorReturnType = GenericTypeVariableResolver::getType($yieldFromType, Generator::class, 'TReturn');
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
					throw new ShouldNotHappenException();
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

		if ($node instanceof Expr\Isset_) {
			$issetResult = true;
			foreach ($node->vars as $var) {
				$result = $this->issetCheck($var, static function (Type $type): ?bool {
					$isNull = (new NullType())->isSuperTypeOf($type);
					if ($isNull->maybe()) {
						return null;
					}

					return !$isNull->yes();
				});
				if ($result !== null) {
					if (!$result) {
						return new ConstantBooleanType($result);
					}

					continue;
				}

				$issetResult = $result;
			}

			if ($issetResult === null) {
				return new BooleanType();
			}

			return new ConstantBooleanType($issetResult);
		}

		if ($node instanceof Expr\AssignOp\Coalesce) {
			return $this->getType(new BinaryOp\Coalesce($node->var, $node->expr, $node->getAttributes()));
		}

		if ($node instanceof Expr\BinaryOp\Coalesce) {
			$leftType = $this->getType($node->left);
			$rightType = $this->filterByFalseyValue(
				new BinaryOp\NotIdentical($node->left, new ConstFetch(new Name('null'))),
			)->getType($node->right);

			$result = $this->issetCheck($node->left, static function (Type $type): ?bool {
				$isNull = (new NullType())->isSuperTypeOf($type);
				if ($isNull->maybe()) {
					return null;
				}

				return !$isNull->yes();
			});

			if ($result === null) {
				return TypeCombinator::union(
					TypeCombinator::removeNull($leftType),
					$rightType,
				);
			}

			if ($result) {
				return TypeCombinator::removeNull($leftType);
			}

			return $rightType;
		}

		if ($node instanceof ConstFetch) {
			$constName = (string) $node->name;
			$loweredConstName = strtolower($constName);
			if ($loweredConstName === 'true') {
				return new ConstantBooleanType(true);
			} elseif ($loweredConstName === 'false') {
				return new ConstantBooleanType(false);
			} elseif ($loweredConstName === 'null') {
				return new NullType();
			}

			if ($node->name->isFullyQualified()) {
				if (array_key_exists($node->name->toCodeString(), $this->constantTypes)) {
					return $this->constantResolver->resolveConstantType($node->name->toString(), $this->constantTypes[$node->name->toCodeString()]);
				}
			}

			if ($this->getNamespace() !== null) {
				$constantName = new FullyQualified([$this->getNamespace(), $constName]);
				if (array_key_exists($constantName->toCodeString(), $this->constantTypes)) {
					return $this->constantResolver->resolveConstantType($constantName->toString(), $this->constantTypes[$constantName->toCodeString()]);
				}
			}

			$constantName = new FullyQualified($constName);
			if (array_key_exists($constantName->toCodeString(), $this->constantTypes)) {
				return $this->constantResolver->resolveConstantType($constantName->toString(), $this->constantTypes[$constantName->toCodeString()]);
			}

			$constantType = $this->constantResolver->resolveConstant($node->name, $this);
			if ($constantType !== null) {
				return $constantType;
			}

			return new ErrorType();
		} elseif ($node instanceof Node\Expr\ClassConstFetch && $node->name instanceof Node\Identifier) {
			$constantName = $node->name->name;
			$isObject = false;
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
							return new GenericClassStringType(new StaticType($this->getClassReflection()));
						}

						$namesToResolve[] = 'static';
						$isObject = true;
					}
				}
				if (in_array(strtolower($constantClass), $namesToResolve, true)) {
					$resolvedName = $this->resolveName($node->class);
					if ($resolvedName === 'parent' && strtolower($constantName) === 'class') {
						return new ClassStringType();
					}
					$constantClassType = $this->resolveTypeByName($node->class);
				}

				if (strtolower($constantName) === 'class') {
					return new ConstantStringType($constantClassType->getClassName(), true);
				}
			} else {
				$constantClassType = $this->getType($node->class);
				$isObject = true;
			}

			$referencedClasses = TypeUtils::getDirectClassNames($constantClassType);
			if (strtolower($constantName) === 'class') {
				if (count($referencedClasses) === 0) {
					if ((new ObjectWithoutClassType())->isSuperTypeOf($constantClassType)->yes()) {
						return new ClassStringType();
					}
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

				$constantClassReflection = $this->reflectionProvider->getClass($referencedClass);
				if (!$constantClassReflection->hasConstant($constantName)) {
					continue;
				}

				if ($constantClassReflection->isEnum() && $constantClassReflection->hasEnumCase($constantName)) {
					$types[] = new EnumCaseObjectType($constantClassReflection->getName(), $constantName);
					continue;
				}

				$constantReflection = $constantClassReflection->getConstant($constantName);
				if (
					$constantReflection instanceof ClassConstantReflection
					&& $isObject
					&& !$constantClassReflection->isFinal()
					&& !$constantReflection->hasPhpDocType()
				) {
					return new MixedType();
				}

				if (
					$isObject
					&& (
						!$constantReflection instanceof ClassConstantReflection
						|| !$constantClassReflection->isFinal()
					)
				) {
					$constantType = $constantReflection->getValueType();
				} else {
					$constantType = ConstantTypeHelper::getTypeFromValue($constantReflection->getValue());
				}

				$constantType = $this->constantResolver->resolveConstantType(
					sprintf('%s::%s', $constantClassReflection->getName(), $constantName),
					$constantType,
				);
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
						return $this->filterByTruthyValue($node->cond)->getType($node->cond);
					}

					return $this->filterByFalseyValue($node->cond)->getType($node->else);
				}
				return TypeCombinator::union(
					TypeCombinator::remove($this->filterByTruthyValue($node->cond)->getType($node->cond), StaticTypeFactory::falsey()),
					$this->filterByFalseyValue($node->cond)->getType($node->else),
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
				$this->filterByFalseyValue($node->cond)->getType($node->else),
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
					$this->getType($node->var),
				),
			);
		}

		if ($node instanceof MethodCall && $node->name instanceof Node\Identifier) {
			$typeCallback = function () use ($node): Type {
				$returnType = $this->methodCallReturnType(
					$this->getType($node->var),
					$node->name->name,
					$node,
				);
				if ($returnType === null) {
					return new ErrorType();
				}
				return $returnType;
			};

			return $this->getNullsafeShortCircuitingType($node->var, $typeCallback());
		}

		if ($node instanceof Expr\NullsafeMethodCall) {
			$varType = $this->getType($node->var);
			if (!TypeCombinator::containsNull($varType)) {
				return $this->getType(new MethodCall($node->var, $node->name, $node->args));
			}

			return TypeCombinator::union(
				$this->filterByTruthyValue(new BinaryOp\NotIdentical($node->var, new ConstFetch(new Name('null'))))
					->getType(new MethodCall($node->var, $node->name, $node->args)),
				new NullType(),
			);
		}

		if ($node instanceof Expr\StaticCall && $node->name instanceof Node\Identifier) {
			$typeCallback = function () use ($node): Type {
				if ($node->class instanceof Name) {
					$staticMethodCalledOnType = $this->resolveTypeByName($node->class);
				} else {
					$staticMethodCalledOnType = TypeTraverser::map($this->getType($node->class), static function (Type $type, callable $traverse): Type {
						if ($type instanceof UnionType) {
							return $traverse($type);
						}

						if ($type instanceof GenericClassStringType) {
							return $type->getGenericType();
						}

						if ($type instanceof ConstantStringType && $type->isClassString()) {
							return new ObjectType($type->getValue());
						}

						return $type;
					});
				}

				$returnType = $this->methodCallReturnType(
					$staticMethodCalledOnType,
					$node->name->toString(),
					$node,
				);
				if ($returnType === null) {
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
				$returnType = $this->propertyFetchType(
					$this->getType($node->var),
					$node->name->name,
					$node,
				);
				if ($returnType === null) {
					return new ErrorType();
				}
				return $returnType;
			};

			return $this->getNullsafeShortCircuitingType($node->var, $typeCallback());
		}

		if ($node instanceof Expr\NullsafePropertyFetch) {
			$varType = $this->getType($node->var);
			if (!TypeCombinator::containsNull($varType)) {
				return $this->getType(new PropertyFetch($node->var, $node->name));
			}

			return TypeCombinator::union(
				$this->filterByTruthyValue(new BinaryOp\NotIdentical($node->var, new ConstFetch(new Name('null'))))
					->getType(new PropertyFetch($node->var, $node->name)),
				new NullType(),
			);
		}

		if (
			$node instanceof Expr\StaticPropertyFetch
			&& $node->name instanceof Node\VarLikeIdentifier
		) {
			$typeCallback = function () use ($node): Type {
				if ($node->class instanceof Name) {
					$staticPropertyFetchedOnType = $this->resolveTypeByName($node->class);
				} else {
					$staticPropertyFetchedOnType = $this->getType($node->class);
					if ($staticPropertyFetchedOnType instanceof GenericClassStringType) {
						$staticPropertyFetchedOnType = $staticPropertyFetchedOnType->getGenericType();
					}
				}

				$returnType = $this->propertyFetchType(
					$staticPropertyFetchedOnType,
					$node->name->toString(),
					$node,
				);
				if ($returnType === null) {
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
					$node->getArgs(),
					$calledOnType->getCallableParametersAcceptors($this),
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

				$resolvedType = $dynamicFunctionReturnTypeExtension->getTypeFromFunctionCall(
					$functionReflection,
					$node,
					$this,
				);
				if ($resolvedType !== null) {
					return $resolvedType;
				}
			}

			return ParametersAcceptorSelector::selectFromArgs(
				$this,
				$node->getArgs(),
				$functionReflection->getVariants(),
			)->getReturnType();
		}

		return new MixedType();
	}

	private function resolveConcatType(Expr\BinaryOp\Concat|Expr\AssignOp\Concat $node): Type
	{
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
			$rightStringType,
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

		// we limit the number of union-types for performance reasons
		if ($leftStringType instanceof UnionType && count($leftStringType->getTypes()) <= 16 && $rightStringType instanceof ConstantStringType) {
			$constantStrings = TypeUtils::getConstantStrings($leftStringType);
			if (count($constantStrings) > 0) {
				$strings = [];
				foreach ($constantStrings as $constantString) {
					if ($constantString->getValue() === '') {
						$strings[] = $rightStringType;

						continue;
					}
					$strings[] = $constantString->append($rightStringType);
				}
				return TypeCombinator::union(...$strings);
			}
		}
		if ($rightStringType instanceof UnionType && count($rightStringType->getTypes()) <= 16 && $leftStringType instanceof ConstantStringType) {
			$constantStrings = TypeUtils::getConstantStrings($rightStringType);
			if (count($constantStrings) > 0) {
				$strings = [];
				foreach ($constantStrings as $constantString) {
					if ($constantString->getValue() === '') {
						$strings[] = $leftStringType;

						continue;
					}
					$strings[] = $leftStringType->append($constantString);
				}
				return TypeCombinator::union(...$strings);
			}
		}

		$accessoryTypes = [];
		if ($leftStringType->isNonEmptyString()->or($rightStringType->isNonEmptyString())->yes()) {
			$accessoryTypes[] = new AccessoryNonEmptyStringType();
		}

		if ($leftStringType->isLiteralString()->and($rightStringType->isLiteralString())->yes()) {
			$accessoryTypes[] = new AccessoryLiteralStringType();
		}

		if (count($accessoryTypes) > 0) {
			$accessoryTypes[] = new StringType();
			return new IntersectionType($accessoryTypes);
		}

		return new StringType();
	}

	private function getNullsafeShortCircuitingType(Expr $expr, Type $type): Type
	{
		if ($expr instanceof Expr\NullsafePropertyFetch || $expr instanceof Expr\NullsafeMethodCall) {
			$varType = $this->getType($expr->var);
			if (TypeCombinator::containsNull($varType)) {
				return TypeCombinator::addNull($type);
			}

			return $type;
		}

		if ($expr instanceof Expr\ArrayDimFetch) {
			return $this->getNullsafeShortCircuitingType($expr->var, $type);
		}

		if ($expr instanceof PropertyFetch) {
			return $this->getNullsafeShortCircuitingType($expr->var, $type);
		}

		if ($expr instanceof Expr\StaticPropertyFetch && $expr->class instanceof Expr) {
			return $this->getNullsafeShortCircuitingType($expr->class, $type);
		}

		if ($expr instanceof MethodCall) {
			return $this->getNullsafeShortCircuitingType($expr->var, $type);
		}

		if ($expr instanceof Expr\StaticCall && $expr->class instanceof Expr) {
			return $this->getNullsafeShortCircuitingType($expr->class, $type);
		}

		return $type;
	}

	/**
	 * @param callable(Type): ?bool $typeCallback
	 */
	private function issetCheck(Expr $expr, callable $typeCallback, ?bool $result = null): ?bool
	{
		// mirrored in PHPStan\Rules\IssetCheck
		if ($expr instanceof Node\Expr\Variable && is_string($expr->name)) {
			$hasVariable = $this->hasVariableType($expr->name);
			if ($hasVariable->maybe()) {
				return null;
			}

			if ($result === null) {
				if ($hasVariable->yes()) {
					if ($expr->name === '_SESSION') {
						return null;
					}

					return $typeCallback($this->getVariableType($expr->name));
				}

				return false;
			}

			return $result;
		} elseif ($expr instanceof Node\Expr\ArrayDimFetch && $expr->dim !== null) {
			$type = $this->treatPhpDocTypesAsCertain
				? $this->getType($expr->var)
				: $this->getNativeType($expr->var);
			$dimType = $this->treatPhpDocTypesAsCertain
				? $this->getType($expr->dim)
				: $this->getNativeType($expr->dim);
			$hasOffsetValue = $type->hasOffsetValueType($dimType);
			if (!$type->isOffsetAccessible()->yes()) {
				return $result ?? $this->issetCheckUndefined($expr->var);
			}

			if ($hasOffsetValue->no()) {
				if ($result !== null) {
					return $result;
				}

				return false;
			}

			if ($hasOffsetValue->maybe()) {
				return null;
			}

			// If offset is cannot be null, store this error message and see if one of the earlier offsets is.
			// E.g. $array['a']['b']['c'] ?? null; is a valid coalesce if a OR b or C might be null.
			if ($hasOffsetValue->yes()) {
				if ($result !== null) {
					return $result;
				}

				$result = $typeCallback($type->getOffsetValueType($dimType));

				if ($result !== null) {
					return $this->issetCheck($expr->var, $typeCallback, $result);
				}
			}

			// Has offset, it is nullable
			return null;

		} elseif ($expr instanceof Node\Expr\PropertyFetch || $expr instanceof Node\Expr\StaticPropertyFetch) {

			$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($expr, $this);

			if ($propertyReflection === null) {
				if ($expr instanceof Node\Expr\PropertyFetch) {
					return $this->issetCheckUndefined($expr->var);
				}

				if ($expr->class instanceof Expr) {
					return $this->issetCheckUndefined($expr->class);
				}

				return null;
			}

			if (!$propertyReflection->isNative()) {
				if ($expr instanceof Node\Expr\PropertyFetch) {
					return $this->issetCheckUndefined($expr->var);
				}

				if ($expr->class instanceof Expr) {
					return $this->issetCheckUndefined($expr->class);
				}

				return null;
			}

			$nativeType = $propertyReflection->getNativeType();
			if (!$nativeType instanceof MixedType) {
				if (!$this->isSpecified($expr)) {
					if ($expr instanceof Node\Expr\PropertyFetch) {
						return $this->issetCheckUndefined($expr->var);
					}

					if ($expr->class instanceof Expr) {
						return $this->issetCheckUndefined($expr->class);
					}

					return null;
				}
			}

			if ($result !== null) {
				return $result;
			}

			$result = $typeCallback($propertyReflection->getWritableType());
			if ($result !== null) {
				if ($expr instanceof Node\Expr\PropertyFetch) {
					return $this->issetCheck($expr->var, $typeCallback, $result);
				}

				if ($expr->class instanceof Expr) {
					return $this->issetCheck($expr->class, $typeCallback, $result);
				}
			}

			return $result;
		}

		if ($result !== null) {
			return $result;
		}

		return $typeCallback($this->getType($expr));
	}

	private function issetCheckUndefined(Expr $expr): ?bool
	{
		if ($expr instanceof Node\Expr\Variable && is_string($expr->name)) {
			$hasVariable = $this->hasVariableType($expr->name);
			if (!$hasVariable->no()) {
				return null;
			}

			return false;
		}

		if ($expr instanceof Node\Expr\ArrayDimFetch && $expr->dim !== null) {
			$type = $this->getType($expr->var);
			$dimType = $this->getType($expr->dim);
			$hasOffsetValue = $type->hasOffsetValueType($dimType);
			if (!$type->isOffsetAccessible()->yes()) {
				return $this->issetCheckUndefined($expr->var);
			}

			if (!$hasOffsetValue->no()) {
				return $this->issetCheckUndefined($expr->var);
			}

			return false;
		}

		if ($expr instanceof Expr\PropertyFetch) {
			return $this->issetCheckUndefined($expr->var);
		}

		if ($expr instanceof Expr\StaticPropertyFetch && $expr->class instanceof Expr) {
			return $this->issetCheckUndefined($expr->class);
		}

		return null;
	}

	/**
	 * @param ParametersAcceptor[] $variants
	 */
	private function createFirstClassCallable(array $variants): Type
	{
		$closureTypes = [];
		foreach ($variants as $variant) {
			$parameters = $variant->getParameters();
			$closureTypes[] = new ClosureType(
				$parameters,
				$variant->getReturnType(),
				$variant->isVariadic(),
				$variant->getTemplateTypeMap(),
				$variant->getResolvedTemplateTypeMap(),
			);
		}

		return TypeCombinator::union(...$closureTypes);
	}

	/** @api */
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
					$this->getNativeType($expr->var),
				),
			);
		}

		return $this->getType($expr);
	}

	/** @api */
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
			$this->nodeScopeResolver,
			$this->constantResolver,
			$this->context,
			$this->phpVersion,
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
			$this->currentlyAllowedUndefinedExpressions,
			$this->nativeExpressionTypes,
			$this->inFunctionCallsStack,
			false,
			$this->afterExtractCall,
			$this->parentScope,
			$this->explicitMixedInUnknownGenericNew,
		);
	}

	private function promoteNativeTypes(): self
	{
		$variableTypes = $this->variableTypes;
		foreach ($this->nativeExpressionTypes as $expressionType => $type) {
			if (substr($expressionType, 0, 1) !== '$') {
				throw new ShouldNotHappenException();
			}

			$variableName = substr($expressionType, 1);
			$has = $this->hasVariableType($variableName);
			if ($has->no()) {
				throw new ShouldNotHappenException();
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
			$this->currentlyAllowedUndefinedExpressions,
			[],
		);
	}

	/**
	 * @param Node\Expr\PropertyFetch|Node\Expr\StaticPropertyFetch $propertyFetch
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

	/** @api */
	protected function getTypeFromArrayDimFetch(
		Expr\ArrayDimFetch $arrayDimFetch,
		Type $offsetType,
		Type $offsetAccessibleType,
	): Type
	{
		if ($arrayDimFetch->dim === null) {
			throw new ShouldNotHappenException();
		}

		if (!$offsetAccessibleType->isArray()->yes() && (new ObjectType(ArrayAccess::class))->isSuperTypeOf($offsetAccessibleType)->yes()) {
			return $this->getType(
				new MethodCall(
					$arrayDimFetch->var,
					new Node\Identifier('offsetGet'),
					[
						new Node\Arg($arrayDimFetch->dim),
					],
				),
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
			throw new ShouldNotHappenException();
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
			return $this->getTypeFromValue(((int) $leftNumberValue) % ((int) $rightNumberValue));
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
				if ($currentClassReflection->getParentClass() !== null) {
					return $currentClassReflection->getParentClass()->getName();
				}
				return null;
			case 'static':
				return null;
		}

		return $originalClass;
	}

	/** @api */
	public function resolveName(Name $name): string
	{
		$originalClass = (string) $name;
		if ($this->isInClass()) {
			if (in_array(strtolower($originalClass), [
				'self',
				'static',
			], true)) {
				if ($this->inClosureBindScopeClass !== null && $this->inClosureBindScopeClass !== 'static') {
					return $this->inClosureBindScopeClass;
				}
				return $this->getClassReflection()->getName();
			} elseif ($originalClass === 'parent') {
				$currentClassReflection = $this->getClassReflection();
				if ($currentClassReflection->getParentClass() !== null) {
					return $currentClassReflection->getParentClass()->getName();
				}
			}
		}

		return $originalClass;
	}

	/** @api */
	public function resolveTypeByName(Name $name): TypeWithClassName
	{
		if ($name->toLowerString() === 'static' && $this->isInClass()) {
			if ($this->inClosureBindScopeClass !== null && $this->inClosureBindScopeClass !== 'static') {
				if ($this->reflectionProvider->hasClass($this->inClosureBindScopeClass)) {
					return new StaticType($this->reflectionProvider->getClass($this->inClosureBindScopeClass));
				}
			}

			return new StaticType($this->getClassReflection());
		}

		$originalClass = $this->resolveName($name);
		if ($this->isInClass()) {
			if ($this->inClosureBindScopeClass !== null && $this->inClosureBindScopeClass !== 'static' && $originalClass === $this->getClassReflection()->getName()) {
				if ($this->reflectionProvider->hasClass($this->inClosureBindScopeClass)) {
					return new ThisType($this->reflectionProvider->getClass($this->inClosureBindScopeClass));
				}
				return new ObjectType($this->inClosureBindScopeClass);
			}

			$thisType = new ThisType($this->getClassReflection());
			$ancestor = $thisType->getAncestorWithClassName($originalClass);
			if ($ancestor !== null) {
				return $ancestor;
			}
		}

		return new ObjectType($originalClass);
	}

	/**
	 * @api
	 * @param mixed $value
	 */
	public function getTypeFromValue($value): Type
	{
		return ConstantTypeHelper::getTypeFromValue($value);
	}

	/** @api */
	public function isSpecified(Expr $node): bool
	{
		$exprString = $this->getNodeKey($node);

		return isset($this->moreSpecificTypes[$exprString])
			&& $this->moreSpecificTypes[$exprString]->getCertainty()->yes();
	}

	/**
	 * @param MethodReflection|FunctionReflection $reflection
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
			$this->currentlyAllowedUndefinedExpressions,
			$this->nativeExpressionTypes,
			$stack,
			$this->afterExtractCall,
			$this->parentScope,
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
			$this->currentlyAllowedUndefinedExpressions,
			$this->nativeExpressionTypes,
			$stack,
			$this->afterExtractCall,
			$this->parentScope,
		);
	}

	/** @api */
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

	/** @api */
	public function isInFunctionExists(string $functionName): bool
	{
		$expr = new FuncCall(new FullyQualified('function_exists'), [
			new Arg(new String_(ltrim($functionName, '\\'))),
		]);

		return (new ConstantBooleanType(true))->isSuperTypeOf($this->getType($expr))->yes();
	}

	/** @api */
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
			],
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
			$this->anonymousFunctionReflection,
		);
	}

	/**
	 * @api
	 * @param Type[] $phpDocParameterTypes
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
		?bool $isPure = null,
	): self
	{
		if (!$this->isInClass()) {
			throw new ShouldNotHappenException();
		}

		return $this->enterFunctionLike(
			new PhpMethodFromParserNodeReflection(
				$this->getClassReflection(),
				$classMethod,
				$this->getFile(),
				$templateTypeMap,
				$this->getRealParameterTypes($classMethod),
				array_map(static fn (Type $type): Type => TemplateTypeHelper::toArgument($type), $phpDocParameterTypes),
				$this->getRealParameterDefaultValues($classMethod),
				$this->transformStaticType($this->getFunctionType($classMethod->returnType, false, false)),
				$phpDocReturnType !== null ? TemplateTypeHelper::toArgument($phpDocReturnType) : null,
				$throwType,
				$deprecatedDescription,
				$isDeprecated,
				$isInternal,
				$isFinal,
				$isPure,
			),
			!$classMethod->isStatic(),
		);
	}

	private function transformStaticType(Type $type): Type
	{
		return TypeTraverser::map($type, function (Type $type, callable $traverse): Type {
			if (!$this->isInClass()) {
				return $type;
			}
			if ($type instanceof StaticType) {
				$classReflection = $this->getClassReflection();
				$changedType = $type->changeBaseClass($classReflection);
				if ($classReflection->isFinal()) {
					$changedType = $changedType->getStaticObjectType();
				}
				return $traverse($changedType);
			}

			return $traverse($type);
		});
	}

	/**
	 * @return Type[]
	 */
	private function getRealParameterTypes(Node\FunctionLike $functionLike): array
	{
		$realParameterTypes = [];
		foreach ($functionLike->getParams() as $parameter) {
			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new ShouldNotHappenException();
			}
			$realParameterTypes[$parameter->var->name] = $this->getFunctionType(
				$parameter->type,
				$this->isParameterValueNullable($parameter),
				false,
			);
		}

		return $realParameterTypes;
	}

	/**
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
				throw new ShouldNotHappenException();
			}
			$realParameterDefaultValues[$parameter->var->name] = $this->getType($parameter->default);
		}

		return $realParameterDefaultValues;
	}

	/**
	 * @api
	 * @param Type[] $phpDocParameterTypes
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
		?bool $isPure = null,
	): self
	{
		return $this->enterFunctionLike(
			new PhpFunctionFromParserNodeReflection(
				$function,
				$this->getFile(),
				$templateTypeMap,
				$this->getRealParameterTypes($function),
				array_map(static fn (Type $type): Type => TemplateTypeHelper::toArgument($type), $phpDocParameterTypes),
				$this->getRealParameterDefaultValues($function),
				$this->getFunctionType($function->returnType, $function->returnType === null, false),
				$phpDocReturnType !== null ? TemplateTypeHelper::toArgument($phpDocReturnType) : null,
				$throwType,
				$deprecatedDescription,
				$isDeprecated,
				$isInternal,
				$isFinal,
				$isPure,
			),
			false,
		);
	}

	private function enterFunctionLike(
		PhpFunctionFromParserNodeReflection $functionReflection,
		bool $preserveThis,
	): self
	{
		$variableTypes = [];
		$nativeExpressionTypes = [];
		foreach (ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getParameters() as $parameter) {
			$parameterType = $parameter->getType();
			if ($parameter->isVariadic()) {
				if ($this->phpVersion->supportsNamedArguments()) {
					$parameterType = new ArrayType(new UnionType([new IntegerType(), new StringType()]), $parameterType);
				} else {
					$parameterType = new ArrayType(new IntegerType(), $parameterType);
				}
			}
			$variableTypes[$parameter->getName()] = VariableTypeHolder::createYes($parameterType);

			$nativeParameterType = $parameter->getNativeType();
			if ($parameter->isVariadic()) {
				if ($this->phpVersion->supportsNamedArguments()) {
					$nativeParameterType = new ArrayType(new UnionType([new IntegerType(), new StringType()]), $nativeParameterType);
				} else {
					$nativeParameterType = new ArrayType(new IntegerType(), $nativeParameterType);
				}
			}
			$nativeExpressionTypes[sprintf('$%s', $parameter->getName())] = $nativeParameterType;
		}

		if ($preserveThis && array_key_exists('this', $this->variableTypes)) {
			$variableTypes['this'] = $this->variableTypes['this'];
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
			[],
			$nativeExpressionTypes,
		);
	}

	public function enterNamespace(string $namespaceName): self
	{
		return $this->scopeFactory->create(
			$this->context->beginFile(),
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			null,
			$namespaceName,
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
			$this->anonymousFunctionReflection,
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
			$this->anonymousFunctionReflection,
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
			$this->anonymousFunctionReflection,
		);
	}

	/** @api */
	public function isInClosureBind(): bool
	{
		return $this->inClosureBindScopeClass !== null;
	}

	/**
	 * @api
	 * @param ParameterReflection[]|null $callableParameters
	 */
	public function enterAnonymousFunction(
		Expr\Closure $closure,
		?array $callableParameters = null,
	): self
	{
		$anonymousFunctionReflection = $this->getType($closure);
		if (!$anonymousFunctionReflection instanceof ClosureType) {
			throw new ShouldNotHappenException();
		}

		$scope = $this->enterAnonymousFunctionWithoutReflection($closure, $callableParameters);

		return $this->scopeFactory->create(
			$scope->context,
			$scope->isDeclareStrictTypes(),
			$scope->constantTypes,
			$scope->getFunction(),
			$scope->getNamespace(),
			$scope->variableTypes,
			$scope->moreSpecificTypes,
			[],
			$scope->inClosureBindScopeClass,
			$anonymousFunctionReflection,
			true,
			[],
			[],
			$scope->nativeExpressionTypes,
			[],
			false,
			$this,
		);
	}

	/**
	 * @param ParameterReflection[]|null $callableParameters
	 */
	private function enterAnonymousFunctionWithoutReflection(
		Expr\Closure $closure,
		?array $callableParameters = null,
	): self
	{
		$variableTypes = [];
		foreach ($closure->params as $i => $parameter) {
			$isNullable = $this->isParameterValueNullable($parameter);
			$parameterType = $this->getFunctionType($parameter->type, $isNullable, $parameter->variadic);
			if ($callableParameters !== null) {
				if (isset($callableParameters[$i])) {
					$parameterType = TypehintHelper::decideType($parameterType, $callableParameters[$i]->getType());
				} elseif (count($callableParameters) > 0) {
					$lastParameter = $callableParameters[count($callableParameters) - 1];
					if ($lastParameter->isVariadic()) {
						$parameterType = TypehintHelper::decideType($parameterType, $lastParameter->getType());
					} else {
						$parameterType = TypehintHelper::decideType($parameterType, new MixedType());
					}
				} else {
					$parameterType = TypehintHelper::decideType($parameterType, new MixedType());
				}
			}

			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new ShouldNotHappenException();
			}
			$variableTypes[$parameter->var->name] = VariableTypeHolder::createYes(
				$parameterType,
			);
		}

		$nativeTypes = [];
		$moreSpecificTypes = [];
		foreach ($closure->uses as $use) {
			if (!is_string($use->var->name)) {
				throw new ShouldNotHappenException();
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
				$matches = Strings::matchAll((string) $exprString, '#^\$([a-zA-Z_\x7f-\xff][a-zA-Z_0-9\x7f-\xff]*)#');
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
			new TrivialParametersAcceptor(),
			true,
			[],
			[],
			$nativeTypes,
			[],
			false,
			$this,
		);
	}

	/**
	 * @api
	 * @param ParameterReflection[]|null $callableParameters
	 */
	public function enterArrowFunction(Expr\ArrowFunction $arrowFunction, ?array $callableParameters = null): self
	{
		$anonymousFunctionReflection = $this->getType($arrowFunction);
		if (!$anonymousFunctionReflection instanceof ClosureType) {
			throw new ShouldNotHappenException();
		}

		$scope = $this->enterArrowFunctionWithoutReflection($arrowFunction, $callableParameters);

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
			[],
			$scope->afterExtractCall,
			$scope->parentScope,
		);
	}

	/**
	 * @param ParameterReflection[]|null $callableParameters
	 */
	private function enterArrowFunctionWithoutReflection(Expr\ArrowFunction $arrowFunction, ?array $callableParameters): self
	{
		$variableTypes = $this->variableTypes;
		$mixed = new MixedType();
		$parameterVariables = [];
		$parameterVariableExpressions = [];
		foreach ($arrowFunction->params as $i => $parameter) {
			if ($parameter->type === null) {
				$parameterType = $mixed;
			} else {
				$isNullable = $this->isParameterValueNullable($parameter);
				$parameterType = $this->getFunctionType($parameter->type, $isNullable, $parameter->variadic);
			}

			if ($callableParameters !== null) {
				if (isset($callableParameters[$i])) {
					$parameterType = TypehintHelper::decideType($parameterType, $callableParameters[$i]->getType());
				} elseif (count($callableParameters) > 0) {
					$lastParameter = $callableParameters[count($callableParameters) - 1];
					if ($lastParameter->isVariadic()) {
						$parameterType = TypehintHelper::decideType($parameterType, $lastParameter->getType());
					} else {
						$parameterType = TypehintHelper::decideType($parameterType, new MixedType());
					}
				} else {
					$parameterType = TypehintHelper::decideType($parameterType, new MixedType());
				}
			}

			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new ShouldNotHappenException();
			}

			$variableTypes[$parameter->var->name] = VariableTypeHolder::createYes($parameterType);
			$parameterVariables[] = $parameter->var->name;
			$parameterVariableExpressions[] = $parameter->var;
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

		$scope = $this->scopeFactory->create(
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
			[],
			$this->afterExtractCall,
			$this->parentScope,
		);

		foreach ($parameterVariableExpressions as $expr) {
			$scope = $scope->invalidateExpression($expr);
		}

		return $scope;
	}

	public function isParameterValueNullable(Node\Param $parameter): bool
	{
		if ($parameter->default instanceof ConstFetch) {
			return strtolower((string) $parameter->default->name) === 'null';
		}

		return false;
	}

	/**
	 * @api
	 * @param Node\Name|Node\Identifier|Node\ComplexType|null $type
	 */
	public function getFunctionType($type, bool $isNullable, bool $isVariadic): Type
	{
		if ($isNullable) {
			return TypeCombinator::addNull(
				$this->getFunctionType($type, false, $isVariadic),
			);
		}
		if ($isVariadic) {
			if ($this->phpVersion->supportsNamedArguments()) {
				return new ArrayType(new UnionType([new IntegerType(), new StringType()]), $this->getFunctionType(
					$type,
					false,
					false,
				));
			}

			return new ArrayType(new IntegerType(), $this->getFunctionType(
				$type,
				false,
				false,
			));
		}

		if ($type instanceof Name) {
			$className = (string) $type;
			$lowercasedClassName = strtolower($className);
			if ($lowercasedClassName === 'parent') {
				if ($this->isInClass() && $this->getClassReflection()->getParentClass() !== null) {
					return new ObjectType($this->getClassReflection()->getParentClass()->getName());
				}

				return new NonexistentParentClassType();
			}
		}

		return ParserNodeTypeToPHPStanType::resolve($type, $this->isInClass() ? $this->getClassReflection() : null);
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
	 * @param Node\Name[] $classes
	 */
	public function enterCatch(array $classes, ?string $variableName): self
	{
		$type = TypeCombinator::union(...array_map(static fn (Node\Name $class): ObjectType => new ObjectType((string) $class), $classes));

		return $this->enterCatchType($type, $variableName);
	}

	public function enterCatchType(Type $catchType, ?string $variableName): self
	{
		if ($variableName === null) {
			return $this;
		}

		return $this->assignVariable(
			$variableName,
			TypeCombinator::intersect($catchType, new ObjectType(Throwable::class)),
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
			$this->currentlyAllowedUndefinedExpressions,
			$this->nativeExpressionTypes,
			[],
			$this->afterExtractCall,
			$this->parentScope,
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
			$this->currentlyAllowedUndefinedExpressions,
			$this->nativeExpressionTypes,
			[],
			$this->afterExtractCall,
			$this->parentScope,
		);
	}

	/** @api */
	public function isInExpressionAssign(Expr $expr): bool
	{
		$exprString = $this->getNodeKey($expr);
		return array_key_exists($exprString, $this->currentlyAssignedExpressions);
	}

	public function setAllowedUndefinedExpression(Expr $expr): self
	{
		$exprString = $this->getNodeKey($expr);
		$currentlyAllowedUndefinedExpressions = $this->currentlyAllowedUndefinedExpressions;
		$currentlyAllowedUndefinedExpressions[$exprString] = true;

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
			$currentlyAllowedUndefinedExpressions,
			$this->nativeExpressionTypes,
			[],
			$this->afterExtractCall,
			$this->parentScope,
		);
	}

	public function unsetAllowedUndefinedExpression(Expr $expr): self
	{
		$exprString = $this->getNodeKey($expr);
		$currentlyAllowedUndefinedExpressions = $this->currentlyAllowedUndefinedExpressions;
		unset($currentlyAllowedUndefinedExpressions[$exprString]);

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
			$currentlyAllowedUndefinedExpressions,
			$this->nativeExpressionTypes,
			[],
			$this->afterExtractCall,
			$this->parentScope,
		);
	}

	/** @api */
	public function isUndefinedExpressionAllowed(Expr $expr): bool
	{
		$exprString = $this->getNodeKey($expr);
		return array_key_exists($exprString, $this->currentlyAllowedUndefinedExpressions);
	}

	public function assignVariable(string $variableName, Type $type, ?TrinaryLogic $certainty = null): self
	{
		if ($certainty === null) {
			$certainty = TrinaryLogic::createYes();
		} elseif ($certainty->no()) {
			throw new ShouldNotHappenException();
		}
		$variableTypes = $this->getVariableTypes();
		$variableTypes[$variableName] = new VariableTypeHolder($type, $certainty);

		$nativeTypes = $this->nativeExpressionTypes;
		$nativeTypes[sprintf('$%s', $variableName)] = $type;

		$variableString = $this->printer->prettyPrintExpr(new Variable($variableName));
		$moreSpecificTypeHolders = $this->moreSpecificTypes;
		foreach (array_keys($moreSpecificTypeHolders) as $key) {
			$matches = Strings::matchAll((string) $key, '#\$[a-zA-Z_\x7f-\xff][a-zA-Z_0-9\x7f-\xff]*#');

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
			$this->currentlyAllowedUndefinedExpressions,
			$nativeTypes,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
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
				[],
				$nativeTypes,
				[],
				$this->afterExtractCall,
				$this->parentScope,
			);
		} elseif ($expr instanceof Expr\ArrayDimFetch && $expr->dim !== null) {
			return $this->specifyExpressionType(
				$expr->var,
				$this->getType($expr->var)->unsetOffset($this->getType($expr->dim)),
			)->invalidateExpression(
				new FuncCall(new FullyQualified('count'), [new Arg($expr->var)]),
			)->invalidateExpression(
				new FuncCall(new FullyQualified('sizeof'), [new Arg($expr->var)]),
			);
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
				$this->currentlyAllowedUndefinedExpressions,
				$this->nativeExpressionTypes,
				$this->inFunctionCallsStack,
				$this->afterExtractCall,
				$this->parentScope,
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
				$this->currentlyAllowedUndefinedExpressions,
				$nativeTypes,
				$this->inFunctionCallsStack,
				$this->afterExtractCall,
				$this->parentScope,
			);
		} elseif ($expr instanceof Expr\ArrayDimFetch && $expr->dim !== null) {
			$constantArrays = TypeUtils::getOldConstantArrays($this->getType($expr->var));
			if (count($constantArrays) > 0) {
				$setArrays = [];
				$dimType = $this->getType($expr->dim);
				foreach ($constantArrays as $constantArray) {
					$setArrays[] = $constantArray->setOffsetValueType($dimType, $type);
				}
				$scope = $this->specifyExpressionType(
					$expr->var,
					TypeCombinator::union(...$setArrays),
				);
			}
		}

		if ($expr instanceof FuncCall && $expr->name instanceof Name && $type instanceof ConstantBooleanType && !$type->getValue()) {
			$functionName = $this->reflectionProvider->resolveFunctionName($expr->name, $this);
			if ($functionName !== null && in_array(strtolower($functionName), [
				'is_dir',
				'is_file',
				'file_exists',
			], true)) {
				return $this;
			}
		}

		return $scope->addMoreSpecificTypes([
			$exprString => $type,
		]);
	}

	public function assignExpression(Expr $expr, Type $type): self
	{
		$scope = $this;
		if ($expr instanceof PropertyFetch) {
			$scope = $this->invalidateExpression($expr)
				->invalidateMethodsOnExpression($expr->var);
		} elseif ($expr instanceof Expr\StaticPropertyFetch) {
			$scope = $this->invalidateExpression($expr);
		}

		return $scope->specifyExpressionType($expr, $type);
	}

	public function invalidateExpression(Expr $expressionToInvalidate, bool $requireMoreCharacters = false): self
	{
		$exprStringToInvalidate = $this->getNodeKey($expressionToInvalidate);
		$expressionToInvalidateClass = get_class($expressionToInvalidate);
		$moreSpecificTypeHolders = $this->moreSpecificTypes;
		$nativeExpressionTypes = $this->nativeExpressionTypes;
		$invalidated = false;
		$nodeFinder = new NodeFinder();
		foreach (array_keys($moreSpecificTypeHolders) as $exprString) {
			$exprString = (string) $exprString;

			try {
				$expr = $this->parser->parseString('<?php ' . $exprString . ';')[0];
			} catch (ParserErrorsException) {
				continue;
			}
			if (!$expr instanceof Node\Stmt\Expression) {
				throw new ShouldNotHappenException();
			}

			$exprExpr = $expr->expr;
			if ($exprExpr instanceof PropertyFetch) {
				$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($exprExpr, $this);
				if ($propertyReflection !== null) {
					$nativePropertyReflection = $propertyReflection->getNativeReflection();
					if ($nativePropertyReflection !== null && $nativePropertyReflection->isReadOnly()) {
						continue;
					}
				}
			}

			$found = $nodeFinder->findFirst([$exprExpr], function (Node $node) use ($expressionToInvalidateClass, $exprStringToInvalidate): bool {
				if (!$node instanceof $expressionToInvalidateClass) {
					return false;
				}

				return $this->getNodeKey($node) === $exprStringToInvalidate;
			});
			if ($found === null) {
				continue;
			}

			if ($requireMoreCharacters && $exprString === $exprStringToInvalidate) {
				continue;
			}

			unset($moreSpecificTypeHolders[$exprString]);
			unset($nativeExpressionTypes[$exprString]);
			$invalidated = true;
		}

		if (!$invalidated) {
			return $this;
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
			$this->currentlyAllowedUndefinedExpressions,
			$nativeExpressionTypes,
			[],
			$this->afterExtractCall,
			$this->parentScope,
		);
	}

	public function invalidateMethodsOnExpression(Expr $expressionToInvalidate): self
	{
		$exprStringToInvalidate = $this->getNodeKey($expressionToInvalidate);
		$moreSpecificTypeHolders = $this->moreSpecificTypes;
		$nativeExpressionTypes = $this->nativeExpressionTypes;
		$invalidated = false;
		$nodeFinder = new NodeFinder();
		foreach (array_keys($moreSpecificTypeHolders) as $exprString) {
			$exprString = (string) $exprString;

			try {
				$expr = $this->parser->parseString('<?php ' . $exprString . ';')[0];
			} catch (ParserErrorsException) {
				continue;
			}
			if (!$expr instanceof Node\Stmt\Expression) {
				throw new ShouldNotHappenException();
			}
			$found = $nodeFinder->findFirst([$expr->expr], function (Node $node) use ($exprStringToInvalidate): bool {
				if (!$node instanceof MethodCall) {
					return false;
				}

				return $this->getNodeKey($node->var) === $exprStringToInvalidate;
			});
			if ($found === null) {
				continue;
			}

			unset($moreSpecificTypeHolders[$exprString]);
			unset($nativeExpressionTypes[$exprString]);
			$invalidated = true;
		}

		if (!$invalidated) {
			return $this;
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
			$this->currentlyAllowedUndefinedExpressions,
			$nativeExpressionTypes,
			[],
			$this->afterExtractCall,
			$this->parentScope,
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
			$typeAfterRemove,
		);
		if ($expr instanceof Variable && is_string($expr->name)) {
			$scope->nativeExpressionTypes[sprintf('$%s', $expr->name)] = TypeCombinator::remove($this->getNativeType($expr), $typeToRemove);
		}

		return $scope;
	}

	/**
	 * @api
	 * @return MutatingScope
	 */
	public function filterByTruthyValue(Expr $expr): Scope
	{
		$exprString = $this->getNodeKey($expr);
		if (array_key_exists($exprString, $this->truthyScopes)) {
			return $this->truthyScopes[$exprString];
		}

		$specifiedTypes = $this->typeSpecifier->specifyTypesInCondition($this, $expr, TypeSpecifierContext::createTruthy());
		$scope = $this->filterBySpecifiedTypes($specifiedTypes);
		$this->truthyScopes[$exprString] = $scope;

		return $scope;
	}

	/**
	 * @api
	 * @return MutatingScope
	 */
	public function filterByFalseyValue(Expr $expr): Scope
	{
		$exprString = $this->getNodeKey($expr);
		if (array_key_exists($exprString, $this->falseyScopes)) {
			return $this->falseyScopes[$exprString];
		}

		$specifiedTypes = $this->typeSpecifier->specifyTypesInCondition($this, $expr, TypeSpecifierContext::createFalsey());
		$scope = $this->filterBySpecifiedTypes($specifiedTypes);
		$this->falseyScopes[$exprString] = $scope;

		return $scope;
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
			// @phpstan-ignore-next-line
			$length = strlen((string) $a['exprString']) - strlen((string) $b['exprString']);
			if ($length !== 0) {
				return $length;
			}

			return $b['sure'] - $a['sure']; // @phpstan-ignore-line
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
				// @phpstan-ignore-next-line
				$match = Strings::match((string) $typeSpecification['exprString'], '#^\$([a-zA-Z_\x7f-\xff][a-zA-Z_0-9\x7f-\xff]*)#');
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

		$newConditionalExpressions = $specifiedTypes->getNewConditionalExpressionHolders();
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

		return $scope->changeConditionalExpressions($newConditionalExpressions);
	}

	/**
	 * @param array<string, ConditionalExpressionHolder[]> $newConditionalExpressionHolders
	 */
	public function changeConditionalExpressions(array $newConditionalExpressionHolders): self
	{
		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->constantTypes,
			$this->getFunction(),
			$this->getNamespace(),
			$this->variableTypes,
			$this->moreSpecificTypes,
			$newConditionalExpressionHolders,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$this->nativeExpressionTypes,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
		);
	}

	/**
	 * @param ConditionalExpressionHolder[] $conditionalExpressionHolders
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
			$this->currentlyAllowedUndefinedExpressions,
			$this->nativeExpressionTypes,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
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
			$this->currentlyAllowedUndefinedExpressions,
			$this->nativeExpressionTypes,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
		);
	}

	/** @api */
	public function isInFirstLevelStatement(): bool
	{
		return $this->inFirstLevelStatement;
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod
	 * @param Type[] $types
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
			$this->currentlyAllowedUndefinedExpressions,
			$this->nativeExpressionTypes,
			[],
			$this->afterExtractCall,
			$this->parentScope,
		);
	}

	public function mergeWith(?self $otherScope): self
	{
		if ($otherScope === null) {
			return $this;
		}

		$variableHolderToType = static fn (VariableTypeHolder $holder): Type => $holder->getType();
		$typeToVariableHolder = static fn (Type $type): VariableTypeHolder => new VariableTypeHolder($type, TrinaryLogic::createYes());

		$filterVariableHolders = static fn (VariableTypeHolder $holder): bool => $holder->getCertainty()->yes();

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
			$mergedVariableHolders,
		);
		$conditionalExpressions = $this->createConditionalExpressions(
			$conditionalExpressions,
			$theirVariableTypes,
			$ourVariableTypes,
			$mergedVariableHolders,
		);
		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			array_map($variableHolderToType, array_filter($this->mergeVariableHolders(
				array_map($typeToVariableHolder, $this->constantTypes),
				array_map($typeToVariableHolder, $otherScope->constantTypes),
			), $filterVariableHolders)),
			$this->getFunction(),
			$this->getNamespace(),
			$mergedVariableHolders,
			$this->mergeVariableHolders($this->moreSpecificTypes, $otherScope->moreSpecificTypes),
			$conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			[],
			[],
			array_map($variableHolderToType, array_filter($this->mergeVariableHolders(
				array_map($typeToVariableHolder, $this->nativeExpressionTypes),
				array_map($typeToVariableHolder, $otherScope->nativeExpressionTypes),
			), $filterVariableHolders)),
			[],
			$this->afterExtractCall && $otherScope->afterExtractCall,
			$this->parentScope,
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
		array $mergedVariableHolders,
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
		$variableHolderToType = static fn (VariableTypeHolder $holder): Type => $holder->getType();
		$typeToVariableHolder = static fn (Type $type): VariableTypeHolder => new VariableTypeHolder($type, TrinaryLogic::createYes());
		$filterVariableHolders = static fn (VariableTypeHolder $holder): bool => $holder->getCertainty()->yes();

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			array_map($variableHolderToType, array_filter($this->processFinallyScopeVariableTypeHolders(
				array_map($typeToVariableHolder, $this->constantTypes),
				array_map($typeToVariableHolder, $finallyScope->constantTypes),
				array_map($typeToVariableHolder, $originalFinallyScope->constantTypes),
			), $filterVariableHolders)),
			$this->getFunction(),
			$this->getNamespace(),
			$this->processFinallyScopeVariableTypeHolders(
				$this->getVariableTypes(),
				$finallyScope->getVariableTypes(),
				$originalFinallyScope->getVariableTypes(),
			),
			$this->processFinallyScopeVariableTypeHolders(
				$this->moreSpecificTypes,
				$finallyScope->moreSpecificTypes,
				$originalFinallyScope->moreSpecificTypes,
			),
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			[],
			[],
			array_map($variableHolderToType, array_filter($this->processFinallyScopeVariableTypeHolders(
				array_map($typeToVariableHolder, $this->nativeExpressionTypes),
				array_map($typeToVariableHolder, $finallyScope->nativeExpressionTypes),
				array_map($typeToVariableHolder, $originalFinallyScope->nativeExpressionTypes),
			), $filterVariableHolders)),
			[],
			$this->afterExtractCall,
			$this->parentScope,
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
		array $originalVariableTypeHolders,
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
	 * @param Expr\ClosureUse[] $byRefUses
	 */
	public function processClosureScope(
		self $closureScope,
		?self $prevScope,
		array $byRefUses,
	): self
	{
		$nativeExpressionTypes = $this->nativeExpressionTypes;
		$variableTypes = $this->variableTypes;
		if (count($byRefUses) === 0) {
			return $this;
		}

		foreach ($byRefUses as $use) {
			if (!is_string($use->var->name)) {
				throw new ShouldNotHappenException();
			}

			$variableName = $use->var->name;

			if (!$closureScope->hasVariableType($variableName)->yes()) {
				$variableTypes[$variableName] = VariableTypeHolder::createYes(new NullType());
				$nativeExpressionTypes[sprintf('$%s', $variableName)] = new NullType();
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
			$nativeExpressionTypes[sprintf('$%s', $variableName)] = $variableType;
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
			[],
			$nativeExpressionTypes,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
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
				$variableTypeHolder->getCertainty()->and($variableTypeHolders[$name]->getCertainty()),
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
				$variableTypeHolder->getCertainty()->and($moreSpecificTypes[$exprString]->getCertainty()),
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
			[],
			$nativeTypes,
			[],
			$this->afterExtractCall,
			$this->parentScope,
		);
	}

	public function generalizeWith(self $otherScope): self
	{
		$variableTypeHolders = $this->generalizeVariableTypeHolders(
			$this->getVariableTypes(),
			$otherScope->getVariableTypes(),
		);

		$moreSpecificTypes = $this->generalizeVariableTypeHolders(
			$this->moreSpecificTypes,
			$otherScope->moreSpecificTypes,
		);

		$variableHolderToType = static fn (VariableTypeHolder $holder): Type => $holder->getType();
		$typeToVariableHolder = static fn (Type $type): VariableTypeHolder => new VariableTypeHolder($type, TrinaryLogic::createYes());
		$filterVariableHolders = static fn (VariableTypeHolder $holder): bool => $holder->getCertainty()->yes();
		$nativeTypes = array_map($variableHolderToType, array_filter($this->generalizeVariableTypeHolders(
			array_map($typeToVariableHolder, $this->nativeExpressionTypes),
			array_map($typeToVariableHolder, $otherScope->nativeExpressionTypes),
		), $filterVariableHolders));

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			array_map($variableHolderToType, array_filter($this->generalizeVariableTypeHolders(
				array_map($typeToVariableHolder, $this->constantTypes),
				array_map($typeToVariableHolder, $otherScope->constantTypes),
			), $filterVariableHolders)),
			$this->getFunction(),
			$this->getNamespace(),
			$variableTypeHolders,
			$moreSpecificTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClass,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			[],
			[],
			$nativeTypes,
			[],
			$this->afterExtractCall,
			$this->parentScope,
		);
	}

	/**
	 * @param VariableTypeHolder[] $variableTypeHolders
	 * @param VariableTypeHolder[] $otherVariableTypeHolders
	 * @return VariableTypeHolder[]
	 */
	private function generalizeVariableTypeHolders(
		array $variableTypeHolders,
		array $otherVariableTypeHolders,
	): array
	{
		foreach ($variableTypeHolders as $name => $variableTypeHolder) {
			if (!isset($otherVariableTypeHolders[$name])) {
				continue;
			}

			$variableTypeHolders[$name] = new VariableTypeHolder(
				self::generalizeType($variableTypeHolder->getType(), $otherVariableTypeHolders[$name]->getType()),
				$variableTypeHolder->getCertainty(),
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
		$integerRanges = ['a' => [], 'b' => []];
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
				if ($type instanceof IntegerRangeType) {
					$integerRanges[$key][] = $type;
					continue;
				}

				$otherTypes[] = $type;
			}
		}

		$resultTypes = [];
		foreach ([
			$constantFloats,
			$constantBooleans,
			$constantStrings,
		] as $constantTypes) {
			if (count($constantTypes['a']) === 0) {
				if (count($constantTypes['b']) > 0) {
					$resultTypes[] = TypeCombinator::union(...$constantTypes['b']);
				}
				continue;
			} elseif (count($constantTypes['b']) === 0) {
				$resultTypes[] = TypeCombinator::union(...$constantTypes['a']);
				continue;
			}

			$aTypes = TypeCombinator::union(...$constantTypes['a']);
			$bTypes = TypeCombinator::union(...$constantTypes['b']);
			if ($aTypes->equals($bTypes)) {
				$resultTypes[] = $aTypes;
				continue;
			}

			$resultTypes[] = TypeCombinator::union(...$constantTypes['a'], ...$constantTypes['b'])->generalize(GeneralizePrecision::moreSpecific());
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
								$constantArraysB->getOffsetValueType($keyType),
							),
						);
					}

					$resultTypes[] = $resultArrayBuilder->getArray();
				} else {
					$resultTypes[] = new ArrayType(
						TypeCombinator::union(self::generalizeType($constantArraysA->getIterableKeyType(), $constantArraysB->getIterableKeyType())),
						TypeCombinator::union(self::generalizeType($constantArraysA->getIterableValueType(), $constantArraysB->getIterableValueType())),
					);
				}
			}
		} elseif (count($constantArrays['b']) > 0) {
			$resultTypes[] = TypeCombinator::union(...$constantArrays['b']);
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
					TypeCombinator::union(self::generalizeType($aValueType, $bValueType)),
				);
			}
		} elseif (count($generalArrays['b']) > 0) {
			$resultTypes[] = TypeCombinator::union(...$generalArrays['b']);
		}

		if (count($constantIntegers['a']) > 0) {
			if (count($constantIntegers['b']) === 0) {
				$resultTypes[] = TypeCombinator::union(...$constantIntegers['a']);
			} else {
				$constantIntegersA = TypeCombinator::union(...$constantIntegers['a']);
				$constantIntegersB = TypeCombinator::union(...$constantIntegers['b']);

				if ($constantIntegersA->equals($constantIntegersB)) {
					$resultTypes[] = $constantIntegersA;
				} else {
					$min = null;
					$max = null;
					foreach ($constantIntegers['a'] as $int) {
						if ($min === null || $int->getValue() < $min) {
							$min = $int->getValue();
						}
						if ($max !== null && $int->getValue() <= $max) {
							continue;
						}

						$max = $int->getValue();
					}

					$gotGreater = false;
					$gotSmaller = false;
					foreach ($constantIntegers['b'] as $int) {
						if ($int->getValue() > $max) {
							$gotGreater = true;
						}
						if ($int->getValue() >= $min) {
							continue;
						}

						$gotSmaller = true;
					}

					if ($gotGreater && $gotSmaller) {
						$resultTypes[] = new IntegerType();
					} elseif ($gotGreater) {
						$resultTypes[] = IntegerRangeType::fromInterval($min, null);
					} elseif ($gotSmaller) {
						$resultTypes[] = IntegerRangeType::fromInterval(null, $max);
					} else {
						$resultTypes[] = TypeCombinator::union($constantIntegersA, $constantIntegersB);
					}
				}
			}
		} elseif (count($constantIntegers['b']) > 0) {
			$resultTypes[] = TypeCombinator::union(...$constantIntegers['b']);
		}

		if (count($integerRanges['a']) > 0) {
			if (count($integerRanges['b']) === 0) {
				$resultTypes[] = TypeCombinator::union(...$integerRanges['a']);
			} else {
				$integerRangesA = TypeCombinator::union(...$integerRanges['a']);
				$integerRangesB = TypeCombinator::union(...$integerRanges['b']);

				if ($integerRangesA->equals($integerRangesB)) {
					$resultTypes[] = $integerRangesA;
				} else {
					$min = null;
					$max = null;
					foreach ($integerRanges['a'] as $range) {
						if ($range->getMin() === null) {
							$rangeMin = PHP_INT_MIN;
						} else {
							$rangeMin = $range->getMin();
						}
						if ($range->getMax() === null) {
							$rangeMax = PHP_INT_MAX;
						} else {
							$rangeMax = $range->getMax();
						}

						if ($min === null || $rangeMin < $min) {
							$min = $rangeMin;
						}
						if ($max !== null && $rangeMax <= $max) {
							continue;
						}

						$max = $rangeMax;
					}

					$gotGreater = false;
					$gotSmaller = false;
					foreach ($integerRanges['b'] as $range) {
						if ($range->getMin() === null) {
							$rangeMin = PHP_INT_MIN;
						} else {
							$rangeMin = $range->getMin();
						}
						if ($range->getMax() === null) {
							$rangeMax = PHP_INT_MAX;
						} else {
							$rangeMax = $range->getMax();
						}

						if ($rangeMax > $max) {
							$gotGreater = true;
						}
						if ($rangeMin >= $min) {
							continue;
						}

						$gotSmaller = true;
					}

					if ($min === PHP_INT_MIN) {
						$min = null;
					}
					if ($max === PHP_INT_MAX) {
						$max = null;
					}

					if ($gotGreater && $gotSmaller) {
						$resultTypes[] = new IntegerType();
					} elseif ($gotGreater) {
						$resultTypes[] = IntegerRangeType::fromInterval($min, null);
					} elseif ($gotSmaller) {
						$resultTypes[] = IntegerRangeType::fromInterval(null, $max);
					} else {
						$resultTypes[] = TypeCombinator::union($integerRangesA, $integerRangesB);
					}
				}
			}
		} elseif (count($integerRanges['b']) > 0) {
			$resultTypes[] = TypeCombinator::union(...$integerRanges['b']);
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

		$typeToVariableHolder = static fn (Type $type): VariableTypeHolder => new VariableTypeHolder($type, TrinaryLogic::createYes());

		$nativeExpressionTypesResult = $this->compareVariableTypeHolders(
			array_map($typeToVariableHolder, $this->nativeExpressionTypes),
			array_map($typeToVariableHolder, $otherScope->nativeExpressionTypes),
		);

		if (!$nativeExpressionTypesResult) {
			return false;
		}

		return $this->compareVariableTypeHolders(
			array_map($typeToVariableHolder, $this->constantTypes),
			array_map($typeToVariableHolder, $otherScope->constantTypes),
		);
	}

	/**
	 * @param VariableTypeHolder[] $variableTypeHolders
	 * @param VariableTypeHolder[] $otherVariableTypeHolders
	 */
	private function compareVariableTypeHolders(array $variableTypeHolders, array $otherVariableTypeHolders): bool
	{
		if (count($variableTypeHolders) !== count($otherVariableTypeHolders)) {
			return false;
		}
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

		return true;
	}

	/** @api */
	public function canAccessProperty(PropertyReflection $propertyReflection): bool
	{
		return $this->canAccessClassMember($propertyReflection);
	}

	/** @api */
	public function canCallMethod(MethodReflection $methodReflection): bool
	{
		if ($this->canAccessClassMember($methodReflection)) {
			return true;
		}

		return $this->canAccessClassMember($methodReflection->getPrototype());
	}

	/** @api */
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
				$typeHolder->getCertainty()->describe(),
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
			$node->getArgs(),
		);

		foreach ($this->dynamicReturnTypeExtensionRegistry->getDynamicStaticMethodReturnTypeExtensionsForClass($classReflection->getName()) as $dynamicStaticMethodReturnTypeExtension) {
			if (!$dynamicStaticMethodReturnTypeExtension->isStaticMethodSupported($constructorMethod)) {
				continue;
			}

			$resolvedType = $dynamicStaticMethodReturnTypeExtension->getTypeFromStaticMethodCall(
				$constructorMethod,
				$methodCall,
				$this,
			);
			if ($resolvedType === null) {
				continue;
			}

			$resolvedTypes[] = $resolvedType;
		}

		if (count($resolvedTypes) > 0) {
			return TypeCombinator::union(...$resolvedTypes);
		}

		$methodResult = $this->getType($methodCall);
		if ($methodResult instanceof NeverType && $methodResult->isExplicit()) {
			return $methodResult;
		}

		$objectType = new ObjectType($resolvedClassName);
		if (!$classReflection->isGeneric()) {
			return $objectType;
		}

		$assignedToProperty = $node->getAttribute('assignedToProperty');
		if ($assignedToProperty !== null) {
			$constructorVariant = ParametersAcceptorSelector::selectSingle($constructorMethod->getVariants());
			$classTemplateTypes = $classReflection->getTemplateTypeMap()->getTypes();
			$originalClassTemplateTypes = $classTemplateTypes;
			foreach ($constructorVariant->getParameters() as $parameter) {
				TypeTraverser::map($parameter->getType(), static function (Type $type, callable $traverse) use (&$classTemplateTypes): Type {
					if ($type instanceof TemplateType && array_key_exists($type->getName(), $classTemplateTypes)) {
						$classTemplateType = $classTemplateTypes[$type->getName()];
						if ($classTemplateType instanceof TemplateType && $classTemplateType->getScope()->equals($type->getScope())) {
							unset($classTemplateTypes[$type->getName()]);
						}
						return $type;
					}

					return $traverse($type);
				});
			}

			if (count($classTemplateTypes) === count($originalClassTemplateTypes)) {
				$propertyType = $this->getType($assignedToProperty);
				if ($objectType->isSuperTypeOf($propertyType)->yes()) {
					return $propertyType;
				}
			}
		}

		if ($constructorMethod instanceof DummyConstructorReflection || $constructorMethod->getDeclaringClass()->getName() !== $classReflection->getName()) {
			return new GenericObjectType(
				$resolvedClassName,
				$classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds()),
			);
		}

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$this,
			$methodCall->getArgs(),
			$constructorMethod->getVariants(),
		);

		if ($this->explicitMixedInUnknownGenericNew) {
			return new GenericObjectType(
				$resolvedClassName,
				$classReflection->typeMapToList($parametersAcceptor->getResolvedTemplateTypeMap()),
			);
		}

		$resolvedPhpDoc = $classReflection->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			return $objectType;
		}

		$list = [];
		$typeMap = $parametersAcceptor->getResolvedTemplateTypeMap();
		foreach ($resolvedPhpDoc->getTemplateTags() as $tag) {
			$templateType = $typeMap->getType($tag->getName());
			if ($templateType !== null) {
				$list[] = $templateType;
				continue;
			}
			$bound = $tag->getBound();
			if ($bound instanceof MixedType && $bound->isExplicitMixed()) {
				$bound = new MixedType(false);
			}
			$list[] = $bound;
		}

		return new GenericObjectType(
			$resolvedClassName,
			$list,
		);
	}

	private function getTypeToInstantiateForNew(Type $type): Type
	{
		$decideType = static function (Type $type): ?Type {
			if ($type instanceof ConstantStringType) {
				return new ObjectType($type->getValue());
			}
			if ($type instanceof GenericClassStringType) {
				return $type->getGenericType();
			}
			if ((new ObjectWithoutClassType())->isSuperTypeOf($type)->yes()) {
				return $type;
			}
			return null;
		};

		if ($type instanceof UnionType) {
			$types = [];
			foreach ($type->getTypes() as $innerType) {
				$decidedType = $decideType($innerType);
				if ($decidedType === null) {
					return new ObjectWithoutClassType();
				}

				$types[] = $decidedType;
			}

			return TypeCombinator::union(...$types);
		}

		$decidedType = $decideType($type);
		if ($decidedType === null) {
			return new ObjectWithoutClassType();
		}

		return $decidedType;
	}

	/** @api */
	public function getMethodReflection(Type $typeWithMethod, string $methodName): ?MethodReflection
	{
		if ($typeWithMethod instanceof UnionType) {
			$newTypes = [];
			foreach ($typeWithMethod->getTypes() as $innerType) {
				if (!$innerType->hasMethod($methodName)->yes()) {
					continue;
				}

				$newTypes[] = $innerType;
			}
			if (count($newTypes) === 0) {
				return null;
			}
			$typeWithMethod = TypeCombinator::union(...$newTypes);
		}

		if (!$typeWithMethod->hasMethod($methodName)->yes()) {
			return null;
		}

		return $typeWithMethod->getMethod($methodName, $this);
	}

	/**
	 * @param MethodCall|Node\Expr\StaticCall $methodCall
	 */
	private function methodCallReturnType(Type $typeWithMethod, string $methodName, Expr $methodCall): ?Type
	{
		$methodReflection = $this->getMethodReflection($typeWithMethod, $methodName);
		if ($methodReflection === null) {
			return null;
		}

		$resolvedTypes = [];
		foreach (TypeUtils::getDirectClassNames($typeWithMethod) as $className) {
			if ($methodCall instanceof MethodCall) {
				foreach ($this->dynamicReturnTypeExtensionRegistry->getDynamicMethodReturnTypeExtensionsForClass($className) as $dynamicMethodReturnTypeExtension) {
					if (!$dynamicMethodReturnTypeExtension->isMethodSupported($methodReflection)) {
						continue;
					}

					$resolvedType = $dynamicMethodReturnTypeExtension->getTypeFromMethodCall($methodReflection, $methodCall, $this);
					if ($resolvedType === null) {
						continue;
					}

					$resolvedTypes[] = $resolvedType;
				}
			} else {
				foreach ($this->dynamicReturnTypeExtensionRegistry->getDynamicStaticMethodReturnTypeExtensionsForClass($className) as $dynamicStaticMethodReturnTypeExtension) {
					if (!$dynamicStaticMethodReturnTypeExtension->isStaticMethodSupported($methodReflection)) {
						continue;
					}

					$resolvedType = $dynamicStaticMethodReturnTypeExtension->getTypeFromStaticMethodCall(
						$methodReflection,
						$methodCall,
						$this,
					);
					if ($resolvedType === null) {
						continue;
					}

					$resolvedTypes[] = $resolvedType;
				}
			}
		}

		if (count($resolvedTypes) > 0) {
			return TypeCombinator::union(...$resolvedTypes);
		}

		return ParametersAcceptorSelector::selectFromArgs(
			$this,
			$methodCall->getArgs(),
			$methodReflection->getVariants(),
		)->getReturnType();
	}

	/** @api */
	public function getPropertyReflection(Type $typeWithProperty, string $propertyName): ?PropertyReflection
	{
		if ($typeWithProperty instanceof UnionType) {
			$newTypes = [];
			foreach ($typeWithProperty->getTypes() as $innerType) {
				if (!$innerType->hasProperty($propertyName)->yes()) {
					continue;
				}

				$newTypes[] = $innerType;
			}
			if (count($newTypes) === 0) {
				return null;
			}
			$typeWithProperty = TypeCombinator::union(...$newTypes);
		}
		if (!$typeWithProperty->hasProperty($propertyName)->yes()) {
			return null;
		}

		return $typeWithProperty->getProperty($propertyName, $this);
	}

	/**
	 * @param PropertyFetch|Node\Expr\StaticPropertyFetch $propertyFetch
	 */
	private function propertyFetchType(Type $fetchedOnType, string $propertyName, Expr $propertyFetch): ?Type
	{
		$propertyReflection = $this->getPropertyReflection($fetchedOnType, $propertyName);
		if ($propertyReflection === null) {
			return null;
		}

		if ($this->isInExpressionAssign($propertyFetch)) {
			return $propertyReflection->getWritableType();
		}

		return $propertyReflection->getReadableType();
	}

	/**
	 * @param ConstantIntegerType|IntegerRangeType $range
	 * @param Node\Expr\AssignOp\Div|Node\Expr\AssignOp\Minus|Node\Expr\AssignOp\Mul|Node\Expr\AssignOp\Plus|Node\Expr\BinaryOp\Div|Node\Expr\BinaryOp\Minus|Node\Expr\BinaryOp\Mul|Node\Expr\BinaryOp\Plus $node
	 * @param IntegerRangeType|ConstantIntegerType|UnionType $operand
	 */
	private function integerRangeMath(Type $range, Expr $node, Type $operand): Type
	{
		if ($range instanceof IntegerRangeType) {
			$rangeMin = $range->getMin();
			$rangeMax = $range->getMax();
		} else {
			$rangeMin = $range->getValue();
			$rangeMax = $rangeMin;
		}

		if ($operand instanceof UnionType) {

			$unionParts = [];

			foreach ($operand->getTypes() as $type) {
				if ($type instanceof IntegerRangeType || $type instanceof ConstantIntegerType) {
					$unionParts[] = $this->integerRangeMath($range, $node, $type);
				} else {
					$unionParts[] = $type->toNumber();
				}
			}

			$union = TypeCombinator::union(...$unionParts);
			if ($operand instanceof BenevolentUnionType) {
				return TypeUtils::toBenevolentUnion($union)->toNumber();
			}

			return $union->toNumber();
		}

		if ($node instanceof Node\Expr\BinaryOp\Plus || $node instanceof Node\Expr\AssignOp\Plus) {
			if ($operand instanceof ConstantIntegerType) {
				/** @var int|float|null $min */
				$min = $rangeMin !== null ? $rangeMin + $operand->getValue() : null;

				/** @var int|float|null $max */
				$max = $rangeMax !== null ? $rangeMax + $operand->getValue() : null;
			} else {
				/** @var int|float|null $min */
				$min = $rangeMin !== null && $operand->getMin() !== null ? $rangeMin + $operand->getMin() : null;

				/** @var int|float|null $max */
				$max = $rangeMax !== null && $operand->getMax() !== null ? $rangeMax + $operand->getMax() : null;
			}
		} elseif ($node instanceof Node\Expr\BinaryOp\Minus || $node instanceof Node\Expr\AssignOp\Minus) {
			if ($operand instanceof ConstantIntegerType) {
				/** @var int|float|null $min */
				$min = $rangeMin !== null ? $rangeMin - $operand->getValue() : null;

				/** @var int|float|null $max */
				$max = $rangeMax !== null ? $rangeMax - $operand->getValue() : null;
			} else {
				if ($rangeMin === $rangeMax && $rangeMin !== null
					&& ($operand->getMin() === null || $operand->getMax() === null)) {
					$min = null;
					$max = $rangeMin;
				} else {
					if ($operand->getMin() === null) {
						$min = null;
					} elseif ($rangeMin !== null) {
						if ($operand->getMax() !== null) {
							/** @var int|float $min */
							$min = $rangeMin - $operand->getMax();
						} else {
							/** @var int|float $min */
							$min = $rangeMin - $operand->getMin();
						}
					} else {
						$min = null;
					}

					if ($operand->getMax() === null) {
						$min = null;
						$max = null;
					} elseif ($rangeMax !== null) {
						if ($rangeMin !== null && $operand->getMin() === null) {
							/** @var int|float $min */
							$min = $rangeMin - $operand->getMax();
							$max = null;
						} elseif ($operand->getMin() !== null) {
							/** @var int|float $max */
							$max = $rangeMax - $operand->getMin();
						} else {
							$max = null;
						}
					} else {
						$max = null;
					}

					if ($min !== null && $max !== null && $min > $max) {
						[$min, $max] = [$max, $min];
					}
				}
			}
		} elseif ($node instanceof Node\Expr\BinaryOp\Mul || $node instanceof Node\Expr\AssignOp\Mul) {
			if ($operand instanceof ConstantIntegerType) {
				/** @var int|float|null $min */
				$min = $rangeMin !== null ? $rangeMin * $operand->getValue() : null;

				/** @var int|float|null $max */
				$max = $rangeMax !== null ? $rangeMax * $operand->getValue() : null;
			} else {
				/** @var int|float|null $min */
				$min = $rangeMin !== null && $operand->getMin() !== null ? $rangeMin * $operand->getMin() : null;

				/** @var int|float|null $max */
				$max = $rangeMax !== null && $operand->getMax() !== null ? $rangeMax * $operand->getMax() : null;
			}

			if ($min !== null && $max !== null && $min > $max) {
				[$min, $max] = [$max, $min];
			}

			// invert maximas on multiplication with negative constants
			if ((($range instanceof ConstantIntegerType && $range->getValue() < 0)
				|| ($operand instanceof ConstantIntegerType && $operand->getValue() < 0))
				&& ($min === null || $max === null)) {
				[$min, $max] = [$max, $min];
			}

		} else {
			if ($operand instanceof ConstantIntegerType) {
				$min = $rangeMin !== null && $operand->getValue() !== 0 ? $rangeMin / $operand->getValue() : null;
				$max = $rangeMax !== null && $operand->getValue() !== 0 ? $rangeMax / $operand->getValue() : null;
			} else {
				$min = $rangeMin !== null && $operand->getMin() !== null && $operand->getMin() !== 0 ? $rangeMin / $operand->getMin() : null;
				$max = $rangeMax !== null && $operand->getMax() !== null && $operand->getMax() !== 0 ? $rangeMax / $operand->getMax() : null;
			}

			if ($range instanceof IntegerRangeType && $operand instanceof IntegerRangeType) {
				if ($rangeMax === null && $operand->getMax() === null) {
					$min = 0;
				} elseif ($rangeMin === null && $operand->getMin() === null) {
					$min = null;
					$max = null;
				}
			}

			if ($operand instanceof IntegerRangeType
				&& ($operand->getMin() === null || $operand->getMax() === null)
				|| ($rangeMin === null || $rangeMax === null)
				|| is_float($min) || is_float($max)
			) {
				if (is_float($min)) {
					$min = (int) $min;
				}
				if (is_float($max)) {
					$max = (int) $max;
				}

				if ($min !== null && $max !== null && $min > $max) {
					[$min, $max] = [$max, $min];
				}

				// invert maximas on division with negative constants
				if ((($range instanceof ConstantIntegerType && $range->getValue() < 0)
						|| ($operand instanceof ConstantIntegerType && $operand->getValue() < 0))
					&& ($min === null || $max === null)) {
					[$min, $max] = [$max, $min];
				}

				if ($min === null && $max === null) {
					return new BenevolentUnionType([new IntegerType(), new FloatType()]);
				}

				return TypeCombinator::union(IntegerRangeType::fromInterval($min, $max), new FloatType());
			}
		}

		if (is_float($min)) {
			$min = null;
		}
		if (is_float($max)) {
			$max = null;
		}

		return IntegerRangeType::fromInterval($min, $max);
	}

	private function resolveLateResolvableTypes(Type $type): Type
	{
		return TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
			$type = $traverse($type);

			if ($type instanceof LateResolvableType) {
				$type = $type->resolve();
			}

			return $type;
		});
	}

}
