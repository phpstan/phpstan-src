/*
 * PHPStanTurbo\ArrowFunctionHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\ArrowFunctionHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry, the static getVariableFlow() — which ClosureProcessor calls —
 * is exported as pt_arrow_function_handler_get_variable_flow(). The twin's
 * specifyTypesCallback is a native closure capturing $this and $expr.
 * ClosureProcessor, its ProcessArrowFunctionResult, ClosureTypeResolver,
 * DefaultNarrowingHelper, VariableFlow and ExpressionResult are called through
 * their direct entries.
 */

#include "support.h"
#include "generated/ArrowFunctionHandler.h"

namespace slots = ptdecl::ArrowFunctionHandler::slot;
namespace sigs = ptdecl::ArrowFunctionHandler::sig;
#include "ClosureSupport.h"

zend_class_entry *pt_ce_arrow_function_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\ArrowFunctionHandler; UNDEF = pending
 * exception. */
class ArrowFunctionHandler
{
public:
	explicit ArrowFunctionHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *closureTypeResolver, zval *expressionResultFactory, zval *defaultNarrowingHelper, zval *closureProcessor)
	{
		writeSlot(slots::closureTypeResolver, closureTypeResolver);
		writeSlot(slots::expressionResultFactory, expressionResultFactory);
		writeSlot(slots::defaultNarrowingHelper, defaultNarrowingHelper);
		writeSlot(slots::closureProcessor, closureProcessor);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptclosure::instanceOf(expr, PT_CLASS_ARROW_FUNCTION);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zv::Val passedToType = pt_expression_context_get_passed_to_type(context);
		if (UNEXPECTED(passedToType.isUndef())) return zv::Val();
		zv::Val nativePassedToType = pt_expression_context_get_native_passed_to_type(context);
		if (UNEXPECTED(nativePassedToType.isUndef())) return zv::Val();
		zv::Val arrowFunctionResult = pt_closure_processor_process_arrow_function_node(OBJ_PROP_NUM(self, slots::closureProcessor), nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, passedToType.raw(), nativePassedToType.raw(), context);
		if (UNEXPECTED(arrowFunctionResult.isUndef())) return zv::Val();
		zval *arrowResult = arrowFunctionResult.raw();
		zv::Val resultHold;
		zval *result = pt_process_arrow_function_result_expression_result(arrowResult, resultHold);
		if (UNEXPECTED(result == NULL)) return zv::Val();

		// A plain typeCallback recursing through getClosureType() would re-walk
		// the body each getType() ask before the cache populates and hang;
		// ExpressionResult excludes closures from its tracked-type early return.
		// Compute the ClosureType once here and store it as an eager value.
		//
		// Both flavours are built from the arrow function body the single walk in
		// processArrowFunctionNode() already covered, without a second walk.
		zv::Val arrowScopeHold;
		zval *arrowScope = pt_process_arrow_function_result_arrow_function_scope(arrowResult, arrowScopeHold);
		if (UNEXPECTED(arrowScope == NULL)) return zv::Val();
		/* the context is immutable: its passed-to types read above */
		zv::Val types[2];
		for (int native = 0; native < 2; native++) {
			zv::Val throwPointsHold, impurePointsHold, invalidateHold;
			zval *throwPoints = pt_process_arrow_function_result_closure_type_throw_points(arrowResult, throwPointsHold);
			zval *impurePoints = throwPoints != NULL ? pt_process_arrow_function_result_closure_type_impure_points(arrowResult, impurePointsHold) : NULL;
			zval *invalidate = impurePoints != NULL ? pt_process_arrow_function_result_invalidate_expressions(arrowResult, invalidateHold) : NULL;
			if (UNEXPECTED(invalidate == NULL)) return zv::Val();
			types[native] = pt_closure_type_resolver_build_closure_type_for_arrow_function(OBJ_PROP_NUM(self, slots::closureTypeResolver), scope, expr, arrowScope, throwPoints, impurePoints, invalidate, native == 1, storage, passedToType.raw(), nativePassedToType.raw());
			if (UNEXPECTED(types[native].isUndef())) return zv::Val();
		}

		zv::Val scopeHold;
		zval *resultScope = pt_expression_result_scope(result, scopeHold);
		if (UNEXPECTED(resultScope == NULL)) return zv::Val();
		zv::Val variableFlow = pt_expression_result_variable_flow(result);
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		bool hasYield = false;
		if (UNEXPECTED(!pt_expression_result_has_yield(result, hasYield))) return zv::Val();
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr);
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		pt_expression_result_args args(resultScope, scope, expr, hasYield, false, &emptyArray, &emptyArray, NULL, specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw()).withType(types[0].raw()).withNativeType(types[1].raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ArrowFunctionHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

	/* Mirrors getVariableFlow() */
	static zv::Val getVariableFlow(zval *expr, zval *bodyResult)
	{
		zval *params = ptclosure::prop(ptclosure::paramsSite, expr, PT_LC("params"));
		if (UNEXPECTED(params == NULL)) return zv::Val();
		zv::Arr outputs = zv::Arr::empty();
		if (EXPECTED(Z_TYPE_P(params) == IS_ARRAY) && zend_hash_num_elements(Z_ARRVAL_P(params)) > 0) {
			zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
			if (UNEXPECTED(variableCe == NULL)) return zv::Val();
			zv::Val paramsHold = zv::Val::copyOf(zv::Ref(params));
			for (zv::ArrayEntry entry : zv::ArrRef(paramsHold.raw())) {
				zval *param = entry.value().deref().raw();
				zval *byRef = ptclosure::prop(ptclosure::paramByRefSite, param, PT_LC("byRef"));
				if (UNEXPECTED(byRef == NULL)) return zv::Val();
				if (!zend_is_true(byRef)) continue;
				zval *var = ptclosure::prop(ptclosure::paramVarSite, param, PT_LC("var"));
				if (UNEXPECTED(var == NULL)) return zv::Val();
				if (Z_TYPE_P(var) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(var), variableCe)) continue;
				zval *name = ptclosure::prop(ptclosure::variableNameSite, var, PT_LC("name"));
				if (UNEXPECTED(name == NULL)) return zv::Val();
				if (Z_TYPE_P(name) != IS_STRING) continue;
				zv::Val read = pt_variable_flow_read(Z_STR_P(name), NULL, false, NULL);
				if (UNEXPECTED(read.isUndef())) return zv::Val();
				outputs.push(std::move(read));
			}
		}

		zv::Val bodyFlow = pt_expression_result_variable_flow(bodyResult);
		if (UNEXPECTED(bodyFlow.isUndef())) return zv::Val();
		zv::Val outputFlow = zend_hash_num_elements(outputs.table()) == 0 ? pt_variable_flow_sequence(0, NULL) : pt_variable_flow_sequence_list(outputs.table());
		if (UNEXPECTED(outputFlow.isUndef())) return zv::Val();
		return pt_variable_flow_arrow(expr, bodyFlow.raw(), outputFlow.raw());
	}

