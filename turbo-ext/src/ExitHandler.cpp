/*
 * PHPStanTurbo\ExitHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\ExitHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback (nothing) and the
 * specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, the node's attributes, ExpressionResult,
 * ExpressionContext, ImpurePoint, VariableFlow, DefaultNarrowingHelper and
 * the Type kernel are called through their direct entries.
 */

#include "support.h"
#include "generated/ExitHandler.h"

namespace slots = ptdecl::ExitHandler::slot;
namespace sigs = ptdecl::ExitHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_exit_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_exh_expr = PT_NODE_PROP(PT_CLASS_EXIT_EXPR, "expr");

/* the twin's literals, permanent interned strings (module startup) */
zend_string *pt_exh_exit = nullptr;
zend_string *pt_exh_die = nullptr;

/* Exit_::KIND_DIE */
constexpr zend_long pt_exh_kind_die = 2;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\ExitHandler; UNDEF = pending
 * exception. */
class ExitHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\ExitHandler::{closure}";
	static constexpr uint32_t defaultNarrowingHelperSlot = slots::defaultNarrowingHelper;

	explicit ExitHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_EXIT_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		// $expr->getAttribute('kind', Exit_::KIND_EXIT): only KIND_DIE picks 'die'
		zv::Val kind = pt_engine_node_get_attribute(Z_OBJ_P(expr), PT_LC("kind"));
		if (UNEXPECTED(kind.isUndef())) return zv::Val();
		zend_string *identifier = Z_TYPE_P(kind.raw()) == IS_LONG && Z_LVAL_P(kind.raw()) == pt_exh_kind_die ? pt_exh_die : pt_exh_exit;
		zv::Val impurePoint = pt_impure_point_new(scope, expr, identifier, identifier, true);
		if (UNEXPECTED(impurePoint.isUndef())) return zv::Val();
		zv::Arr impurePointList = zv::Arr::create(1);
		impurePointList.push(std::move(impurePoint));
		zv::Val impurePoints = std::move(impurePointList);

		bool hasYield = false;
		zv::Val throwPoints = zv::Arr::empty();
		zv::Val variableFlow = zv::Val::null();
		zv::Val scopeHold;
		zval *inner = ptoh::operand(pt_exh_expr, expr);
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		if (Z_TYPE_P(inner) != IS_NULL) {
			zv::Val innerContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(innerContext.isUndef())) return zv::Val();
			zv::Val exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, inner, scope, storage, nodeCallback, innerContext.raw());
			if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
			ptse::ChildResult child;
			if (UNEXPECTED(!child.read(exprResult.raw()))) return zv::Val();
			variableFlow = std::move(child.variableFlow);
			hasYield = child.hasYield;
			throwPoints = zv::Val::copyOf(zv::Ref(child.throwPoints));
			if (UNEXPECTED(!ptse::mergeInto(impurePoints, child.impurePoints))) return zv::Val();
			scopeHold = zv::Val::copyOf(zv::Ref(child.scope));
			scope = scopeHold.raw();
		}

		zv::Val exitFlow = pt_variable_flow_exit_stop();
		if (UNEXPECTED(exitFlow.isUndef())) return zv::Val();
		zv::Args flows{variableFlow.raw(), exitFlow.raw()};
		zv::Val sequence = pt_variable_flow_sequence(2, flows);
		if (UNEXPECTED(sequence.isUndef())) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&ptse::nonAcceptingNeverTypeBody<ExitHandler>);
		zv::Val specifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<ExitHandler>, self, expr);
		pt_expression_result_args args(scope, beforeScope, expr, hasYield, true, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(sequence.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ExitHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::ExitHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_exit_handler)
{
	pt_exh_exit = zend_string_init_interned(PT_LC("exit"), 1);
	pt_exh_die = zend_string_init_interned(PT_LC("die"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\ExitHandler");
	ptdecl::ExitHandler::declareClass(cls);
	ptdecl::ExitHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		ExitHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!ExitHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(ExitHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_exit_handler);
	pt_expr_handler_entry_register(&pt_ce_exit_handler, &ExitHandler::processExprEntry);
}

/* }}} */
