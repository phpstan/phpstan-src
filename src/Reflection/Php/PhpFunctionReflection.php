<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\Cache\Cache;
use PHPStan\Parser\FunctionCallStatementFinder;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\ReflectionWithFilename;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\VoidType;

class PhpFunctionReflection implements FunctionReflection, ReflectionWithFilename
{

	private \ReflectionFunction $reflection;

	private \PHPStan\Parser\Parser $parser;

	private \PHPStan\Parser\FunctionCallStatementFinder $functionCallStatementFinder;

	private \PHPStan\Cache\Cache $cache;

	private \PHPStan\Type\Generic\TemplateTypeMap $templateTypeMap;

	/** @var \PHPStan\Type\Type[] */
	private array $phpDocParameterTypes;

	private ?\PHPStan\Type\Type $phpDocReturnType;

	private ?\PHPStan\Type\Type $phpDocThrowType;

	private ?string $deprecatedDescription;

	private bool $isDeprecated;

	private bool $isInternal;

	private bool $isFinal;

	/** @var string|false */
	private $filename;

	private ?bool $isPure;

	/** @var FunctionVariantWithPhpDocs[]|null */
	private ?array $variants = null;

	/**
	 * @param \ReflectionFunction $reflection
	 * @param Parser $parser
	 * @param FunctionCallStatementFinder $functionCallStatementFinder
	 * @param Cache $cache
	 * @param TemplateTypeMap $templateTypeMap
	 * @param \PHPStan\Type\Type[] $phpDocParameterTypes
	 * @param Type|null $phpDocReturnType
	 * @param Type|null $phpDocThrowType
	 * @param string|null $deprecatedDescription
	 * @param bool $isDeprecated
	 * @param bool $isInternal
	 * @param bool $isFinal
	 * @param string|false $filename
	 * @param bool|null $isPure
	 */
	public function __construct(
		\ReflectionFunction $reflection,
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
		$filename,
		?bool $isPure = null
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

	/**
	 * @return string|false
	 */
	public function getFileName()
	{
		if ($this->filename === false) {
			return false;
		}

		if (!file_exists($this->filename)) {
			return false;
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
					$this->getNativeReturnType()
				),
			];
		}

		return $this->variants;
	}

	/**
	 * @return \PHPStan\Reflection\ParameterReflectionWithPhpDocs[]
	 */
	private function getParameters(): array
	{
		return array_map(function (\ReflectionParameter $reflection): PhpParameterReflection {
			return new PhpParameterReflection(
				$reflection,
				$this->phpDocParameterTypes[$reflection->getName()] ?? null,
				null
			);
		}, $this->reflection->getParameters());
	}

	private function isVariadic(): bool
	{
		$isNativelyVariadic = $this->reflection->isVariadic();
		if (!$isNativelyVariadic && $this->reflection->getFileName() !== false) {
			$fileName = $this->reflection->getFileName();
			if (file_exists($fileName)) {
				$functionName = $this->reflection->getName();
				$modifiedTime = filemtime($fileName);
				if ($modifiedTime === false) {
					$modifiedTime = time();
				}
				$variableCacheKey = sprintf('%d-v1', $modifiedTime);
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
	 * @param \PhpParser\Node[] $nodes
	 * @return bool
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
			$this->phpDocReturnType
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
			$this->isDeprecated || $this->reflection->isDeprecated()
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
