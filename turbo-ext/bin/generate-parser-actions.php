#!/usr/bin/env php
<?php declare(strict_types = 1);

/**
 * Generates src/parser/ParserRunnerActions{1,2,3}.cpp and
 * src/parser/ParserRunnerActionsSplit.h from the reduce closures in the
 * vendored vendor/nikic/php-parser/lib/PhpParser/Parser/Php8.php
 * (initReduceCallbacks), so a php-parser upgrade is "regenerate, port the
 * flagged bodies, corpus-verify" instead of hand-renumbering 482 cases.
 *
 * How it works:
 *  - every `N => static function ($self, $stackPos) { BODY }` entry is
 *    extracted token-wise; `N => null` entries get no case (the engine
 *    applies the default action);
 *  - each BODY is whitespace-normalized and sha1-hashed; if
 *    src/parser/action-overrides/<sha1>.inc exists its contents are emitted
 *    verbatim as the case body (hand-ported special cases; renumbering-safe
 *    because the key is the body content, not the rule number);
 *  - otherwise the BODY is transpiled statement-by-statement to C++ against
 *    the phpstanturbo::ParserEngine API in src/parser/ParserEngine.h (the
 *    actions are emitted as ParserEngine::reduceRange* member functions).
 *    Unsupported constructs make the whole run fail loudly, listing rule
 *    number, sha1 and PHP body — port those by adding an override file
 *    (never guess);
 *  - php-parser constants (Modifiers::*, Stmt\Use_::TYPE_*, ...) are
 *    resolved at generation time under the Composer autoloader and emitted
 *    as numeric values with a `/* Class::CONST *` `/` comment;
 *  - node construction policy: classes with subNodes-style or normalizing
 *    constructors call the real PHP constructor (newNodeCtor); everything
 *    else uses property-slot writes (newNode), verified at generation time
 *    against the reflected constructor body (trivial assignments only).
 *
 * The output is deterministic: the same vendored Php8.php and overrides
 * produce byte-identical files (no timestamps). CI regenerates and diffs.
 */

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

const PHP8_RELATIVE = 'vendor/nikic/php-parser/lib/PhpParser/Parser/Php8.php';

final class GenerateFailure extends Exception
{

	/** @var list<array{rule: int, sha1: string, body: string, reason: string}> */
	public array $failures = [];

}

final class TranspileFailure extends Exception
{

}

/**
 * Token stream over one closure body (whitespace dropped, comments kept).
 */
final class Toks
{

	/** @var list<array{int, string}|string> */
	private array $toks;

	private int $i = 0;

	public function __construct(string $phpBody)
	{
		$all = token_get_all('<?php ' . $phpBody);
		array_shift($all); // T_OPEN_TAG
		$this->toks = array_values(array_filter($all, static function ($t): bool {
			return !(is_array($t) && $t[0] === T_WHITESPACE);
		}));
	}

	/** @return array{int, string}|string|null */
	public function peek(int $ahead = 0)
	{
		return $this->toks[$this->i + $ahead] ?? null;
	}

	/** @return array{int, string}|string */
	public function next()
	{
		if (!isset($this->toks[$this->i])) {
			throw new TranspileFailure('unexpected end of body');
		}
		return $this->toks[$this->i++];
	}

	public function atEnd(): bool
	{
		return $this->i >= count($this->toks);
	}

	/** @param int|string $what token id or literal char */
	public function is($what, int $ahead = 0): bool
	{
		$t = $this->peek($ahead);
		if ($t === null) {
			return false;
		}
		if (is_string($what)) {
			return $t === $what;
		}
		return is_array($t) && $t[0] === $what;
	}

	public function isIdent(string $name, int $ahead = 0): bool
	{
		$t = $this->peek($ahead);
		return is_array($t) && $t[0] === T_STRING && $t[1] === $name;
	}

	public function isVar(string $name, int $ahead = 0): bool
	{
		$t = $this->peek($ahead);
		return is_array($t) && $t[0] === T_VARIABLE && $t[1] === $name;
	}

	/** @param int|string $what */
	public function tryConsume($what): bool
	{
		if ($this->is($what)) {
			$this->i++;
			return true;
		}
		return false;
	}

	/**
	 * @param int|string $what
	 * @return array{int, string}|string
	 */
	public function expect($what)
	{
		if (!$this->is($what)) {
			throw new TranspileFailure(sprintf(
				'expected %s, got %s',
				is_string($what) ? var_export($what, true) : token_name($what),
				$this->describe($this->peek()),
			));
		}
		return $this->next();
	}

	public function expectIdent(): string
	{
		$t = $this->expect(T_STRING);
		return $t[1];
	}

	/** Member names after :: may tokenize as reserved keywords (Modifiers::ABSTRACT, ::FINAL, ...). */
	public function expectMemberName(): string
	{
		$t = $this->peek();
		if (is_array($t) && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $t[1]) === 1) {
			$this->next();
			return $t[1];
		}
		throw new TranspileFailure('expected a member name, got ' . $this->describe($t));
	}

	public function expectInt(): int
	{
		$t = $this->expect(T_LNUMBER);
		return (int) $t[1];
	}

	/** @param array{int, string}|string|null $t */
	private function describe($t): string
	{
		if ($t === null) {
			return 'end of body';
		}
		if (is_string($t)) {
			return var_export($t, true);
		}
		return token_name($t[0]) . '(' . $t[1] . ')';
	}

}

/**
 * A compiled expression fragment.
 *
 * kind:
 *  - slot:  borrowed zval* (semStack slot, array element, property read)
 *  - owned: owned zval rvalue (builders, PN_NEW, helper calls)
 *  - owned_undef: owned zval rvalue that may be IS_UNDEF meaning PHP null
 *  - attrs: owned attributes array rvalue (consumed by builders)
 *  - long:  zend_long rvalue
 *  - bool:  C bool rvalue
 *  - pos:   C int rvalue (stack positions, token positions)
 *  - null:  PHP null literal
 *  - cstr:  C string literal (already escaped, without quotes)
 *  - tokpos: token start/end stack reference (structured, see $tok)
 */
final class CExpr
{

	public string $kind;

	public string $code = '';

	/** @var list<string> setup lines emitted before the line using $code */
	public array $pre = [];

	/** @var list<string> teardown lines emitted after the line using $code */
	public array $post = [];

	/** @var array{which: string, n: int|null, m: int|null}|null for kind=tokpos */
	public ?array $tok = null;

	/** byte length of the (unescaped) value for kind=cstr */
	public int $valueLen = 0;

	public function __construct(string $kind, string $code = '')
	{
		$this->kind = $kind;
		$this->code = $code;
	}

}

final class Transpiler
{

	/** Classes whose PHP constructor must be called (PN_NEW_CTOR): subNodes-style or normalizing ctors. */
	private const CTOR_CLASSES = [
		'PhpParser\Node\Stmt\If_' => true,
		'PhpParser\Node\Stmt\For_' => true,
		'PhpParser\Node\Stmt\Foreach_' => true,
		'PhpParser\Node\Stmt\Function_' => true,
		'PhpParser\Node\Stmt\Class_' => true,
		'PhpParser\Node\Stmt\Interface_' => true,
		'PhpParser\Node\Stmt\Trait_' => true,
		'PhpParser\Node\Stmt\Enum_' => true,
		'PhpParser\Node\Stmt\ClassMethod' => true,
		'PhpParser\Node\Stmt\EnumCase' => true,
		'PhpParser\Node\Stmt\TraitUseAdaptation\Precedence' => true,
		'PhpParser\Node\Stmt\TraitUseAdaptation\Alias' => true,
		'PhpParser\Node\PropertyHook' => true,
		'PhpParser\Node\DeclareItem' => true,
		'PhpParser\Node\Expr\Closure' => true,
		'PhpParser\Node\Expr\ArrowFunction' => true,
	];

	/**
	 * Classes with a non-trivial constructor where property-slot writes are
	 * nevertheless correct for every grammar call site (the normalization
	 * can never fire on parser-produced values). Mirrors the hand-written
	 * port's per-class analysis.
	 */
	private const FORCED_SLOT_CLASSES = [
		'PhpParser\Node\Identifier' => 'ctor only throws on an empty name; the grammar never produces one',
		'PhpParser\Node\VarLikeIdentifier' => 'inherits Identifier\'s ctor; the grammar never produces an empty name',
		'PhpParser\Node\UseItem' => 'is_string($alias) normalization never fires: alias is null or an Identifier node',
		'PhpParser\Node\Const_' => 'is_string($name) normalization never fires: name is an Identifier node',
		'PhpParser\Node\PropertyItem' => 'is_string($name) normalization never fires: name is a VarLikeIdentifier node',
		'PhpParser\Node\Stmt\Goto_' => 'is_string($name) normalization never fires: name is an Identifier node',
		'PhpParser\Node\Stmt\Label' => 'is_string($name) normalization never fires: name is an Identifier node',
		'PhpParser\Node\Expr\PropertyFetch' => 'is_string($name) normalization never fires: name is an Identifier/Expr node',
		'PhpParser\Node\Expr\NullsafePropertyFetch' => 'is_string($name) normalization never fires: name is an Identifier/Expr node',
		'PhpParser\Node\Expr\MethodCall' => 'is_string($name) normalization never fires: name is an Identifier/Expr node',
		'PhpParser\Node\Expr\NullsafeMethodCall' => 'is_string($name) normalization never fires: name is an Identifier/Expr node',
		'PhpParser\Node\Expr\StaticCall' => 'is_string($name) normalization never fires: name is an Identifier/Expr/Variable node',
		'PhpParser\Node\Expr\StaticPropertyFetch' => 'is_string($name) normalization never fires: name is a VarLikeIdentifier/Expr node',
		'PhpParser\Node\Expr\ClassConstFetch' => 'is_string($name) normalization never fires: name is an Identifier/Expr/Error node',
	];

