<?php declare(strict_types = 1);

namespace Bug6485;

interface Block {}

final class BlockStateSerializer
{

	/**
	 * @var array<string, \Closure(never): mixed>
	 */
	private array $serializers = [];

	/**
	 * @template TBlockType of Block
	 * @param TBlockType $block
	 */
	public function serialize(Block $block): mixed
	{
		$class = get_class($block);
		$serializer = $this->serializers[$class] ?? null;

		if ($serializer === null){
			throw new \InvalidArgumentException();
		}

		return $serializer($block);
	}

}
