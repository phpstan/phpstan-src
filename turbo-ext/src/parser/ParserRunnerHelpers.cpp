/*
 * ParserRunnerHelpers.cpp — native ports of php-parser 5.8.0's semantic-action
 * helper methods (ParserAbstract.php lines 560-1341) plus the Node-side
 * fromString builders they rely on (Int_/String_/Float_::fromString,
 * Name::prepareName, Modifiers::verify*), as phpstanturbo::ParserEngine
 * methods named after their ParserAbstract counterparts.
 *
 * Byte-identical AST output vs the PHP implementation is the acceptance bar:
 * error messages, emitError order, and attribute insertion order are ported
 * statement by statement. Ownership rules are documented in ParserEngine.h.
 *
 * Constants replicated from the php-parser sources (values verified there):
 *   Scalar\Int_:        KIND_BIN=2, KIND_OCT=8, KIND_DEC=10, KIND_HEX=16
 *   Scalar\String_:     KIND_SINGLE_QUOTED=1, KIND_DOUBLE_QUOTED=2,
 *                       KIND_HEREDOC=3, KIND_NOWDOC=4
 *   Expr\Cast\Double:   KIND_DOUBLE=1, KIND_FLOAT=2, KIND_REAL=3
 *   Expr\Cast\Int_:     KIND_INT=1, KIND_INTEGER=2
 *   Expr\Cast\Bool_:    KIND_BOOL=1, KIND_BOOLEAN=2
 *   Expr\Cast\String_:  KIND_STRING=1, KIND_BINARY=2
 *   Expr\List_:         KIND_ARRAY=2
 *   Expr\Exit_:         KIND_EXIT=1, KIND_DIE=2
 */

#include "ParserEngine.h"

