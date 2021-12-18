<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\Cache\Cache;
use PHPStan\Parser\FunctionCallStatementFinder;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\VoidType;
use ReflectionException;
use ReflectionParameter;
use function array_map;
use function explode;
use function filemtime;
use function is_bool;
use function is_file;
use function sprintf;
use function strtolower;
use function time;
use const PHP_VERSION_ID;

/** @api */
class PhpMethodReflection implements MethodReflection
{

	private ClassReflection $declaringClass;

	private ?ClassReflection $declaringTrait;

	private BuiltinMethodReflection $reflection;

	private ReflectionProvider $reflectionProvider;

	private Parser $parser;

	private FunctionCallStatementFinder $functionCallStatementFinder;

	private Cache $cache;

	private TemplateTypeMap $templateTypeMap;

	/** @var Type[] */
	private array $phpDocParameterTypes;

	private ?Type $phpDocReturnType;

	private ?Type $phpDocThrowType;

	/** @var PhpParameterReflection[]|null */
	private ?array $parameters = null;

	private ?Type $returnType = null;

	private ?Type $nativeReturnType = null;

	private ?string $deprecatedDescription;

	private bool $isDeprecated;

	private bool $isInternal;

	private bool $isFinal;

	private ?bool $isPure;

	private ?string $stubPhpDocString;

	/** @var FunctionVariantWithPhpDocs[]|null */
	private ?array $variants = null;

