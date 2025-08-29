<?php declare(strict_types = 1);

namespace Bug12894;

/**
 * @template TValue of object|null
 */
interface Dependency {
	/**
	 * @return TValue
	 */
	public function __invoke(): object|null;
}

interface DependencyResolver {
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

/**
 * @internal
 */
class Resolver implements DependencyResolver {
	public function __construct(
		/**
		 * @var Closure(object|null): void
		 */
		protected readonly Closure $run,
	) {
		// empty
	}

	public function resolve(Dependency $dependency): object|null {
		$resolved = $dependency();
		$result = is_object($resolved) ? 1 : 2;
		($this->run)($resolved);
		return $resolved;
	}
}
