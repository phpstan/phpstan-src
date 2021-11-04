<?php // lint >= 8.0

namespace Bug5868PropertyFetch;

class Child
{

    public ?self $child;

	public self $existingChild;

}

class Foo
{
	public ?Child $child;
}

class HelloWorld
{

	function getAttributeInNode(?Foo $node): ?Child
	{
		// Ok
		$tmp = $node?->child;
		$tmp = $node?->child?->child?->child;
		$tmp = $node?->child?->existingChild->child;
		$tmp = $node?->child?->existingChild->child?->existingChild;

		// Errors
		$tmp = $node->child;
		$tmp = $node?->child->child;
		$tmp = $node?->child->existingChild->child;
		$tmp = $node?->child?->existingChild->child->existingChild;
	}

}
