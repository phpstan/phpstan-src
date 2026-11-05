<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ResultCache\LazyCollectedData;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Collector;
use function is_array;

/**
 * @api
 * @phpstan-import-type CollectorData from CollectedData
 */
final class CollectedDataNode extends NodeAbstract implements VirtualNode
{

	private LazyCollectedData $collectedData;

	/**
	 * @param CollectorData|LazyCollectedData $collectedData
	 */
	public function __construct(array|LazyCollectedData $collectedData, private bool $onlyFiles, private ?CollectedDataNodeReads $reads = null)
	{
		parent::__construct([]);
		$this->collectedData = is_array($collectedData) ? LazyCollectedData::fromArray($collectedData) : $collectedData;
	}

	/**
	 * @template TCollector of Collector<Node, TValue>
	 * @template TValue
	 * @param class-string<TCollector> $collectorType
	 * @return array<string, list<TValue>>
	 */
	public function get(string $collectorType): array
	{
		if ($this->reads !== null) {
			$this->reads->addCollectorType($collectorType);
		}

		$result = [];
		foreach ($this->collectedData->iterate($collectorType) as $filePath => $collectedValues) {
			foreach ($collectedValues as $rawData) {
				$result[$filePath][] = $rawData;
			}
		}

		return $result;
	}

	/**
	 * Like get(), but yields the data of one analysed file at a time instead of returning all of it.
	 * On a large project, a rule that processes each file's data and keeps only what it derives from
	 * it does not hold the whole collected data in memory.
	 *
	 * Every call reads the data again.
	 *
	 * @template TCollector of Collector<Node, TValue>
	 * @template TValue
	 * @param class-string<TCollector> $collectorType
	 * @return iterable<string, list<TValue>>
	 */
	public function iterate(string $collectorType): iterable
	{
		if ($this->reads !== null) {
			$this->reads->addCollectorType($collectorType);
		}

		return $this->collectedData->iterate($collectorType);
	}

	/**
	 * Indicates that only files were passed to the analyser, not directory paths.
	 *
	 * True being returned strongly suggests that it's a partial analysis, not full project analysis.
	 */
	public function isOnlyFilesAnalysis(): bool
	{
		if ($this->reads !== null) {
			$this->reads->addOnlyFiles();
		}

		return $this->onlyFiles;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_CollectedDataNode';
	}

	/**
	 * @return array{}
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

}
