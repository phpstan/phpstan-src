<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\UnionType;
use PhpParser\NodeAbstract;

class ClassPropertyNode extends NodeAbstract implements VirtualNode
{

	private string $name;

	private int $flags;

	/** @var Identifier|Name|NullableType|UnionType|null */
	private $type;

	private ?Expr $default;

	private ?string $phpDoc;

	private bool $isPromoted;

	/**
	 * @param int $flags
	 * @param Identifier|Name|NullableType|UnionType|null $type
	 * @param string $name
	 * @param Expr|null $default
	 */
	public function __construct(
		string $name,
		int $flags,
		$type,
		?Expr $default,
		?string $phpDoc,
		bool $isPromoted,
		Node $originalNode
	)
	{
		parent::__construct($originalNode->getAttributes());
		$this->name = $name;
		$this->flags = $flags;
		$this->type = $type;
		$this->default = $default;
		$this->isPromoted = $isPromoted;
		$this->phpDoc = $phpDoc;
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

	/**
	 * @return Identifier|Name|NullableType|UnionType|null
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
