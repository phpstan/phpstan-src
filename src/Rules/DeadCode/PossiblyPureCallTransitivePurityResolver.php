<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ThrowPoint;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Reflection\ReflectionProvider;
use function array_keys;
use function count;
use function strtolower;

/**
 * Shared logic for the CallTo*StatementWithoutImpurePointsRule family.
 *
 * The *WithoutImpurePointsCollector collectors no longer require a declaration to have
 * zero impure points. Instead they record, for every otherwise-effect-free declaration,
 * the set of "possibly pure" callables its impure points refer to (its dependencies).
 *
 * This resolver computes the transitive closure of effect-free declarations: a declaration
 * is effect-free when every callable it depends on is itself effect-free. The base case is
 * a declaration with no dependencies (no impure points at all).
 */
#[AutowiredService]
final class PossiblyPureCallTransitivePurityResolver
{

	private ?CollectedDataNode $cachedNode = null;

	/** @var array<string, true> */
	private array $cachedResult = [];

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public static function functionKey(string $functionName): string
	{
		return 'f' . "\0" . strtolower($functionName);
	}

	public static function methodKey(string $className, string $methodName): string
	{
		return 'm' . "\0" . strtolower($className) . "\0" . strtolower($methodName);
	}

	/**
	 * Resolves the dependencies of a declaration from its impure and throw points.
	 *
	 * Returns the list of callable keys the declaration depends on, or null when the
	 * declaration can never be effect-free (it has a non-call impure point or an explicit
	 * throw point). Implicit throw points are ignored, mirroring how noop expression
	 * statements are detected in NodeScopeResolver.
	 *
	 * @param ImpurePoint[] $impurePoints
	 * @param ThrowPoint[] $throwPoints
	 * @return list<string>|null
	 */
	public function resolveDependencies(array $impurePoints, array $throwPoints): ?array
	{
		foreach ($throwPoints as $throwPoint) {
			if ($throwPoint->isExplicit()) {
				return null;
			}
		}

		$dependencies = [];
		foreach ($impurePoints as $impurePoint) {
			$keys = $this->resolveCall($impurePoint->getNode(), $impurePoint->getScope());
			if ($keys === null) {
				return null;
			}

			foreach ($keys as $key) {
				$dependencies[$key] = true;
			}
		}

		return array_keys($dependencies);
	}

	/**
	 * @return array<string, true>
	 */
	public function getPureCallableKeys(CollectedDataNode $node): array
	{
		if ($this->cachedNode === $node) {
			return $this->cachedResult;
		}

		/** @var array<string, list<string>> $declarations */
		$declarations = [];

		foreach ($node->get(FunctionWithoutImpurePointsCollector::class) as $collected) {
			foreach ($collected as [$functionName, $dependencies]) {
				$declarations[self::functionKey($functionName)] = $dependencies;
			}
		}

		foreach ($node->get(MethodWithoutImpurePointsCollector::class) as $collected) {
			foreach ($collected as [$className, $methodName, , $dependencies]) {
				$declarations[self::methodKey($className, $methodName)] = $dependencies;
			}
		}

		foreach ($node->get(ConstructorWithoutImpurePointsCollector::class) as $collected) {
			foreach ($collected as [$className, $dependencies]) {
				$declarations[self::methodKey($className, '__construct')] = $dependencies;
			}
		}

		$pure = [];
		do {
			$changed = false;
			foreach ($declarations as $key => $dependencies) {
				if (isset($pure[$key])) {
					continue;
				}

				$allPure = true;
				foreach ($dependencies as $dependency) {
					if (!isset($pure[$dependency])) {
						$allPure = false;
						break;
					}
				}

				if (!$allPure) {
					continue;
				}

				$pure[$key] = true;
				$changed = true;
			}
		} while ($changed);

		$this->cachedNode = $node;
		$this->cachedResult = $pure;

		return $pure;
	}

	/**
	 * Resolves a call expression to the keys of the callables it targets, or null when
	 * the call cannot be guaranteed effect-free (unknown callee, overridable method, ...).
	 *
	 * @return list<string>|null
	 */
	private function resolveCall(Node $expr, Scope $scope): ?array
	{
		if ($expr instanceof Node\Expr\FuncCall) {
			if ($expr->isFirstClassCallable()) {
				return null;
			}
			if (!$expr->name instanceof Node\Name) {
				return null;
			}
			if (!$this->reflectionProvider->hasFunction($expr->name, $scope)) {
				return null;
			}

			return [self::functionKey($this->reflectionProvider->getFunction($expr->name, $scope)->getName())];
		}

		if ($expr instanceof Node\Expr\MethodCall || $expr instanceof Node\Expr\NullsafeMethodCall) {
			if ($expr->isFirstClassCallable()) {
				return null;
			}
			if (!$expr->name instanceof Node\Identifier) {
				return null;
			}

			$methodName = $expr->name->toString();
			$calledOnType = $scope->getType($expr->var);
			if (!$calledOnType->hasMethod($methodName)->yes()) {
				return null;
			}

			$keys = [];
			foreach ($calledOnType->getObjectClassReflections() as $classReflection) {
				if (!$classReflection->hasMethod($methodName)) {
					return null;
				}

				$methodReflection = $classReflection->getMethod($methodName, $scope);
				if (
					!$methodReflection->isPrivate()
					&& !$methodReflection->isFinal()->yes()
					&& !$methodReflection->getDeclaringClass()->isFinal()
				) {
					if (!$classReflection->isFinal()) {
						return null;
					}
				}

				$keys[] = self::methodKey($methodReflection->getDeclaringClass()->getName(), $methodReflection->getName());
			}

			if (count($keys) === 0) {
				return null;
			}

			return $keys;
		}

		if ($expr instanceof Node\Expr\StaticCall) {
			if ($expr->isFirstClassCallable()) {
				return null;
			}
			if (!$expr->name instanceof Node\Identifier) {
				return null;
			}
			if (!$expr->class instanceof Node\Name) {
				return null;
			}

			$methodName = $expr->name->toString();
			$calledOnType = $scope->resolveTypeByName($expr->class);
			$methodReflection = $scope->getMethodReflection($calledOnType, $methodName);
			if ($methodReflection === null) {
				return null;
			}

			return [self::methodKey($methodReflection->getDeclaringClass()->getName(), $methodReflection->getName())];
		}

		if ($expr instanceof Node\Expr\New_) {
			if (!$expr->class instanceof Node\Name) {
				return null;
			}

			$className = $expr->class->toString();
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

			return [self::methodKey($constructor->getDeclaringClass()->getName(), '__construct')];
		}

		return null;
	}

}
