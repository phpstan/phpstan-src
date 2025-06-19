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
use function array_values;
use function count;
use function dirname;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function ltrim;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function substr;

final class NeonAdapter implements Adapter
{

	public const CACHE_KEY = 'v31-expand-relative-paths';

	private const PREVENT_MERGING_SUFFIX = '!';

	/** @var FileHelper[] */
	private array $fileHelpers = [];

	/**
	 * @param list<string> $expandRelativePaths
	 */
	public function __construct(private array $expandRelativePaths)
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
				$key = substr($key, 0, -1);
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
						$tmp = new Statement(
							$tmp === null ? $st->getEntity() : [$tmp, ltrim(implode('::', (array) $st->getEntity()), ':')],
							$st->arguments,
						);
					}
					$val = $tmp;
				} else {
					if (
						in_array($keyToResolve, [
							'[parameters][excludePaths][]',
							'[parameters][excludePaths][analyse][]',
							'[parameters][excludePaths][analyseAndScan][]',
						], true)
						&& count($val->attributes) === 1
						&& $val->attributes[0] === '?'
						&& is_string($val->value)
						&& !str_contains($val->value, '%')
						&& !str_starts_with($val->value, '*')
					) {
						$fileHelper = $this->createFileHelperByFile($file);
						$val = new OptionalPath($fileHelper->normalizePath($fileHelper->absolutizePath($val->value)));
					} else {
						$tmp = $this->process([$val->value], $fileKeyToPass, $file);
						$val = new Statement($tmp[0], $this->process($val->attributes, $fileKeyToPass, $file));
					}
				}
			}

			if (in_array($keyToResolve, $this->expandRelativePaths, true) && is_string($val) && !str_contains($val, '%') && !str_starts_with($val, '*')) {
				$fileHelper = $this->createFileHelperByFile($file);
				$val = $fileHelper->normalizePath($fileHelper->absolutizePath($val));
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

	private function createFileHelperByFile(string $file): FileHelper
	{
		$dir = dirname($file);
		if (!isset($this->fileHelpers[$dir])) {
			$this->fileHelpers[$dir] = new FileHelper($dir);
		}

		return $this->fileHelpers[$dir];
	}

}
