<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function count;
use function implode;

class IntersectionTypeMethodReflection implements MethodReflection
{

	private string $methodName;

	/** @var MethodReflection[] */
	private array $methods;

	/**
	 * @param MethodReflection[] $methods
	 */
	public function __construct(string $methodName, array $methods)
	{
		$this->methodName = $methodName;
		$this->methods = $methods;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->methods[0]->getDeclaringClass();
	}

	public function isStatic(): bool
	{
		foreach ($this->methods as $method) {
			if ($method->isStatic()) {
				return true;
			}
		}

		return false;
	}

	public function isPrivate(): bool
	{
		foreach ($this->methods as $method) {
			if (!$method->isPrivate()) {
				return false;
			}
		}

		return true;
	}

	public function isPublic(): bool
	{
		foreach ($this->methods as $method) {
			if ($method->isPublic()) {
				return true;
			}
		}

		return false;
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
		$returnType = TypeCombinator::intersect(...array_map(static fn (MethodReflection $method): Type => TypeCombinator::intersect(...array_map(static fn (ParametersAcceptor $acceptor): Type => $acceptor->getReturnType(), $method->getVariants())), $this->methods));

		return array_map(static fn (ParametersAcceptor $acceptor): ParametersAcceptor => new FunctionVariant(
			$acceptor->getTemplateTypeMap(),
			$acceptor->getResolvedTemplateTypeMap(),
			$acceptor->getParameters(),
			$acceptor->isVariadic(),
			$returnType,
		), $this->methods[0]->getVariants());
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::maxMin(...array_map(static fn (MethodReflection $method): TrinaryLogic => $method->isDeprecated(), $this->methods));
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
		return TrinaryLogic::maxMin(...array_map(static fn (MethodReflection $method): TrinaryLogic => $method->isFinal(), $this->methods));
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::maxMin(...array_map(static fn (MethodReflection $method): TrinaryLogic => $method->isInternal(), $this->methods));
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

		return TypeCombinator::intersect(...$types);
	}

	public function hasSideEffects(): TrinaryLogic
	{
		return TrinaryLogic::maxMin(...array_map(static fn (MethodReflection $method): TrinaryLogic => $method->hasSideEffects(), $this->methods));
	}

	public function getDocComment(): ?string
	{
		return null;
	}

}
