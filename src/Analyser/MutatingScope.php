<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\PropertyHook;
use PhpParser\Node\Scalar;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Traverser\TransformStaticTypeTraverser;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\Container;
use PHPStan\Node\EmitCollectedDataNode;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Expr\CloneReinitializationExpr;
use PHPStan\Node\Expr\GetIterableKeyTypeExpr;
use PHPStan\Node\Expr\IntertwinedVariableByReferenceWithExpr;
use PHPStan\Node\Expr\OriginalForeachKeyExpr;
use PHPStan\Node\Expr\OriginalForeachValueExpr;
use PHPStan\Node\Expr\ParameterVariableOriginalValueExpr;
use PHPStan\Node\Expr\PossiblyImpureCallExpr;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Node\Expr\SetExistingOffsetValueTypeExpr;
use PHPStan\Node\IssetExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\VirtualNode;
use PHPStan\Parser\ArrayMapArgVisitor;
use PHPStan\Parser\Parser;
use PHPStan\Php\PhpVersion;
use PHPStan\Php\PhpVersionFactory;
use PHPStan\Php\PhpVersions;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ExpressionTypeResolverExtensionRegistry;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StaticTypeFactory;
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
use function abs;
use function array_filter;
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_last;
use function array_map;
use function array_merge;
use function array_pop;
use function array_slice;
use function array_unique;
use function array_values;
use function assert;
use function count;
use function explode;
use function get_class;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function ltrim;
use function md5;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function uksort;
use function usort;
use const PHP_INT_MAX;
use const PHP_INT_MIN;
use const PHP_VERSION_ID;

class MutatingScope implements Scope, NodeCallbackInvoker, CollectedDataEmitter
{

	public const KEEP_VOID_ATTRIBUTE_NAME = 'keepVoid';
	private const CONTAINS_SUPER_GLOBAL_ATTRIBUTE_NAME = 'containsSuperGlobal';

	private const COMPLEX_UNION_TYPE_MEMBER_LIMIT = 8;

	/** @var Type[] */
	private array $resolvedTypes = [];

	/** @var array<string, static> */
	private array $truthyScopes = [];

	/** @var array<string, static> */
	private array $falseyScopes = [];

	private ?self $fiberScope = null;

	/** @var non-empty-string|null */
	private ?string $namespace;

	private ?self $scopeOutOfFirstLevelStatement = null;

	private ?self $scopeWithPromotedNativeTypes = null;

	/**
	 * @param int|array{min: int, max: int}|null $configPhpVersion
	 * @param callable(Node $node, Scope $scope): void|null $nodeCallback
	 * @param array<string, ExpressionTypeHolder> $expressionTypes
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @param list<non-empty-string> $inClosureBindScopeClasses
	 * @param array<string, true> $currentlyAssignedExpressions
	 * @param array<string, true> $currentlyAllowedUndefinedExpressions
	 * @param array<string, ExpressionTypeHolder> $nativeExpressionTypes
	 * @param list<array{MethodReflection|FunctionReflection|null, ParameterReflection|null}> $inFunctionCallsStack
	 */
	public function __construct(
		private Container $container,
		protected InternalScopeFactory $scopeFactory,
		private ReflectionProvider $reflectionProvider,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ExpressionTypeResolverExtensionRegistry $expressionTypeResolverExtensionRegistry,
		private ExprPrinter $exprPrinter,
		private TypeSpecifier $typeSpecifier,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private Parser $parser,
		private ConstantResolver $constantResolver,
		protected ScopeContext $context,
		private PhpVersion $phpVersion,
		private AttributeReflectionFactory $attributeReflectionFactory,
		private int|array|null $configPhpVersion,
		private $nodeCallback = null,
		private bool $declareStrictTypes = false,
		private PhpFunctionFromParserNodeReflection|null $function = null,
		?string $namespace = null,
		public array $expressionTypes = [],
		protected array $nativeExpressionTypes = [],
		protected array $conditionalExpressions = [],
		protected array $inClosureBindScopeClasses = [],
		private ?ClosureType $anonymousFunctionReflection = null,
		private bool $inFirstLevelStatement = true,
		protected array $currentlyAssignedExpressions = [],
		protected array $currentlyAllowedUndefinedExpressions = [],
		public array $inFunctionCallsStack = [],
		protected bool $afterExtractCall = false,
		private ?self $parentScope = null,
		public bool $nativeTypesPromoted = false,
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

		if ($this->fiberScope !== null) {
			return $this->fiberScope;
		}

		return $this->fiberScope = $this->scopeFactory->toFiberFactory()->create(
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
					// interface_exists() etc. imply class_exists() therefore not listed here
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
	public function getParentScope(): ?self
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
		$changed = false;

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
				$changed = true;
				continue 2;
			}
		}

		if (!$changed) {
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

		$errorStringFunction = '\openssl_error_string()';
		if (
			!array_key_exists($errorStringFunction, $expressionTypes)
			&& !array_key_exists($errorStringFunction, $nativeExpressionTypes)
		) {
			return $this;
		}

		$changed = false;
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
			unset($expressionTypes[$errorStringFunction]);
			unset($nativeExpressionTypes[$errorStringFunction]);
			$changed = true;
		}

