<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\DI\Helpers;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use function array_key_first;
use function array_unique;
use function count;
use function ksort;
use function preg_quote;
use function sprintf;
use function var_export;
use const SORT_STRING;

final class BaselinePhpErrorFormatter
{

	public function __construct(private RelativePathHelper $relativePathHelper, private bool $useRawMessage)
	{
	}

	public function formatErrors(
		AnalysisResult $analysisResult,
		Output $output,
	): int
	{
		if (!$analysisResult->hasErrors()) {
			$php = '<?php declare(strict_types = 1);';
			$php .= "\n\n";
			$php .= 'return [];';
			$php .= "\n";
			$output->writeRaw($php);
			return 0;
		}

		$fileErrors = [];
		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			if (!$fileSpecificError->canBeIgnored()) {
				continue;
			}
			$traitFilePath = $fileSpecificError->getTraitFilePath();
			$fileErrors['/' . $this->relativePathHelper->getRelativePath($fileSpecificError->getFilePath())][] = [
				'error' => $fileSpecificError,
				'origin' => $traitFilePath !== null ? '/' . $this->relativePathHelper->getRelativePath($traitFilePath) : null,
			];
		}
		ksort($fileErrors, SORT_STRING);

		$php = '<?php declare(strict_types = 1);';
		$php .= "\n\n";
		$php .= '$ignoreErrors = [];';
		$php .= "\n";
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
				if ($this->useRawMessage) {
					$messageKey = 'rawMessage';
				} else {
					$messageKey = 'message';
					$message = '#^' . preg_quote($message, '#') . '$#';
				}

				ksort($identifiers, SORT_STRING);
				if (count($identifiers) > 0) {
					foreach ($identifiers as $identifier => ['count' => $identifierCount, 'origins' => $identifierOrigins]) {
						$uniqueOrigins = array_unique($identifierOrigins);
						$uniformOrigin = count($uniqueOrigins) === 1 && $uniqueOrigins[array_key_first($uniqueOrigins)] !== null ? $uniqueOrigins[array_key_first($uniqueOrigins)] : null;
						if ($uniformOrigin !== null) {
							$php .= sprintf(
								"\$ignoreErrors[] = [\n\t%s => %s,\n\t'identifier' => %s,\n\t'count' => %s,\n\t'path' => __DIR__ . %s,\n\t'origin' => __DIR__ . %s,\n];\n",
								var_export($messageKey, true),
								var_export(Helpers::escape($message), true),
								var_export(Helpers::escape($identifier), true),
								var_export($identifierCount, true),
								var_export(Helpers::escape($file), true),
								var_export(Helpers::escape($uniformOrigin), true),
							);
						} else {
							$php .= sprintf(
								"\$ignoreErrors[] = [\n\t%s => %s,\n\t'identifier' => %s,\n\t'count' => %s,\n\t'path' => __DIR__ . %s,\n];\n",
								var_export($messageKey, true),
								var_export(Helpers::escape($message), true),
								var_export(Helpers::escape($identifier), true),
								var_export($identifierCount, true),
								var_export(Helpers::escape($file), true),
							);
						}
					}
				} else {
					$uniqueOrigins = array_unique($messageOrigins);
					$uniformOrigin = count($uniqueOrigins) === 1 && $uniqueOrigins[array_key_first($uniqueOrigins)] !== null ? $uniqueOrigins[array_key_first($uniqueOrigins)] : null;
					if ($uniformOrigin !== null) {
						$php .= sprintf(
							"\$ignoreErrors[] = [\n\t%s => %s,\n\t'count' => %s,\n\t'path' => __DIR__ . %s,\n\t'origin' => __DIR__ . %s,\n];\n",
							var_export($messageKey, true),
							var_export(Helpers::escape($message), true),
							var_export($totalCount, true),
							var_export(Helpers::escape($file), true),
							var_export(Helpers::escape($uniformOrigin), true),
						);
					} else {
						$php .= sprintf(
							"\$ignoreErrors[] = [\n\t%s => %s,\n\t'count' => %s,\n\t'path' => __DIR__ . %s,\n];\n",
							var_export($messageKey, true),
							var_export(Helpers::escape($message), true),
							var_export($totalCount, true),
							var_export(Helpers::escape($file), true),
						);
					}
				}
			}
		}

		$php .= "\n";
		$php .= 'return [\'parameters\' => [\'ignoreErrors\' => $ignoreErrors]];';
		$php .= "\n";

		$output->writeRaw($php);

		return 1;
	}

}
