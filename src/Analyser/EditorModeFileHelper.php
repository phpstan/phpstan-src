<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * Editor mode (--tmp-file / --instead-of) analyses the content of the temp file
 * but reports it under the real (--instead-of) path, so that Scope::getFile() and
 * anything derived from it matches a normal analysis run.
 *
 * Internal machinery that reads/parses/reflects the file by its Scope path still
 * needs the temp file (its content, its reflection, its cache identity). This helper
 * maps a reported path back to the file whose content is actually analysed.
 */
#[AutowiredService]
final class EditorModeFileHelper
{

	public function __construct(
		#[AutowiredParameter]
		private ?string $singleReflectionFile,
		#[AutowiredParameter]
		private ?string $singleReflectionInsteadOfFile,
	)
	{
	}

	/**
	 * Maps the reported (real) file path back to the file whose content is analysed.
	 * Returns $reportedFile unchanged outside of editor mode.
	 */
	public function getAnalysedFile(string $reportedFile): string
	{
		if (
			$this->singleReflectionFile !== null
			&& $this->singleReflectionInsteadOfFile !== null
			&& $reportedFile === $this->singleReflectionInsteadOfFile
		) {
			return $this->singleReflectionFile;
		}

		return $reportedFile;
	}

}
