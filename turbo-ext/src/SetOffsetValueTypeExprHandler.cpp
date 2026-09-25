/*
 * PHPStanTurbo\SetOffsetValueTypeExprHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\Virtual\SetOffsetValueTypeExprHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's typeCallback is a native closure
 * capturing what the PHP closure captures ($varResult, $dimResult,
 * $valueResult); the specifyTypesCallback is
 * SpecifiedTypes::emptySpecifyCallback().
 *
 * NodeScopeResolver, ExpressionResult, SpecifiedTypes and the Type kernel are
 * called through their direct entries; the virtual node stays PHP and is
 * read in its slot (VirtualExprHandlers.h).
 */

#include "support.h"
#include "generated/SetOffsetValueTypeExprHandler.h"

namespace slots = ptdecl::SetOffsetValueTypeExprHandler::slot;
namespace sigs = ptdecl::SetOffsetValueTypeExprHandler::sig;
#include "VirtualExprHandlers.h"

zend_class_entry *pt_ce_set_offset_value_type_expr_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Virtual\SetOffsetValueTypeExprHandler; UNDEF = pending
 * exception. */
class SetOffsetValueTypeExprHandler
{
public:
	explicit SetOffsetValueTypeExprHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *expressionResultFactory) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		return ptveh::supportsInstance(expr, PT_CLASS_SET_OFFSET_VALUE_TYPE_EXPR, out);
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		// virtual node: callers only read the type, computed lazily by the
		// typeCallback. The (synthetic) sub-expressions are processed here so the
		// typeCallback reads their ExpressionResults instead of Scope::getType().
		// A null specifyTypesCallback falls back to default narrowing in
		// TypeSpecifier, matching the old specifyDefaultTypes().
		zv::Val varHold, dimHold, valueHold;
		zval *var = ptveh::getterRead(ptveh::setOffsetValueTypeExprVar, expr, PT_LC("getvar"), varHold);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		zv::Val varResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, var, scope, storage, nodeCallback, context);
		if (UNEXPECTED(varResult.isUndef())) return zv::Val();
		zval *dim = ptveh::getterRead(ptveh::setOffsetValueTypeExprDim, expr, PT_LC("getdim"), dimHold);
		if (UNEXPECTED(dim == NULL)) return zv::Val();
		zv::Val dimResult = zv::Val::null();
		if (Z_TYPE_P(dim) != IS_NULL) {
			dim = ptveh::getterRead(ptveh::setOffsetValueTypeExprDim, expr, PT_LC("getdim"), dimHold);
			if (UNEXPECTED(dim == NULL)) return zv::Val();
			dimResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, dim, scope, storage, nodeCallback, context);
			if (UNEXPECTED(dimResult.isUndef())) return zv::Val();
		}
		zval *value = ptveh::getterRead(ptveh::setOffsetValueTypeExprValue, expr, PT_LC("getvalue"), valueHold);
		if (UNEXPECTED(value == NULL)) return zv::Val();
		zv::Val valueResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, value, scope, storage, nodeCallback, context);
		if (UNEXPECTED(valueResult.isUndef())) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, varResult.raw(), dimResult.raw(), valueResult.raw());
		zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
		if (UNEXPECTED(specifyTypesCallback.isUndef())) return zv::Val();
		pt_expression_result_args args(scope, scope, expr, false, false, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return SetOffsetValueTypeExprHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

	static constexpr char closureName[] = "PHPStan\\Analyser\\ExprHandler\\Virtual\\SetOffsetValueTypeExprHandler::{closure}";

private:
	zend_object *self;

	/* static fn (bool $nativeTypesPromoted): Type => ($nativeTypesPromoted ?
	 * $varResult->getNativeType() : $varResult->getType())->setOffsetValueType(
	 * $dimResult !== null ? ($nativeTypesPromoted ? $dimResult->getNativeType() :
	 * $dimResult->getType()) : null,
	 * ($nativeTypesPromoted ? $valueResult->getNativeType() :
	 * $valueResult->getType())) — captures: $varResult, $dimResult,
	 * $valueResult */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptveh::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() {
			zv::Val varType = ptveh::resultType(&captures[0], nativeTypesPromoted);
			if (UNEXPECTED(varType.isUndef())) return;
			zv::Val dimType = zv::Val::null();
			if (Z_TYPE(captures[1]) != IS_NULL) {
				dimType = ptveh::resultType(&captures[1], nativeTypesPromoted);
				if (UNEXPECTED(dimType.isUndef())) return;
			}
			zv::Val valueType = ptveh::resultType(&captures[2], nativeTypesPromoted);
			if (UNEXPECTED(valueType.isUndef())) return;
			if (UNEXPECTED(Z_TYPE_P(varType.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function setOffsetValueType() on %s", zend_zval_value_name(varType.raw()));
				return;
			}
			zv::Args callArgs{dimType.raw(), valueType.raw()};
			type = pt_type_call(Z_OBJ_P(varType.raw()), PT_LC("setoffsetvaluetype"), 2, callArgs);
		});
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::SetOffsetValueTypeExprHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_set_offset_value_type_expr_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Virtual\\SetOffsetValueTypeExprHandler");
	ptdecl::SetOffsetValueTypeExprHandler::declareClass(cls);
	ptdecl::SetOffsetValueTypeExprHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory;
		if (!zp::parse<zp::Obj>(execute_data, expressionResultFactory)) RETURN_THROWS();
		SetOffsetValueTypeExprHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!SetOffsetValueTypeExprHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(SetOffsetValueTypeExprHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_set_offset_value_type_expr_handler);
	pt_expr_handler_entry_register(&pt_ce_set_offset_value_type_expr_handler, &SetOffsetValueTypeExprHandler::processExprEntry);
}

/* }}} */
