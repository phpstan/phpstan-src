<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use ArrayAccess;
use Closure;
use Generator;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\Cast\Unset_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\PropertyHook;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Expr\ExistingArrayDimFetch;
use PHPStan\Node\Expr\GetIterableKeyTypeExpr;
use PHPStan\Node\Expr\GetIterableValueTypeExpr;
use PHPStan\Node\Expr\GetOffsetValueTypeExpr;
use PHPStan\Node\Expr\IntertwinedVariableByReferenceWithExpr;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\Node\Expr\OriginalForeachKeyExpr;
use PHPStan\Node\Expr\OriginalPropertyTypeExpr;
use PHPStan\Node\Expr\ParameterVariableOriginalValueExpr;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Node\Expr\SetExistingOffsetValueTypeExpr;
use PHPStan\Node\Expr\SetOffsetValueTypeExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Expr\UnsetOffsetExpr;
use PHPStan\Node\InvalidateExprNode;
use PHPStan\Node\IssetExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Parser\ArrayMapArgVisitor;
use PHPStan\Parser\ImmediatelyInvokedClosureVisitor;
use PHPStan\Parser\NewAssignedToPropertyVisitor;
use PHPStan\Parser\Parser;
use PHPStan\Php\PhpVersion;
use PHPStan\Php\PhpVersionFactory;
use PHPStan\Php\PhpVersions;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\Callables\SimpleThrowPoint;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyConstructorReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\DynamicReturnTypeExtensionRegistry;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ExpressionTypeResolverExtensionRegistry;
use PHPStan\Type\FloatType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\GenericStaticType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use Throwable;
use ValueError;
use function abs;
use function array_filter;
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_slice;
use function array_values;
use function count;
use function explode;
use function get_class;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function ltrim;
use function md5;
use function sprintf;
use function str_decrement;
use function str_increment;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function uksort;
use function usort;
use const PHP_INT_MAX;
use const PHP_INT_MIN;
use const PHP_VERSION_ID;

class MutatingScope implements Scope, NodeCallbackInvoker
{

	private const BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH = 4;

	private const KEEP_VOID_ATTRIBUTE_NAME = 'keepVoid';

	/** @var Type[] */
	private array $resolvedTypes = [];

	/** @var array<string, self> */
	private array $truthyScopes = [];

	/** @var array<string, self> */
	private array $falseyScopes = [];

	/** @var non-empty-string|null */
	private ?string $namespace;

	private ?self $scopeOutOfFirstLevelStatement = null;

	private ?self $scopeWithPromotedNativeTypes = null;

	private static int $resolveClosureTypeDepth = 0;

	/**
	 * @param int|array{min: int, max: int}|null $configPhpVersion
	 * @param callable(Node $node, Scope $scope): void|null $nodeCallback
	 * @param array<string, ExpressionTypeHolder> $expressionTypes
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @param list<string> $inClosureBindScopeClasses
	 * @param array<string, true> $currentlyAssignedExpressions
	 * @param array<string, true> $currentlyAllowedUndefinedExpressions
	 * @param array<string, ExpressionTypeHolder> $nativeExpressionTypes
	 * @param list<array{MethodReflection|FunctionReflection|null, ParameterReflection|null}> $inFunctionCallsStack
	 */
	public function __construct(
		protected InternalScopeFactory $scopeFactory,
		private ReflectionProvider $reflectionProvider,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private DynamicReturnTypeExtensionRegistry $dynamicReturnTypeExtensionRegistry,
		private ExpressionTypeResolverExtensionRegistry $expressionTypeResolverExtensionRegistry,
		private ExprPrinter $exprPrinter,
		private TypeSpecifier $typeSpecifier,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private Parser $parser,
		private NodeScopeResolver $nodeScopeResolver,
		private RicherScopeGetTypeHelper $richerScopeGetTypeHelper,
		private ConstantResolver $constantResolver,
		protected ScopeContext $context,
		private PhpVersion $phpVersion,
		private AttributeReflectionFactory $attributeReflectionFactory,
		private int|array|null $configPhpVersion,
		private $nodeCallback = null,
		private bool $declareStrictTypes = false,
		private PhpFunctionFromParserNodeReflection|null $function = null,
		?string $namespace = null,
		protected array $expressionTypes = [],
		protected array $nativeExpressionTypes = [],
		protected array $conditionalExpressions = [],
		protected array $inClosureBindScopeClasses = [],
		private ?ClosureType $anonymousFunctionReflection = null,
		private bool $inFirstLevelStatement = true,
		protected array $currentlyAssignedExpressions = [],
		protected array $currentlyAllowedUndefinedExpressions = [],
		protected array $inFunctionCallsStack = [],
		protected bool $afterExtractCall = false,
		private ?Scope $parentScope = null,
		protected bool $nativeTypesPromoted = false,
	)
	{
		if ($namespace === '') {
			$namespace = null;
		}

		$this->namespace = $namespace;
	}

	public function toFiberScope(): self
	{
		if (PHP_VERSION_ID < 80100) {
			throw new ShouldNotHappenException('Cannot create FiberScope below PHP 8.1');
		}

		return $this->scopeFactory->toFiberFactory()->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$this->expressionTypes,
			$this->nativeExpressionTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	public function toMutatingScope(): self
	{
		return $this;
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
			null,
			null,
			$this->expressionTypes,
			$this->nativeExpressionTypes,
		);
	}

	/**
	 * @param array<string, ExpressionTypeHolder> $currentExpressionTypes
	 * @return array<string, ExpressionTypeHolder>
	 */
	private function rememberConstructorExpressions(array $currentExpressionTypes): array
	{
		$expressionTypes = [];
		foreach ($currentExpressionTypes as $exprString => $expressionTypeHolder) {
			$expr = $expressionTypeHolder->getExpr();
			if ($expr instanceof FuncCall) {
				if (
					!$expr->name instanceof Name
					|| !in_array($expr->name->name, ['class_exists', 'function_exists'], true)
				) {
					continue;
				}
			} elseif ($expr instanceof PropertyFetch) {
				if (!$this->isReadonlyPropertyFetch($expr, true)) {
					continue;
				}
			} elseif (!$expr instanceof ConstFetch && !$expr instanceof PropertyInitializationExpr) {
				continue;
			}

			$expressionTypes[$exprString] = $expressionTypeHolder;
		}

		if (array_key_exists('$this', $currentExpressionTypes)) {
			$expressionTypes['$this'] = $currentExpressionTypes['$this'];
		}

		return $expressionTypes;
	}

