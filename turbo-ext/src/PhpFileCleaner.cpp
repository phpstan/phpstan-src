/*
 * PHPStanTurbo\PhpFileCleaner — native implementation of
 * PHPStan\Reflection\BetterReflection\SourceLocator\PhpFileCleaner.
 *
 * The PHP twin walks the file byte by byte, appending to a growing string;
 * it is the dominant cost of the optimized locators' directory symbol scan.
 * The implementation is a transliteration, not a rewrite (see SymbolScan.h,
 * which hosts it so SymbolFinderInFiles can reuse it): the output must be
 * byte-identical to the twin's for every input, quirks included, because the
 * cleaned text is fed to a regex whose captures become the symbol index.
 */

#include "support.h"
#include "generated/PhpFileCleaner.h"

namespace sigs = ptdecl::PhpFileCleaner::sig;
#include "zv.h"
#include "SymbolScan.h"

static zend_class_entry *pt_ce_php_file_cleaner = nullptr;

/* {{{ registration */

#include "reg.h"

void pt_register_php_file_cleaner()
{
	reg::Class cls("PHPStan\\Reflection\\BetterReflection\\SourceLocator\\PhpFileCleaner");
	ptdecl::PhpFileCleaner::declareClass(cls);
	/* the twin's scanner state; the native clean() keeps its own in the C++
	 * scanner, so the properties stay at their defaults ($rejectChars,
	 * which the twin's constructor computes, uninitialized) */
	ptdecl::PhpFileCleaner::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method(sigs::clean, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *contents;
		zend_long maxMatches;
		if (!zp::parse<zp::Str, zp::Long>(execute_data, contents, maxMatches)) RETURN_THROWS();

		phpstanturbo::PhpFileCleaner cleaner(ZSTR_VAL(contents), ZSTR_LEN(contents));
		std::string cleaned;
		cleaner.clean(maxMatches, cleaned);
		RETURN_STRINGL(cleaned.data(), cleaned.size());
	});

	cls.shadow(&pt_ce_php_file_cleaner);
}

/* }}} */
