/*
 * PHPStanTurbo\BitwiseNotHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\BitwiseNotHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($this, $expr, $exprResult),
 * the $getTypeCallback it hands InitializerExprTypeResolver (a stack
 * capture array:
 * $nativeTypesPromoted, $expr, $exprResult) and the specifyTypesCallback
 * ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext,
 * DefaultNarrowingHelper and the Type kernel are called through their direct
 * entries, and so is InitializerExprTypeResolver::getBitwiseNotType() (which
 * consults the unary operator extensions), handed the $getTypeCallback as a
 * pt_ietr_get_type over the captures (it calls it synchronously).
 */

#include "support.h"
#include "generated/BitwiseNotHandler.h"

namespace slots = ptdecl::BitwiseNotHandler::slot;
namespace sigs = ptdecl::BitwiseNotHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_bitwise_not_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_bnh_expr = PT_NODE_PROP(PT_CLASS_BITWISE_NOT, "expr");


} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\BitwiseNotHandler; UNDEF = pending
 * exception. */
class BitwiseNotHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\BitwiseNotHandler::{closure}";
	static constexpr uint32_t defaultNarrowingHelperSlot = slots::defaultNarrowingHelper;

	explicit BitwiseNotHandler(zend_object *self) : self(self) {}

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
		int is = ptoh::isInstance(expr, PT_CLASS_BITWISE_NOT);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *inner = ptoh::operand(pt_bnh_expr, expr);
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		zv::Val innerContext = pt_expression_context_enter_deep_keeping_value_flow(context);
		if (UNEXPECTED(innerContext.isUndef())) return zv::Val();
		zv::Val exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, inner, scope, storage, nodeCallback, innerContext.raw());
		if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		ptse::ChildResult child;
		if (UNEXPECTED(!child.read(exprResult.raw()))) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, exprResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<BitwiseNotHandler>, self, expr);
		pt_expression_result_args args(child.scope, scope, expr, child.hasYield, child.isAlwaysTerminating, child.throwPoints, child.impurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(child.variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return BitwiseNotHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* fn (bool $nativeTypesPromoted) =>
	 * $this->initializerExprTypeResolver->getBitwiseNotType($expr->expr, ...) —
	 * captures: $this, $expr, $exprResult */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptse::requireArgs(argc, 1, closureName))) return;
		zval nativeTypesPromoted;
		ZVAL_BOOL(&nativeTypesPromoted, zend_is_true(&argv[0]));
		zv::Val type;
		pt_engine_with_stack([&]() {
			zval *inner = ptoh::operand(pt_bnh_expr, &captures[1]);
			if (UNEXPECTED(inner == NULL)) return;
			// $getType's captures ($nativeTypesPromoted, $expr, $exprResult),
			// borrowed: the resolver calls it synchronously
			zval getTypeCaptures[3];
			ZVAL_COPY_VALUE(&getTypeCaptures[0], &nativeTypesPromoted);
			ZVAL_COPY_VALUE(&getTypeCaptures[1], &captures[1]);
			ZVAL_COPY_VALUE(&getTypeCaptures[2], &captures[2]);
			pt_ietr_get_type getType{&getTypeCallback, getTypeCaptures, &getTypeCallable};
			type = pt_initializer_expr_type_resolver_get_bitwise_not_type(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::initializerExprTypeResolver), inner, getType);
		});
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* the $getType as InitializerExprTypeResolver calls it — data: the
	 * captures */
	static zv::Val getTypeCallback(void *data, zval *e)
	{
		zval type;
		ZVAL_NULL(&type);
		getTypeCallbackBody(static_cast<zval *>(data), 1, e, &type);
		if (UNEXPECTED(EG(exception) != NULL)) {
			zval_ptr_dtor(&type);
			return zv::Val();
		}
		return zv::Val::adopt(type);
	}

	/* the $getType as a PHP callable that outlives the call: the closure over
	 * copies of the captures */
	static zv::Val getTypeCallable(void *data)
	{
		return pt_native_closure_new(&getTypeCallbackBody, 3, static_cast<zval *>(data));
	}

	/* static function (Expr $e) use ($nativeTypesPromoted, $expr,
	 * $exprResult): Type — captures: $nativeTypesPromoted, $expr, $exprResult */
	static void getTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptse::requireArgs(argc, 1, closureName))) return;
		zval *inner = ptoh::operand(pt_bnh_expr, &captures[1]);
		if (UNEXPECTED(inner == NULL)) return;
		if (Z_TYPE(argv[0]) == IS_OBJECT && Z_OBJ(argv[0]) == Z_OBJ_P(inner)) {
			zv::Val type;
			pt_engine_with_stack([&]() { type = ptse::typeOf(&captures[2], Z_TYPE(captures[0]) == IS_TRUE); });
			if (UNEXPECTED(type.isUndef())) return;
			type.intoReturnValue(return_value);
			return;
		}

		pt_throw_should_not_happen();
	}
};

} // namespace phpstanturbo

using phpstanturbo::BitwiseNotHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_bitwise_not_handler()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\BitwiseNotHandler");
	ptdecl::BitwiseNotHandler::declareClass(cls);
	ptdecl::BitwiseNotHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver, *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, initializerExprTypeResolver, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		BitwiseNotHandler(Z_OBJ_P(ZEND_THIS)).construct(initializerExprTypeResolver, expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!BitwiseNotHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(BitwiseNotHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_bitwise_not_handler);
	pt_expr_handler_entry_register(&pt_ce_bitwise_not_handler, &BitwiseNotHandler::processExprEntry);
}

/* }}} */
