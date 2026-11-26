<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\RelativePathHelper;
use Symfony\Component\Console\Formatter\OutputFormatter;
use function count;
use function is_string;
use function sprintf;
use function str_replace;
use function uasort;

#[AutowiredService(name: 'errorFormatter.grouped')]
final class GroupedErrorFormatter implements ErrorFormatter
{

	private const NO_IDENTIFIER = 'without identifier';

	public function __construct(
		#[AutowiredParameter(ref: '@simpleRelativePathHelper')]
		private RelativePathHelper $relativePathHelper,
		#[AutowiredParameter]
		private ?string $editorUrl,
		#[AutowiredParameter]
		private ?string $editorUrlTitle,
	)
	{
	}

	public function formatErrors(AnalysisResult $analysisResult, Output $output): int
	{
		$style = $output->getStyle();

		if (!$analysisResult->hasErrors() && !$analysisResult->hasWarnings()) {
			$style->success('No errors');

			return 0;
		}

		/** @var array<string, Error[]> $groupedErrors */
		$groupedErrors = [];

		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$identifier = $fileSpecificError->getIdentifier() ?? self::NO_IDENTIFIER;

			$groupedErrors[$identifier][] = $fileSpecificError;
		}

		uasort($groupedErrors, static fn ($errorsA, $errorsB) => count($errorsB) <=> count($errorsA));

		foreach ($groupedErrors as $identifier => $errors) {
			$count = count($errors);
			$output->writeRaw(sprintf('[%s] (%dx):', $identifier, $count));
			$output->writeLineFormatted('');

			foreach ($errors as $error) {
				$file = $error->getTraitFilePath() ?? $error->getFilePath();
				$relFile = $this->relativePathHelper->getRelativePath($file);
				$line = (string) $error->getLine();
				$message = $error->getMessage();

				if (is_string($this->editorUrl)) {
					$url = str_replace(
						['%file%', '%relFile%', '%line%'],
						[$file, $relFile, $line],
						$this->editorUrl,
					);

					if (is_string($this->editorUrlTitle)) {
						$title = str_replace(
							['%file%', '%relFile%', '%line%'],
							[$file, $relFile, $line],
							$this->editorUrlTitle,
						);
					} else {
						$title = sprintf('%s:%s', $file, $line);
					}

					$fileStr = '<href=' . OutputFormatter::escape($url) . '>' . $title . '</>';
				} else {
					$fileStr = sprintf('%s:%s', $file, $line);
				}

				$output->writeLineFormatted(sprintf("\t- %s: %s", $fileStr, $message));
			}
			$output->writeLineFormatted('');
		}

		foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
			$output->writeRaw(sprintf('?:?:%s', $notFileSpecificError));
			$output->writeLineFormatted('');
		}

		foreach ($analysisResult->getWarnings() as $warning) {
			$output->writeRaw(sprintf('?:?:%s', $warning));
			$output->writeLineFormatted('');
		}

		$totalErrorsCount = $analysisResult->getTotalErrorsCount();
		$warningsCount = count($analysisResult->getWarnings());

		$finalMessage = sprintf($totalErrorsCount === 1 ? 'Found %d error' : 'Found %d errors', $totalErrorsCount);

		if ($analysisResult->hasWarnings()) {
			$finalMessage .= sprintf($warningsCount === 1 ? ' and %d warning' : ' and %d warnings', $warningsCount);
		}

		if ($analysisResult->hasErrors()) {
			$style->error($finalMessage);
		} else {
			$style->warning($finalMessage);
		}

		return $analysisResult->hasErrors() ? 1 : 0;
	}

}
