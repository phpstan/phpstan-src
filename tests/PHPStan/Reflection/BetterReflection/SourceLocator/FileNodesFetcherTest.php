<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Testing\PHPStanTestCase;
use function file_put_contents;
use function memory_get_usage;
use function str_repeat;
use function strlen;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

class FileNodesFetcherTest extends PHPStanTestCase
{

	public function testFetchingAnUnchangedOneMegabyteFileAgainKeepsNoFurtherCopy(): void
	{
		$fileName = sys_get_temp_dir() . '/phpstan-file-nodes-fetcher-' . uniqid() . '.php';
		$contents = "<?php\n" . str_repeat(' ', 1_048_576) . "\nfunction fooFunction(): void {}\nfunction barFunction(): void {}\n";
		file_put_contents($fileName, $contents);

		try {
			$fetcher = self::getContainer()->getByType(FileNodesFetcher::class);
			$first = $fetcher->fetchNodes($fileName);
			// the parser's AST cache keeps the most recently read string as its key
			$second = $fetcher->fetchNodes($fileName);
			$memoryBefore = memory_get_usage();
			$third = $fetcher->fetchNodes($fileName);

			$this->assertLessThan(strlen($contents) / 2, memory_get_usage() - $memoryBefore);
			$this->assertSame(
				$first->getFunctionNodes()['foofunction'][0]->getLocatedSource()->getSource(),
				$third->getFunctionNodes()['barfunction'][0]->getLocatedSource()->getSource(),
			);
			$this->assertCount(2, $second->getFunctionNodes());
		} finally {
			@unlink($fileName);
		}
	}

}
