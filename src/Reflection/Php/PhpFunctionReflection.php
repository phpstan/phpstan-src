<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\Internal\DeprecatedAttributeHelper;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\FunctionReflectionFactory;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ParameterAllowedConstantsMapProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use function array_key_exists;
use function array_map;
use function is_file;
use function strtolower;

#[GenerateFactory(interface: FunctionReflectionFactory::class)]
final class PhpFunctionReflection implements FunctionReflection
{

	/** @var list<ExtendedFunctionVariant>|null */
	private ?array $variants = null;

	/**
	 * @param array<string, Type> $phpDocParameterTypes
	 * @param array<string, Type> $phpDocParameterOutTypes
	 * @param array<string, bool> $phpDocParameterImmediatelyInvokedCallable
	 * @param array<string, Type> $phpDocParameterClosureThisTypes
	 * @param list<AttributeReflection> $attributes
	 * @param array<string, bool> $phpDocParameterPureUnlessCallableIsImpure
	 */
	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ReflectionFunction $reflection,
		private AttributeReflectionFactory $attributeReflectionFactory,
		private ParameterAllowedConstantsMapProvider $allowedConstantsMapProvider,
		private TemplateTypeMap $templateTypeMap,
		private array $phpDocParameterTypes,
		private ?Type $phpDocReturnType,
		private ?Type $phpDocThrowType,
		private ?string $deprecatedDescription,
		private bool $isDeprecated,
		private bool $isInternal,
		private ?string $filename,
		private ?bool $isPure,
		private Assertions $asserts,
		private bool $acceptsNamedArguments,
		private ?string $phpDocComment,
		private array $phpDocParameterOutTypes,
		private array $phpDocParameterImmediatelyInvokedCallable,
		private array $phpDocParameterClosureThisTypes,
		private array $attributes,
		private array $phpDocParameterPureUnlessCallableIsImpure,
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

	public function getVariants(): array
	{
		return $this->variants ??= [
			new ExtendedFunctionVariant(
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

	public function getOnlyVariant(): ExtendedParametersAcceptor
	{
		return $this->getVariants()[0];
	}

	public function getNamedArgumentsVariants(): ?array
	{
		return null;
	}

	/**
	 * @return list<ExtendedParameterReflection>
	 */
	private function getParameters(): array
	{
		return array_map(function (ReflectionParameter $reflection): PhpParameterReflection {
			if (array_key_exists($reflection->getName(), $this->phpDocParameterImmediatelyInvokedCallable)) {
				$immediatelyInvokedCallable = TrinaryLogic::createFromBoolean($this->phpDocParameterImmediatelyInvokedCallable[$reflection->getName()]);
			} else {
				$immediatelyInvokedCallable = TrinaryLogic::createMaybe();
			}
			return new PhpParameterReflection(
				$this->initializerExprTypeResolver,
				$reflection,
				$this->phpDocParameterTypes[$reflection->getName()] ?? null,
				null,
				$this->phpDocParameterOutTypes[$reflection->getName()] ?? null,
				$immediatelyInvokedCallable,
				$this->phpDocParameterClosureThisTypes[$reflection->getName()] ?? null,
				$this->attributeReflectionFactory->fromNativeReflection($reflection->getAttributes(), InitializerExprContext::fromReflectionParameter($reflection)),
				$this->allowedConstantsMapProvider->getForFunctionParameter(strtolower($this->reflection->getName()), $reflection->getName()),
				$this->phpDocParameterPureUnlessCallableIsImpure[$reflection->getName()] ?? false,
			);
		}, $this->reflection->getParameters());
	}

	private function isVariadic(): bool
	{
		return $this->reflection->isVariadic();
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

		if ($this->reflection->isDeprecated()) {
			$attributes = $this->reflection->getBetterReflection()->getAttributes();
			return DeprecatedAttributeHelper::getDeprecatedDescription($attributes);
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

		return TrinaryLogic::createMaybe();
	}

	public function isPure(): TrinaryLogic
	{
		if ($this->isPure === null) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createFromBoolean($this->isPure);
	}

	public function getPureUnlessCallableIsImpureParameters(): array
	{
		return $this->phpDocParameterPureUnlessCallableIsImpure;
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

	public function acceptsNamedArguments(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->acceptsNamedArguments);
	}

	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function mustUseReturnValue(): TrinaryLogic
	{
		foreach ($this->attributes as $attrib) {
			if (strtolower($attrib->getName()) === 'nodiscard') {
				return TrinaryLogic::createYes();
			}
		}
		return TrinaryLogic::createNo();
	}

}
