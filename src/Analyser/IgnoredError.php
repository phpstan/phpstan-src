<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\File\FileExcluder;
use PHPStan\File\FileHelper;

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
	 * @param FileHelper $fileHelper
	 * @param Error $error
	 * @param string $ignoredErrorPattern
	 * @param string|null $path
	 * @return bool To ignore or not to ignore?
	 */
	public static function shouldIgnore(
		FileHelper $fileHelper,
		Error $error,
		string $ignoredErrorPattern,
		?string $path
	): bool
	{
		// normalize newlines to allow working with ignore-patterns independent of used OS newline-format
		$errorMessage = $error->getMessage();
		$errorMessage = str_replace(['\r\n', '\r'], '\n', $errorMessage);
		$ignoredErrorPattern = str_replace([preg_quote('\r\n'), preg_quote('\r')], preg_quote('\n'), $ignoredErrorPattern);

		if ($path !== null) {
			if (\Nette\Utils\Strings::match($errorMessage, $ignoredErrorPattern) === null) {
				return false;
			}

			$fileExcluder = new FileExcluder($fileHelper, [$path], []);
			$isExcluded = $fileExcluder->isExcludedFromAnalysing($error->getFilePath());
			if (!$isExcluded && $error->getTraitFilePath() !== null) {
				return $fileExcluder->isExcludedFromAnalysing($error->getTraitFilePath());
			}

			return $isExcluded;
		}

		return \Nette\Utils\Strings::match($errorMessage, $ignoredErrorPattern) !== null;
	}

}
