<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;

class ExportedNodeFetcher
{

	private Parser $parser;

	private ExportedNodeVisitor $visitor;

	public function __construct(
		Parser $parser,
		ExportedNodeVisitor $visitor,
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
			/** @var Node[] $ast */
			$ast = $this->parser->parseFile($fileName);
		} catch (ParserErrorsException $e) {
			return [];
		}
		$this->visitor->reset($fileName);
		$nodeTraverser->traverse($ast);

		return $this->visitor->getExportedNodes();
	}

}
