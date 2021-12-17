<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\Cache\Cache;
use PHPStan\Parser\FunctionCallStatementFinder;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\VoidType;
use ReflectionFunction;
use ReflectionParameter;
use function array_map;
use function filemtime;
use function is_file;
use function sprintf;
use function time;

class PhpFunctionReflection implements FunctionReflection
{

	private ReflectionFunction $reflection;

	private Parser $parser;

	private FunctionCallStatementFinder $functionCallStatementFinder;

	private Cache $cache;

	private TemplateTypeMap $templateTypeMap;

	/** @var Type[] */
	private array $phpDocParameterTypes;

	private ?Type $phpDocReturnType;

	private ?Type $phpDocThrowType;

	private ?string $deprecatedDescription;

	private bool $isDeprecated;

	private bool $isInternal;

	private bool $isFinal;

	private ?string $filename;

	private ?bool $isPure;

	/** @var FunctionVariantWithPhpDocs[]|null */
	private ?array $variants = null;

	/**
	 * @param Type[] $phpDocParameterTypes
	 */
	public function __construct(
		ReflectionFunction $reflection,
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
		?string $filename,
		?bool $isPure
	)
	{
		$this->reflection = $reflection;
		$this->parser = $parser;
		$this->functionCallStatementFinder = $functionCallStatementFinder;
		$this->cache = $cache;
		$this->templateTypeMap = $templateTypeMap;
		$this->phpDocParameterTypes = $phpDocParameterTypes;
		$this->phpDocReturnType = $phpDocReturnType;
		$this->phpDocThrowType = $phpDocThrowType;
		$this->isDeprecated = $isDeprecated;
		$this->deprecatedDescription = $deprecatedDescription;
		$this->isInternal = $isInternal;
		$this->isFinal = $isFinal;
		$this->filename = $filename;
		$this->isPure = $isPure;
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
			$reflection,
			$this->phpDocParameterTypes[$reflection->getName()] ?? null,
			null,
		), $this->reflection->getParameters());
	}

	private function isVariadic(): bool
	{
		$isNativelyVariadic = $this->reflection->isVariadic();
		if (!$isNativelyVariadic && $this->reflection->getFileName() !== false) {
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
		if ($this->getReturnType() instanceof VoidType) {
			return TrinaryLogic::createYes();
		}
		if ($this->isPure !== null) {
			return TrinaryLogic::createFromBoolean(!$this->isPure);
		}

		return TrinaryLogic::createMaybe();
	}

	public function isBuiltin(): bool
	{
		return $this->reflection->isInternal();
	}

}
