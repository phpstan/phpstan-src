<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPUnit\Framework\TestCase;
use function array_keys;

class ProcessedFilesCollectorTest extends TestCase
{

	public function testEmpty(): void
	{
		$collector = new ProcessedFilesCollector();
		$this->assertSame([], $collector->getTopMostAnalysedFiles(5));
	}

	public function testSingleFileNotReported(): void
	{
		$collector = new ProcessedFilesCollector();
		$collector->addProcessedFiles(['/path/to/file.php']);
		$this->assertSame([], $collector->getTopMostAnalysedFiles(5));
	}

	public function testTopMostAnalysedFiles(): void
	{
		$collector = new ProcessedFilesCollector();

		// Simulate: file A uses trait T1 and T2, file B uses trait T1
		$collector->addProcessedFiles(['/path/to/A.php', '/path/to/T1.php', '/path/to/T2.php']);
		$collector->addProcessedFiles(['/path/to/B.php', '/path/to/T1.php']);

		$top = $collector->getTopMostAnalysedFiles(5);
		$this->assertSame(['/path/to/T1.php' => 2], $top);
	}

	public function testLimit(): void
	{
		$collector = new ProcessedFilesCollector();

		// Create 7 trait files with varying usage counts
		for ($i = 0; $i < 7; $i++) {
			$files = ['/path/to/main' . $i . '.php'];
			for ($j = 0; $j <= $i; $j++) {
				$files[] = '/path/to/trait' . $j . '.php';
			}
			$collector->addProcessedFiles($files);
		}

		$top = $collector->getTopMostAnalysedFiles(3);
		$this->assertCount(3, $top);

		// trait0.php used 7 times, trait1.php 6 times, trait2.php 5 times
		$files = array_keys($top);
		$this->assertSame('/path/to/trait0.php', $files[0]);
		$this->assertSame(7, $top['/path/to/trait0.php']);
		$this->assertSame('/path/to/trait1.php', $files[1]);
		$this->assertSame(6, $top['/path/to/trait1.php']);
		$this->assertSame('/path/to/trait2.php', $files[2]);
		$this->assertSame(5, $top['/path/to/trait2.php']);
	}

}
