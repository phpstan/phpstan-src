<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\Cache\Cache;
use PHPStan\Parser\FunctionCallStatementFinder;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use function array_map;
use function filemtime;
use function is_file;
use function sprintf;
use function time;

class PhpFunctionReflection implements FunctionReflection
{

	/** @var FunctionVariantWithPhpDocs[]|null */
	private ?array $variants = null;

	/**
	 * @param Type[] $phpDocParameterTypes
	 * @param Type[] $phpDocParameterOutTypes
	 */
	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ReflectionFunction $reflection,
		private Parser $parser,
		private FunctionCallStatementFinder $functionCallStatementFinder,
		private Cache $cache,
		private TemplateTypeMap $templateTypeMap,
		private array $phpDocParameterTypes,
		private ?Type $phpDocReturnType,
		private ?Type $phpDocThrowType,
		private ?string $deprecatedDescription,
		private bool $isDeprecated,
		private bool $isInternal,
		private bool $isFinal,
		private ?string $filename,
		private ?bool $isPure,
		private Assertions $asserts,
		private ?string $phpDocComment,
		private array $phpDocParameterOutTypes,
	)
	{
	}

	public function getName(): string
	{
		return $this->reflection->getName();
	}

	public function getFileName(): ?string
	{
		if ($this->filename === null) {
			return null;
		}

		if (!is_file($this->filename)) {
			return null;
		}

		return $this->filename;
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
		return array_map(fn (ReflectionParameter $reflection): PhpParameterReflection => new PhpParameterReflection(
			$this->initializerExprTypeResolver,
			$reflection,
			$this->phpDocParameterTypes[$reflection->getName()] ?? null,
			null,
			$this->phpDocParameterOutTypes[$reflection->getName()] ?? null,
		), $this->reflection->getParameters());
	}

	private function isVariadic(): bool
	{
		$isNativelyVariadic = $this->reflection->isVariadic();
		if (!$isNativelyVariadic && $this->reflection
			->getFileName() !== false) {
			$fileName = $this->reflection->getFileName();
			if (is_file($fileName)) {
				$functionName = $this->reflection->getName();
				$modifiedTime = filemtime($fileName);
				if ($modifiedTime === false) {
					$modifiedTime = time();
				}
				$variableCacheKey = sprintf('%d-v3', $modifiedTime);
				$key = sprintf('variadic-function-%s-%s', $functionName, $fileName);
				$cachedResult = $this->cache->load($key, $variableCacheKey);
				if ($cachedResult === null) {
					$nodes = $this->parser->parseFile($fileName);
					$result = $this->callsFuncGetArgs($nodes);
					$this->cache->save($key, $variableCacheKey, $result);
					return $result;
				}

				return $cachedResult;
			}
		}

		return $isNativelyVariadic;
	}

	/**
	 * @param Node[] $nodes
	 */
	private function callsFuncGetArgs(array $nodes): bool
	{
		foreach ($nodes as $node) {
			if ($node instanceof Function_) {
				$functionName = (string) $node->namespacedName;

				if ($functionName === $this->reflection->getName()) {
					return $this->functionCallStatementFinder->findFunctionCallInStatements(ParametersAcceptor::VARIADIC_FUNCTIONS, $node->getStmts()) !== null;
				}

				continue;
			}

			if ($node instanceof ClassLike) {
				continue;
			}

			if ($node instanceof Namespace_) {
				if ($this->callsFuncGetArgs($node->stmts)) {
					return true;
				}
			}

			if (!$node instanceof Declare_ || $node->stmts === null) {
				continue;
			}

			if ($this->callsFuncGetArgs($node->stmts)) {
				return true;
			}
		}

		return false;
	}

	private function getReturnType(): Type
	{
		return TypehintHelper::decideTypeFromReflection(
			$this->reflection->getReturnType(),
			$this->phpDocReturnType,
		);
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
		return TypehintHelper::decideTypeFromReflection($this->reflection->getReturnType());
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
		return TrinaryLogic::createFromBoolean(
			$this->isDeprecated || $this->reflection->isDeprecated(),
		);
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->isInternal);
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->isFinal);
	}

	public function getThrowType(): ?Type
	{
		return $this->phpDocThrowType;
	}

	public function hasSideEffects(): TrinaryLogic
	{
		if ($this->getReturnType()->isVoid()->yes()) {
			return TrinaryLogic::createYes();
		}
		if ($this->isPure !== null) {
			return TrinaryLogic::createFromBoolean(!$this->isPure);
		}
		if ($this->variants !== null) {
			foreach ($this->variants as $variant) {
				foreach ($variant->getParameters() as $parameter) {
					if ($parameter->passedByReference()->yes()) {
						return TrinaryLogic::createYes();
					}
				}
			}
		}

		return TrinaryLogic::createMaybe();
	}

	public function isBuiltin(): bool
	{
		return $this->reflection->isInternal();
	}

	public function getAsserts(): Assertions
	{
		return $this->asserts;
	}

	public function getDocComment(): ?string
	{
		return $this->phpDocComment;
	}

	public function returnsByReference(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->reflection->returnsReference());
	}

}
