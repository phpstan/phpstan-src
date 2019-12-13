<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\TrinaryLogic;
use PHPStan\Type\CompoundType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

class TemplateObjectWithoutClassType extends ObjectWithoutClassType implements TemplateType
{

	/** @var TemplateTypeScope */
	private $scope;

	/** @var string */
	private $name;

	/** @var TemplateTypeStrategy */
	private $strategy;

	/** @var TemplateTypeVariance */
	private $variance;

	/** @var ObjectWithoutClassType|null */
	private $bound;

	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		?Type $subtractedType = null
	)
	{
		parent::__construct($subtractedType);

		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getScope(): TemplateTypeScope
	{
		return $this->scope;
	}

	public function getBound(): Type
	{
		if ($this->bound === null) {
			$this->bound = new ObjectWithoutClassType($this->getSubtractedType());
		}
		return $this->bound;
	}

	public function describe(VerbosityLevel $level): string
	{
		$basicDescription = function () use ($level): string {
			return sprintf(
				'%s of %s',
				$this->name,
				parent::describe($level)
			);
		};

		return $level->handle(
			$basicDescription,
			$basicDescription,
			function () use ($basicDescription): string {
				return sprintf('%s (%s, %s)', $basicDescription(), $this->scope->describe(), $this->isArgument() ? 'argument' : 'parameter');
			}
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
			$this->getSubtractedType()
		);
	}

	public function isValidVariance(Type $a, Type $b): bool
	{
		return $this->variance->isValidVariance($a, $b);
	}

	public function getTypeWithoutSubtractedType(): Type
	{
		return new self(
			$this->scope,
			$this->strategy,
			$this->variance,
			$this->name,
			null
		);
	}

	public function subtract(Type $type): Type
	{
		if ($type instanceof self) {
			return new NeverType();
		}
		if ($this->getSubtractedType() !== null) {
			$type = TypeCombinator::union($this->getSubtractedType(), $type);
		}

		return new self(
			$this->scope,
			$this->strategy,
			$this->variance,
			$this->name,
			$type
		);
	}

	public function changeSubtractedType(?Type $subtractedType): Type
	{
		return new self(
			$this->scope,
			$this->strategy,
			$this->variance,
			$this->name,
			$subtractedType
		);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $type->scope->equals($this->scope)
			&& $type->name === $this->name
			&& parent::equals($type);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		return $this->isSubTypeOf($acceptingType);
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->strategy->accepts($this, $type, $strictTypes);
	}

	public function isSubTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof UnionType || $type instanceof IntersectionType) {
			return $type->isSuperTypeOf($this);
		}

		if ($type instanceof ObjectWithoutClassType && !$type instanceof self) {
			return TrinaryLogic::createYes();
		}

		if (!$type instanceof TemplateType) {
			return $type->isSuperTypeOf($this->getBound())
				->and(TrinaryLogic::createMaybe());
		}

		if ($this->equals($type)) {
			return TrinaryLogic::createYes();
		}

		return $type->getBound()->isSuperTypeOf($this->getBound())
			->and(TrinaryLogic::createMaybe());
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return $this->getBound()->isSuperTypeOf($type)
			->and(TrinaryLogic::createMaybe());
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		if ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType) {
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

		if ($this->getBound()->isSuperTypeOf($receivedType)->yes()) {
			return new TemplateTypeMap([
				$this->name => TypeUtils::generalizeType($receivedType),
			]);
		}

		return TemplateTypeMap::createEmpty();
	}


	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		return [new TemplateTypeReference($this, $positionVariance)];
	}

	public function getVariance(): TemplateTypeVariance
	{
		return $this->variance;
	}

	/**
	 * @param mixed[] $properties
	 * @return Type
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['scope'],
			$properties['strategy'],
			$properties['variance'],
			$properties['name'],
			$properties['subtractedType']
		);
	}

}
