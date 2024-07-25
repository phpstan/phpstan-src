<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Collector;
use function array_key_exists;

/**
 * @api
 * @final
 */
class CollectedDataNode extends NodeAbstract
{

	/**
	 * @param CollectedData[] $collectedData
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
		foreach ($this->collectedData as $collectedData) {
			if ($collectedData->getCollectorType() !== $collectorType) {
				continue;
			}

			$filePath = $collectedData->getFilePath();
			if (!array_key_exists($filePath, $result)) {
				$result[$filePath] = [];
			}

			$result[$filePath][] = $collectedData->getData();
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
