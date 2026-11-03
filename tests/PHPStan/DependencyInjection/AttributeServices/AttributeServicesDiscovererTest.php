<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use AttributeServicesFixtures\Psr4Mismatch\WellPlaced;
use olvlvl\ComposerAttributeCollector\TargetClass;
use PHPStan\DependencyInjection\AutowiredService;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use function array_map;
use function defined;
use function is_file;
use function spl_autoload_register;
use function spl_autoload_unregister;
use function str_starts_with;
use function strlen;
use function strtr;
use function substr;

#[RequiresPhp('>= 8.0.0')]
class AttributeServicesDiscovererTest extends TestCase
{

	/**
	 * Composer's PSR-4 loader includes the file of the class name derived from the path on every
	 * autoload, so a file declaring another class must not be autoloaded more than once.
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testFileDeclaringAnotherClassThanItsPath(): void
	{
		$targets = $this->discover('/data/psr4Mismatch', 'AttributeServicesFixtures\\Psr4Mismatch\\');

		$this->assertSame(
			[WellPlaced::class],
			array_map(static fn (TargetClass $target): string => $target->name, $targets->targetClasses[AutowiredService::class] ?? []),
		);
	}

	/**
	 * Only files that can reference PHPStan's DI attributes have their classes loaded.
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testFileMentioningAttributesOnlyInCommentIsNotLoaded(): void
	{
		$targets = $this->discover('/data/prefilter', 'AttributeServicesFixtures\\PHPStan\\Prefilter\\');

		$this->assertSame([], $targets->targetClasses);
		$this->assertFalse(defined('ATTRIBUTE_SERVICES_FIXTURE_COMMENT_ONLY_LOADED'));
	}

	private function discover(string $relativeDirectory, string $prefix): DiscoveredAttributeTargets
	{
		$directory = strtr(__DIR__, '\\', '/') . $relativeDirectory;
		$autoloader = static function (string $className) use ($directory, $prefix): void {
			if (!str_starts_with($className, $prefix)) {
				return;
			}

			$file = $directory . '/' . strtr(substr($className, strlen($prefix)), '\\', '/') . '.php';
			if (!is_file($file)) {
				return;
			}

			include $file;
		};
		spl_autoload_register($autoloader, prepend: true);

		try {
			return (new AttributeServicesDiscoverer())->discover(new ResolvedAttributeServicesDirectories([
				new ResolvedAttributeServicesDirectory($directory, null, []),
			]));
		} finally {
			spl_autoload_unregister($autoloader);
		}
	}

}
