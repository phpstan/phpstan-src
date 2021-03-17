<?php

namespace Bug4707Two;

/**
 * @template TParent of ParentNodeInterface
 */
interface ChildNodeInterface
{}

interface ParentNodeInterface
{
	/** @return ChildNodeInterface<Block> */
	public function getChildren();
}

/** @implements ChildNodeInterface<Block> */
final class Row implements ChildNodeInterface {}

final class Block implements ParentNodeInterface
{
	/** @return Row */
	public function getChildren() {
		return new Row();
	}
}
