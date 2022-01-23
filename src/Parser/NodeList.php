<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;

class NodeList
{

	public function __construct(private Node $node, private ?self $next = null)
	{
	}

	public function append(Node $node): self
	{
		$current = $this;
		while ($current->next !== null) {
			$current = $current->next;
		}

		$new = new self($node);
		$current->next = $new;

		return $new;
	}

	public function getNode(): Node
	{
		return $this->node;
	}

	public function getNext(): ?self
	{
		return $this->next;
	}

}
