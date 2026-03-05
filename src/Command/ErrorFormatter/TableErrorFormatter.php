<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalyseCommand;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\RelativePathHelper;
use PHPStan\File\SimpleRelativePathHelper;
use Symfony\Component\Console\Formatter\OutputFormatter;
use function array_map;
use function array_slice;
use function count;
use function explode;
use function getenv;
use function implode;
use function in_array;
use function is_string;
use function ltrim;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_replace;

#[AutowiredService(name: 'errorFormatter.table')]
final class TableErrorFormatter implements ErrorFormatter
{

	private const MAX_ERRORS_TO_SHOW = 1000;

	public function __construct(
		private RelativePathHelper $relativePathHelper,
		#[AutowiredParameter(ref: '@simpleRelativePathHelper')]
		private SimpleRelativePathHelper $simpleRelativePathHelper,
		private CiDetectedErrorFormatter $ciDetectedErrorFormatter,
		#[AutowiredParameter(ref: '%tipsOfTheDay%')]
		private bool $showTipsOfTheDay,
		#[AutowiredParameter]
		private ?string $editorUrl,
		#[AutowiredParameter]
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
						(int) AnalyseCommand::DEFAULT_LEVEL,
					));
					$output->writeLineFormatted('');
				}
			}

			return 0;
		}

		/** @var array<string, Error[]> $fileErrors */
		$fileErrors = [];
		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			if (!isset($fileErrors[$fileSpecificError->getFile()])) {
				$fileErrors[$fileSpecificError->getFile()] = [];
			}

			$fileErrors[$fileSpecificError->getFile()][] = $fileSpecificError;
		}

		$errorsBudget = getenv('PHPSTAN_ERRORS_LIMIT');
		if ($errorsBudget === false) {
			$errorsBudget = self::MAX_ERRORS_TO_SHOW;
		}
		$errorsBudget = (int) $errorsBudget;

		$printedErrors = 0;
		foreach ($fileErrors as $file => $errors) {
			$rows = [];
			foreach ($errors as $error) {
				$message = $error->getMessage();
				$filePath = $error->getTraitFilePath() ?? $error->getFilePath();
				if ($error->getIdentifier() !== null) {
					$message .= "\n";
					$message .= '🪪  ' . $error->getIdentifier();
					if (!$error->canBeIgnored()) {
						$message .= ' <fg=red>(non-ignorable)</>';
					}
				}
				if ($error->getTip() !== null) {
					$tip = $error->getTip();
					$tip = str_replace('%configurationFile%', $projectConfigFile, $tip);

					$message .= "\n";
					if (str_contains($tip, "\n")) {
						$lines = explode("\n", $tip);
						foreach ($lines as $line) {
							$message .= '💡  ' . ltrim($line, ' •') . "\n";
						}
						$message = rtrim($message, "\n");
					} else {
						$message .= '💡  ' . $tip;
					}
				}

				if (getenv('TERMINAL_EMULATOR') === 'JetBrains-JediTerm') {
					$title = $this->simpleRelativePathHelper->getRelativePath($filePath);
					$message .= sprintf("\nat %s:%d", $title, $error->getLine() ?? 0);

				} elseif (is_string($this->editorUrl)) {
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

				if (
					$error->getIdentifier() !== null
					&& in_array($error->getIdentifier(), ['phpstan.type', 'phpstan.nativeType', 'phpstan.variable', 'phpstan.dumpType', 'phpstan.unknownExpectation'], true)
				) {
					$message = '<fg=red>' . $message . '</>';
				}

				$rows[] = [
					$this->formatLineNumber($error->getLine()),
					$message,
				];
			}

			$printedErrors += count($rows);
			if ($errorsBudget > 0 && $printedErrors > $errorsBudget) {
				$rows = array_slice($rows, 0, $errorsBudget - ($printedErrors - count($rows)));

				$style->table(['Line', $this->relativePathHelper->getRelativePath($file)], $rows);
				break;
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

		if ($errorsBudget > 0 && $printedErrors > $errorsBudget) {
			$note = [];
			$note[] = sprintf('Result is limited to the first %d errors', $errorsBudget);
			$note[] = '- Consider lowering the PHPStan level';
			$note[] = '- Consider using PHPStan Pro for more comfortable error browsing';
			$note[] = '- Pass PHPSTAN_ERRORS_LIMIT=0 environment variable to show all errors';
			$style->note(implode("\n", $note));
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
