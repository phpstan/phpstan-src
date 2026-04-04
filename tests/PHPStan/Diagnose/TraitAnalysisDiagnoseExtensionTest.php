<?php declare(strict_types = 1);

namespace PHPStan\Diagnose;

use PHPStan\Command\Output;
use PHPStan\File\NullRelativePathHelper;
use PHPUnit\Framework\TestCase;

class TraitAnalysisDiagnoseExtensionTest extends TestCase
{

	public function testNoOutput(): void
	{
		$collector = new ProcessedFilesCollector();
		$extension = new TraitAnalysisDiagnoseExtension($collector, new NullRelativePathHelper());

		$lines = [];
		$output = $this->createOutput($lines);

		$extension->print($output);
		$this->assertSame([], $lines);
	}

	public function testPrintsTopFiles(): void
	{
		$collector = new ProcessedFilesCollector();
		$collector->addProcessedFiles(['/src/A.php', '/src/Trait1.php', '/src/Trait2.php']);
		$collector->addProcessedFiles(['/src/B.php', '/src/Trait1.php', '/src/Trait2.php']);
		$collector->addProcessedFiles(['/src/C.php', '/src/Trait1.php']);

		$extension = new TraitAnalysisDiagnoseExtension($collector, new NullRelativePathHelper());

		$lines = [];
		$output = $this->createOutput($lines);

		$extension->print($output);

		$this->assertCount(4, $lines);
		$this->assertStringContainsString('Most often analysed files', $lines[0]);
		$this->assertStringContainsString('/src/Trait1.php', $lines[1]);
		$this->assertStringContainsString('3 times', $lines[1]);
		$this->assertStringContainsString('/src/Trait2.php', $lines[2]);
		$this->assertStringContainsString('2 times', $lines[2]);
		$this->assertSame('', $lines[3]);
	}

	/**
	 * @param list<string> $lines
	 */
	private function createOutput(array &$lines): Output
	{
		$output = $this->createMock(Output::class);
		$output->method('writeLineFormatted')->willReturnCallback(static function (string $message) use (&$lines): void {
			$lines[] = $message;
		});

		return $output;
	}

}
