<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function count;
use function is_string;
use function sprintf;

/**
 * Exercises the rule-facing Scope::filterByTruthyValue()/filterByFalseyValue()
 * API the way a third-party rule would: filtering the scope it received and
 * reading state directly off the filtered scope.
 *
 * @implements Rule<FuncCall>
 */
class NodeCallbackScopeFilterByValueRule implements Rule
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name) {
			return [];
		}

		$functionName = $node->name->getLast();
		$args = $node->getArgs();

		if ($functionName === 'probeFilter') {
			if (count($args) < 2) {
				return [];
			}
			$var = $args[1]->value;
			if (!$var instanceof Variable || !is_string($var->name)) {
				return [];
			}

			$truthyType = $scope->filterByTruthyValue($args[0]->value)->getVariableType($var->name);
			$falseyType = $scope->filterByFalseyValue($args[0]->value)->getVariableType($var->name);

			return [
				RuleErrorBuilder::message(sprintf(
					'truthy: %s, falsey: %s',
					$truthyType->describe(VerbosityLevel::precise()),
					$falseyType->describe(VerbosityLevel::precise()),
				))->identifier('tests.nodeCallbackScopeFilter')->build(),
			];
		}

		if ($functionName === 'probeChainedFilter') {
			if (count($args) < 3) {
				return [];
			}

			$chainedType = $scope->filterByTruthyValue($args[0]->value)
				->filterByFalseyValue($args[1]->value)
				->getType($args[2]->value);

			return [
				RuleErrorBuilder::message(sprintf(
					'chained: %s',
					$chainedType->describe(VerbosityLevel::precise()),
				))->identifier('tests.nodeCallbackScopeFilter')->build(),
			];
		}

		return [];
	}

}