	/** PhpVersion query methods are fixed thresholds on PhpVersion->id (see vendor PhpVersion.php). */
	private const PHP_VERSION_METHODS = [
		'supportsUnicodeEscapes' => 'phpVersionId >= 70000',
		'allowsInvalidOctals' => 'phpVersionId < 70000',
		'allowsAssignNewByReference' => 'phpVersionId < 70000',
	];

	/**
	 * $self helper methods returning a value; the ParserEngine methods carry
	 * the same names, so the map only holds arg specs and the return kind.
	 * arg specs: zvp (borrowed zv::Ref), attrs (owned zv::Arr), pos (C int), bool
	 */
	private const VALUE_METHODS = [
		'handleNamespaces' => [['zvp'], 'owned'],
		'handleBuiltinTypes' => [['zvp'], 'owned'],
		'handleHaltCompiler' => [[], 'owned'],
		'maybeCreateNop' => [['pos', 'pos'], 'owned_undef'],
		'maybeCreateZeroLengthNop' => [['pos'], 'owned_undef'],
		'inlineHtmlHasLeadingNewline' => [['pos'], 'bool'],
		'fixupArrayDestructuring' => [['zvp'], 'owned'],
		'parseLNumber' => [['zvp', 'attrs', 'bool'], 'owned'],
		'parseNumString' => [['zvp', 'attrs'], 'owned'],
		'parseDocString' => [['zvp', 'zvp', 'zvp', 'attrs', 'attrs', 'bool'], 'owned'],
		'createExitExpr' => [['zvp', 'pos', 'zvp', 'attrs'], 'owned'],
		'getIntCastKind' => [['zvp'], 'long'],
		'getFloatCastKind' => [['zvp'], 'long'],
		'getBoolCastKind' => [['zvp'], 'long'],
		'getStringCastKind' => [['zvp'], 'long'],
	];

	/**
	 * $self helper methods called as statements (void); the ParserEngine
	 * method names match the PHP names.
	 * arg specs: zvp, pos, long (slot coerced via zv::Ref::toLong())
	 */
	private const VOID_METHODS = [
		'checkClassModifier' => [['long', 'long', 'pos']],
		'checkModifier' => [['long', 'long', 'pos']],
		'checkPropertyHookModifiers' => [['long', 'long', 'pos']],
		'checkParam' => [['zvp']],
		'checkTryCatch' => [['zvp']],
		'checkNamespace' => [['zvp']],
		'checkClass' => [['zvp', 'pos']],
		'checkInterface' => [['zvp', 'pos']],
		'checkEnum' => [['zvp', 'pos']],
		'checkClassMethod' => [['zvp', 'pos']],
		'checkClassConst' => [['zvp', 'pos']],
		'checkUseUse' => [['zvp', 'pos']],
		'checkPropertyHooksForMultiProperty' => [['zvp', 'pos']],
		'checkEmptyPropertyHookList' => [['zvp', 'pos']],
		'checkConstantAttributes' => [['zvp']],
		'checkPipeOperatorParentheses' => [['zvp']],
		'addPropertyNameToHooks' => [['zvp']],
		'fixupAlternativeElse' => [['zvp']],
		'postprocessList' => [['zvp']],
		// checkPropertyHook is special-cased: (?int $paramListPos) maps to (int, bool hasParamList)
	];

	/** @var array<string, string> alias => FQCN prefix, parsed from Php8.php's use statements */
	private array $imports;

	private Toks $toks;

	private int $tmpCounter = 0;

	private int $subCounter = 0;

	/** @var array<string, bool> declared local attrs variables => consumed yet */
	private array $attrsLocals = [];

	/** @var array<string, array{trivial: bool}> memoized ctor triviality per FQCN */
	private static array $ctorTriviality = [];

	/** @param array<string, string> $imports */
	public function __construct(array $imports)
	{
		$this->imports = $imports;
	}

	/**
	 * @return list<string> fully indented case-body lines (without `return true;`)
	 * @throws TranspileFailure
	 */
	public function transpile(string $body): array
	{
		$this->toks = new Toks($body);
		$this->tmpCounter = 0;
		$this->subCounter = 0;
		$this->attrsLocals = [];
		$lines = [];
		while (!$this->toks->atEnd()) {
			foreach ($this->parseStatement(2) as $line) {
				$lines[] = $line;
			}
		}
		foreach ($this->attrsLocals as $name => $consumed) {
			if (!$consumed) {
				throw new TranspileFailure(sprintf('local $%s (attrs) is never consumed', $name));
			}
		}
		return $lines;
	}

	private function tmp(): string
	{
		$this->tmpCounter++;
		return 't' . $this->tmpCounter;
	}

	private function subName(): string
	{
		$this->subCounter++;
		return 'sub' . $this->subCounter;
	}

	private function fail(string $message): void
	{
		throw new TranspileFailure($message);
	}

	// ===== statements =====

	/** @return list<string> */
	private function parseStatement(int $indent): array
	{
		$t = $this->toks->peek();
		if ($t === ';') {
			$this->toks->next();
			return [];
		}
		if (is_array($t) && ($t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT)) {
			$this->toks->next();
			if (strpos($t[1], '/*') !== 0) {
				$this->fail('unsupported comment style: ' . $t[1]);
			}
			return [$this->ind($indent) . preg_replace('/\s+/', ' ', $t[1])];
		}
		if (is_array($t) && $t[0] === T_IF) {
			return $this->parseIf($indent);
		}
		if (is_array($t) && $t[0] === T_THROW) {
			return $this->parseThrow($indent);
		}
		if (is_array($t) && $t[0] === T_VARIABLE) {
			if ($t[1] === '$self') {
				return $this->parseSelfStatement($indent);
			}
			return $this->parseLocalStatement($indent);
		}
		$this->fail('unsupported statement start: ' . (is_array($t) ? token_name($t[0]) . '(' . $t[1] . ')' : var_export($t, true)));
		return [];
	}

	/** @return list<string> */
	private function parseThrow(int $indent): array
	{
		$this->toks->expect(T_THROW);
		$this->toks->expect(T_NEW);
		$name = $this->parseClassName();
		if ($this->resolveFqcn($name) !== 'PhpParser\Error') {
			$this->fail('throw of unsupported class ' . $name);
		}
		$this->toks->expect('(');
		$msg = $this->parseExpr();
		if ($msg->kind !== 'cstr') {
			$this->fail('throw new Error() message must be a string literal');
		}
		$this->toks->expect(',');
		$attrs = $this->parseExpr();
		if ($attrs->kind !== 'attrs') {
			$this->fail('throw new Error() attributes must be an attributes expression');
		}
		$this->toks->expect(')');
		$this->toks->expect(';');
		return [$this->ind($indent) . sprintf('fatalError("%s", %s);', $msg->code, $attrs->code)];
	}

