<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use Nette\Utils\Strings;
use PHPStan\Analyser\Error;
use PHPStan\File\FileExcluder;
use PHPStan\File\FileHelper;
use PHPStan\ShouldNotHappenException;
use function count;
use function implode;
use function is_array;
use function preg_quote;
use function sprintf;
use function str_replace;

final class IgnoredError
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

		$message = '';
		if (isset($ignoredError['message'])) {
			$message = $ignoredError['message'];
		}
		if (isset($ignoredError['identifier'])) {
			if ($message === '') {
				$message = $ignoredError['identifier'];
			} else {
				$message = sprintf('%s (%s)', $message, $ignoredError['identifier']);
			}
		}

		if ($message === '') {
			throw new ShouldNotHappenException();
		}

		// ignore by path
		if (isset($ignoredError['path'])) {
			return sprintf('%s in path %s', $message, $ignoredError['path']);
		} elseif (isset($ignoredError['paths'])) {
			if (count($ignoredError['paths']) === 1) {
				return sprintf('%s in path %s', $message, implode(', ', $ignoredError['paths']));

			}
			return sprintf('%s in paths: %s', $message, implode(', ', $ignoredError['paths']));
		}

		return $message;
	}

	/**
	 * @return bool To ignore or not to ignore?
	 */
	public static function shouldIgnore(
		FileHelper $fileHelper,
		Error $error,
		?string $ignoredErrorPattern,
		?string $identifier,
		?string $path,
	): bool
	{
		if ($identifier !== null) {
			if ($error->getIdentifier() !== $identifier) {
				return false;
			}
		}

		if ($ignoredErrorPattern !== null) {
			// normalize newlines to allow working with ignore-patterns independent of used OS newline-format
			$errorMessage = $error->getMessage();
			$errorMessage = str_replace(['\r\n', '\r'], '\n', $errorMessage);
			$ignoredErrorPattern = str_replace([preg_quote('\r\n'), preg_quote('\r')], preg_quote('\n'), $ignoredErrorPattern);
			if (Strings::match($errorMessage, $ignoredErrorPattern) === null) {
				return false;
			}
		}

		if ($path !== null) {
			$fileExcluder = new FileExcluder($fileHelper, [$path]);
			$isExcluded = $fileExcluder->isExcludedFromAnalysing($error->getFilePath());
			if (!$isExcluded && $error->getTraitFilePath() !== null) {
				return $fileExcluder->isExcludedFromAnalysing($error->getTraitFilePath());
			}

			return $isExcluded;
		}

		return true;
	}

}
