/*
 * phpstanturbo::ParserEngine — native port of php-parser 5.8.0's LALR engine
 * and node building (vendor/nikic/php-parser: ParserAbstract.php +
 * Parser/Php8.php), structured to mirror ParserAbstract method for method:
 * doParse(), getAttributes(), emitError(), the semantic-action helpers
 * (handleNamespaces, parseLNumber, checkClass, ...) and the generated reduce
 * actions (reduceRange1/2/3, see bin/generate-parser-actions.php).
 *
 * Shadowing seam: PHPStan\Parser\ParserRunner::parse($parser, $code, $errorHandler).
 * The native path activates only for exact PhpParser\Parser\Php8 objects with a
 * known-safe error handler; everything else falls back to $parser->parse().
 *
 * ===== Value ownership discipline (read before touching anything) =====
 *
 * The zv:: types spell the borrowed/owned rules of the old pn_ helpers:
 *  - semStack slots hold OWNED zvals; PN_SEM() hands out BORROWED zv::Ref views.
 *  - semValue is OWNED; assigning a zv::Val moves it in, assigning a zv::Ref
 *    addref-copies (mirroring PHP's `$this->semValue = $this->semStack[...]`).
 *  - functions taking zv::Ref borrow; functions taking zv::Val by value
 *    consume; functions returning zv::Val/zv::Arr transfer ownership.
 *  - node builders addref borrowed props and consume their `attributes`.
 *  - Mirrors PHP semantics exactly: PHP's `$this->semStack[$stackPos] = $v`
 *    keeps values alive until the slot is overwritten; so do we.
 *
 * "throwing" in reduce actions: PhpParser\Error cannot unwind natively (no C++
 * exceptions), so fatalError() records the abort and the engine stops after
 * the action returns — the exact mirror of the PHP catch in doParse().
 */

#ifndef PHPSTANTURBO_PARSER_ENGINE_H
#define PHPSTANTURBO_PARSER_ENGINE_H

#include "../support.h"
#include "../zv.h"

#include <cstddef>
#include <utility>

namespace phpstanturbo {

/* Tokens are copied out of the PHP Token objects once per parse for locality.
 * text is BORROWED from the Token object (the tokens zval array outlives the parse). */
struct Token
{
	int id;
	zend_string *text;
	int line;
	int pos; /* start file offset */
};

/* Resolved-once-per-process data for one parser CE (the LALR tables). */
struct Tables
{
	zend_class_entry *parserCe;
	int tokenToSymbolMapSize;
	int actionTableSize;
	int gotoTableSize;
	int invalidSymbol;
	int errorSymbol;
	int defaultAction;
	int unexpectedTokenRule;
	int YY2TBLSTATE;
	int numNonLeafStates;
	int numRules;             /* count of ruleToLength */
	int *phpTokenToSymbol;    /* dense, phpTokenToSymbolSize entries, -1 = invalid */
	int phpTokenToSymbolSize;
	int *actionBase;          /* numStates*2 (leaf-extended) as stored */
	int actionBaseSize;
	int *action;
	int *actionCheck;
	int *actionDefault;
	int *gotoBase;
	int *gotoTable;
	int *gotoCheck;
	int *gotoDefault;
	int *ruleToNonTerminal;
	int *ruleToLength;
	zend_string **symbolToName; /* borrowed persistent copies (interned dup) */
	int symbolToNameSize;
	bool *dropTokens;           /* indexed by php token id, size phpTokenToSymbolSize */
};

/* Per-node-class construction plan, cached per process in the class registry. */
struct NodeClassInfo
{
	zend_class_entry *ce;
	/* property slots in constructor-parameter order (excluding the parameter
	 * named $attributes wherever it sits); -1 entries mean "call the ctor". */
	int propSlots[16];
	uint32_t attrsSlot; /* slot of NodeAbstract::$attributes */
	int numProps;
	bool useCtor; /* subNodes-style or otherwise non-trivial ctor */
};

/*
 * A borrowed node-property / array-element argument: a semStack slot (Ref),
 * an owned temporary (Val, released by the caller's scope after the callee
 * addref'd it), or PHP null (nullptr). Single pointer, zero-cost.
 */
class Borrowed
{
	zval *p;

public:
	Borrowed(std::nullptr_t) : p(NULL) {}
	Borrowed(zv::Ref r) : p(r.raw()) {}
	Borrowed(zv::Val &v) : p(v.raw()) {}
	Borrowed(zv::Val &&v) : p(v.raw()) {}

