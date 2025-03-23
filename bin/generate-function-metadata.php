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

	$parser = (new ParserFactory())->createForNewestSupportedVersion();
	$finder = new Finder();
	$finder->in(__DIR__ . '/../vendor/jetbrains/phpstorm-stubs')->files()->name('*.php');

	$visitor = new class() extends NodeVisitorAbstract {

		/** @var string[] */
		public array $functions = [];

		/** @var list<string> */
		public array $impureFunctions = [];

		/** @var string[] */
		public array $methods = [];

		public function enterNode(Node $node)
		{
			if ($node instanceof Node\Stmt\Function_) {
				assert(isset($node->namespacedName));
				$functionName = $node->namespacedName->toLowerString();

				foreach ($node->attrGroups as $attrGroup) {
					foreach ($attrGroup->attrs as $attr) {
						if ($attr->name->toString() !== Pure::class) {
							continue;
						}

						// The following functions have side effects, but their state is managed within the PHPStan scope:
						if (in_array($functionName, [
							'stat',
							'lstat',
							'file_exists',
							'is_writable',
							'is_writeable',
							'is_readable',
							'is_executable',
							'is_file',
							'is_dir',
							'is_link',
							'filectime',
							'fileatime',
							'filemtime',
							'fileinode',
							'filegroup',
							'fileowner',
							'filesize',
							'filetype',
							'fileperms',
							'ftell',
							'ini_get',
							'function_exists',
							'json_last_error',
							'json_last_error_msg',
						], true)) {
							$this->functions[] = $functionName;
							break 2;
						}

						// PhpStorm stub's #[Pure(true)] means the function has side effects but its return value is important.
						// In PHPStan's criteria, these functions are simply considered as ['hasSideEffect' => true].
						if (isset($attr->args[0]->value->name->name) && $attr->args[0]->value->name->name === 'true') {
							$this->impureFunctions[] = $functionName;
						} else {
							$this->functions[] = $functionName;
						}
						break 2;
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

	/** @var array<string, array{hasSideEffects: bool}> $metadata */
	$metadata = require __DIR__ . '/functionMetadata_original.php';
	foreach ($visitor->functions as $functionName) {
		if (array_key_exists($functionName, $metadata)) {
			if ($metadata[$functionName]['hasSideEffects']) {
				throw new ShouldNotHappenException($functionName);
			}
		}
		$metadata[$functionName] = ['hasSideEffects' => false];
	}
	foreach ($visitor->impureFunctions as $functionName) {
		if (array_key_exists($functionName, $metadata)) {
			if (in_array($functionName, [
				'ob_get_contents',
			], true)) {
				continue;
			}
			if ($metadata[$functionName]['hasSideEffects']) {
				throw new ShouldNotHappenException($functionName);
			}
		}
		$metadata[$functionName] = ['hasSideEffects' => true];
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
