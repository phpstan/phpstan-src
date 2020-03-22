<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

class AnalyserResult
{

	/** @var \PHPStan\Analyser\Error[] */
	private $unorderedErrors;

	/** @var \PHPStan\Analyser\Error[] */
	private $errors;

	/** @var string[] */
	private $internalErrors;

	/** @var bool */
	private $hasInferrablePropertyTypesFromConstructor;

	/** @var array<string, array<string>>|null */
	private $dependencies;

	/** @var bool */
	private $reachedInternalErrorsCountLimit;

	/**
	 * @param \PHPStan\Analyser\Error[] $errors
	 * @param string[] $internalErrors
	 * @param bool $hasInferrablePropertyTypesFromConstructor
	 * @param array<string, array<string>>|null $dependencies
	 * @param bool $reachedInternalErrorsCountLimit
	 */
	public function __construct(
		array $errors,
		array $internalErrors,
		bool $hasInferrablePropertyTypesFromConstructor,
		?array $dependencies,
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
		$this->hasInferrablePropertyTypesFromConstructor = $hasInferrablePropertyTypesFromConstructor;
		$this->dependencies = $dependencies;
		$this->reachedInternalErrorsCountLimit = $reachedInternalErrorsCountLimit;
	}

	/**
	 * @return \PHPStan\Analyser\Error[]
	 */
	public function getUnorderedErrors(): array
	{
		return $this->unorderedErrors;
	}

	/**
	 * @return \PHPStan\Analyser\Error[]
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

	public function hasInferrablePropertyTypesFromConstructor(): bool
	{
		return $this->hasInferrablePropertyTypesFromConstructor;
	}

	/**
	 * @return array<string, array<string>>|null
	 */
	public function getDependencies(): ?array
	{
		return $this->dependencies;
	}

	public function hasReachedInternalErrorsCountLimit(): bool
	{
		return $this->reachedInternalErrorsCountLimit;
	}

}
