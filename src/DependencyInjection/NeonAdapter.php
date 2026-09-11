<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Config\Adapter;
use Nette\DI\Config\Helpers;
use Nette\DI\Definitions\Statement;
use Nette\DI\InvalidConfigurationException;
use Nette\Neon\Entity;
use Nette\Neon\Exception;
use Nette\Neon\Neon;
use Override;
use PHPStan\DependencyInjection\Neon\OptionalPath;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use function array_key_exists;
use function array_values;
use function count;
use function dirname;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function ltrim;
use function preg_replace_callback;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function substr;

final class NeonAdapter implements Adapter
{

	public const CACHE_KEY = 'v33-known-placeholders-expanded';

	private const PREVENT_MERGING_SUFFIX = '!';

	/** @var FileHelper[] */
	private array $fileHelpers = [];

	/**
	 * @param list<string> $expandRelativePaths
	 * @param array<string, mixed> $parameters the parameters already known when the config is loaded
	 *                                         (rootDir, currentWorkingDirectory, env), see LoaderFactory
	 */
	public function __construct(private array $expandRelativePaths, private array $parameters = [])
	{
	}

	/**
	 * @return mixed[]
	 */
	#[Override]
	public function load(string $file): array
	{
		$contents = FileReader::read($file);
		try {
			return $this->process((array) Neon::decode($contents), '', $file);
		} catch (Exception $e) {
			throw new Exception(sprintf('Error while loading %s: %s', $file, $e->getMessage()));
		}
	}

	/**
	 * @param mixed[] $arr
	 * @return mixed[]
	 */
	public function process(array $arr, string $fileKey, string $file): array
	{
		$res = [];
		foreach ($arr as $key => $val) {
			if (is_string($key) && substr($key, -1) === self::PREVENT_MERGING_SUFFIX) {
				if (!is_array($val) && $val !== null) {
					throw new InvalidConfigurationException(sprintf('Replacing operator is available only for arrays, item \'%s\' is not array.', $key));
				}
				$key = substr($key, 0, -1) ?: '';
				$val[Helpers::PREVENT_MERGING] = true;
			}

			$keyToResolve = $fileKey;
			if (is_int($key)) {
				$keyToResolve .= '[]';
			} else {
				$keyToResolve .= '[' . $key . ']';
			}

			if (is_array($val)) {
				if (!is_int($key)) {
					$fileKeyToPass = $fileKey . '[' . $key . ']';
				} else {
					$fileKeyToPass = $fileKey . '[]';
				}
				$val = $this->process($val, $fileKeyToPass, $file);

			} elseif ($val instanceof Entity) {
				if (!is_int($key)) {
					$fileKeyToPass = $fileKey . '(' . $key . ')';
				} else {
					$fileKeyToPass = $fileKey . '()';
				}
				if ($val->value === Neon::CHAIN) {
					$tmp = null;
					foreach ($this->process($val->attributes, $fileKeyToPass, $file) as $st) {
						/** @var Statement $st */
						$tmp = new Statement(
							$tmp === null ? $st->getEntity() : [$tmp, ltrim(implode('::', (array) $st->getEntity()), ':')],
							$st->arguments,
						);
					}
					$val = $tmp;
				} else {
					$optionalPath = $this->createOptionalPath($keyToResolve, $val, $file);
					if ($optionalPath !== null) {
						$val = $optionalPath;
					} else {
						$tmp = $this->process([$val->value], $fileKeyToPass, $file);
						$val = new Statement($tmp[0], $this->process($val->attributes, $fileKeyToPass, $file));
					}
				}
			}

			if (in_array($keyToResolve, $this->expandRelativePaths, true) && is_string($val) && !str_starts_with($val, '*')) {
				$path = $this->expandKnownParameters($val);
				if ($path !== null) {
					$fileHelper = $this->createFileHelperByFile($file);
					$val = $fileHelper->normalizePath($fileHelper->absolutizePath($path));
				}
			}

			if (
				$keyToResolve === '[parameters][excludePaths]'
				&& $val !== null
				&& array_values($val) === $val
			) {
				$val = ['analyseAndScan' => $val, 'analyse' => []];
			}

			$res[$key] = $val;
		}
		return $res;
	}

	/**
	 * `- path (?)` in excludePaths marks the path optional. It becomes an OptionalPath when it can be
	 * resolved right here, like a plain path entry; otherwise the entity is processed as a statement.
	 */
	private function createOptionalPath(string $keyToResolve, Entity $entity, string $file): ?OptionalPath
	{
		if (
			!in_array($keyToResolve, [
				'[parameters][excludePaths][]',
				'[parameters][excludePaths][analyse][]',
				'[parameters][excludePaths][analyseAndScan][]',
			], true)
			|| count($entity->attributes) !== 1
			|| $entity->attributes[0] !== '?'
			|| !is_string($entity->value)
			|| str_starts_with($entity->value, '*')
		) {
			return null;
		}

		$path = $this->expandKnownParameters($entity->value);
		if ($path === null) {
			return null;
		}

		$fileHelper = $this->createFileHelperByFile($file);

		return new OptionalPath($fileHelper->normalizePath($fileHelper->absolutizePath($path)));
	}

	/**
	 * Expands the placeholders whose values are known before the container is compiled - rootDir,
	 * currentWorkingDirectory and env - so a path written through them is absolutized and normalized
	 * like a plain one. Returns null for a value with a placeholder this cannot resolve (a parameter
	 * the config defines itself, an unset env variable, the %% escape): that one is left to the DI
	 * compiler as written.
	 */
	private function expandKnownParameters(string $value): ?string
	{
		if (!str_contains($value, '%')) {
			return $value;
		}

		$unresolved = false;
		$expanded = preg_replace_callback('~%([\w.-]*)%~', function (array $matches) use (&$unresolved): string {
			$parameter = $this->getKnownParameter($matches[1]);
			if ($parameter === null) {
				$unresolved = true;

				return $matches[0];
			}

			return $parameter;
		}, $value);

		if ($unresolved || $expanded === null) {
			return null;
		}

		return $expanded;
	}

	/**
	 * @param string $name a parameter name as written between the percent signs, `env.HOME` for a nested one
	 */
	private function getKnownParameter(string $name): ?string
	{
		$value = $this->parameters;
		foreach (explode('.', $name) as $key) {
			if (!is_array($value) || !array_key_exists($key, $value)) {
				return null;
			}
			$value = $value[$key];
		}

		return is_string($value) ? $value : null;
	}

	private function createFileHelperByFile(string $file): FileHelper
	{
		$dir = dirname($file);
		if (!isset($this->fileHelpers[$dir])) {
			$this->fileHelpers[$dir] = new FileHelper($dir);
		}

		return $this->fileHelpers[$dir];
	}

}
