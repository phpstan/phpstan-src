<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use function sprintf;

class JunitErrorFormatter implements ErrorFormatter
{

	/** @var \PHPStan\File\RelativePathHelper */
	private $relativePathHelper;

	public function __construct(RelativePathHelper $relativePathHelper)
	{
		$this->relativePathHelper = $relativePathHelper;
	}

	public function formatErrors(
		AnalysisResult $analysisResult,
		Output $output
	): int
	{
		$result = '<?xml version="1.0" encoding="UTF-8"?>';
		$result .= sprintf(
			'<testsuite failures="%d" name="phpstan" tests="%d" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/r5.5.1/platform-tests/src/test/resources/jenkins-junit.xsd">',
			$analysisResult->getTotalErrorsCount(),
			$analysisResult->getTotalErrorsCount()
		);

		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$fileName = $this->relativePathHelper->getRelativePath($fileSpecificError->getFile());
			$result .= $this->createTestCase(
				sprintf('%s:%s', $fileName, (string) $fileSpecificError->getLine()),
				$this->escape($fileSpecificError->getMessage())
			);
		}

		foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
			$result .= $this->createTestCase('General error', $this->escape($notFileSpecificError));
		}

		if (!$analysisResult->hasErrors()) {
			$result .= $this->createTestCase('phpstan');
		}

		$result .= '</testsuite>';

		$output->writeRaw($result);

		return intval($analysisResult->hasErrors());
	}

	/**
	 * Format a single test case
	 *
	 * @param string      $reference
	 * @param string|null $message
	 *
	 * @return string
	 */
	private function createTestCase(string $reference, ?string $message = null): string
	{
		$result = sprintf('<testcase name="%s">', $this->escape($reference));

		if ($message !== null) {
			$result .= sprintf('<failure message="%s" />', $this->escape($message));
		}

		$result .= '</testcase>';

		return $result;
	}

	/**
	 * Escapes values for using in XML
	 *
	 * @param string $string
	 * @return string
	 */
	protected function escape(string $string): string
	{
		return htmlspecialchars($string, ENT_XML1 | ENT_COMPAT, 'UTF-8');
	}

}
