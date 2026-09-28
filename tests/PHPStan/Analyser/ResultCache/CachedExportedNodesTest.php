<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Override;
use PHPStan\Dependency\ExportedNode\ExportedTraitNode;
use PHPUnit\Framework\TestCase;
use function file_put_contents;
use function fopen;
use function serialize;
use function strlen;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class CachedExportedNodesTest extends TestCase
{

	private string $file;

	/** @var array<string, array{int, positive-int}> */
	private array $locations;

	#[Override]
	protected function setUp(): void
	{
		parent::setUp();

		$file = tempnam(sys_get_temp_dir(), 'phpstan-cached-nodes-');
		$this->assertIsString($file);
		$this->file = $file;

		$header = "some other section\n";
		$first = serialize([$this->node('First')]);
		$second = serialize([$this->node('Second'), $this->node('Another')]);
		file_put_contents($this->file, $header . $first . $second);
		$firstLength = strlen($first);
		$secondLength = strlen($second);
		if ($firstLength === 0 || $secondLength === 0) {
			$this->fail('Serialized nodes are never empty.');
		}

		$this->locations = [
			'/project/First.php' => [strlen($header), $firstLength],
			'/project/Second.php' => [strlen($header) + $firstLength, $secondLength],
		];
	}

	#[Override]
	protected function tearDown(): void
	{
		@unlink($this->file);
		parent::tearDown();
	}

	private function node(string $name): ExportedTraitNode
	{
		return new ExportedTraitNode($name, null, [], [], [], []);
	}

	private function create(): CachedExportedNodes
	{
		$handle = fopen($this->file, 'r');
		$this->assertNotFalse($handle);

		return CachedExportedNodes::createFromFile($handle, $this->locations);
	}

	public function testDecodesOneFileWithoutTheOthers(): void
	{
		$nodes = $this->create();

		$this->assertEquals([$this->node('Second'), $this->node('Another')], $nodes->decode('/project/Second.php'));
		$this->assertEquals([$this->node('First')], $nodes->decode('/project/First.php'));
		$this->assertSame(serialize([$this->node('First')]), $nodes->read('/project/First.php'));
		$this->assertSame(strlen(serialize([$this->node('First')])), $nodes->getLength('/project/First.php'));
		$this->assertSame(
			serialize([$this->node('First')]) . serialize([$this->node('Second'), $this->node('Another')]),
			$nodes->readRange($nodes->getOffset('/project/First.php'), $nodes->getLength('/project/First.php') + $nodes->getLength('/project/Second.php')),
		);
	}

	public function testOnlyAndWithout(): void
	{
		$nodes = $this->create();

		$this->assertSame(['/project/First.php', '/project/Second.php'], $nodes->getFiles());
		$this->assertSame(['/project/Second.php'], $nodes->only(['/project/Second.php' => true, '/project/Unknown.php' => true])->getFiles());
		$this->assertSame(['/project/First.php'], $nodes->without(['/project/Second.php' => true])->getFiles());
		$this->assertFalse($nodes->without(['/project/Second.php' => true])->has('/project/Second.php'));
		$this->assertTrue($nodes->has('/project/Second.php'));
		// the instances share the file
		$this->assertEquals([$this->node('First')], $nodes->only(['/project/First.php' => true])->decode('/project/First.php'));
	}

	public function testUnknownFile(): void
	{
		$this->expectException(CachedExportedNodesUnreadableException::class);
		$this->create()->decode('/project/Unknown.php');
	}

	public function testTruncatedFile(): void
	{
		$nodes = $this->create();
		file_put_contents($this->file, 'too short');

		$this->expectException(CachedExportedNodesUnreadableException::class);
		$nodes->decode('/project/Second.php');
	}

	public function testClosed(): void
	{
		$nodes = $this->create();
		$nodes->close();

		$this->expectException(CachedExportedNodesUnreadableException::class);
		$nodes->read('/project/First.php');
	}

	public function testEmpty(): void
	{
		$nodes = CachedExportedNodes::createEmpty();

		$this->assertSame([], $nodes->getFiles());
		$this->assertFalse($nodes->has('/project/First.php'));
		$nodes->close();
	}

}
