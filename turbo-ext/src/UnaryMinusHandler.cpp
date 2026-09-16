/*
 * PHPStanTurbo\UnaryMinusHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\UnaryMinusHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($this, $expr, $exprResult),
 * the $getTypeCallback it hands InitializerExprTypeResolver
 * ($nativeTypesPromoted, $expr, $exprResult, $nodeScopeResolver, $scope) and
 * the specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext,
 * DefaultNarrowingHelper and the Type kernel are called through their direct
 * entries; InitializerExprTypeResolver::getUnaryMinusType() (which consults
 * the unary operator extensions) through a cached site.
 */

#include "support.h"
#include "generated/UnaryMinusHandler.h"

namespace slots = ptdecl::UnaryMinusHandler::slot;
namespace sigs = ptdecl::UnaryMinusHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_unary_minus_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_umh_expr = PT_NODE_PROP(PT_CLASS_UNARY_MINUS, "expr");

pt_method_site pt_umh_get_unary_minus_type_site;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\UnaryMinusHandler; UNDEF = pending
 * exception. */
class UnaryMinusHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\UnaryMinusHandler::{closure}";
	static constexpr uint32_t defaultNarrowingHelperSlot = slots::defaultNarrowingHelper;

	explicit UnaryMinusHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *initializerExprTypeResolver, zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::initializerExprTypeResolver, initializerExprTypeResolver);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_UNARY_MINUS);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *inner = ptoh::operand(pt_umh_expr, expr);
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		zv::Val innerContext = pt_expression_context_enter_deep_keeping_value_flow(context);
		if (UNEXPECTED(innerContext.isUndef())) return zv::Val();
		zv::Val exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, inner, scope, storage, nodeCallback, innerContext.raw());
		if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		ptse::ChildResult child;
		if (UNEXPECTED(!child.read(exprResult.raw()))) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, exprResult.raw(), nodeScopeResolver, scope);
		zv::Val specifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<UnaryMinusHandler>, self, expr);
		pt_expression_result_args args(child.scope, scope, expr, child.hasYield, child.isAlwaysTerminating, child.throwPoints, child.impurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(child.variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return UnaryMinusHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* fn (bool $nativeTypesPromoted) =>
	 * $this->initializerExprTypeResolver->getUnaryMinusType($expr->expr, ...) —
	 * captures: $this, $expr, $exprResult, $nodeScopeResolver, $scope */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptse::requireArgs(argc, 1, closureName))) return;
		zval nativeTypesPromoted;
		ZVAL_BOOL(&nativeTypesPromoted, zend_is_true(&argv[0]));
		zv::Val type;
		pt_engine_with_stack([&]() {
			zval *inner = ptoh::operand(pt_umh_expr, &captures[1]);
			if (UNEXPECTED(inner == NULL)) return;
			zv::Val getType = pt_native_closure(&getTypeCallbackBody, &nativeTypesPromoted, &captures[1], &captures[2], &captures[3], &captures[4]);
			zv::Args callArgs{inner, getType.raw()};
			type = pt_call_method_cached(pt_umh_get_unary_minus_type_site, Z_OBJ_P(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::initializerExprTypeResolver)), PT_LC("getunaryminustype"), 2, callArgs);
		});
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* static function (Expr $e) use ($nativeTypesPromoted, $expr,
	 * $exprResult, $nodeScopeResolver, $scope): Type — captures:
	 * $nativeTypesPromoted, $expr, $exprResult, $nodeScopeResolver, $scope */
	static void getTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptse::requireArgs(argc, 1, closureName))) return;
		zval *inner = ptoh::operand(pt_umh_expr, &captures[1]);
		if (UNEXPECTED(inner == NULL)) return;
		if (Z_TYPE(argv[0]) == IS_OBJECT && Z_OBJ(argv[0]) == Z_OBJ_P(inner)) {
			zv::Val type;
			pt_engine_with_stack([&]() { type = ptse::typeOf(&captures[2], Z_TYPE(captures[0]) == IS_TRUE); });
			if (UNEXPECTED(type.isUndef())) return;
			type.intoReturnValue(return_value);
			return;
		}

		// a synthetic node ($expr->expr * -1, derived for an IntegerRangeType
		// operand) created inside getUnaryMinusType - priced on demand
		zv::Val type;
		pt_engine_with_stack([&]() {
			zval *scope = &captures[4];
			zv::Val result = pt_node_scope_resolver_process_synthetic_on_demand(&captures[3], &argv[0], scope);
			if (UNEXPECTED(result.isUndef())) return;
			if (UNEXPECTED(Z_TYPE_P(result.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getTypeOnScope() on %s", zend_zval_value_name(result.raw()));
				return;
			}
			type = pt_expression_result_get_type_on_scope(result.raw(), scope, Z_TYPE(captures[0]) == IS_TRUE);
		});
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::UnaryMinusHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_unary_minus_handler()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\UnaryMinusHandler");
	ptdecl::UnaryMinusHandler::declareClass(cls);
	ptdecl::UnaryMinusHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver, *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, initializerExprTypeResolver, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		UnaryMinusHandler(Z_OBJ_P(ZEND_THIS)).construct(initializerExprTypeResolver, expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!UnaryMinusHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(UnaryMinusHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_unary_minus_handler);
	pt_expr_handler_entry_register(&pt_ce_unary_minus_handler, &UnaryMinusHandler::processExprEntry);
}

/* }}} */
