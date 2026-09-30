<?php // lint >= 8.1

namespace ClassConstantWildcardStaticTemplate;

use function PHPStan\Testing\assertType;

interface Extension
{

	/**
	 * @param static::* $type
	 * @return static::*
	 */
	public function doFoo(string $type): string;

	/**
	 * @return static::VERSION
	 */
	public function getVersion(): string;

}

final class FileExtension implements Extension
{

	public const FILE = 'file';
	public const VERSION = 'file-1';

	public function doFoo(string $type): string
	{
		assertType("'file'|'file-1'", $type);

		return $type;
	}

	public function getVersion(): string
	{
		return self::VERSION;
	}

}

class ContainerExtension implements Extension
{

	public const SERVICE_HAS = 'hasService';
	public const SERVICE_GET = 'getService';
	public const PARAMETER = 'parameter';
	public const VERSION = 'container-1';

	public function doFoo(string $type): string
	{
		// static can be a subclass declaring more constants - only the native type is certain
		assertType('string', $type);

		return $type;
	}

	public function getVersion(): string
	{
		return static::VERSION;
	}

	/**
	 * @param static::SERVICE_* $type
	 */
	public function doService(string $type): void
	{
		assertType('string', $type);
	}

	/**
	 * @return static::SERVICE_*
	 */
	public function getService(): string
	{
		return self::SERVICE_HAS;
	}

}

final class SymfonyContainerExtension extends ContainerExtension
{

	public const SERVICE_TAGGED = 'taggedService';
	public const VERSION = 'symfony-1';

}

/**
 * @template T of Extension
 * @param class-string<T> $extensionClass
 * @param T::* $type
 * @return T::*
 */
function valueOf(string $extensionClass, string $type): string
{
	assertType('class-string<T of ClassConstantWildcardStaticTemplate\Extension (function ClassConstantWildcardStaticTemplate\valueOf(), argument)>', $extensionClass);

	return $type;
}

/**
 * @template T of Extension
 * @param class-string<T> $extensionClass
 * @return class-string<T>
 */
function classOf(string $extensionClass): string
{
	/** @var T::class $class */
	$class = $extensionClass;

	return $class;
}

/**
 * @template T of FileExtension
 * @param class-string<T> $extensionClass
 * @param T::* $type
 */
function finalBound(string $extensionClass, string $type): void
{
	assertType("'file'|'file-1'", $type);
}

function (
	Extension $extension,
	FileExtension $fileExtension,
	ContainerExtension $containerExtension,
	SymfonyContainerExtension $symfonyContainerExtension,
): void {
	// the class the method is called on decides which constants static:: stands for
	assertType("'file'|'file-1'", $fileExtension->doFoo(FileExtension::FILE));
	assertType("'container-1'|'getService'|'hasService'|'parameter'", $containerExtension->doFoo(ContainerExtension::PARAMETER));
	assertType("'getService'|'hasService'|'parameter'|'symfony-1'|'taggedService'", $symfonyContainerExtension->doFoo(SymfonyContainerExtension::SERVICE_TAGGED));
	assertType("'getService'|'hasService'", $containerExtension->getService());
	assertType("'getService'|'hasService'|'taggedService'", $symfonyContainerExtension->getService());

	// an interface declares none of the constants its implementations do - only the native type is certain
	assertType('string', $extension->doFoo('anything'));

	// a single constant is the one of the class the method is called on, as with static::FOO elsewhere
	assertType("'file-1'", $fileExtension->getVersion());
	assertType("'container-1'", $containerExtension->getVersion());
	assertType("'symfony-1'", $symfonyContainerExtension->getVersion());

	// T::* stands for the constants of the class T is inferred as
	assertType("'file'|'file-1'", valueOf(FileExtension::class, FileExtension::FILE));
	assertType("'getService'|'hasService'|'parameter'|'symfony-1'|'taggedService'", valueOf(SymfonyContainerExtension::class, SymfonyContainerExtension::SERVICE_HAS));
	assertType('class-string<ClassConstantWildcardStaticTemplate\FileExtension>', classOf(FileExtension::class));
};

trait HasModes
{

	/**
	 * @param static::MODE_* $mode
	 * @return static::MODE_*
	 */
	public function withMode(string $mode): string
	{
		// in the context of the only class using the trait, which is final
		assertType("'fast'|'slow'", $mode);

		return $mode;
	}

}

final class Engine
{

	use HasModes;

	public const MODE_FAST = 'fast';
	public const MODE_SLOW = 'slow';

}

function (Engine $engine): void {
	assertType("'fast'|'slow'", $engine->withMode(Engine::MODE_FAST));
};
