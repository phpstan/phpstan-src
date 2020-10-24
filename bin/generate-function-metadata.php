#!/usr/bin/env php
<?php declare(strict_types=1);

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

(function () {
	require_once __DIR__ . '/../vendor/autoload.php';

	$parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
	$finder = new Symfony\Component\Finder\Finder();
	$finder->in(__DIR__ . '/../vendor/jetbrains/phpstorm-stubs')->files()->name('*.php');

	$visitor = new class() extends \PhpParser\NodeVisitorAbstract {

		/** @var string[] */
		public $functions = [];

		public function enterNode(Node $node)
		{
			if (!$node instanceof Node\Stmt\Function_) {
				return;
			}

			foreach ($node->attrGroups as $attrGroup) {
				foreach ($attrGroup->attrs as $attr) {
					if ($attr->name->toString() === \JetBrains\PhpStorm\Pure::class) {
						$this->functions[] = $node->namespacedName->toLowerString();
						break;
					}
				}
			}

			return null;
		}
	};

	foreach ($finder as $stubFile) {
		$path = $stubFile->getPathname();
		$traverser = new NodeTraverser();
		$traverser->addVisitor(new NameResolver());
		$traverser->addVisitor($visitor);

		$traverser->traverse(
			$parser->parse(\PHPStan\File\FileReader::read($path))
		);
	}

	$metadata = require __DIR__ . '/functionMetadata_original.php';
	foreach ($visitor->functions as $functionName) {
		if (array_key_exists($functionName, $metadata)) {
			if ($metadata[$functionName]['hasSideEffects']) {
				throw new \PHPStan\ShouldNotHappenException($functionName);
			}
		}
		$metadata[$functionName] = ['hasSideEffects' => false];
	}

	ksort($metadata);

	$template = <<<'php'
<?php declare(strict_types = 1);

return [
%s
];
php;
	$content = '';
	foreach ($metadata as $name => $meta) {
		$content .= sprintf(
			"\t%s => [%s => %s],\n",
			var_export($name, true),
			var_export('hasSideEffects', true),
			var_export($meta['hasSideEffects'], true),
		);
	}

	\PHPStan\File\FileWriter::write(__DIR__ . '/../resources/functionMetadata.php', sprintf($template, $content));

})();
