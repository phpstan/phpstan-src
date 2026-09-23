/*
 * Shared scanning primitives behind the optimized source locators' directory
 * symbol scan — the pipeline PHPStan runs per file:
 *
 *   php_strip_whitespace()  ->  clean()  ->  symbol regex
 *
 * All three stages live here natively so SymbolFinderInFiles can run them
 * back to back over one reusable pair of buffers, while PhpFileCleaner.cpp
 * still exposes the middle stage on its own as the shadow of the PHP twin.
 *
 * The stages stay separate passes on purpose. php_strip_whitespace() deletes
 * comments without leaving a separator, so an identifier split by a comment
 * really does reach the cleaner joined back together — fusing comment removal
 * into the cleaner would lose that join, and with it the parity the port is
 * judged on.
 */

#ifndef PHPSTANTURBO_SYMBOLSCAN_H
#define PHPSTANTURBO_SYMBOLSCAN_H

#include "support.h"

#include <string>
#include <vector>

/* The twin's $rejectChars: '{}?"\'</d' plus the first byte of each type
 * keyword. Built once at static-init time so the scan loop is a table read. */
static const struct RejectTable {
	bool bytes[256];

	RejectTable() : bytes()
	{
		for (const char *p = "{}?\"'</dcite"; *p != '\0'; p++) {
			bytes[(unsigned char) *p] = true;
		}
	}
} pt_reject_table;

