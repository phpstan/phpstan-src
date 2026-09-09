<?php declare(strict_types = 1);

namespace PHPStan\Build;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use Throwable;
use function array_keys;
use function count;
use function dirname;
use function file_exists;
use function file_get_contents;
use function in_array;
use function is_string;
use function json_decode;
use function strtolower;
use function substr;

/**
 * Source-level inliner for the phar build (compiler's PrepareCommand): for
 * every method call whose receiver type resolves to a single class and whose
 * callee body is one `return <expr>;`, records a textual replacement of the
 * call with that expression ($this and parameters substituted), plus the
 * non-public properties the expression reads (they become public for the
 * rewrite to run — see InlineEditsApplier in the compiler).
 *
 * Callees are ones no subclass can override — final class, final or private
 * method — or, closed-world, non-final ones nothing in the scanned code base
 * overrides (OverridesScanner). Every call frame saved is engine work saved:
 * a getter call costs about 40ns of frame setup for a body that reads one
 * property; a self-analysis measured -4% user CPU.
 *
 * @implements Collector<MethodCall, array{file: string, start: int, end: int, replacement: string, callee: string, publicize: list<array{class: string, property: string, file: string|null}>}>
 */
final class InlineCallCollector implements Collector
{

	private const MAX_EXPR_NODES = 30;

	/** @var array<string, array<string, Stmt\ClassMethod|null>> */
	private array $methodNodes = [];

	/** @var array<string, true>|null */
	private ?array $overrides = null;

	public function __construct(private Parser $parser, private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return MethodCall::class;
	}

