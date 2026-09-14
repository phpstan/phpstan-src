<?php declare(strict_types = 1);

namespace PHPStan\Build;

use FilesystemIterator;
use JsonException;
use Nette\Utils\RegexpException;
use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeUnsealedTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeParameterNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Turbo\ShadowedByTurboExtension;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;
use Throwable;
use function class_exists;
use function count;
use function file_get_contents;
use function interface_exists;
use function json_decode;
use function json_encode;
use function ksort;
use function realpath;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtr;
use function substr;
use function var_export;
use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * Collects the ShadowedByTurboExtension and ReferencedByTurboExtension
 * attributes with runtime reflection and renders the two generated files:
 * vendor/turbo-shadowed-classes.json and vendor/turbo-class-map.php. Shared
 * by build/generate-turbo-manifest.php (which writes the artifacts on every
 * autoloader dump) and turbo-ext/bin/side-by-side.php (which re-derives and
 * byte-compares them, so a stale dump fails the check). Not autoloaded and
 * not shipped — require this file directly; the Composer autoloader must be
 * registered.
 */
final class TurboAttributeCollector
{

	// Shadowed classes living in vendor/ cannot carry the attribute, so
	// their pairs are hardcoded. Class name => .cpp
	private const VENDORED_PAIRS = [
		NodeTraverser::class => 'turbo-ext/src/NodeTraverser.cpp',
	];

	// Classes the native code references that live in vendor/ cannot carry
	// the ReferencedByTurboExtension attribute either; their class-map
	// entries are hardcoded. Key (pt_class_refs in turbo-ext/src/support.cpp)
	// => class
	private const VENDORED_CLASS_MAP = [
		'variable' => Variable::class,
		'funcCall' => FuncCall::class,
		'node' => Node::class,
		'name' => Name::class,
		'expr' => Expr::class,
		'propertyFetch' => PropertyFetch::class,
		'nullsafePropertyFetch' => NullsafePropertyFetch::class,
		'identifier' => Identifier::class,
		'arrayDimFetch' => ArrayDimFetch::class,
		'methodCall' => MethodCall::class,
		'functionLike' => FunctionLike::class,
		'callLike' => CallLike::class,
		'staticCall' => StaticCall::class,
		'newExpr' => New_::class,
		'classStmt' => Class_::class,
		'variadicPlaceholder' => VariadicPlaceholder::class,
		'scalar' => Scalar::class,
		'arrayExpr' => Array_::class,
		'unaryMinus' => UnaryMinus::class,
		'yield' => Yield_::class,
		'yieldFrom' => YieldFrom::class,
		'stmt' => Stmt::class,
		'nodeVisitorAbstract' => NodeVisitorAbstract::class,
		'closureExpr' => Closure::class,
		'arrowFunction' => ArrowFunction::class,
		'identifierTypeNode' => IdentifierTypeNode::class,
		'genericTypeNode' => GenericTypeNode::class,
		'constTypeNode' => ConstTypeNode::class,
		'constExprIntegerNode' => ConstExprIntegerNode::class,
		'constExprStringNode' => ConstExprStringNode::class,
		'netteStrings' => Strings::class,
		'netteRegexpException' => RegexpException::class,
		'constExprFloatNode' => ConstExprFloatNode::class,
		'thisTypeNode' => ThisTypeNode::class,
		'objectShapeNode' => ObjectShapeNode::class,
		'objectShapeItemNode' => ObjectShapeItemNode::class,
		'constFetchNode' => ConstFetchNode::class,
		'phpDocPrinter' => Printer::class,
		'callableTypeNode' => CallableTypeNode::class,
		'callableTypeParameterNode' => CallableTypeParameterNode::class,
		'templateTagValueNode' => TemplateTagValueNode::class,
		'arrayShapeNode' => ArrayShapeNode::class,
		'arrayShapeItemNode' => ArrayShapeItemNode::class,
		'arrayShapeUnsealedTypeNode' => ArrayShapeUnsealedTypeNode::class,
		'unionTypeNode' => UnionTypeNode::class,
		'intersectionTypeNode' => IntersectionTypeNode::class,
	];

	private string $realRoot;

	/** @var array<string, true> realpaths of composer.json autoload.files */
	private array $functionFiles = [];

	/** @throws JsonException */
	public function __construct(string $root)
	{
		$realRoot = realpath($root);
		if ($realRoot === false) {
			throw new RuntimeException(sprintf('realpath(%s) failed', $root));
		}
		$this->realRoot = $realRoot;

		// files-autoloaded entries define functions, not classes, and are
		// already loaded — a class_exists() on their PSR-4-derived name would
		// include them a second time and fatal on the redeclaration
		$composerJsonContents = file_get_contents($realRoot . '/composer.json');
		if ($composerJsonContents === false) {
			throw new RuntimeException('Could not read composer.json');
		}
		$composerJson = json_decode($composerJsonContents, true, 8, JSON_THROW_ON_ERROR);
		foreach ($composerJson['autoload']['files'] ?? [] as $functionFile) {
			$real = realpath($realRoot . '/' . $functionFile);
			if ($real === false) {
				continue;
			}
			$this->functionFiles[$real] = true;
		}
	}

