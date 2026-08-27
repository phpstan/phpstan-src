<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use function array_keys;
use function array_splice;
use function count;
use function explode;
use function implode;
use function preg_match;
use function sprintf;
use function str_replace;
use function strcasecmp;
use function strrpos;
use function strtolower;
use function substr;
use function usort;

/**
 * Inserts attribute lines and `use` imports into a PHP file's source. php-parser only
 * locates the positions - the edit itself is plain line insertion, so every untouched
 * line stays byte-identical.
 */
final class PhpAttributeInserter
{

	/**
	 * @param list<ServiceConversion> $conversions conversions whose classes are declared in this content
	 * @throws Neon2AttributesException
	 */
	public function insert(string $content, array $conversions): string
	{
		$parser = (new ParserFactory())->createForNewestSupportedVersion();
		$stmts = $parser->parse($content);
		if ($stmts === null) {
			throw new Neon2AttributesException('Cannot parse the PHP file.');
		}

		$traverser = new NodeTraverser(new NameResolver(options: ['replaceNodes' => false]));
		$stmts = $traverser->traverse($stmts);
		$nodeFinder = new NodeFinder();

		$lines = explode("\n", $content);

		$existingImports = [];
		$existingAliases = [];
		$lastUseLine = null;
		$firstUseLine = null;
		foreach ($nodeFinder->findInstanceOf($stmts, Use_::class) as $use) {
			if ($use->type !== Use_::TYPE_NORMAL) {
				continue;
			}
			foreach ($use->uses as $useUse) {
				$existingImports[strtolower($useUse->name->toString())] = true;
				$existingAliases[strtolower($useUse->getAlias()->toString())] = $useUse->name->toString();
			}
			$firstUseLine ??= $use->getStartLine();
			$lastUseLine = $use->getEndLine();
		}

		/** @var list<array{int, list<string>}> $insertions line number (1-based, insert before) => lines */
		$insertions = [];
		$importsToAdd = [];

		foreach ($conversions as $conversion) {
			$classNode = null;
			foreach ($nodeFinder->findInstanceOf($stmts, ClassLike::class) as $candidate) {
				if ($candidate->namespacedName === null || strcasecmp($candidate->namespacedName->toString(), $conversion->className) !== 0) {
					continue;
				}

				$classNode = $candidate;
				break;
			}

			if ($classNode === null) {
				throw new Neon2AttributesException(sprintf('Class %s is not declared in %s.', $conversion->className, $conversion->phpFile));
			}

			$attributeCode = $conversion->attributeCode;
			$parameterAttributes = $conversion->parameterAttributes;
			foreach ($conversion->useImports as $import) {
				$shortName = self::getShortName($import);
				$lowerImport = strtolower($import);
				$lowerShortName = strtolower($shortName);
				if (isset($existingImports[$lowerImport])) {
					continue;
				}
				if (isset($existingAliases[$lowerShortName]) && strcasecmp($existingAliases[$lowerShortName], $import) !== 0) {
					// another class already claims the short name - fall back to the fully qualified form
					$attributeCode = str_replace('#[' . $shortName, '#[\\' . $import, $attributeCode);
					foreach ($parameterAttributes as $parameterName => $parameterAttributeCode) {
						$parameterAttributes[$parameterName] = str_replace('#[' . $shortName, '#[\\' . $import, $parameterAttributeCode);
					}
					continue;
				}

				$importsToAdd[$import] = true;
				$existingAliases[$lowerShortName] = $import;
				$existingImports[$lowerImport] = true;
			}

			$classLine = $classNode->getStartLine();
			$indent = self::getIndent($lines[$classLine - 1] ?? '');
			$insertions[] = [$classLine, [$indent . $attributeCode]];

			if (count($parameterAttributes) === 0) {
				continue;
			}

			$constructor = null;
			foreach ($classNode->getMethods() as $method) {
				if (strcasecmp($method->name->toString(), '__construct') !== 0) {
					continue;
				}

				$constructor = $method;
				break;
			}
			if ($constructor === null) {
				throw new Neon2AttributesException(sprintf('Class %s has no constructor to carry #[AutowiredParameter].', $conversion->className));
			}

			foreach ($parameterAttributes as $parameterName => $parameterAttributeCode) {
				$parameterNode = null;
				foreach ($constructor->getParams() as $param) {
					if (!$param->var instanceof Variable || $param->var->name !== $parameterName) {
						continue;
					}

					$parameterNode = $param;
					break;
				}
				if ($parameterNode === null) {
					throw new Neon2AttributesException(sprintf('Constructor of %s has no parameter $%s.', $conversion->className, $parameterName));
				}

				$parameterLine = $parameterNode->getStartLine();
				if (!self::parameterStartsItsLine($constructor, $parameterLine, $parameterNode)) {
					throw new Neon2AttributesException(sprintf('Parameter $%s of %s does not start its own line, cannot insert #[AutowiredParameter] deterministically.', $parameterName, $conversion->className));
				}

				$parameterIndent = self::getIndent($lines[$parameterLine - 1] ?? '');
				$insertions[] = [$parameterLine, [$parameterIndent . $parameterAttributeCode]];
			}
		}

		if (count($importsToAdd) > 0) {
			$insertions[] = $this->buildImportInsertion($lines, $stmts, $nodeFinder, $importsToAdd, $firstUseLine, $lastUseLine);
		}

		usort($insertions, static fn (array $a, array $b): int => $b[0] <=> $a[0]);
		foreach ($insertions as [$line, $newLines]) {
			array_splice($lines, $line - 1, 0, $newLines);
		}

		return implode("\n", $lines);
	}

