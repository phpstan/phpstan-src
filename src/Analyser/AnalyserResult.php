<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Collectors\CollectedData;
use PHPStan\Dependency\ExportedNode;
use function usort;

class AnalyserResult
{

	/** @var Error[] */
	private array $unorderedErrors;

	/**
	 * @param Error[] $errors
	 * @param CollectedData[] $collectedData
	 * @param string[] $internalErrors
	 * @param array<string, array<string>>|null $dependencies
	 * @param array<string, array<ExportedNode>> $exportedNodes
	 */
	public function __construct(
		private array $errors,
		private array $internalErrors,
		private array $collectedData,
		private ?array $dependencies,
		private array $exportedNodes,
		private bool $reachedInternalErrorsCountLimit,
	)
	{
		$this->unorderedErrors = $errors;

		usort(
			$this->errors,
			static fn (Error $a, Error $b): int => [
				$a->getFile(),
				$a->getLine(),
				$a->getMessage(),
			] <=> [
				$b->getFile(),
				$b->getLine(),
				$b->getMessage(),
			],
		);
	}

	/**
	 * @return Error[]
	 */
	public function getUnorderedErrors(): array
	{
		return $this->unorderedErrors;
	}

	/**
	 * @return Error[]
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * @return string[]
	 */
	public function getInternalErrors(): array
	{
		return $this->internalErrors;
	}

	/**
	 * @return CollectedData[]
	 */
	public function getCollectedData(): array
	{
		return $this->collectedData;
	}

	/**
	 * @return array<string, array<string>>|null
	 */
	public function getDependencies(): ?array
	{
		return $this->dependencies;
	}

	/**
	 * @return array<string, array<ExportedNode>>
	 */
	public function getExportedNodes(): array
	{
		return $this->exportedNodes;
	}

	public function hasReachedInternalErrorsCountLimit(): bool
	{
		return $this->reachedInternalErrorsCountLimit;
	}

}
