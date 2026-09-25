/*
 * PHPStanTurbo\UnsetOffsetExprHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\Virtual\UnsetOffsetExprHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The sub-expressions are walked through
 * NodeScopeResolver's direct entry and the result is built by
 * VirtualExprResultHelper::createUnsetOffsetExprResult() through its direct
 * entry.
 */

#include "support.h"
#include "generated/UnsetOffsetExprHandler.h"

namespace slots = ptdecl::UnsetOffsetExprHandler::slot;
namespace sigs = ptdecl::UnsetOffsetExprHandler::sig;
#include "VirtualExprHandlers.h"

zend_class_entry *pt_ce_unset_offset_expr_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Virtual\UnsetOffsetExprHandler; UNDEF = pending
 * exception. */
class UnsetOffsetExprHandler
{
public:
	explicit UnsetOffsetExprHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *virtualExprResultHelper) const
	{
		pt_write_slot(self, slots::virtualExprResultHelper, virtualExprResultHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		return ptveh::supportsInstance(expr, PT_CLASS_UNSET_OFFSET_EXPR, out);
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		// virtual node: callers only read the type, computed lazily by the
		// typeCallback. The (synthetic) sub-expressions are processed here - by
		// on-demand time their real leaves are already stored, so this reads them
		// back; the typeCallback then reads the ExpressionResults instead of
		// Scope::getType(). A null specifyTypesCallback falls back to default
		// narrowing in TypeSpecifier, matching the old specifyDefaultTypes().
		zv::Val varHold, dimHold;
		zval *var = ptveh::getterRead(ptveh::unsetOffsetExprVar, expr, PT_LC("getvar"), varHold);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		zv::Val varResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, var, scope, storage, nodeCallback, context);
		if (UNEXPECTED(varResult.isUndef())) return zv::Val();
		zval *dim = ptveh::getterRead(ptveh::unsetOffsetExprDim, expr, PT_LC("getdim"), dimHold);
		if (UNEXPECTED(dim == NULL)) return zv::Val();
		zv::Val dimResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, dim, scope, storage, nodeCallback, context);
		if (UNEXPECTED(dimResult.isUndef())) return zv::Val();

		return pt_virtual_expr_result_helper_create_unset_offset_expr_result(OBJ_PROP_NUM(self, slots::virtualExprResultHelper), scope, expr, varResult.raw(), dimResult.raw());
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return UnsetOffsetExprHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::UnsetOffsetExprHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_unset_offset_expr_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Virtual\\UnsetOffsetExprHandler");
	ptdecl::UnsetOffsetExprHandler::declareClass(cls);
	ptdecl::UnsetOffsetExprHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *virtualExprResultHelper;
		if (!zp::parse<zp::Obj>(execute_data, virtualExprResultHelper)) RETURN_THROWS();
		UnsetOffsetExprHandler(Z_OBJ_P(ZEND_THIS)).construct(virtualExprResultHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!UnsetOffsetExprHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(UnsetOffsetExprHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_unset_offset_expr_handler);
	pt_expr_handler_entry_register(&pt_ce_unset_offset_expr_handler, &UnsetOffsetExprHandler::processExprEntry);
}

/* }}} */
