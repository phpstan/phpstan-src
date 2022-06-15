<?php declare(strict_types = 1);

namespace PHPStan\Process;

use function count;
use function fgets;
use function file_get_contents;
use function function_exists;
use function is_file;
use function is_numeric;
use function is_resource;
use function pclose;
use function popen;
use function preg_match_all;
use const DIRECTORY_SEPARATOR;

class CpuCoreCounter
{

	private ?int $count = null;

	public function getNumberOfCpuCores(): int
	{
		if ($this->count !== null) {
			return $this->count;
		}

		if (!function_exists('proc_open')) {
			return $this->count = 1;
		}

		// Support for Kubernetes pods with limited resources
		// See: https://github.com/phpstan/phpstan/issues/7479
		if (@is_file('/sys/fs/cgroup/cpu/cpu.cfs_quota_us') && @is_file('/sys/fs/cgroup/cpu/cpu.cfs_period_us')) {
			$quota = @file_get_contents('/sys/fs/cgroup/cpu/cpu.cfs_quota_us');
			$period = @file_get_contents('/sys/fs/cgroup/cpu/cpu.cfs_period_us');

			if (is_numeric($quota) && is_numeric($period) && $period > 0) {
				return $this->count = (int) ($quota / $period);
			}
		}

		// from brianium/paratest
		if (@is_file('/proc/cpuinfo')) {
			// Linux (and potentially Windows with linux sub systems)
			$cpuinfo = @file_get_contents('/proc/cpuinfo');
			if ($cpuinfo !== false) {
				preg_match_all('/^processor/m', $cpuinfo, $matches);
				return $this->count = count($matches[0]);
			}
		}

		if (DIRECTORY_SEPARATOR === '\\') {
			// Windows
			$process = @popen('wmic cpu get NumberOfLogicalProcessors', 'rb');
			if (is_resource($process)) {
				fgets($process);
				$cores = (int) fgets($process);
				pclose($process);

				return $this->count = $cores;
			}
		}

		$process = @popen('sysctl -n hw.ncpu', 'rb');
		if (is_resource($process)) {
			// *nix (Linux, BSD and Mac)
			$cores = (int) fgets($process);
			pclose($process);

			return $this->count = $cores;
		}

		return $this->count = 2;
	}

}
