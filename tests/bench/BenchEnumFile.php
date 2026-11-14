<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\Enum\EnumCaseObjectType;
use Tally\ScratchDirectory;
use function enum_exists;
use function file_put_contents;
use function implode;
use function sprintf;

/**
 * Enums with a given number of cases, for the benchmarks of large enums. Each is written into the
 * scratch directory and loaded, so reflection finds it through its file like any loaded class.
 */
final class BenchEnumFile
{

	/**
	 * @return string the name of the enum, its cases are C0, C1, …
	 */
	public static function create(ScratchDirectory $scratch, string $name, int $cases): string
	{
		$className = 'PHPStanBenchEnum\\' . $name;
		if (enum_exists($className)) {
			return $className;
		}

		$lines = [];
		for ($i = 0; $i < $cases; $i++) {
			$lines[] = sprintf("\tcase C%d;", $i);
		}

		$file = $scratch->path() . '/' . $name . '.php';
		$code = sprintf("<?php declare(strict_types = 1);\n\nnamespace PHPStanBenchEnum;\n\nenum %s\n{\n\n%s\n\n}\n", $name, implode("\n", $lines));
		if (file_put_contents($file, $code) === false) {
			throw new LogicException(sprintf('Could not write %s.', $file));
		}
		require_once $file;

		return $className;
	}

	/**
	 * @return list<EnumCaseObjectType>
	 */
	public static function cases(string $className, int $cases): array
	{
		$types = [];
		for ($i = 0; $i < $cases; $i++) {
			$types[] = new EnumCaseObjectType($className, 'C' . $i);
		}

		return $types;
	}

}