	/** @return list<string> */
	private function parseSelfStatement(int $indent): array
	{
		$this->toks->expect(T_VARIABLE); // $self
		$this->toks->expect(T_OBJECT_OPERATOR);
		$name = $this->toks->expectIdent();

		if ($name === 'semValue') {
			return $this->parseSemValueStatement($indent);
		}
		if ($name === 'semStack') {
			// $self->semStack[POS][] = EXPR;
			[$n, $m] = $this->parseSemStackIndex();
			$slot = sprintf('PN_SEM(%d, %d)', $n, $m);
			$this->toks->expect('[');
			$this->toks->expect(']');
			$this->toks->expect('=');
			$rhs = $this->parseExpr();
			$this->toks->expect(';');
			if ($rhs->kind !== 'slot') {
				$this->fail('semStack push of non-slot value (kind ' . $rhs->kind . ')');
			}
			return [$this->ind($indent) . sprintf('pushOnto(%s, %s);', $slot, $rhs->code)];
		}
		if ($name === 'errorState') {
			$this->toks->expect('=');
			$value = $this->toks->expectInt();
			$this->toks->expect(';');
			return [$this->ind($indent) . sprintf('errorState = %d;', $value)];
		}
		if ($name === 'createdArrays' || $name === 'parenthesizedArrowFunctions') {
			$this->toks->expect(T_OBJECT_OPERATOR);
			$method = $this->toks->expectIdent();
			if ($method !== 'offsetSet') {
				$this->fail(sprintf('unsupported %s->%s()', $name, $method));
			}
			$this->toks->expect('(');
			$arg = $this->parseExpr();
			$this->toks->expect(')');
			$this->toks->expect(';');
			if ($arg->code !== 'semValue.ref()') {
				$this->fail($name . '->offsetSet() on something else than $self->semValue');
			}
			$fn = $name === 'createdArrays' ? 'createdArraysAdd' : 'parenthesizedArrowFunctionsAdd';
			return [$this->ind($indent) . sprintf('%s(semValue.ref());', $fn)];
		}
		if ($name === 'emitError') {
			return $this->parseEmitError($indent);
		}
		if ($name === 'checkPropertyHook') {
			$this->toks->expect('(');
			$node = $this->parseExpr();
			$this->toks->expect(',');
			if ($this->toks->isIdent('null')) {
				$this->toks->next();
				$posCode = '0, false';
			} else {
				$pos = $this->parseExpr();
				if ($pos->kind !== 'pos') {
					$this->fail('checkPropertyHook() second argument must be a position');
				}
				$posCode = $pos->code . ', true';
			}
			$this->toks->expect(')');
			$this->toks->expect(';');
			return [$this->ind($indent) . sprintf('checkPropertyHook(%s, %s);', $this->toZvpSimple($node, 'checkPropertyHook'), $posCode)];
		}
		if (isset(self::VOID_METHODS[$name])) {
			[$specs] = self::VOID_METHODS[$name];
			$this->toks->expect('(');
			$args = [];
			foreach ($specs as $i => $spec) {
				if ($i > 0) {
					$this->toks->expect(',');
				}
				$args[] = $this->convertArg($this->parseExpr(), $spec, $name);
			}
			$this->toks->expect(')');
			$this->toks->expect(';');
			return [$this->ind($indent) . sprintf('%s(%s);', $name, implode(', ', $args))];
		}
		$this->fail('unsupported $self member in statement position: ' . $name);
		return [];
	}

	/** @return list<string> */
	private function parseSemValueStatement(int $indent): array
	{
		if ($this->toks->is(T_OBJECT_OPERATOR)) {
			$this->toks->next();
			$member = $this->toks->expectIdent();
			if ($member === 'setAttribute' && $this->toks->is('(')) {
				$this->toks->next();
				$key = $this->parseExpr();
				if ($key->kind !== 'cstr') {
					$this->fail('setAttribute() key must be a string literal');
				}
				$this->toks->expect(',');
				$value = $this->parseExpr();
				$this->toks->expect(')');
				$this->toks->expect(';');
				$owned = $this->toOwned($value, 'setAttribute value');
				$lines = $this->indentAll($value->pre, $indent);
				$lines[] = $this->ind($indent) . sprintf('setNodeAttribute(semValue.ref(), "%s", %s);', $key->code, $owned);
				return array_merge($lines, $this->indentAll($value->post, $indent));
			}
			// $self->semValue->PROP = EXPR;
			$this->toks->expect('=');
			$rhs = $this->parseExpr();
			$this->toks->expect(';');
			$owned = $this->toOwned($rhs, 'property write value');
			$lines = $this->indentAll($rhs->pre, $indent);
			$lines[] = $this->ind($indent) . sprintf('propWrite(semValue.ref(), "%s", %s);', $member, $owned);
			return array_merge($lines, $this->indentAll($rhs->post, $indent));
		}

		$this->toks->expect('=');
		$rhs = $this->parseExpr();
		$this->toks->expect(';');
		$lines = $this->indentAll($rhs->pre, $indent);
		switch ($rhs->kind) {
			case 'slot':
			case 'owned':
			case 'attrs':
				$lines[] = $this->ind($indent) . sprintf('semValue = %s;', $rhs->code);
				break;
			case 'owned_undef':
				$tmp = $this->tmp();
				$lines[] = $this->ind($indent) . sprintf('zv::Val %s = %s;', $tmp, $rhs->code);
				$lines[] = $this->ind($indent) . sprintf('if (%s.isUndef()) {', $tmp);
				$lines[] = $this->ind($indent + 1) . sprintf('%s = zv::Val::null(); /* PHP null return */', $tmp);
				$lines[] = $this->ind($indent) . '}';
				$lines[] = $this->ind($indent) . sprintf('semValue = std::move(%s);', $tmp);
				break;
			case 'long':
				$lines[] = $this->ind($indent) . sprintf('semValue = zv::Val::integer(%s);', $rhs->code);
				break;
			case 'bool':
				$lines[] = $this->ind($indent) . sprintf('semValue = zv::Val::boolean(%s);', $rhs->code);
				break;
			case 'null':
				$lines[] = $this->ind($indent) . 'semValue = zv::Val::null();';
				break;
			default:
				$this->fail('unsupported semValue assignment of kind ' . $rhs->kind);
		}
		return array_merge($lines, $this->indentAll($rhs->post, $indent));
	}

	/** @return list<string> */
	private function parseEmitError(int $indent): array
	{
		// already consumed: $self->emitError
		$this->toks->expect('(');
		$this->toks->expect(T_NEW);
		$name = $this->parseClassName();
		if ($this->resolveFqcn($name) !== 'PhpParser\Error') {
			$this->fail('emitError() of unsupported class ' . $name);
		}
		$this->toks->expect('(');
		$msg = $this->parseExpr();
		if ($msg->kind !== 'cstr') {
			$this->fail('emitError() message must be a string literal');
		}
		$this->toks->expect(',');
		$attrs = $this->parseExpr();
		if ($attrs->kind !== 'attrs') {
			$this->fail('emitError() attributes must be an attributes expression');
		}
		$this->toks->expect(')');
		$this->toks->expect(')');
		$this->toks->expect(';');
		return [$this->ind($indent) . sprintf('emitError("%s", %s);', $msg->code, $attrs->code)];
	}

	/** @return list<string> */
	private function parseLocalStatement(int $indent): array
	{
		$t = $this->toks->expect(T_VARIABLE);
		$name = substr($t[1], 1);
		if ($this->toks->is('[')) {
			// $attrs['key'] = EXPR;
			if (!isset($this->attrsLocals[$name])) {
				$this->fail('index write to undeclared local $' . $name);
			}
			$this->toks->next();
			$key = $this->parseExpr();
			if ($key->kind !== 'cstr') {
				$this->fail('local array write key must be a string literal');
			}
			$this->toks->expect(']');
			$this->toks->expect('=');
			$rhs = $this->parseExpr();
			$this->toks->expect(';');
			$owned = $this->toOwned($rhs, 'attrs value');
			$lines = $this->indentAll($rhs->pre, $indent);
			$lines[] = $this->ind($indent) . sprintf('%s.set("%s", %s);', $name, $key->code, $owned);
			return array_merge($lines, $this->indentAll($rhs->post, $indent));
		}
		$this->toks->expect('=');
		$rhs = $this->parseExpr();
		$this->toks->expect(';');
		if ($rhs->kind !== 'attrs') {
			$this->fail(sprintf('local $%s holds unsupported kind %s (only attributes arrays are supported)', $name, $rhs->kind));
		}
		if (isset($this->attrsLocals[$name])) {
			$this->fail('local $' . $name . ' redeclared');
		}
		$this->attrsLocals[$name] = false;
		$lines = $this->indentAll($rhs->pre, $indent);
		$lines[] = $this->ind($indent) . sprintf('zv::Arr %s = %s;', $name, $rhs->code);
		return array_merge($lines, $this->indentAll($rhs->post, $indent));
	}

	/** @return list<string> */
	private function parseIf(int $indent): array
	{
		$this->toks->expect(T_IF);
		$this->toks->expect('(');
		$cond = $this->parseCondition();
		$this->toks->expect(')');
		$lines = [$this->ind($indent) . sprintf('if (%s) {', $cond)];
		foreach ($this->parseBranch($indent + 1) as $line) {
			$lines[] = $line;
		}
		while (true) {
			if ($this->toks->is(T_ELSEIF) || ($this->toks->is(T_ELSE) && $this->toks->is(T_IF, 1))) {
				if ($this->toks->tryConsume(T_ELSEIF) === false) {
					$this->toks->expect(T_ELSE);
					$this->toks->expect(T_IF);
				}
				$this->toks->expect('(');
				$cond = $this->parseCondition();
				$this->toks->expect(')');
				$lines[] = $this->ind($indent) . sprintf('} else if (%s) {', $cond);
				foreach ($this->parseBranch($indent + 1) as $line) {
					$lines[] = $line;
				}
				continue;
			}
			if ($this->toks->is(T_ELSE)) {
				$this->toks->next();
				$lines[] = $this->ind($indent) . '} else {';
				foreach ($this->parseBranch($indent + 1) as $line) {
					$lines[] = $line;
				}
				continue;
			}
			break;
		}
		$lines[] = $this->ind($indent) . '}';
		return $lines;
	}

	/** @return list<string> */
	private function parseBranch(int $indent): array
	{
		if ($this->toks->tryConsume('{')) {
			$lines = [];
			while (!$this->toks->is('}')) {
				foreach ($this->parseStatement($indent) as $line) {
					$lines[] = $line;
				}
			}
			$this->toks->expect('}');
			return $lines;
		}
		return $this->parseStatement($indent);
	}

