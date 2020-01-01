<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\StreamOutput;

class CommandHelperTest extends TestCase
{

	public function dataBegin(): array
	{
		return [
			[
				'',
				'',
				__DIR__ . '/data/testIncludesExpand.neon',
				null,
				[
					'level' => 'max',
				],
				false,
			],
			[
				'',
				'Recursive included file',
				__DIR__ . '/data/1.neon',
				null,
				[],
				true,
			],
			[
				'',
				'does not exist',
				__DIR__ . '/data/nonexistent.neon',
				null,
				[],
				true,
			],
			[
				'',
				'is missing or is not readable',
				__DIR__ . '/data/containsNonexistent.neon',
				null,
				[],
				true,
			],
			[
				'',
				'These files are included multiple times',
				__DIR__ . '/../../../conf/config.level7.neon',
				'7',
				[],
				true,
			],
			[
				'',
				'These files are included multiple times',
				__DIR__ . '/../../../conf/config.level7.neon',
				'6',
				[],
				true,
			],
			[
				'',
				'These files are included multiple times',
				__DIR__ . '/../../../conf/config.level6.neon',
				'7',
				[],
				true,
			],
			[
				'',
				'',
				__DIR__ . '/data/includePhp.neon',
				null,
				[
					'level' => '3',
				],
				false,
			],
		];
	}

	/**
	 * @dataProvider dataBegin
	 * @param string $input
	 * @param string $expectedOutput
	 * @param string|null $projectConfigFile
	 * @param string|null $level
	 * @param mixed[] $expectedParameters
	 * @param bool $expectException
	 */
	public function testBegin(
		string $input,
		string $expectedOutput,
		?string $projectConfigFile,
		?string $level,
		array $expectedParameters,
		bool $expectException
	): void
	{
		$resource = fopen('php://memory', 'w', false);
		if ($resource === false) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		$output = new StreamOutput($resource);

		try {
			$result = CommandHelper::begin(
				new StringInput($input),
				$output,
				[__DIR__],
				null,
				null,
				null,
				[],
				$projectConfigFile,
				$level,
				false
			);
			if ($expectException) {
				$this->fail();
			}
		} catch (\PHPStan\Command\InceptionNotSuccessfulException $e) {
			if (!$expectException) {
				rewind($output->getStream());
				$contents = stream_get_contents($output->getStream());
				if ($contents === false) {
					throw new \PHPStan\ShouldNotHappenException();
				}
				$this->fail($contents);
			}
		}

		rewind($output->getStream());

		$contents = stream_get_contents($output->getStream());
		if ($contents === false) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		$this->assertStringContainsString($expectedOutput, $contents);

		if (isset($result)) {
			$parameters = $result->getContainer()->getParameters();
			foreach ($expectedParameters as $name => $expectedValue) {
				$this->assertArrayHasKey($name, $parameters);
				$this->assertSame($expectedValue, $parameters[$name]);
			}
		} else {
			$this->assertCount(0, $expectedParameters);
		}
	}

	public function dataResolveRelativePaths(): array
	{
		return [
			[
				__DIR__ . '/relative-paths/root.neon',
				[
					'bootstrap' => __DIR__ . DIRECTORY_SEPARATOR . 'relative-paths' . DIRECTORY_SEPARATOR . 'here.php',
					'autoload_files' => [
						__DIR__ . DIRECTORY_SEPARATOR . 'relative-paths' . DIRECTORY_SEPARATOR . 'here.php',
						__DIR__ . DIRECTORY_SEPARATOR . 'relative-paths' . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'there.php',
						__DIR__ . DIRECTORY_SEPARATOR . 'up.php',
					],
					'autoload_directories' => [
						__DIR__ . DIRECTORY_SEPARATOR . 'relative-paths' . DIRECTORY_SEPARATOR . 'src',
						__DIR__ . DIRECTORY_SEPARATOR . 'relative-paths',
						realpath(__DIR__ . '/../../../') . '/conf',
					],
					'paths' => [
						__DIR__ . DIRECTORY_SEPARATOR . 'relative-paths' . DIRECTORY_SEPARATOR . 'src',
					],
					'memoryLimitFile' => __DIR__ . DIRECTORY_SEPARATOR . 'relative-paths' . DIRECTORY_SEPARATOR . '.memory_limit',
					'excludes_analyse' => [
						__DIR__ . DIRECTORY_SEPARATOR . 'relative-paths' . DIRECTORY_SEPARATOR . 'src',
						__DIR__ . DIRECTORY_SEPARATOR . 'relative-paths' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'data',
						'*/src/*/data',
					],
				],
			],
			[
				__DIR__ . '/relative-paths/nested/nested.neon',
				[
					'autoload_files' => [
						__DIR__ . DIRECTORY_SEPARATOR . 'relative-paths' . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'here.php',
						__DIR__ . DIRECTORY_SEPARATOR . 'relative-paths' . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'there.php',
						__DIR__ . DIRECTORY_SEPARATOR . 'relative-paths' . DIRECTORY_SEPARATOR . 'up.php',
					],
					'ignoreErrors' => [
						[
							'message' => '#aaa#',
							'path' => __DIR__ . DIRECTORY_SEPARATOR . 'relative-paths' . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'aaa.php',
						],
						[
							'message' => '#bbb#',
							'paths' => [
								__DIR__ . DIRECTORY_SEPARATOR . 'relative-paths' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'aaa.php',
								__DIR__ . DIRECTORY_SEPARATOR . 'relative-paths' . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bbb.php',
							],
						],
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataResolveRelativePaths
	 * @param string $configFile
	 * @param array<string, string> $expectedParameters
	 */
	public function testResolveRelativePaths(
		string $configFile,
		array $expectedParameters
	): void
	{
		$result = CommandHelper::begin(
			new StringInput(''),
			new NullOutput(),
			[__DIR__],
			null,
			null,
			null,
			[],
			$configFile,
			'0',
			false
		);
		$parameters = $result->getContainer()->getParameters();
		foreach ($expectedParameters as $name => $expectedValue) {
			$this->assertArrayHasKey($name, $parameters);
			$this->assertSame($expectedValue, $parameters[$name]);
		}
	}

}