	public function processNode(Node $node, Scope $scope): ?array
	{
		if (!$node->name instanceof Node\Identifier || $node->isFirstClassCallable()) {
			return null;
		}
		$args = $node->getArgs();
		foreach ($args as $arg) {
			if ($arg->name !== null || $arg->unpack || $arg->byRef) {
				return null;
			}
		}
		$methodName = $node->name->toString();
		$classReflections = $scope->getType($node->var)->getObjectClassReflections();
		if (count($classReflections) !== 1) {
			return null;
		}
		$classReflection = $classReflections[0];
		if (!$classReflection->hasNativeMethod($methodName)) {
			return null;
		}
		$method = $classReflection->getNativeMethod($methodName);
		$declaringClass = $method->getDeclaringClass();
		if ($method->isStatic()) {
			return null;
		}
		// a turbo-shadowed class runs natively: its PHP twin's bodies (and
		// properties) are not what executes, so they must not be inlined
		if ($this->isShadowedByTurbo($declaringClass)) {
			return null;
		}
		$guardFree = $declaringClass->isFinal() || $method->isFinal()->yes() || $method->isPrivate();
		if (!$guardFree && $this->isOverridden($declaringClass, $methodName)) {
			return null;
		}

		$methodNode = $this->findMethodNode($declaringClass, $methodName);
		if ($methodNode === null || $methodNode->byRef || $methodNode->isStatic() || $methodNode->stmts === null) {
			return null;
		}
		$stmts = $methodNode->stmts;
		if (count($stmts) !== 1 || !$stmts[0] instanceof Stmt\Return_ || $stmts[0]->expr === null) {
			return null;
		}
		if (count($args) !== count($methodNode->params)) {
			return null;
		}
		$paramNames = [];
		foreach ($methodNode->params as $i => $param) {
			if ($param->byRef || $param->variadic || !$param->var instanceof Expr\Variable || !is_string($param->var->name)) {
				return null;
			}
			$paramNames[$param->var->name] = $i;
		}

		$expr = $stmts[0]->expr;
		$finder = new NodeFinder();
		$forbidden = $finder->findFirst($expr, static function (Node $n) use ($paramNames): bool {
			if ($n instanceof Expr\Closure || $n instanceof Expr\ArrowFunction || $n instanceof Expr\Yield_ || $n instanceof Expr\YieldFrom
				|| $n instanceof Expr\Assign || $n instanceof Expr\AssignOp || $n instanceof Expr\AssignRef
				|| $n instanceof Expr\PreInc || $n instanceof Expr\PostInc || $n instanceof Expr\PreDec || $n instanceof Expr\PostDec
				|| $n instanceof Node\Scalar\MagicConst || $n instanceof Expr\Include_ || $n instanceof Expr\Eval_
				|| $n instanceof Expr\Match_ || $n instanceof Expr\Throw_ || $n instanceof Expr\Exit_ || $n instanceof Expr\ErrorSuppress
				|| $n instanceof Expr\List_ || $n instanceof Expr\Isset_ || $n instanceof Expr\Empty_) {
				return true;
			}
			if (($n instanceof Expr\StaticCall || $n instanceof Expr\ClassConstFetch || $n instanceof Expr\StaticPropertyFetch || $n instanceof Expr\New_ || $n instanceof Expr\Instanceof_)
				&& (!$n->class instanceof Node\Name || in_array(strtolower($n->class->toString()), ['self', 'static', 'parent'], true))) {
				return true;
			}
			if ($n instanceof Expr\FuncCall) {
				if (!$n->name instanceof Node\Name) {
					return true;
				}
				if (in_array(strtolower($n->name->toString()), ['func_get_args', 'func_num_args', 'func_get_arg', 'compact', 'extract', 'get_called_class', 'get_class', 'debug_backtrace'], true)) {
					return true;
				}
			}
			if ($n instanceof Expr\Variable) {
				if (!is_string($n->name)) {
					return true;
				}
				if ($n->name !== 'this' && !isset($paramNames[$n->name])) {
					return true; // superglobals, static vars, undefined
				}
			}
			if ($n instanceof Expr\PropertyFetch && !($n->var instanceof Expr\Variable && $n->var->name === 'this')) {
				return true; // could be a same-class private on another instance
			}
			if ($n instanceof Expr\NullsafePropertyFetch || $n instanceof Expr\NullsafeMethodCall) {
				return true;
			}
			return false;
		});
		if ($forbidden !== null) {
			return null;
		}
		if (count($finder->find($expr, static fn (): bool => true)) > self::MAX_EXPR_NODES) {
			return null;
		}

		$thisUses = count($finder->find($expr, static fn (Node $n): bool => $n instanceof Expr\Variable && $n->name === 'this'));
		if ($thisUses !== 1 && !self::isSimple($node->var)) {
			return null;
		}
		foreach ($paramNames as $name => $i) {
			$uses = count($finder->find($expr, static fn (Node $n): bool => $n instanceof Expr\Variable && $n->name === $name));
			if ($uses !== 1 && !self::isSimple($args[$i]->value)) {
				return null;
			}
		}

		// everything the expression calls or reads must stay accessible from the
		// call site's class — private methods, constants and constructors do not
		if (!$this->innerMembersArePublic($expr, $declaringClass, $method, $paramNames)) {
			return null;
		}

		$publicize = [];
		$callSiteClass = $scope->getClassReflection();
		foreach ($finder->findInstanceOf($expr, Expr\PropertyFetch::class) as $fetch) {
			if (!$fetch->name instanceof Node\Identifier) {
				return null;
			}
			$propertyName = $fetch->name->toString();
			if (!$declaringClass->hasNativeProperty($propertyName)) {
				return null;
			}
			$property = $declaringClass->getNativeProperty($propertyName);
			if ($property->isPublic()) {
				continue;
			}
			$propertyDeclaringClass = $property->getDeclaringClass();
			if ($callSiteClass !== null && $callSiteClass->getName() === $propertyDeclaringClass->getName()) {
				continue;
			}
			foreach ($this->publicizeTargets($propertyDeclaringClass->getName(), $propertyName) as $target) {
				$publicize[] = $target;
			}
		}

		// PHPStan synthesizes MethodCall nodes (nullsafe desugaring, closures, …)
		// with position attributes copied from other nodes: accept only a node
		// whose source slice literally is this call
		$start = $node->getStartFilePos();
		$end = $node->getEndFilePos();
		if (!$this->sliceIsThisCall($scope->getFile(), $start, $end, $methodName, count($args))) {
			return null;
		}

		$replacement = $this->substitute($expr, $node->var, $paramNames, $args);
		$code = (new Standard())->prettyPrintExpr($replacement);
		if (!self::isAtom($replacement)) {
			$code = '(' . $code . ')';
		}

		return [
			'file' => $scope->getFile(),
			'start' => $start,
			'end' => $end,
			'replacement' => $code,
			'callee' => $declaringClass->getName() . '::' . $methodName,
			'publicize' => $publicize,
		];
	}

