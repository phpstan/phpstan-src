<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;

#[AutowiredService]
final class DomDocumentStubFilesExtension implements StubFilesExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getFiles(): array
	{
		// Since PHP 8.0 DOMDocument::load()/loadXML()/loadHTML()/loadHTMLFile()
		// throw a ValueError when passed an empty string.
		if ($this->phpVersion->getVersionId() >= 80000) {
			return [__DIR__ . '/../../stubs/DOMDocument_php8.stub'];
		}

		return [__DIR__ . '/../../stubs/DOMDocument.stub'];
	}

}
