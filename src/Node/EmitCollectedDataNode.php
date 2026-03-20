<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Collectors\Collector;

/**
 * @template TNodeType of Node
 * @template TValue
 */
final class EmitCollectedDataNode extends NodeAbstract implements VirtualNode
{

	/**
	 * @param class-string<Collector<TNodeType, TValue>> $collectorType
	 * @param TValue $data
	 */
	public function __construct(
		private string $collectorType,
		private mixed $data,
	)
	{
		parent::__construct([]);
	}

	/**
	 * @return class-string<Collector<TNodeType, TValue>>
	 */
	public function getCollectorType(): string
	{
		return $this->collectorType;
	}

	/**
	 * @return TValue
	 */
	public function getData(): mixed
	{
		return $this->data;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_EmitCollectedDataNode';
	}

	/**
	 * @return list<string>
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

}