	/**
	 * @param array<string, int> $paramNames
	 */
	private function innerMembersArePublic(Expr $expr, ClassReflection $declaringClass, ExtendedMethodReflection $method, array $paramNames): bool
	{
		$finder = new NodeFinder();
		$parameters = $method->getOnlyVariant()->getParameters();
		foreach ($finder->findInstanceOf($expr, MethodCall::class) as $call) {
			if (!$call->name instanceof Node\Identifier) {
				return false;
			}
			$receivers = null;
			if ($call->var instanceof Expr\Variable && $call->var->name === 'this') {
				$receivers = [$declaringClass];
			} elseif ($call->var instanceof Expr\Variable && is_string($call->var->name) && isset($paramNames[$call->var->name])) {
				$parameter = $parameters[$paramNames[$call->var->name]] ?? null;
				$receivers = $parameter === null ? null : $parameter->getType()->getObjectClassReflections();
			} elseif ($call->var instanceof Expr\PropertyFetch && $call->var->var instanceof Expr\Variable && $call->var->var->name === 'this' && $call->var->name instanceof Node\Identifier) {
				$propertyName = $call->var->name->toString();
				$receivers = $declaringClass->hasNativeProperty($propertyName)
					? $declaringClass->getNativeProperty($propertyName)->getReadableType()->getObjectClassReflections()
					: null;
			}
			if ($receivers === null || $receivers === []) {
				return false;
			}
			foreach ($receivers as $receiver) {
				$name = $call->name->toString();
				if (!$receiver->hasNativeMethod($name) || !$receiver->getNativeMethod($name)->isPublic()) {
					return false;
				}
			}
		}
		foreach ($finder->findInstanceOf($expr, Expr\StaticCall::class) as $call) {
			if (!$call->class instanceof Node\Name || !$call->name instanceof Node\Identifier || !$this->reflectionProvider->hasClass($call->class->toString())) {
				return false;
			}
			$class = $this->reflectionProvider->getClass($call->class->toString());
			if (!$class->hasNativeMethod($call->name->toString()) || !$class->getNativeMethod($call->name->toString())->isPublic()) {
				return false;
			}
		}
		foreach ($finder->findInstanceOf($expr, Expr\ClassConstFetch::class) as $fetch) {
			if (!$fetch->class instanceof Node\Name || !$fetch->name instanceof Node\Identifier) {
				return false;
			}
			if (strtolower($fetch->name->toString()) === 'class') {
				continue;
			}
			if (!$this->reflectionProvider->hasClass($fetch->class->toString())) {
				return false;
			}
			$class = $this->reflectionProvider->getClass($fetch->class->toString());
			if (!$class->hasConstant($fetch->name->toString()) || !$class->getConstant($fetch->name->toString())->isPublic()) {
				return false;
			}
		}
		foreach ($finder->findInstanceOf($expr, Expr\New_::class) as $new) {
			if (!$new->class instanceof Node\Name || !$this->reflectionProvider->hasClass($new->class->toString())) {
				return false;
			}
			$class = $this->reflectionProvider->getClass($new->class->toString());
			if ($class->hasConstructor() && !$class->getConstructor()->isPublic()) {
				return false;
			}
		}
		if ($finder->findFirstInstanceOf($expr, Expr\StaticPropertyFetch::class) !== null) {
			return false; // rare; not worth resolving
		}

		return true;
	}

	/** @var array<string, string> */
	private array $fileContents = [];

	private function sliceIsThisCall(string $file, int $start, int $end, string $methodName, int $argCount): bool
	{
		if ($start < 0 || $end < $start) {
			return false;
		}
		if (!isset($this->fileContents[$file])) {
			$contents = file_get_contents($file);
			$this->fileContents[$file] = $contents === false ? '' : $contents;
		}
		$slice = substr($this->fileContents[$file], $start, $end - $start + 1);
		try {
			$stmts = (new ParserFactory())->createForHostVersion()->parse('<?php ' . $slice . ';');
		} catch (Throwable) {
			return false;
		}
		if ($stmts === null || count($stmts) !== 1 || !$stmts[0] instanceof Stmt\Expression) {
			return false;
		}
		$call = $stmts[0]->expr;

		return $call instanceof MethodCall
			&& $call->name instanceof Node\Identifier
			&& $call->name->toString() === $methodName
			&& !$call->isFirstClassCallable()
			&& count($call->getArgs()) === $argCount;
	}

	private function findMethodNode(ClassReflection $classReflection, string $methodName): ?Stmt\ClassMethod
	{
		$file = $classReflection->getFileName();
		if ($file === null) {
			return null;
		}
		$key = $file . '#' . strtolower($classReflection->getName());
		if (!isset($this->methodNodes[$key])) {
			$this->methodNodes[$key] = [];
			foreach ((new NodeFinder())->findInstanceOf($this->parser->parseFile($file), Stmt\ClassLike::class) as $classLike) {
				if (!isset($classLike->namespacedName) || strtolower($classLike->namespacedName->toString()) !== strtolower($classReflection->getName())) {
					continue;
				}
				foreach ($classLike->getMethods() as $classMethod) {
					$this->methodNodes[$key][strtolower($classMethod->name->toString())] = $classMethod;
				}
			}
		}

		return $this->methodNodes[$key][strtolower($methodName)] ?? null;
	}

