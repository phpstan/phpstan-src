<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Collector;

/**
 * @api
 * @phpstan-import-type CollectorData from CollectedData
 */
final class CollectedDataNode extends NodeAbstract implements VirtualNode
{

	/**
	 * @param CollectorData $collectedData
	 */
	public function __construct(private array $collectedData, private bool $onlyFiles)
	{
		parent::__construct([]);
	}

	/**
	 * @template TCollector of Collector<Node, TValue>
	 * @template TValue
	 * @param class-string<TCollector> $collectorType
	 * @return array<string, list<TValue>>
	 */
	public function get(string $collectorType): array
	{
		$result = [];
		foreach ($this->collectedData as $filePath => $collectedDataPerCollector) {
			if (!isset($collectedDataPerCollector[$collectorType])) {
				continue;
			}

			foreach ($collectedDataPerCollector[$collectorType] as $rawData) {
				$result[$filePath][] = $rawData;
			}
		}

		return $result;
	}

	/**
	 * Indicates that only files were passed to the analyser, not directory paths.
	 *
	 * True being returned strongly suggests that it's a partial analysis, not full project analysis.
	 */
	public function isOnlyFilesAnalysis(): bool
	{
		return $this->onlyFiles;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_CollectedDataNode';
	}

	/**
	 * @return array{}
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
