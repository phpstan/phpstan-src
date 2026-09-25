/*
 * PHPStanTurbo\InlineHtmlHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\InlineHtmlHandler.
 *
 * A DI service (#[AutowiredService]) without constructor. processStmt() is
 * registered as the class's statement-handler entry (Engine.h); ImpurePoint
 * and the statement results are called through their direct entries.
 */

#include "support.h"
#include "generated/InlineHtmlHandler.h"

namespace sigs = ptdecl::InlineHtmlHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_inline_html_handler = nullptr;

namespace {

/* the impure point's literals, permanent interned strings (module startup) */
zend_string *pt_ihh_between_php_tags = nullptr;
zend_string *pt_ihh_description = nullptr;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\InlineHtmlHandler; UNDEF = pending
 * exception. */
class InlineHtmlHandler
{
public:
	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *stmt, bool &out)
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_INLINE_HTML_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	static zv::Val processStmt(zval *stmt, zval *scope)
	{
		zv::Val impurePoint = pt_impure_point_new(scope, stmt, pt_ihh_between_php_tags, pt_ihh_description, true);
		if (UNEXPECTED(impurePoint.isUndef())) return zv::Val();
		zv::Arr impurePoints = zv::Arr::create(1);
		impurePoints.push(std::move(impurePoint));
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return pt_internal_statement_result_new(scope, false, false, &emptyArray, &emptyArray, impurePoints.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		(void) handler;
		(void) nodeScopeResolver;
		(void) storage;
		(void) nodeCallback;
		(void) context;
		return processStmt(stmt, scope);
	}
};

} // namespace phpstanturbo

using phpstanturbo::InlineHtmlHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_inline_html_handler)
{
	pt_ihh_between_php_tags = zend_string_init_interned(PT_LC("betweenPhpTags"), 1);
	pt_ihh_description = zend_string_init_interned(PT_LC("output between PHP opening and closing tags"), 1);

	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\InlineHtmlHandler");
	ptdecl::InlineHtmlHandler::declareClass(cls);
	ptdecl::InlineHtmlHandler::declareProperties(cls);

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *stmt;
		if (!zp::parse<zp::Obj>(execute_data, stmt)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!InlineHtmlHandler::supports(stmt, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::processStmt, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		(void) nodeScopeResolver;
		(void) storage;
		(void) nodeCallback;
		(void) context;
		PT_RETURN_VAL(InlineHtmlHandler::processStmt(stmt, scope));
	});

	cls.shadow(&pt_ce_inline_html_handler);
	pt_stmt_handler_entry_register(&pt_ce_inline_html_handler, &InlineHtmlHandler::processStmtEntry);
}

/* }}} */
