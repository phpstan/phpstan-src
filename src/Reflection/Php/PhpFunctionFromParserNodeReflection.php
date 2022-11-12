<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\PassedByReference;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use function array_reverse;
use function is_array;
use function is_string;

class PhpFunctionFromParserNodeReflection implements FunctionReflection
{

	/** @var Function_|ClassMethod */
	private Node\FunctionLike $functionLike;

	/** @var FunctionVariantWithPhpDocs[]|null */
	private ?array $variants = null;

	/**
	 * @param Function_|ClassMethod $functionLike
	 * @param Type[] $realParameterTypes
	 * @param Type[] $phpDocParameterTypes
	 * @param Type[] $realParameterDefaultValues
	 * @param Type[] $parameterOutTypes
	 */
	public function __construct(
		FunctionLike $functionLike,
		private string $fileName,
		private TemplateTypeMap $templateTypeMap,
		private array $realParameterTypes,
		private array $phpDocParameterTypes,
		private array $realParameterDefaultValues,
		private Type $realReturnType,
		private ?Type $phpDocReturnType,
		private ?Type $throwType,
		private ?string $deprecatedDescription,
		private bool $isDeprecated,
		private bool $isInternal,
		private bool $isFinal,
		private ?bool $isPure,
		private bool $acceptsNamedArguments,
		private Assertions $assertions,
		private ?string $phpDocComment,
		private array $parameterOutTypes,
	)
	{
		$this->functionLike = $functionLike;
	}

	protected function getFunctionLike(): FunctionLike
	{
		return $this->functionLike;
	}

	public function getFileName(): string
	{
		return $this->fileName;
	}

	public function getName(): string
	{
		if ($this->functionLike instanceof ClassMethod) {
			return $this->functionLike->name->name;
		}

		if ($this->functionLike->namespacedName === null) {
			throw new ShouldNotHappenException();
		}

		return (string) $this->functionLike->namespacedName;
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
					$this->phpDocReturnType ?? new MixedType(),
					$this->realReturnType,
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
		$parameters = [];
		$isOptional = true;

		/** @var Node\Param $parameter */
		foreach (array_reverse($this->functionLike->getParams()) as $parameter) {
			if ($parameter->default === null && !$parameter->variadic) {
				$isOptional = false;
			}

			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new ShouldNotHappenException();
			}
			$parameters[] = new PhpParameterFromParserNodeReflection(
				$parameter->var->name,
				$isOptional,
				$this->realParameterTypes[$parameter->var->name],
				$this->phpDocParameterTypes[$parameter->var->name] ?? null,
				$parameter->byRef
					? PassedByReference::createCreatesNewVariable()
					: PassedByReference::createNo(),
				$this->realParameterDefaultValues[$parameter->var->name] ?? null,
				$parameter->variadic,
				$this->parameterOutTypes[$parameter->var->name] ?? null,
			);
		}

		return array_reverse($parameters);
	}

	private function isVariadic(): bool
	{
		foreach ($this->functionLike->getParams() as $parameter) {
			if ($parameter->variadic) {
				return true;
			}
		}

		return false;
	}

	private function getReturnType(): Type
	{
		return TypehintHelper::decideType($this->realReturnType, $this->phpDocReturnType);
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
		return TrinaryLogic::createFromBoolean($this->isDeprecated);
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->isInternal);
	}

	public function isFinal(): TrinaryLogic
	{
		$finalMethod = false;
		if ($this->functionLike instanceof ClassMethod) {
			$finalMethod = $this->functionLike->isFinal();
		}
		return TrinaryLogic::createFromBoolean($finalMethod || $this->isFinal);
	}

	public function getThrowType(): ?Type
	{
		return $this->throwType;
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
		return false;
	}

	public function isGenerator(): bool
	{
		return $this->nodeIsOrContainsYield($this->functionLike);
	}

	public function acceptsNamedArguments(): bool
	{
		return $this->acceptsNamedArguments;
	}

	private function nodeIsOrContainsYield(Node $node): bool
	{
		if ($node instanceof Node\Expr\Yield_) {
			return true;
		}

		if ($node instanceof Node\Expr\YieldFrom) {
			return true;
		}

		foreach ($node->getSubNodeNames() as $nodeName) {
			$nodeProperty = $node->$nodeName;

			if ($nodeProperty instanceof Node && $this->nodeIsOrContainsYield($nodeProperty)) {
				return true;
			}

			if (!is_array($nodeProperty)) {
				continue;
			}

			foreach ($nodeProperty as $nodePropertyArrayItem) {
				if ($nodePropertyArrayItem instanceof Node && $this->nodeIsOrContainsYield($nodePropertyArrayItem)) {
					return true;
				}
			}
		}

		return false;
	}

	public function getAsserts(): Assertions
	{
		return $this->assertions;
	}

	public function getDocComment(): ?string
	{
		return $this->phpDocComment;
	}

	public function returnsByReference(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->functionLike->returnsByRef());
	}

}
