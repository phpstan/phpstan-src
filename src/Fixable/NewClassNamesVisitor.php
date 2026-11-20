<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PhpParser\Comment\Doc;
use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;
use PhpParser\NodeVisitorAbstract;
use PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use PHPStan\PhpDocParser\Ast\Node as PhpDocNodeInterface;
use PHPStan\PhpDocParser\Ast\NodeTraverser as PhpDocNodeTraverser;
use PHPStan\PhpDocParser\Ast\NodeVisitor\CloningVisitor as PhpDocCloningVisitor;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\RelativeNameNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\PhpDocParser\Printer\Printer;
use function array_pop;
use function array_search;
use function array_splice;
use function count;
use function explode;
use function ltrim;
use function str_contains;
use function str_starts_with;
use function strcasecmp;
use function strtolower;
use function substr;

/**
 * Writes the class names that were added to a file, e.g. by a fix,
 * relative to the namespace and the `use` statements, and adds the `use`
 * statements they need, the same way in PHP code and in PHPDocs.
 *
 * Runs after php-parser's NameResolver in the same traversal, on statements
 * cloned with CloningVisitor:
 *
 * - in PHP code, an added name is a FullyQualified without the origNode attribute,
 * - in a PHPDoc that changed, an added name is a fully qualified name (\Foo\Bar)
 *   in a tag that was not there before.
 *
 * Names that were already in the file are kept as they are written.
 *
 * An added class name is written as:
 *
 * 1. `self` for the current class, if $preferSelf is enabled,
 * 2. relative to an existing `use`, or to the current namespace,
 * 3. its short name with a new `use`, if $importNames is enabled
 *    and the short name does not mean something else in the file already,
 * 4. otherwise fully qualified.
 *
 * @internal
 */
final class NewClassNamesVisitor extends NodeVisitorAbstract
{

	private const SKIP_ATTRIBUTE = 'phpstanNewClassNamesSkip';

	private const SELF_NOT_ALLOWED_ATTRIBUTE = 'phpstanNewClassNamesSelfNotAllowed';

	/** @var list<non-empty-string|null> */
	private array $classNames = [];

	/** @var array<lowercase-string, true> lowercase first parts of names in the current namespace that already mean something */
	private array $takenNames = [];

	/** @var list<non-empty-string> */
	private array $addedUses = [];

	private Lexer $phpDocLexer;

	private PhpDocParser $phpDocParser;

	public function __construct(
		private NameContext $nameContext,
		private bool $importNames,
		private bool $preferSelf,
	)
	{
		// the format-preserving printer needs token indexes
		$config = new ParserConfig(['lines' => true, 'indexes' => true]);
		$constExprParser = new ConstExprParser($config);
		$this->phpDocLexer = new Lexer($config);
		$this->phpDocParser = new PhpDocParser($config, new TypeParser($config, $constExprParser), $constExprParser);
	}

	/**
	 * Whether there is something to write in $newNodes, the nodes returned by fixes.
	 * The rest of the file is cloned unchanged, so the visitor would not change it.
	 * Traversing the whole file is skipped when this returns false,
	 * which matters in files with many fixable errors.
	 *
	 * @param Node[] $newNodes
	 */
	public static function hasNewNames(array $newNodes): bool
	{
		return (new NodeFinder())->findFirst($newNodes, static function (Node $node): bool {
			if ($node instanceof FullyQualified && $node->getAttribute('origNode') === null) {
				return true;
			}

			$doc = $node->getDocComment();
			if ($doc === null) {
				return false;
			}

			$originalNode = $node->getAttribute('origNode');
			$originalDoc = $originalNode instanceof Node ? $originalNode->getDocComment() : null;

			return $originalDoc === null || $originalDoc->getText() !== $doc->getText();
		}) !== null;
	}

	#[Override]
	public function beforeTraverse(array $nodes): ?array
	{
		$this->classNames = [];
		$this->addedUses = [];
		$this->takenNames = $this->findTakenNames($nodes);

		return null;
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Stmt\Namespace_) {
			$this->addedUses = [];
			$this->takenNames = $this->findTakenNames($node->stmts);
		}