	zval *raw() const { return p; }
};

/*
 * $this->semValue: an owned slot assignable from an owned zv::Val (move) or a
 * borrowed zv::Ref (addref copy) — the two forms `$this->semValue = ...`
 * takes in the reduce actions.
 */
class SemValue
{
	zval z;

public:
	SemValue() { ZVAL_UNDEF(&z); }
	SemValue(const SemValue &) = delete;
	SemValue &operator=(const SemValue &) = delete;
	~SemValue() { zval_ptr_dtor(&z); }

	void operator=(zv::Val owned)
	{
		zval_ptr_dtor(&z);
		zval v = owned.take();
		ZVAL_COPY_VALUE(&z, &v);
	}

	void operator=(zv::Ref borrowed)
	{
		/* addref before the release: the borrow may alias the current value */
		Z_TRY_ADDREF_P(borrowed.raw());
		zval_ptr_dtor(&z);
		ZVAL_COPY_VALUE(&z, borrowed.raw());
	}

	zv::Ref ref() { return zv::Ref(&z); }
	zval *raw() { return &z; }
};

class ParserEngine
{
public:
	/* borrows the parser and error handler for the duration of one parse */
	ParserEngine(zval *parserObj, zval *errorHandler);
	~ParserEngine();

	/* extracts the process-wide tables from the first Php8 parser seen;
	 * false → this parser cannot be handled natively (caller delegates) */
	static bool prepareTables(zval *parserObj);

	/* mirrors ParserAbstract::parse(): tokenize → doParse → post-process.
	 * false → delegate to the PHP implementation. */
	bool parse(zval *code, zval *return_value);

private:
	/* ===== engine state (mirrors ParserAbstract's protected properties) ===== */

	zval *semStack = NULL; /* owned zvals */
	int *stateStack = NULL;
	int *tokenStartStack = NULL;
	int *tokenEndStack = NULL;
	int stackCap = 0;
	SemValue semValue;
	Token *tokens = NULL;
	int numTokens = 0;
	int tokenPos = 0;
	int errorState = 0;
	bool aborted = false;   /* a would-be-thrown PhpParser\Error occurred */
	zv::Val abortErrorMsg;  /* string, set when aborted (UNDEF = pending zend exception) */
	zv::Val abortErrorAttrs;

	const Tables *tables = NULL;

	/* environment (borrowed for the duration of the parse) */
	zval *parserObj;
	zval *errorHandler;
	zval *tokensZv = NULL; /* PHP array of Token objects */
	zend_long phpVersionId = 0;

	/* per-parse trackers (native equivalents of the SplObjectStorages) */
	HashTable createdArrays;         /* obj handle => zval of Array_ node (owned) */
	HashTable parenthesizedArrowFns; /* obj handle => null */

	/* ===== the LALR loop (ParserRunner.cpp) ===== */

	zv::Val doParse();                                  /* ParserAbstract::doParse() */
	bool reduce(int rule, int stackPos);                /* one semantic action; false = default */
	bool reduceRange1(int rule, int stackPos);          /* generated, ParserRunnerActions1.cpp */
	bool reduceRange2(int rule, int stackPos);          /* generated, ParserRunnerActions2.cpp */
	bool reduceRange3(int rule, int stackPos);          /* generated, ParserRunnerActions3.cpp */
	zend_string *getErrorMessage(int symbol, int state); /* ParserAbstract::getErrorMessage() */
	void growStacks(int needed);
	void writeSlot(int pos, zval owned);
	bool buildTokens();                                 /* dense Token copies of $this->tokens */
	void checkCreatedArrays();                          /* the createdArrays loop in parse() */
	zv::Val makeComment(const Token *tok, int tokenPos);

	/* CommentAnnotatingVisitor port */
	struct CommentState
	{
		int *positions;
		int count;
		int index;
		int pos;
		bool stopped;
	};
	void annotateComments(zv::Ref stmts);
	bool commentEnterNode(CommentState &st, zend_object *node);
	void commentWalkNode(CommentState &st, zend_object *node);
	void commentWalkArray(CommentState &st, HashTable *ht);

	/* ===== attributes ===== */

	/* getAttributes(tokenStartPos, tokenEndPos): owned array, exact key order */
	zv::Arr getAttributes(int tokenStartPos, int tokenEndPos);
	zv::Arr getAttributesAt(int stackPos); /* ParserAbstract::getAttributesAt() */
	zv::Arr getAttributesForToken(int tokenPos);

	/* ===== errors ===== */

	void emitError(const char *msg, zv::Val attributes);
	void emitError(zend_string *msg, zv::Val attributes); /* msg borrowed */
	/* `throw new Error(...)`: records the abort; the caller must return */
	void fatalError(const char *msg, zv::Val attributes);

	/* ===== class resolution + node creation (scoped-phar safe) ===== */

	/* "Expr\\Assign"-style alias relative to PhpParser\; useCtor forces
	 * PHP-constructor invocation (exact semantics for ctors with logic) */
	static NodeClassInfo *resolveNodeClass(const char *alias, bool useCtor);

