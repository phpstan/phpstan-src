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
#include "generated/SymbolFinderInFiles.h"

namespace slots = ptdecl::SymbolFinderInFiles::slot;
namespace sigs = ptdecl::SymbolFinderInFiles::sig;
#include "zv.h"
#include "SymbolScan.h"


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

	bool readFile(zend_string *path);
	void scan(bool supportsEnums);
	static void symbolsToArray(const Symbols &symbols, zval *out);
};

/*
 * The twin reaches the file through php_strip_whitespace(), which opens it
 * the way the engine opens an included file: PHP's stream layer with the
 * include path, the include-only checks (a non-regular file is refused,
 * allow_url_include applies) and every stream wrapper — a phar:// path is a
 * supported scan case. The same call here keeps all of that, and Windows'
 * UTF-8 paths, identical. A file that cannot be opened behaves like the
 * twin's suppressed warning: no symbols.
 */
bool SymbolFinderInFiles::readFile(zend_string *path)
{
	source.clear();

	php_stream *stream = php_stream_open_wrapper_ex(ZSTR_VAL(path), "rb", USE_PATH | STREAM_OPEN_FOR_INCLUDE, NULL, NULL);
	if (stream == NULL) return false;

	char chunk[65536];
	for (;;) {
		ssize_t got = php_stream_read(stream, chunk, sizeof(chunk));
		if (got < 0) {
			php_stream_close(stream);
			source.clear();
			return false;
		}
		if (got == 0) break;
		source.append(chunk, (size_t) got);
	}
	php_stream_close(stream);

	return true;
}

void SymbolFinderInFiles::scan(bool supportsEnums)
{
	symbols.clear();

	if (source.empty()) return;

	CommentStripper stripper(source.data(), source.size(), shortOpenTagEnabled(), skipShebangEnabled());
	stripper.strip(stripped);

	if (stripped.empty()) return;

	size_t matches = prefilterCount(stripped.data(), stripped.size(), supportsEnums);
	if (matches == 0) return;

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
	zv::Val owned = zv::Val::adopt(result);

	for (zv::ArrayEntry file : zv::TableRef(files)) {
		zv::Ref value = file.value().deref();
		/* findSymbolsInFile(string $file, ...) under strict_types */
		if (!value.isString()) {
			zend_type_error("%s::findSymbolsInFile(): Argument #1 ($file) must be of type string, %s given", ZSTR_VAL(pt_ce_symbol_finder->name), zend_zval_value_name(value.raw()));
			return zv::Val();
		}

		zend_string *path = value.asString();
		/* php_strip_whitespace()'s Z_PARAM_PATH_STR */
		if (UNEXPECTED(ZSTR_LEN(path) != strlen(ZSTR_VAL(path)))) {
			zend_value_error("php_strip_whitespace(): Argument #1 ($filename) must not contain any null bytes");
			return zv::Val();
		}
		bool read = readFile(path);
		/* a user stream wrapper threw: the twin's @ does not catch that */
		if (UNEXPECTED(EG(exception))) return zv::Val();
		if (read) {
			scan(supportsEnums);
		} else {
			symbols.clear();
		}

		/* $result[$file] = ...: a numeric-string path becomes an int key */
		zval triple;
		symbolsToArray(symbols, &triple);
		zend_symtable_update(Z_ARRVAL_P(owned.raw()), path, &triple);

		if (source.capacity() > BUFFER_RETENTION_LIMIT) {
			std::string().swap(source);
		}
	}

	return owned;
}

} // namespace phpstanturbo

/* {{{ registration */

#include "reg.h"

void pt_register_symbol_finder_in_files()
{
	reg::Class cls("PHPStan\\Reflection\\BetterReflection\\SourceLocator\\SymbolFinderInFiles");
	ptdecl::SymbolFinderInFiles::declareClass(cls);
	ptdecl::SymbolFinderInFiles::declareProperties(cls);

	/* the arginfo has to keep the real parameter class name: Nette reflects
	 * this constructor while compiling the container (rule 6). The promoted
	 * $cleaner is kept like the twin keeps it; findSymbols() cleans with
	 * the native scanner (PhpFileCleaner is final and shadowed too) */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *cleaner;
		if (!zp::parse<zp::Obj>(execute_data, cleaner)) RETURN_THROWS();
		zval *slot = OBJ_PROP_NUM(Z_OBJ_P(ZEND_THIS), slots::cleaner);
		zval previous;
		ZVAL_COPY_VALUE(&previous, slot);
		ZVAL_COPY(slot, cleaner);
		Z_PROP_FLAG_P(slot) = 0;
		zval_ptr_dtor(&previous);
	});

	cls.method(sigs::findSymbols, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *files;
		bool supportsEnums;
		if (!zp::parse<zp::Ht, zp::Bool>(execute_data, files, supportsEnums)) RETURN_THROWS();

		phpstanturbo::SymbolFinderInFiles finder;
		zv::Val result = finder.findSymbols(files, supportsEnums);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.shadow(&pt_ce_symbol_finder);
}

/* }}} */
