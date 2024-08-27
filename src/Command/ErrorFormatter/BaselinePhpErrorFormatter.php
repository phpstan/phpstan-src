<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\DI\Helpers;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use function array_keys;
use function count;
use function implode;
use function ksort;
use function preg_quote;
use function sort;
use function sprintf;
use function var_export;
use const SORT_STRING;

final class BaselinePhpErrorFormatter
{

	public function __construct(private RelativePathHelper $relativePathHelper)
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
				if (!isset($fileErrorsByMessage[$errorMessage])) {
					$fileErrorsByMessage[$errorMessage] = [
						1,
						$error->getIdentifier() !== null ? [$error->getIdentifier() => true] : [],
					];
					continue;
				}

				$fileErrorsByMessage[$errorMessage][0]++;

				if ($error->getIdentifier() === null) {
					continue;
				}
				$fileErrorsByMessage[$errorMessage][1][$error->getIdentifier()] = true;
			}
			ksort($fileErrorsByMessage, SORT_STRING);

			foreach ($fileErrorsByMessage as $message => [$count, $identifiersInKeys]) {
				$identifiers = array_keys($identifiersInKeys);
				sort($identifiers);
				$identifiersComment = '';
				if (count($identifiers) > 0) {
					if (count($identifiers) === 1) {
						$identifiersComment = "\n\t// identifier: " . $identifiers[0];
					} else {
						$identifiersComment = "\n\t// identifiers: " . implode(', ', $identifiers);
					}
				}

				$php .= sprintf(
					"\$ignoreErrors[] = [%s\n\t'message' => %s,\n\t'count' => %d,\n\t'path' => __DIR__ . %s,\n];\n",
					$identifiersComment,
					var_export(Helpers::escape('#^' . preg_quote($message, '#') . '$#'), true),
					var_export($count, true),
					var_export(Helpers::escape($file), true),
				);
			}
		}

		$php .= "\n";
		$php .= 'return [\'parameters\' => [\'ignoreErrors\' => $ignoreErrors]];';
		$php .= "\n";

		$output->writeRaw($php);

		return 1;
	}

}
