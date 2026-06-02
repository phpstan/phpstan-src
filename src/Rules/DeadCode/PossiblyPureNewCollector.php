<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\RegisteredCollector;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use function count;
use function strtolower;

/**
 * @implements Collector<Expression, array{string, int, bool}>
 */
#[RegisteredCollector(level: 4)]
final class PossiblyPureNewCollector implements Collector
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private EmptyBodyCallableDetector $emptyBodyCallableDetector,
	)
	{
	}

	public function getNodeType(): string
	{
		return Expression::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		if (!$node->expr instanceof Node\Expr\New_) {
			return null;
		}

		if (!$node->expr->class instanceof Node\Name) {
			return null;
		}

		$className = $node->expr->class->toString();

		if (!$this->reflectionProvider->hasClass($className)) {
			return null;
		}

		$classReflection = $this->reflectionProvider->getClass($className);
		if (!$classReflection->hasConstructor()) {
			return null;
		}

		$constructor = $classReflection->getConstructor();
		if (strtolower($constructor->getName()) !== '__construct') {
			return null;
		}

		if (!$constructor->isPure()->maybe()) {
			return null;
		}

		return [
			$constructor->getDeclaringClass()->getName(),
			$node->getStartLine(),
			$this->hasEmptyConstructorBody($constructor),
		];
	}

	private function hasEmptyConstructorBody(ExtendedMethodReflection $constructor): bool
	{
		if (count($constructor->getAsserts()->getAll()) !== 0) {
			return false;
		}

		$declaringClass = $constructor->getDeclaringClass();
		// built-in classes are reflected from stubs with empty bodies that don't reflect reality
		if ($declaringClass->isBuiltin()) {
			return false;
		}

		return $this->emptyBodyCallableDetector->hasEmptyMethodBody(
			$declaringClass->getFileName(),
			$declaringClass->getName(),
			$constructor->getName(),
		);
	}

}
