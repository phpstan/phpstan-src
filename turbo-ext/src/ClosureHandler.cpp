/*
 * PHPStanTurbo\ClosureHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\ClosureHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry, the static getVariableFlow() — which ArgumentsHandler calls
 * too — is exported as pt_closure_handler_get_variable_flow(). The twin's
 * specifyTypesCallback is a native closure capturing $this and $expr.
 * ClosureProcessor, its ProcessClosureResult, ClosureTypeResolver,
 * DefaultNarrowingHelper, VariableFlow and ExpressionResult are called through
 * their direct entries.
 */

#include "support.h"
#include "generated/ClosureHandler.h"

namespace slots = ptdecl::ClosureHandler::slot;
namespace sigs = ptdecl::ClosureHandler::sig;
#include "ClosureSupport.h"

zend_class_entry *pt_ce_closure_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\ClosureHandler; UNDEF = pending
 * exception. */
class ClosureHandler
{
public:
	explicit ClosureHandler(zend_object *self) : self(self) {}

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
		int is = ptclosure::instanceOf(expr, PT_CLASS_CLOSURE_EXPR);
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
		zv::Val processClosureResult = pt_closure_processor_process_closure_node(OBJ_PROP_NUM(self, slots::closureProcessor), nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context, passedToType.raw(), nativePassedToType.raw());
		if (UNEXPECTED(processClosureResult.isUndef())) return zv::Val();

		// A plain typeCallback recursing through getClosureType() would re-walk
		// the body each getType() ask before the cache populates and hang;
		// ExpressionResult excludes closures from its tracked-type early return.
		// Compute the ClosureType once here and store it as an eager value.
		//
		// The phpdoc flavour is built from the returns/yields the single body walk
		// in processClosureNode() already gathered, without a second walk; a
		// closure's native type equals its phpdoc type.
		zv::Val type;
		{
			zval *result = processClosureResult.raw();
			zv::Val returnsHold, yieldsHold, endsHold, throwPointsHold, impurePointsHold, invalidateHold;
			zval *returns = pt_process_closure_result_gathered_return_statements(result, returnsHold);
			zval *yields = returns != NULL ? pt_process_closure_result_gathered_yield_statements(result, yieldsHold) : NULL;
			zval *ends = yields != NULL ? pt_process_closure_result_execution_ends(result, endsHold) : NULL;
			zval *throwPoints = ends != NULL ? pt_process_closure_result_throw_points(result, throwPointsHold) : NULL;
			zval *impurePoints = throwPoints != NULL ? pt_process_closure_result_closure_type_impure_points(result, impurePointsHold) : NULL;
			zval *invalidate = impurePoints != NULL ? pt_process_closure_result_invalidate_expressions(result, invalidateHold) : NULL;
			if (UNEXPECTED(invalidate == NULL)) return zv::Val();
			/* the context is immutable: its passed-to types read above */
			type = pt_closure_type_resolver_build_closure_type_for_closure(OBJ_PROP_NUM(self, slots::closureTypeResolver), scope, expr, returns, yields, ends, throwPoints, impurePoints, invalidate, false, storage, passedToType.raw(), nativePassedToType.raw());
			if (UNEXPECTED(type.isUndef())) return zv::Val();
		}

		zv::Val resultScope;
		{
			zv::Val scopeHold;
			zval *closureScope = pt_process_closure_result_scope(processClosureResult.raw(), scopeHold);
			if (UNEXPECTED(closureScope == NULL)) return zv::Val();
			zv::Val closureScopeHold = zv::Val::copyOf(zv::Ref(closureScope));
			resultScope = pt_process_closure_result_apply_by_ref_use_scope(processClosureResult.raw(), closureScopeHold.raw());
			if (UNEXPECTED(resultScope.isUndef())) return zv::Val();
		}
		zv::Val variableFlow = pt_closure_handler_get_variable_flow(expr);
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr);
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		pt_expression_result_args args(resultScope.raw(), scope, expr, false, false, &emptyArray, &emptyArray, NULL, specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw()).withType(type.raw()).withNativeType(type.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ClosureHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

	/* Mirrors getVariableFlow(): the sequence of the uses' reads / escapes, or null */
	static zv::Val getVariableFlow(zval *expr)
	{
		zval *uses = ptclosure::prop(ptclosure::usesSite, expr, PT_LC("uses"));
		if (UNEXPECTED(uses == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(uses) != IS_ARRAY) || zend_hash_num_elements(Z_ARRVAL_P(uses)) == 0) return pt_variable_flow_sequence(0, NULL);
		zv::Val usesHold = zv::Val::copyOf(zv::Ref(uses));
		zv::Arr flows = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(usesHold.raw())));
		for (zv::ArrayEntry entry : zv::ArrRef(usesHold.raw())) {
			zval *use = entry.value().deref().raw();
			zval *var = ptclosure::prop(ptclosure::useVarSite, use, PT_LC("var"));
			if (UNEXPECTED(var == NULL)) return zv::Val();
			zval *name = ptclosure::prop(ptclosure::variableNameSite, var, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return zv::Val();
			if (Z_TYPE_P(name) != IS_STRING) continue;
			zval *byRef = ptclosure::prop(ptclosure::useByRefSite, use, PT_LC("byRef"));
			if (UNEXPECTED(byRef == NULL)) return zv::Val();
			zv::Val flow = zend_is_true(byRef) ? pt_variable_flow_escape(Z_STR_P(name)) : pt_variable_flow_read(Z_STR_P(name), NULL, false, NULL);
			if (UNEXPECTED(flow.isUndef())) return zv::Val();
			flows.push(std::move(flow));
		}
		return pt_variable_flow_sequence_list(flows.table());
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
		if (UNEXPECTED(!ptcall::requireArguments(argc, 2, "PHPStan\\Analyser\\ExprHandler\\ClosureHandler::{closure}"))) return;
		zv::Val specifiedTypes = pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), &captures[1], &argv[0]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClosureHandler;

zv::Val pt_closure_handler_get_variable_flow(zval *expr)
{
	return ClosureHandler::getVariableFlow(expr);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_closure_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\ClosureHandler");
	ptdecl::ClosureHandler::declareClass(cls);
	ptdecl::ClosureHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *closureTypeResolver, *expressionResultFactory, *defaultNarrowingHelper, *closureProcessor;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, closureTypeResolver, expressionResultFactory, defaultNarrowingHelper, closureProcessor)) RETURN_THROWS();
		ClosureHandler(Z_OBJ_P(ZEND_THIS)).construct(closureTypeResolver, expressionResultFactory, defaultNarrowingHelper, closureProcessor);
	});

	cls.method<&ClosureHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(ClosureHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::getVariableFlow, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		zend_class_entry *closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
		if (UNEXPECTED(closureCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(expr, closureCe)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ClosureHandler::getVariableFlow(expr));
	});

	cls.shadow(&pt_ce_closure_handler);
	pt_expr_handler_entry_register(&pt_ce_closure_handler, &ClosureHandler::processExprEntry);
}

/* }}} */
