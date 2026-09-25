/*
 * PHPStanTurbo\IncludeHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\IncludeHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback (nothing) and the
 * specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, MutatingScope,
 * InternalThrowPoint, ImpurePoint, VariableFlow, DefaultNarrowingHelper and
 * the Type kernel are called through their direct entries.
 */

#include "support.h"
#include "generated/IncludeHandler.h"

namespace slots = ptdecl::IncludeHandler::slot;
namespace sigs = ptdecl::IncludeHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_include_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_inh_expr = PT_NODE_PROP(PT_CLASS_INCLUDE_EXPR, "expr");

NodeProp pt_inh_type = PT_NODE_PROP(PT_CLASS_INCLUDE_EXPR, "type");

/* the twin's literals, permanent interned strings (module startup) */
zend_string *pt_inh_include = nullptr;
zend_string *pt_inh_require = nullptr;

/* Include_::TYPE_INCLUDE / TYPE_INCLUDE_ONCE */
constexpr zend_long pt_inh_type_include = 1;
constexpr zend_long pt_inh_type_include_once = 2;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\IncludeHandler; UNDEF = pending
 * exception. */
class IncludeHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\IncludeHandler::{closure}";
	static constexpr uint32_t defaultNarrowingHelperSlot = slots::defaultNarrowingHelper;

	explicit IncludeHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_INCLUDE_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zval *inner = ptoh::operand(pt_inh_expr, expr);
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		zv::Val innerContext = pt_expression_context_enter_deep(context);
		if (UNEXPECTED(innerContext.isUndef())) return zv::Val();
		zv::Val exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, inner, scope, storage, nodeCallback, innerContext.raw());
		if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		zval *type = ptoh::operand(pt_inh_type, expr);
		if (UNEXPECTED(type == NULL)) return zv::Val();
		zend_string *identifier = Z_TYPE_P(type) == IS_LONG && (Z_LVAL_P(type) == pt_inh_type_include || Z_LVAL_P(type) == pt_inh_type_include_once) ? pt_inh_include : pt_inh_require;
		ptse::ChildResult child;
		if (UNEXPECTED(!child.read(exprResult.raw()))) return zv::Val();
		// the included file may read any variable
		zv::Val extractedScope = pt_mutating_scope_after_extract_call(Z_OBJ_P(child.scope));
		if (UNEXPECTED(extractedScope.isUndef())) return zv::Val();
		zv::Val resultScope = pt_mutating_scope_invalidate_volatile_expressions(Z_OBJ_P(extractedScope.raw()));
		if (UNEXPECTED(resultScope.isUndef())) return zv::Val();

		zv::Val throwPoint = pt_internal_throw_point_create_implicit(resultScope.raw(), expr);
		if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();

		zv::Val variableFlow;
		{
			zv::Val readAll = pt_variable_flow_all_read_all();
			if (UNEXPECTED(readAll.isUndef())) return zv::Val();
			zv::Val typeHold;
			zval *throwPointType = pt_internal_throw_point_type(throwPoint.raw(), typeHold);
			if (UNEXPECTED(throwPointType == NULL)) return zv::Val();
			zv::Val throwing = pt_variable_flow_throwing(throwPointType, true, false);
			if (UNEXPECTED(throwing.isUndef())) return zv::Val();
			zv::Args flows{child.variableFlow.raw(), readAll.raw(), throwing.raw()};
			variableFlow = pt_variable_flow_sequence(3, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		zv::Val throwPoints = ptse::mergeOne(child.throwPoints, std::move(throwPoint));
		if (UNEXPECTED(throwPoints.isUndef())) return zv::Val();
		zv::Val impurePoint = pt_impure_point_new(resultScope.raw(), expr, identifier, identifier, true);
		if (UNEXPECTED(impurePoint.isUndef())) return zv::Val();
		zv::Val impurePoints = ptse::mergeOne(child.impurePoints, std::move(impurePoint));
		if (UNEXPECTED(impurePoints.isUndef())) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&ptse::mixedTypeBody<IncludeHandler>);
		zv::Val specifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<IncludeHandler>, self, expr);
		pt_expression_result_args args(resultScope.raw(), beforeScope, expr, child.hasYield, child.isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return IncludeHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::IncludeHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_include_handler)
{
	pt_inh_include = zend_string_init_interned(PT_LC("include"), 1);
	pt_inh_require = zend_string_init_interned(PT_LC("require"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\IncludeHandler");
	ptdecl::IncludeHandler::declareClass(cls);
	ptdecl::IncludeHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		IncludeHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!IncludeHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(IncludeHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_include_handler);
	pt_expr_handler_entry_register(&pt_ce_include_handler, &IncludeHandler::processExprEntry);
}

/* }}} */