	/**
	 * @param list<string> $lines
	 * @param Node[] $stmts
	 * @param array<string, true> $importsToAdd
	 * @return array{int, list<string>}
	 * @throws Neon2AttributesException
	 */
	private function buildImportInsertion(array $lines, array $stmts, NodeFinder $nodeFinder, array $importsToAdd, ?int $firstUseLine, ?int $lastUseLine): array
	{
		$newImports = array_keys($importsToAdd);
		usort($newImports, static fn (string $a, string $b): int => strcasecmp($a, $b));
		$newLines = [];
		foreach ($newImports as $import) {
			$newLines[] = sprintf('use %s;', $import);
		}

		if ($firstUseLine !== null && $lastUseLine !== null) {
			// insert after the last existing import; exact alphabetical interleaving would
			// require reordering foreign lines, appending keeps the edit minimal
			return [$lastUseLine + 1, $newLines];
		}

		$namespaceNodes = $nodeFinder->findInstanceOf($stmts, Namespace_::class);
		if (count($namespaceNodes) > 0 && $namespaceNodes[0]->name !== null) {
			$namespaceLine = $namespaceNodes[0]->name->getEndLine();
			$insertAt = $namespaceLine + 1;
			while (isset($lines[$insertAt - 1]) && $lines[$insertAt - 1] === '') {
				$insertAt++;
			}

			$newLines[] = '';
			return [$insertAt, $newLines];
		}

		throw new Neon2AttributesException('Cannot find a place for the use imports - the file has no namespace declaration.');
	}

	private static function parameterStartsItsLine(ClassMethod $constructor, int $parameterLine, Param $parameterNode): bool
	{
		if ($constructor->getStartLine() === $parameterLine) {
			return false;
		}

		foreach ($constructor->getParams() as $param) {
			if ($param === $parameterNode) {
				continue;
			}
			if ($param->getStartLine() === $parameterLine || $param->getEndLine() === $parameterLine) {
				return false;
			}
		}

		return true;
	}

	private static function getIndent(string $line): string
	{
		$matches = [];
		if (preg_match('/^(\s*)/', $line, $matches) === 1) {
			return $matches[1];
		}

		return '';
	}

	private static function getShortName(string $className): string
	{
		$pos = strrpos($className, '\\');
		if ($pos === false) {
			return $className;
		}

		return substr($className, $pos + 1);
	}

}
