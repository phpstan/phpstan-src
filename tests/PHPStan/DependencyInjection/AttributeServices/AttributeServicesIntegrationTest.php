<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use AttributeServicesFixtures\DiscoveredCollector;
use AttributeServicesFixtures\DiscoveredConditionalRule;
use AttributeServicesFixtures\DiscoveredConditionalRuleNotMet;
use AttributeServicesFixtures\DiscoveredConditionalService;
use AttributeServicesFixtures\DiscoveredNamedService;
use AttributeServicesFixtures\DiscoveredNonAutowiredService;
use AttributeServicesFixtures\DiscoveredRuleLevelEight;
use AttributeServicesFixtures\DiscoveredRuleLevelThree;
use AttributeServicesFixtures\DiscoveredService;
use AttributeServicesFixtures\DiscoveredStaticallyCreated;
use AttributeServicesFixtures\DiscoveredValue;
use AttributeServicesFixtures\DiscoveredValueFactory;
use AttributeServicesFixtures\ExtensionInterfaces\AttributeMappedService;
use AttributeServicesFixtures\ExtensionInterfaces\ConditionallyTaggedService;
use AttributeServicesFixtures\ExtensionInterfaces\NeonMappedService;
use Nette\DI\InvalidConfigurationException;
use PHPStan\Collectors\RegistryFactory;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\DependencyInjection\DerivativeContainerFactory;
use PHPStan\DependencyInjection\MissingImplementedInterfaceInServiceWithTagException;
use PHPStan\Rules\LazyRegistry;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use Throwable;
use function array_filter;
use function array_map;
use function dirname;
use function md5;
use function mkdir;
use function sort;
use function strtr;
use function sys_get_temp_dir;
use function uniqid;
use const PHP_VERSION_ID;

#[RequiresPhp('>= 8.0.0')]
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

		// PHP 8.0 cannot even load a class with `new` in attribute arguments
		$configFile = PHP_VERSION_ID >= 80100 ? 'attributeServicesNewInInitializers.neon' : 'attributeServices.neon';

		return self::$container = $containerFactory->create(self::$tmpDir, [__DIR__ . '/' . $configFile], [], [dirname(__DIR__, 4)]);
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

	public function testDiscoveredServicesCreatedByStaticFactory(): void
	{
		$container = self::createContainer();
		$this->assertInstanceOf(DiscoveredStaticallyCreated::class, $container->getService('attributeServicesFixtures.staticallyCreated'));
		$this->assertInstanceOf(DiscoveredStaticallyCreated::class, $container->getService('attributeServicesFixtures.staticallyCreatedNonAutowired'));
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

	#[RequiresPhp('>= 8.1.0')]
	public function testDiscoveredConditionalWithNewInAttributeArguments(): void
	{
		$container = self::createContainer();
		$rules = $container->getServicesByTag(LazyRegistry::RULE_TAG);
		$this->assertCount(1, array_filter($rules, static fn ($rule): bool => $rule instanceof DiscoveredConditionalRule));
	}

	public function testDiscoveredConditional(): void
	{
		$container = self::createContainer();
		$rules = $container->getServicesByTag(LazyRegistry::RULE_TAG);
		$this->assertCount(0, array_filter($rules, static fn ($rule): bool => $rule instanceof DiscoveredConditionalRuleNotMet));

		// the services are registered even when the condition is not met, only their tags are skipped
		$this->assertCount(1, $container->findServiceNamesByType(DiscoveredConditionalRuleNotMet::class));
		$this->assertCount(1, $container->findServiceNamesByType(DiscoveredConditionalService::class));
	}

	public function testThirdPartyExtensionInterfaces(): void
	{
		$container = (new ContainerFactory(__DIR__))->create(self::createTmpDir(), [__DIR__ . '/extensionInterfaces.neon'], [], [dirname(__DIR__, 4)]);

		$attributeMapped = $container->getServicesByTag('attributeServicesFixtures.attributeMapped');
		$this->assertSame([AttributeMappedService::class], array_map(static fn (object $service): string => $service::class, $attributeMapped));

		$neonMapped = array_map(static fn (object $service): string => $service::class, $container->getServicesByTag('attributeServicesFixtures.neonMapped'));
		sort($neonMapped);
		$this->assertSame([ConditionallyTaggedService::class, NeonMappedService::class], $neonMapped);
	}

	/**
	 * @return iterable<array{string, class-string<Throwable>, string}>
	 */
	public static function dataExtensionInterfacesErrors(): iterable
	{
		yield [
			'extensionInterfacesWrongTag.neon',
			MissingImplementedInterfaceInServiceWithTagException::class,
			'Service of type AttributeServicesFixtures\\ExtensionInterfaces\\WronglyTaggedService with tag attributeServicesFixtures.neonMapped does not implement interface AttributeServicesFixtures\\ExtensionInterfaces\\NeonMappedExtension.',
		];
		yield [
			'extensionInterfacesCoreTag.neon',
			InvalidConfigurationException::class,
			'Tag phpstan.rules.rule is mapped to interface PHPStan\\Rules\\Rule, the extensionInterfaces section maps it to AttributeServicesFixtures\\ExtensionInterfaces\\NeonMappedExtension.',
		];
		yield [
			'extensionInterfacesMissing.neon',
			InvalidConfigurationException::class,
			'Interface AttributeServicesFixtures\\ExtensionInterfaces\\Missing from the extensionInterfaces section does not exist.',
		];
	}

	/**
	 * @param class-string<Throwable> $exceptionClass
	 */
	#[DataProvider('dataExtensionInterfacesErrors')]
	public function testExtensionInterfacesErrors(string $neonFile, string $exceptionClass, string $expectedMessage): void
	{
		$this->expectException($exceptionClass);
		$this->expectExceptionMessage($expectedMessage);
		(new ContainerFactory(__DIR__))->create(self::createTmpDir(), [__DIR__ . '/' . $neonFile], [], [dirname(__DIR__, 4)]);
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
		$expected = [strtr(__DIR__, '\\', '/') . '/data/services'];
		if (PHP_VERSION_ID >= 80100) {
			$expected[] = strtr(__DIR__, '\\', '/') . '/data/servicesNewInInitializers';
		}
		$this->assertSame($expected, $container->getParameter('attributeServicesDirectories'));
	}

	public function testRegisteredDiscoveredClassesParameter(): void
	{
		$container = self::createContainer();
		$classes = $container->getParameter('attributeServicesClasses');
		$this->assertIsArray($classes);
		$this->assertContains(DiscoveredService::class, $classes);
		$this->assertContains(DiscoveredRuleLevelThree::class, $classes);
		$this->assertContains(DiscoveredConditionalRuleNotMet::class, $classes);
		$this->assertNotContains(DiscoveredRuleLevelEight::class, $classes);
		$this->assertContains(DiscoveredValue::class, $classes);
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
