<?php declare(strict_types = 1);

namespace PHPStan\Process;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use function extension_loaded;
use function getenv;
use function putenv;
use function sprintf;

#[CoversNothing]
class PcovHelperTest extends TestCase
{

	public function testIsActiveRequiresTheExtension(): void
	{
		if (extension_loaded('pcov')) {
			$this->assertTrue(PcovHelper::isLoaded());
			return;
		}

		$this->assertFalse(PcovHelper::isLoaded());
		$this->assertFalse(PcovHelper::isActive());
		$this->assertFalse(PcovHelper::shouldDisableInSubProcesses());
	}

	public function testShouldNotDisableInSubProcessesWhenAllowed(): void
	{
		$originalValue = getenv(PcovHelper::ALLOW_ENV_VARIABLE);
		putenv(sprintf('%s=1', PcovHelper::ALLOW_ENV_VARIABLE));

		try {
			$this->assertTrue(PcovHelper::isAllowed());
			$this->assertFalse(PcovHelper::shouldDisableInSubProcesses());
		} finally {
			self::restoreAllowEnvVariable($originalValue);
		}
	}

	public function testShouldDisableInSubProcessesEvenWhenNotActiveInThisProcess(): void
	{
		if (!PcovHelper::isLoaded()) {
			$this->markTestSkipped('pcov is not loaded in this process.');
		}

		$originalValue = getenv(PcovHelper::ALLOW_ENV_VARIABLE);
		putenv(PcovHelper::ALLOW_ENV_VARIABLE);

		try {
			$this->assertFalse(PcovHelper::isAllowed());
			// sub-processes read the php.ini, not this process' command line
			$this->assertTrue(PcovHelper::shouldDisableInSubProcesses());
			$this->assertNotNull(PcovHelper::getVersion());
		} finally {
			self::restoreAllowEnvVariable($originalValue);
		}
	}

	private static function restoreAllowEnvVariable(string|false $originalValue): void
	{
		putenv($originalValue === false
			? PcovHelper::ALLOW_ENV_VARIABLE
			: sprintf('%s=%s', PcovHelper::ALLOW_ENV_VARIABLE, $originalValue));
	}

}
