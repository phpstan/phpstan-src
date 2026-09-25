/*
 * PHPStanTurbo\ExistingArrayDimFetchHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\Virtual\ExistingArrayDimFetchHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's typeCallback is a native closure
 * capturing what the PHP closure captures ($arrayDimFetchResult); the
 * specifyTypesCallback is SpecifiedTypes::emptySpecifyCallback().
 *
 * NodeScopeResolver, ExpressionResult, SpecifiedTypes and the Type kernel are
 * called through their direct entries; the virtual node stays PHP and is
 * read in its slot (VirtualExprHandlers.h).
 */

#include "support.h"
#include "generated/ExistingArrayDimFetchHandler.h"

namespace slots = ptdecl::ExistingArrayDimFetchHandler::slot;
namespace sigs = ptdecl::ExistingArrayDimFetchHandler::sig;
#include "VirtualExprHandlers.h"

zend_class_entry *pt_ce_existing_array_dim_fetch_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Virtual\ExistingArrayDimFetchHandler; UNDEF = pending
 * exception. */
class ExistingArrayDimFetchHandler
{
public:
	explicit ExistingArrayDimFetchHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *expressionResultFactory) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		return ptveh::supportsInstance(expr, PT_CLASS_EXISTING_ARRAY_DIM_FETCH, out);
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		// virtual node: callers only read the type, computed lazily by the
		// typeCallback. The plain array dim fetch is processed here (its real
		// leaves are already stored by on-demand time) so the typeCallback reads
		// its ExpressionResult instead of Scope::getType(). A null
		// specifyTypesCallback falls back to default narrowing in TypeSpecifier,
		// matching the old specifyDefaultTypes().
		zv::Val varHold, dimHold;
		zval *var = ptveh::getterRead(ptveh::existingArrayDimFetchVar, expr, PT_LC("getvar"), varHold);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		zval *dim = ptveh::getterRead(ptveh::existingArrayDimFetchDim, expr, PT_LC("getdim"), dimHold);
		if (UNEXPECTED(dim == NULL)) return zv::Val();
		zv::Args nodeArgs{var, dim};
		zv::Val arrayDimFetch = pt_type_new(PT_CLASS_ARRAY_DIM_FETCH, 2, nodeArgs);
		if (UNEXPECTED(arrayDimFetch.isUndef())) return zv::Val();
		zv::Val arrayDimFetchResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, arrayDimFetch.raw(), scope, storage, nodeCallback, context);
		if (UNEXPECTED(arrayDimFetchResult.isUndef())) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, arrayDimFetchResult.raw());
		zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
		if (UNEXPECTED(specifyTypesCallback.isUndef())) return zv::Val();
		pt_expression_result_args args(scope, scope, expr, false, false, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ExistingArrayDimFetchHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

	static constexpr char closureName[] = "PHPStan\\Analyser\\ExprHandler\\Virtual\\ExistingArrayDimFetchHandler::{closure}";

private:
	zend_object *self;

	/* static fn (bool $nativeTypesPromoted): Type => ($nativeTypesPromoted ?
	 * $arrayDimFetchResult->getNativeType() : $arrayDimFetchResult->getType())
	 * — captures: $arrayDimFetchResult */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptveh::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() { type = ptveh::resultType(&captures[0], nativeTypesPromoted); });
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ExistingArrayDimFetchHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_existing_array_dim_fetch_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Virtual\\ExistingArrayDimFetchHandler");
	ptdecl::ExistingArrayDimFetchHandler::declareClass(cls);
	ptdecl::ExistingArrayDimFetchHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory;
		if (!zp::parse<zp::Obj>(execute_data, expressionResultFactory)) RETURN_THROWS();
		ExistingArrayDimFetchHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!ExistingArrayDimFetchHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(ExistingArrayDimFetchHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_existing_array_dim_fetch_handler);
	pt_expr_handler_entry_register(&pt_ce_existing_array_dim_fetch_handler, &ExistingArrayDimFetchHandler::processExprEntry);
}

/* }}} */
