<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14804;

use Closure;
use function PHPStan\Testing\assertType;

interface ContainerInterface
{
}

enum Lifecycle
{

	case TRANSIENT;
	case PERSISTENT;

}

class Container implements ContainerInterface
{

	/** @var array<class-string, Lifecycle> */
	private array $registry = [];

	/** @var array<class-string, object|null> */
	private array $persistentDependencies = [];

	/** @var array<class-string, (Closure(ContainerInterface $container, array<mixed> $arguments): object)> */
	private array $initializers = [];

	/** @var array<class-string, true> */
	private array $resolving = [];

	/**
	 * @template TClassName of object
	 * @param class-string<TClassName> $className
	 * @return TClassName
	 */
	public function resolve(string $className, array $arguments = []): object
	{
		$lifecycle = $this->registry[$className] ?? Lifecycle::TRANSIENT;

		if (
			$lifecycle === Lifecycle::PERSISTENT &&
			isset($this->persistentDependencies[$className])
		) {
			/** @var TClassName */
			return $this->persistentDependencies[$className];
		}

		if (isset($this->resolving[$className])) {
			throw new \Exception();
		}

		$this->resolving[$className] = true;

		try {
			if (isset($this->initializers[$className])) {
				assertType('$this(Bug14804\Container)', $this);
				assertType('Bug14804\Lifecycle::PERSISTENT|Bug14804\Lifecycle::TRANSIENT', $lifecycle);

				/** @var TClassName $instance */
				$instance = ($this->initializers[$className])($this, $arguments);

				if ($lifecycle === Lifecycle::PERSISTENT) {
					assertType('Bug14804\Lifecycle::PERSISTENT', $lifecycle);
					assertType('$this(Bug14804\Container)', $this);

					unset($this->initializers[$className]);
					$this->persistentDependencies[$className] = $instance;
				}

				return $instance;
			}

			throw new \Exception();
		} finally {
			unset($this->resolving[$className]);
		}
	}

}
