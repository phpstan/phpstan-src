/*
 * PHPStanTurbo\BooleanOrHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\BooleanOrHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($leftResult,
 * $rightResult), the specifyTypesCallback ($this, $nodeScopeResolver,
 * $scope, $expr, $leftResult, $rightResult) and, per ask, the operand
 * callbacks it hands to BooleanNarrowingHelper::specifyDisjunction() (the
 * operand result each).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, MutatingScope,
 * VariableFlow, BooleanNarrowingHelper and the Type kernel are called
 * through their direct entries; the virtual BooleanOrNode is instantiated
 * through the class map.
 */

#include "support.h"
#include "generated/BooleanOrHandler.h"

namespace slots = ptdecl::BooleanOrHandler::slot;
namespace sigs = ptdecl::BooleanOrHandler::sig;
#include "OperatorHandlers.h"

zend_class_entry *pt_ce_boolean_or_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\BooleanOrHandler; UNDEF = pending
 * exception. */
class BooleanOrHandler
{
public:
	explicit BooleanOrHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *booleanNarrowingHelper, zval *expressionResultFactory) const
	{
		pt_write_slot(self, slots::booleanNarrowingHelper, booleanNarrowingHelper);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_BOOLEAN_OR_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		if (!is) {
			is = ptoh::isInstance(expr, PT_CLASS_LOGICAL_OR_EXPR);
			if (UNEXPECTED(is < 0)) return false;
		}
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		zv::Val leftContext = pt_expression_context_enter_deep(context);
		if (UNEXPECTED(leftContext.isUndef())) return zv::Val();
		zv::Val leftResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, left, scope, storage, nodeCallback, leftContext.raw());
		if (UNEXPECTED(leftResult.isUndef())) return zv::Val();
		zv::Val leftFalseyScope = pt_expression_result_get_falsey_scope(leftResult.raw());
		if (UNEXPECTED(leftFalseyScope.isUndef())) return zv::Val();
		zval *right = ptoh::binaryOpRight(expr);
		if (UNEXPECTED(right == NULL)) return zv::Val();
		zv::Val rightContext = pt_expression_context_without_value_flow(context);
		if (UNEXPECTED(rightContext.isUndef())) return zv::Val();
		zv::Val rightResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, right, leftFalseyScope.raw(), storage, nodeCallback, rightContext.raw());
		if (UNEXPECTED(rightResult.isUndef())) return zv::Val();
		zv::Val rightExprType = pt_expression_result_get_type(rightResult.raw());
		if (UNEXPECTED(rightExprType.isUndef())) return zv::Val();
		bool explicitNever;
		if (UNEXPECTED(!ptoh::isExplicitNever(rightExprType.raw(), explicitNever))) return zv::Val();
		zv::Val hold;
		zv::Val leftMergedWithRightScope;
		if (explicitNever) {
			zv::Val leftTruthyScope = pt_expression_result_get_truthy_scope(leftResult.raw());
			if (UNEXPECTED(leftTruthyScope.isUndef())) return zv::Val();
			zval *rightScope = pt_expression_result_scope(rightResult.raw(), hold);
			if (UNEXPECTED(rightScope == NULL)) return zv::Val();
			zv::Val constraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(rightScope));
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
			leftMergedWithRightScope = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(leftTruthyScope.raw()), constraints.raw());
		} else {
			zval *leftScope = pt_expression_result_scope(leftResult.raw(), hold);
			if (UNEXPECTED(leftScope == NULL)) return zv::Val();
			zv::Val rightHold;
			zval *rightScope = pt_expression_result_scope(rightResult.raw(), rightHold);
			if (UNEXPECTED(rightScope == NULL)) return zv::Val();
			leftMergedWithRightScope = pt_mutating_scope_merge_with(Z_OBJ_P(leftScope), rightScope);
		}
		if (UNEXPECTED(leftMergedWithRightScope.isUndef())) return zv::Val();

		zv::Val variableFlow = sequenceWithOptionalRight(leftResult.raw(), rightResult.raw());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		bool leftHasYield, rightHasYield = false, isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_has_yield(leftResult.raw(), leftHasYield))) return zv::Val();
		if (!leftHasYield && UNEXPECTED(!pt_expression_result_has_yield(rightResult.raw(), rightHasYield))) return zv::Val();
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(leftResult.raw(), isAlwaysTerminating))) return zv::Val();
		zv::Val leftPointsHold, rightPointsHold;
		zval *leftThrowPoints = pt_expression_result_throw_points(leftResult.raw(), leftPointsHold);
		if (UNEXPECTED(leftThrowPoints == NULL)) return zv::Val();
		zval *rightThrowPoints = pt_expression_result_throw_points(rightResult.raw(), rightPointsHold);
		if (UNEXPECTED(rightThrowPoints == NULL)) return zv::Val();
		zv::Val throwPoints = ptoh::arrayMerge(leftThrowPoints, rightThrowPoints);
		zval *leftImpurePoints = pt_expression_result_impure_points(leftResult.raw(), leftPointsHold);
		if (UNEXPECTED(leftImpurePoints == NULL)) return zv::Val();
		zval *rightImpurePoints = pt_expression_result_impure_points(rightResult.raw(), rightPointsHold);
		if (UNEXPECTED(rightImpurePoints == NULL)) return zv::Val();
		zv::Val impurePoints = ptoh::arrayMerge(leftImpurePoints, rightImpurePoints);

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, leftResult.raw(), rightResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, nodeScopeResolver, scope, expr, leftResult.raw(), rightResult.raw());
		pt_expression_result_args args(leftMergedWithRightScope.raw(), scope, expr, leftHasYield || rightHasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw()).withFalseyScopeOverrideResult(rightResult.raw());
		zv::Val result = pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		// store before emitting the virtual node: its rules ask about the raw
		// expression, and a synchronously invoked rule (the plain resolver,
		// PHP < 8.1) must find the result in the storage instead of re-walking
		// it on demand; processExprNodeInternal()'s later store is a no-op
		if (UNEXPECTED(!pt_node_scope_resolver_store_expression_result(nodeScopeResolver, storage, expr, result.raw()))) return zv::Val();
		zv::Args nodeArgv{expr, leftFalseyScope.raw()};
		zv::Val node = pt_type_new(PT_CLASS_BOOLEAN_OR_NODE, 2, nodeArgv);
		if (UNEXPECTED(node.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback_with_expression(nodeScopeResolver, nodeCallback, node.raw(), scope, storage, context))) return zv::Val();

		return result;
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return BooleanOrHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\BooleanOrHandler::{closure}";

	/* VariableFlow::sequence($leftResult->getVariableFlow(),
	 * VariableFlow::choice($rightResult->getVariableFlow(), null)) */
	static zv::Val sequenceWithOptionalRight(zval *leftResult, zval *rightResult)
	{
		zv::Val leftFlow = pt_expression_result_variable_flow(leftResult);
		if (UNEXPECTED(leftFlow.isUndef())) return zv::Val();
		zv::Val rightFlow = pt_expression_result_variable_flow(rightResult);
		if (UNEXPECTED(rightFlow.isUndef())) return zv::Val();
		zv::Args choiceArgv{rightFlow.raw(), zv::null};
		zv::Val choice = pt_variable_flow_choice(2, choiceArgv);
		if (UNEXPECTED(choice.isUndef())) return zv::Val();
		zv::Args sequenceArgv{leftFlow.raw(), choice.raw()};
		return pt_variable_flow_sequence(2, sequenceArgv);
	}

	/* static function (bool $nativeTypesPromoted) use ($leftResult,
	 * $rightResult): Type — captures: $leftResult, $rightResult */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() { type = resolveType(&captures[0], &captures[1], nativeTypesPromoted); });
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	static zv::Val resolveType(zval *leftResult, zval *rightResult, bool nativeTypesPromoted)
	{
		zv::Val leftType = nativeTypesPromoted ? pt_expression_result_get_native_type(leftResult) : pt_expression_result_get_type(leftResult);
		if (UNEXPECTED(leftType.isUndef())) return zv::Val();
		ptoh::BooleanOf leftBooleanType;
		if (UNEXPECTED(!leftBooleanType.init(leftType.raw()))) return zv::Val();
		int leftTrue = leftBooleanType.isTrue();
		if (UNEXPECTED(leftTrue < 0)) return zv::Val();
		if (leftTrue) return ptoh::constantBoolean(true);

		// the right side was processed on the left-falsey scope including
		// the left's side effects (assignments, by-ref writes) - that
		// captured scope is the evaluation point, no re-walk and no
		// depth cap needed
		zv::Val rightType = nativeTypesPromoted ? pt_expression_result_get_native_type(rightResult) : pt_expression_result_get_type(rightResult);
		if (UNEXPECTED(rightType.isUndef())) return zv::Val();
		ptoh::BooleanOf rightBooleanType;
		if (UNEXPECTED(!rightBooleanType.init(rightType.raw()))) return zv::Val();
		int rightTrue = rightBooleanType.isTrue();
		if (UNEXPECTED(rightTrue < 0)) return zv::Val();
		if (rightTrue) return ptoh::constantBoolean(true);

		int leftFalse = leftBooleanType.isFalse();
		if (UNEXPECTED(leftFalse < 0)) return zv::Val();
		if (leftFalse) {
			int rightFalse = rightBooleanType.isFalse();
			if (UNEXPECTED(rightFalse < 0)) return zv::Val();
			if (rightFalse) return ptoh::constantBoolean(false);
		}

		return ptoh::booleanType();
	}

	/* fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes
	 * => $this->booleanNarrowingHelper->specifyDisjunction(...) — captures:
	 * $this, $nodeScopeResolver, $scope, $expr, $leftResult, $rightResult */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 2, closureName))) return;
		zval *nodeScopeResolver = &captures[1];
		zval *scope = &captures[2];
		zval *expr = &captures[3];
		zval *leftResult = &captures[4];
		zval *rightResult = &captures[5];
		zv::Val promotedScope;
		zval *s = scope;
		if (zend_is_true(&argv[1])) {
			promotedScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope));
			if (UNEXPECTED(promotedScope.isUndef())) return;
			s = promotedScope.raw();
		}
		zval *left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return;
		zv::Val leftTypes = pt_native_closure(&specifiedTypesForScopeBody, leftResult);
		zv::Val leftType = pt_native_closure(&resultTypeBody, leftResult);
		zv::Val leftTruthyScope = pt_native_closure(&truthyScopeBody, leftResult);
		zv::Val leftFalseyScope = pt_native_closure(&falseyScopeBody, leftResult);
		zval *right = ptoh::binaryOpRight(expr);
		if (UNEXPECTED(right == NULL)) return;
		zv::Val rightTypes = pt_native_closure(&specifiedTypesForScopeBody, rightResult);
		zv::Val rightType = pt_native_closure(&resultTypeBody, rightResult);
		zv::Val rightTruthyScope = pt_native_closure(&truthyScopeBody, rightResult);
		zv::Val specifiedTypes = pt_boolean_narrowing_helper_specify_disjunction(Z_OBJ_P(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::booleanNarrowingHelper)), nodeScopeResolver, s, &argv[0], expr, left, leftTypes.raw(), leftType.raw(), leftTruthyScope.raw(), leftFalseyScope.raw(), right, rightTypes.raw(), rightType.raw(), rightTruthyScope.raw());
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* static fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes
	 * => $result->getSpecifiedTypesForScope($scope, $ctx) — captures: the
	 * operand result */
	static void specifiedTypesForScopeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 2, closureName))) return;
		zv::Val specifiedTypes = pt_expression_result_get_specified_types_for_scope(&captures[0], &argv[0], &argv[1]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* static fn (bool $nativeTypesPromoted): Type => $nativeTypesPromoted ?
	 * $result->getNativeType() : $result->getType() — captures: the operand
	 * result */
	static void resultTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 1, closureName))) return;
		zv::Val type = zend_is_true(&argv[0]) ? pt_expression_result_get_native_type(&captures[0]) : pt_expression_result_get_type(&captures[0]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* static fn (): MutatingScope => $result->getTruthyScope() — captures:
	 * the operand result */
	static void truthyScopeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zv::Val truthyScope = pt_expression_result_get_truthy_scope(&captures[0]);
		if (UNEXPECTED(truthyScope.isUndef())) return;
		truthyScope.intoReturnValue(return_value);
	}

	/* static fn (): MutatingScope => $result->getFalseyScope() — captures:
	 * the operand result */
	static void falseyScopeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zv::Val falseyScope = pt_expression_result_get_falsey_scope(&captures[0]);
		if (UNEXPECTED(falseyScope.isUndef())) return;
		falseyScope.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::BooleanOrHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_boolean_or_handler()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\BooleanOrHandler");
	ptdecl::BooleanOrHandler::declareClass(cls);
	ptdecl::BooleanOrHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *booleanNarrowingHelper, *expressionResultFactory;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, booleanNarrowingHelper, expressionResultFactory)) RETURN_THROWS();
		BooleanOrHandler(Z_OBJ_P(ZEND_THIS)).construct(booleanNarrowingHelper, expressionResultFactory);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!BooleanOrHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(BooleanOrHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_boolean_or_handler);
	pt_expr_handler_entry_register(&pt_ce_boolean_or_handler, &BooleanOrHandler::processExprEntry);
}

/* }}} */
