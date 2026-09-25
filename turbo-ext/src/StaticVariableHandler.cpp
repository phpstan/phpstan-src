/*
 * PHPStanTurbo\StaticVariableHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\StaticVariableHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h).
 *
 * ImpurePoint, ExpressionResult, VariableFlow, MixedType, TrinaryLogic,
 * MutatingScope, VarAnnotationProcessor, the contexts, the statement results
 * and NodeScopeResolver are called through their direct entries.
 */

#include "support.h"
#include "generated/StaticVariableHandler.h"

namespace slots = ptdecl::StaticVariableHandler::slot;
namespace sigs = ptdecl::StaticVariableHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_static_variable_handler = nullptr;

namespace {

pt_property_site pt_svh_vars_site;
pt_property_site pt_svh_static_var_var_site;
pt_property_site pt_svh_static_var_default_site;
pt_property_site pt_svh_variable_name_site;

/* the impure point's literals, permanent interned strings (module startup) */
zend_string *pt_svh_static = nullptr;
zend_string *pt_svh_static_variable = nullptr;

/* $impurePoints = array_merge($impurePoints, $result->getImpurePoints()); false = pending exception */
[[nodiscard]] bool mergeImpurePoints(zv::Arr &impurePoints, zval *result)
{
	zv::Val hold;
	zval *resultImpurePoints = pt_expression_result_impure_points(result, hold);
	return resultImpurePoints != NULL && pt_callable_array_merge_into(impurePoints, resultImpurePoints);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\StaticVariableHandler; UNDEF =
 * pending exception. */
class StaticVariableHandler
{
public:
	explicit StaticVariableHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *varAnnotationProcessor)
	{
		zv::ObjRef(self).propAtWrite(slots::varAnnotationProcessor, zv::Val::copyOf(zv::Ref(varAnnotationProcessor)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_STATIC_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zv::Val scopeHold = zv::Val::copyOf(zv::Ref(scope));
		zv::Arr impurePoints = zv::Arr::create(1);
		{
			zv::Val impurePoint = pt_impure_point_new(scope, stmt, pt_svh_static, pt_svh_static_variable, true);
			if (UNEXPECTED(impurePoint.isUndef())) return zv::Val();
			impurePoints.push(std::move(impurePoint));
		}

		zv::Arr vars = zv::Arr::empty();
		zv::Arr variableFlows = zv::Arr::empty();
		zval *stmtVars = ptsh::readNodeProperty(pt_svh_vars_site, stmt, PT_LC("vars"));
		if (UNEXPECTED(stmtVars == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(stmtVars) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(stmtVars));
			if (UNEXPECTED(EG(exception))) return zv::Val();
		} else {
			/* foreach iterates the array it started with */
			zv::Val iterated = zv::Val::copyOf(zv::Ref(stmtVars));
			for (auto entry : zv::ArrRef(iterated.raw())) {
				if (UNEXPECTED(!processVar(nodeScopeResolver, stmt, entry.value().deref().raw(), scopeHold, storage, nodeCallback, context, impurePoints, vars, variableFlows))) return zv::Val();
			}
		}

		zv::Val annotatedScope = pt_var_annotation_processor_process_var_annotation(OBJ_PROP_NUM(self, slots::varAnnotationProcessor), scopeHold.raw(), vars.raw(), stmt, NULL);
		if (UNEXPECTED(annotatedScope.isUndef())) return zv::Val();

		zv::Val variableFlow = pt_variable_flow_sequence_list(variableFlows.table());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return pt_internal_statement_result_new(annotatedScope.raw(), false, false, &emptyArray, &emptyArray, impurePoints.raw(), NULL, variableFlow.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return StaticVariableHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* ExpressionContext::createDeep($context->shouldResolveTemplateArguments()) */
	static zv::Val deepContext(zval *context)
	{
		bool resolveTemplateArguments;
		if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
		return pt_expression_context_create_deep(resolveTemplateArguments);
	}

	/* the loop body over one static variable; false = pending exception */
	[[nodiscard]] static bool processVar(zval *nodeScopeResolver, zval *stmt, zval *staticVar, zv::Val &scope, zval *storage, zval *nodeCallback, zval *context, zv::Arr &impurePoints, zv::Arr &vars, zv::Arr &variableFlows)
	{
		if (UNEXPECTED(Z_TYPE_P(staticVar) != IS_OBJECT)) {
			zend_error(E_WARNING, "Attempt to read property \"var\" on %s", zend_zval_value_name(staticVar));
			if (UNEXPECTED(EG(exception))) return false;
			zend_error(E_WARNING, "Attempt to read property \"name\" on null");
			if (UNEXPECTED(EG(exception))) return false;
			pt_throw_should_not_happen();
			return false;
		}
		zval *var = ptsh::readNodeProperty(pt_svh_static_var_var_site, staticVar, PT_LC("var"));
		if (UNEXPECTED(var == NULL)) return false;
		zval *name;
		if (UNEXPECTED(Z_TYPE_P(var) != IS_OBJECT)) {
			zend_error(E_WARNING, "Attempt to read property \"name\" on %s", zend_zval_value_name(var));
			if (UNEXPECTED(EG(exception))) return false;
			name = &EG(uninitialized_zval);
		} else {
			name = ptsh::readNodeProperty(pt_svh_variable_name_site, var, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return false;
		}
		if (Z_TYPE_P(name) != IS_STRING) {
			pt_throw_should_not_happen();
			return false;
		}

		zval *defaultValue = ptsh::readNodeProperty(pt_svh_static_var_default_site, staticVar, PT_LC("default"));
		if (UNEXPECTED(defaultValue == NULL)) return false;
		if (Z_TYPE_P(defaultValue) != IS_NULL) {
			zv::Val defaultHold = zv::Val::copyOf(zv::Ref(defaultValue));
			zv::Val expressionContext = deepContext(context);
			if (UNEXPECTED(expressionContext.isUndef())) return false;
			zv::Val defaultExprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, defaultHold.raw(), scope.raw(), storage, nodeCallback, expressionContext.raw());
			if (UNEXPECTED(defaultExprResult.isUndef())) return false;
			zv::Val variableFlow = pt_expression_result_variable_flow(defaultExprResult.raw());
			if (UNEXPECTED(variableFlow.isUndef())) return false;
			variableFlows.push(std::move(variableFlow));
			if (UNEXPECTED(!mergeImpurePoints(impurePoints, defaultExprResult.raw()))) return false;
		}

		var = ptsh::readNodeProperty(pt_svh_static_var_var_site, staticVar, PT_LC("var"));
		if (UNEXPECTED(var == NULL)) return false;
		zv::Val varHold = zv::Val::copyOf(zv::Ref(var));
		if (UNEXPECTED(Z_TYPE_P(varHold.raw()) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::enterExpressionAssign(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(varHold.raw()));
			return false;
		}
		zv::Val assignScope = pt_mutating_scope_enter_expression_assign(Z_OBJ_P(scope.raw()), Z_OBJ_P(varHold.raw()), true);
		if (UNEXPECTED(assignScope.isUndef())) return false;
		scope = std::move(assignScope);
		{
			zval *varName = ptsh::readNodeProperty(pt_svh_variable_name_site, varHold.raw(), PT_LC("name"));
			if (UNEXPECTED(varName == NULL)) return false;
			if (UNEXPECTED(Z_TYPE_P(varName) != IS_STRING)) {
				zend_type_error("PHPStan\\Analyser\\VariableFlow::escape(): Argument #1 ($name) must be of type string, %s given", zend_zval_value_name(varName));
				return false;
			}
			zv::Val escape = pt_variable_flow_escape(Z_STR_P(varName));
			if (UNEXPECTED(escape.isUndef())) return false;
			variableFlows.push(std::move(escape));
		}
		{
			zv::Val expressionContext = deepContext(context);
			if (UNEXPECTED(expressionContext.isUndef())) return false;
			zv::Val varResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, varHold.raw(), scope.raw(), storage, nodeCallback, expressionContext.raw());
			if (UNEXPECTED(varResult.isUndef())) return false;
			if (UNEXPECTED(!mergeImpurePoints(impurePoints, varResult.raw()))) return false;
		}
		zv::Val exitScope = pt_mutating_scope_exit_expression_assign(Z_OBJ_P(scope.raw()), Z_OBJ_P(varHold.raw()));
		if (UNEXPECTED(exitScope.isUndef())) return false;
		scope = std::move(exitScope);

		zval *varName = ptsh::readNodeProperty(pt_svh_variable_name_site, varHold.raw(), PT_LC("name"));
		if (UNEXPECTED(varName == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(varName) != IS_STRING)) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::assignVariable(): Argument #1 ($variableName) must be of type string, %s given", zend_zval_value_name(varName));
			return false;
		}
		zv::Val nameHold = zv::Val::copyOf(zv::Ref(varName));
		zval mixed;
		if (UNEXPECTED(!pt_mixed_type_new(&mixed))) return false;
		zv::Val type = zv::Val::adopt(mixed);
		zval nativeMixed;
		if (UNEXPECTED(!pt_mixed_type_new(&nativeMixed))) return false;
		zv::Val nativeType = zv::Val::adopt(nativeMixed);
		zv::Val assigned = pt_mutating_scope_assign_variable(Z_OBJ_P(scope.raw()), Z_STR_P(nameHold.raw()), type.raw(), nativeType.raw(), pt_trinary_singleton(PT_TRI_YES));
		if (UNEXPECTED(assigned.isUndef())) return false;
		scope = std::move(assigned);

		zval *pushedName = ptsh::readNodeProperty(pt_svh_variable_name_site, varHold.raw(), PT_LC("name"));
		if (UNEXPECTED(pushedName == NULL)) return false;
		vars.push(zv::Ref(pushedName));
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::StaticVariableHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_static_variable_handler)
{
	pt_svh_static = zend_string_init_interned(PT_LC("static"), 1);
	pt_svh_static_variable = zend_string_init_interned(PT_LC("static variable"), 1);

	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\StaticVariableHandler");
	ptdecl::StaticVariableHandler::declareClass(cls);
	ptdecl::StaticVariableHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *varAnnotationProcessor;
		if (!zp::parse<zp::Obj>(execute_data, varAnnotationProcessor)) RETURN_THROWS();
		StaticVariableHandler(Z_OBJ_P(ZEND_THIS)).construct(varAnnotationProcessor);
	});

	cls.method<&StaticVariableHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(StaticVariableHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_static_variable_handler);
	pt_stmt_handler_entry_register(&pt_ce_static_variable_handler, &StaticVariableHandler::processStmtEntry);
}

/* }}} */