	private function parseCondition(): string
	{
		$negated = false;
		if ($this->toks->tryConsume('!')) {
			$negated = true;
		}
		$lhs = $this->parseExpr();
		$code = null;
		if ($this->toks->is(T_IS_IDENTICAL) || $this->toks->is(T_IS_NOT_IDENTICAL)) {
			$isNot = $this->toks->is(T_IS_NOT_IDENTICAL);
			$this->toks->next();
			$zvp = $this->toZvpSimple($lhs, 'comparison');
			if ($this->toks->isIdent('null')) {
				$this->toks->next();
				$code = sprintf('%s.isNull()', $zvp);
			} elseif ($this->toks->is(T_CONSTANT_ENCAPSED_STRING)) {
				$str = $this->parseExpr();
				$code = sprintf('%s.stringEquals("%s")', $zvp, $str->code);
				if ($isNot) {
					$code = sprintf('!(%s)', $code);
					$isNot = false;
				}
			} else {
				$this->fail('unsupported comparison operand in condition');
			}
			if ($isNot) {
				$code = '!' . $code;
			}
		} elseif ($this->toks->is(T_INSTANCEOF)) {
			$this->toks->next();
			$name = $this->parseClassName();
			$this->resolveFqcn($name); // validate it resolves
			$code = sprintf('isInstanceOf(%s, "%s")', $this->toZvpSimple($lhs, 'instanceof'), $this->cAlias($name));
		} else {
			if ($lhs->kind !== 'bool') {
				$this->fail('unsupported bare condition of kind ' . $lhs->kind);
			}
			$code = $lhs->code;
		}
		if ($lhs->pre !== [] || $lhs->post !== []) {
			$this->fail('condition operand requires temporaries');
		}
		if ($negated) {
			$code = sprintf('!(%s)', $code);
		}
		return $code;
	}

	// ===== expressions =====

	private function parseExpr(): CExpr
	{
		$expr = $this->parsePrimary();
		// only the modifier-mask `A | B` binary occurs in the closures
		while ($this->toks->is('|')) {
			$this->toks->next();
			$rhs = $this->parsePrimary();
			$expr = new CExpr('long', sprintf('%s | %s', $this->toLong($expr), $this->toLong($rhs)));
		}
		return $expr;
	}

