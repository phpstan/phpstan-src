<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use stdClass;

class ExtensionsCollectionTest extends PHPStanTestCase
{

	/**
	 * @return iterable<array{class-string, string}>
	 */
	public static function dataGetExtensions(): iterable
	{
		return [
			[DynamicMethodReturnTypeExtension::class, 'phpstan.broker.dynamicMethodReturnTypeExtension'],
			[ReadWritePropertiesExtension::class, 'phpstan.properties.readWriteExtension'],
			[IgnoreErrorExtension::class, 'phpstan.ignoreErrorExtension'],
		];
	}

	/**
	 * @param class-string $interfaceName
	 */
	#[DataProvider('dataGetExtensions')]
	public function testGetExtensions(string $interfaceName, string $tag): void
	{
		$container = self::getContainer();
		$extensions = $container->getExtensions($interfaceName);

		$this->assertSame($container->getServicesByTag($tag), $extensions);
		foreach ($extensions as $extension) {
			$this->assertInstanceOf($interfaceName, $extension);
		}
	}

	public function testGetExtensionsOfNonExtensionInterface(): void
	{
		$this->expectException(MissingServiceException::class);
		$this->expectExceptionMessage('Interface stdClass is not an extension interface. Mark it with the #[PHPStan\DependencyInjection\ExtensionInterface] attribute.');
		self::getContainer()->getExtensions(stdClass::class);
	}

	public function testLazyExtensionsCollectionReleasesTheContainer(): void
	{
		$collection = new LazyExtensionsCollection(self::getContainer(), DynamicMethodReturnTypeExtension::class);

		$containerProperty = new ReflectionProperty(LazyExtensionsCollection::class, 'container');
		$this->assertNotNull($containerProperty->getValue($collection));

		$extensions = $collection->getAll();

		$this->assertNull($containerProperty->getValue($collection));
		$this->assertSame($extensions, $collection->getAll());
	}

	public function testDirectExtensionsCollection(): void
	{
		$extension = self::getContainer()->getExtensions(IgnoreErrorExtension::class);
		$collection = new DirectExtensionsCollection($extension);

		$this->assertSame($extension, $collection->getAll());
	}

}
