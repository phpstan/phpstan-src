<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

class FileAnalyserResult
{

	/** @var Error[] */
	private array $errors;

	/** @var array<int, string> */
	private array $dependencies;

	/**
	 * @param Error[] $errors
	 * @param array<int, string> $dependencies
	 */
	public function __construct(array $errors, array $dependencies)
	{
		$this->errors = $errors;
		$this->dependencies = $dependencies;
	}

	/**
	 * @return Error[]
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * @return array<int, string>
	 */
	public function getDependencies(): array
	{
		return $this->dependencies;
	}

}
