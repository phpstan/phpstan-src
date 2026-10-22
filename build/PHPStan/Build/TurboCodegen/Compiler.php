<?php declare(strict_types = 1);

namespace PHPStan\Build\TurboCodegen;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\NodeAbstract;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use function addcslashes;
use function array_column;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_reverse;
use function array_search;
use function array_shift;
use function array_slice;
use function array_unique;
use function array_values;
use function basename;
use function class_exists;
use function count;
use function dirname;
use function explode;
use function file_get_contents;
use function function_exists;
use function get_parent_class;
use function glob;
use function implode;
use function in_array;
use function interface_exists;
use function is_a;
use function is_array;
use function is_bool;
use function is_file;
use function is_float;
use function is_int;
use function is_string;
use function is_subclass_of;
use function json_decode;
use function ksort;
use function lcfirst;
use function ltrim;
use function ord;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function preg_split;
use function range;
use function spl_object_id;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function stripslashes;
use function strlen;
use function strrpos;
use function strtolower;
use function strtoupper;
use function substr;
use function ucfirst;
use function ucwords;
use function var_export;
use const PREG_SET_ORDER;
use const PREG_SPLIT_DELIM_CAPTURE;

/**
 * Compiles PHP twins of turbo-shadowed classes to C++ registered through
 * reg::Class, the way a hand-written port is.
 *
 * The compiled subset is what the twins use: final classes, typed
 * properties, scalar and object locals, foreach, if, return, throw, static
 * and instance calls, `new`, arrow functions, `??=` on static properties and
 * array offsets. The lowering is driven by PHPStan's native types
 * (TypeOracle): an `int` stays a C++ zend_long, a `bool` a C++ bool, an
 * object of a class in the compile set is entered through a direct C++ call
 * (no engine frame) and read through its declared property slots. Anything
 * else stays a zval and goes through the engine (CodegenRuntime.h). A
 * construct outside the subset is a CompileError, never a silent fallback.
 */
final class Compiler
{

	/** implementations a virtual call tests the receiver's class against one by one; more go through a cached switch */
	private const VIRTUAL_DISPATCH_CHAIN_LIMIT = 4;

	/**
	 * builtins whose result depends on the calling class (`self`/`static`
	 * callables, the frame's scope); the ones resolving visibility through
	 * the object handlers (get_object_vars(), array_column()) see the
	 * compiled class as the fake scope callFunction() sets
	 */
	private const SCOPE_SENSITIVE_FUNCTIONS = ['is_callable', 'call_user_func', 'call_user_func_array', 'array_map', 'array_filter', 'usort', 'uasort', 'uksort', 'array_walk', 'array_reduce', 'get_class_methods', 'get_called_class', 'func_get_args', 'compact', 'extract', 'get_defined_vars'];

	private const CPP_KEYWORDS = ['and', 'or', 'not', 'xor', 'new', 'delete', 'default', 'namespace', 'class', 'union', 'switch', 'case', 'template', 'typename', 'operator', 'this', 'register', 'auto', 'char', 'short', 'long', 'float', 'double', 'int', 'bool', 'void', 'static', 'extern', 'const', 'volatile', 'goto', 'return', 'if', 'else', 'for', 'while', 'do', 'break', 'continue', 'try', 'catch', 'throw'];

	/** @var array<string, ClassUnit> lowercase FQCN => unit */
	private array $units = [];

	/** per-file state, set by compileClass() */
	private ?ClassUnit $current = null;

	/** @var array<array-key, int> literal => index (a numeric string literal is an int key) */
	private array $literals = [];

	/** @var list<string> */
	private array $siteDecls = [];

	/** @var array<string, string> */
	private array $siteNames = [];

	/** @var list<string> */
	private array $closures = [];

	private int $closureCount = 0;

	private NodeFinder $finder;

	/** @var array<string, int> engine calls emitted, by receiver class and method (survey statistics) */
	public array $engineCalls = [];

	/** @var array<string, string>|null lowercase FQCN => generated-header stem of every shadowed class */
	private ?array $manifest = null;

	/** @var array<string, array{function: string, kind: string, params: list<array{name: string, type: string, default: string|null}>, nullAsNull: bool}>|null */
	private ?array $nativeNewEntries = null;

	/** @var array<string, true> the method's variables holding a closure that may stay a native closure holder (closureHolderVariables()) */
	private array $holderVariables = [];

	/** @var list<string>|null */
	private ?array $shadowedClassNames = null;

	/** @var array<string, array<Node\Stmt>> */
	private array $parsedFiles = [];

	/** whether the closure expression being compiled may stay a native closure holder (a `callable` parameter's argument) */
	private bool $closureAsHolder = false;

	/** @var array<string, array{kind: string, params: list<array{name: string, type: string, default: string|null, enum?: string}>, variadic: string|null, nullAsNull: bool}>|null the hand-written direct entries (nativeEntries()) */
	private ?array $nativeEntries = null;

	/** @var array<string, true> generated headers the file being compiled reads slots from */
	private array $extraIncludes = [];

	/** @var array<string, string>|null lowercase FQCN => the native class-entry variable of every shadowed class */
	private ?array $shadowCes = null;

	/** @var array<string, true>|null class-entry variables some header declares */
	private ?array $declaredCes = null;

	/**
	 * @param array<string, array{file: string, stem: string, ce: string, implementation: string, defineCe?: bool, new?: array{string, string}, dispatch?: array{function: string, enum: string, prefix: string, receiver: string, fallback?: string}}> $classes FQCN => where its twin lives, its generated-header stem, its class-entry variable, the .cpp to write, and the native C API to export (see CompiledIntoTurboExtension)
	 */
	public function __construct(array $classes)
	{
		$this->finder = new NodeFinder();
		foreach ($classes as $className => $config) {
			$oracle = new TypeOracle($config['file']);
			$reflection = self::reflect($className);
			$unit = new ClassUnit($className, $config['stem'], $config['ce'], $reflection, $oracle, $oracle->getClass($className), $config['implementation'], $config['defineCe'] ?? false, $config['new'] ?? null, $config['dispatch'] ?? null);
			$this->units[strtolower($className)] = $unit;
		}
		foreach ($this->units as $unit) {
			foreach ($unit->node->getMethods() as $method) {
				$unit->methods[$method->name->toLowerString()] = $this->describeMethod($unit, $method);
			}
			foreach ($unit->node->getTraitUses() as $traitUse) {
				if ($traitUse->adaptations !== []) {
					throw new CompileError(sprintf('%s: trait adaptations (insteadof/as) are not supported', $unit->className));
				}
			}
			// trait methods, as PHPStan analysed them in this class, unless the class overrides them
			foreach ($unit->oracle->getTraitMethods($unit->className) as $lowerName => [$method, $trait]) {
				if (isset($unit->methods[$lowerName])) {
					continue;
				}
				$unit->methods[$lowerName] = $this->describeMethod($unit, $method, $trait);
			}
		}
	}

	/** @return array<string, string> file name => content */
	public function compile(): array
	{
		$files = ['turbo-ext/src/CodegenDecls.h' => $this->renderDeclarations()];
		foreach ($this->units as $unit) {
			$files[$unit->implementation] = $this->compileClass($unit);
		}
		return $files;
	}

	/**
	 * Compiles every method on its own and reports the first construct
	 * outside the compiled subset per method (null = compiles).
	 *
	 * @return array<string, array<string, string|null>> FQCN => method => error
	 */
	public function survey(): array
	{
		$report = [];
		foreach ($this->units as $unit) {
			$this->current = $unit;
			foreach ($unit->methods as $method) {
				$this->literals = [];
				$this->siteDecls = [];
				$this->siteNames = [];
				$this->closures = [];
				try {
					$this->compileMethod($method);
					$report[$unit->className][$method->name] = null;
				} catch (CompileError $e) {
					$report[$unit->className][$method->name] = $e->getMessage();
				}
			}
		}
		return $report;
	}

	// ---------------------------------------------------------------- declarations

	/**
	 * A class constant default as its value: callers in other files lower the
	 * default where the oracle cannot type the declaring file's nodes.
	 */
	private function resolvedDefault(?Expr $default, ReflectionParameter $parameter): ?Expr
	{
		if (!$default instanceof Expr\ClassConstFetch || !$parameter->isDefaultValueAvailable()) {
			return $default;
		}
		$value = $parameter->getDefaultValue();
		if (is_int($value)) {
			return new Node\Scalar\Int_($value);
		}
		if (is_string($value)) {
			return new Node\Scalar\String_($value);
		}
		return $default;
	}

	private function describeMethod(ClassUnit $unit, Stmt\ClassMethod $method, ?string $trait = null): MethodInfo
	{
		$reflection = new ReflectionMethod($unit->className, $method->name->toString());
		$params = [];
		foreach ($method->params as $i => $param) {
			$rp = $reflection->getParameters()[$i];
			[$kind, $declared, $describe, $class, $nullable, $isArray] = $this->lowerDeclaredType($rp->getType(), $unit, true);
			if ($kind === 'void') {
				throw new CompileError('void parameter type');
			}
			if ($param->variadic && $kind !== 'zval') {
				throw new CompileError(sprintf('%s::%s(): only object/array variadics are supported', $unit->className, $method->name));
			}
			$params[] = new ParamInfo(
				$rp->getName(),
				$i,
				$kind,
				$declared,
				$describe,
				$class,
				$nullable,
				$param->variadic,
				$this->resolvedDefault($param->default, $rp),
				$isArray,
				$param->flags !== 0,
				($param->flags & Modifiers::READONLY) !== 0,
				$param->byRef,
			);
		}

		$returnType = $reflection->getReturnType();
		if ($method->name->toLowerString() === '__construct' || ($returnType === null && !$this->returnsValue($method->stmts ?? []))) {
			$return = new ReturnInfo('void', new VoidType(), 'void');
		} elseif ($returnType === null) {
			// no declaration, but a value returned: mixed
			$return = new ReturnInfo('zval', new MixedType(), 'mixed');
		} else {
			[$kind, $declared, $describe, $class, $nullable] = $this->lowerDeclaredType($returnType, $unit, false);
			if ($kind === 'callable') {
				throw new CompileError('callable return types are not supported');
			}
			$return = new ReturnInfo($kind, $declared, $describe, $class, $nullable);
		}

		return new MethodInfo(
			$unit,
			$method->name->toString(),
			$method->isStatic(),
			$method->isPrivate() || $method->isFinal() || $unit->reflection->isFinal(),
			$params,
			$return,
			$method,
			$trait,
		);
	}

	/**
	 * Whether a body returns a value (closures and nested functions aside).
	 *
	 * @param array<Stmt> $stmts
	 */
	private function returnsValue(array $stmts): bool
	{
		$stack = $stmts;
		while ($stack !== []) {
			$node = array_pop($stack);
			if ($node instanceof Stmt\Return_ && $node->expr !== null) {
				return true;
			}
			if ($node instanceof Expr\Closure || $node instanceof Expr\ArrowFunction || $node instanceof Stmt\Function_ || $node instanceof Stmt\Class_) {
				continue;
			}
			foreach ($node->getSubNodeNames() as $name) {
				$sub = $node->$name;
				if ($sub instanceof Node) {
					$stack[] = $sub;
				} elseif (is_array($sub)) {
					foreach ($sub as $item) {
						if (!($item instanceof Node)) {
							continue;
						}

						$stack[] = $item;
					}
				}
			}
		}
		return false;
	}

	/**
	 * @return array{'long'|'bool'|'zval'|'callable'|'void', Type, string, string|null, bool, bool}
	 */
	private function lowerDeclaredType(?ReflectionType $type, ClassUnit $unit, bool $isParameter): array
	{
		if ($type === null) {
			return ['zval', new MixedType(), 'mixed', null, true, false];
		}
		if ($type instanceof ReflectionUnionType) {
			$members = [];
			foreach ($type->getTypes() as $member) {
				if (!$member instanceof ReflectionNamedType) {
					throw new CompileError(sprintf('%s: DNF declared types are not supported', $unit->className));
				}
				[, $memberType] = $this->lowerDeclaredType($member, $unit, $isParameter);
				$members[] = $memberType;
			}
			$union = TypeCombinator::union(...$members);
			return ['zval', $union, (string) $type, null, $type->allowsNull(), false];
		}
		if ($type instanceof ReflectionIntersectionType) {
			// an object of every member class (Scope&NodeCallbackInvoker&CollectedDataEmitter)
			$members = [];
			foreach ($type->getTypes() as $member) {
				if (!$member instanceof ReflectionNamedType || $member->isBuiltin()) {
					throw new CompileError(sprintf('%s: unsupported intersection member', $unit->className));
				}
				$members[] = new ObjectType($member->getName());
			}
			return ['zval', TypeCombinator::intersect(...$members), (string) $type, null, false, false];
		}
		if (!$type instanceof ReflectionNamedType) {
			throw new CompileError(sprintf('%s: unsupported declared type %s', $unit->className, (string) $type));
		}
		return $this->lowerNamedType($type->getName(), $type->allowsNull(), $unit);
	}

	/**
	 * A type written in the twin's source (closure signatures), lowered like
	 * a reflected one.
	 *
	 * @return array{'long'|'bool'|'zval'|'callable'|'void', Type, string, string|null, bool, bool}
	 */
	private function lowerAstType(?Node $type): array
	{
		if ($type === null) {
			return ['zval', new MixedType(), 'mixed', null, true, false];
		}
		if ($type instanceof Node\NullableType) {
			return $this->lowerNamedType($this->astTypeName($type->type), true, $this->unit());
		}
		if ($type instanceof Node\UnionType) {
			$members = [];
			$described = [];
			$nullable = false;
			foreach ($type->types as $member) {
				if (!$member instanceof Node\Identifier && !$member instanceof Node\Name) {
					throw new CompileError('DNF closure types are not supported');
				}
				$name = $this->astTypeName($member);
				$nullable = $nullable || strtolower($name) === 'null';
				[, $memberType] = $this->lowerNamedType($name, false, $this->unit());
				$members[] = $memberType;
				$described[] = $name;
			}
			return ['zval', TypeCombinator::union(...$members), implode('|', $described), null, $nullable, false];
		}
		if ($type instanceof Node\Identifier || $type instanceof Node\Name) {
			$name = $this->astTypeName($type);
			return $this->lowerNamedType($name, strtolower($name) === 'mixed' || strtolower($name) === 'null', $this->unit());
		}
		throw new CompileError('intersection closure types are not supported');
	}

	private function astTypeName(Node $type): string
	{
		if ($type instanceof Node\Name) {
			$lower = $type->toLowerString();
			if (in_array($lower, ['self', 'static'], true)) {
				return $this->unit()->className;
			}
			return ltrim($type->toString(), '\\');
		}
		if ($type instanceof Node\Identifier) {
			return $type->toString();
		}
		throw new CompileError('unsupported type node');
	}

	/**
	 * @return array{'long'|'bool'|'zval'|'callable'|'void', Type, string, string|null, bool, bool}
	 */
	private function lowerNamedType(string $name, bool $allowsNull, ClassUnit $unit): array
	{
		$nullable = $allowsNull && $name !== 'mixed' && $name !== 'null';
		$describe = ($nullable ? '?' : '') . $name;
		switch (strtolower($name)) {
			case 'int':
				if ($nullable) {
					return ['zval', TypeCombinator::addNull(new IntegerType()), $describe, null, true, false];
				}
				return ['long', new IntegerType(), $describe, null, false, false];
			case 'bool':
				if ($nullable) {
					return ['zval', TypeCombinator::addNull(new BooleanType()), $describe, null, true, false];
				}
				return ['bool', new BooleanType(), $describe, null, false, false];
			case 'void':
				return ['void', new VoidType(), 'void', null, false, false];
			case 'null':
				return ['zval', new NullType(), 'null', null, true, false];
			case 'false':
				return ['zval', new ConstantBooleanType(false), 'false', null, false, false];
			case 'true':
				return ['zval', new ConstantBooleanType(true), 'true', null, false, false];
			case 'float':
				$t = new FloatType();
				return ['zval', $nullable ? TypeCombinator::addNull($t) : $t, $describe, null, $nullable, false];
			case 'object':
				$t = new ObjectWithoutClassType();
				return ['zval', $nullable ? TypeCombinator::addNull($t) : $t, $describe, null, $nullable, false];
			case 'iterable':
				$t = new IterableType(new MixedType(), new MixedType());
				return ['zval', $nullable ? TypeCombinator::addNull($t) : $t, $describe, null, $nullable, false];
			case 'array':
				$t = new ArrayType(new MixedType(), new MixedType());
				return ['zval', $nullable ? TypeCombinator::addNull($t) : $t, $describe, null, $nullable, !$nullable];
			case 'string':
				$t = new StringType();
				return ['zval', $nullable ? TypeCombinator::addNull($t) : $t, $describe, null, $nullable, false];
			case 'callable':
				// a callable value, invoked through pt_type_call_callable()
				return ['zval', $nullable ? TypeCombinator::addNull(new CallableType()) : new CallableType(), $describe, null, $nullable, false];
			case 'mixed':
				return ['zval', new MixedType(), 'mixed', null, true, false];
			case 'self':
			case 'static':
				$name = $unit->className;
				$describe = ($nullable ? '?' : '') . $name;
				// no break
			default:
				$t = new ObjectType($name);
				return ['zval', $nullable ? TypeCombinator::addNull($t) : $t, $describe, $name, $nullable, false];
		}
	}

	private function renderDeclarations(): string
	{
		$out = [];
		$out[] = '/* Generated by turbo-ext/bin/compile-twins.php — do not edit. */';
		$out[] = '';
		$out[] = '#ifndef PHPSTANTURBO_CODEGEN_DECLS_H';
		$out[] = '#define PHPSTANTURBO_CODEGEN_DECLS_H';
		$out[] = '';
		$out[] = '#include "CodegenRuntime.h"';
		$out[] = '';
		$out[] = '/* the class entries of the compiled classes, set at activation */';
		foreach ($this->units as $unit) {
			$out[] = sprintf('extern zend_class_entry *%s;', $unit->ce);
		}
		$out[] = '';
		foreach ($this->units as $unit) {
			$out[] = sprintf('namespace pgen::%s {', $unit->stem);
			foreach ($unit->methods as $method) {
				$out[] = $this->prototype($method) . ';';
			}
			$out[] = sprintf('} // namespace pgen::%s', $unit->stem);
			$out[] = '';
		}
		$out[] = '#endif';
		return implode("\n", $out) . "\n";
	}

	private function cppMethodName(MethodInfo $method): string
	{
		return 'm_' . $method->name;
	}

	private function prototype(MethodInfo $method): string
	{
		$params = [];
		if (!$method->isStatic) {
			$params[] = 'zend_object *self';
		}
		foreach ($method->params as $param) {
			if ($param->variadic) {
				$params[] = sprintf('zval *p_%s, uint32_t p_%s_count', $param->name, $param->name);
				continue;
			}
			if ($param->byRef) {
				// the IS_REFERENCE zval the argument is bound through
				$params[] = 'zval *p_' . $param->name;
				continue;
			}
			switch ($param->kind) {
				case 'long':
					$params[] = 'zend_long p_' . $param->name;
					break;
				case 'bool':
					$params[] = 'bool p_' . $param->name;
					break;
				case 'callable':
					$params[] = 'pgrt::Callable &p_' . $param->name;
					break;
				default:
					$params[] = 'zval *p_' . $param->name;
			}
		}
		switch ($method->return->kind) {
			case 'long':
				$params[] = 'zend_long *ret';
				break;
			case 'bool':
				$params[] = 'bool *ret';
				break;
			case 'zval':
				$params[] = 'zval *ret';
				break;
		}
		return sprintf('[[nodiscard]] bool %s(%s)', $this->cppMethodName($method), implode(', ', $params));
	}

	// ---------------------------------------------------------------- per class

	/** @return ReflectionClass<object> */
	private static function reflect(string $className): ReflectionClass
	{
		if (!class_exists($className) && !interface_exists($className)) {
			throw new CompileError(sprintf('class %s does not exist', $className));
		}
		return new ReflectionClass($className);
	}

	private function unit(): ClassUnit
	{
		if ($this->current === null) {
			throw new CompileError('no class is being compiled');
		}
		return $this->current;
	}

	private function compileClass(ClassUnit $unit): string
	{
		$this->current = $unit;
		$this->extraIncludes = [];
		$this->literals = [];
		$this->siteDecls = [];
		$this->siteNames = [];
		$this->closures = [];
		$this->closureCount = 0;

		if ($unit->reflection->getTraitNames() !== [] && !str_contains($this->generatedHeader($unit->stem), 'inline void registerTraits(')) {
			// the methods a trait adds are not compiled into the class
			throw new CompileError(sprintf('%s uses a trait without a hand-written registrar', $unit->className));
		}

		$bodies = [];
		foreach ($unit->methods as $method) {
			if ($method->node->isAbstract()) {
				continue;
			}
			try {
				$bodies[] = $this->compileMethod($method);
			} catch (CompileError $e) {
				throw new CompileError(sprintf('%s(): %s', $this->label($method), $e->getMessage()), previous: $e);
			}
		}

		$exports = $this->renderExports($unit);
		$registration = $this->renderRegistration($unit);

		$out = [];
		$out[] = sprintf('/* Generated by turbo-ext/bin/compile-twins.php from %s — do not edit. */', $this->relativeFile($unit));
		$out[] = '';
		$out[] = '#include "CodegenDecls.h"';
		// the shared trait tables a generated header's registerTraits() names
		$out[] = '#include "TypeTraits.h"';
		$out[] = sprintf('#include "generated/%s.h"', $unit->stem);
		$traits = [];
		foreach ($unit->methods as $method) {
			if ($method->trait === null) {
				continue;
			}
			$traits[$this->shortName($method->trait)] = true;
		}
		foreach (array_keys($traits) as $trait) {
			$out[] = sprintf('#include "generated/%s.h"', $trait);
		}
		foreach (array_keys($this->extraIncludes) as $stem) {
			if ($stem === $unit->stem || isset($traits[$stem])) {
				continue;
			}
			$out[] = sprintf('#include "generated/%s.h"', $stem);
		}
		$out[] = '';
		if ($unit->defineCe) {
			$out[] = sprintf('zend_class_entry *%s = nullptr;', $unit->ce);
			$out[] = '';
		}
		if ($this->literals !== []) {
			$out[] = '/* the interned string literals, created at module startup */';
			$out[] = sprintf('static zval pg_lit[%d];', count($this->literals));
		}
		$out[] = '';
		foreach ($this->siteDecls as $decl) {
			$out[] = $decl;
		}
		$out[] = '';
		foreach ($this->closures as $closure) {
			$out[] = $closure;
			$out[] = '';
		}
		$out[] = sprintf('namespace pgen::%s {', $unit->stem);
		$out[] = '';
		foreach ($bodies as $body) {
			$out[] = $body;
			$out[] = '';
		}
		$out[] = sprintf('} // namespace pgen::%s', $unit->stem);
		$out[] = '';
		foreach ($exports as $line) {
			$out[] = $line;
		}
		$out[] = '';
		if ($unit->methods !== []) {
			$out[] = sprintf('namespace sigs = ptdecl::%s::sig;', $unit->stem);
			$out[] = '';
		}
		$out[] = $registration;
		return implode("\n", $out) . "\n";
	}

	private function relativeFile(ClassUnit $unit): string
	{
		// relative to the repository, the same in every checkout
		$root = dirname(__DIR__, 4) . '/';
		$file = (string) $unit->reflection->getFileName();

		return str_starts_with($file, $root) ? substr($file, strlen($root)) : $file;
	}

	private function renderRegistration(ClassUnit $unit): string
	{
		$out = [];
		$out[] = sprintf('PT_MINIT_REGISTRATION(pt_register_codegen_%s)', $unit->stem);
		$out[] = '{';
		$out[] = '/*LITERALS*/';
		$out[] = sprintf("\treg::Class cls(\"%s\");", addcslashes($unit->className, '\\'));
		$out[] = sprintf("\tptdecl::%s::declareClass(cls);", $unit->stem);
		$out[] = sprintf("\tptdecl::%s::declareProperties(cls);", $unit->stem);
		foreach ($unit->reflection->getReflectionConstants() as $constant) {
			if ($constant->getDeclaringClass()->getName() !== $unit->className) {
				continue;
			}
			$value = $constant->getValue();
			$visibility = $constant->isPrivate() ? 'private' : ($constant->isProtected() ? 'protected' : 'public');
			if (is_int($value)) {
				$out[] = sprintf("\tcls.%s(\"%s\", %d);", $visibility === 'public' ? 'classConstantLong' : $visibility . 'ClassConstantLong', $constant->getName(), $value);
			} elseif (is_string($value) && (str_contains($value, "\0") || $visibility === 'protected')) {
				// a C string cannot hold the NUL (and reg.h has no protected
				// string form): built with its length
				$builder = sprintf('pg_const_%s', $constant->getName());
				$this->closures[] = sprintf("static void %s(zval *out)\n{\n\tZVAL_INTERNED_STR(out, pgrt::internLiteral(%s, %d));\n}", $builder, $this->stringLiteral($value), strlen($value));
				$out[] = sprintf("\tcls.%s(\"%s\", %s);", $visibility === 'public' ? 'classConstantValue' : $visibility . 'ClassConstantValue', $constant->getName(), $builder);
			} elseif (is_string($value)) {
				$out[] = sprintf("\tcls.%s(\"%s\", \"%s\");", $visibility . 'ClassConstantString', $constant->getName(), self::cString($value));
			} elseif (is_array($value)) {
				// a persistent immutable array built at declaration
				$builder = sprintf('pg_const_%s', $constant->getName());
				$this->closures[] = $this->renderConstantBuilder($builder, $value);
				$out[] = sprintf("\tcls.%s(\"%s\", %s);", $visibility === 'public' ? 'classConstantValue' : $visibility . 'ClassConstantValue', $constant->getName(), $builder);
			} else {
				throw new CompileError(sprintf('%s::%s: only int/string/array constants are supported', $unit->className, $constant->getName()));
			}
		}
		foreach ($unit->methods as $method) {
			if ($method->node->isAbstract()) {
				// declared like the twin's, for the PHP subclasses to implement
				$out[] = sprintf("\tcls.method(sigs::%s, nullptr);", $method->name);
				continue;
			}
			$out[] = $this->renderGlue($method);
			$op = $this->opAdapter($method);
			if ($op === null) {
				continue;
			}

			$out[] = $op;
		}
		if (str_contains($this->generatedHeader($unit->stem), 'inline void registerTraits(')) {
			// the hand-written trait registrars: they skip the methods compiled
			// above and declare what the traits add besides (properties)
			$out[] = sprintf("\tptdecl::%s::registerTraits(cls);", $unit->stem);
		}
		$out[] = sprintf("\tcls.shadow(&%s);", $unit->ce);
		foreach ($this->familyRegistrations($unit) as $line) {
			$out[] = "\t" . $line;
		}
		$out[] = '}';
		// the literals the bodies and the glue (defaults) use, interned first
		$literals = [];
		foreach ($this->literals as $literal => $index) {
			$literals[] = sprintf("\tZVAL_INTERNED_STR(&pg_lit[%d], pgrt::internLiteral(%s, %d));", $index, $this->stringLiteral((string) $literal), strlen((string) $literal));
		}
		return str_replace("/*LITERALS*/\n", $literals === [] ? '' : implode("\n", $literals) . "\n", implode("\n", $out));
	}

	/**
	 * The builder of an array class constant: a persistent, immutable array
	 * of the twin's value (the engine references it for the process
	 * lifetime), nested arrays included.
	 *
	 * @param array<mixed> $value
	 */
	private function renderConstantBuilder(string $name, array $value): string
	{
		$lines = [];
		$counter = 0;
		$build = function (array $array) use (&$build, &$lines, &$counter): string {
			$table = 't' . $counter++;
			$lines[] = sprintf("\tHashTable *%s = pgrt::constArrayNew(%d);", $table, count($array));
			foreach ($array as $key => $item) {
				$z = 'v' . $counter++;
				$lines[] = sprintf("\tzval %s;", $z);
				if (is_int($item)) {
					$lines[] = sprintf("\tZVAL_LONG(&%s, %d);", $z, $item);
				} elseif (is_bool($item)) {
					$lines[] = sprintf("\tZVAL_BOOL(&%s, %s);", $z, $item ? 'true' : 'false');
				} elseif ($item === null) {
					$lines[] = sprintf("\tZVAL_NULL(&%s);", $z);
				} elseif (is_float($item)) {
					$lines[] = sprintf("\tZVAL_DOUBLE(&%s, %s);", $z, var_export($item, true));
				} elseif (is_string($item)) {
					$lines[] = sprintf("\tZVAL_INTERNED_STR(&%s, pgrt::internLiteral(%s, %d));", $z, $this->stringLiteral($item), strlen($item));
				} elseif (is_array($item)) {
					$lines[] = sprintf("\tpgrt::constArrayFinish(%s, &%s);", $build($item), $z);
				} else {
					throw new CompileError('an array constant holding an object or an enum');
				}
				if (is_int($key)) {
					$lines[] = sprintf("\tzend_hash_index_update(%s, %d, &%s);", $table, $key, $z);
				} else {
					$lines[] = sprintf("\tzend_hash_update(%s, pgrt::internLiteral(%s, %d), &%s);", $table, $this->stringLiteral($key), strlen($key), $z);
				}
			}
			return $table;
		};
		$root = $build($value);
		return implode("\n", [
			sprintf('static void %s(zval *out)', $name),
			'{',
			...$lines,
			sprintf("\tpgrt::constArrayFinish(%s, out);", $root),
			'}',
		]);
	}

	/**
	 * The direct entry native callers use instead of a by-name call when the
	 * method is one of TypeOps.h's operations: the op's argument vector
	 * distributed over the parameters and checked like a call.
	 */
	private function opAdapter(MethodInfo $method): ?string
	{
		if ($method->isStatic) {
			return null;
		}
		$op = 'PT_OP_' . strtoupper((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $method->name));
		if (!in_array($op, $this->opIds(), true)) {
			return null;
		}
		$f = new FunctionContext($this->label($method), 'return zv::Val();', null, null);
		$args = $this->distributeVector($f, $method, 'argv', 'argc');
		$value = $this->emitDirectCall($f, $method, 'self', $args);
		switch ($value->kind) {
			case 'long':
				$f->line(sprintf('return zv::Val::integer(%s);', $value->code));
				break;
			case 'bool':
				$f->line(sprintf('return zv::Val::boolean(%s);', $value->code));
				break;
			case 'zval':
				$f->line(sprintf('return %s;', $value->temp));
				break;
			default:
				$f->line('return zv::Val::null();');
		}
		return sprintf("\tcls.op(%s, PT_OP_LAMBDA {\n\t\t(void) scope;\n%s\n\t});", $op, preg_replace('/^\t/m', "\t\t", $f->render()));
	}

	/** the TypeOps.h operation a by-name call is, when its argument count matches the op's */
	private function opFor(string $name, string $argc): ?string
	{
		static $ops = null;
		if ($ops === null) {
			$ops = [];
			preg_match_all('~/\* (PT_OP_\w+) \*/ \{ "(\w+)", sizeof\("\w+"\) - 1, (\d+),~', (string) file_get_contents(__DIR__ . '/../../../../turbo-ext/src/TypeOps.cpp'), $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$ops[$match[2]] = [$match[1], (int) $match[3]];
			}
		}
		$op = $ops[strtolower($name)] ?? null;
		if ($op === null || (string) $op[1] !== $argc) {
			return null;
		}
		return $op[0];
	}

	/** @return list<string> */
	private function opIds(): array
	{
		static $ids = null;
		if ($ids === null) {
			preg_match_all('/^\s*(PT_OP_[A-Z_]+),/m', (string) file_get_contents(__DIR__ . '/../../../../turbo-ext/src/TypeOps.h'), $m);
			$ids = $m[1];
		}
		return $ids;
	}

	/**
	 * The native C API other files of the extension use for the class:
	 * its class-entry variable, a `pt_x_new()` constructor entering the
	 * compiled constructor, a member dispatch over a family enum.
	 *
	 * @return list<string>
	 */
	private function renderExports(ClassUnit $unit): array
	{
		$out = [];
		if ($unit->newExport !== null) {
			$out = [...$out, ...$this->renderNewExport($unit, $unit->newExport[0], $unit->newExport[1])];
		}
		if ($unit->dispatchExport !== null) {
			$out = [...$out, ...$this->renderDispatchExport($unit, $unit->dispatchExport)];
		}
		return $out;
	}

	/**
	 * The direct entries native dispatchers enter a class through, by the
	 * family it belongs to: an expression or statement handler's
	 * processExpr()/processStmt() for the walk, a node visitor's hooks for
	 * the native NodeTraverser (when the twin's hook always returns null).
	 *
	 * @return list<string> registration lines (after cls.shadow())
	 */
	private function familyRegistrations(ClassUnit $unit): array
	{
		$lines = [];
		$reflection = $unit->reflection;
		$handlers = [
			'PHPStan\\Analyser\\ExprHandler' => ['processExpr', 'pt_expr_handler_entry_register', 7],
			'PHPStan\\Analyser\\StmtHandler' => ['processStmt', 'pt_stmt_handler_entry_register', 6],
		];
		foreach ($handlers as $interface => [$methodName, $register, $count]) {
			if (!$reflection->implementsInterface($interface)) {
				continue;
			}
			$method = $unit->methods[strtolower($methodName)] ?? null;
			if ($method === null || count($method->params) !== $count) {
				continue;
			}
			foreach ($method->params as $param) {
				if ($param->kind !== 'zval' || $param->variadic || $param->byRef) {
					throw new CompileError(sprintf('%s::%s() does not match the handler entry signature', $unit->className, $methodName));
				}
			}
			$lines[] = sprintf('%s(&%s, [](%s) -> zv::Val { zv::Val r; if (UNEXPECTED(!pgen::%s::%s(%s))) return zv::Val(); return r; });', $register, $unit->ce, 'zend_object *handler, ' . implode(', ', array_map(static fn (int $i): string => 'zval *a' . $i, range(1, $count))), $unit->stem, $this->cppMethodName($method), implode(', ', ['handler', ...array_map(static fn (int $i): string => 'a' . $i, range(1, $count)), 'r.raw()']));
		}
		if ($reflection->implementsInterface('PhpParser\\NodeVisitor')) {
			$hooks = [];
			foreach (['enterNode', 'leaveNode'] as $hook) {
				$method = $unit->methods[strtolower($hook)] ?? null;
				if ($method === null || !$this->alwaysReturnsNull($method->node) || count($method->params) !== 1) {
					$hooks[] = 'NULL';
					continue;
				}
				$hooks[] = sprintf('[](zend_object *visitor, zend_object *node) -> bool { zval nodeZv; ZVAL_OBJ(&nodeZv, node); zv::Val r; return pgen::%s::%s(visitor, &nodeZv, r.raw()); }', $unit->stem, $this->cppMethodName($method));
			}
			if ($hooks !== ['NULL', 'NULL']) {
				$lines[] = sprintf('static const pt_native_visitor entry = { &%s, %s, %s, NULL };', $unit->ce, $hooks[0], $hooks[1]);
				$lines[] = 'pt_native_visitor_register(&entry);';
			}
		}
		return $lines;
	}

	/** whether every return of a method returns null (or nothing), so its native hook can omit the result */
	private function alwaysReturnsNull(Stmt\ClassMethod $method): bool
	{
		foreach ($this->scopeNodes($method->stmts ?? []) as $node) {
			if (!$node instanceof Stmt\Return_) {
				continue;
			}
			if ($node->expr === null) {
				continue;
			}
			if ($node->expr instanceof Expr\ConstFetch && $node->expr->name->toLowerString() === 'null') {
				continue;
			}
			return false;
		}
		return true;
	}

	/**
	 * `new X(...)` for native callers, in the calling convention the
	 * hand-written port exported: `val` returns zv::Val (UNDEF = pending
	 * exception) and takes the constructor's parameters, `out` writes *out
	 * and returns false on an exception, `argv` takes the arguments as a
	 * vector.
	 *
	 * @return list<string>
	 */
	private function renderNewExport(ClassUnit $unit, string $name, string $flavor): array
	{
		$constructor = $unit->methods['__construct'] ?? null;
		$inherited = $constructor === null ? $unit->reflection->getConstructor() : null;
		if ($inherited !== null && $inherited->getNumberOfParameters() > 0) {
			throw new CompileError(sprintf('%s inherits a constructor with parameters; export its `new` by hand', $unit->className));
		}
		$params = $constructor !== null ? $constructor->params : [];
		$decl = [];
		$pre = [];
		$args = [];
		if ($flavor === 'argv') {
			$decl[] = 'uint32_t argc';
			$decl[] = 'zval *argv';
			if ($constructor !== null) {
				$f = new FunctionContext($this->label($constructor), 'return zv::Val();', null, null);
				$args = $this->distributeVector($f, $constructor, 'argv', 'argc');
				$pre[] = $f->render();
			}
		} else {
			foreach ($params as $param) {
				if ($param->variadic) {
					throw new CompileError('variadic constructors cannot be exported');
				}
				// C++ keywords are legal PHP parameter names ($default)
				$var = 'a_' . $param->name;
				if ($param->kind === 'long') {
					$decl[] = 'zend_long ' . $var;
					$args[] = $var;
				} elseif ($param->kind === 'bool') {
					$decl[] = 'bool ' . $var;
					$args[] = $var;
				} elseif ($param->kind === 'callable') {
					throw new CompileError('callable constructor parameters cannot be exported');
				} elseif (TypeCombinator::removeNull($param->declared)->isString()->yes()) {
					// strings travel as zend_string * (NULL = null)
					$decl[] = 'zend_string *' . $var;
					$pre[] = sprintf("\tzval %s_zv;", $var);
					$none = $param->default !== null ? $this->constantZvalInit($var . '_zv', $param->default) : sprintf('ZVAL_NULL(&%s_zv);', $var);
					$pre[] = sprintf("\tif (%s != NULL) { ZVAL_STR(&%s_zv, %s); } else { %s }", $var, $var, $var, $none);
					$args[] = '&' . $var . '_zv';
				} else {
					// the hand-written ports' convention: NULL = null, or the
					// parameter's default where it has one
					$decl[] = 'zval *' . $var;
					$pre[] = sprintf("\tzval %s_none;", $var);
					$pre[] = "\t" . ($param->default !== null ? $this->constantZvalInit($var . '_none', $param->default) : sprintf('ZVAL_NULL(&%s_none);', $var));
					$pre[] = sprintf("\tif (%s == NULL) %s = &%s_none;", $var, $var, $var);
					$args[] = $var;
				}
			}
		}
		$construct = $constructor !== null
			? sprintf('pgen::%s::%s(%s)', $unit->stem, $this->cppMethodName($constructor), implode(', ', ['Z_OBJ(object)', ...$args]))
			: ($inherited !== null ? 'pgrt::callInheritedConstructor(Z_OBJ(object))' : 'true');
		$out = [];
		$out[] = sprintf('/* new %s(...) for native callers */', $unit->className);
		if ($flavor === 'out') {
			$out[] = sprintf('bool %s(%s)', $name, implode(', ', ['zval *out', ...$decl]));
		} else {
			$out[] = sprintf('zv::Val %s(%s)', $name, implode(', ', $decl));
		}
		$fail = $flavor === 'out' ? 'return false;' : 'return zv::Val();';
		$out[] = '{';
		foreach ($pre as $line) {
			$out[] = str_replace('return zv::Val();', $fail, $line);
		}
		$out[] = "\tzval object;";
		$out[] = sprintf("\tif (UNEXPECTED(object_init_ex(&object, %s) != SUCCESS)) %s", $unit->ce, $fail);
		$out[] = sprintf("\tif (UNEXPECTED(!%s)) {", $construct);
		$out[] = "\t\tzend_object_store_ctor_failed(Z_OBJ(object));";
		$out[] = "\t\tzval_ptr_dtor(&object);";
		$out[] = "\t\t" . $fail;
		$out[] = "\t}";
		$out[] = $flavor === 'out' ? "\tZVAL_COPY_VALUE(out, &object);
	return true;" : "\treturn zv::Val::adopt(object);";
		$out[] = '}';
		$out[] = '';
		return $out;
	}

	/**
	 * A family's member dispatch (`pt_x_call(object, member)`): every member
	 * of the enum the class implements entered directly, the others through
	 * the family's by-name fallback.
	 *
	 * @param array{function: string, enum: string, prefix: string, receiver: string, fallback?: string} $dispatch
	 * @return list<string>
	 */
	private function renderDispatchExport(ClassUnit $unit, array $dispatch): array
	{
		$members = $this->enumMembers($dispatch['enum'], $dispatch['prefix']);
		$out = [];
		$out[] = sprintf('/* the %s dispatch for native callers */', $dispatch['enum']);
		$out[] = sprintf('zv::Val %s(zend_object *%s, %s member)', $dispatch['function'], $dispatch['receiver'], $dispatch['enum']);
		$out[] = '{';
		$out[] = "\tswitch (member) {";
		foreach ($members as $constant => $suffix) {
			$methodName = lcfirst(str_replace('_', '', ucwords(strtolower($suffix), '_')));
			$method = $unit->methods[strtolower($methodName)] ?? null;
			if ($suffix === 'MEMBER_COUNT') {
				$out[] = sprintf("\t\tcase %s: break;", $constant);
				continue;
			}
			if ($method === null || $method->isStatic || $method->params !== []) {
				if (!isset($dispatch['fallback'])) {
					throw new CompileError(sprintf('%s has no method for %s and no fallback', $unit->className, $constant));
				}
				$out[] = sprintf("\t\tcase %s: return %s(%s, member);", $constant, $dispatch['fallback'], $dispatch['receiver']);
				continue;
			}
			$call = sprintf('pgen::%s::%s', $unit->stem, $this->cppMethodName($method));
			switch ($method->return->kind) {
				case 'bool':
					$out[] = sprintf("\t\tcase %s: { bool r; if (UNEXPECTED(!%s(%s, &r))) return zv::Val(); return zv::Val::boolean(r); }", $constant, $call, $dispatch['receiver']);
					break;
				case 'long':
					$out[] = sprintf("\t\tcase %s: { zend_long r; if (UNEXPECTED(!%s(%s, &r))) return zv::Val(); return zv::Val::integer(r); }", $constant, $call, $dispatch['receiver']);
					break;
				case 'zval':
					$out[] = sprintf("\t\tcase %s: { zv::Val r; if (UNEXPECTED(!%s(%s, r.raw()))) return zv::Val(); return r; }", $constant, $call, $dispatch['receiver']);
					break;
				default:
					$out[] = sprintf("\t\tcase %s: { if (UNEXPECTED(!%s(%s))) return zv::Val(); return zv::Val::null(); }", $constant, $call, $dispatch['receiver']);
			}
		}
		$out[] = "\t}";
		$out[] = "\tZEND_UNREACHABLE();";
		$out[] = "\treturn zv::Val();";
		$out[] = '}';
		$out[] = '';
		return $out;
	}

	/** @return array<string, string> constant => suffix after the prefix, in declaration order */
	private function enumMembers(string $enum, string $prefix): array
	{
		foreach (glob(__DIR__ . '/../../../../turbo-ext/src/*.h') ?: [] as $header) {
			$code = (string) file_get_contents($header);
			if (preg_match('/enum\s+' . preg_quote($enum, '/') . '\b[^{]*\{(.*?)\}/s', $code, $m) !== 1) {
				continue;
			}
			preg_match_all('/\b(' . preg_quote($prefix, '/') . '(\w+))\b/', (string) preg_replace('~/\*.*?\*/~s', '', $m[1]), $matches, PREG_SET_ORDER);
			$members = [];
			foreach ($matches as $match) {
				$members[$match[1]] = $match[2];
			}
			return $members;
		}
		throw new CompileError(sprintf('enum %s not found', $enum));
	}

	/**
	 * The body of a C string literal of bytes: quotes, backslashes, `?`
	 * (trigraphs) and every non-printable byte escaped — three-digit octal,
	 * so a following digit cannot extend the escape.
	 */
	private static function cString(string $bytes): string
	{
		$out = '';
		$length = strlen($bytes);
		for ($i = 0; $i < $length; $i++) {
			$c = $bytes[$i];
			$o = ord($c);
			if (in_array($c, ['"', '\\', '?'], true)) {
				$out .= '\\' . $c;
			} elseif ($o < 0x20 || $o === 0x7F) {
				$out .= sprintf('\\%03o', $o);
			} else {
				$out .= $c;
			}
		}
		return $out;
	}

	/** drops one pair of enclosing parentheses (`if ((a == b))` warns) */
	private static function unparen(string $code): string
	{
		if (!str_starts_with($code, '(') || !str_ends_with($code, ')')) {
			return $code;
		}
		$depth = 0;
		$length = strlen($code);
		for ($i = 0; $i < $length; $i++) {
			if ($code[$i] === '(') {
				$depth++;
			} elseif ($code[$i] === ')') {
				$depth--;
				if ($depth === 0 && $i !== $length - 1) {
					return $code;
				}
			}
		}
		return substr($code, 1, -1);
	}

	private function generatedHeader(string $stem): string
	{
		$path = __DIR__ . '/../../../../turbo-ext/src/generated/' . $stem . '.h';
		return is_file($path) ? (string) file_get_contents($path) : '';
	}

	/**
	 * `ptdecl::<Stem>::slot::<name>` of a declared instance property of a
	 * class: from the generated header of the declaring class (an inherited
	 * property keeps its slot in every subclass), null where there is none
	 * (a trait's property, a class that is not shadowed). The header is
	 * included by the file being compiled.
	 */
	private function slotExpr(string $class, string $property, string $object): ?string
	{
		if (!class_exists($class)) {
			return null;
		}
		$reflection = new ReflectionClass($class);
		if (!$reflection->hasProperty($property) || $reflection->getProperty($property)->isStatic()) {
			return null;
		}
		$declaring = $reflection->getProperty($property)->getDeclaringClass()->getName();
		$stem = $this->stemOf($declaring);
		if ($stem === null || preg_match('/inline constexpr uint32_t ' . preg_quote($property, '/') . ' = /', $this->generatedHeader($stem)) !== 1) {
			// declared, but by a trait or a class without a generated header:
			// the slot looked up once per receiver class
			$site = $this->site('pgrt::PropIndexSite', sprintf('slot_%s_%d', $property, count($this->siteNames)));
			return sprintf('pgrt::propIndex(%s, %s, "%s", %d)', $site, $object, $property, strlen($property));
		}
		$this->extraIncludes[$stem] = true;
		return sprintf('ptdecl::%s::slot::%s', $stem, $property);
	}

	/**
	 * Whether `$x->name` on an instance of the class may read a cached slot:
	 * a declared, non-static, public property without hooks (subclasses
	 * cannot narrow its visibility; any other case the cache misses falls
	 * back to the handlers).
	 */
	/**
	 * Whether every class the receiver can be declares the property publicly
	 * (a subclass cannot narrow it): the receiver's one class, or each class
	 * of an object union like Assign|AssignRef|AssignOp.
	 */
	private function isCacheablePropertyOf(?string $class, Type $native, string $name): bool
	{
		if ($class !== null) {
			return $this->isCacheableProperty($class, $name);
		}
		if (!$native->isObject()->yes()) {
			return false;
		}
		$names = $native->getObjectClassNames();
		if ($names === []) {
			return false;
		}
		foreach ($names as $candidate) {
			if (!$this->isCacheableProperty($candidate, $name)) {
				return false;
			}
		}
		return true;
	}

	/** whether one of the object classes of a type is compiled (its reads have the slot-speculation path) */
	private function isCompiledPropertyOf(Type $type): bool
	{
		foreach ($type->getObjectClassNames() as $candidate) {
			if (isset($this->units[strtolower($candidate)])) {
				return true;
			}
		}
		return false;
	}

	private function isCacheableProperty(string $class, string $name): bool
	{
		if (!class_exists($class) && !interface_exists($class)) {
			return false;
		}
		$reflection = new ReflectionClass($class);
		if (!$reflection->hasProperty($name)) {
			return false;
		}
		$property = $reflection->getProperty($name);
		if ($property->isStatic() || !$property->isPublic()) {
			return false;
		}
		return !$property->hasHooks();
	}

	/** the generated-header stem of a shadowed class */
	private function stemOf(string $class): ?string
	{
		$unit = $this->units[strtolower($class)] ?? null;
		if ($unit !== null) {
			return $unit->stem;
		}
		if ($this->manifest === null) {
			$this->manifest = [];
			$json = json_decode((string) file_get_contents(__DIR__ . '/../../../../vendor/turbo-shadowed-classes.json'), true);
			foreach (is_array($json) ? $json : [] as $name => $entry) {
				if (!is_array($entry) || !isset($entry['cpp']) || !is_string($entry['cpp'])) {
					continue;
				}
				$this->manifest[strtolower((string) $name)] = basename($entry['cpp'], '.cpp');
			}
		}
		return $this->manifest[strtolower($class)] ?? null;
	}

	private function shortName(string $class): string
	{
		$parts = explode('\\', $class);
		return $parts[count($parts) - 1];
	}

	private function sigName(MethodInfo $method): string
	{
		$name = $method->name;
		if (in_array(strtolower($name), self::CPP_KEYWORDS, true)) {
			return $name . '_';
		}
		return $name;
	}

	private function renderGlue(MethodInfo $method): string
	{
		$label = $this->label($method);
		$lines = [];
		$args = [];
		$required = 0;
		$parse = [];
		$after = [];
		$optionalStarted = false;
		foreach ($method->params as $param) {
			$name = 'p_' . $param->name;
			if ($param->variadic) {
				$lines[] = sprintf('zval *%s = NULL;', $name);
				$lines[] = sprintf('uint32_t %s_count = 0;', $name);
				$parse[] = sprintf("Z_PARAM_VARIADIC('*', %s, %s_count)", $name, $name);
				if ($param->class !== null) {
					$after[] = sprintf('for (uint32_t i = 0; i < %s_count; i++) {', $name);
					$after[] = sprintf("\tif (UNEXPECTED(!%s)) {", $this->instanceCheck(sprintf('zv::Ref(&%s[i]).deref().raw()', $name), $param->class, $param->nullable));
					$after[] = sprintf("\t\tzend_argument_type_error(%d + i, \"must be of type %%s\", \"%s\");", $param->position + 1, addcslashes($param->describe, '\\"'));
					$after[] = "\t\tRETURN_THROWS();";
					$after[] = "\t}";
					$after[] = '}';
				}
				$args[] = $name;
				$args[] = $name . '_count';
				continue;
			}
			if ($param->default !== null && !$optionalStarted) {
				$parse[] = 'Z_PARAM_OPTIONAL';
				$optionalStarted = true;
			}
			if ($param->byRef) {
				if ($param->default === null) {
					$required++;
				}
				$lines[] = sprintf('zval *%s = NULL;', $name);
				$parse[] = sprintf('Z_PARAM_ZVAL(%s)', $name);
				if ($param->default !== null) {
					// omitted: a fresh reference to the default, dropped after the call
					$lines[] = sprintf('zv::Val %s_omitted;', $name);
					$after[] = sprintf('if (%s == NULL) { zval d; %s zval r; ZVAL_NEW_REF(&r, &d); %s_omitted = zv::Val::adopt(r); %s = %s_omitted.raw(); }', $name, $this->constantZvalInit('d', $param->default), $name, $name, $name);
				}
				$check = $this->typeCheck(sprintf('zv::Ref(%s).deref().raw()', $name), $param->declared, $param->describe);
				if ($check !== null) {
					$after[] = sprintf('if (UNEXPECTED(!%s)) { zend_argument_type_error(%d, "must be of type %%s, %%s given", "%s", zend_zval_value_name(zv::Ref(%s).deref().raw())); RETURN_THROWS(); }', $check, $param->position + 1, addcslashes($param->describe, '\\"'), $name);
				}
				$args[] = $name;
				continue;
			}
			if ($param->default === null) {
				$required++;
			}
			switch ($param->kind) {
				case 'long':
					$lines[] = sprintf('zend_long %s = %s;', $name, $param->default !== null ? $this->constantLong($param->default) : '0');
					$parse[] = sprintf('Z_PARAM_LONG(%s)', $name);
					$args[] = $name;
					break;
				case 'bool':
					$lines[] = sprintf('bool %s = %s;', $name, $param->default !== null ? $this->constantBool($param->default) : 'false');
					$parse[] = sprintf('Z_PARAM_BOOL(%s)', $name);
					$args[] = $name;
					break;
				case 'callable':
					$lines[] = sprintf('pgrt::Callable %s;', $name);
					$parse[] = sprintf('Z_PARAM_FUNC(%s.fci, %s.fcc)', $name, $name);
					$args[] = $name;
					break;
				default:
					$lines[] = sprintf('zval *%s = NULL;', $name);
					if ($param->default !== null) {
						$lines[] = sprintf('zval %s_default;', $name);
						$lines[] = $this->constantZvalInit($name . '_default', $param->default);
						$lines[] = sprintf('%s = &%s_default;', $name, $name);
					}
					if ($param->class !== null) {
						// parsed as any value, then checked against the class
						// (instanceCheck() accepts the native class and the
						// twin), so a non-object is reported with the class
						// name like the twin does
						// (a DI service's constructor takes any object, like the
						// hand ports: the container wires the right ones, and the
						// differential tests hand in recording doubles)
						$serviceConstructor = $method->name === '__construct' && $method->unit->reflection->getAttributes(AutowiredService::class) !== [];
						$parse[] = sprintf('Z_PARAM_%s%s(%s)', $serviceConstructor ? 'OBJECT' : 'ZVAL', $param->nullable ? '_OR_NULL' : '', $name);
						$after[] = $serviceConstructor ? '' : sprintf('if (%s != NULL && UNEXPECTED(%s || !%s)) { zend_argument_type_error(%d, "must be of type %%s, %%s given", "%s", zend_zval_value_name(%s)); RETURN_THROWS(); }', $name, $this->nonObjectCheck($name, $param->nullable), $this->instanceCheck($name, $param->class, $param->nullable), $param->position + 1, addcslashes($param->describe, '\\"'), $name);
						if ($param->nullable) {
							$lines[] = sprintf('zval %s_null;', $name);
							$lines[] = sprintf('ZVAL_NULL(&%s_null);', $name);
							$after[] = sprintf('if (%s == NULL) %s = &%s_null;', $name, $name, $name);
						}
					} else {
						$this->scalarParamGlue($param, $name, $lines, $parse, $after);
					}
					$args[] = $name;
			}
		}

		$self = $method->isStatic ? null : 'Z_OBJ_P(ZEND_THIS)';
		$callArgs = $args;
		if ($self !== null) {
			$callArgs = [$self, ...$callArgs];
		}
		$call = sprintf('pgen::%s::%s', $method->unit->stem, $this->cppMethodName($method));

		$body = [];
		foreach ($lines as $line) {
			$body[] = $line;
		}
		// no upper bound: a userland method ignores the arguments it does
		// not declare ($this->{$name}($a, $b) on a one-parameter method)
		if ($parse !== []) {
			$body[] = sprintf('ZEND_PARSE_PARAMETERS_START(%d, -1)', $required);
			foreach ($parse as $p) {
				$body[] = "\t" . $p;
			}
			$body[] = 'ZEND_PARSE_PARAMETERS_END();';
		}
		foreach ($after as $line) {
			$body[] = $line;
		}
		switch ($method->return->kind) {
			case 'long':
				$body[] = 'zend_long r;';
				$body[] = sprintf('if (UNEXPECTED(!%s(%s))) RETURN_THROWS();', $call, implode(', ', [...$callArgs, '&r']));
				$body[] = 'RETURN_LONG(r);';
				break;
			case 'bool':
				$body[] = 'bool r;';
				$body[] = sprintf('if (UNEXPECTED(!%s(%s))) RETURN_THROWS();', $call, implode(', ', [...$callArgs, '&r']));
				$body[] = 'RETURN_BOOL(r);';
				break;
			case 'zval':
				$body[] = 'ZVAL_UNDEF(return_value);';
				$body[] = sprintf('if (UNEXPECTED(!%s(%s))) RETURN_THROWS();', $call, implode(', ', [...$callArgs, 'return_value']));
				break;
			default:
				$body[] = sprintf('if (UNEXPECTED(!%s(%s))) RETURN_THROWS();', $call, implode(', ', $callArgs));
		}

		$out = [];
		$sigs = $method->trait !== null ? sprintf('ptdecl::%s::sig', $this->shortName($method->trait)) : 'sigs';
		$out[] = sprintf("\tcls.method(%s::%s, [](INTERNAL_FUNCTION_PARAMETERS) { /* %s() */", $sigs, $this->sigName($method), $label);
		foreach ($body as $line) {
			$out[] = "\t\t" . $line;
		}
		$out[] = "\t});";
		return implode("\n", $out);
	}

	/** The glue's test for a value a class-typed parameter rejects before its class is checked. */
	private function nonObjectCheck(string $name, bool $nullable): string
	{
		if ($nullable) {
			return sprintf('(Z_TYPE_P(%s) != IS_OBJECT && Z_TYPE_P(%s) != IS_NULL)', $name, $name);
		}

		return sprintf('Z_TYPE_P(%s) != IS_OBJECT', $name);
	}

	/**
	 * The ZPP of a zval-lowered parameter that is not an object: the engine's
	 * own macro for its type, so a weak-mode caller gets the same coercions a
	 * PHP function would apply, and a borrowed zval over the parsed value.
	 *
	 * @param list<string> $lines
	 * @param list<string> $parse
	 * @param list<string> $after
	 */
	private function scalarParamGlue(ParamInfo $param, string $name, array &$lines, array &$parse, array &$after): void
	{
		$declared = TypeCombinator::removeNull($param->declared);
		$orNull = $param->nullable ? '_OR_NULL' : '';
		$nullNormalize = static function () use ($name, &$lines, &$after): void {
			$lines[] = sprintf('zval %s_null;', $name);
			$lines[] = sprintf('ZVAL_NULL(&%s_null);', $name);
			$after[] = sprintf('if (%s == NULL) %s = &%s_null;', $name, $name, $name);
		};
		if ($param->declared instanceof MixedType) {
			$parse[] = sprintf('Z_PARAM_ZVAL(%s)', $name);
			return;
		}
		if ($declared->equals(new CallableType())) {
			$parse[] = sprintf('Z_PARAM_ZVAL%s(%s)', $param->nullable ? '_OR_NULL' : '', $name);
			$after[] = sprintf('if (%s != NULL && %sUNEXPECTED(!zend_is_callable(%s, 0, NULL))) { zend_argument_type_error(%d, "must be a valid callback, %%s given", zend_zval_value_name(%s)); RETURN_THROWS(); }', $name, $param->nullable ? sprintf('Z_TYPE_P(%s) != IS_NULL && ', $name) : '', $name, $param->position + 1, $name);
			if ($param->nullable) {
				$nullNormalize();
			}
			return;
		}
		if ($declared->isObject()->yes()) {
			// an intersection or union of classes: any value, then checked
			$check = $this->typeCheck($name, $declared, $param->describe);
			$parse[] = sprintf('Z_PARAM_%s%s(%s)', $check !== null ? 'ZVAL' : 'OBJECT', $orNull, $name);
			if ($check !== null) {
				$after[] = sprintf('if (%s != NULL && UNEXPECTED(%s || !%s)) { zend_argument_type_error(%d, "must be of type %%s, %%s given", "%s", zend_zval_value_name(%s)); RETURN_THROWS(); }', $name, $this->nonObjectCheck($name, $param->nullable), $check, $param->position + 1, addcslashes($param->describe, '\\"'), $name);
			}
			if ($param->nullable) {
				$nullNormalize();
			}
			return;
		}
		if ($declared->isArray()->yes()) {
			$parse[] = sprintf('Z_PARAM_ARRAY%s(%s)', $orNull, $name);
			if ($param->nullable) {
				$nullNormalize();
			}
			return;
		}
		if ($declared->isString()->yes()) {
			$lines[] = sprintf('zend_string *%s_str = NULL;', $name);
			$lines[] = sprintf('zval %s_zv;', $name);
			$parse[] = sprintf('Z_PARAM_STR%s(%s_str)', $orNull, $name);
			$after[] = sprintf('if (%s_str != NULL) { ZVAL_STR(&%s_zv, %s_str); %s = &%s_zv; }', $name, $name, $name, $name, $name);
			if ($param->nullable) {
				$nullNormalize();
			}
			return;
		}
		if ($param->nullable && ($declared->isInteger()->yes() || $declared->isBoolean()->yes() || $declared->isFloat()->yes())) {
			[$macro, $ctype, $set] = $declared->isInteger()->yes() ? ['LONG', 'zend_long', 'ZVAL_LONG'] : ($declared->isBoolean()->yes() ? ['BOOL', 'bool', 'ZVAL_BOOL'] : ['DOUBLE', 'double', 'ZVAL_DOUBLE']);
			$lines[] = sprintf('%s %s_raw = 0;', $ctype, $name);
			$lines[] = sprintf('bool %s_isNull = true;', $name);
			$lines[] = sprintf('zval %s_zv;', $name);
			$parse[] = sprintf('Z_PARAM_%s_OR_NULL(%s_raw, %s_isNull)', $macro, $name, $name);
			$after[] = sprintf('if (%s != NULL || ZEND_NUM_ARGS() > %d) { if (%s_isNull) { ZVAL_NULL(&%s_zv); } else { %s(&%s_zv, %s_raw); } %s = &%s_zv; }', $name, $param->position, $name, $name, $set, $name, $name, $name, $name);
			return;
		}
		if ($declared->isFloat()->yes()) {
			$lines[] = sprintf('double %s_raw = 0;', $name);
			$lines[] = sprintf('zval %s_zv;', $name);
			$parse[] = sprintf('Z_PARAM_DOUBLE(%s_raw)', $name);
			$after[] = sprintf('if (ZEND_NUM_ARGS() > %d) { ZVAL_DOUBLE(&%s_zv, %s_raw); %s = &%s_zv; }', $param->position, $name, $name, $name, $name);
			return;
		}
		// any other union (string|int|null): taken as it is and checked the
		// strict way — PHPStan's own callers are strict_types files
		$check = $this->typeCheck($name, $param->declared, $param->describe);
		$parse[] = sprintf('Z_PARAM_ZVAL(%s)', $name);
		if ($check === null) {
			return;
		}

		$after[] = sprintf('if (%s != NULL && UNEXPECTED(!%s)) { zend_argument_type_error(%d, "must be of type %%s, %%s given", "%s", zend_zval_value_name(%s)); RETURN_THROWS(); }', $name, $check, $param->position + 1, addcslashes($param->describe, '\\"'), $name);
	}

	private function label(MethodInfo $method): string
	{
		return $method->unit->className . '::' . $method->name;
	}

	private function constantLong(Expr $default): string
	{
		$value = $this->constantValue($default);
		if (!is_int($value)) {
			throw new CompileError('int default expected');
		}
		return (string) $value;
	}

	private function constantBool(Expr $default): string
	{
		$value = $this->constantValue($default);
		if (!is_bool($value)) {
			throw new CompileError('bool default expected');
		}
		return $value ? 'true' : 'false';
	}

	private function constantZvalInit(string $cVar, Expr $default): string
	{
		if ($default instanceof Expr\Array_ && count($default->items) === 0) {
			return sprintf('ZVAL_EMPTY_ARRAY(&%s);', $cVar);
		}
		if ($default instanceof Expr\ConstFetch && $default->name->toString() === 'DIRECTORY_SEPARATOR') {
			// the platform's, not the compiling machine's
			return sprintf('ZVAL_CHAR(&%s, DEFAULT_SLASH);', $cVar);
		}
		$value = $this->constantValue($default);
		if ($value === null) {
			return sprintf('ZVAL_NULL(&%s);', $cVar);
		}
		if (is_int($value)) {
			return sprintf('ZVAL_LONG(&%s, %d);', $cVar, $value);
		}
		if (is_bool($value)) {
			return sprintf('ZVAL_BOOL(&%s, %s);', $cVar, $value ? 'true' : 'false');
		}
		if (is_string($value)) {
			return sprintf('ZVAL_COPY_VALUE(&%s, %s);', $cVar, $this->literal($value)->code);
		}
		if (is_float($value)) {
			return sprintf('ZVAL_DOUBLE(&%s, %s);', $cVar, var_export($value, true));
		}
		throw new CompileError('unsupported default value');
	}

	private function constantValue(Expr $expr): mixed
	{
		$type = $this->unit()->oracle->nativeType($expr);
		if ($expr instanceof Expr\ConstFetch) {
			$name = $expr->name->toLowerString();
			if ($name === 'null') {
				return null;
			}
			if ($name === 'true') {
				return true;
			}
			if ($name === 'false') {
				return false;
			}
		}
		if ($expr instanceof Node\Scalar\Int_ || $expr instanceof Node\Scalar\String_) {
			return $expr->value;
		}
		$values = $type->getConstantScalarValues();
		if (count($values) === 1) {
			return $values[0];
		}
		throw new CompileError(sprintf('not a constant expression on line %d', $expr->getStartLine()));
	}

	// ---------------------------------------------------------------- per method

	/** @phpstan-impure collects the file's literals, sites and closures */
	private function compileMethod(MethodInfo $method): string
	{
		$f = new FunctionContext($this->label($method), 'return false;', $method->isStatic ? null : 'self', $method->return);
		$this->declareLocals($f, $method->params, $method->node->stmts ?? []);
		$this->holderVariables = $this->closureHolderVariables($method->node->stmts ?? []);
		$f->stableVars = $this->neverWrittenVariables($method->node->stmts ?? [], array_map(static fn (ParamInfo $p): string => $p->name, $method->params));

		// promoted constructor parameters
		if (strtolower($method->name) === '__construct') {
			foreach ($method->params as $param) {
				if (!$param->promoted) {
					continue;
				}
				$value = $this->readLocal($f, $param->name);
				$owned = $this->own($f, $value);
				$slot = $this->slotExpr($this->unit()->className, $param->name, 'self') ?? throw new CompileError(sprintf('promoted $%s has no slot', $param->name));
				if ($param->promotedReadonly) {
					$f->line(sprintf('zval %s_raw = %s.take();', $owned, $owned));
					$f->failIf(sprintf('!pgrt::initReadonly(self, %s, "%s", &%s_raw)', $slot, $param->name, $owned));
				} else {
					$f->line(sprintf('zval %s_raw = %s.take();', $owned, $owned));
					$f->line(sprintf('pgrt::writeSlot(self, %s, &%s_raw);', $slot, $owned));
				}
			}
		}

		$this->statements($f, $method->node->stmts ?? []);
		if ($method->return->kind === 'void') {
			$f->line('return true;');
		} elseif (!$this->endsWithReturnOrThrow($method->node->stmts ?? [])) {
			// falling off the end of a non-void function: the engine's TypeError
			$f->line('zval none;');
			$f->line('ZVAL_NULL(&none);');
			$f->line(sprintf('pgrt::throwReturnType("%s", "%s", &none);', addcslashes($f->label, '\\'), addcslashes($method->return->describe, '\\')));
			$f->line('return false;');
		}

		return sprintf("/* %s() */\n%s\n{\n%s\n}", $this->label($method), $this->prototype($method), $f->render());
	}

	/** @param list<Stmt> $stmts */
	private function endsWithReturnOrThrow(array $stmts): bool
	{
		if ($stmts === []) {
			return false;
		}
		$last = $stmts[count($stmts) - 1];
		if ($last instanceof Stmt\Return_) {
			return true;
		}
		if ($last instanceof Stmt\Expression && $last->expr instanceof Expr\Throw_) {
			return true;
		}
		if ($last instanceof Stmt\If_ && $last->else !== null && $last->elseifs === []) {
			return $this->endsWithReturnOrThrow($last->stmts) && $this->endsWithReturnOrThrow($last->else->stmts);
		}
		return false;
	}

	/**
	 * Decides the C++ home of every local variable from the native types of
	 * everything assigned to it.
	 *
	 * @param list<ParamInfo> $params
	 * @param list<Stmt>|Expr $body
	 * @param array<string, LocalVariable> $captures a closure's captured variables
	 */
	private function declareLocals(FunctionContext $f, array $params, array|Expr $body, array $captures = []): void
	{
		$bodyNodes = $body instanceof Expr ? [$body] : $body;
		$assigned = [];
		$foreachOnly = [];
		$refs = [];
		$staticVars = [];
		foreach ($this->scopeNodes($bodyNodes) as $node) {
			if ($node instanceof Expr\Closure) {
				foreach ($node->uses as $use) {
					if (!$use->byRef || !is_string($use->var->name)) {
						continue;
					}

					$refs[$use->var->name] = true;
				}
				continue;
			}
			if ($node instanceof Expr\AssignRef) {
				if (!$node->var instanceof Expr\Variable || !is_string($node->var->name)) {
					throw new CompileError(sprintf('reference assignment to a non-variable (line %d)', $node->getStartLine()));
				}
				$refs[$node->var->name] = true;
				$assigned[$node->var->name][] = new MixedType();
				continue;
			}
			if ($node instanceof Expr\Assign || $node instanceof Expr\AssignOp) {
				$var = $node->var;
				if ($var instanceof Expr\Variable && is_string($var->name)) {
					$assigned[$var->name][] = $node instanceof Expr\Assign ? $this->unit()->oracle->nativeType($node->expr) : $this->unit()->oracle->nativeType($node);
				} elseif ($var instanceof Expr\ArrayDimFetch || $var instanceof Expr\PropertyFetch) {
					$root = $var;
					while ($root instanceof Expr\ArrayDimFetch) {
						$root = $root->var;
					}
					if ($var instanceof Expr\ArrayDimFetch && $root instanceof Expr\Variable && is_string($root->name) && $root->name !== 'this') {
						$assigned[$root->name][] = new ArrayType(new MixedType(), new MixedType());
					}
				} elseif ($var instanceof Expr\List_ || $var instanceof Expr\Array_) {
					foreach ($this->finder->findInstanceOf([$var], Expr\Variable::class) as $item) {
						if (!is_string($item->name)) {
							continue;
						}

						$assigned[$item->name][] = new MixedType();
					}
				}
			}
			if ($node instanceof Expr\CallLike && !$node->isFirstClassCallable()) {
				// a variable a by-reference parameter of the callee may create (preg_match()'s $matches)
				$callee = $this->calleeReflection($node);
				if ($callee !== null) {
					foreach ($node->getArgs() as $i => $arg) {
						if ($arg->name !== null || $arg->unpack || !$arg->value instanceof Expr\Variable || !is_string($arg->value->name)) {
							continue;
						}
						$parameter = $callee->getParameters()[$i] ?? null;
						if ($parameter === null || !$parameter->isPassedByReference()) {
							continue;
						}

						$assigned[$arg->value->name][] = new MixedType();
					}
				}
			}
			if ($node instanceof Stmt\Catch_ && $node->var !== null && is_string($node->var->name)) {
				$assigned[$node->var->name][] = TypeCombinator::union(...array_map(static fn (Node\Name $type): Type => new ObjectType($type->toString()), $node->types));
			}
			if ($node instanceof Stmt\Foreach_) {
				$iterable = $this->unit()->oracle->nativeType($node->expr);
				if ($node->keyVar !== null) {
					if (!$node->keyVar instanceof Expr\Variable || !is_string($node->keyVar->name)) {
						throw new CompileError('foreach needs a plain key variable');
					}
					$assigned[$node->keyVar->name][] = $iterable->isArray()->yes() ? $iterable->getIterableKeyType() : new MixedType();
				}
				if ($node->valueVar instanceof Expr\Variable && is_string($node->valueVar->name)) {
					if ($node->byRef) {
						$refs[$node->valueVar->name] = true;
						$assigned[$node->valueVar->name][] = new MixedType();
					} else {
						$foreachOnly[$node->valueVar->name] = true;
					}
				} elseif ($node->valueVar instanceof Expr\List_ || $node->valueVar instanceof Expr\Array_) {
					foreach ($this->finder->findInstanceOf([$node->valueVar], Expr\Variable::class) as $item) {
						if (!is_string($item->name)) {
							continue;
						}

						$assigned[$item->name][] = new MixedType();
					}
				}
			}
			if (!($node instanceof Stmt\Static_)) {
				continue;
			}

			foreach ($node->vars as $var) {
				if (!is_string($var->var->name) || $var->default === null) {
					throw new CompileError('static variables need a constant initializer');
				}
				$staticVars[$var->var->name] = $this->unit()->oracle->nativeType($var->default);
			}
		}

		foreach ($params as $param) {
			if ($param->variadic) {
				$f->locals[$param->name] = new LocalVariable('vector', 'p_' . $param->name, $param->class);
				if (isset($assigned[$param->name]) || isset($refs[$param->name])) {
					throw new CompileError('reassigning a variadic parameter is not supported');
				}
				continue;
			}
			$cName = 'l_' . $param->name;
			if ($param->byRef) {
				$f->locals[$param->name] = new LocalVariable('ref', 'p_' . $param->name);
				continue;
			}
			if (isset($refs[$param->name])) {
				if ($param->kind === 'callable') {
					throw new CompileError('binding a callable parameter by reference is not supported');
				}
				$f->line(sprintf('zv::Val %s = %s;', $cName, $this->paramAsVal($param)));
				$f->locals[$param->name] = new LocalVariable('ref', $cName . '.raw()');
				continue;
			}
			if (isset($assigned[$param->name]) || isset($foreachOnly[$param->name])) {
				// a reassigned parameter gets a local copy
				if ($param->kind === 'callable') {
					throw new CompileError('reassigning a callable parameter is not supported');
				}
				$kind = in_array($param->kind, ['long', 'bool'], true) && $this->sameScalarKind($param->kind, $assigned[$param->name] ?? []) ? $param->kind : 'zval';
				if ($kind === 'zval') {
					$f->line(sprintf('zv::Val %s = %s;', $cName, $this->paramAsVal($param)));
				} else {
					$f->line(sprintf('%s %s = p_%s;', $kind === 'long' ? 'zend_long' : 'bool', $cName, $param->name));
				}
				$f->locals[$param->name] = new LocalVariable($kind, $cName);
				continue;
			}
			switch ($param->kind) {
				case 'long':
				case 'bool':
					$f->locals[$param->name] = new LocalVariable($param->kind, 'p_' . $param->name);
					break;
				case 'callable':
					$f->locals[$param->name] = new LocalVariable('callable', 'p_' . $param->name);
					break;
				default:
					$f->locals[$param->name] = new LocalVariable('param', 'p_' . $param->name, $param->nullable ? null : $param->class, $param->isArray);
			}
		}

		foreach ($captures as $name => $capture) {
			if ($capture->kind === 'ref' || (!isset($assigned[$name]) && !isset($foreachOnly[$name]) && !isset($refs[$name]))) {
				$f->locals[$name] = $capture;
				continue;
			}
			// a by-value capture the body writes: its own copy, like the closure's static variable
			$f->line(sprintf('zv::Val l_%s = zv::Val::copyOf(zv::Ref(%s).deref());', $name, $capture->cName));
			$f->locals[$name] = isset($refs[$name]) ? new LocalVariable('ref', 'l_' . $name . '.raw()') : new LocalVariable('zval', 'l_' . $name);
		}

		foreach ($staticVars as $name => $type) {
			if (isset($assigned[$name])) {
				throw new CompileError(sprintf('static $%s is written to; only read-only constant statics are supported', $name));
			}
			if (!$type->isConstantArray()->yes()) {
				throw new CompileError(sprintf('static $%s is not a constant array', $name));
			}
			$f->locals[$name] = new LocalVariable('constarray', '', isArray: true, constant: $type);
		}

		foreach (array_keys($refs) as $name) {
			if (isset($f->locals[$name])) {
				continue;
			}
			$f->line(sprintf('zv::Val l_%s;', $name));
			$f->locals[$name] = new LocalVariable('ref', 'l_' . $name . '.raw()');
		}

		foreach (array_keys($foreachOnly) as $name) {
			// a parameter or capture got its copy above, an otherwise assigned variable is declared below
			if (isset($f->locals[$name]) || isset($assigned[$name])) {
				continue;
			}
			$f->line(sprintf('zval *l_%s = NULL;', $name));
			$f->locals[$name] = new LocalVariable('param', 'l_' . $name);
		}

		$lists = $this->nonEscapingLists($bodyNodes, array_keys($assigned));
		foreach ($assigned as $name => $types) {
			if (isset($f->locals[$name]) || $name === 'this') {
				continue;
			}
			if (isset($foreachOnly[$name])) {
				$types[] = new MixedType();
			}
			if (isset($lists[$name])) {
				$f->line(sprintf('pgrt::ValVec l_%s;', $name));
				$f->locals[$name] = new LocalVariable('list', 'l_' . $name);
				continue;
			}
			$union = TypeCombinator::union(...$types);
			if ($union->isInteger()->yes()) {
				$f->line(sprintf('zend_long l_%s = 0;', $name));
				$f->locals[$name] = new LocalVariable('long', 'l_' . $name);
			} elseif ($union->isBoolean()->yes()) {
				$f->line(sprintf('bool l_%s = false;', $name));
				$f->locals[$name] = new LocalVariable('bool', 'l_' . $name);
			} else {
				$f->line(sprintf('zv::Val l_%s;', $name));
				$f->locals[$name] = new LocalVariable('zval', 'l_' . $name, $this->knownClass($union), $union->isArray()->yes());
			}
		}

		if ($f->self === null) {
			return;
		}

		$f->line('zval this_zv;');
		$f->line(sprintf('ZVAL_OBJ(&this_zv, %s);', $f->self));
		$f->line('(void) this_zv;');
	}

	/** @param list<Type> $assignedTypes */
	private function sameScalarKind(string $kind, array $assignedTypes): bool
	{
		foreach ($assignedTypes as $type) {
			if ($kind === 'long' && !$type->isInteger()->yes()) {
				return false;
			}
			if ($kind === 'bool' && !$type->isBoolean()->yes()) {
				return false;
			}
		}
		return true;
	}

	/** an owned copy of a parameter as a zv::Val initializer */
	private function paramAsVal(ParamInfo $param): string
	{
		switch ($param->kind) {
			case 'long':
				return sprintf('zv::Val::integer(p_%s)', $param->name);
			case 'bool':
				return sprintf('zv::Val::boolean(p_%s)', $param->name);
			default:
				return sprintf('zv::Val::copyOf(zv::Ref(p_%s))', $param->name);
		}
	}

	/**
	 * Every node of a function body that belongs to its own scope: nested
	 * closures, arrow functions and classes are reported but not entered.
	 *
	 * @param list<Node> $nodes
	 * @return list<Node>
	 */
	private function scopeNodes(array $nodes): array
	{
		$out = [];
		$stack = array_reverse($nodes);
		while ($stack !== []) {
			$node = array_pop($stack);
			$out[] = $node;
			if ($node instanceof Expr\Closure || $node instanceof Expr\ArrowFunction || $node instanceof Stmt\Class_ || $node instanceof Expr\New_ && $node->class instanceof Stmt\Class_) {
				continue;
			}
			$children = [];
			foreach ($node->getSubNodeNames() as $name) {
				$sub = $node->$name;
				if ($sub instanceof Node) {
					$children[] = $sub;
				} elseif (is_array($sub)) {
					foreach ($sub as $item) {
						if (!($item instanceof Node)) {
							continue;
						}

						$children[] = $item;
					}
				}
			}
			foreach (array_reverse($children) as $child) {
				$stack[] = $child;
			}
		}
		return $out;
	}

	/**
	 * The locals used only as `$x = []`, `$x[] = ...` and `...$x`: lists no
	 * one else sees, which need no PHP array.
	 *
	 * @param list<Node> $body
	 * @param list<string> $candidates
	 * @return array<string, true>
	 */
	private function nonEscapingLists(array $body, array $candidates): array
	{
		$total = [];
		$accounted = [];
		$inClosure = [];
		foreach ($this->finder->find($body, static fn (Node $n): bool => $n instanceof Expr\ArrowFunction || $n instanceof Expr\Closure) as $closure) {
			foreach ($this->finder->findInstanceOf([$closure], Expr\Variable::class) as $var) {
				if (!is_string($var->name)) {
					continue;
				}

				$inClosure[$var->name] = true;
			}
		}
		foreach ($this->finder->find($body, static fn (Node $n): bool => true) as $node) {
			if ($node instanceof Expr\Variable && is_string($node->name)) {
				$total[$node->name] = ($total[$node->name] ?? 0) + 1;
			}
			if ($node instanceof Expr\Assign && $node->var instanceof Expr\Variable && is_string($node->var->name) && $node->expr instanceof Expr\Array_ && count($node->expr->items) === 0) {
				$accounted[$node->var->name] = ($accounted[$node->var->name] ?? 0) + 1;
			}
			if ($node instanceof Expr\Assign && $node->var instanceof Expr\ArrayDimFetch && $node->var->dim === null && $node->var->var instanceof Expr\Variable && is_string($node->var->var->name)) {
				$accounted[$node->var->var->name] = ($accounted[$node->var->var->name] ?? 0) + 1;
			}
			if (!($node instanceof Node\Arg) || !$node->unpack || !($node->value instanceof Expr\Variable) || !is_string($node->value->name)) {
				continue;
			}

			$accounted[$node->value->name] = ($accounted[$node->value->name] ?? 0) + 1;
		}
		$lists = [];
		foreach ($candidates as $name) {
			if (isset($inClosure[$name]) || !isset($total[$name]) || !(($accounted[$name] ?? 0) === $total[$name])) {
				continue;
			}

			$lists[$name] = true;
		}
		return $lists;
	}

	/**
	 * The class a receiver is proven to be: the narrowed native type of the
	 * expression when it is more specific than what the value carries (a
	 * `Node $node` parameter after `$node instanceof FuncCall`).
	 */
	private function receiverClass(Value $receiver, Type $native): ?string
	{
		$narrowed = $this->knownClass($native);
		if ($receiver->class === null) {
			return $narrowed;
		}
		if ($narrowed !== null && is_a($narrowed, $receiver->class, true)) {
			return $narrowed;
		}
		return $receiver->class;
	}

	private function knownClass(Type $type): ?string
	{
		if (!$type->isObject()->yes()) {
			return null;
		}
		$names = $type->getObjectClassNames();
		if (count($names) !== 1) {
			return null;
		}
		return $names[0];
	}

	// ---------------------------------------------------------------- statements

	/** @param list<Stmt> $stmts */
	private function statements(FunctionContext $f, array $stmts): void
	{
		foreach ($stmts as $stmt) {
			$this->statement($f, $stmt);
		}
	}

	private function statement(FunctionContext $f, Stmt $stmt): void
	{
		if ($stmt instanceof Stmt\Expression) {
			$f->open('');
			$this->expr($f, $stmt->expr);
			$f->close();
			return;
		}
		if ($stmt instanceof Stmt\Return_) {
			$this->returnStatement($f, $stmt->expr);
			return;
		}
		if ($stmt instanceof Stmt\If_) {
			$this->ifStatement($f, $stmt->cond, $stmt->stmts, $stmt->elseifs, $stmt->else);
			return;
		}
		if ($stmt instanceof Stmt\Foreach_) {
			$this->foreachStatement($f, $stmt);
			return;
		}
		if ($stmt instanceof Stmt\Static_) {
			return; // read-only constant statics are folded at their reads
		}
		if ($stmt instanceof Stmt\Continue_ || $stmt instanceof Stmt\Break_) {
			$levels = 1;
			if ($stmt->num !== null) {
				if (!$stmt->num instanceof Node\Scalar\Int_) {
					throw new CompileError('break/continue with a non-literal level');
				}
				$levels = $stmt->num->value;
			}
			$f->line($f->jump($stmt instanceof Stmt\Continue_, $levels));
			return;
		}
		if ($stmt instanceof Stmt\Nop) {
			return;
		}
		if ($stmt instanceof Stmt\While_) {
			$f->open('');
			$f->open('while (true)');
			$f->open('');
			$f->line(sprintf('if (!(%s)) break;', $this->condition($f, $stmt->cond)));
			$f->close();
			$this->loopBody($f, $stmt->stmts, null);
			return;
		}
		if ($stmt instanceof Stmt\Do_) {
			$f->open('');
			$f->open('while (true)');
			$this->loopBody($f, $stmt->stmts, function () use ($f, $stmt): void {
				$f->open('');
				$f->line(sprintf('if (!(%s)) break;', $this->condition($f, $stmt->cond)));
				$f->close();
			});
			return;
		}
		if ($stmt instanceof Stmt\For_) {
			$f->open('');
			foreach ($stmt->init as $init) {
				$f->open('');
				$this->expr($f, $init);
				$f->close();
			}
			$f->open('for (;;)');
			$f->open('');
			foreach ($stmt->cond as $i => $cond) {
				$c = $this->condition($f, $cond);
				if ($i !== count($stmt->cond) - 1) {
					continue;
				}
				$f->line(sprintf('if (!(%s)) break;', $c));
			}
			$f->close();
			$this->loopBody($f, $stmt->stmts, function () use ($f, $stmt): void {
				foreach ($stmt->loop as $loop) {
					$f->open('');
					$this->expr($f, $loop);
					$f->close();
				}
			});
			return;
		}
		if ($stmt instanceof Stmt\Switch_) {
			$this->switchStatement($f, $stmt);
			return;
		}
		if ($stmt instanceof Stmt\TryCatch) {
			$this->tryStatement($f, $stmt);
			return;
		}
		if ($stmt instanceof Stmt\Unset_) {
			foreach ($stmt->vars as $var) {
				$this->unsetTarget($f, $var);
			}
			return;
		}
		throw new CompileError(sprintf('unsupported statement %s on line %d', $stmt::class, $stmt->getStartLine()));
	}

	/**
	 * A loop's body inside an already opened C++ loop (and the block around
	 * it): `continue` jumps to the label before the step, `break` to the one
	 * after the loop. Closes both.
	 *
	 * @param list<Stmt> $stmts
	 * @param (callable(): void)|null $step
	 */
	private function loopBody(FunctionContext $f, array $stmts, ?callable $step): void
	{
		$f->pushLoop(false);
		$f->open('');
		$this->statements($f, $stmts);
		$f->close();
		$loop = $f->popLoop();
		if ($loop['continueUsed']) {
			$f->line($loop['continue'] . ':;');
		}
		if ($step !== null) {
			$step();
		}
		$f->close();
		if ($loop['breakUsed']) {
			$f->line($loop['break'] . ':;');
		}
		$f->close();
	}

	/**
	 * switch: the subject evaluated once, the matching case found by loose
	 * comparison in order, then a C++ switch over the case index keeps the
	 * fall-through and `break` semantics.
	 */
	private function switchStatement(FunctionContext $f, Stmt\Switch_ $stmt): void
	{
		$f->open('');
		$subject = $this->stabilize($f, $this->expr($f, $stmt->cond));
		$index = $f->temp('sw');
		$f->line(sprintf('int %s = -1;', $index));
		$default = null;
		foreach (array_values($stmt->cases) as $i => $case) {
			if ($case->cond === null) {
				$default = $i;
				continue;
			}
			$f->open(sprintf('if (%s == -1)', $index));
			$value = $this->expr($f, $case->cond);
			$f->line(sprintf('if (%s) %s = %d;', self::unparen($this->looseEquals($f, $subject, $value)), $index, $i));
			$f->close();
		}
		if ($default !== null) {
			$f->line(sprintf('if (%s == -1) %s = %d;', $index, $index, $default));
		}
		$f->pushLoop(true);
		$f->open(sprintf('switch (%s)', $index));
		foreach (array_values($stmt->cases) as $i => $case) {
			$f->line(sprintf('case %d: {', $i));
			$this->statements($f, $case->stmts);
			$f->line('}');
			$f->line('[[fallthrough]];');
		}
		$f->line('default: break;');
		$f->close();
		$loop = $f->popLoop();
		if ($loop['breakUsed']) {
			$f->line($loop['break'] . ':;');
		}
		$f->close();
	}

	/**
	 * try/catch: inside the try body a pending exception jumps to the
	 * handler, which takes it over if it is an instance of a caught class
	 * and propagates it otherwise.
	 */
	private function tryStatement(FunctionContext $f, Stmt\TryCatch $stmt): void
	{
		$returnThrough = null;
		if ($stmt->finally !== null) {
			$guarded = [...$stmt->stmts, ...array_merge(...array_map(static fn (Stmt\Catch_ $c): array => $c->stmts, $stmt->catches))];
			if ($this->escapingJumps($guarded, false) !== []) {
				throw new CompileError('break/continue out of a try with finally is not supported');
			}
			if ($this->escapingJumps($stmt->finally->stmts, true) !== []) {
				throw new CompileError('return/break/continue out of a finally block is not supported');
			}
			if ($this->escapingJumps($guarded, true) !== []) {
				if ($f->isClosure) {
					throw new CompileError('return out of a try with finally in a closure is not supported');
				}
				$returnThrough = ['label' => '', 'flag' => $f->temp('returning'), 'slot' => $f->temp('returned')];
			}
		}
		$outerFail = $f->fail;
		$finallyLabel = $stmt->finally !== null ? sprintf('finally_%s', $f->temp('f')) : null;
		$afterTry = $finallyLabel !== null ? sprintf('goto %s;', $finallyLabel) : $outerFail;
		$catchLabel = sprintf('catch_%s', $f->temp('c'));
		$f->open('');
		if ($returnThrough !== null) {
			// a return in the try or a catch runs the finally block first
			$returnThrough['label'] = $finallyLabel;
			$f->line(sprintf('bool %s = false;', $returnThrough['flag']));
			$f->line(sprintf('zv::Val %s;', $returnThrough['slot']));
			$f->finally[] = $returnThrough;
		}
		$f->fail = $stmt->catches !== [] ? sprintf('goto %s;', $catchLabel) : $afterTry;
		$f->open('');
		$this->statements($f, $stmt->stmts);
		$f->close();
		$f->fail = $afterTry;
		if ($stmt->catches !== []) {
			$f->line('if (false) {');
			$f->line(sprintf('%s:', $catchLabel));
			$f->open('');
			$f->line('zend_object *thrown = EG(exception);');
			$caught = $f->temp('caught');
			$f->line(sprintf('bool %s = false;', $caught));
			foreach ($stmt->catches as $catch) {
				$checks = [];
				foreach ($catch->types as $type) {
					$checks[] = $this->catchCheck(ltrim($type->toString(), '\\'));
				}
				$f->open(sprintf('if (!%s && (%s))', $caught, implode(' || ', $checks)));
				$f->line(sprintf('%s = true;', $caught));
				$f->line('zval exception;');
				$f->line('ZVAL_OBJ_COPY(&exception, thrown);');
				$f->line('zend_clear_exception();');
				if ($catch->var !== null) {
					$this->assignTo($f, $catch->var, '&exception');
				}
				$f->line('zval_ptr_dtor(&exception);');
				$this->statements($f, $catch->stmts);
				$f->close();
			}
			$f->line(sprintf('if (!%s) %s', $caught, $afterTry));
			$f->close();
			$f->line('}');
		}
		$f->fail = $outerFail;
		if ($returnThrough !== null) {
			array_pop($f->finally);
		}
		if ($finallyLabel !== null) {
			// reached normally, after a handled exception, or with one pending:
			// the block runs with it set aside, then it continues (chained to
			// one the block throws itself, as the engine does)
			$f->line(sprintf('%s:;', $finallyLabel));
			$f->line('pt_finally([&]() {');
			$f->fail = 'return;';
			$f->open('');
			$this->statements($f, $stmt->finally->stmts);
			$f->close();
			$f->line('});');
			$f->fail = $outerFail;
			$f->failIf('EG(exception)');
			if ($returnThrough !== null) {
				$f->open(sprintf('if (%s)', $returnThrough['flag']));
				$this->completeReturn($f, $returnThrough['slot']);
				$f->close();
			}
		}
		$f->close();
	}

	/**
	 * The end of a return whose value (a zval one in $slot) is set: through
	 * the next enclosing finally block, or out of the function.
	 */
	private function completeReturn(FunctionContext $f, ?string $slot): void
	{
		$outer = $f->finally[count($f->finally) - 1] ?? null;
		if ($outer !== null) {
			if ($slot !== null) {
				$f->line(sprintf('%s = std::move(%s);', $outer['slot'], $slot));
			}
			$f->line(sprintf('%s = true;', $outer['flag']));
			$f->line(sprintf('goto %s;', $outer['label']));
			return;
		}
		if ($slot !== null && $f->return?->kind === 'zval') {
			$f->line(sprintf('*ret = %s.take();', $slot));
		}
		$f->line('return true;');
	}

	/**
	 * The return statements (with $returns) or the break/continue statements
	 * that leave these statements: the ones of a nested function or
	 * closure, and the jumps a nested loop or switch takes, stay inside.
	 *
	 * @param array<mixed> $stmts nodes, or the sub-node values of one
	 * @return list<Stmt>
	 */
	private function escapingJumps(array $stmts, bool $returns, int $depth = 0): array
	{
		$found = [];
		foreach ($stmts as $node) {
			if (!$node instanceof Node) {
				continue;
			}
			if ($node instanceof Expr\Closure || $node instanceof Expr\ArrowFunction || $node instanceof Stmt\Function_ || $node instanceof Stmt\Class_ || $node instanceof Expr\New_ && $node->class instanceof Stmt\Class_) {
				continue;
			}
			if ($returns && $node instanceof Stmt\Return_) {
				$found[] = $node;
			}
			if (!$returns && ($node instanceof Stmt\Break_ || $node instanceof Stmt\Continue_)) {
				$levels = $node->num instanceof Node\Scalar\Int_ ? $node->num->value : 1;
				if ($levels > $depth) {
					$found[] = $node;
				}
			}
			$loop = $node instanceof Stmt\For_ || $node instanceof Stmt\Foreach_ || $node instanceof Stmt\While_ || $node instanceof Stmt\Do_ || $node instanceof Stmt\Switch_;
			foreach ($node->getSubNodeNames() as $name) {
				$sub = $node->$name;
				$found = [...$found, ...$this->escapingJumps(is_array($sub) ? $sub : [$sub], $returns, $depth + ($loop ? 1 : 0))];
			}
		}
		return $found;
	}

	private function catchCheck(string $class): string
	{
		switch (strtolower($class)) {
			case 'throwable':
				return 'instanceof_function(thrown->ce, zend_ce_throwable)';
			case 'exception':
				return 'instanceof_function(thrown->ce, zend_ce_exception)';
			case 'error':
				return 'instanceof_function(thrown->ce, zend_ce_error)';
		}
		$site = $this->site('pgrt::ClassSite', 'cls_' . $class);
		// catch never autoloads: an unloaded class catches nothing
		return sprintf('pgrt::objectInstanceOf(thrown, pgrt::lookupClassQuiet(%s, "%s", %d))', $site, addcslashes($class, '\\'), strlen($class));
	}

	private function unsetTarget(FunctionContext $f, Expr $var): void
	{
		if ($var instanceof Expr\Variable && is_string($var->name)) {
			$local = $f->locals[$var->name] ?? null;
			if ($local === null || !in_array($local->kind, ['zval', 'ref'], true)) {
				throw new CompileError(sprintf('unset($%s) of a variable without a zval home', $var->name));
			}
			$f->line(sprintf('%s.release();', $local->kind === 'zval' ? $local->cName : $this->refStorage($local)));
			return;
		}
		if ($var instanceof Expr\ArrayDimFetch && $var->dim !== null) {
			$f->open('');
			$key = $this->stabilize($f, $this->expr($f, $var->dim));
			$container = $this->writablePtr($f, $var->var, false);
			$f->open(sprintf('if (Z_TYPE_P(%s) == IS_ARRAY)', $container));
			$f->line(sprintf('SEPARATE_ARRAY(%s);', $container));
			if ($key->kind === 'long') {
				$f->line(sprintf('zend_hash_index_del(Z_ARRVAL_P(%s), %s);', $container, $key->code));
			} else {
				$k = $this->asZval($f, $key);
				$f->line(sprintf('if (Z_TYPE_P(%s) == IS_STRING) { zend_symtable_del(Z_ARRVAL_P(%s), Z_STR_P(%s)); } else if (Z_TYPE_P(%s) == IS_LONG) { zend_hash_index_del(Z_ARRVAL_P(%s), Z_LVAL_P(%s)); } else { zend_throw_error(NULL, "phpstan_turbo codegen: unset() with this offset type is not compiled"); %s }', $k, $container, $k, $k, $container, $k, $f->fail));
			}
			$f->close();
			$f->close();
			return;
		}
		if ($var instanceof Expr\PropertyFetch && $var->name instanceof Node\Identifier) {
			// through the object handlers, with the class as calling scope
			$object = $this->asZval($f, $this->stabilize($f, $this->expr($f, $var->var)));
			$f->failIf(sprintf('!pgrt::unsetProperty(%s, %s, %s)', $object, $this->unit()->ce, $this->literal($var->name->toString())->code));
			return;
		}
		throw new CompileError('unset() of this target is not supported');
	}

	private function returnStatement(FunctionContext $f, ?Expr $expr): void
	{
		$return = $f->return;
		if ($return === null) {
			throw new CompileError('return outside a function');
		}
		$f->open('');
		$through = $f->finally[count($f->finally) - 1] ?? null;
		if ($expr === null) {
			if ($through !== null) {
				$f->line(sprintf('%s = true;', $through['flag']));
				$f->line(sprintf('goto %s;', $through['label']));
			} else {
				$f->line($f->isClosure ? 'return;' : 'return true;');
			}
			$f->close();
			return;
		}
		$value = $this->expr($f, $expr);
		$proven = $return->declared->isSuperTypeOf($this->unit()->oracle->nativeType($expr))->yes();
		if ($f->isClosure) {
			$z = $this->asZval($f, $value);
			if (!$proven) {
				$this->emitTypeCheck($f, $z, $return->describe, $return->declared, sprintf('pgrt::throwReturnType("%s", "%s", %s); return;', addcslashes($f->label, '\\'), addcslashes($return->describe, '\\'), $z));
			}
			if ($value->temp !== null) {
				$f->line(sprintf('*return_value = %s.take();', $value->temp));
			} else {
				$f->line(sprintf('ZVAL_COPY(return_value, %s);', $z));
			}
			$f->line('return;');
			$f->close();
			return;
		}
		switch ($return->kind) {
			case 'long':
				$f->line(sprintf('*ret = %s;', $this->asLong($f, $value, $return->describe, $f->label)));
				break;
			case 'bool':
				$f->line(sprintf('*ret = %s;', $this->asBool($value)));
				break;
			case 'zval':
				$z = $this->asZval($f, $value);
				if (!$proven) {
					$this->emitTypeCheck($f, $z, $return->describe, $return->declared, sprintf('pgrt::throwReturnType("%s", "%s", %s); return false;', addcslashes($f->label, '\\'), addcslashes($return->describe, '\\'), $z));
				}
				if ($through !== null) {
					// held until the finally blocks ran
					if ($value->temp !== null) {
						$f->line(sprintf('%s = std::move(%s);', $through['slot'], $value->temp));
					} elseif ($value->kind === 'zval') {
						$f->line(sprintf('%s = zv::Val::copyOf(zv::Ref(%s));', $through['slot'], $z));
					} else {
						$f->line(sprintf('{ zval c; ZVAL_COPY_VALUE(&c, %s); %s = zv::Val::adopt(c); }', $z, $through['slot']));
					}
				} elseif ($value->temp !== null) {
					$f->line(sprintf('*ret = %s.take();', $value->temp));
				} elseif ($value->kind === 'zval') {
					$f->line(sprintf('ZVAL_COPY(ret, %s);', $z));
				} else {
					$f->line(sprintf('ZVAL_COPY_VALUE(ret, %s);', $z));
				}
				break;
			default:
				throw new CompileError('return with a value from a void function');
		}
		if ($through !== null) {
			$f->line(sprintf('%s = true;', $through['flag']));
			$f->line(sprintf('goto %s;', $through['label']));
		} else {
			$f->line('return true;');
		}
		$f->close();
	}

	/**
	 * @param list<Stmt> $stmts
	 * @param array<Stmt\ElseIf_> $elseifs
	 */
	private function ifStatement(FunctionContext $f, Expr $cond, array $stmts, array $elseifs, ?Stmt\Else_ $else): void
	{
		$f->open('');
		$c = $this->condition($f, $cond);
		$f->open(sprintf('if (%s)', self::unparen($c)));
		$this->statements($f, $stmts);
		if ($elseifs === [] && $else === null) {
			$f->close();
			$f->close();
			return;
		}
		$f->close(' else {');
		$f->open('');
		if ($elseifs !== []) {
			$first = array_values($elseifs)[0];
			$this->ifStatement($f, $first->cond, $first->stmts, array_slice(array_values($elseifs), 1), $else);
		} elseif ($else !== null) {
			$this->statements($f, $else->stmts);
		}
		$f->close();
		$f->line('}');
		$f->close();
	}

	/**
	 * foreach over a value not proven to be an array, through
	 * pgrt::ForeachIter (an array by position, a Traversable through the
	 * engine's iterator); `continue` lands before the iterator advances.
	 * The caller's block is open and closed by the caller.
	 */
	private function iteratorForeach(FunctionContext $f, Stmt\Foreach_ $stmt, string $subject): void
	{
		$it = $f->temp('it');
		$f->line(sprintf('pgrt::ForeachIter %s;', $it));
		$f->failIf(sprintf('!%s.begin(%s)', $it, $subject));
		$f->open(sprintf('while (%s.valid())', $it));
		if ($stmt->keyVar !== null) {
			$key = $f->temp('k');
			$f->line(sprintf('zv::Val %s;', $key));
			$f->line(sprintf('%s.key(%s.raw());', $it, $key));
			$this->assignTo($f, $stmt->keyVar, $key . '.raw()', true);
		}
		$this->assignTo($f, $stmt->valueVar, sprintf('zv::Ref(%s.current()).deref().raw()', $it), true);
		$f->pushLoop(false);
		$f->open('');
		$this->statements($f, $stmt->stmts);
		$f->close();
		$loop = $f->popLoop();
		if ($loop['continueUsed']) {
			$f->line($loop['continue'] . ':;');
		}
		$f->failIf(sprintf('!%s.next()', $it));
		$f->close();
		if ($loop['breakUsed']) {
			$f->line($loop['break'] . ':;');
		}
		$f->failIf('EG(exception)');
		$f->close();
	}

	private function foreachStatement(FunctionContext $f, Stmt\Foreach_ $stmt): void
	{
		$f->open('');
		$source = $stmt->expr;
		if (!$stmt->byRef && $source instanceof Expr\Variable && is_string($source->name) && ($f->locals[$source->name] ?? null)?->kind === 'vector') {
			$vector = $f->locals[$source->name];
			$i = $f->temp('i');
			$f->open(sprintf('for (uint32_t %s = 0; %s < %s_count; %s++)', $i, $i, $vector->cName, $i));
			if ($stmt->keyVar !== null) {
				$key = $f->temp('k');
				$f->line(sprintf('zval %s;', $key));
				$f->line(sprintf('ZVAL_LONG(&%s, (zend_long) %s);', $key, $i));
				$this->assignTo($f, $stmt->keyVar, '&' . $key, true);
			}
			$this->assignTo($f, $stmt->valueVar, sprintf('zv::Ref(&%s[%s]).deref().raw()', $vector->cName, $i), true);
			$this->loopBody($f, $stmt->stmts, null);
			return;
		}

		$ht = $f->temp('ht');
		if ($stmt->byRef) {
			// over the variable's own array, separated first: each element
			// becomes a reference the value variable is bound to
			if (!$source instanceof Expr\Variable || !is_string($source->name) || !in_array(($f->locals[$source->name] ?? null)?->kind, ['zval', 'ref'], true)) {
				throw new CompileError('foreach by reference over anything but a local array is not supported');
			}
			$container = $f->locals[$source->name];
			$arr = $f->temp('a');
			$f->line(sprintf('zval *%s = zv::Ref(%s).deref().raw();', $arr, $container->kind === 'zval' ? $container->cName . '.raw()' : $container->cName));
			$f->open(sprintf('if (UNEXPECTED(Z_TYPE_P(%s) != IS_ARRAY))', $arr));
			$f->line(sprintf('zend_error(E_WARNING, "foreach() argument must be of type array|object, %%s given", zend_zval_value_name(%s));', $arr));
			$f->failIf('EG(exception)');
			$f->close(' else {');
			$f->open('');
			$f->line(sprintf('SEPARATE_ARRAY(%s);', $arr));
			$f->line(sprintf('HashTable *%s = Z_ARRVAL_P(%s);', $ht, $arr));
		} else {
			$array = $this->expr($f, $source);
			if ($array->kind !== 'zval') {
				throw new CompileError('foreach over a non-array');
			}
			if (!$array->isArray && !$this->unit()->oracle->nativeType($source)->isArray()->yes()) {
				// not proven an array: an array, a Traversable or the VM's warning
				$this->iteratorForeach($f, $stmt, $this->asZval($f, $array));
				return;
			}
			$held = $f->temp('a');
			$f->line(sprintf('zv::Val %s = zv::Val::copyOf(zv::Ref(%s).deref());', $held, $array->code));
			$f->line(sprintf('HashTable *%s = Z_ARRVAL_P(%s.raw());', $ht, $held));
		}
		$v = $f->temp('v');
		$num = $f->temp('n');
		$str = $f->temp('s');
		$f->line(sprintf('zval *%s;', $v));
		$f->line(sprintf('zend_ulong %s;', $num));
		$f->line(sprintf('zend_string *%s;', $str));
		$f->open(sprintf('ZEND_HASH_FOREACH_KEY_VAL(%s, %s, %s, %s)', $ht, $num, $str, $v));
		$f->line(sprintf('(void) %s; (void) %s;', $num, $str));
		if ($stmt->keyVar !== null) {
			$key = $f->temp('k');
			$f->line(sprintf('zval %s;', $key));
			$f->line(sprintf('if (%s != NULL) { ZVAL_STR(&%s, %s); } else { ZVAL_LONG(&%s, (zend_long) %s); }', $str, $key, $str, $key, $num));
			$this->assignTo($f, $stmt->keyVar, '&' . $key, true);
		}
		if ($stmt->byRef) {
			if (!$stmt->valueVar instanceof Expr\Variable || !is_string($stmt->valueVar->name) || ($f->locals[$stmt->valueVar->name] ?? null)?->kind !== 'ref') {
				throw new CompileError('foreach by reference needs a plain variable');
			}
			$f->line(sprintf('ZVAL_MAKE_REF(%s);', $v));
			$f->line(sprintf('%s = zv::Val::copyOf(zv::Ref(%s));', $this->refStorage($f->locals[$stmt->valueVar->name]), $v));
		} else {
			$this->assignTo($f, $stmt->valueVar, sprintf('zv::Ref(%s).deref().raw()', $v), true);
		}
		$f->pushLoop(false);
		$f->open('');
		$this->statements($f, $stmt->stmts);
		$f->close();
		$loop = $f->popLoop();
		if ($loop['continueUsed']) {
			$f->line($loop['continue'] . ':;');
		}
		$f->close();
		$f->line('ZEND_HASH_FOREACH_END();');
		if ($loop['breakUsed']) {
			$f->line($loop['break'] . ':;');
		}
		if ($stmt->byRef) {
			$f->close();
			$f->line('}');
		}
		$f->close();
	}

	/** the zv::Val a `ref` local of this function is stored in */
	private function refStorage(LocalVariable $local): string
	{
		if (!str_ends_with($local->cName, '.raw()')) {
			throw new CompileError('rebinding a captured reference is not supported');
		}
		return substr($local->cName, 0, -strlen('.raw()'));
	}

	/**
	 * Stores a value (a borrowed `zval *`) into an assignment target: a local
	 * of any home, or a list()/[] destructuring of it. $bind lets a variable
	 * only ever bound by foreach borrow the element instead of copying it.
	 */
	private function assignTo(FunctionContext $f, Expr $target, string $zvalPtr, bool $bind = false): void
	{
		if ($target instanceof Expr\Variable && is_string($target->name)) {
			$local = $f->locals[$target->name] ?? null;
			if ($local === null) {
				throw new CompileError(sprintf('$%s has no home', $target->name));
			}
			switch ($local->kind) {
				case 'param':
					if (!$bind) {
						throw new CompileError(sprintf('$%s is not assignable', $target->name));
					}
					$f->line(sprintf('%s = %s;', $local->cName, $zvalPtr));
					return;
				case 'zval':
					$f->line(sprintf('%s = zv::Val::copyOf(zv::Ref(%s));', $local->cName, $zvalPtr));
					return;
				case 'ref':
					$f->line(sprintf('zv::Ref(zv::Ref(%s).deref().raw()).assign(zv::Val::copyOf(zv::Ref(%s)));', $local->cName, $zvalPtr));
					return;
				case 'long':
					$f->line(sprintf('%s = %s;', $local->cName, $this->asLong($f, new Value('zval', $zvalPtr), 'int', $f->label)));
					return;
				case 'bool':
					$f->line(sprintf('if (UNEXPECTED(Z_TYPE_P(%s) != IS_TRUE && Z_TYPE_P(%s) != IS_FALSE)) { zend_type_error("%s: bool expected, %%s given", zend_zval_value_name(%s)); %s }', $zvalPtr, $zvalPtr, addcslashes($f->label, '\\'), $zvalPtr, $f->fail));
					$f->line(sprintf('%s = Z_TYPE_P(%s) == IS_TRUE;', $local->cName, $zvalPtr));
					return;
			}
			throw new CompileError(sprintf('$%s cannot be assigned', $target->name));
		}
		if ($target instanceof Expr\List_ || $target instanceof Expr\Array_) {
			// list(...) = $array: each item from its key or position; a
			// missing key warns and reads null, a non-array reads null throughout
			$arr = $f->temp('la');
			$f->line(sprintf('zval *%s = %s;', $arr, $zvalPtr));
			$position = 0;
			foreach ($target->items as $item) {
				if ($item === null) {
					$position++;
					continue;
				}
				if ($item->byRef || $item->unpack) {
					throw new CompileError('by-reference list() items are not supported');
				}
				$e = $f->temp('le');
				$f->line(sprintf('zval *%s = NULL;', $e));
				if ($item->key === null) {
					$f->line(sprintf('if (Z_TYPE_P(%s) == IS_ARRAY) { %s = zend_hash_index_find(Z_ARRVAL_P(%s), %d); if (%s == NULL) { pgrt::warnUndefinedIndex(%d); } }', $arr, $e, $arr, $position, $e, $position));
					$position++;
				} else {
					$key = $this->expr($f, $item->key);
					if ($key->kind === 'long') {
						$f->line(sprintf('if (Z_TYPE_P(%s) == IS_ARRAY) { %s = zend_hash_index_find(Z_ARRVAL_P(%s), %s); if (%s == NULL) { pgrt::warnUndefinedIndex(%s); } }', $arr, $e, $arr, $key->code, $e, $key->code));
					} else {
						$k = $this->asZval($f, $key);
						$f->line(sprintf('if (Z_TYPE_P(%s) == IS_ARRAY && Z_TYPE_P(%s) == IS_STRING) { %s = zend_symtable_find(Z_ARRVAL_P(%s), Z_STR_P(%s)); if (%s == NULL) { zend_error(E_WARNING, "Undefined array key \\"%%s\\"", Z_STRVAL_P(%s)); } }', $arr, $k, $e, $arr, $k, $e, $k));
					}
				}
				$f->failIf('EG(exception)');
				$f->line(sprintf('if (%s == NULL) %s = &EG(uninitialized_zval);', $e, $e));
				$this->assignTo($f, $item->value, sprintf('zv::Ref(%s).deref().raw()', $e));
			}
			return;
		}
		throw new CompileError(sprintf('unsupported assignment target %s', $target::class));
	}

	// ---------------------------------------------------------------- expressions

	private function condition(FunctionContext $f, Expr $expr): string
	{
		return $this->asBool($this->expr($f, $expr));
	}

	private function expr(FunctionContext $f, Expr $expr): Value
	{
		$oracle = $this->unit()->oracle;
		$native = $oracle->nativeType($expr);

		if ($expr instanceof Expr\ErrorSuppress) {
			// evaluated, and owned, before error_reporting is restored
			$silence = $f->temp('silence');
			$f->line(sprintf('pgrt::Silence %s;', $silence));
			$inner = $this->expr($f, $expr->expr);
			if ($inner->kind === 'void') {
				$f->line(sprintf('%s.end();', $silence));
				return $inner;
			}
			$t = $this->own($f, $inner);
			$f->line(sprintf('%s.end();', $silence));
			return new Value('zval', $t . '.raw()', $t, $inner->class, $inner->isArray);
		}

		if ($expr instanceof Expr\Variable) {
			if (!is_string($expr->name)) {
				throw new CompileError('variable variables are not supported');
			}
			if ($expr->name === 'this') {
				return new Value('zval', '&this_zv', null, $this->unit()->className);
			}
			return $this->readLocal($f, $expr->name);
		}

		if ($expr instanceof Node\Scalar\Int_) {
			return Value::long((string) $expr->value);
		}
		if ($expr instanceof Node\Scalar\String_) {
			return $this->literal($expr->value);
		}
		if ($expr instanceof Expr\ConstFetch) {
			$name = $expr->name->toLowerString();
			if ($name === 'null') {
				return Value::null();
			}
			if (in_array($name, ['true', 'false'], true)) {
				return Value::bool($name);
			}
			$values = $native->getConstantScalarValues();
			if (count($values) === 1 && (is_int($values[0]) || is_string($values[0]) || is_bool($values[0]))) {
				return is_int($values[0]) ? Value::long((string) $values[0]) : (is_bool($values[0]) ? Value::bool($values[0] ? 'true' : 'false') : $this->literal($values[0]));
			}
			// a constant whose value is not known statically
			$t = $f->temp('cn');
			$constant = ltrim($expr->name->toString(), '\\');
			$f->line(sprintf('zval *%s = zend_get_constant_str("%s", %d);', $t, addcslashes($constant, '\\\\'), strlen($constant)));
			$f->line(sprintf('if (UNEXPECTED(%s == NULL)) { zend_throw_error(NULL, "Undefined constant \\"%%s\\"", "%s"); %s }', $t, addcslashes($constant, '\\\\'), $f->fail));
			return $this->narrowZval($t, '', $native);
		}
		if ($expr instanceof Expr\ClassConstFetch && $expr->name instanceof Node\Identifier && $expr->name->toLowerString() === 'class') {
			if ($expr->class instanceof Node\Name && $expr->class->toLowerString() !== 'static') {
				return $this->literal($this->className($expr->class));
			}
			if ($expr->class instanceof Node\Name && $f->self === null) {
				// static::class in a static method: the called scope of its
				// frame (the method is only entered through the engine, see
				// usesCalledScope())
				if ($this->unit()->reflection->isFinal()) {
					return $this->literal($this->unit()->className);
				}
				$t = $f->temp('cls');
				$f->line(sprintf('zend_class_entry *%s_ce = zend_get_called_scope(EG(current_execute_data));', $t));
				$f->line(sprintf('zv::Val %s = zv::Val::string((%s_ce != NULL ? %s_ce : %s)->name);', $t, $t, $t, $this->unit()->ce));
				return new Value('zval', $t . '.raw()', $t);
			}
			$object = $expr->class instanceof Node\Name ? new Value('zval', '&this_zv') : $this->expr($f, $expr->class);
			$t = $f->temp('cls');
			$z = $this->asZval($f, $object);
			$f->line(sprintf('if (UNEXPECTED(Z_TYPE_P(%s) != IS_OBJECT)) { zend_type_error("Cannot use \\"::class\\" on %%s", zend_zval_value_name(%s)); %s }', $z, $z, $f->fail));
			$f->line(sprintf('zv::Val %s = zv::Val::string(Z_OBJCE_P(%s)->name);', $t, $z));
			return new Value('zval', $t . '.raw()', $t);
		}
		if ($expr instanceof Expr\ClassConstFetch && $native->getConstantScalarValues() === []) {
			// an array, enum case or otherwise non-scalar constant: the engine's own value
			if (!$expr->class instanceof Node\Name || !$expr->name instanceof Node\Identifier) {
				throw new CompileError('dynamic class constant fetch');
			}
			$class = $this->className($expr->class);
			$ce = $this->classEntry($class, $f);
			$t = $f->temp('cc');
			$f->line(sprintf('zval *%s = pgrt::classConstant(%s, %s, %s);', $t, $ce, $this->literal($expr->name->toString())->code, $this->unit()->ce));
			$f->failIf(sprintf('%s == NULL', $t));
			return $this->narrowZval($t, '', $native);
		}
		if ($expr instanceof Expr\ClassConstFetch) {
			$value = $this->constantValue($expr);
			if (is_int($value)) {
				return Value::long((string) $value);
			}
			if (is_string($value)) {
				return $this->literal($value);
			}
			if (is_bool($value)) {
				return Value::bool($value ? 'true' : 'false');
			}
			if ($value === null) {
				return Value::null();
			}
			throw new CompileError('only int/string/bool/null class constants are supported');
		}
		if ($expr instanceof Expr\Array_) {
			return $this->arrayLiteral($f, $expr);
		}
		if ($expr instanceof Expr\PropertyFetch) {
			return $this->propertyFetch($f, $expr);
		}
		if ($expr instanceof Expr\StaticPropertyFetch) {
			$slot = $this->staticPropertySlot($f, $expr);
			$f->failIf(sprintf('Z_TYPE_P(%s) == IS_UNDEF && (zend_throw_error(NULL, "Typed static property %%s::$%%s must not be accessed before initialization", "%s", "%s"), true)', $slot, addcslashes($this->className($expr->class), '\\'), $expr->name instanceof Node\VarLikeIdentifier ? $expr->name->toString() : ''));
			return new Value('zval', $slot, class: $this->knownClass($native), isArray: $native->isArray()->yes());
		}
		if ($expr instanceof Expr\ArrayDimFetch) {
			return $this->arrayDimFetch($f, $expr);
		}
		if ($expr instanceof Expr\Assign) {
			return $this->assign($f, $expr);
		}
		if ($expr instanceof Expr\AssignOp\Coalesce) {
			return $this->coalesceAssign($f, $expr);
		}
		if (($expr instanceof Expr\AssignOp\BitwiseAnd || $expr instanceof Expr\AssignOp\BitwiseOr) && $expr->var instanceof Expr\Variable && is_string($expr->var->name) && ($f->locals[$expr->var->name] ?? null)?->kind === 'long') {
			return $this->bitwiseAssign($f, $expr);
		}
		if ($expr instanceof Expr\AssignOp) {
			return $this->compoundAssign($f, $expr);
		}
		if ($expr instanceof Expr\BinaryOp\Coalesce) {
			$ptr = $this->issetPtr($f, $expr->left);
			$t = $f->temp('q');
			$f->line(sprintf('zv::Val %s;', $t));
			$f->open(sprintf('if (%s)', $this->setCheck($ptr)));
			$f->line(sprintf('%s = zv::Val::copyOf(zv::Ref(%s));', $t, $ptr));
			$f->close(' else {');
			$f->open('');
			$f->line(sprintf('%s = std::move(%s);', $t, $this->own($f, $this->expr($f, $expr->right))));
			$f->close();
			$f->line('}');
			return $this->narrowZval($t . '.raw()', $t, $native);
		}
		if ($expr instanceof Expr\BinaryOp) {
			return $this->binaryOp($f, $expr);
		}
		if ($expr instanceof Expr\BooleanNot) {
			return Value::bool('!(' . $this->condition($f, $expr->expr) . ')');
		}
		if ($expr instanceof Expr\Ternary) {
			return $this->ternary($f, $expr);
		}
		if ($expr instanceof Expr\New_) {
			return $this->newExpr($f, $expr);
		}
		if ($expr instanceof Expr\MethodCall) {
			return $this->methodCall($f, $expr);
		}
		if ($expr instanceof Expr\StaticCall) {
			return $this->staticCall($f, $expr);
		}
		if ($expr instanceof Expr\FuncCall) {
			return $this->funcCall($f, $expr);
		}
		if ($expr instanceof Expr\ArrowFunction || $expr instanceof Expr\Closure) {
			return $this->closureExpr($f, $expr);
		}
		if ($expr instanceof Expr\Throw_) {
			$exception = $this->expr($f, $expr->expr);
			$owned = $this->own($f, $exception);
			$f->line(sprintf('zend_throw_exception_object(%s.raw()); ZVAL_UNDEF(%s.raw());', $owned, $owned));
			$f->line($f->fail);
			return Value::void();
		}
		if ($expr instanceof Expr\Clone_) {
			$value = $this->expr($f, $expr->expr);
			$t = $f->temp('cl');
			$f->line(sprintf('zv::Val %s;', $t));
			$f->failIf(sprintf('!pgrt::cloneObject(%s, %s.raw())', $this->asZval($f, $value), $t));
			return new Value('zval', $t . '.raw()', $t, $value->class ?? $this->knownClass($native));
		}
		if ($expr instanceof Expr\Isset_) {
			$checks = [];
			foreach ($expr->vars as $var) {
				$ptr = $this->issetPtr($f, $var);
				$checks[] = sprintf('(%s)', $this->setCheck($ptr));
			}
			return Value::bool(implode(' && ', $checks));
		}
		if ($expr instanceof Expr\Empty_) {
			$ptr = $this->issetPtr($f, $expr->expr);
			return Value::bool(sprintf('(%s == NULL || !zend_is_true(%s))', $ptr, $ptr));
		}
		if ($expr instanceof Expr\PreInc || $expr instanceof Expr\PreDec || $expr instanceof Expr\PostInc || $expr instanceof Expr\PostDec) {
			return $this->incDec($f, $expr);
		}
		if ($expr instanceof Expr\Cast) {
			return $this->cast($f, $expr);
		}
		if ($expr instanceof Node\Scalar\InterpolatedString) {
			$parts = [];
			foreach ($expr->parts as $part) {
				if ($part instanceof Node\InterpolatedStringPart) {
					$parts[] = $this->literal($part->value);
				} else {
					$parts[] = $this->stabilize($f, $this->expr($f, $part));
				}
			}
			$t = $f->temp('str');
			$f->line(sprintf('pgrt::StringBuilder %s_b;', $t));
			foreach ($parts as $part) {
				$f->failIf(sprintf('!%s_b.append(%s)', $t, $this->asZval($f, $part)));
			}
			$f->line(sprintf('zv::Val %s = %s_b.finish();', $t, $t));
			return new Value('zval', $t . '.raw()', $t);
		}
		if ($expr instanceof Node\Scalar\Float_) {
			$t = $f->temp('fl');
			$f->line(sprintf('zval %s;', $t));
			$f->line(sprintf('ZVAL_DOUBLE(&%s, %s);', $t, var_export($expr->value, true)));
			return new Value('zval', '&' . $t);
		}
		if ($expr instanceof Node\Scalar\MagicConst) {
			$value = $this->constantValue($expr);
			if (is_int($value)) {
				return Value::long((string) $value);
			}
			return $this->literal((string) $value);
		}
		if ($expr instanceof Expr\UnaryMinus) {
			$operand = $this->expr($f, $expr->expr);
			if ($operand->kind === 'long') {
				$t = $f->temp('neg');
				$f->line(sprintf('zend_long %s;', $t));
				$f->failIf(sprintf('!pgrt::negateLong(%s, &%s)', $operand->code, $t));
				return Value::long($t);
			}
			return $this->arithmetic($f, 'mul_function', $operand, Value::long('-1'), $native);
		}
		if ($expr instanceof Expr\UnaryPlus) {
			return $this->arithmetic($f, 'mul_function', $this->expr($f, $expr->expr), Value::long('1'), $native);
		}
		if ($expr instanceof Expr\BitwiseNot) {
			$operand = $this->expr($f, $expr->expr);
			if ($operand->kind === 'long') {
				return Value::long(sprintf('(~%s)', $operand->code));
			}
			$t = $f->temp('bn');
			$f->line(sprintf('zv::Val %s;', $t));
			$f->failIf(sprintf('bitwise_not_function(%s.raw(), %s) != SUCCESS', $t, $this->asZval($f, $operand)));
			return $this->narrowZval($t . '.raw()', $t, $native);
		}
		if ($expr instanceof Expr\Instanceof_) {
			$value = $this->expr($f, $expr->expr);
			if (!$expr->class instanceof Node\Name) {
				$z = $this->asZval($f, $value);
				$class = $this->asZval($f, $this->expr($f, $expr->class));
				$t = $f->temp('io');
				$f->line(sprintf('bool %s;', $t));
				$f->failIf(sprintf('!pgrt::instanceOfDynamic(%s, %s, &%s)', $z, $class, $t));
				return Value::bool($t);
			}
			$z = $this->asZval($f, $value);
			return Value::bool($this->instanceCheck($z, $this->className($expr->class), false));
		}

		throw new CompileError(sprintf('unsupported expression %s on line %d', $expr::class, $expr->getStartLine()));
	}

	private function readLocal(FunctionContext $f, string $name): Value
	{
		$local = $f->locals[$name] ?? null;
		if ($local === null) {
			throw new CompileError(sprintf('undefined variable $%s in %s', $name, $f->label));
		}
		switch ($local->kind) {
			case 'long':
				return Value::long($local->cName);
			case 'bool':
				return Value::bool($local->cName);
			case 'zval':
				return new Value('zval', $local->cName . '.raw()', class: $local->class, isArray: $local->isArray, stable: isset($f->stableVars[$name]));
			case 'param':
				return new Value('zval', $local->cName, class: $local->class, isArray: $local->isArray, stable: isset($f->stableVars[$name]));
			case 'ref':
				return new Value('zval', sprintf('zv::Ref(%s).deref().raw()', $local->cName), class: $local->class, isArray: $local->isArray);
			case 'callable':
				return new Value('zval', sprintf('&%s.fci.function_name', $local->cName));
			case 'vector':
				// a variadic parameter used as a value: the list it is in PHP
				$t = $f->temp('vec');
				$f->line(sprintf('zv::Val %s = zv::Val::adopt([&]() { zval a; array_init_size(&a, %s_count); for (uint32_t i = 0; i < %s_count; i++) { Z_TRY_ADDREF(%s[i]); zend_hash_next_index_insert_new(Z_ARRVAL(a), &%s[i]); } return a; }());', $t, $local->cName, $local->cName, $local->cName, $local->cName));
				return new Value('zval', $t . '.raw()', $t, isArray: true);
		}
		throw new CompileError(sprintf('$%s cannot be read as a value here', $name));
	}

	private function literal(string $value): Value
	{
		if (!array_key_exists($value, $this->literals)) {
			$this->literals[$value] = count($this->literals);
		}
		return new Value('zval', sprintf('&pg_lit[%d]', $this->literals[$value]));
	}

	private function arrayLiteral(FunctionContext $f, Expr\Array_ $expr): Value
	{
		$t = $f->temp('arr');
		if (count($expr->items) === 0) {
			$f->line(sprintf('zval %s_raw;', $t));
			$f->line(sprintf('ZVAL_EMPTY_ARRAY(&%s_raw);', $t));
			return new Value('zval', sprintf('&%s_raw', $t), isArray: true);
		}
		$f->line(sprintf('zv::Val %s = zv::Val::adopt([]() { zval a; array_init_size(&a, %d); return a; }());', $t, count($expr->items)));
		foreach ($expr->items as $item) {
			if ($item->byRef) {
				throw new CompileError('by-reference array items are not supported');
			}
			if ($item->unpack) {
				$f->failIf(sprintf('!pgrt::spreadInto(%s.raw(), %s)', $t, $this->asZval($f, $this->expr($f, $item->value))));
				continue;
			}
			$value = $this->own($f, $this->expr($f, $item->value));
			if ($item->key === null) {
				$f->line(sprintf('{ zval v = %s.take(); zend_hash_next_index_insert(Z_ARRVAL_P(%s.raw()), &v); }', $value, $t));
				continue;
			}
			$key = $this->expr($f, $item->key);
			if ($key->kind === 'long') {
				$f->line(sprintf('{ zval v = %s.take(); zend_hash_index_update(Z_ARRVAL_P(%s.raw()), %s, &v); }', $value, $t, $key->code));
			} elseif ($this->unit()->oracle->nativeType($item->key)->isString()->yes()) {
				$f->line(sprintf('{ zval v = %s.take(); zend_symtable_update(Z_ARRVAL_P(%s.raw()), Z_STR_P(%s), &v); }', $value, $t, $this->asZval($f, $key)));
			} else {
				$f->failIf(sprintf('!([&]() { zval v = %s.take(); return pgrt::insertKeyed(%s.raw(), %s, &v); }())', $value, $t, $this->asZval($f, $key)));
			}
		}
		return new Value('zval', $t . '.raw()', $t, isArray: true);
	}

	/**
	 * A C string literal of the value, for pgrt::internLiteral(); one with a
	 * NUL byte is a named array instead: the compiler numbers such literals'
	 * sections after the ones the engine's headers define, which differ
	 * between PHP versions, and the shared core must compile to the same
	 * object against every version.
	 */
	private function stringLiteral(string $value): string
	{
		if (!str_contains($value, "\0")) {
			return '"' . self::cString($value) . '"';
		}
		$name = sprintf('pg_nul_literal_%d', $this->closureCount++);
		$this->closures[] = sprintf('static const char %s[] = "%s";', $name, self::cString($value));
		return $name;
	}

	private function className(Node $class): string
	{
		if (!$class instanceof Node\Name) {
			throw new CompileError('dynamic class references are not supported');
		}
		$lower = $class->toLowerString();
		if (in_array($lower, ['self', 'static'], true)) {
			if ($lower === 'static' && !$this->unit()->reflection->isFinal()) {
				throw new CompileError('static:: in a non-final class is not supported');
			}
			return $this->unit()->className;
		}
		if ($lower === 'parent') {
			throw new CompileError('parent:: is not supported');
		}
		return ltrim($class->toString(), '\\');
	}

	private function classEntry(string $className, ?FunctionContext $f): string
	{
		$unit = $this->units[strtolower($className)] ?? null;
		if ($unit !== null) {
			return $unit->ce;
		}
		$shadowCe = $this->shadowCe($className);
		if ($shadowCe !== null) {
			return $shadowCe;
		}
		if ($f === null) {
			throw new CompileError(sprintf('class %s needs a run-time lookup outside a function', $className));
		}
		$site = $this->site('pgrt::ClassSite', 'cls_' . $className);
		$t = $f->temp('ce');
		$f->line(sprintf('zend_class_entry *%s = pgrt::lookupClass(%s, "%s", %d);', $t, $site, addcslashes($className, '\\'), strlen($className)));
		$f->failIf(sprintf('%s == NULL', $t));
		return $t;
	}

	private function site(string $type, string $key): string
	{
		if (isset($this->siteNames[$key])) {
			return $this->siteNames[$key];
		}
		$name = sprintf('pg_site_%d', count($this->siteNames));
		$this->siteNames[$key] = $name;
		$this->siteDecls[] = sprintf('static %s %s; /* %s */', $type, $name, str_contains($key, '*/') ? '' : $key);
		return $name;
	}

	private function propertyFetch(FunctionContext $f, Expr\PropertyFetch $expr): Value
	{
		if (!$expr->name instanceof Node\Identifier) {
			// $object->{$name}: through the object handlers
			$z = $this->asZval($f, $this->stabilize($f, $this->expr($f, $expr->var)));
			$propertyName = $this->dynamicPropertyName($f, $expr->name);
			$owned = $f->temp('o');
			$f->line(sprintf('zv::Val %s;', $owned));
			$f->failIf(sprintf('!pgrt::readPropertyGeneric(%s, %s, Z_STRVAL_P(%s), Z_STRLEN_P(%s), %s.raw())', $z, $this->unit()->ce, $propertyName, $propertyName, $owned));
			return $this->narrowZval($owned . '.raw()', $owned, $this->unit()->oracle->nativeType($expr));
		}
		$name = $expr->name->toString();
		$receiverNative = $this->unit()->oracle->nativeType($expr->var);
		$receiver = $this->expr($f, $expr->var);
		$class = $this->receiverClass($receiver, $receiverNative);
		$native = $this->unit()->oracle->nativeType($expr);

		$unit = $class !== null ? ($this->units[strtolower($class)] ?? null) : null;
		$slotConstant = $unit !== null && $receiver->kind === 'zval' ? $this->slotExpr($unit->className, $name, sprintf('Z_OBJ_P(%s)', $receiver->code)) : null;
		if ($slotConstant !== null) {
			$slot = $f->temp('p');
			$f->line(sprintf('zval *%s = pgrt::readSlot(Z_OBJ_P(%s), %s, "%s");', $slot, $receiver->code, $slotConstant, $name));
			$f->failIf(sprintf('%s == NULL', $slot));
			return $this->fromSlot($slot, $native);
		}

		// a public declared property of any other class: an inline cache of
		// its slot per receiver class, the handlers for anything else
		// (an unset slot, a magic __get, another class)
		// (the PHPDoc type is only a guess, but the cache takes public
		// declared properties of whatever class the object has, and the
		// handlers anything else)
		if ($receiver->kind === 'zval' && ($this->isCacheablePropertyOf($class, $receiverNative, $name) || (($class === null || !$this->isCacheableProperty($class, $name)) && $this->isCacheablePropertyOf(null, $this->unit()->oracle->phpDocType($expr->var), $name) && !$this->isCompiledPropertyOf($this->unit()->oracle->phpDocType($expr->var))))) {
			$z = $this->asZval($f, $receiver);
			$site = $this->site('pgrt::PropSite', sprintf('prop_%s_%d', $name, count($this->siteNames)));
			$p = $f->temp('p');
			$owned = $f->temp('o');
			$f->line(sprintf('zv::Val %s;', $owned));
			$f->line(sprintf('zval *%s = Z_TYPE_P(%s) == IS_OBJECT ? pgrt::propCached(%s, Z_OBJ_P(%s), "%s", %d) : NULL;', $p, $z, $site, $z, $name, strlen($name)));
			$f->open(sprintf('if (UNEXPECTED(%s == NULL || Z_ISUNDEF_P(%s)))', $p, $p));
			$f->failIf(sprintf('!pgrt::readPropertyGeneric(%s, %s, "%s", %d, %s.raw())', $z, $this->unit()->ce, $name, strlen($name), $owned));
			$f->line(sprintf('%s = %s.raw();', $p, $owned));
			$f->close();
			return $this->fromSlot(sprintf('zv::Ref(%s).deref().raw()', $p), $native);
		}

		// speculate on the PHPDoc type: a guarded slot read, the engine otherwise
		$speculated = $this->knownClass($this->unit()->oracle->phpDocType($expr->var));
		$z = $this->asZval($f, $receiver);
		$out = $f->temp('p');
		$owned = $f->temp('o');
		$f->line(sprintf('zval *%s;', $out));
		$f->line(sprintf('zv::Val %s;', $owned));
		$specUnit = $speculated !== null ? ($this->units[strtolower($speculated)] ?? null) : null;
		$specSlot = $specUnit !== null && $specUnit->reflection->isFinal() ? $this->slotExpr($specUnit->className, $name, sprintf('Z_OBJ_P(%s)', $z)) : null;
		if ($specUnit !== null && $specSlot !== null) {
			$f->open(sprintf('if (EXPECTED(Z_TYPE_P(%s) == IS_OBJECT && Z_OBJCE_P(%s) == %s))', $z, $z, $specUnit->ce));
			$f->line(sprintf('%s = pgrt::readSlot(Z_OBJ_P(%s), %s, "%s");', $out, $z, $specSlot, $name));
			$f->failIf(sprintf('%s == NULL', $out));
			$f->close(' else {');
			$f->open('');
		} else {
			$f->open('');
		}
		$f->failIf(sprintf('!pgrt::readPropertyGeneric(%s, %s, "%s", %d, %s.raw())', $z, $this->unit()->ce, $name, strlen($name), $owned));
		$f->line(sprintf('%s = %s.raw();', $out, $owned));
		$f->close();
		if ($specUnit !== null && $specSlot !== null) {
			$f->line('}');
		}
		return new Value('zval', $out, class: $this->knownClass($native), isArray: $native->isArray()->yes());
	}

	private function fromSlot(string $slot, Type $native): Value
	{
		if ($native->isInteger()->yes()) {
			return Value::long(sprintf('Z_LVAL_P(%s)', $slot));
		}
		if ($native->isBoolean()->yes()) {
			return Value::bool(sprintf('(Z_TYPE_P(%s) == IS_TRUE)', $slot));
		}
		return new Value('zval', $slot, class: $this->knownClass($native), isArray: $native->isArray()->yes());
	}

	private function staticPropertySlot(FunctionContext $f, Expr\StaticPropertyFetch $expr): string
	{
		if (!$expr->name instanceof Node\VarLikeIdentifier) {
			throw new CompileError('dynamic static property names are not supported');
		}
		$className = $this->className($expr->class);
		$ce = $this->classEntry($className, $f);
		$name = $expr->name->toString();
		$site = $this->site('pgrt::StaticSite', 'static_' . $className . '::' . $name);
		$t = $f->temp('s');
		$f->line(sprintf('zval *%s = pgrt::staticSlot(%s, %s, "%s", %d);', $t, $site, $ce, $name, strlen($name)));
		$f->line(sprintf('ZVAL_DEREF(%s);', $t));
		return $t;
	}

	private function arrayDimFetch(FunctionContext $f, Expr\ArrayDimFetch $expr): Value
	{
		if ($expr->dim === null) {
			throw new CompileError('[] read');
		}
		$var = $expr->var;
		if ($var instanceof Expr\Variable && is_string($var->name) && ($f->locals[$var->name] ?? null)?->kind === 'constarray') {
			return $this->constantArrayFetch($f, $f->locals[$var->name], $expr->dim);
		}
		$array = $this->expr($f, $var);
		if ($this->hasSideEffects($expr->dim)) {
			$array = $this->stabilize($f, $array);
		}
		$key = $this->expr($f, $expr->dim);
		$z = $this->asZval($f, $array);
		$t = $f->temp('e');
		$tmp = $f->temp('et');
		$f->line(sprintf('zv::Val %s;', $tmp));
		if ($key->kind === 'long') {
			// the common case inline: an array and an int key
			$f->line(sprintf('zval *%s = Z_TYPE_P(%s) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(%s), %s) : NULL;', $t, $z, $z, $key->code));
			$f->open(sprintf('if (UNEXPECTED(%s == NULL))', $t));
			$f->line(sprintf('%s = pgrt::dimRead(%s, %s, %s.raw());', $t, $z, $this->asZval($f, $key), $tmp));
			$f->failIf(sprintf('%s == NULL', $t));
			$f->close();
		} else {
			$f->line(sprintf('zval *%s = pgrt::dimRead(%s, %s, %s.raw());', $t, $z, $this->asZval($f, $key), $tmp));
			$f->failIf(sprintf('%s == NULL', $t));
		}
		$native = $this->unit()->oracle->nativeType($expr);
		return new Value('zval', sprintf('zv::Ref(%s).deref().raw()', $t), class: $this->knownClass($native), isArray: $native->isArray()->yes());
	}

	/** a read of a constant `static` array: a switch over its keys */
	private function constantArrayFetch(FunctionContext $f, LocalVariable $local, Expr $dim): Value
	{
		$array = $local->constant;
		if ($array === null || !$array->isConstantArray()->yes()) {
			throw new CompileError('constant array expected');
		}
		$arrays = $array->getConstantArrays();
		if (count($arrays) !== 1) {
			throw new CompileError('one constant array expected');
		}
		$constant = $arrays[0];
		$key = $this->expr($f, $dim);
		if ($key->kind !== 'long') {
			throw new CompileError('constant static arrays are read with int keys only');
		}
		$t = $f->temp('c');
		$f->line(sprintf('zval *%s;', $t));
		$f->open(sprintf('switch (%s)', $key->code));
		foreach ($constant->getKeyTypes() as $i => $keyType) {
			$keyValues = $keyType->getConstantScalarValues();
			$valueValues = $constant->getValueTypes()[$i]->getConstantScalarValues();
			if (count($keyValues) !== 1 || !is_int($keyValues[0]) || count($valueValues) !== 1 || !is_string($valueValues[0])) {
				throw new CompileError('constant static arrays must map ints to strings');
			}
			$f->line(sprintf('case %d: %s = %s; break;', $keyValues[0], $t, $this->literal($valueValues[0])->code));
		}
		$f->line(sprintf('default: pgrt::warnUndefinedIndex(%s); if (UNEXPECTED(EG(exception))) %s %s = &EG(uninitialized_zval); break;', $key->code, $f->fail, $t));
		$f->close();
		return new Value('zval', $t);
	}

	private function assign(FunctionContext $f, Expr\Assign $expr): Value
	{
		$target = $expr->var;
		if ($target instanceof Expr\Variable && is_string($target->name) && $expr->expr instanceof Expr\Variable && $expr->expr->name === $target->name) {
			// `$x = $x;` (a PHPDoc retyping): nothing changes
			return $this->expr($f, $expr->expr);
		}
		if ($target instanceof Expr\Variable && is_string($target->name)) {
			$local = $f->locals[$target->name] ?? null;
			if ($local === null) {
				throw new CompileError(sprintf('no home for $%s', $target->name));
			}
			if ($local->kind === 'list') {
				$f->line(sprintf('%s.clear();', $local->cName));
				return Value::void();
			}
			$this->closureAsHolder = isset($this->holderVariables[$target->name]) && ($expr->expr instanceof Expr\Closure || $expr->expr instanceof Expr\ArrowFunction);
			$value = $this->expr($f, $expr->expr);
			$this->closureAsHolder = false;
			if ($local->kind === 'ref') {
				// through the reference, like any write to a variable bound by reference
				$owned = $this->own($f, $value);
				$f->line(sprintf('zv::Ref(zv::Ref(%s).deref().raw()).assign(std::move(%s));', $local->cName, $owned));
				return new Value('zval', sprintf('zv::Ref(%s).deref().raw()', $local->cName));
			}
			switch ($local->kind) {
				case 'long':
					$f->line(sprintf('%s = %s;', $local->cName, $this->asLong($f, $value, 'int', $f->label)));
					return Value::long($local->cName);
				case 'bool':
					$f->line(sprintf('%s = %s;', $local->cName, $this->asBool($value)));
					return Value::bool($local->cName);
				case 'zval':
					$owned = $this->own($f, $value);
					$f->line(sprintf('%s = std::move(%s);', $local->cName, $owned));
					return new Value('zval', $local->cName . '.raw()', class: $local->class, isArray: $local->isArray);
			}
			throw new CompileError(sprintf('$%s cannot be assigned', $target->name));
		}
		if ($target instanceof Expr\ArrayDimFetch && $target->dim === null && $target->var instanceof Expr\Variable && is_string($target->var->name)) {
			$local = $f->locals[$target->var->name] ?? null;
			if ($local !== null && $local->kind === 'list') {
				$owned = $this->own($f, $this->expr($f, $expr->expr));
				$f->line(sprintf('{ zval v = %s.take(); %s.pushOwned(&v); }', $owned, $local->cName));
				return Value::void();
			}
		}
		if ($target instanceof Expr\ArrayDimFetch) {
			// PHP evaluates the offset, then the value, then writes
			$key = $target->dim !== null ? $this->stabilize($f, $this->expr($f, $target->dim)) : null;
			$value = $this->stabilize($f, $this->expr($f, $expr->expr));
			$owned = $f->temp('o');
			$f->line(sprintf('zv::Val %s = zv::Val::copyOf(zv::Ref(%s));', $owned, $this->asZval($f, $value)));
			$container = $this->writablePtr($f, $target->var, true);
			$this->dimStore($f, $container, $key, $owned);
			return $value;
		}
		if ($target instanceof Expr\PropertyFetch) {
			return $this->assignProperty($f, $target, $expr->expr);
		}
		if ($target instanceof Expr\StaticPropertyFetch) {
			$value = $this->stabilize($f, $this->expr($f, $expr->expr));
			$owned = $f->temp('o');
			$f->line(sprintf('zv::Val %s = zv::Val::copyOf(zv::Ref(%s));', $owned, $this->asZval($f, $value)));
			$propertyType = $this->staticPropertyType($target);
			if ($propertyType !== null && !$propertyType->isSuperTypeOf($this->unit()->oracle->nativeType($expr->expr))->yes()) {
				$check = $this->typeCheck($owned . '.raw()', $propertyType, $propertyType->describe(VerbosityLevel::typeOnly()));
				if ($check !== null) {
					$f->line(sprintf('if (UNEXPECTED(!%s)) { zend_type_error("Cannot assign %%s to property %%s::$%%s of type %%s", zend_zval_value_name(%s.raw()), "%s", "%s", "%s"); %s }', $check, $owned, addcslashes($this->className($target->class), '\\\\'), $target->name instanceof Node\VarLikeIdentifier ? $target->name->toString() : '', addcslashes($propertyType->describe(VerbosityLevel::typeOnly()), '\\\\'), $f->fail));
				}
			}
			$slot = $this->staticPropertySlot($f, $target);
			$f->line(sprintf('zv::Ref(%s).assign(std::move(%s));', $slot, $owned));
			return $value;
		}
		if ($target instanceof Expr\List_ || $target instanceof Expr\Array_) {
			$value = $this->stabilize($f, $this->expr($f, $expr->expr));
			$this->assignTo($f, $target, $this->asZval($f, $value));
			return $value;
		}
		throw new CompileError(sprintf('unsupported assignment target %s on line %d', $target::class, $expr->getStartLine()));
	}

	/**
	 * A writable, dereferenced zval for the container of an offset write:
	 * a local, a property or static property slot, or a nested offset
	 * (created on the way, as the VM's write fetches do). With $forArray the
	 * zval is made an array (null auto-vivifies) and separated.
	 */
	private function writablePtr(FunctionContext $f, Expr $expr, bool $forArray): string
	{
		$t = $f->temp('w');
		if ($expr instanceof Expr\Variable && is_string($expr->name)) {
			$local = $f->locals[$expr->name] ?? null;
			if ($local === null || !in_array($local->kind, ['zval', 'ref'], true)) {
				throw new CompileError(sprintf('$%s has no writable zval home', $expr->name));
			}
			$f->line(sprintf('zval *%s = zv::Ref(%s).deref().raw();', $t, $local->kind === 'zval' ? $local->cName . '.raw()' : $local->cName));
		} elseif ($expr instanceof Expr\PropertyFetch) {
			$slot = $this->propertySlotForWrite($f, $expr);
			$f->line(sprintf('zval *%s = zv::Ref(%s).deref().raw();', $t, $slot));
		} elseif ($expr instanceof Expr\StaticPropertyFetch) {
			$slot = $this->staticPropertySlot($f, $expr);
			$f->line(sprintf('zval *%s = %s;', $t, $slot));
		} elseif ($expr instanceof Expr\ArrayDimFetch) {
			$key = $expr->dim !== null ? $this->stabilize($f, $this->expr($f, $expr->dim)) : null;
			$parent = $this->writablePtr($f, $expr->var, true);
			$f->line(sprintf('zval *%s;', $t));
			if ($key === null) {
				$f->line(sprintf('{ zval none; ZVAL_NULL(&none); %s = zend_hash_next_index_insert(Z_ARRVAL_P(%s), &none); }', $t, $parent));
				$f->line(sprintf('if (UNEXPECTED(%s == NULL)) { zend_throw_error(NULL, "Cannot add element to the array as the next element is already occupied"); %s }', $t, $f->fail));
			} else {
				$f->line(sprintf('%s = %s;', $t, $this->dimLookupForWrite($f, $parent, $key)));
				$f->failIf(sprintf('%s == NULL', $t));
				$f->line(sprintf('ZVAL_DEREF(%s);', $t));
			}
		} else {
			throw new CompileError(sprintf('%s is not a writable container', $expr::class));
		}
		if ($forArray) {
			$f->line(sprintf('if (Z_TYPE_P(%s) <= IS_NULL) { zval_ptr_dtor(%s); array_init(%s); } else if (UNEXPECTED(Z_TYPE_P(%s) != IS_ARRAY)) { zend_throw_error(NULL, "Cannot use a scalar value as an array"); %s }', $t, $t, $t, $t, $f->fail));
			$f->line(sprintf('SEPARATE_ARRAY(%s);', $t));
		}
		return $t;
	}

	/** the element of an array for a write: found or added as null (NULL = an illegal offset, error pending) */
	private function dimLookupForWrite(FunctionContext $f, string $array, Value $key): string
	{
		if ($key->kind === 'long') {
			return sprintf('pgrt::dimWriteLong(%s, %s)', $array, $key->code);
		}
		return sprintf('pgrt::dimWrite(%s, %s)', $array, $this->asZval($f, $key));
	}

	private function dimStore(FunctionContext $f, string $array, ?Value $key, string $owned): void
	{
		if ($key === null) {
			$f->line(sprintf('{ zval v = %s.take(); if (UNEXPECTED(zend_hash_next_index_insert(Z_ARRVAL_P(%s), &v) == NULL)) { zval_ptr_dtor(&v); zend_throw_error(NULL, "Cannot add element to the array as the next element is already occupied"); %s } }', $owned, $array, $f->fail));
			return;
		}
		$e = $f->temp('e');
		$f->line(sprintf('zval *%s = %s;', $e, $this->dimLookupForWrite($f, $array, $key)));
		$f->failIf(sprintf('%s == NULL', $e));
		$f->line(sprintf('zv::Ref(%s).assign(std::move(%s));', $e, $owned));
	}

	/** the name of `->{$expr}` as a string zval (the engine's conversion of a non-string) */
	private function dynamicPropertyName(FunctionContext $f, Expr $name): string
	{
		$t = $f->temp('pn');
		$f->line(sprintf('zv::Val %s;', $t));
		$f->failIf(sprintf('!pgrt::propertyName(%s, %s.raw())', $this->asZval($f, $this->expr($f, $name)), $t));
		return $t . '.raw()';
	}

	/** the slot of a declared property of a compiled class, for a write that is not a readonly initialization */
	private function propertySlotForWrite(FunctionContext $f, Expr\PropertyFetch $fetch): string
	{
		if (!$fetch->name instanceof Node\Identifier) {
			throw new CompileError('dynamic property names are not supported');
		}
		$name = $fetch->name->toString();
		$receiver = $this->stabilize($f, $this->expr($f, $fetch->var));
		$class = $this->receiverClass($receiver, $this->unit()->oracle->nativeType($fetch->var));
		$unit = $class !== null ? ($this->units[strtolower($class)] ?? null) : null;
		if ($unit === null || !$unit->reflection->hasProperty($name)) {
			throw new CompileError(sprintf('writing into ->%s of a value not proven to be a compiled class', $name));
		}
		$property = $unit->reflection->getProperty($name);
		if ($property->isReadOnly() || $property->isStatic()) {
			throw new CompileError(sprintf('->%s is readonly or static', $name));
		}
		$slot = $this->slotExpr($unit->className, $name, sprintf('Z_OBJ_P(%s)', $receiver->code));
		if ($slot === null) {
			throw new CompileError(sprintf('->%s has no slot', $name));
		}
		return sprintf('OBJ_PROP_NUM(Z_OBJ_P(%s), %s)', $receiver->code, $slot);
	}

	/**
	 * $object->name = value: a declared property of a compiled class by slot
	 * (readonly initialized once, a typed one checked unless proven), any
	 * other through the object handlers with the class as calling scope.
	 */
	private function assignProperty(FunctionContext $f, Expr\PropertyFetch $target, Expr $valueExpr): Value
	{
		if (!$target->name instanceof Node\Identifier) {
			// $object->{$name} = value: through the object handlers
			$receiver = $this->stabilize($f, $this->expr($f, $target->var));
			$propertyName = $this->dynamicPropertyName($f, $target->name);
			$value = $this->stabilize($f, $this->expr($f, $valueExpr));
			$f->failIf(sprintf('!pgrt::writePropertyGeneric(%s, %s, %s, %s)', $this->asZval($f, $receiver), $this->unit()->ce, $propertyName, $this->asZval($f, $value)));
			return $value;
		}
		$name = $target->name->toString();
		$receiver = $this->stabilize($f, $this->expr($f, $target->var));
		$value = $this->stabilize($f, $this->expr($f, $valueExpr));
		$z = $this->asZval($f, $value);
		$class = $this->receiverClass($receiver, $this->unit()->oracle->nativeType($target->var));
		$unit = $class !== null ? ($this->units[strtolower($class)] ?? null) : null;
		$slot = $unit !== null ? $this->slotExpr($unit->className, $name, sprintf('Z_OBJ_P(%s)', $receiver->code)) : null;
		if ($unit !== null && $slot !== null) {
			$property = $unit->reflection->getProperty($name);
			$owned = $f->temp('o');
			$f->line(sprintf('zval %s;', $owned));
			$f->line(sprintf('ZVAL_COPY(&%s, %s);', $owned, $z));
			$type = $property->getType();
			if ($type !== null) {
				[, $declared, $describe] = $this->lowerDeclaredType($type, $unit, false);
				if (!$declared->isSuperTypeOf($this->unit()->oracle->nativeType($valueExpr))->yes()) {
					$check = $this->typeCheck('&' . $owned, $declared, $describe);
					if ($check !== null) {
						$f->line(sprintf('if (UNEXPECTED(!%s)) { zend_type_error("Cannot assign %%s to property %%s::$%%s of type %%s", zend_zval_value_name(&%s), "%s", "%s", "%s"); zval_ptr_dtor(&%s); %s }', $check, $owned, addcslashes($unit->className, '\\\\'), $name, addcslashes($describe, '\\\\'), $owned, $f->fail));
					}
				}
			}
			if ($property->isReadOnly()) {
				$f->failIf(sprintf('!pgrt::initReadonly(Z_OBJ_P(%s), %s, "%s", &%s)', $receiver->code, $slot, $name, $owned));
			} else {
				$f->line(sprintf('pgrt::writeSlot(Z_OBJ_P(%s), %s, &%s);', $receiver->code, $slot, $owned));
			}
			return $value;
		}
		$r = $this->asZval($f, $receiver);
		$f->failIf(sprintf('!pgrt::writePropertyGeneric(%s, %s, %s, %s)', $r, $this->unit()->ce, $this->literal($name)->code, $z));
		return $value;
	}

	private function coalesceAssign(FunctionContext $f, Expr\AssignOp\Coalesce $expr): Value
	{
		$target = $expr->var;
		$native = $this->unit()->oracle->nativeType($expr);
		if ($target instanceof Expr\StaticPropertyFetch) {
			$slot = $this->staticPropertySlot($f, $target);
			$f->open(sprintf('if (Z_TYPE_P(%s) <= IS_NULL)', $slot));
			$value = $this->expr($f, $expr->expr);
			$owned = $this->own($f, $value);
			$propertyType = $this->staticPropertyType($target);
			if ($propertyType !== null && !$propertyType->isSuperTypeOf($this->unit()->oracle->nativeType($expr->expr))->yes()) {
				$class = $this->knownClass($propertyType);
				if ($class === null) {
					throw new CompileError('typed static property checks are implemented for class types only');
				}
				$f->open(sprintf('if (UNEXPECTED(!%s))', $this->instanceCheck($owned . '.raw()', $class, false)));
				$f->line(sprintf('zend_type_error("Cannot assign %%s to property %%s::$%%s of type %%s", zend_zval_value_name(%s.raw()), "%s", "%s", "%s");', $owned, addcslashes($this->className($target->class), '\\'), $target->name, addcslashes($class, '\\')));
				$f->line($f->fail);
				$f->close();
			}
			$again = $this->staticPropertySlot($f, $target);
			$f->line(sprintf('zval old = *%s; *%s = %s.take(); zval_ptr_dtor(&old);', $again, $again, $owned));
			$f->close();
			return new Value('zval', $slot, class: $this->knownClass($native), isArray: $native->isArray()->yes());
		}
		if (
			$target instanceof Expr\ArrayDimFetch
			&& $target->dim !== null
			&& $this->unit()->oracle->nativeType($target->dim)->isInteger()->yes()
			&& ($target->var instanceof Expr\StaticPropertyFetch || ($target->var instanceof Expr\Variable && is_string($target->var->name) && ($f->locals[$target->var->name] ?? null)?->kind === 'zval'))
		) {
			$container = $target->var;
			$key = $this->stabilize($f, $this->expr($f, $target->dim));
			if ($key->kind !== 'long') {
				throw new CompileError('??= on array offsets supports int keys only');
			}
			$arrayPtr = function () use ($f, $container): string {
				if ($container instanceof Expr\StaticPropertyFetch) {
					return $this->staticPropertySlot($f, $container);
				}
				$local = is_string($container->name) ? ($f->locals[$container->name] ?? null) : null;
				if ($local === null || $local->kind !== 'zval') {
					throw new CompileError('??= on offsets of this container is not supported');
				}
				return $local->cName . '.raw()';
			};
			$arr = $arrayPtr();
			$e = $f->temp('e');
			$f->line(sprintf('zval *%s = Z_TYPE_P(%s) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(%s), %s) : NULL;', $e, $arr, $arr, $key->code));
			$f->line(sprintf('if (%s != NULL) ZVAL_DEREF(%s);', $e, $e));
			$f->open(sprintf('if (%s == NULL || Z_TYPE_P(%s) == IS_NULL)', $e, $e));
			$value = $this->expr($f, $expr->expr);
			$owned = $this->own($f, $value);
			$arr2 = $arrayPtr();
			$f->line(sprintf('if (UNEXPECTED(Z_TYPE_P(%s) != IS_ARRAY)) { zend_throw_error(NULL, "Cannot use a scalar value as an array"); %s }', $arr2, $f->fail));
			$f->line(sprintf('{ zval v = %s.take(); pgrt::setIndex(%s, %s, &v); }', $owned, $arr2, $key->code));
			$f->line(sprintf('%s = zend_hash_index_find(Z_ARRVAL_P(%s), %s);', $e, $arr2, $key->code));
			$f->close();
			return new Value('zval', $e, class: $this->knownClass($native), isArray: $native->isArray()->yes());
		}
		// any other target: isset, then a plain assignment (its sub-expressions
		// are evaluated again, so they must not have side effects)
		if ($this->hasSideEffects($target)) {
			throw new CompileError(sprintf('??= on a target with side effects (line %d)', $expr->getStartLine()));
		}
		$ptr = $this->issetPtr($f, $target);
		$t = $f->temp('ca');
		$f->line(sprintf('zv::Val %s;', $t));
		$f->open(sprintf('if (%s)', $this->setCheck($ptr)));
		$f->line(sprintf('%s = zv::Val::copyOf(zv::Ref(%s));', $t, $ptr));
		$f->close(' else {');
		$f->open('');
		$assigned = $this->assign($f, new Expr\Assign($target, $expr->expr, $expr->getAttributes()));
		$f->line(sprintf('%s = zv::Val::copyOf(zv::Ref(%s));', $t, $this->asZval($f, $assigned)));
		$f->close();
		$f->line('}');
		return $this->narrowZval($t . '.raw()', $t, $native);
	}

	private function staticPropertyType(Expr\StaticPropertyFetch $fetch): ?Type
	{
		$class = self::reflect($this->className($fetch->class));
		$property = $class->getProperty($fetch->name instanceof Node\VarLikeIdentifier ? $fetch->name->toString() : '');
		$type = $property->getType();
		if ($type === null) {
			return null;
		}
		[, $declared] = $this->lowerDeclaredType($type, $this->units[strtolower($class->getName())] ?? $this->unit(), false);
		return $declared;
	}

	/**
	 * $target op= $value: an int local in C++, anything else in place
	 * through the engine's operator function on the target's writable zval.
	 */
	private function compoundAssign(FunctionContext $f, Expr\AssignOp $expr): Value
	{
		$functions = [
			Expr\AssignOp\Plus::class => ['add_function', 'addLong', '+'],
			Expr\AssignOp\Minus::class => ['sub_function', 'subLong', '-'],
			Expr\AssignOp\Mul::class => ['mul_function', 'mulLong', '*'],
			Expr\AssignOp\Div::class => ['div_function', null, null],
			Expr\AssignOp\Mod::class => ['mod_function', null, null],
			Expr\AssignOp\Pow::class => ['pow_function', null, null],
			Expr\AssignOp\Concat::class => ['concat_function', null, null],
			Expr\AssignOp\BitwiseAnd::class => ['bitwise_and_function', null, '&'],
			Expr\AssignOp\BitwiseOr::class => ['bitwise_or_function', null, '|'],
			Expr\AssignOp\BitwiseXor::class => ['bitwise_xor_function', null, '^'],
			Expr\AssignOp\ShiftLeft::class => ['shift_left_function', null, null],
			Expr\AssignOp\ShiftRight::class => ['shift_right_function', null, null],
		];
		if (!isset($functions[$expr::class])) {
			throw new CompileError(sprintf('unsupported compound assignment %s', $expr::class));
		}
		[$function, $longOp, $cOp] = $functions[$expr::class];
		$target = $expr->var;
		if ($target instanceof Expr\Variable && is_string($target->name) && ($f->locals[$target->name] ?? null)?->kind === 'long') {
			$local = $f->locals[$target->name];
			$value = $this->expr($f, $expr->expr);
			$long = $this->asLong($f, $value, 'int', $f->label);
			if ($longOp !== null) {
				$f->failIf(sprintf('!pgrt::%s(%s, %s, &%s)', $longOp, $local->cName, $long, $local->cName));
			} elseif ($cOp !== null) {
				$f->line(sprintf('%s %s= %s;', $local->cName, $cOp, $long));
			} else {
				throw new CompileError(sprintf('%s on an int-typed local', $expr::class));
			}
			return Value::long($local->cName);
		}
		$ptr = $this->writablePtr($f, $target, false);
		$value = $this->expr($f, $expr->expr);
		$z = $this->asZval($f, $value);
		$f->failIf(sprintf('%s(%s, %s, %s) != SUCCESS || EG(exception)', $function, $ptr, $ptr, $z));
		return new Value('zval', $ptr);
	}

	private function bitwiseAssign(FunctionContext $f, Expr\AssignOp $expr): Value
	{
		$target = $expr->var;
		if (!$target instanceof Expr\Variable || !is_string($target->name)) {
			throw new CompileError('compound assignment to non-locals is not supported');
		}
		$local = $f->locals[$target->name] ?? null;
		if ($local === null || $local->kind !== 'long') {
			throw new CompileError(sprintf('&=/|= on $%s needs an int local', $target->name));
		}
		$op = $expr instanceof Expr\AssignOp\BitwiseAnd ? '&' : '|';
		$value = $this->expr($f, $expr->expr);
		$f->line(sprintf('%s %s= %s;', $local->cName, $op, $this->asLong($f, $value, 'int', $f->label)));
		return Value::long($local->cName);
	}

	private function binaryOp(FunctionContext $f, Expr\BinaryOp $expr): Value
	{
		$native = $this->unit()->oracle->nativeType($expr);
		if ($expr instanceof Expr\BinaryOp\BooleanAnd || $expr instanceof Expr\BinaryOp\BooleanOr || $expr instanceof Expr\BinaryOp\LogicalAnd || $expr instanceof Expr\BinaryOp\LogicalOr) {
			$t = $f->temp('b');
			$f->line(sprintf('bool %s = %s;', $t, $this->condition($f, $expr->left)));
			$f->open(sprintf('if (%s%s)', $expr instanceof Expr\BinaryOp\BooleanAnd || $expr instanceof Expr\BinaryOp\LogicalAnd ? '' : '!', $t));
			$f->line(sprintf('%s = %s;', $t, $this->condition($f, $expr->right)));
			$f->close();
			return Value::bool($t);
		}

		if ($expr instanceof Expr\BinaryOp\Identical || $expr instanceof Expr\BinaryOp\NotIdentical) {
			foreach ([[$expr->left, $expr->right], [$expr->right, $expr->left]] as [$a, $b]) {
				if ($b instanceof Expr\Array_ && count($b->items) === 0 && $a instanceof Expr\Variable && is_string($a->name) && in_array(($f->locals[$a->name] ?? null)?->kind, ['vector', 'list'], true)) {
					$local = $f->locals[$a->name];
					$empty = $local->kind === 'vector' ? sprintf('(%s_count == 0)', $local->cName) : sprintf('(%s.size() == 0)', $local->cName);
					return Value::bool($expr instanceof Expr\BinaryOp\Identical ? $empty : '!' . $empty);
				}
			}
		}
		$left = $this->expr($f, $expr->left);
		if ($this->hasSideEffects($expr->right)) {
			$left = $this->stabilize($f, $left);
		}
		$right = $this->expr($f, $expr->right);

		if ($expr instanceof Expr\BinaryOp\Identical || $expr instanceof Expr\BinaryOp\NotIdentical) {
			$code = $this->identical($f, $left, $right, $expr->left, $expr->right);
			return Value::bool($expr instanceof Expr\BinaryOp\Identical ? $code : '!' . $code);
		}

		$longs = $left->kind === 'long' && $right->kind === 'long';
		$cmp = [
			Expr\BinaryOp\Greater::class => '>',
			Expr\BinaryOp\GreaterOrEqual::class => '>=',
			Expr\BinaryOp\Smaller::class => '<',
			Expr\BinaryOp\SmallerOrEqual::class => '<=',
		];
		if (isset($cmp[$expr::class])) {
			if ($longs) {
				return Value::bool(sprintf('(%s %s %s)', $left->code, $cmp[$expr::class], $right->code));
			}
			return Value::bool(sprintf('(zend_compare(%s, %s) %s 0)', $this->asZval($f, $left), $this->asZval($f, $right), $cmp[$expr::class]));
		}
		$bit = [
			Expr\BinaryOp\BitwiseAnd::class => '&',
			Expr\BinaryOp\BitwiseOr::class => '|',
			Expr\BinaryOp\BitwiseXor::class => '^',
		];
		if (isset($bit[$expr::class])) {
			if (!$longs) {
				// the engine's operator: string operands, the TypeError of an array
				$generic = [
					Expr\BinaryOp\BitwiseAnd::class => 'bitwise_and_function',
					Expr\BinaryOp\BitwiseOr::class => 'bitwise_or_function',
					Expr\BinaryOp\BitwiseXor::class => 'bitwise_xor_function',
				];
				return $this->arithmetic($f, $generic[$expr::class], $left, $right, $native);
			}
			return Value::long(sprintf('(%s %s %s)', $left->code, $bit[$expr::class], $right->code));
		}
		if ($expr instanceof Expr\BinaryOp\Equal || $expr instanceof Expr\BinaryOp\NotEqual) {
			$code = $this->looseEquals($f, $left, $right);
			return Value::bool($expr instanceof Expr\BinaryOp\Equal ? $code : '!' . $code);
		}
		if ($expr instanceof Expr\BinaryOp\Spaceship) {
			if ($longs) {
				return Value::long(sprintf('ZEND_THREEWAY_COMPARE(%s, %s)', $left->code, $right->code));
			}
			return Value::long(sprintf('(zend_long) zend_compare(%s, %s)', $this->asZval($f, $left), $this->asZval($f, $right)));
		}
		if ($expr instanceof Expr\BinaryOp\LogicalXor) {
			return Value::bool(sprintf('(%s != %s)', $this->asBool($left), $this->asBool($right)));
		}
		if ($expr instanceof Expr\BinaryOp\Concat) {
			$t = $f->temp('cat');
			$f->line(sprintf('zv::Val %s;', $t));
			$f->failIf(sprintf('!pgrt::concat(%s, %s, %s.raw())', $this->asZval($f, $left), $this->asZval($f, $right), $t));
			return new Value('zval', $t . '.raw()', $t);
		}
		$overflowing = [
			Expr\BinaryOp\Plus::class => ['addLong', 'add_function'],
			Expr\BinaryOp\Minus::class => ['subLong', 'sub_function'],
			Expr\BinaryOp\Mul::class => ['mulLong', 'mul_function'],
		];
		if (isset($overflowing[$expr::class])) {
			[$longOp, $generic] = $overflowing[$expr::class];
			if ($longs && $native->isInteger()->yes()) {
				$t = $f->temp('ar');
				$f->line(sprintf('zend_long %s;', $t));
				$f->failIf(sprintf('!pgrt::%s(%s, %s, &%s)', $longOp, $left->code, $right->code, $t));
				return Value::long($t);
			}
			return $this->arithmetic($f, $generic, $left, $right, $native);
		}
		$generic = [
			Expr\BinaryOp\Div::class => 'div_function',
			Expr\BinaryOp\Mod::class => 'mod_function',
			Expr\BinaryOp\Pow::class => 'pow_function',
			Expr\BinaryOp\ShiftLeft::class => 'shift_left_function',
			Expr\BinaryOp\BitwiseAnd::class => 'bitwise_and_function',
			Expr\BinaryOp\BitwiseOr::class => 'bitwise_or_function',
			Expr\BinaryOp\BitwiseXor::class => 'bitwise_xor_function',
		];
		if (isset($generic[$expr::class]) && !$longs) {
			return $this->arithmetic($f, $generic[$expr::class], $left, $right, $native);
		}
		if ($expr instanceof Expr\BinaryOp\Mod && $longs) {
			$t = $f->temp('md');
			$f->line(sprintf('zend_long %s;', $t));
			$f->failIf(sprintf('!pgrt::modLong(%s, %s, &%s)', $left->code, $right->code, $t));
			return Value::long($t);
		}
		if (isset($generic[$expr::class])) {
			return $this->arithmetic($f, $generic[$expr::class], $left, $right, $native);
		}
		if ($expr instanceof Expr\BinaryOp\ShiftRight) {
			if (!$longs) {
				throw new CompileError('>> on non-int operands is not supported');
			}
			$t = $f->temp('sh');
			$f->line(sprintf('zend_long %s;', $t));
			$f->failIf(sprintf('!pgrt::shiftRight(%s, %s, &%s)', $left->code, $right->code, $t));
			return Value::long($t);
		}
		throw new CompileError(sprintf('unsupported operator %s on line %d', $expr::class, $expr->getStartLine()));
	}

	/** $a == $b with PHP 8 semantics */
	private function looseEquals(FunctionContext $f, Value $left, Value $right): string
	{
		if ($left->kind === 'long' && $right->kind === 'long') {
			return sprintf('(%s == %s)', $left->code, $right->code);
		}
		if ($left->kind === 'bool' && $right->kind === 'bool') {
			return sprintf('(%s == %s)', $left->code, $right->code);
		}
		return sprintf('(zend_compare(%s, %s) == 0)', $this->asZval($f, $left), $this->asZval($f, $right));
	}

	/** a binary operator through the engine's operator function (any operand types, PHP's coercions and errors) */
	private function arithmetic(FunctionContext $f, string $function, Value $left, Value $right, Type $native): Value
	{
		$t = $f->temp('ar');
		$f->line(sprintf('zv::Val %s;', $t));
		$f->failIf(sprintf('%s(%s.raw(), %s, %s) != SUCCESS || EG(exception)', $function, $t, $this->asZval($f, $left), $this->asZval($f, $right)));
		return $this->narrowZval($t . '.raw()', $t, $native);
	}

	/** an owned zval result, as an unboxed scalar when its native type is one */
	private function narrowZval(string $code, string $temp, Type $native): Value
	{
		if ($native->isInteger()->yes()) {
			return Value::long(sprintf('Z_LVAL_P(%s)', $code));
		}
		if ($native->isBoolean()->yes()) {
			return Value::bool(sprintf('(Z_TYPE_P(%s) == IS_TRUE)', $code));
		}
		return new Value('zval', $code, $temp !== '' ? $temp : null, $this->knownClass($native), $native->isArray()->yes());
	}

	private function incDec(FunctionContext $f, Expr\PreInc|Expr\PreDec|Expr\PostInc|Expr\PostDec $expr): Value
	{
		$increment = $expr instanceof Expr\PreInc || $expr instanceof Expr\PostInc;
		$post = $expr instanceof Expr\PostInc || $expr instanceof Expr\PostDec;
		$var = $expr->var;
		if ($var instanceof Expr\Variable && is_string($var->name) && ($f->locals[$var->name] ?? null)?->kind === 'long') {
			$local = $f->locals[$var->name];
			$old = $f->temp('old');
			$f->line(sprintf('zend_long %s = %s;', $old, $local->cName));
			$f->failIf(sprintf('!pgrt::%s(%s, 1, &%s)', $increment ? 'addLong' : 'subLong', $old, $local->cName));
			return Value::long($post ? $old : $local->cName);
		}
		$ptr = $this->writablePtr($f, $var, false);
		$old = $f->temp('old');
		if ($post) {
			$f->line(sprintf('zv::Val %s = zv::Val::copyOf(zv::Ref(%s));', $old, $ptr));
		}
		$f->failIf(sprintf('%s(%s) != SUCCESS || EG(exception)', $increment ? 'increment_function' : 'decrement_function', $ptr));
		if ($post) {
			return $this->narrowZval($old . '.raw()', $old, $this->unit()->oracle->nativeType($expr));
		}
		return new Value('zval', $ptr);
	}

	private function cast(FunctionContext $f, Expr\Cast $expr): Value
	{
		$operand = $this->expr($f, $expr->expr);
		if ($expr instanceof Expr\Cast\Int_) {
			if ($operand->kind === 'long') {
				return $operand;
			}
			if ($operand->kind === 'bool') {
				return Value::long(sprintf('((zend_long) %s)', $operand->code));
			}
			$t = $f->temp('ci');
			$f->line(sprintf('zend_long %s = zval_get_long(%s);', $t, $this->asZval($f, $operand)));
			$f->failIf('EG(exception)');
			return Value::long($t);
		}
		if ($expr instanceof Expr\Cast\Bool_) {
			return Value::bool($this->asBool($operand));
		}
		$z = $this->asZval($f, $operand);
		$t = $f->temp('cv');
		$f->line(sprintf('zv::Val %s = zv::Val::copyOf(zv::Ref(%s));', $t, $z));
		if ($expr instanceof Expr\Cast\String_) {
			$f->failIf(sprintf('!pgrt::castString(%s.raw())', $t));
		} elseif ($expr instanceof Expr\Cast\Double) {
			$f->line(sprintf('convert_to_double(%s.raw());', $t));
		} elseif ($expr instanceof Expr\Cast\Array_) {
			$f->line(sprintf('convert_to_array(%s.raw());', $t));
		} elseif ($expr instanceof Expr\Cast\Object_) {
			$f->line(sprintf('convert_to_object(%s.raw());', $t));
		} else {
			throw new CompileError(sprintf('unsupported cast %s', $expr::class));
		}
		$f->failIf('EG(exception)');
		return new Value('zval', $t . '.raw()', $t, isArray: $expr instanceof Expr\Cast\Array_);
	}

	/**
	 * The zval an isset()/??/empty() operand reads, or NULL where PHP finds
	 * nothing — without the warnings a plain read would raise.
	 */
	private function issetPtr(FunctionContext $f, Expr $expr): string
	{
		$t = $f->temp('is');
		if ($expr instanceof Expr\Variable && is_string($expr->name)) {
			if ($expr->name === 'this') {
				$f->line(sprintf('zval *%s = &this_zv;', $t));
				return $t;
			}
			$local = $f->locals[$expr->name] ?? null;
			if ($local === null) {
				return 'NULL';
			}
			switch ($local->kind) {
				case 'zval':
					$f->line(sprintf('zval *%s = Z_ISUNDEF_P(%s.raw()) ? NULL : %s.raw();', $t, $local->cName, $local->cName));
					return $t;
				case 'ref':
					$f->line(sprintf('zval *%s = zv::Ref(%s).deref().raw();', $t, $local->cName));
					$f->line(sprintf('if (Z_ISUNDEF_P(%s)) %s = NULL;', $t, $t));
					return $t;
				case 'param':
					return $local->cName;
			}
			return $this->asZval($f, $this->readLocal($f, $expr->name));
		}
		if ($expr instanceof Expr\ArrayDimFetch && $expr->dim !== null) {
			$base = $this->issetPtr($f, $expr->var);
			$key = $this->asZval($f, $this->stabilize($f, $this->expr($f, $expr->dim)));
			$tmp = $f->temp('ist');
			$f->line(sprintf('zv::Val %s;', $tmp));
			$f->line(sprintf('zval *%s = %s == NULL ? NULL : pgrt::dimIs(%s, %s, %s.raw());', $t, $base, $base, $key, $tmp));
			$f->failIf('EG(exception)');
			return $t;
		}
		if ($expr instanceof Expr\PropertyFetch && $expr->name instanceof Node\Identifier && $expr->var instanceof Expr\Variable && $expr->var->name === 'this' && $f->self !== null) {
			// a declared property of this compiled class: its slot (unset or
			// uninitialized = UNDEF), unless magic methods take part
			$name = $expr->name->toString();
			$reflection = $this->unit()->reflection;
			$property = $reflection->hasProperty($name) ? $reflection->getProperty($name) : null;
			$slot = !$reflection->hasMethod('__get') && !$reflection->hasMethod('__isset') && $property !== null && !$property->isStatic()
				&& (!$property->isPrivate() || $property->getDeclaringClass()->getName() === $reflection->getName())
				? $this->slotExpr($this->unit()->className, $name, $f->self)
				: null;
			if ($slot !== null) {
				$f->line(sprintf('zval *%s = zv::Ref(OBJ_PROP_NUM(%s, %s)).deref().raw();', $t, $f->self, $slot));
				$f->line(sprintf('if (Z_ISUNDEF_P(%s)) %s = NULL;', $t, $t));
				return $t;
			}
		}
		if (($expr instanceof Expr\PropertyFetch || $expr instanceof Expr\NullsafePropertyFetch) && $expr->name instanceof Node\Identifier) {
			$name = $expr->name->toString();
			$base = $this->issetPtr($f, $expr->var);
			$tmp = $f->temp('ist');
			$f->line(sprintf('zv::Val %s;', $tmp));
			$f->line(sprintf('zval *%s = %s == NULL ? NULL : pgrt::propIs(%s, %s, %s, %s.raw());', $t, $base, $base, $this->unit()->ce, $this->literal($name)->code, $tmp));
			$f->failIf('EG(exception)');
			return $t;
		}
		if ($expr instanceof Expr\StaticPropertyFetch) {
			$slot = $this->staticPropertySlot($f, $expr);
			$f->line(sprintf('zval *%s = Z_ISUNDEF_P(%s) ? NULL : %s;', $t, $slot, $slot));
			return $t;
		}
		// any other expression is evaluated: it is set when it is not null
		$value = $this->stabilize($f, $this->expr($f, $expr));
		return $this->asZval($f, $value);
	}

	/** isset() of an issetPtr(): set and not null (the address of a temporary is never NULL) */
	private function setCheck(string $ptr): string
	{
		if (str_starts_with($ptr, '&')) {
			return sprintf('Z_TYPE_P(%s) > IS_NULL', $ptr);
		}

		return sprintf('%s != NULL && Z_TYPE_P(%s) > IS_NULL', $ptr, $ptr);
	}

	private function identical(FunctionContext $f, Value $left, Value $right, Expr $leftExpr, Expr $rightExpr): string
	{
		if ($left->kind === 'null' || $right->kind === 'null') {
			$other = $left->kind === 'null' ? $right : $left;
			if ($other->kind === 'null') {
				return 'true';
			}
			if ($other->kind !== 'zval') {
				return 'false';
			}
			return sprintf('(Z_TYPE_P(%s) == IS_NULL)', $other->code);
		}
		if ($left->kind === 'long' && $right->kind === 'long') {
			return sprintf('(%s == %s)', $left->code, $right->code);
		}
		if ($left->kind === 'bool' && $right->kind === 'bool') {
			return sprintf('(%s == %s)', $left->code, $right->code);
		}
		foreach ([[$left, $right, $rightExpr, $leftExpr], [$right, $left, $leftExpr, $rightExpr]] as [$a, $b, $bExpr, $aExpr]) {
			if ($b->kind === 'long' && $a->kind === 'zval') {
				return sprintf('(Z_TYPE_P(%s) == IS_LONG && Z_LVAL_P(%s) == %s)', $a->code, $a->code, $b->code);
			}
			if ($b->kind === 'bool' && $a->kind === 'zval') {
				return sprintf('(Z_TYPE_P(%s) == (%s ? IS_TRUE : IS_FALSE))', $a->code, $b->code);
			}
			if ($bExpr instanceof Expr\Array_ && count($bExpr->items) === 0) {
				if ($aExpr instanceof Expr\Variable && is_string($aExpr->name) && ($f->locals[$aExpr->name] ?? null)?->kind === 'vector') {
					return sprintf('(%s_count == 0)', $f->locals[$aExpr->name]->cName);
				}
				return sprintf('(Z_TYPE_P(%s) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(%s)) == 0)', $this->asZval($f, $a), $this->asZval($f, $a));
			}
		}
		if (in_array($left->kind, ['long', 'bool'], true) !== in_array($right->kind, ['long', 'bool'], true) && $left->kind !== 'zval' && $right->kind !== 'zval') {
			return 'false';
		}
		$l = $this->asZval($f, $left);
		$r = $this->asZval($f, $right);
		$leftObject = $left->class !== null || $this->unit()->oracle->nativeType($leftExpr)->isObject()->yes();
		$rightObject = $right->class !== null || $this->unit()->oracle->nativeType($rightExpr)->isObject()->yes();
		if ($leftObject && $rightObject) {
			return sprintf('(Z_OBJ_P(%s) == Z_OBJ_P(%s))', $l, $r);
		}
		return sprintf('zend_is_identical(%s, %s)', $l, $r);
	}

	private function ternary(FunctionContext $f, Expr\Ternary $expr): Value
	{
		if ($expr->if === null) {
			// `$a ?: $b`: $a evaluated once, kept when truthy
			$native = $this->unit()->oracle->nativeType($expr);
			$t = $f->temp('q');
			$f->line(sprintf('zv::Val %s = std::move(%s);', $t, $this->own($f, $this->expr($f, $expr->cond))));
			$f->open(sprintf('if (!zend_is_true(%s.raw()))', $t));
			$f->line(sprintf('%s = std::move(%s);', $t, $this->own($f, $this->expr($f, $expr->else))));
			$f->close();
			return new Value('zval', $t . '.raw()', $t, $this->knownClass($native), $native->isArray()->yes());
		}
		$cond = $this->condition($f, $expr->cond);
		$native = $this->unit()->oracle->nativeType($expr);
		if ($native->isInteger()->yes() && !$this->hasHoistedWork($expr->if) && !$this->hasHoistedWork($expr->else)) {
			$a = $this->expr($f, $expr->if);
			$b = $this->expr($f, $expr->else);
			if ($a->kind === 'long' && $b->kind === 'long') {
				return Value::long(sprintf('(%s ? %s : %s)', $cond, $a->code, $b->code));
			}
		}
		$t = $f->temp('q');
		if ($native->isInteger()->yes()) {
			$f->line(sprintf('zend_long %s;', $t));
			$f->open(sprintf('if (%s)', self::unparen($cond)));
			$f->line(sprintf('%s = %s;', $t, $this->asLong($f, $this->expr($f, $expr->if), 'int', $f->label)));
			$f->close(' else {');
			$f->open('');
			$f->line(sprintf('%s = %s;', $t, $this->asLong($f, $this->expr($f, $expr->else), 'int', $f->label)));
			$f->close();
			$f->line('}');
			return Value::long($t);
		}
		$f->line(sprintf('zv::Val %s;', $t));
		$f->open(sprintf('if (%s)', self::unparen($cond)));
		$f->line(sprintf('%s = std::move(%s);', $t, $this->own($f, $this->expr($f, $expr->if))));
		$f->close(' else {');
		$f->open('');
		$f->line(sprintf('%s = std::move(%s);', $t, $this->own($f, $this->expr($f, $expr->else))));
		$f->close();
		$f->line('}');
		return new Value('zval', $t . '.raw()', $t, $this->knownClass($native), $native->isArray()->yes());
	}

	/** whether lowering emits statements (anything but constants, locals and operators over them) */
	private function hasHoistedWork(Expr $expr): bool
	{
		foreach ($this->finder->find([$expr], static fn (Node $n): bool => true) as $node) {
			if (!($node instanceof Expr\Variable || $node instanceof Expr\ClassConstFetch || $node instanceof Node\Scalar || $node instanceof Expr\ConstFetch || $node instanceof Expr\BinaryOp\BitwiseAnd || $node instanceof Expr\BinaryOp\BitwiseOr || $node instanceof Node\Name || $node instanceof Node\Identifier)) {
				return true;
			}
		}
		return false;
	}

	private function hasSideEffects(Expr $expr): bool
	{
		return $this->finder->findFirst([$expr], static fn (Node $n): bool => ($n instanceof Expr\CallLike && !self::isPureBuiltinCall($n)) || $n instanceof Expr\Assign || $n instanceof Expr\AssignOp || $n instanceof Expr\AssignRef || $n instanceof Expr\PreInc || $n instanceof Expr\PreDec || $n instanceof Expr\PostInc || $n instanceof Expr\PostDec || $n instanceof Expr\Throw_) !== null;
	}

	/** a call of a builtin that only computes from its (by-value) arguments */
	private static function isPureBuiltinCall(Expr\CallLike $call): bool
	{
		if (!$call instanceof Expr\FuncCall || !$call->name instanceof Node\Name) {
			return false;
		}
		return in_array($call->name->toLowerString(), [
			'strtolower', 'strtoupper', 'ucfirst', 'lcfirst', 'trim', 'ltrim', 'rtrim', 'strlen', 'substr', 'str_replace',
			'sprintf', 'implode', 'count', 'is_int', 'is_string', 'is_bool', 'is_array', 'is_object', 'is_null', 'is_float',
			'array_keys', 'array_values', 'in_array', 'array_key_exists', 'spl_object_id', 'get_class', 'strpos', 'str_contains',
			'str_starts_with', 'str_ends_with', 'abs', 'min', 'max', 'intdiv', 'array_merge', 'array_slice', 'max', 'md5', 'sha1',
		], true);
	}

	/** pins a value so later side effects cannot change what it reads */
	private function stabilize(FunctionContext $f, Value $value): Value
	{
		if ($value->stable) {
			return $value;
		}
		if ($value->kind === 'long') {
			if (preg_replace('/^[A-Za-z_][A-Za-z0-9_]*$|^-?[0-9]+$/', '', $value->code) === '') {
				return $value;
			}
			$t = $f->temp('k');
			$f->line(sprintf('zend_long %s = %s;', $t, $value->code));
			return Value::long($t);
		}
		if ($value->kind === 'bool') {
			$t = $f->temp('k');
			$f->line(sprintf('bool %s = %s;', $t, $value->code));
			return Value::bool($t);
		}
		if ($value->kind === 'zval' && $value->temp === null && !str_contains($value->code, 'pg_lit') && $value->code !== '&this_zv') {
			$t = $this->own($f, $value);
			return new Value('zval', $t . '.raw()', $t, $value->class, $value->isArray);
		}
		return $value;
	}

	private function newExpr(FunctionContext $f, Expr\New_ $expr): Value
	{
		if ($expr->class instanceof Node\Name && $expr->class->toLowerString() === 'static' && !$this->unit()->reflection->isFinal()) {
			// new static in a non-final class: $this's class, or a static
			// method's called scope (such a method is always entered through
			// the engine, see usesCalledScope())
			$t = $f->temp('n');
			$ce = $f->temp('nce');
			if ($f->self !== null) {
				$f->line(sprintf('zend_class_entry *%s = %s->ce;', $ce, $f->self));
			} else {
				$f->line(sprintf('zend_class_entry *%s = zend_get_called_scope(EG(current_execute_data));', $ce));
				$f->line(sprintf('if (%s == NULL) %s = %s;', $ce, $ce, $this->unit()->ce));
			}
			[$argv, $argc, $named] = $this->engineArgs($f, $expr->args);
			$f->line(sprintf('zv::Val %s;', $t));
			$f->failIf(sprintf('!pgrt::newObject(%s, %s, %s, %s.raw(), %s)', $ce, $argc, $argv, $t, $named));
			return new Value('zval', $t . '.raw()', $t, $this->unit()->className);
		}
		if ($expr->class instanceof Expr) {
			// new $class(...): the class (an object's, or a name, autoloaded) before the arguments
			$t = $f->temp('n');
			$ce = $f->temp('nce');
			$f->line(sprintf('zend_class_entry *%s = pgrt::fetchClassDynamic(%s);', $ce, $this->asZval($f, $this->expr($f, $expr->class))));
			$f->failIf(sprintf('%s == NULL', $ce));
			[$argv, $argc, $named] = $this->engineArgs($f, $expr->args);
			$f->line(sprintf('zv::Val %s;', $t));
			$f->failIf(sprintf('!pgrt::newObject(%s, %s, %s, %s.raw(), %s)', $ce, $argc, $argv, $t, $named));
			return new Value('zval', $t . '.raw()', $t, $this->knownClass($this->unit()->oracle->nativeType($expr)));
		}
		$className = $this->className($expr->class);
		if ($this->isVendoredShadow($className)) {
			// a shadowed vendor class (a php-parser node): the class its name
			// means at run time — the native one in production, the PHP
			// original beside it in the differential tests, where PHP code
			// type-hinting the name receives the object
			$t = $f->temp('n');
			$site = $this->site('pgrt::ClassSite', 'cls_' . $className);
			$ce = $f->temp('nce');
			$f->line(sprintf('zend_class_entry *%s = pgrt::lookupClass(%s, "%s", %d);', $ce, $site, addcslashes($className, '\\'), strlen($className)));
			$f->failIf(sprintf('%s == NULL', $ce));
			[$argv, $argc, $named] = $this->engineArgs($f, $expr->args);
			$f->line(sprintf('zv::Val %s;', $t));
			$f->failIf(sprintf('!pgrt::newObject(%s, %s, %s, %s.raw(), %s)', $ce, $argc, $argv, $t, $named));
			return new Value('zval', $t . '.raw()', $t, $className);
		}
		$unit = $this->units[strtolower($className)] ?? null;
		if ($unit !== null && !isset($unit->methods['__construct']) && $unit->reflection->getConstructor() !== null) {
			// an inherited constructor: the engine runs it
			$unit = null;
		}
		$t = $f->temp('n');
		if ($unit !== null) {
			$constructor = $unit->methods['__construct'] ?? null;
			$args = $constructor !== null ? $this->directArgs($f, $constructor, $expr->args) : [];
			$f->line(sprintf('zval %s_raw;', $t));
			$f->failIf(sprintf('object_init_ex(&%s_raw, %s) != SUCCESS', $t, $unit->ce));
			$f->line(sprintf('zv::Val %s = zv::Val::adopt(%s_raw);', $t, $t));
			if ($constructor !== null) {
				$call = sprintf('pgen::%s::%s(%s)', $unit->stem, $this->cppMethodName($constructor), implode(', ', ['Z_OBJ_P(' . $t . '.raw())', ...$args]));
				$f->line(sprintf('if (UNEXPECTED(!%s)) { zend_object_store_ctor_failed(Z_OBJ_P(%s.raw())); %s }', $call, $t, $f->fail));
			}
			return new Value('zval', $t . '.raw()', $t, $className);
		}
		$native = $this->nativeNew($f, $className, $expr, $t);
		if ($native !== null) {
			return $native;
		}
		[$argv, $argc, $named] = $this->engineArgs($f, $expr->args);
		$ce = $this->classEntry($className, $f);
		$f->line(sprintf('zv::Val %s;', $t));
		$f->failIf(sprintf('!pgrt::newObject(%s, %s, %s, %s.raw(), %s)', $ce, $argc, $argv, $t, $named));
		return new Value('zval', $t . '.raw()', $t, $className);
	}

	/**
	 * new C(...) of a hand-written port through its `pt_<c>_new()` export
	 * (support.h): the C++ parameters carry the constructor's parameter
	 * names, so positional and named arguments map onto them; an omitted one
	 * takes the C++ default (`NULL` / `-1` being the ports' "default").
	 * Conversions need a proven type: bool, int, string; `int` stands for a
	 * `?bool` as -1/0/1.
	 */
	private function nativeNew(FunctionContext $f, string $className, Expr\New_ $expr, string $t): ?Value
	{
		if (!class_exists($className) || $this->shadowCe($className) === null) {
			return null;
		}
		$reflection = new ReflectionClass($className);
		$constructor = $reflection->getConstructor();
		if ($constructor === null) {
			return null;
		}
		$short = $reflection->getShortName();
		$entry = $this->nativeNewEntries()['pt_' . self::snake($short) . '_new'] ?? null;
		if ($entry === null) {
			return null;
		}
		$phpParameters = [];
		foreach ($constructor->getParameters() as $parameter) {
			if ($parameter->isVariadic() || $parameter->isPassedByReference()) {
				return null;
			}
			$phpParameters[$parameter->getName()] = $parameter;
		}
		$byName = [];
		$positional = array_keys($phpParameters);
		foreach ($expr->args as $i => $arg) {
			if (!$arg instanceof Node\Arg || $arg->unpack || $arg->byRef) {
				return null;
			}
			$parameter = $arg->name !== null ? $arg->name->toString() : ($positional[$i] ?? null);
			if ($parameter === null || !isset($phpParameters[$parameter]) || isset($byName[$parameter])) {
				return null;
			}
			$byName[$parameter] = $arg;
		}
		foreach ($entry['params'] as $param) {
			if (!isset($phpParameters[$param['name']])) {
				return null;
			}
		}
		if (count($entry['params']) !== count($phpParameters)) {
			return null;
		}
		// everything decided before any argument is evaluated
		foreach ($entry['params'] as $param) {
			if (isset($byName[$param['name']])) {
				if (!$this->entryArgumentConvertible($param['type'], $byName[$param['name']]->value)) {
					return null;
				}
				// (an argument not proven to be an object gets the engine's TypeError)
				if ($param['type'] === 'zval' && $this->needsObjectGuard($constructor, $param['name'], 0, $byName[$param['name']]->value)) {
					return null;
				}
			} elseif ($param['default'] === null) {
				return null;
			}
		}
		$sideEffects = $this->callHasSideEffects($expr->args);
		$evaluated = [];
		foreach ($byName as $parameter => $arg) {
			$value = $this->expr($f, $arg->value);
			$evaluated[$parameter] = [$sideEffects ? $this->stabilize($f, $value) : $value, $this->unit()->oracle->nativeType($arg->value)];
		}
		$codes = [];
		$pendingDefaults = [];
		foreach ($entry['params'] as $param) {
			if (!isset($evaluated[$param['name']])) {
				if ($param['default'] === null) {
					return null;
				}
				$pendingDefaults[] = $param['default'];
				continue;
			}
			[$value, $type] = $evaluated[$param['name']];
			$code = $this->nativeNewArgument($f, $param['type'], $value, $type);
			if ($code === null) {
				return null;
			}
			if ($param['type'] === 'zval' && ($entry['nullAsNull'] || $param['default'] === 'NULL') && $phpParameters[$param['name']]->allowsNull()) {
				// the ports take a nullable parameter's null as NULL
				$code = sprintf('(Z_TYPE_P(%1$s) == IS_NULL ? NULL : %1$s)', $code);
			}
			$codes = [...$codes, ...$pendingDefaults, $code];
			$pendingDefaults = [];
		}
		if ($entry['kind'] === 'out') {
			$f->line(sprintf('zv::Val %s;', $t));
			$f->failIf(sprintf('!%s(%s)', $entry['function'], implode(', ', [$t . '.raw()', ...$codes])));
		} else {
			$f->line(sprintf('zv::Val %s = %s(%s);', $t, $entry['function'], implode(', ', $codes)));
			$f->failIf(sprintf('%s.isUndef()', $t));
		}
		return new Value('zval', $t . '.raw()', $t, $className);
	}

	/** an argument converted to a native constructor export's parameter type; null when not proven */
	private function nativeNewArgument(FunctionContext $f, string $type, Value $value, Type $native): ?string
	{
		switch ($type) {
			case 'zval':
				return $this->asZval($f, $value);
			case 'objarg':
				return $native->isObject()->yes() ? sprintf('Z_OBJ_P(%s)', $this->asZval($f, $value)) : null;
			case 'bool':
				if ($value->kind === 'bool') {
					return $value->code;
				}
				return $native->isBoolean()->yes() ? sprintf('(Z_TYPE_P(%s) == IS_TRUE)', $this->asZval($f, $value)) : null;
			case 'long':
				if ($value->kind === 'long') {
					return $value->code;
				}
				return $native->isInteger()->yes() ? sprintf('Z_LVAL_P(%s)', $this->asZval($f, $value)) : null;
			case 'string':
				return $native->isString()->yes() ? sprintf('Z_STR_P(%s)', $this->asZval($f, $value)) : null;
			case 'nstring':
				if (!TypeCombinator::removeNull($native)->isString()->yes()) {
					return null;
				}
				$z = $this->asZval($f, $value);
				return sprintf('(Z_TYPE_P(%s) == IS_STRING ? Z_STR_P(%s) : NULL)', $z, $z);
			case 'tri':
				if ($value->kind === 'bool') {
					return sprintf('(%s ? 1 : 0)', $value->code);
				}
				if (!$native->isNull()->or($native->isBoolean())->yes() && !TypeCombinator::removeNull($native)->isBoolean()->yes()) {
					return null;
				}
				$z = $this->asZval($f, $value);
				return sprintf('(Z_TYPE_P(%s) == IS_NULL ? -1 : (Z_TYPE_P(%s) == IS_TRUE ? 1 : 0))', $z, $z);
		}
		return null;
	}

	/**
	 * The `new` exports of the hand-written ports: `zv::Val pt_<c>_new(...)`
	 * or `bool pt_<c>_new(zval *out, ...)` with parameters named like the
	 * constructor's.
	 *
	 * @return array<string, array{function: string, kind: string, params: list<array{name: string, type: string, default: string|null}>, nullAsNull: bool}>
	 */
	private function nativeNewEntries(): array
	{
		if ($this->nativeNewEntries !== null) {
			return $this->nativeNewEntries;
		}
		$this->nativeNewEntries = [];
		$code = (string) file_get_contents(__DIR__ . '/../../../../turbo-ext/src/support.h');
		preg_match_all('~(/\*(?:(?!\*/).)*\*/\s*)?^(?:\[\[nodiscard\]\] )?(zv::Val|bool)\s*(pt_\w+_new)\(([^()]*)\);~ms', $code, $matches, PREG_SET_ORDER);
		foreach ($matches as [, $comment, $return, $function, $parameters]) {
			$list = $parameters === '' ? [] : explode(', ', $parameters);
			$kind = 'val';
			if ($return === 'bool') {
				if (($list[0] ?? null) !== 'zval *out') {
					continue;
				}
				array_shift($list);
				$kind = 'out';
			}
			$params = [];
			foreach ($list as $parameter) {
				if (preg_match('~^(zval \*|bool |zend_string \*|zend_long |int )(\w+)(?: = (\S+))?$~', $parameter, $m) !== 1) {
					continue 2;
				}
				$types = ['zval *' => 'zval', 'bool ' => 'bool', 'zend_string *' => 'string', 'zend_long ' => 'long', 'int ' => 'tri'];
				$params[] = ['name' => $m[2], 'type' => $types[$m[1]], 'default' => $m[3] ?? null];
			}
			$this->nativeNewEntries[$function] = ['function' => $function, 'kind' => $kind, 'params' => $params, 'nullAsNull' => preg_match('~(?<!IS_)NULL (?:for|=|is|as) (?:a )?null~i', $comment) === 1];
		}
		return $this->nativeNewEntries;
	}

	private function methodCall(FunctionContext $f, Expr\MethodCall $expr): Value
	{
		if (!$expr->name instanceof Node\Identifier) {
			// $object->{$name}(...): the receiver, then the name, then the arguments
			$object = $this->asZval($f, $this->stabilize($f, $this->expr($f, $expr->var)));
			$methodName = $this->asZval($f, $this->stabilize($f, $this->expr($f, $expr->name)));
			[$argv, $argc, $named] = $this->engineArgs($f, $expr->args);
			$t = $f->temp('r');
			$f->line(sprintf('zv::Val %s;', $t));
			$f->failIf(sprintf('!pgrt::callMethodDynamic(%s, %s, %s, %s, %s, %s.raw(), %s)', $object, $this->unit()->ce, $methodName, $argc, $argv, $t, $named));
			return $this->narrowZval($t . '.raw()', $t, $this->unit()->oracle->nativeType($expr));
		}
		$name = $expr->name->toString();
		$receiver = $this->expr($f, $expr->var);
		$class = $this->receiverClass($receiver, $this->unit()->oracle->nativeType($expr->var));
		$unit = $class !== null ? ($this->units[strtolower($class)] ?? null) : null;
		$method = $unit?->methods[strtolower($name)] ?? null;
		if ($method !== null && !$method->isStatic && $method->direct) {
			if ($this->callHasSideEffects($expr->args)) {
				$receiver = $this->stabilize($f, $receiver);
			}
			$args = $this->directArgs($f, $method, $expr->args);
			return $this->emitDirectCall($f, $method, sprintf('Z_OBJ_P(%s)', $receiver->code), $args);
		}
		if ($this->callHasSideEffects($expr->args)) {
			$receiver = $this->stabilize($f, $receiver);
		}
		// without a native class, speculate on the PHPDoc one: compiled
		// implementations are entered by the receiver's exact class and the
		// direct entries dispatch on it themselves, so either is guarded
		$dispatchClass = $class ?? $this->knownClass($this->unit()->oracle->phpDocType($expr->var));
		// a TypeOps.h operation dispatches every registered class — hand
		// ports and compiled twins alike — through its op entry, which the
		// per-class checks of virtualDispatch() would only partly cover
		$isOp = $this->plainArgs($expr->args) !== null && $this->opFor($name, (string) count($expr->args)) !== null;
		if ($dispatchClass !== null && !$isOp) {
			$dispatched = $this->virtualDispatch($f, $dispatchClass, $name, $receiver, $expr);
			if ($dispatched !== null) {
				return $dispatched;
			}
		}
		$intrinsic = $this->nodeIntrinsic($f, $class, $name, $receiver, $expr);
		if ($intrinsic !== null) {
			return $intrinsic;
		}
		if ($class !== null && strtolower($name) === 'create' && is_a($class, ExpressionResultFactory::class, true)) {
			$created = $this->expressionResultCreate($f, $receiver, $expr);
			if ($created !== null) {
				return $created;
			}
		}
		if ($dispatchClass !== null && !$isOp) {
			$native = $this->unit()->oracle->nativeType($expr);
			$entry = $this->nativeEntryCall($f, $dispatchClass, $name, $receiver, $expr->args, $native);
			if ($entry !== null) {
				return $entry;
			}
		}
		if (!$isOp) {
			// an interface or intersection receiver ($scope): the entries of
			// the native classes implementing it, by the object's exact class
			$receiverNames = $class !== null ? [$class] : $this->unit()->oracle->nativeType($expr->var)->getObjectClassNames();
			if ($receiverNames === []) {
				$receiverNames = $this->unit()->oracle->phpDocType($expr->var)->getObjectClassNames();
			}
			if ($receiverNames !== []) {
				$implemented = $this->implementorEntryCall($f, $receiverNames, $name, $receiver, $expr->args, $this->unit()->oracle->nativeType($expr));
				if ($implemented !== null) {
					return $implemented;
				}
			}
			if (count($receiverNames) === 1 && $expr->args === []) {
				$getter = $this->trivialGetterCall($f, $receiverNames[0], $name, $receiver, $this->unit()->oracle->nativeType($expr));
				if ($getter !== null) {
					return $getter;
				}
			}
		}
		$key = ($class ?? '?') . '->' . $name;
		$this->engineCalls[$key] = ($this->engineCalls[$key] ?? 0) + 1;
		[$argv, $argc, $named] = $this->engineArgs($f, $expr->args, $dispatchClass !== null ? $this->methodReflection($dispatchClass, $name) : null);
		$t = $f->temp('r');
		$f->line(sprintf('zv::Val %s;', $t));
		$op = $this->opFor($name, $argc);
		$z = $this->asZval($f, $receiver);
		if ($op !== null && $named === 'NULL') {
			// a hot operation (TypeOps.h): the receiver class's registered
			// direct entry, the engine for any other class
			$f->open(sprintf('if (EXPECTED(Z_TYPE_P(%s) == IS_OBJECT))', $z));
			$f->line(sprintf('%s = pt_type_op(Z_OBJ_P(%s), %s, %s, %s);', $t, $z, $op, $argc, $argv));
			$f->failIf(sprintf('%s.isUndef()', $t));
			$f->close(' else {');
			$f->open('');
		}
		if ($argc === '0' && $named === 'NULL') {
			// a getter or a constant answer of the receiver's class is read without the call
			$site = $this->site('pgrt::MethodShapeSite', sprintf('method0_%s_%d', $name, count($this->siteNames)));
			$f->failIf(sprintf('!pgrt::callMethod0(%s, %s, "%s", "%s", %d, %s.raw())', $site, $z, $name, strtolower($name), strlen($name), $t));
		} else {
			$site = $this->site('pt_method_site', sprintf('method_%s_%d', $name, count($this->siteNames)));
			$f->failIf(sprintf('!pgrt::callMethod(%s, %s, "%s", "%s", %d, %s, %s, %s.raw(), %s)', $site, $z, $name, strtolower($name), strlen($name), $argc, $argv, $t, $named));
		}
		if ($op !== null && $named === 'NULL') {
			$f->close();
			$f->line('}');
		}
		$this->flushWriteBacks($f);
		return $this->fromEngineResult($t, $this->unit()->oracle->nativeType($expr));
	}

	/**
	 * A php-parser node method the parser visitors call per node, done by
	 * the runtime on the node's slots (pgrt::node*, guarded per class
	 * against overrides, the engine otherwise). Applies to receivers of an
	 * unknown class too — the guard decides at run time.
	 */
	private function nodeIntrinsic(FunctionContext $f, ?string $class, string $name, Value $receiver, Expr\MethodCall $expr): ?Value
	{
		$table = [
			'setattribute' => [NodeAbstract::class, 'nodeSetAttribute', 2, 2],
			'getattribute' => [NodeAbstract::class, 'nodeGetAttribute', 1, 2],
			'hasattribute' => [NodeAbstract::class, 'nodeHasAttribute', 1, 1],
			'tostring' => [[Node\Name::class, Node\Identifier::class], 'nameToString', 0, 0],
			'tolowerstring' => [[Node\Name::class, Node\Identifier::class], 'nameToLowerString', 0, 0],
			'isfirstclasscallable' => [Expr\CallLike::class, 'callIsFirstClassCallable', 0, 0],
			'getargs' => [Expr\CallLike::class, 'callGetArgs', 0, 0],
		];
		$entry = $table[strtolower($name)] ?? null;
		if ($entry === null) {
			return null;
		}
		[$declaring, $helper, $min, $max] = $entry;
		if ($class !== null) {
			$matches = false;
			foreach ((array) $declaring as $candidate) {
				if (!is_a($class, $candidate, true) && !is_a($candidate, $class, true)) {
					continue;
				}

				$matches = true;
			}
			if (!$matches) {
				return null;
			}
		}
		$count = count($expr->args);
		if ($count < $min || $count > $max) {
			return null;
		}
		foreach ($expr->args as $arg) {
			if (!$arg instanceof Node\Arg || $arg->unpack || $arg->name !== null || $arg->byRef) {
				return null;
			}
		}
		$sideEffects = $this->callHasSideEffects($expr->args);
		if ($sideEffects) {
			$receiver = $this->stabilize($f, $receiver);
		}
		$args = [];
		foreach ($expr->args as $arg) {
			$value = $this->expr($f, $arg->value);
			$args[] = $this->asZval($f, $sideEffects ? $this->stabilize($f, $value) : $value);
		}
		if ($helper === 'nodeGetAttribute' && count($args) === 1) {
			$args[] = 'NULL';
		}
		$site = $this->site('pt_method_site', sprintf('method_%s_%d', $name, count($this->siteNames)));
		$t = $f->temp('r');
		$f->line(sprintf('zv::Val %s;', $t));
		$f->failIf(sprintf('!pgrt::%s(%s)', $helper, implode(', ', array_merge([$site, $this->asZval($f, $receiver)], $args, [$t . '.raw()']))));
		return $this->fromEngineResult($t, $this->unit()->oracle->nativeType($expr));
	}

	/**
	 * A call of a hand-written port's direct entry instead of the method:
	 * `pt_<class>_<method>(zval *receiver, zval *arg...)` (support.h and the
	 * other headers) is the native body for the native class and the method
	 * by name for any other — the convention every engine-port entry
	 * follows. The shapes recognised are nativeEntries()'s. Looked up for the
	 * receiver's class and its ancestors (the same virtual method), only for
	 * positional arguments and no by-reference parameter. A static method's
	 * entry takes no receiver. A non-object receiver goes through the engine
	 * (its Error).
	 *
	 * @param array<Node\Arg|Node\VariadicPlaceholder> $callArgs
	 */
	private function nativeEntryCall(FunctionContext $f, string $class, string $name, ?Value $receiver, array $callArgs, Type $native): ?Value
	{
		$args = $this->plainArgs($callArgs);
		$spread = false;
		$named = false;
		if ($args === null) {
			// a variadic method's call may unpack (`merge(...$dependencies)`),
			// a static one's name its arguments (`exit(..., name: $x)`)
			$args = [];
			foreach ($callArgs as $arg) {
				if (!$arg instanceof Node\Arg || $arg->byRef || ($arg->unpack && $arg->name !== null)) {
					return null;
				}
				$spread = $spread || $arg->unpack;
				$named = $named || $arg->name !== null;
				$args[] = $arg;
			}
			if ($spread && $named) {
				return null;
			}
		}
		if ($named && $receiver !== null) {
			return null;
		}
		if (!class_exists($class) && !interface_exists($class)) {
			return null;
		}
		$reflection = $this->methodReflection($class, $name);
		if ($reflection === null || $reflection->isStatic() !== ($receiver === null)) {
			return null;
		}
		$variadicMethod = false;
		foreach ($reflection->getParameters() as $parameter) {
			if ($parameter->isPassedByReference()) {
				return null;
			}
			if (!$parameter->isVariadic()) {
				continue;
			}

			$variadicMethod = true;
		}
		// (an unpacking call only of a static method: the engine fallback of an instance call takes plain values)
		if (($variadicMethod && count($reflection->getParameters()) !== 1) || ($spread && (!$variadicMethod || $receiver !== null))) {
			return null;
		}
		$returnsVoid = (string) $reflection->getReturnType() === 'void';
		$entries = $this->nativeEntries();
		$found = null;
		$classReflection = new ReflectionClass($class);
		$candidates = [$classReflection, ...array_values($classReflection->getInterfaces())];
		for ($parent = $classReflection->getParentClass(); $parent !== false; $parent = $parent->getParentClass()) {
			$candidates[] = $parent;
		}
		foreach ($candidates as $candidate) {
			if (!$candidate->hasMethod($name)) {
				continue;
			}
			$snake = 'pt_' . self::snake($candidate->getShortName()) . '_' . self::snake($name);
			$entry = $entries[$snake] ?? null;
			if ($entry === null && str_starts_with($name, 'get') && strlen($name) > 3) {
				// the borrowed slot readers drop the getter's prefix
				$snake = 'pt_' . self::snake($candidate->getShortName()) . '_' . self::snake(lcfirst(substr($name, 3)));
				$entry = $entries[$snake] ?? null;
				// (and the value getters named like them: pt_expression_result_variable_flow())
				if ($entry !== null && !in_array($entry['kind'], ['hold', 'boolout'], true) && !($entry['kind'] === 'val' && count($entry['params']) === 1 && $entry['variadic'] === null)) {
					$entry = null;
				}
			}
			if ($entry === null || ($entry['kind'] === 'status' && !$returnsVoid) || ($entry['variadic'] !== null) !== $variadicMethod) {
				continue;
			}
			// (the reader shapes take a receiver)
			if ($receiver === null && in_array($entry['kind'], ['hold', 'boolout'], true)) {
				continue;
			}
			$params = $entry['params'];
			$exactCe = null;
			if ($receiver !== null) {
				$first = array_shift($params);
				if ($first === null || !in_array($first['type'], ['zval', 'obj'], true)) {
					continue;
				}
				if ($first['type'] === 'obj') {
					$exactCe = $this->shadowCe($candidate->getName());
					if ($exactCe === null) {
						continue;
					}
				}
			} elseif (($params[0]['type'] ?? null) === 'obj') {
				continue;
			}
			if (!$variadicMethod) {
				// the arguments onto the entry's parameters: by position, or
				// by name — the entries carry the method's parameter names
				$phpNames = array_map(static fn (ReflectionParameter $p): string => $p->getName(), $reflection->getParameters());
				$assigned = [];
				foreach ($args as $i => $arg) {
					$index = $arg->name === null ? $i : array_search($arg->name->toString(), array_column($params, 'name'), true);
					if ($index === false || !isset($params[$index]) || isset($assigned[$index]) || ($arg->name !== null && !in_array($arg->name->toString(), $phpNames, true))) {
						continue 2;
					}
					$assigned[$index] = $arg;
				}
				foreach ($params as $index => $param) {
					if (!isset($assigned[$index]) && $param['default'] === null) {
						continue 2;
					}
				}
			}
			$found = [$snake, $entry, $params, $exactCe];
			break;
		}
		if ($found === null) {
			return $receiver !== null && $args === [] ? $this->memberDispatchCall($f, $classReflection, $name, $receiver, $native, $returnsVoid) : null;
		}
		[$function, $entry, $params, $exactCe] = $found;
		$kind = $entry['kind'];
		$sideEffects = $this->callHasSideEffects($callArgs);
		$values = [];
		$codes = [];
		$guards = [];
		if ($variadicMethod) {
			foreach ($spread ? [] : $args as $arg) {
				$value = $this->expr($f, $arg->value);
				$values[] = $this->asZval($f, $sideEffects ? $this->stabilize($f, $value) : $value);
			}
		} else {
			// convertible first (no code emitted for a call that falls back),
			// then evaluated in source order
			$paramNames = array_column($params, 'name');
			foreach ($args as $i => $arg) {
				$index = $arg->name === null ? $i : (int) array_search($arg->name->toString(), $paramNames, true);
				if ($params[$index]['type'] === 'enum' && $this->enumArgument($params[$index], $class, $arg->value) === null) {
					return null;
				}
				if (!$this->entryArgumentConvertible($params[$index]['type'], $arg->value)) {
					return null;
				}
			}
			$converted = [];
			$holders = $this->holderPositions($args, $reflection);
			foreach ($args as $i => $arg) {
				$index = $arg->name === null ? $i : (int) array_search($arg->name->toString(), $paramNames, true);
				if ($params[$index]['type'] === 'enum') {
					$converted[$index] = (string) $this->enumArgument($params[$index], $class, $arg->value);
					continue;
				}
				$this->closureAsHolder = isset($holders[$i]);
				$value = $this->expr($f, $arg->value);
				$this->closureAsHolder = false;
				if ($sideEffects) {
					$value = $this->stabilize($f, $value);
				}
				$values[] = $this->asZval($f, $value);
				$converted[$index] = (string) $this->nativeNewArgument($f, $params[$index]['type'], $value, $this->unit()->oracle->nativeType($arg->value));
				if ($params[$index]['type'] === 'zval' && $this->needsObjectGuard($reflection, $params[$index]['name'], $index, $arg->value)) {
					// the TypeError the method's own parameter check raises for a
					// non-object, which the port does not check
					$z = $this->asZval($f, $value);
					$parameter = $this->methodParameter($reflection, $params[$index]['name'], $index);
					$guards[] = [
						$parameter !== null && $parameter->allowsNull()
							? sprintf('(Z_TYPE_P(%1$s) == IS_OBJECT || Z_TYPE_P(%1$s) == IS_NULL)', $z)
							: sprintf('Z_TYPE_P(%s) == IS_OBJECT', $z),
						sprintf(
							'zend_type_error("%s::%s(): Argument #%d ($%s) must be of type %s, %%s given", zend_zval_value_name(%s));',
							addcslashes($reflection->getDeclaringClass()->getName(), '\\'),
							$reflection->getName(),
							($parameter?->getPosition() ?? $index) + 1,
							$parameter?->getName() ?? $params[$index]['name'],
							addcslashes((string) $parameter?->getType(), '\\'),
							$z,
						),
					];
				}
				if ($params[$index]['type'] !== 'zval' || (!$entry['nullAsNull'] && $params[$index]['default'] !== 'NULL') || !$this->parameterAllowsNull($reflection, $params[$index]['name'], $index)) {
					continue;
				}

				// the ports take a nullable parameter's null as NULL
				$converted[$index] = sprintf('(Z_TYPE_P(%1$s) == IS_NULL ? NULL : %1$s)', $converted[$index]);
			}
			$pending = [];
			foreach ($params as $index => $param) {
				if (!isset($converted[$index])) {
					$pending[] = (string) $param['default'];
					continue;
				}
				$codes = [...$codes, ...$pending, $converted[$index]];
				$pending = [];
			}
			if ($named) {
				// (no engine fallback for a static call: $values is only the instance fallback's)
				$values = [];
			}
		}
		$z = $receiver !== null ? $this->asZval($f, $receiver) : null;
		if ($spread) {
			$vec = $this->argVector($f, $args);
			if ($entry['variadic'] === 'argvp') {
				$pointers = $f->temp('vp');
				$f->line(sprintf('pgrt::PtrVec %s;', $pointers));
				$f->line(sprintf('for (uint32_t i = 0; i < %s.size(); i++) %s.push(&%s.argv()[i]);', $vec, $pointers, $vec));
				$codes = [sprintf('%s.size()', $pointers), sprintf('%s.data()', $pointers)];
			} else {
				$codes = [sprintf('%s.size()', $vec), sprintf('%s.argv()', $vec)];
			}
		} elseif ($variadicMethod) {
			$argv = 'NULL';
			if ($values !== []) {
				$argv = $f->temp('vv');
				if ($entry['variadic'] === 'argvp') {
					$f->line(sprintf('zval *%s[%d] = {%s};', $argv, count($values), implode(', ', $values)));
				} else {
					$f->line(sprintf('zval %s[%d];', $argv, count($values)));
					foreach ($values as $i => $value) {
						$f->line(sprintf('ZVAL_COPY_VALUE(&%s[%d], %s);', $argv, $i, $value));
					}
				}
			}
			$codes = [(string) count($values), $argv];
		}
		$callArgs = [...($z !== null ? [$exactCe !== null ? sprintf('Z_OBJ_P(%s)', $z) : $z] : []), ...$codes];
		if (in_array($kind, ['hold', 'boolout'], true)) {
			return $this->readerEntryCall($f, $function, $kind, $name, (string) $z, $native, $exactCe);
		}
		$t = $f->temp('r');
		$f->line(sprintf('zv::Val %s;', $t));
		if ($z !== null) {
			$f->open(sprintf('if (EXPECTED(Z_TYPE_P(%s) == IS_OBJECT%s))', $z, $exactCe !== null ? sprintf(' && Z_OBJCE_P(%s) == %s', $z, $exactCe) : ''));
		} else {
			$f->open('');
		}
		foreach ($guards as [$condition, $error]) {
			$f->line(sprintf('if (UNEXPECTED(!(%s))) { %s %s }', $condition, $error, $f->fail));
		}
		switch ($kind) {
			case 'val':
				$f->line(sprintf('%s = %s(%s);', $t, $function, implode(', ', $callArgs)));
				$f->failIf(sprintf('%s.isUndef()', $t));
				break;
			case 'out':
				$f->failIf(sprintf('!%s(%s)', $function, implode(', ', [$t . '.raw()', ...$callArgs])));
				break;
			case 'status':
				$f->failIf(sprintf('!%s(%s)', $function, implode(', ', $callArgs)));
				$f->line(sprintf('ZVAL_NULL(%s.raw());', $t));
				break;
			default:
				// a borrowed object (a singleton)
				$o = $f->temp('bo');
				$f->line(sprintf('zend_object *%s = %s(%s);', $o, $function, implode(', ', $callArgs)));
				$f->failIf(sprintf('%s == NULL', $o));
				$f->line(sprintf('GC_ADDREF(%s);', $o));
				$f->line(sprintf('ZVAL_OBJ(%s.raw(), %s);', $t, $o));
		}
		if ($z !== null) {
			$f->close(' else {');
			$f->open('');
			$argv = 'NULL';
			if ($values !== []) {
				$argv = $f->temp('av');
				$f->line(sprintf('zval %s[%d];', $argv, count($values)));
				foreach ($values as $i => $value) {
					$f->line(sprintf('ZVAL_COPY_VALUE(&%s[%d], %s);', $argv, $i, $value));
				}
			}
			$site = $this->site('pt_method_site', sprintf('method_%s_%d', $name, count($this->siteNames)));
			$f->failIf(sprintf('!pgrt::callMethod(%s, %s, "%s", "%s", %d, %d, %s, %s.raw())', $site, $z, $name, strtolower($name), strlen($name), count($values), $argv, $t));
			$f->close();
			$f->line('}');
		} else {
			$f->close();
		}
		if ($returnsVoid) {
			return Value::void();
		}
		return $this->fromEngineResult($t, $native);
	}

	/** the method's parameter an entry parameter stands for: by name, else by position */
	private function methodParameter(ReflectionMethod $method, string $name, int $index): ?ReflectionParameter
	{
		foreach ($method->getParameters() as $candidate) {
			if ($candidate->getName() === $name) {
				return $candidate;
			}
		}
		return $method->getParameters()[$index] ?? null;
	}

	/** whether a class-typed parameter receives an argument not proven to be an object */
	private function needsObjectGuard(ReflectionMethod $method, string $name, int $index, Expr $arg): bool
	{
		$parameter = $this->methodParameter($method, $name, $index);
		$type = $parameter?->getType();
		if ($type === null) {
			return false;
		}
		$classTyped = $type instanceof ReflectionNamedType ? !$type->isBuiltin() || $type->getName() === 'object' : false;
		if ($type instanceof ReflectionUnionType) {
			$classTyped = true;
			foreach ($type->getTypes() as $member) {
				if (!($member instanceof ReflectionNamedType) || !$member->isBuiltin() || $member->getName() === 'null' || $member->getName() === 'object') {
					continue;
				}

				$classTyped = false;
			}
		}
		if (!$classTyped) {
			return false;
		}
		$native = TypeCombinator::removeNull($this->unit()->oracle->nativeType($arg));
		return !$native->isObject()->yes();
	}

	/**
	 * $receiver->name(...) of an interface or intersection type through the
	 * direct entries of the shadowed classes implementing it
	 * (`pt_<class>_<method>(zend_object *receiver, ...)`, taking exactly that
	 * class): one branch per class by the object's class entry, the engine
	 * for any other object. Positional arguments only, every one convertible
	 * for every entry.
	 *
	 * @param list<string> $receiverNames
	 * @param array<Node\Arg|Node\VariadicPlaceholder> $callArgs
	 */
	private function implementorEntryCall(FunctionContext $f, array $receiverNames, string $name, Value $receiver, array $callArgs, Type $native): ?Value
	{
		$args = $this->plainArgs($callArgs);
		if ($args === null) {
			return null;
		}
		$method = null;
		foreach ($receiverNames as $receiverName) {
			$method ??= $this->methodReflection($receiverName, $name);
		}
		if ($method === null || $method->isStatic()) {
			return null;
		}
		foreach ($method->getParameters() as $parameter) {
			if ($parameter->isPassedByReference() || $parameter->isVariadic()) {
				return null;
			}
		}
		$returnsVoid = (string) $method->getReturnType() === 'void';
		$entries = $this->nativeEntries();
		$branches = [];
		foreach ($this->shadowedClassNames() as $candidate) {
			if (!class_exists($candidate)) {
				continue;
			}
			$reflection = new ReflectionClass($candidate);
			if ($reflection->isAbstract()) {
				continue;
			}
			foreach ($receiverNames as $receiverName) {
				if (!is_a($candidate, $receiverName, true)) {
					continue 2;
				}
			}
			$ce = $this->shadowCe($candidate);
			if ($ce === null) {
				continue;
			}
			$entryName = 'pt_' . self::snake($reflection->getShortName()) . '_' . self::snake($name);
			$entry = $entries[$entryName] ?? null;
			if ($entry === null && $reflection->hasMethod($name)) {
				// an inherited method: the declaring class's export, which
				// the inherited internal method runs too
				$declaring = $reflection->getMethod($name)->getDeclaringClass();
				if ($declaring->getName() !== $reflection->getName() && $this->shadowCe($declaring->getName()) !== null) {
					$entryName = 'pt_' . self::snake($declaring->getShortName()) . '_' . self::snake($name);
					$entry = $entries[$entryName] ?? null;
				}
			}
			if ($entry === null || $entry['variadic'] !== null || ($entry['kind'] === 'status' && !$returnsVoid) || !in_array($entry['kind'], ['val', 'out', 'status', 'boolout'], true)) {
				continue;
			}
			$params = $entry['params'];
			$first = array_shift($params);
			if ($first === null || $first['type'] !== 'obj' || count($args) > count($params)) {
				continue;
			}
			$objectGuards = [];
			foreach ($params as $index => $param) {
				if (!isset($args[$index])) {
					if ($param['default'] === null) {
						continue 2;
					}
					continue;
				}
				if ($param['type'] === 'objarg' && !$this->entryArgumentConvertible('objarg', $args[$index]->value)) {
					// an object the native type does not prove (a PHPDoc-typed
					// node): the branch tests it, anything else takes the engine
					$objectGuards[] = $index;
					continue;
				}
				if ($param['type'] === 'enum' || !$this->entryArgumentConvertible($param['type'], $args[$index]->value)) {
					continue 2;
				}
			}
			$branches[] = [$ce, $entryName, $entry, $params, $objectGuards];
		}
		if ($branches === []) {
			return null;
		}
		$sideEffects = $this->callHasSideEffects($callArgs);
		$receiver = $this->stabilize($f, $receiver);
		$values = [];
		$evaluated = [];
		foreach ($args as $arg) {
			$value = $this->expr($f, $arg->value);
			if ($sideEffects) {
				$value = $this->stabilize($f, $value);
			}
			$evaluated[] = [$value, $this->unit()->oracle->nativeType($arg->value)];
			$values[] = $this->asZval($f, $value);
		}
		$z = $this->asZval($f, $receiver);
		$t = $f->temp('r');
		$f->line(sprintf('zv::Val %s;', $t));
		$rc = $f->temp('rc');
		$f->line(sprintf('zend_class_entry *%s = Z_TYPE_P(%s) == IS_OBJECT ? Z_OBJCE_P(%s) : NULL;', $rc, $z, $z));
		$first = true;
		foreach ($branches as [$ce, $entryName, $entry, $params, $objectGuards]) {
			$condition = sprintf('%s == %s', $rc, $ce);
			foreach ($objectGuards as $index) {
				$condition .= sprintf(' && Z_TYPE_P(zv::Ref(%s).deref().raw()) == IS_OBJECT', $values[$index]);
			}
			$f->open(sprintf('%sif (%s)', $first ? '' : 'else ', $condition));
			$first = false;
			$codes = [sprintf('Z_OBJ_P(%s)', $z)];
			$pending = [];
			foreach ($params as $index => $param) {
				if (!isset($evaluated[$index])) {
					$pending[] = (string) $param['default'];
					continue;
				}
				[$value, $type] = $evaluated[$index];
				$code = in_array($index, $objectGuards, true)
					? sprintf('Z_OBJ_P(zv::Ref(%s).deref().raw())', $values[$index])
					: (string) $this->nativeNewArgument($f, $param['type'], $value, $type);
				$codes = [...$codes, ...$pending, $code];
				$pending = [];
			}
			switch ($entry['kind']) {
				case 'val':
					$f->line(sprintf('%s = %s(%s);', $t, $entryName, implode(', ', $codes)));
					$f->failIf(sprintf('%s.isUndef()', $t));
					break;
				case 'out':
					$f->failIf(sprintf('!%s(%s)', $entryName, implode(', ', [$t . '.raw()', ...$codes])));
					break;
				case 'status':
					$f->failIf(sprintf('!%s(%s)', $entryName, implode(', ', $codes)));
					$f->line(sprintf('ZVAL_NULL(%s.raw());', $t));
					break;
				default:
					$out = $f->temp('bo');
					$f->line(sprintf('bool %s = false;', $out));
					$f->failIf(sprintf('!%s(%s, %s)', $entryName, $codes[0], $out));
					$f->line(sprintf('ZVAL_BOOL(%s.raw(), %s);', $t, $out));
			}
			$f->close();
		}
		$f->open('else');
		$argv = 'NULL';
		if ($values !== []) {
			$argv = $f->temp('av');
			$f->line(sprintf('zval %s[%d];', $argv, count($values)));
			foreach ($values as $i => $value) {
				$f->line(sprintf('ZVAL_COPY_VALUE(&%s[%d], %s);', $argv, $i, $value));
			}
		}
		$receiverClass = count($receiverNames) === 1 && (class_exists($receiverNames[0]) || interface_exists($receiverNames[0])) ? new ReflectionClass($receiverNames[0]) : null;
		$this->emitDispatchFallback($f, $receiverClass, $name, $z, count($values), $argv, $t);
		$f->close();
		if ($returnsVoid) {
			return Value::void();
		}
		return $this->fromEngineResult($t, $native);
	}

	/**
	 * $receiver->getter() of a user method whose whole body is `return
	 * $this->prop;` in a class the compiler does not compile (php-parser's
	 * nodes, PHP helpers): the property slot read by runtime when the
	 * object's class still has that method (pgrt::trivialGetter()), the
	 * engine otherwise.
	 */
	private function trivialGetterCall(FunctionContext $f, string $class, string $name, Value $receiver, Type $native): ?Value
	{
		if (isset($this->units[strtolower($class)]) || (!class_exists($class) && !interface_exists($class))) {
			return null;
		}
		$method = $this->methodReflection($class, $name);
		if ($method === null || $method->isStatic() || $method->isAbstract() || $method->isInternal() || $method->getFileName() === false) {
			return null;
		}
		$declaring = $method->getDeclaringClass();
		$property = $this->trivialGetterProperty($method);
		if ($property === null || !$declaring->hasProperty($property)) {
			return null;
		}
		$reflectionProperty = $declaring->getProperty($property);
		if ($reflectionProperty->isStatic() || $reflectionProperty->hasHooks()) {
			return null;
		}
		$z = $this->asZval($f, $receiver);
		$site = $this->site('pgrt::GetterSite', sprintf('getter_%s_%d', $name, count($this->siteNames)));
		$declSite = $this->site('pgrt::ClassSite', 'cls_' . $declaring->getName());
		$slot = $f->temp('gs');
		$t = $f->temp('r');
		$f->line(sprintf('zval *%s = Z_TYPE_P(%s) == IS_OBJECT ? pgrt::trivialGetter(%s, Z_OBJ_P(%s), pgrt::lookupClassQuiet(%s, "%s", %d), "%s", %d, "%s", %d) : NULL;', $slot, $z, $site, $z, $declSite, addcslashes($declaring->getName(), '\\'), strlen($declaring->getName()), strtolower($name), strlen($name), $property, strlen($property)));
		$f->line(sprintf('zv::Val %s;', $t));
		$f->open(sprintf('if (EXPECTED(%s != NULL))', $slot));
		$f->line(sprintf('ZVAL_COPY(%s.raw(), %s);', $t, $slot));
		$f->close(' else {');
		$f->open('');
		$callSite = $this->site('pgrt::MethodShapeSite', sprintf('method0_%s_%d', $name, count($this->siteNames)));
		$f->failIf(sprintf('!pgrt::callMethod0(%s, %s, "%s", "%s", %d, %s.raw())', $callSite, $z, $name, strtolower($name), strlen($name), $t));
		$f->close();
		$f->line('}');
		return $this->fromEngineResult($t, $native);
	}

	/** the property a method only returns (`return $this->prop;`), null for any other body */
	private function trivialGetterProperty(ReflectionMethod $method): ?string
	{
		$file = (string) $method->getFileName();
		if (!isset($this->parsedFiles[$file])) {
			$parser = (new ParserFactory())->createForHostVersion();
			$this->parsedFiles[$file] = $parser->parse((string) file_get_contents($file)) ?? [];
		}
		$found = $this->finder->findFirst($this->parsedFiles[$file], static fn (Node $node): bool => $node instanceof Stmt\ClassMethod
			&& $node->name->toLowerString() === strtolower($method->getName())
			&& $node->getStartLine() === $method->getStartLine());
		if (!$found instanceof Stmt\ClassMethod || $found->stmts === null || count($found->stmts) !== 1) {
			return null;
		}
		$return = $found->stmts[0];
		if (!$return instanceof Stmt\Return_ || !$return->expr instanceof Expr\PropertyFetch) {
			return null;
		}
		$fetch = $return->expr;
		if (!$fetch->var instanceof Expr\Variable || $fetch->var->name !== 'this' || !$fetch->name instanceof Node\Identifier) {
			return null;
		}
		return $fetch->name->toString();
	}

	/** @return list<string> every shadowed class (the manifest's) */
	private function shadowedClassNames(): array
	{
		if ($this->shadowedClassNames === null) {
			$json = json_decode((string) file_get_contents(__DIR__ . '/../../../../vendor/turbo-shadowed-classes.json'), true);
			$this->shadowedClassNames = is_array($json) ? array_keys($json) : [];
		}
		return $this->shadowedClassNames;
	}

	/**
	 * $factory->create(...) as pt_expression_result_create() (Engine.h): the
	 * nine required parameters through pt_expression_result_args' constructor,
	 * each optional one the call passes through its with*() setter — the
	 * container's factory gets the native ExpressionResult built directly,
	 * any other factory create() with the same named arguments. Arguments
	 * are evaluated in source order; a bool parameter needs a proven bool.
	 */
	private function expressionResultCreate(FunctionContext $f, Value $receiver, Expr\MethodCall $expr): ?Value
	{
		$parameters = (new ReflectionMethod(ExpressionResultFactory::class, 'create'))->getParameters();
		$byName = [];
		foreach ($expr->args as $i => $arg) {
			if (!$arg instanceof Node\Arg || $arg->unpack || $arg->byRef) {
				return null;
			}
			$parameter = $arg->name !== null ? $arg->name->toString() : ($parameters[$i] ?? null)?->getName();
			if ($parameter === null || isset($byName[$parameter])) {
				return null;
			}
			$byName[$parameter] = $arg;
		}
		$bools = ['hasYield', 'isAlwaysTerminating', 'containsNullsafe'];
		$callables = ['typeCallback', 'specifyTypesCallback', 'createTypesCallback'];
		$required = array_slice(array_map(static fn (ReflectionParameter $p): string => $p->getName(), $parameters), 0, 9);
		// everything decided before any argument is evaluated
		foreach ($required as $parameter) {
			if (!isset($byName[$parameter])) {
				return null;
			}
		}
		foreach ($bools as $parameter) {
			if (isset($byName[$parameter]) && !$this->unit()->oracle->nativeType($byName[$parameter]->value)->isBoolean()->yes()) {
				return null;
			}
		}
		$sideEffects = $this->callHasSideEffects($expr->args);
		$values = [];
		foreach ($byName as $parameter => $arg) {
			$this->closureAsHolder = in_array($parameter, $callables, true) && ($arg->value instanceof Expr\Closure || $arg->value instanceof Expr\ArrowFunction);
			$value = $this->expr($f, $arg->value);
			$this->closureAsHolder = false;
			if ($sideEffects) {
				$value = $this->stabilize($f, $value);
			}
			if (in_array($parameter, $bools, true)) {
				if ($value->kind !== 'bool') {
					$value = Value::bool(sprintf('(Z_TYPE_P(%s) == IS_TRUE)', $this->asZval($f, $value)));
				}
				$values[$parameter] = $value->code;
				continue;
			}
			$values[$parameter] = $this->asZval($f, $value);
		}
		$constructor = [];
		foreach ($required as $parameter) {
			$constructor[] = $values[$parameter];
		}
		$args = $f->temp('era');
		$f->line(sprintf('pt_expression_result_args %s(%s);', $args, implode(', ', $constructor)));
		foreach ($values as $parameter => $code) {
			if (in_array($parameter, $required, true)) {
				continue;
			}
			$f->line(sprintf('%s.with%s(%s);', $args, ucfirst($parameter), $code));
		}
		$t = $f->temp('r');
		$z = $this->asZval($f, $receiver);
		$f->line(sprintf('zv::Val %s = pt_expression_result_create(%s, %s);', $t, $z, $args));
		$f->failIf(sprintf('%s.isUndef()', $t));
		return new Value('zval', $t . '.raw()', $t, ExpressionResult::class);
	}

	/** whether the method's parameter an entry parameter stands for (by name, else by position) accepts null */
	private function parameterAllowsNull(ReflectionMethod $method, string $name, int $index): bool
	{
		foreach ($method->getParameters() as $parameter) {
			if ($parameter->getName() === $name) {
				return $parameter->allowsNull();
			}
		}
		return ($method->getParameters()[$index] ?? null)?->allowsNull() ?? false;
	}

	/** whether an argument can be converted to an entry parameter's type */
	private function entryArgumentConvertible(string $type, Expr $arg): bool
	{
		$native = $this->unit()->oracle->nativeType($arg);
		switch ($type) {
			case 'zval':
			case 'enum':
				return true;
			case 'bool':
				return $native->isBoolean()->yes();
			case 'long':
				return $native->isInteger()->yes();
			case 'string':
				return $native->isString()->yes();
			case 'nstring':
				return TypeCombinator::removeNull($native)->isString()->yes();
			case 'objarg':
				return $native->isObject()->yes();
			case 'tri':
				return TypeCombinator::removeNull($native)->isBoolean()->yes();
		}
		return false;
	}

	/**
	 * An enum parameter's member for a class-constant argument of the
	 * method's class: `VariableFlow::RETURN` for pt_variable_flow_exit_kind is
	 * PT_VARIABLE_FLOW_EXIT_RETURN — the enum member ending in the constant's
	 * name. Null for anything else.
	 *
	 * @param array{name: string, type: string, default: string|null, enum?: string} $param
	 */
	private function enumArgument(array $param, string $class, Expr $arg): ?string
	{
		if (!$arg instanceof Expr\ClassConstFetch || !$arg->name instanceof Node\Identifier || !isset($param['enum'])) {
			return null;
		}
		if (strtolower($this->className($arg->class)) !== strtolower($class)) {
			return null;
		}
		$constant = strtoupper($arg->name->toString());
		foreach (array_keys($this->enumMembers($param['enum'], 'PT_')) as $member) {
			if (str_ends_with($member, '_' . $constant)) {
				return $member;
			}
		}
		return null;
	}

	/**
	 * A borrowed slot reader of AnalyserValues.h: the slot of the native
	 * value class, the getter's result (held) for any other object.
	 */
	private function readerEntryCall(FunctionContext $f, string $function, string $kind, string $name, string $z, Type $native, ?string $exactCe = null): Value
	{
		$condition = sprintf('Z_TYPE_P(%s) == IS_OBJECT', $z) . ($exactCe !== null ? sprintf(' && Z_OBJCE_P(%s) == %s', $z, $exactCe) : '');
		$receiverArg = $exactCe !== null ? sprintf('Z_OBJ_P(%s)', $z) : $z;
		if ($kind === 'boolout') {
			$out = $f->temp('bo');
			$f->line(sprintf('bool %s = false;', $out));
			$f->open(sprintf('if (EXPECTED(%s))', $condition));
			$f->failIf(sprintf('!%s(%s, %s)', $function, $receiverArg, $out));
			$f->close(' else {');
			$f->open('');
			$t = $f->temp('r');
			$f->line(sprintf('zv::Val %s;', $t));
			$site = $this->site('pt_method_site', sprintf('method_%s_%d', $name, count($this->siteNames)));
			$f->failIf(sprintf('!pgrt::callMethod(%s, %s, "%s", "%s", %d, 0, NULL, %s.raw())', $site, $z, $name, strtolower($name), strlen($name), $t));
			$f->line(sprintf('%s = zend_is_true(%s.raw());', $out, $t));
			$f->close();
			$f->line('}');
			return Value::bool($out);
		}
		$hold = $f->temp('h');
		$p = $f->temp('p');
		$f->line(sprintf('zv::Val %s;', $hold));
		$f->line(sprintf('zval *%s;', $p));
		$f->open(sprintf('if (EXPECTED(%s))', $condition));
		$f->line(sprintf('%s = %s(%s, %s);', $p, $function, $receiverArg, $hold));
		$f->failIf(sprintf('%s == NULL', $p));
		$f->close(' else {');
		$f->open('');
		$site = $this->site('pt_method_site', sprintf('method_%s_%d', $name, count($this->siteNames)));
		$f->failIf(sprintf('!pgrt::callMethod(%s, %s, "%s", "%s", %d, 0, NULL, %s.raw())', $site, $z, $name, strtolower($name), strlen($name), $hold));
		$f->line(sprintf('%s = %s.raw();', $p, $hold));
		$f->close();
		$f->line('}');
		return $this->fromSlot($p, $native);
	}

	/**
	 * The member-enum entry of a reflection family the receiver class belongs
	 * to (pt_extended_method_reflection_call() and its siblings answer the
	 * hand ports natively and take the engine for any other class) — the
	 * function and the member constant, null when there is none.
	 *
	 * @param ReflectionClass<object> $class
	 * @return array{string, string}|null
	 */
	private function memberDispatchEntry(ReflectionClass $class, string $name): ?array
	{
		$families = [
			ExtendedMethodReflection::class => ['pt_extended_method_reflection_call', 'pt_method_reflection_member', 'PT_MR_'],
			ExtendedPropertyReflection::class => ['pt_extended_property_reflection_call', 'pt_property_reflection_member', 'PT_PROP_'],
			ParameterReflection::class => ['pt_parameter_reflection_call', 'pt_parameter_reflection_member', 'PT_PR_'],
			ClassMemberReflection::class => ['pt_class_member_reflection_call', 'pt_class_member_reflection_member', 'PT_CMR_'],
		];
		foreach ($families as $interface => [$function, $enum, $prefix]) {
			if (!$class->implementsInterface($interface) && $class->getName() !== $interface) {
				continue;
			}
			if (!(new ReflectionClass($interface))->hasMethod($name)) {
				continue;
			}
			$constant = $prefix . strtoupper((string) preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', '_', $name));
			if (!array_key_exists($constant, $this->enumMembers($enum, $prefix))) {
				continue;
			}
			return [$function, $constant];
		}
		return null;
	}

	/**
	 * The engine call that ends a dispatch without arguments: the member-enum
	 * entry when the receiver's family has one, pgrt::callMethod() otherwise.
	 */
	/**
	 * The by-name call a dispatch falls back to, or the reflection family's
	 * member-enum entry when the receiver class has one.
	 *
	 * @param ReflectionClass<object>|null $class
	 */
	private function emitDispatchFallback(FunctionContext $f, ?ReflectionClass $class, string $name, string $z, int $argc, string $argv, string $t): void
	{
		$entry = $argc === 0 && $class !== null ? $this->memberDispatchEntry($class, $name) : null;
		if ($entry !== null) {
			$f->line(sprintf('%s = %s(%s, %s);', $t, $entry[0], $z, $entry[1]));
			$f->failIf(sprintf('%s.isUndef()', $t));
			return;
		}
		$site = $this->site('pt_method_site', sprintf('method_%s_%d', $name, count($this->siteNames)));
		$f->failIf(sprintf('!pgrt::callMethod(%s, %s, "%s", "%s", %d, %d, %s, %s.raw())', $site, $z, $name, strtolower($name), strlen($name), $argc, $argv, $t));
	}

	/**
	 * $reflection->member() of a reflection family the hand-written ports
	 * dispatch by member constant (`zv::Val f(zval *, enum member)`: the
	 * native body of the classes it knows, a cached method site otherwise).
	 *
	 * @param ReflectionClass<object> $class
	 */
	private function memberDispatchCall(FunctionContext $f, ReflectionClass $class, string $name, Value $receiver, Type $native, bool $returnsVoid): ?Value
	{
		$entry = $this->memberDispatchEntry($class, $name);
		if ($entry === null) {
			return null;
		}
		[$function, $constant] = $entry;
		$z = $this->asZval($f, $receiver);
		$t = $f->temp('r');
		$f->line(sprintf('zv::Val %s;', $t));
		$f->open(sprintf('if (EXPECTED(Z_TYPE_P(%s) == IS_OBJECT))', $z));
		$f->line(sprintf('%s = %s(%s, %s);', $t, $function, $z, $constant));
		$f->failIf(sprintf('%s.isUndef()', $t));
		$f->close(' else {');
		$f->open('');
		$site = $this->site('pt_method_site', sprintf('method_%s_%d', $name, count($this->siteNames)));
		$f->failIf(sprintf('!pgrt::callMethod(%s, %s, "%s", "%s", %d, 0, NULL, %s.raw())', $site, $z, $name, strtolower($name), strlen($name), $t));
		$f->close();
		$f->line('}');
		return $returnsVoid ? Value::void() : $this->fromEngineResult($t, $native);
	}

	/**
	 * The direct entries of the hand-written headers, by function — the
	 * shape of the return:
	 *
	 * - val: `zv::Val f(...)`, UNDEF = pending exception
	 * - out: `bool f(zval *out, ...)` (the out parameter not listed)
	 * - status: `bool f(...)` of a void method, false = pending exception
	 * - borrowed: `zend_object *f(...)` documented as borrowed, NULL = pending exception
	 * - hold / boolout: AnalyserValues.h's slot readers, `zval *f(zval *, zv::Val &hold)`
	 *   (NULL = pending exception) and `bool f(zval *, bool &out)`
	 *
	 * and of the parameters: `zval *` (a trailing `= NULL` is the ports'
	 * "default"), `bool` (with a C++ default) — never a `zend_object *`
	 * receiver, those take the native class only — and a variadic method's `uint32_t argc, zval *argv` (argv) or
	 * `uint32_t argc, zval *const *argv` (argvp) tail.
	 *
	 * @return array<string, array{kind: string, params: list<array{name: string, type: string, default: string|null, enum?: string}>, variadic: string|null, nullAsNull: bool}>
	 */
	private function nativeEntries(): array
	{
		if ($this->nativeEntries !== null) {
			return $this->nativeEntries;
		}
		$this->nativeEntries = [];
		// the headers CodegenRuntime.h includes
		foreach (['support.h', 'Engine.h', 'TypeOps.h', 'ParameterValues.h', 'AnalyserValues.h'] as $header) {
			$code = (string) file_get_contents(__DIR__ . '/../../../../turbo-ext/src/' . $header);
			preg_match_all('~^inline (?:zval \*|bool )(pt_\w+)\(zval \*\w+, (zv::Val &hold|bool &out)\)$~m', $code, $readers, PREG_SET_ORDER);
			foreach ($readers as [, $function, $readerTail]) {
				$this->nativeEntries[$function] = ['kind' => $readerTail === 'bool &out' ? 'boolout' : 'hold', 'params' => [['name' => 'receiver', 'type' => 'zval', 'default' => null]], 'variadic' => null, 'nullAsNull' => false];
			}
			preg_match_all('~(/\*(?:(?!\*/).)*\*/\s*)?^(?:\[\[nodiscard\]\] )?(zv::Val|bool|zend_object \*)\s*(pt_\w+)\(([^()]*)\);~ms', $code, $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				[, $comment, $return, $function, $parameters] = $match;
				$variadic = null;
				if (preg_match('~(?:^|, )uint32_t argc, zval \*(const \*)?argv$~', $parameters, $tail) === 1) {
					$variadic = ($tail[1] ?? '') !== '' ? 'argvp' : 'argv';
					$parameters = (string) preg_replace('~(?:^|, )uint32_t argc, zval \*(?:const \*)?argv$~', '', $parameters);
				}
				if ($return === 'bool' && preg_match('~^(zval|zend_object) \*\w+, bool &out$~', $parameters, $m) === 1) {
					// a bool getter's answer through an out parameter
					$this->nativeEntries[$function] = ['kind' => 'boolout', 'params' => [['name' => 'receiver', 'type' => $m[1] === 'zval' ? 'zval' : 'obj', 'default' => null]], 'variadic' => null, 'nullAsNull' => false];
					continue;
				}
				$params = [];
				$valid = true;
				foreach ($parameters === '' ? [] : explode(', ', $parameters) as $parameter) {
					if ($params === [] && preg_match('~^zend_object \*(\w+)$~', $parameter, $m) === 1) {
						// a receiver of exactly the native class
						$params[] = ['name' => $m[1], 'type' => 'obj', 'default' => null];
					} elseif ($params !== [] && preg_match('~^zend_object \*(\w+)$~', $parameter, $m) === 1) {
						// an argument the port takes as an object (proven one at the call)
						$params[] = ['name' => $m[1], 'type' => 'objarg', 'default' => null];
					} elseif (preg_match('~^zval \*(\w+)( = NULL)?$~', $parameter, $m) === 1) {
						$params[] = ['name' => $m[1], 'type' => 'zval', 'default' => ($m[2] ?? '') !== '' ? 'NULL' : null];
					} elseif (preg_match('~^bool (\w+)(?: = (true|false))?$~', $parameter, $m) === 1) {
						$params[] = ['name' => $m[1], 'type' => 'bool', 'default' => $m[2] ?? null];
					} elseif (preg_match('~^zend_long (\w+)(?: = (-?\d+))?$~', $parameter, $m) === 1) {
						$params[] = ['name' => $m[1], 'type' => 'long', 'default' => $m[2] ?? null];
					} elseif (preg_match('~^zend_string \*(\w+)( = NULL)?$~', $parameter, $m) === 1) {
						$params[] = ['name' => $m[1], 'type' => ($m[2] ?? '') !== '' ? 'nstring' : 'string', 'default' => ($m[2] ?? '') !== '' ? 'NULL' : null];
					} elseif (preg_match('~^(pt_\w+_kind) (\w+)$~', $parameter, $m) === 1) {
						$params[] = ['name' => $m[2], 'type' => 'enum', 'default' => null, 'enum' => $m[1]];
					} else {
						$valid = false;
						break;
					}
				}
				if (!$valid) {
					continue;
				}
				if ($return === 'zv::Val') {
					$kind = 'val';
				} elseif ($return === 'bool' && $parameters !== '' && str_starts_with($parameters, 'zval *out')) {
					$kind = 'out';
					array_shift($params);
				} elseif ($return === 'bool') {
					$kind = 'status';
				} elseif (str_contains($comment, 'borrowed')) {
					$kind = 'borrowed';
				} else {
					continue;
				}
				// whether the port takes a nullable parameter's null as a C NULL (documented so)
				$nullAsNull = preg_match('~(?<!IS_)NULL (?:for|=|is|as) (?:a )?null~i', $comment) === 1;
				$this->nativeEntries[$function] = ['kind' => $kind, 'params' => $params, 'variadic' => $variadic, 'nullAsNull' => $nullAsNull];
			}
		}
		return $this->nativeEntries;
	}

	private static function snake(string $name): string
	{
		return strtolower((string) preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', '_', $name));
	}

	/** @return ReflectionMethod|null the method a class has under the name (any visibility) */
	private function methodReflection(string $class, string $name): ?ReflectionMethod
	{
		if (!class_exists($class) && !interface_exists($class)) {
			return null;
		}
		$reflection = new ReflectionClass($class);
		return $reflection->hasMethod($name) ? $reflection->getMethod($name) : null;
	}

	/**
	 * $receiver->name(...) on a non-final compiled class: the receiver's
	 * exact class picks the compiled implementation of every compiled class
	 * it could be (the closed set this compile knows), entered directly;
	 * any other class — a PHP subclass — goes through the engine. Null when
	 * nothing could be entered directly.
	 */
	private function virtualDispatch(FunctionContext $f, string $class, string $name, Value $receiver, Expr\MethodCall $expr): ?Value
	{
		foreach ($expr->args as $arg) {
			if (!$arg instanceof Node\Arg || $arg->unpack || $arg->name !== null) {
				return null;
			}
		}
		$groups = [];
		foreach ($this->units as $unit) {
			if (strtolower($unit->className) !== strtolower($class) && !is_subclass_of($unit->className, $class)) {
				continue;
			}
			$reflection = $this->methodReflection($unit->className, $name);
			if ($reflection === null || $reflection->isStatic() || $reflection->isAbstract()) {
				continue;
			}
			$implementer = $unit->methods[strtolower($name)] ?? null;
			if ($implementer === null) {
				$declaring = $this->units[strtolower($reflection->getDeclaringClass()->getName())] ?? null;
				$implementer = $declaring?->methods[strtolower($name)] ?? null;
			}
			if ($implementer === null) {
				continue;
			}
			$key = $implementer->unit->stem . '::' . $implementer->name;
			$groups[$key]['method'] = $implementer;
			$groups[$key]['ces'][] = $unit->ce;
		}
		if ($groups === []) {
			return null;
		}
		$args = $expr->args;
		$values = [];
		foreach ($this->lowerArgs($f, $args, $this->methodReflection($class, $name)) as $value) {
			$values[] = $this->stabilize($f, $value);
		}
		$z = $this->asZval($f, $receiver);
		$t = $f->temp('vd');
		$f->line(sprintf('zv::Val %s;', $t));
		$f->line(sprintf('zend_class_entry *%s_ce = Z_TYPE_P(%s) == IS_OBJECT ? Z_OBJCE_P(%s) : NULL;', $t, $z, $z));
		$f->line(sprintf('bool %s_done = false;', $t));
		if (count($groups) > self::VIRTUAL_DISPATCH_CHAIN_LIMIT) {
			// many implementations (a rule registry, a wrapped rule): the
			// receiver's class picks its case from a cache, not a chain of tests
			$tableCes = [];
			$tableCases = [];
			$case = 0;
			foreach ($groups as $group) {
				$case++;
				foreach (array_unique($group['ces']) as $ce) {
					$tableCes[] = '&' . $ce;
					$tableCases[] = $case;
				}
			}
			$cache = $this->site('pgrt::DispatchCache', sprintf('dispatch_%s_%d', $name, count($this->siteNames)));
			$f->line(sprintf('static zend_class_entry **const %s_table[] = { %s };', $t, implode(', ', $tableCes)));
			$f->line(sprintf('static const uint16_t %s_cases[] = { %s };', $t, implode(', ', $tableCases)));
			$f->open(sprintf('switch (pgrt::dispatchCase(%s, %s_ce, %s_table, %s_cases, %d))', $cache, $t, $t, $t, count($tableCes)));
			$case = 0;
			foreach ($groups as $group) {
				$case++;
				$f->open(sprintf('case %d:', $case));
				$callArgs = $this->arrangeArgs($f, $group['method'], $args, $values);
				$result = $this->emitDirectCall($f, $group['method'], sprintf('Z_OBJ_P(%s)', $z), $callArgs);
				if ($result->kind !== 'void') {
					$f->line(sprintf('%s = std::move(%s);', $t, $this->own($f, $result)));
				}
				$f->line(sprintf('%s_done = true;', $t));
				$f->close();
				$f->line('break;');
			}
			$f->line('default:');
			$f->line('break;');
			$f->close();
			$groups = [];
		}
		foreach ($groups as $group) {
			$conditions = array_map(static fn (string $ce): string => sprintf('%s_ce == %s', $t, $ce), array_unique($group['ces']));
			$f->open(sprintf('if (%s)', implode(' || ', $conditions)));
			$callArgs = $this->arrangeArgs($f, $group['method'], $args, $values);
			$result = $this->emitDirectCall($f, $group['method'], sprintf('Z_OBJ_P(%s)', $z), $callArgs);
			if ($result->kind !== 'void') {
				$f->line(sprintf('%s = std::move(%s);', $t, $this->own($f, $result)));
			}
			$f->line(sprintf('%s_done = true;', $t));
			$f->close();
		}
		$f->open(sprintf('if (!%s_done)', $t));
		$argv = 'NULL';
		if ($values !== []) {
			// a subclass's override is called by name: by-reference parameters
			// get a reference written back after the call, as on the engine path
			$parameters = $this->methodReflection($class, $name)?->getParameters() ?? [];
			$argv = $f->temp('av');
			$f->line(sprintf('zval %s[%d];', $argv, count($values)));
			foreach ($values as $i => $value) {
				$parameter = $parameters[$i] ?? (count($parameters) > 0 && $parameters[count($parameters) - 1]->isVariadic() ? $parameters[count($parameters) - 1] : null);
				$zval = $parameter !== null && $parameter->isPassedByReference()
					? $this->byRefArg($f, $args[$i]->value)
					: $this->asZval($f, $value);
				$f->line(sprintf('ZVAL_COPY_VALUE(&%s[%d], %s);', $argv, $i, $zval));
			}
		}
		$this->emitDispatchFallback($f, class_exists($class) || interface_exists($class) ? new ReflectionClass($class) : null, $name, $z, count($values), $argv, $t);
		$this->flushWriteBacks($f);
		$f->close();
		return $this->narrowZval($t . '.raw()', $t, $this->unit()->oracle->nativeType($expr));
	}

	private function staticCall(FunctionContext $f, Expr\StaticCall $expr): Value
	{
		if (!$expr->name instanceof Node\Identifier) {
			throw new CompileError('dynamic static method names are not supported');
		}
		$name = $expr->name->toString();
		if ($expr->class instanceof Expr) {
			// $class::name(...): the class before the arguments, the method found at run time
			$class = $this->asZval($f, $this->stabilize($f, $this->expr($f, $expr->class)));
			[$argv, $argc, $named] = $this->engineArgs($f, $expr->args);
			$t = $f->temp('r');
			$f->line(sprintf('zv::Val %s;', $t));
			$f->failIf(sprintf('!pgrt::callStaticDynamic(%s, %s, %s, %s, %s, %s.raw(), %s)', $class, $this->unit()->ce, $this->literal($name)->code, $argc, $argv, $t, $named));
			return $this->narrowZval($t . '.raw()', $t, $this->unit()->oracle->nativeType($expr));
		}
		$lower = $expr->class->toLowerString();
		if ($lower === 'parent' || (in_array($lower, ['self', 'static'], true) && $f->self !== null && !($this->methodReflection($this->unit()->className, $name)?->isStatic() ?? true))) {
			return $this->boundInstanceCall($f, $expr, $lower === 'parent');
		}
		$className = $this->className($expr->class);
		$unit = $this->units[strtolower($className)] ?? null;
		$method = $unit?->methods[strtolower($name)] ?? null;
		if ($method !== null && $method->isStatic && !$this->usesCalledScope($method)) {
			$args = $this->directArgs($f, $method, $expr->args);
			return $this->emitDirectCall($f, $method, null, $args);
		}
		$levels = ['typeonly' => 'TYPE_ONLY', 'value' => 'VALUE', 'precise' => 'PRECISE', 'cache' => 'CACHE'];
		if (strtolower($className) === strtolower(VerbosityLevel::class) && isset($levels[strtolower($name)]) && $expr->args === []) {
			// the twin's singletons, held natively (borrowed: copied)
			$t = $f->temp('vl');
			$f->line(sprintf('zval *%s_p = pt_verbosity_level_singleton(PT_VERBOSITY_LEVEL_%s);', $t, $levels[strtolower($name)]));
			$f->failIf(sprintf('%s_p == NULL', $t));
			$f->line(sprintf('zv::Val %s = zv::Val::copyOf(zv::Ref(%s_p));', $t, $t));
			return new Value('zval', $t . '.raw()', $t, VerbosityLevel::class);
		}
		$entry = $this->nativeEntryCall($f, $className, $name, null, $expr->args, $this->unit()->oracle->nativeType($expr));
		if ($entry !== null) {
			return $entry;
		}
		$key = $className . '::' . $name;
		$this->engineCalls[$key] = ($this->engineCalls[$key] ?? 0) + 1;
		[$argv, $argc, $named] = $this->engineArgs($f, $expr->args, $this->methodReflection($className, $name));
		$ce = $this->classEntry($className, $f);
		$site = $this->site('pt_method_site', sprintf('static_%s::%s', $className, $name));
		$t = $f->temp('r');
		$f->line(sprintf('zv::Val %s;', $t));
		$f->failIf(sprintf('!pgrt::callStatic(%s, %s, "%s", %d, %s, %s, %s.raw(), %s)', $site, $ce, strtolower($name), strlen($name), $argc, $argv, $t, $named));
		$this->flushWriteBacks($f);
		return $this->fromEngineResult($t, $this->unit()->oracle->nativeType($expr));
	}

	/**
	 * parent::name(...) / self::name(...) of an instance method: the
	 * implementation that class has, bound to $this without virtual
	 * dispatch — compiled implementations entered directly, others through
	 * the engine with that class's function.
	 */
	private function boundInstanceCall(FunctionContext $f, Expr\StaticCall $expr, bool $parent): Value
	{
		if (!$expr->name instanceof Node\Identifier) {
			throw new CompileError('dynamic method names are not supported');
		}
		$name = $expr->name->toString();
		$reflection = $this->unit()->reflection;
		if ($parent) {
			$reflection = $reflection->getParentClass();
			if ($reflection === false) {
				throw new CompileError('parent:: in a class without a parent');
			}
		}
		$method = $reflection->hasMethod($name) ? $reflection->getMethod($name) : null;
		if ($method === null) {
			throw new CompileError(sprintf('%s::%s() does not exist', $reflection->getName(), $name));
		}
		if ($method->isStatic()) {
			// a static method through parent::/self::: the class's own
			$className = $method->getDeclaringClass()->getName();
			$unit = $this->units[strtolower($className)] ?? null;
			$compiled = $unit?->methods[strtolower($name)] ?? null;
			if ($compiled !== null) {
				return $this->emitDirectCall($f, $compiled, null, $this->directArgs($f, $compiled, $expr->args));
			}
			[$argv, $argc, $named] = $this->engineArgs($f, $expr->args, $method);
			$ce = $this->classEntry($reflection->getName(), $f);
			$site = $this->site('pt_method_site', sprintf('static_%s::%s', $reflection->getName(), $name));
			$t = $f->temp('r');
			$f->line(sprintf('zv::Val %s;', $t));
			$f->failIf(sprintf('!pgrt::callStatic(%s, %s, "%s", %d, %s, %s, %s.raw(), %s)', $site, $ce, strtolower($name), strlen($name), $argc, $argv, $t, $named));
			$this->flushWriteBacks($f);
			return $this->fromEngineResult($t, $this->unit()->oracle->nativeType($expr));
		}
		if ($f->self === null) {
			throw new CompileError('parent::/self:: instance call outside an instance method');
		}
		// the implementation: the declaring class, or the class itself for a trait method compiled into it
		$declaring = $method->getDeclaringClass()->getName();
		$unit = $this->units[strtolower($declaring)] ?? null;
		$compiled = $unit?->methods[strtolower($name)] ?? null;
		if ($compiled !== null) {
			return $this->emitDirectCall($f, $compiled, $f->self, $this->directArgs($f, $compiled, $expr->args));
		}
		[$argv, $argc, $named] = $this->engineArgs($f, $expr->args, $method);
		$ce = $this->classEntry($reflection->getName(), $f);
		$site = $this->site('pt_method_site', sprintf('bound_%s::%s', $reflection->getName(), $name));
		$t = $f->temp('r');
		$f->line(sprintf('zv::Val %s;', $t));
		$f->failIf(sprintf('!pgrt::callBound(%s, %s, %s, "%s", %d, %s, %s, %s.raw(), %s)', $site, $ce, $f->self, strtolower($name), strlen($name), $argc, $argv, $t, $named));
		$this->flushWriteBacks($f);
		return $this->fromEngineResult($t, $this->unit()->oracle->nativeType($expr));
	}

	private function funcCall(FunctionContext $f, Expr\FuncCall $expr): Value
	{
		$native = $this->unit()->oracle->nativeType($expr);
		if ($expr->name instanceof Expr) {
			$callee = $expr->name;
			if ($callee instanceof Expr\Variable && is_string($callee->name) && ($f->locals[$callee->name] ?? null)?->kind === 'callable') {
				[$argv, $argc, $named] = $this->engineArgs($f, $expr->args);
				$t = $f->temp('r');
				$f->line(sprintf('zv::Val %s;', $t));
				$f->failIf(sprintf('!pgrt::callCallable(%s, %s, %s, %s.raw(), %s)', $f->locals[$callee->name]->cName, $argc, $argv, $t, $named));
				return $this->fromEngineResult($t, $native);
			}
			$value = $this->stabilize($f, $this->expr($f, $callee));
			[$argv, $argc, $named] = $this->engineArgs($f, $expr->args);
			$t = $f->temp('r');
			$f->line(sprintf('zv::Val %s;', $t));
			$f->failIf(sprintf('!pgrt::callValue(%s, %s, %s, %s.raw(), %s)', $this->asZval($f, $value), $argc, $argv, $t, $named));
			return $this->fromEngineResult($t, $native);
		}
		$name = strtolower(ltrim($expr->name->toString(), '\\'));
		$resolved = $expr->name->getAttribute('resolvedName');
		if ($resolved instanceof Node\Name) {
			$name = strtolower($resolved->toString());
		}
		if (($name === 'assert' || (str_ends_with($name, '\\assert') && !function_exists($name))) && $expr->getAttribute('turboAssertCall') !== true) {
			// as the engine compiles it: the arguments evaluated and the
			// call made only while zend.assertions is 1, true otherwise
			$t = $f->temp('as');
			$f->line(sprintf('zv::Val %s = zv::Val::boolean(true);', $t));
			$f->open('if (EG(assertions) > 0)');
			$expr->setAttribute('turboAssertCall', true);
			$args = $expr->args;
			if (count($args) === 1 && $args[0] instanceof Node\Arg) {
				// the description the engine's compiler adds: the assertion's source
				$expr->args[] = new Node\Arg(new Node\Scalar\String_(sprintf('assert(%s)', (new PrettyPrinter\Standard())->prettyPrintExpr($args[0]->value))));
			}
			$f->line(sprintf('%s = std::move(%s);', $t, $this->own($f, $this->funcCall($f, $expr))));
			$expr->args = $args;
			$expr->setAttribute('turboAssertCall', false);
			$f->close();
			return new Value('zval', $t . '.raw()', $t);
		}
		if (str_contains($name, '\\')) {
			$parts = explode('\\', $name);
			$name = $parts[count($parts) - 1];
		}
		if (!function_exists($name) || !(new ReflectionFunction($name))->isInternal()) {
			throw new CompileError(sprintf('only built-in functions can be called (%s)', $name));
		}
		if ($name === 'array_column') {
			$intrinsic = $this->arrayColumnIntrinsic($f, $expr);
			if ($intrinsic !== null) {
				return $intrinsic;
			}
		}
		if (in_array($name, self::SCOPE_SENSITIVE_FUNCTIONS, true) && !$this->callbacksAreClosures($expr, $name)) {
			// a frameless direct call leaves the native caller's frame current:
			// the builtin would resolve the callable against the wrong class
			throw new CompileError(sprintf('%s() depends on the calling scope and has no intrinsic for this call (line %d)', $name, $expr->getStartLine()));
		}
		if ($name === 'count' && count($expr->args) === 1 && $expr->args[0] instanceof Node\Arg && !$expr->args[0]->unpack && $expr->args[0]->name === null) {
			$array = $this->expr($f, $expr->args[0]->value);
			if ($array->isArray || $this->unit()->oracle->nativeType($expr->args[0]->value)->isArray()->yes()) {
				return Value::long(sprintf('(zend_long) zend_hash_num_elements(Z_ARRVAL_P(%s))', $this->asZval($f, $array)));
			}
			// an array counted here, anything else by the engine (the
			// evaluated argument as it is: evaluated once)
			$z = $f->temp('ca');
			$f->line(sprintf('zval *%s = zv::Ref(%s).deref().raw();', $z, $this->asZval($f, $array)));
			$count = $f->temp('cn');
			$f->line(sprintf('zend_long %s;', $count));
			$f->open(sprintf('if (EXPECTED(Z_TYPE_P(%s) == IS_ARRAY))', $z));
			$f->line(sprintf('%s = (zend_long) zend_hash_num_elements(Z_ARRVAL_P(%s));', $count, $z));
			$f->close(' else {');
			$f->open('');
			$site = $this->site('pgrt::FunctionSite', 'function_count');
			$t = $f->temp('r');
			$f->line(sprintf('zv::Val %s;', $t));
			$f->failIf(sprintf('!pgrt::callFunction(%s, "count", 5, %s, 1, %s, %s.raw())', $site, $this->unit()->ce, $z, $t));
			$f->line(sprintf('%s = Z_LVAL_P(%s.raw());', $count, $t));
			$f->close();
			$f->line('}');
			return Value::long($count);
		}
		$intrinsic = $this->builtinIntrinsic($f, $name, $expr);
		if ($intrinsic !== null) {
			return $intrinsic;
		}
		[$argv, $argc, $named] = $this->engineArgs($f, $expr->args, new ReflectionFunction($name));
		$site = $this->site('pgrt::FunctionSite', 'function_' . $name);
		$t = $f->temp('r');
		$f->line(sprintf('zv::Val %s;', $t));
		$f->failIf(sprintf('!pgrt::callFunction(%s, "%s", %d, %s, %s, %s, %s.raw(), %s)', $site, $name, strlen($name), $this->unit()->ce, $argc, $argv, $t, $named));
		$this->flushWriteBacks($f);
		return $this->fromEngineResult($t, $native);
	}

	/** the frameless runtime versions of the array builtins the twins lean on */
	private function builtinIntrinsic(FunctionContext $f, string $name, Expr\FuncCall $expr): ?Value
	{
		$native = $this->unit()->oracle->nativeType($expr);
		$single = count($expr->args) === 1 && $expr->args[0] instanceof Node\Arg && !$expr->args[0]->unpack && $expr->args[0]->name === null;
		switch ($name) {
			case 'array_merge':
				[$argv, $argc, $named] = $this->engineArgs($f, $expr->args);
				if ($named !== 'NULL') {
					return null;
				}
				$t = $f->temp('r');
				$f->line(sprintf('zv::Val %s;', $t));
				$f->failIf(sprintf('!pgrt::arrayMerge(%s, %s, %s.raw())', $argc, $argv, $t));
				return new Value('zval', $t . '.raw()', $t, isArray: true);
			case 'array_unique':
			case 'array_values':
				if (!$single) {
					return null;
				}
				$array = $this->asZval($f, $this->expr($f, $expr->args[0]->value));
				$t = $f->temp('r');
				$f->line(sprintf('zv::Val %s;', $t));
				$f->failIf(sprintf('!pgrt::%s(%s, %s.raw())', $name === 'array_unique' ? 'arrayUnique' : 'arrayValues', $array, $t));
				return new Value('zval', $t . '.raw()', $t, isArray: true);
			case 'is_bool':
			case 'is_int':
			case 'is_string':
			case 'is_array':
			case 'is_object':
			case 'is_null':
			case 'is_float':
			case 'is_scalar':
				if (!$single) {
					return null;
				}
				$value = $this->expr($f, $expr->args[0]->value);
				if (in_array($value->kind, ['long', 'bool'], true)) {
					$is = $value->kind === 'long' ? ['is_int', 'is_scalar'] : ['is_bool', 'is_scalar'];
					return Value::bool(in_array($name, $is, true) ? 'true' : 'false');
				}
				$z = $f->temp('ty');
				$f->line(sprintf('uint8_t %s = Z_TYPE_P(zv::Ref(%s).deref().raw());', $z, $this->asZval($f, $value)));
				$checks = [
					'is_bool' => '(%1$s == IS_TRUE || %1$s == IS_FALSE)',
					'is_int' => '%1$s == IS_LONG',
					'is_string' => '%1$s == IS_STRING',
					'is_array' => '%1$s == IS_ARRAY',
					'is_object' => '%1$s == IS_OBJECT',
					'is_null' => '%1$s <= IS_NULL',
					'is_float' => '%1$s == IS_DOUBLE',
					'is_scalar' => '(%1$s >= IS_FALSE && %1$s <= IS_STRING)',
				];
				return Value::bool('(' . sprintf($checks[$name], $z) . ')');
			case 'in_array':
				return $this->inArrayIntrinsic($f, $expr);
			case 'strtolower':
				if (!$single) {
					return null;
				}
				$string = $this->asZval($f, $this->expr($f, $expr->args[0]->value));
				$t = $f->temp('r');
				// the ASCII fold strtolower() is since PHP 8.2; an already lowercase string comes back addref'd
				if ($this->unit()->oracle->nativeType($expr->args[0]->value)->isString()->yes()) {
					$f->line(sprintf('zv::Val %s = zv::Val::adoptString(zend_string_tolower(Z_STR_P(%s)));', $t, $string));
					return new Value('zval', $t . '.raw()', $t);
				}
				// a string folded here, anything else by the engine (its coercion or TypeError)
				$z = $f->temp('sa');
				$f->line(sprintf('zval *%s = zv::Ref(%s).deref().raw();', $z, $string));
				$f->line(sprintf('zv::Val %s;', $t));
				$f->open(sprintf('if (EXPECTED(Z_TYPE_P(%s) == IS_STRING))', $z));
				$f->line(sprintf('%s = zv::Val::adoptString(zend_string_tolower(Z_STR_P(%s)));', $t, $z));
				$f->close(' else {');
				$f->open('');
				$site = $this->site('pgrt::FunctionSite', 'function_strtolower');
				$f->failIf(sprintf('!pgrt::callFunction(%s, "strtolower", 10, %s, 1, %s, %s.raw())', $site, $this->unit()->ce, $z, $t));
				$f->close();
				$f->line('}');
				return new Value('zval', $t . '.raw()', $t);
			case 'spl_object_id':
				if (!$single || !$this->unit()->oracle->nativeType($expr->args[0]->value)->isObject()->yes()) {
					return null;
				}
				$object = $this->asZval($f, $this->expr($f, $expr->args[0]->value));
				return Value::long(sprintf('(zend_long) Z_OBJ_HANDLE_P(%s)', $object));
			case 'array_key_exists':
				$args = $this->plainArgs($expr->args);
				if ($args === null || count($args) !== 2 || !$this->unit()->oracle->nativeType($args[1]->value)->isArray()->yes()) {
					return null;
				}
				$keyType = $this->unit()->oracle->nativeType($args[0]->value);
				if (!$keyType->isString()->yes() && !$keyType->isInteger()->yes()) {
					return null;
				}
				$key = $this->stabilize($f, $this->expr($f, $args[0]->value));
				$array = $this->asZval($f, $this->expr($f, $args[1]->value));
				if ($key->kind === 'long' || $keyType->isInteger()->yes()) {
					$code = $key->kind === 'long' ? $key->code : sprintf('Z_LVAL_P(%s)', $this->asZval($f, $key));
					return Value::bool(sprintf('zend_hash_index_exists(Z_ARRVAL_P(%s), (zend_ulong) %s)', $array, $code));
				}
				return Value::bool(sprintf('zend_symtable_exists(Z_ARRVAL_P(%s), Z_STR_P(%s))', $array, $this->asZval($f, $key)));
			case 'array_reverse':
				if (!$single || !$this->unit()->oracle->nativeType($expr->args[0]->value)->isList()->yes()) {
					return null;
				}
				$array = $this->asZval($f, $this->expr($f, $expr->args[0]->value));
				$t = $f->temp('r');
				$f->line(sprintf('zv::Val %s;', $t));
				$f->line(sprintf('pgrt::listReverse(%s, %s.raw());', $array, $t));
				return new Value('zval', $t . '.raw()', $t, isArray: true);
			case 'implode':
				$args = $this->plainArgs($expr->args);
				if ($args === null || count($args) !== 2 || !$this->unit()->oracle->nativeType($args[0]->value)->isString()->yes() || !$this->unit()->oracle->nativeType($args[1]->value)->isArray()->yes()) {
					return null;
				}
				$glue = $this->asZval($f, $this->stabilize($f, $this->expr($f, $args[0]->value)));
				$pieces = $this->asZval($f, $this->expr($f, $args[1]->value));
				$t = $f->temp('r');
				$f->line(sprintf('zv::Val %s;', $t));
				$f->failIf(sprintf('!pgrt::implode(%s, %s, %s.raw())', $glue, $pieces, $t));
				return new Value('zval', $t . '.raw()', $t);
			case 'sprintf':
				return $this->sprintfIntrinsic($f, $expr);
			case 'array_last':
			case 'array_first':
				if (!$single) {
					return null;
				}
				$array = $this->asZval($f, $this->expr($f, $expr->args[0]->value));
				$site = $this->site('pgrt::FunctionSite', 'function_' . $name);
				$t = $f->temp('r');
				$f->line(sprintf('zv::Val %s;', $t));
				$f->failIf(sprintf('!pgrt::arrayEdge(%s, "%s", %d, %s, %s, %s.raw())', $site, $name, strlen($name), $name === 'array_last' ? 'true' : 'false', $array, $t));
				return $this->fromEngineResult($t, $native);
			case 'str_starts_with':
			case 'str_ends_with':
			case 'str_contains':
				if (count($expr->args) !== 2) {
					return null;
				}
				$args = $this->plainArgs($expr->args);
				if ($args === null) {
					return null;
				}
				$haystack = $this->asZval($f, $this->stabilize($f, $this->expr($f, $args[0]->value)));
				$needle = $this->asZval($f, $this->expr($f, $args[1]->value));
				$site = $this->site('pgrt::FunctionSite', 'function_' . $name);
				$t = $f->temp('r');
				$f->line(sprintf('zv::Val %s;', $t));
				$kinds = ['str_starts_with' => 'pgrt::StrMatch::STARTS', 'str_ends_with' => 'pgrt::StrMatch::ENDS', 'str_contains' => 'pgrt::StrMatch::CONTAINS'];
				$f->failIf(sprintf('!pgrt::strMatch(%s, "%s", %d, %s, %s, %s, %s.raw())', $site, $name, strlen($name), $kinds[$name], $haystack, $needle, $t));
				return Value::bool(sprintf('(Z_TYPE_P(%s.raw()) == IS_TRUE)', $t));
			case 'get_class':
				if (!$single) {
					return null;
				}
				$object = $this->asZval($f, $this->expr($f, $expr->args[0]->value));
				$t = $f->temp('r');
				$f->line(sprintf('zv::Val %s;', $t));
				$f->failIf(sprintf('!pgrt::getClass(%s, %s.raw())', $object, $t));
				return new Value('zval', $t . '.raw()', $t);
			case 'array_pop':
				// on the variable or property itself: the by-reference
				// argument's temporary would share the array and the pop
				// would separate (copy) it
				if (!$single) {
					return null;
				}
				$target = $expr->args[0]->value;
				if (!$this->unit()->oracle->nativeType($target)->isArray()->yes() || $this->hasSideEffects($target)) {
					return null;
				}
				if (!$target instanceof Expr\PropertyFetch && !($target instanceof Expr\Variable && is_string($target->name) && in_array(($f->locals[$target->name] ?? null)?->kind, ['zval', 'ref'], true))) {
					return null;
				}
				$array = $this->writablePtr($f, $target, true);
				$t = $f->temp('r');
				$f->line(sprintf('zv::Val %s;', $t));
				$f->line(sprintf('pgrt::arrayPop(%s, %s.raw());', $array, $t));
				return $this->fromEngineResult($t, $native);
			case 'min':
			case 'max':
				if (!$single) {
					return null;
				}
				$array = $this->asZval($f, $this->expr($f, $expr->args[0]->value));
				$site = $this->site('pgrt::FunctionSite', 'function_' . $name);
				$t = $f->temp('r');
				$f->line(sprintf('zv::Val %s;', $t));
				$f->failIf(sprintf('!pgrt::arrayMinMax(%s, %s, %s, %s, %s.raw())', $site, $name === 'max' ? 'true' : 'false', $array, $this->unit()->ce, $t));
				return $this->fromEngineResult($t, $native);
		}
		return null;
	}

	/**
	 * The arguments when each is positional and not unpacked.
	 *
	 * @param array<Node\Arg|Node\VariadicPlaceholder> $args
	 * @return list<Node\Arg>|null
	 */
	private function plainArgs(array $args): ?array
	{
		$plain = [];
		foreach ($args as $arg) {
			if (!$arg instanceof Node\Arg || $arg->unpack || $arg->name !== null) {
				return null;
			}
			$plain[] = $arg;
		}
		return $plain;
	}

	/**
	 * sprintf() of a literal format whose only conversions are %s, %d and
	 * %%: the pieces appended in order (%s converts like a string cast,
	 * %d like an int cast — what sprintf() does for them).
	 */
	private function sprintfIntrinsic(FunctionContext $f, Expr\FuncCall $expr): ?Value
	{
		$args = $this->plainArgs($expr->args);
		if ($args === null || $args === [] || !$args[0]->value instanceof Node\Scalar\String_) {
			return null;
		}
		$format = $args[0]->value->value;
		if (preg_match_all('/%(.)/', $format, $conversions) === false) {
			return null;
		}
		$pieces = preg_split('/(%[sd%])/', $format, flags: PREG_SPLIT_DELIM_CAPTURE);
		if ($pieces === false) {
			return null;
		}
		$count = 0;
		foreach ($conversions[1] as $conversion) {
			if ($conversion === '%') {
				continue;
			}
			if (!in_array($conversion, ['s', 'd'], true)) {
				return null;
			}
			$count++;
		}
		if ($count !== count($args) - 1) {
			return null;
		}
		$values = [];
		foreach (array_slice($args, 1) as $i => $arg) {
			$value = $this->expr($f, $arg->value);
			$values[] = $i < $count - 1 ? $this->stabilize($f, $value) : $value;
		}
		$builder = $f->temp('sb');
		$f->line(sprintf('pgrt::StringBuilder %s;', $builder));
		$next = 0;
		foreach ($pieces as $piece) {
			if ($piece === '') {
				continue;
			}
			if ($piece === '%%') {
				$f->line(sprintf('%s.appendLiteral("%%", 1);', $builder));
			} elseif ($piece === '%s') {
				$f->failIf(sprintf('!%s.append(%s)', $builder, $this->asZval($f, $values[$next++])));
			} elseif ($piece === '%d') {
				$value = $values[$next++];
				$f->line(sprintf('%s.appendLong(%s);', $builder, $value->kind === 'long' ? $value->code : sprintf('zval_get_long(%s)', $this->asZval($f, $value))));
			} else {
				$f->line(sprintf('%s.appendLiteral("%s", %d);', $builder, self::cString($piece), strlen($piece)));
			}
		}
		$t = $f->temp('r');
		$f->line(sprintf('zv::Val %s = %s.finish();', $t, $builder));
		return new Value('zval', $t . '.raw()', $t);
	}

	/**
	 * in_array($needle, $haystack, true): identity against each element,
	 * unrolled for a literal list of scalars.
	 */
	private function inArrayIntrinsic(FunctionContext $f, Expr\FuncCall $expr): ?Value
	{
		if (count($expr->args) !== 3) {
			return null;
		}
		$args = $this->plainArgs($expr->args);
		if ($args === null) {
			return null;
		}
		$strict = $args[2]->value;
		if (!$strict instanceof Expr\ConstFetch || $strict->name->toLowerString() !== 'true') {
			return null;
		}
		$haystack = $args[1]->value;
		if ($haystack instanceof Expr\Array_) {
			foreach ($haystack->items as $item) {
				if ($item->key !== null || $item->byRef || $item->unpack || !$item->value instanceof Node\Scalar\String_) {
					return null;
				}
			}
		} elseif (!$this->unit()->oracle->nativeType($haystack)->isArray()->yes()) {
			return null;
		}
		$needle = $this->asZval($f, $this->stabilize($f, $this->expr($f, $args[0]->value)));
		if ($haystack instanceof Expr\Array_) {
			$items = [];
			foreach ($haystack->items as $item) {
				if (!$item->value instanceof Node\Scalar\String_) {
					continue;
				}
				$items[] = $this->asZval($f, $this->literal($item->value->value));
			}
			$t = $f->temp('ia');
			$f->line(sprintf('bool %s = false;', $t));
			if ($items !== []) {
				$f->line(sprintf('if (Z_TYPE_P(%s) == IS_STRING) %s = %s;', $needle, $t, implode(' || ', array_map(static fn (string $item): string => sprintf('zend_string_equals(Z_STR_P(%s), Z_STR_P(%s))', $needle, $item), $items))));
			}
			return Value::bool($t);
		}
		$array = $this->asZval($f, $this->expr($f, $haystack));
		return Value::bool(sprintf('pgrt::inArrayStrict(%s, %s)', $needle, $array));
	}

	/** a callback-taking builtin is scope-independent when the callable it receives is a closure */
	private function callbacksAreClosures(Expr\FuncCall $expr, string $name): bool
	{
		$positions = ['array_map' => 0, 'array_filter' => 1, 'usort' => 1, 'uasort' => 1, 'uksort' => 1, 'array_walk' => 1, 'array_reduce' => 1, 'call_user_func' => 0, 'call_user_func_array' => 0];
		if (!isset($positions[$name])) {
			return false;
		}
		$arg = $expr->args[$positions[$name]] ?? null;
		if (!$arg instanceof Node\Arg || $arg->unpack) {
			return false;
		}
		return $arg->value instanceof Expr\ArrowFunction || $arg->value instanceof Expr\Closure;
	}

	/**
	 * array_column($objects, 'property') over objects of a compiled class
	 * whose declared property the compiler reads by slot — what the builtin
	 * does, minus its dependence on the calling scope.
	 */
	private function arrayColumnIntrinsic(FunctionContext $f, Expr\FuncCall $expr): ?Value
	{
		if (count($expr->args) !== 2 || !$expr->args[0] instanceof Node\Arg || !$expr->args[1] instanceof Node\Arg || $expr->args[0]->unpack) {
			return null;
		}
		$column = $expr->args[1]->value;
		if (!$column instanceof Node\Scalar\String_) {
			return null;
		}
		$source = $expr->args[0]->value;
		$element = $this->unit()->oracle->nativeType($source)->getIterableValueType();
		$class = $this->knownClass($element);
		$unit = $class !== null ? ($this->units[strtolower($class)] ?? null) : null;
		$property = $column->value;
		if ($unit === null || !$unit->reflection->isFinal() || !$unit->reflection->hasProperty($property) || $unit->reflection->getProperty($property)->isStatic()) {
			return null;
		}
		$t = $f->temp('col');
		$f->line(sprintf('zv::Val %s = zv::Val::adopt([]() { zval a; array_init(&a); return a; }());', $t));
		$element = $f->temp('el');
		$className = $unit->className;
		if ($this->slotExpr($className, $property, 'NULL') === null) {
			return null;
		}
		$body = function (string $z) use ($f, $t, $className, $property): void {
			$p = $f->temp('p');
			$slot = $this->slotExpr($className, $property, sprintf('Z_OBJ_P(%s)', $z));
			$f->line(sprintf('zval *%s = pgrt::readSlot(Z_OBJ_P(%s), %s, "%s");', $p, $z, $slot, $property));
			$f->failIf(sprintf('%s == NULL', $p));
			$f->line(sprintf('Z_TRY_ADDREF_P(%s);', $p));
			$f->line(sprintf('zend_hash_next_index_insert(Z_ARRVAL_P(%s.raw()), %s);', $t, $p));
		};
		if ($source instanceof Expr\Variable && is_string($source->name) && ($f->locals[$source->name] ?? null)?->kind === 'vector') {
			$vector = $f->locals[$source->name]->cName;
			$i = $f->temp('i');
			$f->open(sprintf('for (uint32_t %s = 0; %s < %s_count; %s++)', $i, $i, $vector, $i));
			$f->line(sprintf('zval *%s = zv::Ref(&%s[%s]).deref().raw();', $element, $vector, $i));
			$body($element);
			$f->close();
		} else {
			$array = $this->asZval($f, $this->expr($f, $source));
			$f->line(sprintf('zval *%s;', $element));
			$f->open(sprintf('ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(%s), %s)', $array, $element));
			$f->line(sprintf('ZVAL_DEREF(%s);', $element));
			// a non-object row is skipped by the builtin; rows of the class are read by slot
			$f->line(sprintf('if (Z_TYPE_P(%s) != IS_OBJECT || Z_OBJCE_P(%s) != %s) continue;', $element, $element, $unit->ce));
			$body($element);
			$f->close();
			$f->line('ZEND_HASH_FOREACH_END();');
		}
		return new Value('zval', $t . '.raw()', $t, isArray: true);
	}

	private function fromEngineResult(string $temp, Type $native): Value
	{
		if ($native->isInteger()->yes()) {
			return Value::long(sprintf('Z_LVAL_P(%s.raw())', $temp));
		}
		if ($native->isBoolean()->yes()) {
			return Value::bool(sprintf('(Z_TYPE_P(%s.raw()) == IS_TRUE)', $temp));
		}
		if ($native->isVoid()->yes()) {
			return Value::void();
		}
		return new Value('zval', $temp . '.raw()', $temp, $this->knownClass($native), $native->isArray()->yes());
	}

	/** @param array<Node\Arg|Node\VariadicPlaceholder> $args */
	private function callHasSideEffects(array $args): bool
	{
		foreach ($args as $arg) {
			if (!$arg instanceof Node\Arg) {
				throw new CompileError('first-class callables are not supported');
			}
			if ($this->hasSideEffects($arg->value)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * The C++ arguments of a direct call: each value converted to the
	 * callee's parameter kind — checked at run time where the native types
	 * do not prove it — a default for an omitted optional parameter, the
	 * variadic tail as a zval vector.
	 *
	 * @param array<Node\Arg|Node\VariadicPlaceholder> $args
	 * @return list<string>
	 */
	private function directArgs(FunctionContext $f, MethodInfo $method, array $args): array
	{
		$hasSpread = false;
		foreach ($args as $arg) {
			if (!$arg instanceof Node\Arg) {
				throw new CompileError('first-class callables are not supported');
			}
			if (!$arg->unpack) {
				continue;
			}

			$hasSpread = true;
		}

		if ($hasSpread) {
			foreach ($args as $arg) {
				if ($arg->name !== null) {
					throw new CompileError('spread and named arguments together are not supported');
				}
			}
			return $this->directArgsFromVector($f, $method, $args);
		}

		return $this->arrangeArgs($f, $method, array_values($args), $this->lowerArgs($f, array_values($args), $this->methodReflection($method->unit->className, $method->name)));
	}

	/**
	 * Already evaluated arguments (source order) converted to a direct
	 * call's parameters: named arguments on their parameter's position,
	 * defaults for omitted optional ones, the variadic tail as a vector.
	 *
	 * @param list<Node\Arg> $args
	 * @param list<Value> $evaluated
	 * @return list<string>
	 */
	private function arrangeArgs(FunctionContext $f, MethodInfo $method, array $args, array $evaluated): array
	{
		$label = $this->label($method);
		$values = [];
		$arranged = [];
		foreach ($args as $i => $arg) {
			if ($arg->name === null) {
				$values[count($arranged)] = $evaluated[$i];
				$arranged[count($arranged)] = $arg;
				continue;
			}
			$position = null;
			foreach ($method->params as $param) {
				if ($param->name !== $arg->name->toString() || $param->variadic) {
					continue;
				}
				$position = $param->position;
			}
			if ($position === null) {
				throw new CompileError(sprintf('unknown named argument $%s for %s()', $arg->name->toString(), $label));
			}
			$values[$position] = $evaluated[$i];
			$arranged[$position] = $arg;
		}
		$out = [];
		foreach ($method->params as $param) {
			if ($param->variadic) {
				ksort($values);
				ksort($arranged);
				$rest = array_values(array_slice($values, $param->position, preserve_keys: true));
				$restExprs = array_values(array_slice($arranged, $param->position, preserve_keys: true));
				if ($rest === []) {
					$out[] = 'NULL';
					$out[] = '0';
					continue;
				}
				$vec = $f->temp('va');
				$f->line(sprintf('zval %s[%d];', $vec, count($rest)));
				foreach ($rest as $i => $value) {
					$z = $this->asZval($f, $value);
					$argExpr = $restExprs[$i];
					if ($param->class !== null && !$param->declared->isSuperTypeOf($this->unit()->oracle->nativeType($argExpr->value))->yes()) {
						$this->emitTypeCheck($f, $z, $param->describe, $param->declared, sprintf('pgrt::throwArgumentType("%s", %d, "%s", "%s", %s); %s', addcslashes($label, '\\'), $param->position + $i + 1, $param->name, addcslashes($param->describe, '\\'), $z, $f->fail));
					}
					$f->line(sprintf('ZVAL_COPY_VALUE(&%s[%d], %s);', $vec, $i, $z));
				}
				$out[] = $vec;
				$out[] = (string) count($rest);
				continue;
			}
			if (!isset($values[$param->position])) {
				if ($param->default === null) {
					throw new CompileError(sprintf('missing argument $%s for %s()', $param->name, $label));
				}
				$out[] = $this->defaultArg($f, $param);
				continue;
			}
			$out[] = $this->convertArg($f, $values[$param->position], $arranged[$param->position]->value, $param, $label);
		}
		// surplus arguments to a user function are evaluated above and ignored
		return $out;
	}

	/**
	 * @param array<Node\Arg> $args
	 * @return list<Value>
	 */
	private function lowerArgs(FunctionContext $f, array $args, ?ReflectionFunctionAbstract $callee = null): array
	{
		$holders = $this->holderPositions($args, $callee);
		$values = [];
		foreach ($args as $i => $arg) {
			$this->closureAsHolder = isset($holders[$i]);
			$value = $this->expr($f, $arg->value);
			$this->closureAsHolder = false;
			$laterHaveEffects = false;
			foreach (array_slice($args, $i + 1) as $later) {
				if (!$this->hasSideEffects($later->value)) {
					continue;
				}

				$laterHaveEffects = true;
			}
			if ($laterHaveEffects) {
				$value = $this->stabilize($f, $value);
			}
			$values[] = $value;
		}
		return $values;
	}

	/**
	 * The parameters and captured variables (by name) a function body never
	 * writes: no assignment, increment, foreach/catch/list target, unset,
	 * global/static, capture by reference or by-reference argument anywhere
	 * in it (nested closures included). Their values cannot change while an
	 * argument list is evaluated, so they need no pinning copy.
	 *
	 * @param array<Node\Stmt> $stmts
	 * @param list<string> $candidates
	 * @return array<string, true>
	 */
	private function neverWrittenVariables(array $stmts, array $candidates): array
	{
		$written = [];
		$mark = static function (?Node $target) use (&$written): void {
			if ($target === null) {
				return;
			}
			foreach ((new NodeFinder())->findInstanceOf([$target], Expr\Variable::class) as $variable) {
				if (!is_string($variable->name)) {
					continue;
				}
				$written[$variable->name] = true;
			}
		};
		$dynamic = false;
		foreach ($this->finder->find($stmts, static fn (Node $node): bool => true) as $node) {
			if ($node instanceof Expr\Assign || $node instanceof Expr\AssignRef || $node instanceof Expr\AssignOp) {
				$mark($node->var);
			} elseif ($node instanceof Expr\PreInc || $node instanceof Expr\PreDec || $node instanceof Expr\PostInc || $node instanceof Expr\PostDec) {
				$mark($node->var);
			} elseif ($node instanceof Node\Stmt\Foreach_) {
				$mark($node->keyVar);
				$mark($node->valueVar);
			} elseif ($node instanceof Node\Stmt\Catch_) {
				$mark($node->var);
			} elseif ($node instanceof Node\Stmt\Unset_ || $node instanceof Node\Stmt\Global_ || $node instanceof Node\Stmt\Static_) {
				$mark($node);
			} elseif ($node instanceof Expr\ClosureUse && $node->byRef) {
				$mark($node->var);
			} elseif ($node instanceof Node\Arg && $node->byRef) {
				$mark($node->value);
			} elseif ($node instanceof Expr\Variable && !is_string($node->name)) {
				$dynamic = true;
			} elseif ($node instanceof Expr\FuncCall && $node->name instanceof Node\Name && in_array($node->name->toLowerString(), ['extract', 'compact', 'get_defined_vars', 'parse_str'], true)) {
				$dynamic = true;
			} elseif ($node instanceof Expr\CallLike && !$node->isFirstClassCallable()) {
				// a by-reference parameter of the callee; an unknown callee may have one
				$callee = $this->calleeReflection($node);
				foreach ($node->getArgs() as $i => $arg) {
					if (!$arg->value instanceof Expr\Variable) {
						continue;
					}
					$parameter = $callee !== null ? ($arg->name !== null ? $this->methodParameterByName($callee, $arg->name->toString()) : ($callee->getParameters()[$i] ?? null)) : null;
					if ($callee !== null && !$arg->unpack && ($parameter === null || !$parameter->isPassedByReference()) && ($parameter !== null || !$callee->isVariadic())) {
						continue;
					}

					$mark($arg->value);
				}
			}
		}
		if ($dynamic) {
			return [];
		}
		$stable = [];
		foreach ($candidates as $name) {
			if (isset($written[$name])) {
				continue;
			}

			$stable[$name] = true;
		}
		return $stable;
	}

	private function methodParameterByName(ReflectionFunctionAbstract $callee, string $name): ?ReflectionParameter
	{
		foreach ($callee->getParameters() as $parameter) {
			if ($parameter->getName() === $name) {
				return $parameter;
			}
		}
		return null;
	}

	/**
	 * The variables of a method body assigned a closure exactly once and
	 * otherwise only called (`$cb(...)`), captured by value (`use ($cb)`)
	 * or passed to a parameter declared `callable` — anywhere in the body,
	 * nested closures included: such a closure never needs to be a real
	 * Closure, so it stays a native closure holder.
	 *
	 * @param array<Node\Stmt> $stmts
	 * @return array<string, true>
	 */
	private function closureHolderVariables(array $stmts): array
	{
		$candidates = [];
		foreach ($this->finder->findInstanceOf($stmts, Expr\Assign::class) as $assign) {
			if (!$assign->var instanceof Expr\Variable || !is_string($assign->var->name)) {
				continue;
			}
			if (!$assign->expr instanceof Expr\Closure && !$assign->expr instanceof Expr\ArrowFunction) {
				continue;
			}
			$candidates[$assign->var->name][] = $assign->var;
		}
		if ($candidates === []) {
			return [];
		}
		// the occurrences that keep a holder valid
		$safe = [];
		foreach ($candidates as $targets) {
			foreach ($targets as $target) {
				$safe[spl_object_id($target)] = true;
			}
		}
		foreach ($this->finder->find($stmts, static fn (Node $node): bool => $node instanceof Expr\FuncCall || $node instanceof Expr\ClosureUse || $node instanceof Expr\CallLike) as $node) {
			if ($node instanceof Expr\FuncCall && $node->name instanceof Expr\Variable) {
				$safe[spl_object_id($node->name)] = true;
			}
			if ($node instanceof Expr\ClosureUse && !$node->byRef) {
				$safe[spl_object_id($node->var)] = true;
			}
			if (!$node instanceof Expr\CallLike || $node->isFirstClassCallable()) {
				continue;
			}
			$callee = $this->calleeReflection($node);
			$args = array_values(array_filter($node->getArgs(), static fn (Node\Arg $arg): bool => !$arg->unpack));
			foreach (array_keys($this->holderPositions($args, $callee, true)) as $position) {
				$safe[spl_object_id($args[$position]->value)] = true;
			}
		}
		$holders = [];
		foreach (array_keys($candidates) as $name) {
			if (count($candidates[$name]) !== 1) {
				continue;
			}
			$ok = true;
			foreach ($this->finder->find($stmts, static fn (Node $node): bool => $node instanceof Expr\Variable && $node->name === $name) as $occurrence) {
				if (!isset($safe[spl_object_id($occurrence)])) {
					$ok = false;
					break;
				}
			}
			if (!$ok) {
				continue;
			}

			$holders[$name] = true;
		}
		return $holders;
	}

	/** the function or method a call resolves to statically, null when unknown */
	private function calleeReflection(Expr\CallLike $call): ?ReflectionFunctionAbstract
	{
		if ($call instanceof Expr\FuncCall && $call->name instanceof Node\Name) {
			$name = $call->name->toString();
			$resolved = $call->name->getAttribute('resolvedName');
			if ($resolved instanceof Node\Name) {
				$name = $resolved->toString();
			}
			if (!function_exists($name)) {
				$name = substr($name, (int) strrpos($name, '\\') + ((int) strrpos($name, '\\') > 0 ? 1 : 0));
			}
			return function_exists($name) ? new ReflectionFunction($name) : null;
		}
		if (($call instanceof Expr\MethodCall || $call instanceof Expr\NullsafeMethodCall) && $call->name instanceof Node\Identifier) {
			$class = $this->knownClass($this->unit()->oracle->nativeType($call->var)) ?? $this->knownClass($this->unit()->oracle->phpDocType($call->var));
			return $class !== null ? $this->methodReflection($class, $call->name->toString()) : null;
		}
		if ($call instanceof Expr\StaticCall && $call->name instanceof Node\Identifier && $call->class instanceof Node\Name) {
			$lower = $call->class->toLowerString();
			$class = in_array($lower, ['self', 'static'], true) ? $this->unit()->className : ($lower === 'parent' ? (string) get_parent_class($this->unit()->className) : $this->className($call->class));
			return $class !== '' ? $this->methodReflection($class, $call->name->toString()) : null;
		}
		if ($call instanceof Expr\New_ && $call->class instanceof Node\Name) {
			$lower = $call->class->toLowerString();
			// new static: this class's constructor (a subclass's takes the same arguments by the LSP-free convention the twin relies on too)
			$class = in_array($lower, ['self', 'static'], true) ? $this->unit()->className : $this->className($call->class);
			return class_exists($class) ? (new ReflectionClass($class))->getConstructor() : null;
		}
		return null;
	}

	/**
	 * The arguments that are inline closures passed to a parameter declared
	 * `callable`: they stay native closure holders (callable themselves)
	 * instead of becoming a Closure — what the hand ports pass, and what a
	 * native ExpressionResult calls without the engine.
	 *
	 * @param array<Node\Arg> $args
	 * @return array<int, true>
	 */
	private function holderPositions(array $args, ?ReflectionFunctionAbstract $callee, bool $anyValue = false): array
	{
		if ($callee === null) {
			return [];
		}
		$parameters = $callee->getParameters();
		$positions = [];
		foreach (array_values($args) as $i => $arg) {
			if (!$anyValue && !$arg->value instanceof Expr\Closure && !$arg->value instanceof Expr\ArrowFunction) {
				continue;
			}
			$parameter = null;
			if ($arg->name !== null) {
				foreach ($parameters as $candidate) {
					if ($candidate->getName() !== $arg->name->toString()) {
						continue;
					}

					$parameter = $candidate;
				}
			} else {
				$parameter = $parameters[$i] ?? (count($parameters) > 0 && $parameters[count($parameters) - 1]->isVariadic() ? $parameters[count($parameters) - 1] : null);
			}
			$type = $parameter?->getType();
			if (!($type instanceof ReflectionNamedType) || $type->getName() !== 'callable') {
				continue;
			}

			$positions[$i] = true;
		}
		return $positions;
	}

	/**
	 * Direct-call arguments that include a spread: the whole argument list
	 * is assembled at run time and distributed over the parameters, every
	 * value checked against its parameter type.
	 *
	 * @param array<Node\Arg> $args
	 * @return list<string>
	 */
	private function directArgsFromVector(FunctionContext $f, MethodInfo $method, array $args): array
	{
		$vec = $this->argVector($f, $args);
		return $this->distributeVector($f, $method, $vec . '.argv()', $vec . '.size()');
	}

	/**
	 * Distributes a run-time argument vector over a method's parameters,
	 * checking every value against its parameter type.
	 *
	 * @return list<string>
	 */
	private function distributeVector(FunctionContext $f, MethodInfo $method, string $argv, string $size): array
	{
		$label = $this->label($method);
		$out = [];
		foreach ($method->params as $param) {
			$n = $param->position;
			if ($param->variadic) {
				$count = $f->temp('vn');
				$f->line(sprintf('uint32_t %s = %s > %d ? %s - %d : 0;', $count, $size, $n, $size, $n));
				if ($param->class !== null) {
					$i = $f->temp('i');
					$f->open(sprintf('for (uint32_t %s = 0; %s < %s; %s++)', $i, $i, $count, $i));
					$z = sprintf('zv::Ref(&%s[%d + %s]).deref().raw()', $argv, $n, $i);
					$this->emitTypeCheck($f, $z, $param->describe, $param->declared, sprintf('pgrt::throwArgumentType("%s", %d + %s + 1, "%s", "%s", %s); %s', addcslashes($label, '\\'), $n, $i, $param->name, addcslashes($param->describe, '\\'), $z, $f->fail));
					$f->close();
				}
				$out[] = sprintf('%s > 0 ? &%s[%d] : NULL', $count, $argv, $n);
				$out[] = $count;
				continue;
			}
			$p = $f->temp('pa');
			$f->line(sprintf('zval *%s;', $p));
			// the default lives in the enclosing block: the pointer outlives the else branch
			$default = $param->default !== null ? $this->defaultArg($f, $param, true) : null;
			$f->open(sprintf('if (%s > %d)', $size, $n));
			$f->line(sprintf('%s = zv::Ref(&%s[%d]).deref().raw();', $p, $argv, $n));
			$this->emitTypeCheck($f, $p, $param->describe, $param->declared, sprintf('pgrt::throwArgumentType("%s", %d, "%s", "%s", %s); %s', addcslashes($label, '\\'), $n + 1, $param->name, addcslashes($param->describe, '\\'), $p, $f->fail));
			$f->close(' else {');
			$f->open('');
			if ($param->default === null) {
				$f->line(sprintf('pgrt::throwTooFewArguments("%s", %s, %d);', addcslashes($label, '\\'), $size, $n + 1));
				$f->line($f->fail);
			} else {
				$f->line(sprintf('%s = %s;', $p, $default));
			}
			$f->close();
			$f->line('}');
			switch ($param->kind) {
				case 'long':
					$out[] = sprintf('Z_LVAL_P(%s)', $p);
					break;
				case 'bool':
					$out[] = sprintf('(Z_TYPE_P(%s) == IS_TRUE)', $p);
					break;
				case 'callable':
					throw new CompileError('spreading into a callable parameter is not supported');
				default:
					$out[] = $p;
			}
		}
		return $out;
	}

	/**
	 * Engine-call arguments: [argv expression, argc expression, named-arguments HashTable * or NULL].
	 *
	 * @param array<Node\Arg|Node\VariadicPlaceholder> $args
	 * @return array{string, string, string}
	 */
	private function engineArgs(FunctionContext $f, array $args, ?ReflectionFunctionAbstract $callee = null): array
	{
		$hasNamed = false;
		foreach ($args as $arg) {
			if (!$arg instanceof Node\Arg) {
				throw new CompileError('first-class callables are not supported');
			}
			if ($arg->name === null) {
				continue;
			}

			$hasNamed = true;
		}
		$plain = array_values($args);
		foreach ($plain as $arg) {
			if ($arg->unpack) {
				if ($hasNamed) {
					throw new CompileError('spread and named arguments together are not supported');
				}
				$vec = $this->argVector($f, $plain);
				return [$vec . '.argv()', $vec . '.size()', 'NULL'];
			}
		}
		if ($args === []) {
			return ['NULL', '0', 'NULL'];
		}
		$values = $this->lowerArgs($f, $plain, $callee);
		if ($callee !== null) {
			// by-reference parameters of the callee get a temporary reference
			// written back after the call (flushWriteBacks() at the call site)
			$parameters = $callee->getParameters();
			foreach ($plain as $i => $arg) {
				if ($arg->name !== null) {
					continue;
				}
				$parameter = $parameters[$i] ?? (count($parameters) > 0 && $parameters[count($parameters) - 1]->isVariadic() ? $parameters[count($parameters) - 1] : null);
				if ($parameter === null || !$parameter->isPassedByReference()) {
					continue;
				}
				$values[$i] = new Value('zval', $this->byRefArg($f, $arg->value));
			}
		}
		$positional = [];
		$named = [];
		foreach ($plain as $i => $arg) {
			if ($arg->name !== null) {
				$named[$arg->name->toString()] = $values[$i];
			} else {
				if ($named !== []) {
					throw new CompileError('a positional argument after a named one');
				}
				$positional[] = $values[$i];
			}
		}
		$argv = 'NULL';
		if ($positional !== []) {
			$argv = $f->temp('av');
			$f->line(sprintf('zval %s[%d];', $argv, count($positional)));
			foreach ($positional as $i => $value) {
				$f->line(sprintf('ZVAL_COPY_VALUE(&%s[%d], %s);', $argv, $i, $this->asZval($f, $value)));
			}
		}
		$namedTable = 'NULL';
		if ($named !== []) {
			$table = $f->temp('na');
			$f->line(sprintf('zv::Arr %s = zv::Arr::create(%d);', $table, count($named)));
			foreach ($named as $name => $value) {
				$f->line(sprintf('{ zval v; ZVAL_COPY(&v, %s); zend_hash_str_add_new(%s.table(), "%s", %d, &v); }', $this->asZval($f, $value), $table, $name, strlen($name)));
			}
			$namedTable = $table . '.table()';
		}
		return [$argv, (string) count($positional), $namedTable];
	}

	/** @param array<Node\Arg> $args */
	private function argVector(FunctionContext $f, array $args): string
	{
		$vec = $f->temp('vec');
		$values = [];
		foreach ($args as $i => $arg) {
			if ($arg->unpack && $arg->value instanceof Expr\Variable && is_string($arg->value->name) && ($f->locals[$arg->value->name] ?? null)?->kind === 'vector') {
				$values[] = ['vector', $f->locals[$arg->value->name]->cName];
				continue;
			}
			if ($arg->unpack && $arg->value instanceof Expr\Variable && is_string($arg->value->name) && ($f->locals[$arg->value->name] ?? null)?->kind === 'list') {
				$values[] = ['list', $f->locals[$arg->value->name]->cName];
				continue;
			}
			$value = $this->expr($f, $arg->value);
			$laterHaveEffects = false;
			foreach (array_slice($args, $i + 1) as $later) {
				if (!$this->hasSideEffects($later->value)) {
					continue;
				}

				$laterHaveEffects = true;
			}
			if ($laterHaveEffects || $arg->unpack) {
				$value = $this->stabilize($f, $value);
			}
			$values[] = [$arg->unpack ? 'spread' : 'value', $value];
		}
		$f->line(sprintf('pgrt::ArgVec %s;', $vec));
		foreach ($values as [$kind, $value]) {
			if ($kind === 'vector') {
				$f->line(sprintf('for (uint32_t i = 0; i < %s_count; i++) %s.push(&%s[i]);', $value, $vec, $value));
			} elseif ($kind === 'list') {
				$f->line(sprintf('for (uint32_t i = 0; i < %s.size(); i++) %s.push(&%s.argv()[i]);', $value, $vec, $value));
			} elseif ($kind === 'spread') {
				$z = $this->asZval($f, $value);
				$f->line(sprintf('if (UNEXPECTED(Z_TYPE_P(%s) != IS_ARRAY)) { zend_type_error("Only arrays and Traversables can be unpacked, %%s given", zend_zval_value_name(%s)); %s }', $z, $z, $f->fail));
				$f->line(sprintf('%s.spread(%s);', $vec, $z));
			} else {
				$f->line(sprintf('%s.push(%s);', $vec, $this->asZval($f, $value)));
			}
		}
		return $vec;
	}

	private function defaultArg(FunctionContext $f, ParamInfo $param, bool $asZval = false): string
	{
		$default = $param->default;
		if ($default === null) {
			throw new CompileError('no default');
		}
		if (!$asZval) {
			if ($param->kind === 'long') {
				return $this->constantLong($default);
			}
			if ($param->kind === 'bool') {
				return $this->constantBool($default);
			}
		}
		$t = $f->temp('d');
		$f->line(sprintf('zval %s;', $t));
		$f->line($this->constantZvalInit($t, $default));
		if ($param->byRef) {
			// an omitted by-reference parameter binds a fresh reference to its default
			$f->line(sprintf('zv::Val %s_ref = zv::Val::adopt([&]() { zval r; ZVAL_NEW_REF(&r, &%s); return r; }());', $t, $t));
			return $t . '_ref.raw()';
		}
		return '&' . $t;
	}

	/**
	 * A by-reference argument: a temporary reference holding the target's
	 * current value, written back to the target after the call (registered
	 * in $f->writeBacks). Returns the reference's `zval *`.
	 */
	private function byRefArg(FunctionContext $f, Expr $target): string
	{
		if (!($target instanceof Expr\Variable || $target instanceof Expr\PropertyFetch || $target instanceof Expr\ArrayDimFetch || $target instanceof Expr\StaticPropertyFetch)) {
			throw new CompileError('a by-reference argument that is not a variable, property or offset');
		}
		if ($this->hasSideEffects($target)) {
			throw new CompileError('a by-reference argument with side effects');
		}
		$current = $this->issetPtr($f, $target);
		$ref = $f->temp('ref');
		if (str_starts_with($current, '&')) {
			// the address of a temporary: never NULL
			$f->line(sprintf('zv::Val %s = zv::Val::adopt([&]() { zval inner; ZVAL_COPY(&inner, %s); zval r; ZVAL_NEW_REF(&r, &inner); return r; }());', $ref, $current));
		} else {
			$f->line(sprintf('zv::Val %s = zv::Val::adopt([&]() { zval inner; if (%s != NULL) { ZVAL_COPY(&inner, %s); } else { ZVAL_NULL(&inner); } zval r; ZVAL_NEW_REF(&r, &inner); return r; }());', $ref, $current, $current));
		}
		$f->writeBacks[] = function () use ($f, $target, $ref): void {
			$f->open('');
			if ($target instanceof Expr\Variable) {
				$this->assignTo($f, $target, sprintf('Z_REFVAL_P(%s.raw())', $ref));
			} else {
				$ptr = $this->writablePtr($f, $target, false);
				$f->line(sprintf('zv::Ref(%s).assign(zv::Val::copyOf(zv::Ref(Z_REFVAL_P(%s.raw()))));', $ptr, $ref));
			}
			$f->close();
		};
		return $ref . '.raw()';
	}

	/** runs the write-backs of the by-reference arguments of the call just emitted */
	private function flushWriteBacks(FunctionContext $f): void
	{
		$writeBacks = $f->writeBacks;
		$f->writeBacks = [];
		foreach ($writeBacks as $writeBack) {
			$writeBack();
		}
	}

	private function convertArg(FunctionContext $f, Value $value, Expr $argExpr, ParamInfo $param, string $label): string
	{
		if ($param->byRef) {
			return $this->byRefArg($f, $argExpr);
		}
		$proven = $param->declared->isSuperTypeOf($this->unit()->oracle->nativeType($argExpr))->yes();
		$fail = static fn (string $z): string => sprintf('pgrt::throwArgumentType("%s", %d, "%s", "%s", %s); %s', addcslashes($label, '\\'), $param->position + 1, $param->name, addcslashes($param->describe, '\\'), $z, $f->fail);
		switch ($param->kind) {
			case 'long':
				if ($value->kind === 'long') {
					return $value->code;
				}
				$z = $this->asZval($f, $value);
				if (!$proven) {
					$f->line(sprintf('if (UNEXPECTED(Z_TYPE_P(%s) != IS_LONG)) { %s }', $z, $fail($z)));
				}
				return sprintf('Z_LVAL_P(%s)', $z);
			case 'bool':
				if ($value->kind === 'bool') {
					return $value->code;
				}
				$z = $this->asZval($f, $value);
				if (!$proven) {
					$f->line(sprintf('if (UNEXPECTED(Z_TYPE_P(%s) != IS_TRUE && Z_TYPE_P(%s) != IS_FALSE)) { %s }', $z, $z, $fail($z)));
				}
				return sprintf('(Z_TYPE_P(%s) == IS_TRUE)', $z);
			case 'callable':
				if ($argExpr instanceof Expr\Variable && is_string($argExpr->name) && ($f->locals[$argExpr->name] ?? null)?->kind === 'callable') {
					return $f->locals[$argExpr->name]->cName;
				}
				$callable = $f->temp('cb');
				$f->line(sprintf('pgrt::Callable %s;', $callable));
				$f->failIf(sprintf('!pgrt::callableOf(%s, %s)', $this->asZval($f, $value), $callable));
				return $callable;
			default:
				$z = $this->asZval($f, $value);
				if (!$proven) {
					$this->emitTypeCheck($f, $z, $param->describe, $param->declared, $fail($z));
				}
				return $z;
		}
	}

	/** a vendored class the extension shadows (its pair hardcoded in TurboAttributeCollector) */
	private function isVendoredShadow(string $className): bool
	{
		if (!class_exists($className) || ($this->units[strtolower($className)] ?? null) === null && $this->shadowCe($className) === null) {
			return false;
		}
		return str_contains(str_replace('\\', '/', (string) (new ReflectionClass($className))->getFileName()), '/vendor/');
	}

	/** whether a static method of a non-final class reads its called scope (static::class, new static): it needs its own frame */
	private function usesCalledScope(MethodInfo $method): bool
	{
		if ($method->unit->reflection->isFinal()) {
			return false;
		}
		return $this->finder->findFirst([$method->node], static fn (Node $n): bool => ($n instanceof Expr\ClassConstFetch || $n instanceof Expr\New_) && $n->class instanceof Node\Name && $n->class->toLowerString() === 'static') !== null;
	}

	/**
	 * @param list<string> $args
	 */
	private function emitDirectCall(FunctionContext $f, MethodInfo $method, ?string $self, array $args): Value
	{
		$callArgs = $self !== null ? [$self, ...$args] : $args;
		$call = sprintf('pgen::%s::%s', $method->unit->stem, $this->cppMethodName($method));
		$t = $f->temp('r');
		switch ($method->return->kind) {
			case 'long':
				$f->line(sprintf('zend_long %s;', $t));
				$f->failIf(sprintf('!%s(%s)', $call, implode(', ', [...$callArgs, '&' . $t])));
				$this->flushWriteBacks($f);
				return Value::long($t);
			case 'bool':
				$f->line(sprintf('bool %s;', $t));
				$f->failIf(sprintf('!%s(%s)', $call, implode(', ', [...$callArgs, '&' . $t])));
				$this->flushWriteBacks($f);
				return Value::bool($t);
			case 'zval':
				$f->line(sprintf('zv::Val %s;', $t));
				$f->failIf(sprintf('!%s(%s)', $call, implode(', ', [...$callArgs, $t . '.raw()'])));
				$this->flushWriteBacks($f);
				return new Value('zval', $t . '.raw()', $t, $method->return->nullable ? null : $method->return->class, $method->return->declared->isArray()->yes());
			default:
				$f->failIf(sprintf('!%s(%s)', $call, implode(', ', $callArgs)));
				$this->flushWriteBacks($f);
				return Value::void();
		}
	}

	/**
	 * A closure or arrow function: its body becomes a native closure body
	 * (pt_native_closure) over the captured values — by value as `use ($x)`
	 * and an arrow function copy them, by reference as `use (&$x)` binds
	 * them, `$this` when the body uses it — handed out as a real \\Closure.
	 */
	private function closureExpr(FunctionContext $f, Expr\ArrowFunction|Expr\Closure $expr): Value
	{
		// a closure nested in this one's body is a Closure again
		$asHolder = $this->closureAsHolder;
		$this->closureAsHolder = false;
		$label = $this->unit()->className . '::{closure}';
		$params = [];
		$paramNames = [];
		foreach ($expr->params as $i => $param) {
			if (!$param->var instanceof Expr\Variable || !is_string($param->var->name)) {
				throw new CompileError('closure parameter');
			}
			if ($param->byRef || $param->variadic) {
				throw new CompileError('by-reference and variadic closure parameters are not supported');
			}
			[$kind, $declared, $describe, $class, $nullable, $isArray] = $this->lowerAstType($param->type);
			if (in_array($kind, ['callable', 'void'], true)) {
				$kind = 'zval';
			}
			$params[] = new ParamInfo($param->var->name, $i, $kind, $declared, $describe, $class, $nullable, false, $param->default, $isArray, false, false);
			$paramNames[$param->var->name] = true;
		}

		// what the closure captures, in creation order
		$captures = [];
		if ($expr instanceof Expr\Closure) {
			foreach ($expr->uses as $use) {
				if (!is_string($use->var->name)) {
					throw new CompileError('closure use');
				}
				$captures[$use->var->name] = $use->byRef;
			}
		} else {
			foreach ($this->finder->findInstanceOf([$expr->expr], Expr\Variable::class) as $var) {
				if (!is_string($var->name) || isset($paramNames[$var->name]) || $var->name === 'this' || !isset($f->locals[$var->name])) {
					continue;
				}
				$captures[$var->name] = false;
			}
		}
		$body = $expr instanceof Expr\Closure ? $expr->stmts : [$expr->expr];
		$usesThis = !$expr->static && $this->finder->findFirst($body, static fn (Node $n): bool => $n instanceof Expr\Variable && $n->name === 'this') !== null;
		if ($usesThis && $f->self === null) {
			throw new CompileError('a closure using $this outside an instance method');
		}

		if ($expr->returnType !== null) {
			[$returnKind, $returnDeclared, $returnDescribe, $returnClass, $returnNullable] = $this->lowerAstType($expr->returnType);
			$return = $returnKind === 'void'
				? new ReturnInfo('void', $returnDeclared, 'void')
				: new ReturnInfo('zval', $returnDeclared, $returnDescribe, $returnClass, $returnNullable);
		} else {
			$return = new ReturnInfo('zval', new MixedType(), 'mixed');
		}

		$index = ++$this->closureCount;
		$fn = sprintf('pg_closure_%d', $index);
		$captureLocals = [];
		$k = 0;
		foreach ($captures as $name => $byRef) {
			$captureLocals[$name] = $byRef
				? new LocalVariable('ref', sprintf('&captures[%d]', $k))
				: new LocalVariable('param', sprintf('zv::Ref(&captures[%d]).deref().raw()', $k));
			$k++;
		}
		$thisIndex = $usesThis ? $k : null;

		$inner = new FunctionContext($label, 'return;', $thisIndex !== null ? sprintf('Z_OBJ(captures[%d])', $thisIndex) : null, $return, true);
		$required = 0;
		foreach ($params as $param) {
			if ($param->default !== null) {
				continue;
			}

			$required = $param->position + 1;
		}
		// the parameters: present arguments checked like the closure's signature, defaults for missing ones
		foreach ($params as $param) {
			$z = 'a_' . $param->name;
			$inner->line(sprintf('zval *%s;', $z));
			if ($param->default !== null) {
				$inner->line(sprintf('zval %s_default;', $z));
				$inner->line($this->constantZvalInit($z . '_default', $param->default));
				$inner->line(sprintf('%s = argc > %d ? zv::Ref(&argv[%d]).deref().raw() : &%s_default;', $z, $param->position, $param->position, $z));
			} else {
				$inner->line(sprintf('%s = zv::Ref(&argv[%d]).deref().raw();', $z, $param->position));
			}
			$this->emitTypeCheck($inner, $z, $param->describe, $param->declared, sprintf('pgrt::throwArgumentType("%s", %d, "%s", "%s", %s); return;', addcslashes($label, '\\'), $param->position + 1, $param->name, addcslashes($param->describe, '\\'), $z));
			switch ($param->kind) {
				case 'long':
					$inner->line(sprintf('zend_long p_%s = Z_LVAL_P(%s);', $param->name, $z));
					$inner->line(sprintf('(void) p_%s;', $param->name));
					break;
				case 'bool':
					$inner->line(sprintf('bool p_%s = Z_TYPE_P(%s) == IS_TRUE;', $param->name, $z));
					$inner->line(sprintf('(void) p_%s;', $param->name));
					break;
				default:
					$inner->line(sprintf('zval *p_%s = %s;', $param->name, $z));
					$inner->line(sprintf('(void) p_%s;', $param->name));
			}
		}
		$this->declareLocals($inner, $params, $expr instanceof Expr\Closure ? $expr->stmts : $expr->expr, $captureLocals);
		$inner->stableVars = $this->neverWrittenVariables($expr instanceof Expr\Closure ? $expr->stmts : [new Node\Stmt\Return_($expr->expr)], [...array_map(static fn (ParamInfo $p): string => $p->name, $params), ...array_keys($captures)]);
		if ($expr instanceof Expr\ArrowFunction) {
			$this->returnStatement($inner, $expr->expr);
		} else {
			$this->statements($inner, $expr->stmts);
		}
		$out = [];
		$out[] = sprintf('static void %s(zval *captures, uint32_t argc, zval *argv, zval *return_value)', $fn);
		$out[] = '{';
		$out[] = "\t(void) captures;";
		$out[] = "\t(void) argv;";
		$out[] = sprintf("\tif (UNEXPECTED(argc < %d)) { pgrt::throwTooFewArguments(\"%s\", argc, %d); return; }", $required, addcslashes($label, '\\'), $required);
		$out[] = $inner->render();
		$out[] = '}';
		$this->closures[] = implode("\n", $out);

		// the creation site
		$count = count($captures) + ($usesThis ? 1 : 0);
		$mask = 0;
		$argv = 'NULL';
		if ($count > 0) {
			$argv = $f->temp('cap');
			$f->line(sprintf('zval %s[%d];', $argv, $count));
			$k = 0;
			foreach ($captures as $name => $byRef) {
				$local = $f->locals[$name] ?? null;
				if ($local === null) {
					// an undefined variable is captured as null
					$f->line(sprintf('ZVAL_NULL(&%s[%d]);', $argv, $k));
				} elseif ($byRef) {
					if ($local->kind !== 'ref') {
						throw new CompileError(sprintf('$%s is captured by reference but has no reference home', $name));
					}
					$f->line(sprintf('if (!Z_ISREF_P(%s)) { if (Z_ISUNDEF_P(%s)) { ZVAL_NULL(%s); } ZVAL_MAKE_REF(%s); }', $local->cName, $local->cName, $local->cName, $local->cName));
					$f->line(sprintf('ZVAL_COPY_VALUE(&%s[%d], %s);', $argv, $k, $local->cName));
					$mask |= 1 << $k;
				} else {
					$f->line(sprintf('ZVAL_COPY_VALUE(&%s[%d], %s);', $argv, $k, $this->asZval($f, $this->readLocal($f, $name))));
				}
				$k++;
			}
			if ($usesThis) {
				$f->line(sprintf('ZVAL_COPY_VALUE(&%s[%d], &this_zv);', $argv, $k));
			}
		}
		$holder = $f->temp('h');
		$f->line(sprintf('zv::Val %s = pt_native_closure_new(&%s, %d, %s, %du);', $holder, $fn, $count, $argv, $mask));
		if ($asHolder) {
			// the holder is callable itself: a `callable` parameter takes it as it is
			return new Value('zval', $holder . '.raw()', $holder);
		}
		$closure = $f->temp('cl');
		$f->line(sprintf('zv::Val %s = pt_native_closure_to_closure(%s.raw());', $closure, $holder));
		$f->failIf(sprintf('%s.isUndef()', $closure));
		return new Value('zval', $closure . '.raw()', $closure, 'Closure');
	}

	// ---------------------------------------------------------------- conversions

	private function asZval(FunctionContext $f, Value $value): string
	{
		switch ($value->kind) {
			case 'zval':
				return $value->code;
			case 'long':
				$t = $f->temp('z');
				$f->line(sprintf('zval %s;', $t));
				$f->line(sprintf('ZVAL_LONG(&%s, %s);', $t, $value->code));
				return '&' . $t;
			case 'bool':
				$t = $f->temp('z');
				$f->line(sprintf('zval %s;', $t));
				$f->line(sprintf('ZVAL_BOOL(&%s, %s);', $t, $value->code));
				return '&' . $t;
			case 'null':
				$t = $f->temp('z');
				$f->line(sprintf('zval %s;', $t));
				$f->line(sprintf('ZVAL_NULL(&%s);', $t));
				return '&' . $t;
		}
		throw new CompileError('a void expression used as a value');
	}

	private function asLong(FunctionContext $f, Value $value, string $describe, string $label): string
	{
		if ($value->kind === 'long') {
			return $value->code;
		}
		if ($value->kind === 'zval') {
			$f->line(sprintf('if (UNEXPECTED(Z_TYPE_P(%s) != IS_LONG)) { zend_type_error("%s: %s expected, %%s given", zend_zval_value_name(%s)); %s }', $value->code, addcslashes($label, '\\'), $describe, $value->code, $f->fail));
			return sprintf('Z_LVAL_P(%s)', $value->code);
		}
		throw new CompileError(sprintf('%s where an int is expected', $value->kind));
	}

	private function asBool(Value $value): string
	{
		switch ($value->kind) {
			case 'bool':
				return $value->code;
			case 'long':
				return sprintf('(%s != 0)', $value->code);
			case 'null':
				return 'false';
			case 'zval':
				return sprintf('zend_is_true(%s)', $value->code);
		}
		throw new CompileError('a void expression used as a condition');
	}

	/** a zv::Val temp that owns the value */
	private function own(FunctionContext $f, Value $value): string
	{
		if ($value->temp !== null) {
			return $value->temp;
		}
		$t = $f->temp('o');
		switch ($value->kind) {
			case 'long':
				$f->line(sprintf('zv::Val %s = zv::Val::integer(%s);', $t, $value->code));
				break;
			case 'bool':
				$f->line(sprintf('zv::Val %s = zv::Val::boolean(%s);', $t, $value->code));
				break;
			case 'null':
				$f->line(sprintf('zv::Val %s = zv::Val::null();', $t));
				break;
			case 'zval':
				$f->line(sprintf('zv::Val %s = zv::Val::copyOf(zv::Ref(%s));', $t, $value->code));
				break;
			default:
				throw new CompileError('a void expression used as a value');
		}
		return $t;
	}

	private function instanceCheck(string $zvalPtr, string $class, bool $nullable): string
	{
		$unit = $this->units[strtolower($class)] ?? null;
		$native = $unit !== null ? $unit->ce : $this->shadowCe($class);
		if (strtolower($class) === 'closure') {
			$check = sprintf('(Z_TYPE_P(%s) == IS_OBJECT && Z_OBJCE_P(%s) == zend_ce_closure)', $zvalPtr, $zvalPtr);
		} elseif ($native !== null) {
			// the native class, or — in the differential tests, where the
			// natives are declared under prefixed names beside the twins —
			// the class the name means (in production one and the same)
			$fast = $unit !== null && $unit->reflection->isFinal()
				? sprintf('(Z_TYPE_P(%s) == IS_OBJECT && Z_OBJCE_P(%s) == %s)', $zvalPtr, $zvalPtr, $native)
				: sprintf('pgrt::instanceOfClass(%s, %s)', $zvalPtr, $native);
			$site = $this->site('pgrt::ClassSite', 'cls_' . $class);
			$check = sprintf('(%s || pgrt::instanceOfClass(%s, pgrt::lookupClassQuiet(%s, "%s", %d)))', $fast, $zvalPtr, $site, addcslashes($class, '\\'), strlen($class));
		} else {
			$check = sprintf('pgrt::instanceOfClass(%s, %s)', $zvalPtr, $this->classEntryExpr($class));
		}
		if ($nullable) {
			return sprintf('(Z_TYPE_P(%s) == IS_NULL || %s)', $zvalPtr, $check);
		}
		return $check;
	}

	/**
	 * The class-entry variable of a shadowed class: its native class (also
	 * under the prefixed names of the differential tests), set at activation.
	 */
	private function shadowCe(string $class): ?string
	{
		if ($this->shadowCes === null) {
			$this->shadowCes = [];
			foreach (glob(__DIR__ . '/../../../../turbo-ext/src/*.cpp') ?: [] as $file) {
				$code = (string) file_get_contents($file);
				if (preg_match_all('/reg::Class cls\("((?:[^"\\\\]|\\\\.)+)"\);.*?cls\.shadow\(&(\w+)\)/s', $code, $matches, PREG_SET_ORDER) === 0) {
					continue;
				}
				foreach ($matches as $match) {
					$this->shadowCes[strtolower(stripslashes($match[1]))] = $match[2];
				}
			}
		}
		$ce = $this->shadowCes[strtolower($class)] ?? null;
		return $ce !== null && $this->isDeclaredCe($ce) ? $ce : null;
	}

	/** whether a class-entry variable is declared by a header the generated files include */
	private function isDeclaredCe(string $ce): bool
	{
		if ($this->declaredCes === null) {
			$this->declaredCes = [];
			foreach (glob(__DIR__ . '/../../../../turbo-ext/src/*.h') ?: [] as $header) {
				if (basename($header) === 'CodegenDecls.h') {
					continue;
				}
				preg_match_all('/extern zend_class_entry \*(\w+);/', (string) file_get_contents($header), $matches);
				foreach ($matches[1] as $name) {
					$this->declaredCes[$name] = true;
				}
			}
		}
		foreach ($this->units as $unit) {
			if ($unit->ce === $ce) {
				return true;
			}
		}
		return isset($this->declaredCes[$ce]);
	}

	/**
	 * A class entry usable inside a condition: a compiled class's variable,
	 * or a per-request cached lookup (an unloadable class compares as no
	 * class, like instanceof of an unknown name).
	 */
	private function classEntryExpr(string $class): string
	{
		$unit = $this->units[strtolower($class)] ?? null;
		if ($unit !== null) {
			return $unit->ce;
		}
		$shadowCe = $this->shadowCe($class);
		if ($shadowCe !== null) {
			return $shadowCe;
		}
		$site = $this->site('pgrt::ClassSite', 'cls_' . $class);
		return sprintf('pgrt::lookupClassQuiet(%s, "%s", %d)', $site, addcslashes($class, '\\'), strlen($class));
	}

	private function emitTypeCheck(FunctionContext $f, string $z, string $describe, Type $declared, string $onFailure): void
	{
		$check = $this->typeCheck($z, $declared, $describe);
		if ($check === null) {
			return;
		}
		$f->line(sprintf('if (UNEXPECTED(!%s)) { %s }', $check, $onFailure));
	}

	/** the C++ condition a value of the declared type satisfies (strict mode); null = any value does */
	private function typeCheck(string $z, Type $declared, string $describe): ?string
	{
		if ($declared instanceof MixedType) {
			return null;
		}
		$atoms = $declared instanceof UnionType ? $declared->getTypes() : [$declared];
		$checks = [];
		foreach ($atoms as $atom) {
			if ($atom->isObject()->yes() && count($atom->getObjectClassNames()) > 1) {
				// an intersection (the union's members are its atoms): an object of every class
				$members = array_map(fn (string $name): string => $this->instanceCheck($z, $name, false), $atom->getObjectClassNames());
				$checks[] = '(' . implode(' && ', $members) . ')';
			} elseif ($atom->isNull()->yes()) {
				$checks[] = sprintf('Z_TYPE_P(%s) == IS_NULL', $z);
			} elseif ($atom->isTrue()->yes()) {
				$checks[] = sprintf('Z_TYPE_P(%s) == IS_TRUE', $z);
			} elseif ($atom->isFalse()->yes()) {
				$checks[] = sprintf('Z_TYPE_P(%s) == IS_FALSE', $z);
			} elseif ($atom->isBoolean()->yes()) {
				$checks[] = sprintf('Z_TYPE_P(%s) == IS_TRUE || Z_TYPE_P(%s) == IS_FALSE', $z, $z);
			} elseif ($atom->isInteger()->yes()) {
				$checks[] = sprintf('Z_TYPE_P(%s) == IS_LONG', $z);
			} elseif ($atom->isString()->yes()) {
				$checks[] = sprintf('Z_TYPE_P(%s) == IS_STRING', $z);
			} elseif ($atom->isArray()->yes()) {
				$checks[] = sprintf('Z_TYPE_P(%s) == IS_ARRAY', $z);
			} elseif ($atom->isObject()->yes() && $atom->getObjectClassNames() === []) {
				$checks[] = sprintf('Z_TYPE_P(%s) == IS_OBJECT', $z);
			} elseif ($atom->equals(new CallableType())) {
				$checks[] = sprintf('zend_is_callable(%s, 0, NULL)', $z);
			} elseif ($atom->equals(new IterableType(new MixedType(), new MixedType()))) {
				$checks[] = sprintf('Z_TYPE_P(%s) == IS_ARRAY || (Z_TYPE_P(%s) == IS_OBJECT && instanceof_function(Z_OBJCE_P(%s), zend_ce_traversable))', $z, $z, $z);
			} elseif ($atom->isFloat()->yes()) {
				throw new CompileError(sprintf('run-time float checks (int widening) are not compiled (%s)', $describe));
			} elseif (count($atom->getObjectClassNames()) === 1) {
				$checks[] = $this->instanceCheck($z, $atom->getObjectClassNames()[0], false);
			} else {
				throw new CompileError(sprintf('no run-time check for %s', $describe));
			}
		}
		if (count($checks) === 1 && str_starts_with($checks[0], '(') && self::unparen($checks[0]) !== $checks[0]) {
			return $checks[0];
		}
		return '(' . implode(' || ', $checks) . ')';
	}

}
