<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

class AnalyserResult
{

	/** @var string[]|\PHPStan\Analyser\Error[] */
	private $errors;

	/** @var bool */
	private $hasInferrablePropertyTypesFromConstructor;

	/**
	 * @param string[]|\PHPStan\Analyser\Error[] $errors
	 * @param bool $hasInferrablePropertyTypesFromConstructor
	 */
	public function __construct(
		array $errors,
		bool $hasInferrablePropertyTypesFromConstructor
	)
	{
		$this->errors = $errors;
		$this->hasInferrablePropertyTypesFromConstructor = $hasInferrablePropertyTypesFromConstructor;
	}

	/**
	 * @return string[]|\PHPStan\Analyser\Error[]
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	public function hasInferrablePropertyTypesFromConstructor(): bool
	{
		return $this->hasInferrablePropertyTypesFromConstructor;
	}

}
