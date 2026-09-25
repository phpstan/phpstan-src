<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use JsonException;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ReflectionProvider\DummyReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_map;
use function json_decode;
use function json_encode;
use function serialize;
use function substr_count;
use const JSON_THROW_ON_ERROR;

final class ExportedNodeResolverTest extends PHPStanTestCase
{

	/**
	 * @return RootExportedNode[]
	 */
	private function fetchNodes(string $file, CountingReflectionProvider $reflectionProvider): array
	{
		$resolver = new ExportedNodeResolver(
			$reflectionProvider,
			self::getContainer()->getByType(ExprPrinter::class),
		);

		/** @var Parser $parser */
		$parser = self::getContainer()->getService('defaultAnalysisParser');

		return (new ExportedNodeFetcher($parser, new ExportedNodeVisitor($resolver)))->fetchNodes($file);
	}

	public function testPropertiesWithoutHooksDoNotLookUpTheClass(): void
	{
		// A property without hooks can never be virtual, so answering that must not reach for
		// reflection: during a result cache restore this runs in the main process and the first
		// lookup boots BetterReflection and the stub files in front of the analysis.
		$reflectionProvider = new CountingReflectionProvider(new DummyReflectionProvider());
		$nodes = $this->fetchNodes(__DIR__ . '/data/exported-properties-no-hooks.php', $reflectionProvider);

		$this->assertNotSame([], $nodes);
		$this->assertSame(0, $reflectionProvider->hasClassCallCount);
	}

	// The parser follows the analysed PHP version, which defaults to the running one, so the hooks
	// in the data file do not parse below 8.4 and no nodes come out at all.
	#[RequiresPhp('>= 8.4.0')]
	public function testHookedPropertyStillLooksUpTheClass(): void
	{
		$reflectionProvider = new CountingReflectionProvider(new DummyReflectionProvider());
		$nodes = $this->fetchNodes(__DIR__ . '/data/exported-properties-hooks.php', $reflectionProvider);

		$this->assertNotSame([], $nodes);
		$this->assertGreaterThan(0, $reflectionProvider->hasClassCallCount);
	}

	/**
	 * @throws JsonException
	 */
	public function testUsesAreStoredOncePerFile(): void
	{
		$reflectionProvider = new CountingReflectionProvider(new DummyReflectionProvider());
		$nodes = $this->fetchNodes(__DIR__ . '/data/exported-phpdoc-namespace-uses.php', $reflectionProvider);
		$this->assertCount(2, $nodes);
		$this->assertUsesStoredOnce($nodes);

		// a parallel worker sends the nodes as JSON, which repeats the uses for every PHPDoc
		$decoder = new ExportedNodeDecoder();
		$decodedNodes = array_map(static function (array $node) use ($decoder): ExportedNode {
			/** @var class-string<RootExportedNode> $class */
			$class = $node['type'];

			return $class::decode($node['data'], $decoder);
		}, json_decode(json_encode($nodes, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR));
		$this->assertCount(2, $decodedNodes);
		foreach ($nodes as $i => $node) {
			$this->assertTrue($node->equals($decodedNodes[$i]));
		}
		$this->assertUsesStoredOnce($decodedNodes);
	}

	/**
	 * @param ExportedNode[] $nodes
	 */
	private function assertUsesStoredOnce(array $nodes): void
	{
		$serialized = serialize($nodes);
		$this->assertSame(1, substr_count($serialized, 'ExportedPhpDocNamespaceUses\\Models\\ModelOne'));
		$this->assertSame(1, substr_count($serialized, 'ExportedPhpDocNamespaceUses\\Models\\ModelTwo'));
		$this->assertSame(1, substr_count($serialized, 'ExportedPhpDocNamespaceUses\\Models\\SOME_CONSTANT'));
	}

}
