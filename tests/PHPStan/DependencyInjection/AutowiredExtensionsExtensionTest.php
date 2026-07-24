<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Countable;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPUnit\Framework\TestCase;
use function md5;
use function mkdir;
use function sys_get_temp_dir;
use function uniqid;

class AutowiredExtensionsExtensionTest extends TestCase
{

	private static ?Container $container = null;

	private static function createContainer(): Container
	{
		if (self::$container !== null) {
			return self::$container;
		}

		$tmpDir = sys_get_temp_dir() . '/phpstan-autowired-extensions-' . md5(uniqid(more_entropy: true));
		mkdir($tmpDir, 0777, true);

		$containerFactory = new ContainerFactory(__DIR__);

		return self::$container = $containerFactory->create($tmpDir, [__DIR__ . '/autowiredExtensions.neon'], []);
	}

	public function testGetExtensions(): void
	{
		$container = self::createContainer();
		$extensions = $container->getExtensionsCollection(ReadWritePropertiesExtension::class)->getAll();
		$this->assertCount(1, $extensions);
		$this->assertInstanceOf(TestedReadWritePropertiesExtension::class, $extensions[0]);
	}

	public function testGetExtensionsCollectionIsLazyAndMemoized(): void
	{
		$container = self::createContainer();
		$collection = $container->getExtensionsCollection(ReadWritePropertiesExtension::class);
		$this->assertInstanceOf(LazyExtensionsCollection::class, $collection);
		$this->assertSame($collection->getAll(), $collection->getAll());
		$this->assertSame($collection, $container->getExtensionsCollection(ReadWritePropertiesExtension::class));
	}

	public function testGetExtensionsForUnknownInterface(): void
	{
		$container = self::createContainer();
		$this->expectException(MissingServiceException::class);
		$this->expectExceptionMessage('Countable is not an extension interface marked with the #[ExtensionInterface] attribute.');
		$container->getExtensionsCollection(Countable::class);
	}

	public function testAliasOfServiceWithAutowiredExtensionsParameterIsLeftAlone(): void
	{
		$container = self::createContainer();
		$this->assertSame(
			$container->getService('currentPhpVersionRichParser'),
			$container->getService('testAliasedRichParser'),
		);
	}

}