		if (!$changed) {
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
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	/**
	 * Forgets every tracked volatile global-state expression: argument-less
	 * function-call expressions whose value reflects mutable global/output-buffer
	 * state rather than just their arguments (ob_get_level(), openssl_error_string()),
	 * as well as superglobal variables and their offsets. Any call to code PHPStan
	 * cannot inspect may change that state transitively, so these narrowings must be
	 * forgotten afterwards. The argument-less functions take no arguments, so the
	 * exact key lookups keep this O(1) in the common case where nothing is tracked.
	 */
	public function invalidateVolatileExpressions(): self
	{
		$expressionTypes = $this->expressionTypes;
		$nativeExpressionTypes = $this->nativeExpressionTypes;

		$changed = false;
		foreach (['ob_get_level', 'openssl_error_string'] as $functionName) {
			foreach ([$functionName . '()', '\\' . $functionName . '()'] as $exprString) {
				if (
					!array_key_exists($exprString, $expressionTypes)
					&& !array_key_exists($exprString, $nativeExpressionTypes)
				) {
					continue;
				}

				unset($expressionTypes[$exprString]);
				unset($nativeExpressionTypes[$exprString]);
				$changed = true;
			}
		}

		foreach (array_keys($expressionTypes + $nativeExpressionTypes) as $exprString) {
			if (!$this->isSuperglobalExpressionString($exprString)) {
				continue;
			}

			unset($expressionTypes[$exprString]);
			unset($nativeExpressionTypes[$exprString]);
			$changed = true;
		}

		if (!$changed) {
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
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->parentScope,
			$this->nativeTypesPromoted,
		);
	}

	private function isSuperglobalExpressionString(string $exprString): bool
	{
		foreach (self::SUPERGLOBAL_VARIABLES as $superglobalName) {
			$variableString = '$' . $superglobalName;
			if ($exprString === $variableString || str_starts_with($exprString, $variableString . '[')) {
				return true;
			}
		}

		return false;
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
		$hasVariableType = $this->hasVariableType($variableName);

		if ($hasVariableType->maybe()) {
			if ($variableName === 'argc') {
				return StaticTypeFactory::argc();
			}
			if ($variableName === 'argv') {
				return StaticTypeFactory::argv();
			}
			if ($this->canAnyVariableExist()) {
				return new MixedType();
			}
		}

		if ($hasVariableType->no()) {
			throw new UndefinedVariableException($this, $variableName);
		}

		$varExprString = '$' . $variableName;
		if (!array_key_exists($varExprString, $this->expressionTypes)) {
			if ($this->isGlobalVariable($variableName)) {
				return new ArrayType(new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType(true));
			}
			return new MixedType();
		}

		return $this->expressionTypes[$varExprString]->getType();
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

	/**
	 * @return list<string>
	 */
	public function findPossiblyImpureCallDescriptions(Expr $expr): array
	{
		$nodeFinder = new NodeFinder();
		$callExprDescriptions = [];
		$foundCallExprMatch = false;
		$matchedCallExprKeys = [];
		foreach ($this->expressionTypes as $holder) {
			$holderExpr = $holder->getExpr();
			if (!$holderExpr instanceof PossiblyImpureCallExpr) {
				continue;
			}

			$callExprKey = $this->getNodeKey($holderExpr->callExpr);

			$found = $nodeFinder->findFirst([$expr], function (Node $node) use ($callExprKey): bool {
				if (!$node instanceof Expr) {
					return false;
				}

				return $this->getNodeKey($node) === $callExprKey;
			});

			if ($found === null) {
				continue;
			}

			$foundCallExprMatch = true;
			$matchedCallExprKeys[$callExprKey] = true;

			// Only show the tip when the scope's type for the call expression
			// differs from the declared return type, meaning control flow
			// narrowing affected the type (the cached value was narrowed).
			assert($found instanceof Expr);
			$scopeType = $this->getType($found);
			$declaredReturnType = $holder->getType();
			if ($declaredReturnType->isSuperTypeOf($scopeType)->yes() && $scopeType->isSuperTypeOf($declaredReturnType)->yes()) {
				continue;
			}

			$callExprDescriptions[] = $holderExpr->getCallDescription();
		}

		// If the first pass found a callExpr in the error expression but
		// filtered it out (return type wasn't narrowed), the error is
		// explained by the return type alone - skip the fallback.
		if ($foundCallExprMatch && count($callExprDescriptions) === 0) {
			return [];
		}

		// Second pass: match by impactedExpr for cases where a maybe-impure method
		// on an object didn't invalidate it, but a different method's return
		// value was narrowed on that object.
		// Skip when the expression itself is a direct method/static call -
		// those are passed by ImpossibleCheckType rules where the error is
		// about the call's arguments, not about object state.
		if (!($expr instanceof Expr\MethodCall || $expr instanceof Expr\StaticCall)) {
			$impactedExprDescriptions = [];
			foreach ($this->expressionTypes as $holder) {
				$holderExpr = $holder->getExpr();
				if (!$holderExpr instanceof PossiblyImpureCallExpr) {
					continue;
				}

				$impactedExprKey = $this->getNodeKey($holderExpr->impactedExpr);

				// Skip if impactedExpr is the same as callExpr (function calls)
				if ($impactedExprKey === $this->getNodeKey($holderExpr->callExpr)) {
					continue;
				}

				// Skip if this entry's callExpr was already matched in the first pass
				$callExprKey = $this->getNodeKey($holderExpr->callExpr);
				if (isset($matchedCallExprKeys[$callExprKey])) {
					continue;
				}

				$found = $nodeFinder->findFirst([$expr], function (Node $node) use ($impactedExprKey): bool {
					if (!$node instanceof Expr) {
						return false;
					}

					return $this->getNodeKey($node) === $impactedExprKey;
				});

				if ($found === null) {
					continue;
				}

				$impactedExprDescriptions[] = $holderExpr->getCallDescription();
			}

			// Prefer impactedExpr matches (intermediate calls that could have
			// invalidated the object) over callExpr matches
			if (count($impactedExprDescriptions) > 0) {
				return array_values(array_unique($impactedExprDescriptions));
			}
		}

		if (count($callExprDescriptions) > 0) {
			return array_values(array_unique($callExprDescriptions));
		}

		return [];
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
		$key = $this->getNodeKey($node);

		if (!array_key_exists($key, $this->resolvedTypes)) {
			$this->resolvedTypes[$key] = TypeUtils::resolveLateResolvableTypes($this->resolveType($key, $node));
		}
		return $this->resolvedTypes[$key];
	}

	public function getScopeType(Expr $expr): Type
	{
		return $this->getType($expr);
	}

	public function getScopeNativeType(Expr $expr): Type
	{
		return $this->getNativeType($expr);
	}

	public function getNodeKey(Expr $node): string
	{
		// perf optimize for the most common path
		if ($node instanceof Variable && !$node->name instanceof Expr) {
			return '$' . $node->name;
		}

		$key = $this->exprPrinter->printExpr($node);
		$attributes = $node->getAttributes();
		if (
			$node instanceof Node\FunctionLike
			&& (($attributes[ArrayMapArgVisitor::ATTRIBUTE_NAME] ?? null) !== null)
			&& (($attributes['startFilePos'] ?? null) !== null)
		) {
			$key .= '/*' . $attributes['startFilePos'];
			foreach ($attributes[ArrayMapArgVisitor::ATTRIBUTE_NAME] as $arg) {
				$key .= ':' . $this->exprPrinter->printExpr($arg->value);
			}
			$key .= '*/';
		}

		if (($attributes[self::KEEP_VOID_ATTRIBUTE_NAME] ?? null) === true) {
			$key .= '/*' . self::KEEP_VOID_ATTRIBUTE_NAME . '*/';
		}

		return $key;
	}

	public function getClosureScopeCacheKey(): string
	{
		$parts = [];
		foreach ($this->expressionTypes as $exprString => $expressionTypeHolder) {
			if ($expressionTypeHolder->getExpr() instanceof VirtualNode) {
				continue;
			}
			$parts[] = sprintf('%s::%s', $exprString, $expressionTypeHolder->getType()->describe(VerbosityLevel::cache()));
		}
		$parts[] = '---';

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

		if (
			!$node instanceof Variable
			&& !$node instanceof Expr\Closure
			&& !$node instanceof Expr\ArrowFunction
			&& $this->hasExpressionType($node)->yes()
		) {
			return $this->expressionTypes[$exprString]->getType();
		}

		/** @var ExprHandler<Expr> $exprHandler */
		foreach ($this->container->getServicesByTag(ExprHandler::EXTENSION_TAG) as $exprHandler) {
			if (!$exprHandler->supports($node)) {
				continue;
			}

			return $exprHandler->resolveType($this, $node);
		}

		return new MixedType();
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
					$nativeReflection = $propertyReflection->getNativeReflection();
					if ($nativeReflection === null || !$nativeReflection->isPromoted() || (!$nativeReflection->isReadOnly() && !$nativeReflection->isHooked())) {
						if ($expr instanceof Node\Expr\PropertyFetch) {
							return $this->issetCheckUndefined($expr->var);
						}

						if ($expr->class instanceof Expr) {
							return $this->issetCheckUndefined($expr->class);
						}

						return null;
					}
				}
			}

			if ($result !== null) {
				if ($expr instanceof Node\Expr\PropertyFetch) {
					return $this->issetCheck($expr->var, $typeCallback, $result);
				}

				if ($expr->class instanceof Expr) {
					return $this->issetCheck($expr->class, $typeCallback, $result);
				}

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
		if (
			!$node instanceof Match_
			&& (
				(
					!$node instanceof FuncCall
					&& !$node instanceof MethodCall
					&& !$node instanceof Expr\NullsafeMethodCall
					&& !$node instanceof Expr\StaticCall
				) || $node->isFirstClassCallable()
			)
		) {
			return $this->getType($node);
		}

		$originalType = $this->getType($node);
		if (!TypeCombinator::containsNull($originalType)) {
			return $originalType;
		}

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
	public function pushInFunctionCall($reflection, ?ParameterReflection $parameter, bool $rememberTypes): self
	{
		$stack = $this->inFunctionCallsStack;
		$stack[] = [$reflection, $parameter];

		$functionScope = $this->scopeFactory->create(
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

		if ($rememberTypes) {
			$functionScope->resolvedTypes = $this->resolvedTypes;
		}

		return $functionScope;
	}

	public function popInFunctionCall(): self
	{
		$stack = $this->inFunctionCallsStack;
		array_pop($stack);

		$parentScope = $this->scopeFactory->create(
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

		$parentScope->resolvedTypes = $this->resolvedTypes;

		return $parentScope;
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
				'enum_exists',
			], true)) {
				return true;
			}
		}

		// interface_exists() etc. imply class_exists() therefore not listed here
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
		?ResolvedPhpDocBlock $resolvedPhpDocBlock = null,
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
				$resolvedPhpDocBlock,
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
		?ResolvedPhpDocBlock $resolvedPhpDocBlock = null,
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
				$resolvedPhpDocBlock,
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
		return TypeTraverser::map($type, new TransformStaticTypeTraverser($this));
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

		$functionParameters = $functionReflection->getParameters();
		foreach ($functionParameters as $parameter) {
			$parametersByName[$parameter->getName()] = $parameter;
		}

		$expressionTypes = [];
		$nativeExpressionTypes = [];
		$conditionalTypes = [];

		if ($preserveConstructorScope) {
			$expressionTypes = $this->expressionTypes;
			$nativeExpressionTypes = $this->nativeExpressionTypes;
		}

		foreach ($functionParameters as $parameter) {
			$parameterType = $parameter->getType();

			if ($parameterType instanceof ConditionalTypeForParameter) {
				$targetParameterName = substr($parameterType->getParameterName(), 1);
				if (array_key_exists($targetParameterName, $parametersByName)) {
					$targetParameter = $parametersByName[$targetParameterName];

					$ifType = $parameterType->isNegated() ? $parameterType->getElse() : $parameterType->getIf();
					$elseType = $parameterType->isNegated() ? $parameterType->getIf() : $parameterType->getElse();

					$holder = new ConditionalExpressionHolder([
						$parameterType->getParameterName() => ExpressionTypeHolder::createYes(new Variable($targetParameterName), TypeCombinator::intersect($targetParameter->getType(), $parameterType->getTarget())),
					], ExpressionTypeHolder::createYes(new Variable($parameter->getName()), $ifType));
					$conditionalTypes['$' . $parameter->getName()][$holder->getKey()] = $holder;

					$holder = new ConditionalExpressionHolder([
						$parameterType->getParameterName() => ExpressionTypeHolder::createYes(new Variable($targetParameterName), TypeCombinator::remove($targetParameter->getType(), $parameterType->getTarget())),
					], ExpressionTypeHolder::createYes(new Variable($parameter->getName()), $elseType));
					$conditionalTypes['$' . $parameter->getName()][$holder->getKey()] = $holder;
				}
			}

			$paramExprString = '$' . $parameter->getName();
			if ($parameter->isVariadic()) {
				if (!$this->getPhpVersion()->supportsNamedArguments()->no() && $functionReflection->acceptsNamedArguments()->yes()) {
					$parameterType = new ArrayType(new UnionType([IntegerRangeType::createAllGreaterThanOrEqualTo(0), new StringType()]), $parameterType);
				} else {
					$parameterType = new IntersectionType([new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $parameterType), new AccessoryArrayListType()]);
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
					$nativeParameterType = new ArrayType(new UnionType([IntegerRangeType::createAllGreaterThanOrEqualTo(0), new StringType()]), $nativeParameterType);
				} else {
					$nativeParameterType = new IntersectionType([new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $nativeParameterType), new AccessoryArrayListType()]);
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
	 * @param list<non-empty-string> $scopeClasses
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
			foreach ($restoreThisScope->expressionTypes as $exprString => $expressionTypeHolder) {
				if (!str_starts_with($exprString, '$this')) {
					continue;
				}

				$expressionTypes[$exprString] = $expressionTypeHolder;
			}

			foreach ($restoreThisScope->nativeExpressionTypes as $exprString => $expressionTypeHolder) {
				if (!str_starts_with($exprString, '$this')) {
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
	 * @param ParameterReflection[]|null $nativeCallableParameters
	 */
	public function enterAnonymousFunction(
		Expr\Closure $closure,
		?array $callableParameters,
		?array $nativeCallableParameters = null,
	): self
	{
		$anonymousFunctionReflection = $this->resolveType('__phpstanClosure', $closure);
		if (!$anonymousFunctionReflection instanceof ClosureType) {
			throw new ShouldNotHappenException();
		}

		$scope = $this->enterAnonymousFunctionWithoutReflection($closure, $callableParameters, $nativeCallableParameters);

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
			false,
			$this,
			$this->nativeTypesPromoted,
		);
	}

	/**
	 * @param ParameterReflection[]|null $callableParameters
	 * @param ParameterReflection[]|null $nativeCallableParameters
	 */
	public function enterAnonymousFunctionWithoutReflection(
		Expr\Closure $closure,
		?array $callableParameters,
		?array $nativeCallableParameters,
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
			$nativeParameterType = $parameterType = $this->getFunctionType($parameter->type, $isNullable, $parameter->variadic);
			if ($callableParameters !== null) {
				$parameterType = self::intersectButNotNever($parameterType, $this->getCallableParameterType($parameter, $callableParameters, $i));
			}
			if ($nativeCallableParameters !== null) {
				$nativeParameterType = self::intersectButNotNever($nativeParameterType, $this->getCallableParameterType($parameter, $nativeCallableParameters, $i));
			}
			$expressionTypes[$paramExprString] = ExpressionTypeHolder::createYes($parameter->var, $parameterType);
			$nativeTypes[$paramExprString] = ExpressionTypeHolder::createYes($parameter->var, $nativeParameterType);
		}

		$nonRefVariableNames = [];
		$useVariableNames = [];
		foreach ($closure->uses as $use) {
			if (!is_string($use->var->name)) {
				throw new ShouldNotHappenException();
			}
			$variableName = $use->var->name;
			$paramExprString = '$' . $use->var->name;
			$useVariableNames[$paramExprString] = true;
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

		$filteredConditionalExpressions = [];
		foreach ($this->conditionalExpressions as $conditionalExprString => $holders) {
			if (!array_key_exists($conditionalExprString, $useVariableNames)) {
				continue;
			}
			$filteredHolders = [];
			foreach ($holders as $holder) {
				foreach (array_keys($holder->getConditionExpressionTypeHolders()) as $holderExprString) {
					if (!array_key_exists($holderExprString, $useVariableNames)) {
						continue 2;
					}
				}
				$filteredHolders[] = $holder;
			}
			if ($filteredHolders === []) {
				continue;
			}

			$filteredConditionalExpressions[$conditionalExprString] = $filteredHolders;
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			array_merge($this->getConstantTypes(), $expressionTypes),
			array_merge($this->getNativeConstantTypes(), $nativeTypes),
			$filteredConditionalExpressions,
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
			&& in_array(
				$expr->name->toLowerString(),
				[
					'class_exists',
					'interface_exists',
					'trait_exists',
					'enum_exists',
					'function_exists',
				],
				true,
			)
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
	 * @param ParameterReflection[]|null $nativeCallableParameters
	 */
	public function enterArrowFunction(Expr\ArrowFunction $arrowFunction, ?array $callableParameters, ?array $nativeCallableParameters = null): self
	{
		$anonymousFunctionReflection = $this->resolveType('__phpStanArrowFn', $arrowFunction);
		if (!$anonymousFunctionReflection instanceof ClosureType) {
			throw new ShouldNotHappenException();
		}

		$scope = $this->enterArrowFunctionWithoutReflection($arrowFunction, $callableParameters, $nativeCallableParameters);

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
	 * @param ParameterReflection[]|null $nativeCallableParameters
	 */
	public function enterArrowFunctionWithoutReflection(Expr\ArrowFunction $arrowFunction, ?array $callableParameters, ?array $nativeCallableParameters): self
	{
		$arrowFunctionScope = $this;
		foreach ($arrowFunction->params as $i => $parameter) {
			$isNullable = $this->isParameterValueNullable($parameter);
			$nativeParameterType = $parameterType = $this->getFunctionType($parameter->type, $isNullable, $parameter->variadic);
			if ($callableParameters !== null) {
				$parameterType = self::intersectButNotNever($parameterType, $this->getCallableParameterType($parameter, $callableParameters, $i));
			}
			if ($nativeCallableParameters !== null) {
				$nativeParameterType = self::intersectButNotNever($nativeParameterType, $this->getCallableParameterType($parameter, $nativeCallableParameters, $i));
			}

			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new ShouldNotHappenException();
			}
			$arrowFunctionScope = $arrowFunctionScope->assignVariable($parameter->var->name, $parameterType, $nativeParameterType, TrinaryLogic::createYes());
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
				return new ArrayType(new UnionType([IntegerRangeType::createAllGreaterThanOrEqualTo(0), new StringType()]), $this->getFunctionType(
					$type,
					$isNullable,
					false,
				));
			}

			return new IntersectionType([new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $this->getFunctionType(
				$type,
				$isNullable,
				false,
			)), new AccessoryArrayListType()]);
		}
		return $this->initializerExprTypeResolver->getFunctionType($type, $isNullable, false, InitializerExprContext::fromScope($this));
	}

	/**
	 * @param ParameterReflection[] $callableParameters
	 */
	private function getCallableParameterType(Node\Param $parameter, array $callableParameters, int $index): Type
	{
		if ($parameter->variadic) {
			return $this->buildVariadicArrayTypeFromCallableParameters($callableParameters, $index);
		}

		if (isset($callableParameters[$index])) {
			return $callableParameters[$index]->getType();
		}

		if (count($callableParameters) === 0) {
			return new MixedType();
		}

		$lastParameter = array_last($callableParameters);
		if ($lastParameter->isVariadic()) {
			return $lastParameter->getType();
		}

		return new MixedType();
	}

	/**
	 * @param array<ParameterReflection> $callableParameters
	 */
	private function buildVariadicArrayTypeFromCallableParameters(array $callableParameters, int $startIndex): Type
	{
		$elementTypes = [];
		$callableParametersCount = count($callableParameters);
		for ($j = $startIndex; $j < $callableParametersCount; $j++) {
			$elementTypes[] = $callableParameters[$j]->getType();
			if ($callableParameters[$j]->isVariadic()) {
				break;
			}
		}

		if ($elementTypes === [] && $callableParametersCount > 0) {
			$lastParameter = array_last($callableParameters);
			if ($lastParameter->isVariadic()) {
				$elementTypes[] = $lastParameter->getType();
			}
		}

		if ($elementTypes === []) {
			return new MixedType();
		}

		$elementType = TypeCombinator::union(...$elementTypes);

		if (!$this->getPhpVersion()->supportsNamedArguments()->no()) {
			return new ArrayType(new UnionType([IntegerRangeType::createAllGreaterThanOrEqualTo(0), new StringType()]), $elementType);
		}

		return new IntersectionType([new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $elementType), new AccessoryArrayListType()]);
	}

	public static function intersectButNotNever(Type $nativeType, Type $inferredType): Type
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

	public function enterMatch(Expr\Match_ $expr, Type $condType, Type $condNativeType): self
	{
		if ($expr->cond instanceof Variable) {
			return $this;
		}
		if ($expr->cond instanceof AlwaysRememberedExpr) {
			$cond = $expr->cond->expr;
		} else {
			$cond = $expr->cond;
		}
		if ($cond instanceof Scalar) {
			return $this;
		}

		$type = $condType;
		$nativeType = $condNativeType;
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
		// Track the original foreach value so narrowings applied to the value
		// variable (e.g. is_string($type)) can later be projected back onto the
		// corresponding array dim fetch without being confused by a reassignment
		// ($type = 'foo' invalidates this expression, same as OriginalForeachKeyExpr).
		$scope = $scope->assignExpression(new OriginalForeachValueExpr($valueName), $valueType, $nativeValueType);
		if ($valueByRef && $iterateeType->isArray()->yes() && $iterateeType->isConstantArray()->no()) {
			$scope = $scope->assignExpression(
				new IntertwinedVariableByReferenceWithExpr($valueName, $iteratee, new SetExistingOffsetValueTypeExpr(
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
		if (count($this->currentlyAssignedExpressions) === 0) {
			return false;
		}

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
		if (count($this->currentlyAllowedUndefinedExpressions) === 0) {
			return false;
		}
		$exprString = $this->getNodeKey($expr);
		return array_key_exists($exprString, $this->currentlyAllowedUndefinedExpressions);
	}

	/**
	 * @param list<string> $intertwinedPropagatedFrom
	 */
	public function assignVariable(string $variableName, Type $type, Type $nativeType, TrinaryLogic $certainty, array $intertwinedPropagatedFrom = []): self
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

		foreach ($scope->expressionTypes as $exprString => $expressionType) {
			if (!$expressionType->getExpr() instanceof IntertwinedVariableByReferenceWithExpr) {
				continue;
			}
			if (!$expressionType->getCertainty()->yes()) {
				continue;
			}
			if ($expressionType->getExpr()->getVariableName() !== $variableName) {
				continue;
			}

			$assignedExpr = $expressionType->getExpr()->getAssignedExpr();
			if (
				$assignedExpr instanceof Expr\ArrayDimFetch
				&& !$this->isDimFetchPathReachable($scope, $assignedExpr)
			) {
				unset($scope->expressionTypes[$exprString]);
				unset($scope->nativeExpressionTypes[$exprString]);
				continue;
			}

			// When the byref's dim is non-constant AND not enumerable as a
			// finite set of scalars (e.g. general `int` or `mixed`), the just-
			// performed write to $array might or might not have hit the byref's
			// slot. Union the new $array[dim] read with the byref's previous
			// type and the pre-write $array[dim] so values that could still be
			// at the slot (unmodified or shadowed by an explicit-key overwrite)
			// survive. For finitely-enumerable dims (e.g. `bool`, `int<0, 5>`)
			// the array literal builder enumerates all possibilities, so the
			// new $array[dim] read already covers every reachable slot.
			$unionWithOld = false;
			if ($assignedExpr instanceof Expr\ArrayDimFetch && $assignedExpr->dim !== null) {
				$dimType = $scope->getType($assignedExpr->dim);
				if (count($dimType->getConstantScalarValues()) !== 1 && count($dimType->getFiniteTypes()) === 0) {
					$unionWithOld = true;
				}
			}

			$assignedType = $scope->getType($assignedExpr);
			$assignedNativeType = $scope->getNativeType($assignedExpr);

			$has = $scope->hasExpressionType($expressionType->getExpr()->getExpr());
			if (
				$expressionType->getExpr()->getExpr() instanceof Variable
				&& is_string($expressionType->getExpr()->getExpr()->name)
				&& !$has->no()
			) {
				$targetVarName = $expressionType->getExpr()->getExpr()->name;
				if (in_array($targetVarName, $intertwinedPropagatedFrom, true)) {
					continue;
				}
				if ($unionWithOld) {
					$targetVarNode = new Variable($targetVarName);
					$assignedType = TypeCombinator::union(
						$assignedType,
						$this->getType($assignedExpr),
						$scope->getType($targetVarNode),
					);
					$assignedNativeType = TypeCombinator::union(
						$assignedNativeType,
						$this->getNativeType($assignedExpr),
						$scope->getNativeType($targetVarNode),
					);
				}
				$scope = $scope->assignVariable(
					$targetVarName,
					$assignedType,
					$assignedNativeType,
					$has,
					array_merge($intertwinedPropagatedFrom, [$variableName]),
				);
			} else {
				$targetRootVar = $this->getIntertwinedRefRootVariableName($expressionType->getExpr()->getExpr());
				if ($targetRootVar !== null && in_array($targetRootVar, $intertwinedPropagatedFrom, true)) {
					continue;
				}
				$scope = $scope->assignExpression(
					$expressionType->getExpr()->getExpr(),
					$assignedType,
					$assignedNativeType,
				);
			}
		}

		return $scope;
	}

	private function isDimFetchPathReachable(self $scope, Expr\ArrayDimFetch $dimFetch): bool
	{
		if ($dimFetch->dim === null) {
			return false;
		}

		if (!$dimFetch->var instanceof Expr\ArrayDimFetch) {
			return true;
		}

		$varType = $scope->getType($dimFetch->var);
		$dimType = $scope->getType($dimFetch->dim);

		if (!$varType->hasOffsetValueType($dimType)->yes()) {
			return false;
		}

		return $this->isDimFetchPathReachable($scope, $dimFetch->var);
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
		if ($expr instanceof Scalar) {
			return $this;
		}

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
				$isArray = $exprVarType->isArray();
				if (!$exprVarType instanceof MixedType && !$isArray->no()) {
					$varType = $exprVarType;
					if (!$isArray->yes()) {
						if ($dimType->isInteger()->yes()) {
							$varType = TypeCombinator::intersect($exprVarType, StaticTypeFactory::intOffsetAccessibleType());
						} else {
							$varType = TypeCombinator::intersect($exprVarType, StaticTypeFactory::generalOffsetAccessibleType());
						}
					}

					if ($dimType instanceof ConstantIntegerType || $dimType instanceof ConstantStringType) {
						if (!$this->isComplexUnionType($varType)) {
							$varType = TypeCombinator::intersect(
								$varType,
								new HasOffsetValueType($dimType, $type),
							);
						}
					}

					$scope = $scope->specifyExpressionType(
						$expr->var,
						$varType,
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

		$scope = $this->assignExpression(new PropertyInitializationExpr($propertyName), new MixedType(), new MixedType());

		$function = $scope->getFunction();
		if (
			$function instanceof MethodReflection
			&& strtolower($function->getName()) === '__clone'
			&& $scope->phpVersion->supportsReadonlyPropertyReinitializationOnClone()
		) {
			$scope = $scope->assignExpression(new CloneReinitializationExpr($propertyName), new MixedType(), new MixedType());
		}

		return $scope;
	}

	public function invalidateExpression(Expr $expressionToInvalidate, bool $requireMoreCharacters = false, ?ClassReflection $invalidatingClass = null): self
	{
		$expressionTypes = $this->expressionTypes;
		$nativeExpressionTypes = $this->nativeExpressionTypes;
		$invalidated = false;
		$exprStringToInvalidate = $this->getNodeKey($expressionToInvalidate);

		foreach ($expressionTypes as $exprString => $exprTypeHolder) {
			$exprExpr = $exprTypeHolder->getExpr();
			if (!$this->shouldInvalidateExpression($exprStringToInvalidate, $expressionToInvalidate, $exprExpr, $exprString, $requireMoreCharacters, $invalidatingClass)) {
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
			$firstExpr = $holders[array_key_first($holders)]->getTypeHolder()->getExpr();
			if ($this->shouldInvalidateExpression($exprStringToInvalidate, $expressionToInvalidate, $firstExpr, $this->getNodeKey($firstExpr), $requireMoreCharacters, $invalidatingClass)) {
				$invalidated = true;
				continue;
			}
			$filteredHolders = [];
			foreach ($holders as $key => $holder) {
				$shouldKeep = true;
				$conditionalTypeHolders = $holder->getConditionExpressionTypeHolders();
				foreach ($conditionalTypeHolders as $conditionalTypeHolderExprString => $conditionalTypeHolder) {
					if ($this->shouldInvalidateExpression($exprStringToInvalidate, $expressionToInvalidate, $conditionalTypeHolder->getExpr(), $conditionalTypeHolderExprString, false, $invalidatingClass)) {
						$invalidated = true;
						$shouldKeep = false;
						break;
					}
				}
				if (!$shouldKeep) {
					continue;
				}

				$filteredHolders[$key] = $holder;
			}
			if (count($filteredHolders) <= 0) {
				continue;
			}

			$newConditionalExpressions[$conditionalExprString] = $filteredHolders;
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

	private function getIntertwinedRefRootVariableName(Expr $expr): ?string
	{
		if ($expr instanceof Variable && is_string($expr->name)) {
			return $expr->name;
		}
		if ($expr instanceof Expr\ArrayDimFetch) {
			return $this->getIntertwinedRefRootVariableName($expr->var);
		}
		return null;
	}

	private function shouldInvalidateExpression(string $exprStringToInvalidate, Expr $exprToInvalidate, Expr $expr, string $exprString, bool $requireMoreCharacters = false, ?ClassReflection $invalidatingClass = null): bool
	{
		if (
			$expr instanceof IntertwinedVariableByReferenceWithExpr
			&& $exprToInvalidate instanceof Variable
			&& is_string($exprToInvalidate->name)
			&& (
				$expr->getVariableName() === $exprToInvalidate->name
				|| $this->getIntertwinedRefRootVariableName($expr->getExpr()) === $exprToInvalidate->name
				|| $this->getIntertwinedRefRootVariableName($expr->getAssignedExpr()) === $exprToInvalidate->name
			)
		) {
			return false;
		}

		if ($requireMoreCharacters && $exprStringToInvalidate === $exprString) {
			return false;
		}

		// Variables will not contain traversable expressions. skip the NodeFinder overhead
		if ($expr instanceof Variable && is_string($expr->name) && !$requireMoreCharacters) {
			return $exprStringToInvalidate === $exprString;
		}

		// getNodeKey() is the pretty-printed expression, and the standard printer is
		// compositional: the key of any sub-expression appears verbatim as a substring of
		// the key of the expression containing it. So if the invalidated expression's key
		// does not appear anywhere in this expression's key, this expression cannot contain
		// it and we can skip the expensive AST traversal below.
		// Carve-outs where that invariant does not hold:
		// - '$this' is special-cased in the visitor to also match self/static/parent,
		// - PHPStan's virtual nodes (printed as '__phpstan…') use non-compositional printers
		//   (e.g. a wrapped variable is printed by name, not as '$name'),
		// - keys carrying a getNodeKey() suffix ('/*…*/') are not plain substrings.
		if (
			$exprStringToInvalidate !== '$this'
			&& !str_contains($exprStringToInvalidate, '__phpstan')
			&& !str_contains($exprStringToInvalidate, '/*')
			&& !str_contains($exprString, '__phpstan')
			&& !str_contains($exprString, $exprStringToInvalidate)
		) {
			return false;
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

		if (
			$invalidatingClass !== null
			&& $requireMoreCharacters
			&& $this->isPrivatePropertyOfDifferentClass($expr, $invalidatingClass)
		) {
			return false;
		}

		return true;
	}

	private function isPrivatePropertyOfDifferentClass(Expr $expr, ClassReflection $invalidatingClass): bool
	{
		if ($expr instanceof Expr\StaticPropertyFetch || $expr instanceof PropertyFetch) {
			$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($expr, $this);
			if ($propertyReflection === null) {
				return false;
			}
			if (!$propertyReflection->isPrivate()) {
				return false;
			}
			return $propertyReflection->getDeclaringClass()->getName() !== $invalidatingClass->getName();
		}

		return false;
	}

	private function invalidateMethodsOnExpression(Expr $expressionToInvalidate): self
	{
		$exprStringToInvalidate = null;
		$expressionTypes = $this->expressionTypes;
		$nativeExpressionTypes = $this->nativeExpressionTypes;
		$invalidated = false;
		foreach ($expressionTypes as $exprString => $exprTypeHolder) {
			$expr = $exprTypeHolder->getExpr();
			if (!$expr instanceof MethodCall) {
				continue;
			}

			$exprStringToInvalidate ??= $this->getNodeKey($expressionToInvalidate);
			if ($this->getNodeKey($expr->var) !== $exprStringToInvalidate) {
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

	/**
	 * Returns true when the type is a large union with intersection
	 * members that carry HasOffsetValueType — a sign of combinatorial
	 * growth from successive array|object offset access patterns.
	 * Operating on such types is expensive and should be skipped.
	 */
	private function isComplexUnionType(Type $type): bool
	{
		if (!$type instanceof UnionType) {
			return false;
		}
		$types = $type->getTypes();
		if (count($types) <= self::COMPLEX_UNION_TYPE_MEMBER_LIMIT) {
			return false;
		}
		foreach ($types as $member) {
			if (!$member instanceof IntersectionType) {
				continue;
			}
			foreach ($member->getTypes() as $innerType) {
				if ($innerType instanceof HasOffsetValueType) {
					return true;
				}
			}
		}
		return false;
	}

	public function addTypeToExpression(Expr $expr, Type $type): self
	{
		$originalExprType = $this->getType($expr);
		if ($this->isComplexUnionType($originalExprType)) {
			return $this;
		}

		$nativeType = $this->getNativeType($expr);

		if ($originalExprType->equals($nativeType)) {
			$newType = TypeCombinator::intersect($type, $originalExprType);
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
		if ($typeToRemove instanceof NeverType) {
			return $this;
		}

		$exprType = $this->getType($expr);
		if ($exprType instanceof NeverType) {
			return $this;
		}

		if ($this->isComplexUnionType($exprType)) {
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
	 */
	public function filterByTruthyValue(Expr $expr): self
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
	 */
	public function filterByFalseyValue(Expr $expr): self
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

	/**
	 * @return static
	 */
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
			$specifiedExpressions[$typeSpecification['exprString']] = ExpressionTypeHolder::createYes($expr, $scope->getScopeType($expr));
		}

		$conditions = [];
		$originallySpecifiedExprStrings = $specifiedExpressions;
		$prevSpecifiedCount = -1;
		while (count($specifiedExpressions) !== $prevSpecifiedCount) {
			$prevSpecifiedCount = count($specifiedExpressions);
			foreach ($scope->conditionalExpressions as $conditionalExprString => $conditionalExpressions) {
				if (array_key_exists($conditionalExprString, $conditions)) {
					continue;
				}

				// Pass 1: Prefer exact matches
				foreach ($conditionalExpressions as $conditionalExpression) {
					if (
						$conditionalExpression->getTypeHolder()->getCertainty()->no()
						&& array_key_exists($conditionalExprString, $originallySpecifiedExprStrings)
					) {
						continue;
					}
					foreach ($conditionalExpression->getConditionExpressionTypeHolders() as $holderExprString => $conditionalTypeHolder) {
						if (
							!array_key_exists($holderExprString, $specifiedExpressions)
							|| !$conditionalTypeHolder->equals($specifiedExpressions[$holderExprString])
						) {
							continue 2;
						}
					}

					$conditions[$conditionalExprString][] = $conditionalExpression;
					$specifiedExpressions[$conditionalExprString] = $conditionalExpression->getTypeHolder();
				}

				if (array_key_exists($conditionalExprString, $conditions)) {
					continue;
				}

				// Pass 2: Supertype match. Only runs when Pass 1 found no exact match for this expression.
				foreach ($conditionalExpressions as $conditionalExpression) {
					if ($conditionalExpression->getTypeHolder()->getCertainty()->no()) {
						continue;
					}
					foreach ($conditionalExpression->getConditionExpressionTypeHolders() as $holderExprString => $conditionalTypeHolder) {
						if (
							!array_key_exists($holderExprString, $specifiedExpressions)
							|| !$conditionalTypeHolder->getCertainty()->equals($specifiedExpressions[$holderExprString]->getCertainty())
							|| !$conditionalTypeHolder->getType()->isSuperTypeOf($specifiedExpressions[$holderExprString]->getType())->yes()
						) {
							continue 2;
						}
					}

					$conditions[$conditionalExprString][] = $conditionalExpression;
					$specifiedExpressions[$conditionalExprString] = $conditionalExpression->getTypeHolder();
				}
			}
		}

		foreach ($conditions as $conditionalExprString => $expressions) {
			$certainty = TrinaryLogic::lazyExtremeIdentity($expressions, static fn (ConditionalExpressionHolder $holder) => $holder->getTypeHolder()->getCertainty());
			if ($certainty->no()) {
				unset($scope->expressionTypes[$conditionalExprString]);
			} else {
				if (array_key_exists($conditionalExprString, $scope->expressionTypes)) {
					$type = $expressions[0]->getTypeHolder()->getType();
					for ($i = 1, $count = count($expressions); $i < $count; $i++) {
						$type = TypeCombinator::intersect($type, $expressions[$i]->getTypeHolder()->getType());
					}

					$scope->expressionTypes[$conditionalExprString] = new ExpressionTypeHolder(
						$scope->expressionTypes[$conditionalExprString]->getExpr(),
						TypeCombinator::intersect($scope->expressionTypes[$conditionalExprString]->getType(), $type),
						TrinaryLogic::maxMin($scope->expressionTypes[$conditionalExprString]->getCertainty(), $certainty),
					);
				} else {
					$scope->expressionTypes[$conditionalExprString] = $expressions[0]->getTypeHolder();
				}
			}
		}

		/** @var static */
		return $scope->scopeFactory->create(
			$scope->context,
			$scope->isDeclareStrictTypes(),
			$scope->getFunction(),
			$scope->getNamespace(),
			$scope->expressionTypes,
			$scope->nativeExpressionTypes,
			$this->mergeConditionalExpressions($specifiedTypes->getNewConditionalExpressionHolders(), $scope->conditionalExpressions),
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
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	public function getConditionalExpressions(): array
	{
		return $this->conditionalExpressions;
	}

	/**
	 * @param ConditionalExpressionHolder[] $conditionalExpressionHolders
	 */
	public function addConditionalExpressions(string $exprString, array $conditionalExpressionHolders): self
	{
		$conditionalExpressions = $this->conditionalExpressions;
		// Merge rather than overwrite: multiple independent holders can target the same
		// expression (e.g. `$xIsA = $x instanceof A && $y instanceof A` stores a holder
		// for `$x` keyed on `$xIsA`; later `$yIsA = $y instanceof A && $x instanceof A`
		// stores another holder for the same target `$x` keyed on `$yIsA`). Replacing
		// the existing entry here would throw away the earlier binding, breaking
		// narrowing inside later `if ($xIsA) { … }` inside `if ($xIsA || $yIsA)`.
		// Holder keys (`getKey()`) disambiguate identical entries so we still dedupe.
		$existing = $conditionalExpressions[$exprString] ?? [];
		foreach ($conditionalExpressionHolders as $holder) {
			$existing[$holder->getKey()] = $holder;
		}
		$conditionalExpressions[$exprString] = $existing;
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

	public function mergeWith(?self $otherScope, bool $preserveVacuousConditionals = false): self
	{
		if ($otherScope === null || $this === $otherScope) {
			return $this;
		}
		$ourExpressionTypes = $this->expressionTypes;
		$theirExpressionTypes = $otherScope->expressionTypes;

		$mergedExpressionTypes = $this->mergeVariableHolders($ourExpressionTypes, $theirExpressionTypes);
		$conditionalExpressions = $this->intersectConditionalExpressions($otherScope->conditionalExpressions);
		if ($preserveVacuousConditionals) {
			$conditionalExpressions = $this->preserveVacuousConditionalExpressions(
				$conditionalExpressions,
				$this->conditionalExpressions,
				$theirExpressionTypes,
			);
			$conditionalExpressions = $this->preserveVacuousConditionalExpressions(
				$conditionalExpressions,
				$otherScope->conditionalExpressions,
				$ourExpressionTypes,
			);
		}
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

		$filter = static function (ExpressionTypeHolder $expressionTypeHolder) {
			if ($expressionTypeHolder->getCertainty()->yes()) {
				return true;
			}

			$expr = $expressionTypeHolder->getExpr();

			return $expr instanceof Variable
				|| $expr instanceof FuncCall
				|| $expr instanceof VirtualNode;
		};

		$mergedExpressionTypes = array_filter($mergedExpressionTypes, $filter);

		$ourNativeExpressionTypes = $this->nativeExpressionTypes;
		$theirNativeExpressionTypes = $otherScope->nativeExpressionTypes;
		$mergedNativeExpressionTypes = [];
		foreach ($ourNativeExpressionTypes as $exprString => $expressionTypeHolder) {
			if (!array_key_exists($exprString, $theirNativeExpressionTypes)) {
				continue;
			}
			if (!array_key_exists($exprString, $ourExpressionTypes)) {
				continue;
			}
			if (!array_key_exists($exprString, $theirExpressionTypes)) {
				continue;
			}
			if (!$expressionTypeHolder->equals($ourExpressionTypes[$exprString])) {
				continue;
			}
			if (!$theirNativeExpressionTypes[$exprString]->equals($theirExpressionTypes[$exprString])) {
				continue;
			}
			if (!array_key_exists($exprString, $mergedExpressionTypes)) {
				continue;
			}
			$mergedNativeExpressionTypes[$exprString] = $mergedExpressionTypes[$exprString];
			unset($ourNativeExpressionTypes[$exprString]);
			unset($theirNativeExpressionTypes[$exprString]);
		}

		return $this->scopeFactory->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$mergedExpressionTypes,
			array_merge($mergedNativeExpressionTypes, array_filter($this->mergeVariableHolders($ourNativeExpressionTypes, $theirNativeExpressionTypes), $filter)),
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
			$intersectedHolders = [];
			foreach ($holders as $key => $holder) {
				if (!array_key_exists($key, $otherHolders)) {
					continue;
				}
				$intersectedHolders[$key] = $holder;
			}

			if (count($intersectedHolders) === 0) {
				continue;
			}

			$newConditionalExpressions[$exprString] = $intersectedHolders;
		}

		return $newConditionalExpressions;
	}

	/**
	 * @param array<string, ConditionalExpressionHolder[]> $currentConditionalExpressions
	 * @param array<string, ConditionalExpressionHolder[]> $sourceConditionalExpressions
	 * @param array<string, ExpressionTypeHolder> $otherExpressionTypes
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function preserveVacuousConditionalExpressions(
		array $currentConditionalExpressions,
		array $sourceConditionalExpressions,
		array $otherExpressionTypes,
	): array
	{
		foreach ($sourceConditionalExpressions as $exprString => $holders) {
			foreach ($holders as $key => $holder) {
				if (isset($currentConditionalExpressions[$exprString][$key])) {
					continue;
				}

				$typeHolder = $holder->getTypeHolder();
				if ($typeHolder->getCertainty()->no() && !$typeHolder->getExpr() instanceof Variable) {
					continue;
				}

				foreach ($holder->getConditionExpressionTypeHolders() as $guardExprString => $guardTypeHolder) {
					if (!array_key_exists($guardExprString, $otherExpressionTypes)) {
						continue;
					}

					$otherType = $otherExpressionTypes[$guardExprString]->getType();
					$guardType = $guardTypeHolder->getType();

					if ($otherType->isSuperTypeOf($guardType)->no()) {
						$currentConditionalExpressions[$exprString][$key] = $holder;
						break;
					}
				}
			}
		}

		return $currentConditionalExpressions;
	}

	/**
	 * @param array<string, ConditionalExpressionHolder[]> $newConditionalExpressions
	 * @param array<string, ConditionalExpressionHolder[]> $existingConditionalExpressions
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function mergeConditionalExpressions(array $newConditionalExpressions, array $existingConditionalExpressions): array
	{
		$result = $existingConditionalExpressions;
		foreach ($newConditionalExpressions as $exprString => $holders) {
			if (!array_key_exists($exprString, $result)) {
				$result[$exprString] = $holders;
			} else {
				$result[$exprString] = array_merge($result[$exprString], $holders);
			}
		}

		return $result;
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

		// When our-branch type is a subtype of their-branch type, the union
		// absorbs it (merged === their). Such a variable is a poor *guard* —
		// asserting its our-branch type later wouldn't reliably select this
		// branch — but it remains a valid conditional *target*, so only exclude
		// it from guard selection instead of dropping it entirely.
		$guardsToExclude = [];
		foreach ($theirExpressionTypes as $exprString => $holder) {
			if (!array_key_exists($exprString, $mergedExpressionTypes)) {
				continue;
			}

			if (!$mergedExpressionTypes[$exprString]->equalTypes($holder)) {
				continue;
			}

			if (
				array_key_exists($exprString, $newVariableTypes)
				&& !$newVariableTypes[$exprString]->getCertainty()->equals($holder->getCertainty())
				&& $newVariableTypes[$exprString]->equalTypes($holder)
			) {
				continue;
			}

			$guardsToExclude[$exprString] = true;
		}

		$typeGuards = [];
		foreach ($newVariableTypes as $exprString => $holder) {
			if ($holder->getExpr() instanceof VirtualNode) {
				continue;
			}
			if (!array_key_exists($exprString, $mergedExpressionTypes)) {
				continue;
			}
			if (!$holder->getCertainty()->yes()) {
				continue;
			}
			if (array_key_exists($exprString, $guardsToExclude)) {
				continue;
			}

			if (
				array_key_exists($exprString, $theirExpressionTypes)
				&& !$theirExpressionTypes[$exprString]->getCertainty()->yes()
			) {
				continue;
			}

			if ($mergedExpressionTypes[$exprString]->equalTypes($holder)) {
				continue;
			}

			$typeGuards[$exprString] = $holder;
		}

		if (count($typeGuards) === 0) {
			return $conditionalExpressions;
		}

		// Both isSuperTypeOf() checks below depend only on the guard (and its
		// their-branch type), not on the target $exprString, so their results are
		// invariant across the target loop. Cache them per guard to avoid
		// recomputing expensive supertype checks on big union / constant-array
		// types once per (target, guard) pair.
		$guardIsSuperTypeOfTheirExprCache = [];
		$theirExprIsSuperTypeOfGuardCache = [];

		foreach ($newVariableTypes as $exprString => $holder) {
			if ($holder->getExpr() instanceof VirtualNode) {
				continue;
			}
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

			$exprIsGuardExcluded = array_key_exists($exprString, $guardsToExclude);
			foreach ($variableTypeGuards as $guardExprString => $guardHolder) {
				// A subtype-absorbed target (kept only for re-narrowing) paired with a
				// constant-array guard never helps: such a guard represents a unique
				// literal value that is not re-asserted as a condition later, yet the
				// downstream isSuperTypeOf() guard machinery pays to compare these
				// (potentially huge) constant arrays on every branch merge. Skip them.
				if ($exprIsGuardExcluded && $guardHolder->getType()->isConstantArray()->yes()) {
					continue;
				}

				if (
					array_key_exists($guardExprString, $theirExpressionTypes)
					&& $theirExpressionTypes[$guardExprString]->getCertainty()->yes()
				) {
					$guardIsSuperTypeOfTheirExpr = $guardIsSuperTypeOfTheirExprCache[$guardExprString] ??= $guardHolder->getType()->isSuperTypeOf($theirExpressionTypes[$guardExprString]->getType());

					// The reverse isSuperTypeOf() check is expensive on big union /
					// constant-array types, so it is evaluated last and only when the
					// cheaper forward-based conditions did not already decide.
					if (
						$guardIsSuperTypeOfTheirExpr->yes()
						|| (
							array_key_exists($exprString, $theirExpressionTypes)
							&& $theirExpressionTypes[$exprString]->getCertainty()->yes()
							&& !$guardIsSuperTypeOfTheirExpr->no()
						)
						|| (
							!array_key_exists($exprString, $theirExpressionTypes)
							&& $holder->getType()->equals($guardHolder->getType())
							&& !$guardIsSuperTypeOfTheirExpr->no()
						)
						|| ($theirExprIsSuperTypeOfGuardCache[$guardExprString] ??= $theirExpressionTypes[$guardExprString]->getType()->isSuperTypeOf($guardHolder->getType()))->yes()
					) {
						continue;
					}
				}

				$conditionalExpression = new ConditionalExpressionHolder([$guardExprString => $guardHolder], $holder);
				$conditionalExpressions[$exprString][$conditionalExpression->getKey()] = $conditionalExpression;
			}
		}

		foreach ($mergedExpressionTypes as $exprString => $mergedExprTypeHolder) {
			if (array_key_exists($exprString, $ourExpressionTypes)) {
				continue;
			}

			foreach ($typeGuards as $guardExprString => $guardHolder) {
				$conditionalExpression = new ConditionalExpressionHolder([$guardExprString => $guardHolder], new ExpressionTypeHolder($mergedExprTypeHolder->getExpr(), new ErrorType(), TrinaryLogic::createNo()));
				$conditionalExpressions[$exprString][$conditionalExpression->getKey()] = $conditionalExpression;
			}
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

				$containsSuperGlobal = $expr->getAttribute(self::CONTAINS_SUPER_GLOBAL_ATTRIBUTE_NAME);
				if ($containsSuperGlobal === null) {
					$containsSuperGlobal = $nodeFinder->findFirst($expr, $globalVariableCallback) !== null;
					$expr->setAttribute(self::CONTAINS_SUPER_GLOBAL_ATTRIBUTE_NAME, $containsSuperGlobal);
				}
				if ($containsSuperGlobal === true) {
					continue;
				}

				$intersectedVariableTypeHolders[$exprString] = ExpressionTypeHolder::createMaybe($expr, $variableTypeHolder->getType());
			}
		}

		foreach ($theirVariableTypeHolders as $exprString => $variableTypeHolder) {
			if (isset($intersectedVariableTypeHolders[$exprString])) {
				continue;
			}

			$expr = $variableTypeHolder->getExpr();

			$containsSuperGlobal = $expr->getAttribute(self::CONTAINS_SUPER_GLOBAL_ATTRIBUTE_NAME);
			if ($containsSuperGlobal === null) {
				$containsSuperGlobal = $nodeFinder->findFirst($expr, $globalVariableCallback) !== null;
				$expr->setAttribute(self::CONTAINS_SUPER_GLOBAL_ATTRIBUTE_NAME, $containsSuperGlobal);
			}
			if ($containsSuperGlobal === true) {
				continue;
			}

			$intersectedVariableTypeHolders[$exprString] = ExpressionTypeHolder::createMaybe($expr, $variableTypeHolder->getType());
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
			$this->intersectConditionalExpressions($finallyScope->conditionalExpressions),
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
				&& !$originalVariableTypeHolders[$exprString]->equalTypes($variableTypeHolder)
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

			$holder = ExpressionTypeHolder::createYes($use->var, $variableType);
			$expressionTypes[$variableExprString] = $holder;
			$nativeExpressionTypes[$variableExprString] = $holder;
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
			$this->intersectConditionalExpressions($finalScope->conditionalExpressions),
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
				if (!$this->shouldInvalidateExpression($generalizedExprString, $generalizedExpr, $variableTypeHolder->getExpr(), $variableExprString)) {
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

		// Track whether either input carries a BenevolentUnion so the result
		// can be re-wrapped at the end. `flattenTypes` below drops the
		// BenevolentUnion wrapper, which would silently downgrade e.g.
		// `(float|int)` (numeric-accepting) to a strict `float|int`. Inside a
		// loop's fixed-point this propagates into the iterable value type of
		// an array and turns `return [..., $int]` checks into false positives
		// when the iteration body's `+ 1` arithmetic was originally produced
		// by an `ErrorType`-derived `int|float` benevolent union (the typical
		// case for reads of literally-missing keys inside the body).
		$wrapBenevolent = $a instanceof BenevolentUnionType || $b instanceof BenevolentUnionType;

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
					// Both inputs are sealed constant array shapes — their key
					// sets are finite by construction. On the fall-through
					// ArrayType path, recursing into `generalizeType` would
					// widen e.g. `0|1` to `int<0, max>` — for both the keys and
					// the values — losing the loop's per-iteration precision.
					// Keep the literal union instead so the loop's bounds stay
					// visible. (Scoped to sealed shapes so the general
					// `generalize()` widening contract for legacy arrays — see
					// ScopeTest::testGeneralize — is unaffected.)
					$bothSealed = true;
					foreach ([...$constantArrays['a'], ...$constantArrays['b']] as $constantArrayCheck) {
						foreach ($constantArrayCheck->getConstantArrays() as $constantArrayInstance) {
							if (!$constantArrayInstance->isSealed()->yes()) {
								$bothSealed = false;
								break 2;
							}
						}
					}
					if ($bothSealed) {
						$resultKeyType = TypeCombinator::union($constantArraysA->getIterableKeyType(), $constantArraysB->getIterableKeyType());
						$resultValueType = TypeCombinator::union($constantArraysA->getIterableValueType(), $constantArraysB->getIterableValueType());
						if ($resultValueType->isOversizedArray()->yes()) {
							// The literal value union outgrew the shape limit (a
							// deeply/widely nested value): fall back to generalizing
							// it into a bounded range-keyed array rather than
							// keeping an oversized literal shape.
							$resultValueType = TypeCombinator::union($this->generalizeType($constantArraysA->getIterableValueType(), $constantArraysB->getIterableValueType(), $depth + 1));
						}
					} else {
						$resultKeyType = TypeCombinator::union($this->generalizeType($constantArraysA->getIterableKeyType(), $constantArraysB->getIterableKeyType(), $depth + 1));
						$resultValueType = TypeCombinator::union($this->generalizeType($constantArraysA->getIterableValueType(), $constantArraysB->getIterableValueType(), $depth + 1));
					}
					$resultType = new ArrayType(
						$resultKeyType,
						$resultValueType,
					);
					$accessories = [];
					if (
						$constantArraysA->isIterableAtLeastOnce()->yes()
						&& $constantArraysB->isIterableAtLeastOnce()->yes()
						&& $constantArraysA->getArraySize()->getGreaterOrEqualType($this->phpVersion)->isSuperTypeOf($constantArraysB->getArraySize())->yes()
					) {
						$accessories[] = new NonEmptyArrayType();
					}
					if ($constantArraysA->isList()->yes() && $constantArraysB->isList()->yes()) {
						$accessories[] = new AccessoryArrayListType();
					}

					if (count($accessories) === 0) {
						$resultTypes[] = $resultType;
					} else {
						$resultTypes[] = TypeCombinator::intersect($resultType, ...$accessories);
					}
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

				$accessories = [];
				if ($generalArraysA->isIterableAtLeastOnce()->yes() && $generalArraysB->isIterableAtLeastOnce()->yes()) {
					$accessories[] = new NonEmptyArrayType();
				}
				if ($generalArraysA->isList()->yes() && $generalArraysB->isList()->yes()) {
					$accessories[] = new AccessoryArrayListType();
				}
				if ($generalArraysA->isOversizedArray()->yes() && $generalArraysB->isOversizedArray()->yes()) {
					$accessories[] = new OversizedArrayType();
				}

				if (count($accessories) === 0) {
					$resultTypes[] = $resultType;
				} else {
					$resultTypes[] = TypeCombinator::intersect($resultType, ...$accessories);
				}
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

					$newMin = $min;
					$newMax = $max;
					foreach ($constantIntegers['b'] as $int) {
						if ($int->getValue() > $newMax) {
							$newMax = $int->getValue();
						}
						if ($int->getValue() >= $newMin) {
							continue;
						}

						$newMin = $int->getValue();
					}

					if ($newMax > $max && $newMin < $min) {
						$resultTypes[] = IntegerRangeType::fromInterval($newMin, $newMax);
					} elseif ($newMax > $max) {
						$resultTypes[] = IntegerRangeType::fromInterval($min, null);
					} elseif ($newMin < $min) {
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

					$newMin = $min;
					$newMax = $max;
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

						if ($rangeMax > $newMax) {
							$newMax = $rangeMax;
						}
						if ($rangeMin >= $newMin) {
							continue;
						}

						$newMin = $rangeMin;
					}

					$gotGreater = $newMax > $max;
					$gotSmaller = $newMin < $min;

					if ($min === PHP_INT_MIN) {
						$min = null;
					}
					if ($max === PHP_INT_MAX) {
						$max = null;
					}
					if ($newMin === PHP_INT_MIN) {
						$newMin = null;
					}
					if ($newMax === PHP_INT_MAX) {
						$newMax = null;
					}

					if ($gotGreater && $gotSmaller) {
						$resultTypes[] = IntegerRangeType::fromInterval($newMin, $newMax);
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

		$combined = TypeCombinator::union(...$resultTypes, ...$otherTypes);
		if ($wrapBenevolent) {
			$combined = TypeUtils::toBenevolentUnion($combined);
		}

		return TypeCombinator::union(TypeCombinator::intersect(
			$combined,
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

			if (!$variableTypeHolder->equalTypes($otherVariableTypeHolders[$variableExprString])) {
				return false;
			}
		}

		return true;
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

	public function filterTypeWithMethod(Type $typeWithMethod, string $methodName): ?Type
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

	/**
	 * @template TNodeType of Node
	 * @template TValue
	 * @param class-string<Collector<TNodeType, TValue>> $collectorType
	 * @param TValue $data
	 */
	public function emitCollectedData(string $collectorType, mixed $data): void
	{
		$nodeCallback = $this->nodeCallback;
		if ($nodeCallback === null) {
			throw new ShouldNotHappenException('Node callback is not present in this scope');
		}

		$nodeCallback(new EmitCollectedDataNode($collectorType, $data), $this);
	}

}
