/*
 * PHPStanTurbo\ThrowHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\ThrowHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback (nothing) and the
 * specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, InternalThrowPoint,
 * VariableFlow, DefaultNarrowingHelper and the Type kernel are called through
 * their direct entries.
 */

#include "support.h"
#include "generated/ThrowHandler.h"

namespace slots = ptdecl::ThrowHandler::slot;
namespace sigs = ptdecl::ThrowHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_throw_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_thh_expr = PT_NODE_PROP(PT_CLASS_THROW_EXPR, "expr");

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\ThrowHandler; UNDEF = pending
 * exception. */
class ThrowHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\ThrowHandler::{closure}";
	static constexpr uint32_t defaultNarrowingHelperSlot = slots::defaultNarrowingHelper;

	explicit ThrowHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_THROW_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *inner = ptoh::operand(pt_thh_expr, expr);
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		bool resolveTemplateArguments;
		if (UNEXPECTED(!pt_expression_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
		zv::Val deepContext = pt_expression_context_create_deep(resolveTemplateArguments);
		if (UNEXPECTED(deepContext.isUndef())) return zv::Val();
		zv::Val innerContext = pt_expression_context_enter_throw(deepContext.raw());
		if (UNEXPECTED(innerContext.isUndef())) return zv::Val();
		zv::Val exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, inner, scope, storage, nodeCallback, innerContext.raw());
		if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		ptse::ChildResult child;
		if (UNEXPECTED(!child.read(exprResult.raw()))) return zv::Val();

		zv::Val variableFlow;
		{
			zv::Val exprType = pt_expression_result_get_type(exprResult.raw());
			if (UNEXPECTED(exprType.isUndef())) return zv::Val();
			zv::Val throwing = pt_variable_flow_throwing(exprType.raw(), false, false);
			if (UNEXPECTED(throwing.isUndef())) return zv::Val();
			zv::Args flows{child.variableFlow.raw(), throwing.raw()};
			variableFlow = pt_variable_flow_sequence(2, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		zv::Val throwPoints;
		{
			zv::Val exprType = pt_expression_result_get_type(exprResult.raw());
			if (UNEXPECTED(exprType.isUndef())) return zv::Val();
			zv::Val throwPoint = pt_internal_throw_point_create_explicit(scope, exprType.raw(), expr, false, true);
			if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();
			throwPoints = ptse::mergeOne(child.throwPoints, std::move(throwPoint));
			if (UNEXPECTED(throwPoints.isUndef())) return zv::Val();
		}

		zv::Val typeCallback = pt_native_closure(&ptse::nonAcceptingNeverTypeBody<ThrowHandler>);
		zv::Val specifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<ThrowHandler>, self, expr);
		pt_expression_result_args args(scope, scope, expr, false, true, throwPoints.raw(), child.impurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ThrowHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::ThrowHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_throw_handler()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\ThrowHandler");
	ptdecl::ThrowHandler::declareClass(cls);
	ptdecl::ThrowHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		ThrowHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!ThrowHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(ThrowHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_throw_handler);
	pt_expr_handler_entry_register(&pt_ce_throw_handler, &ThrowHandler::processExprEntry);
}

/* }}} */
