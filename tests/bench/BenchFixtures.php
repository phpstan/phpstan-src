<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use Tally\ScratchDirectory;
use function file_put_contents;
use function md5;

/**
 * Declares classes a microbenchmark reflects, generated for its data set. Reflection finds them
 * through the file they are declared in, which the native run and the counted run share.
 */
final class BenchFixtures
{

	public static function declare(ScratchDirectory $scratch, string $code): void
	{
		$file = $scratch->path() . '/fixtures-' . md5($code) . '.php';
		file_put_contents($file, "<?php\n\n" . $code);
		require_once $file;
	}

}
