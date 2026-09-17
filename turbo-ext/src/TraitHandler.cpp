/*
 * PHPStanTurbo\TraitHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\TraitHandler.
 *
 * A DI service (#[AutowiredService]) without constructor. processStmt() is
 * registered as the class's statement-handler entry (Engine.h); MutatingScope
 * and the statement results are called through their direct entries, the
 * php-parser Name through the site below.
 */

#include "support.h"
#include "generated/TraitHandler.h"

namespace sigs = ptdecl::TraitHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_trait_handler = nullptr;

namespace {

pt_property_site pt_th_namespaced_name_site;
pt_property_site pt_th_name_site;

/* the 'trait_exists' literal (module startup) */
zend_string *pt_th_trait_exists = nullptr;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\TraitHandler; UNDEF = pending
 * exception. */
class TraitHandler
{
public:
	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *stmt, bool &out)
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_TRAIT_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	static zv::Val processStmt(zval *stmt, zval *scope)
	{
		// declaring the trait defines it in global state,
		// so a negative trait_exists() narrowing that may refer to that trait must be forgotten
		zval *name = pt_property_cached(pt_th_namespaced_name_site, Z_OBJ_P(stmt), PT_LC("namespacedName"));
		if (name != NULL) ZVAL_DEREF(name);
		if (name == NULL || Z_TYPE_P(name) == IS_UNDEF || Z_TYPE_P(name) == IS_NULL) {
			name = ptsh::readNodeProperty(pt_th_name_site, stmt, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return zv::Val();
		}
		zv::Val declaredSymbolName = zv::Val::null();
		bool error = false;
		if (ptsh::isInstanceOf(name, PT_CLASS_NAME, error)) {
			zv::Val nameHold = zv::Val::copyOf(zv::Ref(name));
			declaredSymbolName = pt_name_node_to_string(nameHold.raw());
			if (UNEXPECTED(declaredSymbolName.isUndef())) return zv::Val();
		}
		if (UNEXPECTED(error)) return zv::Val();
		zv::Arr functionNames = zv::Arr::create(1);
		functionNames.push(zv::Val::string(pt_th_trait_exists));
		zv::Val invalidatedScope = pt_mutating_scope_invalidate_existence_check_expressions(Z_OBJ_P(scope), functionNames.raw(), declaredSymbolName.raw());
		if (UNEXPECTED(invalidatedScope.isUndef())) return zv::Val();

		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return pt_internal_statement_result_new(invalidatedScope.raw(), false, false, &emptyArray, &emptyArray, &emptyArray);
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

using phpstanturbo::TraitHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_trait_handler()
{
	pt_th_trait_exists = zend_string_init_interned(PT_LC("trait_exists"), 1);

	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\TraitHandler");
	ptdecl::TraitHandler::declareClass(cls);
	ptdecl::TraitHandler::declareProperties(cls);

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *stmt;
		if (!zp::parse<zp::Obj>(execute_data, stmt)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!TraitHandler::supports(stmt, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(TraitHandler::processStmt(stmt, scope));
	});

	cls.shadow(&pt_ce_trait_handler);
	pt_stmt_handler_entry_register(&pt_ce_trait_handler, &TraitHandler::processStmtEntry);
}

/* }}} */
