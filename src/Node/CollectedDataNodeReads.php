<?php declare(strict_types = 1);

namespace PHPStan\Node;

use function array_keys;

/**
 * What a rule asked from a CollectedDataNode while it ran. The result cache reuses the rule's errors
 * on the next run when none of it changed.
 */
final class CollectedDataNodeReads
{

	/** @var array<string, true> */
	private array $collectorTypes = [];

	private bool $onlyFilesRead = false;

	public function addCollectorType(string $collectorType): void
	{
		$this->collectorTypes[$collectorType] = true;
	}

	public function addOnlyFiles(): void
	{
		$this->onlyFilesRead = true;
	}

	/**
	 * @return list<string>
	 */
	public function getCollectorTypes(): array
	{
		return array_keys($this->collectorTypes);
	}

	public function isOnlyFilesRead(): bool
	{
		return $this->onlyFilesRead;
	}

}
