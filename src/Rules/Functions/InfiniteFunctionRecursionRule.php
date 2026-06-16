<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\InfiniteRecursionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<FunctionReturnStatementsNode>
 */
#[RegisteredRule(level: 4)]
final class InfiniteFunctionRecursionRule implements Rule
{

	public function __construct(
		private InfiniteRecursionFinder $finder,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return FunctionReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->isGenerator()) {
			return [];
		}

		$function = $node->getFunctionReflection();
		$functionName = strtolower($function->getName());
		$reflectionProvider = $this->reflectionProvider;

		$isSelfCall = static function (Expr $expr) use ($functionName, $reflectionProvider, $scope): bool {
			if (!$expr instanceof FuncCall) {
				return false;
			}
			if ($expr->isFirstClassCallable()) {
				return false;
			}
			if (!$expr->name instanceof Name) {
				return false;
			}
			if (!$reflectionProvider->hasFunction($expr->name, $scope)) {
				return false;
			}

			return strtolower($reflectionProvider->getFunction($expr->name, $scope)->getName()) === $functionName;
		};

		$call = $this->finder->find($node->getStatements(), $isSelfCall);
		if ($call === null) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Function %s() calls itself on every code path, leading to infinite recursion.',
				$function->getName(),
			))
				->identifier('function.infiniteRecursion')
				->line($call->getStartLine())
				->build(),
		];
	}

}
