<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\ObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use function sprintf;

/** @api */
class ObjectWithoutClassType implements SubtractableType
{

	use ObjectTypeTrait;
	use NonGenericTypeTrait;
	use UndecidedComparisonTypeTrait;
	use NonGeneralizableTypeTrait;

	private ?Type $subtractedType;

	/** @api */
	public function __construct(
		?Type $subtractedType = null,
	)
	{
		if ($subtractedType instanceof NeverType) {
			$subtractedType = null;
		}

		$this->subtractedType = $subtractedType;
	}

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return [];
	}

	public function getObjectClassNames(): array
	{
		return [];
	}

	public function getObjectClassReflections(): array
	{
		return [];
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->acceptsWithReason($type, $strictTypes)->result;
	}

	public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedWithReasonBy($this, $strictTypes);
		}

		return AcceptsResult::createFromBoolean(
			$type instanceof self || $type instanceof ObjectShapeType || $type->getObjectClassNames() !== [],
		);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		if ($type instanceof self) {
			if ($this->subtractedType === null) {
				return TrinaryLogic::createYes();
			}
			if ($type->subtractedType !== null) {
				$isSuperType = $type->subtractedType->isSuperTypeOf($this->subtractedType);
				if ($isSuperType->yes()) {
					return TrinaryLogic::createYes();
				}
			}

			return TrinaryLogic::createMaybe();
		}

		if ($type instanceof ObjectShapeType) {
			return TrinaryLogic::createYes();
		}

		if ($type->getObjectClassNames() === []) {
			return TrinaryLogic::createNo();
		}

		if ($this->subtractedType === null) {
			return TrinaryLogic::createYes();
		}

		return $this->subtractedType->isSuperTypeOf($type)->negate();
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		if ($this->subtractedType === null) {
			if ($type->subtractedType === null) {
				return true;
			}

			return false;
		}

		if ($type->subtractedType === null) {
			return false;
		}

		return $this->subtractedType->equals($type->subtractedType);
	}

	public function describe(VerbosityLevel $level): string
	{
		return $level->handle(
			static fn (): string => 'object',
			static fn (): string => 'object',
			function () use ($level): string {
				$description = 'object';
				if ($this->subtractedType !== null) {
					$description .= sprintf('~%s', $this->subtractedType->describe($level));
				}

				return $description;
			},
		);
	}

	public function isOffsetAccessLegal(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getEnumCases(): array
	{
		return [];
	}

	public function subtract(Type $type): Type
	{
		if ($type instanceof self) {
			return new NeverType();
		}
		if ($this->subtractedType !== null) {
			$type = TypeCombinator::union($this->subtractedType, $type);
		}

		return new self($type);
	}

	public function getTypeWithoutSubtractedType(): Type
	{
		return new self();
	}

	public function changeSubtractedType(?Type $subtractedType): Type
	{
		return new self($subtractedType);
	}

	public function getSubtractedType(): ?Type
	{
		return $this->subtractedType;
	}

	public function traverse(callable $cb): Type
	{
		$subtractedType = $this->subtractedType !== null ? $cb($this->subtractedType) : null;

		if ($subtractedType !== $this->subtractedType) {
			return new self($subtractedType);
		}

		return $this;
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if ($this->subtractedType === null) {
			return $this;
		}

		return new self();
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($this->isSuperTypeOf($typeToRemove)->yes()) {
			return $this->subtract($typeToRemove);
		}

		return null;
	}

	public function exponentiate(Type $exponent): Type
	{
		if (!$exponent instanceof NeverType && !$this->isSuperTypeOf($exponent)->no()) {
			return TypeCombinator::union($this, $exponent);
		}

		return new BenevolentUnionType([
			new FloatType(),
			new IntegerType(),
		]);
	}

	public function getFiniteTypes(): array
	{
		return [];
	}

	public function toPhpDocNode(): TypeNode
	{
		return new IdentifierTypeNode('object');
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['subtractedType'] ?? null);
	}

}
