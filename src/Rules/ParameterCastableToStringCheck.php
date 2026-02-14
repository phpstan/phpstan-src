<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Arg;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

#[AutowiredService]
final class ParameterCastableToStringCheck
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

		$arrayTypeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$parameter->value,
			'',
			static fn (Type $type): bool => $type->isArray()->yes(),
		);

		$arrayType = $arrayTypeResult->getType();
		if (!$arrayType->isArray()->yes()) {
			return null;
		}

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			new TypeExpr($arrayType->getIterableValueType()),
			'',
			static fn (Type $type): bool => !$castFn($type) instanceof ErrorType,
		);

		if (!$castFn($typeResult->getType()) instanceof ErrorType) {
			return null;
		}

		return RuleErrorBuilder::message(
			sprintf($errorMessageTemplate, $parameterName, $functionName, $arrayTypeResult->getType()->describe(VerbosityLevel::typeOnly())),
		)->identifier('argument.type')->build();
	}

	public function getParameterName(Arg $parameter, int $parameterIdx, ?ParameterReflection $parameterReflection): string
	{
		if ($parameterReflection === null) {
			return sprintf('#%d', $parameterIdx + 1);
		}

		$paramName = $parameterReflection->getName();
		$origParameter = $parameter->getAttributes()[ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE] ?? null;

		if (!$origParameter instanceof Arg) {
			$origParameter = $parameter;
		}

		return $origParameter->name !== null
			? sprintf('$%s', $paramName)
			: sprintf('#%d $%s', $parameterIdx + 1, $paramName);
	}

}
