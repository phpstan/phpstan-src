/*
 * PHPStanTurbo\SymbolFinderInFiles — native implementation of
 * PHPStan\Reflection\BetterReflection\SourceLocator\SymbolFinderInFiles.
 *
 * The twin runs four stages per file — php_strip_whitespace(), a prefilter
 * regex, PhpFileCleaner::clean() and the symbol regex — each materializing a
 * PHP string. Here the same three transformations (the prefilter is only an
 * optimization, see below) run back to back over two reusable buffers, so a
 * whole directory is scanned without allocating a PHP value per file.
 *
 * The prefilter survives as a native counting scan that stops at two matches
 * — the cleaner only ever asks whether the count is exactly one. Dropping it
 * looked safe and is not: $typeConfig always contains `enum`, so on a
 * supportsEnums=false run the early return fires on an enum the symbol regex
 * cannot collect and truncates away symbols that follow it.
 *
 * Parity with the twin is the bar, and it is checked file by file over the
 * whole repository by turbo-ext/tests/symbol-finder-corpus.php.
 */

#include "support.h"
#include "zv.h"
#include "SymbolScan.h"

#ifdef PHP_WIN32
#include <io.h>
#include <fcntl.h>
#else
#include <fcntl.h>
#include <sys/stat.h>
#include <unistd.h>
#endif

static zend_class_entry *pt_ce_symbol_finder = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\...\SymbolFinderInFiles. The buffers live for the whole
 * findSymbols() call so a directory of thousands of files reuses one pair of
 * allocations. */
class SymbolFinderInFiles
{
public:
	/* files far above this are not worth keeping the read buffer for */
	static constexpr size_t BUFFER_RETENTION_LIMIT = 4 * 1024 * 1024;

	zv::Val findSymbols(HashTable *files, bool supportsEnums);

private:
	std::string source;
	std::string stripped;
	std::string cleaned;
	Symbols symbols;

	bool readFile(const char *path, size_t pathLen);
	void scan(bool supportsEnums);
	static void symbolsToArray(const Symbols &symbols, zval *out);
};

/*
 * The twin reaches the file through php_strip_whitespace(), which goes past
 * the stream wrappers; the locators only ever pass real paths from their own
 * directory walk, so a plain open() is enough — and an unreadable file has to
 * behave like the twin's suppressed warning, i.e. produce no symbols.
 */
bool SymbolFinderInFiles::readFile(const char *path, size_t pathLen)
{
	source.clear();

	if (pathLen == 0 || memchr(path, '\0', pathLen) != NULL) {
		return false;
	}

#ifdef PHP_WIN32
	int fd = _open(path, _O_RDONLY | _O_BINARY);
#else
	int fd = open(path, O_RDONLY);
#endif
	if (fd < 0) {
		return false;
	}

	char chunk[65536];
	for (;;) {
#ifdef PHP_WIN32
		int got = _read(fd, chunk, sizeof(chunk));
#else
		ssize_t got = read(fd, chunk, sizeof(chunk));
#endif
		if (got < 0) {
#ifdef PHP_WIN32
			_close(fd);
#else
			close(fd);
#endif
			source.clear();
			return false;
		}
		if (got == 0) {
			break;
		}
		source.append(chunk, (size_t) got);
	}

#ifdef PHP_WIN32
	_close(fd);
#else
	close(fd);
#endif

	return true;
}

void SymbolFinderInFiles::scan(bool supportsEnums)
{
	symbols.clear();

	if (source.empty()) {
		return;
	}

	CommentStripper stripper(source.data(), source.size(), shortOpenTagEnabled());
	stripper.strip(stripped);

	if (stripped.empty()) {
		return;
	}

	size_t matches = prefilterCount(stripped.data(), stripped.size(), supportsEnums);
	if (matches == 0) {
		return;
	}

	PhpFileCleaner cleaner(stripped.data(), stripped.size());
	cleaner.clean((zend_long) matches, cleaned);

	SymbolMatcher matcher(cleaned.data(), cleaned.size(), supportsEnums);
	matcher.match(symbols);
}

void SymbolFinderInFiles::symbolsToArray(const Symbols &symbols, zval *out)
{
	zval &triple = *out;
	array_init_size(&triple, 3);

	const std::vector<std::string> *groups[3] = { &symbols.classes, &symbols.functions, &symbols.constants };
	for (const std::vector<std::string> *group : groups) {
		zval list;
		array_init_size(&list, (uint32_t) group->size());
		for (const std::string &name : *group) {
			zval item;
			ZVAL_STRINGL(&item, name.data(), name.size());
			zend_hash_next_index_insert_new(Z_ARRVAL(list), &item);
		}
		zend_hash_next_index_insert_new(Z_ARRVAL(triple), &list);
	}
}

zv::Val SymbolFinderInFiles::findSymbols(HashTable *files, bool supportsEnums)
{
	zval result;
	array_init_size(&result, zend_hash_num_elements(files));

	for (zv::ArrayEntry file : zv::TableRef(files)) {
		zv::Ref value = file.value().deref();
		if (!value.isString()) {
			continue;
		}

		zend_string *path = value.asString();
		if (readFile(ZSTR_VAL(path), ZSTR_LEN(path))) {
			scan(supportsEnums);
		} else {
			symbols.clear();
		}

		zval triple;
		symbolsToArray(symbols, &triple);
		zend_hash_update(Z_ARRVAL(result), path, &triple);

		if (source.capacity() > BUFFER_RETENTION_LIMIT) {
			std::string().swap(source);
		}
	}

	return zv::Val::adopt(result);
}

} // namespace phpstanturbo

/* {{{ registration */

#include "reg.h"

#define CLEANER_CLASS "PHPStan\\Reflection\\BetterReflection\\SourceLocator\\PhpFileCleaner"

void pt_register_symbol_finder_in_files()
{
	reg::Class cls("PHPStanTurbo\\SymbolFinderInFiles");

	/* the arginfo has to keep the real parameter class name: Nette reflects
	 * this constructor while compiling the container (rule 6) */
	cls.method("__construct", reg::Public, 1, { reg::obj("cleaner", CLEANER_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *cleaner;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(cleaner)
		ZEND_PARSE_PARAMETERS_END();
		(void) cleaner;
	});

	cls.method("findSymbols", reg::Public, 2, { reg::arrayArg("files"), reg::boolArg("supportsEnums") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *files;
		bool supportsEnums;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_ARRAY_HT(files)
			Z_PARAM_BOOL(supportsEnums)
		ZEND_PARSE_PARAMETERS_END();

		phpstanturbo::SymbolFinderInFiles finder;
		finder.findSymbols(files, supportsEnums).intoReturnValue(return_value);
	});

	/* not final: a PHP stub subclass may extend this class */
	pt_ce_symbol_finder = cls.register_();
}

/* }}} */
