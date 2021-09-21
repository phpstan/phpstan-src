<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\UnionType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\FuncCall>
 */
class ImplodeFunctionRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Reflection\ReflectionProvider $reflectionProvider;

	public function __construct(
		ReflectionProvider $reflectionProvider
	)
	{
		$this->reflectionProvider = $reflectionProvider;
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
			$arrayType = $scope->getType($args[0]->value);
		} elseif (count($args) === 2) {
			$arrayType = $scope->getType($args[1]->value);
		} else {
			return [];
		}

		if ($arrayType->getIterableValueType()->isArray()->yes()) {
			return [
				RuleErrorBuilder::message(
					sprintf('Call to %s with invalid nested array argument.', $functionName)
				)->build(),
			];
		}
		if ($arrayType->getIterableValueType() instanceof UnionType) {
			foreach ($arrayType->getIterableValueType()->getTypes() as $subType) {
				if ($subType->isArray()->yes()) {
					return [
						RuleErrorBuilder::message(
							sprintf('Call to %s with invalid nested array argument in union type.', $functionName)
						)->build(),
					];
				}
			}
		}

		return [];
	}

}
