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
use PHPStan\Reflection\SideEffects;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Finder\Finder;

(function (): void {
	require_once __DIR__ . '/../vendor/autoload.php';

	$parser = (new ParserFactory())->createForNewestSupportedVersion();
	$finder = new Finder();
	$finder->in(__DIR__ . '/../vendor/jetbrains/phpstorm-stubs')->exclude('tests')->files()->name('*.php');

	$visitor = new class() extends NodeVisitorAbstract {

		/** @var string[] */
		public array $functions = [];

		/** @var list<string> */
		public array $impureFunctions = [];

		/** @var list<string> */
		public array $managedStateFunctions = [];

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

						// The following functions read global state, but PHPStan tracks that state in the scope,
						// so they stay pure for the rules:
						if (in_array($functionName, [
							'ftell',
							'ini_get',
							'function_exists',
							'ob_get_level',
						], true)) {
							$this->managedStateFunctions[] = $functionName;
							break 2;
						}

						// PhpStorm stub's #[Pure(true)] means the function writes nothing, but its result
						// depends on global or external state, so it is not remembered either.
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
				if (!isset($class->namespacedName)) {
					// anonymous class
					return null;
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

	/** @var array<string, array{effects: list<string>, hasSideEffects?: false, pureUnlessCallableIsImpureParameters?: array<string, bool>, pureUnlessParameterPassedParameters?: array<string, bool>}> $metadata */
	$metadata = require __DIR__ . '/functionMetadata_original.php';
	$leavesError = static fn (array $effects): bool => count(array_filter(
		$effects,
		static fn (string $effect): bool => str_starts_with($effect, SideEffects::WRITES_GLOBAL_ERROR . '.'),
	)) > 0;
	foreach ($visitor->functions as $functionName) {
		if (array_key_exists($functionName, $metadata)) {
			// reading state PHPStan tracks itself keeps the function pure for the rules,
			// and so does leaving an error for an error getter
			if (
				$metadata[$functionName]['effects'] !== []
				&& ($metadata[$functionName]['hasSideEffects'] ?? null) !== false
				&& !$leavesError($metadata[$functionName]['effects'])
			) {
				throw new ShouldNotHappenException($functionName);
			}

			continue;
		}
		$metadata[$functionName] = ['effects' => []];
	}
	foreach ($visitor->managedStateFunctions as $functionName) {
		if (array_key_exists($functionName, $metadata)) {
			throw new ShouldNotHappenException($functionName);
		}
		$metadata[$functionName] = ['effects' => ['reads-global'], 'hasSideEffects' => false];
	}
	foreach ($visitor->impureFunctions as $functionName) {
		if (in_array($functionName, [
			'class_exists',
			'enum_exists',
			'interface_exists',
			'trait_exists',
		], true)) {
			continue;
		}
		if (array_key_exists($functionName, $metadata)) {
			if (in_array($functionName, [
				'ob_get_contents',
			], true)) {
				continue;
			}
			if ($metadata[$functionName]['effects'] === []) {
				throw new ShouldNotHappenException($functionName);
			}

			continue;
		}
		$metadata[$functionName] = ['effects' => ['reads-global', 'nondeterministic']];
	}

	foreach ($visitor->methods as $methodName) {
		if (array_key_exists($methodName, $metadata)) {
			if ($leavesError($metadata[$methodName]['effects'])) {
				continue;
			}
			if ($metadata[$methodName]['effects'] !== []) {
				throw new ShouldNotHappenException($methodName);
			}
		}
		$metadata[$methodName] = ['effects' => []];
	}

	ksort($metadata);

	$template = <<<'php'
<?php declare(strict_types = 1);

use PHPStan\Reflection\SideEffects;

/**
 * GENERATED FILE - DO NOT EDIT!
 *
 * This file is generated automatically when running bin/generate-function-metadata.php
 * and the result is merged from bin/functionMetadata_original.php and by looking at jetbrains/phpstorm-stubs methods
 * and functions with the #[Pure] attribute.
 *
 * If you want to add new entries here follow these steps:
 * 1) verify on https://phpstan.org/try whether the entry you are going to add does not already work as expected.
 * 2) Contribute functions with side effects, and corrections of the attributes, to bin/functionMetadata_original.php.
 * 3) Contribute the #[Pure] functions without side effects to https://github.com/JetBrains/phpstorm-stubs
 * 4) Once the PR from 3) is merged, please update the package here and run ./bin/generate-function-metadata.php.
 *
 * The array is keyed by lowercase function name or "Class::method". The format of
 * the entries is described in bin/functionMetadata_original.php.
 */

/** @var array<string, array{effects: list<string>, hasSideEffects?: false, pureUnlessCallableIsImpureParameters?: array<string, bool>, pureUnlessParameterPassedParameters?: array<string, bool>}> */
return [
%s
];
php;
	$content = '';
	$escape = static fn (mixed $value): string => var_export($value, true);
	$effectConstants = array_flip((new ReflectionClass(SideEffects::class))->getConstants());
	$encodeEffect = static function (string $effect) use ($effectConstants, $escape): string {
		if (isset($effectConstants[$effect])) {
			return 'SideEffects::' . $effectConstants[$effect];
		}
		if (preg_match('~^' . preg_quote(SideEffects::WRITES_ARGS, '~') . '(\(.+\))$~', $effect, $matches) === 1) {
			return 'SideEffects::' . $effectConstants[SideEffects::WRITES_ARGS] . ' . ' . $escape($matches[1]);
		}
		if ($effect !== '*') {
			throw new ShouldNotHappenException(sprintf('Effect %s has no SideEffects constant.', $effect));
		}

		return $escape($effect);
	};
	$encodeParameterMap = static fn (array $parameters) => sprintf(
		'[%s]',
		implode(
			', ',
			array_map(
				static fn ($key, $param) => sprintf('%s => %s', $escape($key), $escape($param)),
				array_keys($parameters),
				$parameters,
			),
		),
	);

	foreach ($metadata as $name => $meta) {
		$entries = [sprintf('%s => [%s]', $escape('effects'), implode(', ', array_map($encodeEffect, $meta['effects'])))];
		if (isset($meta['hasSideEffects'])) {
			$entries[] = sprintf('%s => %s', $escape('hasSideEffects'), $escape($meta['hasSideEffects']));
		}
		if (isset($meta['pureUnlessCallableIsImpureParameters'])) {
			$entries[] = sprintf('%s => %s', $escape('pureUnlessCallableIsImpureParameters'), $encodeParameterMap($meta['pureUnlessCallableIsImpureParameters']));
		}
		if (isset($meta['pureUnlessParameterPassedParameters'])) {
			$entries[] = sprintf('%s => %s', $escape('pureUnlessParameterPassedParameters'), $encodeParameterMap($meta['pureUnlessParameterPassedParameters']));
		}
		$content .= sprintf(
			"\t%s => [%s],\n",
			var_export($name, true),
			implode(', ', $entries),
		);
	}

	FileWriter::write(__DIR__ . '/../resources/functionMetadata.php', sprintf($template, $content) . "\n");
})();
