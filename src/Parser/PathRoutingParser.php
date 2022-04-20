<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PHPStan\File\FileHelper;
use function array_fill_keys;
use function strpos;

class PathRoutingParser implements Parser
{

	private ?string $singleReflectionFile;

	/** @var bool[] filePath(string) => bool(true) */
	private array $analysedFiles = [];

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
	}

	public function parseFile(string $file): array
	{
		$normalizedPath = $this->fileHelper->normalizePath($file, '/');
		if (strpos($normalizedPath, 'vendor/jetbrains/phpstorm-stubs') !== false) {
			return $this->php8Parser->parseFile($file);
		}
		if (strpos($normalizedPath, 'vendor/phpstan/php-8-stubs/stubs') !== false) {
			return $this->php8Parser->parseFile($file);
		}

		$file = $this->fileHelper->normalizePath($file);
		if (!isset($this->analysedFiles[$file]) && $file !== $this->singleReflectionFile) {
			return $this->currentPhpVersionSimpleParser->parseFile($file);
		}

		return $this->currentPhpVersionRichParser->parseFile($file);
	}

	public function parseString(string $sourceCode): array
	{
		return $this->currentPhpVersionSimpleParser->parseString($sourceCode);
	}

}
