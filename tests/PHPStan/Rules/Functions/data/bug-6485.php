<?php declare(strict_types = 1);

namespace Bug6485;

class CompoundTag{}

class Block{
	public function getTypeId() : int{ return 0; }
}

final class BlockStateSerializerR13{

	/**
	 * These callables actually accept Block, but for the sake of type completeness, it has to be never, since we can't
	 * describe the bottom type of a type hierarchy only containing Block.
	 * @phpstan-var array<string, array<int, \Closure(never) : CompoundTag>>
	 */
	private array $serializers = [];

	/**
	 * @phpstan-template TBlockType of Block
	 * @phpstan-param TBlockType $block
	 */
	public function serialize(Block $block) : CompoundTag{
		$class = get_class($block);
		$serializer = $this->serializers[$class][$block->getTypeId()] ?? null;

		if($serializer === null){
			//TODO: use a proper exception type for this
			throw new \InvalidArgumentException("No serializer registered for this block (this is probably a plugin bug)");
		}

		return $serializer($block);
	}
}
