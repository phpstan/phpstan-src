<?php declare(strict_types = 1);

namespace PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;

#[AutowiredService]
final class RegistryFactory
{

	public const COLLECTOR_TAG = 'phpstan.collector';

	/**
	 * @param ExtensionsCollection<Collector<Node, mixed>> $collectors
	 */
	public function __construct(
		#[AutowiredExtensions(of: Collector::class)]
		private ExtensionsCollection $collectors,
	)
	{
	}

	public function create(): Registry
	{
		return new Registry(
			$this->collectors->getAll(),
		);
	}

}
