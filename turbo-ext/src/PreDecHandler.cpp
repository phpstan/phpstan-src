/*
 * PHPStanTurbo\PreDecHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\PreDecHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The typeCallback is IncDecTypeHelper's native
 * closure (pt_inc_dec_type_helper_get_type_callback()); the
 * specifyTypesCallback a native closure capturing what the twin's arrow
 * function captures ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, VariableFlow,
 * VariableFlowBuilder, VariableWrite, IncDecTypeHelper, AssignHandler and
 * DefaultNarrowingHelper are called through their direct entries.
 */

#include "support.h"
#include "generated/PreDecHandler.h"

namespace slots = ptdecl::PreDecHandler::slot;
namespace sigs = ptdecl::PreDecHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_pre_dec_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_pdh_var = PT_NODE_PROP(PT_CLASS_PRE_DEC, "var");

/* VariableWrite::KIND_PRE_DEC */
constexpr zend_long pt_pdh_kind = 5;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\PreDecHandler; UNDEF = pending
 * exception. */
class PreDecHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\PreDecHandler::{closure}";
	static constexpr uint32_t defaultNarrowingHelperSlot = slots::defaultNarrowingHelper;

	explicit PreDecHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *incDecTypeHelper, zval *defaultNarrowingHelper, zval *assignHandler) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::incDecTypeHelper, incDecTypeHelper);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
		pt_write_slot(self, slots::assignHandler, assignHandler);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_PRE_DEC);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *var = ptoh::operand(pt_pdh_var, expr);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		zv::Val valueFlowWrite = pt_variable_flow_builder_write_site(var, pt_pdh_kind, scope, storage);
		if (UNEXPECTED(valueFlowWrite.isUndef())) return zv::Val();
		zv::Val valueContext = ptse::valueFlowContext(context, valueFlowWrite.raw());
		if (UNEXPECTED(valueContext.isUndef())) return zv::Val();
		zv::Val varResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, var, scope, storage, nodeCallback, valueContext.raw());
		if (UNEXPECTED(varResult.isUndef())) return zv::Val();

		zv::Val typeCallback = pt_inc_dec_type_helper_get_type_callback(OBJ_PROP_NUM(self, slots::incDecTypeHelper), var, varResult.raw(), false);
		if (UNEXPECTED(typeCallback.isUndef())) return zv::Val();
		zv::Val specifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<PreDecHandler>, self, expr);

		ptse::ChildResult child;
		if (UNEXPECTED(!child.read(varResult.raw()))) return zv::Val();

		// the result standing for the whole inc/dec expression - threaded into
		// processVirtualAssign() as the value to assign so applyWrite() reads it
		// directly instead of re-processing the node on demand (which would
		// recurse)
		pt_expression_result_args valueArgs(child.scope, scope, expr, child.hasYield, child.isAlwaysTerminating, child.throwPoints, child.impurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		zv::Val incDecValueResult = pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), valueArgs);
		if (UNEXPECTED(incDecValueResult.isUndef())) return zv::Val();

		// processVirtualAssign() emits nodes (PropertyAssignNode) whose rules ask
		// about this whole expression - store its result first so those asks
		// answer from the storage; processExprNode() overwrites it with the
		// final result after this handler returns
		if (UNEXPECTED(!pt_node_scope_resolver_store_expression_result(nodeScopeResolver, storage, expr, incDecValueResult.raw()))) return zv::Val();

		zv::Val assignResult = pt_assign_handler_process_virtual_assign(OBJ_PROP_NUM(self, slots::assignHandler), nodeScopeResolver, child.scope, storage, stmt, var, expr, nodeCallback, incDecValueResult.raw());
		if (UNEXPECTED(assignResult.isUndef())) return zv::Val();
		zv::Val assignedScope = ptse::scopeOf(assignResult.raw());
		if (UNEXPECTED(assignedScope.isUndef())) return zv::Val();

		zv::Val variableFlow = ptse::incDecFlow(child.variableFlow.raw(), valueFlowWrite.raw(), context, var, pt_pdh_kind, assignedScope.raw(), storage);
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		pt_expression_result_args args(assignedScope.raw(), scope, expr, child.hasYield, child.isAlwaysTerminating, child.throwPoints, child.impurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return PreDecHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::PreDecHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_pre_dec_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\PreDecHandler");
	ptdecl::PreDecHandler::declareClass(cls);
	ptdecl::PreDecHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *incDecTypeHelper, *defaultNarrowingHelper, *assignHandler;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, expressionResultFactory, incDecTypeHelper, defaultNarrowingHelper, assignHandler)) RETURN_THROWS();
		PreDecHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, incDecTypeHelper, defaultNarrowingHelper, assignHandler);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!PreDecHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(PreDecHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_pre_dec_handler);
	pt_expr_handler_entry_register(&pt_ce_pre_dec_handler, &PreDecHandler::processExprEntry);
}

/* }}} */