	/* core: props are borrowed zval* (NULL = PHP null); attributes consumed.
	 * On failure records the abort and returns UNDEF. */
	zv::Val createNode(const char *alias, bool useCtor, zv::Val attributes, int nprops, zval **props);

	/* new Alias(props..., $attributes) via property-slot writes */
	template <typename... Props>
	zv::Val newNode(const char *alias, zv::Val attributes, Props &&... props)
	{
		zval *raw[] = {Borrowed(std::forward<Props>(props)).raw()...};
		return createNode(alias, false, std::move(attributes), (int) sizeof...(Props), raw);
	}

	zv::Val newNode(const char *alias, zv::Val attributes) /* zero-property nodes */
	{
		return createNode(alias, false, std::move(attributes), 0, NULL);
	}

	/* new Alias(props..., $attributes) through the real PHP constructor */
	template <typename... Props>
	zv::Val newNodeCtor(const char *alias, zv::Val attributes, Props &&... props)
	{
		zval *raw[] = {Borrowed(std::forward<Props>(props)).raw()...};
		return createNode(alias, true, std::move(attributes), (int) sizeof...(Props), raw);
	}

	/* new Name(...) / new Name\FullyQualified(...) with Name::prepareName() */
	zv::Val newName(zv::Ref strOrParts, zv::Val attributes);
	zv::Val newNameVariant(const char *alias, zv::Ref strOrParts, zv::Val attributes);

	/* ===== node / array access ===== */

	static bool isInstanceOf(zv::Ref value, const char *alias);
	/* borrowed property read; raw() == NULL when the property is missing */
	static zv::Ref prop(zv::Ref node, const char *name);
	static void propWrite(zv::Ref node, const char *name, zv::Val value);
	zv::Arr getNodeAttributes(zv::Ref node);                               /* $node->getAttributes() */
	void setNodeAttribute(zv::Ref node, const char *key, zv::Val value);   /* $node->setAttribute() */
	/* borrowed $array[$index] element read; raw() == NULL when absent */
	static zv::Ref itemAt(zv::Ref array, zend_ulong index);

	/* $slot[] = $value on an array held in a semStack slot */
	static void pushOnto(zv::Ref arraySlot, zv::Ref value)
	{
		SEPARATE_ARRAY(arraySlot.raw());
		Z_TRY_ADDREF_P(value.raw());
		zend_hash_next_index_insert(Z_ARRVAL_P(arraySlot.raw()), value.raw());
	}

	static void pushOnto(zv::Ref arraySlot, zv::Val value)
	{
		SEPARATE_ARRAY(arraySlot.raw());
		zval v = value.take();
		zend_hash_next_index_insert(Z_ARRVAL_P(arraySlot.raw()), &v);
	}

	/* array($v1) / array($v1, $v2) literals */
	static zv::Arr arrayOf(Borrowed v1)
	{
		zv::Arr a = zv::Arr::create(1);
		a.push(zv::Ref(v1.raw()));
		return a;
	}

	static zv::Arr arrayOf(Borrowed v1, Borrowed v2)
	{
		zv::Arr a = zv::Arr::create(2);
		a.push(zv::Ref(v1.raw()));
		a.push(zv::Ref(v2.raw()));
		return a;
	}

	/* substr($str, $offset) on a semStack string slot */
	static zv::Val substr(zv::Ref str, zend_long offset);

	/* ===== per-parse trackers ===== */

	void createdArraysAdd(zv::Ref arrayNode);
	void createdArraysRemove(zv::Ref arrayNode);
	void parenthesizedArrowFunctionsAdd(zv::Ref expr);

	/* ===== ParserAbstract semantic helpers (ParserRunnerHelpers.cpp) ===== */

