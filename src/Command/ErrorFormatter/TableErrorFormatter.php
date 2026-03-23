<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalyseCommand;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\CommandHelper;
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

	public const ERRORS_LIMIT = 1000;
	private const FORCE_SHOW_ALL_ERRORS = 'PHPSTAN_TABLE_ERROR_FORMATTER_FORCE_SHOW_ALL_ERRORS';

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
		#[AutowiredParameter]
		private string $usedLevel,
		private ?int $errorsBudget = null,
	)
	{
		if ($this->errorsBudget !== null) {
			return;
		}

		$forceShowAll = getenv(self::FORCE_SHOW_ALL_ERRORS);
		if (!in_array($forceShowAll, [false, '0'], true)) {
			return;
		}

		$this->errorsBudget = self::ERRORS_LIMIT;
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

		$errorsBudget = $this->errorsBudget;
		$printedErrors = 0;
		foreach ($fileErrors as $file => $errors) {
			$rows = [];
			foreach ($errors as $error) {
				$message = $error->getMessage();
				$filePath = $error->getTraitFilePath() ?? $error->getFilePath();
				if (
					$error->getIdentifier() !== null
					&& in_array($error->getIdentifier(), ['phpstan.type', 'phpstan.nativeType', 'phpstan.variable', 'phpstan.dumpType', 'phpstan.unknownExpectation'], true)
				) {
					$message = '<fg=red>' . OutputFormatter::escape($message) . '</>';
				}
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

				$rows[] = [
					$this->formatLineNumber($error->getLine()),
					$message,
				];
			}

			$printedErrors += count($rows);
			if ($errorsBudget !== null && $printedErrors > $errorsBudget) {
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

		if ($errorsBudget !== null && $printedErrors > $errorsBudget) {
			$style->error(sprintf('Found %s+ errors', $errorsBudget));

			$note = [];
			$note[] = sprintf('Result is limited to the first %d errors', $errorsBudget);
			if ($this->usedLevel !== CommandHelper::DEFAULT_LEVEL) {
				$note[] = '- Consider lowering the PHPStan level';
			}
			$note[] = sprintf('- Pass %s=1 environment variable to show all errors', self::FORCE_SHOW_ALL_ERRORS);
			$note[] = '- Consider using PHPStan Pro for more comfortable error browsing';
			$note[] = '  Learn more: https://phpstan.com';
			$style->note(implode("\n", $note));
		} else {
			$finalMessage = sprintf($analysisResult->getTotalErrorsCount() === 1 ? 'Found %d error' : 'Found %d errors', $analysisResult->getTotalErrorsCount());
			if ($warningsCount > 0) {
				$finalMessage .= sprintf($warningsCount === 1 ? ' and %d warning' : ' and %d warnings', $warningsCount);
			}

			if ($analysisResult->getTotalErrorsCount() > 0) {
				$style->error($finalMessage);
			} else {
				$style->warning($finalMessage);
			}
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
