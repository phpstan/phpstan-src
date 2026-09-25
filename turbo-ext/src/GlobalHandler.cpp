/*
 * PHPStanTurbo\GlobalHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\GlobalHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h).
 *
 * ImpurePoint, ExpressionResult, VariableFlow(Builder), StaticTypeFactory,
 * MixedType, TrinaryLogic, MutatingScope, VarAnnotationProcessor, the
 * contexts, the statement results and NodeScopeResolver are called through
 * their direct entries.
 */

#include "support.h"
#include "generated/GlobalHandler.h"

namespace slots = ptdecl::GlobalHandler::slot;
namespace sigs = ptdecl::GlobalHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_global_handler = nullptr;

namespace {

pt_property_site pt_gh_vars_site;
pt_property_site pt_gh_variable_name_site;

/* the impure point's literals, permanent interned strings (module startup) */
zend_string *pt_gh_global = nullptr;
zend_string *pt_gh_global_variable = nullptr;

/* Mirrors getGlobalVariableType(). */
zv::Val globalVariableType(zend_string *variableName)
{
	if (zend_string_equals_literal(variableName, "argc")) return pt_static_type_factory_argc();
	if (zend_string_equals_literal(variableName, "argv")) return pt_static_type_factory_argv();
	zval mixed;
	if (UNEXPECTED(!pt_mixed_type_new(&mixed))) return zv::Val();
	return zv::Val::adopt(mixed);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\GlobalHandler; UNDEF = pending
 * exception. */
class GlobalHandler
{
public:
	explicit GlobalHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *varAnnotationProcessor)
	{
		zv::ObjRef(self).propAtWrite(slots::varAnnotationProcessor, zv::Val::copyOf(zv::Ref(varAnnotationProcessor)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_GLOBAL_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zv::Val scopeHold = zv::Val::copyOf(zv::Ref(scope));
		zv::Arr impurePoints = zv::Arr::create(1);
		{
			zv::Val impurePoint = pt_impure_point_new(scope, stmt, pt_gh_global, pt_gh_global_variable, true);
			if (UNEXPECTED(impurePoint.isUndef())) return zv::Val();
			impurePoints.push(std::move(impurePoint));
		}
		zv::Arr vars = zv::Arr::empty();
		zv::Arr variableFlows = zv::Arr::empty();
		zval *stmtVars = ptsh::readNodeProperty(pt_gh_vars_site, stmt, PT_LC("vars"));
		if (UNEXPECTED(stmtVars == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(stmtVars) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(stmtVars));
			if (UNEXPECTED(EG(exception))) return zv::Val();
		} else {
			/* foreach iterates the array it started with */
			zv::Val iterated = zv::Val::copyOf(zv::Ref(stmtVars));
			bool error = false;
			for (auto entry : zv::ArrRef(iterated.raw())) {
				zval *var = entry.value().deref().raw();
				if (!ptsh::isInstanceOf(var, PT_CLASS_VARIABLE, error)) {
					if (!error) pt_throw_should_not_happen();
					return zv::Val();
				}
				zv::Val allowedScope = pt_node_scope_resolver_look_for_set_allowed_undefined_expressions(nodeScopeResolver, scopeHold.raw(), var);
				if (UNEXPECTED(allowedScope.isUndef())) return zv::Val();
				scopeHold = std::move(allowedScope);
				bool resolveTemplateArguments;
				if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
				zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
				if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
				zv::Val varResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, var, scopeHold.raw(), storage, nodeCallback, expressionContext.raw());
				if (UNEXPECTED(varResult.isUndef())) return zv::Val();
				{
					zv::Val escapeRoot = pt_variable_flow_builder_escape_root(var);
					if (UNEXPECTED(escapeRoot.isUndef())) return zv::Val();
					zv::Val targetRead = pt_variable_flow_builder_target_read(var, storage, false, NULL);
					if (UNEXPECTED(targetRead.isUndef())) return zv::Val();
					zval flows[2];
					ZVAL_COPY_VALUE(&flows[0], escapeRoot.raw());
					ZVAL_COPY_VALUE(&flows[1], targetRead.raw());
					zv::Val flow = pt_variable_flow_sequence(2, flows);
					if (UNEXPECTED(flow.isUndef())) return zv::Val();
					variableFlows.push(std::move(flow));
				}
				{
					zv::Val hold;
					zval *resultImpurePoints = pt_expression_result_impure_points(varResult.raw(), hold);
					if (UNEXPECTED(resultImpurePoints == NULL || !pt_callable_array_merge_into(impurePoints, resultImpurePoints))) return zv::Val();
				}
				zv::Val unsetScope = pt_node_scope_resolver_look_for_unset_allowed_undefined_expressions(nodeScopeResolver, scopeHold.raw(), var);
				if (UNEXPECTED(unsetScope.isUndef())) return zv::Val();
				scopeHold = std::move(unsetScope);

				zval *name = ptsh::readNodeProperty(pt_gh_variable_name_site, var, PT_LC("name"));
				if (UNEXPECTED(name == NULL)) return zv::Val();
				if (Z_TYPE_P(name) != IS_STRING) continue;
				zv::Val nameHold = zv::Val::copyOf(zv::Ref(name));
				zv::Val varType = globalVariableType(Z_STR_P(nameHold.raw()));
				if (UNEXPECTED(varType.isUndef())) return zv::Val();
				zv::Val assigned = pt_mutating_scope_assign_variable(Z_OBJ_P(scopeHold.raw()), Z_STR_P(nameHold.raw()), varType.raw(), varType.raw(), pt_trinary_singleton(PT_TRI_YES));
				if (UNEXPECTED(assigned.isUndef())) return zv::Val();
				scopeHold = std::move(assigned);
				vars.push(std::move(nameHold));
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
		return GlobalHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::GlobalHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_global_handler)
{
	pt_gh_global = zend_string_init_interned(PT_LC("global"), 1);
	pt_gh_global_variable = zend_string_init_interned(PT_LC("global variable"), 1);

	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\GlobalHandler");
	ptdecl::GlobalHandler::declareClass(cls);
	ptdecl::GlobalHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *varAnnotationProcessor;
		if (!zp::parse<zp::Obj>(execute_data, varAnnotationProcessor)) RETURN_THROWS();
		GlobalHandler(Z_OBJ_P(ZEND_THIS)).construct(varAnnotationProcessor);
	});

	cls.method<&GlobalHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(GlobalHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_global_handler);
	pt_stmt_handler_entry_register(&pt_ce_global_handler, &GlobalHandler::processStmtEntry);
}

/* }}} */
