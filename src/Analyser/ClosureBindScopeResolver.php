<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parser\ClosureBindArgVisitor;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use function count;

/**
 * Resolves the class scope that a `self`/`parent`/`static` class-name node is bound to
 * by a surrounding `Closure::bind()` call. {@see ClosureBindArgVisitor} annotates such
 * nodes with the bind scope argument (the 3rd argument of `Closure::bind()`).
 */
#[AutowiredService]
final class ClosureBindScopeResolver
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	/**
	 * Returns the class the given class-name node is bound to via `Closure::bind()`, or
	 * null when the node is not inside a bound closure or the bind scope argument does not
	 * resolve to a single known class (e.g. the default "static" scope).
	 */
	public function resolveScopeClass(Scope $scope, Name $class): ?ClassReflection
	{
		$scopeArg = $class->getAttribute(ClosureBindArgVisitor::SCOPE_ATTRIBUTE_NAME);
		if (!$scopeArg instanceof Expr) {
			// Either the node is not inside a bound closure, or the attribute is null for
			// the default "static" scope. Both keep the enclosing class.
			return null;
		}

		$objectClassNames = $scope->getType($scopeArg)->getClassStringObjectType()->getObjectClassNames();
		if (count($objectClassNames) !== 1) {
			return null;
		}

		$className = $objectClassNames[0];
		if (!$this->reflectionProvider->hasClass($className)) {
			return null;
		}

		return $this->reflectionProvider->getClass($className);
	}

}
