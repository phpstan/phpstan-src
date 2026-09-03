<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use function array_merge;
use function array_values;
use function dirname;
use function explode;
use function get_include_path;
use const PATH_SEPARATOR;

/**
 * The absolute paths an include/require of a given path could resolve to, in the order PHP would try
 * them. `stream_resolve_include_path()` cannot be used: it resolves against the running script, and
 * what matters is the analysed file.
 */
#[AutowiredService]
final class IncludedFilePathResolver
{

	public function __construct(
		#[AutowiredParameter]
		private string $currentWorkingDirectory,
		private FileHelper $fileHelper,
	)
	{
	}

	/**
	 * @return list<string>
	 */
	public function resolve(string $path, Scope $scope): array
	{
		$directories = array_merge(
			[$this->currentWorkingDirectory],
			explode(PATH_SEPARATOR, get_include_path()),
			[dirname($this->getScopeFile($scope))],
		);

		$candidatePaths = [];
		foreach ($directories as $directory) {
			if ($directory === '') {
				continue;
			}

			$candidatePath = (new FileHelper($directory))->absolutizePath($path);
			$candidatePaths[$candidatePath] = $candidatePath;
		}

		return array_values($candidatePaths);
	}

	/**
	 * Both `__DIR__` and the "calling script's own directory" fallback of a relative include are
	 * resolved at compile time, so inside a trait they point at the file the trait is declared in - not
	 * at the file of the class that uses it, which is what Scope::getFile() returns in a trait context.
	 */
	private function getScopeFile(Scope $scope): string
	{
		if ($scope->isInTrait()) {
			$traitFileName = $scope->getTraitReflection()->getFileName();
			if ($traitFileName !== null) {
				return $this->fileHelper->normalizePath($traitFileName);
			}
		}

		return $scope->getFile();
	}

}
