<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use DOMDocument;
use DOMElement;
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
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;

		$testsuite = $dom->createElement('testsuite');
		$testsuite->setAttribute('failures', (string) $analysisResult->getTotalErrorsCount());
		$testsuite->setAttribute('name', 'phpstan');
		$testsuite->setAttribute('tests', (string) $analysisResult->getTotalErrorsCount());
		$testsuite->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$testsuite->setAttribute('xsi:noNamespaceSchemaLocation', 'https://raw.githubusercontent.com/junit-team/junit5/r5.5.1/platform-tests/src/test/resources/jenkins-junit.xsd');
		$dom->appendChild($testsuite);

		foreach ($analysisResult->getFileSpecificErrors() as $error) {
			$fileName = $this->relativePathHelper->getRelativePath($error->getFile());
			$this->createTestCase($dom, $testsuite, sprintf('%s:%s', $fileName, (string) $error->getLine()), $error->getMessage());
		}

		foreach ($analysisResult->getNotFileSpecificErrors() as $genericError) {
			$this->createTestCase($dom, $testsuite, 'Generic error', $genericError);
		}

		if (!$analysisResult->hasErrors()) {
			$this->createTestCase($dom, $testsuite, 'phpstan');
		}

		$output->writeRaw($dom->saveXML());

		return intval($analysisResult->hasErrors());
	}

	private function createTestCase(DOMDocument $dom, DOMElement $testsuite, string $reference, ?string $message = null): void
	{
		$testcase = $dom->createElement('testcase');
		$testcase->setAttribute('name', $reference);

		if ($message !== null) {
			$failure = $dom->createElement('failure');
			$failure->setAttribute('message', $message);

			$testcase->appendChild($failure);
		}

		$testsuite->appendChild($testcase);
	}

}
