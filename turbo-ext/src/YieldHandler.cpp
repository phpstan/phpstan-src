/*
 * PHPStanTurbo\YieldHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\YieldHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($functionReflection) and
 * the specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, MutatingScope,
 * InternalThrowPoint, ImpurePoint, VariableFlow(Builder),
 * DefaultNarrowingHelper and the Type kernel are called through their direct
 * entries; the function reflection's getReturnType() through a cached site.
 */

#include "support.h"
#include "Engine.h"
#include "generated/YieldHandler.h"

namespace slots = ptdecl::YieldHandler::slot;
namespace sigs = ptdecl::YieldHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_yield_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_yh_key = PT_NODE_PROP(PT_CLASS_YIELD, "key");
NodeProp pt_yh_value = PT_NODE_PROP(PT_CLASS_YIELD, "value");

pt_method_site pt_yh_get_return_type_site;

/* the twin's literals, permanent interned strings (module startup) */
zend_string *pt_yh_yield = nullptr;
zend_string *pt_yh_generator = nullptr;
zend_string *pt_yh_tsend = nullptr;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\YieldHandler; UNDEF = pending
 * exception. */
class YieldHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\YieldHandler::{closure}";
	static constexpr uint32_t defaultNarrowingHelperSlot = slots::defaultNarrowingHelper;

	explicit YieldHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper, zval *templateArgumentObserver) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
		pt_write_slot(self, slots::templateArgumentObserver, templateArgumentObserver);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_YIELD);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zv::Val throwPoints;
		{
			zv::Val throwPoint = pt_internal_throw_point_create_implicit(scope, expr);
			if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();
			zv::Arr list = zv::Arr::create(1);
			list.push(std::move(throwPoint));
			throwPoints = std::move(list);
		}
		zv::Val impurePoints;
		{
			zv::Val impurePoint = pt_impure_point_new(scope, expr, pt_yh_yield, pt_yh_yield, true);
			if (UNEXPECTED(impurePoint.isUndef())) return zv::Val();
			zv::Arr list = zv::Arr::create(1);
			list.push(std::move(impurePoint));
			impurePoints = std::move(list);
		}
		bool isAlwaysTerminating = false;
		zv::Val keyResult = zv::Val::null();
		zv::Val valueResult = zv::Val::null();
		zv::Val scopeHold;

		zval *key = ptoh::operand(pt_yh_key, expr);
		if (UNEXPECTED(key == NULL)) return zv::Val();
		if (Z_TYPE_P(key) != IS_NULL) {
			zv::Val keyContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(keyContext.isUndef())) return zv::Val();
			keyResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, key, scope, storage, nodeCallback, keyContext.raw());
			if (UNEXPECTED(keyResult.isUndef())) return zv::Val();
			scopeHold = ptse::scopeOf(keyResult.raw());
			if (UNEXPECTED(scopeHold.isUndef())) return zv::Val();
			scope = scopeHold.raw();
			{
				zv::Val hold;
				zval *points = pt_expression_result_throw_points(keyResult.raw(), hold);
				if (UNEXPECTED(points == NULL || !ptse::mergeInto(throwPoints, points))) return zv::Val();
			}
			{
				zv::Val hold;
				zval *points = pt_expression_result_impure_points(keyResult.raw(), hold);
				if (UNEXPECTED(points == NULL || !ptse::mergeInto(impurePoints, points))) return zv::Val();
			}
			if (UNEXPECTED(!pt_expression_result_is_always_terminating(keyResult.raw(), isAlwaysTerminating))) return zv::Val();
		}
		zval *value = ptoh::operand(pt_yh_value, expr);
		if (UNEXPECTED(value == NULL)) return zv::Val();
		if (Z_TYPE_P(value) != IS_NULL) {
			zv::Val valueContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(valueContext.isUndef())) return zv::Val();
			valueResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, value, scope, storage, nodeCallback, valueContext.raw());
			if (UNEXPECTED(valueResult.isUndef())) return zv::Val();
			scopeHold = ptse::scopeOf(valueResult.raw());
			if (UNEXPECTED(scopeHold.isUndef())) return zv::Val();
			scope = scopeHold.raw();
			{
				zv::Val hold;
				zval *points = pt_expression_result_throw_points(valueResult.raw(), hold);
				if (UNEXPECTED(points == NULL || !ptse::mergeInto(throwPoints, points))) return zv::Val();
			}
			{
				zv::Val hold;
				zval *points = pt_expression_result_impure_points(valueResult.raw(), hold);
				if (UNEXPECTED(points == NULL || !ptse::mergeInto(impurePoints, points))) return zv::Val();
			}
			if (!isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(valueResult.raw(), isAlwaysTerminating))) return zv::Val();
		}

		{
			zv::Val observingFrame = pt_node_scope_resolver_observing_template_argument_frame(nodeScopeResolver, scope);
			if (UNEXPECTED(observingFrame.isUndef())) return zv::Val();
			if (Z_TYPE_P(observingFrame.raw()) == IS_OBJECT) {
				// the consumer of the generator can do anything with what it yields
				zval *yieldedResults[2] = {keyResult.raw(), valueResult.raw()};
				for (zval *yieldedResult : yieldedResults) {
					if (Z_TYPE_P(yieldedResult) == IS_NULL) continue;
					zv::Val yieldedType = pt_expression_result_get_type(yieldedResult);
					if (UNEXPECTED(yieldedType.isUndef())) return zv::Val();
					zv::Val constraints = pt_template_argument_observer_collect_escape(OBJ_PROP_NUM(self, slots::templateArgumentObserver), yieldedType.raw());
					if (UNEXPECTED(constraints.isUndef())) return zv::Val();
					zv::Val constrained = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(scope), constraints.raw());
					if (UNEXPECTED(constrained.isUndef())) return zv::Val();
					scopeHold = std::move(constrained);
					scope = scopeHold.raw();
				}
			}
		}

		// the enclosing function is lexical - the generator TSend type does not
		// vary with the scope the callback is later invoked on - resolve it once here.
		zv::Val functionReflection = pt_mutating_scope_get_function(Z_OBJ_P(beforeScope));
		if (UNEXPECTED(functionReflection.isUndef())) return zv::Val();

		zv::Val variableFlow;
		{
			zv::Val keyFlow = zv::Val::null();
			if (!keyResult.isNull()) {
				keyFlow = pt_expression_result_variable_flow(keyResult.raw());
				if (UNEXPECTED(keyFlow.isUndef())) return zv::Val();
			}
			zv::Val valueFlow = zv::Val::null();
			if (!valueResult.isNull()) {
				valueFlow = pt_expression_result_variable_flow(valueResult.raw());
				if (UNEXPECTED(valueFlow.isUndef())) return zv::Val();
			}
			zv::Val throwsFlow = pt_variable_flow_builder_throws(expr, Z_ARRVAL_P(throwPoints.raw()));
			if (UNEXPECTED(throwsFlow.isUndef())) return zv::Val();
			zv::Args flows{keyFlow.raw(), valueFlow.raw(), throwsFlow.raw()};
			variableFlow = pt_variable_flow_sequence(3, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, functionReflection.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<YieldHandler>, self, expr);
		pt_expression_result_args args(scope, beforeScope, expr, true, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return YieldHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* static function () use ($functionReflection): Type — captures:
	 * $functionReflection */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zval *functionReflection = &captures[0];
		zv::Val type;
		pt_engine_with_stack([&]() {
			if (Z_TYPE_P(functionReflection) == IS_NULL) {
				type = pt_type_new_mixed_type();
				return;
			}

			zv::Val returnType = pt_call_method_cached(pt_yh_get_return_type_site, Z_OBJ_P(functionReflection), PT_LC("getreturntype"), 0, NULL);
			if (UNEXPECTED(returnType.isUndef())) return;
			if (UNEXPECTED(Z_TYPE_P(returnType.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getTemplateType() on %s", zend_zval_value_name(returnType.raw()));
				return;
			}
			zv::Args templateArgs{pt_yh_generator, pt_yh_tsend};
			zv::Val generatorSendType = pt_type_call(Z_OBJ_P(returnType.raw()), PT_LC("gettemplatetype"), 2, templateArgs);
			if (UNEXPECTED(generatorSendType.isUndef())) return;
			if (Z_TYPE_P(generatorSendType.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(generatorSendType.raw()), pt_ce_error_type)) {
				type = pt_type_new_mixed_type();
				return;
			}

			type = std::move(generatorSendType);
		});
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::YieldHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_yield_handler)
{
	pt_yh_yield = zend_string_init_interned(PT_LC("yield"), 1);
	pt_yh_generator = zend_string_init_interned(PT_LC("Generator"), 1);
	pt_yh_tsend = zend_string_init_interned(PT_LC("TSend"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\YieldHandler");
	ptdecl::YieldHandler::declareClass(cls);
	ptdecl::YieldHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper, *templateArgumentObserver;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper, templateArgumentObserver)) RETURN_THROWS();
		YieldHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper, templateArgumentObserver);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!YieldHandler::supports(expr, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

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
		PT_RETURN_VAL(YieldHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_yield_handler);
	pt_expr_handler_entry_register(&pt_ce_yield_handler, &YieldHandler::processExprEntry);
}

/* }}} */