	public function rememberConstructorScope(): self
	{
		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			null,
			$this->getNamespace(),
			$this->rememberConstructorExpressions($this->expressionTypes),
			$this->rememberConstructorExpressions($this->nativeExpressionTypes),
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			[],
			[],
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	private function isReadonlyPropertyFetch(PropertyFetch $expr, bool $allowOnlyOnThis): bool
	{
		if (!$this->phpVersion->supportsReadOnlyProperties()) {
			return false;
		}

		while ($expr instanceof PropertyFetch) {
			if ($expr->var instanceof Variable) {
				if (
					$allowOnlyOnThis
					&& (
						! $expr->name instanceof Node\Identifier
						|| !is_string($expr->var->name)
						|| $expr->var->name !== 'this'
					)
				) {
					return false;
				}
			} elseif (!$expr->var instanceof PropertyFetch) {
				return false;
			}

			$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($expr, $this);
			if ($propertyReflection === null) {
				return false;
			}

			$nativePropertyReflection = $propertyReflection->getNativeReflection();
			if ($nativePropertyReflection === null || !$nativePropertyReflection->isReadOnly()) {
				return false;
			}

			$expr = $expr->var;
		}

		return true;
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
	 */
	public function getFunction(): ?PhpFunctionFromParserNodeReflection
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
			$this->getFunction(),
			$this->getNamespace(),
			$this->expressionTypes,
			$this->nativeExpressionTypes,
			[],
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$this->inFunctionCallsStack,
			true,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	public function afterClearstatcacheCall(): self
	{
		$expressionTypes = $this->expressionTypes;
		$nativeExpressionTypes = $this->nativeExpressionTypes;
		foreach (array_keys($expressionTypes) as $exprString) {
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
				if (!str_starts_with($exprString, $functionName . '(') && !str_starts_with($exprString, '\\' . $functionName . '(')) {
					continue;
				}

				unset($expressionTypes[$exprString]);
				unset($nativeExpressionTypes[$exprString]);
				continue 2;
			}
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$expressionTypes,
			$nativeExpressionTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	public function afterOpenSslCall(string $openSslFunctionName): self
	{
		$expressionTypes = $this->expressionTypes;
		$nativeExpressionTypes = $this->nativeExpressionTypes;

		if (in_array($openSslFunctionName, [
			'openssl_cipher_iv_length',
			'openssl_cms_decrypt',
			'openssl_cms_encrypt',
			'openssl_cms_read',
			'openssl_cms_sign',
			'openssl_cms_verify',
			'openssl_csr_export_to_file',
			'openssl_csr_export',
			'openssl_csr_get_public_key',
			'openssl_csr_get_subject',
			'openssl_csr_new',
			'openssl_csr_sign',
			'openssl_decrypt',
			'openssl_dh_compute_key',
			'openssl_digest',
			'openssl_encrypt',
			'openssl_get_curve_names',
			'openssl_get_privatekey',
			'openssl_get_publickey',
			'openssl_open',
			'openssl_pbkdf2',
			'openssl_pkcs12_export_to_file',
			'openssl_pkcs12_export',
			'openssl_pkcs12_read',
			'openssl_pkcs7_decrypt',
			'openssl_pkcs7_encrypt',
			'openssl_pkcs7_read',
			'openssl_pkcs7_sign',
			'openssl_pkcs7_verify',
			'openssl_pkey_derive',
			'openssl_pkey_export_to_file',
			'openssl_pkey_export',
			'openssl_pkey_get_private',
			'openssl_pkey_get_public',
			'openssl_pkey_new',
			'openssl_private_decrypt',
			'openssl_private_encrypt',
			'openssl_public_decrypt',
			'openssl_public_encrypt',
			'openssl_random_pseudo_bytes',
			'openssl_seal',
			'openssl_sign',
			'openssl_spki_export_challenge',
			'openssl_spki_export',
			'openssl_spki_new',
			'openssl_spki_verify',
			'openssl_verify',
			'openssl_x509_checkpurpose',
			'openssl_x509_export_to_file',
			'openssl_x509_export',
			'openssl_x509_fingerprint',
			'openssl_x509_read',
			'openssl_x509_verify',
		], true)) {
			unset($expressionTypes['\openssl_error_string()']);
			unset($nativeExpressionTypes['\openssl_error_string()']);
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$expressionTypes,
			$nativeExpressionTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	/** @api */
	public function hasVariableType(string $variableName): TrinaryLogic
	{
		if ($this->isGlobalVariable($variableName)) {
			return TrinaryLogic::createYes();
		}

		$varExprString = '$' . $variableName;
		if (!isset($this->expressionTypes[$varExprString])) {
			if ($this->canAnyVariableExist()) {
				return TrinaryLogic::createMaybe();
			}

			return TrinaryLogic::createNo();
		}

		return $this->expressionTypes[$varExprString]->getCertainty();
	}

	/** @api */
	public function getVariableType(string $variableName): Type
	{
		if ($this->hasVariableType($variableName)->maybe()) {
			if ($variableName === 'argc') {
				return IntegerRangeType::fromInterval(1, null);
			}
			if ($variableName === 'argv') {
				return TypeCombinator::intersect(
					new ArrayType(new IntegerType(), new StringType()),
					new NonEmptyArrayType(),
					new AccessoryArrayListType(),
				);
			}
			if ($this->canAnyVariableExist()) {
				return new MixedType();
			}
		}

		if ($this->hasVariableType($variableName)->no()) {
			throw new UndefinedVariableException($this, $variableName);
		}

		$varExprString = '$' . $variableName;
		if (!array_key_exists($varExprString, $this->expressionTypes)) {
			if ($this->isGlobalVariable($variableName)) {
				return new ArrayType(new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType(true));
			}
			return new MixedType();
		}

		return TypeUtils::resolveLateResolvableTypes($this->expressionTypes[$varExprString]->getType());
	}

	/**
	 * @api
	 * @return list<string>
	 */
	public function getDefinedVariables(): array
	{
		$variables = [];
		foreach ($this->expressionTypes as $exprString => $holder) {
			if (!$holder->getExpr() instanceof Variable) {
				continue;
			}
			if (!$holder->getCertainty()->yes()) {
				continue;
			}

			$variables[] = substr($exprString, 1);
		}

		return $variables;
	}

	/**
	 * @api
	 * @return list<string>
	 */
	public function getMaybeDefinedVariables(): array
	{
		$variables = [];
		foreach ($this->expressionTypes as $exprString => $holder) {
			if (!$holder->getExpr() instanceof Variable) {
				continue;
			}
			if (!$holder->getCertainty()->maybe()) {
				continue;
			}

			$variables[] = substr($exprString, 1);
		}

		return $variables;
	}

	private function isGlobalVariable(string $variableName): bool
	{
		return in_array($variableName, self::SUPERGLOBAL_VARIABLES, true);
	}

	/** @api */
	public function hasConstant(Name $name): bool
	{
		$isCompilerHaltOffset = $name->toString() === '__COMPILER_HALT_OFFSET__';
		if ($isCompilerHaltOffset) {
			return $this->fileHasCompilerHaltStatementCalls();
		}

		if ($this->getGlobalConstantType($name) !== null) {
			return true;
		}

		return $this->reflectionProvider->hasConstant($name, $this);
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
	public function getAnonymousFunctionReflection(): ?ClosureType
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
			return $this->getIterableKeyType($this->getType($node->getExpr()));
		}
		if ($node instanceof GetIterableValueTypeExpr) {
			return $this->getIterableValueType($this->getType($node->getExpr()));
		}
		if ($node instanceof GetOffsetValueTypeExpr) {
			return $this->getType($node->getVar())->getOffsetValueType($this->getType($node->getDim()));
		}
		if ($node instanceof ExistingArrayDimFetch) {
			return $this->getType(new Expr\ArrayDimFetch($node->getVar(), $node->getDim()));
		}
		if ($node instanceof UnsetOffsetExpr) {
			return $this->getType($node->getVar())->unsetOffset($this->getType($node->getDim()));
		}
		if ($node instanceof SetOffsetValueTypeExpr) {
			$varNode = $node->getVar();
			$varType = $this->getType($varNode);
			if ($varNode instanceof OriginalPropertyTypeExpr) {
				$currentPropertyType = $this->getType($varNode->getPropertyFetch());
				if ($varType instanceof UnionType) {
					$varType = $varType->filterTypes(static fn (Type $innerType) => !$innerType->isSuperTypeOf($currentPropertyType)->no());
				}
			}
			return $varType->setOffsetValueType(
				$node->getDim() !== null ? $this->getType($node->getDim()) : null,
				$this->getType($node->getValue()),
			);
		}
		if ($node instanceof SetExistingOffsetValueTypeExpr) {
			$varNode = $node->getVar();
			$varType = $this->getType($varNode);
			if ($varNode instanceof OriginalPropertyTypeExpr) {
				$currentPropertyType = $this->getType($varNode->getPropertyFetch());
				if ($varType instanceof UnionType) {
					$varType = $varType->filterTypes(static fn (Type $innerType) => !$innerType->isSuperTypeOf($currentPropertyType)->no());
				}
			}
			return $varType->setExistingOffsetValueType(
				$this->getType($node->getDim()),
				$this->getType($node->getValue()),
			);
		}
		if ($node instanceof TypeExpr) {
			return $node->getExprType();
		}
		if ($node instanceof NativeTypeExpr) {
			if ($this->nativeTypesPromoted) {
				return $node->getNativeType();
			}
			return $node->getPhpDocType();
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
			$this->resolvedTypes[$key] = TypeUtils::resolveLateResolvableTypes($this->resolveType($key, $node));
		}
		return $this->resolvedTypes[$key];
	}

	private function getNodeKey(Expr $node): string
	{
		$key = $this->exprPrinter->printExpr($node);

		$attributes = $node->getAttributes();
		if (
			$node instanceof Node\FunctionLike
			&& (($attributes[ArrayMapArgVisitor::ATTRIBUTE_NAME] ?? null) !== null)
			&& (($attributes['startFilePos'] ?? null) !== null)
		) {
			$key .= '/*' . $attributes['startFilePos'] . '*/';
		}

		if (($attributes[self::KEEP_VOID_ATTRIBUTE_NAME] ?? null) === true) {
			$key .= '/*' . self::KEEP_VOID_ATTRIBUTE_NAME . '*/';
		}

		return $key;
	}

	private function getClosureScopeCacheKey(): string
	{
		$parts = [];
		foreach ($this->expressionTypes as $exprString => $expressionTypeHolder) {
			$parts[] = sprintf('%s::%s', $exprString, $expressionTypeHolder->getType()->describe(VerbosityLevel::cache()));
		}
		$parts[] = '---';
		foreach ($this->nativeExpressionTypes as $exprString => $expressionTypeHolder) {
			$parts[] = sprintf('%s::%s', $exprString, $expressionTypeHolder->getType()->describe(VerbosityLevel::cache()));
		}

		$parts[] = sprintf(':%d', count($this->inFunctionCallsStack));
		foreach ($this->inFunctionCallsStack as [$method, $parameter]) {
			if ($parameter === null) {
				$parts[] = ',null';
				continue;
			}

			$parts[] = sprintf(',%s', $parameter->getType()->describe(VerbosityLevel::cache()));
		}

		return md5(implode("\n", $parts));
	}

	private function resolveType(string $exprString, Expr $node): Type
	{
		foreach ($this->expressionTypeResolverExtensionRegistry->getExtensions() as $extension) {
			$type = $extension->getType($node, $this);
			if ($type !== null) {
				return $type;
			}
		}

		if ($node instanceof Expr\Exit_ || $node instanceof Expr\Throw_) {
			return new NonAcceptingNeverType();
		}

		if (!$node instanceof Variable && $this->hasExpressionType($node)->yes()) {
			return $this->expressionTypes[$exprString]->getType();
		}

		if ($node instanceof AlwaysRememberedExpr) {
			return $this->nativeTypesPromoted ? $node->getNativeExprType() : $node->getExprType();
		}

		if ($node instanceof Expr\BinaryOp\Smaller) {
			return $this->getType($node->left)->isSmallerThan($this->getType($node->right), $this->phpVersion)->toBooleanType();
		}

		if ($node instanceof Expr\BinaryOp\SmallerOrEqual) {
			return $this->getType($node->left)->isSmallerThanOrEqual($this->getType($node->right), $this->phpVersion)->toBooleanType();
		}

		if ($node instanceof Expr\BinaryOp\Greater) {
			return $this->getType($node->right)->isSmallerThan($this->getType($node->left), $this->phpVersion)->toBooleanType();
		}

		if ($node instanceof Expr\BinaryOp\GreaterOrEqual) {
			return $this->getType($node->right)->isSmallerThanOrEqual($this->getType($node->left), $this->phpVersion)->toBooleanType();
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

			return $this->initializerExprTypeResolver->resolveEqualType($leftType, $rightType)->type;
		}

		if ($node instanceof Expr\BinaryOp\NotEqual) {
			return $this->getType(new Expr\BooleanNot(new BinaryOp\Equal($node->left, $node->right)));
		}

		if ($node instanceof Expr\Empty_) {
			$result = $this->issetCheck($node->expr, static function (Type $type): ?bool {
				$isNull = $type->isNull();
				$isFalsey = $type->toBoolean()->isFalse();
				if ($isNull->maybe()) {
					return null;
				}
				if ($isFalsey->maybe()) {
					return null;
				}

				if ($isNull->yes()) {
					return $isFalsey->no();
				}

				return !$isFalsey->yes();
			});
			if ($result === null) {
				return new BooleanType();
			}

			return new ConstantBooleanType(!$result);
		}

		if ($node instanceof Node\Expr\BooleanNot) {
			$exprBooleanType = $this->getType($node->expr)->toBoolean();
			if ($exprBooleanType instanceof ConstantBooleanType) {
				return new ConstantBooleanType(!$exprBooleanType->getValue());
			}

			return new BooleanType();
		}

		if ($node instanceof Node\Expr\BitwiseNot) {
			return $this->initializerExprTypeResolver->getBitwiseNotType($node->expr, fn (Expr $expr): Type => $this->getType($expr));
		}

		if (
			$node instanceof Node\Expr\BinaryOp\BooleanAnd
			|| $node instanceof Node\Expr\BinaryOp\LogicalAnd
		) {
			$leftBooleanType = $this->getType($node->left)->toBoolean();
			if ($leftBooleanType->isFalse()->yes()) {
				return new ConstantBooleanType(false);
			}

			if ($this->getBooleanExpressionDepth($node->left) <= self::BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH) {
				$leftResult = $this->nodeScopeResolver->processExprNode(new Node\Stmt\Expression($node->left), $node->left, $this, new ExpressionResultStorage(), new NoopNodeCallback(), ExpressionContext::createDeep());
				$rightBooleanType = $leftResult->getTruthyScope()->getType($node->right)->toBoolean();
			} else {
				$rightBooleanType = $this->filterByTruthyValue($node->left)->getType($node->right)->toBoolean();
			}

			if ($rightBooleanType->isFalse()->yes()) {
				return new ConstantBooleanType(false);
			}

			if (
				$leftBooleanType->isTrue()->yes()
				&& $rightBooleanType->isTrue()->yes()
			) {
				return new ConstantBooleanType(true);
			}

			return new BooleanType();
		}

		if (
			$node instanceof Node\Expr\BinaryOp\BooleanOr
			|| $node instanceof Node\Expr\BinaryOp\LogicalOr
		) {
			$leftBooleanType = $this->getType($node->left)->toBoolean();
			if ($leftBooleanType->isTrue()->yes()) {
				return new ConstantBooleanType(true);
			}

			if ($this->getBooleanExpressionDepth($node->left) <= self::BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH) {
				$leftResult = $this->nodeScopeResolver->processExprNode(new Node\Stmt\Expression($node->left), $node->left, $this, new ExpressionResultStorage(), new NoopNodeCallback(), ExpressionContext::createDeep());
				$rightBooleanType = $leftResult->getFalseyScope()->getType($node->right)->toBoolean();
			} else {
				$rightBooleanType = $this->filterByFalseyValue($node->left)->getType($node->right)->toBoolean();
			}

			if ($rightBooleanType->isTrue()->yes()) {
				return new ConstantBooleanType(true);
			}

			if (
				$leftBooleanType->isFalse()->yes()
				&& $rightBooleanType->isFalse()->yes()
			) {
				return new ConstantBooleanType(false);
			}

			return new BooleanType();
		}

		if ($node instanceof Node\Expr\BinaryOp\LogicalXor) {
			$leftBooleanType = $this->getType($node->left)->toBoolean();
			$rightBooleanType = $this->getType($node->right)->toBoolean();

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
			return $this->richerScopeGetTypeHelper->getIdenticalResult($this, $node)->type;
		}

		if ($node instanceof Expr\BinaryOp\NotIdentical) {
			return $this->richerScopeGetTypeHelper->getNotIdenticalResult($this, $node)->type;
		}

		if ($node instanceof Expr\Instanceof_) {
			$expressionType = $this->getType($node->expr);
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
					if ($type->getObjectClassNames() !== []) {
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
			return $this->initializerExprTypeResolver->getUnaryMinusType($node->expr, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof Expr\BinaryOp\Concat) {
			return $this->initializerExprTypeResolver->getConcatType($node->left, $node->right, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof Expr\AssignOp\Concat) {
			return $this->initializerExprTypeResolver->getConcatType($node->var, $node->expr, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof BinaryOp\BitwiseAnd) {
			return $this->initializerExprTypeResolver->getBitwiseAndType($node->left, $node->right, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof Expr\AssignOp\BitwiseAnd) {
			return $this->initializerExprTypeResolver->getBitwiseAndType($node->var, $node->expr, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof BinaryOp\BitwiseOr) {
			return $this->initializerExprTypeResolver->getBitwiseOrType($node->left, $node->right, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof Expr\AssignOp\BitwiseOr) {
			return $this->initializerExprTypeResolver->getBitwiseOrType($node->var, $node->expr, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof BinaryOp\BitwiseXor) {
			return $this->initializerExprTypeResolver->getBitwiseXorType($node->left, $node->right, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof Expr\AssignOp\BitwiseXor) {
			return $this->initializerExprTypeResolver->getBitwiseXorType($node->var, $node->expr, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof Expr\BinaryOp\Spaceship) {
			return $this->initializerExprTypeResolver->getSpaceshipType($node->left, $node->right, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof BinaryOp\Div) {
			return $this->initializerExprTypeResolver->getDivType($node->left, $node->right, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof Expr\AssignOp\Div) {
			return $this->initializerExprTypeResolver->getDivType($node->var, $node->expr, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof BinaryOp\Mod) {
			return $this->initializerExprTypeResolver->getModType($node->left, $node->right, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof Expr\AssignOp\Mod) {
			return $this->initializerExprTypeResolver->getModType($node->var, $node->expr, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof BinaryOp\Plus) {
			return $this->initializerExprTypeResolver->getPlusType($node->left, $node->right, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof Expr\AssignOp\Plus) {
			return $this->initializerExprTypeResolver->getPlusType($node->var, $node->expr, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof BinaryOp\Minus) {
			return $this->initializerExprTypeResolver->getMinusType($node->left, $node->right, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof Expr\AssignOp\Minus) {
			return $this->initializerExprTypeResolver->getMinusType($node->var, $node->expr, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof BinaryOp\Mul) {
			return $this->initializerExprTypeResolver->getMulType($node->left, $node->right, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof Expr\AssignOp\Mul) {
			return $this->initializerExprTypeResolver->getMulType($node->var, $node->expr, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof BinaryOp\Pow) {
			return $this->initializerExprTypeResolver->getPowType($node->left, $node->right, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof Expr\AssignOp\Pow) {
			return $this->initializerExprTypeResolver->getPowType($node->var, $node->expr, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof BinaryOp\ShiftLeft) {
			return $this->initializerExprTypeResolver->getShiftLeftType($node->left, $node->right, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof Expr\AssignOp\ShiftLeft) {
			return $this->initializerExprTypeResolver->getShiftLeftType($node->var, $node->expr, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof BinaryOp\ShiftRight) {
			return $this->initializerExprTypeResolver->getShiftRightType($node->left, $node->right, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof Expr\AssignOp\ShiftRight) {
			return $this->initializerExprTypeResolver->getShiftRightType($node->var, $node->expr, fn (Expr $expr): Type => $this->getType($expr));
		}

		if ($node instanceof Expr\Clone_) {
			$cloneType = TypeCombinator::intersect($this->getType($node->expr), new ObjectWithoutClassType());

			return TypeTraverser::map($cloneType, static function (Type $type, callable $traverse): Type {
				if ($type instanceof UnionType || $type instanceof IntersectionType) {
					return $traverse($type);
				}
				if ($type instanceof ThisType) {
					return new StaticType($type->getClassReflection(), $type->getSubtractedType());
				}

				return $type;
			});
		}

		if ($node instanceof Node\Scalar\Int_) {
			return $this->initializerExprTypeResolver->getType($node, InitializerExprContext::fromScope($this));
		} elseif ($node instanceof String_) {
			return $this->initializerExprTypeResolver->getType($node, InitializerExprContext::fromScope($this));
		} elseif ($node instanceof Node\Scalar\InterpolatedString) {
			$resultType = null;
			foreach ($node->parts as $part) {
				if ($part instanceof InterpolatedStringPart) {
					$partType = new ConstantStringType($part->value);
				} else {
					$partType = $this->getType($part)->toString();
				}
				if ($resultType === null) {
					$resultType = $partType;
					continue;
				}

				$resultType = $this->initializerExprTypeResolver->resolveConcatType($resultType, $partType);
			}

			return $resultType ?? new ConstantStringType('');
		} elseif ($node instanceof Node\Scalar\Float_) {
			return $this->initializerExprTypeResolver->getType($node, InitializerExprContext::fromScope($this));
		} elseif ($node instanceof Expr\CallLike && $node->isFirstClassCallable()) {
			if ($node instanceof FuncCall && $node->name instanceof Expr) {
				$callableType = $this->getType($node->name);
				if (!$callableType->isCallable()->yes()) {
					return new ObjectType(Closure::class);
				}

				return $this->initializerExprTypeResolver->createFirstClassCallable(
					null,
					$callableType->getCallableParametersAcceptors($this),
					$this->nativeTypesPromoted,
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

				return $this->initializerExprTypeResolver->createFirstClassCallable(
					$method,
					$method->getVariants(),
					$this->nativeTypesPromoted,
				);
			}

			return $this->initializerExprTypeResolver->getFirstClassCallableType($node, InitializerExprContext::fromScope($this), $this->nativeTypesPromoted);
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
			$arrayMapArgs = $node->getAttribute(ArrayMapArgVisitor::ATTRIBUTE_NAME);
			$immediatelyInvokedArgs = $node->getAttribute(ImmediatelyInvokedClosureVisitor::ARGS_ATTRIBUTE_NAME);
			if ($arrayMapArgs !== null) {
				$callableParameters = [];
				foreach ($arrayMapArgs as $funcCallArg) {
					$callableParameters[] = new DummyParameter('item', $this->getType($funcCallArg->value)->getIterableValueType(), false, PassedByReference::createNo(), false, null);
				}
			} elseif ($immediatelyInvokedArgs !== null) {
				foreach ($immediatelyInvokedArgs as $immediatelyInvokedArg) {
					$callableParameters[] = new DummyParameter('item', $this->getType($immediatelyInvokedArg->value), false, PassedByReference::createNo(), false, null);
				}
			} else {
				$inFunctionCallsStackCount = count($this->inFunctionCallsStack);
				if ($inFunctionCallsStackCount > 0) {
					[, $inParameter] = $this->inFunctionCallsStack[$inFunctionCallsStackCount - 1];
					if ($inParameter !== null) {
						$callableParameters = $this->nodeScopeResolver->createCallableParameters($this, $node, null, $inParameter->getType());
					}
				}
			}

			if ($node instanceof Expr\ArrowFunction) {
				$arrowScope = $this->enterArrowFunctionWithoutReflection($node, $callableParameters);

				if ($node->expr instanceof Expr\Yield_ || $node->expr instanceof Expr\YieldFrom) {
					$yieldNode = $node->expr;

					if ($yieldNode instanceof Expr\Yield_) {
						if ($yieldNode->key === null) {
							$keyType = new IntegerType();
						} else {
							$keyType = $arrowScope->getType($yieldNode->key);
						}

						if ($yieldNode->value === null) {
							$valueType = new NullType();
						} else {
							$valueType = $arrowScope->getType($yieldNode->value);
						}
					} else {
						$yieldFromType = $arrowScope->getType($yieldNode->expr);
						$keyType = $arrowScope->getIterableKeyType($yieldFromType);
						$valueType = $arrowScope->getIterableValueType($yieldFromType);
					}

					$returnType = new GenericObjectType(Generator::class, [
						$keyType,
						$valueType,
						new MixedType(),
						new VoidType(),
					]);
				} else {
					$returnType = $arrowScope->getKeepVoidType($node->expr);
					if ($node->returnType !== null) {
						$nativeReturnType = $this->getFunctionType($node->returnType, false, false);
						$returnType = self::intersectButNotNever($nativeReturnType, $returnType);
					}
				}

				$arrowFunctionImpurePoints = [];
				$invalidateExpressions = [];
				$arrowFunctionExprResult = $this->nodeScopeResolver->processExprNode(
					new Node\Stmt\Expression($node->expr),
					$node->expr,
					$arrowScope,
					new ExpressionResultStorage(),
					static function (Node $node, Scope $scope) use ($arrowScope, &$arrowFunctionImpurePoints, &$invalidateExpressions): void {
						if ($scope->getAnonymousFunctionReflection() !== $arrowScope->getAnonymousFunctionReflection()) {
							return;
						}

						if ($node instanceof InvalidateExprNode) {
							$invalidateExpressions[] = $node;
							return;
						}

						if (!$node instanceof PropertyAssignNode) {
							return;
						}

						$arrowFunctionImpurePoints[] = new ImpurePoint(
							$scope,
							$node,
							'propertyAssign',
							'property assignment',
							true,
						);
					},
					ExpressionContext::createDeep(),
				);
				$throwPoints = array_map(static fn ($throwPoint) => $throwPoint->toPublic(), $arrowFunctionExprResult->getThrowPoints());
				$impurePoints = array_merge($arrowFunctionImpurePoints, $arrowFunctionExprResult->getImpurePoints());
				$usedVariables = [];
			} else {
				$cachedTypes = $node->getAttribute('phpstanCachedTypes', []);
				$cacheKey = $this->getClosureScopeCacheKey();
				if (array_key_exists($cacheKey, $cachedTypes)) {
					$cachedClosureData = $cachedTypes[$cacheKey];

					$mustUseReturnValue = TrinaryLogic::createNo();
					foreach ($node->attrGroups as $attrGroup) {
						foreach ($attrGroup->attrs as $attr) {
							if ($attr->name->toLowerString() === 'nodiscard') {
								$mustUseReturnValue = TrinaryLogic::createYes();
								break;
							}
						}
					}

					return new ClosureType(
						$parameters,
						$cachedClosureData['returnType'],
						$isVariadic,
						TemplateTypeMap::createEmpty(),
						TemplateTypeMap::createEmpty(),
						TemplateTypeVarianceMap::createEmpty(),
						throwPoints: $cachedClosureData['throwPoints'],
						impurePoints: $cachedClosureData['impurePoints'],
						invalidateExpressions: $cachedClosureData['invalidateExpressions'],
						usedVariables: $cachedClosureData['usedVariables'],
						acceptsNamedArguments: TrinaryLogic::createYes(),
						mustUseReturnValue: $mustUseReturnValue,
					);
				}
				if (self::$resolveClosureTypeDepth >= 2) {
					return new ClosureType(
						$parameters,
						$this->getFunctionType($node->returnType, false, false),
						$isVariadic,
					);
				}

				self::$resolveClosureTypeDepth++;

				$closureScope = $this->enterAnonymousFunctionWithoutReflection($node, $callableParameters);
				$closureReturnStatements = [];
				$closureYieldStatements = [];
				$onlyNeverExecutionEnds = null;
				$closureImpurePoints = [];
				$invalidateExpressions = [];

				try {
					$closureStatementResult = $this->nodeScopeResolver->processStmtNodes($node, $node->stmts, $closureScope, static function (Node $node, Scope $scope) use ($closureScope, &$closureReturnStatements, &$closureYieldStatements, &$onlyNeverExecutionEnds, &$closureImpurePoints, &$invalidateExpressions): void {
						if ($scope->getAnonymousFunctionReflection() !== $closureScope->getAnonymousFunctionReflection()) {
							return;
						}

						if ($node instanceof InvalidateExprNode) {
							$invalidateExpressions[] = $node;
							return;
						}

						if ($node instanceof PropertyAssignNode) {
							$closureImpurePoints[] = new ImpurePoint(
								$scope,
								$node,
								'propertyAssign',
								'property assignment',
								true,
							);
							return;
						}

						if ($node instanceof ExecutionEndNode) {
							if ($node->getStatementResult()->isAlwaysTerminating()) {
								foreach ($node->getStatementResult()->getExitPoints() as $exitPoint) {
									if ($exitPoint->getStatement() instanceof Node\Stmt\Return_) {
										$onlyNeverExecutionEnds = false;
										continue;
									}

									if ($onlyNeverExecutionEnds === null) {
										$onlyNeverExecutionEnds = true;
									}

									break;
								}

								if (count($node->getStatementResult()->getExitPoints()) === 0) {
									if ($onlyNeverExecutionEnds === null) {
										$onlyNeverExecutionEnds = true;
									}
								}
							} else {
								$onlyNeverExecutionEnds = false;
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
					}, StatementContext::createTopLevel());
				} finally {
					self::$resolveClosureTypeDepth--;
				}

				$throwPoints = $closureStatementResult->getThrowPoints();
				$impurePoints = array_merge($closureImpurePoints, $closureStatementResult->getImpurePoints());

				$returnTypes = [];
				$hasNull = false;
				foreach ($closureReturnStatements as [$returnNode, $returnScope]) {
					if ($returnNode->expr === null) {
						$hasNull = true;
						continue;
					}

					$returnTypes[] = $returnScope->toMutatingScope()->getType($returnNode->expr);
				}

				if (count($returnTypes) === 0) {
					if ($onlyNeverExecutionEnds === true && !$hasNull) {
						$returnType = new NonAcceptingNeverType();
					} else {
						$returnType = new VoidType();
					}
				} else {
					if ($onlyNeverExecutionEnds === true) {
						$returnTypes[] = new NonAcceptingNeverType();
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
								$keyTypes[] = $yieldScope->toMutatingScope()->getType($yieldNode->key);
							}

							if ($yieldNode->value === null) {
								$valueTypes[] = new NullType();
							} else {
								$valueTypes[] = $yieldScope->toMutatingScope()->getType($yieldNode->value);
							}

							continue;
						}

						$yieldFromType = $yieldScope->toMutatingScope()->getType($yieldNode->expr);
						$keyTypes[] = $yieldScope->toMutatingScope()->getIterableKeyType($yieldFromType);
						$valueTypes[] = $yieldScope->toMutatingScope()->getIterableValueType($yieldFromType);
					}

					$returnType = new GenericObjectType(Generator::class, [
						TypeCombinator::union(...$keyTypes),
						TypeCombinator::union(...$valueTypes),
						new MixedType(),
						$returnType,
					]);
				} else {
					if ($node->returnType !== null) {
						$nativeReturnType = $this->getFunctionType($node->returnType, false, false);
						$returnType = self::intersectButNotNever($nativeReturnType, $returnType);
					}
				}

				$usedVariables = [];
				foreach ($node->uses as $use) {
					if (!is_string($use->var->name)) {
						continue;
					}

					$usedVariables[] = $use->var->name;
				}

				foreach ($node->uses as $use) {
					if (!$use->byRef) {
						continue;
					}

					$impurePoints[] = new ImpurePoint(
						$this,
						$node,
						'functionCall',
						'call to a Closure with by-ref use',
						true,
					);
					break;
				}
			}

			foreach ($parameters as $parameter) {
				if ($parameter->passedByReference()->no()) {
					continue;
				}

				$impurePoints[] = new ImpurePoint(
					$this,
					$node,
					'functionCall',
					'call to a Closure with by-ref parameter',
					true,
				);
			}

			$throwPointsForClosureType = array_map(static fn (ThrowPoint $throwPoint) => $throwPoint->isExplicit() ? SimpleThrowPoint::createExplicit($throwPoint->getType(), $throwPoint->canContainAnyThrowable()) : SimpleThrowPoint::createImplicit(), $throwPoints);
			$impurePointsForClosureType = array_map(static fn (ImpurePoint $impurePoint) => new SimpleImpurePoint($impurePoint->getIdentifier(), $impurePoint->getDescription(), $impurePoint->isCertain()), $impurePoints);

			$cachedTypes = $node->getAttribute('phpstanCachedTypes', []);
			$cachedTypes[$this->getClosureScopeCacheKey()] = [
				'returnType' => $returnType,
				'throwPoints' => $throwPointsForClosureType,
				'impurePoints' => $impurePointsForClosureType,
				'invalidateExpressions' => $invalidateExpressions,
				'usedVariables' => $usedVariables,
			];
			$node->setAttribute('phpstanCachedTypes', $cachedTypes);

			$mustUseReturnValue = TrinaryLogic::createNo();
			foreach ($node->attrGroups as $attrGroup) {
				foreach ($attrGroup->attrs as $attr) {
					if ($attr->name->toLowerString() === 'nodiscard') {
						$mustUseReturnValue = TrinaryLogic::createYes();
						break;
					}
				}
			}

			return new ClosureType(
				$parameters,
				$returnType,
				$isVariadic,
				TemplateTypeMap::createEmpty(),
				TemplateTypeMap::createEmpty(),
				TemplateTypeVarianceMap::createEmpty(),
				throwPoints: $throwPointsForClosureType,
				impurePoints: $impurePointsForClosureType,
				invalidateExpressions: $invalidateExpressions,
				usedVariables: $usedVariables,
				acceptsNamedArguments: TrinaryLogic::createYes(),
				mustUseReturnValue: $mustUseReturnValue,
			);
		} elseif ($node instanceof New_) {
			if ($node->class instanceof Name) {
				return $this->exactInstantiation($node, $node->class);
			}
			if ($node->class instanceof Node\Stmt\Class_) {
				$anonymousClassReflection = $this->reflectionProvider->getAnonymousClassReflection($node->class, $this);

				return new ObjectType($anonymousClassReflection->getName());
			}

			$exprType = $this->getType($node->class);
			return $exprType->getObjectTypeOrClassStringObjectType();

		} elseif ($node instanceof Array_) {
			return $this->initializerExprTypeResolver->getArrayType($node, fn (Expr $expr): Type => $this->getType($expr));
		} elseif ($node instanceof Unset_) {
			return new NullType();
		} elseif ($node instanceof Expr\Cast) {
			return $this->initializerExprTypeResolver->getCastType($node, fn (Expr $expr): Type => $this->getType($expr));
		} elseif ($node instanceof Node\Scalar\MagicConst) {
			return $this->initializerExprTypeResolver->getType($node, InitializerExprContext::fromScope($this));
		} elseif ($node instanceof Expr\PostInc || $node instanceof Expr\PostDec) {
			return $this->getType($node->var);
		} elseif ($node instanceof Expr\PreInc || $node instanceof Expr\PreDec) {
			$varType = $this->getType($node->var);
			$varScalars = $varType->getConstantScalarValues();

			if (count($varScalars) > 0) {
				$newTypes = [];

				foreach ($varScalars as $varValue) {
					// until PHP 8.5 it was valid to increment/decrement an empty string.
					// see https://github.com/php/php-src/issues/19597
					if ($node instanceof Expr\PreInc) {
						if ($varValue === '') {
							$varValue = '1';
						} elseif (is_string($varValue) && !is_numeric($varValue)) {
							try {
								$varValue = str_increment($varValue);
							} catch (ValueError) {
								return new NeverType();
							}
						} elseif (!is_bool($varValue)) {
							++$varValue;
						}
					} else {
						if ($varValue === '') {
							$varValue = -1;
						} elseif (is_string($varValue) && !is_numeric($varValue)) {
							try {
								$varValue = str_decrement($varValue);
							} catch (ValueError) {
								return new NeverType();
							}
						} elseif (is_numeric($varValue)) {
							--$varValue;
						}
					}

					$newTypes[] = $this->getTypeFromValue($varValue);
				}
				return TypeCombinator::union(...$newTypes);
			} elseif ($varType->isString()->yes()) {
				if ($varType->isLiteralString()->yes()) {
					return new IntersectionType([
						new StringType(),
						new AccessoryLiteralStringType(),
					]);
				}

				if ($varType->isNumericString()->yes()) {
					return new BenevolentUnionType([
						new IntegerType(),
						new FloatType(),
					]);
				}

				return new BenevolentUnionType([
					new StringType(),
					new IntegerType(),
					new FloatType(),
				]);
			}

			if ($node instanceof Expr\PreInc) {
				return $this->getType(new BinaryOp\Plus($node->var, new Node\Scalar\Int_(1)));
			}

			return $this->getType(new BinaryOp\Minus($node->var, new Node\Scalar\Int_(1)));
		} elseif ($node instanceof Expr\Yield_) {
			$functionReflection = $this->getFunction();
			if ($functionReflection === null) {
				return new MixedType();
			}

			$returnType = $functionReflection->getReturnType();
			$generatorSendType = $returnType->getTemplateType(Generator::class, 'TSend');
			if ($generatorSendType instanceof ErrorType) {
				return new MixedType();
			}

			return $generatorSendType;
		} elseif ($node instanceof Expr\YieldFrom) {
			$yieldFromType = $this->getType($node->expr);
			$generatorReturnType = $yieldFromType->getTemplateType(Generator::class, 'TReturn');
			if ($generatorReturnType instanceof ErrorType) {
				return new MixedType();
			}

			return $generatorReturnType;
		} elseif ($node instanceof Expr\Match_) {
			$cond = $node->cond;
			$condType = $this->getType($cond);
			$types = [];

			$matchScope = $this;
			$arms = $node->arms;
			if ($condType->isEnum()->yes()) {
				// enum match analysis would work even without this if branch
				// but would be much slower
				// this avoids using ObjectType::$subtractedType which is slow for huge enums
				// because of repeated union type normalization
				$enumCases = $condType->getEnumCases();
				if (count($enumCases) > 0) {
					$indexedEnumCases = [];
					foreach ($enumCases as $enumCase) {
						$indexedEnumCases[strtolower($enumCase->getClassName())][$enumCase->getEnumCaseName()] = $enumCase;
					}
					$unusedIndexedEnumCases = $indexedEnumCases;

					foreach ($arms as $i => $arm) {
						if ($arm->conds === null) {
							continue;
						}

						$conditionCases = [];
						foreach ($arm->conds as $armCond) {
							if (!$armCond instanceof Expr\ClassConstFetch) {
								continue 2;
							}
							if (!$armCond->class instanceof Name) {
								continue 2;
							}
							if (!$armCond->name instanceof Node\Identifier) {
								continue 2;
							}
							$fetchedClassName = $this->resolveName($armCond->class);
							$loweredFetchedClassName = strtolower($fetchedClassName);
							if (!array_key_exists($loweredFetchedClassName, $indexedEnumCases)) {
								continue 2;
							}

							$caseName = $armCond->name->toString();
							if (!array_key_exists($caseName, $indexedEnumCases[$loweredFetchedClassName])) {
								continue 2;
							}

							$conditionCases[] = $indexedEnumCases[$loweredFetchedClassName][$caseName];
							unset($unusedIndexedEnumCases[$loweredFetchedClassName][$caseName]);
						}

						$conditionCasesCount = count($conditionCases);
						if ($conditionCasesCount === 0) {
							throw new ShouldNotHappenException();
						} elseif ($conditionCasesCount === 1) {
							$conditionCaseType = $conditionCases[0];
						} else {
							$conditionCaseType = new UnionType($conditionCases);
						}

						$types[] = $matchScope->addTypeToExpression(
							$cond,
							$conditionCaseType,
						)->getType($arm->body);
						unset($arms[$i]);
					}

					$remainingCases = [];
					foreach ($unusedIndexedEnumCases as $cases) {
						foreach ($cases as $case) {
							$remainingCases[] = $case;
						}
					}

					$remainingCasesCount = count($remainingCases);
					if ($remainingCasesCount === 0) {
						$remainingType = new NeverType();
					} elseif ($remainingCasesCount === 1) {
						$remainingType = $remainingCases[0];
					} else {
						$remainingType = new UnionType($remainingCases);
					}

					$matchScope = $matchScope->addTypeToExpression($cond, $remainingType);
				}
			}

			foreach ($arms as $arm) {
				if ($arm->conds === null) {
					if ($node->hasAttribute(self::KEEP_VOID_ATTRIBUTE_NAME)) {
						$arm->body->setAttribute(self::KEEP_VOID_ATTRIBUTE_NAME, $node->getAttribute(self::KEEP_VOID_ATTRIBUTE_NAME));
					}
					$types[] = $matchScope->getType($arm->body);
					continue;
				}

				if (count($arm->conds) === 0) {
					throw new ShouldNotHappenException();
				}

				if (count($arm->conds) === 1) {
					$filteringExpr = new BinaryOp\Identical($cond, $arm->conds[0]);
				} else {
					$items = [];
					foreach ($arm->conds as $filteringExpr) {
						$items[] = new Node\ArrayItem($filteringExpr);
					}
					$filteringExpr = new FuncCall(
						new Name\FullyQualified('in_array'),
						[
							new Arg($cond),
							new Arg(new Array_($items)),
							new Arg(new ConstFetch(new Name\FullyQualified('true'))),
						],
					);
				}

				$filteringExprType = $matchScope->getType($filteringExpr);

				if (!$filteringExprType->isFalse()->yes()) {
					$truthyScope = $matchScope->filterByTruthyValue($filteringExpr);
					if ($node->hasAttribute(self::KEEP_VOID_ATTRIBUTE_NAME)) {
						$arm->body->setAttribute(self::KEEP_VOID_ATTRIBUTE_NAME, $node->getAttribute(self::KEEP_VOID_ATTRIBUTE_NAME));
					}
					$types[] = $truthyScope->getType($arm->body);
				}

				$matchScope = $matchScope->filterByFalseyValue($filteringExpr);
			}

			return TypeCombinator::union(...$types);
		}

		if ($node instanceof Expr\Isset_) {
			$issetResult = true;
			foreach ($node->vars as $var) {
				$result = $this->issetCheck($var, static function (Type $type): ?bool {
					$isNull = $type->isNull();
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
			$issetLeftExpr = new Expr\Isset_([$node->left]);
			$leftType = $this->filterByTruthyValue($issetLeftExpr)->getType($node->left);

			$result = $this->issetCheck($node->left, static function (Type $type): ?bool {
				$isNull = $type->isNull();
				if ($isNull->maybe()) {
					return null;
				}

				return !$isNull->yes();
			});

			if ($result !== null && $result !== false) {
				return TypeCombinator::removeNull($leftType);
			}

			$rightType = $this->filterByFalseyValue($issetLeftExpr)->getType($node->right);

			if ($result === null) {
				return TypeCombinator::union(
					TypeCombinator::removeNull($leftType),
					$rightType,
				);
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

			$namespacedName = null;
			if (!$node->name->isFullyQualified() && $this->getNamespace() !== null) {
				$namespacedName = new FullyQualified([$this->getNamespace(), $node->name->toString()]);
			}
			$globalName = new FullyQualified($node->name->toString());

			foreach ([$namespacedName, $globalName] as $name) {
				if ($name === null) {
					continue;
				}
				$constFetch = new ConstFetch($name);
				if ($this->hasExpressionType($constFetch)->yes()) {
					return $this->constantResolver->resolveConstantType(
						$name->toString(),
						$this->expressionTypes[$this->getNodeKey($constFetch)]->getType(),
					);
				}
			}

			$constantType = $this->constantResolver->resolveConstant($node->name, $this);
			if ($constantType !== null) {
				return $constantType;
			}

			return new ErrorType();
		} elseif ($node instanceof Node\Expr\ClassConstFetch && $node->name instanceof Node\Identifier) {
			if ($this->hasExpressionType($node)->yes()) {
				return $this->expressionTypes[$exprString]->getType();
			}
			return $this->initializerExprTypeResolver->getClassConstFetchTypeByReflection(
				$node->class,
				$node->name->name,
				$this->isInClass() ? $this->getClassReflection() : null,
				fn (Expr $expr): Type => $this->getType($expr),
			);
		}

		if ($node instanceof Expr\Ternary) {
			$condResult = $this->nodeScopeResolver->processExprNode(new Node\Stmt\Expression($node->cond), $node->cond, $this, new ExpressionResultStorage(), new NoopNodeCallback(), ExpressionContext::createDeep());
			if ($node->if === null) {
				$conditionType = $this->getType($node->cond);
				$booleanConditionType = $conditionType->toBoolean();
				if ($booleanConditionType->isTrue()->yes()) {
					return $condResult->getTruthyScope()->getType($node->cond);
				}

				if ($booleanConditionType->isFalse()->yes()) {
					return $condResult->getFalseyScope()->getType($node->else);
				}

				return TypeCombinator::union(
					TypeCombinator::removeFalsey($condResult->getTruthyScope()->getType($node->cond)),
					$condResult->getFalseyScope()->getType($node->else),
				);
			}

			$booleanConditionType = $this->getType($node->cond)->toBoolean();
			if ($booleanConditionType->isTrue()->yes()) {
				return $condResult->getTruthyScope()->getType($node->if);
			}

			if ($booleanConditionType->isFalse()->yes()) {
				return $condResult->getFalseyScope()->getType($node->else);
			}

			return TypeCombinator::union(
				$condResult->getTruthyScope()->getType($node->if),
				$condResult->getFalseyScope()->getType($node->else),
			);
		}

		if ($node instanceof Variable) {
			if (is_string($node->name)) {
				if ($this->hasVariableType($node->name)->no()) {
					return new ErrorType();
				}

				return $this->getVariableType($node->name);
			}

			$nameType = $this->getType($node->name);
			if (count($nameType->getConstantStrings()) > 0) {
				$types = [];
				foreach ($nameType->getConstantStrings() as $constantString) {
					$variableScope = $this
						->filterByTruthyValue(
							new BinaryOp\Identical($node->name, new String_($constantString->getValue())),
						);
					if ($variableScope->hasVariableType($constantString->getValue())->no()) {
						$types[] = new ErrorType();
						continue;
					}

					$types[] = $variableScope->getVariableType($constantString->getValue());
				}

				return TypeCombinator::union(...$types);
			}
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

		if ($node instanceof MethodCall) {
			if ($node->name instanceof Node\Identifier) {
				if ($this->nativeTypesPromoted) {
					$methodReflection = $this->getMethodReflection(
						$this->getNativeType($node->var),
						$node->name->name,
					);
					if ($methodReflection === null) {
						$returnType = new ErrorType();
					} else {
						$returnType = ParametersAcceptorSelector::combineAcceptors($methodReflection->getVariants())->getNativeReturnType();
					}

					return $this->getNullsafeShortCircuitingType($node->var, $returnType);
				}

				$returnType = $this->methodCallReturnType(
					$this->getType($node->var),
					$node->name->name,
					$node,
				);
				if ($returnType === null) {
					$returnType = new ErrorType();
				}
				return $this->getNullsafeShortCircuitingType($node->var, $returnType);
			}

			$nameType = $this->getType($node->name);
			if (count($nameType->getConstantStrings()) > 0) {
				return TypeCombinator::union(
					...array_map(fn ($constantString) => $constantString->getValue() === '' ? new ErrorType() : $this
						->filterByTruthyValue(new BinaryOp\Identical($node->name, new String_($constantString->getValue())))
						->getType(new MethodCall($node->var, new Identifier($constantString->getValue()), $node->args)), $nameType->getConstantStrings()),
				);
			}
		}

		if ($node instanceof Expr\NullsafeMethodCall) {
			$varType = $this->getType($node->var);
			if ($varType->isNull()->yes()) {
				return new NullType();
			}
			if (!TypeCombinator::containsNull($varType)) {
				return $this->getType(new MethodCall($node->var, $node->name, $node->args));
			}

			return TypeCombinator::union(
				$this->filterByTruthyValue(new BinaryOp\NotIdentical($node->var, new ConstFetch(new Name('null'))))
					->getType(new MethodCall($node->var, $node->name, $node->args)),
				new NullType(),
			);
		}

		if ($node instanceof Expr\StaticCall) {
			if ($node->name instanceof Node\Identifier) {
				if ($this->nativeTypesPromoted) {
					if ($node->class instanceof Name) {
						$staticMethodCalledOnType = $this->resolveTypeByNameWithLateStaticBinding($node->class, $node->name);
					} else {
						$staticMethodCalledOnType = $this->getNativeType($node->class);
					}
					$methodReflection = $this->getMethodReflection(
						$staticMethodCalledOnType,
						$node->name->name,
					);
					if ($methodReflection === null) {
						$callType = new ErrorType();
					} else {
						$callType = ParametersAcceptorSelector::combineAcceptors($methodReflection->getVariants())->getNativeReturnType();
					}

					if ($node->class instanceof Expr) {
						return $this->getNullsafeShortCircuitingType($node->class, $callType);
					}

					return $callType;
				}

				if ($node->class instanceof Name) {
					$staticMethodCalledOnType = $this->resolveTypeByNameWithLateStaticBinding($node->class, $node->name);
				} else {
					$staticMethodCalledOnType = TypeCombinator::removeNull($this->getType($node->class))->getObjectTypeOrClassStringObjectType();
				}

				$callType = $this->methodCallReturnType(
					$staticMethodCalledOnType,
					$node->name->toString(),
					$node,
				);
				if ($callType === null) {
					$callType = new ErrorType();
				}

				if ($node->class instanceof Expr) {
					return $this->getNullsafeShortCircuitingType($node->class, $callType);
				}

				return $callType;
			}

			$nameType = $this->getType($node->name);
			if (count($nameType->getConstantStrings()) > 0) {
				return TypeCombinator::union(
					...array_map(fn ($constantString) => $constantString->getValue() === '' ? new ErrorType() : $this
						->filterByTruthyValue(new BinaryOp\Identical($node->name, new String_($constantString->getValue())))
						->getType(new Expr\StaticCall($node->class, new Identifier($constantString->getValue()), $node->args)), $nameType->getConstantStrings()),
				);
			}
		}

		if ($node instanceof BinaryOp\Pipe) {
			if ($node->right instanceof FuncCall && $node->right->isFirstClassCallable()) {
				return $this->getType(new FuncCall($node->right->name, [
					new Arg($node->left),
				]));
			} elseif ($node->right instanceof MethodCall && $node->right->isFirstClassCallable()) {
				return $this->getType(new MethodCall($node->right->var, $node->right->name, [
					new Arg($node->left),
				]));
			} elseif ($node->right instanceof Expr\StaticCall && $node->right->isFirstClassCallable()) {
				return $this->getType(new Expr\StaticCall($node->right->class, $node->right->name, [
					new Arg($node->left),
				]));
			}

			return $this->getType(new FuncCall($node->right, [
				new Arg($node->left),
			]));
		}

		if ($node instanceof PropertyFetch) {
			if ($node->name instanceof Node\Identifier) {
				if ($this->nativeTypesPromoted) {
					$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($node, $this);
					if ($propertyReflection === null) {
						return new ErrorType();
					}

					if (!$propertyReflection->hasNativeType()) {
						return new MixedType();
					}

					$nativeType = $propertyReflection->getNativeType();

					return $this->getNullsafeShortCircuitingType($node->var, $nativeType);
				}

				$returnType = $this->propertyFetchType(
					$this->getType($node->var),
					$node->name->name,
					$node,
				);
				if ($returnType === null) {
					$returnType = new ErrorType();
				}

				return $this->getNullsafeShortCircuitingType($node->var, $returnType);
			}

			$nameType = $this->getType($node->name);
			if (count($nameType->getConstantStrings()) > 0) {
				return TypeCombinator::union(
					...array_map(fn ($constantString) => $constantString->getValue() === '' ? new ErrorType() : $this
						->filterByTruthyValue(new BinaryOp\Identical($node->name, new String_($constantString->getValue())))
						->getType(
							new PropertyFetch($node->var, new Identifier($constantString->getValue())),
						), $nameType->getConstantStrings()),
				);
			}
		}

		if ($node instanceof Expr\NullsafePropertyFetch) {
			$varType = $this->getType($node->var);
			if ($varType->isNull()->yes()) {
				return new NullType();
			}
			if (!TypeCombinator::containsNull($varType)) {
				return $this->getType(new PropertyFetch($node->var, $node->name));
			}

			return TypeCombinator::union(
				$this->filterByTruthyValue(new BinaryOp\NotIdentical($node->var, new ConstFetch(new Name('null'))))
					->getType(new PropertyFetch($node->var, $node->name)),
				new NullType(),
			);
		}

		if ($node instanceof Expr\StaticPropertyFetch) {
			if ($node->name instanceof Node\VarLikeIdentifier) {
				if ($this->nativeTypesPromoted) {
					$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($node, $this);
					if ($propertyReflection === null) {
						return new ErrorType();
					}
					if (!$propertyReflection->hasNativeType()) {
						return new MixedType();
					}

					$nativeType = $propertyReflection->getNativeType();

					if ($node->class instanceof Expr) {
						return $this->getNullsafeShortCircuitingType($node->class, $nativeType);
					}

					return $nativeType;
				}

				if ($node->class instanceof Name) {
					$staticPropertyFetchedOnType = $this->resolveTypeByName($node->class);
				} else {
					$staticPropertyFetchedOnType = TypeCombinator::removeNull($this->getType($node->class))->getObjectTypeOrClassStringObjectType();
				}

				$fetchType = $this->propertyFetchType(
					$staticPropertyFetchedOnType,
					$node->name->toString(),
					$node,
				);
				if ($fetchType === null) {
					$fetchType = new ErrorType();
				}

				if ($node->class instanceof Expr) {
					return $this->getNullsafeShortCircuitingType($node->class, $fetchType);
				}

				return $fetchType;
			}

			$nameType = $this->getType($node->name);
			if (count($nameType->getConstantStrings()) > 0) {
				return TypeCombinator::union(
					...array_map(fn ($constantString) => $constantString->getValue() === '' ? new ErrorType() : $this
						->filterByTruthyValue(new BinaryOp\Identical($node->name, new String_($constantString->getValue())))
						->getType(new Expr\StaticPropertyFetch($node->class, new Node\VarLikeIdentifier($constantString->getValue()))), $nameType->getConstantStrings()),
				);
			}
		}

		if ($node instanceof FuncCall) {
			if ($node->name instanceof Expr) {
				$calledOnType = $this->getType($node->name);
				if ($calledOnType->isCallable()->no()) {
					return new ErrorType();
				}

				$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
					$this,
					$node->getArgs(),
					$calledOnType->getCallableParametersAcceptors($this),
					null,
				);

				$functionName = null;
				if ($node->name instanceof String_) {
					/** @var non-empty-string $name */
					$name = $node->name->value;
					$functionName = new Name($name);
				} elseif (
					$node->name instanceof FuncCall
					&& $node->name->name instanceof Name
					&& $node->name->isFirstClassCallable()
				) {
					$functionName = $node->name->name;
				}

				$normalizedNode = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);
				if ($normalizedNode !== null && $functionName !== null && $this->reflectionProvider->hasFunction($functionName, $this)) {
					$functionReflection = $this->reflectionProvider->getFunction($functionName, $this);
					$resolvedType = $this->getDynamicFunctionReturnType($normalizedNode, $functionReflection);
					if ($resolvedType !== null) {
						return $resolvedType;
					}
				}

				return $parametersAcceptor->getReturnType();
			}

			if (!$this->reflectionProvider->hasFunction($node->name, $this)) {
				return new ErrorType();
			}

			$functionReflection = $this->reflectionProvider->getFunction($node->name, $this);
			if ($this->nativeTypesPromoted) {
				return ParametersAcceptorSelector::combineAcceptors($functionReflection->getVariants())->getNativeReturnType();
			}

			if ($functionReflection->getName() === 'call_user_func') {
				$result = ArgumentsNormalizer::reorderCallUserFuncArguments($node, $this);
				if ($result !== null) {
					[, $innerFuncCall] = $result;

					return $this->getType($innerFuncCall);
				}
			}

			$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
				$this,
				$node->getArgs(),
				$functionReflection->getVariants(),
				$functionReflection->getNamedArgumentsVariants(),
			);
			$normalizedNode = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);
			if ($normalizedNode !== null) {
				if ($functionReflection->getName() === 'clone' && count($normalizedNode->getArgs()) > 0) {
					$cloneType = $this->getType(new Expr\Clone_($normalizedNode->getArgs()[0]->value));
					if (count($normalizedNode->getArgs()) === 2) {
						$propertiesType = $this->getType($normalizedNode->getArgs()[1]->value);
						if ($propertiesType->isConstantArray()->yes()) {
							$constantArrays = $propertiesType->getConstantArrays();
							if (count($constantArrays) === 1) {
								$accessories = [];
								foreach ($constantArrays[0]->getKeyTypes() as $keyType) {
									$constantKeyTypes = $keyType->getConstantScalarValues();
									if (count($constantKeyTypes) !== 1) {
										return $cloneType;
									}
									$accessories[] = new HasPropertyType((string) $constantKeyTypes[0]);
								}
								if (count($accessories) > 0 && count($accessories) <= 16) {
									return TypeCombinator::intersect($cloneType, ...$accessories);
								}
							}
						}
					}

					return $cloneType;
				}
				$resolvedType = $this->getDynamicFunctionReturnType($normalizedNode, $functionReflection);
				if ($resolvedType !== null) {
					return $resolvedType;
				}
			}

			return $this->transformVoidToNull($parametersAcceptor->getReturnType(), $node);
		}

		return new MixedType();
	}

	private function getDynamicFunctionReturnType(FuncCall $normalizedNode, FunctionReflection $functionReflection): ?Type
	{
		foreach ($this->dynamicReturnTypeExtensionRegistry->getDynamicFunctionReturnTypeExtensions() as $dynamicFunctionReturnTypeExtension) {
			if (!$dynamicFunctionReturnTypeExtension->isFunctionSupported($functionReflection)) {
				continue;
			}

			$resolvedType = $dynamicFunctionReturnTypeExtension->getTypeFromFunctionCall(
				$functionReflection,
				$normalizedNode,
				$this,
			);
			if ($resolvedType !== null) {
				return $resolvedType;
			}
		}

		return null;
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

	private function transformVoidToNull(Type $type, Node $node): Type
	{
		if ($node->getAttribute(self::KEEP_VOID_ATTRIBUTE_NAME) === true) {
			return $type;
		}

		return TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
			if ($type instanceof UnionType || $type instanceof IntersectionType) {
				return $traverse($type);
			}

			if ($type->isVoid()->yes()) {
				return new NullType();
			}

			return $type;
		});
	}

	/**
	 * @param callable(Type): ?bool $typeCallback
	 */
	public function issetCheck(Expr $expr, callable $typeCallback, ?bool $result = null): ?bool
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
			$type = $this->getType($expr->var);
			if (!$type->isOffsetAccessible()->yes()) {
				return $result ?? $this->issetCheckUndefined($expr->var);
			}

			$dimType = $this->getType($expr->dim);
			$hasOffsetValue = $type->hasOffsetValueType($dimType);
			if ($hasOffsetValue->no()) {
				return false;
			}

			// If offset cannot be null, store this error message and see if one of the earlier offsets is.
			// E.g. $array['a']['b']['c'] ?? null; is a valid coalesce if a OR b or C might be null.
			if ($hasOffsetValue->yes()) {
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

			if ($propertyReflection->hasNativeType() && !$propertyReflection->isVirtual()->yes()) {
				if (!$this->hasExpressionType($expr)->yes()) {
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
			if (!$type->isOffsetAccessible()->yes()) {
				return $this->issetCheckUndefined($expr->var);
			}

			$dimType = $this->getType($expr->dim);
			$hasOffsetValue = $type->hasOffsetValueType($dimType);

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

	/** @api */
	public function getNativeType(Expr $expr): Type
	{
		return $this->promoteNativeTypes()->getType($expr);
	}

	public function getKeepVoidType(Expr $node): Type
	{
		$clonedNode = clone $node;
		$clonedNode->setAttribute(self::KEEP_VOID_ATTRIBUTE_NAME, true);

		return $this->getType($clonedNode);
	}

	public function doNotTreatPhpDocTypesAsCertain(): Scope
	{
		return $this->promoteNativeTypes();
	}

	private function promoteNativeTypes(): self
	{
		if ($this->nativeTypesPromoted) {
			return $this;
		}

		if ($this->scopeWithPromotedNativeTypes !== null) {
			return $this->scopeWithPromotedNativeTypes;
		}

		return $this->scopeWithPromotedNativeTypes = $this->scopeFactory->create(
			$this->context,
			$this->declareStrictTypes,
			$this->function,
			$this->namespace,
			$this->nativeExpressionTypes,
			[],
			[],
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
			true,
		);
	}

	private function getTypeFromArrayDimFetch(
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

	/** @api */
	public function resolveName(Name $name): string
	{
		$originalClass = (string) $name;
		if ($this->isInClass()) {
			$lowerClass = strtolower($originalClass);
			if (in_array($lowerClass, [
				'self',
				'static',
			], true)) {
				if ($this->inClosureBindScopeClasses !== [] && $this->inClosureBindScopeClasses !== ['static']) {
					return $this->inClosureBindScopeClasses[0];
				}
				return $this->getClassReflection()->getName();
			} elseif ($lowerClass === 'parent') {
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
			if ($this->inClosureBindScopeClasses !== [] && $this->inClosureBindScopeClasses !== ['static']) {
				if ($this->reflectionProvider->hasClass($this->inClosureBindScopeClasses[0])) {
					return new StaticType($this->reflectionProvider->getClass($this->inClosureBindScopeClasses[0]));
				}
			}

			return new StaticType($this->getClassReflection());
		}

		$originalClass = $this->resolveName($name);
		if ($this->isInClass()) {
			if ($this->inClosureBindScopeClasses === [$originalClass]) {
				if ($this->reflectionProvider->hasClass($originalClass)) {
					return new ThisType($this->reflectionProvider->getClass($originalClass));
				}
				return new ObjectType($originalClass);
			}

			$thisType = new ThisType($this->getClassReflection());
			$ancestor = $thisType->getAncestorWithClassName($originalClass);
			if ($ancestor !== null) {
				return $ancestor;
			}
		}

		return new ObjectType($originalClass);
	}

	private function resolveTypeByNameWithLateStaticBinding(Name $class, Node\Identifier $name): TypeWithClassName
	{
		$classType = $this->resolveTypeByName($class);

		if (
			$classType instanceof StaticType
			&& !in_array($class->toLowerString(), ['self', 'static', 'parent'], true)
		) {
			$methodReflectionCandidate = $this->getMethodReflection(
				$classType,
				$name->name,
			);
			if ($methodReflectionCandidate !== null && $methodReflectionCandidate->isStatic()) {
				$classType = $classType->getStaticObjectType();
			}
		}

		return $classType;
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
	public function hasExpressionType(Expr $node): TrinaryLogic
	{
		if ($node instanceof Variable && is_string($node->name)) {
			return $this->hasVariableType($node->name);
		}

		$exprString = $this->getNodeKey($node);
		if (!isset($this->expressionTypes[$exprString])) {
			return TrinaryLogic::createNo();
		}
		return $this->expressionTypes[$exprString]->getCertainty();
	}

	/**
	 * @param MethodReflection|FunctionReflection|null $reflection
	 */
	public function pushInFunctionCall($reflection, ?ParameterReflection $parameter): self
	{
		$stack = $this->inFunctionCallsStack;
		$stack[] = [$reflection, $parameter];

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$this->expressionTypes,
			$this->nativeExpressionTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$stack,
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	public function popInFunctionCall(): self
	{
		$stack = $this->inFunctionCallsStack;
		array_pop($stack);

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$this->expressionTypes,
			$this->nativeExpressionTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$stack,
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	/** @api */
	public function isInClassExists(string $className): bool
	{
		foreach ($this->inFunctionCallsStack as [$inFunctionCall]) {
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

		return $this->getType($expr)->isTrue()->yes();
	}

	public function getFunctionCallStack(): array
	{
		return array_values(array_filter(
			array_map(static fn ($values) => $values[0], $this->inFunctionCallsStack),
			static fn (FunctionReflection|MethodReflection|null $reflection) => $reflection !== null,
		));
	}

	public function getFunctionCallStackWithParameters(): array
	{
		return array_values(array_filter(
			$this->inFunctionCallsStack,
			static fn ($item) => $item[0] !== null,
		));
	}

	/** @api */
	public function isInFunctionExists(string $functionName): bool
	{
		$expr = new FuncCall(new FullyQualified('function_exists'), [
			new Arg(new String_(ltrim($functionName, '\\'))),
		]);

		return $this->getType($expr)->isTrue()->yes();
	}

	/** @api */
	public function enterClass(ClassReflection $classReflection): self
	{
		$thisHolder = ExpressionTypeHolder::createYes(new Variable('this'), new ThisType($classReflection));
		$constantTypes = $this->getConstantTypes();
		$constantTypes['$this'] = $thisHolder;
		$nativeConstantTypes = $this->getNativeConstantTypes();
		$nativeConstantTypes['$this'] = $thisHolder;

		return $this->scopeFactory->create(
			$this->context->enterClass($classReflection),
			$this->isDeclareStrictTypes(),
			null,
			$this->getNamespace(),
			$constantTypes,
			$nativeConstantTypes,
			[],
			[],
			null,
			true,
			[],
			[],
			[],
			false,
			$classReflection->isAnonymous() ? $this : null,
		);
	}

	public function enterTrait(ClassReflection $traitReflection): self
	{
		$namespace = null;
		$traitName = $traitReflection->getName();
		$traitNameParts = explode('\\', $traitName);
		if (count($traitNameParts) > 1) {
			$namespace = implode('\\', array_slice($traitNameParts, 0, -1));
		}
		return $this->scopeFactory->create(
			$this->context->enterTrait($traitReflection),
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$namespace,
			$this->expressionTypes,
			$this->nativeExpressionTypes,
			[],
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
		);
	}

	/**
	 * @api
	 * @param Type[] $phpDocParameterTypes
	 * @param Type[] $parameterOutTypes
	 * @param array<string, bool> $immediatelyInvokedCallableParameters
	 * @param array<string, Type> $phpDocClosureThisTypeParameters
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
		bool $acceptsNamedArguments = true,
		?Assertions $asserts = null,
		?Type $selfOutType = null,
		?string $phpDocComment = null,
		array $parameterOutTypes = [],
		array $immediatelyInvokedCallableParameters = [],
		array $phpDocClosureThisTypeParameters = [],
		bool $isConstructor = false,
	): self
	{
		if (!$this->isInClass()) {
			throw new ShouldNotHappenException();
		}

		return $this->enterFunctionLike(
			new PhpMethodFromParserNodeReflection(
				$this->getClassReflection(),
				$classMethod,
				null,
				$this->getFile(),
				$templateTypeMap,
				$this->getRealParameterTypes($classMethod),
				array_map(fn (Type $type): Type => $this->transformStaticType(TemplateTypeHelper::toArgument($type)), $phpDocParameterTypes),
				$this->getRealParameterDefaultValues($classMethod),
				$this->getParameterAttributes($classMethod),
				$this->transformStaticType($this->getFunctionType($classMethod->returnType, false, false)),
				$phpDocReturnType !== null ? $this->transformStaticType(TemplateTypeHelper::toArgument($phpDocReturnType)) : null,
				$throwType !== null ? $this->transformStaticType(TemplateTypeHelper::toArgument($throwType)) : null,
				$deprecatedDescription,
				$isDeprecated,
				$isInternal,
				$isFinal,
				$isPure,
				$acceptsNamedArguments,
				$asserts ?? Assertions::createEmpty(),
				$selfOutType,
				$phpDocComment,
				array_map(fn (Type $type): Type => $this->transformStaticType(TemplateTypeHelper::toArgument($type)), $parameterOutTypes),
				$immediatelyInvokedCallableParameters,
				array_map(fn (Type $type): Type => $this->transformStaticType(TemplateTypeHelper::toArgument($type)), $phpDocClosureThisTypeParameters),
				$isConstructor,
				$this->attributeReflectionFactory->fromAttrGroups($classMethod->attrGroups, InitializerExprContext::fromStubParameter($this->getClassReflection()->getName(), $this->getFile(), $classMethod)),
			),
			!$classMethod->isStatic(),
		);
	}

	/**
	 * @param Type[] $phpDocParameterTypes
	 */
	public function enterPropertyHook(
		Node\PropertyHook $hook,
		string $propertyName,
		Identifier|Name|ComplexType|null $nativePropertyTypeNode,
		?Type $phpDocPropertyType,
		array $phpDocParameterTypes,
		?Type $throwType,
		?string $deprecatedDescription,
		bool $isDeprecated,
		?string $phpDocComment,
	): self
	{
		if (!$this->isInClass()) {
			throw new ShouldNotHappenException();
		}

		$phpDocParameterTypes = array_map(fn (Type $type): Type => $this->transformStaticType(TemplateTypeHelper::toArgument($type)), $phpDocParameterTypes);

		$hookName = $hook->name->toLowerString();
		if ($hookName === 'set') {
			if ($hook->params === []) {
				$hook = clone $hook;
				$hook->params = [
					new Node\Param(new Variable('value'), type: $nativePropertyTypeNode),
				];
			}

			$firstParam = $hook->params[0] ?? null;
			if (
				$firstParam !== null
				&& $phpDocPropertyType !== null
				&& $firstParam->var instanceof Variable
				&& is_string($firstParam->var->name)
			) {
				$valueParamPhpDocType = $phpDocParameterTypes[$firstParam->var->name] ?? null;
				if ($valueParamPhpDocType === null) {
					$phpDocParameterTypes[$firstParam->var->name] = $this->transformStaticType(TemplateTypeHelper::toArgument($phpDocPropertyType));
				}
			}

			$realReturnType = new VoidType();
			$phpDocReturnType = null;
		} elseif ($hookName === 'get') {
			$realReturnType = $this->getFunctionType($nativePropertyTypeNode, false, false);
			$phpDocReturnType = $phpDocPropertyType !== null ? $this->transformStaticType(TemplateTypeHelper::toArgument($phpDocPropertyType)) : null;
		} else {
			throw new ShouldNotHappenException();
		}

		$realParameterTypes = $this->getRealParameterTypes($hook);

		return $this->enterFunctionLike(
			new PhpMethodFromParserNodeReflection(
				$this->getClassReflection(),
				$hook,
				$propertyName,
				$this->getFile(),
				TemplateTypeMap::createEmpty(),
				$realParameterTypes,
				$phpDocParameterTypes,
				[],
				$this->getParameterAttributes($hook),
				$realReturnType,
				$phpDocReturnType,
				$throwType !== null ? $this->transformStaticType(TemplateTypeHelper::toArgument($throwType)) : null,
				$deprecatedDescription,
				$isDeprecated,
				false,
				false,
				false,
				true,
				Assertions::createEmpty(),
				null,
				$phpDocComment,
				[],
				[],
				[],
				false,
				$this->attributeReflectionFactory->fromAttrGroups($hook->attrGroups, InitializerExprContext::fromStubParameter($this->getClassReflection()->getName(), $this->getFile(), $hook)),
			),
			true,
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
				if ($classReflection->isFinal() && !$type instanceof ThisType) {
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
				$this->isParameterValueNullable($parameter) && $parameter->flags === 0,
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
	 * @return array<string, list<AttributeReflection>>
	 */
	private function getParameterAttributes(ClassMethod|Function_|PropertyHook $functionLike): array
	{
		$parameterAttributes = [];
		$className = null;
		if ($this->isInClass()) {
			$className = $this->getClassReflection()->getName();
		}
		foreach ($functionLike->getParams() as $parameter) {
			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new ShouldNotHappenException();
			}

			$parameterAttributes[$parameter->var->name] = $this->attributeReflectionFactory->fromAttrGroups($parameter->attrGroups, InitializerExprContext::fromStubParameter($className, $this->getFile(), $functionLike));
		}

		return $parameterAttributes;
	}

	/**
	 * @api
	 * @param Type[] $phpDocParameterTypes
	 * @param Type[] $parameterOutTypes
	 * @param array<string, bool> $immediatelyInvokedCallableParameters
	 * @param array<string, Type> $phpDocClosureThisTypeParameters
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
		?bool $isPure = null,
		bool $acceptsNamedArguments = true,
		?Assertions $asserts = null,
		?string $phpDocComment = null,
		array $parameterOutTypes = [],
		array $immediatelyInvokedCallableParameters = [],
		array $phpDocClosureThisTypeParameters = [],
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
				$this->getParameterAttributes($function),
				$this->getFunctionType($function->returnType, $function->returnType === null, false),
				$phpDocReturnType !== null ? TemplateTypeHelper::toArgument($phpDocReturnType) : null,
				$throwType,
				$deprecatedDescription,
				$isDeprecated,
				$isInternal,
				$isPure,
				$acceptsNamedArguments,
				$asserts ?? Assertions::createEmpty(),
				$phpDocComment,
				array_map(static fn (Type $type): Type => TemplateTypeHelper::toArgument($type), $parameterOutTypes),
				$immediatelyInvokedCallableParameters,
				$phpDocClosureThisTypeParameters,
				$this->attributeReflectionFactory->fromAttrGroups($function->attrGroups, InitializerExprContext::fromStubParameter(null, $this->getFile(), $function)),
			),
			false,
		);
	}

	private function enterFunctionLike(
		PhpFunctionFromParserNodeReflection $functionReflection,
		bool $preserveConstructorScope,
	): self
	{
		$parametersByName = [];

		foreach ($functionReflection->getParameters() as $parameter) {
			$parametersByName[$parameter->getName()] = $parameter;
		}

		$expressionTypes = [];
		$nativeExpressionTypes = [];
		$conditionalTypes = [];

		if ($preserveConstructorScope) {
			$expressionTypes = $this->rememberConstructorExpressions($this->expressionTypes);
			$nativeExpressionTypes = $this->rememberConstructorExpressions($this->nativeExpressionTypes);
		}

		foreach ($functionReflection->getParameters() as $parameter) {
			$parameterType = $parameter->getType();

			if ($parameterType instanceof ConditionalTypeForParameter) {
				$targetParameterName = substr($parameterType->getParameterName(), 1);
				if (array_key_exists($targetParameterName, $parametersByName)) {
					$targetParameter = $parametersByName[$targetParameterName];

					$ifType = $parameterType->isNegated() ? $parameterType->getElse() : $parameterType->getIf();
					$elseType = $parameterType->isNegated() ? $parameterType->getIf() : $parameterType->getElse();

					$holder = new ConditionalExpressionHolder([
						$parameterType->getParameterName() => ExpressionTypeHolder::createYes(new Variable($targetParameterName), TypeCombinator::intersect($targetParameter->getType(), $parameterType->getTarget())),
					], new ExpressionTypeHolder(new Variable($parameter->getName()), $ifType, TrinaryLogic::createYes()));
					$conditionalTypes['$' . $parameter->getName()][$holder->getKey()] = $holder;

					$holder = new ConditionalExpressionHolder([
						$parameterType->getParameterName() => ExpressionTypeHolder::createYes(new Variable($targetParameterName), TypeCombinator::remove($targetParameter->getType(), $parameterType->getTarget())),
					], new ExpressionTypeHolder(new Variable($parameter->getName()), $elseType, TrinaryLogic::createYes()));
					$conditionalTypes['$' . $parameter->getName()][$holder->getKey()] = $holder;
				}
			}

			$paramExprString = '$' . $parameter->getName();
			if ($parameter->isVariadic()) {
				if (!$this->getPhpVersion()->supportsNamedArguments()->no() && $functionReflection->acceptsNamedArguments()->yes()) {
					$parameterType = new ArrayType(new UnionType([new IntegerType(), new StringType()]), $parameterType);
				} else {
					$parameterType = TypeCombinator::intersect(new ArrayType(new IntegerType(), $parameterType), new AccessoryArrayListType());
				}
			}
			$parameterNode = new Variable($parameter->getName());
			$expressionTypes[$paramExprString] = ExpressionTypeHolder::createYes($parameterNode, $parameterType);

			$parameterOriginalValueExpr = new ParameterVariableOriginalValueExpr($parameter->getName());
			$parameterOriginalValueExprString = $this->getNodeKey($parameterOriginalValueExpr);
			$expressionTypes[$parameterOriginalValueExprString] = ExpressionTypeHolder::createYes($parameterOriginalValueExpr, $parameterType);

			$nativeParameterType = $parameter->getNativeType();
			if ($parameter->isVariadic()) {
				if (!$this->getPhpVersion()->supportsNamedArguments()->no() && $functionReflection->acceptsNamedArguments()->yes()) {
					$nativeParameterType = new ArrayType(new UnionType([new IntegerType(), new StringType()]), $nativeParameterType);
				} else {
					$nativeParameterType = TypeCombinator::intersect(new ArrayType(new IntegerType(), $nativeParameterType), new AccessoryArrayListType());
				}
			}
			$nativeExpressionTypes[$paramExprString] = ExpressionTypeHolder::createYes($parameterNode, $nativeParameterType);
			$nativeExpressionTypes[$parameterOriginalValueExprString] = ExpressionTypeHolder::createYes($parameterOriginalValueExpr, $nativeParameterType);
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$functionReflection,
			$this->getNamespace(),
			array_merge($this->getConstantTypes(), $expressionTypes),
			array_merge($this->getNativeConstantTypes(), $nativeExpressionTypes),
			$conditionalTypes,
		);
	}

	/** @api */
	public function enterNamespace(string $namespaceName): self
	{
		return $this->scopeFactory->create(
			$this->context->beginFile(),
			$this->isDeclareStrictTypes(),
			null,
			$namespaceName,
		);
	}

	/**
	 * @param list<string> $scopeClasses
	 */
	public function enterClosureBind(?Type $thisType, ?Type $nativeThisType, array $scopeClasses): self
	{
		$expressionTypes = $this->expressionTypes;
		if ($thisType !== null) {
			$expressionTypes['$this'] = ExpressionTypeHolder::createYes(new Variable('this'), $thisType);
		} else {
			unset($expressionTypes['$this']);
		}

		$nativeExpressionTypes = $this->nativeExpressionTypes;
		if ($nativeThisType !== null) {
			$nativeExpressionTypes['$this'] = ExpressionTypeHolder::createYes(new Variable('this'), $nativeThisType);
		} else {
			unset($nativeExpressionTypes['$this']);
		}

		if ($scopeClasses === ['static'] && $this->isInClass()) {
			$scopeClasses = [$this->getClassReflection()->getName()];
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$expressionTypes,
			$nativeExpressionTypes,
			$this->conditionalExpressions,
			$scopeClasses,
			$this->anonymousFunctionReflection,
		);
	}

	public function restoreOriginalScopeAfterClosureBind(self $originalScope): self
	{
		$expressionTypes = $this->expressionTypes;
		if (isset($originalScope->expressionTypes['$this'])) {
			$expressionTypes['$this'] = $originalScope->expressionTypes['$this'];
		} else {
			unset($expressionTypes['$this']);
		}

		$nativeExpressionTypes = $this->nativeExpressionTypes;
		if (isset($originalScope->nativeExpressionTypes['$this'])) {
			$nativeExpressionTypes['$this'] = $originalScope->nativeExpressionTypes['$this'];
		} else {
			unset($nativeExpressionTypes['$this']);
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$expressionTypes,
			$nativeExpressionTypes,
			$this->conditionalExpressions,
			$originalScope->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
		);
	}

	public function restoreThis(self $restoreThisScope): self
	{
		$expressionTypes = $this->expressionTypes;
		$nativeExpressionTypes = $this->nativeExpressionTypes;

		if ($restoreThisScope->isInClass()) {
			$nodeFinder = new NodeFinder();
			$cb = static fn ($expr) => $expr instanceof Variable && $expr->name === 'this';
			foreach ($restoreThisScope->expressionTypes as $exprString => $expressionTypeHolder) {
				$expr = $expressionTypeHolder->getExpr();
				$thisExpr = $nodeFinder->findFirst([$expr], $cb);
				if ($thisExpr === null) {
					continue;
				}

				$expressionTypes[$exprString] = $expressionTypeHolder;
			}

			foreach ($restoreThisScope->nativeExpressionTypes as $exprString => $expressionTypeHolder) {
				$expr = $expressionTypeHolder->getExpr();
				$thisExpr = $nodeFinder->findFirst([$expr], $cb);
				if ($thisExpr === null) {
					continue;
				}

				$nativeExpressionTypes[$exprString] = $expressionTypeHolder;
			}
		} else {
			unset($expressionTypes['$this']);
			unset($nativeExpressionTypes['$this']);
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$expressionTypes,
			$nativeExpressionTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			[],
			[],
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	public function enterClosureCall(Type $thisType, Type $nativeThisType): self
	{
		$expressionTypes = $this->expressionTypes;
		$expressionTypes['$this'] = ExpressionTypeHolder::createYes(new Variable('this'), $thisType);

		$nativeExpressionTypes = $this->nativeExpressionTypes;
		$nativeExpressionTypes['$this'] = ExpressionTypeHolder::createYes(new Variable('this'), $nativeThisType);

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$expressionTypes,
			$nativeExpressionTypes,
			$this->conditionalExpressions,
			$thisType->getObjectClassNames(),
			$this->anonymousFunctionReflection,
		);
	}

	/** @api */
	public function isInClosureBind(): bool
	{
		return $this->inClosureBindScopeClasses !== [];
	}

	/**
	 * @api
	 * @param ParameterReflection[]|null $callableParameters
	 */
	public function enterAnonymousFunction(
		Expr\Closure $closure,
		?array $callableParameters,
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
			$scope->getFunction(),
			$scope->getNamespace(),
			$scope->expressionTypes,
			$scope->nativeExpressionTypes,
			[],
			$scope->inClosureBindScopeClasses,
			$anonymousFunctionReflection,
			true,
			[],
			[],
			$this->inFunctionCallsStack,
			false,
			$this,
			$this->nativeTypesPromoted,
		);
	}

	/**
	 * @param ParameterReflection[]|null $callableParameters
	 */
	private function enterAnonymousFunctionWithoutReflection(
		Expr\Closure $closure,
		?array $callableParameters,
	): self
	{
		$expressionTypes = [];
		$nativeTypes = [];
		foreach ($closure->params as $i => $parameter) {
			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new ShouldNotHappenException();
			}
			$paramExprString = sprintf('$%s', $parameter->var->name);
			$isNullable = $this->isParameterValueNullable($parameter);
			$parameterType = $this->getFunctionType($parameter->type, $isNullable, $parameter->variadic);
			if ($callableParameters !== null) {
				if (isset($callableParameters[$i])) {
					$parameterType = self::intersectButNotNever($parameterType, $callableParameters[$i]->getType());
				} elseif (count($callableParameters) > 0) {
					$lastParameter = array_last($callableParameters);
					if ($lastParameter->isVariadic()) {
						$parameterType = self::intersectButNotNever($parameterType, $lastParameter->getType());
					} else {
						$parameterType = self::intersectButNotNever($parameterType, new MixedType());
					}
				} else {
					$parameterType = self::intersectButNotNever($parameterType, new MixedType());
				}
			}
			$holder = ExpressionTypeHolder::createYes($parameter->var, $parameterType);
			$expressionTypes[$paramExprString] = $holder;
			$nativeTypes[$paramExprString] = $holder;
		}

		$nonRefVariableNames = [];
		foreach ($closure->uses as $use) {
			if (!is_string($use->var->name)) {
				throw new ShouldNotHappenException();
			}
			$variableName = $use->var->name;
			$paramExprString = '$' . $use->var->name;
			if ($use->byRef) {
				$holder = ExpressionTypeHolder::createYes($use->var, new MixedType());
				$expressionTypes[$paramExprString] = $holder;
				$nativeTypes[$paramExprString] = $holder;
				continue;
			}
			$nonRefVariableNames[$variableName] = true;
			if ($this->hasVariableType($variableName)->no()) {
				$variableType = new ErrorType();
				$variableNativeType = new ErrorType();
			} else {
				$variableType = $this->getVariableType($variableName);
				$variableNativeType = $this->getNativeType($use->var);
			}
			$expressionTypes[$paramExprString] = ExpressionTypeHolder::createYes($use->var, $variableType);
			$nativeTypes[$paramExprString] = ExpressionTypeHolder::createYes($use->var, $variableNativeType);
		}

		$nonStaticExpressions = $this->invalidateStaticExpressions($this->expressionTypes);
		foreach ($nonStaticExpressions as $exprString => $typeHolder) {
			$expr = $typeHolder->getExpr();

			if ($expr instanceof Variable) {
				continue;
			}

			$variables = (new NodeFinder())->findInstanceOf([$expr], Variable::class);
			if ($variables === [] && !$this->expressionTypeIsUnchangeable($typeHolder)) {
				continue;
			}

			foreach ($variables as $variable) {
				if (!is_string($variable->name)) {
					continue 2;
				}
				if (!array_key_exists($variable->name, $nonRefVariableNames)) {
					continue 2;
				}
			}

			$expressionTypes[$exprString] = $typeHolder;
		}

		if ($this->hasVariableType('this')->yes() && !$closure->static) {
			$node = new Variable('this');
			$expressionTypes['$this'] = ExpressionTypeHolder::createYes($node, $this->getType($node));
			$nativeTypes['$this'] = ExpressionTypeHolder::createYes($node, $this->getNativeType($node));

			if ($this->phpVersion->supportsReadOnlyProperties()) {
				foreach ($nonStaticExpressions as $exprString => $typeHolder) {
					$expr = $typeHolder->getExpr();

					if (!$expr instanceof PropertyFetch) {
						continue;
					}

					if (!$this->isReadonlyPropertyFetch($expr, true)) {
						continue;
					}

					$expressionTypes[$exprString] = $typeHolder;
				}
			}
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			array_merge($this->getConstantTypes(), $expressionTypes),
			array_merge($this->getNativeConstantTypes(), $nativeTypes),
			[],
			$this->inClosureBindScopeClasses,
			new ClosureType(),
			true,
			[],
			[],
			[],
			false,
			$this,
			$this->nativeTypesPromoted,
		);
	}

	private function expressionTypeIsUnchangeable(ExpressionTypeHolder $typeHolder): bool
	{
		$expr = $typeHolder->getExpr();
		$type = $typeHolder->getType();

		return $expr instanceof FuncCall
			&& !$expr->isFirstClassCallable()
			&& $expr->name instanceof FullyQualified
			&& $expr->name->toLowerString() === 'function_exists'
			&& isset($expr->getArgs()[0])
			&& count($this->getType($expr->getArgs()[0]->value)->getConstantStrings()) === 1
			&& $type->isTrue()->yes();
	}

	/**
	 * @param array<string, ExpressionTypeHolder> $expressionTypes
	 * @return array<string, ExpressionTypeHolder>
	 */
	private function invalidateStaticExpressions(array $expressionTypes): array
	{
		$filteredExpressionTypes = [];
		$nodeFinder = new NodeFinder();
		foreach ($expressionTypes as $exprString => $expressionType) {
			$staticExpression = $nodeFinder->findFirst(
				[$expressionType->getExpr()],
				static fn ($node) => $node instanceof Expr\StaticCall || $node instanceof Expr\StaticPropertyFetch,
			);
			if ($staticExpression !== null) {
				continue;
			}
			$filteredExpressionTypes[$exprString] = $expressionType;
		}
		return $filteredExpressionTypes;
	}

	/**
	 * @api
	 * @param ParameterReflection[]|null $callableParameters
	 */
	public function enterArrowFunction(Expr\ArrowFunction $arrowFunction, ?array $callableParameters): self
	{
		$anonymousFunctionReflection = $this->getType($arrowFunction);
		if (!$anonymousFunctionReflection instanceof ClosureType) {
			throw new ShouldNotHappenException();
		}

		$scope = $this->enterArrowFunctionWithoutReflection($arrowFunction, $callableParameters);

		return $this->scopeFactory->create(
			$scope->context,
			$scope->isDeclareStrictTypes(),
			$scope->getFunction(),
			$scope->getNamespace(),
			$scope->expressionTypes,
			$scope->nativeExpressionTypes,
			$scope->conditionalExpressions,
			$scope->inClosureBindScopeClasses,
			$anonymousFunctionReflection,
			true,
			[],
			[],
			$this->inFunctionCallsStack,
			$scope->afterExtractCall,
			$scope->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	/**
	 * @param ParameterReflection[]|null $callableParameters
	 */
	private function enterArrowFunctionWithoutReflection(Expr\ArrowFunction $arrowFunction, ?array $callableParameters): self
	{
		$arrowFunctionScope = $this;
		foreach ($arrowFunction->params as $i => $parameter) {
			if ($parameter->type === null) {
				$parameterType = new MixedType();
			} else {
				$isNullable = $this->isParameterValueNullable($parameter);
				$parameterType = $this->getFunctionType($parameter->type, $isNullable, $parameter->variadic);
			}

			if ($callableParameters !== null) {
				if (isset($callableParameters[$i])) {
					$parameterType = self::intersectButNotNever($parameterType, $callableParameters[$i]->getType());
				} elseif (count($callableParameters) > 0) {
					$lastParameter = array_last($callableParameters);
					if ($lastParameter->isVariadic()) {
						$parameterType = self::intersectButNotNever($parameterType, $lastParameter->getType());
					} else {
						$parameterType = self::intersectButNotNever($parameterType, new MixedType());
					}
				} else {
					$parameterType = self::intersectButNotNever($parameterType, new MixedType());
				}
			}

			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new ShouldNotHappenException();
			}
			$arrowFunctionScope = $arrowFunctionScope->assignVariable($parameter->var->name, $parameterType, $parameterType, TrinaryLogic::createYes());
		}

		if ($arrowFunction->static) {
			$arrowFunctionScope = $arrowFunctionScope->invalidateExpression(new Variable('this'));
		}

		return $this->scopeFactory->create(
			$arrowFunctionScope->context,
			$this->isDeclareStrictTypes(),
			$arrowFunctionScope->getFunction(),
			$arrowFunctionScope->getNamespace(),
			$this->invalidateStaticExpressions($arrowFunctionScope->expressionTypes),
			$arrowFunctionScope->nativeExpressionTypes,
			$arrowFunctionScope->conditionalExpressions,
			$arrowFunctionScope->inClosureBindScopeClasses,
			new ClosureType(),
			true,
			[],
			[],
			[],
			$arrowFunctionScope->afterExtractCall,
			$arrowFunctionScope->parentScope,
			$this->nativeTypesPromoted,
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
	 * @api
	 * @param Node\Name|Node\Identifier|Node\ComplexType|null $type
	 */
	public function getFunctionType($type, bool $isNullable, bool $isVariadic): Type
	{
		if ($isVariadic) {
			if (!$this->getPhpVersion()->supportsNamedArguments()->no()) {
				return new ArrayType(new UnionType([new IntegerType(), new StringType()]), $this->getFunctionType(
					$type,
					$isNullable,
					false,
				));
			}

			return TypeCombinator::intersect(new ArrayType(new IntegerType(), $this->getFunctionType(
				$type,
				$isNullable,
				false,
			)), new AccessoryArrayListType());
		}
		return $this->initializerExprTypeResolver->getFunctionType($type, $isNullable, false, InitializerExprContext::fromScope($this));
	}

	private static function intersectButNotNever(Type $nativeType, Type $inferredType): Type
	{
		if ($nativeType->isSuperTypeOf($inferredType)->no()) {
			return $nativeType;
		}

		$result = TypeCombinator::intersect($nativeType, $inferredType);
		if (TypeCombinator::containsNull($nativeType)) {
			return TypeCombinator::addNull($result);
		}

		return $result;
	}

	public function enterMatch(Expr\Match_ $expr): self
	{
		if ($expr->cond instanceof Variable) {
			return $this;
		}
		if ($expr->cond instanceof AlwaysRememberedExpr) {
			$cond = $expr->cond->expr;
		} else {
			$cond = $expr->cond;
		}

		$type = $this->getType($cond);
		$nativeType = $this->getNativeType($cond);
		$condExpr = new AlwaysRememberedExpr($cond, $type, $nativeType);
		$expr->cond = $condExpr;

		return $this->assignExpression($condExpr, $type, $nativeType);
	}

	public function enterForeach(self $originalScope, Expr $iteratee, string $valueName, ?string $keyName, bool $valueByRef): self
	{
		$iterateeType = $originalScope->getType($iteratee);
		$nativeIterateeType = $originalScope->getNativeType($iteratee);
		$valueType = $originalScope->getIterableValueType($iterateeType);
		$nativeValueType = $originalScope->getIterableValueType($nativeIterateeType);
		$scope = $this->assignVariable(
			$valueName,
			$valueType,
			$nativeValueType,
			TrinaryLogic::createYes(),
		);
		if ($valueByRef && $iterateeType->isArray()->yes() && $iterateeType->isConstantArray()->no()) {
			$scope = $scope->assignExpression(
				new IntertwinedVariableByReferenceWithExpr($valueName, $iteratee, new SetOffsetValueTypeExpr(
					$iteratee,
					new GetIterableKeyTypeExpr($iteratee),
					new Variable($valueName),
				)),
				$valueType,
				$nativeValueType,
			);
		}
		if ($keyName !== null) {
			$scope = $scope->enterForeachKey($originalScope, $iteratee, $keyName);

			if ($valueByRef && $iterateeType->isArray()->yes() && $iterateeType->isConstantArray()->no()) {
				$scope = $scope->assignExpression(
					new IntertwinedVariableByReferenceWithExpr($valueName, new Expr\ArrayDimFetch($iteratee, new Variable($keyName)), new Variable($valueName)),
					$valueType,
					$nativeValueType,
				);
			}
		}

		return $scope;
	}

	public function enterForeachKey(self $originalScope, Expr $iteratee, string $keyName): self
	{
		$iterateeType = $originalScope->getType($iteratee);
		$nativeIterateeType = $originalScope->getNativeType($iteratee);

		$keyType = $originalScope->getIterableKeyType($iterateeType);
		$nativeKeyType = $originalScope->getIterableKeyType($nativeIterateeType);

		$scope = $this->assignVariable(
			$keyName,
			$keyType,
			$nativeKeyType,
			TrinaryLogic::createYes(),
		);

		$originalForeachKeyExpr = new OriginalForeachKeyExpr($keyName);
		$scope = $scope->assignExpression($originalForeachKeyExpr, $keyType, $nativeKeyType);
		if ($iterateeType->isArray()->yes()) {
			$scope = $scope->assignExpression(
				new Expr\ArrayDimFetch($iteratee, new Variable($keyName)),
				$originalScope->getIterableValueType($iterateeType),
				$originalScope->getIterableValueType($nativeIterateeType),
			);
		}

		return $scope;
	}

	public function enterCatchType(Type $catchType, ?string $variableName): self
	{
		if ($variableName === null) {
			return $this;
		}

		return $this->assignVariable(
			$variableName,
			TypeCombinator::intersect($catchType, new ObjectType(Throwable::class)),
			TypeCombinator::intersect($catchType, new ObjectType(Throwable::class)),
			TrinaryLogic::createYes(),
		);
	}

	public function enterExpressionAssign(Expr $expr): self
	{
		$exprString = $this->getNodeKey($expr);
		$currentlyAssignedExpressions = $this->currentlyAssignedExpressions;
		$currentlyAssignedExpressions[$exprString] = true;

		$scope = $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$this->expressionTypes,
			$this->nativeExpressionTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			[],
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
		$scope->resolvedTypes = $this->resolvedTypes;
		$scope->truthyScopes = $this->truthyScopes;
		$scope->falseyScopes = $this->falseyScopes;

		return $scope;
	}

	public function exitExpressionAssign(Expr $expr): self
	{
		$exprString = $this->getNodeKey($expr);
		$currentlyAssignedExpressions = $this->currentlyAssignedExpressions;
		unset($currentlyAssignedExpressions[$exprString]);

		$scope = $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$this->expressionTypes,
			$this->nativeExpressionTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			[],
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
		$scope->resolvedTypes = $this->resolvedTypes;
		$scope->truthyScopes = $this->truthyScopes;
		$scope->falseyScopes = $this->falseyScopes;

		return $scope;
	}

	/** @api */
	public function isInExpressionAssign(Expr $expr): bool
	{
		$exprString = $this->getNodeKey($expr);
		return array_key_exists($exprString, $this->currentlyAssignedExpressions);
	}

	public function setAllowedUndefinedExpression(Expr $expr): self
	{
		if ($expr instanceof Expr\StaticPropertyFetch) {
			return $this;
		}

		$exprString = $this->getNodeKey($expr);
		$currentlyAllowedUndefinedExpressions = $this->currentlyAllowedUndefinedExpressions;
		$currentlyAllowedUndefinedExpressions[$exprString] = true;

		$scope = $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$this->expressionTypes,
			$this->nativeExpressionTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$currentlyAllowedUndefinedExpressions,
			[],
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
		$scope->resolvedTypes = $this->resolvedTypes;
		$scope->truthyScopes = $this->truthyScopes;
		$scope->falseyScopes = $this->falseyScopes;

		return $scope;
	}

	public function unsetAllowedUndefinedExpression(Expr $expr): self
	{
		$exprString = $this->getNodeKey($expr);
		$currentlyAllowedUndefinedExpressions = $this->currentlyAllowedUndefinedExpressions;
		unset($currentlyAllowedUndefinedExpressions[$exprString]);

		$scope = $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$this->expressionTypes,
			$this->nativeExpressionTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$currentlyAllowedUndefinedExpressions,
			[],
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
		$scope->resolvedTypes = $this->resolvedTypes;
		$scope->truthyScopes = $this->truthyScopes;
		$scope->falseyScopes = $this->falseyScopes;

		return $scope;
	}

	/** @api */
	public function isUndefinedExpressionAllowed(Expr $expr): bool
	{
		$exprString = $this->getNodeKey($expr);
		return array_key_exists($exprString, $this->currentlyAllowedUndefinedExpressions);
	}

	public function assignVariable(string $variableName, Type $type, Type $nativeType, TrinaryLogic $certainty): self
	{
		$node = new Variable($variableName);
		$scope = $this->assignExpression($node, $type, $nativeType);
		if ($certainty->no()) {
			throw new ShouldNotHappenException();
		} elseif (!$certainty->yes()) {
			$exprString = '$' . $variableName;
			$scope->expressionTypes[$exprString] = new ExpressionTypeHolder($node, $type, $certainty);
			$scope->nativeExpressionTypes[$exprString] = new ExpressionTypeHolder($node, $nativeType, $certainty);
		}

		foreach ($scope->expressionTypes as $expressionType) {
			if (!$expressionType->getExpr() instanceof IntertwinedVariableByReferenceWithExpr) {
				continue;
			}
			if (!$expressionType->getCertainty()->yes()) {
				continue;
			}
			if ($expressionType->getExpr()->getVariableName() !== $variableName) {
				continue;
			}

			$has = $scope->hasExpressionType($expressionType->getExpr()->getExpr());
			if (
				$expressionType->getExpr()->getExpr() instanceof Variable
				&& is_string($expressionType->getExpr()->getExpr()->name)
				&& !$has->no()
			) {
				$scope = $scope->assignVariable(
					$expressionType->getExpr()->getExpr()->name,
					$scope->getType($expressionType->getExpr()->getAssignedExpr()),
					$scope->getNativeType($expressionType->getExpr()->getAssignedExpr()),
					$has,
				);
			} else {
				$scope = $scope->assignExpression(
					$expressionType->getExpr()->getExpr(),
					$scope->getType($expressionType->getExpr()->getAssignedExpr()),
					$scope->getNativeType($expressionType->getExpr()->getAssignedExpr()),
				);
			}

		}

		return $scope;
	}

	private function unsetExpression(Expr $expr): self
	{
		$scope = $this;
		if ($expr instanceof Expr\ArrayDimFetch && $expr->dim !== null) {
			$exprVarType = $scope->getType($expr->var);
			$dimType = $scope->getType($expr->dim);
			$unsetType = $exprVarType->unsetOffset($dimType);
			$exprVarNativeType = $scope->getNativeType($expr->var);
			$dimNativeType = $scope->getNativeType($expr->dim);
			$unsetNativeType = $exprVarNativeType->unsetOffset($dimNativeType);
			$scope = $scope->assignExpression($expr->var, $unsetType, $unsetNativeType)->invalidateExpression(
				new FuncCall(new FullyQualified('count'), [new Arg($expr->var)]),
			)->invalidateExpression(
				new FuncCall(new FullyQualified('sizeof'), [new Arg($expr->var)]),
			)->invalidateExpression(
				new FuncCall(new Name('count'), [new Arg($expr->var)]),
			)->invalidateExpression(
				new FuncCall(new Name('sizeof'), [new Arg($expr->var)]),
			);

			if ($expr->var instanceof Expr\ArrayDimFetch && $expr->var->dim !== null) {
				$scope = $scope->assignExpression(
					$expr->var->var,
					$this->getType($expr->var->var)->setOffsetValueType(
						$scope->getType($expr->var->dim),
						$scope->getType($expr->var),
					),
					$this->getNativeType($expr->var->var)->setOffsetValueType(
						$scope->getNativeType($expr->var->dim),
						$scope->getNativeType($expr->var),
					),
				);
			}
		}

		return $scope->invalidateExpression($expr);
	}

	public function specifyExpressionType(Expr $expr, Type $type, Type $nativeType, TrinaryLogic $certainty): self
	{
		if ($expr instanceof ConstFetch) {
			$loweredConstName = strtolower($expr->name->toString());
			if (in_array($loweredConstName, ['true', 'false', 'null'], true)) {
				return $this;
			}
		}

		if ($expr instanceof FuncCall && $expr->name instanceof Name && $type->isFalse()->yes()) {
			$functionName = $this->reflectionProvider->resolveFunctionName($expr->name, $this);
			if ($functionName !== null && in_array(strtolower($functionName), [
				'is_dir',
				'is_file',
				'file_exists',
			], true)) {
				return $this;
			}
		}

		$scope = $this;
		if (
			$expr instanceof Expr\ArrayDimFetch
			&& $expr->dim !== null
			&& !$expr->dim instanceof Expr\PreInc
			&& !$expr->dim instanceof Expr\PreDec
			&& !$expr->dim instanceof Expr\PostDec
			&& !$expr->dim instanceof Expr\PostInc
		) {
			$dimType = $scope->getType($expr->dim)->toArrayKey();
			if ($dimType->isInteger()->yes() || $dimType->isString()->yes()) {
				$exprVarType = $scope->getType($expr->var);
				if (!$exprVarType instanceof MixedType && !$exprVarType->isArray()->no()) {
					$types = [
						new ArrayType(new MixedType(), new MixedType()),
						new ObjectType(ArrayAccess::class),
						new NullType(),
					];
					if ($dimType->isInteger()->yes()) {
						$types[] = new StringType();
					}
					$offsetValueType = TypeCombinator::intersect($exprVarType, TypeCombinator::union(...$types));

					if ($dimType instanceof ConstantIntegerType || $dimType instanceof ConstantStringType) {
						$offsetValueType = TypeCombinator::intersect(
							$offsetValueType,
							new HasOffsetValueType($dimType, $type),
						);
					}

					$scope = $scope->specifyExpressionType(
						$expr->var,
						$offsetValueType,
						$scope->getNativeType($expr->var),
						$certainty,
					);
				}
			}
		}

		if ($certainty->no()) {
			throw new ShouldNotHappenException();
		}

		$exprString = $this->getNodeKey($expr);
		$expressionTypes = $scope->expressionTypes;
		$expressionTypes[$exprString] = new ExpressionTypeHolder($expr, $type, $certainty);
		$nativeTypes = $scope->nativeExpressionTypes;
		$nativeTypes[$exprString] = new ExpressionTypeHolder($expr, $nativeType, $certainty);

		$scope = $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$expressionTypes,
			$nativeTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);

		if ($expr instanceof AlwaysRememberedExpr) {
			return $scope->specifyExpressionType($expr->expr, $type, $nativeType, $certainty);
		}

		return $scope;
	}

	public function assignExpression(Expr $expr, Type $type, Type $nativeType): self
	{
		$scope = $this;
		if ($expr instanceof PropertyFetch) {
			$scope = $this->invalidateExpression($expr)
				->invalidateMethodsOnExpression($expr->var);
		} elseif ($expr instanceof Expr\StaticPropertyFetch) {
			$scope = $this->invalidateExpression($expr);
		} elseif ($expr instanceof Variable) {
			$scope = $this->invalidateExpression($expr);
		}

		return $scope->specifyExpressionType($expr, $type, $nativeType, TrinaryLogic::createYes());
	}

	public function assignInitializedProperty(Type $fetchedOnType, string $propertyName): self
	{
		if (!$this->isInClass()) {
			return $this;
		}

		if (TypeUtils::findThisType($fetchedOnType) === null) {
			return $this;
		}

		$propertyReflection = $this->getInstancePropertyReflection($fetchedOnType, $propertyName);
		if ($propertyReflection === null) {
			return $this;
		}
		$declaringClass = $propertyReflection->getDeclaringClass();
		if ($this->getClassReflection()->getName() !== $declaringClass->getName()) {
			return $this;
		}
		if (!$declaringClass->hasNativeProperty($propertyName)) {
			return $this;
		}

		return $this->assignExpression(new PropertyInitializationExpr($propertyName), new MixedType(), new MixedType());
	}

	public function invalidateExpression(Expr $expressionToInvalidate, bool $requireMoreCharacters = false): self
	{
		$expressionTypes = $this->expressionTypes;
		$nativeExpressionTypes = $this->nativeExpressionTypes;
		$invalidated = false;
		$exprStringToInvalidate = $this->getNodeKey($expressionToInvalidate);

		foreach ($expressionTypes as $exprString => $exprTypeHolder) {
			$exprExpr = $exprTypeHolder->getExpr();
			if (!$this->shouldInvalidateExpression($exprStringToInvalidate, $expressionToInvalidate, $exprExpr, $requireMoreCharacters)) {
				continue;
			}

			unset($expressionTypes[$exprString]);
			unset($nativeExpressionTypes[$exprString]);
			$invalidated = true;
		}

		$newConditionalExpressions = [];
		foreach ($this->conditionalExpressions as $conditionalExprString => $holders) {
			if (count($holders) === 0) {
				continue;
			}
			if ($this->shouldInvalidateExpression($exprStringToInvalidate, $expressionToInvalidate, $holders[array_key_first($holders)]->getTypeHolder()->getExpr())) {
				$invalidated = true;
				continue;
			}
			foreach ($holders as $holder) {
				$conditionalTypeHolders = $holder->getConditionExpressionTypeHolders();
				foreach ($conditionalTypeHolders as $conditionalTypeHolder) {
					if ($this->shouldInvalidateExpression($exprStringToInvalidate, $expressionToInvalidate, $conditionalTypeHolder->getExpr())) {
						$invalidated = true;
						continue 3;
					}
				}
			}
			$newConditionalExpressions[$conditionalExprString] = $holders;
		}

		if (!$invalidated) {
			return $this;
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$expressionTypes,
			$nativeExpressionTypes,
			$newConditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			[],
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	private function shouldInvalidateExpression(string $exprStringToInvalidate, Expr $exprToInvalidate, Expr $expr, bool $requireMoreCharacters = false): bool
	{
		if ($requireMoreCharacters && $exprStringToInvalidate === $this->getNodeKey($expr)) {
			return false;
		}

		// Variables will not contain traversable expressions. skip the NodeFinder overhead
		if ($expr instanceof Variable && is_string($expr->name) && !$requireMoreCharacters) {
			return $exprStringToInvalidate === $this->getNodeKey($expr);
		}

		$nodeFinder = new NodeFinder();
		$expressionToInvalidateClass = get_class($exprToInvalidate);
		$found = $nodeFinder->findFirst([$expr], function (Node $node) use ($expressionToInvalidateClass, $exprStringToInvalidate): bool {
			if (
				$exprStringToInvalidate === '$this'
				&& $node instanceof Name
				&& (
					in_array($node->toLowerString(), ['self', 'static', 'parent'], true)
					|| ($this->getClassReflection() !== null && $this->getClassReflection()->is($this->resolveName($node)))
				)
			) {
				return true;
			}

			if (!$node instanceof $expressionToInvalidateClass) {
				return false;
			}

			$nodeString = $this->getNodeKey($node);

			return $nodeString === $exprStringToInvalidate;
		});

		if ($found === null) {
			return false;
		}

		if (
			$expr instanceof PropertyFetch
			&& $requireMoreCharacters
			&& $this->isReadonlyPropertyFetch($expr, false)
		) {
			return false;
		}

		return true;
	}

	private function invalidateMethodsOnExpression(Expr $expressionToInvalidate): self
	{
		$exprStringToInvalidate = $this->getNodeKey($expressionToInvalidate);
		$expressionTypes = $this->expressionTypes;
		$nativeExpressionTypes = $this->nativeExpressionTypes;
		$invalidated = false;
		$nodeFinder = new NodeFinder();
		foreach ($expressionTypes as $exprString => $exprTypeHolder) {
			$expr = $exprTypeHolder->getExpr();
			$found = $nodeFinder->findFirst([$expr], function (Node $node) use ($exprStringToInvalidate): bool {
				if (!$node instanceof MethodCall) {
					return false;
				}

				return $this->getNodeKey($node->var) === $exprStringToInvalidate;
			});
			if ($found === null) {
				continue;
			}

			unset($expressionTypes[$exprString]);
			unset($nativeExpressionTypes[$exprString]);
			$invalidated = true;
		}

		if (!$invalidated) {
			return $this;
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$expressionTypes,
			$nativeExpressionTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			[],
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	private function setExpressionCertainty(Expr $expr, TrinaryLogic $certainty): self
	{
		if ($this->hasExpressionType($expr)->no()) {
			throw new ShouldNotHappenException();
		}

		$originalExprType = $this->getType($expr);
		$nativeType = $this->getNativeType($expr);

		return $this->specifyExpressionType(
			$expr,
			$originalExprType,
			$nativeType,
			$certainty,
		);
	}

	public function addTypeToExpression(Expr $expr, Type $type): self
	{
		$originalExprType = $this->getType($expr);
		$nativeType = $this->getNativeType($expr);

		if ($originalExprType->equals($nativeType)) {
			$newType = TypeCombinator::intersect($type, $originalExprType);
			if ($newType->isConstantScalarValue()->yes() && $newType->equals($originalExprType)) {
				// don't add the same type over and over again to improve performance
				return $this;
			}
			return $this->specifyExpressionType($expr, $newType, $newType, TrinaryLogic::createYes());
		}

		return $this->specifyExpressionType(
			$expr,
			TypeCombinator::intersect($type, $originalExprType),
			TypeCombinator::intersect($type, $nativeType),
			TrinaryLogic::createYes(),
		);
	}

	public function removeTypeFromExpression(Expr $expr, Type $typeToRemove): self
	{
		$exprType = $this->getType($expr);
		if (
			$exprType instanceof NeverType ||
			$typeToRemove instanceof NeverType
		) {
			return $this;
		}
		return $this->specifyExpressionType(
			$expr,
			TypeCombinator::remove($exprType, $typeToRemove),
			TypeCombinator::remove($this->getNativeType($expr), $typeToRemove),
			TrinaryLogic::createYes(),
		);
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
			if ($expr instanceof Node\Scalar || $expr instanceof Array_ || $expr instanceof Expr\UnaryMinus && $expr->expr instanceof Node\Scalar) {
				continue;
			}
			$typeSpecifications[] = [
				'sure' => true,
				'exprString' => (string) $exprString,
				'expr' => $expr,
				'type' => $type,
			];
		}
		foreach ($specifiedTypes->getSureNotTypes() as $exprString => [$expr, $type]) {
			if ($expr instanceof Node\Scalar || $expr instanceof Array_ || $expr instanceof Expr\UnaryMinus && $expr->expr instanceof Node\Scalar) {
				continue;
			}
			$typeSpecifications[] = [
				'sure' => false,
				'exprString' => (string) $exprString,
				'expr' => $expr,
				'type' => $type,
			];
		}

		usort($typeSpecifications, static function (array $a, array $b): int {
			$length = strlen($a['exprString']) - strlen($b['exprString']);
			if ($length !== 0) {
				return $length;
			}

			return $b['sure'] - $a['sure']; // @phpstan-ignore minus.leftNonNumeric, minus.rightNonNumeric
		});

		$scope = $this;
		$specifiedExpressions = [];
		foreach ($typeSpecifications as $typeSpecification) {
			$expr = $typeSpecification['expr'];
			$type = $typeSpecification['type'];

			if ($expr instanceof IssetExpr) {
				$issetExpr = $expr;
				$expr = $issetExpr->getExpr();

				if ($typeSpecification['sure']) {
					$scope = $scope->setExpressionCertainty(
						$expr,
						TrinaryLogic::createMaybe(),
					);
				} else {
					$scope = $scope->unsetExpression($expr);
				}

				continue;
			}

			if ($typeSpecification['sure']) {
				if ($specifiedTypes->shouldOverwrite()) {
					$scope = $scope->assignExpression($expr, $type, $type);
				} else {
					$scope = $scope->addTypeToExpression($expr, $type);
				}
			} else {
				$scope = $scope->removeTypeFromExpression($expr, $type);
			}
			$specifiedExpressions[$this->getNodeKey($expr)] = ExpressionTypeHolder::createYes($expr, $scope->getType($expr));
		}

		$conditions = [];
		foreach ($scope->conditionalExpressions as $conditionalExprString => $conditionalExpressions) {
			foreach ($conditionalExpressions as $conditionalExpression) {
				foreach ($conditionalExpression->getConditionExpressionTypeHolders() as $holderExprString => $conditionalTypeHolder) {
					if (!array_key_exists($holderExprString, $specifiedExpressions) || !$specifiedExpressions[$holderExprString]->equals($conditionalTypeHolder)) {
						continue 2;
					}
				}

				$conditions[$conditionalExprString][] = $conditionalExpression;
				$specifiedExpressions[$conditionalExprString] = $conditionalExpression->getTypeHolder();
			}
		}

		foreach ($conditions as $conditionalExprString => $expressions) {
			$certainty = TrinaryLogic::lazyExtremeIdentity($expressions, static fn (ConditionalExpressionHolder $holder) => $holder->getTypeHolder()->getCertainty());
			if ($certainty->no()) {
				unset($scope->expressionTypes[$conditionalExprString]);
			} else {
				$type = TypeCombinator::intersect(...array_map(static fn (ConditionalExpressionHolder $holder) => $holder->getTypeHolder()->getType(), $expressions));

				$scope->expressionTypes[$conditionalExprString] = array_key_exists($conditionalExprString, $scope->expressionTypes)
					? new ExpressionTypeHolder(
						$scope->expressionTypes[$conditionalExprString]->getExpr(),
						TypeCombinator::intersect($scope->expressionTypes[$conditionalExprString]->getType(), $type),
						TrinaryLogic::maxMin($scope->expressionTypes[$conditionalExprString]->getCertainty(), $certainty),
					)
					: $expressions[0]->getTypeHolder();
			}
		}

		return $scope->scopeFactory->create(
			$scope->context,
			$scope->isDeclareStrictTypes(),
			$scope->getFunction(),
			$scope->getNamespace(),
			$scope->expressionTypes,
			$scope->nativeExpressionTypes,
			array_merge($specifiedTypes->getNewConditionalExpressionHolders(), $scope->conditionalExpressions),
			$scope->inClosureBindScopeClasses,
			$scope->anonymousFunctionReflection,
			$scope->inFirstLevelStatement,
			$scope->currentlyAssignedExpressions,
			$scope->currentlyAllowedUndefinedExpressions,
			$scope->inFunctionCallsStack,
			$scope->afterExtractCall,
			$scope->parentScope,
			$scope->nativeTypesPromoted,
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
			$this->getFunction(),
			$this->getNamespace(),
			$this->expressionTypes,
			$this->nativeExpressionTypes,
			$conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	public function exitFirstLevelStatements(): self
	{
		if (!$this->inFirstLevelStatement) {
			return $this;
		}

		if ($this->scopeOutOfFirstLevelStatement !== null) {
			return $this->scopeOutOfFirstLevelStatement;
		}

		$scope = $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$this->expressionTypes,
			$this->nativeExpressionTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			false,
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
		$scope->resolvedTypes = $this->resolvedTypes;
		$scope->truthyScopes = $this->truthyScopes;
		$scope->falseyScopes = $this->falseyScopes;
		$this->scopeOutOfFirstLevelStatement = $scope;

		return $scope;
	}

	/** @api */
	public function isInFirstLevelStatement(): bool
	{
		return $this->inFirstLevelStatement;
	}

	public function mergeWith(?self $otherScope): self
	{
		if ($otherScope === null) {
			return $this;
		}
		$ourExpressionTypes = $this->expressionTypes;
		$theirExpressionTypes = $otherScope->expressionTypes;

		$mergedExpressionTypes = $this->mergeVariableHolders($ourExpressionTypes, $theirExpressionTypes);
		$conditionalExpressions = $this->intersectConditionalExpressions($otherScope->conditionalExpressions);
		$conditionalExpressions = $this->createConditionalExpressions(
			$conditionalExpressions,
			$ourExpressionTypes,
			$theirExpressionTypes,
			$mergedExpressionTypes,
		);
		$conditionalExpressions = $this->createConditionalExpressions(
			$conditionalExpressions,
			$theirExpressionTypes,
			$ourExpressionTypes,
			$mergedExpressionTypes,
		);
		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$mergedExpressionTypes,
			$this->mergeVariableHolders($this->nativeExpressionTypes, $otherScope->nativeExpressionTypes),
			$conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			[],
			[],
			[],
			$this->afterExtractCall && $otherScope->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
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
	 * @param array<string, ExpressionTypeHolder> $ourExpressionTypes
	 * @param array<string, ExpressionTypeHolder> $theirExpressionTypes
	 * @param array<string, ExpressionTypeHolder> $mergedExpressionTypes
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function createConditionalExpressions(
		array $conditionalExpressions,
		array $ourExpressionTypes,
		array $theirExpressionTypes,
		array $mergedExpressionTypes,
	): array
	{
		$newVariableTypes = $ourExpressionTypes;
		foreach ($theirExpressionTypes as $exprString => $holder) {
			if (!array_key_exists($exprString, $mergedExpressionTypes)) {
				continue;
			}

			if (!$mergedExpressionTypes[$exprString]->getType()->equals($holder->getType())) {
				continue;
			}

			unset($newVariableTypes[$exprString]);
		}

		$typeGuards = [];
		foreach ($newVariableTypes as $exprString => $holder) {
			if (!$holder->getCertainty()->yes()) {
				continue;
			}
			if (!array_key_exists($exprString, $mergedExpressionTypes)) {
				continue;
			}
			if ($mergedExpressionTypes[$exprString]->getType()->equals($holder->getType())) {
				continue;
			}

			$typeGuards[$exprString] = $holder;
		}

		if (count($typeGuards) === 0) {
			return $conditionalExpressions;
		}

		foreach ($newVariableTypes as $exprString => $holder) {
			if (
				array_key_exists($exprString, $mergedExpressionTypes)
				&& $mergedExpressionTypes[$exprString]->equals($holder)
			) {
				continue;
			}

			$variableTypeGuards = $typeGuards;
			unset($variableTypeGuards[$exprString]);

			if (count($variableTypeGuards) === 0) {
				continue;
			}

			$conditionalExpression = new ConditionalExpressionHolder($variableTypeGuards, $holder);
			$conditionalExpressions[$exprString][$conditionalExpression->getKey()] = $conditionalExpression;
		}

		foreach ($mergedExpressionTypes as $exprString => $mergedExprTypeHolder) {
			if (array_key_exists($exprString, $ourExpressionTypes)) {
				continue;
			}

			$conditionalExpression = new ConditionalExpressionHolder($typeGuards, new ExpressionTypeHolder($mergedExprTypeHolder->getExpr(), new ErrorType(), TrinaryLogic::createNo()));
			$conditionalExpressions[$exprString][$conditionalExpression->getKey()] = $conditionalExpression;
		}

		return $conditionalExpressions;
	}

	/**
	 * @param array<string, ExpressionTypeHolder> $ourVariableTypeHolders
	 * @param array<string, ExpressionTypeHolder> $theirVariableTypeHolders
	 * @return array<string, ExpressionTypeHolder>
	 */
	private function mergeVariableHolders(array $ourVariableTypeHolders, array $theirVariableTypeHolders): array
	{
		$intersectedVariableTypeHolders = [];
		$globalVariableCallback = fn (Node $node) => $node instanceof Variable && is_string($node->name) && $this->isGlobalVariable($node->name);
		$nodeFinder = new NodeFinder();
		foreach ($ourVariableTypeHolders as $exprString => $variableTypeHolder) {
			if (isset($theirVariableTypeHolders[$exprString])) {
				if ($variableTypeHolder === $theirVariableTypeHolders[$exprString]) {
					$intersectedVariableTypeHolders[$exprString] = $variableTypeHolder;
					continue;
				}

				$intersectedVariableTypeHolders[$exprString] = $variableTypeHolder->and($theirVariableTypeHolders[$exprString]);
			} else {
				$expr = $variableTypeHolder->getExpr();
				if ($nodeFinder->findFirst($expr, $globalVariableCallback) !== null) {
					continue;
				}

				$intersectedVariableTypeHolders[$exprString] = ExpressionTypeHolder::createMaybe($variableTypeHolder->getExpr(), $variableTypeHolder->getType());
			}
		}

		foreach ($theirVariableTypeHolders as $exprString => $variableTypeHolder) {
			if (isset($intersectedVariableTypeHolders[$exprString])) {
				continue;
			}

			$expr = $variableTypeHolder->getExpr();
			if ($nodeFinder->findFirst($expr, $globalVariableCallback) !== null) {
				continue;
			}

			$intersectedVariableTypeHolders[$exprString] = ExpressionTypeHolder::createMaybe($variableTypeHolder->getExpr(), $variableTypeHolder->getType());
		}

		return $intersectedVariableTypeHolders;
	}

	public function mergeInitializedProperties(self $calledMethodScope): self
	{
		$scope = $this;
		foreach ($calledMethodScope->expressionTypes as $exprString => $typeHolder) {
			$exprString = (string) $exprString;
			if (!str_starts_with($exprString, '__phpstanPropertyInitialization(')) {
				continue;
			}
			$propertyName = substr($exprString, strlen('__phpstanPropertyInitialization('), -1);
			$propertyExpr = new PropertyInitializationExpr($propertyName);
			if (!array_key_exists($exprString, $scope->expressionTypes)) {
				$scope = $scope->assignExpression($propertyExpr, new MixedType(), new MixedType());
				$scope->expressionTypes[$exprString] = $typeHolder;
				continue;
			}

			$certainty = $scope->expressionTypes[$exprString]->getCertainty();
			$scope = $scope->assignExpression($propertyExpr, new MixedType(), new MixedType());
			$scope->expressionTypes[$exprString] = new ExpressionTypeHolder(
				$typeHolder->getExpr(),
				$typeHolder->getType(),
				$typeHolder->getCertainty()->or($certainty),
			);
		}

		return $scope;
	}

	public function processFinallyScope(self $finallyScope, self $originalFinallyScope): self
	{
		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$this->processFinallyScopeVariableTypeHolders(
				$this->expressionTypes,
				$finallyScope->expressionTypes,
				$originalFinallyScope->expressionTypes,
			),
			$this->processFinallyScopeVariableTypeHolders(
				$this->nativeExpressionTypes,
				$finallyScope->nativeExpressionTypes,
				$originalFinallyScope->nativeExpressionTypes,
			),
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			[],
			[],
			[],
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	/**
	 * @param array<string, ExpressionTypeHolder> $ourVariableTypeHolders
	 * @param array<string, ExpressionTypeHolder> $finallyVariableTypeHolders
	 * @param array<string, ExpressionTypeHolder> $originalVariableTypeHolders
	 * @return array<string, ExpressionTypeHolder>
	 */
	private function processFinallyScopeVariableTypeHolders(
		array $ourVariableTypeHolders,
		array $finallyVariableTypeHolders,
		array $originalVariableTypeHolders,
	): array
	{
		foreach ($finallyVariableTypeHolders as $exprString => $variableTypeHolder) {
			if (
				isset($originalVariableTypeHolders[$exprString])
				&& !$originalVariableTypeHolders[$exprString]->getType()->equals($variableTypeHolder->getType())
			) {
				$ourVariableTypeHolders[$exprString] = $variableTypeHolder;
				continue;
			}

			if (isset($originalVariableTypeHolders[$exprString])) {
				continue;
			}

			$ourVariableTypeHolders[$exprString] = $variableTypeHolder;
		}

		return $ourVariableTypeHolders;
	}

	/**
	 * @param Node\ClosureUse[] $byRefUses
	 */
	public function processClosureScope(
		self $closureScope,
		?self $prevScope,
		array $byRefUses,
	): self
	{
		$nativeExpressionTypes = $this->nativeExpressionTypes;
		$expressionTypes = $this->expressionTypes;
		if (count($byRefUses) === 0) {
			return $this;
		}

		foreach ($byRefUses as $use) {
			if (!is_string($use->var->name)) {
				throw new ShouldNotHappenException();
			}

			$variableName = $use->var->name;
			$variableExprString = '$' . $variableName;

			if (!$closureScope->hasVariableType($variableName)->yes()) {
				$holder = ExpressionTypeHolder::createYes($use->var, new NullType());
				$expressionTypes[$variableExprString] = $holder;
				$nativeExpressionTypes[$variableExprString] = $holder;
				continue;
			}

			$variableType = $closureScope->getVariableType($variableName);

			if ($prevScope !== null) {
				$prevVariableType = $prevScope->getVariableType($variableName);
				if (!$variableType->equals($prevVariableType)) {
					$variableType = TypeCombinator::union($variableType, $prevVariableType);
					$variableType = $this->generalizeType($variableType, $prevVariableType, 0);
				}
			}

			$expressionTypes[$variableExprString] = ExpressionTypeHolder::createYes($use->var, $variableType);
			$nativeExpressionTypes[$variableExprString] = ExpressionTypeHolder::createYes($use->var, $variableType);
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$expressionTypes,
			$nativeExpressionTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			[],
			[],
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	public function processAlwaysIterableForeachScopeWithoutPollute(self $finalScope): self
	{
		$expressionTypes = $this->expressionTypes;
		foreach ($finalScope->expressionTypes as $variableExprString => $variableTypeHolder) {
			if (!isset($expressionTypes[$variableExprString])) {
				$expressionTypes[$variableExprString] = ExpressionTypeHolder::createMaybe($variableTypeHolder->getExpr(), $variableTypeHolder->getType());
				continue;
			}

			$expressionTypes[$variableExprString] = new ExpressionTypeHolder(
				$variableTypeHolder->getExpr(),
				$variableTypeHolder->getType(),
				$variableTypeHolder->getCertainty()->and($expressionTypes[$variableExprString]->getCertainty()),
			);
		}
		$nativeTypes = $this->nativeExpressionTypes;
		foreach ($finalScope->nativeExpressionTypes as $variableExprString => $variableTypeHolder) {
			if (!isset($nativeTypes[$variableExprString])) {
				$nativeTypes[$variableExprString] = ExpressionTypeHolder::createMaybe($variableTypeHolder->getExpr(), $variableTypeHolder->getType());
				continue;
			}

			$nativeTypes[$variableExprString] = new ExpressionTypeHolder(
				$variableTypeHolder->getExpr(),
				$variableTypeHolder->getType(),
				$variableTypeHolder->getCertainty()->and($nativeTypes[$variableExprString]->getCertainty()),
			);
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$expressionTypes,
			$nativeTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			[],
			[],
			[],
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	public function generalizeWith(self $otherScope): self
	{
		$variableTypeHolders = $this->generalizeVariableTypeHolders(
			$this->expressionTypes,
			$otherScope->expressionTypes,
		);
		$nativeTypes = $this->generalizeVariableTypeHolders(
			$this->nativeExpressionTypes,
			$otherScope->nativeExpressionTypes,
		);

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$variableTypeHolders,
			$nativeTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->anonymousFunctionReflection,
			$this->inFirstLevelStatement,
			[],
			[],
			[],
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	/**
	 * @param array<string, ExpressionTypeHolder> $variableTypeHolders
	 * @param array<string, ExpressionTypeHolder> $otherVariableTypeHolders
	 * @return array<string, ExpressionTypeHolder>
	 */
	private function generalizeVariableTypeHolders(
		array $variableTypeHolders,
		array $otherVariableTypeHolders,
	): array
	{
		uksort($variableTypeHolders, static fn (string $exprA, string $exprB): int => strlen($exprA) <=> strlen($exprB));

		$generalizedExpressions = [];
		$newVariableTypeHolders = [];
		foreach ($variableTypeHolders as $variableExprString => $variableTypeHolder) {
			foreach ($generalizedExpressions as $generalizedExprString => $generalizedExpr) {
				if (!$this->shouldInvalidateExpression($generalizedExprString, $generalizedExpr, $variableTypeHolder->getExpr())) {
					continue;
				}

				continue 2;
			}
			if (!isset($otherVariableTypeHolders[$variableExprString])) {
				$newVariableTypeHolders[$variableExprString] = $variableTypeHolder;
				continue;
			}

			$generalizedType = $this->generalizeType($variableTypeHolder->getType(), $otherVariableTypeHolders[$variableExprString]->getType(), 0);
			if (
				!$generalizedType->equals($variableTypeHolder->getType())
			) {
				$generalizedExpressions[$variableExprString] = $variableTypeHolder->getExpr();
			}
			$newVariableTypeHolders[$variableExprString] = new ExpressionTypeHolder(
				$variableTypeHolder->getExpr(),
				$generalizedType,
				$variableTypeHolder->getCertainty(),
			);
		}

		return $newVariableTypeHolders;
	}

	private function generalizeType(Type $a, Type $b, int $depth): Type
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
				if ($type->isConstantArray()->yes()) {
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
				if (
					$constantArraysA->getIterableKeyType()->equals($constantArraysB->getIterableKeyType())
					&& $constantArraysA->getArraySize()->getGreaterOrEqualType($this->phpVersion)->isSuperTypeOf($constantArraysB->getArraySize())->yes()
				) {
					$resultArrayBuilder = ConstantArrayTypeBuilder::createEmpty();
					foreach (TypeUtils::flattenTypes($constantArraysA->getIterableKeyType()) as $keyType) {
						$resultArrayBuilder->setOffsetValueType(
							$keyType,
							$this->generalizeType(
								$constantArraysA->getOffsetValueType($keyType),
								$constantArraysB->getOffsetValueType($keyType),
								$depth + 1,
							),
							!$constantArraysA->hasOffsetValueType($keyType)->and($constantArraysB->hasOffsetValueType($keyType))->negate()->no(),
						);
					}

					$resultTypes[] = $resultArrayBuilder->getArray();
				} else {
					$resultType = new ArrayType(
						TypeCombinator::union($this->generalizeType($constantArraysA->getIterableKeyType(), $constantArraysB->getIterableKeyType(), $depth + 1)),
						TypeCombinator::union($this->generalizeType($constantArraysA->getIterableValueType(), $constantArraysB->getIterableValueType(), $depth + 1)),
					);
					if (
						$constantArraysA->isIterableAtLeastOnce()->yes()
						&& $constantArraysB->isIterableAtLeastOnce()->yes()
						&& $constantArraysA->getArraySize()->getGreaterOrEqualType($this->phpVersion)->isSuperTypeOf($constantArraysB->getArraySize())->yes()
					) {
						$resultType = TypeCombinator::intersect($resultType, new NonEmptyArrayType());
					}
					if ($constantArraysA->isList()->yes() && $constantArraysB->isList()->yes()) {
						$resultType = TypeCombinator::intersect($resultType, new AccessoryArrayListType());
					}
					$resultTypes[] = $resultType;
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
				if (
					$aValueType->isArray()->yes()
					&& $aValueType->isConstantArray()->no()
					&& $bValueType->isArray()->yes()
					&& $bValueType->isConstantArray()->no()
				) {
					$aDepth = self::getArrayDepth($aValueType) + $depth;
					$bDepth = self::getArrayDepth($bValueType) + $depth;
					if (
						($aDepth > 2 || $bDepth > 2)
						&& abs($aDepth - $bDepth) > 0
					) {
						$aValueType = new MixedType();
						$bValueType = new MixedType();
					}
				}

				$resultType = new ArrayType(
					TypeCombinator::union($this->generalizeType($generalArraysA->getIterableKeyType(), $generalArraysB->getIterableKeyType(), $depth + 1)),
					TypeCombinator::union($this->generalizeType($aValueType, $bValueType, $depth + 1)),
				);
				if ($generalArraysA->isIterableAtLeastOnce()->yes() && $generalArraysB->isIterableAtLeastOnce()->yes()) {
					$resultType = TypeCombinator::intersect($resultType, new NonEmptyArrayType());
				}
				if ($generalArraysA->isList()->yes() && $generalArraysB->isList()->yes()) {
					$resultType = TypeCombinator::intersect($resultType, new AccessoryArrayListType());
				}
				if ($generalArraysA->isOversizedArray()->yes() && $generalArraysB->isOversizedArray()->yes()) {
					$resultType = TypeCombinator::intersect($resultType, new OversizedArrayType());
				}
				$resultTypes[] = $resultType;
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

		$accessoryTypes = array_map(
			static fn (Type $type): Type => $type->generalize(GeneralizePrecision::moreSpecific()),
			TypeUtils::getAccessoryTypes($a),
		);

		return TypeCombinator::union(TypeCombinator::intersect(
			TypeCombinator::union(...$resultTypes, ...$otherTypes),
			...$accessoryTypes,
		), ...$otherTypes);
	}

	private static function getArrayDepth(Type $type): int
	{
		$depth = 0;
		$arrays = TypeUtils::toBenevolentUnion($type)->getArrays();
		while (count($arrays) > 0) {
			$temp = $type->getIterableValueType();
			$type = $temp;
			$arrays = TypeUtils::toBenevolentUnion($type)->getArrays();
			$depth++;
		}

		return $depth;
	}

	public function equals(self $otherScope): bool
	{
		if (!$this->context->equals($otherScope->context)) {
			return false;
		}

		if (!$this->compareVariableTypeHolders($this->expressionTypes, $otherScope->expressionTypes)) {
			return false;
		}
		return $this->compareVariableTypeHolders($this->nativeExpressionTypes, $otherScope->nativeExpressionTypes);
	}

	/**
	 * @param array<string, ExpressionTypeHolder> $variableTypeHolders
	 * @param array<string, ExpressionTypeHolder> $otherVariableTypeHolders
	 */
	private function compareVariableTypeHolders(array $variableTypeHolders, array $otherVariableTypeHolders): bool
	{
		if (count($variableTypeHolders) !== count($otherVariableTypeHolders)) {
			return false;
		}
		foreach ($variableTypeHolders as $variableExprString => $variableTypeHolder) {
			if (!isset($otherVariableTypeHolders[$variableExprString])) {
				return false;
			}

			if (!$variableTypeHolder->getCertainty()->equals($otherVariableTypeHolders[$variableExprString]->getCertainty())) {
				return false;
			}

			if (!$variableTypeHolder->getType()->equals($otherVariableTypeHolders[$variableExprString]->getType())) {
				return false;
			}

			unset($otherVariableTypeHolders[$variableExprString]);
		}

		return true;
	}

	private function getBooleanExpressionDepth(Expr $expr, int $depth = 0): int
	{
		while (
			$expr instanceof BinaryOp\BooleanOr
			|| $expr instanceof BinaryOp\LogicalOr
			|| $expr instanceof BinaryOp\BooleanAnd
			|| $expr instanceof BinaryOp\LogicalAnd
		) {
			return $this->getBooleanExpressionDepth($expr->left, $depth + 1);
		}

		return $depth;
	}

	/**
	 * @api
	 * @deprecated Use canReadProperty() or canWriteProperty()
	 */
	public function canAccessProperty(PropertyReflection $propertyReflection): bool
	{
		return $this->canAccessClassMember($propertyReflection);
	}

	/** @api */
	public function canReadProperty(ExtendedPropertyReflection $propertyReflection): bool
	{
		return $this->canAccessClassMember($propertyReflection);
	}

	/** @api */
	public function canWriteProperty(ExtendedPropertyReflection $propertyReflection): bool
	{
		if (!$propertyReflection->isPrivateSet() && !$propertyReflection->isProtectedSet()) {
			return $this->canAccessClassMember($propertyReflection);
		}

		if (!$this->phpVersion->supportsAsymmetricVisibility()) {
			return $this->canAccessClassMember($propertyReflection);
		}

		$propertyDeclaringClass = $propertyReflection->getDeclaringClass();
		$canAccessClassMember = static function (ClassReflection $classReflection) use ($propertyReflection, $propertyDeclaringClass) {
			if ($propertyReflection->isPrivateSet()) {
				return $classReflection->getName() === $propertyDeclaringClass->getName();
			}

			// protected set

			if (
				$classReflection->getName() === $propertyDeclaringClass->getName()
				|| $classReflection->isSubclassOfClass($propertyDeclaringClass->removeFinalKeywordOverride())
			) {
				return true;
			}

			return $propertyReflection->getDeclaringClass()->isSubclassOfClass($classReflection);
		};

		foreach ($this->inClosureBindScopeClasses as $inClosureBindScopeClass) {
			if (!$this->reflectionProvider->hasClass($inClosureBindScopeClass)) {
				continue;
			}

			if ($canAccessClassMember($this->reflectionProvider->getClass($inClosureBindScopeClass))) {
				return true;
			}
		}

		if ($this->isInClass()) {
			return $canAccessClassMember($this->getClassReflection());
		}

		return false;
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
	public function canAccessConstant(ClassConstantReflection $constantReflection): bool
	{
		return $this->canAccessClassMember($constantReflection);
	}

	private function canAccessClassMember(ClassMemberReflection $classMemberReflection): bool
	{
		if ($classMemberReflection->isPublic()) {
			return true;
		}

		$classMemberDeclaringClass = $classMemberReflection->getDeclaringClass();
		$canAccessClassMember = static function (ClassReflection $classReflection) use ($classMemberReflection, $classMemberDeclaringClass) {
			if ($classMemberReflection->isPrivate()) {
				return $classReflection->getName() === $classMemberDeclaringClass->getName();
			}

			// protected

			if (
				$classReflection->getName() === $classMemberDeclaringClass->getName()
				|| $classReflection->isSubclassOfClass($classMemberDeclaringClass->removeFinalKeywordOverride())
			) {
				return true;
			}

			return $classMemberReflection->getDeclaringClass()->isSubclassOfClass($classReflection);
		};

		foreach ($this->inClosureBindScopeClasses as $inClosureBindScopeClass) {
			if (!$this->reflectionProvider->hasClass($inClosureBindScopeClass)) {
				continue;
			}

			if ($canAccessClassMember($this->reflectionProvider->getClass($inClosureBindScopeClass))) {
				return true;
			}
		}

		if ($this->isInClass()) {
			return $canAccessClassMember($this->getClassReflection());
		}

		return false;
	}

	/**
	 * @return string[]
	 */
	public function debug(): array
	{
		$descriptions = [];
		foreach ($this->expressionTypes as $name => $variableTypeHolder) {
			$key = sprintf('%s (%s)', $name, $variableTypeHolder->getCertainty()->describe());
			$descriptions[$key] = $variableTypeHolder->getType()->describe(VerbosityLevel::precise());
		}
		foreach ($this->nativeExpressionTypes as $exprString => $nativeTypeHolder) {
			$key = sprintf('native %s (%s)', $exprString, $nativeTypeHolder->getCertainty()->describe());
			$descriptions[$key] = $nativeTypeHolder->getType()->describe(VerbosityLevel::precise());
		}

		foreach (array_keys($this->currentlyAssignedExpressions) as $exprString) {
			$descriptions[sprintf('currently assigned %s', $exprString)] = 'true';
		}

		foreach (array_keys($this->currentlyAllowedUndefinedExpressions) as $exprString) {
			$descriptions[sprintf('currently allowed undefined %s', $exprString)] = 'true';
		}

		foreach ($this->conditionalExpressions as $exprString => $holders) {
			foreach (array_values($holders) as $i => $holder) {
				$key = sprintf('condition about %s #%d', $exprString, $i + 1);
				$parts = [];
				foreach ($holder->getConditionExpressionTypeHolders() as $conditionalExprString => $expressionTypeHolder) {
					$parts[] = $conditionalExprString . '=' . $expressionTypeHolder->getType()->describe(VerbosityLevel::precise());
				}
				$condition = implode(' && ', $parts);
				$descriptions[$key] = sprintf(
					'if %s then %s is %s (%s)',
					$condition,
					$exprString,
					$holder->getTypeHolder()->getType()->describe(VerbosityLevel::precise()),
					$holder->getTypeHolder()->getCertainty()->describe(),
				);
			}
		}

		return $descriptions;
	}

	private function exactInstantiation(New_ $node, Name $className): Type
	{
		$resolvedClassName = $this->resolveName($className);
		$isStatic = false;
		$lowercasedClassName = $className->toLowerString();
		if ($lowercasedClassName === 'static') {
			$isStatic = true;
		}

		if (!$this->reflectionProvider->hasClass($resolvedClassName)) {
			if ($lowercasedClassName === 'static') {
				if (!$this->isInClass()) {
					return new ErrorType();
				}

				return new StaticType($this->getClassReflection());
			}
			if ($lowercasedClassName === 'parent') {
				return new NonexistentParentClassType();
			}

			return new ObjectType($resolvedClassName);
		}

		$classReflection = $this->reflectionProvider->getClass($resolvedClassName);
		$nonFinalClassReflection = $classReflection;
		if (!$isStatic) {
			$classReflection = $classReflection->asFinal();
		}
		if ($classReflection->hasConstructor()) {
			$constructorMethod = $classReflection->getConstructor();
		} else {
			$constructorMethod = new DummyConstructorReflection($classReflection);
		}

		if ($constructorMethod->getName() === '') {
			throw new ShouldNotHappenException();
		}

		$resolvedTypes = [];
		$methodCall = new Expr\StaticCall(
			new Name($resolvedClassName),
			new Node\Identifier($constructorMethod->getName()),
			$node->getArgs(),
		);

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$this,
			$methodCall->getArgs(),
			$constructorMethod->getVariants(),
			$constructorMethod->getNamedArgumentsVariants(),
		);
		$normalizedMethodCall = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $methodCall);

		if ($normalizedMethodCall !== null) {
			foreach ($this->dynamicReturnTypeExtensionRegistry->getDynamicStaticMethodReturnTypeExtensionsForClass($classReflection->getName()) as $dynamicStaticMethodReturnTypeExtension) {
				if (!$dynamicStaticMethodReturnTypeExtension->isStaticMethodSupported($constructorMethod)) {
					continue;
				}

				$resolvedType = $dynamicStaticMethodReturnTypeExtension->getTypeFromStaticMethodCall(
					$constructorMethod,
					$normalizedMethodCall,
					$this,
				);
				if ($resolvedType === null) {
					continue;
				}

				$resolvedTypes[] = $resolvedType;
			}
		}

		if (count($resolvedTypes) > 0) {
			return TypeCombinator::union(...$resolvedTypes);
		}

		$methodResult = $this->getType($methodCall);
		if ($methodResult instanceof NeverType && $methodResult->isExplicit()) {
			return $methodResult;
		}

		$objectType = $isStatic ? new StaticType($classReflection) : new ObjectType($resolvedClassName, classReflection: $classReflection);
		if (!$classReflection->isGeneric()) {
			return $objectType;
		}

		$assignedToProperty = $node->getAttribute(NewAssignedToPropertyVisitor::ATTRIBUTE_NAME);
		if ($assignedToProperty !== null) {
			$constructorVariants = $constructorMethod->getVariants();
			if (count($constructorVariants) === 1) {
				$constructorVariant = $constructorVariants[0];
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
					$propertyType = TypeCombinator::removeNull($this->getType($assignedToProperty));
					$nonFinalObjectType = $isStatic ? new StaticType($nonFinalClassReflection) : new ObjectType($resolvedClassName, classReflection: $nonFinalClassReflection);
					if ($nonFinalObjectType->isSuperTypeOf($propertyType)->yes()) {
						return $propertyType;
					}
				}
			}
		}

		if ($constructorMethod instanceof DummyConstructorReflection) {
			if ($isStatic) {
				return new GenericStaticType(
					$classReflection,
					$classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds()),
					null,
					[],
				);
			}

			$types = $classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds());
			return new GenericObjectType(
				$resolvedClassName,
				$types,
				classReflection: $classReflection->withTypes($types)->asFinal(),
			);
		}

		if ($constructorMethod->getDeclaringClass()->getName() !== $classReflection->getName()) {
			if (!$constructorMethod->getDeclaringClass()->isGeneric()) {
				if ($isStatic) {
					return new GenericStaticType(
						$classReflection,
						$classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds()),
						null,
						[],
					);
				}

				$types = $classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds());
				return new GenericObjectType(
					$resolvedClassName,
					$types,
					classReflection: $classReflection->withTypes($types)->asFinal(),
				);
			}
			$newType = new GenericObjectType($resolvedClassName, $classReflection->typeMapToList($classReflection->getTemplateTypeMap()));
			$ancestorType = $newType->getAncestorWithClassName($constructorMethod->getDeclaringClass()->getName());
			if ($ancestorType === null) {
				if ($isStatic) {
					return new GenericStaticType(
						$classReflection,
						$classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds()),
						null,
						[],
					);
				}

				$types = $classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds());
				return new GenericObjectType(
					$resolvedClassName,
					$types,
					classReflection: $classReflection->withTypes($types)->asFinal(),
				);
			}
			$ancestorClassReflections = $ancestorType->getObjectClassReflections();
			if (count($ancestorClassReflections) !== 1) {
				if ($isStatic) {
					return new GenericStaticType(
						$classReflection,
						$classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds()),
						null,
						[],
					);
				}

				$types = $classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds());
				return new GenericObjectType(
					$resolvedClassName,
					$types,
					classReflection: $classReflection->withTypes($types)->asFinal(),
				);
			}

			$newParentNode = new New_(new Name($constructorMethod->getDeclaringClass()->getName()), $node->args);
			$newParentType = $this->getType($newParentNode);
			$newParentTypeClassReflections = $newParentType->getObjectClassReflections();
			if (count($newParentTypeClassReflections) !== 1) {
				if ($isStatic) {
					return new GenericStaticType(
						$classReflection,
						$classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds()),
						null,
						[],
					);
				}

				$types = $classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds());
				return new GenericObjectType(
					$resolvedClassName,
					$types,
					classReflection: $classReflection->withTypes($types)->asFinal(),
				);
			}
			$newParentTypeClassReflection = $newParentTypeClassReflections[0];

			$ancestorClassReflection = $ancestorClassReflections[0];
			$ancestorMapping = [];
			foreach ($ancestorClassReflection->getActiveTemplateTypeMap()->getTypes() as $typeName => $templateType) {
				if (!$templateType instanceof TemplateType) {
					continue;
				}

				$ancestorMapping[$typeName] = $templateType;
			}

			$resolvedTypeMap = [];
			foreach ($newParentTypeClassReflection->getActiveTemplateTypeMap()->getTypes() as $typeName => $type) {
				if (!array_key_exists($typeName, $ancestorMapping)) {
					continue;
				}

				$ancestorType = $ancestorMapping[$typeName];
				if (!$ancestorType->getBound()->isSuperTypeOf($type)->yes()) {
					continue;
				}

				if (!array_key_exists($ancestorType->getName(), $resolvedTypeMap)) {
					$resolvedTypeMap[$ancestorType->getName()] = $type;
					continue;
				}

				$resolvedTypeMap[$ancestorType->getName()] = TypeCombinator::union($resolvedTypeMap[$ancestorType->getName()], $type);
			}

			if ($isStatic) {
				return new GenericStaticType(
					$classReflection,
					$classReflection->typeMapToList(new TemplateTypeMap($resolvedTypeMap)),
					null,
					[],
				);
			}

			$types = $classReflection->typeMapToList(new TemplateTypeMap($resolvedTypeMap));
			return new GenericObjectType(
				$resolvedClassName,
				$types,
				classReflection: $classReflection->withTypes($types)->asFinal(),
			);
		}

		$resolvedTemplateTypeMap = $parametersAcceptor->getResolvedTemplateTypeMap();
		$types = $classReflection->typeMapToList($classReflection->getTemplateTypeMap());
		$newGenericType = new GenericObjectType(
			$resolvedClassName,
			$types,
			classReflection: $classReflection->withTypes($types)->asFinal(),
		);
		if ($isStatic) {
			$newGenericType = new GenericStaticType(
				$classReflection,
				$types,
				null,
				[],
			);
		}
		return TypeTraverser::map($newGenericType, static function (Type $type, callable $traverse) use ($resolvedTemplateTypeMap): Type {
			if ($type instanceof TemplateType && !$type->isArgument()) {
				$newType = $resolvedTemplateTypeMap->getType($type->getName());
				if ($newType === null || $newType instanceof ErrorType) {
					return $type->getDefault() ?? $type->getBound();
				}

				return TemplateTypeHelper::generalizeInferredTemplateType($type, $newType);
			}

			return $traverse($type);
		});
	}

	private function filterTypeWithMethod(Type $typeWithMethod, string $methodName): ?Type
	{
		if ($typeWithMethod instanceof UnionType) {
			$typeWithMethod = $typeWithMethod->filterTypes(static fn (Type $innerType) => $innerType->hasMethod($methodName)->yes());
		}

		if (!$typeWithMethod->hasMethod($methodName)->yes()) {
			return null;
		}

		return $typeWithMethod;
	}

	/** @api */
	public function getMethodReflection(Type $typeWithMethod, string $methodName): ?ExtendedMethodReflection
	{
		$type = $this->filterTypeWithMethod($typeWithMethod, $methodName);
		if ($type === null) {
			return null;
		}

		return $type->getMethod($methodName, $this);
	}

	public function getNakedMethod(Type $typeWithMethod, string $methodName): ?ExtendedMethodReflection
	{
		$type = $this->filterTypeWithMethod($typeWithMethod, $methodName);
		if ($type === null) {
			return null;
		}

		return $type->getUnresolvedMethodPrototype($methodName, $this)->getNakedMethod();
	}

	/**
	 * @param MethodCall|Node\Expr\StaticCall $methodCall
	 */
	private function methodCallReturnType(Type $typeWithMethod, string $methodName, Expr $methodCall): ?Type
	{
		$typeWithMethod = $this->filterTypeWithMethod($typeWithMethod, $methodName);
		if ($typeWithMethod === null) {
			return null;
		}

		$methodReflection = $typeWithMethod->getMethod($methodName, $this);
		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$this,
			$methodCall->getArgs(),
			$methodReflection->getVariants(),
			$methodReflection->getNamedArgumentsVariants(),
		);
		if ($methodCall instanceof MethodCall) {
			$normalizedMethodCall = ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $methodCall);
		} else {
			$normalizedMethodCall = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $methodCall);
		}
		if ($normalizedMethodCall === null) {
			return $this->transformVoidToNull($parametersAcceptor->getReturnType(), $methodCall);
		}

		$resolvedTypes = [];
		foreach ($typeWithMethod->getObjectClassNames() as $className) {
			if ($normalizedMethodCall instanceof MethodCall) {
				foreach ($this->dynamicReturnTypeExtensionRegistry->getDynamicMethodReturnTypeExtensionsForClass($className) as $dynamicMethodReturnTypeExtension) {
					if (!$dynamicMethodReturnTypeExtension->isMethodSupported($methodReflection)) {
						continue;
					}

					$resolvedType = $dynamicMethodReturnTypeExtension->getTypeFromMethodCall($methodReflection, $normalizedMethodCall, $this);
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
						$normalizedMethodCall,
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
			return $this->transformVoidToNull(TypeCombinator::union(...$resolvedTypes), $methodCall);
		}

		return $this->transformVoidToNull($parametersAcceptor->getReturnType(), $methodCall);
	}

	/**
	 * @api
	 * @deprecated Use getInstancePropertyReflection or getStaticPropertyReflection instead
	 */
	public function getPropertyReflection(Type $typeWithProperty, string $propertyName): ?ExtendedPropertyReflection
	{
		if ($typeWithProperty instanceof UnionType) {
			$typeWithProperty = $typeWithProperty->filterTypes(static fn (Type $innerType) => $innerType->hasProperty($propertyName)->yes());
		}
		if (!$typeWithProperty->hasProperty($propertyName)->yes()) {
			return null;
		}

		return $typeWithProperty->getProperty($propertyName, $this);
	}

	/** @api */
	public function getInstancePropertyReflection(Type $typeWithProperty, string $propertyName): ?ExtendedPropertyReflection
	{
		if ($typeWithProperty instanceof UnionType) {
			$typeWithProperty = $typeWithProperty->filterTypes(static fn (Type $innerType) => $innerType->hasInstanceProperty($propertyName)->yes());
		}
		if (!$typeWithProperty->hasInstanceProperty($propertyName)->yes()) {
			return null;
		}

		return $typeWithProperty->getInstanceProperty($propertyName, $this);
	}

	/** @api */
	public function getStaticPropertyReflection(Type $typeWithProperty, string $propertyName): ?ExtendedPropertyReflection
	{
		if ($typeWithProperty instanceof UnionType) {
			$typeWithProperty = $typeWithProperty->filterTypes(static fn (Type $innerType) => $innerType->hasStaticProperty($propertyName)->yes());
		}
		if (!$typeWithProperty->hasStaticProperty($propertyName)->yes()) {
			return null;
		}

		return $typeWithProperty->getStaticProperty($propertyName, $this);
	}

	/**
	 * @param PropertyFetch|Node\Expr\StaticPropertyFetch $propertyFetch
	 */
	private function propertyFetchType(Type $fetchedOnType, string $propertyName, Expr $propertyFetch): ?Type
	{
		if ($propertyFetch instanceof PropertyFetch) {
			$propertyReflection = $this->getInstancePropertyReflection($fetchedOnType, $propertyName);
		} else {
			$propertyReflection = $this->getStaticPropertyReflection($fetchedOnType, $propertyName);
		}

		if ($propertyReflection === null) {
			return null;
		}

		if ($this->isInExpressionAssign($propertyFetch)) {
			return $propertyReflection->getWritableType();
		}

		return $propertyReflection->getReadableType();
	}

	public function getConstantReflection(Type $typeWithConstant, string $constantName): ?ClassConstantReflection
	{
		if ($typeWithConstant instanceof UnionType) {
			$typeWithConstant = $typeWithConstant->filterTypes(static fn (Type $innerType) => $innerType->hasConstant($constantName)->yes());
		}
		if (!$typeWithConstant->hasConstant($constantName)->yes()) {
			return null;
		}

		return $typeWithConstant->getConstant($constantName);
	}

	public function getConstantExplicitTypeFromConfig(string $constantName, Type $constantType): Type
	{
		return $this->constantResolver->resolveConstantType($constantName, $constantType);
	}

	/**
	 * @return array<string, ExpressionTypeHolder>
	 */
	private function getConstantTypes(): array
	{
		$constantTypes = [];
		foreach ($this->expressionTypes as $exprString => $typeHolder) {
			$expr = $typeHolder->getExpr();
			if (!$expr instanceof ConstFetch) {
				continue;
			}
			$constantTypes[$exprString] = $typeHolder;
		}
		return $constantTypes;
	}

	private function getGlobalConstantType(Name $name): ?Type
	{
		$fetches = [];
		if (!$name->isFullyQualified() && $this->getNamespace() !== null) {
			$fetches[] = new ConstFetch(new FullyQualified([$this->getNamespace(), $name->toString()]));
		}

		$fetches[] = new ConstFetch(new FullyQualified($name->toString()));
		$fetches[] = new ConstFetch($name);

		foreach ($fetches as $constFetch) {
			if ($this->hasExpressionType($constFetch)->yes()) {
				return $this->getType($constFetch);
			}
		}

		return null;
	}

	/**
	 * @return array<string, ExpressionTypeHolder>
	 */
	private function getNativeConstantTypes(): array
	{
		$constantTypes = [];
		foreach ($this->nativeExpressionTypes as $exprString => $typeHolder) {
			$expr = $typeHolder->getExpr();
			if (!$expr instanceof ConstFetch) {
				continue;
			}
			$constantTypes[$exprString] = $typeHolder;
		}
		return $constantTypes;
	}

	public function getIterableKeyType(Type $iteratee): Type
	{
		if ($iteratee instanceof UnionType) {
			$filtered = $iteratee->filterTypes(static fn (Type $innerType) => $innerType->isIterable()->yes());
			if (!$filtered instanceof NeverType) {
				$iteratee = $filtered;
			}
		}

		return $iteratee->getIterableKeyType();
	}

	public function getIterableValueType(Type $iteratee): Type
	{
		if ($iteratee instanceof UnionType) {
			$filtered = $iteratee->filterTypes(static fn (Type $innerType) => $innerType->isIterable()->yes());
			if (!$filtered instanceof NeverType) {
				$iteratee = $filtered;
			}
		}

		return $iteratee->getIterableValueType();
	}

	public function getPhpVersion(): PhpVersions
	{
		$constType = $this->getGlobalConstantType(new Name('PHP_VERSION_ID'));

		$isOverallPhpVersionRange = false;
		if (
			$constType instanceof IntegerRangeType
			&& $constType->getMin() === ConstantResolver::PHP_MIN_ANALYZABLE_VERSION_ID
			&& ($constType->getMax() === null || $constType->getMax() === PhpVersionFactory::MAX_PHP_VERSION)
		) {
			$isOverallPhpVersionRange = true;
		}

		if ($constType !== null && !$isOverallPhpVersionRange) {
			return new PhpVersions($constType);
		}

		if (is_array($this->configPhpVersion)) {
			return new PhpVersions(IntegerRangeType::fromInterval($this->configPhpVersion['min'], $this->configPhpVersion['max']));
		}
		return new PhpVersions(new ConstantIntegerType($this->phpVersion->getVersionId()));
	}

	public function invokeNodeCallback(Node $node): void
	{
		$nodeCallback = $this->nodeCallback;
		if ($nodeCallback === null) {
			throw new ShouldNotHappenException('Node callback is not present in this scope');
		}

		$nodeCallback($node, $this);
	}

}
