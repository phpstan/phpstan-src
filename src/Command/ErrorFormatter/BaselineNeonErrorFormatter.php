<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\DI\Helpers;
use Nette\Neon\Neon;
use Nette\Utils\Strings;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use PHPStan\ShouldNotHappenException;
use function array_key_first;
use function array_unique;
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
			$traitFilePath = $fileSpecificError->getTraitFilePath();
			$fileErrors[$this->relativePathHelper->getRelativePath($fileSpecificError->getFilePath())][] = [
				'error' => $fileSpecificError,
				'origin' => $traitFilePath !== null ? $this->relativePathHelper->getRelativePath($traitFilePath) : null,
			];
		}
		ksort($fileErrors, SORT_STRING);

		$messageKey = $this->useRawMessage ? 'rawMessage' : 'message';
		$errorsToOutput = [];
		foreach ($fileErrors as $file => $fileErrorEntries) {
			$fileErrorsByMessage = [];
			foreach ($fileErrorEntries as ['error' => $error, 'origin' => $origin]) {
				$errorMessage = $error->getMessage();
				$identifier = $error->getIdentifier();
				if (!isset($fileErrorsByMessage[$errorMessage])) {
					$fileErrorsByMessage[$errorMessage] = [
						'count' => 1,
						'origins' => [$origin],
						'identifiers' => $identifier !== null ? [$identifier => ['count' => 1, 'origins' => [$origin]]] : [],
					];
					continue;
				}

				$fileErrorsByMessage[$errorMessage]['count']++;
				$fileErrorsByMessage[$errorMessage]['origins'][] = $origin;

				if ($identifier === null) {
					continue;
				}

				if (!isset($fileErrorsByMessage[$errorMessage]['identifiers'][$identifier])) {
					$fileErrorsByMessage[$errorMessage]['identifiers'][$identifier] = ['count' => 1, 'origins' => [$origin]];
					continue;
				}

				$fileErrorsByMessage[$errorMessage]['identifiers'][$identifier]['count']++;
				$fileErrorsByMessage[$errorMessage]['identifiers'][$identifier]['origins'][] = $origin;
			}
			ksort($fileErrorsByMessage, SORT_STRING);

			foreach ($fileErrorsByMessage as $message => ['count' => $totalCount, 'origins' => $messageOrigins, 'identifiers' => $identifiers]) {
				if (!$this->useRawMessage) {
					$message = '#^' . preg_quote($message, '#') . '$#';
				}

				ksort($identifiers, SORT_STRING);
				if (count($identifiers) > 0) {
					foreach ($identifiers as $identifier => ['count' => $identifierCount, 'origins' => $identifierOrigins]) {
						$uniqueOrigins = array_unique($identifierOrigins);
						$uniformOrigin = count($uniqueOrigins) === 1 && $uniqueOrigins[array_key_first($uniqueOrigins)] !== null ? $uniqueOrigins[array_key_first($uniqueOrigins)] : null;
						$entry = [
							$messageKey => Helpers::escape($message),
							'identifier' => $identifier,
							'count' => $identifierCount,
							'path' => Helpers::escape($file),
						];
						if ($uniformOrigin !== null) {
							$entry['origin'] = Helpers::escape($uniformOrigin);
						}
						$errorsToOutput[] = $entry;
					}
				} else {
					$uniqueOrigins = array_unique($messageOrigins);
					$uniformOrigin = count($uniqueOrigins) === 1 && $uniqueOrigins[array_key_first($uniqueOrigins)] !== null ? $uniqueOrigins[array_key_first($uniqueOrigins)] : null;
					$entry = [
						$messageKey => Helpers::escape($message),
						'count' => $totalCount,
						'path' => Helpers::escape($file),
					];
					if ($uniformOrigin !== null) {
						$entry['origin'] = Helpers::escape($uniformOrigin);
					}
					$errorsToOutput[] = $entry;
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
