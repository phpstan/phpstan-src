/*
 * PHPStanTurbo\ErrorSuppressHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\ErrorSuppressHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($exprResult) and the
 * specifyTypesCallback ($exprResult, $expr).
 *
 * NodeScopeResolver, ExpressionResult and SpecifiedTypes are called through
 * their direct entries.
 */

#include "support.h"
#include "generated/ErrorSuppressHandler.h"

namespace slots = ptdecl::ErrorSuppressHandler::slot;
namespace sigs = ptdecl::ErrorSuppressHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_error_suppress_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_esh_expr = PT_NODE_PROP(PT_CLASS_ERROR_SUPPRESS_EXPR, "expr");

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\ErrorSuppressHandler; UNDEF = pending
 * exception. */
class ErrorSuppressHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\ErrorSuppressHandler::{closure}";

	explicit ErrorSuppressHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *expressionResultFactory) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_ERROR_SUPPRESS_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *inner = ptoh::operand(pt_esh_expr, expr);
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		zv::Val exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, inner, scope, storage, nodeCallback, context);
		if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		ptse::ChildResult child;
		if (UNEXPECTED(!child.read(exprResult.raw()))) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&ptse::childTypeBody<ErrorSuppressHandler>, exprResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, exprResult.raw(), expr);
		pt_expression_result_args args(child.scope, scope, expr, child.hasYield, child.isAlwaysTerminating, child.throwPoints, child.impurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(child.variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ErrorSuppressHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* static fn (TypeSpecifierContext $context, bool $nativeTypesPromoted):
	 * SpecifiedTypes => $exprResult->getSpecifiedTypes($context,
	 * $nativeTypesPromoted)->setRootExpr($expr) — captures: $exprResult, $expr */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptse::requireArgs(argc, 2, closureName))) return;
		zv::Val types;
		pt_engine_with_stack([&]() {
			zv::Val specified = pt_expression_result_get_specified_types(&captures[0], &argv[0], zend_is_true(&argv[1]));
			if (UNEXPECTED(specified.isUndef())) return;
			if (UNEXPECTED(Z_TYPE_P(specified.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(specified.raw()));
				return;
			}
			types = pt_specified_types_set_root_expr(Z_OBJ_P(specified.raw()), &captures[1]);
		});
		if (UNEXPECTED(types.isUndef())) return;
		types.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ErrorSuppressHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_error_suppress_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\ErrorSuppressHandler");
	ptdecl::ErrorSuppressHandler::declareClass(cls);
	ptdecl::ErrorSuppressHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory;
		if (!zp::parse<zp::Obj>(execute_data, expressionResultFactory)) RETURN_THROWS();
		ErrorSuppressHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!ErrorSuppressHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(ErrorSuppressHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_error_suppress_handler);
	pt_expr_handler_entry_register(&pt_ce_error_suppress_handler, &ErrorSuppressHandler::processExprEntry);
}

/* }}} */
