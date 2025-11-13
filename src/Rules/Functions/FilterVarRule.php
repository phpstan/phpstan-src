<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Php\FilterFunctionReturnTypeHelper;
use function count;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
#[RegisteredRule(level: 0)]
final class FilterVarRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		if ($this->reflectionProvider->resolveFunctionName($node->name, $scope) !== 'filter_var') {
			return [];
		}

		$args = $node->getArgs();

		if ($this->reflectionProvider->hasConstant(new Name\FullyQualified('FILTER_THROW_ON_FAILURE'), null)) {
			if (count($args) < 3) {
				return [];
			}

			$flagsType = $scope->getType($args[2]->value);

			if ($this->filterFunctionReturnTypeHelper->hasFlag('FILTER_NULL_ON_FAILURE', $flagsType)
				->and($this->filterFunctionReturnTypeHelper->hasFlag('FILTER_THROW_ON_FAILURE', $flagsType))
				->yes()
			) {
				return [
					RuleErrorBuilder::message('Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.')
						->identifier('filterVar.nullOnFailureAndThrowOnFailure')
						->build(),
				];
			}
		}

		return [];
	}

}
