<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Override;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use function array_map;
use function escapeshellarg;
use function exec;
use function implode;
use function md5;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;
use const PHP_BINARY;

#[Group('exec')]
#[CoversNothing]
class ResultCacheInfoCommandTest extends TestCase
{

	private string $projectDir;

	#[Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->projectDir = sys_get_temp_dir() . '/phpstan-result-cache-info-' . md5(uniqid(more_entropy: true));
		FileSystem::write($this->projectDir . '/src/Foo.php', <<<'PHP'
<?php declare(strict_types = 1);

namespace ResultCacheInfoTest;

class Foo
{

	public function doFoo(): int
	{
		return 1;
	}

}

PHP);
		FileSystem::write($this->projectDir . '/src/Bar.php', <<<'PHP'
<?php declare(strict_types = 1);

namespace ResultCacheInfoTest;

class Bar
{

	public function doBar(): int
	{
		return (new Foo())->doFoo();
	}

}

PHP);
		FileSystem::write($this->projectDir . '/phpstan.neon', sprintf(
			"parameters:\n\tlevel: 5\n\ttmpDir: %s\n\tpaths:\n\t\t- %s\n",
			$this->projectDir . '/tmp',
			$this->projectDir . '/src',
		));
	}

	#[Override]
	protected function tearDown(): void
	{
		FileSystem::delete($this->projectDir);

		parent::tearDown();
	}

	public function testResultCacheIsNotUsedWithoutCacheFile(): void
	{
		[$output, $exitCode] = $this->runPhpstan(['result-cache-info', '--json']);
		$this->assertSame(0, $exitCode, $output);

		$json = Json::decode($output, Json::FORCE_ARRAY);
		$this->assertFalse($json['resultCacheExists']);
		$this->assertFalse($json['resultCacheUsed']);
		$this->assertSame('Result cache not used because the cache file does not exist.', $json['notUsedReason']);
		$this->assertSame(2, $json['analysedFilesCount']);
		$this->assertSame(2, $json['filesToAnalyseCount']);
		$this->assertNull($json['lastFullAnalysisTime']);
		$this->assertSame($this->projectDir . '/tmp/resultCache.php', $json['resultCachePath']);

		[, $exitCode] = $this->runPhpstan(['result-cache-info', '--json', '--fail-without-result-cache']);
		$this->assertSame(2, $exitCode);
	}

	public function testResultCacheIsUsedAfterAnalysis(): void
	{
		[$analyseOutput, $analyseExitCode] = $this->runPhpstan(['analyse', '--no-progress']);
		$this->assertSame(0, $analyseExitCode, $analyseOutput);

		[$output, $exitCode] = $this->runPhpstan(['result-cache-info', '--json']);
		$this->assertSame(0, $exitCode, $output);

		$json = Json::decode($output, Json::FORCE_ARRAY);
		$this->assertTrue($json['resultCacheExists']);
		$this->assertTrue($json['resultCacheUsed']);
		$this->assertNull($json['notUsedReason']);
		$this->assertSame(2, $json['analysedFilesCount']);
		$this->assertSame(0, $json['filesToAnalyseCount']);
		$this->assertIsInt($json['lastFullAnalysisTime']);

		[, $exitCode] = $this->runPhpstan(['result-cache-info', '--json', '--fail-without-result-cache']);
		$this->assertSame(0, $exitCode);
	}

	public function testChangedFileIsCountedAsFileToAnalyse(): void
	{
		[$analyseOutput, $analyseExitCode] = $this->runPhpstan(['analyse', '--no-progress']);
		$this->assertSame(0, $analyseExitCode, $analyseOutput);

		FileSystem::write(
			$this->projectDir . '/src/Foo.php',
			FileSystem::read($this->projectDir . '/src/Foo.php') . "\n",
		);

		[$output, $exitCode] = $this->runPhpstan(['result-cache-info', '--json']);
		$this->assertSame(0, $exitCode, $output);

		$json = Json::decode($output, Json::FORCE_ARRAY);
		$this->assertTrue($json['resultCacheUsed']);
		$this->assertSame(2, $json['analysedFilesCount']);
		$this->assertSame(1, $json['filesToAnalyseCount']);
	}

	public function testChangedLevelInvalidatesResultCache(): void
	{
		[$analyseOutput, $analyseExitCode] = $this->runPhpstan(['analyse', '--no-progress']);
		$this->assertSame(0, $analyseExitCode, $analyseOutput);

		[$output, $exitCode] = $this->runPhpstan(['result-cache-info', '--json', '-l', '6']);
		$this->assertSame(0, $exitCode, $output);

		$json = Json::decode($output, Json::FORCE_ARRAY);
		$this->assertTrue($json['resultCacheExists']);
		$this->assertFalse($json['resultCacheUsed']);
		$this->assertStringStartsWith('Result cache not used because the metadata do not match: ', $json['notUsedReason']);
		$this->assertStringContainsString('level', $json['notUsedReason']);
		$this->assertSame(2, $json['filesToAnalyseCount']);
	}

	public function testHumanReadableOutput(): void
	{
		[$output, $exitCode] = $this->runPhpstan(['result-cache-info']);
		$this->assertSame(0, $exitCode, $output);
		$this->assertStringContainsString('Result cache file: ' . $this->projectDir . '/tmp/resultCache.php', $output);
		$this->assertStringContainsString('Result cache will not be used.', $output);
		$this->assertStringContainsString('Reason: Result cache not used because the cache file does not exist.', $output);
		$this->assertStringContainsString('2 out of 2 files will be analysed.', $output);

		[$analyseOutput, $analyseExitCode] = $this->runPhpstan(['analyse', '--no-progress']);
		$this->assertSame(0, $analyseExitCode, $analyseOutput);

		[$output, $exitCode] = $this->runPhpstan(['result-cache-info']);
		$this->assertSame(0, $exitCode, $output);
		$this->assertStringContainsString('Result cache will be used.', $output);
		$this->assertStringContainsString('Last full analysis: ', $output);
		$this->assertStringContainsString('0 out of 2 files will be analysed.', $output);
	}

	/**
	 * @param string[] $args
	 * @return array{string, int}
	 */
	private function runPhpstan(array $args): array
	{
		$command = sprintf(
			'%s %s %s --configuration %s 2>&1',
			escapeshellarg(PHP_BINARY),
			escapeshellarg(__DIR__ . '/../../../bin/phpstan'),
			implode(' ', array_map(static fn (string $arg): string => escapeshellarg($arg), $args)),
			escapeshellarg($this->projectDir . '/phpstan.neon'),
		);

		exec($command, $outputLines, $exitCode);

		return [implode("\n", $outputLines), $exitCode];
	}

}
