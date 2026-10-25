<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

/**
 * The errors of one rule on CollectedDataNode, with what the rule asked from the node to get them:
 * a hash of the data of every collector it asked for, and the value of isOnlyFilesAnalysis() if it
 * asked for that. It also keeps the values the rule declared through DependencyTracker, see
 * ValueDependencyCollector. While all of it is the same, the next run reuses the errors instead of
 * running the rule again.
 */
final class CollectedDataRuleResult
{

	/**
	 * @param array<string, string> $collectorHashes collector type => hash of its data
	 * @param list<Error> $errors before the ignore extensions and the local ignores
	 * @param list<array{string, string, string}> $valueDependencies [extension class, key as the extension stores it, value]
	 */
	public function __construct(
		private array $collectorHashes,
		private ?bool $onlyFiles,
		private array $errors,
		private array $valueDependencies = [],
	)
	{
	}

	/**
	 * @return array<string, string>
	 */
	public function getCollectorHashes(): array
	{
		return $this->collectorHashes;
	}

	/**
	 * Null when the rule did not ask.
	 */
	public function getOnlyFiles(): ?bool
	{
		return $this->onlyFiles;
	}

	/**
	 * @return list<Error>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * @return list<array{string, string, string}>
	 */
	public function getValueDependencies(): array
	{
		return $this->valueDependencies;
	}

	/**
	 * @param callable(string): string $transform
	 */
	public function transformPaths(callable $transform): self
	{
		$errors = [];
		foreach ($this->errors as $error) {
			$errors[] = $error->transformPaths($transform);
		}

		return new self($this->collectorHashes, $this->onlyFiles, $errors, $this->valueDependencies);
	}

}
