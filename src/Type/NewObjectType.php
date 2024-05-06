<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function sprintf;

/** @api */
class NewObjectType implements CompoundType, LateResolvableType
{

	use LateResolvableTypeTrait;
	use NonGeneralizableTypeTrait;

	public function __construct(private Type $type)
	{
	}

	public function getType(): Type
	{
		return $this->type;
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
		return sprintf('new<%s>', $this->type->describe($level));
	}

	public function isResolvable(): bool
	{
		return !TypeUtils::containsTemplateType($this->type);
	}

	protected function getResult(): Type
	{
		return $this->type->getObjectTypeOrClassStringObjectType();
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
		return new GenericTypeNode(new IdentifierTypeNode('new'), [$this->type->toPhpDocNode()]);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['type'],
		);
	}

}
