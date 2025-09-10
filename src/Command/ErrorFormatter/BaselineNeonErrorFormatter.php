<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\DI\Helpers;
use Nette\Neon\Neon;
use Nette\Utils\Strings;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use PHPStan\ShouldNotHappenException;
use function count;
use function ksort;
use function preg_quote;
use function substr;
use const SORT_STRING;

final class BaselineNeonErrorFormatter
{

	public function __construct(private RelativePathHelper $relativePathHelper, private bool $useRawMessage)
	{
	}

	public function formatErrors(
		AnalysisResult $analysisResult,
		Output $output,
		string $existingBaselineContent,
	): int
	{
		if (!$analysisResult->hasErrors()) {
			$output->writeRaw($this->getNeon([], $existingBaselineContent));
			return 0;
		}

		$fileErrors = [];
		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			if (!$fileSpecificError->canBeIgnored()) {
				continue;
			}
			$fileErrors[$this->relativePathHelper->getRelativePath($fileSpecificError->getFilePath())][] = $fileSpecificError;
		}
		ksort($fileErrors, SORT_STRING);

		$messageKey = $this->useRawMessage ? 'rawMessage' : 'message';
		$errorsToOutput = [];
		foreach ($fileErrors as $file => $errors) {
			$fileErrorsByMessage = [];
			foreach ($errors as $error) {
				$errorMessage = $error->getMessage();
				$identifier = $error->getIdentifier();
				if (!isset($fileErrorsByMessage[$errorMessage])) {
					$fileErrorsByMessage[$errorMessage] = [
						1,
						$identifier !== null ? [$identifier => 1] : [],
					];
					continue;
				}

				$fileErrorsByMessage[$errorMessage][0]++;

				if ($identifier === null) {
					continue;
				}

				if (!isset($fileErrorsByMessage[$errorMessage][1][$identifier])) {
					$fileErrorsByMessage[$errorMessage][1][$identifier] = 1;
					continue;
				}

				$fileErrorsByMessage[$errorMessage][1][$identifier]++;
			}
			ksort($fileErrorsByMessage, SORT_STRING);

			foreach ($fileErrorsByMessage as $message => [$totalCount, $identifiers]) {
				if (!$this->useRawMessage) {
					$message = '#^' . preg_quote($message, '#') . '$#';
				}

				ksort($identifiers, SORT_STRING);
				if (count($identifiers) > 0) {
					foreach ($identifiers as $identifier => $identifierCount) {
						$errorsToOutput[] = [
							$messageKey => Helpers::escape($message),
							'identifier' => $identifier,
							'count' => $identifierCount,
							'path' => Helpers::escape($file),
						];
					}
				} else {
					$errorsToOutput[] = [
						$messageKey => Helpers::escape($message),
						'count' => $totalCount,
						'path' => Helpers::escape($file),
					];
				}
			}
		}

		$output->writeRaw($this->getNeon($errorsToOutput, $existingBaselineContent));

		return 1;
	}

	/**
	 * @param array<int, array<string, string|int>> $ignoreErrors
	 */
	private function getNeon(array $ignoreErrors, string $existingBaselineContent): string
	{
		$neon = Neon::encode([
			'parameters' => [
				'ignoreErrors' => $ignoreErrors,
			],
		], Neon::BLOCK);

		if (substr($neon, -2) !== "\n\n") {
			throw new ShouldNotHappenException();
		}

		if ($existingBaselineContent === '') {
			return substr($neon, 0, -1);
		}

		$existingBaselineContentEndOfFileNewlinesMatches = Strings::match($existingBaselineContent, "~(\n)+$~");
		$existingBaselineContentEndOfFileNewlines = $existingBaselineContentEndOfFileNewlinesMatches !== null
			? $existingBaselineContentEndOfFileNewlinesMatches[0]
			: '';

		return substr($neon, 0, -2) . $existingBaselineContentEndOfFileNewlines;
	}

}
