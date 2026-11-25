<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use SplObjectStorage;
use function array_pop;
use function count;
use function in_array;
use function is_string;
use function strtolower;

/**
 * Attaches PreservedProperties to each method and static call on $this in a
 * class's methods: the properties of $this no code the call can run writes,
 * read from the class's own code only (see PreservedPropertiesClassScan).
 *
 * Interfaces and calls in traits and closures (which can be bound to another
 * object) get nothing, so every property is forgotten there as before. A trait
 * gets its scan, which the classes using it merge in. Files outside the analysed
 * paths are not parsed by the rich parser at all, so a trait there has no scan.
 */
#[AutowiredService]
final class PreservedPropertiesVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'preservedProperties';

	/** Attached to each method of a class: ClassLikeHandler walks the methods by it, callees before their callers. */
	public const RANK_ATTRIBUTE_NAME = 'calleesFirstRank';

	/** Attached to each trait: its PreservedPropertiesClassScan, merged into the scans of the classes using it. */
	public const TRAIT_SCAN_ATTRIBUTE_NAME = 'preservedPropertiesTraitScan';

	/** Attached to a method only the class's own code calls: the lowercase names of the methods it is only ever run from (see PreservedPropertiesClassScan::annotateOnlyCalledFrom()). */
	public const ONLY_CALLED_FROM_ATTRIBUTE_NAME = 'onlyCalledFrom';

	private ?PreservedPropertiesClassScan $scan = null;

	/** @var list<PreservedPropertiesClassScan|null> scans of the classes enclosing an anonymous class */
	private array $outerScans = [];

	/** @var SplObjectStorage<Expr\Variable, null> occurrences of $this that do not let it escape */
	private SplObjectStorage $containedThis;

	public function __construct()
	{
		$this->containedThis = new SplObjectStorage();
	}

	#[Override]
	public function beforeTraverse(array $nodes): ?array
	{
		$this->scan = null;
		$this->outerScans = [];
		$this->containedThis = new SplObjectStorage();
		return null;
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Stmt\ClassLike) {
			$this->outerScans[] = $this->scan;
			$this->scan = $this->createScan($node);
			return null;
		}

		$scan = $this->scan;
		if ($scan === null) {
			return null;
		}

		if ($node instanceof Node\Scalar\String_) {
			// may name a method in a callable
			$scan->calledElsewhere[strtolower($node->value)] = true;
			return null;
		}

		if ($scan->currentMethod === null) {
			if ($node instanceof Node\Stmt\ClassMethod && $node->stmts !== null) {
				$scan->currentMethod = $node->name->toLowerString();
				$scan->returnsByRef = $node->byRef;
				$scan->summaries[$scan->currentMethod] = self::emptySummary();
			}
			return null;
		}

		if ($node instanceof Node\Stmt\Function_) {
			$scan->functionDepth++;
			return null;
		}
		if ($scan->functionDepth > 0) {
			return null;
		}

		if ($node instanceof Expr\Closure || $node instanceof Expr\ArrowFunction) {
			$scan->closureDepth++;
			$scan->handOut(PreservedPropertiesClassScan::CALL_SELF, PreservedPropertiesClassScan::CLOSURES);
			if ($node->byRef) {
				// what a by-reference closure returns, its caller can write
				$this->markUnknown($scan);
			}
			return null;
		}

		$this->enterBodyNode($scan, $node);

		return null;
	}

	#[Override]
	public function leaveNode(Node $node): ?Node
	{
		if ($node instanceof Node\Stmt\ClassLike) {
			$scan = $this->scan;
			if ($node instanceof Node\Stmt\Trait_) {
				$node->setAttribute(self::TRAIT_SCAN_ATTRIBUTE_NAME, $scan);
			} elseif ($scan !== null) {
				if ($scan->traitUses !== [] && !$scan->unsupported) {
					$scan->deferCalls();
				} else {
					$scan->annotateCalls();
				}
				$scan->rankMethods($node);
				$scan->annotateOnlyCalledFrom($node);
			}
			$this->scan = array_pop($this->outerScans);
			return null;
		}

		$scan = $this->scan;
		if ($scan === null || $scan->currentMethod === null) {
			return null;
		}

		if ($node instanceof Node\Stmt\Function_) {
			$scan->functionDepth--;
		} elseif ($scan->functionDepth > 0) {
			return null;
		} elseif ($node instanceof Expr\Closure || $node instanceof Expr\ArrowFunction) {
			$scan->closureDepth--;
		} elseif ($node instanceof Node\Stmt\ClassMethod) {
			$scan->currentMethod = null;
		}

		return null;
	}

	#[Override]
	public function afterTraverse(array $nodes): ?array
	{
		$this->containedThis = new SplObjectStorage();
		return null;
	}

	private function createScan(Node\Stmt\ClassLike $node): ?PreservedPropertiesClassScan
	{
		if ($node instanceof Node\Stmt\Class_) {
			// AnonymousClassVisitor may already have replaced an anonymous class with a node
			// that has no namespacedName, depending on the order the visitors run in
			$scan = new PreservedPropertiesClassScan(
				$node->isFinal() || $node->isAnonymous(),
				$node->extends !== null,
				$node->isAnonymous() ? null : ($node->namespacedName ?? null)?->toLowerString(),
			);
			$scan->parentName = $node->extends?->toString();
		} elseif ($node instanceof Node\Stmt\Enum_) {
			$scan = new PreservedPropertiesClassScan(
				true,
				false,
				($node->namespacedName ?? null)?->toLowerString(),
			);
		} elseif ($node instanceof Node\Stmt\Trait_) {
			// its methods run in the classes that use it, which tell whether they are final or have a parent
			$scan = new PreservedPropertiesClassScan(false, false, null);
		} else {
			// an interface has no code
			return null;
		}

		foreach ($node->stmts as $stmt) {
			if ($stmt instanceof Node\Stmt\TraitUse) {
				$scan->traitUses[] = $stmt;
			} elseif ($stmt instanceof Node\Stmt\Property) {
				if ($stmt->isStatic()) {
					continue;
				}
				if ($stmt->hooks !== []) {
					$scan->unsupported = true;
				}
				foreach ($stmt->props as $prop) {
					$scan->addProperty($prop->name->toString(), $stmt->isPrivate() || $stmt->isPrivateSet(), $stmt->isProtected() || $stmt->isProtectedSet());
				}
			} elseif ($stmt instanceof Node\Stmt\ClassMethod) {
				$methodName = $stmt->name->toLowerString();
				if (in_array($methodName, ['__get', '__set', '__unset'], true)) {
					$scan->unsupported = true;
				}
				$scan->methods[$methodName] = [$stmt->isPrivate(), !$stmt->isPrivate() && !$stmt->isFinal()];
				$byPosition = [];
				$byName = [];
				$variadic = false;
				foreach ($stmt->params as $param) {
					$byPosition[] = $param->byRef;
					if ($param->var instanceof Expr\Variable && is_string($param->var->name)) {
						$byName[$param->var->name] = $param->byRef;
					}
					$variadic = $param->variadic;
				}
				$scan->methodParameters[$methodName] ??= [$byPosition, $byName, $variadic];
				if ($methodName !== '__construct') {
					continue;
				}
				foreach ($stmt->params as $param) {
					if (!$param->isPromoted() || !$param->var instanceof Expr\Variable || !is_string($param->var->name)) {
						continue;
					}
					if ($param->hooks !== []) {
						$scan->unsupported = true;
					}
					$scan->addProperty($param->var->name, $param->isPrivate() || $param->isPrivateSet(), $param->isProtected() || $param->isProtectedSet());
				}
			}
		}

		return $scan;
	}

	/**
	 * @return array{writes: array<string, true>, calls: list<array{PreservedPropertiesClassScan::CALL_*, string}>, functionArguments: list<array{list<non-empty-string>, int, string}>, escapes: bool, unknown: bool}
	 */
	private static function emptySummary(): array
	{
		return ['writes' => [], 'calls' => [], 'functionArguments' => [], 'escapes' => false, 'unknown' => false];
	}

	private function enterBodyNode(PreservedPropertiesClassScan $scan, Node $node): void
	{
		if (
			$node instanceof Expr\Array_
			&& count($node->items) === 2
			&& !$node->items[1]->value instanceof Node\Scalar\String_
			&& $this->isThisOrSelf($node->items[0]->value)
		) {
			// a callable of a method whose name is not written here
			$scan->hasDynamicCalls = true;
		}

		if ($node instanceof Expr\Variable && $node->name === 'this') {
			if (!$this->containedThis->offsetExists($node)) {
				// passed out, assigned or used as a callable: other code can call any method on it
				$this->markUnknown($scan);
				$scan->thisEscapes = true;
			}
			return;
		}

		if (
			$node instanceof Expr\PropertyFetch
			|| $node instanceof Expr\NullsafePropertyFetch
		) {
			$this->containThis($node->var);
			return;
		}

		if ($node instanceof Expr\Instanceof_) {
			$this->containThis($node->expr);
			return;
		}

		if (
			$node instanceof Expr\BinaryOp\Identical
			|| $node instanceof Expr\BinaryOp\NotIdentical
			|| $node instanceof Expr\BinaryOp\Equal
			|| $node instanceof Expr\BinaryOp\NotEqual
		) {
			$this->containThis($node->left);
			$this->containThis($node->right);
			return;
		}

		if ($node instanceof Expr\ClassConstFetch || $node instanceof Expr\StaticPropertyFetch) {
			$this->containThis($node->class);
			return;
		}

		if ($node instanceof Expr\MethodCall || $node instanceof Expr\NullsafeMethodCall) {
			if ($this->isThis($node->var) && $node->name instanceof Identifier) {
				$this->methodArgs($scan, $node->args, $node->name->toLowerString());
			} else {
				$this->writeArgs($scan, $node->args, true);
			}
			if (!$node->name instanceof Identifier) {
				$scan->hasDynamicCalls = true;
			}
			if (!$this->isThis($node->var)) {
				if ($node->name instanceof Identifier) {
					// another object of the class can call its private methods too
					$scan->calledElsewhere[$node->name->toLowerString()] = true;
				}
				// another object's method can do anything a call from outside the class can
				$this->escape($scan);
				return;
			}
			$this->containThis($node->var);
			if (!$node->name instanceof Identifier) {
				// can be any method, private ones too
				$this->markUnknown($scan);
				return;
			}
			if ($node->isFirstClassCallable()) {
				$scan->handOut(PreservedPropertiesClassScan::CALL_THIS, $node->name->toLowerString());
				return;
			}
			$this->addCall($scan, $node, PreservedPropertiesClassScan::CALL_THIS, $node->name->toLowerString());
			return;
		}

		if ($node instanceof Expr\StaticCall) {
			if (!$node->name instanceof Identifier) {
				$this->writeArgs($scan, $node->args);
			} elseif ($node->class instanceof Name && $node->class->toLowerString() === 'static') {
				// an override has to keep its parameters by reference or by value
				$this->methodArgs($scan, $node->args, $node->name->toLowerString());
			} else {
				$this->classMethodArgs($scan, $node->args, $node->class, $node->name->toLowerString());
			}
			$this->enterStaticCall($scan, $node);
			return;
		}

		if ($node instanceof Expr\FuncCall) {
			// a function, or a callable value that may be a closure with this $this
			$this->escape($scan);
			if ($node->name instanceof Name) {
				$this->passThisOut($scan, $node->args);
				$this->functionArgs($scan, $node->args, $node->name);
			} else {
				$this->writeArgs($scan, $node->args);
			}
			return;
		}

		if ($node instanceof Expr\New_) {
			$this->escape($scan);
			if (
				!$node->class instanceof Name
				|| !in_array($node->class->toLowerString(), ['self', 'static', 'parent', $scan->lowerName], true)
			) {
				$this->passThisOut($scan, $node->args);
			}
			// a subclass's constructor need not keep the parameters of the one it overrides, so 'new static' is not followed
			$this->classMethodArgs($scan, $node->args, $node->class, '__construct');
			return;
		}

		if ($node instanceof Expr\Assign && $node->var instanceof Expr\StaticPropertyFetch && $this->isThis($node->expr)) {
			// kept for later, like a singleton instance: code that reads it can call any method on it
			$this->containThis($node->expr);
			$scan->thisEscapes = true;
		}

		if ($node instanceof Expr\Assign || $node instanceof Expr\AssignOp) {
			$this->write($scan, $node->var, $node->var instanceof Expr\PropertyFetch);
			return;
		}

		if ($node instanceof Expr\AssignRef) {
			$this->write($scan, $node->var);
			$this->write($scan, $node->expr);
			return;
		}

		if (
			$node instanceof Expr\PreInc
			|| $node instanceof Expr\PreDec
			|| $node instanceof Expr\PostInc
			|| $node instanceof Expr\PostDec
		) {
			$this->write($scan, $node->var);
			return;
		}

		if ($node instanceof Node\Stmt\Unset_) {
			foreach ($node->vars as $var) {
				$this->write($scan, $var);
			}
			return;
		}

		if ($node instanceof Node\Stmt\Foreach_) {
			if ($node->keyVar !== null) {
				$this->write($scan, $node->keyVar);
			}
			$this->write($scan, $node->valueVar);
			if ($node->byRef) {
				$this->write($scan, $node->expr);
			}
			return;
		}

		if ($node instanceof Node\ArrayItem && $node->byRef) {
			$this->write($scan, $node->value);
			return;
		}

		if ($node instanceof Node\Stmt\Return_ && $node->expr !== null && $scan->returnsByRef && $scan->closureDepth === 0) {
			// a by-reference method lets the caller write what it returns
			$this->write($scan, $node->expr);
			return;
		}

		if (!($node instanceof Expr\Include_) && !($node instanceof Expr\Eval_)) {
			return;
		}

		// the included code runs with this $this
		$this->markUnknown($scan);
	}

	private function enterStaticCall(PreservedPropertiesClassScan $scan, Expr\StaticCall $node): void
	{
		if ($node->class instanceof Name) {
			$lowerClassName = $node->class->toLowerString();
			if ($lowerClassName === 'static') {
				$kind = PreservedPropertiesClassScan::CALL_THIS;
			} elseif ($lowerClassName === 'self' || $lowerClassName === $scan->lowerName) {
				$kind = PreservedPropertiesClassScan::CALL_SELF;
			} else {
				// parent:: runs the parent's code with this $this, another class is outside too
				$kind = PreservedPropertiesClassScan::CALL_OUTSIDE;
			}
		} elseif ($this->isThis($node->class)) {
			$this->containThis($node->class);
			$kind = PreservedPropertiesClassScan::CALL_THIS;
		} else {
			// '$class::m()' can name this class or an ancestor and run any of their methods with this $this
			if ($node->name instanceof Identifier) {
				$scan->calledElsewhere[$node->name->toLowerString()] = true;
			} else {
				$scan->hasDynamicCalls = true;
			}
			$this->markUnknown($scan);
			return;
		}

		if (!$node->name instanceof Identifier) {
			$scan->hasDynamicCalls = true;
			$this->markUnknown($scan);
			return;
		}

		if ($node->isFirstClassCallable()) {
			if ($kind !== PreservedPropertiesClassScan::CALL_OUTSIDE) {
				$scan->handOut($kind, $node->name->toLowerString());
			}
			return;
		}

		$this->addCall($scan, $node, $kind, $node->name->toLowerString());
	}

	/**
	 * @param PreservedPropertiesClassScan::CALL_* $kind
	 */
	private function addCall(PreservedPropertiesClassScan $scan, Expr $node, int $kind, string $methodName): void
	{
		if ($scan->closureDepth === 0) {
			$scan->calls[] = [$node, $kind, $methodName];
		}

		if ($kind === PreservedPropertiesClassScan::CALL_OUTSIDE) {
			$scan->calledElsewhere[$methodName] = true;
			$this->escape($scan);
			return;
		}

		$scan->callers[$methodName][$scan->closureDepth > 0 ? PreservedPropertiesClassScan::CLOSURES : (string) $scan->currentMethod] = true;

		$scan->summaries[(string) $scan->currentMethod]['calls'][] = [$kind, $methodName];
		if ($scan->closureDepth <= 0) {
			return;
		}

		$scan->closures['calls'][] = [$kind, $methodName];
	}

	private function escape(PreservedPropertiesClassScan $scan): void
	{
		$scan->summaries[(string) $scan->currentMethod]['escapes'] = true;
		if ($scan->closureDepth <= 0) {
			return;
		}

		$scan->closures['escapes'] = true;
	}

	private function markUnknown(PreservedPropertiesClassScan $scan): void
	{
		$scan->summaries[(string) $scan->currentMethod]['unknown'] = true;
		if ($scan->closureDepth <= 0) {
			return;
		}

		$scan->closures['unknown'] = true;
	}

	/**
	 * A property passed as an argument may be passed by reference.
	 *
	 * @param array<Node> $args
	 * @param bool $toOtherObject whether the arguments go to a method of another object, which takes a
	 *        property by reference so rarely that it does not keep the walk's type of what an override assigns
	 */
	private function writeArgs(PreservedPropertiesClassScan $scan, array $args, bool $toOtherObject = false): void
	{
		foreach ($args as $arg) {
			if (!$arg instanceof Arg) {
				continue;
			}

			$this->write($scan, $arg->value, false, $toOtherObject);
		}
	}

	/**
	 * A property passed to a method of the class is written only when the parameter is by
	 * reference; any other method may take it by reference.
	 *
	 * @param array<Node> $args
	 */
	private function methodArgs(PreservedPropertiesClassScan $scan, array $args, string $methodName): void
	{
		if (!isset($scan->methodParameters[$methodName])) {
			$this->writeArgs($scan, $args);
			return;
		}

		[$byPosition, $byName, $variadic] = $scan->methodParameters[$methodName];
		foreach ($args as $position => $arg) {
			if (!$arg instanceof Arg) {
				continue;
			}
			if ($arg->unpack) {
				$byRef = in_array(true, $byPosition, true);
			} elseif ($arg->name !== null) {
				$byRef = $byName[$arg->name->toString()] ?? $variadic && $byPosition[count($byPosition) - 1];
			} else {
				$byRef = $byPosition[$position] ?? $variadic && $byPosition[count($byPosition) - 1];
			}
			if (!$byRef) {
				continue;
			}

			$this->write($scan, $arg->value);
		}
	}

	/**
	 * A property passed positionally to a named function, or to a method of another class named
	 * in the call, is written only when the parameter is by reference, which
	 * PreservedPropertiesResolver looks up.
	 *
	 * @param array<Node> $args
	 * @param list<non-empty-string> $calleeNames candidate function names, or 'Class::method'
	 */
	private function calleeArgs(PreservedPropertiesClassScan $scan, array $args, array $calleeNames): void
	{
		foreach ($args as $position => $arg) {
			if (!$arg instanceof Arg) {
				continue;
			}
			$propertyName = $this->writtenPropertyName($scan, $arg->value);
			if ($propertyName === null) {
				continue;
			}
			if ($arg->unpack || $arg->name !== null) {
				$this->writeName($scan, $propertyName);
				continue;
			}
			$scan->summaries[(string) $scan->currentMethod]['functionArguments'][] = [$calleeNames, $position, $propertyName];
			if ($scan->closureDepth <= 0) {
				continue;
			}

			$scan->closures['functionArguments'][] = [$calleeNames, $position, $propertyName];
		}
	}

	/**
	 * @param array<Node> $args
	 */
	private function functionArgs(PreservedPropertiesClassScan $scan, array $args, Name $name): void
	{
		$functionNames = [];
		$namespacedName = $name->getAttribute('namespacedName');
		if ($namespacedName instanceof Name) {
			$functionNames[] = $namespacedName->toString();
		}
		$functionNames[] = $name->toString();

		$this->calleeArgs($scan, $args, $functionNames);
	}

	/**
	 * The arguments of a static call or 'new': 'self' is the class itself, a named class or
	 * 'parent' is looked up by reflection, and anything else may take them by reference.
	 *
	 * @param array<Node> $args
	 */
	private function classMethodArgs(PreservedPropertiesClassScan $scan, array $args, Node $class, string $methodName): void
	{
		if (!$class instanceof Name) {
			$this->writeArgs($scan, $args);
			return;
		}

		$lowerClassName = $class->toLowerString();
		if ($lowerClassName === 'self' || $lowerClassName === $scan->lowerName) {
			$this->methodArgs($scan, $args, $methodName);
			return;
		}
		if ($lowerClassName === 'parent') {
			$className = $scan->parentName;
		} elseif ($lowerClassName === 'static') {
			$className = null;
		} else {
			$className = $class->toString();
		}
		if ($className === null) {
			$this->writeArgs($scan, $args);
			return;
		}

		$this->calleeArgs($scan, $args, [$className . '::' . $methodName]);
	}

	/**
	 * The property a write to $expr writes, or null when it writes none; a
	 * dynamic name makes the summary unknown.
	 */
	private function writtenPropertyName(PreservedPropertiesClassScan $scan, Expr $expr): ?string
	{
		while ($expr instanceof Expr\ArrayDimFetch) {
			$expr = $expr->var;
		}

		if (!$expr instanceof Expr\PropertyFetch && !$expr instanceof Expr\NullsafePropertyFetch) {
			return null;
		}

		if (!$expr->name instanceof Identifier) {
			$this->markUnknown($scan);
			return null;
		}

		return $expr->name->toString();
	}

	/**
	 * @param bool $plain whether the walk sees the value written, a PropertyAssignNode of an assignment
	 */
	private function write(PreservedPropertiesClassScan $scan, Expr $expr, bool $plain = false, bool $toOtherObject = false): void
	{
		if ($expr instanceof Expr\List_ || $expr instanceof Expr\Array_) {
			foreach ($expr->items as $item) {
				if ($item === null) {
					continue;
				}
				$this->write($scan, $item->value, false, $toOtherObject);
			}
			return;
		}

		$propertyName = $this->writtenPropertyName($scan, $expr);
		if ($propertyName === null) {
			return;
		}

		$this->writeName($scan, $propertyName, $plain, $toOtherObject);
	}

	private function writeName(PreservedPropertiesClassScan $scan, string $propertyName, bool $plain = false, bool $toOtherObject = false): void
	{
		$scan->summaries[(string) $scan->currentMethod]['writes'][$propertyName] = true;
		if ($plain && $scan->closureDepth <= 0) {
			$scan->assignments[(string) $scan->currentMethod][$propertyName] = true;
		} elseif (!$toOtherObject) {
			$scan->nonPlainWrites[$propertyName] = true;
		}
		if ($scan->closureDepth <= 0) {
			return;
		}

		$scan->closures['writes'][$propertyName] = true;
	}

	/**
	 * $this passed by value to code of another class: that code can call any method of the class
	 * whenever it runs, but cannot write its private properties.
	 *
	 * @param array<Node> $args
	 */
	private function passThisOut(PreservedPropertiesClassScan $scan, array $args): void
	{
		foreach ($args as $arg) {
			if (!$arg instanceof Arg || $arg->unpack || $arg->byRef || !$this->isThis($arg->value)) {
				continue;
			}
			$this->containThis($arg->value);
			$scan->thisEscapes = true;
		}
	}

	private function isThis(Node $node): bool
	{
		return $node instanceof Expr\Variable && $node->name === 'this';
	}

	private function isThisOrSelf(Expr $expr): bool
	{
		if ($this->isThis($expr) || $expr instanceof Node\Scalar\MagicConst\Class_) {
			return true;
		}

		return $expr instanceof Expr\ClassConstFetch
			&& $expr->class instanceof Name
			&& in_array($expr->class->toLowerString(), ['self', 'static'], true)
			&& $expr->name instanceof Identifier
			&& $expr->name->toLowerString() === 'class';
	}

	private function containThis(Node $node): void
	{
		if (!$node instanceof Expr\Variable || !$this->isThis($node)) {
			return;
		}

		$this->containedThis->offsetSet($node, null);
	}

}
