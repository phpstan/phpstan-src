<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Arg;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

class ParameterCastableToStringCheck
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	/** @param callable(Type): Type $castFn */
	public function checkParameter(
		Arg $parameter,
		Scope $scope,
		string $errorMessageTemplate,
		callable $castFn,
		string $functionName,
		string $parameterName,
	): ?IdentifierRuleError
	{
		if ($parameter->unpack) {
			return null;
		}

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$parameter->value,
			'',
			static fn (Type $type): bool => !$castFn($type->getIterableValueType()) instanceof ErrorType,
		);

		if ($typeResult->getType() instanceof ErrorType
			|| !$castFn($typeResult->getType()->getIterableValueType()) instanceof ErrorType) {
			return null;
		}

		return RuleErrorBuilder::message(
			sprintf($errorMessageTemplate, $parameterName, $functionName, $typeResult->getType()->describe(VerbosityLevel::typeOnly())),
		)->identifier('argument.type')->build();
	}

}
