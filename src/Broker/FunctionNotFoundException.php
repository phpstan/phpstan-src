<?php declare(strict_types = 1);

namespace PHPStan\Broker;

use PHPStan\AnalysedCodeException;
use function sprintf;

final class FunctionNotFoundException extends AnalysedCodeException
{

	public function __construct(private string $functionName)
	{
		parent::__construct(sprintf('Function %s not found while trying to analyse it - discovering symbols is probably not configured properly.', $functionName));
	}

	public function getFunctionName(): string
	{
		return $this->functionName;
	}

	public function getTip(): string
	{
		return 'Learn more at https://phpstan.org/user-guide/discovering-symbols';
	}

}
