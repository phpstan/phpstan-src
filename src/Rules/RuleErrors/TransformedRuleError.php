<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Analyser\Error;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\ShouldNotHappenException;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class TransformedRuleError implements IdentifierRuleError
{

	public function __construct(private Error $error)
	{
	}

	public function getError(): Error
	{
		return $this->error;
	}

	public function getIdentifier(): string
	{
		$identifier = $this->error->getIdentifier();
		if ($identifier === null) {
			throw new ShouldNotHappenException();
		}

		return $identifier;
	}

	public function getMessage(): string
	{
		return $this->error->getMessage();
	}

}
