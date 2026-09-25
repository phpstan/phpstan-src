/*
 * PHPStanTurbo\ShellExecHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\ShellExecHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback (nothing) and the
 * specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext,
 * ImplicitToStringCallHelper, VariableFlow, DefaultNarrowingHelper,
 * TypeCombinator and the Type kernel are called through their direct entries.
 */

#include "support.h"
#include "generated/ShellExecHandler.h"

namespace slots = ptdecl::ShellExecHandler::slot;
namespace sigs = ptdecl::ShellExecHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_shell_exec_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_seh_parts = PT_NODE_PROP(PT_CLASS_SHELL_EXEC_EXPR, "parts");

/* foreach ($expr->parts as $part): the array iterated (an addref'ed copy,
 * as foreach holds it), or UNDEF with the warning raised for a non-array;
 * false = pending exception */
[[nodiscard]] bool partsOf(zval *expr, zv::Arr &out)
{
	zval *parts = ptoh::operand(pt_seh_parts, expr);
	if (UNEXPECTED(parts == NULL)) return false;
	if (UNEXPECTED(Z_TYPE_P(parts) != IS_ARRAY)) {
		zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(parts));
		if (UNEXPECTED(EG(exception))) return false;
		out = zv::Arr::empty();
		return true;
	}
	out = zv::Arr::copyOfTable(Z_ARRVAL_P(parts));
	return true;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\ShellExecHandler; UNDEF =
 * pending exception. */
class ShellExecHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\ShellExecHandler::{closure}";
	static constexpr uint32_t defaultNarrowingHelperSlot = slots::defaultNarrowingHelper;

	explicit ShellExecHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *implicitToStringCallHelper, zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::implicitToStringCallHelper, implicitToStringCallHelper);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_SHELL_EXEC_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		bool hasYield = false;
		zv::Val throwPoints = zv::Arr::empty();
		zv::Arr variableFlows = zv::Arr::empty();
		zv::Val impurePoints = zv::Arr::empty();
		bool isAlwaysTerminating = false;
		zv::Val scopeHold;

		zv::Arr parts;
		if (UNEXPECTED(!partsOf(expr, parts))) return zv::Val();
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) return zv::Val();
		zval *helper = OBJ_PROP_NUM(self, slots::implicitToStringCallHelper);
		for (zv::ArrayEntry entry : zv::TableRef(parts.table())) {
			zval *part = entry.value().deref().raw();
			if (Z_TYPE_P(part) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(part), exprCe)) {
				continue;
			}
			zv::Val partContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(partContext.isUndef())) return zv::Val();
			zv::Val partResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, part, scope, storage, nodeCallback, partContext.raw());
			if (UNEXPECTED(partResult.isUndef())) return zv::Val();
			zv::Val variableFlow = pt_expression_result_variable_flow(partResult.raw());
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
			variableFlows.push(std::move(variableFlow));
			if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(partResult.raw(), hasYield))) return zv::Val();
			{
				zv::Val hold;
				zval *points = pt_expression_result_throw_points(partResult.raw(), hold);
				if (UNEXPECTED(points == NULL || !ptse::mergeInto(throwPoints, points))) return zv::Val();
			}
			{
				zv::Val hold;
				zval *points = pt_expression_result_impure_points(partResult.raw(), hold);
				if (UNEXPECTED(points == NULL || !ptse::mergeInto(impurePoints, points))) return zv::Val();
			}

			zv::Val toStringResult = pt_implicit_to_string_call_helper_process_implicit_to_string_call(helper, part, scope, partResult.raw());
			if (UNEXPECTED(toStringResult.isUndef())) return zv::Val();
			{
				zv::Val hold;
				zval *points = pt_expression_result_throw_points(toStringResult.raw(), hold);
				if (UNEXPECTED(points == NULL || !ptse::mergeInto(throwPoints, points))) return zv::Val();
			}
			{
				zv::Val hold;
				zval *points = pt_expression_result_impure_points(toStringResult.raw(), hold);
				if (UNEXPECTED(points == NULL || !ptse::mergeInto(impurePoints, points))) return zv::Val();
			}

			if (!isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(partResult.raw(), isAlwaysTerminating))) return zv::Val();
			zv::Val nextHold;
			zval *nextScope = pt_expression_result_scope(partResult.raw(), nextHold);
			if (UNEXPECTED(nextScope == NULL)) return zv::Val();
			scopeHold = zv::Val::copyOf(zv::Ref(nextScope));
			scope = scopeHold.raw();
		}

		zv::Val variableFlow = pt_variable_flow_sequence_list(variableFlows.table());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody);
		zv::Val specifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<ShellExecHandler>, self, expr);
		pt_expression_result_args args(scope, beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ShellExecHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* static fn (bool $nativeTypesPromoted): Type =>
	 * TypeCombinator::union(new StringType(), new ConstantBooleanType(false),
	 * new NullType()) — captures nothing */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		(void) argv;
		if (UNEXPECTED(!ptse::requireArgs(argc, 1, closureName))) return;
		zval types[3];
		if (UNEXPECTED(!pt_string_type_new(&types[0]))) return;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&types[1], false))) {
			zval_ptr_dtor(&types[0]);
			return;
		}
		if (UNEXPECTED(!pt_null_type_new(&types[2]))) {
			zval_ptr_dtor(&types[0]);
			zval_ptr_dtor(&types[1]);
			return;
		}
		zv::Val type = pt_type_combinator_union(3, types);
		zval_ptr_dtor(&types[0]);
		zval_ptr_dtor(&types[1]);
		zval_ptr_dtor(&types[2]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ShellExecHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_shell_exec_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\ShellExecHandler");
	ptdecl::ShellExecHandler::declareClass(cls);
	ptdecl::ShellExecHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *implicitToStringCallHelper, *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, implicitToStringCallHelper, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		ShellExecHandler(Z_OBJ_P(ZEND_THIS)).construct(implicitToStringCallHelper, expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!ShellExecHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(ShellExecHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_shell_exec_handler);
	pt_expr_handler_entry_register(&pt_ce_shell_exec_handler, &ShellExecHandler::processExprEntry);
}

/* }}} */