namespace phpstanturbo {

/* PCRE's \w in a non-UTF-8 pattern: bytes >= 0x80 are not word bytes */
inline bool isWordByte(unsigned char c)
{
	return (c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') || (c >= '0' && c <= '9') || c == '_';
}

/* PCRE's \s */
inline bool isSpaceByte(unsigned char c)
{
	return c == ' ' || c == '\t' || c == '\n' || c == '\r' || c == '\f' || c == '\v';
}

/* [a-zA-Z_\x7f-\xff] */
inline bool isNameStart(unsigned char c)
{
	return (c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') || c == '_' || c >= 0x7f;
}

/* [a-zA-Z0-9_\x7f-\xff\-] — the dash is in the class, odd as it looks */
inline bool isNameByte(unsigned char c)
{
	return isNameStart(c) || (c >= '0' && c <= '9') || c == '-';
}

/* [a-zA-Z_\x80-\xff] / [a-zA-Z0-9_\x80-\xff] — heredoc labels start at
 * \x80, not \x7f, in the twin's patterns */
inline bool isLabelStart(unsigned char c)
{
	return (c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') || c == '_' || c >= 0x80;
}

inline bool isLabelByte(unsigned char c)
{
	return isLabelStart(c) || (c >= '0' && c <= '9');
}

inline bool equalsIgnoreCase(const char *a, const char *lowercaseB, size_t n)
{
	for (size_t i = 0; i < n; i++) {
		char c = a[i];
		if (c >= 'A' && c <= 'Z') {
			c = (char) (c - 'A' + 'a');
		}
		if (c != lowercaseB[i]) return false;
	}
	return true;
}

/* the define()/namespace name class: like isNameByte but without the dash */
inline bool isDefineNameByte(unsigned char c)
{
	return isNameStart(c) || (c >= '0' && c <= '9');
}

/* whether a bare `<?` opens PHP — the same flag the real lexer consults */
inline bool shortOpenTagEnabled()
{
	return CG(short_tags) != 0;
}

/* whether the lexer skips a leading "#!" line — the CLI sets it for the
 * whole request, so php_strip_whitespace() skips it too */
inline bool skipShebangEnabled()
{
	return CG(skip_shebang);
}

/* first bytes of the keywords the symbol matcher can start a branch on */
static const struct KeywordStartTable {
	bool bytes[256];

	KeywordStartTable() : bytes()
	{
		for (const char *p = "citefndCITEFND"; *p != '\0'; p++) {
			bytes[(unsigned char) *p] = true;
		}
	}
} pt_keyword_start_table;



/* Mirrors PHPStan\...\PhpFileCleaner. State is per-call, so unlike the PHP
 * twin (which keeps $contents/$len/$index as properties) nothing lives on the
 * PHP object. */
class PhpFileCleaner
{
public:
	PhpFileCleaner(const char *contents, size_t len) : contents(contents), len(len), index(0) {}

	void clean(zend_long maxMatches, std::string &out);

private:
	const char *contents;
	size_t len;
	size_t index;

	/* `.\b(?<![\$:>])` anchored one byte before `at`: the byte before the
	 * keyword must exist, must not be a word byte (that is the \b, since the
	 * keyword starts with one) and must not be $, : or >. */
	bool prevByteOpensKeyword(size_t at) const
	{
		if (at == 0 || at > len) return false;
		unsigned char prev = (unsigned char) contents[at - 1];
		return !isWordByte(prev) && prev != '$' && prev != ':' && prev != '>';
	}

	bool peek(char c) const { return index + 1 < len && contents[index + 1] == c; }

	/* `\s++[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\-]*+` starting at `from`;
	 * on success `end` receives the offset just past the name. */
	bool matchSpacesAndName(size_t from, size_t *end) const
	{
		size_t p = from;
		while (p < len && isSpaceByte((unsigned char) contents[p])) {
			p++;
		}
		if (p == from || p >= len || !isNameStart((unsigned char) contents[p])) return false;
		p++;
		while (p < len && isNameByte((unsigned char) contents[p])) {
			p++;
		}
		*end = p;
		return true;
	}

	void skipToPhp();
	void skipString(char delimiter);
	void consumeString(char delimiter, std::string &clean);
	void skipComment();
	void skipToNewline();
	bool matchHeredocStart(size_t *labelStart, size_t *labelLen, size_t *end) const;
	void skipHeredoc(const char *label, size_t labelLen);
};

inline void PhpFileCleaner::skipToPhp()
{
	while (index < len) {
		if (contents[index] == '<' && peek('?')) {
			index += 2;
			break;
		}

		index += 1;
	}
}

/* The twin's consumeString(): copies the string body verbatim, keeping
 * backslash escapes, up to and including the closing delimiter. */
inline void PhpFileCleaner::consumeString(char delimiter, std::string &clean)
{
	index += 1;
	while (index < len) {
		if (contents[index] == '\\' && (peek('\\') || peek(delimiter))) {
			clean.append(contents + index, 2);
			index += 2;
			continue;
		}

		if (contents[index] == delimiter) {
			clean.push_back(delimiter);
			index += 1;
			break;
		}

		clean.push_back(contents[index]);
		index += 1;
	}
}

inline void PhpFileCleaner::skipString(char delimiter)
{
	index += 1;
	while (index < len) {
		while (index < len && contents[index] != '\\' && contents[index] != delimiter) {
			index++;
		}
		if (index >= len) break;
		if (contents[index] == '\\' && (peek('\\') || peek(delimiter))) {
			index += 2;
			continue;
		}
		if (contents[index] == delimiter) {
			index += 1;
			break;
		}
		index += 1;
	}
}

inline void PhpFileCleaner::skipComment()
{
	index += 2;
	while (index < len) {
		while (index < len && contents[index] != '*') {
			index++;
		}

		if (peek('/')) {
			index += 2;
			break;
		}

		index += 1;
	}
}

inline void PhpFileCleaner::skipToNewline()
{
	while (index < len && contents[index] != '\r' && contents[index] != '\n') {
		index++;
	}
}

/* `{<<<[ \t]*+(['"]?)([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*+)\1(?:\r\n|\n|\r)}A` */
inline bool PhpFileCleaner::matchHeredocStart(size_t *labelStart, size_t *labelLen, size_t *end) const
{
	size_t p = index;
	if (p + 3 > len || contents[p] != '<' || contents[p + 1] != '<' || contents[p + 2] != '<') return false;
	p += 3;
	while (p < len && (contents[p] == ' ' || contents[p] == '\t')) {
		p++;
	}
	char quote = '\0';
	if (p < len && (contents[p] == '\'' || contents[p] == '"')) {
		quote = contents[p];
		p++;
	}
	if (p >= len || !isLabelStart((unsigned char) contents[p])) return false;
	size_t start = p;
	p++;
	while (p < len && isLabelByte((unsigned char) contents[p])) {
		p++;
	}
	*labelStart = start;
	*labelLen = p - start;
	if (quote != '\0') {
		if (p >= len || contents[p] != quote) return false;
		p++;
	}
	if (p < len && contents[p] == '\r') {
		p += (p + 1 < len && contents[p + 1] == '\n') ? 2 : 1;
	} else if (p < len && contents[p] == '\n') {
		p += 1;
	} else {
		return false;
	}
	*end = p;
	return true;
}

inline void PhpFileCleaner::skipHeredoc(const char *label, size_t labelLen)
{
	char firstLabelByte = label[0];

	while (index < len) {
		/* the label may be preceded by indentation */
		char c = contents[index];
		if (c == '\t' || c == ' ') {
			index += 1;
			continue;
		}
		if (c == firstLabelByte
			&& index + labelLen <= len
			&& memcmp(contents + index, label, labelLen) == 0
			&& (index + labelLen >= len || !isLabelByte((unsigned char) contents[index + labelLen]))
		) {
			index += labelLen;
			return;
		}

		skipToNewline();
		while (index < len && (contents[index] == '\r' || contents[index] == '\n')) {
			index++;
		}
	}
}

inline void PhpFileCleaner::clean(zend_long maxMatches, std::string &out)
{
	/* keyed by first byte, exactly like the twin's $typeConfig */
	struct TypeConfig {
		char firstByte;
		const char *name;
		size_t length;
	};
	static const TypeConfig types[] = {
		{ 'c', "class", 5 },
		{ 'i', "interface", 9 },
		{ 't', "trait", 5 },
		{ 'e', "enum", 4 },
	};

	std::string &clean = out;
	clean.clear();
	clean.reserve(len);

	bool inType = false;
	zend_long typeLevel = 0;
	bool inDefine = false;

	while (index < len) {
		skipToPhp();
		clean.append("<?", 2);

		while (index < len) {
			char c = contents[index];

			if (c == '?' && peek('>')) {
				clean.append("?>", 2);
				index += 2;
				break; /* continue 2 */
			}

			if (c == '"' || c == '\'') {
				if (inDefine) {
					clean.push_back(c);
					consumeString(c, clean);
					inDefine = false;
				} else {
					skipString(c);
					clean.append("null", 4);
				}

				continue;
			}

			if (c == '{') {
				if (inType) {
					typeLevel++;
				}

				clean.push_back(c);
				index++;
				continue;
			}

			if (c == '}') {
				if (inType) {
					typeLevel--;

					if (typeLevel == 0) {
						inType = false;
					}
				}

				clean.push_back(c);
				index++;
				continue;
			}

			if (c == '<' && peek('<')) {
				size_t labelStart, labelLen, end;
				if (matchHeredocStart(&labelStart, &labelLen, &end)) {
					const char *label = contents + labelStart;
					index = end;
					skipHeredoc(label, labelLen);
					clean.append("null", 4);
					continue;
				}
			}

			if (c == '/') {
				if (peek('/')) {
					skipToNewline();
					continue;
				}
				if (peek('*')) {
					skipComment();
					continue;
				}
			}

			/* `~.\b(?<![\$:>])const(\s++NAME)~Ais` at index - 1 */
			if (inType && c == 'c' && prevByteOpensKeyword(index) && index + 5 <= len
				&& equalsIgnoreCase(contents + index, "const", 5)
			) {
				size_t end;
				if (matchSpacesAndName(index + 5, &end)) {
					/* invalid PHP, but it only has to stop the symbol regex
					 * from reading a class constant as a global one */
					clean.append("class_const", 11);
					clean.append(contents + index + 5, end - (index + 5));
					index = end;
					continue;
				}
			}

			/* `~.\b(?<![\$:>])define\s*+\(~Ais` at index - 1 */
			if (c == 'd' && prevByteOpensKeyword(index) && index + 6 <= len
				&& equalsIgnoreCase(contents + index, "define", 6)
			) {
				size_t p = index + 6;
				while (p < len && isSpaceByte((unsigned char) contents[p])) {
					p++;
				}
				if (p < len && contents[p] == '(') {
					/* the twin appends the whole match, which starts one byte
					 * before the keyword — that byte is already in the output,
					 * so it lands twice. Harmless for the symbol regex, and
					 * reproduced here to keep the output byte-identical. */
					clean.append(contents + index - 1, p + 1 - (index - 1));
					index = p + 1;
					inDefine = true;
					continue;
				}
			}

			for (const TypeConfig &type : types) {
				if (type.firstByte != c) continue;

				if (index + type.length <= len && memcmp(contents + index, type.name, type.length) == 0) {
					if (maxMatches == 1 && prevByteOpensKeyword(index)) {
						size_t end;
						if (matchSpacesAndName(index + type.length, &end)) {
							clean.append(contents + index - 1, end - (index - 1));
							return;
						}
					}

					inType = true;
				}

				break;
			}

			index += 1;
			size_t skipFrom = index;
			while (index < len && !pt_reject_table.bytes[(unsigned char) contents[index]]) {
				index++;
			}
			if (index > skipFrom) {
				clean.push_back(c);
				clean.append(contents + skipFrom, index - skipFrom);
			} else {
				clean.push_back(c);
			}
		}
	}

}



/* {{{ stage 1 — php_strip_whitespace() */

/*
 * Produces php_strip_whitespace()'s output byte for byte: zend_strip() over
 * the engine's lexer. The exact bytes matter — the cleaner skips a // comment
 * to the next newline and pairs quotes naively, so a newline zend_strip()
 * collapses into a space, or the "\n" it writes after a heredoc's closing
 * label, decides which symbols survive. zend_strip() drops comments, writes
 * one space for a whitespace token (none for a run that only a comment
 * interrupted), writes every other token verbatim, and after T_END_HEREDOC
 * writes the next token verbatim unless it is whitespace — a comment
 * included — followed by "\n".
 *
 * So this is a port of the lexer rules (Zend/zend_language_scanner.l) that
 * zend_strip() can observe: the states and the state stack (strings with
 * {$...}/${...} interpolation, heredoc/nowdoc, variable offsets, property
 * lookups, brace nesting), where whitespace and comments start, the tokens
 * that swallow whitespace or comments (casts, `yield from`), and the length
 * of any token that can follow a closing heredoc label. Tokens are otherwise
 * copied as source spans, coalesced so a run of them is one append.
 */
class CommentStripper
{
public:
	CommentStripper(const char *contents, size_t len, bool shortOpenTag, bool skipShebang)
		: contents(contents), len(len), shortOpenTag(shortOpenTag), skipShebang(skipShebang) {}

	void strip(std::string &out);

private:
	enum State : unsigned char {
		INITIAL,
		IN_SCRIPTING,
		LOOKING_FOR_PROPERTY,
		DOUBLE_QUOTES,
		BACKQUOTE,
		HEREDOC,
		NOWDOC,
		END_HEREDOC,
		VAR_OFFSET,
		LOOKING_FOR_VARNAME,
	};

	enum Kind : unsigned char {
		END,
		WHITESPACE,
		COMMENT,
		END_HEREDOC_TOKEN,
		OTHER,
	};

	struct HeredocLabel {
		size_t label;
		size_t length;
		size_t indentation;
	};

	const char *contents;
	size_t len;
	size_t index = 0;
	bool shortOpenTag;
	bool skipShebang;
	State state = INITIAL;
	std::vector<State> stateStack;
	std::vector<HeredocLabel> heredocLabels;

	/* the pending output span [spanStart, spanEnd) of the source */
	std::string *out = nullptr;
	size_t spanStart = 0;
	size_t spanEnd = 0;

	/* the byte at `at`, or the NUL the engine's scan buffer ends with */
	unsigned char at(size_t at) const
	{
		return at < len ? (unsigned char) contents[at] : 0;
	}

	static bool isWhitespace(unsigned char c)
	{
		return c == ' ' || c == '\t' || c == '\n' || c == '\r';
	}

	static bool isDigit(unsigned char c)
	{
		return c >= '0' && c <= '9';
	}

	static bool isHexDigit(unsigned char c)
	{
		return isDigit(c) || (c >= 'a' && c <= 'f') || (c >= 'A' && c <= 'F');
	}

	/* re2c's "..." literals are case-insensitive (--case-inverted) */
	bool matchesIgnoreCase(size_t at, const char *lowercase, size_t n) const
	{
		return at + n <= len && equalsIgnoreCase(contents + at, lowercase, n);
	}

	void pushState(State next)
	{
		stateStack.push_back(state);
		state = next;
	}

	/* never empty where the lexer pops; IN_SCRIPTING keeps a malformed
	 * sequence making progress */
	void popState()
	{
		if (stateStack.empty()) {
			state = IN_SCRIPTING;
			return;
		}
		state = stateStack.back();
		stateStack.pop_back();
	}

	void emit(size_t start, size_t length)
	{
		if (length == 0) return;
		if (start == spanEnd) {
			spanEnd += length;
			return;
		}
		flush();
		spanStart = start;
		spanEnd = start + length;
	}

	void emitByte(char c, size_t sourceAt)
	{
		if (sourceAt == spanEnd && sourceAt < len && contents[sourceAt] == c) {
			spanEnd++;
			return;
		}
		flush();
		out->push_back(c);
		spanStart = spanEnd = SIZE_MAX;
	}

	void flush()
	{
		if (spanEnd > spanStart && spanEnd != SIZE_MAX) {
			out->append(contents + spanStart, spanEnd - spanStart);
		}
		spanStart = spanEnd = SIZE_MAX;
	}

	size_t skipLabel(size_t at) const
	{
		while (at < len && isLabelByte((unsigned char) contents[at])) {
			at++;
		}
		return at;
	}

	/* LNUM: [0-9]+(_[0-9]+)*; false when there is none at `at` */
	bool skipLnum(size_t &at) const
	{
		if (!isDigit(this->at(at))) return false;
		while (isDigit(this->at(at))) {
			at++;
		}
		while (this->at(at) == '_' && isDigit(this->at(at + 1))) {
			at++;
			while (isDigit(this->at(at))) {
				at++;
			}
		}
		return true;
	}

	/* HNUM/BNUM/ONUM: "0x"[0-9a-fA-F]+(_[0-9a-fA-F]+)* and the like */
	bool skipPrefixedNumber(size_t &at) const
	{
		if (this->at(at) != '0') return false;
		unsigned char kind = this->at(at + 1);
		bool (*digit)(unsigned char);
		if (kind == 'x' || kind == 'X') {
			digit = [](unsigned char c) { return isHexDigit(c); };
		} else if (kind == 'b' || kind == 'B') {
			digit = [](unsigned char c) { return c == '0' || c == '1'; };
		} else if (kind == 'o' || kind == 'O') {
			digit = [](unsigned char c) { return c >= '0' && c <= '7'; };
		} else {
			return false;
		}
		size_t p = at + 2;
		if (!digit(this->at(p))) return false;
		while (digit(this->at(p))) {
			p++;
		}
		while (this->at(p) == '_' && digit(this->at(p + 1))) {
			p++;
			while (digit(this->at(p))) {
				p++;
			}
		}
		at = p;
		return true;
	}

	/* LNUM, HNUM, BNUM, ONUM, DNUM, EXPONENT_DNUM — the longest match at
	 * `at`, which holds a digit or a "." followed by one */
	size_t skipNumber(size_t at) const
	{
		size_t p = at;
		if (skipPrefixedNumber(p)) return p;
		bool integer = skipLnum(p);
		size_t end = p;
		if (this->at(p) == '.') {
			size_t fraction = p + 1;
			if (skipLnum(fraction)) {
				end = fraction;
			} else if (integer) {
				end = p + 1;
			}
		}
		unsigned char e = this->at(end);
		if (end > at && (e == 'e' || e == 'E')) {
			size_t exponent = end + 1;
			if (this->at(exponent) == '+' || this->at(exponent) == '-') {
				exponent++;
			}
			if (skipLnum(exponent)) {
				end = exponent;
			}
		}
		return end;
	}

	/* "#" or "//" up to a newline or "?>", neither of which it includes */
	size_t skipLineComment(size_t at) const
	{
		while (at < len) {
			char c = contents[at];
			if (c == '\n' || c == '\r') break;
			if (c == '?' && this->at(at + 1) == '>') break;
			at++;
		}
		return at;
	}

	/* from "/" "*" to the closing star-slash, or to the end when unterminated */
	size_t skipBlockComment(size_t at) const
	{
		at += 2;
		while (at < len) {
			if (contents[at++] == '*' && this->at(at) == '/') {
				return at + 1;
			}
		}
		return len;
	}

	/* one {WHITESPACE}|{MULTI_LINE_COMMENT}|{SINGLE_LINE_COMMENT}|{HASH_COMMENT}
	 * element of the `yield from` rule; false when none starts at `at` */
	bool skipWhitespaceOrComment(size_t &at) const
	{
		unsigned char c = this->at(at);
		if (at < len && isWhitespace(c)) {
			while (at < len && isWhitespace((unsigned char) contents[at])) {
				at++;
			}
			return true;
		}
		if (c == '/' && this->at(at + 1) == '*') {
			/* "/" "*" ([^*\x00]* "*"+) ([^*"/"\x00] [^*\x00]* "*"+)* "/" */
			for (size_t p = at + 2; p < len; p++) {
				if (contents[p] == '\0') return false;
				if (contents[p] == '*' && this->at(p + 1) == '/') {
					at = p + 2;
					return true;
				}
			}
			return false;
		}
		size_t p;
		if (c == '/' && this->at(at + 1) == '/') {
			p = at + 2;
		} else if (c == '#' && this->at(at + 1) != '[' && this->at(at + 1) != '\0') {
			p = at + 1;
		} else {
			return false;
		}
		/* [^\x00\n\r]* [\n\r] */
		while (p < len && contents[p] != '\0' && contents[p] != '\n' && contents[p] != '\r') {
			p++;
		}
		if (p >= len || contents[p] == '\0') return false;
		at = p + 1;
		return true;
	}

	size_t castLength(size_t at) const;
	size_t yieldFromLength(size_t at) const;
	bool startHeredoc(size_t at);

	Kind lexInitial();
	Kind inlineHtml();
	Kind lexScripting();
	Kind lexInterpolated();
	Kind lexVarOffset();
	Kind next(size_t &start);
};

/* "(" [ \t]* type [ \t]* ")" — the cast tokens keep their inner blanks */
inline size_t CommentStripper::castLength(size_t at) const
{
	static const char *const casts[] = {
		"int", "integer", "float", "double", "real", "string", "binary", "array", "object", "bool", "boolean", "unset",
#if PHP_VERSION_ID >= 80500
		"void",
#endif
	};
	size_t p = at + 1;
	while (this->at(p) == ' ' || this->at(p) == '\t') {
		p++;
	}
	size_t word = p;
	while ((this->at(p) >= 'a' && this->at(p) <= 'z') || (this->at(p) >= 'A' && this->at(p) <= 'Z')) {
		p++;
	}
	size_t wordLength = p - word;
	bool known = false;
	for (const char *cast : casts) {
		if (strlen(cast) == wordLength && equalsIgnoreCase(contents + word, cast, wordLength)) {
			known = true;
			break;
		}
	}
	if (!known) return 0;
	while (this->at(p) == ' ' || this->at(p) == '\t') {
		p++;
	}
	return this->at(p) == ')' ? p + 1 - at : 0;
}

/* "yield" {WHITESPACE_OR_COMMENTS} "from" [^a-zA-Z0-9_\x80-\xff] — one
 * token, whitespace and comments included; 0 when it does not match */
inline size_t CommentStripper::yieldFromLength(size_t at) const
{
	size_t p = at + 5;
	if (!skipWhitespaceOrComment(p)) return 0;
	while (skipWhitespaceOrComment(p)) {
	}
	if (!matchesIgnoreCase(p, "from", 4) || isLabelByte(this->at(p + 4))) return 0;
	return p + 4 - at;
}

/* b?"<<<" [ \t]* (LABEL | 'LABEL' | "LABEL") NEWLINE; false when `at` does
 * not start one (the "b" prefix is lexed as a label of its own before) */
inline bool CommentStripper::startHeredoc(size_t at)
{
	size_t p = at + 3;
	while (this->at(p) == ' ' || this->at(p) == '\t') {
		p++;
	}
	unsigned char quote = this->at(p);
	if (quote == '\'' || quote == '"') {
		p++;
	} else {
		quote = 0;
	}
	if (!isLabelStart(this->at(p))) return false;
	size_t label = p;
	p = skipLabel(p);
	size_t labelLength = p - label;
	if (quote != 0) {
		if (this->at(p) != quote) return false;
		p++;
	}
	if (this->at(p) == '\r') {
		p += this->at(p + 1) == '\n' ? 2 : 1;
	} else if (this->at(p) == '\n') {
		p++;
	} else {
		return false;
	}

	index = p;
	heredocLabels.push_back({label, labelLength, 0});
	state = quote == '\'' ? NOWDOC : HEREDOC;

	/* the closing label right on the next line */
	size_t indentation = 0;
	while (p < len && (contents[p] == ' ' || contents[p] == '\t')) {
		p++;
		indentation++;
	}
	if (p < len
		&& labelLength < len - p
		&& memcmp(contents + p, contents + label, labelLength) == 0
		&& !isLabelByte(this->at(p + labelLength))
	) {
		heredocLabels.back().indentation = indentation;
		state = END_HEREDOC;
	}
	return true;
}

inline CommentStripper::Kind CommentStripper::lexInitial()
{
	if (index >= len) return END;
	if (at(index) == '<' && at(index + 1) == '?') {
		if (at(index + 2) == '=') {
			index += 3;
			state = IN_SCRIPTING;
			return OTHER;
		}
		if (matchesIgnoreCase(index + 2, "php", 3)) {
			unsigned char c = at(index + 5);
			if (c == ' ' || c == '\t' || c == '\n') {
				index += 6;
				state = IN_SCRIPTING;
				return OTHER;
			}
			if (c == '\r') {
				index += at(index + 6) == '\n' ? 7 : 6;
				state = IN_SCRIPTING;
				return OTHER;
			}
			if (index + 5 == len) {
				index += 5;
				state = IN_SCRIPTING;
				return OTHER;
			}
			if (shortOpenTag) {
				index += 2;
				state = IN_SCRIPTING;
				return OTHER;
			}
			index += 5;
			return inlineHtml();
		}
		if (shortOpenTag) {
			index += 2;
			state = IN_SCRIPTING;
			return OTHER;
		}
		index += 2;
		return inlineHtml();
	}
	index++;
	return inlineHtml();
}

/* T_INLINE_HTML up to the next opening tag the lexer accepts */
inline CommentStripper::Kind CommentStripper::inlineHtml()
{
	for (;;) {
		const char *lt = index < len ? (const char *) memchr(contents + index, '<', len - index) : NULL;
		index = lt != NULL ? (size_t) (lt - contents) + 1 : len;
		if (index >= len) break;
		if (contents[index] == '?') {
			if (shortOpenTag
				|| at(index + 1) == '='
				|| (matchesIgnoreCase(index + 1, "php", 3) && (index + 4 == len || isWhitespace(at(index + 4))))
			) {
				index--;
				break;
			}
		}
	}
	return OTHER;
}

inline CommentStripper::Kind CommentStripper::lexScripting()
{
	if (index >= len) return END;
	unsigned char c = at(index);
	unsigned char c1 = at(index + 1);
	unsigned char c2 = at(index + 2);

	switch (c) {
		case ' ':
		case '\t':
		case '\n':
		case '\r':
			while (index < len && isWhitespace((unsigned char) contents[index])) {
				index++;
			}
			return WHITESPACE;
		case '#':
			if (c1 == '[') {
				index += 2;
				return OTHER;
			}
			index = skipLineComment(index + 1);
			return COMMENT;
		case '/':
			if (c1 == '/') {
				index = skipLineComment(index + 2);
				return COMMENT;
			}
			if (c1 == '*') {
				index = skipBlockComment(index);
				return COMMENT;
			}
			index += c1 == '=' ? 2 : 1;
			return OTHER;
		case '?':
			if (c1 == '>') {
				index += 2;
				if (at(index) == '\r') {
					index += at(index + 1) == '\n' ? 2 : 1;
				} else if (at(index) == '\n') {
					index++;
				}
				state = INITIAL;
				return OTHER;
			}
			if (c1 == '-' && c2 == '>') {
				index += 3;
				pushState(LOOKING_FOR_PROPERTY);
				return OTHER;
			}
			if (c1 == '?') {
				index += c2 == '=' ? 3 : 2;
				return OTHER;
			}
			index++;
			return OTHER;
		case '\'':
			for (index++; index < len; ) {
				if (contents[index] == '\'') {
					index++;
					break;
				}
				if (contents[index++] == '\\' && index < len) {
					index++;
				}
			}
			return OTHER;
		case '"': {
			/* the whole string when nothing is interpolated, else just the
			 * quote and the string's own state */
			for (size_t p = index + 1; p < len; ) {
				unsigned char s = (unsigned char) contents[p++];
				if (s == '"') {
					index = p;
					return OTHER;
				}
				if (s == '$' && (isLabelStart(at(p)) || at(p) == '{')) break;
				if (s == '{' && at(p) == '$') break;
				if (s == '\\' && p < len) {
					p++;
				}
			}
			index++;
			state = DOUBLE_QUOTES;
			return OTHER;
		}
		case '`':
			index++;
			state = BACKQUOTE;
			return OTHER;
		case '<':
			if (c1 == '<' && c2 == '<' && startHeredoc(index)) return OTHER;
			if (c1 == '<') {
				index += c2 == '=' ? 3 : 2;
			} else if (c1 == '=') {
				index += c2 == '>' ? 3 : 2;
			} else {
				index += c1 == '>' ? 2 : 1;
			}
			return OTHER;
		case '>':
			if (c1 == '>') {
				index += c2 == '=' ? 3 : 2;
			} else {
				index += c1 == '=' ? 2 : 1;
			}
			return OTHER;
		case '=':
			if (c1 == '=') {
				index += c2 == '=' ? 3 : 2;
			} else {
				index += c1 == '>' ? 2 : 1;
			}
			return OTHER;
		case '!':
			if (c1 == '=') {
				index += c2 == '=' ? 3 : 2;
			} else {
				index++;
			}
			return OTHER;
		case '+':
			index += c1 == '+' || c1 == '=' ? 2 : 1;
			return OTHER;
		case '-':
			if (c1 == '>') {
				index += 2;
				pushState(LOOKING_FOR_PROPERTY);
				return OTHER;
			}
			index += c1 == '-' || c1 == '=' ? 2 : 1;
			return OTHER;
		case '*':
			if (c1 == '*') {
				index += c2 == '=' ? 3 : 2;
			} else {
				index += c1 == '=' ? 2 : 1;
			}
			return OTHER;
		case '.':
			if (c1 == '.' && c2 == '.') {
				index += 3;
			} else if (isDigit(c1)) {
				index = skipNumber(index);
			} else {
				index += c1 == '=' ? 2 : 1;
			}
			return OTHER;
		case '%':
		case '^':
			index += c1 == '=' ? 2 : 1;
			return OTHER;
		case '&':
			index += c1 == '&' || c1 == '=' ? 2 : 1;
			return OTHER;
		case '|':
#if PHP_VERSION_ID >= 80500
			index += c1 == '|' || c1 == '=' || c1 == '>' ? 2 : 1;
#else
			index += c1 == '|' || c1 == '=' ? 2 : 1;
#endif
			return OTHER;
		case ':':
			index += c1 == ':' ? 2 : 1;
			return OTHER;
		case '(': {
			size_t cast = castLength(index);
			index += cast != 0 ? cast : 1;
			return OTHER;
		}
		case '{':
			index++;
			pushState(IN_SCRIPTING);
			return OTHER;
		case '}':
			index++;
			popState();
			return OTHER;
		case '$':
			index = isLabelStart(c1) ? skipLabel(index + 1) : index + 1;
			return OTHER;
		case '\\':
			/* "\\" LABEL ("\\" LABEL)* */
			index++;
			while (at(index - 1) == '\\' && isLabelStart(at(index))) {
				index = skipLabel(index);
				if (at(index) != '\\' || !isLabelStart(at(index + 1))) break;
				index++;
			}
			return OTHER;
		default:
			break;
	}

	if (isDigit(c)) {
		index = skipNumber(index);
		return OTHER;
	}
	if (isLabelStart(c)) {
		size_t end = skipLabel(index);
		if (end - index == 5 && equalsIgnoreCase(contents + index, "yield", 5)) {
			size_t yieldFrom = yieldFromLength(index);
			if (yieldFrom != 0) {
				end = index + yieldFrom;
			}
		}
		index = end;
		return OTHER;
	}
	/* the other single-byte tokens and T_BAD_CHARACTER */
	index++;
	return OTHER;
}

/* the states of a string's body: "...", `...` and heredoc/nowdoc */
inline CommentStripper::Kind CommentStripper::lexInterpolated()
{
	unsigned char c = at(index);
	if (state != NOWDOC) {
		if ((state == DOUBLE_QUOTES && c == '"') || (state == BACKQUOTE && c == '`')) {
			index++;
			state = IN_SCRIPTING;
			return OTHER;
		}
		if (c == '$') {
			unsigned char c1 = at(index + 1);
			if (isLabelStart(c1)) {
				size_t p = skipLabel(index + 1);
				index = p;
				if (at(p) == '-' && at(p + 1) == '>' && isLabelStart(at(p + 2))) {
					pushState(LOOKING_FOR_PROPERTY);
				} else if (at(p) == '?' && at(p + 1) == '-' && at(p + 2) == '>' && isLabelStart(at(p + 3))) {
					pushState(LOOKING_FOR_PROPERTY);
				} else if (at(p) == '[') {
					pushState(VAR_OFFSET);
				}
				return OTHER;
			}
			if (c1 == '{') {
				index += 2;
				pushState(LOOKING_FOR_VARNAME);
				return OTHER;
			}
		}
		if (c == '{' && at(index + 1) == '$') {
			index++;
			pushState(IN_SCRIPTING);
			return OTHER;
		}
	}

	if (index >= len) return END;

	if (state == DOUBLE_QUOTES || state == BACKQUOTE) {
		char delimiter = state == DOUBLE_QUOTES ? '"' : '`';
		size_t p = index + 1;
		if (c == '\\' && p < len) {
			p++;
		}
		while (p < len) {
			char s = contents[p++];
			if (s == delimiter) {
				p--;
				break;
			}
			if (s == '$') {
				if (isLabelStart(at(p)) || at(p) == '{') {
					p--;
					break;
				}
				continue;
			}
			if (s == '{') {
				if (at(p) == '$') {
					p--;
					break;
				}
				continue;
			}
			if (s == '\\' && p < len) {
				p++;
			}
		}
		index = p;
		return OTHER;
	}

	/* heredoc and nowdoc bodies: the closing label is looked for at the
	 * start of each line, after its indentation, and must not end the file */
	const HeredocLabel &label = heredocLabels.back();
	size_t p = index;
	while (p < len) {
		char s = contents[p++];
		if (s == '\r' || s == '\n') {
			if (s == '\r' && at(p) == '\n') {
				p++;
			}
			size_t indentation = 0;
			while (p < len && (contents[p] == ' ' || contents[p] == '\t')) {
				p++;
				indentation++;
			}
			if (p == len) break;
			if (isLabelStart(at(p))
				&& label.length < len - p
				&& memcmp(contents + p, contents + label.label, label.length) == 0
			) {
				if (isLabelByte(at(p + label.length))) continue;
				p -= indentation;
				heredocLabels.back().indentation = indentation;
				state = END_HEREDOC;
				break;
			}
			continue;
		}
		if (state == NOWDOC) continue;
		if (s == '$') {
			if (isLabelStart(at(p)) || at(p) == '{') {
				p--;
				break;
			}
			continue;
		}
		if (s == '{') {
			if (at(p) == '$') {
				p--;
				break;
			}
			continue;
		}
		if (s == '\\' && p < len && contents[p] != '\n' && contents[p] != '\r') {
			p++;
		}
	}
	index = p;
	return OTHER;
}

/* "$var[" inside a string: a single offset, verbatim, up to "]" or the
 * byte that aborts it (which stays for the string) */
inline CommentStripper::Kind CommentStripper::lexVarOffset()
{
	unsigned char c = at(index);
	if (isDigit(c)) {
		size_t p = index;
		if (!skipPrefixedNumber(p)) {
			skipLnum(p);
		}
		index = p;
		return OTHER;
	}
	if (c == '$' && isLabelStart(at(index + 1))) {
		index = skipLabel(index + 1);
		return OTHER;
	}
	if (c == ']') {
		index++;
		popState();
		return OTHER;
	}
	if (c != 0 && strchr(";:,.|^&+-/*=%!~$<>?@[(){}\"`", c) != NULL) {
		index++;
		return OTHER;
	}
	if (c != 0 && strchr(" \n\r\t\\'#", c) != NULL) {
		/* an empty T_ENCAPSED_AND_WHITESPACE */
		popState();
		return OTHER;
	}
	if (isLabelStart(c)) {
		index = skipLabel(index);
		return OTHER;
	}
	if (index >= len) return END;
	index++;
	return OTHER;
}

/* lex_scan(): the next token, [start, index) */
inline CommentStripper::Kind CommentStripper::next(size_t &start)
{
	for (;;) {
		start = index;
		switch (state) {
			case INITIAL:
				return lexInitial();
			case IN_SCRIPTING:
				return lexScripting();
			case LOOKING_FOR_PROPERTY: {
				unsigned char c = at(index);
				if (index < len && isWhitespace(c)) return lexScripting();
				if (c == '-' && at(index + 1) == '>') {
					index += 2;
					return OTHER;
				}
				if (c == '?' && at(index + 1) == '-' && at(index + 2) == '>') {
					index += 3;
					return OTHER;
				}
				if (isLabelStart(c)) {
					index = skipLabel(index);
					popState();
					return OTHER;
				}
				if (c == '#' || (c == '/' && at(index + 1) == '/')) {
					index = skipLineComment(index + (c == '#' ? 1 : 2));
					return COMMENT;
				}
				if (c == '/' && at(index + 1) == '*') {
					index = skipBlockComment(index);
					return COMMENT;
				}
				popState();
				continue;
			}
			case DOUBLE_QUOTES:
			case BACKQUOTE:
			case HEREDOC:
			case NOWDOC:
				return lexInterpolated();
			case END_HEREDOC: {
				HeredocLabel label = heredocLabels.back();
				heredocLabels.pop_back();
				index += label.indentation + label.length;
				state = IN_SCRIPTING;
				return END_HEREDOC_TOKEN;
			}
			case VAR_OFFSET:
				return lexVarOffset();
			case LOOKING_FOR_VARNAME: {
				if (isLabelStart(at(index))) {
					size_t end = skipLabel(index);
					if (at(end) == '[' || at(end) == '}') {
						index = end;
						popState();
						pushState(IN_SCRIPTING);
						return OTHER;
					}
				}
				popState();
				pushState(IN_SCRIPTING);
				continue;
			}
		}
	}
}

/* zend_strip() */
inline void CommentStripper::strip(std::string &output)
{
	output.clear();
	output.reserve(len);
	out = &output;
	spanStart = spanEnd = SIZE_MAX;

	/* <SHEBANG>"#!" .* {NEWLINE} is skipped, not emitted */
	if (skipShebang && at(0) == '#' && at(1) == '!') {
		const char *newline = len > 2 ? (const char *) memchr(contents + 2, '\n', len - 2) : NULL;
		if (newline != NULL) {
			index = (size_t) (newline - contents) + 1;
		} else {
			for (size_t p = len; p > 2; p--) {
				if (contents[p - 1] == '\r') {
					index = p;
					break;
				}
			}
		}
	}

	bool prevSpace = false;
	size_t start;
	for (;;) {
		Kind kind = next(start);
		if (kind == END) break;
		if (kind == WHITESPACE) {
			if (!prevSpace) {
				emitByte(' ', start);
				prevSpace = true;
			}
			continue;
		}
		if (kind == COMMENT) continue;
		emit(start, index - start);
		if (kind == END_HEREDOC_TOKEN) {
			/* the following token, verbatim unless it is whitespace */
			if (next(start) != WHITESPACE) {
				emit(start, index - start);
			}
			emitByte('\n', index);
			prevSpace = true;
			continue;
		}
		prevSpace = false;
	}
	flush();
	out = nullptr;
}

/* }}} */


/* {{{ stage 2a — the prefilter count */

/*
 * The twin's prefilter, `{\b(?:(?:class|interface|trait|const|function|enum)\s)
 * |(?:define\s*\()}i`, whose match count it hands to the cleaner as
 * maxMatches. Only "is it exactly one" is ever asked (that is what arms the
 * cleaner's early return) and zero means the twin returns no symbols at all,
 * so counting stops at two.
 *
 * It cannot be skipped even though the full scan finds the same declarations:
 * $typeConfig always contains `enum`, so on a supportsEnums=false run the
 * early return can fire on an enum the symbol regex has no branch for and
 * truncate away a function or constant that would otherwise be found.
 *
 * Note the pattern's shape: the \b applies to the keyword branch only, and
 * the keyword must be followed by whitespace, both unlike the symbol regex.
 */
inline size_t prefilterCount(const char *contents, size_t len, bool supportsEnums)
{
	static const char *const keywords[] = { "class", "interface", "trait", "const", "function", "enum" };
	static const size_t keywordLengths[] = { 5, 9, 5, 5, 8, 4 };
	const size_t keywordCount = supportsEnums ? 6 : 5;

	size_t count = 0;
	size_t i = 0;
	while (i < len && count < 2) {
		unsigned char c = (unsigned char) contents[i];
		if (!pt_keyword_start_table.bytes[c]) {
			i++;
			continue;
		}

		if (i == 0 || !isWordByte((unsigned char) contents[i - 1])) {
			bool matched = false;
			for (size_t k = 0; k < keywordCount; k++) {
				size_t length = keywordLengths[k];
				if (i + length < len
					&& equalsIgnoreCase(contents + i, keywords[k], length)
					&& isSpaceByte((unsigned char) contents[i + length])
				) {
					count++;
					i += length + 1;
					matched = true;
					break;
				}
			}
			if (matched) continue;
		}

		/* the define branch carries no \b — `mydefine(` counts too */
		if ((c == 'd' || c == 'D') && i + 6 <= len && equalsIgnoreCase(contents + i, "define", 6)) {
			size_t p = i + 6;
			while (p < len && isSpaceByte((unsigned char) contents[p])) {
				p++;
			}
			if (p < len && contents[p] == '(') {
				count++;
				i = p + 1;
				continue;
			}
		}

		i++;
	}

	return count;
}

/* }}} */

/* {{{ stage 3 — the symbol regex */

struct Symbols {
	std::vector<std::string> classes;
	std::vector<std::string> functions;
	std::vector<std::string> constants;

	void clear()
	{
		classes.clear();
		functions.clear();
		constants.clear();
	}
};

/*
 * The preg_match_all() over the cleaned text plus the loop that turns its
 * captures into symbol names. The pattern is one alternation of five
 * branches, all sharing a `\b(?<![\$:>])` prefix, so the walk only has to
 * try a branch where that guard holds and the byte can start a keyword.
 */
class SymbolMatcher
{
public:
	SymbolMatcher(const char *contents, size_t len, bool supportsEnums)
		: contents(contents), len(len), supportsEnums(supportsEnums) {}

	void match(Symbols &out);

private:
	const char *contents;
	size_t len;
	bool supportsEnums;
	std::string currentNamespace;

	bool guard(size_t at) const
	{
		if (at == 0) return true;
		unsigned char prev = (unsigned char) contents[at - 1];
		return !isWordByte(prev) && prev != '$' && prev != ':' && prev != '>';
	}

	bool keyword(size_t at, const char *lowercase, size_t length) const
	{
		return at + length <= len && equalsIgnoreCase(contents + at, lowercase, length)
			&& (at + length >= len || !isWordByte((unsigned char) contents[at + length]));
	}

	size_t skipSpaces(size_t at) const
	{
		while (at < len && isSpaceByte((unsigned char) contents[at])) {
			at++;
		}
		return at;
	}

	/* [a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\-]*+ */
	size_t readName(size_t at) const
	{
		if (at >= len || !isNameStart((unsigned char) contents[at])) return 0;
		size_t end = at + 1;
		while (end < len && isNameByte((unsigned char) contents[end])) {
			end++;
		}
		return end;
	}

	/* the define() name: identifiers joined by one or two backslashes */
	size_t readDefineName(size_t at) const
	{
		if (at >= len || !isNameStart((unsigned char) contents[at])) return 0;
		size_t end = at + 1;
		while (end < len && isDefineNameByte((unsigned char) contents[end])) {
			end++;
		}
		for (;;) {
			size_t p = end;
			size_t slashes = 0;
			while (p < len && contents[p] == '\\' && slashes < 2) {
				p++;
				slashes++;
			}
			if (slashes == 0 || p >= len || !isNameStart((unsigned char) contents[p])) break;
			p++;
			while (p < len && isDefineNameByte((unsigned char) contents[p])) {
				p++;
			}
			end = p;
		}
		return end;
	}

	static void appendLowercase(std::string &out, const char *from, size_t length)
	{
		for (size_t i = 0; i < length; i++) {
			char c = from[i];
			out.push_back(c >= 'A' && c <= 'Z' ? (char) (c - 'A' + 'a') : c);
		}
	}

	/* strtolower(ltrim($namespace . $name, '\\')) */
	std::string qualified(const char *name, size_t nameLen) const
	{
		std::string full = currentNamespace;
		full.append(name, nameLen);
		size_t start = 0;
		while (start < full.size() && full[start] == '\\') {
			start++;
		}
		std::string result;
		result.reserve(full.size() - start);
		appendLowercase(result, full.data() + start, full.size() - start);
		return result;
	}

	/* self::normalizeConstantName(): the namespace part lowercases, the
	 * constant's own name keeps its case */
	static std::string normalizeConstantName(const std::string &name)
	{
		if (name.find('\\') == std::string::npos) return name;

		std::vector<std::string> parts;
		size_t start = 0;
		for (size_t i = 0; i <= name.size(); i++) {
			if (i == name.size() || name[i] == '\\') {
				if (i > start) {
					parts.emplace_back(name, start, i - start);
				}
				start = i + 1;
			}
		}
		if (parts.empty()) return std::string("\\");

		std::string result;
		for (size_t i = 0; i + 1 < parts.size(); i++) {
			if (i > 0) {
				result.push_back('\\');
			}
			appendLowercase(result, parts[i].data(), parts[i].size());
		}
		result.push_back('\\');
		result.append(parts.back());

		return result;
	}

	/* ltrim($namespace . $name, '\\') without the lowercasing */
	std::string qualifiedConstant(const char *name, size_t nameLen) const
	{
		std::string full = currentNamespace;
		full.append(name, nameLen);
		size_t start = 0;
		while (start < full.size() && full[start] == '\\') {
			start++;
		}
		return full.substr(start);
	}
};

inline void SymbolMatcher::match(Symbols &out)
{
	currentNamespace.clear();

	size_t i = 0;
	while (i < len) {
		unsigned char c = (unsigned char) contents[i];
		if (!pt_keyword_start_table.bytes[c] || !guard(i)) {
			i++;
			continue;
		}

		/* class|interface|trait[|enum] \s++ NAME */
		static const char *const typeNames[] = { "class", "interface", "trait", "enum" };
		static const size_t typeLengths[] = { 5, 9, 5, 4 };
		bool matched = false;
		for (size_t t = 0; t < 4; t++) {
			if (t == 3 && !supportsEnums) break;
			if (!keyword(i, typeNames[t], typeLengths[t])) continue;
			size_t after = i + typeLengths[t];
			size_t nameStart = skipSpaces(after);
			if (nameStart == after) break;
			size_t nameEnd = readName(nameStart);
			if (nameEnd == 0) break;
			size_t nameLen = nameEnd - nameStart;
			/* skip anonymous classes: `new class extends X` captures the
			 * keyword that follows as if it were the name */
			if (!(nameLen == 7 && memcmp(contents + nameStart, "extends", 7) == 0)
				&& !(nameLen == 10 && memcmp(contents + nameStart, "implements", 10) == 0)
			) {
				out.classes.push_back(qualified(contents + nameStart, nameLen));
			}
			i = nameEnd;
			matched = true;
			break;
		}
		if (matched) continue;

		/* function \s++ (&\s*)? NAME \s*+ [&(] */
		if (keyword(i, "function", 8)) {
			size_t after = i + 8;
			size_t p = skipSpaces(after);
			if (p != after) {
				if (p < len && contents[p] == '&') {
					p = skipSpaces(p + 1);
				}
				size_t nameEnd = readName(p);
				if (nameEnd != 0) {
					size_t tail = skipSpaces(nameEnd);
					if (tail < len && (contents[tail] == '&' || contents[tail] == '(')) {
						out.functions.push_back(qualified(contents + p, nameEnd - p));
						i = tail + 1;
						continue;
					}
				}
			}
		}

		/* const \s++ NAME \s*+ [^;] */
		if (keyword(i, "const", 5)) {
			size_t after = i + 5;
			size_t p = skipSpaces(after);
			if (p != after) {
				size_t nameEnd = readName(p);
				if (nameEnd != 0) {
					size_t tail = skipSpaces(nameEnd);
					if (tail < len && contents[tail] != ';') {
						out.constants.push_back(normalizeConstantName(qualifiedConstant(contents + p, nameEnd - p)));
						i = tail + 1;
						continue;
					}
				}
			}
		}

		/* define \s*+ \( \s*+ ['"] DNAME */
		if (keyword(i, "define", 6)) {
			size_t p = skipSpaces(i + 6);
			if (p < len && contents[p] == '(') {
				p = skipSpaces(p + 1);
				if (p < len && (contents[p] == '\'' || contents[p] == '"')) {
					size_t nameStart = p + 1;
					size_t nameEnd = readDefineName(nameStart);
					if (nameEnd != 0) {
						out.constants.push_back(normalizeConstantName(std::string(contents + nameStart, nameEnd - nameStart)));
						i = nameEnd;
						continue;
					}
				}
			}
		}

		/* namespace (\s++ NSNAME)? \s*+ [{;] */
		if (keyword(i, "namespace", 9)) {
			size_t after = i + 9;
			size_t nameStart = skipSpaces(after);
			size_t nameEnd = nameStart;
			if (nameStart != after && nameStart < len && isNameStart((unsigned char) contents[nameStart])) {
				nameEnd = nameStart + 1;
				while (nameEnd < len && isDefineNameByte((unsigned char) contents[nameEnd])) {
					nameEnd++;
				}
				for (;;) {
					size_t p = skipSpaces(nameEnd);
					if (p >= len || contents[p] != '\\') break;
					p = skipSpaces(p + 1);
					if (p >= len || !isNameStart((unsigned char) contents[p])) break;
					p++;
					while (p < len && isDefineNameByte((unsigned char) contents[p])) {
						p++;
					}
					nameEnd = p;
				}
			} else {
				nameEnd = after;
				nameStart = after;
			}

			size_t tail = skipSpaces(nameEnd);
			if (tail < len && (contents[tail] == '{' || contents[tail] == ';')) {
				currentNamespace.clear();
				for (size_t p = nameStart; p < nameEnd; p++) {
					char ch = contents[p];
					if (isSpaceByte((unsigned char) ch)) continue;
					currentNamespace.push_back(ch >= 'A' && ch <= 'Z' ? (char) (ch - 'A' + 'a') : ch);
				}
				currentNamespace.push_back('\\');
				i = tail + 1;
				continue;
			}
		}

		i++;
	}
}

/* }}} */

} // namespace phpstanturbo

#endif
