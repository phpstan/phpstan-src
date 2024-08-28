<?php declare(strict_types = 1);

namespace PHPStan\Generics;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;

/**
 * @group genericsmulti
 */
class GenericsWithMultipleFilesIntegrationTest extends PHPStanTestCase
{
	/**
	 * @return array<array<string>>
	 */
	public function dataTopics(): array
	{
		return [
			'bug9630' => ['bug9630', [9]],
			'bug9630_2' => ['bug9630_2', [9]],
		];
	}

	/**
	 * @dataProvider dataTopics
	 *
	 * @param int[] $levels
	 */
	public function testDir(string $dir, array $levels): void
	{
		$dir = $this->getDataPath() . DIRECTORY_SEPARATOR . $dir;
		$this->assertDirectoryIsReadable($dir);
		$phpstanCmd = escapeshellcmd($this->getPhpStanExecutablePath());
		$configPath = $this->getPhpStanConfigPath();

		$configOption = is_null($configPath) ? '' : '--configuration ' . escapeshellarg($configPath);

		exec(sprintf('%s %s clear-result-cache %s 2>&1', escapeshellcmd(PHP_BINARY), $phpstanCmd, $configOption), $clearResultCacheOutputLines, $clearResultCacheExitCode);

		if (0 !== $clearResultCacheExitCode) {
			throw new ShouldNotHappenException('Could not clear result cache: ' . implode("\n", $clearResultCacheOutputLines));
		}

		putenv('__PHPSTAN_FORCE_VALIDATE_STUB_FILES=1');

		foreach ($levels as $level) {
			unset($outputLines);

			$toExec = sprintf('%s %s analyse --no-progress --error-format=prettyJson --level=%d %s %s', escapeshellcmd(PHP_BINARY), $phpstanCmd, $level, $configOption, escapeshellarg($dir));

			exec($toExec, $outputLines);

			$output = implode("\n", $outputLines);

			try {
				$actualJson = Json::decode($output, Json::FORCE_ARRAY);
			} catch (JsonException) {
				throw new JsonException(sprintf('Cannot decode: %s', $output));
			}

			// Check that there was no error during the execution of PHPStan
			foreach ($actualJson['files'] as $file => $fileJson) {
				$this->assertCount(0, $fileJson['messages'], 'The file ' . $file . ' contains errors. The command that produced the error was: ' . $toExec);
			}
		}
	}

	public function getDataPath(): string
	{
		return __DIR__ . '/data';
	}

	public function getPhpStanExecutablePath(): string
	{
		return __DIR__ . '/../../../bin/phpstan';
	}

	public function getPhpStanConfigPath(): string
	{
		return __DIR__ . '/generics.neon';
	}
}
