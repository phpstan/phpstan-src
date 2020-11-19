<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\ResultCache\ResultCacheClearer;
use PHPStan\Command\ErrorFormatter\TableErrorFormatter;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\File\FuzzyRelativePathHelper;
use PHPStan\File\NullRelativePathHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class AnalyseApplicationIntegrationTest extends \PHPStan\Testing\TestCase
{

	public function testExecuteOnAFile(): void
	{
		$output = $this->runPath(__DIR__ . '/data/file-without-errors.php', 0);
		$this->assertStringContainsString('No errors', $output);
	}

	public function testExecuteOnANonExistentPath(): void
	{
		$path = __DIR__ . '/foo';
		$output = $this->runPath($path, 1);
		$this->assertStringContainsString(sprintf(
			'File %s does not exist.',
			$path
		), $output);
	}

	public function testExecuteOnAFileWithErrors(): void
	{
		$path = __DIR__ . '/../Rules/Functions/data/nonexistent-function.php';
		$output = $this->runPath($path, 1);
		$this->assertStringContainsString('Function foobarNonExistentFunction not found.', $output);
	}

	private function runPath(string $path, int $expectedStatusCode): string
	{
		if (PHP_VERSION_ID >= 80000 && DIRECTORY_SEPARATOR === '\\') {
			$this->markTestSkipped('Skipped because of https://github.com/symfony/symfony/issues/37508');
		}
		self::getContainer()->getByType(ResultCacheClearer::class)->clear();
		$analyserApplication = self::getContainer()->getByType(AnalyseApplication::class);
		$resource = fopen('php://memory', 'w', false);
		if ($resource === false) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		$output = new StreamOutput($resource);

		$symfonyOutput = new SymfonyOutput(
			$output,
			new \PHPStan\Command\Symfony\SymfonyStyle(new SymfonyStyle($this->createMock(InputInterface::class), $output))
		);

		$memoryLimitFile = self::getContainer()->getParameter('memoryLimitFile');

		$relativePathHelper = new FuzzyRelativePathHelper(new NullRelativePathHelper(), __DIR__, [], DIRECTORY_SEPARATOR);
		$errorFormatter = new TableErrorFormatter($relativePathHelper, false);
		$analysisResult = $analyserApplication->analyse(
			[$path],
			true,
			$symfonyOutput,
			$symfonyOutput,
			false,
			false,
			null,
			null,
			$this->createMock(InputInterface::class)
		);
		if (file_exists($memoryLimitFile)) {
			unlink($memoryLimitFile);
		}
		$statusCode = $errorFormatter->formatErrors($analysisResult, $symfonyOutput);
		$this->assertSame($expectedStatusCode, $statusCode);

		rewind($output->getStream());

		$contents = stream_get_contents($output->getStream());
		if ($contents === false) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return $contents;
	}

}
