<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\TrinaryLogic;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @template TBound of Type
 */
trait TemplateTypeTrait
{

	private string $name;

	private TemplateTypeScope $scope;

	private TemplateTypeStrategy $strategy;

	private TemplateTypeVariance $variance;

	/** @var TBound */
	private Type $bound;

	public function getName(): string
	{
		return $this->name;
	}

	public function getScope(): TemplateTypeScope
	{
		return $this->scope;
	}

	/** @return TBound */
	public function getBound(): Type
	{
		return $this->bound;
	}

	public function describe(VerbosityLevel $level): string
	{
		$basicDescription = function () use ($level): string {
			// @phpstan-ignore-next-line
			if ($this->bound instanceof MixedType && $this->bound->getSubtractedType() === null && !$this->bound instanceof TemplateMixedType) {
				$boundDescription = '';
			} else { // @phpstan-ignore-line
				$boundDescription = sprintf(' of %s', $this->bound->describe($level));
			}
			return sprintf(
				'%s%s',
				$this->name,
				$boundDescription,
			);
		};

		return $level->handle(
			$basicDescription,
			$basicDescription,
			fn (): string => sprintf('%s (%s, %s)', $basicDescription(), $this->scope->describe(), $this->isArgument() ? 'argument' : 'parameter'),
		);
	}

	public function isArgument(): bool
	{
		return $this->strategy->isArgument();
	}

	public function toArgument(): TemplateType
	{
		return new self(
			$this->scope,
			new TemplateTypeArgumentStrategy(),
			$this->variance,
			$this->name,
			TemplateTypeHelper::toArgument($this->getBound()),
		);
	}

	public function isValidVariance(Type $a, Type $b): TrinaryLogic
	{
		return $this->variance->isValidVariance($a, $b);
	}

	public function subtract(Type $type): Type
	{
		return $this;
	}

	public function getTypeWithoutSubtractedType(): Type
	{
		return $this;
	}

	public function changeSubtractedType(?Type $subtractedType): Type
	{
		return $this;
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $type->scope->equals($this->scope)
			&& $type->name === $this->name
			&& $this->bound->equals($type->bound);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		/** @var Type $bound */
		$bound = $this->getBound();
		if (
			!$acceptingType instanceof $bound
			&& !$this instanceof $acceptingType
			&& !$acceptingType instanceof TemplateType
			&& ($acceptingType instanceof UnionType || $acceptingType instanceof IntersectionType)
		) {
			return $acceptingType->accepts($this, $strictTypes);
		}

		if (!$acceptingType instanceof TemplateType) {
			return $acceptingType->accepts($this->getBound(), $strictTypes);
		}

		if ($this->getScope()->equals($acceptingType->getScope()) && $this->getName() === $acceptingType->getName()) {
			return $acceptingType->getBound()->accepts($this->getBound(), $strictTypes);
		}

		return $acceptingType->getBound()->accepts($this->getBound(), $strictTypes)
			->and(TrinaryLogic::createMaybe());
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->strategy->accepts($this, $type, $strictTypes);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof TemplateType || $type instanceof IntersectionType) {
			return $type->isSubTypeOf($this);
		}

		return $this->getBound()->isSuperTypeOf($type)
			->and(TrinaryLogic::createMaybe());
	}

	public function isSubTypeOf(Type $type): TrinaryLogic
	{
		/** @var Type $bound */
		$bound = $this->getBound();
		if (
			!$type instanceof $bound
			&& !$this instanceof $type
			&& !$type instanceof TemplateType
			&& ($type instanceof UnionType || $type instanceof IntersectionType)
		) {
			return $type->isSuperTypeOf($this);
		}

		if (!$type instanceof TemplateType) {
			return $type->isSuperTypeOf($this->getBound());
		}

		if ($this->getScope()->equals($type->getScope()) && $this->getName() === $type->getName()) {
			return $type->getBound()->isSuperTypeOf($this->getBound());
		}

		return $type->getBound()->isSuperTypeOf($this->getBound())
			->and(TrinaryLogic::createMaybe());
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		if (!$receivedType instanceof TemplateType && ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType)) {
			return $receivedType->inferTemplateTypesOn($this);
		}

		if (
			$receivedType instanceof TemplateType
			&& $this->getBound()->isSuperTypeOf($receivedType->getBound())->yes()
		) {
			return new TemplateTypeMap([
				$this->name => $receivedType,
			]);
		}

		$map = $this->getBound()->inferTemplateTypes($receivedType);
		$resolvedBound = TemplateTypeHelper::resolveTemplateTypes($this->getBound(), $map);
		if ($resolvedBound->isSuperTypeOf($receivedType)->yes()) {
			return (new TemplateTypeMap([
				$this->name => $this->shouldGeneralizeInferredType() ? $receivedType->generalize(GeneralizePrecision::templateArgument()) : $receivedType,
			]))->union($map);
		}

		return $map;
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		return [new TemplateTypeReference($this, $positionVariance)];
	}

	public function getVariance(): TemplateTypeVariance
	{
		return $this->variance;
	}

	protected function shouldGeneralizeInferredType(): bool
	{
		return true;
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		$removedBound = TypeCombinator::remove($this->getBound(), $typeToRemove);
		$type = TemplateTypeFactory::create(
			$this->getScope(),
			$this->getName(),
			$removedBound,
			$this->getVariance(),
		);
		if ($this->isArgument()) {
			return TemplateTypeHelper::toArgument($type);
		}

		return $type;
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['scope'],
			$properties['strategy'],
			$properties['variance'],
			$properties['name'],
			$properties['bound'],
		);
	}

}
