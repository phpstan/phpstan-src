/*
 * PHPStanTurbo\BooleanNotHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\BooleanNotHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($exprResult) and the
 * specifyTypesCallback ($this, $expr, $exprResult).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, SpecifiedTypes,
 * TypeSpecifierContext, DefaultNarrowingHelper and the Type kernel are called
 * through their direct entries.
 */

#include "support.h"
#include "generated/BooleanNotHandler.h"

namespace slots = ptdecl::BooleanNotHandler::slot;
namespace sigs = ptdecl::BooleanNotHandler::sig;
#include "OperatorHandlers.h"

zend_class_entry *pt_ce_boolean_not_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\BooleanNotHandler; UNDEF = pending
 * exception. */
class BooleanNotHandler
{
public:
	explicit BooleanNotHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_BOOLEAN_NOT_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zval *inner = ptoh::operand(ptoh::booleanNotExprProp, expr);
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		zv::Val innerContext = pt_expression_context_enter_deep_keeping_value_flow(context);
		if (UNEXPECTED(innerContext.isUndef())) return zv::Val();
		zv::Val exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, inner, scope, storage, nodeCallback, innerContext.raw());
		if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		zv::Val scopeHold, throwPointsHold, impurePointsHold;
		zval *resultScope = pt_expression_result_scope(exprResult.raw(), scopeHold);
		if (UNEXPECTED(resultScope == NULL)) return zv::Val();

		zv::Val variableFlow = pt_expression_result_variable_flow(exprResult.raw());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		bool hasYield, isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_has_yield(exprResult.raw(), hasYield))) return zv::Val();
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(exprResult.raw(), isAlwaysTerminating))) return zv::Val();
		zval *throwPoints = pt_expression_result_throw_points(exprResult.raw(), throwPointsHold);
		if (UNEXPECTED(throwPoints == NULL)) return zv::Val();
		zval *impurePoints = pt_expression_result_impure_points(exprResult.raw(), impurePointsHold);
		if (UNEXPECTED(impurePoints == NULL)) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, exprResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr, exprResult.raw());
		pt_expression_result_args args(resultScope, beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints, impurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return BooleanNotHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\BooleanNotHandler::{closure}";

	/* static function (bool $nativeTypesPromoted) use ($exprResult): Type —
	 * captures: $exprResult */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() { type = resolveType(&captures[0], nativeTypesPromoted); });
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	static zv::Val resolveType(zval *exprResult, bool nativeTypesPromoted)
	{
		zv::Val exprType = nativeTypesPromoted ? pt_expression_result_get_native_type(exprResult) : pt_expression_result_get_type(exprResult);
		if (UNEXPECTED(exprType.isUndef())) return zv::Val();
		ptoh::BooleanOf exprBooleanType;
		if (UNEXPECTED(!exprBooleanType.init(exprType.raw()))) return zv::Val();
		int isTrue = exprBooleanType.isTrue();
		if (UNEXPECTED(isTrue < 0)) return zv::Val();
		if (isTrue) return ptoh::constantBoolean(false);
		int isFalse = exprBooleanType.isFalse();
		if (UNEXPECTED(isFalse < 0)) return zv::Val();
		if (isFalse) return ptoh::constantBoolean(true);

		return ptoh::booleanType();
	}

	/* function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use
	 * ($expr, $exprResult): SpecifiedTypes — captures: $this, $expr,
	 * $exprResult */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 2, closureName))) return;
		zval *expr = &captures[1];
		zval *exprResult = &captures[2];
		zval *context = &argv[0];
		bool isNull;
		if (UNEXPECTED(!pt_type_specifier_context_null(Z_OBJ_P(context), isNull))) return;
		if (isNull) {
			zv::Val specifiedTypes = pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), expr, context);
			if (UNEXPECTED(specifiedTypes.isUndef())) return;
			specifiedTypes.intoReturnValue(return_value);
			return;
		}

		// The negated operand was processed above; compose its narrowing
		// directly from its result rather than re-resolving the node.
		zv::Val negated = pt_type_specifier_context_negate(Z_OBJ_P(context));
		if (UNEXPECTED(negated.isUndef())) return;
		zv::Val specifiedTypes = pt_expression_result_get_specified_types(exprResult, negated.raw(), zend_is_true(&argv[1]));
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		if (UNEXPECTED(Z_TYPE_P(specifiedTypes.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(specifiedTypes.raw()));
			return;
		}
		zv::Val rooted = pt_specified_types_set_root_expr(Z_OBJ_P(specifiedTypes.raw()), expr);
		if (UNEXPECTED(rooted.isUndef())) return;
		rooted.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::BooleanNotHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_boolean_not_handler()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\BooleanNotHandler");
	ptdecl::BooleanNotHandler::declareClass(cls);
	ptdecl::BooleanNotHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		BooleanNotHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!BooleanNotHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(BooleanNotHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_boolean_not_handler);
	pt_expr_handler_entry_register(&pt_ce_boolean_not_handler, &BooleanNotHandler::processExprEntry);
}

/* }}} */