	private function parsePrimary(): CExpr
	{
		$t = $this->toks->peek();

		if ($t === '-') {
			$this->toks->next();
			$value = $this->toks->expectInt();
			return new CExpr('long', (string) -$value);
		}
		if (is_array($t) && $t[0] === T_LNUMBER) {
			$this->toks->next();
			return new CExpr('long', (string) (int) $t[1]);
		}
		if (is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING) {
			$this->toks->next();
			$value = $this->phpStringValue($t[1]);
			$expr = new CExpr('cstr', $this->cEscape($value));
			$expr->valueLen = strlen($value);
			return $expr;
		}
		if (is_array($t) && $t[0] === T_VARIABLE) {
			return $this->parseVariableExpr();
		}
		if (is_array($t) && $t[0] === T_NEW) {
			return $this->parseNew();
		}
		if (is_array($t) && $t[0] === T_ARRAY) {
			$this->toks->next();
			$this->toks->expect('(');
			return $this->parseArrayLiteral(')');
		}
		if ($t === '[') {
			$this->toks->next();
			return $this->parseArrayLiteral(']');
		}
		if (is_array($t) && $t[0] === T_STRING) {
			if ($t[1] === 'null') {
				$this->toks->next();
				return new CExpr('null');
			}
			if ($t[1] === 'true') {
				$this->toks->next();
				return new CExpr('bool', 'true');
			}
			if ($t[1] === 'false') {
				$this->toks->next();
				return new CExpr('bool', 'false');
			}
			if ($t[1] === 'substr') {
				return $this->parseSubstr();
			}
		}
		if (is_array($t) && in_array($t[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
			// Class::CONST or Class::staticMethod(...)
			return $this->parseStaticAccess();
		}
		$this->fail('unsupported expression start: ' . (is_array($t) ? token_name($t[0]) . '(' . $t[1] . ')' : var_export($t, true)));
		return new CExpr('null');
	}

	private function parseSubstr(): CExpr
	{
		$this->toks->expect(T_STRING); // substr
		$this->toks->expect('(');
		$subject = $this->parseExpr();
		if ($subject->kind !== 'slot') {
			$this->fail('substr() subject must be a semStack slot');
		}
		$this->toks->expect(',');
		$offset = $this->parseExpr();
		if ($offset->kind !== 'long') {
			$this->fail('substr() offset must be an integer literal');
		}
		$this->toks->expect(')');
		return new CExpr('owned', sprintf('substr(%s, %s)', $subject->code, $offset->code));
	}

	private function parseStaticAccess(): CExpr
	{
		$name = $this->parseClassName();
		$this->toks->expect(T_DOUBLE_COLON);
		$member = $this->toks->expectMemberName();
		$fqcn = $this->resolveFqcn($name);
		if ($this->toks->is('(')) {
			if ($fqcn === 'PhpParser\Node\Scalar\String_' && $member === 'fromString') {
				$this->toks->expect('(');
				$raw = $this->convertArg($this->parseExpr(), 'zvp', 'String_::fromString');
				$this->toks->expect(',');
				$attrs = $this->convertArg($this->parseExpr(), 'attrs', 'String_::fromString');
				$this->toks->expect(',');
				$unicode = $this->convertArg($this->parseExpr(), 'bool', 'String_::fromString');
				$this->toks->expect(')');
				return new CExpr('owned', sprintf('stringFromString(%s, %s, %s)', $raw, $attrs, $unicode));
			}
			if ($fqcn === 'PhpParser\Node\Scalar\Float_' && $member === 'fromString') {
				$this->toks->expect('(');
				$raw = $this->convertArg($this->parseExpr(), 'zvp', 'Float_::fromString');
				$this->toks->expect(',');
				$attrs = $this->convertArg($this->parseExpr(), 'attrs', 'Float_::fromString');
				$this->toks->expect(')');
				return new CExpr('owned', sprintf('floatFromString(%s, %s)', $raw, $attrs));
			}
			$this->fail(sprintf('unsupported static method call %s::%s()', $name, $member));
		}
		if (!defined($fqcn . '::' . $member)) {
			$this->fail(sprintf('cannot resolve constant %s::%s (FQCN %s)', $name, $member, $fqcn));
		}
		$value = constant($fqcn . '::' . $member);
		if (!is_int($value)) {
			$this->fail(sprintf('constant %s::%s is not an integer', $name, $member));
		}
		return new CExpr('long', sprintf('%d /* %s::%s */', $value, $name, $member));
	}

	private function parseVariableExpr(): CExpr
	{
		$t = $this->toks->expect(T_VARIABLE);
		$var = $t[1];

		if ($var === '$stackPos') {
			if ($this->toks->is('-') && $this->toks->is('(', 1)) {
				$this->toks->expect('-');
				$this->toks->expect('(');
				$n = $this->toks->expectInt();
				$this->toks->expect('-');
				$m = $this->toks->expectInt();
				$this->toks->expect(')');
				return new CExpr('pos', sprintf('stackPos - (%d - %d)', $n, $m));
			}
			return new CExpr('pos', 'stackPos');
		}

		if ($var === '$self') {
			$this->toks->expect(T_OBJECT_OPERATOR);
			$member = $this->toks->expectIdent();
			return $this->parseSelfMemberExpr($member);
		}

		// locals: only declared attrs arrays are supported in expressions
		$name = substr($var, 1);
		if (isset($this->attrsLocals[$name])) {
			if ($this->attrsLocals[$name]) {
				$this->fail(sprintf('local $%s (attrs) consumed twice', $name));
			}
			$this->attrsLocals[$name] = true;
			return new CExpr('attrs', sprintf('std::move(%s)', $name));
		}
		$this->fail('unsupported local variable in expression: ' . $var);
		return new CExpr('null');
	}

	private function parseSelfMemberExpr(string $member): CExpr
	{
		if ($member === 'semStack') {
			[$n, $m] = $this->parseSemStackIndex();
			$expr = new CExpr('slot', sprintf('PN_SEM(%d, %d)', $n, $m));
			return $this->parseSlotSuffixes($expr);
		}
		if ($member === 'semValue') {
			$expr = new CExpr('slot', 'semValue.ref()');
			return $this->parseSlotSuffixes($expr);
		}
		if ($member === 'tokenPos') {
			return new CExpr('pos', 'tokenPos');
		}
		if ($member === 'tokenStartStack' || $member === 'tokenEndStack') {
			$which = $member === 'tokenStartStack' ? 'start' : 'end';
			$this->toks->expect('[');
			$pos = $this->parseExpr();
			$this->toks->expect(']');
			$expr = new CExpr('tokpos');
			if ($pos->code === 'stackPos') {
				$expr->tok = ['which' => $which, 'n' => null, 'm' => null];
				$expr->code = sprintf('%s[stackPos]', $member);
			} elseif (preg_match('/^stackPos - \((\d+) - (\d+)\)$/', $pos->code, $mm) === 1) {
				$expr->tok = ['which' => $which, 'n' => (int) $mm[1], 'm' => (int) $mm[2]];
				$expr->code = sprintf('%s(%d, %d)', $which === 'start' ? 'PN_TOKSTART' : 'PN_TOKEND', $mm[1], $mm[2]);
			} else {
				$this->fail('unsupported token stack index: ' . $pos->code);
			}
			return $expr;
		}
		if ($member === 'getAttributes') {
			$this->toks->expect('(');
			$a = $this->parseExpr();
			$this->toks->expect(',');
			$b = $this->parseExpr();
			$this->toks->expect(')');
			if ($a->kind !== 'tokpos' || $b->kind !== 'tokpos' || $a->tok['which'] !== 'start' || $b->tok['which'] !== 'end') {
				$this->fail('getAttributes() arguments must be tokenStartStack/tokenEndStack expressions');
			}
			if ($a->tok['n'] === null) {
				$this->fail('getAttributes() with a bare $stackPos start position');
			}
			$n = $a->tok['n'];
			$m1 = $a->tok['m'];
			if ($b->tok['n'] === null) {
				$m2 = $n; // tokenEndStack[$stackPos] == PN_TOKEND(n, n)
			} else {
				// PN_ATTRS only uses the difference n-m; renormalize onto $n
				$m2 = $n - ($b->tok['n'] - $b->tok['m']);
			}
			return new CExpr('attrs', sprintf('PN_ATTRS(%d, %d, %d)', $n, $m1, $m2));
		}
		if ($member === 'phpVersion') {
			$this->toks->expect(T_OBJECT_OPERATOR);
			$method = $this->toks->expectIdent();
			$this->toks->expect('(');
			$this->toks->expect(')');
			if (!isset(self::PHP_VERSION_METHODS[$method])) {
				$this->fail('unsupported PhpVersion method: ' . $method);
			}
			return new CExpr('bool', sprintf('%s /* PhpVersion::%s() */', self::PHP_VERSION_METHODS[$method], $method));
		}
		if (isset(self::VALUE_METHODS[$member])) {
			[$specs, $retKind] = self::VALUE_METHODS[$member];
			$this->toks->expect('(');
			$args = [];
			$pre = [];
			$post = [];
			foreach ($specs as $i => $spec) {
				if ($i > 0) {
					$this->toks->expect(',');
				}
				$arg = $this->parseExpr();
				$args[] = $this->convertArgCollect($arg, $spec, $member, $pre, $post);
			}
			$this->toks->expect(')');
			$expr = new CExpr($retKind, sprintf('%s(%s)', $member, implode(', ', $args)));
			$expr->pre = $pre;
			$expr->post = $post;
			return $expr;
		}
		$this->fail('unsupported $self member in expression: ' . $member);
		return new CExpr('null');
	}

	private function parseSlotSuffixes(CExpr $expr): CExpr
	{
		while (true) {
			if ($this->toks->is('[')) {
				$this->toks->next();
				$idx = $this->toks->expectInt();
				$this->toks->expect(']');
				$expr = $this->withCarried($expr, new CExpr('slot', sprintf('itemAt(%s, %d)', $expr->code, $idx)));
				continue;
			}
			if ($this->toks->is(T_OBJECT_OPERATOR) && !$this->toks->is('(', 2)) {
				$this->toks->next();
				$prop = $this->toks->expectIdent();
				$expr = $this->withCarried($expr, new CExpr('slot', sprintf('prop(%s, "%s")', $expr->code, $prop)));
				continue;
			}
			break;
		}
		return $expr;
	}

	private function withCarried(CExpr $old, CExpr $new): CExpr
	{
		$new->pre = $old->pre;
		$new->post = $old->post;
		return $new;
	}

	/** @return array{int, int} */
	private function parseSemStackIndex(): array
	{
		$this->toks->expect('[');
		$this->toks->expect(T_VARIABLE); // $stackPos
		$this->toks->expect('-');
		$this->toks->expect('(');
		$n = $this->toks->expectInt();
		$this->toks->expect('-');
		$m = $this->toks->expectInt();
		$this->toks->expect(')');
		$this->toks->expect(']');
		return [$n, $m];
	}

	private function parseArrayLiteral(string $closer): CExpr
	{
		if ($this->toks->tryConsume($closer)) {
			return new CExpr('owned', 'zv::Arr::empty()');
		}
		// assoc (subNodes-style) if the first element is 'key' =>
		if ($this->toks->is(T_CONSTANT_ENCAPSED_STRING) && $this->toks->is(T_DOUBLE_ARROW, 1)) {
			return $this->parseSubNodesLiteral($closer);
		}
		$elements = [];
		while (true) {
			$elements[] = $this->parseExpr();
			if ($this->toks->tryConsume(',')) {
				if ($this->toks->tryConsume($closer)) {
					break;
				}
				continue;
			}
			$this->toks->expect($closer);
			break;
		}
		if (count($elements) > 2) {
			$this->fail('positional array literal with more than 2 elements');
		}
		$pre = [];
		$post = [];
		$args = [];
		foreach ($elements as $element) {
			// arrayOf() borrows every element, so PHP null becomes an owned
			// zv::Val::null() temporary (nullptr is only valid for node props)
			$args[] = $this->toBorrowedCollect($element, 'array element', $pre, $post, false);
		}
		$expr = new CExpr('owned', count($args) === 1
			? sprintf('arrayOf(%s)', $args[0])
			: sprintf('arrayOf(%s, %s)', $args[0], $args[1]));
		$expr->pre = $pre;
		$expr->post = $post;
		return $expr;
	}

	private function parseSubNodesLiteral(string $closer): CExpr
	{
		$sub = $this->subName();
		$pre = [sprintf('zv::Arr %s = zv::Arr::empty();', $sub)];
		$post = [];
		while (true) {
			$keyTok = $this->toks->expect(T_CONSTANT_ENCAPSED_STRING);
			$key = $this->phpStringValue($keyTok[1]);
			$this->toks->expect(T_DOUBLE_ARROW);
			$value = $this->parseExpr();
			foreach ($value->pre as $line) {
				$pre[] = $line;
			}
			$pre[] = sprintf('%s.set("%s", %s);', $sub, $this->cEscape($key), $this->toOwned($value, 'subNodes value'));
			foreach ($value->post as $line) {
				$pre[] = $line;
			}
			if ($this->toks->tryConsume(',')) {
				if ($this->toks->tryConsume($closer)) {
					break;
				}
				continue;
			}
			$this->toks->expect($closer);
			break;
		}
		$expr = new CExpr('subarr', $sub);
		$expr->pre = $pre;
		$expr->post = $post;
		return $expr;
	}

	private function parseNew(): CExpr
	{
		$this->toks->expect(T_NEW);
		$srcName = $this->parseClassName();
		$fqcn = $this->resolveFqcn($srcName);
		$this->toks->expect('(');
		/** @var list<CExpr> $args */
		$args = [];
		if (!$this->toks->tryConsume(')')) {
			while (true) {
				$args[] = $this->parseExpr();
				if ($this->toks->tryConsume(',')) {
					if ($this->toks->tryConsume(')')) {
						break;
					}
					continue;
				}
				$this->toks->expect(')');
				break;
			}
		}

		if ($fqcn === 'PhpParser\Node\Name') {
			return $this->buildNameCall('newName(%s, %s)', $srcName, null, $args);
		}
		if ($fqcn === 'PhpParser\Node\Name\FullyQualified' || $fqcn === 'PhpParser\Node\Name\Relative') {
			$alias = $fqcn === 'PhpParser\Node\Name\FullyQualified' ? 'Name\\\\FullyQualified' : 'Name\\\\Relative';
			return $this->buildNameCall('newNameVariant("' . $alias . '", %s, %s)', $srcName, $alias, $args);
		}

		$ctorParams = $this->reflectCtorParams($fqcn, $srcName);
		if (isset(self::CTOR_CLASSES[$fqcn])) {
			return $this->buildCtorNew($srcName, $fqcn, $ctorParams, $args);
		}
		return $this->buildSlotsNew($srcName, $fqcn, $ctorParams, $args);
	}

	/** @param list<CExpr> $args */
	private function buildNameCall(string $format, string $srcName, ?string $alias, array $args): CExpr
	{
		if (count($args) !== 2) {
			$this->fail(sprintf('new %s with %d args (expected 2)', $srcName, count($args)));
		}
		$pre = [];
		$post = [];
		$strOrParts = $this->toRefCollect($args[0], 'Name argument', $pre, $post);
		if ($args[1]->kind !== 'attrs') {
			$this->fail(sprintf('new %s second argument must be an attributes expression', $srcName));
		}
		foreach ($args[1]->pre as $line) {
			$pre[] = $line;
		}
		foreach ($args[1]->post as $line) {
			$post[] = $line;
		}
		$expr = new CExpr('owned', sprintf($format, $strOrParts, $args[1]->code));
		$expr->pre = $pre;
		$expr->post = $post;
		return $expr;
	}

	/**
	 * @param list<ReflectionParameter> $ctorParams
	 * @param list<CExpr> $args
	 */
	private function buildCtorNew(string $srcName, string $fqcn, array $ctorParams, array $args): CExpr
	{
		// newNodeCtor passes props then attrs as the constructor arguments, so
		// the call shape must be exact: every parameter supplied, $attributes last.
		if (count($args) !== count($ctorParams)) {
			$this->fail(sprintf(
				'new %s (ctor mode) with %d args but the constructor has %d parameters',
				$srcName,
				count($args),
				count($ctorParams),
			));
		}
		$last = $ctorParams[count($ctorParams) - 1];
		if ($last->getName() !== 'attributes') {
			$this->fail(sprintf('new %s (ctor mode): last constructor parameter is not $attributes', $srcName));
		}
		$attrsExpr = $args[count($args) - 1];
		if ($attrsExpr->kind !== 'attrs') {
			$this->fail(sprintf('new %s (ctor mode): last argument is not an attributes expression', $srcName));
		}
		$pre = [];
		$post = [];
		$propArgs = [];
		for ($i = 0; $i < count($args) - 1; $i++) {
			$propArgs[] = $this->toBorrowedCollect($args[$i], 'constructor argument', $pre, $post);
		}
		foreach ($attrsExpr->pre as $line) {
			$pre[] = $line;
		}
		foreach ($attrsExpr->post as $line) {
			$post[] = $line;
		}
		$expr = new CExpr('owned', sprintf(
			'newNodeCtor("%s", %s, %s)',
			$this->cAlias($srcName),
			$attrsExpr->code,
			implode(', ', $propArgs),
		));
		$expr->pre = $pre;
		$expr->post = $post;
		return $expr;
	}

	/**
	 * @param list<ReflectionParameter> $ctorParams
	 * @param list<CExpr> $args
	 */
	private function buildSlotsNew(string $srcName, string $fqcn, array $ctorParams, array $args): CExpr
	{
		$this->assertTrivialCtorOrForced($fqcn, $srcName);
		if (count($args) > count($ctorParams)) {
			$this->fail(sprintf('new %s with more args than constructor parameters', $srcName));
		}
		$pre = [];
		$post = [];
		$attrsCode = null;
		$propArgs = [];
		foreach ($ctorParams as $i => $param) {
			if ($param->getName() === 'attributes') {
				if (!isset($args[$i])) {
					$this->fail(sprintf('new %s does not pass $attributes', $srcName));
				}
				if ($args[$i]->kind !== 'attrs') {
					$this->fail(sprintf('new %s: $attributes argument is not an attributes expression', $srcName));
				}
				foreach ($args[$i]->pre as $line) {
					$pre[] = $line;
				}
				foreach ($args[$i]->post as $line) {
					$post[] = $line;
				}
				$attrsCode = $args[$i]->code;
				continue;
			}
			if (isset($args[$i])) {
				if ($args[$i]->kind === 'subarr') {
					$this->fail(sprintf('new %s: subNodes-style array argument outside ctor mode', $srcName));
				}
				$propArgs[] = $this->toBorrowedCollect($args[$i], 'constructor argument', $pre, $post);
				continue;
			}
			// omitted trailing parameter: materialize its reflected default
			$propArgs[] = $this->defaultPropArg($param, $srcName);
		}
		if ($attrsCode === null) {
			$this->fail(sprintf('new %s: constructor has no $attributes parameter', $srcName));
		}
		if ($propArgs === []) {
			$expr = new CExpr('owned', sprintf('newNode("%s", %s)', $this->cAlias($srcName), $attrsCode));
		} else {
			$expr = new CExpr('owned', sprintf(
				'newNode("%s", %s, %s)',
				$this->cAlias($srcName),
				$attrsCode,
				implode(', ', $propArgs),
			));
		}
		$expr->pre = $pre;
		$expr->post = $post;
		return $expr;
	}

	private function defaultPropArg(ReflectionParameter $param, string $srcName): string
	{
		if (!$param->isDefaultValueAvailable()) {
			$this->fail(sprintf('new %s omits parameter $%s which has no default', $srcName, $param->getName()));
		}
		$default = $param->getDefaultValue();
		$comment = sprintf('/* ctor default for omitted $%s */', $param->getName());
		if ($default === null) {
			return 'nullptr ' . $comment;
		}
		if (is_bool($default)) {
			return sprintf('zv::Val::boolean(%s) %s', $default ? 'true' : 'false', $comment);
		}
		if (is_int($default)) {
			return sprintf('zv::Val::integer(%d) %s', $default, $comment);
		}
		if (is_array($default) && $default === []) {
			return sprintf('zv::Arr::empty() %s', $comment);
		}
		$this->fail(sprintf('new %s omits parameter $%s with unsupported default', $srcName, $param->getName()));
		return '';
	}

	/** @return list<ReflectionParameter> */
	private function reflectCtorParams(string $fqcn, string $srcName): array
	{
		if (!class_exists($fqcn)) {
			$this->fail(sprintf('class %s (%s) does not exist', $srcName, $fqcn));
		}
		$ctor = (new ReflectionClass($fqcn))->getConstructor();
		if ($ctor === null) {
			$this->fail(sprintf('class %s has no constructor', $srcName));
		}
		return $ctor->getParameters();
	}

	private function assertTrivialCtorOrForced(string $fqcn, string $srcName): void
	{
		if (isset(self::FORCED_SLOT_CLASSES[$fqcn])) {
			return;
		}
		if (!isset(self::$ctorTriviality[$fqcn])) {
			self::$ctorTriviality[$fqcn] = ['trivial' => $this->ctorIsTrivial($fqcn)];
		}
		if (!self::$ctorTriviality[$fqcn]['trivial']) {
			$this->fail(sprintf(
				'new %s: constructor of %s is not a trivial property-assignment ctor; add it to CTOR_CLASSES (PN_NEW_CTOR) or FORCED_SLOT_CLASSES with a justification',
				$srcName,
				$fqcn,
			));
		}
	}

	/**
	 * A ctor is trivial when its body consists only of `$this->x = $x;`
	 * statements — then writing property slots directly (PN_NEW) is exactly
	 * equivalent to calling it.
	 */
	private function ctorIsTrivial(string $fqcn): bool
	{
		$ctor = (new ReflectionClass($fqcn))->getConstructor();
		if ($ctor === null || $ctor->getFileName() === false) {
			return false;
		}
		$lines = file($ctor->getFileName());
		if ($lines === false) {
			return false;
		}
		$src = implode('', array_slice($lines, $ctor->getStartLine() - 1, $ctor->getEndLine() - $ctor->getStartLine() + 1));
		$open = strpos($src, '{');
		$close = strrpos($src, '}');
		if ($open === false || $close === false || $close < $open) {
			return false;
		}
		$body = substr($src, $open + 1, $close - $open - 1);
		$body = preg_replace('~/\*.*?\*/~s', '', $body);
		$body = preg_replace('~//[^\n]*~', '', $body);
		return preg_match('~^(?:\s*\$this->(\w+)\s*=\s*\$(\1)\s*;)*\s*$~', $body) === 1;
	}

	private function parseClassName(): string
	{
		$t = $this->toks->peek();
		if (is_array($t) && in_array($t[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
			$this->toks->next();
			$name = ltrim($t[1], '\\');
			// PHP < 8 tokenizes qualified names as T_STRING/T_NS_SEPARATOR sequences
			while ($this->toks->is(T_NS_SEPARATOR)) {
				$this->toks->next();
				$name .= '\\' . $this->toks->expectIdent();
			}
			return $name;
		}
		$this->fail('expected a class name');
		return '';
	}

	private function resolveFqcn(string $srcName): string
	{
		$parts = explode('\\', $srcName);
		$first = $parts[0];
		if (!isset($this->imports[$first])) {
			$this->fail(sprintf('class name %s does not resolve through Php8.php\'s use statements', $srcName));
		}
		$parts[0] = $this->imports[$first];
		return implode('\\', $parts);
	}

	/** pn_class_resolve tries PhpParser\Node\<alias>, then PhpParser\<alias>: the source name works as-is. */
	private function cAlias(string $srcName): string
	{
		return str_replace('\\', '\\\\', $srcName);
	}

	// ===== conversions =====

	private function toOwned(CExpr $expr, string $context): string
	{
		switch ($expr->kind) {
			case 'slot':
				return sprintf('zv::Val::copyOf(%s)', $expr->code);
			case 'owned':
			case 'attrs':
				return $expr->code;
			case 'long':
				return sprintf('zv::Val::integer(%s)', $expr->code);
			case 'bool':
				return sprintf('zv::Val::boolean(%s)', $expr->code);
			case 'null':
				return 'zv::Val::null()';
			case 'cstr':
				return sprintf('zv::Val::string("%s", %d)', $expr->code, $expr->valueLen);
			default:
				$this->fail(sprintf('cannot convert kind %s to an owned zv::Val (%s)', $expr->kind, $context));
				return '';
		}
	}

	private function toLong(CExpr $expr): string
	{
		if ($expr->kind === 'slot') {
			return sprintf('%s.toLong()', $expr->code);
		}
		if ($expr->kind === 'long') {
			return $expr->code;
		}
		$this->fail('cannot convert kind ' . $expr->kind . ' to zend_long');
		return '';
	}

	/** zv::Ref conversion for simple contexts that admit no temporaries */
	private function toZvpSimple(CExpr $expr, string $context): string
	{
		if ($expr->kind !== 'slot' || $expr->pre !== [] || $expr->post !== []) {
			$this->fail(sprintf('%s operand must be a simple borrowed zv::Ref, got kind %s', $context, $expr->kind));
		}
		return $expr->code;
	}

	/**
	 * Borrowed-argument conversion for newNode/newNodeCtor props and arrayOf
	 * elements: slots pass through, scalars become inline owned temporaries
	 * (released at the end of the statement, after the callee addref'd them),
	 * node-building results become named zv::Val locals (RAII release).
	 *
	 * @param list<string> $pre
	 * @param list<string> $post
	 */
	private function toBorrowedCollect(CExpr $expr, string $context, array &$pre, array &$post, bool $nullAsPtr = true): string
	{
		foreach ($expr->pre as $line) {
			$pre[] = $line;
		}
		switch ($expr->kind) {
			case 'slot':
			case 'subarr':
				foreach ($expr->post as $line) {
					$post[] = $line;
				}
				return $expr->code;
			case 'null':
				return $nullAsPtr ? 'nullptr' : 'zv::Val::null()';
			case 'owned':
			case 'attrs':
				$tmp = $this->tmp();
				$pre[] = sprintf('zv::Val %s = %s;', $tmp, $expr->code);
				foreach ($expr->post as $line) {
					$pre[] = $line;
				}
				return $tmp;
			case 'long':
				return sprintf('zv::Val::integer(%s)', $expr->code);
			case 'bool':
				return sprintf('zv::Val::boolean(%s)', $expr->code);
			case 'cstr':
				return sprintf('zv::Val::string("%s", %d)', $expr->code, $expr->valueLen);
			default:
				$this->fail(sprintf('cannot pass kind %s as a borrowed argument (%s)', $expr->kind, $context));
				return '';
		}
	}

	/**
	 * zv::Ref conversion for helper-method arguments; owned values are
	 * materialized into named zv::Val locals and passed as .ref().
	 *
	 * @param list<string> $pre
	 * @param list<string> $post
	 */
	private function toRefCollect(CExpr $expr, string $context, array &$pre, array &$post): string
	{
		foreach ($expr->pre as $line) {
			$pre[] = $line;
		}
		switch ($expr->kind) {
			case 'slot':
				foreach ($expr->post as $line) {
					$post[] = $line;
				}
				return $expr->code;
			case 'owned':
			case 'attrs':
				$tmp = $this->tmp();
				$pre[] = sprintf('zv::Val %s = %s;', $tmp, $expr->code);
				foreach ($expr->post as $line) {
					$pre[] = $line;
				}
				return $tmp . '.ref()';
			case 'cstr':
				$tmp = $this->tmp();
				$pre[] = sprintf('zv::Val %s = zv::Val::string("%s", %d);', $tmp, $expr->code, $expr->valueLen);
				return $tmp . '.ref()';
			default:
				$this->fail(sprintf('cannot pass kind %s as zv::Ref (%s)', $expr->kind, $context));
				return '';
		}
	}

	/** Argument conversion for void helper calls (no temporaries allowed). */
	private function convertArg(CExpr $expr, string $spec, string $method): string
	{
		if ($expr->pre !== [] || $expr->post !== []) {
			$this->fail(sprintf('%s() argument requires temporaries', $method));
		}
		return $this->convertArgSpec($expr, $spec, $method);
	}

	/**
	 * Argument conversion for value helper calls (temporaries collected).
	 *
	 * @param list<string> $pre
	 * @param list<string> $post
	 */
	private function convertArgCollect(CExpr $expr, string $spec, string $method, array &$pre, array &$post): string
	{
		if ($spec === 'zvp') {
			return $this->toRefCollect($expr, $method . '() argument', $pre, $post);
		}
		foreach ($expr->pre as $line) {
			$pre[] = $line;
		}
		foreach ($expr->post as $line) {
			$post[] = $line;
		}
		return $this->convertArgSpec($expr, $spec, $method);
	}

	private function convertArgSpec(CExpr $expr, string $spec, string $method): string
	{
		switch ($spec) {
			case 'zvp':
				if ($expr->kind !== 'slot') {
					$this->fail(sprintf('%s() argument must be a borrowed zv::Ref, got %s', $method, $expr->kind));
				}
				return $expr->code;
			case 'attrs':
				if ($expr->kind !== 'attrs') {
					$this->fail(sprintf('%s() argument must be an attributes expression, got %s', $method, $expr->kind));
				}
				return $expr->code;
			case 'pos':
				if ($expr->kind === 'tokpos') {
					return $expr->code;
				}
				if ($expr->kind !== 'pos' && $expr->kind !== 'long') {
					$this->fail(sprintf('%s() argument must be a position, got %s', $method, $expr->kind));
				}
				return $expr->code;
			case 'long':
				return $this->toLong($expr);
			case 'bool':
				if ($expr->kind !== 'bool') {
					$this->fail(sprintf('%s() argument must be a bool, got %s', $method, $expr->kind));
				}
				return $expr->code;
			default:
				$this->fail('unknown arg spec ' . $spec);
				return '';
		}
	}

	// ===== small utilities =====

	private function ind(int $level): string
	{
		return str_repeat("\t", $level);
	}

	/**
	 * @param list<string> $lines
	 * @return list<string>
	 */
	private function indentAll(array $lines, int $indent): array
	{
		$out = [];
		foreach ($lines as $line) {
			$out[] = $this->ind($indent) . $line;
		}
		return $out;
	}

	private function phpStringValue(string $literal): string
	{
		$quote = $literal[0];
		$inner = substr($literal, 1, -1);
		if ($quote === "'") {
			return str_replace(['\\\\', "\\'"], ['\\', "'"], $inner);
		}
		if (strpos($inner, '\\') !== false || strpos($inner, '$') !== false) {
			$this->fail('unsupported double-quoted string literal: ' . $literal);
		}
		return $inner;
	}

	private function cEscape(string $value): string
	{
		if (preg_match('/^[\x20-\x7e]*$/', $value) !== 1) {
			$this->fail('string literal contains non-printable characters');
		}
		return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
	}

}

// ===== extraction =====

/**
 * @return array{entries: array<int, string|null>, imports: array<string, string>}
 */
function extractReduceCallbacks(string $php8Path): array
{
	$src = file_get_contents($php8Path);
	if ($src === false) {
		fwrite(STDERR, "cannot read $php8Path\n");
		exit(1);
	}
	$tokens = token_get_all($src);
	$n = count($tokens);

	// parse the use statements (Error, Modifiers, Node, Expr, Name, Scalar, Stmt, ...)
	$imports = [];
	for ($i = 0; $i < $n; $i++) {
		$t = $tokens[$i];
		if (!is_array($t) || $t[0] !== T_USE) {
			continue;
		}
		$fqcn = '';
		for ($j = $i + 1; $j < $n; $j++) {
			$u = $tokens[$j];
			if (is_array($u) && in_array($u[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
				$fqcn .= $u[1];
			} elseif (is_array($u) && $u[0] === T_WHITESPACE) {
				continue;
			} elseif ($u === ';') {
				break;
			} else {
				$fqcn = ''; // aliased or grouped use — not present in Php8.php
				break;
			}
		}
		if ($fqcn !== '') {
			$parts = explode('\\', trim($fqcn, '\\'));
			$imports[end($parts)] = trim($fqcn, '\\');
		}
	}

	// locate the initReduceCallbacks method body
	$start = null;
	for ($i = 0; $i < $n; $i++) {
		$t = $tokens[$i];
		if (is_array($t) && $t[0] === T_STRING && $t[1] === 'initReduceCallbacks') {
			$start = $i;
			break;
		}
	}
	if ($start === null) {
		fwrite(STDERR, "initReduceCallbacks not found in $php8Path\n");
		exit(1);
	}
	$i = $start;
	while ($i < $n && $tokens[$i] !== '{') {
		$i++;
	}
	$depth = 1;
	$i++;

	$entries = [];
	while ($i < $n && $depth > 0) {
		$t = $tokens[$i];
		if ($t === '{') {
			$depth++;
			$i++;
			continue;
		}
		if ($t === '}') {
			$depth--;
			$i++;
			continue;
		}
		if (is_array($t) && $t[0] === T_LNUMBER) {
			$rule = (int) $t[1];
			$j = $i + 1;
			while ($j < $n && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT], true)) {
				$j++;
			}
			if (!is_array($tokens[$j]) || $tokens[$j][0] !== T_DOUBLE_ARROW) {
				$i++;
				continue;
			}
			$j++;
			while ($j < $n && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT], true)) {
				$j++;
			}
			if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING && strtolower($tokens[$j][1]) === 'null') {
				$entries[$rule] = null;
				$i = $j + 1;
				continue;
			}
			// static function ($self, $stackPos) { BODY }
			while ($j < $n && $tokens[$j] !== '{') {
				$j++;
			}
			$bodyStart = $j + 1;
			$d = 1;
			$k = $bodyStart;
			while ($k < $n && $d > 0) {
				$u = $tokens[$k];
				if ($u === '{' || (is_array($u) && in_array($u[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
					$d++;
				} elseif ($u === '}') {
					$d--;
					if ($d === 0) {
						break;
					}
				}
				$k++;
			}
			$body = '';
			for ($p = $bodyStart; $p < $k; $p++) {
				$body .= is_array($tokens[$p]) ? $tokens[$p][1] : $tokens[$p];
			}
			$entries[$rule] = trim($body);
			$i = $k + 1;
			continue;
		}
		$i++;
	}
	ksort($entries);
	return ['entries' => $entries, 'imports' => $imports];
}

function normalizeBody(string $body): string
{
	return preg_replace('/\s+/', ' ', trim($body));
}

// ===== helper definitions included per generated file when referenced =====

/** @return list<array{needle: string, code: string}> */
function helperDefinitions(): array
{
	return [
		[
			'needle' => 'phpArrayPop',
			'code' => "/* array_pop(\$this->semValue) on a packed 0..n-1 list, with the same\n"
				. " * copy-on-write separation PHP performs on write. Also resets\n"
				. " * nNextFreeElement like array_pop. */\n"
				. "static void phpArrayPop(zval *arr)\n"
				. "{\n"
				. "\tSEPARATE_ARRAY(arr);\n"
				. "\tHashTable *ht = Z_ARRVAL_P(arr);\n"
				. "\tuint32_t n = zend_hash_num_elements(ht);\n"
				. "\tif (n == 0) {\n"
				. "\t\treturn;\n"
				. "\t}\n"
				. "\tzend_hash_index_del(ht, n - 1);\n"
				. "\tht->nNextFreeElement = (zend_long) (n - 1);\n"
				. "\tzend_hash_internal_pointer_reset(ht);\n"
				. "}\n",
		],
	];
}

// ===== main =====

$php8Path = $root . '/' . PHP8_RELATIVE;
$overridesDir = __DIR__ . '/../src/parser/action-overrides';
$parserDir = __DIR__ . '/../src/parser';

$extracted = extractReduceCallbacks($php8Path);
$entries = $extracted['entries'];
$imports = $extracted['imports'];

if (count($entries) === 0 || $imports === []) {
	fwrite(STDERR, "extraction failed: no entries or no use statements found\n");
	exit(1);
}

$transpiler = new Transpiler($imports);

/** @var array<int, array{lines: list<string>, override: string|null}> $cases */
$cases = [];
/** @var list<array{rule: int, sha1: string, body: string, reason: string}> $failures */
$failures = [];
/** @var array<string, true> $usedOverrides */
$usedOverrides = [];

foreach ($entries as $rule => $body) {
	if ($body === null) {
		continue;
	}
	$norm = normalizeBody($body);
	$sha1 = sha1($norm);
	$overrideFile = $overridesDir . '/' . $sha1 . '.inc';
	if (is_file($overrideFile)) {
		$content = rtrim(file_get_contents($overrideFile), "\n");
		$cases[$rule] = ['lines' => explode("\n", $content), 'override' => $sha1];
		$usedOverrides[$sha1] = true;
		continue;
	}
	try {
		$cases[$rule] = ['lines' => $transpiler->transpile($body), 'override' => null];
	} catch (TranspileFailure $e) {
		$failures[] = ['rule' => $rule, 'sha1' => $sha1, 'body' => $norm, 'reason' => $e->getMessage()];
	}
}

if ($failures !== []) {
	fwrite(STDERR, sprintf(
		"generate-parser-actions: %d closure(s) could not be transpiled and have no override.\n"
		. "Port each one to C++ by hand (the existing generated cases are the cookbook) and save it as\n"
		. "%s/<sha1>.inc, then re-run.\n\n",
		count($failures),
		'turbo-ext/src/parser/action-overrides',
	));
	foreach ($failures as $failure) {
		fwrite(STDERR, sprintf(
			"rule %d\n  sha1:   %s\n  reason: %s\n  body:   %s\n\n",
			$failure['rule'],
			$failure['sha1'],
			$failure['reason'],
			$failure['body'],
		));
	}
	exit(1);
}

// warn about orphaned overrides (upstream body changed or vanished)
$orphans = [];
foreach (glob($overridesDir . '/*.inc') ?: [] as $file) {
	$sha1 = basename($file, '.inc');
	if (!isset($usedOverrides[$sha1])) {
		$orphans[] = basename($file);
	}
}
if ($orphans !== []) {
	fwrite(STDERR, "warning: unused override file(s) — the upstream body they matched no longer exists:\n");
	foreach ($orphans as $orphan) {
		fwrite(STDERR, "  action-overrides/$orphan\n");
	}
}

// ===== split into three roughly equal-by-rule-count files =====

$ruleNumbers = array_keys($cases);
sort($ruleNumbers);
$total = count($ruleNumbers);
$perFile = (int) ceil($total / 3);
$fileRules = [
	array_slice($ruleNumbers, 0, $perFile),
	array_slice($ruleNumbers, $perFile, $perFile),
	array_slice($ruleNumbers, 2 * $perFile),
];
$split1 = $fileRules[1][0];
$split2 = $fileRules[2][0];

$generatedBy = '/* GENERATED by turbo-ext/bin/generate-parser-actions.php — do not edit */';

$outputs = [];
foreach ($fileRules as $idx => $rules) {
	$fileNo = $idx + 1;
	$first = $rules[0];
	$last = $rules[count($rules) - 1];

	$body = '';
	foreach ($rules as $rule) {
		$case = $cases[$rule];
		if ($case['override'] !== null) {
			$body .= sprintf("\t/* rule %d (from action-overrides/%s.inc) */\n", $rule, $case['override']);
		} else {
			$body .= sprintf("\t/* rule %d */\n", $rule);
		}
		$body .= sprintf("\tcase %d: {\n", $rule);
		foreach ($case['lines'] as $line) {
			$body .= rtrim($line) === '' ? "\n" : $line . "\n";
		}
		$body .= "\t\treturn true;\n";
		$body .= "\t}\n";
	}

	$helpers = '';
	foreach (helperDefinitions() as $helper) {
		if (preg_match('/\b' . preg_quote($helper['needle'], '/') . '\b/', $body) === 1) {
			$helpers .= "\n" . $helper['code'];
		}
	}

	$content = $generatedBy . "\n";
	$content .= "/*\n";
	$content .= sprintf(" * Reduce actions for rules %d-%d of php-parser's Php8 grammar\n", $first, $last);
	$content .= " * (vendor/nikic/php-parser/lib/PhpParser/Parser/Php8.php, initReduceCallbacks).\n";
	$content .= " * Rules with a null callback have no case here; returning false makes the\n";
	$content .= " * engine apply the default action. Hand-ported special cases are emitted\n";
	$content .= " * verbatim from src/parser/action-overrides/<sha1-of-php-body>.inc.\n";
	$content .= " */\n";
	$content .= "\n#include \"ParserEngine.h\"\n";
	$content .= "\nnamespace phpstanturbo {\n";
	$content .= $helpers;
	$content .= "\n";
	$content .= sprintf("bool ParserEngine::reduceRange%d(int rule, int stackPos)\n", $fileNo);
	$content .= "{\n";
	$content .= "\tswitch (rule) {\n";
	$content .= $body;
	$content .= "\tdefault:\n";
	$content .= "\t\treturn false;\n";
	$content .= "\t}\n";
	$content .= "}\n";
	$content .= "\n} // namespace phpstanturbo\n";

	$outputs[sprintf('%s/ParserRunnerActions%d.cpp', $parserDir, $fileNo)] = $content;
}

$splitHeader = $generatedBy . "\n";
$splitHeader .= "/* Dispatch boundaries for ParserEngine::reduce() (ParserRunner.cpp): rules\n";
$splitHeader .= " * below PN_REDUCE_SPLIT_1 live in ParserRunnerActions1.cpp (reduceRange1),\n";
$splitHeader .= " * below PN_REDUCE_SPLIT_2 in ParserRunnerActions2.cpp (reduceRange2), the\n";
$splitHeader .= " * rest in ParserRunnerActions3.cpp (reduceRange3). */\n";
$splitHeader .= "\n";
$splitHeader .= "#ifndef PHPSTANTURBO_PN_ACTIONS_SPLIT_H\n";
$splitHeader .= "#define PHPSTANTURBO_PN_ACTIONS_SPLIT_H\n";
$splitHeader .= "\n";
$splitHeader .= sprintf("#define PN_REDUCE_SPLIT_1 %d\n", $split1);
$splitHeader .= sprintf("#define PN_REDUCE_SPLIT_2 %d\n", $split2);
$splitHeader .= "\n";
$splitHeader .= "#endif\n";
$outputs[$parserDir . '/ParserRunnerActionsSplit.h'] = $splitHeader;

foreach ($outputs as $path => $content) {
	if (is_file($path) && file_get_contents($path) === $content) {
		continue;
	}
	file_put_contents($path, $content);
}

$overrideRules = [];
foreach ($cases as $rule => $case) {
	if ($case['override'] !== null) {
		$overrideRules[] = $rule;
	}
}

printf(
	"generated %d cases (%d transpiled, %d from overrides: rules %s) across 3 files; split at %d / %d\n",
	count($cases),
	count($cases) - count($overrideRules),
	count($overrideRules),
	implode(', ', $overrideRules),
	$split1,
	$split2,
);
