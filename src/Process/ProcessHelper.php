<?php declare(strict_types = 1);

namespace PHPStan\Process;

use PHPStan\Command\AnalyseCommand;
use PHPStan\Turbo\TurboExtensionSelector;
use PHPStan\Turbo\TurboProcessRestarter;
use Symfony\Component\Console\Input\InputInterface;
use function array_merge;
use function escapeshellarg;
use function getmypid;
use function implode;
use function ini_get;
use function is_bool;
use function php_ini_loaded_file;
use function sprintf;
use function sys_get_temp_dir;
use const PHP_BINARY;
use const PHP_OS_FAMILY;

/**
 * Builds the command line of a spawned worker process (see SpawnedProcess
 * and SpawnedProcessPromise).
 *
 * Besides the worker command and its options it spells out the PHP
 * configuration the worker runs with. The php.ini is inherited through
 * `-c`, but command-line `-d` entries are not, so whatever the spawning
 * process got that way - the turbo extension and the OPcache setup of the
 * TurboProcessRestarter restart - is repeated here; see
 * resolveWorkerIniEntries() for the set and the reasoning.
 */
final class ProcessHelper
{

	/** How many workers this process spawned so far - numbers the Windows OPcache instances, see resolveWorkerIniEntries() */
	private static int $spawnedWorkerCount = 0;

	/**
	 * @param string[] $additionalItems
	 */
	public static function getWorkerCommand(
		string $mainScript,
		string $commandName,
		?string $projectConfigFile,
		array $additionalItems,
		InputInterface $input,
	): string
	{
		$phpIni = php_ini_loaded_file();
		$phpCmd = $phpIni === false ? escapeshellarg(PHP_BINARY) : sprintf('%s -c %s', escapeshellarg(PHP_BINARY), escapeshellarg($phpIni));

		$processCommandArray = [
			$phpCmd,
			'-d',
			// quote value so PHP will parse it as a string when the path contains a bitwise operator like ~
			'sys_temp_dir=' . escapeshellarg("'" . sys_get_temp_dir() . "'"),
		];

		if ($input->getOption('memory-limit') === null) {
			$processCommandArray[] = '-d';
			$processCommandArray[] = 'memory_limit=' . ini_get('memory_limit');
		}

		$turboExtension = TurboExtensionSelector::findExtensionForWorkers();
		if ($turboExtension !== null) {
			$processCommandArray[] = '-d';
			// quote value so PHP will parse it as a string when the path contains a bitwise operator like ~
			$processCommandArray[] = 'extension=' . escapeshellarg("'" . $turboExtension . "'");
			// the same marker the process restart sets: the worker's
			// TurboExtensionSelector then knows the extension came through -d,
			// not the php.ini - nothing that process spawns would inherit it
			$processCommandArray[] = '-d';
			$processCommandArray[] = TurboProcessRestarter::EXTENSION_PATH_INI . '=' . escapeshellarg("'" . $turboExtension . "'");
		}

		$parentPid = getmypid();
		self::$spawnedWorkerCount++;
		foreach (self::resolveWorkerIniEntries(TurboProcessRestarter::getOpcacheArgs(), PHP_OS_FAMILY, $parentPid === false ? 0 : $parentPid, self::$spawnedWorkerCount) as $iniEntry) {
			$processCommandArray[] = '-d';
			$processCommandArray[] = $iniEntry;
		}

		foreach ([$mainScript, $commandName] as $arg) {
			$processCommandArray[] = escapeshellarg($arg);
		}

		if ($projectConfigFile !== null) {
			$processCommandArray[] = '--configuration';
			$processCommandArray[] = escapeshellarg($projectConfigFile);
		}

		$options = [
			AnalyseCommand::OPTION_LEVEL,
			'autoload-file',
			'memory-limit',
			'xdebug',
			'verbose',
		];
		foreach ($options as $optionName) {
			/** @var bool|string|null $optionValue */
			$optionValue = $input->getOption($optionName);
			if (is_bool($optionValue)) {
				if ($optionValue === true) {
					$processCommandArray[] = sprintf('--%s', $optionName);
				}
				continue;
			}
			if ($optionValue === null) {
				continue;
			}

			$processCommandArray[] = sprintf('--%s=%s', $optionName, escapeshellarg($optionValue));
		}

		$processCommandArray = array_merge($processCommandArray, $additionalItems);

		$processCommandArray[] = '--';

		/** @var string[] $paths */
		$paths = $input->getArgument('paths');
		foreach ($paths as $path) {
			$processCommandArray[] = escapeshellarg($path);
		}

		return implode(' ', $processCommandArray);
	}

	/**
	 * The ini entries a spawned worker gets as `-d name=value`, besides
	 * memory_limit, sys_temp_dir and the extension.
	 *
	 * The OPcache entries are the ones the TurboProcessRestarter restart
	 * gives the main process (see its resolveOpcacheArgs() for what each
	 * does). A spawned worker compiles the whole application again and gains
	 * the same from them - optimized opcodes, interned strings, the
	 * inheritance cache - and, with the turbo extension active in a phar,
	 * the optimizer pass dropping PHPStan's own run-time type checks, which
	 * exists only inside OPcache. Without them, a worker on a pcntl host
	 * re-executed itself through TurboProcessRestarter to get OPcache: one
	 * exec more per worker, and one that rebuilt the command line from
	 * scratch, dropping the sys_temp_dir and extension entries of the spawn.
	 * A Windows worker ran without OPcache altogether.
	 *
	 * On Windows the cache is not private to the process: OPcache there
	 * opens a named file mapping shared by every process of the same user,
	 * PHP build and SAPI, so identically configured workers would all attach
	 * to the first one's segment. That is the concurrently populated shared
	 * cache forked workers had to be taken off (see ForkedProcess), and
	 * attaching fails outright - a fatal startup error - when the segment's
	 * base address is taken in the new process. An opcache.cache_id (a
	 * Windows-only directive, folded into the mapping name) unique to the
	 * worker gives each its own segment, never shared, never reattached: the
	 * model spawned workers have elsewhere, where the segment is an anonymous
	 * mapping private to the process.
	 *
	 * The restart marker closes the loop: the worker's configuration is
	 * decided here, so the worker must not run TurboProcessRestarter itself.
	 *
	 * @param list<string> $opcacheArgs TurboProcessRestarter::getOpcacheArgs() of the spawning process
	 * @return list<string>
	 */
	public static function resolveWorkerIniEntries(array $opcacheArgs, string $osFamily, int $parentPid, int $workerNumber): array
	{
		$entries = $opcacheArgs;
		if ($entries !== [] && $osFamily === 'Windows') {
			$entries[] = sprintf('opcache.cache_id=phpstan-%d-%d', $parentPid, $workerNumber);
		}
		$entries[] = TurboProcessRestarter::RESTARTED_INI . '=1';

		return $entries;
	}

}
