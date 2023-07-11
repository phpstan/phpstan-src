<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use SplFileObject;
use function array_filter;
use function htmlspecialchars;
use function implode;
use function is_null;
use function sprintf;
use const ENT_COMPAT;
use const ENT_XML1;
use const PHP_EOL;

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
			'<testsuite failures="%d" name="phpstan" tests="%d" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/r5.9.3/platform-tests/src/test/resources/jenkins-junit.xsd">',
			$totalFailuresCount,
			$totalTestsCount,
		);

		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$fileName = $this->relativePathHelper->getRelativePath($fileSpecificError->getFile());
			$result .= $this->createTestCase(
				$fileName,
				'ERROR',
				$this->escape(implode(PHP_EOL, array_filter([ $fileSpecificError->getMessage(), $fileSpecificError->getTip() ]))),
				$fileSpecificError->getLine(),
				$fileSpecificError->getIdentifier(),
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
	private function createTestCase(string $reference, string $type, ?string $message = null, ?int $line = null, ?string $identifier = null): string
	{
		$result = sprintf(
			'<testcase name="%s" classname="%s">', // class="%s"  file="%s" line="%d",
			$this->escape(is_null($identifier) ? '' : $identifier),
			//$this->escape($reference),
			//$this->escape($reference),
			$this->escape(sprintf('%s:%d', $reference, $line)),
			//$line,
		);

		if ($message !== null) {
			if ($line === null) {
				$result .= sprintf(
					'<failure type="%s" message="%s />',
					$this->escape($type),
					$this->escape($message),
				);
			} else {
				$result .= sprintf(
					'<failure type="%s" message="%s"><![CDATA[%s]]></failure>',
					$this->escape($type),
					$this->escape($message),
					$this->fileGetLine($reference, $line),
				);
			}
		}

		return $result . '</testcase>';
	}

	private function fileGetLine(string $fileName, int $lineNumber): ?string
	{
		$file = new SplFileObject($fileName);
		if (!$file->eof()) {
			$file->seek($lineNumber);
			// @phpstan-ignore-next-line
			return $file->current(); // $contents would hold the data from line x
		}
		return null;
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
