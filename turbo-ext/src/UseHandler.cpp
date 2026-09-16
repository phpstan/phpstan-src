/*
 * PHPStanTurbo\UseHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\UseHandler.
 *
 * A DI service (#[AutowiredService]) without constructor. processStmt() is
 * registered as the class's statement-handler entry (Engine.h); the
 * statement results and NodeScopeResolver are called through their direct
 * entries.
 */

#include "support.h"
#include "generated/UseHandler.h"

namespace sigs = ptdecl::UseHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_use_handler = nullptr;

namespace {

pt_property_site pt_uh_uses_site;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\UseHandler; UNDEF = pending
 * exception. */
class UseHandler
{
public:
	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *stmt, bool &out)
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_USE_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	static zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		(void) context;
		zval *uses = ptsh::readNodeProperty(pt_uh_uses_site, stmt, PT_LC("uses"));
		if (UNEXPECTED(uses == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(uses) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(uses));
			if (UNEXPECTED(EG(exception))) return zv::Val();
		} else {
			/* foreach iterates the array it started with */
			zv::Val iterated = zv::Val::copyOf(zv::Ref(uses));
			for (auto entry : zv::ArrRef(iterated.raw())) {
				zval *use = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(use) != IS_OBJECT)) {
					zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::callNodeCallback(): Argument #2 ($node) must be of type PhpParser\\Node, %s given", zend_zval_value_name(use));
					return zv::Val();
				}
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, use, scope, storage))) return zv::Val();
			}
		}

		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return pt_internal_statement_result_new(scope, false, false, &emptyArray, &emptyArray, &emptyArray);
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		(void) handler;
		return processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}
};

} // namespace phpstanturbo

using phpstanturbo::UseHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_use_handler()
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\UseHandler");
	ptdecl::UseHandler::declareClass(cls);
	ptdecl::UseHandler::declareProperties(cls);

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *stmt;
		if (!zp::parse<zp::Obj>(execute_data, stmt)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!UseHandler::supports(stmt, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(UseHandler::processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_use_handler);
	pt_stmt_handler_entry_register(&pt_ce_use_handler, &UseHandler::processStmtEntry);
}

/* }}} */
