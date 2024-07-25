<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Exception;
use function implode;
use function sprintf;

final class DuplicateIncludedFilesException extends Exception
{

	/**
	 * @param string[] $files
	 */
	public function __construct(private array $files)
	{
		parent::__construct(sprintf('These files are included multiple times: %s', implode(', ', $this->files)));
	}

	/**
	 * @return string[]
	 */
	public function getFiles(): array
	{
		return $this->files;
	}

}
