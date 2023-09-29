#!/usr/bin/env php
<?php declare(strict_types = 1);

use JetBrains\PhpStorm\Pure;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\NodeConnectingVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Finder\Finder;

(function (): void {
	require_once __DIR__ . '/../vendor/autoload.php';

	$parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
	$finder = new Finder();
	$finder->in(__DIR__ . '/../vendor/jetbrains/phpstorm-stubs')->files()->name('*.php');

	$visitor = new class() extends NodeVisitorAbstract {

		/** @var string[] */
		public array $functions = [];

		/** @var string[] */
		public array $methods = [];

		public function enterNode(Node $node)
		{
			if ($node instanceof Node\Stmt\Function_) {
				foreach ($node->attrGroups as $attrGroup) {
					foreach ($attrGroup->attrs as $attr) {
						if ($attr->name->toString() === Pure::class) {
							$this->functions[] = $node->namespacedName->toLowerString();
							break 2;
						}
					}
				}
			}

			if ($node instanceof Node\Stmt\ClassMethod) {
				$class = $node->getAttribute('parent');
				if (!$class instanceof Node\Stmt\ClassLike) {
					throw new ShouldNotHappenException($node->name->toString());
				}
				$className = $class->namespacedName->toString();
				foreach ($node->attrGroups as $attrGroup) {
					foreach ($attrGroup->attrs as $attr) {
						if ($attr->name->toString() === Pure::class) {
							$this->methods[] = sprintf('%s::%s', $className, $node->name->toString());
							break 2;
						}
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
		$traverser->addVisitor(new NodeConnectingVisitor());
		$traverser->addVisitor($visitor);

		$traverser->traverse(
			$parser->parse(FileReader::read($path)),
		);
	}

	$metadata = require __DIR__ . '/functionMetadata_original.php';
	foreach ($visitor->functions as $functionName) {
		if (array_key_exists($functionName, $metadata)) {
			if ($metadata[$functionName]['hasSideEffects']) {
				if (in_array($functionName, [
					'mt_rand',
					'rand',
					'random_bytes',
					'random_int',
					'connection_aborted',
					'connection_status',
				], true)) {
					continue;
				}
				throw new ShouldNotHappenException($functionName);
			}
		}
		$metadata[$functionName] = ['hasSideEffects' => false];
	}

	foreach ($visitor->methods as $methodName) {
		if (array_key_exists($methodName, $metadata)) {
			if ($metadata[$methodName]['hasSideEffects']) {
				throw new ShouldNotHappenException($methodName);
			}
		}
		$metadata[$methodName] = ['hasSideEffects' => false];
	}

	ksort($metadata);

	$template = <<<'php'
<?php declare(strict_types = 1);

/**
 * GENERATED FILE - DO NOT EDIT!
 *
 * This file is generated automatically when running bin/generate-function-metadata.php
 * and the result is merged from bin/functionMetadata_original.php and by looking at jetbrains/phpstorm-stubs methods
 * and functions with the #[Pure] attribute.
 *
 * If you want to add new entries here follow these steps:
 * 1) verify on https://phpstan.org/try whether the entry you are going to add does not already work as expected.
 * 2) Contribute the functions that have 'hasSideEffects' => true as a modification to bin/functionMetadata_original.php.
 * 3) Contribute the #[Pure] functions without side effects to https://github.com/JetBrains/phpstorm-stubs
 * 4) Once the PR from 3) is merged, please update the package here and run ./bin/generate-function-metadata.php.
 */

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

	FileWriter::write(__DIR__ . '/../resources/functionMetadata.php', sprintf($template, $content));
})();
