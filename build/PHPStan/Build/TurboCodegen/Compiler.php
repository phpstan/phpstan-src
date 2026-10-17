<?php declare(strict_types = 1);

namespace PHPStan\Build\TurboCodegen;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;
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
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use function addcslashes;
use function array_key_exists;
use function array_keys;
use function array_pop;
use function array_reverse;
use function array_slice;
use function array_values;
use function class_exists;
use function count;
use function explode;
use function file_get_contents;
use function function_exists;
use function glob;
use function implode;
use function in_array;
use function interface_exists;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function lcfirst;
use function ltrim;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function stripslashes;
use function strlen;
use function strtolower;
use function strtoupper;
use function substr;
use function ucwords;
use function var_export;
use const PREG_SET_ORDER;

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

	/** builtins whose result depends on the calling class (visibility checks, `self`/`static` callables) */
	private const SCOPE_SENSITIVE_FUNCTIONS = ['array_column', 'get_object_vars', 'property_exists', 'method_exists', 'is_callable', 'call_user_func', 'call_user_func_array', 'array_map', 'array_filter', 'usort', 'uasort', 'uksort', 'array_walk', 'array_reduce', 'get_class_methods', 'get_called_class', 'func_get_args', 'compact', 'extract', 'get_defined_vars'];

	private const CPP_KEYWORDS = ['and', 'or', 'not', 'xor', 'new', 'delete', 'default', 'namespace', 'class', 'union', 'switch', 'case', 'template', 'typename', 'operator', 'this', 'register', 'auto', 'char', 'short', 'long', 'float', 'double', 'int', 'bool', 'void', 'static', 'extern', 'const', 'volatile', 'goto', 'return', 'if', 'else', 'for', 'while', 'do', 'break', 'continue', 'try', 'catch', 'throw'];

	/** @var array<string, ClassUnit> lowercase FQCN => unit */
	private array $units = [];

	/** per-file state, set by compileClass() */
	private ?ClassUnit $current = null;

	/** @var array<string, int> */
	private array $literals = [];

	/** @var list<string> */
	private array $siteDecls = [];

	/** @var array<string, string> */
	private array $siteNames = [];

	/** @var list<string> */
	private array $closures = [];

	private int $closureCount = 0;

	private NodeFinder $finder;

	/** @var array<string, string>|null lowercase FQCN => the native class-entry variable of every shadowed class */
	private ?array $shadowCes = null;

	/**
	 * @param array<string, array{file: string, stem: string, ce: string, implementation: string, defineCe?: bool, new?: array{string, string}, dispatch?: array{function: string, enum: string, prefix: string, receiver: string, fallback?: string}}> $classes FQCN => where its twin lives, its generated-header stem, its class-entry variable, the .cpp to write, and the native C API to export (see compiled-classes.php)
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

	private function describeMethod(ClassUnit $unit, Stmt\ClassMethod $method): MethodInfo
	{
		$reflection = new ReflectionMethod($unit->className, $method->name->toString());
		$params = [];
		foreach ($method->params as $i => $param) {
			if ($param->byRef) {
				throw new CompileError(sprintf('%s::%s(): by-reference parameters are not supported', $unit->className, $method->name));
			}
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
				$param->default,
				$isArray,
				$param->flags !== 0,
				($param->flags & Modifiers::READONLY) !== 0,
			);
		}

		$returnType = $reflection->getReturnType();
		if ($method->name->toLowerString() === '__construct' || $returnType === null) {
			$return = new ReturnInfo('void', new VoidType(), 'void');
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
		);
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
		if (!$type instanceof ReflectionNamedType) {
			throw new CompileError(sprintf('%s: intersection declared types are not supported', $unit->className));
		}
		return $this->lowerNamedType($type->getName(), $type->allowsNull(), $unit, $isParameter);
	}

	/**
	 * A type written in the twin's source (closure signatures), lowered like
	 * a reflected one.
	 *
	 * @return array{'long'|'bool'|'zval'|'callable'|'void', Type, string, string|null, bool, bool}
	 */
	private function lowerAstType(?Node $type, bool $isParameter): array
	{
		if ($type === null) {
			return ['zval', new MixedType(), 'mixed', null, true, false];
		}
		if ($type instanceof Node\NullableType) {
			return $this->lowerNamedType($this->astTypeName($type->type), true, $this->unit(), $isParameter);
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
				[, $memberType] = $this->lowerNamedType($name, false, $this->unit(), $isParameter);
				$members[] = $memberType;
				$described[] = $name;
			}
			return ['zval', TypeCombinator::union(...$members), implode('|', $described), null, $nullable, false];
		}
		if ($type instanceof Node\Identifier || $type instanceof Node\Name) {
			$name = $this->astTypeName($type);
			return $this->lowerNamedType($name, strtolower($name) === 'mixed' || strtolower($name) === 'null', $this->unit(), $isParameter);
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
	private function lowerNamedType(string $name, bool $allowsNull, ClassUnit $unit, bool $isParameter): array
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
				if (!$isParameter || $nullable) {
					throw new CompileError('callable is supported as a non-nullable parameter type only');
				}
				return ['callable', new CallableType(), 'callable', null, false, false];
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
		$this->literals = [];
		$this->siteDecls = [];
		$this->siteNames = [];
		$this->closures = [];
		$this->closureCount = 0;

		$bodies = [];
		foreach ($unit->methods as $method) {
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
		$out[] = sprintf('#include "generated/%s.h"', $unit->stem);
		foreach ($this->units as $other) {
			if ($other === $unit) {
				continue;
			}
			$out[] = sprintf('#include "generated/%s.h"', $other->stem);
		}
		$out[] = '';
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
		return (string) preg_replace('~^.*/(src/)~', '$1', (string) $unit->reflection->getFileName());
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
			if (is_int($value)) {
				$out[] = sprintf("\tcls.%s(\"%s\", %d);", $constant->isPrivate() ? 'privateClassConstantLong' : 'classConstantLong', $constant->getName(), $value);
			} elseif (is_string($value)) {
				$out[] = sprintf("\tcls.%s(\"%s\", \"%s\");", $constant->isPrivate() ? 'privateClassConstantString' : 'publicClassConstantString', $constant->getName(), addcslashes($value, '"\\'));
			} else {
				throw new CompileError(sprintf('%s::%s: only int/string constants are supported', $unit->className, $constant->getName()));
			}
		}
		foreach ($unit->methods as $method) {
			$out[] = $this->renderGlue($method);
			$op = $this->opAdapter($method);
			if ($op === null) {
				continue;
			}

			$out[] = $op;
		}
		$out[] = sprintf("\tcls.shadow(&%s);", $unit->ce);
		$out[] = '}';
		// the literals the bodies and the glue (defaults) use, interned first
		$literals = [];
		foreach ($this->literals as $literal => $index) {
			$literals[] = sprintf("\tZVAL_INTERNED_STR(&pg_lit[%d], pgrt::internLiteral(\"%s\", %d));", $index, addcslashes($literal, "\"\\\n\r\t"), strlen($literal));
		}
		return str_replace("/*LITERALS*/\n", $literals === [] ? '' : implode("\n", $literals) . "\n", implode("\n", $out));
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
		if ($unit->defineCe) {
			$out[] = sprintf('zend_class_entry *%s = nullptr;', $unit->ce);
			$out[] = '';
		}
		if ($unit->newExport !== null) {
			$out = [...$out, ...$this->renderNewExport($unit, $unit->newExport[0], $unit->newExport[1])];
		}
		if ($unit->dispatchExport !== null) {
			$out = [...$out, ...$this->renderDispatchExport($unit, $unit->dispatchExport)];
		}
		return $out;
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
		if ($constructor === null && $unit->reflection->getConstructor() !== null) {
			throw new CompileError(sprintf('%s inherits its constructor; export its `new` by hand', $unit->className));
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
				if ($param->kind === 'long') {
					$decl[] = 'zend_long ' . $param->name;
					$args[] = $param->name;
				} elseif ($param->kind === 'bool') {
					$decl[] = 'bool ' . $param->name;
					$args[] = $param->name;
				} elseif ($param->kind === 'callable') {
					throw new CompileError('callable constructor parameters cannot be exported');
				} elseif (TypeCombinator::removeNull($param->declared)->isString()->yes()) {
					// strings travel as zend_string * (NULL = null)
					$decl[] = 'zend_string *' . $param->name;
					$pre[] = sprintf("\tzval %s_zv;", $param->name);
					$none = $param->default !== null ? $this->constantZvalInit($param->name . '_zv', $param->default) : sprintf('ZVAL_NULL(&%s_zv);', $param->name);
					$pre[] = sprintf("\tif (%s != NULL) { ZVAL_STR(&%s_zv, %s); } else { %s }", $param->name, $param->name, $param->name, $none);
					$args[] = '&' . $param->name . '_zv';
				} else {
					// the hand-written ports' convention: NULL = null, or the
					// parameter's default where it has one
					$decl[] = 'zval *' . $param->name;
					$pre[] = sprintf("\tzval %s_none;", $param->name);
					$pre[] = "\t" . ($param->default !== null ? $this->constantZvalInit($param->name . '_none', $param->default) : sprintf('ZVAL_NULL(&%s_none);', $param->name));
					$pre[] = sprintf("\tif (%s == NULL) %s = &%s_none;", $param->name, $param->name, $param->name);
					$args[] = $param->name;
				}
			}
		}
		$construct = $constructor !== null
			? sprintf('pgen::%s::%s(%s)', $unit->stem, $this->cppMethodName($constructor), implode(', ', ['Z_OBJ(object)', ...$args]))
			: 'true';
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
		$max = 0;
		$parse = [];
		$after = [];
		$optionalStarted = false;
		foreach ($method->params as $param) {
			$name = 'p_' . $param->name;
			if ($param->variadic) {
				$lines[] = sprintf('zval *%s = NULL;', $name);
				$lines[] = sprintf('uint32_t %s_count = 0;', $name);
				$parse[] = sprintf("Z_PARAM_VARIADIC('*', %s, %s_count)", $name, $name);
				$max = -1;
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
			if ($param->default === null) {
				$required++;
			}
			if ($max >= 0) {
				$max++;
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
						// parsed as any object, then checked against the class
						// (instanceCheck() accepts the native class and the twin)
						$parse[] = sprintf('Z_PARAM_OBJECT%s(%s)', $param->nullable ? '_OR_NULL' : '', $name);
						$after[] = sprintf('if (%s != NULL && UNEXPECTED(!%s)) { zend_argument_type_error(%d, "must be of type %%s, %%s given", "%s", zend_zval_value_name(%s)); RETURN_THROWS(); }', $name, $this->instanceCheck($name, $param->class, $param->nullable), $param->position + 1, addcslashes($param->describe, '\\"'), $name);
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
		if ($parse === []) {
			$body[] = 'ZEND_PARSE_PARAMETERS_NONE();';
		} else {
			$body[] = sprintf('ZEND_PARSE_PARAMETERS_START(%d, %d)', $required, $max);
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
		$out[] = sprintf("\tcls.method(sigs::%s, [](INTERNAL_FUNCTION_PARAMETERS) { /* %s() */", $this->sigName($method), $label);
		foreach ($body as $line) {
			$out[] = "\t\t" . $line;
		}
		$out[] = "\t});";
		return implode("\n", $out);
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
		throw new CompileError(sprintf('parameter $%s of type %s has no argument parsing yet', $param->name, $param->describe));
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
		if ($expr instanceof Node\Scalar\Int_) {
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

		// promoted constructor parameters
		if (strtolower($method->name) === '__construct') {
			foreach ($method->params as $param) {
				if (!$param->promoted) {
					continue;
				}
				$value = $this->readLocal($f, $param->name);
				$owned = $this->own($f, $value);
				$slot = sprintf('ptdecl::%s::slot::%s', $this->unit()->stem, $param->name);
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
		if ($stmt instanceof Stmt\Continue_ && $stmt->num === null) {
			$f->line('continue;');
			return;
		}
		if ($stmt instanceof Stmt\Break_ && $stmt->num === null) {
			$f->line('break;');
			return;
		}
		if ($stmt instanceof Stmt\Nop) {
			return;
		}
		if ($stmt instanceof Stmt\While_) {
			$f->open('while (true)');
			$f->line(sprintf('if (!(%s)) break;', $this->condition($f, $stmt->cond)));
			$this->statements($f, $stmt->stmts);
			$f->close();
			return;
		}
		if ($stmt instanceof Stmt\Do_) {
			if ($this->finder->findFirst($stmt->stmts, static fn (Node $n): bool => $n instanceof Stmt\Continue_) !== null) {
				throw new CompileError('continue inside do-while is not supported');
			}
			$f->open('while (true)');
			$this->statements($f, $stmt->stmts);
			$f->line(sprintf('if (!(%s)) break;', $this->condition($f, $stmt->cond)));
			$f->close();
			return;
		}
		if ($stmt instanceof Stmt\For_) {
			$f->open('');
			foreach ($stmt->init as $init) {
				$this->expr($f, $init);
			}
			$f->open('for (;;)');
			foreach ($stmt->cond as $i => $cond) {
				$c = $this->condition($f, $cond);
				if ($i !== count($stmt->cond) - 1) {
					continue;
				}

				$f->line(sprintf('if (!(%s)) break;', $c));
			}
			$this->statements($f, $stmt->stmts);
			// `continue` must still run the loop step: the step is emitted in a
			// lambda-free way by forbidding continue in compiled for loops
			if ($this->finder->findFirst($stmt->stmts, static fn (Node $n): bool => $n instanceof Stmt\Continue_) !== null) {
				throw new CompileError('continue inside for is not supported');
			}
			foreach ($stmt->loop as $loop) {
				$this->expr($f, $loop);
			}
			$f->close();
			$f->close();
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
	 * switch: the subject evaluated once, the matching case found by loose
	 * comparison in order, then a C++ switch over the case index keeps the
	 * fall-through and `break` semantics.
	 */
	private function switchStatement(FunctionContext $f, Stmt\Switch_ $stmt): void
	{
		if ($this->finder->findFirst($stmt->cases, static fn (Node $n): bool => $n instanceof Stmt\Continue_ || ($n instanceof Stmt\Break_ && $n->num !== null)) !== null) {
			throw new CompileError('continue/break N inside switch is not supported');
		}
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
			$f->line(sprintf('if (%s) %s = %d;', $this->looseEquals($f, $subject, $value), $index, $i));
			$f->close();
		}
		if ($default !== null) {
			$f->line(sprintf('if (%s == -1) %s = %d;', $index, $index, $default));
		}
		$f->open(sprintf('switch (%s)', $index));
		foreach (array_values($stmt->cases) as $i => $case) {
			$f->line(sprintf('case %d: {', $i));
			$this->statements($f, $case->stmts);
			$f->line('}');
			$f->line('[[fallthrough]];');
		}
		$f->line('default: break;');
		$f->close();
		$f->close();
	}

	/**
	 * try/catch: inside the try body a pending exception jumps to the
	 * handler, which takes it over if it is an instance of a caught class
	 * and propagates it otherwise.
	 */
	private function tryStatement(FunctionContext $f, Stmt\TryCatch $stmt): void
	{
		if ($stmt->finally !== null) {
			throw new CompileError('finally is not supported yet');
		}
		$label = sprintf('catch_%s', $f->temp('c'));
		$outerFail = $f->fail;
		$f->open('');
		$f->fail = sprintf('goto %s;', $label);
		$f->open('');
		$this->statements($f, $stmt->stmts);
		$f->close();
		$f->fail = $outerFail;
		$f->line(sprintf('if (false) {'));
		$f->line(sprintf('%s:', $label));
		$f->open('');
		$f->line('zend_object *thrown = EG(exception);');
		$caught = $f->temp('caught');
		$f->line(sprintf('bool %s = false;', $caught));
		foreach ($stmt->catches as $catch) {
			$checks = [];
			foreach ($catch->types as $type) {
				$checks[] = $this->catchCheck(ltrim($type->toString(), '\\\\'));
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
		$f->line(sprintf('if (!%s) %s', $caught, $outerFail));
		$f->close();
		$f->line('}');
		$f->close();
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
		throw new CompileError('unset() of this target is not supported');
	}

	private function returnStatement(FunctionContext $f, ?Expr $expr): void
	{
		$return = $f->return;
		if ($return === null) {
			throw new CompileError('return outside a function');
		}
		$f->open('');
		if ($expr === null) {
			$f->line($f->isClosure ? 'return;' : 'return true;');
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
				if ($value->temp !== null) {
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
		$f->line('return true;');
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
			$this->statements($f, $stmt->stmts);
			$f->close();
			$f->close();
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
			$held = $f->temp('a');
			$f->line(sprintf('zv::Val %s = zv::Val::copyOf(zv::Ref(%s).deref());', $held, $array->code));
			if (!$array->isArray && !$this->unit()->oracle->nativeType($source)->isArray()->yes()) {
				// not proven an array: the VM's warning for a scalar; iterating an
				// object's properties is outside the compiled subset
				$f->open(sprintf('if (UNEXPECTED(Z_TYPE_P(%s.raw()) != IS_ARRAY))', $held));
				$f->line(sprintf('if (Z_TYPE_P(%s.raw()) == IS_OBJECT) { zend_throw_error(NULL, "phpstan_turbo codegen: foreach over an object is not compiled"); %s }', $held, $f->fail));
				$f->line(sprintf('zend_error(E_WARNING, "foreach() argument must be of type array|object, %%s given", zend_zval_value_name(%s.raw()));', $held));
				$f->failIf('EG(exception)');
				$f->line(sprintf('%s = zv::Val(zv::Arr::empty());', $held));
				$f->close();
			}
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
		$this->statements($f, $stmt->stmts);
		$f->close();
		$f->line('ZEND_HASH_FOREACH_END();');
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
		if ($expr instanceof Expr\AssignOp\BitwiseAnd || $expr instanceof Expr\AssignOp\BitwiseOr) {
			return $this->bitwiseAssign($f, $expr);
		}
		if ($expr instanceof Expr\BinaryOp\Coalesce) {
			$ptr = $this->issetPtr($f, $expr->left);
			$t = $f->temp('q');
			$f->line(sprintf('zv::Val %s;', $t));
			$f->open(sprintf('if (%s != NULL && Z_TYPE_P(%s) > IS_NULL)', $ptr, $ptr));
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
		if ($expr instanceof Expr\Isset_) {
			$checks = [];
			foreach ($expr->vars as $var) {
				$ptr = $this->issetPtr($f, $var);
				$checks[] = sprintf('(%s != NULL && Z_TYPE_P(%s) > IS_NULL)', $ptr, $ptr);
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
				throw new CompileError('dynamic instanceof is not supported');
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
				return new Value('zval', $local->cName . '.raw()', class: $local->class, isArray: $local->isArray);
			case 'param':
				return new Value('zval', $local->cName, class: $local->class, isArray: $local->isArray);
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
			if ($item->byRef || $item->unpack) {
				throw new CompileError('array spread/by-ref items are not supported');
			}
			$value = $this->own($f, $this->expr($f, $item->value));
			if ($item->key === null) {
				$f->line(sprintf('{ zval v = %s.take(); zend_hash_next_index_insert(Z_ARRVAL_P(%s.raw()), &v); }', $value, $t));
				continue;
			}
			$key = $this->expr($f, $item->key);
			if ($key->kind === 'long') {
				$f->line(sprintf('{ zval v = %s.take(); zend_hash_index_update(Z_ARRVAL_P(%s.raw()), %s, &v); }', $value, $t, $key->code));
			} else {
				$f->line(sprintf('{ zval v = %s.take(); zend_symtable_update(Z_ARRVAL_P(%s.raw()), Z_STR_P(%s), &v); }', $value, $t, $this->asZval($f, $key)));
			}
		}
		return new Value('zval', $t . '.raw()', $t, isArray: true);
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
			throw new CompileError('dynamic property names are not supported');
		}
		$name = $expr->name->toString();
		$receiverNative = $this->unit()->oracle->nativeType($expr->var);
		$receiver = $this->expr($f, $expr->var);
		$class = $receiver->class ?? $this->knownClass($receiverNative);
		$native = $this->unit()->oracle->nativeType($expr);

		$unit = $class !== null ? ($this->units[strtolower($class)] ?? null) : null;
		if ($unit !== null && $receiver->kind === 'zval' && $unit->reflection->hasProperty($name) && !$unit->reflection->getProperty($name)->isStatic()) {
			$slot = $f->temp('p');
			$f->line(sprintf('zval *%s = pgrt::readSlot(Z_OBJ_P(%s), ptdecl::%s::slot::%s, "%s");', $slot, $receiver->code, $unit->stem, $name, $name));
			$f->failIf(sprintf('%s == NULL', $slot));
			return $this->fromSlot($slot, $native);
		}

		// speculate on the PHPDoc type: a guarded slot read, the engine otherwise
		$speculated = $this->knownClass($this->unit()->oracle->phpDocType($expr->var));
		$z = $this->asZval($f, $receiver);
		$out = $f->temp('p');
		$owned = $f->temp('o');
		$f->line(sprintf('zval *%s;', $out));
		$f->line(sprintf('zv::Val %s;', $owned));
		$specUnit = $speculated !== null ? ($this->units[strtolower($speculated)] ?? null) : null;
		if ($specUnit !== null && $specUnit->reflection->isFinal() && $specUnit->reflection->hasProperty($name)) {
			$f->open(sprintf('if (EXPECTED(Z_TYPE_P(%s) == IS_OBJECT && Z_OBJCE_P(%s) == %s))', $z, $z, $specUnit->ce));
			$f->line(sprintf('%s = pgrt::readSlot(Z_OBJ_P(%s), ptdecl::%s::slot::%s, "%s");', $out, $z, $specUnit->stem, $name, $name));
			$f->failIf(sprintf('%s == NULL', $out));
			$f->close(' else {');
			$f->open('');
		} else {
			$f->open('');
		}
		$f->failIf(sprintf('!pgrt::readPropertyGeneric(%s, %s, "%s", %d, %s.raw())', $z, $this->unit()->ce, $name, strlen($name), $owned));
		$f->line(sprintf('%s = %s.raw();', $out, $owned));
		$f->close();
		if ($specUnit !== null && $specUnit->reflection->isFinal() && $specUnit->reflection->hasProperty($name)) {
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
		$key = $this->expr($f, $expr->dim);
		$z = $this->asZval($f, $array);
		$t = $f->temp('e');
		if ($key->kind !== 'long') {
			throw new CompileError('only int array offsets are supported in reads');
		}
		$f->line(sprintf('zval *%s = Z_TYPE_P(%s) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(%s), %s) : NULL;', $t, $z, $z, $key->code));
		$f->open(sprintf('if (UNEXPECTED(%s == NULL))', $t));
		$f->line(sprintf('pgrt::warnUndefinedIndex(%s);', $key->code));
		$f->failIf('EG(exception)');
		$f->line(sprintf('%s = &EG(uninitialized_zval);', $t));
		$f->close();
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
		if ($target instanceof Expr\Variable && is_string($target->name)) {
			$local = $f->locals[$target->name] ?? null;
			if ($local === null) {
				throw new CompileError(sprintf('no home for $%s', $target->name));
			}
			if ($local->kind === 'list') {
				$f->line(sprintf('%s.clear();', $local->cName));
				return Value::void();
			}
			$value = $this->expr($f, $expr->expr);
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

	/** the slot of a declared property of a compiled class, for a write that is not a readonly initialization */
	private function propertySlotForWrite(FunctionContext $f, Expr\PropertyFetch $fetch): string
	{
		if (!$fetch->name instanceof Node\Identifier) {
			throw new CompileError('dynamic property names are not supported');
		}
		$name = $fetch->name->toString();
		$receiver = $this->stabilize($f, $this->expr($f, $fetch->var));
		$class = $receiver->class ?? $this->knownClass($this->unit()->oracle->nativeType($fetch->var));
		$unit = $class !== null ? ($this->units[strtolower($class)] ?? null) : null;
		if ($unit === null || !$unit->reflection->hasProperty($name)) {
			throw new CompileError(sprintf('writing into ->%s of a value not proven to be a compiled class', $name));
		}
		$property = $unit->reflection->getProperty($name);
		if ($property->isReadOnly() || $property->isStatic()) {
			throw new CompileError(sprintf('->%s is readonly or static', $name));
		}
		return sprintf('OBJ_PROP_NUM(Z_OBJ_P(%s), ptdecl::%s::slot::%s)', $receiver->code, $unit->stem, $name);
	}

	/**
	 * $object->name = value: a declared property of a compiled class by slot
	 * (readonly initialized once, a typed one checked unless proven), any
	 * other through the object handlers with the class as calling scope.
	 */
	private function assignProperty(FunctionContext $f, Expr\PropertyFetch $target, Expr $valueExpr): Value
	{
		if (!$target->name instanceof Node\Identifier) {
			throw new CompileError('dynamic property names are not supported');
		}
		$name = $target->name->toString();
		$receiver = $this->stabilize($f, $this->expr($f, $target->var));
		$value = $this->stabilize($f, $this->expr($f, $valueExpr));
		$z = $this->asZval($f, $value);
		$class = $receiver->class ?? $this->knownClass($this->unit()->oracle->nativeType($target->var));
		$unit = $class !== null ? ($this->units[strtolower($class)] ?? null) : null;
		if ($unit !== null && $unit->reflection->hasProperty($name) && !$unit->reflection->getProperty($name)->isStatic()) {
			$property = $unit->reflection->getProperty($name);
			$slot = sprintf('ptdecl::%s::slot::%s', $unit->stem, $name);
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
		if ($target instanceof Expr\ArrayDimFetch && $target->dim !== null) {
			$container = $target->var;
			$key = $this->stabilize($f, $this->expr($f, $target->dim));
			if ($key->kind !== 'long') {
				throw new CompileError('??= on array offsets supports int keys only');
			}
			$arrayPtr = function () use ($f, $container): string {
				if ($container instanceof Expr\StaticPropertyFetch) {
					return $this->staticPropertySlot($f, $container);
				}
				if ($container instanceof Expr\Variable && is_string($container->name) && ($f->locals[$container->name] ?? null)?->kind === 'zval') {
					return $f->locals[$container->name]->cName . '.raw()';
				}
				throw new CompileError('??= on offsets of this container is not supported');
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
		throw new CompileError(sprintf('unsupported ??= target on line %d', $expr->getStartLine()));
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
				throw new CompileError(sprintf('bitwise operators on non-int operands are not supported (line %d)', $expr->getStartLine()));
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
		return sprintf('zend_is_equal(%s, %s)', $this->asZval($f, $left), $this->asZval($f, $right));
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
				return '&this_zv';
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
			throw new CompileError('?: is not supported');
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
		return $this->finder->findFirst([$expr], static fn (Node $n): bool => $n instanceof Expr\CallLike || $n instanceof Expr\Assign || $n instanceof Expr\AssignOp || $n instanceof Expr\Throw_) !== null;
	}

	/** pins a value so later side effects cannot change what it reads */
	private function stabilize(FunctionContext $f, Value $value): Value
	{
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
		$className = $this->className($expr->class);
		$unit = $this->units[strtolower($className)] ?? null;
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
		[$argv, $argc, $named] = $this->engineArgs($f, $expr->args);
		$ce = $this->classEntry($className, $f);
		$f->line(sprintf('zv::Val %s;', $t));
		$f->failIf(sprintf('!pgrt::newObject(%s, %s, %s, %s.raw(), %s)', $ce, $argc, $argv, $t, $named));
		return new Value('zval', $t . '.raw()', $t, $className);
	}

	private function methodCall(FunctionContext $f, Expr\MethodCall $expr): Value
	{
		if (!$expr->name instanceof Node\Identifier) {
			throw new CompileError('dynamic method names are not supported');
		}
		$name = $expr->name->toString();
		$receiver = $this->expr($f, $expr->var);
		$class = $receiver->class ?? $this->knownClass($this->unit()->oracle->nativeType($expr->var));
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
		[$argv, $argc, $named] = $this->engineArgs($f, $expr->args);
		$site = $this->site('pt_method_site', sprintf('method_%s_%d', $name, count($this->siteNames)));
		$t = $f->temp('r');
		$f->line(sprintf('zv::Val %s;', $t));
		$f->failIf(sprintf('!pgrt::callMethod(%s, %s, "%s", "%s", %d, %s, %s, %s.raw(), %s)', $site, $this->asZval($f, $receiver), $name, strtolower($name), strlen($name), $argc, $argv, $t, $named));
		return $this->fromEngineResult($t, $this->unit()->oracle->nativeType($expr));
	}

	private function staticCall(FunctionContext $f, Expr\StaticCall $expr): Value
	{
		if (!$expr->name instanceof Node\Identifier) {
			throw new CompileError('dynamic static method names are not supported');
		}
		$name = $expr->name->toString();
		$className = $this->className($expr->class);
		$unit = $this->units[strtolower($className)] ?? null;
		$method = $unit?->methods[strtolower($name)] ?? null;
		if ($method !== null && $method->isStatic) {
			$args = $this->directArgs($f, $method, $expr->args);
			return $this->emitDirectCall($f, $method, null, $args);
		}
		[$argv, $argc, $named] = $this->engineArgs($f, $expr->args);
		$ce = $this->classEntry($className, $f);
		$site = $this->site('pt_method_site', sprintf('static_%s::%s', $className, $name));
		$t = $f->temp('r');
		$f->line(sprintf('zv::Val %s;', $t));
		$f->failIf(sprintf('!pgrt::callStatic(%s, %s, "%s", %d, %s, %s, %s.raw(), %s)', $site, $ce, strtolower($name), strlen($name), $argc, $argv, $t, $named));
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
		if (str_contains($name, '\\')) {
			$parts = explode('\\', $name);
			$name = $parts[count($parts) - 1];
		}
		if (!function_exists($name) || !(new ReflectionFunction($name))->isInternal()) {
			throw new CompileError(sprintf('only built-in functions can be called (%s)', $name));
		}
		if (in_array($name, self::SCOPE_SENSITIVE_FUNCTIONS, true) && !$this->callbacksAreClosures($expr, $name)) {
			$intrinsic = $name === 'array_column' ? $this->arrayColumnIntrinsic($f, $expr) : null;
			if ($intrinsic === null) {
				// a frameless direct call leaves the native caller's frame current:
				// the builtin would resolve visibility against the wrong class
				throw new CompileError(sprintf('%s() depends on the calling scope and has no intrinsic for this call (line %d)', $name, $expr->getStartLine()));
			}
			return $intrinsic;
		}
		if ($name === 'count' && count($expr->args) === 1 && $expr->args[0] instanceof Node\Arg && !$expr->args[0]->unpack) {
			$array = $this->expr($f, $expr->args[0]->value);
			if ($array->isArray || $this->unit()->oracle->nativeType($expr->args[0]->value)->isArray()->yes()) {
				return Value::long(sprintf('(zend_long) zend_hash_num_elements(Z_ARRVAL_P(%s))', $this->asZval($f, $array)));
			}
		}
		$intrinsic = $this->builtinIntrinsic($f, $name, $expr);
		if ($intrinsic !== null) {
			return $intrinsic;
		}
		[$argv, $argc, $named] = $this->engineArgs($f, $expr->args);
		$site = $this->site('pgrt::FunctionSite', 'function_' . $name);
		$t = $f->temp('r');
		$f->line(sprintf('zv::Val %s;', $t));
		$f->failIf(sprintf('!pgrt::callFunction(%s, "%s", %d, %s, %s, %s, %s.raw(), %s)', $site, $name, strlen($name), $this->unit()->ce, $argc, $argv, $t, $named));
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
		$slot = sprintf('ptdecl::%s::slot::%s', $unit->stem, $property);
		$body = static function (string $z) use ($f, $t, $slot, $property): void {
			$p = $f->temp('p');
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
		$label = $this->label($method);
		$hasSpread = false;
		foreach ($args as $arg) {
			if (!$arg instanceof Node\Arg) {
				throw new CompileError('first-class callables are not supported');
			}
			if ($arg->name !== null) {
				throw new CompileError('named arguments are not supported');
			}
			if (!$arg->unpack) {
				continue;
			}

			$hasSpread = true;
		}

		if ($hasSpread) {
			return $this->directArgsFromVector($f, $method, $args);
		}

		$values = $this->lowerArgs($f, $args);
		$out = [];
		foreach ($method->params as $param) {
			if ($param->variadic) {
				$rest = array_slice($values, $param->position);
				$restExprs = array_slice($args, $param->position);
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
			$out[] = $this->convertArg($f, $values[$param->position], $args[$param->position]->value, $param, $label);
		}
		// surplus arguments to a user function are evaluated above and ignored
		return $out;
	}

	/**
	 * @param array<Node\Arg> $args
	 * @return list<Value>
	 */
	private function lowerArgs(FunctionContext $f, array $args): array
	{
		$values = [];
		foreach ($args as $i => $arg) {
			$value = $this->expr($f, $arg->value);
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
	private function engineArgs(FunctionContext $f, array $args): array
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
		$values = $this->lowerArgs($f, $plain);
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
		return '&' . $t;
	}

	private function convertArg(FunctionContext $f, Value $value, Expr $argExpr, ParamInfo $param, string $label): string
	{
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
				throw new CompileError('passing callables between compiled methods is not supported yet');
			default:
				$z = $this->asZval($f, $value);
				if (!$proven) {
					$this->emitTypeCheck($f, $z, $param->describe, $param->declared, $fail($z));
				}
				return $z;
		}
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
				return Value::long($t);
			case 'bool':
				$f->line(sprintf('bool %s;', $t));
				$f->failIf(sprintf('!%s(%s)', $call, implode(', ', [...$callArgs, '&' . $t])));
				return Value::bool($t);
			case 'zval':
				$f->line(sprintf('zv::Val %s;', $t));
				$f->failIf(sprintf('!%s(%s)', $call, implode(', ', [...$callArgs, $t . '.raw()'])));
				return new Value('zval', $t . '.raw()', $t, $method->return->nullable ? null : $method->return->class, $method->return->declared->isArray()->yes());
			default:
				$f->failIf(sprintf('!%s(%s)', $call, implode(', ', $callArgs)));
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
			[$kind, $declared, $describe, $class, $nullable, $isArray] = $this->lowerAstType($param->type, true);
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
			[$returnKind, $returnDeclared, $returnDescribe, $returnClass, $returnNullable] = $this->lowerAstType($expr->returnType, false);
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
					break;
				case 'bool':
					$inner->line(sprintf('bool p_%s = Z_TYPE_P(%s) == IS_TRUE;', $param->name, $z));
					break;
				default:
					$inner->line(sprintf('zval *p_%s = %s;', $param->name, $z));
			}
		}
		$this->declareLocals($inner, $params, $expr instanceof Expr\Closure ? $expr->stmts : $expr->expr, $captureLocals);
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
		return $this->shadowCes[strtolower($class)] ?? null;
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
			if ($atom->isNull()->yes()) {
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