namespace phpstanturbo {

/* ===== small utilities (pure string/number plumbing, deliberately zend-level) ===== */

/* Owned duplicate of an array (deep-ish: zend_array_dup, values addref'd). */
static zv::Arr dupArray(zv::Ref arr)
{
	zv::Arr r;
	ZVAL_ARR(r.raw(), zend_array_dup(Z_ARRVAL_P(arr.raw())));
	return r;
}

static inline char toLowerAscii(char c)
{
	return (c >= 'A' && c <= 'Z') ? (char) (c + ('a' - 'A')) : c;
}

/* Case-insensitive ASCII equality against a lowercase literal. */
static bool iequals(zend_string *s, const char *lit, size_t litLen)
{
	if (ZSTR_LEN(s) != litLen) {
		return false;
	}
	const char *v = ZSTR_VAL(s);
	for (size_t i = 0; i < litLen; i++) {
		if (toLowerAscii(v[i]) != lit[i]) {
			return false;
		}
	}
	return true;
}

/* strpos(strtolower($hay), $needleLower) !== false */
static bool containsLower(zend_string *hay, const char *needle, size_t nlen)
{
	const char *h = ZSTR_VAL(hay);
	size_t hlen = ZSTR_LEN(hay);
	if (nlen > hlen) {
		return false;
	}
	for (size_t i = 0; i + nlen <= hlen; i++) {
		size_t j = 0;
		while (j < nlen && toLowerAscii(h[i + j]) == needle[j]) {
			j++;
		}
		if (j == nlen) {
			return true;
		}
	}
	return false;
}

static bool isHexDigit(char c)
{
	return (c >= '0' && c <= '9') || (c >= 'a' && c <= 'f') || (c >= 'A' && c <= 'F');
}

/* borrowed "name" string prop of an Identifier/Name node, or NULL */
static zend_string *nodeNameString(zv::Ref node)
{
	zv::Ref n = zv::ObjRef(node.raw()).prop("name", sizeof("name") - 1);
	if (n.raw() == NULL || !n.isString()) {
		return NULL;
	}
	return n.asString();
}

static bool isSpecialClassName(zend_string *name)
{
	return iequals(name, "self", 4) || iequals(name, "parent", 6) || iequals(name, "static", 6);
}

/* ===== runtime token id constants (see the ParserEngine.h declarations) ===== */

#define PNH_TOK_UNRESOLVED (-3)
#define PNH_TOK_MISSING (-2)

static zend_long g_tCommentId = PNH_TOK_UNRESOLVED;
static zend_long g_tDocCommentId = PNH_TOK_UNRESOLVED;
static zend_long g_tWhitespaceId = PNH_TOK_UNRESOLVED;
static zend_long g_tInlineHtmlId = PNH_TOK_UNRESOLVED;

static zend_long tokenConstant(const char *name, size_t len, zend_long *cache)
{
	if (*cache == PNH_TOK_UNRESOLVED) {
		zval *c = zend_get_constant_str(name, len);
		*cache = (c != NULL && Z_TYPE_P(c) == IS_LONG) ? Z_LVAL_P(c) : PNH_TOK_MISSING;
	}
	return *cache;
}

zend_long tokenIdComment()
{
	return tokenConstant("T_COMMENT", sizeof("T_COMMENT") - 1, &g_tCommentId);
}

zend_long tokenIdDocComment()
{
	return tokenConstant("T_DOC_COMMENT", sizeof("T_DOC_COMMENT") - 1, &g_tDocCommentId);
}

zend_long tokenIdWhitespace()
{
	return tokenConstant("T_WHITESPACE", sizeof("T_WHITESPACE") - 1, &g_tWhitespaceId);
}

zend_long tokenIdInlineHtml()
{
	return tokenConstant("T_INLINE_HTML", sizeof("T_INLINE_HTML") - 1, &g_tInlineHtmlId);
}

/* isset($this->dropTokens[$token->id]) — bound by dropTokensSize:
 * T_BAD_CHARACTER sits above the grammar's symbol map */
static bool isDropToken(const Tables *tables, int id)
{
	return id >= 0 && id < tables->dropTokensSize && tables->dropTokens[id];
}

/* ===== numeric parsing helpers ===== */

struct ParsedNum
{
	bool isDouble;
	zend_long lval;
	double dval;
};

/*
 * Port of php-src _php_math_basetozval() (hexdec/bindec/octdec): invalid
 * characters are skipped, values that overflow zend_long continue in double.
 */
static ParsedNum baseToNum(const char *s, size_t len, int base)
{
	ParsedNum r;
	r.isDouble = false;
	r.lval = 0;
	r.dval = 0.0;

	zend_long num = 0;
	double fnum = 0.0;
	int mode = 0;
	const zend_long cutoff = ZEND_LONG_MAX / base;
	const int cutlim = (int) (ZEND_LONG_MAX % base);

	for (size_t i = 0; i < len; i++) {
		char ch = s[i];
		int c;
		if (ch >= '0' && ch <= '9') {
			c = ch - '0';
		} else if (ch >= 'A' && ch <= 'Z') {
			c = ch - 'A' + 10;
		} else if (ch >= 'a' && ch <= 'z') {
			c = ch - 'a' + 10;
		} else {
			continue;
		}
		if (c >= base) {
			continue;
		}
		if (mode == 0) {
			if (num < cutoff || (num == cutoff && c <= cutlim)) {
				num = num * base + c;
				continue;
			}
			fnum = (double) num;
			mode = 1;
		}
		fnum = fnum * base + c;
	}

	if (mode == 1) {
		r.isDouble = true;
		r.dval = fnum;
	} else {
		r.lval = num;
	}
	return r;
}

/*
 * strtol-style parse with saturation on overflow — matches PHP's (int) cast
 * on integer-format strings and intval($str, $base). Only bases <= 10 are
 * needed here (digits '0'-'9').
 */
static zend_long strtolBase(const char *s, size_t len, int base)
{
	size_t i = 0;
	while (i < len && (s[i] == ' ' || s[i] == '\t' || s[i] == '\n'
			|| s[i] == '\v' || s[i] == '\f' || s[i] == '\r')) {
		i++;
	}
	bool neg = false;
	if (i < len && (s[i] == '+' || s[i] == '-')) {
		neg = s[i] == '-';
		i++;
	}
	zend_ulong acc = 0;
	bool over = false;
	const zend_ulong limit = neg ? ((zend_ulong) ZEND_LONG_MAX + 1) : (zend_ulong) ZEND_LONG_MAX;
	for (; i < len; i++) {
		char ch = s[i];
		if (ch < '0' || ch > '9') {
			break;
		}
		zend_ulong d = (zend_ulong) (ch - '0');
		if ((int) d >= base) {
			break;
		}
		if (!over) {
			if (acc > (limit - d) / (zend_ulong) base) {
				over = true;
			} else {
				acc = acc * (zend_ulong) base + d;
			}
		}
	}
	if (over) {
		return neg ? ZEND_LONG_MIN : ZEND_LONG_MAX;
	}
	if (neg) {
		return (zend_long) (0 - acc);
	}
	return (zend_long) acc;
}

/* ===== string transformation helpers ===== */

/* str_replace($str, '_', '') — returns owned string */
static zend_string *stripUnderscores(zend_string *in)
{
	if (memchr(ZSTR_VAL(in), '_', ZSTR_LEN(in)) == NULL) {
		return zend_string_copy(in);
	}
	smart_str out = {};
	const char *s = ZSTR_VAL(in);
	size_t n = ZSTR_LEN(in);
	for (size_t i = 0; i < n; i++) {
		if (s[i] != '_') {
			smart_str_appendc(&out, s[i]);
		}
	}
	return smart_str_extract(&out);
}

/* One left-to-right non-overlapping pass replacing the 2-byte sequence f0 f1 with repl. */
static zend_string *replace2Bytes(zend_string *in, char f0, char f1, char repl)
{
	smart_str out = {};
	const char *s = ZSTR_VAL(in);
	size_t n = ZSTR_LEN(in);
	size_t i = 0;
	while (i < n) {
		if (i + 1 < n && s[i] == f0 && s[i + 1] == f1) {
			smart_str_appendc(&out, repl);
			i += 2;
		} else {
			smart_str_appendc(&out, s[i]);
			i++;
		}
	}
	return smart_str_extract(&out);
}

/* preg_replace('~(\r\n|\n|\r)\z~', '', $s) — consumes the input string */
static zend_string *stripTrailingNewline(zend_string *s)
{
	const char *v = ZSTR_VAL(s);
	size_t n = ZSTR_LEN(s);
	size_t cut = 0;
	if (n >= 2 && v[n - 2] == '\r' && v[n - 1] == '\n') {
		cut = 2;
	} else if (n >= 1 && (v[n - 1] == '\n' || v[n - 1] == '\r')) {
		cut = 1;
	}
	if (cut == 0) {
		return s;
	}
	zend_string *r = zend_string_init(v, n - cut, 0);
	zend_string_release(s);
	return r;
}

/* String_::codePointToUtf8() — caller guarantees num <= 0x1FFFFF */
static void utf8Append(smart_str *out, zend_ulong num)
{
	if (num <= 0x7F) {
		smart_str_appendc(out, (char) num);
	} else if (num <= 0x7FF) {
		smart_str_appendc(out, (char) ((num >> 6) + 0xC0));
		smart_str_appendc(out, (char) ((num & 0x3F) + 0x80));
	} else if (num <= 0xFFFF) {
		smart_str_appendc(out, (char) ((num >> 12) + 0xE0));
		smart_str_appendc(out, (char) (((num >> 6) & 0x3F) + 0x80));
		smart_str_appendc(out, (char) ((num & 0x3F) + 0x80));
	} else {
		smart_str_appendc(out, (char) ((num >> 18) + 0xF0));
		smart_str_appendc(out, (char) (((num >> 12) & 0x3F) + 0x80));
		smart_str_appendc(out, (char) (((num >> 6) & 0x3F) + 0x80));
		smart_str_appendc(out, (char) ((num & 0x3F) + 0x80));
	}
}

/*
 * String_::parseEscapeSequences() port. Returns an owned string, or NULL after
 * fatalError() (codepoint too large — the Error propagates out of the action
 * in PHP; the engine aborts after the action returns).
 */
zend_string *ParserEngine::parseEscapeSequences(zend_string *strIn, bool hasQuote, char quote, bool parseUnicodeEscape)
{
	zend_string *str;
	if (hasQuote) {
		str = replace2Bytes(strIn, '\\', quote, quote);
	} else {
		str = zend_string_copy(strIn);
	}

	const char *s = ZSTR_VAL(str);
	size_t n = ZSTR_LEN(str);
	smart_str out = {};
	size_t i = 0;
	while (i < n) {
		char c = s[i];
		if (c != '\\' || i + 1 >= n) {
			smart_str_appendc(&out, c);
			i++;
			continue;
		}
		char d = s[i + 1];
		switch (d) {
			case '\\':
			case '$':
				smart_str_appendc(&out, d);
				i += 2;
				continue;
			case 'n':
				smart_str_appendc(&out, '\n');
				i += 2;
				continue;
			case 'r':
				smart_str_appendc(&out, '\r');
				i += 2;
				continue;
			case 't':
				smart_str_appendc(&out, '\t');
				i += 2;
				continue;
			case 'f':
				smart_str_appendc(&out, '\f');
				i += 2;
				continue;
			case 'v':
				smart_str_appendc(&out, '\v');
				i += 2;
				continue;
			case 'e':
				smart_str_appendc(&out, '\x1B');
				i += 2;
				continue;
			case 'x':
			case 'X': {
				size_t j = 0;
				while (j < 2 && i + 2 + j < n && isHexDigit(s[i + 2 + j])) {
					j++;
				}
				if (j == 0) {
					break; /* no match: literal backslash */
				}
				unsigned val = 0;
				for (size_t t = 0; t < j; t++) {
					char h = s[i + 2 + t];
					unsigned dv;
					if (h >= '0' && h <= '9') {
						dv = (unsigned) (h - '0');
					} else if (h >= 'a' && h <= 'f') {
						dv = (unsigned) (h - 'a' + 10);
					} else {
						dv = (unsigned) (h - 'A' + 10);
					}
					val = val * 16 + dv;
				}
				smart_str_appendc(&out, (char) (val & 255));
				i += 2 + j;
				continue;
			}
			case 'u': {
				if (!parseUnicodeEscape) {
					break;
				}
				if (i + 2 >= n || s[i + 2] != '{') {
					break;
				}
				size_t k = i + 3;
				size_t digits = 0;
				while (k < n && isHexDigit(s[k])) {
					k++;
					digits++;
				}
				if (digits == 0 || k >= n || s[k] != '}') {
					break;
				}
				ParsedNum cp = baseToNum(s + i + 3, digits, 16);
				/* hexdec overflow → PHP_INT_MAX → codePointToUtf8 throws; > 0x1FFFFF throws */
				if (cp.isDouble || cp.lval > 0x1FFFFF) {
					smart_str_free(&out);
					zend_string_release(str);
					fatalError("Invalid UTF-8 codepoint escape sequence: Codepoint too large", zv::Arr::empty());
					return NULL;
				}
				utf8Append(&out, (zend_ulong) cp.lval);
				i = k + 1;
				continue;
			}
			default:
				if (d >= '0' && d <= '7') {
					size_t j = 1;
					while (j < 3 && i + 1 + j < n && s[i + 1 + j] >= '0' && s[i + 1 + j] <= '7') {
						j++;
					}
					unsigned val = 0;
					for (size_t t = 0; t < j; t++) {
						val = val * 8 + (unsigned) (s[i + 1 + t] - '0');
					}
					smart_str_appendc(&out, (char) (val & 255));
					i += 1 + j;
					continue;
				}
				break;
		}
		/* no escape sequence matched at this backslash: emit it literally */
		smart_str_appendc(&out, '\\');
		i += 1;
	}
	zend_string_release(str);
	return smart_str_extract(&out);
}

/*
 * String_::parseEscapeSequences($part->value, $quote, $unicode) through the
 * real PHP static method (resolved via the class registry); writes the result
 * back into the InterpolatedStringPart. Used by the encapsed-string actions.
 */
void ParserEngine::parseEscapeSequencesInPart(zv::Ref partNode, const char *quote)
{
	NodeClassInfo *cls = resolveNodeClass("Scalar\\String_", true);
	if (cls == NULL || cls->ce == NULL) {
		return;
	}
	zend_function *fn = (zend_function *) zend_hash_str_find_ptr(
		&cls->ce->function_table, "parseescapesequences", sizeof("parseescapesequences") - 1);
	if (fn == NULL) {
		return;
	}
	zv::Ref value = prop(partNode, "value");
	if (value.raw() == NULL) {
		return;
	}
	zval args[3];
	ZVAL_COPY(&args[0], value.raw());
	ZVAL_STRING(&args[1], quote);
	ZVAL_BOOL(&args[2], phpVersionId >= 70000); /* PhpVersion::supportsUnicodeEscapes() */
	zval retval;
	ZVAL_UNDEF(&retval);
	zend_call_known_function(fn, NULL, cls->ce, &retval, 3, args, NULL);
	zval_ptr_dtor(&args[1]);
	zval_ptr_dtor(&args[0]);
	if (UNEXPECTED(EG(exception) != NULL)) {
		/* parseEscapeSequences throws PhpParser\Error for oversized \u{…}
		 * codepoints; doParse's catch (Error $e) must see it as an abort,
		 * not a raw exception escaping mid-reduce */
		zval_ptr_dtor(&retval);
		abortForPendingException();
		return;
	}
	if (Z_TYPE(retval) == IS_UNDEF) {
		return;
	}
	propWrite(partNode, "value", zv::Val::adopt(retval));
}

/*
 * ParserAbstract::stripIndentation() port. The PHP implementation is a
 * preg_replace_callback over /$start([ \t]*)($end)?/ where $start matches at
 * line starts ((?<=\n), plus \A when $newlineAtStart) and $end is an
 * empty-width group ((?=[\r\n]), plus \z when $newlineAtEnd). The callback
 * emits errors (mixed indentation / insufficient level) and strips up to
 * $indentLen chars of $indentChar from each line start. Note the regex also
 * produces an empty match at the end of a string that ends with "\n" — the
 * error checks run there too.
 *
 * Returns an owned string. Errors go through emitError with a copy of
 * attrsBorrowed (stripIndentation emits, it never throws).
 */
zend_string *ParserEngine::stripIndentation(zend_string *str, zend_long indentLen, char indentChar, bool newlineAtStart, bool newlineAtEnd, zv::Ref attrsBorrowed)
{
	if (indentLen == 0) {
		return zend_string_copy(str);
	}

	const char *s = ZSTR_VAL(str);
	size_t n = ZSTR_LEN(str);
	const char other = indentChar == ' ' ? '\t' : ' ';
	smart_str out = {};
	size_t pos = 0;
	bool atLineStart = newlineAtStart;

	for (;;) {
		if (atLineStart) {
			size_t q = pos;
			while (q < n && (s[q] == ' ' || s[q] == '\t')) {
				q++;
			}
			size_t wsLen = q - pos;
			size_t prefixLen = wsLen < (size_t) indentLen ? wsLen : (size_t) indentLen;
			bool endGroupMatched = q < n ? (s[q] == '\r' || s[q] == '\n') : newlineAtEnd;
			if (prefixLen > 0 && memchr(s + pos, other, prefixLen) != NULL) {
				emitError("Invalid indentation - tabs and spaces cannot be mixed", zv::Val::copyOf(attrsBorrowed));
			} else if (prefixLen < (size_t) indentLen && !endGroupMatched) {
				char msg[96];
				snprintf(msg, sizeof(msg),
					"Invalid body indentation level (expecting an indentation level of at least " ZEND_LONG_FMT ")",
					indentLen);
				emitError(msg, zv::Val::copyOf(attrsBorrowed));
			}
			if (q > pos + prefixLen) {
				smart_str_appendl(&out, s + pos + prefixLen, q - (pos + prefixLen));
			}
			pos = q;
		}
		if (pos >= n) {
			break;
		}
		const char *nl = (const char *) memchr(s + pos, '\n', n - pos);
		if (nl == NULL) {
			smart_str_appendl(&out, s + pos, n - pos);
			break;
		}
		size_t after = (size_t) (nl - s) + 1;
		smart_str_appendl(&out, s + pos, after - pos);
		pos = after;
		atLineStart = true;
	}

	return smart_str_extract(&out);
}

/* ===== namespace handling ===== */

enum
{
	PNH_NS_NONE = 0,
	PNH_NS_SEMICOLON = 1,
	PNH_NS_BRACE = 2,
};

/* preg_match('/\A#!.*\r?\n\z/', $stmt->value): starts with "#!", ends with
 * "\n", and the only "\n" is the final byte (`.` matches \r but not \n). */
static bool isHashbangInlineHtml(zv::Ref stmt)
{
	zv::Ref value = zv::ObjRef(stmt.raw()).prop("value", sizeof("value") - 1);
	if (value.raw() == NULL || !value.isString()) {
		return false;
	}
	const char *s = Z_STRVAL_P(value.raw());
	size_t n = Z_STRLEN_P(value.raw());
	if (n < 3 || s[0] != '#' || s[1] != '!' || s[n - 1] != '\n') {
		return false;
	}
	return memchr(s, '\n', n - 1) == NULL;
}

/* getNamespaceErrorAttributes(): attrs copy with end* narrowed to the "namespace" keyword */
zv::Arr ParserEngine::getNamespaceErrorAttributes(zv::Ref nsNode)
{
	zv::Arr attrs = getNodeAttributes(nsNode);
	attrs.separate();
	HashTable *ht = attrs.table();
	zval *v;

	v = zend_hash_str_find(ht, "startLine", sizeof("startLine") - 1);
	if (v != NULL && Z_TYPE_P(v) != IS_NULL) {
		zval c;
		ZVAL_COPY(&c, v);
		zend_hash_str_update(ht, "endLine", sizeof("endLine") - 1, &c);
	}
	v = zend_hash_str_find(ht, "startTokenPos", sizeof("startTokenPos") - 1);
	if (v != NULL && Z_TYPE_P(v) != IS_NULL) {
		zval c;
		ZVAL_COPY(&c, v);
		zend_hash_str_update(ht, "endTokenPos", sizeof("endTokenPos") - 1, &c);
	}
	v = zend_hash_str_find(ht, "startFilePos", sizeof("startFilePos") - 1);
	if (v != NULL && Z_TYPE_P(v) != IS_NULL) {
		zval c;
		ZVAL_LONG(&c, zval_get_long(v) + (zend_long) (sizeof("namespace") - 1) - 1);
		zend_hash_str_update(ht, "endFilePos", sizeof("endFilePos") - 1, &c);
	}
	return attrs;
}

int ParserEngine::getNamespacingStyle(zv::Ref stmts)
{
	int style = PNH_NS_NONE;
	bool hasNotAllowedStmts = false;
	zend_long i = -1;

	for (auto entry : zv::ArrRef(stmts.raw())) {
		zv::Ref stmt = entry.value();
		i++;
		if (stmt.isObject() && isInstanceOf(stmt, "Node\\Stmt\\Namespace_")) {
			zv::Ref sub = prop(stmt, "stmts");
			int currentStyle = (sub.raw() != NULL && Z_TYPE_P(sub.raw()) == IS_NULL) ? PNH_NS_SEMICOLON : PNH_NS_BRACE;
			if (style == PNH_NS_NONE) {
				style = currentStyle;
				if (hasNotAllowedStmts) {
					emitError("Namespace declaration statement has to be the very first statement in the script",
						getNamespaceErrorAttributes(stmt));
				}
			} else if (style != currentStyle) {
				emitError("Cannot mix bracketed namespace declarations with unbracketed namespace declarations",
					getNamespaceErrorAttributes(stmt));
				/* Treat like semicolon style for namespace normalization */
				return PNH_NS_SEMICOLON;
			}
			continue;
		}

		/* declare(), __halt_compiler() and nops can be used before a namespace declaration */
		if (stmt.isObject()
				&& (isInstanceOf(stmt, "Node\\Stmt\\Declare_")
					|| isInstanceOf(stmt, "Node\\Stmt\\HaltCompiler")
					|| isInstanceOf(stmt, "Node\\Stmt\\Nop"))) {
			continue;
		}

		/* There may be a hashbang line at the very start of the file */
		if (i == 0 && stmt.isObject()
				&& isInstanceOf(stmt, "Node\\Stmt\\InlineHTML")
				&& isHashbangInlineHtml(stmt)) {
			continue;
		}

		hasNotAllowedStmts = true;
	}

	return style;
}

/* fixupNamespaceAttributes(): extend the namespace node's end attributes to its last stmt */
void ParserEngine::fixupNamespaceAttributes(zv::Ref nsNode)
{
	zv::Ref stmts = prop(nsNode, "stmts");
	if (stmts.raw() == NULL || !stmts.isArray()) {
		return;
	}
	uint32_t count = zend_hash_num_elements(stmts.asArrayTable());
	if (count == 0) {
		return;
	}
	zv::Ref lastStmt = itemAt(stmts, count - 1);
	if (lastStmt.raw() == NULL || !lastStmt.isObject()) {
		return;
	}
	zv::Arr lastAttrs = getNodeAttributes(lastStmt);
	static const char *const endKeys[3] = {"endLine", "endFilePos", "endTokenPos"};
	for (int k = 0; k < 3; k++) {
		/* hasAttribute() is array_key_exists: null values count as present */
		zval *v = zend_hash_str_find(lastAttrs.table(), endKeys[k], strlen(endKeys[k]));
		if (v != NULL) {
			setNodeAttribute(nsNode, endKeys[k], zv::Val::copyOf(zv::Ref(v)));
		}
	}
}

zv::Val ParserEngine::handleNamespaces(zv::Ref stmts)
{
	int style = getNamespacingStyle(stmts);

	if (style == PNH_NS_NONE) {
		/* not namespaced, nothing to do */
		return zv::Val::copyOf(stmts);
	}

	if (style == PNH_NS_BRACE) {
		/* only check that there are no invalid statements between the namespaces */
		bool afterFirstNamespace = false;
		bool hasErrored = false;
		for (auto entry : zv::ArrRef(stmts.raw())) {
			zv::Ref stmt = entry.value();
			if (stmt.isObject() && isInstanceOf(stmt, "Node\\Stmt\\Namespace_")) {
				afterFirstNamespace = true;
			} else if (!(stmt.isObject() && isInstanceOf(stmt, "Node\\Stmt\\HaltCompiler"))
					&& !(stmt.isObject() && isInstanceOf(stmt, "Node\\Stmt\\Nop"))
					&& afterFirstNamespace && !hasErrored) {
				emitError("No code may exist outside of namespace {}", getNodeAttributes(stmt));
				hasErrored = true; /* Avoid one error for every statement */
			}
		}
		return zv::Val::copyOf(stmts);
	}

	/* For semicolon namespaces move the statements after a namespace declaration into ->stmts */
	zv::Arr resultStmts = zv::Arr::empty();
	zval *lastNs = NULL;
	zval *pendingNs = NULL; /* the namespace the pending stmts belong to, if any */
	zv::Arr pendingStmts;   /* UNDEF while no semicolon namespace is open */

	/* close out the last semicolon-style namespace: write collected stmts, fix attributes
	 * (the deferred equivalent of PHP's `$stmt->stmts = []` + by-ref appends) */
	auto closePendingNamespace = [&]() {
		if (pendingNs != NULL) {
			propWrite(zv::Ref(pendingNs), "stmts", std::move(pendingStmts));
			pendingNs = NULL;
		}
		fixupNamespaceAttributes(zv::Ref(lastNs));
	};

	for (auto entry : zv::ArrRef(stmts.raw())) {
		zv::Ref stmt = entry.value();
		if (stmt.isObject() && isInstanceOf(stmt, "Node\\Stmt\\Namespace_")) {
			if (lastNs != NULL) {
				closePendingNamespace();
			}
			zv::Ref sub = prop(stmt, "stmts");
			if (sub.raw() != NULL && Z_TYPE_P(sub.raw()) == IS_NULL) {
				pendingNs = stmt.raw();
				pendingStmts = zv::Arr::empty();
				resultStmts.push(stmt);
			} else {
				/* This handles the invalid case of mixed style namespaces */
				resultStmts.push(stmt);
				pendingNs = NULL;
			}
			lastNs = stmt.raw();
		} else if (stmt.isObject() && isInstanceOf(stmt, "Node\\Stmt\\HaltCompiler")) {
			/* __halt_compiler() is not moved into the namespace */
			resultStmts.push(stmt);
		} else {
			if (pendingNs != NULL) {
				pendingStmts.push(stmt);
			} else {
				resultStmts.push(stmt);
			}
		}
	}

	if (lastNs != NULL) {
		closePendingNamespace();
	}
	return zv::Val(std::move(resultStmts));
}

/* ===== handleBuiltinTypes ===== */

static const struct
{
	const char *name;
	uint32_t len;
	int minVersion;
} g_builtinTypes[] = {
	{"array", 5, 50100},
	{"callable", 8, 50400},
	{"bool", 4, 70000},
	{"int", 3, 70000},
	{"float", 5, 70000},
	{"string", 6, 70000},
	{"iterable", 8, 70100},
	{"void", 4, 70100},
	{"object", 6, 70200},
	{"null", 4, 80000},
	{"false", 5, 80000},
	{"mixed", 5, 80000},
	{"never", 5, 80100},
	{"true", 4, 80200},
};

zv::Val ParserEngine::handleBuiltinTypes(zv::Ref nameNode)
{
	/* Name::isUnqualified() is overridden to return false in FullyQualified/Relative */
	if (isInstanceOf(nameNode, "Node\\Name\\FullyQualified")
			|| isInstanceOf(nameNode, "Node\\Name\\Relative")) {
		return zv::Val::copyOf(nameNode);
	}
	zv::Ref nameProp = prop(nameNode, "name");
	if (nameProp.raw() == NULL || !nameProp.isString()
			|| memchr(Z_STRVAL_P(nameProp.raw()), '\\', Z_STRLEN_P(nameProp.raw())) != NULL) {
		return zv::Val::copyOf(nameNode);
	}

	zend_string *lower = zend_string_tolower(nameProp.asString());
	int minVersion = -1;
	for (size_t i = 0; i < sizeof(g_builtinTypes) / sizeof(g_builtinTypes[0]); i++) {
		if (ZSTR_LEN(lower) == g_builtinTypes[i].len
				&& memcmp(ZSTR_VAL(lower), g_builtinTypes[i].name, g_builtinTypes[i].len) == 0) {
			minVersion = g_builtinTypes[i].minVersion;
			break;
		}
	}
	if (minVersion < 0 || phpVersionId < (zend_long) minVersion) {
		zend_string_release(lower);
		return zv::Val::copyOf(nameNode);
	}

	return newNode("Node\\Identifier", getNodeAttributes(nameNode), zv::Val::adoptString(lower));
}

/* ===== cast kind helpers ===== */

zend_long ParserEngine::getFloatCastKind(zv::Ref castTokenText)
{
	zend_string *s = castTokenText.asString();
	if (containsLower(s, "float", 5)) {
		return 2; /* Double::KIND_FLOAT */
	}
	if (containsLower(s, "real", 4)) {
		return 3; /* Double::KIND_REAL */
	}
	return 1; /* Double::KIND_DOUBLE */
}

zend_long ParserEngine::getIntCastKind(zv::Ref castTokenText)
{
	if (containsLower(castTokenText.asString(), "integer", 7)) {
		return 2; /* Cast\Int_::KIND_INTEGER */
	}
	return 1; /* Cast\Int_::KIND_INT */
}

zend_long ParserEngine::getBoolCastKind(zv::Ref castTokenText)
{
	if (containsLower(castTokenText.asString(), "boolean", 7)) {
		return 2; /* Cast\Bool_::KIND_BOOLEAN */
	}
	return 1; /* Cast\Bool_::KIND_BOOL */
}

zend_long ParserEngine::getStringCastKind(zv::Ref castTokenText)
{
	if (containsLower(castTokenText.asString(), "binary", 6)) {
		return 2; /* Cast\String_::KIND_BINARY */
	}
	return 1; /* Cast\String_::KIND_STRING */
}

/* ===== parseLNumber (Int_::fromString + the emit-and-dummy catch) ===== */

zv::Val ParserEngine::parseLNumber(zv::Ref str, zv::Arr attributes, bool allowInvalidOctal)
{
	zend_string *orig = str.asString();

	/* Int_::fromString mutates a by-value copy of $attributes; the catch in
	 * parseLNumber constructs the dummy node with the ORIGINAL attributes
	 * (without rawValue/kind). Keep both. */
	zv::Arr workAttrs = dupArray(attributes.ref());
	workAttrs.set("rawValue", zv::Val::string(orig));

	zend_string *stripped = stripUnderscores(orig);
	const char *s = ZSTR_VAL(stripped);
	size_t n = ZSTR_LEN(stripped);

	zend_long kind;
	zend_long value;

	if (n == 0 || s[0] != '0' || n == 1) {
		kind = 10; /* Int_::KIND_DEC */
		value = strtolBase(s, n, 10); /* (int) cast: saturating for pure digit strings */
	} else if (s[1] == 'x' || s[1] == 'X') {
		kind = 16; /* Int_::KIND_HEX */
		ParsedNum num = baseToNum(s, n, 16);
		/* overflow to double would TypeError in PHP; unreachable via the lexer */
		value = num.isDouble ? zend_dval_to_lval(num.dval) : num.lval;
	} else if (s[1] == 'b' || s[1] == 'B') {
		kind = 2; /* Int_::KIND_BIN */
		ParsedNum num = baseToNum(s, n, 2);
		value = num.isDouble ? zend_dval_to_lval(num.dval) : num.lval;
	} else {
		if (!allowInvalidOctal
				&& (memchr(s, '8', n) != NULL || memchr(s, '9', n) != NULL)) {
			zend_string_release(stripped);
			/* throw new Error('Invalid numeric literal', $attributes) — caught
			 * by parseLNumber: emitError($error); return new Int_(0, $attributes) */
			emitError("Invalid numeric literal", std::move(workAttrs));
			return newNode("Node\\Scalar\\Int_", std::move(attributes), zv::Val::integer(0));
		}
		/* Strip optional explicit octal prefix. */
		size_t skip = (s[1] == 'o' || s[1] == 'O') ? 2 : 0;
		kind = 8; /* Int_::KIND_OCT */
		/* intval($str, 8): strtol semantics, cuts at the first invalid digit */
		value = strtolBase(s + skip, n - skip, 8);
	}

	zend_string_release(stripped);
	attributes.release();
	workAttrs.set("kind", zv::Val::integer(kind));
	return newNode("Node\\Scalar\\Int_", std::move(workAttrs), zv::Val::integer(value));
}

/* ===== parseNumString ===== */

zv::Val ParserEngine::parseNumString(zv::Ref str, zv::Val attributes)
{
	zend_string *zstr = str.asString();
	const char *s = ZSTR_VAL(zstr);
	size_t n = ZSTR_LEN(zstr);

	/* /^(?:0|-?[1-9][0-9]*)$/ — PCRE '$' also matches before one final "\n" */
	size_t core = (n > 0 && s[n - 1] == '\n') ? n - 1 : n;
	bool matches = false;
	if (core == 1 && s[0] == '0') {
		matches = true;
	} else {
		size_t i = 0;
		if (i < core && s[i] == '-') {
			i++;
		}
		if (i < core && s[i] >= '1' && s[i] <= '9') {
			i++;
			while (i < core && s[i] >= '0' && s[i] <= '9') {
				i++;
			}
			matches = i == core;
		}
	}

	if (matches) {
		/* $num = +$str; is_int($num) — overflow to float means String_ */
		bool neg = s[0] == '-';
		size_t i = neg ? 1 : 0;
		zend_ulong acc = 0;
		bool over = false;
		const zend_ulong limit = neg ? ((zend_ulong) ZEND_LONG_MAX + 1) : (zend_ulong) ZEND_LONG_MAX;
		for (; i < core; i++) {
			zend_ulong d = (zend_ulong) (s[i] - '0');
			if (acc > (limit - d) / 10) {
				over = true;
				break;
			}
			acc = acc * 10 + d;
		}
		if (!over) {
			zend_long value = neg ? (zend_long) (0 - acc) : (zend_long) acc;
			return newNode("Node\\Scalar\\Int_", std::move(attributes), zv::Val::integer(value));
		}
	}

	return newNode("Node\\Scalar\\String_", std::move(attributes), zv::Val::string(zstr));
}

/* ===== parseDocString ===== */

/* label from /\A[bB]?<<<[ \t]*['"]?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/ */
static bool isLabelChar(unsigned char c)
{
	return (c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z')
		|| (c >= '0' && c <= '9') || c == '_' || c >= 0x7f;
}

static zend_string *docLabel(zend_string *startToken)
{
	const char *s = ZSTR_VAL(startToken);
	size_t n = ZSTR_LEN(startToken);
	size_t i = 0;
	if (i < n && (s[i] == 'b' || s[i] == 'B')) {
		i++;
	}
	if (i + 3 <= n && memcmp(s + i, "<<<", 3) == 0) {
		i += 3;
	}
	while (i < n && (s[i] == ' ' || s[i] == '\t')) {
		i++;
	}
	if (i < n && (s[i] == '\'' || s[i] == '"')) {
		i++;
	}
	size_t start = i;
	while (i < n && isLabelChar((unsigned char) s[i])) {
		i++;
	}
	return zend_string_init(s + start, i - start, 0);
}

zv::Val ParserEngine::parseDocString(zv::Ref startTokenRef, zv::Ref contents, zv::Ref endTokenRef, zv::Arr attributes, zv::Val endAttributes, bool parseUnicodeEscape)
{
	zend_string *startToken = startTokenRef.asString();
	zend_string *endToken = endTokenRef.asString();

	zend_long kind = memchr(ZSTR_VAL(startToken), '\'', ZSTR_LEN(startToken)) == NULL
		? 3 /* String_::KIND_HEREDOC */
		: 4 /* String_::KIND_NOWDOC */;

	zend_string *label = docLabel(startToken);

	/* /\A[ \t]*\/ of the end token */
	size_t indLen = 0;
	{
		const char *e = ZSTR_VAL(endToken);
		size_t en = ZSTR_LEN(endToken);
		while (indLen < en && (e[indLen] == ' ' || e[indLen] == '\t')) {
			indLen++;
		}
	}
	zend_string *indentation = zend_string_init(ZSTR_VAL(endToken), indLen, 0);

	attributes.set("kind", zv::Val::integer(kind));
	attributes.set("docLabel", zv::Val::adoptString(label)); /* transfers the label reference */
	attributes.set("docIndentation", zv::Val::string(indentation));

	bool indentHasSpaces = memchr(ZSTR_VAL(indentation), ' ', ZSTR_LEN(indentation)) != NULL;
	bool indentHasTabs = memchr(ZSTR_VAL(indentation), '\t', ZSTR_LEN(indentation)) != NULL;
	zend_long indentLen;
	if (indentHasSpaces && indentHasTabs) {
		emitError("Invalid indentation - tabs and spaces cannot be mixed", std::move(endAttributes));
		/* Proceed processing as if this doc string is not indented */
		indentLen = 0;
	} else {
		endAttributes.release();
		indentLen = (zend_long) ZSTR_LEN(indentation);
	}
	/* computed from the pre-reset flags, exactly like the PHP source */
	char indentChar = indentHasSpaces ? ' ' : '\t';
	zend_string_release(indentation);

	if (contents.isString()) {
		zend_string *contentsStr = contents.asString();
		if (ZSTR_LEN(contentsStr) == 0) {
			attributes.set("rawValue", zv::Val::string(contentsStr));
			return newNode("Node\\Scalar\\String_", std::move(attributes), zv::Val::string("", 0));
		}

		zend_string *stripped = stripIndentation(contentsStr, indentLen, indentChar, true, true, attributes.ref());
		stripped = stripTrailingNewline(stripped);
		attributes.set("rawValue", zv::Val::string(stripped));

		zend_string *value;
		if (kind == 3 /* KIND_HEREDOC */) {
			value = parseEscapeSequences(stripped, false, 0, parseUnicodeEscape);
			zend_string_release(stripped);
			if (value == NULL) {
				return zv::Val();
			}
		} else {
			value = stripped;
		}
		return newNode("Node\\Scalar\\String_", std::move(attributes), zv::Val::adoptString(value));
	}

	/* interpolated: array of (Expr|InterpolatedStringPart) */
	zv::ArrRef parts(contents.raw());
	uint32_t numParts = parts.size();

	zv::Ref first = itemAt(contents, 0);
	if (first.raw() != NULL && first.isObject()
			&& !isInstanceOf(first, "Node\\InterpolatedStringPart")) {
		/* If there is no leading encapsed string part, pretend there is an
		 * empty one — called purely for the error side effects. */
		zv::Arr firstAttrs = getNodeAttributes(first);
		zend_string *empty = ZSTR_EMPTY_ALLOC();
		zend_string *r = stripIndentation(empty, indentLen, indentChar, true, false, firstAttrs.ref());
		zend_string_release(r);
		zend_string_release(empty);
	}

	zv::Arr newContents = zv::Arr::empty();
	zend_long i = -1;
	for (auto entry : parts) {
		zv::Ref part = entry.value();
		i++;
		if (part.isObject() && isInstanceOf(part, "Node\\InterpolatedStringPart")) {
			bool isLast = (uint32_t) (i + 1) == numParts;
			zv::Ref valueProp = prop(part, "value");
			if (valueProp.raw() == NULL || !valueProp.isString()) {
				newContents.push(part);
				continue;
			}
			zv::Arr partAttrs = getNodeAttributes(part);
			zend_string *stripped = stripIndentation(valueProp.asString(),
				indentLen, indentChar, i == 0, isLast, partAttrs.ref());
			partAttrs.release();
			if (isLast) {
				stripped = stripTrailingNewline(stripped);
			}
			propWrite(part, "value", zv::Val::string(stripped));
			setNodeAttribute(part, "rawValue", zv::Val::string(stripped));
			zend_string *parsed = parseEscapeSequences(stripped, false, 0, parseUnicodeEscape);
			zend_string_release(stripped);
			if (parsed == NULL) {
				return zv::Val();
			}
			bool isEmpty = ZSTR_LEN(parsed) == 0;
			propWrite(part, "value", zv::Val::adoptString(parsed));
			if (isEmpty) {
				continue;
			}
		}
		newContents.push(part);
	}

	return newNode("Node\\Scalar\\InterpolatedString", std::move(attributes), newContents);
}

/* ===== comment-driven Nop creation ===== */

/*
 * getCommentBeforeToken(): returns the token index of the last comment before
 * $tokenPos (walking back over drop tokens), or -1. The PHP code materializes
 * a Comment object here; its only observable use in the Nop helpers is the
 * end line/pos/tokenPos, which we compute directly from the token.
 */
int ParserEngine::getCommentBeforeToken(int tokenPos)
{
	zend_long tComment = tokenIdComment();
	zend_long tDocComment = tokenIdDocComment();
	while (--tokenPos >= 0) {
		const Token *t = &tokens[tokenPos];
		if (!isDropToken(tables, t->id)) {
			break;
		}
		if ((zend_long) t->id == tComment || (zend_long) t->id == tDocComment) {
			return tokenPos;
		}
	}
	return -1;
}

zv::Val ParserEngine::maybeCreateZeroLengthNop(int tokenPos)
{
	int ci = getCommentBeforeToken(tokenPos);
	if (ci < 0) {
		return zv::Val::null();
	}
	const Token *t = &tokens[ci];
	const char *text = ZSTR_VAL(t->text);
	size_t tlen = ZSTR_LEN(t->text);

	/* Comment::getEndLine() = $token->line + substr_count($token->text, "\n") */
	zend_long nlCount = 0;
	{
		const char *p = text;
		const char *e = text + tlen;
		while (p < e && (p = (const char *) memchr(p, '\n', (size_t) (e - p))) != NULL) {
			nlCount++;
			p++;
		}
	}
	zend_long commentEndLine = (zend_long) t->line + nlCount;
	/* Comment::getEndFilePos() = $token->getEndPos() - 1 = pos + strlen(text) - 1 */
	zend_long commentEndFilePos = (zend_long) t->pos + (zend_long) tlen - 1;
	zend_long commentEndTokenPos = (zend_long) ci;

	zv::Arr attrs = zv::Arr::create(6);
	attrs.set("startLine", zv::Val::integer(commentEndLine));
	attrs.set("endLine", zv::Val::integer(commentEndLine));
	attrs.set("startFilePos", zv::Val::integer(commentEndFilePos + 1));
	attrs.set("endFilePos", zv::Val::integer(commentEndFilePos));
	attrs.set("startTokenPos", zv::Val::integer(commentEndTokenPos + 1));
	attrs.set("endTokenPos", zv::Val::integer(commentEndTokenPos));
	return newNode("Node\\Stmt\\Nop", std::move(attrs));
}

zv::Val ParserEngine::maybeCreateNop(int tokenStartPos, int tokenEndPos)
{
	if (getCommentBeforeToken(tokenStartPos) < 0) {
		return zv::Val::null();
	}
	return newNode("Node\\Stmt\\Nop", getAttributes(tokenStartPos, tokenEndPos));
}

/* ===== handleHaltCompiler / inlineHtmlHasLeadingNewline ===== */

zv::Val ParserEngine::handleHaltCompiler()
{
	zend_string *text = NULL;
	int next = tokenPos + 1;
	if (next >= 0 && next < numTokens) {
		const Token *t = &tokens[next];
		zend_long tInlineHtml = tokenIdInlineHtml();
		if ((zend_long) t->id == tInlineHtml) {
			text = t->text;
		}
	}
	/* Prevent the lexer from returning any further tokens. */
	tokenPos = numTokens - 2;

	if (text != NULL) {
		return zv::Val::string(text);
	}
	return zv::Val::string("", 0);
}

bool ParserEngine::inlineHtmlHasLeadingNewline(int stackPos)
{
	int pos = tokenStartStack[stackPos];
	if (pos > 0) {
		zend_string *prevText = tokens[pos - 1].text;
		return memchr(ZSTR_VAL(prevText), '\n', ZSTR_LEN(prevText)) != NULL
			|| memchr(ZSTR_VAL(prevText), '\r', ZSTR_LEN(prevText)) != NULL;
	}
	return true;
}

/* ===== array destructuring fixups ===== */

zv::Val ParserEngine::fixupArrayDestructuring(zv::Ref arrayNode)
{
	createdArraysRemove(arrayNode);

	zv::Arr newItems = zv::Arr::empty();
	zv::Ref items = prop(arrayNode, "items");
	if (items.raw() != NULL && items.isArray()) {
		for (auto entry : zv::ArrRef(items.raw())) {
			zv::Ref item = entry.value();
			if (!item.isObject()) {
				newItems.push(item);
				continue;
			}
			zv::Ref value = prop(item, "value");
			if (value.raw() != NULL && value.isObject()
					&& isInstanceOf(value, "Node\\Expr\\Error")) {
				/* Error was a placeholder for an empty element, legal in destructuring */
				newItems.push(zv::Val::null());
				continue;
			}
			if (value.raw() != NULL && value.isObject()
					&& isInstanceOf(value, "Node\\Expr\\Array_")) {
				zv::Val inner = fixupArrayDestructuring(value);
				if (aborted) {
					return zv::Val();
				}
				zv::Ref key = prop(item, "key");
				if (key.raw() != NULL && Z_TYPE_P(key.raw()) == IS_NULL) {
					key = zv::Ref(NULL);
				}
				zv::Ref byRef = prop(item, "byRef");
				/* new ArrayItem($fixedUp, $item->key, $item->byRef, $item->getAttributes()) */
				zv::Val newItem = newNode("Node\\ArrayItem", getNodeAttributes(item),
					inner, key.raw() != NULL ? Borrowed(key) : Borrowed(nullptr), byRef, zv::Val::boolean(false));
				if (aborted) {
					return zv::Val();
				}
				newItems.push(std::move(newItem));
				continue;
			}
			newItems.push(item);
		}
	}

	/* ['kind' => Expr\List_::KIND_ARRAY] + $node->getAttributes():
	 * left keys first, right-side keys appended unless already present */
	zv::Arr attrs = zv::Arr::create(6);
	attrs.set("kind", zv::Val::integer(2 /* Expr\List_::KIND_ARRAY */));
	{
		zv::Arr nodeAttrs = getNodeAttributes(arrayNode);
		zend_ulong idx;
		zend_string *k;
		zval *v;
		ZEND_HASH_FOREACH_KEY_VAL(nodeAttrs.table(), idx, k, v) {
			if (k != NULL) {
				if (!zend_hash_exists(attrs.table(), k)) {
					Z_TRY_ADDREF_P(v);
					zend_hash_add_new(attrs.table(), k, v);
				}
			} else {
				if (!zend_hash_index_exists(attrs.table(), idx)) {
					Z_TRY_ADDREF_P(v);
					zend_hash_index_add_new(attrs.table(), idx, v);
				}
			}
		} ZEND_HASH_FOREACH_END();
	}

	return newNode("Node\\Expr\\List_", std::move(attrs), newItems);
}

void ParserEngine::postprocessList(zv::Ref listNode)
{
	zv::Ref items = prop(listNode, "items");
	if (items.raw() == NULL || !items.isArray()) {
		return;
	}

	bool any = false;
	for (auto entry : zv::ArrRef(items.raw())) {
		zv::Ref item = entry.value();
		if (!item.isObject()) {
			continue;
		}
		zv::Ref value = prop(item, "value");
		if (value.raw() != NULL && value.isObject()
				&& isInstanceOf(value, "Node\\Expr\\Error")) {
			any = true;
			break;
		}
	}
	if (!any) {
		return;
	}

	/* $node->items[$i] = null for the Error placeholders */
	zv::Arr newItems = dupArray(items);
	zend_ulong idx;
	zval *it;
	ZEND_HASH_FOREACH_NUM_KEY_VAL(newItems.table(), idx, it) {
		if (Z_TYPE_P(it) != IS_OBJECT) {
			continue;
		}
		zv::Ref value = prop(zv::Ref(it), "value");
		if (value.raw() != NULL && value.isObject()
				&& isInstanceOf(value, "Node\\Expr\\Error")) {
			zval nz;
			ZVAL_NULL(&nz);
			zend_hash_index_update(newItems.table(), idx, &nz);
		}
	} ZEND_HASH_FOREACH_END();
	propWrite(listNode, "items", std::move(newItems));
}

/* ===== fixupAlternativeElse ===== */

void ParserEngine::fixupAlternativeElse(zv::Ref node)
{
	/* Make sure a trailing nop statement carrying comments is part of the node. */
	zv::Ref stmts = prop(node, "stmts");
	if (stmts.raw() == NULL || !stmts.isArray()) {
		return;
	}
	uint32_t numStmts = zend_hash_num_elements(stmts.asArrayTable());
	if (numStmts == 0) {
		return;
	}
	zv::Ref last = itemAt(stmts, numStmts - 1);
	if (last.raw() == NULL || !last.isObject()
			|| !isInstanceOf(last, "Node\\Stmt\\Nop")) {
		return;
	}
	zv::Arr nopAttrs = getNodeAttributes(last);
	static const char *const endKeys[3] = {"endLine", "endFilePos", "endTokenPos"};
	for (int k = 0; k < 3; k++) {
		/* the PHP code uses isset() here, so null values do NOT count */
		zval *v = zend_hash_str_find(nopAttrs.table(), endKeys[k], strlen(endKeys[k]));
		if (v != NULL && Z_TYPE_P(v) != IS_NULL) {
			setNodeAttribute(node, endKeys[k], zv::Val::copyOf(zv::Ref(v)));
		}
	}
}

/* ===== modifier verification (Modifiers::verify*) ===== */

static const char *modifierToString(zend_long modifier)
{
	switch (modifier) {
		case PN_MOD_PUBLIC:
			return "public";
		case PN_MOD_PROTECTED:
			return "protected";
		case PN_MOD_PRIVATE:
			return "private";
		case PN_MOD_STATIC:
			return "static";
		case PN_MOD_ABSTRACT:
			return "abstract";
		case PN_MOD_FINAL:
			return "final";
		case PN_MOD_READONLY:
			return "readonly";
		case PN_MOD_PUBLIC_SET:
			return "public(set)";
		case PN_MOD_PROTECTED_SET:
			return "protected(set)";
		case PN_MOD_PRIVATE_SET:
			return "private(set)";
	}
	return "unknown"; /* Modifiers::toString would throw; unreachable from the grammar */
}

void ParserEngine::checkClassModifier(zend_long a, zend_long b, int modifierStackPos)
{
	/* Modifiers::verifyClassModifier throws at the FIRST violation only */
	if ((a & b) != 0) {
		zend_string *msg = zend_strpprintf(0, "Multiple %s modifiers are not allowed", modifierToString(b));
		emitError(msg, getAttributesAt(modifierStackPos));
		zend_string_release(msg);
		return;
	}
	if ((a & 48) != 0 && (b & 48) != 0) {
		emitError("Cannot use the final modifier on an abstract class", getAttributesAt(modifierStackPos));
	}
}

void ParserEngine::verifyModifier(zend_long a, zend_long b, int modifierStackPos)
{
	const zend_long visibilityMask = PN_MOD_PUBLIC | PN_MOD_PROTECTED | PN_MOD_PRIVATE;
	const zend_long visibilitySetMask = PN_MOD_PUBLIC_SET | PN_MOD_PROTECTED_SET | PN_MOD_PRIVATE_SET;

	if (((a & visibilityMask) != 0 && (b & visibilityMask) != 0)
			|| ((a & visibilitySetMask) != 0 && (b & visibilitySetMask) != 0)) {
		emitError("Multiple access type modifiers are not allowed", getAttributesAt(modifierStackPos));
		return;
	}
	if ((a & b) != 0) {
		zend_string *msg = zend_strpprintf(0, "Multiple %s modifiers are not allowed", modifierToString(b));
		emitError(msg, getAttributesAt(modifierStackPos));
		zend_string_release(msg);
		return;
	}
	if ((a & 48) != 0 && (b & 48) != 0) {
		emitError("Cannot use the final modifier on an abstract class member", getAttributesAt(modifierStackPos));
	}
}

void ParserEngine::checkModifier(zend_long a, zend_long b, int modifierStackPos)
{
	verifyModifier(a, b, modifierStackPos);
}

void ParserEngine::checkPropertyHookModifiers(zend_long a, zend_long b, int modifierPos)
{
	verifyModifier(a, b, modifierPos);
	/* checked independently — both errors can be emitted from one call */
	if (b != PN_MOD_FINAL) {
		zend_string *msg = zend_strpprintf(0, "Cannot use the %s modifier on a property hook", modifierToString(b));
		emitError(msg, getAttributesAt(modifierPos));
		zend_string_release(msg);
	}
}

/* ===== structural check* helpers ===== */

void ParserEngine::checkParam(zv::Ref param)
{
	zv::Ref variadic = prop(param, "variadic");
	zv::Ref def = prop(param, "default");
	if (variadic.raw() != NULL && variadic.isTrue()
			&& def.raw() != NULL && def.isObject()) {
		emitError("Variadic parameter cannot have a default value", getNodeAttributes(def));
	}

	zv::Ref type = prop(param, "type");
	if (type.raw() != NULL && type.isObject()
			&& isInstanceOf(type, "Node\\Identifier")) {
		zv::Ref tn = prop(type, "name");
		if (tn.raw() != NULL && tn.stringEquals("void")) {
			emitError("void cannot be used as a parameter type", getNodeAttributes(type));
		}
	}
}

void ParserEngine::checkTryCatch(zv::Ref node)
{
	zv::Ref catches = prop(node, "catches");
	zv::Ref finally = prop(node, "finally");
	bool noCatches = catches.raw() == NULL || !catches.isArray()
		|| zend_hash_num_elements(catches.asArrayTable()) == 0;
	if (noCatches && finally.raw() != NULL && Z_TYPE_P(finally.raw()) == IS_NULL) {
		emitError("Cannot use try without catch or finally", getNodeAttributes(node));
	}
}

void ParserEngine::checkNamespace(zv::Ref node)
{
	zv::Ref stmts = prop(node, "stmts");
	if (stmts.raw() == NULL || !stmts.isArray()) {
		return;
	}
	for (auto entry : zv::ArrRef(stmts.raw())) {
		zv::Ref stmt = entry.value();
		if (stmt.isObject() && isInstanceOf(stmt, "Node\\Stmt\\Namespace_")) {
			emitError("Namespace declarations cannot be nested", getNodeAttributes(stmt));
		}
	}
}

void ParserEngine::checkClassName(zv::Ref name, int namePos)
{
	if (name.raw() == NULL || !name.isObject()) {
		return;
	}
	zend_string *n = nodeNameString(name);
	if (n == NULL || !isSpecialClassName(n)) {
		return;
	}
	zend_string *msg = zend_strpprintf(0, "Cannot use '%s' as class name as it is reserved", ZSTR_VAL(n));
	emitError(msg, getAttributesAt(namePos));
	zend_string_release(msg);
}

void ParserEngine::checkImplementedInterfaces(zv::Ref interfaces)
{
	if (interfaces.raw() == NULL || !interfaces.isArray()) {
		return;
	}
	for (auto entry : zv::ArrRef(interfaces.raw())) {
		zv::Ref iface = entry.value();
		if (!iface.isObject()) {
			continue;
		}
		zend_string *n = nodeNameString(iface);
		if (n == NULL || !isSpecialClassName(n)) {
			continue;
		}
		zend_string *msg = zend_strpprintf(0, "Cannot use '%s' as interface name as it is reserved", ZSTR_VAL(n));
		emitError(msg, getNodeAttributes(iface));
		zend_string_release(msg);
	}
}

void ParserEngine::checkClass(zv::Ref node, int namePos)
{
	checkClassName(prop(node, "name"), namePos);

	zv::Ref extends = prop(node, "extends");
	if (extends.raw() != NULL && extends.isObject()) {
		zend_string *n = nodeNameString(extends);
		if (n != NULL && isSpecialClassName(n)) {
			zend_string *msg = zend_strpprintf(0, "Cannot use '%s' as class name as it is reserved", ZSTR_VAL(n));
			emitError(msg, getNodeAttributes(extends));
			zend_string_release(msg);
		}
	}

	checkImplementedInterfaces(prop(node, "implements"));
}

void ParserEngine::checkInterface(zv::Ref node, int namePos)
{
	checkClassName(prop(node, "name"), namePos);
	checkImplementedInterfaces(prop(node, "extends"));
}

void ParserEngine::checkEnum(zv::Ref node, int namePos)
{
	checkClassName(prop(node, "name"), namePos);
	checkImplementedInterfaces(prop(node, "implements"));
}

void ParserEngine::checkClassMethod(zv::Ref node, int modifierPos)
{
	zv::Ref flags = prop(node, "flags");
	zend_long f = flags.raw() != NULL ? flags.toLong() : 0;
	zv::Ref name = prop(node, "name");
	zend_string *n = (name.raw() != NULL && name.isObject()) ? nodeNameString(name) : NULL;
	if (n == NULL) {
		return;
	}

	if ((f & PN_MOD_STATIC) != 0) {
		const char *fmt = NULL;
		if (iequals(n, "__construct", sizeof("__construct") - 1)) {
			fmt = "Constructor %s() cannot be static";
		} else if (iequals(n, "__destruct", sizeof("__destruct") - 1)) {
			fmt = "Destructor %s() cannot be static";
		} else if (iequals(n, "__clone", sizeof("__clone") - 1)) {
			fmt = "Clone method %s() cannot be static";
		}
		if (fmt != NULL) {
			zend_string *msg = zend_strpprintf(0, fmt, ZSTR_VAL(n));
			emitError(msg, getAttributesAt(modifierPos));
			zend_string_release(msg);
		}
	}

	if ((f & PN_MOD_READONLY) != 0) {
		zend_string *msg = zend_strpprintf(0, "Method %s() cannot be readonly", ZSTR_VAL(n));
		emitError(msg, getAttributesAt(modifierPos));
		zend_string_release(msg);
	}
}

void ParserEngine::checkClassConst(zv::Ref node, int modifierPos)
{
	zv::Ref flags = prop(node, "flags");
	zend_long f = flags.raw() != NULL ? flags.toLong() : 0;
	static const zend_long modifiers[3] = {PN_MOD_STATIC, PN_MOD_ABSTRACT, PN_MOD_READONLY};
	for (int i = 0; i < 3; i++) {
		if ((f & modifiers[i]) != 0) {
			zend_string *msg = zend_strpprintf(0, "Cannot use '%s' as constant modifier",
				modifierToString(modifiers[i]));
			emitError(msg, getAttributesAt(modifierPos));
			zend_string_release(msg);
		}
	}
}

void ParserEngine::checkUseUse(zv::Ref node, int namePos)
{
	zv::Ref alias = prop(node, "alias");
	if (alias.raw() == NULL || !alias.isObject()) {
		return;
	}
	zend_string *aliasStr = nodeNameString(alias);
	if (aliasStr == NULL || !isSpecialClassName(aliasStr)) {
		return;
	}
	zv::Ref name = prop(node, "name");
	zend_string *nameStr = (name.raw() != NULL && name.isObject()) ? nodeNameString(name) : NULL;
	/* sprintf('Cannot use %s as %s because \'%2$s\' is a special class name', ...) */
	zend_string *msg = zend_strpprintf(0, "Cannot use %s as %s because '%s' is a special class name",
		nameStr != NULL ? ZSTR_VAL(nameStr) : "", ZSTR_VAL(aliasStr), ZSTR_VAL(aliasStr));
	emitError(msg, getAttributesAt(namePos));
	zend_string_release(msg);
}

void ParserEngine::checkPropertyHooksForMultiProperty(zv::Ref property, int hookPos)
{
	zv::Ref props = prop(property, "props");
	if (props.raw() != NULL && props.isArray()
			&& zend_hash_num_elements(props.asArrayTable()) > 1) {
		emitError("Cannot use hooks when declaring multiple properties", getAttributesAt(hookPos));
	}
}

void ParserEngine::checkEmptyPropertyHookList(zv::Ref hooks, int hookPos)
{
	if (hooks.raw() == NULL || !hooks.isArray()
			|| zend_hash_num_elements(hooks.asArrayTable()) == 0) {
		emitError("Property hook list cannot be empty", getAttributesAt(hookPos));
	}
}

void ParserEngine::checkPropertyHook(zv::Ref hook, int paramListPos, bool hasParamList)
{
	zv::Ref name = prop(hook, "name");
	if (name.raw() == NULL || !name.isObject()) {
		return;
	}
	zend_string *n = nodeNameString(name);
	if (n == NULL) {
		return;
	}
	bool isGet = iequals(n, "get", 3);
	bool isSet = iequals(n, "set", 3);
	if (!isGet && !isSet) {
		zend_string *msg = zend_strpprintf(0, "Unknown hook \"%s\", expected \"get\" or \"set\"", ZSTR_VAL(n));
		emitError(msg, getNodeAttributes(name));
		zend_string_release(msg);
	}
	if (isGet && hasParamList) {
		emitError("get hook must not have a parameter list", getAttributesAt(paramListPos));
	}
}

void ParserEngine::checkConstantAttributes(zv::Ref node)
{
	zv::Ref attrGroups = prop(node, "attrGroups");
	zv::Ref consts = prop(node, "consts");
	bool hasAttrGroups = attrGroups.raw() != NULL && attrGroups.isArray()
		&& zend_hash_num_elements(attrGroups.asArrayTable()) != 0;
	bool multipleConsts = consts.raw() != NULL && consts.isArray()
		&& zend_hash_num_elements(consts.asArrayTable()) > 1;
	if (hasAttrGroups && multipleConsts) {
		emitError("Cannot use attributes on multiple constants at once", getNodeAttributes(node));
	}
}

void ParserEngine::checkPipeOperatorParentheses(zv::Ref expr)
{
	if (!expr.isObject()) {
		return;
	}
	if (!isInstanceOf(expr, "Node\\Expr\\ArrowFunction")) {
		return;
	}
	if (zend_hash_index_exists(&parenthesizedArrowFns, (zend_ulong) Z_OBJ_HANDLE_P(expr.raw()))) {
		return;
	}
	emitError("Arrow functions on the right hand side of |> must be parenthesized", getNodeAttributes(expr));
}

void ParserEngine::addPropertyNameToHooks(zv::Ref node)
{
	zv::Val nameVal;

	if (isInstanceOf(node, "Node\\Stmt\\Property")) {
		/* $node->props[0]->name->toString() */
		zv::Ref props = prop(node, "props");
		zv::Ref first = (props.raw() != NULL && props.isArray())
			? itemAt(props, 0)
			: zv::Ref(NULL);
		if (first.raw() != NULL && first.isObject()) {
			zv::Ref ident = prop(first, "name");
			if (ident.raw() != NULL && ident.isObject()) {
				zv::Ref n = prop(ident, "name");
				if (n.raw() != NULL) {
					nameVal = zv::Val::copyOf(n);
				}
			}
		}
	} else {
		/* Param: $node->var->name */
		zv::Ref var = prop(node, "var");
		if (var.raw() != NULL && var.isObject()) {
			zv::Ref n = prop(var, "name");
			if (n.raw() != NULL) {
				nameVal = zv::Val::copyOf(n);
			}
		}
	}
	if (nameVal.isUndef()) {
		return;
	}

	zv::Ref hooks = prop(node, "hooks");
	if (hooks.raw() != NULL && hooks.isArray()) {
		for (auto entry : zv::ArrRef(hooks.raw())) {
			zv::Ref hook = entry.value();
			if (!hook.isObject()) {
				continue;
			}
			setNodeAttribute(hook, "propertyName", zv::Val::copyOf(nameVal.ref()));
		}
	}
}

/* ===== createExitExpr ===== */

zv::Val ParserEngine::createExitExpr(zv::Ref nameStr, int namePos, zv::Ref args, zv::Arr attributes)
{
	HashTable *argsHt = (args.raw() != NULL && args.isArray()) ? args.asArrayTable() : NULL;
	uint32_t numArgs = argsHt != NULL ? zend_hash_num_elements(argsHt) : 0;

	/* isSimpleExit() */
	bool simple = false;
	zv::Ref firstArg(NULL);
	if (numArgs == 0) {
		simple = true;
	} else if (numArgs == 1) {
		firstArg = itemAt(args, 0);
		if (firstArg.raw() != NULL && firstArg.isObject()
				&& isInstanceOf(firstArg, "Node\\Arg")) {
			zv::Ref argName = prop(firstArg, "name");
			zv::Ref byRef = prop(firstArg, "byRef");
			zv::Ref unpack = prop(firstArg, "unpack");
			simple = argName.raw() != NULL && Z_TYPE_P(argName.raw()) == IS_NULL
				&& byRef.raw() != NULL && byRef.isFalse()
				&& unpack.raw() != NULL && unpack.isFalse();
		}
	}

	if (simple) {
		/* Create Exit node for backwards compatibility. */
		zend_long kind = iequals(nameStr.asString(), "exit", 4)
			? 1 /* Exit_::KIND_EXIT */
			: 2 /* Exit_::KIND_DIE */;
		attributes.set("kind", zv::Val::integer(kind));
		zv::Ref exprVal(NULL);
		if (numArgs == 1 && firstArg.raw() != NULL) {
			exprVal = prop(firstArg, "value");
			if (exprVal.raw() != NULL && Z_TYPE_P(exprVal.raw()) == IS_NULL) {
				exprVal = zv::Ref(NULL);
			}
		}
		return newNode("Node\\Expr\\Exit_", std::move(attributes),
			exprVal.raw() != NULL ? Borrowed(exprVal) : Borrowed(nullptr));
	}

	zv::Val nameNode = newName(nameStr, getAttributesAt(namePos));
	if (aborted) {
		return zv::Val();
	}
	return newNode("Node\\Expr\\FuncCall", std::move(attributes), nameNode, args);
}

/* ===== Name builders (Name::prepareName) ===== */

/* Returns an owned prepared name string or NULL after fatalError. */
zend_string *ParserEngine::prepareName(zv::Ref nameVal)
{
	if (nameVal.isString()) {
		if (ZSTR_LEN(nameVal.asString()) == 0) {
			/* InvalidArgumentException in PHP; mapped onto the abort channel */
			fatalError("Name cannot be empty", zv::Arr::empty());
			return NULL;
		}
		return zend_string_copy(nameVal.asString());
	}
	if (nameVal.isArray()) {
		if (zend_hash_num_elements(nameVal.asArrayTable()) == 0) {
			fatalError("Name cannot be empty", zv::Arr::empty());
			return NULL;
		}
		smart_str out = {};
		bool isFirst = true;
		for (auto entry : zv::ArrRef(nameVal.raw())) {
			if (!isFirst) {
				smart_str_appendc(&out, '\\');
			}
			zend_string *ps = zval_get_string(entry.value().raw());
			smart_str_append(&out, ps);
			zend_string_release(ps);
			isFirst = false;
		}
		return smart_str_extract(&out);
	}
	if (nameVal.isObject() && isInstanceOf(nameVal, "Node\\Name")) {
		zv::Ref inner = prop(nameVal, "name");
		if (inner.raw() != NULL && inner.isString()) {
			return zend_string_copy(inner.asString());
		}
	}
	fatalError("Expected string, array of parts or Name instance", zv::Arr::empty());
	return NULL;
}

zv::Val ParserEngine::newNameVariant(const char *alias, zv::Ref strOrParts, zv::Val attributes)
{
	zend_string *prepared = prepareName(strOrParts);
	if (prepared == NULL) {
		return zv::Val();
	}
	/* Name's final ctor only runs prepareName + assigns; constructing with the
	 * already-prepared string through the prop-slot path is byte-equivalent. */
	return newNode(alias, std::move(attributes), zv::Val::adoptString(prepared));
}

zv::Val ParserEngine::newName(zv::Ref strOrParts, zv::Val attributes)
{
	return newNameVariant("Node\\Name", strOrParts, std::move(attributes));
}

/* ===== String_::fromString ===== */

zv::Val ParserEngine::stringFromString(zv::Ref raw, zv::Arr attributes, bool parseUnicodeEscape)
{
	zend_string *rawStr = raw.asString();
	const char *s = ZSTR_VAL(rawStr);
	size_t n = ZSTR_LEN(rawStr);

	bool singleQuoted = (n > 0 && s[0] == '\'')
		|| (n > 1 && s[1] == '\'' && (s[0] == 'b' || s[0] == 'B'));
	attributes.set("kind", zv::Val::integer(singleQuoted ? 1 /* KIND_SINGLE_QUOTED */ : 2 /* KIND_DOUBLE_QUOTED */));
	attributes.set("rawValue", zv::Val::string(rawStr));

	/* String_::parse() */
	size_t bLength = (n > 0 && (s[0] == 'b' || s[0] == 'B')) ? 1 : 0;
	size_t innerLen = n >= bLength + 2 ? n - bLength - 2 : 0;
	zend_string *inner = zend_string_init(s + bLength + 1, innerLen, 0);

	zend_string *value;
	if (bLength < n && s[bLength] == '\'') {
		/* str_replace(['\\\\', '\\\''], ['\\', '\''], ...): two sequential passes */
		zend_string *pass1 = replace2Bytes(inner, '\\', '\\', '\\');
		value = replace2Bytes(pass1, '\\', '\'', '\'');
		zend_string_release(pass1);
	} else {
		value = parseEscapeSequences(inner, true, '"', parseUnicodeEscape);
	}
	zend_string_release(inner);
	if (value == NULL) {
		return zv::Val();
	}

	return newNode("Node\\Scalar\\String_", std::move(attributes), zv::Val::adoptString(value));
}

/* ===== Float_::fromString ===== */

zv::Val ParserEngine::floatFromString(zv::Ref raw, zv::Arr attributes)
{
	zend_string *orig = raw.asString();
	attributes.set("rawValue", zv::Val::string(orig));

	zend_string *stripped = stripUnderscores(orig);
	const char *s = ZSTR_VAL(stripped);
	size_t n = ZSTR_LEN(stripped);

	double value = 0.0;
	bool handled = false;
	if (n > 0 && s[0] == '0') {
		if (n > 1 && (s[1] == 'x' || s[1] == 'X')) {
			ParsedNum num = baseToNum(s, n, 16);
			value = num.isDouble ? num.dval : (double) num.lval;
			handled = true;
		} else if (n > 1 && (s[1] == 'b' || s[1] == 'B')) {
			ParsedNum num = baseToNum(s, n, 2);
			value = num.isDouble ? num.dval : (double) num.lval;
			handled = true;
		} else if (memchr(s, '.', n) == NULL && memchr(s, 'e', n) == NULL
				&& memchr(s, 'E', n) == NULL) {
			/* octdec(substr($str, 0, strcspn($str, '89'))) */
			size_t cut = 0;
			while (cut < n && s[cut] != '8' && s[cut] != '9') {
				cut++;
			}
			ParsedNum num = baseToNum(s, cut, 8);
			value = num.isDouble ? num.dval : (double) num.lval;
			handled = true;
		}
	}
	if (!handled) {
		/* (float) cast on the DNUMBER text */
		value = zend_strtod(s, NULL);
	}
	zend_string_release(stripped);

	zval v;
	ZVAL_DOUBLE(&v, value);
	return newNode("Node\\Scalar\\Float_", std::move(attributes), zv::Val::adopt(v));
}

} // namespace phpstanturbo