	zv::Val handleNamespaces(zv::Ref stmts);
	int getNamespacingStyle(zv::Ref stmts);
	zv::Arr getNamespaceErrorAttributes(zv::Ref nsNode);
	void fixupNamespaceAttributes(zv::Ref nsNode);
	zv::Val handleBuiltinTypes(zv::Ref nameNode);
	static zend_long getFloatCastKind(zv::Ref castTokenText);
	static zend_long getIntCastKind(zv::Ref castTokenText);
	static zend_long getBoolCastKind(zv::Ref castTokenText);
	static zend_long getStringCastKind(zv::Ref castTokenText);
	zv::Val parseLNumber(zv::Ref str, zv::Arr attributes, bool allowInvalidOctal);
	zv::Val parseNumString(zv::Ref str, zv::Val attributes); /* Int_|String_ */
	zv::Val parseDocString(zv::Ref startToken, zv::Ref contents, zv::Ref endToken,
		zv::Arr attributes, zv::Val endAttributes, bool parseUnicodeEscape);
	/* String_::parseEscapeSequences() on an InterpolatedStringPart's ->value,
	 * through the real PHP static method (used by the encapsed-string actions) */
	void parseEscapeSequencesInPart(zv::Ref partNode, const char *quote);
	/* String_::parseEscapeSequences() native port; NULL after fatalError() */
	zend_string *parseEscapeSequences(zend_string *str, bool hasQuote, char quote, bool parseUnicodeEscape);
	/* ParserAbstract::stripIndentation(); errors get a copy of attrsBorrowed */
	zend_string *stripIndentation(zend_string *str, zend_long indentLen, char indentChar,
		bool newlineAtStart, bool newlineAtEnd, zv::Ref attrsBorrowed);
	int getCommentBeforeToken(int tokenPos); /* token index or -1 */
	zv::Val maybeCreateZeroLengthNop(int tokenPos);
	zv::Val maybeCreateNop(int tokenStartPos, int tokenEndPos);
	zv::Val handleHaltCompiler();
	bool inlineHtmlHasLeadingNewline(int stackPos);
	zv::Val fixupArrayDestructuring(zv::Ref arrayNode);
	void postprocessList(zv::Ref listNode);
	void fixupAlternativeElse(zv::Ref node);
	void checkClassModifier(zend_long a, zend_long b, int modifierStackPos);
	void checkModifier(zend_long a, zend_long b, int modifierStackPos);
	void checkPropertyHookModifiers(zend_long a, zend_long b, int modifierPos);
	void verifyModifier(zend_long a, zend_long b, int modifierStackPos);
	void checkParam(zv::Ref param);
	void checkTryCatch(zv::Ref node);
	void checkNamespace(zv::Ref node);
	void checkClass(zv::Ref node, int namePos);
	void checkInterface(zv::Ref node, int namePos);
	void checkEnum(zv::Ref node, int namePos);
	void checkClassMethod(zv::Ref node, int modifierPos);
	void checkClassConst(zv::Ref node, int modifierPos);
	void checkUseUse(zv::Ref node, int namePos);
	void checkPropertyHooksForMultiProperty(zv::Ref property, int hookPos);
	void checkEmptyPropertyHookList(zv::Ref hooks, int hookPos);
	void checkPropertyHook(zv::Ref hook, int paramListPos, bool hasParamList);
	void checkConstantAttributes(zv::Ref node);
	void checkPipeOperatorParentheses(zv::Ref expr);
	void addPropertyNameToHooks(zv::Ref node);
	zv::Val createExitExpr(zv::Ref nameStr, int namePos, zv::Ref args, zv::Arr attributes);
	/* Name::prepareName(); NULL after fatalError() */
	zend_string *prepareName(zv::Ref nameVal);
	zv::Val stringFromString(zv::Ref raw, zv::Arr attributes, bool parseUnicodeEscape); /* Scalar\String_::fromString */
	zv::Val floatFromString(zv::Ref raw, zv::Arr attributes);                           /* Scalar\Float_::fromString */
	void checkClassName(zv::Ref name, int namePos);
	void checkImplementedInterfaces(zv::Ref interfaces);
};

/* Modifiers::* constants replicated (stable public API of php-parser). */
enum
{
	PN_MOD_PUBLIC = 1,
	PN_MOD_PROTECTED = 2,
	PN_MOD_PRIVATE = 4,
	PN_MOD_STATIC = 8,
	PN_MOD_ABSTRACT = 16,
	PN_MOD_FINAL = 32,
	PN_MOD_READONLY = 64,
	PN_MOD_PUBLIC_SET = 128,
	PN_MOD_PROTECTED_SET = 256,
	PN_MOD_PRIVATE_SET = 512,
};

} // namespace phpstanturbo

/*
 * Reduce-action idioms — only meaningful inside ParserEngine member functions
 * with a `stackPos` local (the generated reduceRange* bodies):
 *   PN_SEM(n, m)         $this->semStack[$stackPos - (n - m)]    borrowed zv::Ref
 *   PN_TOKSTART/END(n, m) the token start/end stacks at that slot
 *   PN_ATTRS(n, m1, m2)  getAttributes() over the token span      owned zv::Arr
 */
#define PN_SEM(n, m) zv::Ref(&semStack[stackPos - ((n) - (m))])
#define PN_TOKSTART(n, m) tokenStartStack[stackPos - ((n) - (m))]
#define PN_TOKEND(n, m) tokenEndStack[stackPos - ((n) - (m))]
#define PN_ATTRS(n, m1, m2) getAttributes(PN_TOKSTART(n, m1), PN_TOKEND(n, m2))

#endif
