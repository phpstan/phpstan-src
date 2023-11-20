<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NeverType;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Expression>
 */
class CallToConstructorStatementWithoutSideEffectsRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Expression::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->expr instanceof Node\Expr\New_) {
			return [];
		}

		$instantiation = $node->expr;
		if (!$instantiation->class instanceof Node\Name) {
			return [];
		}

		$className = $scope->resolveName($instantiation->class);
		if (!$this->reflectionProvider->hasClass($className)) {
			return [];
		}

		$classReflection = $this->reflectionProvider->getClass($className);
		if (!$classReflection->hasConstructor()) {
			return [];
		}

		$constructor = $classReflection->getConstructor();
		if ($constructor->hasSideEffects()->no()) {
			$throwsType = $constructor->getThrowType();
			if ($throwsType !== null && !$throwsType->isVoid()->yes()) {
				return [];
			}

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

		return [];
	}

}
