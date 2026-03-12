<?php // lint >= 8.0

namespace BugNullsafeCoalesceNullableObject;

class Detail
{
	public string $label;

	public function __construct(string $label)
	{
		$this->label = $label;
	}
}

class Node
{
	private ?Detail $detail;

	public function __construct(?Detail $detail)
	{
		$this->detail = $detail;
	}

	public function getDetail(): ?Detail
	{
		return $this->detail;
	}
}

class Root
{
	private ?Node $node;

	public function __construct(?Node $node)
	{
		$this->node = $node;
	}

	public function getNode(): ?Node
	{
		return $this->node;
	}
}

class Foo
{
	public function chainedNullable(Root $root): void
	{
		$a = $root->getNode()?->getDetail()?->label ?? '';
	}

	public function singleNullable(Node $node): void
	{
		$a = $node->getDetail()?->label ?? '';
	}

	public function allNonNullable(Detail $detail): void
	{
		$a = $detail?->label ?? '';
	}
}
