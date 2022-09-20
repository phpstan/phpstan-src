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
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\Expr\GetIterableKeyTypeExpr;
use PHPStan\Node\Expr\GetIterableValueTypeExpr;
use PHPStan\Node\Expr\GetOffsetValueTypeExpr;
use PHPStan\Node\Expr\OriginalPropertyTypeExpr;
use PHPStan\Node\Expr\SetOffsetValueTypeExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\ArrayMapArgVisitor;
use PHPStan\Parser\NewAssignedToPropertyVisitor;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\Dummy\DummyConstructorReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
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
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\DynamicReturnTypeExtensionRegistry;
use PHPStan\Type\ErrorType;
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
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
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
use function array_slice;
use function count;
use function explode;
use function get_class;
use function implode;
use function in_array;
use function is_string;
use function ltrim;
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

	/** @var Type[] */
	private array $resolvedTypes = [];

	/** @var array<string, self> */
	private array $truthyScopes = [];

	/** @var array<string, self> */
	private array $falseyScopes = [];

	private ?string $namespace;

	private ?self $scopeOutOfFirstLevelStatement = null;

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
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private DynamicReturnTypeExtensionRegistry $dynamicReturnTypeExtensionRegistry,
		private ExprPrinter $exprPrinter,
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
		private bool $explicitMixedForGlobalVariables = false,
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

	public function afterOpenSslCall(string $openSslFunctionName): self
	{
		$moreSpecificTypes = $this->moreSpecificTypes;

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
			unset($moreSpecificTypes['\openssl_error_string()']);
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
		if ($this->hasVariableType($variableName)->maybe()) {
			if ($variableName === 'argc') {
				return IntegerRangeType::fromInterval(1, null);
			}
			if ($variableName === 'argv') {
				return TypeCombinator::intersect(
					new ArrayType(new IntegerType(), new StringType()),
					new NonEmptyArrayType(),
				);
			}
		}

		if ($this->isGlobalVariable($variableName)) {
			return new ArrayType(new StringType(), new MixedType($this->explicitMixedForGlobalVariables));
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
		if ($node instanceof TypeExpr) {
			return $node->getExprType();
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
			$this->resolvedTypes[$key] = TypeUtils::resolveLateResolvableTypes($this->resolveType($node));
		}
		return $this->resolvedTypes[$key];
	}

	private function getNodeKey(Expr $node): string
	{
		return $this->exprPrinter->printExpr($node);
	}

	private function resolveType(Expr $node): Type
	{
		if ($node instanceof Expr\Exit_ || $node instanceof Expr\Throw_) {
			return new NeverType(true);
		}

		$exprString = $this->getNodeKey($node);
		if (isset($this->moreSpecificTypes[$exprString]) && $this->moreSpecificTypes[$exprString]->getCertainty()->yes()) {
			return $this->moreSpecificTypes[$exprString]->getType();
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

			return $this->initializerExprTypeResolver->resolveEqualType($leftType, $rightType);
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
			return $this->initializerExprTypeResolver->getBitwiseNotType($node->expr, fn (Expr $expr): Type => $this->getType($expr));
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

			return $this->initializerExprTypeResolver->resolveIdenticalType($leftType, $rightType);
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
			return $this->getType($node->expr);
		}

		if ($node instanceof LNumber) {
			return $this->initializerExprTypeResolver->getType($node, InitializerExprContext::fromScope($this));
		} elseif ($node instanceof String_) {
			return $this->initializerExprTypeResolver->getType($node, InitializerExprContext::fromScope($this));
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
				$isNonFalsy = false;
				$isLiteralString = true;
				foreach ($parts as $partType) {
					if ($partType->isNonFalsyString()->yes()) {
						$isNonFalsy = true;
					}
					if ($partType->isNonEmptyString()->yes()) {
						$isNonEmpty = true;
					}
					if ($partType->isLiteralString()->yes()) {
						continue;
					}
					$isLiteralString = false;
				}

				$accessoryTypes = [];
				if ($isNonFalsy === true) {
					$accessoryTypes[] = new AccessoryNonFalsyStringType();
				} elseif ($isNonEmpty === true) {
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
			return $this->initializerExprTypeResolver->getType($node, InitializerExprContext::fromScope($this));
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
			$arrayMapArgs = $node->getAttribute(ArrayMapArgVisitor::ATTRIBUTE_NAME);
			if ($arrayMapArgs !== null) {
				$callableParameters = [];
				foreach ($arrayMapArgs as $funcCallArg) {
					$callableParameters[] = new DummyParameter('item', $this->getType($funcCallArg->value)->getIterableValueType(), false, PassedByReference::createNo(), false, null);
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
						$keyType = $yieldFromType->getIterableKeyType();
						$valueType = $yieldFromType->getIterableValueType();
					}

					$returnType = new GenericObjectType(Generator::class, [
						$keyType,
						$valueType,
						new MixedType(),
						new VoidType(),
					]);
				} else {
					$returnType = $arrowScope->getType($node->expr);
					if ($node->returnType !== null) {
						$returnType = TypehintHelper::decideType($this->getFunctionType($node->returnType, false, false), $returnType);
					}
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
			return $this->initializerExprTypeResolver->getArrayType($node, fn (Expr $expr): Type => $this->getType($expr));
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
		} elseif ($node instanceof Node\Scalar\MagicConst) {
			return $this->initializerExprTypeResolver->getType($node, InitializerExprContext::fromScope($this));
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
			} elseif ($varType->isString()->yes()) {
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

				$filteringExprType = $matchScope->getType($filteringExpr);

				if (!(new ConstantBooleanType(false))->isSuperTypeOf($filteringExprType)->yes()) {
					$truthyScope = $matchScope->filterByTruthyValue($filteringExpr);
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
			return $this->initializerExprTypeResolver->getClassConstFetchTypeByReflection(
				$node->class,
				$node->name->name,
				$this->isInClass() ? $this->getClassReflection() : null,
				fn (Expr $expr): Type => $this->getType($expr),
			);
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
			$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
				$this,
				$node->getArgs(),
				$functionReflection->getVariants(),
			);
			$normalizedNode = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);
			if ($normalizedNode !== null) {
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
			}

			return $parametersAcceptor->getReturnType();
		}

		return new MixedType();
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
				return false;
			}

			// If offset is cannot be null, store this error message and see if one of the earlier offsets is.
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
			$this->initializerExprTypeResolver,
			$this->dynamicReturnTypeExtensionRegistry,
			$this->exprPrinter,
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
			if ($this->inClosureBindScopeClass === $originalClass) {
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

		$scope = $this->scopeFactory->create(
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
		$scope->resolvedTypes = $this->resolvedTypes;
		$scope->truthyScopes = $this->truthyScopes;
		$scope->falseyScopes = $this->falseyScopes;

		return $scope;
	}

	public function popInFunctionCall(): self
	{
		$stack = $this->inFunctionCallsStack;
		array_pop($stack);

		$scope = $this->scopeFactory->create(
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
		$scope->resolvedTypes = $this->resolvedTypes;
		$scope->truthyScopes = $this->truthyScopes;
		$scope->falseyScopes = $this->falseyScopes;

		return $scope;
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
			[],
			[],
			null,
			null,
			true,
			[],
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
			$this->constantTypes,
			$this->getFunction(),
			$namespace,
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
		bool $acceptsNamedArguments = true,
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
				$acceptsNamedArguments,
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
		bool $acceptsNamedArguments = true,
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
				$acceptsNamedArguments,
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
				if ($this->phpVersion->supportsNamedArguments() && $functionReflection->acceptsNamedArguments()) {
					$parameterType = new ArrayType(new UnionType([new IntegerType(), new StringType()]), $parameterType);
				} else {
					$parameterType = new ArrayType(new IntegerType(), $parameterType);
				}
			}
			$variableTypes[$parameter->getName()] = VariableTypeHolder::createYes($parameterType);

			$nativeParameterType = $parameter->getNativeType();
			if ($parameter->isVariadic()) {
				if ($this->phpVersion->supportsNamedArguments() && $functionReflection->acceptsNamedArguments()) {
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

		if ($iterateeType->isArray()->yes()) {
			$scope = $scope->specifyExpressionType(
				new Expr\ArrayDimFetch($iteratee, new Variable($keyName)),
				$iterateeType->getIterableValueType(),
			);
		}

		return $scope;
	}

	/**
	 * @deprecated Use enterCatchType
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

		$scope = $this->scopeFactory->create(
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
		if ($this->phpVersion->deprecatesDynamicProperties() && $expr instanceof Expr\StaticPropertyFetch) {
			return $this;
		}

		$exprString = $this->getNodeKey($expr);
		$currentlyAllowedUndefinedExpressions = $this->currentlyAllowedUndefinedExpressions;
		$currentlyAllowedUndefinedExpressions[$exprString] = true;

		$scope = $this->scopeFactory->create(
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

		$variableString = $this->exprPrinter->printExpr(new Variable($variableName));
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
			$exprVarType = $this->getType($expr->var);
			$dimType = $this->getType($expr->dim);
			$unsetType = $exprVarType->unsetOffset($dimType);
			$scope = $this->specifyExpressionType($expr->var, $unsetType)->invalidateExpression(
				new FuncCall(new FullyQualified('count'), [new Arg($expr->var)]),
			)->invalidateExpression(
				new FuncCall(new FullyQualified('sizeof'), [new Arg($expr->var)]),
			);

			if ($expr->var instanceof Expr\ArrayDimFetch && $expr->var->dim !== null) {
				$scope = $scope->specifyExpressionType(
					$expr->var->var,
					$scope->getType($expr->var->var)->setOffsetValueType(
						$scope->getType($expr->var->dim),
						$scope->getType($expr->var),
					),
				);
			}

			return $scope;
		}

		return $this;
	}

	private function specifyExpressionType(Expr $expr, Type $type, ?Type $nativeType = null): self
	{
		if ($expr instanceof Node\Scalar || $expr instanceof Array_) {
			return $this;
		}

		if ($expr instanceof ConstFetch) {
			$constantTypes = $this->constantTypes;
			$constantName = new FullyQualified($expr->name->toString());

			if ($type instanceof NeverType) {
				unset($constantTypes[$constantName->toCodeString()]);
			} else {
				$constantTypes[$constantName->toCodeString()] = $type;
			}

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

			$nativeTypes = $this->nativeExpressionTypes;
			$exprString = sprintf('$%s', $variableName);
			if ($nativeType !== null) {
				$nativeTypes[$exprString] = $nativeType;
			}

			$conditionalExpressions = [];
			foreach ($this->conditionalExpressions as $conditionalExprString => $holders) {
				if ($conditionalExprString === $exprString) {
					continue;
				}

				foreach ($holders as $holder) {
					foreach (array_keys($holder->getConditionExpressionTypes()) as $conditionExprString2) {
						if ($conditionExprString2 === $exprString) {
							continue 3;
						}
					}
				}

				$conditionalExpressions[$conditionalExprString] = $holders;
			}

			$moreSpecificTypeHolders = $this->moreSpecificTypes;
			foreach ($moreSpecificTypeHolders as $specifiedExprString => $specificTypeHolder) {
				if (!$specificTypeHolder->getCertainty()->yes()) {
					continue;
				}

				$specifiedExprString = (string) $specifiedExprString;
				$specifiedExpr = $this->exprStringToExpr($specifiedExprString);
				if ($specifiedExpr === null) {
					continue;
				}
				if (!$specifiedExpr instanceof Expr\ArrayDimFetch) {
					continue;
				}

				if (!$specifiedExpr->dim instanceof Variable) {
					continue;
				}

				if (!is_string($specifiedExpr->dim->name)) {
					continue;
				}

				if ($specifiedExpr->dim->name !== $variableName) {
					continue;
				}

				$moreSpecificTypeHolders[$specifiedExprString] = VariableTypeHolder::createYes($this->getType($specifiedExpr->var)->getOffsetValueType($type));
				unset($nativeTypes[$specifiedExprString]);
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
		} elseif ($expr instanceof Expr\ArrayDimFetch && $expr->dim !== null) {
			$dimType = ArrayType::castToArrayKeyType($this->getType($expr->dim));
			if ($dimType instanceof ConstantIntegerType || $dimType instanceof ConstantStringType) {
				$exprVarType = $this->getType($expr->var);
				if (!$exprVarType instanceof MixedType && !$exprVarType->isArray()->no()) {
					$types = [
						new ArrayType(new MixedType(), new MixedType()),
						new ObjectType(ArrayAccess::class),
						new NullType(),
					];
					if ($dimType instanceof ConstantIntegerType) {
						$types[] = new StringType();
					}
					$scope = $this->specifyExpressionType(
						$expr->var,
						TypeCombinator::intersect(
							TypeCombinator::intersect($exprVarType, TypeCombinator::union(...$types)),
							new HasOffsetValueType($dimType, $type),
						),
					);
				}
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

	public function assignExpression(Expr $expr, Type $type, ?Type $nativeType = null): self
	{
		$scope = $this;
		if ($expr instanceof PropertyFetch) {
			$scope = $this->invalidateExpression($expr)
				->invalidateMethodsOnExpression($expr->var);
		} elseif ($expr instanceof Expr\StaticPropertyFetch) {
			$scope = $this->invalidateExpression($expr);
		}

		return $scope->specifyExpressionType($expr, $type, $nativeType);
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
			$exprExpr = $this->exprStringToExpr($exprString);
			if ($exprExpr === null) {
				continue;
			}
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

	private function exprStringToExpr(string $exprString): ?Expr
	{
		try {
			$expr = $this->parser->parseString('<?php ' . $exprString . ';')[0];
		} catch (ParserErrorsException) {
			return null;
		}
		if (!$expr instanceof Node\Stmt\Expression) {
			throw new ShouldNotHappenException();
		}

		return $expr->expr;
	}

	private function invalidateMethodsOnExpression(Expr $expressionToInvalidate): self
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

	private function addTypeToExpression(Expr $expr, Type $type): self
	{
		$originalExprType = $this->getType($expr);
		$nativeType = $this->getNativeType($expr);

		if ($originalExprType->equals($nativeType)) {
			$newType = TypeCombinator::intersect($type, $originalExprType);
			return $this->specifyExpressionType($expr, $newType, $newType);
		}

		return $this->specifyExpressionType(
			$expr,
			TypeCombinator::intersect($type, $originalExprType),
			TypeCombinator::intersect($type, $nativeType),
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
			if ($typeSpecification['sure']) {
				if ($specifiedTypes->shouldOverwrite()) {
					$scope = $scope->specifyExpressionType($expr, $type, $type);
				} else {
					$scope = $scope->addTypeToExpression($expr, $type);
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
						continue;
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
		if (!$this->inFirstLevelStatement) {
			return $this;
		}

		if ($this->scopeOutOfFirstLevelStatement !== null) {
			return $this->scopeOutOfFirstLevelStatement;
		}

		$scope = $this->scopeFactory->create(
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
							!$constantArraysA->hasOffsetValueType($keyType)->and($constantArraysB->hasOffsetValueType($keyType))->negate()->no(),
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

		$accessoryTypes = [];
		$aAccessoryTypes = TypeUtils::getAccessoryTypes($a);
		$bAccessoryTypes = TypeUtils::getAccessoryTypes($b);
		if (count($aAccessoryTypes) > 0) {
			if (count($bAccessoryTypes) === 0) {
				$accessoryTypes = $aAccessoryTypes;
			} else {
				$commonTypeMaps = [];
				foreach ([$aAccessoryTypes, $bAccessoryTypes] as $listKey => $accessoryTypeList) {
					foreach ($accessoryTypeList as $accessoryType) {
						if ($accessoryType instanceof HasOffsetValueType) {
							$commonTypeMaps[$listKey][sprintf('hasOffsetValue(%s)', $accessoryType->getOffsetType()->describe(VerbosityLevel::cache()))][] = $accessoryType;
							continue;
						}

						$commonTypeMaps[$listKey][$accessoryType->describe(VerbosityLevel::cache())][] = $accessoryType;
					}

				}

				$accessoryTypes = array_map(
					static fn (Type $type): Type => $type->generalize(GeneralizePrecision::moreSpecific()),
					TypeCombinator::unionCommonTypeMaps($commonTypeMaps),
				);
			}
		} elseif (count($bAccessoryTypes) > 0) {
			$accessoryTypes = $bAccessoryTypes;
		}

		return TypeCombinator::intersect(
			TypeCombinator::union(...$resultTypes, ...$otherTypes),
			...$accessoryTypes,
		);
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

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$this,
			$methodCall->getArgs(),
			$constructorMethod->getVariants(),
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

		$objectType = new ObjectType($resolvedClassName);
		if (!$classReflection->isGeneric()) {
			return $objectType;
		}

		$assignedToProperty = $node->getAttribute(NewAssignedToPropertyVisitor::ATTRIBUTE_NAME);
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
				$propertyType = TypeCombinator::removeNull($this->getType($assignedToProperty));
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
		if ($type instanceof UnionType) {
			$types = array_map(fn (Type $type) => $this->getTypeToInstantiateForNew($type), $type->getTypes());
			return TypeCombinator::union(...$types);
		}

		if ($type instanceof IntersectionType) {
			$types = array_map(fn (Type $type) => $this->getTypeToInstantiateForNew($type), $type->getTypes());
			return TypeCombinator::intersect(...$types);
		}

		if ($type instanceof ConstantStringType) {
			return new ObjectType($type->getValue());
		}

		if ($type instanceof GenericClassStringType) {
			return $type->getGenericType();
		}

		if ((new ObjectWithoutClassType())->isSuperTypeOf($type)->yes()) {
			return $type;
		}

		return new ObjectWithoutClassType();
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

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$this,
			$methodCall->getArgs(),
			$methodReflection->getVariants(),
		);
		if ($methodCall instanceof MethodCall) {
			$normalizedMethodCall = ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $methodCall);
		} else {
			$normalizedMethodCall = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $methodCall);
		}
		if ($normalizedMethodCall === null) {
			return $parametersAcceptor->getReturnType();
		}

		$resolvedTypes = [];
		foreach (TypeUtils::getDirectClassNames($typeWithMethod) as $className) {
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
			return TypeCombinator::union(...$resolvedTypes);
		}

		return $parametersAcceptor->getReturnType();
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

}
