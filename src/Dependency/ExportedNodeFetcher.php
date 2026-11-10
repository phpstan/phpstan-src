<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PhpParser\NodeTraverser;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;

#[AutowiredService]
final class ExportedNodeFetcher
{

	public function __construct(
		#[AutowiredParameter(ref: '@defaultAnalysisParser')]
		private Parser $parser,
		private ExportedNodeVisitor $visitor,
	)
	{
	}

	/**
	 * @return RootExportedNode[]
	 */
	public function fetchNodes(string $fileName): array
	{
		return $this->fetchNodesIfParsable($fileName) ?? [];
	}

	/**
	 * @return RootExportedNode[]|null null when the file has a parse error
	 */
	public function fetchNodesIfParsable(string $fileName): ?array
	{
		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor($this->visitor);

		try {
			$ast = $this->parser->parseFile($fileName);
		} catch (ParserErrorsException) {
			return null;
		}
		$this->visitor->reset($fileName);
		$nodeTraverser->traverse($ast);

		return $this->visitor->getExportedNodes();
	}

}
