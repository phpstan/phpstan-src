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
		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor($this->visitor);

		try {
			$ast = $this->parser->parseFile($fileName);
		} catch (ParserErrorsException) {
			return [];
		}
		$this->visitor->reset($fileName);
		$nodeTraverser->traverse($ast);

		return $this->visitor->getExportedNodes();
	}

}
