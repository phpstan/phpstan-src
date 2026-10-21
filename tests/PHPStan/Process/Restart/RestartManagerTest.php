<?php declare(strict_types = 1);

namespace PHPStan\Process\Restart;

use Override;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function getenv;
use function putenv;
use function sprintf;

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

}
