/*
 * PHPStanTurbo\MethodCallableNodeHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\Virtual\MethodCallableNodeHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($this, $beforeScope,
 * $expr, $varResult) and the specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, VariableFlow,
 * MutatingScope, SpecifiedTypes, DefaultNarrowingHelper, the method
 * reflection and the Type kernel are called through their direct entries;
 * InitializerExprTypeResolver, which stays PHP for now, through the cached
 * method site of VirtualExprHandlers.h.
 */

#include "support.h"
#include "generated/MethodCallableNodeHandler.h"

namespace slots = ptdecl::MethodCallableNodeHandler::slot;
namespace sigs = ptdecl::MethodCallableNodeHandler::sig;
#include "VirtualExprHandlers.h"
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_method_callable_node_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Virtual\MethodCallableNodeHandler;
 * UNDEF = pending exception. */
class MethodCallableNodeHandler
{
public:
	explicit MethodCallableNodeHandler(zend_object *self) : self(self) {}

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
		return ptveh::supportsInstance(expr, PT_CLASS_METHOD_CALLABLE_NODE, out);
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zv::Val varHold, nameHold, scopeHold, nameScopeHold, throwPointsHold, impurePointsHold, nameThrowPointsHold, nameImpurePointsHold;
		zval *var = ptveh::getterRead(ptveh::methodCallableNodeVar, expr, PT_LC("getvar"), varHold);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		zv::Val varContext = ptveh::createDeepContext(context);
		if (UNEXPECTED(varContext.isUndef())) return zv::Val();
		zv::Val varResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, var, scope, storage, nodeCallback, varContext.raw());
		if (UNEXPECTED(varResult.isUndef())) return zv::Val();
		zval *currentScope = pt_expression_result_scope(varResult.raw(), scopeHold);
		if (UNEXPECTED(currentScope == NULL)) return zv::Val();
		bool hasYield;
		if (UNEXPECTED(!pt_expression_result_has_yield(varResult.raw(), hasYield))) return zv::Val();
		zval *borrowed = pt_expression_result_throw_points(varResult.raw(), throwPointsHold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val throwPoints = zv::Val::copyOf(zv::Ref(borrowed));
		borrowed = pt_expression_result_impure_points(varResult.raw(), impurePointsHold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val impurePoints = zv::Val::copyOf(zv::Ref(borrowed));
		bool isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(varResult.raw(), isAlwaysTerminating))) return zv::Val();
		zv::Val nameResult = zv::Val::null();
		zval *name = ptveh::getterRead(ptveh::methodCallableNodeName, expr, PT_LC("getname"), nameHold);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		int nameIsExpr = ptveh::isInstance(name, PT_CLASS_EXPR);
		if (UNEXPECTED(nameIsExpr < 0)) return zv::Val();
		if (nameIsExpr) {
			name = ptveh::getterRead(ptveh::methodCallableNodeName, expr, PT_LC("getname"), nameHold);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			zv::Val nameContext = ptveh::createDeepContext(context);
			if (UNEXPECTED(nameContext.isUndef())) return zv::Val();
			nameResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, name, currentScope, storage, nodeCallback, nameContext.raw());
			if (UNEXPECTED(nameResult.isUndef())) return zv::Val();
			currentScope = pt_expression_result_scope(nameResult.raw(), nameScopeHold);
			if (UNEXPECTED(currentScope == NULL)) return zv::Val();
			bool nameHasYield;
			if (UNEXPECTED(!pt_expression_result_has_yield(nameResult.raw(), nameHasYield))) return zv::Val();
			hasYield = hasYield || nameHasYield;
			borrowed = pt_expression_result_throw_points(nameResult.raw(), nameThrowPointsHold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			throwPoints = ptcall::arrayMerge(throwPoints.raw(), borrowed);
			borrowed = pt_expression_result_impure_points(nameResult.raw(), nameImpurePointsHold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			impurePoints = ptcall::arrayMerge(impurePoints.raw(), borrowed);
			if (!isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(nameResult.raw(), isAlwaysTerminating))) return zv::Val();
		}

		zv::Val varFlow = pt_expression_result_variable_flow(varResult.raw());
		if (UNEXPECTED(varFlow.isUndef())) return zv::Val();
		zv::Val nameFlow = zv::Val::null();
		if (!nameResult.isNull()) {
			nameFlow = pt_expression_result_variable_flow(nameResult.raw());
			if (UNEXPECTED(nameFlow.isUndef())) return zv::Val();
		}
		zv::Args flows{varFlow.raw(), nameFlow.raw()};
		zv::Val variableFlow = pt_variable_flow_sequence(2, flows);
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, beforeScope, expr, varResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr);
		pt_expression_result_args args(currentScope, beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* Mirrors resolveType(). */
	zv::Val resolveType(zval *scope, zval *expr, zval *varResult) const
	{
		zv::Val originalNodeHold;
		zval *originalNode = ptveh::getterRead(ptveh::methodCallableNodeOriginalNode, expr, PT_LC("getoriginalnode"), originalNodeHold);
		if (UNEXPECTED(originalNode == NULL)) return zv::Val();
		zval *originalName = ptveh::nodeRead(ptveh::methodCallName, originalNode);
		if (UNEXPECTED(originalName == NULL)) return zv::Val();
		int originalNameIsIdentifier = ptveh::isInstance(originalName, PT_CLASS_IDENTIFIER);
		if (UNEXPECTED(originalNameIsIdentifier < 0)) return zv::Val();
		if (!originalNameIsIdentifier) {
			return ptveh::closureObjectType();
		}

		// $originalNode->var is the same node as $expr->getVar(), processed in
		// processExpr - read its ExpressionResult instead of Scope::getType()
		bool nativeTypesPromoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return zv::Val();
		zv::Val varType = pt_expression_result_get_type_on_scope(varResult, scope, nativeTypesPromoted);
		if (UNEXPECTED(varType.isUndef())) return zv::Val();
		zval *methodName = ptveh::nodeRead(ptveh::identifierName, originalName);
		if (UNEXPECTED(methodName == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(methodName) != IS_STRING)) {
			zend_type_error("PhpParser\\Node\\Identifier::toString(): Return value must be of type string, %s returned", zend_zval_value_name(methodName));
			return zv::Val();
		}
		zv::Val method = pt_mutating_scope_get_method_reflection(Z_OBJ_P(scope), varType.raw(), Z_STR_P(methodName));
		if (UNEXPECTED(method.isUndef())) return zv::Val();
		if (method.isNull()) {
			return ptveh::closureObjectType();
		}

		zv::Val variants = pt_extended_method_reflection_call(method.raw(), PT_MR_GET_VARIANTS);
		if (UNEXPECTED(variants.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return zv::Val();
		return ptveh::createFirstClassCallable(OBJ_PROP_NUM(self, slots::initializerExprTypeResolver), method.raw(), variants.raw(), nativeTypesPromoted);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return MethodCallableNodeHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

	static constexpr char closureName[] = "PHPStan\\Analyser\\ExprHandler\\Virtual\\MethodCallableNodeHandler::{closure}";

private:
	zend_object *self;

	/* fn (bool $nativeTypesPromoted): Type => $this->resolveType($nativeTypesPromoted
	 * ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope, $expr,
	 * $varResult) — captures: $this, $beforeScope, $expr, $varResult */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptveh::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() {
			zv::Val scopeHold;
			zval *scope = ptveh::promotedScope(&captures[1], nativeTypesPromoted, scopeHold);
			if (UNEXPECTED(scope == NULL)) return;
			type = MethodCallableNodeHandler(Z_OBJ(captures[0])).resolveType(scope, &captures[2], &captures[3]);
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

using phpstanturbo::MethodCallableNodeHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_method_callable_node_handler()
{
	ptveh::initLiterals();

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Virtual\\MethodCallableNodeHandler");
	ptdecl::MethodCallableNodeHandler::declareClass(cls);
	ptdecl::MethodCallableNodeHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper, *initializerExprTypeResolver;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper, initializerExprTypeResolver)) RETURN_THROWS();
		MethodCallableNodeHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper, initializerExprTypeResolver);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!MethodCallableNodeHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(MethodCallableNodeHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::resolveType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *varResult;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, scope, expr, varResult)) RETURN_THROWS();
		PT_RETURN_VAL(MethodCallableNodeHandler(Z_OBJ_P(ZEND_THIS)).resolveType(scope, expr, varResult));
	});

	cls.shadow(&pt_ce_method_callable_node_handler);
	pt_expr_handler_entry_register(&pt_ce_method_callable_node_handler, &MethodCallableNodeHandler::processExprEntry);
}

/* }}} */
