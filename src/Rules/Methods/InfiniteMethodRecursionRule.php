<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\InfiniteRecursionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
#[RegisteredRule(level: 4)]
final class InfiniteMethodRecursionRule implements Rule
{

	public function __construct(private InfiniteRecursionFinder $finder)
	{
	}

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->isGenerator()) {
			return [];
		}

		$method = $node->getMethodReflection();
		$methodName = $method->getName();
		$className = $node->getClassReflection()->getName();
		$isStatic = $method->isStatic();
		$isConstructor = strtolower($methodName) === '__construct';

		$isSelfCall = static function (Expr $expr) use ($methodName, $className, $isStatic, $isConstructor): bool {
			// A constructor that unconditionally instantiates its own class
			// re-enters itself, which is also infinite recursion.
			if ($expr instanceof New_) {
				return $isConstructor
					&& $expr->class instanceof Name
					&& in_array(strtolower($expr->class->toString()), ['self', 'static', strtolower($className)], true);
			}

			if ($expr instanceof MethodCall) {
				return !$isStatic
					&& !$expr->isFirstClassCallable()
					&& $expr->var instanceof Variable
					&& $expr->var->name === 'this'
					&& $expr->name instanceof Identifier
					&& strtolower($expr->name->name) === strtolower($methodName);
			}

			if ($expr instanceof StaticCall) {
				return $isStatic
					&& !$expr->isFirstClassCallable()
					&& $expr->class instanceof Name
					&& in_array(strtolower($expr->class->toString()), ['self', 'static', strtolower($className)], true)
					&& $expr->name instanceof Identifier
					&& strtolower($expr->name->name) === strtolower($methodName);
			}

			return false;
		};

		$call = $this->finder->find($node->getStatements(), $isSelfCall);
		if ($call === null) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Method %s::%s() calls itself on every code path, leading to infinite recursion.',
				$node->getClassReflection()->getDisplayName(),
				$methodName,
			))
				->identifier('method.infiniteRecursion')
				->line($call->getStartLine())
				->build(),
		];
	}

}
