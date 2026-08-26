<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_values;
use function count;
use function sprintf;

/** @api */
final class ValueOfType implements CompoundType, LateResolvableType
{

	use LateResolvableTypeTrait;
	use NonGeneralizableTypeTrait;

	public function __construct(private Type $type)
	{
	}

	public function getReferencedClasses(): array
	{
		return $this->type->getReferencedClasses();
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		return $this->type->getReferencedTemplateTypes($positionVariance);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $this->type->equals($type->type);
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('value-of<%s>', $this->type->describe($level));
	}

	public function isResolvable(): bool
	{
		return !TypeUtils::containsTemplateType($this->type);
	}

	protected function getResult(): Type
	{
		if ($this->type->isEnum()->yes()) {
			$enumCases = $this->type->getEnumCases();
			if (
				$enumCases === []
				&& $this->type instanceof TemplateType
				&& (new ObjectType('BackedEnum'))->isSuperTypeOf($this->type->getBound())->yes()
			) {
				$backingTypes = [];
				foreach ($this->type->getBound()->getObjectClassReflections() as $classReflection) {
					$ancestor = $classReflection->getAncestorWithClassName('BackedEnum');
					if ($ancestor === null) {
						$backingTypes = [];
						break;
					}

					$ancestorTypes = $ancestor->getActiveTemplateTypeMap()->getTypes();
					if (count($ancestorTypes) !== 1) {
						$backingTypes = [];
						break;
					}

					$backingType = array_values($ancestorTypes)[0];
					if ($backingType instanceof ErrorType) {
						$backingTypes = [];
						break;
					}

					$backingTypes[] = $backingType;
				}

				if ($backingTypes !== []) {
					return TypeCombinator::union(...$backingTypes);
				}

				return new UnionType([new IntegerType(), new StringType()]);
			}

			$valueTypes = [];
			foreach ($enumCases as $enumCase) {
				$valueType = $enumCase->getBackingValueType();
				if ($valueType === null) {
					continue;
				}

				$valueTypes[] = $valueType;
			}

			if (count($valueTypes) === 0) {
				return new NeverType();
			}
			if (count($valueTypes) === 1) {
				return $valueTypes[0];
			}

			return new UnionType($valueTypes);
		}

		return $this->type->getIterableValueType();
	}

	/**
	 * @param callable(Type): Type $cb
	 */
	public function traverse(callable $cb): Type
	{
		$type = $cb($this->type);

		if ($this->type === $type) {
			return $this;
		}

		return new self($type);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if (!$right instanceof self) {
			return $this;
		}

		$type = $cb($this->type, $right->type);

		if ($this->type === $type) {
			return $this;
		}

		return new self($type);
	}

	public function toPhpDocNode(): TypeNode
	{
		return new GenericTypeNode(new IdentifierTypeNode('value-of'), [$this->type->toPhpDocNode()]);
	}

}
