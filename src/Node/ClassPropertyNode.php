<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeAbstract;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Type;

/** @api */
class ClassPropertyNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private string $name,
		private int $flags,
		private Identifier|Name|Node\ComplexType|null $type,
		private ?Expr $default,
		private ?string $phpDoc,
		private ?Type $phpDocType,
		private bool $isPromoted,
		private bool $isPromotedFromTrait,
		Node $originalNode,
		private bool $isReadonlyByPhpDoc,
		private bool $isDeclaredInTrait,
		private bool $isReadonlyClass,
		private bool $isAllowedPrivateMutation,
		private ClassReflection $classReflection,
	)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getFlags(): int
	{
		return $this->flags;
	}

	public function getDefault(): ?Expr
	{
		return $this->default;
	}

	public function isPromoted(): bool
	{
		return $this->isPromoted;
	}

	public function isPromotedFromTrait(): bool
	{
		return $this->isPromotedFromTrait;
	}

	public function getPhpDoc(): ?string
	{
		return $this->phpDoc;
	}

	public function getPhpDocType(): ?Type
	{
		return $this->phpDocType;
	}

	public function isPublic(): bool
	{
		return ($this->flags & Modifiers::PUBLIC) !== 0
			|| ($this->flags & Modifiers::VISIBILITY_MASK) === 0;
	}

	public function isProtected(): bool
	{
		return (bool) ($this->flags & Modifiers::PROTECTED);
	}

	public function isPrivate(): bool
	{
		return (bool) ($this->flags & Modifiers::PRIVATE);
	}

	public function isStatic(): bool
	{
		return (bool) ($this->flags & Modifiers::STATIC);
	}

	public function isReadOnly(): bool
	{
		return (bool) ($this->flags & Modifiers::READONLY) || $this->isReadonlyClass;
	}

	public function isReadOnlyByPhpDoc(): bool
	{
		return $this->isReadonlyByPhpDoc;
	}

	public function isDeclaredInTrait(): bool
	{
		return $this->isDeclaredInTrait;
	}

	public function isAllowedPrivateMutation(): bool
	{
		return $this->isAllowedPrivateMutation;
	}

	/**
	 * @return Identifier|Name|Node\ComplexType|null
	 */
	public function getNativeType()
	{
		return $this->type;
	}

	public function getClassReflection(): ClassReflection
	{
		return $this->classReflection;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_ClassPropertyNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
