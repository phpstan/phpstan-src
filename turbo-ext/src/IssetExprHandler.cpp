/*
 * PHPStanTurbo\IssetExprHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\Virtual\IssetExprHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($nodeScopeResolver, $expr,
 * $scope) and the specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, MutatingScope, SpecifiedTypes and DefaultNarrowingHelper
 * are called through their direct entries; the virtual node stays PHP and is
 * read in its slot (VirtualExprHandlers.h).
 */

#include "support.h"
#include "generated/IssetExprHandler.h"

namespace slots = ptdecl::IssetExprHandler::slot;
namespace sigs = ptdecl::IssetExprHandler::sig;
#include "VirtualExprHandlers.h"

zend_class_entry *pt_ce_isset_expr_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Virtual\IssetExprHandler; UNDEF = pending
 * exception. */
class IssetExprHandler
{
public:
	explicit IssetExprHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		return ptveh::supportsInstance(expr, PT_CLASS_ISSET_EXPR, out);
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		(void) stmt;
		(void) storage;
		(void) nodeCallback;
		(void) context;
		// because this is a virtual node handler, the caller will only be interested
		// in the type - we don't process the inner expr, just report its type
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, nodeScopeResolver, expr, scope);
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr);
		pt_expression_result_args args(scope, scope, expr, false, false, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return IssetExprHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

	static constexpr char closureName[] = "PHPStan\\Analyser\\ExprHandler\\Virtual\\IssetExprHandler::{closure}";

private:
	zend_object *self;

	/* static fn (bool $nativeTypesPromoted): Type =>
	 * $nodeScopeResolver->readScopeStateOrSyntheticType($expr->getExpr(),
	 * $nativeTypesPromoted ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope)
	 * — captures: $nodeScopeResolver, $expr, $scope */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptveh::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() {
			zv::Val innerHold;
			zval *inner = ptveh::getterRead(ptveh::issetExprExpr, &captures[1], PT_LC("getexpr"), innerHold);
			if (UNEXPECTED(inner == NULL)) return;
			zv::Val scopeHold;
			zval *scope = ptveh::promotedScope(&captures[2], nativeTypesPromoted, scopeHold);
			if (UNEXPECTED(scope == NULL)) return;
			type = pt_node_scope_resolver_read_scope_state_or_synthetic_type(&captures[0], inner, scope);
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

using phpstanturbo::IssetExprHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_isset_expr_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Virtual\\IssetExprHandler");
	ptdecl::IssetExprHandler::declareClass(cls);
	ptdecl::IssetExprHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		IssetExprHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!IssetExprHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(IssetExprHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_isset_expr_handler);
	pt_expr_handler_entry_register(&pt_ce_isset_expr_handler, &IssetExprHandler::processExprEntry);
}

/* }}} */
