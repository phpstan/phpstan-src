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

namespace FileReferenceTest {

	use JetBrains\PhpStorm\FileReference;

	function loadFile(#[FileReference] string $path): void
	{
	}

	function loadFileWithBasePath(#[FileReference('file-reference-base')] string $path): void
	{
	}

	class Loader
	{

		public function __construct(#[FileReference] string $path)
		{
		}

		public function load(#[FileReference] string $path): void
		{
		}

		public static function loadStatic(#[FileReference] string $path): void
		{
		}

	}

	class PromotedLoader
	{

		public function __construct(#[FileReference] public string $path)
		{
		}

	}

	// function calls
	loadFile('file-reference-existing.php');
	loadFile('file-reference-missing.php');
	loadFile('file-reference-base');
	loadFile('file-reference-nested.php');

	// non-constant path is not checked
	$dynamicPath = (string) rand();
	loadFile($dynamicPath);

	// base path resolves against the analysed file directory
	loadFileWithBasePath('file-reference-nested.php');
	loadFileWithBasePath('file-reference-missing.php');

	// constructor calls
	new Loader('file-reference-existing.php');
	new Loader('file-reference-missing.php');

	// method calls
	$loader = new Loader('file-reference-existing.php');
	$loader->load('file-reference-existing.php');
	$loader->load('file-reference-missing.php');

	// static calls
	Loader::loadStatic('file-reference-existing.php');
	Loader::loadStatic('file-reference-missing.php');

	// promoted constructor property
	new PromotedLoader('file-reference-existing.php');
	new PromotedLoader('file-reference-missing.php');

}

namespace {

	// File-existence-testing functions legitimately receive paths that may not
	// exist, so they are exempt even when annotated with #[FileReference].
	// See https://youtrack.jetbrains.com/issue/WI-85516
	function is_file(#[\JetBrains\PhpStorm\FileReference] string $path): bool
	{
		return true;
	}

	is_file('file-reference-missing.php');

}
