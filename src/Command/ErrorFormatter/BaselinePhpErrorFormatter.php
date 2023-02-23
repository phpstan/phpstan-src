<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\DI\Helpers;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use function ksort;
use function preg_quote;
use function sprintf;
use function var_export;
use const SORT_STRING;

class BaselinePhpErrorFormatter
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
			$fileErrors['/' . $this->relativePathHelper->getRelativePath($fileSpecificError->getFilePath())][] = $fileSpecificError->getMessage();
		}
		ksort($fileErrors, SORT_STRING);

		$php = '<?php declare(strict_types = 1);';
		$php .= "\n\n";
		$php .= '$ignoreErrors = [];';
		$php .= "\n";
		foreach ($fileErrors as $file => $errorMessages) {
			$fileErrorsCounts = [];
			foreach ($errorMessages as $errorMessage) {
				if (!isset($fileErrorsCounts[$errorMessage])) {
					$fileErrorsCounts[$errorMessage] = 1;
					continue;
				}

				$fileErrorsCounts[$errorMessage]++;
			}
			ksort($fileErrorsCounts, SORT_STRING);

			foreach ($fileErrorsCounts as $message => $count) {
				$template = <<<'PHP'
$ignoreErrors[] = [
	'message' => %s,
	'count' => %d,
	'path' => __DIR__ . %s,
];
PHP;
				$php .= sprintf(
					$template,
					var_export(Helpers::escape('#^' . preg_quote($message, '#') . '$#'), true),
					var_export($count, true),
					var_export(Helpers::escape($file), true),
				);
				$php .= "\n";
			}
		}

		$php .= "\n";
		$php .= 'return [\'parameters\' => [\'ignoreErrors\' => $ignoreErrors]];';

		$output->writeRaw($php);

		return 1;
	}

}
