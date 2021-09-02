<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Parser\Parser;
use PHPStan\Type\FileTypeMapper;

class StubPhpDocProviderFactory
{

	private \PHPStan\Parser\Parser $parser;

	private \PHPStan\Type\FileTypeMapper $fileTypeMapper;

	/** @var string[] */
	private array $stubFiles;

	/**
	 * @param \PHPStan\Parser\Parser $parser
	 * @param string[] $stubFiles
	 */
	public function __construct(
		Parser $parser,
		FileTypeMapper $fileTypeMapper,
		array $stubFiles
	)
	{
		$this->parser = $parser;
		$this->fileTypeMapper = $fileTypeMapper;
		$this->stubFiles = $stubFiles;
	}

	public function create(): StubPhpDocProvider
	{
		return new StubPhpDocProvider(
			$this->parser,
			$this->fileTypeMapper,
			$this->stubFiles
		);
	}

}
