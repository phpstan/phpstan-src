<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\DI\Helpers;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
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
			$fileErrors['/' . $this->relativePathHelper->getRelativePath($fileSpecificError->getFilePath())][] = $fileSpecificError;
		}
		ksort($fileErrors, SORT_STRING);

		$php = '<?php declare(strict_types = 1);';
		$php .= "\n\n";
		$php .= '$ignoreErrors = [];';
		$php .= "\n";
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
				if ($this->useRawMessage) {
					$messageKey = 'rawMessage';
				} else {
					$messageKey = 'message';
					$message = '#^' . preg_quote($message, '#') . '$#';
				}

				ksort($identifiers, SORT_STRING);
				if (count($identifiers) > 0) {
					foreach ($identifiers as $identifier => $identifierCount) {
						$php .= sprintf(
							"\$ignoreErrors[] = [\n\t%s => %s,\n\t'identifier' => %s,\n\t'count' => %s,\n\t'path' => __DIR__ . %s,\n];\n",
							var_export($messageKey, true),
							var_export(Helpers::escape($message), true),
							var_export(Helpers::escape($identifier), true),
							var_export($identifierCount, true),
							var_export(Helpers::escape($file), true),
						);
					}
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

		$php .= "\n";
		$php .= 'return [\'parameters\' => [\'ignoreErrors\' => $ignoreErrors]];';
		$php .= "\n";

		$output->writeRaw($php);

		return 1;
	}

}
