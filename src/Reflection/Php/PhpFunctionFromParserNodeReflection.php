<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\VoidType;

class PhpFunctionFromParserNodeReflection implements \PHPStan\Reflection\FunctionReflection
{

	/** @var Function_|ClassMethod */
	private \PhpParser\Node\FunctionLike $functionLike;

	private string $fileName;

	private \PHPStan\Type\Generic\TemplateTypeMap $templateTypeMap;

	/** @var \PHPStan\Type\Type[] */
	private array $realParameterTypes;

	/** @var \PHPStan\Type\Type[] */
	private array $phpDocParameterTypes;

	/** @var \PHPStan\Type\Type[] */
	private array $realParameterDefaultValues;

	private \PHPStan\Type\Type $realReturnType;

	private ?\PHPStan\Type\Type $phpDocReturnType;

	private ?\PHPStan\Type\Type $throwType;

	private ?string $deprecatedDescription;

	private bool $isDeprecated;

	private bool $isInternal;

	private bool $isFinal;

	private ?bool $isPure;

	/** @var FunctionVariantWithPhpDocs[]|null */
	private ?array $variants = null;

	/**
	 * @param Function_|ClassMethod $functionLike
	 * @param \PHPStan\Type\Type[] $realParameterTypes
	 * @param \PHPStan\Type\Type[] $phpDocParameterTypes
	 * @param \PHPStan\Type\Type[] $realParameterDefaultValues
	 */
	public function __construct(
		FunctionLike $functionLike,
		string $fileName,
		TemplateTypeMap $templateTypeMap,
		array $realParameterTypes,
		array $phpDocParameterTypes,
		array $realParameterDefaultValues,
		Type $realReturnType,
		?Type $phpDocReturnType,
		?Type $throwType,
		?string $deprecatedDescription,
		bool $isDeprecated,
		bool $isInternal,
		bool $isFinal,
		?bool $isPure
	)
	{
		$this->functionLike = $functionLike;
		$this->fileName = $fileName;
		$this->templateTypeMap = $templateTypeMap;
		$this->realParameterTypes = $realParameterTypes;
		$this->phpDocParameterTypes = $phpDocParameterTypes;
		$this->realParameterDefaultValues = $realParameterDefaultValues;
		$this->realReturnType = $realReturnType;
		$this->phpDocReturnType = $phpDocReturnType;
		$this->throwType = $throwType;
		$this->deprecatedDescription = $deprecatedDescription;
		$this->isDeprecated = $isDeprecated;
		$this->isInternal = $isInternal;
		$this->isFinal = $isFinal;
		$this->isPure = $isPure;
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
			throw new \PHPStan\ShouldNotHappenException();
		}

		return (string) $this->functionLike->namespacedName;
	}

	/**
	 * @return \PHPStan\Reflection\ParametersAcceptorWithPhpDocs[]
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
					$this->realReturnType
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
		$parameters = [];
		$isOptional = true;

		/** @var \PhpParser\Node\Param $parameter */
		foreach (array_reverse($this->functionLike->getParams()) as $parameter) {
			if ($parameter->default === null && !$parameter->variadic) {
				$isOptional = false;
			}

			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new \PHPStan\ShouldNotHappenException();
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
				$parameter->variadic
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
		return false;
	}

}