		if ($node instanceof Stmt\ClassLike) {
			$this->classNames[] = $node instanceof Stmt\Trait_ || $node->namespacedName === null ? null : $node->namespacedName->toString();
		}

		$this->markNames($node);
		$this->writeNewPhpDocNames($node);

		if (
			!$node instanceof FullyQualified
			|| $node->getAttribute('origNode') !== null
			|| $node->getAttribute(self::SKIP_ATTRIBUTE) === true
		) {
			return null;
		}

		return $this->resolve($node->toString(), $node->getAttribute(self::SELF_NOT_ALLOWED_ATTRIBUTE) !== true);
	}

	#[Override]
	public function leaveNode(Node $node): ?Node
	{
		if ($node instanceof Stmt\ClassLike) {
			array_pop($this->classNames);
		}

		if ($node instanceof Stmt\Namespace_) {
			$node->stmts = $this->addUses($node->stmts);
			$this->addedUses = [];
		}

		return null;
	}

	#[Override]
	public function afterTraverse(array $nodes): ?array
	{
		if ($this->addedUses === []) {
			return null;
		}

		/** @var list<Stmt> $nodes */
		return $this->addUses($nodes);
	}

	/**
	 * @param non-empty-string $className
	 * @return Name|null null to keep it fully qualified
	 */
	private function resolve(string $className, bool $selfAllowed): ?Name
	{
		$currentClassName = $this->classNames === [] ? null : $this->classNames[count($this->classNames) - 1];
		if ($this->preferSelf && $selfAllowed && $currentClassName !== null && strcasecmp($currentClassName, $className) === 0) {
			return new Name('self');
		}

		$shortName = $this->nameContext->getShortName($className, Stmt\Use_::TYPE_NORMAL);
		if (
			!$shortName->isFullyQualified()
			// in the global namespace, Foo\Bar is not shorter than \Foo\Bar
			&& ($shortName->isUnqualified() || strcasecmp($shortName->toString(), $className) !== 0)
		) {
			return $shortName;
		}

		if (!$this->importNames) {
			return null;
		}

		$name = new Name($className);
		$lastName = $name->getLast();
		if (!$name->isQualified() || isset($this->takenNames[strtolower($lastName)])) {
			return null;
		}

		$this->takenNames[strtolower($lastName)] = true;
		$this->nameContext->addAlias($name, $lastName, Stmt\Use_::TYPE_NORMAL);
		$this->addedUses[] = $className;

		return new Name([$lastName]);
	}

	/**
	 * Names that the file already uses without a leading backslash, and classes declared in it.
	 * Importing a class with one of these short names would change what the existing code means.
	 *
	 * @param Node[] $nodes
	 * @return array<lowercase-string, true>
	 */
	private function findTakenNames(array $nodes): array
	{
		$takenNames = [];
		$nodeFinder = new NodeFinder();
		// names that are not class names used in the code, or that only declare names
		$skippedNames = [];
		foreach ($nodeFinder->find($nodes, static fn (Node $node): bool => $node instanceof Node\Expr\FuncCall || $node instanceof Node\Expr\ConstFetch || $node instanceof Node\UseItem || $node instanceof Stmt\GroupUse || $node instanceof Stmt\Namespace_) as $node) {
			if ($node instanceof Stmt\GroupUse) {
				$skippedNames[] = $node->prefix;
			} elseif ($node instanceof Node\UseItem || $node instanceof Stmt\Namespace_ || $node instanceof Node\Expr\FuncCall || $node instanceof Node\Expr\ConstFetch) {
				$skippedNames[] = $node->name;
			}
		}

		$phpDocNameCollector = new class extends AbstractNodeVisitor {

			/** @var array<lowercase-string, true> */
			public array $names = [];

			#[Override]
			public function enterNode(PhpDocNodeInterface $node): ?PhpDocNodeInterface
			{
				if ($node instanceof IdentifierTypeNode && !str_starts_with($node->name, '\\')) {
					$this->names[strtolower(explode('\\', $node->name)[0])] = true;
				} elseif ($node instanceof PhpDocTagNode && str_contains($node->name, '\\')) {
					// Doctrine-style annotations like @ORM\Column
					$this->names[strtolower(explode('\\', ltrim($node->name, '@'))[0])] = true;
				}

				return null;
			}

		};
		$phpDocTraverser = new PhpDocNodeTraverser([$phpDocNameCollector]);

		foreach ($nodeFinder->find($nodes, static fn (Node $node): bool => true) as $node) {
			if ($node instanceof Stmt\Use_ || $node instanceof Stmt\GroupUse) {
				foreach ($node->uses as $useItem) {
					if (($useItem->type === Stmt\Use_::TYPE_UNKNOWN ? $node->type : $useItem->type) !== Stmt\Use_::TYPE_NORMAL) {
						continue;
					}

					$takenNames[strtolower($useItem->getAlias()->toString())] = true;
				}
				continue;
			}

			if ($node instanceof Stmt\ClassLike && $node->name !== null) {
				$takenNames[strtolower($node->name->toString())] = true;
			}

			if ($node instanceof Name && !$node->isFullyQualified() && !$node->isSpecialClassName()) {
				$isSkipped = false;
				foreach ($skippedNames as $skippedName) {
					if ($skippedName === $node) {
						$isSkipped = true;
						break;
					}
				}
				if (!$isSkipped) {
					$takenNames[strtolower($node->getFirst())] = true;
				}
			}

			$doc = $node->getDocComment();
			if ($doc === null) {
				continue;
			}

			$phpDocTraverser->traverse([$this->parsePhpDoc($doc)]);
		}

		return $takenNames + $phpDocNameCollector->names;
	}

	/**
	 * Writes the fully qualified class names in the PHPDoc tags that the fix added,
	 * like the added class names in PHP code.
	 */
	private function writeNewPhpDocNames(Node $node): void
	{
		$doc = $node->getDocComment();
		if ($doc === null) {
			return;
		}

		$originalNode = $node->getAttribute('origNode');
		$originalDoc = $originalNode instanceof Node ? $originalNode->getDocComment() : null;
		if ($originalDoc !== null && $originalDoc->getText() === $doc->getText()) {
			return;
		}

		// tags that were already there are kept as written
		$originalTags = [];
		if ($originalDoc !== null) {
			foreach ($this->parsePhpDoc($originalDoc)->getTags() as $tag) {
				$originalTags[] = (string) $tag;
			}
		}

		$tokens = new TokenIterator($this->phpDocLexer->tokenize($doc->getText()));
		$phpDocNode = $this->phpDocParser->parse($tokens);

		/** @var PhpDocNode $newPhpDocNode */
		[$newPhpDocNode] = (new PhpDocNodeTraverser([new PhpDocCloningVisitor()]))->traverse([$phpDocNode]);

		$namesTraverser = new PhpDocNodeTraverser([new class (fn (string $className, bool $selfAllowed): ?Name => $this->resolve($className, $selfAllowed)) extends AbstractNodeVisitor {

			/** @var callable(non-empty-string, bool): ?Name */
			private $resolve;

			/** @param callable(non-empty-string, bool): ?Name $resolve */
			public function __construct(callable $resolve)
			{
				$this->resolve = $resolve;
			}

			#[Override]
			public function enterNode(PhpDocNodeInterface $node): ?PhpDocNodeInterface
			{
				// keywords and template types are never written with a leading backslash
				if (!$node instanceof IdentifierTypeNode || !str_starts_with($node->name, '\\')) {
					return null;
				}

				$className = substr($node->name, 1);
				if ($className === '') {
					return null;
				}

				$name = ($this->resolve)($className, true);
				if ($name === null) {
					return null;
				}

				return $name->isSpecialClassName() ? new IdentifierTypeNode($name->toString()) : new RelativeNameNode($name->toString());
			}

		}]);

		$changed = false;
		foreach ($newPhpDocNode->children as $i => $child) {
			if (!$child instanceof PhpDocTagNode) {
				continue;
			}

			$key = array_search((string) $child, $originalTags, true);
			if ($key !== false) {
				unset($originalTags[$key]);
				continue;
			}

			[$newChild] = $namesTraverser->traverse([$child]);
			if (!$newChild instanceof PhpDocTagNode) {
				continue;
			}

			$newPhpDocNode->children[$i] = $newChild;
			$changed = true;
		}

		if (!$changed) {
			return;
		}

		$node->setDocComment(new Doc((new Printer())->printFormatPreserving($newPhpDocNode, $phpDocNode, $tokens)));
	}

	private function parsePhpDoc(Doc $doc): PhpDocNode
	{
		return $this->phpDocParser->parse(new TokenIterator($this->phpDocLexer->tokenize($doc->getText())));
	}

	/**
	 * Function and constant names are not class names,
	 * a use statement imports a name as it is written,
	 * and `self` is not valid PHP everywhere a class name is.
	 */
	private function markNames(Node $node): void
	{
		if ($node instanceof Stmt\Use_) {
			foreach ($node->uses as $useItem) {
				if (!$useItem->name instanceof FullyQualified || $useItem->name->getAttribute('origNode') !== null) {
					continue;
				}

				$useItem->name = new Name($useItem->name->toString());
			}
			return;
		}

		if ($node instanceof Node\Expr\FuncCall || $node instanceof Node\Expr\ConstFetch) {
			$node->name->setAttribute(self::SKIP_ATTRIBUTE, true);
			return;
		}

		$names = [];
		if ($node instanceof Node\Attribute) {
			$names = [$node->name];
		} elseif ($node instanceof Stmt\Class_) {
			$names = [$node->extends, ...$node->implements];
		} elseif ($node instanceof Stmt\Interface_) {
			$names = $node->extends;
		} elseif ($node instanceof Stmt\Enum_) {
			$names = $node->implements;
		} elseif ($node instanceof Stmt\TraitUse) {
			$names = $node->traits;
		}

		foreach ($names as $name) {
			if ($name === null) {
				continue;
			}

			$name->setAttribute(self::SELF_NOT_ALLOWED_ATTRIBUTE, true);
		}
	}

	/**
	 * Inserts the added `use` statements in alphabetical order among the existing ones.
	 *
	 * @param list<Stmt> $stmts statements of a namespace, or of a file without one
	 * @return list<Stmt>
	 */
	private function addUses(array $stmts): array
	{
		foreach ($this->addedUses as $className) {
			$stmts = $this->addUse($stmts, $className);
		}

		return $stmts;
	}

	/**
	 * @param list<Stmt> $stmts
	 * @param non-empty-string $className
	 * @return list<Stmt>
	 */
	private function addUse(array $stmts, string $className): array
	{
		$use = new Stmt\Use_([new Node\UseItem(new Name($className))]);

		$position = null;
		$lastClassUse = null;
		$firstOtherUse = null;
		$lastDeclare = null;
		foreach ($stmts as $i => $stmt) {
			if ($stmt instanceof Stmt\Declare_) {
				$lastDeclare = $i;
				continue;
			}
			if (!$stmt instanceof Stmt\Use_) {
				continue;
			}
			if ($stmt->type !== Stmt\Use_::TYPE_NORMAL) {
				$firstOtherUse ??= $i;
				continue;
			}

			$lastClassUse = $i;
			if ($position !== null || count($stmt->uses) !== 1 || strcasecmp($stmt->uses[0]->name->toString(), $className) <= 0) {
				continue;
			}

			$position = $i;
		}

		if ($position === null) {
			if ($lastClassUse !== null) {
				$position = $lastClassUse + 1;
			} elseif ($firstOtherUse !== null) {
				$position = $firstOtherUse;
			} else {
				// the first use statement, separated from the code below it by an empty line
				array_splice($stmts, $lastDeclare !== null ? $lastDeclare + 1 : 0, 0, [$use, new Stmt\Nop()]);
				return $stmts;
			}
		}

		array_splice($stmts, $position, 0, [$use]);

		return $stmts;
	}

}
