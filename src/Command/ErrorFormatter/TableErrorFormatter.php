<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalyseCommand;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use PHPStan\File\SimpleRelativePathHelper;
use Symfony\Component\Console\Formatter\OutputFormatter;
use function array_key_exists;
use function array_map;
use function count;
use function explode;
use function getenv;
use function in_array;
use function is_string;
use function ltrim;
use function sprintf;
use function str_contains;
use function str_replace;

class TableErrorFormatter implements ErrorFormatter
{

	public function __construct(
		private RelativePathHelper $relativePathHelper,
		private SimpleRelativePathHelper $simpleRelativePathHelper,
		private CiDetectedErrorFormatter $ciDetectedErrorFormatter,
		private bool $showTipsOfTheDay,
		private ?string $editorUrl,
		private ?string $editorUrlTitle,
	)
	{
	}

	/** @api */
	public function formatErrors(
		AnalysisResult $analysisResult,
		Output $output,
	): int
	{
		$this->ciDetectedErrorFormatter->formatErrors($analysisResult, $output);
		$projectConfigFile = 'phpstan.neon';
		if ($analysisResult->getProjectConfigFile() !== null) {
			$projectConfigFile = $this->relativePathHelper->getRelativePath($analysisResult->getProjectConfigFile());
		}

		$style = $output->getStyle();

		if (!$analysisResult->hasErrors() && !$analysisResult->hasWarnings()) {
			$style->success('No errors');

			if ($this->showTipsOfTheDay) {
				if ($analysisResult->isDefaultLevelUsed()) {
					$output->writeLineFormatted('💡 Tip of the Day:');
					$output->writeLineFormatted(sprintf(
						"PHPStan is performing only the most basic checks.\nYou can pass a higher rule level through the <fg=cyan>--%s</> option\n(the default and current level is %d) to analyse code more thoroughly.",
						AnalyseCommand::OPTION_LEVEL,
						AnalyseCommand::DEFAULT_LEVEL,
					));
					$output->writeLineFormatted('');
				}
			}

			return 0;
		}

		/** @var array<string, Error[]> $fileErrors */
		$fileErrors = [];
		$outputIdentifiers = $output->isVerbose();
		$outputIdentifiersInFile = [];
		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			if (!isset($fileErrors[$fileSpecificError->getFile()])) {
				$fileErrors[$fileSpecificError->getFile()] = [];
			}

			$fileErrors[$fileSpecificError->getFile()][] = $fileSpecificError;
			if ($outputIdentifiers) {
				continue;
			}

			$filePath = $fileSpecificError->getTraitFilePath() ?? $fileSpecificError->getFilePath();
			if (array_key_exists($filePath, $outputIdentifiersInFile)) {
				continue;
			}

			if ($fileSpecificError->getIdentifier() === null) {
				continue;
			}

			if (!in_array($fileSpecificError->getIdentifier(), [
				'ignore.unmatchedIdentifier',
				'ignore.parseError',
				'ignore.unmatched',
			], true)) {
				continue;
			}

			$outputIdentifiersInFile[$filePath] = true;
		}

		foreach ($fileErrors as $file => $errors) {
			$rows = [];
			foreach ($errors as $error) {
				$message = $error->getMessage();
				$filePath = $error->getTraitFilePath() ?? $error->getFilePath();
				if (($outputIdentifiers || array_key_exists($filePath, $outputIdentifiersInFile)) && $error->getIdentifier() !== null && $error->canBeIgnored()) {
					$message .= "\n";
					$message .= '🪪  ' . $error->getIdentifier();
				}
				if ($error->getTip() !== null) {
					$tip = $error->getTip();
					$tip = str_replace('%configurationFile%', $projectConfigFile, $tip);

					$message .= "\n";
					if (str_contains($tip, "\n")) {
						$lines = explode("\n", $tip);
						foreach ($lines as $line) {
							$message .= '💡 ' . ltrim($line, ' •') . "\n";
						}
					} else {
						$message .= '💡 ' . $tip;
					}
				}
				if (is_string($this->editorUrl)) {
					$url = str_replace(
						['%file%', '%relFile%', '%line%'],
						[$filePath, $this->simpleRelativePathHelper->getRelativePath($filePath), (string) $error->getLine()],
						$this->editorUrl,
					);

					if (is_string($this->editorUrlTitle)) {
						$title = str_replace(
							['%file%', '%relFile%', '%line%'],
							[$filePath, $this->simpleRelativePathHelper->getRelativePath($filePath), (string) $error->getLine()],
							$this->editorUrlTitle,
						);
					} else {
						$title = $this->relativePathHelper->getRelativePath($filePath);
					}

					$message .= "\n✏️  <href=" . OutputFormatter::escape($url) . '>' . $title . '</>';
				}
				$rows[] = [
					$this->formatLineNumber($error->getLine()),
					$message,
				];
			}

			$style->table(['Line', $this->relativePathHelper->getRelativePath($file)], $rows);
		}

		if (count($analysisResult->getNotFileSpecificErrors()) > 0) {
			$style->table(['', 'Error'], array_map(static fn (string $error): array => ['', OutputFormatter::escape($error)], $analysisResult->getNotFileSpecificErrors()));
		}

		$warningsCount = count($analysisResult->getWarnings());
		if ($warningsCount > 0) {
			$style->table(['', 'Warning'], array_map(static fn (string $warning): array => ['', OutputFormatter::escape($warning)], $analysisResult->getWarnings()));
		}

		$finalMessage = sprintf($analysisResult->getTotalErrorsCount() === 1 ? 'Found %d error' : 'Found %d errors', $analysisResult->getTotalErrorsCount());
		if ($warningsCount > 0) {
			$finalMessage .= sprintf($warningsCount === 1 ? ' and %d warning' : ' and %d warnings', $warningsCount);
		}

		if ($analysisResult->getTotalErrorsCount() > 0) {
			$style->error($finalMessage);
		} else {
			$style->warning($finalMessage);
		}

		return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
	}

	private function formatLineNumber(?int $lineNumber): string
	{
		if ($lineNumber === null) {
			return '';
		}

		$isRunningInVSCodeTerminal = getenv('TERM_PROGRAM') === 'vscode';
		if ($isRunningInVSCodeTerminal) {
			return ':' . $lineNumber;
		}

		return (string) $lineNumber;
	}

}