private:
	zend_object *self;

	void writeSlot(uint32_t index, zval *value)
	{
		zv::ObjRef(self).propAtWrite(index, zv::Val::copyOf(zv::Ref(value)));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, index)) = 0;
	}

	/* fn (TypeSpecifierContext $c, bool $nativeTypesPromoted) =>
	 * $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $c) — captures:
	 * $this, $expr */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptcall::requireArguments(argc, 2, "PHPStan\\Analyser\\ExprHandler\\ArrowFunctionHandler::{closure}"))) return;
		zv::Val specifiedTypes = pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), &captures[1], &argv[0]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ArrowFunctionHandler;

zv::Val pt_arrow_function_handler_get_variable_flow(zval *expr, zval *bodyResult)
{
	return ArrowFunctionHandler::getVariableFlow(expr, bodyResult);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_arrow_function_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\ArrowFunctionHandler");
	ptdecl::ArrowFunctionHandler::declareClass(cls);
	ptdecl::ArrowFunctionHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *closureTypeResolver, *expressionResultFactory, *defaultNarrowingHelper, *closureProcessor;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, closureTypeResolver, expressionResultFactory, defaultNarrowingHelper, closureProcessor)) RETURN_THROWS();
		ArrowFunctionHandler(Z_OBJ_P(ZEND_THIS)).construct(closureTypeResolver, expressionResultFactory, defaultNarrowingHelper, closureProcessor);
	});

	cls.method<&ArrowFunctionHandler::supports, zp::Obj>(sigs::supports);

	cls.method(sigs::processExpr, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *expr, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ArrowFunctionHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::getVariableFlow, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *bodyResult;
		zend_class_entry *arrowFunctionCe = pt_class(PT_CLASS_ARROW_FUNCTION);
		if (UNEXPECTED(arrowFunctionCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OF_CLASS(expr, arrowFunctionCe)
			Z_PARAM_OBJECT(bodyResult)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ArrowFunctionHandler::getVariableFlow(expr, bodyResult));
	});

	cls.shadow(&pt_ce_arrow_function_handler);
	pt_expr_handler_entry_register(&pt_ce_arrow_function_handler, &ArrowFunctionHandler::processExprEntry);
}

/* }}} */
