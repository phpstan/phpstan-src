<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PhpParser\NodeTraverser;
use PHPStan\File\FileReader;

class ExportedNodeFetcher
{

	private \PhpParser\Parser $phpParser;

	private ExportedNodeVisitor $visitor;

	public function __construct(
		\PhpParser\Parser $phpParser,
		ExportedNodeVisitor $visitor
	)
	{
		$this->phpParser = $phpParser;
		$this->visitor = $visitor;
	}

	/**
	 * @param string $fileName
	 * @return ExportedNode[]
	 */
	public function fetchNodes(string $fileName): array
	{
		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor($this->visitor);

		$contents = FileReader::read($fileName);

		try {
			/** @var \PhpParser\Node[] $ast */
			$ast = $this->phpParser->parse($contents);
		} catch (\PhpParser\Error $e) {
			return [];
		}
		$this->visitor->reset($fileName);
		$nodeTraverser->traverse($ast);

		return $this->visitor->getExportedNodes();
	}

}
