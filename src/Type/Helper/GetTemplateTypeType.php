<?php declare(strict_types = 1);

namespace PHPStan\Type\Helper;

use PHPStan\PhpDocParser\Ast\ConstExpr\QuoteAwareConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/** @api */
final class GetTemplateTypeType implements CompoundType, LateResolvableType
{

	use LateResolvableTypeTrait;
	use NonGeneralizableTypeTrait;

	/**
	 * @param class-string $ancestorClassName
	 */
	public function __construct(private Type $type, private string $ancestorClassName, private string $templateTypeName)
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
		return sprintf('template-type<%s, %s, %s>', $this->type->describe($level), $this->ancestorClassName, $this->templateTypeName);
	}

	public function isResolvable(): bool
	{
		return !TypeUtils::containsTemplateType($this->type);
	}

	protected function getResult(): Type
	{
		return $this->type->getTemplateType($this->ancestorClassName, $this->templateTypeName);
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

		return new self($type, $this->ancestorClassName, $this->templateTypeName);
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

		return new self($type, $this->ancestorClassName, $this->templateTypeName);
	}

	public function traverseWithVariance(TemplateTypeVariance $variance, callable $cb): Type
	{
		$type = $this->type->traverseWithVariance($variance, $cb);

		if ($this->type === $type) {
			return $this;
		}

		return new self($type, $this->ancestorClassName, $this->templateTypeName);
	}

	public function toPhpDocNode(): TypeNode
	{
		return new GenericTypeNode(
			new IdentifierTypeNode('template-type'),
			[
				$this->type->toPhpDocNode(),
				new IdentifierTypeNode($this->ancestorClassName),
				new ConstTypeNode(new QuoteAwareConstExprStringNode($this->templateTypeName, QuoteAwareConstExprStringNode::SINGLE_QUOTED)),
			],
		);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['type'],
			$properties['ancestorClassName'],
			$properties['templateTypeName'],
		);
	}

}
