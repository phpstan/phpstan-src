<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Dependency\ExportedNode;
use function usort;

class AnalyserResult
{

	/** @var Error[] */
	private array $unorderedErrors;

	/** @var Error[] */
	private array $errors;

	/** @var string[] */
	private array $internalErrors;

	/** @var array<string, array<string>>|null */
	private ?array $dependencies;

	/** @var array<string, array<ExportedNode>> */
	private array $exportedNodes;

	private bool $reachedInternalErrorsCountLimit;

	/**
	 * @param Error[] $errors
	 * @param string[] $internalErrors
	 * @param array<string, array<string>>|null $dependencies
	 * @param array<string, array<ExportedNode>> $exportedNodes
	 */
	public function __construct(
		array $errors,
		array $internalErrors,
		?array $dependencies,
		array $exportedNodes,
		bool $reachedInternalErrorsCountLimit
	)
	{
		$this->unorderedErrors = $errors;

		usort(
			$errors,
			static function (Error $a, Error $b): int {
				return [
					$a->getFile(),
					$a->getLine(),
					$a->getMessage(),
				] <=> [
					$b->getFile(),
					$b->getLine(),
					$b->getMessage(),
				];
			}
		);

		$this->errors = $errors;
		$this->internalErrors = $internalErrors;
		$this->dependencies = $dependencies;
		$this->exportedNodes = $exportedNodes;
		$this->reachedInternalErrorsCountLimit = $reachedInternalErrorsCountLimit;
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
