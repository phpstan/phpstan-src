<?php

namespace Bug4707Covariant;

/**
 * @template-covariant TParent of ParentNodeInterface
 */
interface ChildNodeInterface
{
	/** @return TParent */
	public function getParent(): ParentNodeInterface;
}

interface ParentNodeInterface
{
	/** @return list<ChildNodeInterface<static>> */
	public function getChildren(): array;
}

final class Block implements ParentNodeInterface
{
	/** @var list<Row> */
	private $rows = [];

	/** @return list<Row> */
	public function getChildren(): array
	{
		return $this->rows;
	}
}

class Block2 implements ParentNodeInterface
{
	/** @var list<Row2> */
	private $rows = [];

	/** @return list<Row2> */
	public function getChildren(): array
	{
		return $this->rows;
	}
}

/** @implements ChildNodeInterface<Block> */
final class Row implements ChildNodeInterface
{
	/** @var Block $parent */
	private $parent;

	public function getParent(): Block
	{
		return $this->parent;
	}
}

/** @implements ChildNodeInterface<Block2> */
final class Row2 implements ChildNodeInterface
{
	/** @var Block2 $parent */
	private $parent;

	public function getParent(): Block2
	{
		return $this->parent;
	}
}
