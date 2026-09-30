<?php // lint >= 8.1

namespace ClassConstantWildcardArguments;

interface Extension
{

	/**
	 * @param static::* $type
	 */
	public function doFoo(string $type): void;

}

final class FileExtension implements Extension
{

	public const FILE = 'file';

	public function doFoo(string $type): void
	{
	}

}

class ContainerExtension implements Extension
{

	public const HAS_SERVICE = 'hasService';

	public function doFoo(string $type): void
	{
	}

}

final class Emitter
{

	/**
	 * @template T of Extension
	 * @param class-string<T> $extensionClass
	 * @param T::* $type
	 */
	public function valueDependency(string $extensionClass, string $type): void
	{
	}

}

function (Emitter $emitter, Extension $extension, FileExtension $fileExtension, ContainerExtension $containerExtension): void {
	$emitter->valueDependency(FileExtension::class, FileExtension::FILE);
	$emitter->valueDependency(ContainerExtension::class, ContainerExtension::HAS_SERVICE);
	$emitter->valueDependency(FileExtension::class, ContainerExtension::HAS_SERVICE);
	$emitter->valueDependency(FileExtension::class, 'nope');

	$fileExtension->doFoo(FileExtension::FILE);
	$fileExtension->doFoo(ContainerExtension::HAS_SERVICE);
	$containerExtension->doFoo('nope');
	$extension->doFoo('anything');
};