	/**
	 * @param Type[] $phpDocParameterTypes
	 */
	public function __construct(
		ClassReflection $declaringClass,
		?ClassReflection $declaringTrait,
		BuiltinMethodReflection $reflection,
		ReflectionProvider $reflectionProvider,
		Parser $parser,
		FunctionCallStatementFinder $functionCallStatementFinder,
		Cache $cache,
		TemplateTypeMap $templateTypeMap,
		array $phpDocParameterTypes,
		?Type $phpDocReturnType,
		?Type $phpDocThrowType,
		?string $deprecatedDescription,
		bool $isDeprecated,
		bool $isInternal,
		bool $isFinal,
		?string $stubPhpDocString,
		?bool $isPure,
	)
	{
		$this->declaringClass = $declaringClass;
		$this->declaringTrait = $declaringTrait;
		$this->reflection = $reflection;
		$this->reflectionProvider = $reflectionProvider;
		$this->parser = $parser;
		$this->functionCallStatementFinder = $functionCallStatementFinder;
		$this->cache = $cache;
		$this->templateTypeMap = $templateTypeMap;
		$this->phpDocParameterTypes = $phpDocParameterTypes;
		$this->phpDocReturnType = $phpDocReturnType;
		$this->phpDocThrowType = $phpDocThrowType;
		$this->deprecatedDescription = $deprecatedDescription;
		$this->isDeprecated = $isDeprecated;
		$this->isInternal = $isInternal;
		$this->isFinal = $isFinal;
		$this->stubPhpDocString = $stubPhpDocString;
		$this->isPure = $isPure;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function getDeclaringTrait(): ?ClassReflection
	{
		return $this->declaringTrait;
	}

	public function getDocComment(): ?string
	{
		if ($this->stubPhpDocString !== null) {
			return $this->stubPhpDocString;
		}

		return $this->reflection->getDocComment();
	}

	/**
	 * @return self|MethodPrototypeReflection
	 */
	public function getPrototype(): ClassMemberReflection
	{
		try {
			$prototypeMethod = $this->reflection->getPrototype();
			$prototypeDeclaringClass = $this->reflectionProvider->getClass($prototypeMethod->getDeclaringClass()->getName());

			$tentativeReturnType = null;
			if ($prototypeMethod->getTentativeReturnType() !== null) {
				$tentativeReturnType = TypehintHelper::decideTypeFromReflection($prototypeMethod->getTentativeReturnType());
			}

			return new MethodPrototypeReflection(
				$prototypeMethod->getName(),
				$prototypeDeclaringClass,
				$prototypeMethod->isStatic(),
				$prototypeMethod->isPrivate(),
				$prototypeMethod->isPublic(),
				$prototypeMethod->isAbstract(),
				$prototypeMethod->isFinal(),
				$prototypeDeclaringClass->getNativeMethod($prototypeMethod->getName())->getVariants(),
				$tentativeReturnType,
			);
		} catch (ReflectionException $e) {
			return $this;
		}
	}

	public function isStatic(): bool
	{
		return $this->reflection->isStatic();
	}

	public function getName(): string
	{
		$name = $this->reflection->getName();
		$lowercaseName = strtolower($name);
		if ($lowercaseName === $name) {
			if (PHP_VERSION_ID >= 80000) {
				return $name;
			}

			// fix for https://bugs.php.net/bug.php?id=74939
			foreach ($this->getDeclaringClass()->getNativeReflection()->getTraitAliases() as $traitTarget) {
				$correctName = $this->getMethodNameWithCorrectCase($name, $traitTarget);
				if ($correctName !== null) {
					$name = $correctName;
					break;
				}
			}
		}

		return $name;
	}

	private function getMethodNameWithCorrectCase(string $lowercaseMethodName, string $traitTarget): ?string
	{
		$trait = explode('::', $traitTarget)[0];
		$traitReflection = $this->reflectionProvider->getClass($trait)->getNativeReflection();
		foreach ($traitReflection->getTraitAliases() as $methodAlias => $aliasTraitTarget) {
			if ($lowercaseMethodName === strtolower($methodAlias)) {
				return $methodAlias;
			}

			$correctName = $this->getMethodNameWithCorrectCase($lowercaseMethodName, $aliasTraitTarget);
			if ($correctName !== null) {
				return $correctName;
			}
		}

		return null;
	}

	/**
	 * @return ParametersAcceptorWithPhpDocs[]
	 */
	public function getVariants(): array
	{
		if ($this->variants === null) {
			$this->variants = [
				new FunctionVariantWithPhpDocs(
					$this->templateTypeMap,
					null,
					$this->getParameters(),
					$this->isVariadic(),
					$this->getReturnType(),
					$this->getPhpDocReturnType(),
					$this->getNativeReturnType(),
				),
			];
		}

		return $this->variants;
	}

	/**
	 * @return ParameterReflectionWithPhpDocs[]
	 */
	private function getParameters(): array
	{
		if ($this->parameters === null) {
			$this->parameters = array_map(fn (ReflectionParameter $reflection): PhpParameterReflection => new PhpParameterReflection(
				$reflection,
				$this->phpDocParameterTypes[$reflection->getName()] ?? null,
				$this->getDeclaringClass()->getName(),
			), $this->reflection->getParameters());
		}

		return $this->parameters;
	}

	private function isVariadic(): bool
	{
		$isNativelyVariadic = $this->reflection->isVariadic();
		$declaringClass = $this->declaringClass;
		$filename = $this->declaringClass->getFileName();
		if ($this->declaringTrait !== null) {
			$declaringClass = $this->declaringTrait;
			$filename = $this->declaringTrait->getFileName();
		}

		if (!$isNativelyVariadic && $filename !== null && is_file($filename)) {
			$modifiedTime = filemtime($filename);
			if ($modifiedTime === false) {
				$modifiedTime = time();
			}
			$key = sprintf('variadic-method-%s-%s-%s', $declaringClass->getName(), $this->reflection->getName(), $filename);
			$variableCacheKey = sprintf('%d-v4', $modifiedTime);
			$cachedResult = $this->cache->load($key, $variableCacheKey);
			if ($cachedResult === null || !is_bool($cachedResult)) {
				$nodes = $this->parser->parseFile($filename);
				$result = $this->callsFuncGetArgs($declaringClass, $nodes);
				$this->cache->save($key, $variableCacheKey, $result);
				return $result;
			}

			return $cachedResult;
		}

		return $isNativelyVariadic;
	}

	/**
	 * @param Node[] $nodes
	 */
	private function callsFuncGetArgs(ClassReflection $declaringClass, array $nodes): bool
	{
		foreach ($nodes as $node) {
			if (
				$node instanceof Node\Stmt\ClassLike
			) {
				if (!isset($node->namespacedName)) {
					continue;
				}
				if ($declaringClass->getName() !== (string) $node->namespacedName) {
					continue;
				}
				if ($this->callsFuncGetArgs($declaringClass, $node->stmts)) {
					return true;
				}
				continue;
			}

			if ($node instanceof ClassMethod) {
				if ($node->getStmts() === null) {
					continue; // interface
				}

				$methodName = $node->name->name;
				if ($methodName === $this->reflection->getName()) {
					return $this->functionCallStatementFinder->findFunctionCallInStatements(ParametersAcceptor::VARIADIC_FUNCTIONS, $node->getStmts()) !== null;
				}

				continue;
			}

			if ($node instanceof Function_) {
				continue;
			}

			if ($node instanceof Namespace_) {
				if ($this->callsFuncGetArgs($declaringClass, $node->stmts)) {
					return true;
				}
				continue;
			}

			if (!$node instanceof Declare_ || $node->stmts === null) {
				continue;
			}

			if ($this->callsFuncGetArgs($declaringClass, $node->stmts)) {
				return true;
			}
		}

		return false;
	}

	public function isPrivate(): bool
	{
		return $this->reflection->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->reflection->isPublic();
	}

	private function getReturnType(): Type
	{
		if ($this->returnType === null) {
			$name = strtolower($this->getName());
			if (
				$name === '__construct'
				|| $name === '__destruct'
				|| $name === '__unset'
				|| $name === '__wakeup'
				|| $name === '__clone'
			) {
				return $this->returnType = TypehintHelper::decideType(new VoidType(), $this->phpDocReturnType);
			}
			if ($name === '__tostring') {
				return $this->returnType = TypehintHelper::decideType(new StringType(), $this->phpDocReturnType);
			}
			if ($name === '__isset') {
				return $this->returnType = TypehintHelper::decideType(new BooleanType(), $this->phpDocReturnType);
			}
			if ($name === '__sleep') {
				return $this->returnType = TypehintHelper::decideType(new ArrayType(new IntegerType(), new StringType()), $this->phpDocReturnType);
			}
			if ($name === '__set_state') {
				return $this->returnType = TypehintHelper::decideType(new ObjectWithoutClassType(), $this->phpDocReturnType);
			}

			$this->returnType = TypehintHelper::decideTypeFromReflection(
				$this->reflection->getReturnType(),
				$this->phpDocReturnType,
				$this->declaringClass->getName(),
			);
		}

		return $this->returnType;
	}

	private function getPhpDocReturnType(): Type
	{
		if ($this->phpDocReturnType !== null) {
			return $this->phpDocReturnType;
		}

		return new MixedType();
	}

	private function getNativeReturnType(): Type
	{
		if ($this->nativeReturnType === null) {
			$this->nativeReturnType = TypehintHelper::decideTypeFromReflection(
				$this->reflection->getReturnType(),
				null,
				$this->declaringClass->getName(),
			);
		}

		return $this->nativeReturnType;
	}

	public function getDeprecatedDescription(): ?string
	{
		if ($this->isDeprecated) {
			return $this->deprecatedDescription;
		}

		return null;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return $this->reflection->isDeprecated()->or(TrinaryLogic::createFromBoolean($this->isDeprecated));
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->reflection->isInternal() || $this->isInternal);
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->reflection->isFinal() || $this->isFinal);
	}

	public function isAbstract(): bool
	{
		return $this->reflection->isAbstract();
	}

	public function getThrowType(): ?Type
	{
		return $this->phpDocThrowType;
	}

	public function hasSideEffects(): TrinaryLogic
	{
		$name = strtolower($this->getName());
		$isVoid = $this->getReturnType() instanceof VoidType;

		if (
			$name !== '__construct'
			&& $isVoid
		) {
			return TrinaryLogic::createYes();
		}
		if ($this->isPure !== null) {
			return TrinaryLogic::createFromBoolean(!$this->isPure);
		}

		if ($isVoid) {
			return TrinaryLogic::createYes();
		}

		return TrinaryLogic::createMaybe();
	}

}
