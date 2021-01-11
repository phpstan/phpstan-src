<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VoidType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Expression>
 */
class CallToConstructorStatementWithoutSideEffectsRule implements Rule
{

	private ReflectionProvider $reflectionProvider;

	public function __construct(ReflectionProvider $reflectionProvider)
	{
		$this->reflectionProvider = $reflectionProvider;
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
			if ($throwsType !== null && !$throwsType instanceof VoidType) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'Call to %s::%s() on a separate line has no effect.',
					$classReflection->getDisplayName(),
					$constructor->getName()
				))->build(),
			];
		}

		return [];
	}

}
