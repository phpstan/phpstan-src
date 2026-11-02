<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use function count;

/**
 * Hands the resolved `attributeServicesDirectories` section from ContainerFactory to the
 * compiler extensions consuming attribute targets. A static slot because Nette constructs
 * compiler extensions with `new $className()` - there is no injection channel that early.
 *
 * ContainerFactory::create() seeds the slot right before the container compiles, and every
 * container build in the process funnels through it (CommandHelper, DerivativeContainerFactory,
 * PHPStanTestCase), so the slot always describes the build currently on the call stack.
 * Discovery is lazy - on a container-cache hit nothing ever asks for the targets,
 * so no third-party class is autoloaded.
 */
final class AttributeServicesDiscoveryContext
{

	private static ?ResolvedAttributeServicesDirectories $currentDirectories = null;

	private static ?DiscoveredAttributeTargets $targets = null;

	public static function set(ResolvedAttributeServicesDirectories $directories): void
	{
		self::$currentDirectories = $directories;
		self::$targets = null;
	}

	/**
	 * @throws InvalidAttributeServicesDirectoriesException
	 */
	public static function getTargets(): DiscoveredAttributeTargets
	{
		if (self::$targets !== null) {
			return self::$targets;
		}

		if (self::$currentDirectories === null || count(self::$currentDirectories->directories) === 0) {
			return self::$targets = DiscoveredAttributeTargets::createEmpty();
		}

		return self::$targets = (new AttributeServicesDiscoverer())->discover(self::$currentDirectories);
	}

}
