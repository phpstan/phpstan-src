<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeAbstract;

/** @api */
class ClassPropertyNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private string $name,
		private int $flags,
		private Identifier|Name|Node\ComplexType|null $type,
		private ?Expr $default,
		private ?string $phpDoc,
		private bool $isPromoted,
		Node $originalNode,
		private bool $isReadonlyByPhpDoc = false,
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

	public function getPhpDoc(): ?string
	{
		return $this->phpDoc;
	}

	public function isPublic(): bool
	{
		return ($this->flags & Class_::MODIFIER_PUBLIC) !== 0
			|| ($this->flags & Class_::VISIBILITY_MODIFIER_MASK) === 0;
	}

	public function isProtected(): bool
	{
		return (bool) ($this->flags & Class_::MODIFIER_PROTECTED);
	}

	public function isPrivate(): bool
	{
		return (bool) ($this->flags & Class_::MODIFIER_PRIVATE);
	}

	public function isStatic(): bool
	{
		return (bool) ($this->flags & Class_::MODIFIER_STATIC);
	}

	public function isReadOnly(): bool
	{
		return (bool) ($this->flags & Class_::MODIFIER_READONLY);
	}

	public function isReadonlyByPhpDoc(): bool
	{
		return $this->isReadonlyByPhpDoc;
	}

	/**
	 * @return Identifier|Name|Node\ComplexType|null
	 */
	public function getNativeType()
	{
		return $this->type;
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
