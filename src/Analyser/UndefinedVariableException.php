<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\AnalysedCodeException;
use function sprintf;

final class UndefinedVariableException extends AnalysedCodeException
{

	public function __construct(private Scope $scope, private string $variableName)
	{
		parent::__construct(sprintf('Undefined variable: $%s', $variableName));
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
