<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\AnalysedCodeException;
use function sprintf;

class UndefinedVariableException extends AnalysedCodeException
{

	private Scope $scope;

	private string $variableName;

	public function __construct(Scope $scope, string $variableName)
	{
		parent::__construct(sprintf('Undefined variable: $%s', $variableName));
		$this->scope = $scope;
		$this->variableName = $variableName;
	}

	public function getScope(): Scope
	{
		return $this->scope;
	}

	public function getVariableName(): string
	{
		return $this->variableName;
	}

	public function getTip(): ?string
	{
		return null;
	}

}
