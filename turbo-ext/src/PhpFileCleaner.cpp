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
#include "zv.h"
#include "SymbolScan.h"

static zend_class_entry *pt_ce_php_file_cleaner = nullptr;

/* {{{ registration */

#include "reg.h"

void pt_register_php_file_cleaner()
{
	reg::Class cls("PHPStanTurbo\\PhpFileCleaner");

	cls.method("__construct", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method("clean", reg::Public, 2, { reg::stringArg("contents"), reg::longArg("maxMatches") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *contents;
		zend_long maxMatches;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_STR(contents)
			Z_PARAM_LONG(maxMatches)
		ZEND_PARSE_PARAMETERS_END();

		phpstanturbo::PhpFileCleaner cleaner(ZSTR_VAL(contents), ZSTR_LEN(contents));
		std::string cleaned;
		cleaner.clean(maxMatches, cleaned);
		RETURN_STRINGL(cleaned.data(), cleaned.size());
	});

	/* not final: a PHP stub subclass may extend this class */
	pt_ce_php_file_cleaner = cls.register_();
}

/* }}} */
