<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\ExtendedDummyParameter;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_filter;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function implode;
use function is_bool;

final class UnionTypeMethodReflection implements ExtendedMethodReflection
{

	/** @var list<ExtendedParametersAcceptor>|null */
	private ?array $cachedVariants = null;

	/**
	 * @param ExtendedMethodReflection[] $methods
	 */
	public function __construct(private string $methodName, private array $methods)
	{
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->methods[0]->getDeclaringClass();
	}

	public function isStatic(): bool
	{
		foreach ($this->methods as $method) {
			if (!$method->isStatic()) {
				return false;
			}
		}

		return true;
	}

	public function isPrivate(): bool
	{
		foreach ($this->methods as $method) {
			if ($method->isPrivate()) {
				return true;
			}
		}

		return false;
	}

	public function isPublic(): bool
	{
		foreach ($this->methods as $method) {
			if (!$method->isPublic()) {
				return false;
			}
		}

		return true;
	}

	public function getName(): string
	{
		return $this->methodName;
	}

	public function getPrototype(): ClassMemberReflection
	{
		return $this;
	}

	public function getVariants(): array
	{
		if ($this->cachedVariants !== null) {
			return $this->cachedVariants;
		}

		$allVariants = array_merge(...array_map(static fn (MethodReflection $method) => $method->getVariants(), $this->methods));
		$combined = ParametersAcceptorSelector::combineAcceptors($allVariants);

		// Fast path: when all methods come from the same class (e.g. enum cases,
		// or multiple subtypes of the same base), params are identical — skip
		// the expensive per-parameter intersection.
		$declaringClasses = [];
		foreach ($this->methods as $method) {
			$declaringClasses[$method->getDeclaringClass()->getName()] = true;
		}

		if (count($declaringClasses) <= 1) {
			return $this->cachedVariants = [$combined];
		}

		// combineAcceptors unions parameter types, but for union types we need
		// to intersect them: the argument must be valid for ALL possible methods
		// since we don't know which runtime type the object is.
		$intersectedParams = [];
		foreach ($combined->getParameters() as $i => $param) {
			$types = [];
			$nativeTypes = [];
			$phpDocTypes = [];
			foreach ($this->methods as $method) {
				$variantTypes = [];
				$variantNativeTypes = [];
				$variantPhpDocTypes = [];
				foreach ($method->getVariants() as $variant) {
					$variantParams = $variant->getParameters();
					if (!isset($variantParams[$i])) {
						continue;
					}
					$variantTypes[] = $variantParams[$i]->getType();
					$variantNativeTypes[] = $variantParams[$i]->getNativeType();
					$variantPhpDocTypes[] = $variantParams[$i]->getPhpDocType();
				}
				if ($variantTypes !== []) {
					$types[] = count($variantTypes) === 1 ? $variantTypes[0] : TypeCombinator::union(...$variantTypes);
				}
				if ($variantNativeTypes !== []) {
					$nativeTypes[] = count($variantNativeTypes) === 1 ? $variantNativeTypes[0] : TypeCombinator::union(...$variantNativeTypes);
				}
				if ($variantPhpDocTypes !== []) {
					$phpDocTypes[] = count($variantPhpDocTypes) === 1 ? $variantPhpDocTypes[0] : TypeCombinator::union(...$variantPhpDocTypes);
				}
			}

			$intersectedParams[] = new ExtendedDummyParameter(
				$param->getName(),
				count($types) > 1 ? TypeCombinator::intersect(...$types) : ($types[0] ?? $param->getType()),
				$param->isOptional(),
				$param->passedByReference(),
				$param->isVariadic(),
				$param->getDefaultValue(),
				count($nativeTypes) > 1 ? TypeCombinator::intersect(...$nativeTypes) : ($nativeTypes[0] ?? $param->getNativeType()),
				count($phpDocTypes) > 1 ? TypeCombinator::intersect(...$phpDocTypes) : ($phpDocTypes[0] ?? $param->getPhpDocType()),
				$param->getOutType(),
				$param->isImmediatelyInvokedCallable(),
				$param->getClosureThisType(),
				$param->getAttributes(),
			);
		}

		return $this->cachedVariants = [new ExtendedFunctionVariant(
			$combined->getTemplateTypeMap(),
			$combined->getResolvedTemplateTypeMap(),
			$intersectedParams,
			$combined->isVariadic(),
			$combined->getReturnType(),
			$combined->getPhpDocReturnType(),
			$combined->getNativeReturnType(),
		)];
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
		return TrinaryLogic::lazyExtremeIdentity($this->methods, static fn (MethodReflection $method): TrinaryLogic => $method->isDeprecated());
	}

