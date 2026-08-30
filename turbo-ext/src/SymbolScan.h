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
		if (c != lowercaseB[i]) {
			return false;
		}
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
		if (at == 0 || at > len) {
			return false;
		}
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
		if (p == from || p >= len || !isNameStart((unsigned char) contents[p])) {
			return false;
		}
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
		if (index >= len) {
			break;
		}
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
	if (p + 3 > len || contents[p] != '<' || contents[p + 1] != '<' || contents[p + 2] != '<') {
		return false;
	}
	p += 3;
	while (p < len && (contents[p] == ' ' || contents[p] == '\t')) {
		p++;
	}
	char quote = '\0';
	if (p < len && (contents[p] == '\'' || contents[p] == '"')) {
		quote = contents[p];
		p++;
	}
	if (p >= len || !isLabelStart((unsigned char) contents[p])) {
		return false;
	}
	size_t start = p;
	p++;
	while (p < len && isLabelByte((unsigned char) contents[p])) {
		p++;
	}
	*labelStart = start;
	*labelLen = p - start;
	if (quote != '\0') {
		if (p >= len || contents[p] != quote) {
			return false;
		}
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
				if (type.firstByte != c) {
					continue;
				}

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



/* {{{ stage 1 — php_strip_whitespace() equivalent */

/* Bytes that can start a construct the stripper must understand. */
static const struct StripTable {
	bool bytes[256];

	StripTable() : bytes()
	{
		for (const char *p = "?/#'\"`<"; *p != '\0'; p++) {
			bytes[(unsigned char) *p] = true;
		}
	}
} pt_strip_table;

/*
 * Removes comments the way php_strip_whitespace() does — emitting nothing in
 * their place, so the bytes around them become adjacent — while copying
 * everything else through verbatim. The twin's stripper also collapses each
 * whitespace run to a single space; that is deliberately not reproduced,
 * because every consumer downstream matches whitespace with \s+ or \s* and
 * cannot tell the difference, and copying spans verbatim is faster.
 *
 * Unlike the cleaner, this stage has to know real lexer rules: # comments
 * (but not #[ attributes), line comments ending at ?>, and backtick strings.
 */
class CommentStripper
{
public:
	CommentStripper(const char *contents, size_t len, bool shortOpenTag)
		: contents(contents), len(len), index(0), shortOpenTag(shortOpenTag) {}

	void strip(std::string &out);

private:
	const char *contents;
	size_t len;
	size_t index;
	bool shortOpenTag;

	/* length of the open tag at `at`, or 0 if there is none */
	size_t openTagLength(size_t at) const
	{
		if (at + 1 >= len || contents[at] != '<' || contents[at + 1] != '?') {
			return 0;
		}
		if (at + 4 < len && equalsIgnoreCase(contents + at + 2, "php", 3)
			&& (at + 5 >= len || isSpaceByte((unsigned char) contents[at + 5]))
		) {
			return 5;
		}
		if (at + 2 < len && contents[at + 2] == '=') {
			return 3;
		}

		return shortOpenTag ? 2 : 0;
	}

	/* a // or # comment: ends at a newline (left in place, it is whitespace)
	 * or at ?>, which the caller then handles as the close tag it is */
	void skipLineComment()
	{
		while (index < len) {
			char c = contents[index];
			if (c == '\n' || c == '\r') {
				return;
			}
			if (c == '?' && index + 1 < len && contents[index + 1] == '>') {
				return;
			}
			index++;
		}
	}

	void skipBlockComment()
	{
		index += 2;
		while (index + 1 < len) {
			if (contents[index] == '*' && contents[index + 1] == '/') {
				index += 2;
				return;
			}
			index++;
		}
		index = len;
	}

	/* copies a quoted string verbatim; a backslash escapes the next byte,
	 * which finds the same closing quote as PHP's own rules do */
	void copyString(char delimiter, std::string &out)
	{
		size_t start = index;
		index++;
		while (index < len) {
			char c = contents[index];
			if (c == '\\' && index + 1 < len) {
				index += 2;
				continue;
			}
			index++;
			if (c == delimiter) {
				break;
			}
		}
		out.append(contents + start, index - start);
	}

	void copyHeredoc(std::string &out);
};

inline void CommentStripper::copyHeredoc(std::string &out)
{
	size_t p = index + 3;
	while (p < len && (contents[p] == ' ' || contents[p] == '\t')) {
		p++;
	}
	char quote = '\0';
	if (p < len && (contents[p] == '\'' || contents[p] == '"')) {
		quote = contents[p];
		p++;
	}
	if (p >= len || !isLabelStart((unsigned char) contents[p])) {
		/* not a heredoc after all — let the caller copy the bytes */
		return;
	}
	size_t labelStart = p;
	p++;
	while (p < len && isLabelByte((unsigned char) contents[p])) {
		p++;
	}
	size_t labelLen = p - labelStart;
	if (quote != '\0') {
		if (p >= len || contents[p] != quote) {
			return;
		}
		p++;
	}
	if (p < len && contents[p] == '\r') {
		p += (p + 1 < len && contents[p + 1] == '\n') ? 2 : 1;
	} else if (p < len && contents[p] == '\n') {
		p += 1;
	} else {
		return;
	}

	size_t bodyStart = p;
	const char *label = contents + labelStart;
	while (p < len) {
		char c = contents[p];
		if (c == '\t' || c == ' ') {
			p++;
			continue;
		}
		if (c == label[0]
			&& p + labelLen <= len
			&& memcmp(contents + p, label, labelLen) == 0
			&& (p + labelLen >= len || !isLabelByte((unsigned char) contents[p + labelLen]))
		) {
			p += labelLen;
			break;
		}
		while (p < len && contents[p] != '\r' && contents[p] != '\n') {
			p++;
		}
		while (p < len && (contents[p] == '\r' || contents[p] == '\n')) {
			p++;
		}
	}
	(void) bodyStart;

	out.append(contents + index, p - index);
	index = p;
}

inline void CommentStripper::strip(std::string &out)
{
	out.clear();
	out.reserve(len);

	while (index < len) {
		/* inline HTML up to the next opening tag, copied verbatim */
		size_t htmlStart = index;
		size_t tagLength = 0;
		while (index < len) {
			tagLength = openTagLength(index);
			if (tagLength != 0) {
				break;
			}
			index++;
		}
		out.append(contents + htmlStart, index - htmlStart);
		if (index >= len) {
			return;
		}
		out.append(contents + index, tagLength);
		index += tagLength;

		while (index < len) {
			char c = contents[index];

			if (c == '?' && index + 1 < len && contents[index + 1] == '>') {
				out.append("?>", 2);
				index += 2;
				break;
			}

			if (c == '/' && index + 1 < len && contents[index + 1] == '/') {
				skipLineComment();
				continue;
			}

			if (c == '#') {
				if (index + 1 < len && contents[index + 1] == '[') {
					out.append("#[", 2);
					index += 2;
					continue;
				}
				skipLineComment();
				continue;
			}

			if (c == '/' && index + 1 < len && contents[index + 1] == '*') {
				skipBlockComment();
				continue;
			}

			if (c == '\'' || c == '"' || c == '`') {
				copyString(c, out);
				continue;
			}

			if (c == '<' && index + 2 < len && contents[index + 1] == '<' && contents[index + 2] == '<') {
				size_t before = index;
				copyHeredoc(out);
				if (index != before) {
					continue;
				}
			}

			size_t start = index;
			index++;
			while (index < len && !pt_strip_table.bytes[(unsigned char) contents[index]]) {
				index++;
			}
			out.append(contents + start, index - start);
		}
	}
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
			if (matched) {
				continue;
			}
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
		if (at == 0) {
			return true;
		}
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
		if (at >= len || !isNameStart((unsigned char) contents[at])) {
			return 0;
		}
		size_t end = at + 1;
		while (end < len && isNameByte((unsigned char) contents[end])) {
			end++;
		}
		return end;
	}

	/* the define() name: identifiers joined by one or two backslashes */
	size_t readDefineName(size_t at) const
	{
		if (at >= len || !isNameStart((unsigned char) contents[at])) {
			return 0;
		}
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
			if (slashes == 0 || p >= len || !isNameStart((unsigned char) contents[p])) {
				break;
			}
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
		if (name.find('\\') == std::string::npos) {
			return name;
		}

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
		if (parts.empty()) {
			return std::string("\\");
		}

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
			if (t == 3 && !supportsEnums) {
				break;
			}
			if (!keyword(i, typeNames[t], typeLengths[t])) {
				continue;
			}
			size_t after = i + typeLengths[t];
			size_t nameStart = skipSpaces(after);
			if (nameStart == after) {
				break;
			}
			size_t nameEnd = readName(nameStart);
			if (nameEnd == 0) {
				break;
			}
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
		if (matched) {
			continue;
		}

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
					if (p >= len || contents[p] != '\\') {
						break;
					}
					p = skipSpaces(p + 1);
					if (p >= len || !isNameStart((unsigned char) contents[p])) {
						break;
					}
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
					if (isSpaceByte((unsigned char) ch)) {
						continue;
					}
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
