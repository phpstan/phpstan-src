<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use AttributeServicesFixtures\DiscoveredCollector;
use AttributeServicesFixtures\DiscoveredNamedService;
use AttributeServicesFixtures\DiscoveredNonAutowiredService;
use AttributeServicesFixtures\DiscoveredRuleLevelEight;
use AttributeServicesFixtures\DiscoveredRuleLevelThree;
use AttributeServicesFixtures\DiscoveredService;
use AttributeServicesFixtures\DiscoveredValueFactory;
use PHPStan\Collectors\RegistryFactory;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\DependencyInjection\DerivativeContainerFactory;
use PHPStan\Rules\LazyRegistry;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function array_filter;
use function dirname;
use function md5;
use function mkdir;
use function sys_get_temp_dir;
use function uniqid;

class AttributeServicesIntegrationTest extends TestCase
{

	private static ?Container $container = null;

	private static ?string $tmpDir = null;

	private static function createContainer(): Container
	{
		if (self::$container !== null) {
			return self::$container;
		}

		self::$tmpDir = self::createTmpDir();
		$containerFactory = new ContainerFactory(__DIR__);

		return self::$container = $containerFactory->create(self::$tmpDir, [__DIR__ . '/attributeServices.neon'], [], [dirname(__DIR__, 4)]);
	}

	private static function createTmpDir(): string
	{
		$tmpDir = sys_get_temp_dir() . '/phpstan-attribute-services-' . md5(uniqid(more_entropy: true));
		mkdir($tmpDir, 0777, true);

		return $tmpDir;
	}

	public function testDiscoveredServiceWithAutowiredParameters(): void
	{
		$container = self::createContainer();
		$service = $container->getByType(DiscoveredService::class);
		$this->assertSame(__DIR__, $service->getCurrentWorkingDirectory());
		$this->assertSame(self::$tmpDir, $service->getTmpDir());
	}

	public function testDiscoveredServiceIsAutoTagged(): void
	{
		$container = self::createContainer();
		$extensions = $container->getExtensionsCollection(ReadWritePropertiesExtension::class)->getAll();
		$this->assertCount(1, $extensions);
		$this->assertInstanceOf(DiscoveredService::class, $extensions[0]);
	}

	public function testDiscoveredNamedService(): void
	{
		$container = self::createContainer();
		$this->assertInstanceOf(DiscoveredNamedService::class, $container->getService('attributeServicesFixtures.named'));
	}

	public function testDiscoveredNonAutowiredService(): void
	{
		$container = self::createContainer();
		$this->assertInstanceOf(DiscoveredNonAutowiredService::class, $container->getService('attributeServicesFixtures.nonAutowired'));
	}

	public function testDiscoveredRulesFollowTheLevel(): void
	{
		$container = self::createContainer();
		$rules = $container->getServicesByTag(LazyRegistry::RULE_TAG);
		$this->assertCount(1, array_filter($rules, static fn ($rule): bool => $rule instanceof DiscoveredRuleLevelThree));
		$this->assertCount(0, array_filter($rules, static fn ($rule): bool => $rule instanceof DiscoveredRuleLevelEight));
	}

	public function testDiscoveredCollector(): void
	{
		$container = self::createContainer();
		$collectors = $container->getServicesByTag(RegistryFactory::COLLECTOR_TAG);
		$this->assertCount(1, array_filter($collectors, static fn ($collector): bool => $collector instanceof DiscoveredCollector));
	}

	public function testDiscoveredGeneratedFactory(): void
	{
		$container = self::createContainer();
		$factory = $container->getByType(DiscoveredValueFactory::class);
		$value = $factory->create('hello');
		$this->assertSame('hello', $value->name);
		$this->assertSame(self::$tmpDir, $value->tmpDir);
	}

	public function testDirectoriesParameterIsAbsolutized(): void
	{
		$container = self::createContainer();
		$this->assertSame([__DIR__ . '/data/services'], $container->getParameter('attributeServicesDirectories'));
	}

	public function testDerivativeContainerSeesDiscoveredServices(): void
	{
		$container = self::createContainer();
		$derivativeContainer = $container->getByType(DerivativeContainerFactory::class)
			->create([dirname(__DIR__, 4) . '/conf/config.stubValidator.neon'], ['allStubFiles' => []]);
		$service = $derivativeContainer->getByType(DiscoveredService::class);
		$this->assertSame(__DIR__, $service->getCurrentWorkingDirectory());
	}

	#[DataProvider('dataErrors')]
	public function testErrors(string $neonFile, string $expectedMessage): void
	{
		$containerFactory = new ContainerFactory(__DIR__);
		$this->expectException(InvalidAttributeServicesDirectoriesException::class);
		$this->expectExceptionMessage($expectedMessage);
		$containerFactory->create(self::createTmpDir(), [__DIR__ . '/' . $neonFile], [], [dirname(__DIR__, 4)]);
	}

	/**
	 * @return iterable<array{string, string}>
	 */
	public static function dataErrors(): iterable
	{
		yield [
			'containerExtension.neon',
			'Attribute #[ContainerExtension] on class AttributeServicesFixtures\ContainerExtension\BadCompilerExtension is not supported in directories from the attributeServicesDirectories section - the list of compiler extensions is fixed before the section is processed. Register the class in the `extensions` section of the configuration file instead.',
		];

		yield [
			'extensionInterface.neon',
			'Attribute #[ExtensionInterface] on AttributeServicesFixtures\ExtensionInterface\BadExtensionInterface is not supported in directories from the attributeServicesDirectories section - third-party extension interfaces are not supported.',
		];

		yield [
			'autowiredExtensionsParam.neon',
			'Attribute #[AutowiredExtensions] on a constructor parameter of class AttributeServicesFixtures\AutowiredExtensions\BadAutowiredExtensionsService is not supported in directories from the attributeServicesDirectories section.',
		];

		yield [
			'internalAttribute.neon',
			'Attribute #[ValidatesStubFiles] on class AttributeServicesFixtures\InternalAttribute\UsesValidatesStubFiles is only supported on classes shipped with PHPStan itself, not on classes discovered through the attributeServicesDirectories section.',
		];

		yield [
			'unloadable.neon',
			'cannot be autoloaded.',
		];
	}

}
