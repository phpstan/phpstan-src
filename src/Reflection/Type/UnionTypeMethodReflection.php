<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function array_merge;
use function count;
use function implode;
use function is_bool;

final class UnionTypeMethodReflection implements ExtendedMethodReflection
{

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
		$variants = array_merge(...array_map(static fn (MethodReflection $method) => $method->getVariants(), $this->methods));

		return [ParametersAcceptorSelector::combineAcceptors($variants)];
	}

	public function getOnlyVariant(): ParametersAcceptorWithPhpDocs
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
		return TrinaryLogic::lazyExtremeIdentity($this->methods, static fn (MethodReflection $method): TrinaryLogic => $method->isInternal());
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
		return Assertions::createEmpty();
	}

	public function acceptsNamedArguments(): bool
	{
		$accepts = true;
		foreach ($this->methods as $method) {
			$accepts = $accepts && $method->acceptsNamedArguments();
		}

		return $accepts;
	}

	public function getSelfOutType(): ?Type
	{
		return null;
	}

	public function returnsByReference(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->methods, static fn (ExtendedMethodReflection $method): TrinaryLogic => $method->returnsByReference());
	}

	public function isAbstract(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->methods, static fn (ExtendedMethodReflection $method): TrinaryLogic => is_bool($method->isAbstract()) ? TrinaryLogic::createFromBoolean($method->isAbstract()) : $method->isAbstract());
	}

}
