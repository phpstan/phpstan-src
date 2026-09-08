<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ReflectionProvider\DummyReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

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

	public function testHookedPropertyStillLooksUpTheClass(): void
	{
		$reflectionProvider = new CountingReflectionProvider(new DummyReflectionProvider());
		$nodes = $this->fetchNodes(__DIR__ . '/data/exported-properties-hooks.php', $reflectionProvider);

		$this->assertNotSame([], $nodes);
		$this->assertGreaterThan(0, $reflectionProvider->hasClassCallCount);
	}

}
