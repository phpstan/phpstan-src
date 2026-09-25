/*
 * PHPStanTurbo\NativeTypeExprHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\Virtual\NativeTypeExprHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h); it hands the virtual node to
 * VirtualExprResultHelper::createTypeExprResult() through its direct entry.
 */

#include "support.h"
#include "generated/NativeTypeExprHandler.h"

namespace slots = ptdecl::NativeTypeExprHandler::slot;
namespace sigs = ptdecl::NativeTypeExprHandler::sig;
#include "VirtualExprHandlers.h"

zend_class_entry *pt_ce_native_type_expr_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Virtual\NativeTypeExprHandler; UNDEF = pending
 * exception. */
class NativeTypeExprHandler
{
public:
	explicit NativeTypeExprHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *virtualExprResultHelper) const
	{
		pt_write_slot(self, slots::virtualExprResultHelper, virtualExprResultHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		return ptveh::supportsInstance(expr, PT_CLASS_NATIVE_TYPE_EXPR, out);
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		(void) nodeScopeResolver;
		(void) stmt;
		(void) storage;
		(void) nodeCallback;
		(void) context;
		// because this is a virtual node handler, the caller will only be interested in the type
		// we don't need to process the inner expr
		return pt_virtual_expr_result_helper_create_type_expr_result(OBJ_PROP_NUM(self, slots::virtualExprResultHelper), scope, expr);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return NativeTypeExprHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::NativeTypeExprHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_native_type_expr_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Virtual\\NativeTypeExprHandler");
	ptdecl::NativeTypeExprHandler::declareClass(cls);
	ptdecl::NativeTypeExprHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *virtualExprResultHelper;
		if (!zp::parse<zp::Obj>(execute_data, virtualExprResultHelper)) RETURN_THROWS();
		NativeTypeExprHandler(Z_OBJ_P(ZEND_THIS)).construct(virtualExprResultHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!NativeTypeExprHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(NativeTypeExprHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_native_type_expr_handler);
	pt_expr_handler_entry_register(&pt_ce_native_type_expr_handler, &NativeTypeExprHandler::processExprEntry);
}

/* }}} */
