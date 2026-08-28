<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Override;
use PHPStan\DependencyInjection\Neon\OptionalPath;
use PHPStan\File\FileHelper;
use function array_key_exists;
use function array_shift;
use function is_array;
use function is_string;
use function preg_match_all;

/**
 * NeonAdapter absolutizes and normalizes the path entries listed in the `expandRelativePaths` section,
 * but only the ones that do not contain a `%placeholder%` - those are still unexpanded strings at load
 * time. A path like `%rootDir%/../../../vendor/autoload.php` therefore reaches the container with its
 * `..` segments intact, and every consumer sees a different spelling of the same file than it would for
 * the equivalent placeholder-free entry.
 *
 * The parameters are fully expanded by the time any extension's loadConfiguration() runs, so this is the
 * first point where the same normalization can be applied. Doing it here rather than at each consumer
 * keeps a single spelling of every configured path in the container - which is what the result cache
 * compares its stored metadata against, see https://github.com/phpstan/phpstan/issues/15125
 */
#[ContainerExtension(name: 'normalizePathParameters')]
final class NormalizePathParametersExtension extends CompilerExtension
{

	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$fileHelper = new FileHelper($builder->parameters['currentWorkingDirectory']);

		foreach ($this->getExpandRelativePaths() as $configKey) {
			if (preg_match_all('~\[([^\]]*)\]~', $configKey, $matches) === 0) {
				continue;
			}

			$segments = $matches[1];
			if (array_shift($segments) !== 'parameters') {
				continue;
			}

			$parameters = $builder->parameters;
			$this->normalizeAtPath($parameters, $segments, $fileHelper);
			$builder->parameters = $parameters;
		}
	}

	/**
	 * @return list<string>
	 */
	private function getExpandRelativePaths(): array
	{
		$configKeys = [];
		foreach ($this->compiler->getExtensions(ExpandRelativePathExtension::class) as $extension) {
			foreach ($extension->getConfig() as $configKey) {
				if (!is_string($configKey)) {
					continue;
				}

				$configKeys[] = $configKey;
			}
		}

		return $configKeys;
	}

	/**
	 * @param mixed[] $value
	 * @param list<string> $segments an empty segment stands for "every element of this list"
	 */
	private function normalizeAtPath(array &$value, array $segments, FileHelper $fileHelper): void
	{
		$segment = array_shift($segments);
		if ($segment === null) {
			return;
		}

		if ($segment === '') {
			foreach ($value as $key => $item) {
				$value[$key] = $this->normalizeItem($item, $segments, $fileHelper);
			}

			return;
		}

		if (!array_key_exists($segment, $value)) {
			return;
		}

		$value[$segment] = $this->normalizeItem($value[$segment], $segments, $fileHelper);
	}

	/**
	 * @param list<string> $segments
	 */
	private function normalizeItem(mixed $item, array $segments, FileHelper $fileHelper): mixed
	{
		if ($segments !== []) {
			if (is_array($item)) {
				$this->normalizeAtPath($item, $segments, $fileHelper);
			}

			return $item;
		}

		if ($item instanceof OptionalPath) {
			return new OptionalPath($this->normalizePath($item->path, $fileHelper));
		}

		if (!is_string($item)) {
			return $item;
		}

		return $this->normalizePath($item, $fileHelper);
	}

	private function normalizePath(string $path, FileHelper $fileHelper): string
	{
		// A path that is still relative here is either an fnmatch pattern or came from a placeholder that
		// expanded to a relative value; there is no config file left to resolve it against, and
		// normalizePath() would silently drop its leading '..' segments. absolutizePath() returning the
		// path unchanged is what "already absolute" means to the rest of PHPStan, including `scheme://` URLs.
		if ($fileHelper->absolutizePath($path) !== $path) {
			return $path;
		}

		return $fileHelper->normalizePath($path);
	}

}
