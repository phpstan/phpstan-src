<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\BetterReflection\SourceStubber\ExtensionVersionProvider;

#[AutowiredService]
final class ExtDsStubFilesExtension implements StubFilesExtension
{

	public function __construct(private ExtensionVersionProvider $extensionVersionProvider)
	{
	}

	public function getFiles(): array
	{
		if (($this->extensionVersionProvider->getExtensionVersions()['ds'] ?? null) === 2) {
			return [];
		}

		return [__DIR__ . '/../../stubs/ext-ds.stub'];
	}

}
