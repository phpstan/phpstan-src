<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\ResultCache\ResultCacheClearer;
use PHPStan\Command\ErrorFormatter\CiDetectedErrorFormatter;
use PHPStan\Command\ErrorFormatter\GithubErrorFormatter;
use PHPStan\Command\ErrorFormatter\TableErrorFormatter;
use PHPStan\Command\ErrorFormatter\TeamcityErrorFormatter;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\File\FuzzyRelativePathHelper;
use PHPStan\File\NullRelativePathHelper;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use function fopen;
use function rewind;
use function sprintf;
use function stream_get_contents;
use const DIRECTORY_SEPARATOR;

#[CoversNothing]
class AnalyseApplicationIntegrationTest extends PHPStanTestCase
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
			$path,
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
		self::getContainer()->getByType(ResultCacheClearer::class)->clear();
		$analyserApplication = self::getContainer()->getByType(AnalyseApplication::class);
		$resource = fopen('php://memory', 'w', false);
		if ($resource === false) {
			throw new ShouldNotHappenException();
		}
		$output = new StreamOutput($resource);

		$symfonyOutput = new SymfonyOutput(
			$output,
			new \PHPStan\Command\Symfony\SymfonyStyle(new SymfonyStyle($this->createStub(InputInterface::class), $output)),
		);

		$relativePathHelper = new FuzzyRelativePathHelper(new NullRelativePathHelper(), __DIR__, [], DIRECTORY_SEPARATOR);
		$errorFormatter = new TableErrorFormatter(
			$relativePathHelper,
			new SimpleRelativePathHelper(__DIR__),
			new CiDetectedErrorFormatter(
				new GithubErrorFormatter($relativePathHelper),
				new TeamcityErrorFormatter($relativePathHelper),
			),
			false,
			null,
			null,
		);
		$analysisResult = $analyserApplication->analyse(
			[$path],
			true,
			$symfonyOutput,
			$symfonyOutput,
			false,
			true,
			null,
			null,
			null,
			null,
			$this->createStub(InputInterface::class),
		);
		$statusCode = $errorFormatter->formatErrors($analysisResult, $symfonyOutput);

		rewind($output->getStream());

		$contents = stream_get_contents($output->getStream());
		$this->assertSame($expectedStatusCode, $statusCode, $contents);

		return $contents;
	}

}
