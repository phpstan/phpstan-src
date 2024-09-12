#!/usr/bin/env php
<?php declare(strict_types = 1);

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Finder\Finder;

$dir = $argv[1];

if (!str_starts_with($dir, '/')) {
	$dir = getcwd() . '/' . $dir;
}

(static function () use ($dir): void {
	require_once __DIR__ . '/../vendor/autoload.php';

	$parser = (new ParserFactory())->createForHostVersion();
	$traverser = new NodeTraverser(new CloningVisitor());
	$printer = new Standard();
	$finder = new Finder();
	$finder->followLinks();

	$removeParamDefaultTraverser = new NodeTraverser(new class () extends NodeVisitorAbstract {

		public function enterNode(Node $node)
		{
			if (!$node instanceof Node\Param) {
				return null;
			}

			$node->default = null;

			return $node;
		}

	});
	foreach ($finder->files()->name('*.php')->in($dir) as $fileInfo) {
		$oldStmts = $parser->parse(file_get_contents($fileInfo->getPathname()));
		$oldTokens = $parser->getTokens();

		$newStmts = $traverser->traverse($oldStmts);
		$newStmts = $removeParamDefaultTraverser->traverse($newStmts);

		$newCode = $printer->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
		file_put_contents($fileInfo->getPathname(), $newCode);
	}
})();
