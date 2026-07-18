<?php declare(strict_types = 1); // lint >= 8.0

namespace JetBrains\PhpStorm {
	// Declared locally: PHPStan does not autoload phpstorm-stubs' meta/attributes/FileReference.php (it is absent from PhpStormStubsMap). Drop this once the attribute is registered there.
	#[\Attribute(\Attribute::TARGET_PARAMETER)]
	class FileReference
	{

		public function __construct(string $basePath = '')
		{
		}

	}

}

namespace FileReferenceIntegration {

	use JetBrains\PhpStorm\FileReference;

	function loadFile(#[FileReference] string $path): void
	{
	}

	loadFile('file-reference-attribute.php');
	loadFile('file-reference-attribute-missing.php');

}
