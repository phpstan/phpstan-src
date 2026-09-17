/*
 * PHPStanTurbo\YieldFromHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\YieldFromHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($exprResult) and the
 * specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, InternalThrowPoint,
 * ImpurePoint, VariableFlow, DefaultNarrowingHelper and the Type kernel are
 * called through their direct entries.
 */

#include "support.h"
#include "generated/YieldFromHandler.h"

namespace slots = ptdecl::YieldFromHandler::slot;
namespace sigs = ptdecl::YieldFromHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_yield_from_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_yfh_expr = PT_NODE_PROP(PT_CLASS_YIELD_FROM, "expr");

/* the twin's literals, permanent interned strings (module startup) */
zend_string *pt_yfh_yield_from_identifier = nullptr;
zend_string *pt_yfh_yield_from_description = nullptr;
zend_string *pt_yfh_generator = nullptr;
zend_string *pt_yfh_treturn = nullptr;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\YieldFromHandler; UNDEF = pending
 * exception. */
class YieldFromHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\YieldFromHandler::{closure}";
	static constexpr uint32_t defaultNarrowingHelperSlot = slots::defaultNarrowingHelper;

	explicit YieldFromHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_YIELD_FROM);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zval *inner = ptoh::operand(pt_yfh_expr, expr);
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		zv::Val innerContext = pt_expression_context_enter_deep(context);
		if (UNEXPECTED(innerContext.isUndef())) return zv::Val();
		zv::Val exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, inner, scope, storage, nodeCallback, innerContext.raw());
		if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		ptse::ChildResult child;
		if (UNEXPECTED(!child.read(exprResult.raw()))) return zv::Val();
		zv::Val resultScope = zv::Val::copyOf(zv::Ref(child.scope));

		zv::Val throwPoint = pt_internal_throw_point_create_implicit(resultScope.raw(), expr);
		if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();

		zv::Val variableFlow;
		{
			zv::Val typeHold;
			zval *throwPointType = pt_internal_throw_point_type(throwPoint.raw(), typeHold);
			if (UNEXPECTED(throwPointType == NULL)) return zv::Val();
			zv::Val throwing = pt_variable_flow_throwing(throwPointType, true, false);
			if (UNEXPECTED(throwing.isUndef())) return zv::Val();
			zv::Args flows{child.variableFlow.raw(), throwing.raw()};
			variableFlow = pt_variable_flow_sequence(2, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		zv::Val throwPoints = ptse::mergeOne(child.throwPoints, std::move(throwPoint));
		if (UNEXPECTED(throwPoints.isUndef())) return zv::Val();
		zv::Val impurePoint = pt_impure_point_new(resultScope.raw(), expr, pt_yfh_yield_from_identifier, pt_yfh_yield_from_description, true);
		if (UNEXPECTED(impurePoint.isUndef())) return zv::Val();
		zv::Val impurePoints = ptse::mergeOne(child.impurePoints, std::move(impurePoint));
		if (UNEXPECTED(impurePoints.isUndef())) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, exprResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<YieldFromHandler>, self, expr);
		pt_expression_result_args args(resultScope.raw(), beforeScope, expr, true, child.isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return YieldFromHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* static function (bool $nativeTypesPromoted) use ($exprResult): Type —
	 * captures: $exprResult */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptse::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() {
			zv::Val yieldFromType = ptse::typeOf(&captures[0], nativeTypesPromoted);
			if (UNEXPECTED(yieldFromType.isUndef())) return;
			if (UNEXPECTED(Z_TYPE_P(yieldFromType.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getTemplateType() on %s", zend_zval_value_name(yieldFromType.raw()));
				return;
			}
			zv::Args templateArgs{pt_yfh_generator, pt_yfh_treturn};
			zv::Val generatorReturnType = pt_type_call(Z_OBJ_P(yieldFromType.raw()), PT_LC("gettemplatetype"), 2, templateArgs);
			if (UNEXPECTED(generatorReturnType.isUndef())) return;
			if (Z_TYPE_P(generatorReturnType.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(generatorReturnType.raw()), pt_ce_error_type)) {
				type = pt_type_new_mixed_type();
				return;
			}

			type = std::move(generatorReturnType);
		});
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::YieldFromHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_yield_from_handler()
{
	pt_yfh_yield_from_identifier = zend_string_init_interned(PT_LC("yieldFrom"), 1);
	pt_yfh_yield_from_description = zend_string_init_interned(PT_LC("yield from"), 1);
	pt_yfh_generator = zend_string_init_interned(PT_LC("Generator"), 1);
	pt_yfh_treturn = zend_string_init_interned(PT_LC("TReturn"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\YieldFromHandler");
	ptdecl::YieldFromHandler::declareClass(cls);
	ptdecl::YieldFromHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		YieldFromHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!YieldFromHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(YieldFromHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_yield_from_handler);
	pt_expr_handler_entry_register(&pt_ce_yield_from_handler, &YieldFromHandler::processExprEntry);
}

/* }}} */
