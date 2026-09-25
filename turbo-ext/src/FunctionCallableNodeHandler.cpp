/*
 * PHPStanTurbo\FunctionCallableNodeHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\Virtual\FunctionCallableNodeHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($this, $beforeScope,
 * $expr, $nameResult) and the specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, MutatingScope,
 * SpecifiedTypes, DefaultNarrowingHelper, InitializerExprTypeResolver and the
 * Type kernel are called through their direct entries; InitializerExprContext,
 * which stays PHP for now, through the cached method site of
 * VirtualExprHandlers.h.
 */

#include "support.h"
#include "generated/FunctionCallableNodeHandler.h"

namespace slots = ptdecl::FunctionCallableNodeHandler::slot;
namespace sigs = ptdecl::FunctionCallableNodeHandler::sig;
#include "VirtualExprHandlers.h"

zend_class_entry *pt_ce_function_callable_node_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Virtual\FunctionCallableNodeHandler;
 * UNDEF = pending exception. */
class FunctionCallableNodeHandler
{
public:
	explicit FunctionCallableNodeHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper, zval *initializerExprTypeResolver) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
		pt_write_slot(self, slots::initializerExprTypeResolver, initializerExprTypeResolver);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		return ptveh::supportsInstance(expr, PT_CLASS_FUNCTION_CALLABLE_NODE, out);
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zval *currentScope = scope;
		zval *throwPoints = NULL;
		zval *impurePoints = NULL;
		bool hasYield = false;
		bool isAlwaysTerminating = false;
		zv::Val nameResult = zv::Val::null();
		zv::Val nameHold, scopeHold, throwPointsHold, impurePointsHold;
		zval *name = ptveh::getterRead(ptveh::functionCallableNodeName, expr, PT_LC("getname"), nameHold);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		int nameIsExpr = ptveh::isInstance(name, PT_CLASS_EXPR);
		if (UNEXPECTED(nameIsExpr < 0)) return zv::Val();
		if (nameIsExpr) {
			name = ptveh::getterRead(ptveh::functionCallableNodeName, expr, PT_LC("getname"), nameHold);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			zv::Val nameContext = ptveh::createDeepContext(context);
			if (UNEXPECTED(nameContext.isUndef())) return zv::Val();
			nameResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, name, scope, storage, nodeCallback, nameContext.raw());
			if (UNEXPECTED(nameResult.isUndef())) return zv::Val();
			currentScope = pt_expression_result_scope(nameResult.raw(), scopeHold);
			if (UNEXPECTED(currentScope == NULL)) return zv::Val();
			if (UNEXPECTED(!pt_expression_result_has_yield(nameResult.raw(), hasYield))) return zv::Val();
			throwPoints = pt_expression_result_throw_points(nameResult.raw(), throwPointsHold);
			if (UNEXPECTED(throwPoints == NULL)) return zv::Val();
			impurePoints = pt_expression_result_impure_points(nameResult.raw(), impurePointsHold);
			if (UNEXPECTED(impurePoints == NULL)) return zv::Val();
			if (UNEXPECTED(!pt_expression_result_is_always_terminating(nameResult.raw(), isAlwaysTerminating))) return zv::Val();
		}

		zv::Val variableFlow = zv::Val::null();
		if (!nameResult.isNull()) {
			variableFlow = pt_expression_result_variable_flow(nameResult.raw());
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, beforeScope, expr, nameResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr);
		pt_expression_result_args args(currentScope, beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints, impurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* Mirrors resolveType(). */
	zv::Val resolveType(zval *scope, zval *expr, zval *nameResult) const
	{
		zv::Val originalNodeHold;
		zval *originalNode = ptveh::getterRead(ptveh::functionCallableNodeOriginalNode, expr, PT_LC("getoriginalnode"), originalNodeHold);
		if (UNEXPECTED(originalNode == NULL)) return zv::Val();
		zval *originalName = ptveh::nodeRead(ptveh::funcCallName, originalNode);
		if (UNEXPECTED(originalName == NULL)) return zv::Val();
		int originalNameIsExpr = ptveh::isInstance(originalName, PT_CLASS_EXPR);
		if (UNEXPECTED(originalNameIsExpr < 0)) return zv::Val();
		if (originalNameIsExpr) {
			// $originalNode->name is the same node as $expr->getName(), processed
			// in processExpr exactly in this branch - read its ExpressionResult
			if (nameResult == NULL || Z_TYPE_P(nameResult) == IS_NULL) {
				zv::Val exception = pt_type_new(PT_CLASS_SHOULD_NOT_HAPPEN, 0, NULL);
				if (UNEXPECTED(exception.isUndef())) return zv::Val();
				zval raw = exception.take();
				zend_throw_exception_object(&raw);
				return zv::Val();
			}
			bool nativeTypesPromoted;
			if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return zv::Val();
			zv::Val callableType = pt_expression_result_get_type_on_scope(nameResult, scope, nativeTypesPromoted);
			if (UNEXPECTED(callableType.isUndef())) return zv::Val();
			zend_long isCallable = pt_type_op_trinary(Z_OBJ_P(callableType.raw()), PT_OP_IS_CALLABLE, 0, NULL);
			if (UNEXPECTED(isCallable < 0)) return zv::Val();
			if (isCallable != PT_TRI_YES) {
				return ptveh::closureObjectType();
			}

			zv::Val acceptors = pt_type_call(Z_OBJ_P(callableType.raw()), PT_LC("getcallableparametersacceptors"), 1, scope);
			if (UNEXPECTED(acceptors.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return zv::Val();
			zval null = {};
			ZVAL_NULL(&null);
			return ptveh::createFirstClassCallable(OBJ_PROP_NUM(self, slots::initializerExprTypeResolver), &null, acceptors.raw(), nativeTypesPromoted);
		}

		zv::Val initializerExprContext = ptveh::initializerExprContextFromScope(scope);
		if (UNEXPECTED(initializerExprContext.isUndef())) return zv::Val();
		bool nativeTypesPromoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return zv::Val();
		return ptveh::getFirstClassCallableType(OBJ_PROP_NUM(self, slots::initializerExprTypeResolver), originalNode, initializerExprContext.raw(), nativeTypesPromoted);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return FunctionCallableNodeHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

	static constexpr char closureName[] = "PHPStan\\Analyser\\ExprHandler\\Virtual\\FunctionCallableNodeHandler::{closure}";

private:
	zend_object *self;

	/* fn (bool $nativeTypesPromoted): Type => $this->resolveType($nativeTypesPromoted
	 * ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope, $expr,
	 * $nameResult) — captures: $this, $beforeScope, $expr, $nameResult */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptveh::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() {
			zv::Val scopeHold;
			zval *scope = ptveh::promotedScope(&captures[1], nativeTypesPromoted, scopeHold);
			if (UNEXPECTED(scope == NULL)) return;
			type = FunctionCallableNodeHandler(Z_OBJ(captures[0])).resolveType(scope, &captures[2], &captures[3]);
		});
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) =>
	 * $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context) —
	 * captures: $this, $expr */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		ptveh::specifyDefaultTypesBody<slots::defaultNarrowingHelper, closureName>(captures, argc, argv, return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::FunctionCallableNodeHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_function_callable_node_handler)
{
	ptveh::initLiterals();

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Virtual\\FunctionCallableNodeHandler");
	ptdecl::FunctionCallableNodeHandler::declareClass(cls);
	ptdecl::FunctionCallableNodeHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper, *initializerExprTypeResolver;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper, initializerExprTypeResolver)) RETURN_THROWS();
		FunctionCallableNodeHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper, initializerExprTypeResolver);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!FunctionCallableNodeHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(FunctionCallableNodeHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::resolveType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *nameResult;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT_OR_NULL(nameResult)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(FunctionCallableNodeHandler(Z_OBJ_P(ZEND_THIS)).resolveType(scope, expr, nameResult));
	});

	cls.shadow(&pt_ce_function_callable_node_handler);
	pt_expr_handler_entry_register(&pt_ce_function_callable_node_handler, &FunctionCallableNodeHandler::processExprEntry);
}

/* }}} */
