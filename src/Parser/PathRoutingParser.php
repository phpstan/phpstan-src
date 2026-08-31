<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PHPStan\File\FileHelper;
use function array_fill_keys;
use function array_slice;
use function count;
use function explode;
use function implode;
use function is_link;
use function realpath;
use function str_contains;
use const DIRECTORY_SEPARATOR;

final class PathRoutingParser implements Parser
{

	private ?string $singleReflectionFile;

	/** @var array<string, true> filePath(string) => bool(true) */
	private array $analysedFiles = [];

	/** @var array<string, bool> */
	private array $shouldUseRichParserCache = [];

	public function __construct(
		private FileHelper $fileHelper,
		private Parser $currentPhpVersionRichParser,
		private Parser $currentPhpVersionSimpleParser,
		private Parser $php8Parser,
		?string $singleReflectionFile,
	)
	{
		$this->singleReflectionFile = $singleReflectionFile !== null ? $fileHelper->normalizePath($singleReflectionFile) : null;
	}

	/**
	 * @param string[] $files
	 */
	public function setAnalysedFiles(array $files): void
	{
		$this->analysedFiles = array_fill_keys($files, true);
		$this->shouldUseRichParserCache = [];
	}

	public function parseFile(string $file): array
	{
		if ($this->isPhp8StubFile($file)) {
			return $this->php8Parser->parseFile($file);
		}

		$parser = $this->shouldUseRichParser($file)
			? $this->currentPhpVersionRichParser
			: $this->currentPhpVersionSimpleParser;

		return $parser->parseFile($this->fileHelper->normalizePath($file));
	}

	public function shouldUseRichParser(string $file): bool
	{
		return $this->shouldUseRichParserCache[$file] ??= $this->resolveShouldUseRichParser($file);
	}

	private function resolveShouldUseRichParser(string $file): bool
	{
		if ($this->isPhp8StubFile($file)) {
			return false;
		}

		$file = $this->fileHelper->normalizePath($file);
		if (!isset($this->analysedFiles[$file]) && $file !== $this->singleReflectionFile) {
			// check symlinked file that still might be in analysedFiles
			$pathParts = explode(DIRECTORY_SEPARATOR, $file);
			for ($i = count($pathParts); $i > 1; $i--) {
				$joinedPartOfPath = implode(DIRECTORY_SEPARATOR, array_slice($pathParts, 0, $i));
				if (!@is_link($joinedPartOfPath)) {
					continue;
				}

				$realFilePath = realpath($file);
				if ($realFilePath !== false) {
					$normalizedRealFilePath = $this->fileHelper->normalizePath($realFilePath);
					if (isset($this->analysedFiles[$normalizedRealFilePath])) {
						return true;
					}
				}
				break;
			}

			return false;
		}

		return true;
	}

	public function parseString(string $sourceCode): array
	{
		return $this->currentPhpVersionSimpleParser->parseString($sourceCode);
	}

	private function isPhp8StubFile(string $file): bool
	{
		$normalizedPath = $this->fileHelper->normalizePath($file, '/');

		return str_contains($normalizedPath, 'vendor/jetbrains/phpstorm-stubs')
			|| str_contains($normalizedPath, 'vendor/phpstan/php-8-stubs/stubs');
	}

}
