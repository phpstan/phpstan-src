<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

class AnalyserResult
{

	/** @var string[]|\PHPStan\Analyser\Error[] */
	private $errors;

	/** @var bool */
	private $hasInferrablePropertyTypesFromConstructor;

	/** @var array<string, array<string>>|null */
	private $dependencies;

	/**
	 * @param string[]|\PHPStan\Analyser\Error[] $errors
	 * @param bool $hasInferrablePropertyTypesFromConstructor
	 * @param array<string, array<string>>|null $dependencies
	 */
	public function __construct(
		array $errors,
		bool $hasInferrablePropertyTypesFromConstructor,
		?array $dependencies
	)
	{
		$this->errors = $errors;
		$this->hasInferrablePropertyTypesFromConstructor = $hasInferrablePropertyTypesFromConstructor;
		$this->dependencies = $dependencies;
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

	/**
	 * @return array<string, array<string>>|null
	 */
	public function getDependencies(): ?array
	{
		return $this->dependencies;
	}

}
