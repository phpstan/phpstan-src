<?php declare(strict_types = 1); // lint >= 8.0

namespace JetBrains\PhpStorm {

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
