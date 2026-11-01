<?php declare(strict_types = 1);

namespace PHPStan\Process\Restart;

/**
 * One reason the process must be re-executed, together with everything needed
 * to neutralize it in the restarted process.
 *
 * The four neutralization channels are what an extension needs:
 *
 * - ini settings are appended after everything else in the composed php.ini,
 *   so they win over the user's files and over the runtime diff. Prefer this
 *   one whenever the extension has a switch: a disarmed extension measures the
 *   same as an absent one (xdebug.mode=off, pcov.enabled=0 and
 *   uopz.disable=1), and leaving it loaded keeps its own functions
 *   reflectable, so the analysis result does not change;
 * - environment variables, for arming that the ini cannot express: xdebug
 *   reads XDEBUG_MODE in preference to xdebug.mode, so flagging the ini alone
 *   would be silently overridable. They are exported before the restart, so
 *   descendants inherit them;
 * - stripped extensions are commented out of the composed php.ini. Only
 *   Tideways needs this today — it has no off switch for the hooks that
 *   crash the turbo extension — and stripping is strictly worse where a
 *   flag exists: it takes the extension's own symbols out of reflection and so
 *   changes what PHPStan knows about code calling them;
 * - command-line arguments are passed to the restarted php binary, for the
 *   inverse case of *loading* an extension from a path that is not in any ini
 *   file. They deliberately do not reach descendant processes, which is why
 *   the turbo binary is still injected into spawned workers by ProcessHelper.
 */
final class RestartReason
{

	/**
	 * @param string $extension the extension this reason is about, as reported by extension_loaded()
	 * @param string $description why the restart is needed, for diagnose output
	 * @param array<string, string> $iniSettings ini settings forced in the restarted process
	 * @param array<string, string> $environmentVariables environment variables exported before the restart
	 * @param list<string> $strippedExtensions extension names whose extension=/zend_extension= lines get commented out
	 * @param list<string> $commandLineArguments php arguments prepended to the restarted command
	 */
	public function __construct(
		private string $extension,
		private string $description,
		private array $iniSettings,
		private array $environmentVariables,
		private array $strippedExtensions,
		private array $commandLineArguments,
	)
	{
	}

	public function getExtension(): string
	{
		return $this->extension;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	/** @return array<string, string> */
	public function getIniSettings(): array
	{
		return $this->iniSettings;
	}

	/** @return array<string, string> */
	public function getEnvironmentVariables(): array
	{
		return $this->environmentVariables;
	}

	/** @return list<string> */
	public function getStrippedExtensions(): array
	{
		return $this->strippedExtensions;
	}

	/** @return list<string> */
	public function getCommandLineArguments(): array
	{
		return $this->commandLineArguments;
	}

}
