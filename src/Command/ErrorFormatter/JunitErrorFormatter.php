<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use function htmlspecialchars;
use function sprintf;
use const ENT_COMPAT;
use const ENT_XML1;

class JunitErrorFormatter implements ErrorFormatter
{

	public function __construct(private RelativePathHelper $relativePathHelper)
	{
	}

	public function formatErrors(
		AnalysisResult $analysisResult,
		Output $output,
	): int
	{
		$totalFailuresCount = $analysisResult->getTotalErrorsCount();
		$totalTestsCount = $analysisResult->hasErrors() ? $totalFailuresCount : 1;

		$result = '<?xml version="1.0" encoding="UTF-8"?>';
		$result .= sprintf(
			'<testsuite failures="%d" name="phpstan" tests="%d" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/r5.5.1/platform-tests/src/test/resources/jenkins-junit.xsd">',
			$totalFailuresCount,
			$totalTestsCount,
		);

		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$fileName = $this->relativePathHelper->getRelativePath($fileSpecificError->getFile());
			$result .= $this->createTestCase(
				sprintf('%s:%s', $fileName, (string) $fileSpecificError->getLine()),
				'ERROR',
				$this->escape($fileSpecificError->getMessage()),
			);
		}

		foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
			$result .= $this->createTestCase('General error', 'ERROR', $this->escape($notFileSpecificError));
		}

		foreach ($analysisResult->getWarnings() as $warning) {
			$result .= $this->createTestCase('Warning', 'WARNING', $this->escape($warning));
		}

		if (!$analysisResult->hasErrors()) {
			$result .= $this->createTestCase('phpstan', '');
		}

		$result .= '</testsuite>';

		$output->writeRaw($result);

		return $analysisResult->hasErrors() ? 1 : 0;
	}

	/**
	 * Format a single test case
	 *
	 *
	 */
	private function createTestCase(string $reference, string $type, ?string $message = null): string
	{
		$result = sprintf('<testcase name="%s">', $this->escape($reference));

		if ($message !== null) {
			$result .= sprintf('<failure type="%s" message="%s" />', $this->escape($type), $this->escape($message));
		}

		$result .= '</testcase>';

		return $result;
	}

	/**
	 * Escapes values for using in XML
	 *
	 */
	private function escape(string $string): string
	{
		return htmlspecialchars($string, ENT_XML1 | ENT_COMPAT, 'UTF-8');
	}

}
