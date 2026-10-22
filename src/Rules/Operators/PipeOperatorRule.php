<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use function sprintf;

/**
 * @implements Rule<Node\Expr\BinaryOp\Pipe>
 */
#[RegisteredRule(level: 0)]
final class PipeOperatorRule implements Rule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\BinaryOp\Pipe::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$rightType = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->right,
			'',
			static fn (Type $type) => $type->isCallable()->yes(),
		)->getType();
		if ($rightType instanceof ErrorType) {
			return [];
		}
		if (!$rightType->isCallable()->yes()) {
			return [];
		}

		$acceptors = $rightType->getCallableParametersAcceptors($scope);
		foreach ($acceptors as $acceptor) {
			foreach ($acceptor->getParameters() as $parameter) {
				if ($parameter->passedByReference()->no()) {
					break;
				}

				return [
					RuleErrorBuilder::message(sprintf(
						'Parameter #1%s of callable on the right side of pipe operator is passed by reference.',
						$parameter->getName() !== '' ? ' $' . $parameter->getName() : '',
					))
						->line($node->right->getStartLine())
						->identifier('pipe.byRef')
						->build(),
				];
			}
		}

		return [];
	}

}
