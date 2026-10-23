<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;
use function array_map;

/**
 * from() and tryFrom() of a backed enum. Reflection declares their parameter
 * as int|string, but PHP only accepts the enum's backing type.
 */
#[ReferencedByTurboExtension(key: 'backedEnumFromMethodReflection')]
final class BackedEnumFromMethodReflection implements ExtendedMethodReflection
{

	/** @var list<ExtendedParametersAcceptor>|null */
	private ?array $variants = null;

	public function __construct(private ExtendedMethodReflection $method, private Type $backingType)
	{
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->method->getDeclaringClass();
	}

	public function isStatic(): bool
	{
		return $this->method->isStatic();
	}

	public function isPrivate(): bool
	{
		return $this->method->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->method->isPublic();
	}

	public function getDocComment(): ?string
	{
		return $this->method->getDocComment();
	}

	public function getName(): string
	{
		return $this->method->getName();
	}

	public function getPrototype(): ClassMemberReflection
	{
		return $this->method->getPrototype();
	}

	public function getVariants(): array
	{
		return $this->variants ??= array_map(fn (ExtendedParametersAcceptor $variant): ExtendedParametersAcceptor => new ExtendedFunctionVariant(
			$variant->getTemplateTypeMap(),
			$variant->getResolvedTemplateTypeMap(),
			array_map(fn (ExtendedParameterReflection $parameter): ExtendedParameterReflection => new ExtendedDummyParameter(
				$parameter->getName(),
				$this->backingType,
				$parameter->isOptional(),
				$parameter->passedByReference(),
				$parameter->isVariadic(),
				$parameter->getDefaultValue(),
				$this->backingType,
				$this->backingType,
				$parameter->getOutType(),
				$parameter->isImmediatelyInvokedCallable(),
				$parameter->getClosureThisType(),
				$parameter->getAttributes(),
				$parameter->getAllowedConstants(),
				$parameter->isPureUnlessCallableIsImpureParameter(),
				$parameter->isPureUnlessParameterPassedParameter(),
			), $variant->getParameters()),
			$variant->isVariadic(),
			$variant->getReturnType(),
			$variant->getPhpDocReturnType(),
			$variant->getNativeReturnType(),
			$variant->getCallSiteVarianceMap(),
		), $this->method->getVariants());
	}

	public function getOnlyVariant(): ExtendedParametersAcceptor
	{
		return $this->getVariants()[0];
	}

	public function getNamedArgumentsVariants(): ?array
	{
		return null;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return $this->method->isDeprecated();
	}

	public function getDeprecatedDescription(): ?string
	{
		return $this->method->getDeprecatedDescription();
	}

	public function isFinal(): TrinaryLogic
	{
		return $this->method->isFinal();
	}

	public function isFinalByKeyword(): TrinaryLogic
	{
		return $this->method->isFinalByKeyword();
	}

	public function isInternal(): TrinaryLogic
	{
		return $this->method->isInternal();
	}

	public function isBuiltin(): TrinaryLogic|bool
	{
		return $this->method->isBuiltin();
	}

	public function getThrowType(): ?Type
	{
		return $this->method->getThrowType();
	}

	public function hasSideEffects(): TrinaryLogic
	{
		return $this->method->hasSideEffects();
	}

	public function getAsserts(): Assertions
	{
		return $this->method->getAsserts();
	}

	public function acceptsNamedArguments(): TrinaryLogic
	{
		return $this->method->acceptsNamedArguments();
	}

	public function getSelfOutType(): ?Type
	{
		return $this->method->getSelfOutType();
	}

	public function returnsByReference(): TrinaryLogic
	{
		return $this->method->returnsByReference();
	}

	public function isAbstract(): TrinaryLogic|bool
	{
		return $this->method->isAbstract();
	}

	public function isPure(): TrinaryLogic
	{
		return $this->method->isPure();
	}

	public function getPureUnlessCallableIsImpureParameters(): array
	{
		return $this->method->getPureUnlessCallableIsImpureParameters();
	}

	public function getPureUnlessParameterPassedParameters(): array
	{
		return $this->method->getPureUnlessParameterPassedParameters();
	}

	public function getAttributes(): array
	{
		return $this->method->getAttributes();
	}

	public function mustUseReturnValue(): TrinaryLogic
	{
		return $this->method->mustUseReturnValue();
	}

	public function getResolvedPhpDoc(): ?ResolvedPhpDocBlock
	{
		return $this->method->getResolvedPhpDoc();
	}

}
