<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\Testing\PHPStanTestCase;
use function file_put_contents;
use function mkdir;
use function sys_get_temp_dir;
use function uniqid;

final class DirectoryWalkerTest extends PHPStanTestCase
{

	private string $directory;

	protected function setUp(): void
	{
		parent::setUp();

		$this->directory = sys_get_temp_dir() . '/' . uniqid('phpstan-walker-', true);
		mkdir($this->directory);
		file_put_contents($this->directory . '/first.php', '<?php');
	}

	public function testWalkAlwaysSeesTheCurrentState(): void
	{
		$walker = new DirectoryWalker();

		$beforeAdding = $walker->walk($this->directory, ['php']);
		file_put_contents($this->directory . '/second.php', '<?php');
		$afterAdding = $walker->walk($this->directory, ['php']);

		$this->assertCount(1, $beforeAdding);
		$this->assertCount(2, $afterAdding);
	}

	public function testWalkCachedIsSharedBetweenCalls(): void
	{
		$walker = new DirectoryWalker();

		$firstWalk = $walker->walkCached($this->directory, ['php']);
		file_put_contents($this->directory . '/second.php', '<?php');
		// The analyse and the scan FileFinder must see one and the same file set - the analysis
		// works off a snapshot taken before it starts.
		$secondWalk = $walker->walkCached($this->directory, ['php']);
		$walker->clearCachedWalks();
		$walkAfterClearing = $walker->walkCached($this->directory, ['php']);

		$this->assertCount(1, $firstWalk);
		$this->assertCount(1, $secondWalk);
		$this->assertCount(2, $walkAfterClearing);
	}

	public function testCachedWalksAreKeyedByExtensions(): void
	{
		$walker = new DirectoryWalker();
		file_put_contents($this->directory . '/notes.txt', 'x');

		$phpFiles = $walker->walkCached($this->directory, ['php']);
		$textFiles = $walker->walkCached($this->directory, ['txt']);

		$this->assertCount(1, $phpFiles);
		$this->assertCount(1, $textFiles);
	}

}
