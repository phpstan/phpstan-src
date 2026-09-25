<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Testing\PHPStanTestCase;
use function file_put_contents;
use function gc_collect_cycles;
use function memory_get_usage;
use function str_repeat;
use function strlen;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

class FileNodesFetcherTest extends PHPStanTestCase
{

	private const FETCH_COUNT_LIMIT = 10;

	public function testFetchingAnUnchangedFileAgainKeepsNoFurtherCopy(): void
	{
		$fileName = sys_get_temp_dir() . '/phpstan-file-nodes-fetcher-' . uniqid() . '.php';
		$contents = "<?php\n" . str_repeat(' ', 1_048_576) . "\nfunction fooFunction(): void {}\nfunction barFunction(): void {}\n";
		file_put_contents($fileName, $contents);

		try {
			$fetcher = self::getContainer()->getByType(FileNodesFetcher::class);
			// the second parse leaves the parser's AST cache holding one more copy of its source-code key
			$results = [$fetcher->fetchNodes($fileName), $fetcher->fetchNodes($fileName)];

			gc_collect_cycles();
			$memoryBefore = memory_get_usage();
			for ($i = 0; $i < self::FETCH_COUNT_LIMIT; $i++) {
				$results[] = $fetcher->fetchNodes($fileName);
			}
			gc_collect_cycles();

			// every result keeps its located sources alive, so a copy per fetch would add up to 10 MB
			$this->assertLessThan(strlen($contents), memory_get_usage() - $memoryBefore);
			foreach ($results as $result) {
				$this->assertCount(2, $result->getFunctionNodes());
			}
		} finally {
			@unlink($fileName);
		}
	}

}
