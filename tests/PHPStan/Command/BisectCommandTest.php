<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\CommandTester;
use function file_put_contents;
use function getenv;
use function getmypid;
use function json_encode;
use function mkdir;
use function putenv;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;

#[Group('exec')]
#[CoversNothing]
class BisectCommandTest extends TestCase
{

	public function testCommandConfiguration(): void
	{
		$command = new BisectCommand();
		$this->assertSame('bisect', $command->getName());
		$definition = $command->getDefinition();
		$this->assertTrue($definition->hasOption('good'));
		$this->assertTrue($definition->hasOption('bad'));
		$this->assertTrue($definition->hasOption('configuration'));
		$this->assertTrue($definition->hasOption('level'));
		$this->assertTrue($definition->hasOption('autoload-file'));
		$this->assertTrue($definition->hasOption('memory-limit'));
		$this->assertTrue($definition->hasArgument('paths'));
	}

	public function testMissingGoodAndBadOptionsNonInteractive(): void
	{
		$command = new BisectCommand();
		$commandTester = new CommandTester($command);
		$commandTester->execute([], ['interactive' => false]);

		$this->assertSame(1, $commandTester->getStatusCode());
		$display = $commandTester->getDisplay();
		$this->assertStringContainsString('good', $display);
		$this->assertStringContainsString('bad', $display);
	}

	public function testReadGitHubTokenFromAuthJson(): void
	{
		$previousGh = getenv('GH_TOKEN');
		$previousGithub = getenv('GITHUB_TOKEN');
		putenv('GH_TOKEN');
		putenv('GITHUB_TOKEN');

		try {
			$tmpDir = sys_get_temp_dir() . '/phpstan-bisect-test-' . getmypid();
			@mkdir($tmpDir, 0777, true);
			file_put_contents($tmpDir . '/auth.json', json_encode([
				'github-oauth' => [
					'github.com' => 'test-token-12345',
				],
			]));

			$command = new BisectCommand();
			$token = $command->getGitHubToken($tmpDir);

			$this->assertSame('test-token-12345', $token);

			@unlink($tmpDir . '/auth.json');
			@rmdir($tmpDir);
		} finally {
			if ($previousGh !== false) {
				putenv('GH_TOKEN=' . $previousGh);
			}
			if ($previousGithub !== false) {
				putenv('GITHUB_TOKEN=' . $previousGithub);
			}
		}
	}

	public function testReadGitHubTokenFromEnvironment(): void
	{
		$previousValue = getenv('GITHUB_TOKEN');
		putenv('GITHUB_TOKEN=env-token-67890');

		try {
			$command = new BisectCommand();
			$token = $command->getGitHubToken('/nonexistent-path');
			$this->assertSame('env-token-67890', $token);
		} finally {
			if ($previousValue !== false) {
				putenv('GITHUB_TOKEN=' . $previousValue);
			} else {
				putenv('GITHUB_TOKEN');
			}
		}
	}

	public function testReadGitHubTokenReturnsNullWhenNotFound(): void
	{
		$previousGh = getenv('GH_TOKEN');
		$previousGithub = getenv('GITHUB_TOKEN');
		putenv('GH_TOKEN');
		putenv('GITHUB_TOKEN');

		try {
			$command = new BisectCommand();
			$token = $command->getGitHubToken('/nonexistent-path');
			$this->assertNull($token);
		} finally {
			if ($previousGh !== false) {
				putenv('GH_TOKEN=' . $previousGh);
			}
			if ($previousGithub !== false) {
				putenv('GITHUB_TOKEN=' . $previousGithub);
			}
		}
	}

	public function testBuildAnalyseArgs(): void
	{
		$command = new BisectCommand();

		$reflection = new ReflectionMethod($command, 'buildAnalyseArgs');

		$testInput = new ArrayInput([
			'paths' => ['src/', 'tests/'],
			'--configuration' => 'phpstan.neon',
			'--level' => '8',
		], $command->getDefinition());

		$args = $reflection->invoke($command, $testInput);
		$this->assertStringContainsString('-c', $args);
		$this->assertStringContainsString('phpstan.neon', $args);
		$this->assertStringContainsString('-l', $args);
		$this->assertStringContainsString('8', $args);
		$this->assertStringContainsString('src/', $args);
		$this->assertStringContainsString('tests/', $args);
	}

}
