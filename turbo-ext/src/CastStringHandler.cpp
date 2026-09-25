/*
 * PHPStanTurbo\CastStringHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\CastStringHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($this, $expr, $exprResult
 * — InitializerExprTypeResolver::getCastType() of a string cast is its
 * operand's toString(), spelled out, so its $getTypeCallback is the operand
 * read in place) and the specifyTypesCallback ($beforeScope, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, MutatingScope,
 * ImplicitToStringCallHelper, VariableFlow(Builder), SpecifiedTypes and the
 * Type kernel are called through their direct entries.
 */

#include "support.h"
#include "generated/CastStringHandler.h"

namespace slots = ptdecl::CastStringHandler::slot;
namespace sigs = ptdecl::CastStringHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_cast_string_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_csh_cast_expr = PT_NODE_PROP(PT_CLASS_CAST_EXPR, "expr");

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\CastStringHandler; UNDEF = pending
 * exception. */
class CastStringHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\CastStringHandler::{closure}";

	explicit CastStringHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *initializerExprTypeResolver, zval *implicitToStringCallHelper, zval *expressionResultFactory) const
	{
		pt_write_slot(self, slots::initializerExprTypeResolver, initializerExprTypeResolver);
		pt_write_slot(self, slots::implicitToStringCallHelper, implicitToStringCallHelper);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_CAST_STRING);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zval *inner = ptoh::operand(pt_csh_cast_expr, expr);
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		zv::Val innerContext = pt_expression_context_enter_deep_keeping_value_flow(context);
		if (UNEXPECTED(innerContext.isUndef())) return zv::Val();
		zv::Val exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, inner, scope, storage, nodeCallback, innerContext.raw());
		if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		zv::Val impurePoints = ptse::impurePointsOf(exprResult.raw());
		if (UNEXPECTED(impurePoints.isUndef())) return zv::Val();
		zv::Val throwPoints = ptse::throwPointsOf(exprResult.raw());
		if (UNEXPECTED(throwPoints.isUndef())) return zv::Val();

		zv::Val toStringResult = pt_implicit_to_string_call_helper_process_implicit_to_string_call(OBJ_PROP_NUM(self, slots::implicitToStringCallHelper), inner, scope, exprResult.raw());
		if (UNEXPECTED(toStringResult.isUndef())) return zv::Val();
		{
			zv::Val hold;
			zval *points = pt_expression_result_throw_points(toStringResult.raw(), hold);
			if (UNEXPECTED(points == NULL || !ptse::mergeInto(throwPoints, points))) return zv::Val();
		}
		{
			zv::Val hold;
			zval *points = pt_expression_result_impure_points(toStringResult.raw(), hold);
			if (UNEXPECTED(points == NULL || !ptse::mergeInto(impurePoints, points))) return zv::Val();
		}

		zv::Val scopeHold;
		zval *resultScope = pt_expression_result_scope(exprResult.raw(), scopeHold);
		if (UNEXPECTED(resultScope == NULL)) return zv::Val();

		zv::Val variableFlow;
		{
			zv::Val exprFlow = pt_expression_result_variable_flow(exprResult.raw());
			if (UNEXPECTED(exprFlow.isUndef())) return zv::Val();
			zv::Val throwsFlow = pt_variable_flow_builder_throws(expr, Z_ARRVAL_P(throwPoints.raw()));
			if (UNEXPECTED(throwsFlow.isUndef())) return zv::Val();
			zv::Args flows{exprFlow.raw(), throwsFlow.raw()};
			variableFlow = pt_variable_flow_sequence(2, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		bool hasYield, isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_has_yield(exprResult.raw(), hasYield))) return zv::Val();
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(exprResult.raw(), isAlwaysTerminating))) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, exprResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, beforeScope, expr);
		pt_expression_result_args args(resultScope, beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return CastStringHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* fn (bool $nativeTypesPromoted): Type =>
	 * $this->initializerExprTypeResolver->getCastType($expr, ...) — captures:
	 * $this, $expr, $exprResult */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptse::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() {
			// getCastType() of a Cast\String_: $getTypeCallback($expr->expr)->toString()
			zv::Val exprType = ptse::typeOf(&captures[2], nativeTypesPromoted);
			if (UNEXPECTED(exprType.isUndef())) return;
			if (UNEXPECTED(Z_TYPE_P(exprType.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function toString() on %s", zend_zval_value_name(exprType.raw()));
				return;
			}
			type = pt_type_call(Z_OBJ_P(exprType.raw()), PT_LC("tostring"), 0, NULL);
		});
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* static fn (TypeSpecifierContext $context, bool $nativeTypesPromoted):
	 * SpecifiedTypes => ($nativeTypesPromoted ?
	 * $beforeScope->doNotTreatPhpDocTypesAsCertain() :
	 * $beforeScope)->obtainResultForNode(new NotEqual($expr->expr, new
	 * String_('')))->getSpecifiedTypes($context,
	 * $nativeTypesPromoted)->setRootExpr($expr) — captures: $beforeScope, $expr */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptse::requireArgs(argc, 2, closureName))) return;
		zval *context = &argv[0];
		bool nativeTypesPromoted = zend_is_true(&argv[1]);
		zv::Val types;
		pt_engine_with_stack([&]() {
			zval *beforeScope = &captures[0];
			zval *expr = &captures[1];
			zv::Val evaluationScope = nativeTypesPromoted ? pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope)) : zv::Val::copyOf(zv::Ref(beforeScope));
			if (UNEXPECTED(evaluationScope.isUndef())) return;
			zval *inner = ptoh::operand(pt_csh_cast_expr, expr);
			if (UNEXPECTED(inner == NULL)) return;
			zval empty;
			ZVAL_EMPTY_STRING(&empty);
			zv::Val emptyString = pt_type_new(PT_CLASS_SCALAR_STRING, 1, &empty);
			if (UNEXPECTED(emptyString.isUndef())) return;
			zv::Args comparisonArgs{inner, emptyString.raw()};
			zv::Val comparison = pt_type_new(PT_CLASS_NOT_EQUAL_EXPR, 2, comparisonArgs);
			if (UNEXPECTED(comparison.isUndef())) return;
			zv::Val result = pt_mutating_scope_obtain_result_for_node(Z_OBJ_P(evaluationScope.raw()), Z_OBJ_P(comparison.raw()));
			if (UNEXPECTED(result.isUndef())) return;
			if (UNEXPECTED(Z_TYPE_P(result.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getSpecifiedTypes() on %s", zend_zval_value_name(result.raw()));
				return;
			}
			zv::Val specified = pt_expression_result_get_specified_types(result.raw(), context, nativeTypesPromoted);
			if (UNEXPECTED(specified.isUndef())) return;
			if (UNEXPECTED(Z_TYPE_P(specified.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(specified.raw()));
				return;
			}
			types = pt_specified_types_set_root_expr(Z_OBJ_P(specified.raw()), expr);
		});
		if (UNEXPECTED(types.isUndef())) return;
		types.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::CastStringHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_cast_string_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\CastStringHandler");
	ptdecl::CastStringHandler::declareClass(cls);
	ptdecl::CastStringHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver, *implicitToStringCallHelper, *expressionResultFactory;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, initializerExprTypeResolver, implicitToStringCallHelper, expressionResultFactory)) RETURN_THROWS();
		CastStringHandler(Z_OBJ_P(ZEND_THIS)).construct(initializerExprTypeResolver, implicitToStringCallHelper, expressionResultFactory);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!CastStringHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(CastStringHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_cast_string_handler);
	pt_expr_handler_entry_register(&pt_ce_cast_string_handler, &CastStringHandler::processExprEntry);
}

/* }}} */
