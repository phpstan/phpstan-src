<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PHPStan\File\FileHelper;

class PathRoutingParser implements Parser
{

	private FileHelper $fileHelper;

	private Parser $currentPhpVersionRichParser;

	private Parser $currentPhpVersionSimpleParser;

	private Parser $php8Parser;

	public function __construct(
		FileHelper $fileHelper,
		Parser $currentPhpVersionRichParser,
		Parser $currentPhpVersionSimpleParser,
		Parser $php8Parser
	)
	{
		$this->fileHelper = $fileHelper;
		$this->currentPhpVersionRichParser = $currentPhpVersionRichParser;
		$this->currentPhpVersionSimpleParser = $currentPhpVersionSimpleParser;
		$this->php8Parser = $php8Parser;
	}

	public function parseFile(string $file): array
	{
		$file = $this->fileHelper->normalizePath($file, '/');
		if (strpos($file, 'vendor/jetbrains/phpstorm-stubs') !== false) {
			return $this->php8Parser->parseFile($file);
		}

		return $this->currentPhpVersionRichParser->parseFile($file);
	}

	public function parseString(string $sourceCode): array
	{
		return $this->currentPhpVersionRichParser->parseString($sourceCode);
	}

}
