/*
 * PHPStanTurbo\InstantiationCallableNodeHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\Virtual\InstantiationCallableNodeHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($this, $expr,
 * $beforeScope) and the specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, VariableFlow,
 * SpecifiedTypes, DefaultNarrowingHelper and InitializerExprTypeResolver are
 * called through their direct entries; InitializerExprContext, which stays PHP
 * for now, through the cached method site of VirtualExprHandlers.h.
 */

#include "support.h"
#include "generated/InstantiationCallableNodeHandler.h"

namespace slots = ptdecl::InstantiationCallableNodeHandler::slot;
namespace sigs = ptdecl::InstantiationCallableNodeHandler::sig;
#include "VirtualExprHandlers.h"
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_instantiation_callable_node_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Virtual\InstantiationCallableNodeHandler;
 * UNDEF = pending exception. */
class InstantiationCallableNodeHandler
{
public:
	explicit InstantiationCallableNodeHandler(zend_object *self) : self(self) {}

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
		return ptveh::supportsInstance(expr, PT_CLASS_INSTANTIATION_CALLABLE_NODE, out);
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zval *currentScope = scope;
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		zv::Val throwPoints = zv::Val::copyOf(zv::Ref(&emptyArray));
		zv::Val impurePoints = zv::Val::copyOf(zv::Ref(&emptyArray));
		bool hasYield = false;
		bool isAlwaysTerminating = false;
		zv::Val classResult = zv::Val::null();
		zv::Val classHold, classScopeHold, classThrowPointsHold, classImpurePointsHold;
		zval *classNode = ptveh::getterRead(ptveh::instantiationCallableNodeClass, expr, PT_LC("getclass"), classHold);
		if (UNEXPECTED(classNode == NULL)) return zv::Val();
		int classIsExpr = ptveh::isInstance(classNode, PT_CLASS_EXPR);
		if (UNEXPECTED(classIsExpr < 0)) return zv::Val();
		if (classIsExpr) {
			classNode = ptveh::getterRead(ptveh::instantiationCallableNodeClass, expr, PT_LC("getclass"), classHold);
			if (UNEXPECTED(classNode == NULL)) return zv::Val();
			zv::Val classContext = ptveh::createDeepContext(context);
			if (UNEXPECTED(classContext.isUndef())) return zv::Val();
			classResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, classNode, currentScope, storage, nodeCallback, classContext.raw());
			if (UNEXPECTED(classResult.isUndef())) return zv::Val();
			currentScope = pt_expression_result_scope(classResult.raw(), classScopeHold);
			if (UNEXPECTED(currentScope == NULL)) return zv::Val();
			if (UNEXPECTED(!pt_expression_result_has_yield(classResult.raw(), hasYield))) return zv::Val();
			zval *borrowed = pt_expression_result_throw_points(classResult.raw(), classThrowPointsHold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			throwPoints = zv::Val::copyOf(zv::Ref(borrowed));
			borrowed = pt_expression_result_impure_points(classResult.raw(), classImpurePointsHold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			impurePoints = zv::Val::copyOf(zv::Ref(borrowed));
			if (UNEXPECTED(!pt_expression_result_is_always_terminating(classResult.raw(), isAlwaysTerminating))) return zv::Val();
		}

		zv::Val classFlow = zv::Val::null();
		if (!classResult.isNull()) {
			classFlow = pt_expression_result_variable_flow(classResult.raw());
			if (UNEXPECTED(classFlow.isUndef())) return zv::Val();
		}
		zv::Val variableFlow = pt_variable_flow_sequence(1, classFlow.raw());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, beforeScope);
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr);
		pt_expression_result_args args(currentScope, beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return InstantiationCallableNodeHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

	static constexpr char closureName[] = "PHPStan\\Analyser\\ExprHandler\\Virtual\\InstantiationCallableNodeHandler::{closure}";

private:
	zend_object *self;

	/* fn (bool $nativeTypesPromoted): Type =>
	 * $this->initializerExprTypeResolver->getFirstClassCallableType($expr->getOriginalNode(),
	 * InitializerExprContext::fromScope($beforeScope), $nativeTypesPromoted) —
	 * captures: $this, $expr, $beforeScope */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptveh::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() {
			zv::Val originalNodeHold;
			zval *originalNode = ptveh::getterRead(ptveh::instantiationCallableNodeOriginalNode, &captures[1], PT_LC("getoriginalnode"), originalNodeHold);
			if (UNEXPECTED(originalNode == NULL)) return;
			zv::Val initializerExprContext = ptveh::initializerExprContextFromScope(&captures[2]);
			if (UNEXPECTED(initializerExprContext.isUndef())) return;
			type = ptveh::getFirstClassCallableType(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::initializerExprTypeResolver), originalNode, initializerExprContext.raw(), nativeTypesPromoted);
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

using phpstanturbo::InstantiationCallableNodeHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_instantiation_callable_node_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Virtual\\InstantiationCallableNodeHandler");
	ptdecl::InstantiationCallableNodeHandler::declareClass(cls);
	ptdecl::InstantiationCallableNodeHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper, *initializerExprTypeResolver;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper, initializerExprTypeResolver)) RETURN_THROWS();
		InstantiationCallableNodeHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper, initializerExprTypeResolver);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!InstantiationCallableNodeHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(InstantiationCallableNodeHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_instantiation_callable_node_handler);
	pt_expr_handler_entry_register(&pt_ce_instantiation_callable_node_handler, &InstantiationCallableNodeHandler::processExprEntry);
}

/* }}} */
