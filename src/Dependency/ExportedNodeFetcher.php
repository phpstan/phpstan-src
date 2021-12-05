<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PhpParser\NodeTraverser;
use PHPStan\Parser\Parser;

class ExportedNodeFetcher
{

	private Parser $parser;

	private ExportedNodeVisitor $visitor;

	public function __construct(
		Parser $parser,
		ExportedNodeVisitor $visitor
	)
	{
		$this->parser = $parser;
		$this->visitor = $visitor;
	}

	/**
	 * @return ExportedNode[]
	 */
	public function fetchNodes(string $fileName): array
	{
		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor($this->visitor);

		try {
			/** @var \PhpParser\Node[] $ast */
			$ast = $this->parser->parseFile($fileName);
		} catch (\PHPStan\Parser\ParserErrorsException $e) {
			return [];
		}
		$this->visitor->reset($fileName);
		$nodeTraverser->traverse($ast);

		return $this->visitor->getExportedNodes();
	}

}
