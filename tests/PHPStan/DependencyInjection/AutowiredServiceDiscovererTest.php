<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\Collectors\RegistryFactory;
use PHPStan\DependencyInjection\AutowiredServices\DiscoveredExtension;
use PHPStan\DependencyInjection\AutowiredServices\TestedDiscoveredAliasedService;
use PHPStan\DependencyInjection\AutowiredServices\TestedDiscoveredCollector;
use PHPStan\DependencyInjection\AutowiredServices\TestedDiscoveredExtension;
use PHPStan\DependencyInjection\AutowiredServices\TestedDiscoveredHighLevelRule;
use PHPStan\DependencyInjection\AutowiredServices\TestedDiscoveredNonAutowiredService;
use PHPStan\DependencyInjection\AutowiredServices\TestedDiscoveredReadWritePropertiesExtension;
use PHPStan\DependencyInjection\AutowiredServices\TestedDiscoveredRule;
use PHPStan\DependencyInjection\AutowiredServices\TestedDiscoveredService;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use PHPStan\Rules\LazyRegistry;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPUnit\Framework\TestCase;
use function array_filter;
use function glob;
use function md5;
use function mkdir;
use function sys_get_temp_dir;
use function uniqid;

class AutowiredServiceDiscovererTest extends TestCase
{

	private static ?Container $container = null;

	private static string $tmpDir;

	private static function createContainer(): Container
	{
		if (self::$container !== null) {
			return self::$container;
		}

		self::$tmpDir = sys_get_temp_dir() . '/phpstan-autowired-services-' . md5(uniqid(more_entropy: true));
		mkdir(self::$tmpDir, 0777, true);

		$containerFactory = new ContainerFactory(__DIR__);

		return self::$container = $containerFactory->create(self::$tmpDir, [__DIR__ . '/autowiredServices.neon'], []);
	}

	public function testAutowiredService(): void
	{
		$service = self::createContainer()->getByType(TestedDiscoveredService::class);
		$this->assertSame(self::$tmpDir, $service->getTmpDir());
		$this->assertFalse($service->isBleedingEdge());
	}

	public function testNonAutowiredService(): void
	{
		$container = self::createContainer();
		$this->assertInstanceOf(
			TestedDiscoveredNonAutowiredService::class,
			$container->getService('testedDiscoveredNonAutowiredService'),
		);
	}

	/**
	 * The pre-filter deciding which files are worth parsing must not assume the attribute is
	 * referred to by name - an aliased namespace import spells out neither.
	 */
	public function testServiceUsingAnAliasedNamespaceImport(): void
	{
		$container = self::createContainer();
		$this->assertInstanceOf(
			TestedDiscoveredAliasedService::class,
			$container->getService('testedDiscoveredAliasedService'),
		);
	}

	public function testNonAutowiredServiceCannotBeAutowired(): void
	{
		$container = self::createContainer();
		$this->expectException(MissingServiceException::class);
		$container->getByType(TestedDiscoveredNonAutowiredService::class);
	}

	public function testRegisteredRuleOnCurrentLevel(): void
	{
		$rules = self::createContainer()->getServicesByTag(LazyRegistry::RULE_TAG);
		$this->assertCount(1, array_filter($rules, static fn ($rule): bool => $rule instanceof TestedDiscoveredRule));
	}

	public function testRegisteredRuleAboveCurrentLevelIsNotRegistered(): void
	{
		$rules = self::createContainer()->getServicesByTag(LazyRegistry::RULE_TAG);
		$this->assertCount(0, array_filter($rules, static fn ($rule): bool => $rule instanceof TestedDiscoveredHighLevelRule));
	}

	public function testRegisteredCollector(): void
	{
		$collectors = self::createContainer()->getServicesByTag(RegistryFactory::COLLECTOR_TAG);
		$this->assertCount(1, array_filter($collectors, static fn ($collector): bool => $collector instanceof TestedDiscoveredCollector));
	}

	public function testAutoTaggedExtension(): void
	{
		$extensions = self::createContainer()->getExtensionsCollection(ReadWritePropertiesExtension::class)->getAll();
		$this->assertCount(1, array_filter($extensions, static fn ($extension): bool => $extension instanceof TestedDiscoveredReadWritePropertiesExtension));
	}

	public function testDiscoveredExtensionInterface(): void
	{
		$extensions = self::createContainer()->getExtensionsCollection(DiscoveredExtension::class)->getAll();
		$this->assertCount(1, $extensions);
		$this->assertInstanceOf(TestedDiscoveredExtension::class, $extensions[0]);
	}

	/**
	 * Editing a discovered class has to invalidate the container the same way editing
	 * a configuration file does - otherwise a changed #[AutowiredService] would keep
	 * being served from the container cache.
	 */
	public function testEditingDiscoveredFileChangesTheContainer(): void
	{
		$dataDir = sys_get_temp_dir() . '/phpstan-discovered-service-' . md5(uniqid(more_entropy: true));
		$tmpDir = $dataDir . '/tmp';
		mkdir($dataDir . '/services', 0777, true);
		mkdir($tmpDir, 0777, true);

		$serviceFile = $dataDir . '/services/DiscoveredService.php';
		FileWriter::write($serviceFile, "<?php declare(strict_types = 1);\n\nnamespace PHPStanTest\\Discovered;\n\n#[\\PHPStan\\DependencyInjection\\AutowiredService(name: 'discoveredTestService')]\nfinal class DiscoveredService\n{\n\n}\n");
		FileWriter::write($dataDir . '/config.neon', "parameters:\n\tautowiredServiceDirectories:\n\t\t- services\n");

		// the discoverer reflects on the class and the autoloader knows nothing about this directory
		require_once $serviceFile;

		$container = (new ContainerFactory($dataDir))->create($tmpDir, [$dataDir . '/config.neon'], []);
		$this->assertTrue($container->hasService('discoveredTestService'));

		$containersBeforeEdit = self::listGeneratedContainers($tmpDir);
		$this->assertCount(1, $containersBeforeEdit);

		FileWriter::write($serviceFile, FileReader::read($serviceFile) . "\n// edited\n");

		(new ContainerFactory($dataDir))->create($tmpDir, [$dataDir . '/config.neon'], []);

		$containersAfterEdit = self::listGeneratedContainers($tmpDir);
		$this->assertCount(2, $containersAfterEdit);
	}

	/**
	 * @return list<string>
	 */
	private static function listGeneratedContainers(string $tmpDir): array
	{
		$files = glob($tmpDir . '/cache/nette.configurator/Container_*.php');
		if ($files === false) {
			return [];
		}

		return $files;
	}

}
