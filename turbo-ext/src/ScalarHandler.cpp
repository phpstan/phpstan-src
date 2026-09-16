/*
 * PHPStanTurbo\ScalarHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\ScalarHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the
 * class's handler entry (Engine.h), so native callers dispatch to it
 * without an engine frame. The typeCallback is a native closure capturing
 * what the twin's arrow function captures ($this, $expr and the initializer
 * context); the result is built through pt_expression_result_create().
 *
 * SpecifiedTypes is called through its direct entry; the collaborators that
 * stay PHP for now — InitializerExprContext and InitializerExprTypeResolver —
 * through the cached method sites in the block below, one helper each.
 */

#include "support.h"
#include "generated/ScalarHandler.h"

namespace slots = ptdecl::ScalarHandler::slot;
namespace sigs = ptdecl::ScalarHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"

zend_class_entry *pt_ce_scalar_handler = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_sh_from_scope_site;
pt_method_site pt_sh_get_type_site;

/* InitializerExprContext::fromScope($scope) */
zv::Val initializerExprContextFromScope(zval *scope)
{
	return pt_call_static_cached(pt_sh_from_scope_site, PT_CLASS_INITIALIZER_EXPR_CONTEXT, PT_LC("fromscope"), 1, scope);
}

/* $initializerExprTypeResolver->getType($expr, $context) */
zv::Val initializerExprType(zval *initializerExprTypeResolver, zval *expr, zval *context)
{
	zv::Args argv{expr, context};
	return pt_call_method_cached(pt_sh_get_type_site, Z_OBJ_P(initializerExprTypeResolver), PT_LC("gettype"), 2, argv);
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\ScalarHandler; UNDEF = pending
 * exception. */
class ScalarHandler
{
public:
	explicit ScalarHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *initializerExprTypeResolver, zval *expressionResultFactory)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::initializerExprTypeResolver, zv::Val::copyOf(zv::Ref(initializerExprTypeResolver)));
		object.propAtWrite(slots::expressionResultFactory, zv::Val::copyOf(zv::Ref(expressionResultFactory)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		zend_class_entry *scalarCe = pt_class(PT_CLASS_SCALAR);
		if (UNEXPECTED(scalarCe == NULL)) return false;
		if (!instanceof_function(Z_OBJCE_P(expr), scalarCe)) {
			out = false;
			return true;
		}
		zend_class_entry *interpolatedStringCe = pt_class(PT_CLASS_INTERPOLATED_STRING);
		if (UNEXPECTED(interpolatedStringCe == NULL)) return false;
		out = !instanceof_function(Z_OBJCE_P(expr), interpolatedStringCe);
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		(void) nodeScopeResolver;
		(void) stmt;
		(void) storage;
		(void) nodeCallback;
		(void) context;
		// a literal's type and its initializer context (file/namespace/class) are
		// lexical - identical on every scope - so build the context once here.
		zv::Val initializerExprContext = initializerExprContextFromScope(scope);
		if (UNEXPECTED(initializerExprContext.isUndef())) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, initializerExprContext.raw());
		zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
		if (UNEXPECTED(specifyTypesCallback.isUndef())) return zv::Val();
		pt_expression_result_args args(scope, scope, expr, false, false, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ScalarHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* fn () => $this->initializerExprTypeResolver->getType($expr, $initializerExprContext)
	 * — captures: $this, $expr, $initializerExprContext */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zval *handler = &captures[0];
		zv::Val type = initializerExprType(OBJ_PROP_NUM(Z_OBJ_P(handler), slots::initializerExprTypeResolver), &captures[1], &captures[2]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ScalarHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_scalar_handler()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\ScalarHandler");
	ptdecl::ScalarHandler::declareClass(cls);
	ptdecl::ScalarHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver, *expressionResultFactory;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, initializerExprTypeResolver, expressionResultFactory)) RETURN_THROWS();
		ScalarHandler(Z_OBJ_P(ZEND_THIS)).construct(initializerExprTypeResolver, expressionResultFactory);
	});

	cls.method<&ScalarHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(ScalarHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_scalar_handler);
	pt_expr_handler_entry_register(&pt_ce_scalar_handler, &ScalarHandler::processExprEntry);
}

/* }}} */
