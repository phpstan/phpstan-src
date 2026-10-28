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
		$parser = $this->getParser($file);
		if ($parser === $this->php8Parser) {
			return $parser->parseFile($file);
		}

		return $parser->parseFile($this->fileHelper->normalizePath($file));
	}

	/**
	 * Whether parseFile() returns the simplified AST of the file, without function bodies
	 * and code for other PHP versions, instead of the full AST of an analysed file.
	 */
	public function returnsSimplifiedAst(string $file): bool
	{
		return $this->getParser($file) === $this->currentPhpVersionSimpleParser;
	}

	private function getParser(string $file): Parser
	{
		$normalizedPath = $this->fileHelper->normalizePath($file, '/');
		if (str_contains($normalizedPath, 'vendor/jetbrains/phpstorm-stubs')) {
			return $this->php8Parser;
		}
		if (str_contains($normalizedPath, 'vendor/phpstan/php-8-stubs/stubs')) {
			return $this->php8Parser;
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
						return $this->currentPhpVersionRichParser;
					}
				}
				break;
			}

			return $this->currentPhpVersionSimpleParser;
		}

		return $this->currentPhpVersionRichParser;
	}

	public function parseString(string $sourceCode): array
	{
		return $this->currentPhpVersionSimpleParser->parseString($sourceCode);
	}

}
