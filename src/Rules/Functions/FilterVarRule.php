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
use PHPStan\Type\Php\FilterFunctionFlagsHelper;
use PHPStan\Type\Php\FilterFunctionReturnTypeHelper;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
#[RegisteredRule(level: 0)]
final class FilterVarRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper,
		private FilterFunctionFlagsHelper $filterFunctionFlagsHelper,
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

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		if (!$this->filterFunctionFlagsHelper->isSupported($functionReflection)) {
			return [];
		}

		if (
			$scope->getPhpVersion()->hasFilterThrowOnFailureConstant()->no()
			|| !$this->reflectionProvider->hasConstant(new Name\FullyQualified('FILTER_THROW_ON_FAILURE'), null)
		) {
			return [];
		}

		foreach ($this->filterFunctionFlagsHelper->getFlagsTypes($functionReflection, $node, $scope) as $flagsType) {
			if (!$this->filterFunctionReturnTypeHelper->hasFlag('FILTER_NULL_ON_FAILURE', $flagsType)
				->and($this->filterFunctionReturnTypeHelper->hasFlag('FILTER_THROW_ON_FAILURE', $flagsType))
				->yes()
			) {
				continue;
			}

			return [
				RuleErrorBuilder::message('Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.')
					->identifier('filterVar.nullOnFailureAndThrowOnFailure')
					->build(),
			];
		}

		return [];
	}

}
