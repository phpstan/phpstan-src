/*
 * PHPStanTurbo\EchoHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\EchoHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h).
 *
 * ExpressionResult, the contexts, ImpurePoint, VariableFlow, the statement
 * results, ImplicitToStringCallHelper and NodeScopeResolver are called
 * through their direct entries.
 */

#include "support.h"
#include "generated/EchoHandler.h"

namespace slots = ptdecl::EchoHandler::slot;
namespace sigs = ptdecl::EchoHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_echo_handler = nullptr;

namespace {

/* $implicitToStringCallHelper->processImplicitToStringCall($expr, $scope,
 * $exprResult) */
zv::Val processImplicitToStringCall(zval *implicitToStringCallHelper, zval *expr, zval *scope, zval *exprResult)
{
	return pt_implicit_to_string_call_helper_process_implicit_to_string_call(implicitToStringCallHelper, expr, scope, exprResult);
}

pt_property_site pt_eh_exprs_site;

/* the impure point's 'echo' literal, a permanent interned string (module
 * startup) */
zend_string *pt_eh_echo = nullptr;

/* $into = array_merge($into, $more); false = pending exception */
[[nodiscard]] bool mergeInto(zv::Arr &into, zval *more)
{
	return pt_callable_array_merge_into(into, more);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\EchoHandler; UNDEF = pending
 * exception. */
class EchoHandler
{
public:
	explicit EchoHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *implicitToStringCallHelper)
	{
		zv::ObjRef(self).propAtWrite(slots::implicitToStringCallHelper, zv::Val::copyOf(zv::Ref(implicitToStringCallHelper)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		zend_class_entry *echoCe = pt_class(PT_CLASS_ECHO_STMT);
		if (UNEXPECTED(echoCe == NULL)) return false;
		out = instanceof_function(Z_OBJCE_P(stmt), echoCe);
		return true;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *entryScope = scope;
		zv::Val scopeHold;
		bool hasYield = false;
		zv::Arr throwPoints = zv::Arr::empty();
		zv::Arr impurePoints = zv::Arr::empty();
		bool isAlwaysTerminating = false;
		zval *exprs = ptsh::readNodeProperty(pt_eh_exprs_site, stmt, PT_LC("exprs"));
		if (UNEXPECTED(exprs == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(exprs) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(exprs));
			if (UNEXPECTED(EG(exception))) return zv::Val();
			exprs = NULL;
		}
		/* foreach iterates the array it started with */
		zv::Arr iterated = exprs != NULL ? zv::Arr::copyOfTable(Z_ARRVAL_P(exprs)) : zv::Arr::empty();
		zv::Arr variableFlows = zv::Arr::create(zend_hash_num_elements(iterated.table()));
		for (auto entry : zv::TableRef(iterated.table())) {
			zval *echoExpr = entry.value().deref().raw();
			bool resolveTemplateArguments;
			if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
			zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
			if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
			zv::Val result = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, echoExpr, scope, storage, nodeCallback, expressionContext.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zv::Val variableFlow = pt_expression_result_variable_flow(result.raw());
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
			variableFlows.push(std::move(variableFlow));
			{
				zv::Val hold;
				zval *resultThrowPoints = pt_expression_result_throw_points(result.raw(), hold);
				if (UNEXPECTED(resultThrowPoints == NULL || !mergeInto(throwPoints, resultThrowPoints))) return zv::Val();
			}
			{
				zv::Val hold;
				zval *resultImpurePoints = pt_expression_result_impure_points(result.raw(), hold);
				if (UNEXPECTED(resultImpurePoints == NULL || !mergeInto(impurePoints, resultImpurePoints))) return zv::Val();
			}
			zv::Val toStringResult = processImplicitToStringCall(OBJ_PROP_NUM(self, slots::implicitToStringCallHelper), echoExpr, scope, result.raw());
			if (UNEXPECTED(toStringResult.isUndef())) return zv::Val();
			{
				zv::Val hold;
				zval *toStringThrowPoints = pt_expression_result_throw_points(toStringResult.raw(), hold);
				if (UNEXPECTED(toStringThrowPoints == NULL || !mergeInto(throwPoints, toStringThrowPoints))) return zv::Val();
			}
			{
				zv::Val hold;
				zval *toStringImpurePoints = pt_expression_result_impure_points(toStringResult.raw(), hold);
				if (UNEXPECTED(toStringImpurePoints == NULL || !mergeInto(impurePoints, toStringImpurePoints))) return zv::Val();
			}
			zv::Val nextScopeHold;
			zval *nextScope = pt_expression_result_scope(result.raw(), nextScopeHold);
			if (UNEXPECTED(nextScope == NULL)) return zv::Val();
			scopeHold = zv::Val::copyOf(zv::Ref(nextScope));
			scope = scopeHold.raw();
			if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(result.raw(), hasYield))) return zv::Val();
			if (!isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(result.raw(), isAlwaysTerminating))) return zv::Val();
		}

		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, stmt, entryScope, storage))) return zv::Val();

		zv::Val impurePoint = pt_impure_point_new(scope, stmt, pt_eh_echo, pt_eh_echo, true);
		if (UNEXPECTED(impurePoint.isUndef())) return zv::Val();
		impurePoints.push(std::move(impurePoint));
		zv::Val variableFlow = pt_variable_flow_sequence_list(variableFlows.table());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		zv::Arr exitPoints = zv::Arr::empty();
		return pt_internal_statement_result_new(scope, hasYield, isAlwaysTerminating, exitPoints.raw(), throwPoints.raw(), impurePoints.raw(), NULL, variableFlow.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return EchoHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::EchoHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_echo_handler)
{
	pt_eh_echo = zend_string_init_interned(PT_LC("echo"), 1);

	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\EchoHandler");
	ptdecl::EchoHandler::declareClass(cls);
	ptdecl::EchoHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *implicitToStringCallHelper;
		if (!zp::parse<zp::Obj>(execute_data, implicitToStringCallHelper)) RETURN_THROWS();
		EchoHandler(Z_OBJ_P(ZEND_THIS)).construct(implicitToStringCallHelper);
	});

	cls.method<&EchoHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(EchoHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_echo_handler);
	pt_stmt_handler_entry_register(&pt_ce_echo_handler, &EchoHandler::processStmtEntry);
}

/* }}} */