	/**
	 * @param array<string, int> $paramNames
	 * @param list<Node\Arg> $args
	 */
	private function substitute(Expr $expr, Expr $receiver, array $paramNames, array $args): Expr
	{
		$cloner = new NodeTraverser(new CloningVisitor());
		$clone = $cloner->traverse([$expr])[0];
		$substituted = (new NodeTraverser(new class ($receiver, $paramNames, $args) extends NodeVisitorAbstract {

			/**
			 * @param array<string, int> $paramNames
			 * @param list<Node\Arg> $args
			 */
			public function __construct(private Expr $receiver, private array $paramNames, private array $args)
			{
			}

			#[Override]
			public function leaveNode(Node $node): ?Node
			{
				if (!$node instanceof Expr\Variable || !is_string($node->name)) {
					return null;
				}
				if ($node->name === 'this') {
					return (new NodeTraverser(new CloningVisitor()))->traverse([$this->receiver])[0];
				}
				if (isset($this->paramNames[$node->name])) {
					return (new NodeTraverser(new CloningVisitor()))->traverse([$this->args[$this->paramNames[$node->name]]->value])[0];
				}

				return null;
			}

		}))->traverse([$clone])[0];
		if (!$substituted instanceof Expr) {
			throw new ShouldNotHappenException();
		}

		return $substituted;
	}

	private static function isSimple(Expr $expr): bool
	{
		if ($expr instanceof Expr\Variable) {
			return is_string($expr->name);
		}
		if ($expr instanceof Expr\PropertyFetch) {
			return $expr->name instanceof Node\Identifier && self::isSimple($expr->var);
		}
		return $expr instanceof Node\Scalar\String_
			|| $expr instanceof Node\Scalar\Int_
			|| $expr instanceof Node\Scalar\Float_
			|| $expr instanceof Expr\ConstFetch
			|| ($expr instanceof Expr\ClassConstFetch && $expr->class instanceof Node\Name);
	}

	private static function isAtom(Expr $expr): bool
	{
		return $expr instanceof Expr\Variable
			|| $expr instanceof Expr\PropertyFetch
			|| $expr instanceof Expr\MethodCall
			|| $expr instanceof Expr\StaticCall
			|| $expr instanceof Expr\FuncCall
			|| $expr instanceof Expr\ConstFetch
			|| $expr instanceof Expr\ClassConstFetch
			|| $expr instanceof Expr\StaticPropertyFetch
			|| $expr instanceof Expr\ArrayDimFetch
			|| $expr instanceof Expr\Array_
			|| $expr instanceof Node\Scalar;
	}

	/** @var array<string, true>|null lowercase FQCNs from vendor/turbo-shadowed-classes.json */
	private ?array $shadowed = null;

	private function isShadowedByTurbo(ClassReflection $classReflection): bool
	{
		if ($this->shadowed === null) {
			$this->shadowed = [];
			$manifest = dirname(__DIR__, 3) . '/vendor/turbo-shadowed-classes.json';
			if (file_exists($manifest)) {
				foreach (array_keys(json_decode((string) file_get_contents($manifest), true)) as $className) {
					$this->shadowed[strtolower((string) $className)] = true;
				}
			}
		}
		foreach ([$classReflection, ...$classReflection->getParents()] as $class) {
			if (isset($this->shadowed[strtolower($class->getName())])) {
				return true;
			}
		}

		return false;
	}

	private ?OverridesScanner $scanner = null;

	private function scanner(): OverridesScanner
	{
		if ($this->scanner === null) {
			$this->scanner = new OverridesScanner();
			$this->overrides = $this->scanner->scan(self::directories());
		}

		return $this->scanner;
	}

	private function isOverridden(ClassReflection $declaringClass, string $methodName): bool
	{
		$this->scanner();

		return isset($this->overrides[strtolower($declaringClass->getName()) . '::' . strtolower($methodName)]);
	}

	/**
	 * The declaration to make public, in the class's own file or in the file
	 * of the trait declaring it, plus every redeclaration in a subclass (a
	 * caller reading the parent's property on a subclass instance would
	 * otherwise hit the subclass's private one).
	 *
	 * @return list<array{class: string, property: string, file: string|null}>
	 */
	private function publicizeTargets(string $className, string $propertyName): array
	{
		$scanner = $this->scanner();
		$lcClass = strtolower($className);
		$targets = [[
			'class' => $className,
			'property' => $propertyName,
			'file' => $scanner->propertyFile($lcClass, $propertyName),
		]];
		foreach ($scanner->redeclaringDescendants($lcClass, $propertyName) as $descendant) {
			$targets[] = [
				'class' => $descendant,
				'property' => $propertyName,
				'file' => $scanner->propertyFile($descendant, $propertyName),
			];
		}

		return $targets;
	}

	/**
	 * The code the phar holds and runs, as far as inlining reaches into it:
	 * PHPStan itself and the three vendor packages on its hot paths (the
	 * paths of build/inline.neon).
	 *
	 * @return list<string>
	 */
	public static function directories(): array
	{
		$root = dirname(__DIR__, 3);

		return [
			$root . '/src',
			$root . '/vendor/nikic/php-parser/lib',
			$root . '/vendor/phpstan/phpdoc-parser/src',
			$root . '/vendor/ondrejmirtes/better-reflection/src',
		];
	}

}
