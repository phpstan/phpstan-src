<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use PHPStan\File\FileHelper;
use PHPStan\ShouldNotHappenException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use function array_map;
use function escapeshellarg;
use function exec;
use function implode;
use function md5;
use function putenv;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;
use const PHP_BINARY;

#[Group('exec')]
#[CoversNothing]
class ParallelAnalyserIntegrationTest extends TestCase
{

	public static function dataRun(): array
	{
		return [
			['analyse'],
			['a'],
		];
	}

	#[DataProvider('dataRun')]
	public function testRun(string $command): void
	{
		$tmpDir = sys_get_temp_dir() . '/' . md5(uniqid());
		putenv('PHPSTAN_TMP_DIR=' . $tmpDir);
		exec(sprintf('%s %s clear-result-cache --configuration %s -q', escapeshellarg(PHP_BINARY), escapeshellarg(__DIR__ . '/../../../bin/phpstan'), escapeshellarg(__DIR__ . '/parallel-analyser.neon')), $clearResultCacheOutputLines, $clearResultCacheExitCode);
		if ($clearResultCacheExitCode !== 0) {
			throw new ShouldNotHappenException('Could not clear result cache.');
		}

		exec(sprintf(
			'%s %s %s -l 8 -c %s --error-format json --no-progress %s',
			escapeshellarg(PHP_BINARY),
			escapeshellarg(__DIR__ . '/../../../bin/phpstan'),
			$command,
			escapeshellarg(__DIR__ . '/parallel-analyser.neon'),
			implode(' ', array_map(static fn (string $path): string => escapeshellarg($path), [
				__DIR__ . '/data/trait-definition.php',
				__DIR__ . '/data/traits.php',
			])),
		), $outputLines, $exitCode);
		$output = implode("\n", $outputLines);

		FileSystem::delete($tmpDir);

		$fileHelper = new FileHelper(__DIR__);
		$filePath = $fileHelper->normalizePath(__DIR__ . '/data/trait-definition.php');
		$this->assertJsonStringEqualsJsonString(Json::encode([
			'totals' => [
				'errors' => 0,
				'file_errors' => 4,
			],
			'files' => [
				sprintf('%s (in context of class ParallelAnalyserIntegrationTest\\Bar)', $filePath) => [
					'errors' => 1,
					'messages' => [
						[
							'message' => 'Method ParallelAnalyserIntegrationTest\\Bar::doFoo() has no return type specified.',
							'line' => 8,
							'ignorable' => true,
							'identifier' => 'missingType.return',
						],
					],
				],
				sprintf('%s (in context of class ParallelAnalyserIntegrationTest\\Foo)', $filePath) => [
					'errors' => 3,
					'messages' => [
						[
							'message' => 'Method ParallelAnalyserIntegrationTest\\Foo::doFoo() has no return type specified.',
							'line' => 8,
							'ignorable' => true,
							'identifier' => 'missingType.return',
						],
						[
							'message' => 'Access to an undefined property ParallelAnalyserIntegrationTest\\Foo::$test.',
							'line' => 10,
							'ignorable' => true,
							'identifier' => 'property.notFound',
							'tip' => 'Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property',
						],
						[
							'message' => 'Access to an undefined property ParallelAnalyserIntegrationTest\\Foo::$test.',
							'line' => 15,
							'ignorable' => true,
							'identifier' => 'property.notFound',
							'tip' => 'Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property',
						],
					],
				],
			],
			'errors' => [],
		]), $output);
		$this->assertSame(1, $exitCode);
	}

}
