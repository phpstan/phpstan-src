<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function count;
use function in_array;
use function sprintf;

/**
 * @deprecated Replaced by PHPStan\Rules\Functions\ImplodeParameterCastableToStringRuleTest
 * @implements Rule<Node\Expr\FuncCall>
 */
final class ImplodeFunctionRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private RuleLevelHelper $ruleLevelHelper,
		private bool $disabled,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->disabled) {
			return [];
		}

		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		$functionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
		if (!in_array($functionName, ['implode', 'join'], true)) {
			return [];
		}

		$args = $node->getArgs();
		if (count($args) === 1) {
			$arrayArg = $args[0]->value;
			$paramNo = 1;
		} elseif (count($args) === 2) {
			$arrayArg = $args[1]->value;
			$paramNo = 2;
		} else {
			return [];
		}

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$arrayArg,
			'',
			static fn (Type $type): bool => !$type->getIterableValueType()->toString() instanceof ErrorType,
		);

		if ($typeResult->getType() instanceof ErrorType
			|| !$typeResult->getType()->getIterableValueType()->toString() instanceof ErrorType) {
			return [];
		}

		return [
			RuleErrorBuilder::message(
				sprintf('Parameter #%d $array of function %s expects array<string>, %s given.', $paramNo, $functionName, $typeResult->getType()->describe(VerbosityLevel::typeOnly())),
			)->identifier('argument.type')->build(),
		];
	}

}
