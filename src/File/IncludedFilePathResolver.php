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
use function in_array;
use function preg_match;
use function stream_get_wrappers;
use function strtolower;
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
		if ($this->hasUnavailableStreamWrapper($path)) {
			return [];
		}

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
	 * A path like `vfs://sites/default/x.php` names a stream wrapper rather than a place on the
	 * filesystem. When that wrapper is not registered in the PHPStan process - vfsStream registers
	 * its own from a test's setUp(), which never runs here - PHP cannot stat the path at all: every
	 * is_file() on it raises "Unable to find the wrapper". Such a path has no candidates, and it
	 * cannot come to have any, so nothing downstream should keep stat'ing it - the result cache
	 * would otherwise record it as a missing file dependency and warn on every run.
	 */
	private function hasUnavailableStreamWrapper(string $path): bool
	{
		if (preg_match('~^([a-z0-9+\-.]+)://~i', $path, $matches) !== 1) {
			return false;
		}

		return !in_array(strtolower($matches[1]), stream_get_wrappers(), true);
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
