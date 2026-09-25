/*
 * PHPStanTurbo\NamespaceHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\NamespaceHandler.
 *
 * A DI service (#[AutowiredService]) without constructor. processStmt() is
 * registered as the class's statement-handler entry (Engine.h); MutatingScope,
 * the statement results and NodeScopeResolver are called through their
 * direct entries, the php-parser Name through the site below.
 */

#include "support.h"
#include "generated/NamespaceHandler.h"

namespace sigs = ptdecl::NamespaceHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_namespace_handler = nullptr;

namespace {

pt_property_site pt_nh_name_site;
pt_property_site pt_nh_stmts_site;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\NamespaceHandler; UNDEF = pending
 * exception. */
class NamespaceHandler
{
public:
	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *stmt, bool &out)
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_NAMESPACE_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	static zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		zval *name = ptsh::readNodeProperty(pt_nh_name_site, stmt, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zv::Val namespaceName;
		if (Z_TYPE_P(name) != IS_NULL) {
			if (UNEXPECTED(Z_TYPE_P(name) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function toString() on %s", zend_zval_value_name(name));
				return zv::Val();
			}
			zv::Val nameHold = zv::Val::copyOf(zv::Ref(name));
			namespaceName = pt_name_node_to_string(nameHold.raw());
			if (UNEXPECTED(namespaceName.isUndef())) return zv::Val();
		} else {
			namespaceName = zv::Val::string(zend_empty_string);
		}
		zv::Val namespaceScope = pt_mutating_scope_enter_namespace(Z_OBJ_P(scope), namespaceName.raw());
		if (UNEXPECTED(namespaceScope.isUndef())) return zv::Val();

		zval *stmts = ptsh::readNodeProperty(pt_nh_stmts_site, stmt, PT_LC("stmts"));
		if (UNEXPECTED(stmts == NULL)) return zv::Val();
		zv::Val stmtsHold = zv::Val::copyOf(zv::Ref(stmts));
		zv::Val result = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, stmtsHold.raw(), namespaceScope.raw(), storage, nodeCallback, context);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zv::Val scopeHold;
		zval *resultScope = pt_internal_statement_result_scope(result.raw(), scopeHold);
		if (UNEXPECTED(resultScope == NULL)) return zv::Val();

		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return pt_internal_statement_result_new(resultScope, false, false, &emptyArray, &emptyArray, &emptyArray);
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		(void) handler;
		return processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}
};

} // namespace phpstanturbo

using phpstanturbo::NamespaceHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_namespace_handler)
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\NamespaceHandler");
	ptdecl::NamespaceHandler::declareClass(cls);
	ptdecl::NamespaceHandler::declareProperties(cls);

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *stmt;
		if (!zp::parse<zp::Obj>(execute_data, stmt)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!NamespaceHandler::supports(stmt, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(NamespaceHandler::processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_namespace_handler);
	pt_stmt_handler_entry_register(&pt_ce_namespace_handler, &NamespaceHandler::processStmtEntry);
}

/* }}} */
