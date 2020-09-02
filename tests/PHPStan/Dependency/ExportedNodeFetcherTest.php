<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Dependency\ExportedNode\ExportedTraitNode;
use PHPStan\File\FileReader;
use PHPStan\Testing\TestCase;
use PHPStan\Type\FileTypeMapper;
use Symfony\Component\Finder\Finder;
use function pathinfo;

class ExportedNodeFetcherTest extends TestCase
{

	public function dataFetchNodes(): iterable
	{
		$finder = new Finder();
		$finder->followLinks();
		foreach ($finder->files()->name('*.php')->in(__DIR__ . '/../../../src') as $fileInfo) {
			yield [$fileInfo->getPathname()];
		}
	}

	/**
	 * @dataProvider dataFetchNodes
	 * @param string $fileName
	 */
	public function testFetchNodes(string $fileName): void
	{
		/** @var \PhpParser\Parser $parser */
		$parser = self::getContainer()->getService('phpParserDecorator');
		$fetcher = new ExportedNodeFetcher($parser, new ExportedNodeVisitor(new ExportedNodeResolver(self::getContainer()->getByType(FileTypeMapper::class), new Standard())));

		/** @var DependencyResolver $dependencyResolver */
		$dependencyResolver = self::getContainer()->getByType(DependencyResolver::class);

		/** @var NodeScopeResolver $nodesScopeResolver */
		$nodesScopeResolver = self::getContainer()->getByType(NodeScopeResolver::class);

		$exportedNodes = [];

		/** @var ScopeFactory $scopeFactory */
		$scopeFactory = self::getContainer()->getByType(ScopeFactory::class);

		$ast = $parser->parse(FileReader::read($fileName));
		if ($ast === null) {
			$this->fail();
		}

		$nodesScopeResolver->processNodes(
			$ast,
			$scopeFactory->create(ScopeContext::create($fileName)),
			static function (\PhpParser\Node $node, Scope $scope) use ($dependencyResolver, &$exportedNodes): void {
				$nodeDependencies = $dependencyResolver->resolveDependencies($node, $scope);
				if ($nodeDependencies->getExportedNode() === null) {
					return;
				}

				$exportedNodes[] = $nodeDependencies->getExportedNode();
			}
		);

		$fetchedNodes = $fetcher->fetchNodes($fileName);
		$this->assertCount(count($exportedNodes), $fetchedNodes, pathinfo($fileName, PATHINFO_BASENAME));

		foreach ($exportedNodes as $i => $exportedNode) {
			$fetchedNode = $fetchedNodes[$i];
			if ($exportedNode instanceof ExportedTraitNode || $fetchedNode instanceof ExportedTraitNode) {
				$this->assertFalse($fetchedNode->equals($exportedNode));
				continue;
			}
			$this->assertTrue($fetchedNode->equals($exportedNode));
		}
	}

}
