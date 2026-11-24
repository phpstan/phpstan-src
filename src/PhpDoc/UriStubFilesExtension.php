<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;

#[AutowiredService]
final class UriStubFilesExtension implements StubFilesExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getFiles(): array
	{
		$files = [];
		if ($this->phpVersion->hasUriExtension()) {
			$files[] = __DIR__ . '/../../stubs/uri.stub';
		}
		if ($this->phpVersion->hasUriBuilders()) {
			$files[] = __DIR__ . '/../../stubs/uri_php86.stub';
		}

		return $files;
	}

}
