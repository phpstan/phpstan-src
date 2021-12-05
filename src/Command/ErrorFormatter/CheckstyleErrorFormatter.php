<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;

class CheckstyleErrorFormatter implements ErrorFormatter
{

	private RelativePathHelper $relativePathHelper;

	public function __construct(RelativePathHelper $relativePathHelper)
	{
		$this->relativePathHelper = $relativePathHelper;
	}

	public function formatErrors(
		AnalysisResult $analysisResult,
		Output $output
	): int
	{
		$output->writeRaw('<?xml version="1.0" encoding="UTF-8"?>');
		$output->writeLineFormatted('');
		$output->writeRaw('<checkstyle>');
		$output->writeLineFormatted('');

		foreach ($this->groupByFile($analysisResult) as $relativeFilePath => $errors) {
			$output->writeRaw(sprintf(
				'<file name="%s">',
				$this->escape($relativeFilePath)
			));
			$output->writeLineFormatted('');

			foreach ($errors as $error) {
				$output->writeRaw(sprintf(
					'  <error line="%d" column="1" severity="error" message="%s" />',
					$this->escape((string) $error->getLine()),
					$this->escape($error->getMessage())
				));
				$output->writeLineFormatted('');
			}
			$output->writeRaw('</file>');
			$output->writeLineFormatted('');
		}

		$notFileSpecificErrors = $analysisResult->getNotFileSpecificErrors();

		if (count($notFileSpecificErrors) > 0) {
			$output->writeRaw('<file>');
			$output->writeLineFormatted('');

			foreach ($notFileSpecificErrors as $error) {
				$output->writeRaw(sprintf('  <error severity="error" message="%s" />', $this->escape($error)));
				$output->writeLineFormatted('');
			}

			$output->writeRaw('</file>');
			$output->writeLineFormatted('');
		}

		if ($analysisResult->hasWarnings()) {
			$output->writeRaw('<file>');
			$output->writeLineFormatted('');

			foreach ($analysisResult->getWarnings() as $warning) {
				$output->writeRaw(sprintf('  <error severity="warning" message="%s" />', $this->escape($warning)));
				$output->writeLineFormatted('');
			}

			$output->writeRaw('</file>');
			$output->writeLineFormatted('');
		}

		$output->writeRaw('</checkstyle>');
		$output->writeLineFormatted('');

		return $analysisResult->hasErrors() ? 1 : 0;
	}

	/**
	 * Escapes values for using in XML
	 *
	 */
	private function escape(string $string): string
	{
		return htmlspecialchars($string, ENT_XML1 | ENT_COMPAT, 'UTF-8');
	}

	/**
	 * Group errors by file
	 *
	 * @return array<string, array<\PHPStan\Analyser\Error>> Array that have as key the relative path of file
	 *                              and as value an array with occurred errors.
	 */
	private function groupByFile(AnalysisResult $analysisResult): array
	{
		$files = [];

		/** @var \PHPStan\Analyser\Error $fileSpecificError */
		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$absolutePath = $fileSpecificError->getFilePath();
			if ($fileSpecificError->getTraitFilePath() !== null) {
				$absolutePath = $fileSpecificError->getTraitFilePath();
			}
			$relativeFilePath = $this->relativePathHelper->getRelativePath(
				$absolutePath
			);

			$files[$relativeFilePath][] = $fileSpecificError;
		}

		return $files;
	}

}
