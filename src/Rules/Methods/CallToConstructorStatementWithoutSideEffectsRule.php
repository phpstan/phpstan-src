<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\NoopExpressionNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NeverType;
use function sprintf;

/**
 * @implements Rule<NoopExpressionNode>
 */
#[RegisteredRule(level: 4)]
final class CallToConstructorStatementWithoutSideEffectsRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return NoopExpressionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$instantiation = $node->getOriginalExpr();
		if (!$instantiation instanceof Node\Expr\New_) {
			return [];
		}

		if (!$instantiation->class instanceof Node\Name) {
			return [];
		}

		$className = $scope->resolveName($instantiation->class);
		if (!$this->reflectionProvider->hasClass($className)) {
			return [];
		}

		$classReflection = $this->reflectionProvider->getClass($className);
		if (!$classReflection->hasConstructor()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Call to new %s() on a separate line has no effect.',
					$classReflection->getDisplayName(),
				))->identifier('new.resultUnused')->build(),
			];
		}

		$constructor = $classReflection->getConstructor();
		$methodResult = $scope->getType($instantiation);
		if ($methodResult instanceof NeverType && $methodResult->isExplicit()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Call to %s::%s() on a separate line has no effect.',
				$classReflection->getDisplayName(),
				$constructor->getName(),
			))->identifier('new.resultUnused')->build(),
		];
	}

}
