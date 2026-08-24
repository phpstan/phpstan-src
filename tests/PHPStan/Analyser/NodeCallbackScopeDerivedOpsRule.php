<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function count;
use function is_string;
use function sprintf;

/**
 * Exercises scope-deriving mutators (assignExpression(), assignVariable())
 * on the scope a rule received, the way third-party rules do: deriving a
 * locally modified scope and expecting getType() on it to see the
 * modification — including combined with filterByTruthyValue() and after an
 * earlier getType() call already answered from the unmodified scope.
 *
 * @implements Rule<FuncCall>
 */
class NodeCallbackScopeDerivedOpsRule implements Rule
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

		if (!$scope instanceof MutatingScope) {
			throw new LogicException('Expected MutatingScope');
		}

		$functionName = $node->name->getLast();
		$args = $node->getArgs();
		if (count($args) === 0) {
			return [];
		}

		$var = $args[count($args) - 1]->value;
		if (!$var instanceof Variable || !is_string($var->name)) {
			return [];
		}

		$assignedType = new ConstantStringType('assigned');

		if ($functionName === 'probeAssignExpression') {
			$assignedScope = $scope->assignExpression($var, $assignedType, $assignedType);

			return [$this->describe('assigned', $assignedScope->getType($var))];
		}

		if ($functionName === 'probeAssignExpressionAfterRead') {
			$before = $scope->getType($var);
			$assignedScope = $scope->assignExpression($var, $assignedType, $assignedType);

			return [$this->describe(sprintf(
				'before %s, after',
				$before->describe(VerbosityLevel::precise()),
			), $assignedScope->getType($var))];
		}

		if ($functionName === 'probeAssignVariable') {
			$assignedScope = $scope->assignVariable($var->name, $assignedType, $assignedType, TrinaryLogic::createYes());

			return [$this->describe('assigned', $assignedScope->getType($var))];
		}

		if ($functionName === 'probeFilterThenAssign') {
			if (count($args) < 2) {
				return [];
			}

			$filteredScope = $scope->filterByTruthyValue($args[0]->value);
			$filteredThenAssignedScope = $filteredScope->assignExpression($var, $assignedType, $assignedType);

			return [$this->describe(sprintf(
				'filtered %s, assigned',
				$filteredScope->getType($var)->describe(VerbosityLevel::precise()),
			), $filteredThenAssignedScope->getType($var))];
		}

		return [];
	}

	private function describe(string $prefix, Type $type): IdentifierRuleError
	{
		return RuleErrorBuilder::message(sprintf(
			'%s: %s',
			$prefix,
			$type->describe(VerbosityLevel::precise()),
		))->identifier('tests.nodeCallbackScopeDerivedOps')->build();
	}

}
