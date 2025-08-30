<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug12894;

use Closure;

/**
 * @template TValue of object|null
 */
interface Dependency
{

	/**
	 * @return TValue
	 */
	public function __invoke(): object|null;

}

interface DependencyResolver
{

	/**
	 * @template V of object|null
	 * @template D of Dependency<V>
	 *
	 * @param D $dependency
	 *
	 * @return V
	 */
	public function resolve(Dependency $dependency): object|null;

}

class Resolver implements DependencyResolver
{
	/**
	 * @var Closure(object|null): void
	 */
	protected Closure $run;

	public function resolve(Dependency $dependency): object|null {
		$resolved = $dependency();
		\PHPStan\Testing\assertType('V of object|null (method Bug12894\DependencyResolver::resolve(), argument)', $resolved);
		$result = is_object($resolved) ? 1 : 2;
		\PHPStan\Testing\assertType('V of object (method Bug12894\DependencyResolver::resolve(), argument)|V of null (method Bug12894\DependencyResolver::resolve(), argument)', $resolved);
		($this->run)($resolved);
		return $resolved;
	}

}
