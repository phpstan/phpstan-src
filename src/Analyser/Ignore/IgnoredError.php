<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use Nette\Utils\Strings;
use PHPStan\Analyser\Error;
use PHPStan\File\FileExcluder;
use PHPStan\File\FileHelper;
use function count;
use function implode;
use function is_array;
use function preg_quote;
use function sprintf;
use function str_replace;

class IgnoredError
{

	/**
	 * @param mixed[]|string $ignoredError
	 * @return string Representation of the ignored error
	 */
	public static function stringifyPattern($ignoredError): string
	{
		if (!is_array($ignoredError)) {
			return $ignoredError;
		}

		// ignore by path
		if (isset($ignoredError['path'])) {
			return sprintf('%s in path %s', $ignoredError['message'], $ignoredError['path']);
		} elseif (isset($ignoredError['paths'])) {
			if (count($ignoredError['paths']) === 1) {
				return sprintf('%s in path %s', $ignoredError['message'], implode(', ', $ignoredError['paths']));

			}
			return sprintf('%s in paths: %s', $ignoredError['message'], implode(', ', $ignoredError['paths']));
		}

		return $ignoredError['message'];
	}

	/**
	 * @return bool To ignore or not to ignore?
	 */
	public static function shouldIgnore(
		FileHelper $fileHelper,
		Error $error,
		string $ignoredErrorPattern,
		?string $path,
	): bool
	{
		// normalize newlines to allow working with ignore-patterns independent of used OS newline-format
		$errorMessage = $error->getMessage();
		$errorMessage = str_replace(['\r\n', '\r'], '\n', $errorMessage);
		$ignoredErrorPattern = str_replace([preg_quote('\r\n'), preg_quote('\r')], preg_quote('\n'), $ignoredErrorPattern);

		if ($path !== null) {
			if (Strings::match($errorMessage, $ignoredErrorPattern) === null) {
				return false;
			}

			$fileExcluder = new FileExcluder($fileHelper, [$path]);
			$isExcluded = $fileExcluder->isExcludedFromAnalysing($error->getFilePath());
			if (!$isExcluded && $error->getTraitFilePath() !== null) {
				return $fileExcluder->isExcludedFromAnalysing($error->getTraitFilePath());
			}

			return $isExcluded;
		}

		return Strings::match($errorMessage, $ignoredErrorPattern) !== null;
	}

}