	/**
	 * Reflects every class under src/ (PSR-4 for the PHPStan namespace, held
	 * in CI by the name collision detector, so the class name follows from
	 * the path) and merges the hardcoded vendored entries.
	 *
	 * @return array{
	 *     manifest: array<string, array{php: string, cpp: string, turboClass: string, final: bool, parent: string|null, interfaces: list<class-string>, vendored?: bool}>,
	 *     classMap: array<string, string>,
	 *     referenced: array<string, string>,
	 * }
	 */
	public function collect(): array
	{
		$pairs = self::VENDORED_PAIRS;
		$classMap = self::VENDORED_CLASS_MAP;
		$referenced = [];

		$sourceDir = $this->realRoot . '/src';
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS));
		foreach ($iterator as $file) {
			if ($file->getExtension() !== 'php') {
				continue;
			}
			$path = str_replace(DIRECTORY_SEPARATOR, '/', $file->getPathname());
			if (isset($this->functionFiles[$file->getRealPath()])) {
				continue;
			}
			$className = 'PHPStan\\' . strtr(substr($path, strlen($sourceDir) + 1, -strlen('.php')), '/', '\\');
			try {
				$isClassLike = class_exists($className) || interface_exists($className);
			} catch (Throwable) {
				// a class that cannot load cannot carry the attributes — e.g.
				// src/Testing extends PHPUnit classes, which are missing when
				// the autoloader was dumped without dev dependencies
				continue;
			}
			if (!$isClassLike) {
				continue; // the file declares no class of its own
			}
			$reflection = new ReflectionClass($className);

			$attributes = $reflection->getAttributes(ShadowedByTurboExtension::class);
			if (count($attributes) > 0) {
				$attribute = $attributes[0]->newInstance();
				$pairs[$className] = $this->relativize($attribute->implementation);
			}

			foreach ($reflection->getAttributes(ReferencedByTurboExtension::class) as $referencedAttribute) {
				$key = $referencedAttribute->newInstance()->key;
				if (isset($classMap[$key])) {
					throw new RuntimeException(sprintf('Both %s and %s claim the class-map key %s', $classMap[$key], $className, $key));
				}
				$classMap[$key] = $className;
				$referenced[$key] = $className;
			}
		}

		ksort($pairs);
		ksort($classMap);
		ksort($referenced);

		$manifest = [];
		$byShortName = [];
		foreach ($pairs as $className => $cppFile) {
			$reflection = new ReflectionClass($className);
			$fileName = $reflection->getFileName();
			if ($fileName === false) {
				throw new RuntimeException(sprintf('%s has no source file', $className));
			}

			// The name the differential tests declare the native class under
			// next to its twin, derived the way the extension derives it -
			// so two shadowed classes sharing a short name would arrive at
			// one native name and silently shadow each other there.
			$shortName = $reflection->getShortName();
			if (isset($byShortName[$shortName])) {
				throw new RuntimeException(sprintf('%s and %s share the short name %s, so both would be declared as PHPStanTurbo\\%s next to their twins', $byShortName[$shortName], $className, $shortName, $shortName));
			}
			$byShortName[$shortName] = $className;
			$turboClass = 'PHPStanTurbo\\' . $shortName;

			$parent = $reflection->getParentClass();
			$phpFile = $this->relativize($fileName);
			$entry = [
				'php' => $phpFile,
				'cpp' => $cppFile,
				'turboClass' => $turboClass,
				// what the native class must declare too — held by
				// turbo-ext/tests/signature-parity.php
				'final' => $reflection->isFinal(),
				'parent' => $parent === false ? null : $parent->getName(),
				'interfaces' => $reflection->getInterfaceNames(),
			];
			if (str_starts_with($phpFile, 'vendor/')) {
				$entry['vendored'] = true;
			}
			$manifest[$className] = $entry;
		}

		return ['manifest' => $manifest, 'classMap' => $classMap, 'referenced' => $referenced];
	}

	/**
	 * @param array<string, array{php: string, cpp: string, turboClass: string, final: bool, parent: string|null, interfaces: list<class-string>, vendored?: bool}> $manifest
	 * @throws JsonException
	 */
	public function renderManifestJson(array $manifest): string
	{
		return json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
	}

	/** @param array<string, string> $classMap */
	public function renderClassMap(array $classMap): string
	{
		$entries = '';
		foreach ($classMap as $key => $className) {
			$entries .= sprintf("\t%s => %s,\n", var_export($key, true), var_export($className, true));
		}

		return sprintf(
			<<<'PHP'
<?php declare(strict_types = 1);

// turbo-class-map.php @generated by build/generate-turbo-manifest.php — do not
// edit. The class map PHPStan\Turbo\TurboExtensionEnabler passes to
// PHPStanTurbo\Runtime::configure(): one entry per key of the native
// class-reference table, from the ReferencedByTurboExtension attributes
// (plus the hardcoded vendored PhpParser entries).

return [
%s];

PHP,
			$entries,
		);
	}

	private function relativize(string $path): string
	{
		$real = realpath($path);
		if ($real === false) {
			throw new RuntimeException(sprintf('%s does not exist', $path));
		}

		return str_replace(DIRECTORY_SEPARATOR, '/', substr($real, strlen($this->realRoot) + 1));
	}

}