	public function getDeprecatedDescription(): ?string
	{
		$descriptions = [];
		foreach ($this->methods as $method) {
			if (!$method->isDeprecated()->yes()) {
				continue;
			}
			$description = $method->getDeprecatedDescription();
			if ($description === null) {
				continue;
			}

			$descriptions[] = $description;
		}

		if (count($descriptions) === 0) {
			return null;
		}

		return implode(' ', $descriptions);
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->methods, static fn (MethodReflection $method): TrinaryLogic => $method->isFinal());
	}

	public function isFinalByKeyword(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->methods, static fn (ExtendedMethodReflection $method): TrinaryLogic => $method->isFinalByKeyword());
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->methods, static fn (ExtendedMethodReflection $method): TrinaryLogic => $method->isInternal());
	}

	public function isBuiltin(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->methods, static fn (ExtendedMethodReflection $method): TrinaryLogic => is_bool($method->isBuiltin()) ? TrinaryLogic::createFromBoolean($method->isBuiltin()) : $method->isBuiltin());
	}

	public function getThrowType(): ?Type
	{
		$types = [];

		foreach ($this->methods as $method) {
			$type = $method->getThrowType();
			if ($type === null) {
				continue;
			}

			$types[] = $type;
		}

		if (count($types) === 0) {
			return null;
		}

		return TypeCombinator::union(...$types);
	}

	public function hasSideEffects(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->methods, static fn (MethodReflection $method): TrinaryLogic => $method->hasSideEffects());
	}

	public function isPure(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->methods, static fn (ExtendedMethodReflection $method): TrinaryLogic => $method->isPure());
	}

	public function getDocComment(): ?string
	{
		return null;
	}

	public function getAsserts(): Assertions
	{
		$assertions = Assertions::createEmpty();

		foreach ($this->methods as $method) {
			$assertions = $assertions->intersect($method->getAsserts());
		}

		return $assertions;
	}

	public function acceptsNamedArguments(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->methods, static fn (ExtendedMethodReflection $method): TrinaryLogic => $method->acceptsNamedArguments());
	}

	public function getSelfOutType(): ?Type
	{
		$types = [];
		foreach ($this->methods as $method) {
			$selfOutType = $method->getSelfOutType();
			if ($selfOutType === null) {
				return null;
			}
			$types[] = $selfOutType;
		}

		if (count($types) === 0) {
			return null;
		}

		return TypeCombinator::union(...$types);
	}

	public function returnsByReference(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->methods, static fn (ExtendedMethodReflection $method): TrinaryLogic => $method->returnsByReference());
	}

	public function isAbstract(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->methods, static fn (ExtendedMethodReflection $method): TrinaryLogic => is_bool($method->isAbstract()) ? TrinaryLogic::createFromBoolean($method->isAbstract()) : $method->isAbstract());
	}

	public function getAttributes(): array
	{
		$result = null;
		foreach ($this->methods as $method) {
			$methodAttributes = $method->getAttributes();
			if ($result === null) {
				$result = $methodAttributes;
				continue;
			}
			$methodAttributeNames = [];
			foreach ($methodAttributes as $attribute) {
				$methodAttributeNames[$attribute->getName()] = true;
			}
			$result = array_filter($result, static fn (AttributeReflection $a) => isset($methodAttributeNames[$a->getName()]));
		}

		return array_values($result ?? []);
	}

	public function mustUseReturnValue(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->methods, static fn (ExtendedMethodReflection $method): TrinaryLogic => $method->mustUseReturnValue());
	}

	public function getResolvedPhpDoc(): ?ResolvedPhpDocBlock
	{
		return null;
	}

}
