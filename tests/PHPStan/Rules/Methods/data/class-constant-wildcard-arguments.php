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

final class Tracker
{

	/**
	 * @template T of Extension
	 * @param class-string<T> $extensionClass
	 * @param T::* $type
	 */
	public function trackValueDependency(string $extensionClass, string $type): void
	{
	}

}

function (Tracker $tracker, Extension $extension, FileExtension $fileExtension, ContainerExtension $containerExtension): void {
	$tracker->trackValueDependency(FileExtension::class, FileExtension::FILE);
	$tracker->trackValueDependency(ContainerExtension::class, ContainerExtension::HAS_SERVICE);
	$tracker->trackValueDependency(FileExtension::class, ContainerExtension::HAS_SERVICE);
	$tracker->trackValueDependency(FileExtension::class, 'nope');

	$fileExtension->doFoo(FileExtension::FILE);
	$fileExtension->doFoo(ContainerExtension::HAS_SERVICE);
	$containerExtension->doFoo('nope');
	$extension->doFoo('anything');
};
