<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\FuncCall>
 */
class ImplodeFunctionRule implements \PHPStan\Rules\Rule
{

	private RuleLevelHelper $ruleLevelHelper;

	private ReflectionProvider $reflectionProvider;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		RuleLevelHelper $ruleLevelHelper
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof \PhpParser\Node\Name)) {
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
			static function (Type $type): bool {
				return !$type->getIterableValueType()->toString() instanceof ErrorType;
			},
			false
		);

		if ($typeResult->getType() instanceof ErrorType
			|| !$typeResult->getType()->getIterableValueType()->toString() instanceof ErrorType) {
			return [];
		}

		return [
			RuleErrorBuilder::message(
				sprintf('Parameter #%d $array of function %s expects array<string>, %s given.', $paramNo, $functionName, $typeResult->getType()->describe(VerbosityLevel::typeOnly()))
			)->build(),
		];
	}

}
