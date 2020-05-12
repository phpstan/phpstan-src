#!/usr/bin/env php
<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('memory_limit', '512M');

use PhpParser\Lexer;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use PhpParser\Parser;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter;
use PhpParser\Node;

class PhpPatcher extends NodeVisitorAbstract
{

	public function leaveNode(Node $node)
	{
		if (!$node instanceof Node\Stmt\Property) {
			return null;
		}
		if ($node->type === null) {
			return null;
		}
		$docComment = $node->getDocComment();
		if ($docComment !== null) {
			$node->type = null;
			return $node;
		}

		$node->setDocComment(new \PhpParser\Comment\Doc(sprintf('/** @var %s */', $this->printType($node->type))));
		$node->type = null;

		return $node;
	}

	/**
	 * @param Identifier|Name|NullableType|UnionType $type
	 * @return string
	 */
	private function printType($type): string
	{
		if ($type instanceof NullableType) {
			return '?' . $this->printType($type->type);
		}

		if ($type instanceof UnionType) {
			throw new \Exception('UnionType not yet supported');
		}

		if ($type instanceof Name) {
			$name = $type->toString();
			if ($type->isFullyQualified()) {
				return '\\' . $name;
			}

			return $name;
		}

		if ($type instanceof Identifier) {
			return $type->name;
		}

		throw new \Exception('Unsupported type class');
	}

}

(function () {
	$dir = __DIR__ . '/../src';

	$lexer = new Lexer\Emulative([
		'usedAttributes' => [
			'comments',
			'startLine', 'endLine',
			'startTokenPos', 'endTokenPos',
		],
	]);
	$parser = new Parser\Php7($lexer, [
		'useIdentifierNodes' => true,
		'useConsistentVariableNodes' => true,
		'useExpressionStatements' => true,
		'useNopStatements' => false,
	]);
	$nameResolver = new NodeVisitor\NameResolver(null, [
		'replaceNodes' => false
	]);

	$printer = new PrettyPrinter\Standard();

	$traverser = new NodeTraverser();
	$traverser->addVisitor(new NodeVisitor\CloningVisitor());
	$traverser->addVisitor($nameResolver);
	$traverser->addVisitor(new PhpPatcher($printer));

	$it = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir),
		RecursiveIteratorIterator::LEAVES_ONLY
	);
	foreach ($it as $file) {
		$fileName = $file->getPathname();
		if (!preg_match('/\.php$/', $fileName)) {
			continue;
		}

		$code = \PHPStan\File\FileReader::read($fileName);
		$origStmts = $parser->parse($code);
		$newCode = $printer->printFormatPreserving(
			$traverser->traverse($origStmts),
			$origStmts,
			$lexer->getTokens()
		);

		\PHPStan\File\FileWriter::write($fileName, $newCode);
	}
})();
