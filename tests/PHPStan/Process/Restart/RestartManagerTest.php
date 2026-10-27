<?php declare(strict_types = 1);

namespace PHPStan\Process\Restart;

use Override;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function fclose;
use function function_exists;
use function getenv;
use function is_file;
use function proc_close;
use function proc_open;
use function putenv;
use function sprintf;
use function stream_get_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use const PHP_BINARY;

#[CoversNothing]
class RestartManagerTest extends TestCase
{

	private const ENV_VARIABLES = [
		RestartManager::REASONS_ENV_VARIABLE,
		'PHPSTAN_ALLOW_XDEBUG',
		'RECTOR_ALLOW_XDEBUG',
	];

	/** @var array<string, string|false> */
	private array $originalEnvironment = [];

	#[Override]
	protected function setUp(): void
	{
		foreach (self::ENV_VARIABLES as $name) {
			$this->originalEnvironment[$name] = getenv($name);
			putenv($name);
		}
	}

	#[Override]
	protected function tearDown(): void
	{
		foreach ($this->originalEnvironment as $name => $value) {
			putenv($value === false ? $name : sprintf('%s=%s', $name, $value));
		}
	}

	/**
	 * @return iterable<string, array{list<string>, bool}>
	 */
	public static function dataIsEligible(): iterable
	{
		yield 'the default command restarts' => [['bin/phpstan'], true];
		yield 'analyse restarts' => [['bin/phpstan', 'analyse', '-l', '8', 'src'], true];
		yield 'analyze restarts' => [['bin/phpstan', 'analyze', 'src'], true];
		yield 'fix restarts' => [['bin/phpstan', 'analyse', '--fix', 'src'], true];
		yield 'bisect restarts' => [['bin/phpstan', 'bisect'], true];

		yield 'worker never restarts' => [['bin/phpstan', 'worker', '--port', '1234'], false];
		yield 'fixer:worker never restarts' => [['bin/phpstan', 'fixer:worker'], false];
		yield 'diagnose never restarts' => [['bin/phpstan', 'diagnose'], false];
		// these analyse nothing, so an exec would buy no speed
		yield 'clear-result-cache never restarts' => [['bin/phpstan', 'clear-result-cache'], false];
		yield 'dump-parameters never restarts' => [['bin/phpstan', 'dump-parameters'], false];

		// --xdebug keeps xdebug; --debug only makes the analysis serial
		yield '--debug restarts' => [['bin/phpstan', 'analyse', '--debug', 'src'], true];

		yield 'an option value is not mistaken for a non-restarting command' => [
			['bin/phpstan', 'analyse', '-c', 'worker', 'src'],
			true,
		];
		yield 'paths after -- do not hide the command name' => [
			['bin/phpstan', 'worker', '--', 'src'],
			false,
		];
	}

	/**
	 * @param list<string> $argv
	 */
	#[DataProvider('dataIsEligible')]
	public function testIsEligible(array $argv, bool $expected): void
	{
		$this->assertSame($expected, RestartManager::isEligible($argv));
	}

	public function testTheMarkerStopsASecondRestart(): void
	{
		$argv = ['bin/phpstan', 'analyse', 'src'];
		$this->assertTrue(RestartManager::isEligible($argv));

		putenv(sprintf('%s=xdebug,pcov', RestartManager::REASONS_ENV_VARIABLE));

		$this->assertFalse(RestartManager::isEligible($argv));
		$this->assertSame([], RestartManager::collectReasons($argv));
	}

	public function testABlackfireProfiledProcessIsNotRestarted(): void
	{
		$argv = ['bin/phpstan', 'analyse', 'src'];
		$this->assertTrue(RestartManager::isEligible($argv));

		$_SERVER['BLACKFIRE_AGENT_SOCKET'] = 'unix:///var/run/blackfire/agent.sock';
		try {
			$this->assertFalse(RestartManager::isEligible($argv));
		} finally {
			unset($_SERVER['BLACKFIRE_AGENT_SOCKET']);
		}
	}

	public function testPerformedReasonsComeFromTheMarker(): void
	{
		$this->assertFalse(RestartManager::wasRestarted());
		$this->assertSame([], RestartManager::getPerformedRestartReasons());
		$this->assertFalse(RestartManager::wasRestartedFor('xdebug'));

		putenv(sprintf('%s=xdebug,pcov', RestartManager::REASONS_ENV_VARIABLE));

		$this->assertTrue(RestartManager::wasRestarted());
		$this->assertSame(['xdebug', 'pcov'], RestartManager::getPerformedRestartReasons());
		$this->assertTrue(RestartManager::wasRestartedFor('xdebug'));
		$this->assertTrue(RestartManager::wasRestartedFor('pcov'));
		$this->assertFalse(RestartManager::wasRestartedFor('phpstan_turbo'));
	}

	public function testTheDeprecatedAllowXdebugEnvVariableIsHonored(): void
	{
		$argv = ['bin/phpstan', 'analyse', 'src'];

		$this->assertFalse(RestartManager::isXdebugAllowed($argv));
		$this->assertTrue(RestartManager::isXdebugAllowed(['bin/phpstan', 'analyse', '--xdebug', 'src']));

		putenv('PHPSTAN_ALLOW_XDEBUG=1');
		$this->assertTrue(RestartManager::isXdebugAllowed($argv));

		putenv('PHPSTAN_ALLOW_XDEBUG=0');
		$this->assertFalse(RestartManager::isXdebugAllowed($argv));

		putenv('PHPSTAN_ALLOW_XDEBUG=');
		$this->assertFalse(RestartManager::isXdebugAllowed($argv));
	}

	public function testAnotherApplicationHasItsOwnAllowXdebugEnvVariable(): void
	{
		$argv = ['vendor/bin/rector', 'process', 'src'];

		putenv('PHPSTAN_ALLOW_XDEBUG=1');
		$this->assertFalse(RestartManager::isXdebugAllowed($argv, 'rector'));

		putenv('RECTOR_ALLOW_XDEBUG=1');
		$this->assertTrue(RestartManager::isXdebugAllowed($argv, 'rector'));
	}

	public function testNonRestartingCommandsCollectNoReasons(): void
	{
		$this->assertSame([], RestartManager::collectReasons(['bin/phpstan', 'worker']));
		$this->assertSame([], RestartManager::collectReasons(['bin/phpstan', 'diagnose']));
	}

	public function testAForkedWorkerDoesNotDeleteTheComposedIni(): void
	{
		if (!function_exists('pcntl_fork')) {
			$this->markTestSkipped('Requires pcntl.');
		}

		$temporaryIni = tempnam(sys_get_temp_dir(), 'phpstan-restart-ini-test-');
		$this->assertNotFalse($temporaryIni);

		$environment = getenv();
		$environment['PHPSTAN_RESTART_INI'] = $temporaryIni;
		$process = proc_open([PHP_BINARY, __DIR__ . '/data/forked-child-ends.php'], [1 => ['pipe', 'w']], $pipes, null, $environment);
		$this->assertIsResource($process);
		$output = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		proc_close($process);

		$deletedByOwner = !is_file($temporaryIni);
		@unlink($temporaryIni);

		// spawned PHP processes read their configuration from it until the run ends
		$this->assertSame('kept', $output);
		$this->assertTrue($deletedByOwner);
	}

}
