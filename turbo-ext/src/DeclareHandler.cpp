/*
 * PHPStanTurbo\DeclareHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\DeclareHandler.
 *
 * A DI service (#[AutowiredService]) without constructor. processStmt() is
 * registered as the class's statement-handler entry (Engine.h);
 * MutatingScope, the contexts, the statement results and NodeScopeResolver
 * are called through their direct entries.
 */

#include "support.h"
#include "generated/DeclareHandler.h"

namespace sigs = ptdecl::DeclareHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_declare_handler = nullptr;

namespace {

pt_property_site pt_dh_declares_site;
pt_property_site pt_dh_declare_value_site;
pt_property_site pt_dh_declare_key_site;
pt_property_site pt_dh_identifier_name_site;
pt_property_site pt_dh_int_value_site;
pt_property_site pt_dh_stmts_site;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\DeclareHandler; UNDEF = pending
 * exception. */
class DeclareHandler
{
public:
	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *stmt, bool &out)
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_DECLARE_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	static zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		zv::Val scopeHold;
		zval *declares = ptsh::readNodeProperty(pt_dh_declares_site, stmt, PT_LC("declares"));
		if (UNEXPECTED(declares == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(declares) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(declares));
			if (UNEXPECTED(EG(exception))) return zv::Val();
		} else {
			/* foreach iterates the array it started with */
			zv::Val iterated = zv::Val::copyOf(zv::Ref(declares));
			for (auto entry : zv::ArrRef(iterated.raw())) {
				zval *declare = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(declare) != IS_OBJECT)) {
					zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::callNodeCallback(): Argument #2 ($node) must be of type PhpParser\\Node, %s given", zend_zval_value_name(declare));
					return zv::Val();
				}
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, declare, scope, storage))) return zv::Val();
				// the value is a constant scalar - process it so its result is stored
				// before the callback fires on it, like every other expression node
				zval *value = ptsh::readNodeProperty(pt_dh_declare_value_site, declare, PT_LC("value"));
				if (UNEXPECTED(value == NULL)) return zv::Val();
				zv::Val valueHold = zv::Val::copyOf(zv::Ref(value));
				bool resolveTemplateArguments;
				if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
				zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
				if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
				zv::Val valueResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, valueHold.raw(), scope, storage, nodeCallback, expressionContext.raw());
				if (UNEXPECTED(valueResult.isUndef())) return zv::Val();

				bool strictTypes;
				if (UNEXPECTED(!isStrictTypesOne(declare, strictTypes))) return zv::Val();
				if (!strictTypes) continue;
				zv::Val strictScope = pt_mutating_scope_enter_declare_strict_types(Z_OBJ_P(scope));
				if (UNEXPECTED(strictScope.isUndef())) return zv::Val();
				scopeHold = std::move(strictScope);
				scope = scopeHold.raw();
			}
		}

		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		zval *stmts = ptsh::readNodeProperty(pt_dh_stmts_site, stmt, PT_LC("stmts"));
		if (UNEXPECTED(stmts == NULL)) return zv::Val();
		if (Z_TYPE_P(stmts) == IS_NULL) {
			return pt_internal_statement_result_new(scope, false, false, &emptyArray, &emptyArray, &emptyArray);
		}
		zv::Val stmtsHold = zv::Val::copyOf(zv::Ref(stmts));
		zv::Val result = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, stmtsHold.raw(), scope, storage, nodeCallback, context);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zv::Val resultScopeHold;
		zval *resultScope = pt_internal_statement_result_scope(result.raw(), resultScopeHold);
		if (UNEXPECTED(resultScope == NULL)) return zv::Val();
		bool hasYield;
		if (UNEXPECTED(!pt_internal_statement_result_has_yield(result.raw(), hasYield))) return zv::Val();
		zv::Val throwPointsHold;
		zval *throwPoints = pt_internal_statement_result_throw_points(result.raw(), throwPointsHold);
		if (UNEXPECTED(throwPoints == NULL)) return zv::Val();
		zv::Val impurePointsHold;
		zval *impurePoints = pt_internal_statement_result_impure_points(result.raw(), impurePointsHold);
		if (UNEXPECTED(impurePoints == NULL)) return zv::Val();
		bool alwaysTerminating;
		if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(result.raw(), alwaysTerminating))) return zv::Val();
		zv::Val exitPointsHold;
		zval *exitPoints = pt_internal_statement_result_exit_points(result.raw(), exitPointsHold);
		if (UNEXPECTED(exitPoints == NULL)) return zv::Val();
		return pt_internal_statement_result_new(resultScope, hasYield, alwaysTerminating, exitPoints, throwPoints, impurePoints);
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		(void) handler;
		return processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	/* $declare->key->name === 'strict_types' && $declare->value instanceof Int_
	 * && $declare->value->value === 1; false = pending exception */
	[[nodiscard]] static bool isStrictTypesOne(zval *declare, bool &out)
	{
		out = false;
		zval *key = ptsh::readNodeProperty(pt_dh_declare_key_site, declare, PT_LC("key"));
		if (UNEXPECTED(key == NULL)) return false;
		zval *keyName;
		if (UNEXPECTED(Z_TYPE_P(key) != IS_OBJECT)) {
			zend_error(E_WARNING, "Attempt to read property \"name\" on %s", zend_zval_value_name(key));
			if (UNEXPECTED(EG(exception))) return false;
			keyName = &EG(uninitialized_zval);
		} else {
			keyName = ptsh::readNodeProperty(pt_dh_identifier_name_site, key, PT_LC("name"));
			if (UNEXPECTED(keyName == NULL)) return false;
		}
		if (Z_TYPE_P(keyName) != IS_STRING || !zend_string_equals_literal(Z_STR_P(keyName), "strict_types")) return true;
		zval *value = ptsh::readNodeProperty(pt_dh_declare_value_site, declare, PT_LC("value"));
		if (UNEXPECTED(value == NULL)) return false;
		bool error = false;
		if (!ptsh::isInstanceOf(value, PT_CLASS_SCALAR_INT, error)) return !error;
		zval *intValue = ptsh::readNodeProperty(pt_dh_int_value_site, value, PT_LC("value"));
		if (UNEXPECTED(intValue == NULL)) return false;
		out = Z_TYPE_P(intValue) == IS_LONG && Z_LVAL_P(intValue) == 1;
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::DeclareHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_declare_handler()
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\DeclareHandler");
	ptdecl::DeclareHandler::declareClass(cls);
	ptdecl::DeclareHandler::declareProperties(cls);

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *stmt;
		if (!zp::parse<zp::Obj>(execute_data, stmt)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!DeclareHandler::supports(stmt, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(DeclareHandler::processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_declare_handler);
	pt_stmt_handler_entry_register(&pt_ce_declare_handler, &DeclareHandler::processStmtEntry);
}

/* }}} */
