/*
 * PHPStanTurbo\UnsetHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\UnsetHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h).
 *
 * The ExistingArrayDimFetch chain the twin builds with a recursive closure is
 * built by a native recursion (under pt_engine_with_stack()).
 * NodeScopeResolver, AssignHandler, MethodThrowPointHelper, ExpressionResult,
 * ExpressionResultStorage, VariableFlow(Builder), VariableWriteOffset,
 * ImpurePoint, MutatingScope, VirtualExprResultHelper, the Type kernel, the
 * contexts and the statement results are called through their direct
 * entries; the container through the site below.
 */

#include "support.h"
#include "generated/UnsetHandler.h"

namespace slots = ptdecl::UnsetHandler::slot;
namespace sigs = ptdecl::UnsetHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_unset_handler = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_uh_get_by_type_site;

/* $container->getByType($className) */
zv::Val containerGetByType(zval *container, zend_string *className)
{
	zval name;
	ZVAL_STR(&name, className);
	return pt_call_method_cached(pt_uh_get_by_type_site, Z_OBJ_P(container), PT_LC("getbytype"), 1, &name);
}

/* }}} */

pt_property_site pt_uh_vars_site;
pt_property_site pt_uh_array_dim_fetch_var_site;
pt_property_site pt_uh_array_dim_fetch_dim_site;
pt_property_site pt_uh_variable_name_site;

/* permanent interned strings (module startup) */
zend_string *pt_uh_array_access = nullptr;
zend_string *pt_uh_method_throw_point_helper = nullptr;
zend_string *pt_uh_virtual_expr_result_helper = nullptr;
zend_string *pt_uh_offset_unset = nullptr;
zend_string *pt_uh_property_unset = nullptr;
zend_string *pt_uh_property_unset_description = nullptr;

/* PhpParser's VariableWrite::KIND_ASSIGN */
constexpr zend_long PT_UH_KIND_ASSIGN = 1;

zend_never_inline ZEND_COLD void memberCallOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
}

/* Mirrors hasDestructionSideEffects(); false = pending exception */
[[nodiscard]] bool hasDestructionSideEffects(zval *type, bool &out)
{
	zv::Val isObjectResult = pt_type_call(Z_OBJ_P(type), PT_LC("isobject"), 0, NULL);
	if (UNEXPECTED(isObjectResult.isUndef())) return false;
	zend_long isObject = pt_type_trinary_value(isObjectResult.raw());
	if (UNEXPECTED(isObject < 0)) return false;
	if (isObject != PT_TRI_NO) {
		out = true;
		return true;
	}
	zval resource;
	if (UNEXPECTED(!pt_resource_type_new(&resource))) return false;
	zv::Val resourceType = zv::Val::adopt(resource);
	zv::Val isResource = pt_type_op(Z_OBJ_P(resourceType.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, type);
	if (UNEXPECTED(isResource.isUndef())) return false;
	zend_long resourceTrinary = pt_type_result_trinary(isResource.raw());
	if (UNEXPECTED(resourceTrinary < 0)) return false;
	if (resourceTrinary != PT_TRI_NO) {
		out = true;
		return true;
	}
	zend_long isArray = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_ARRAY, 0, NULL);
	if (UNEXPECTED(isArray < 0)) return false;
	if (isArray == PT_TRI_NO) {
		out = false;
		return true;
	}
	zv::Val valueType = pt_type_op(Z_OBJ_P(type), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
	if (UNEXPECTED(valueType.isUndef())) return false;
	if (UNEXPECTED(Z_TYPE_P(valueType.raw()) != IS_OBJECT)) {
		memberCallOnNonObject("isObject", valueType.raw());
		return false;
	}
	bool result = false;
	bool ok = false;
	pt_engine_with_stack([&]() { ok = hasDestructionSideEffects(valueType.raw(), result); });
	out = result;
	return ok;
}

/* $buildExistingChain($node): ArrayDimFetches with a dim wrapped in
 * ExistingArrayDimFetch nodes over the original sub-expressions */
zv::Val buildExistingChain(zval *node)
{
	bool error = false;
	if (!ptsh::isInstanceOf(node, PT_CLASS_ARRAY_DIM_FETCH, error)) {
		if (UNEXPECTED(error)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(node));
	}
	zval *dim = ptsh::readNodeProperty(pt_uh_array_dim_fetch_dim_site, node, PT_LC("dim"));
	if (UNEXPECTED(dim == NULL)) return zv::Val();
	if (Z_TYPE_P(dim) == IS_NULL) return zv::Val::copyOf(zv::Ref(node));
	zval *var = ptsh::readNodeProperty(pt_uh_array_dim_fetch_var_site, node, PT_LC("var"));
	if (UNEXPECTED(var == NULL)) return zv::Val();
	zv::Val varHold = zv::Val::copyOf(zv::Ref(var));
	zv::Val chain;
	pt_engine_with_stack([&]() { chain = buildExistingChain(varHold.raw()); });
	if (UNEXPECTED(chain.isUndef())) return zv::Val();
	dim = ptsh::readNodeProperty(pt_uh_array_dim_fetch_dim_site, node, PT_LC("dim"));
	if (UNEXPECTED(dim == NULL)) return zv::Val();
	zv::Args argv{chain.raw(), dim};
	return pt_type_new(PT_CLASS_EXISTING_ARRAY_DIM_FETCH, 2, argv);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\UnsetHandler; UNDEF = pending
 * exception. */
class UnsetHandler
{
public:
	explicit UnsetHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *container, zval *assignHandler)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::container, zv::Val::copyOf(zv::Ref(container)));
		object.propAtWrite(slots::assignHandler, zv::Val::copyOf(zv::Ref(assignHandler)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_UNSET_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *entryScope = scope;
		State state;
		state.scope = zv::Val::copyOf(zv::Ref(scope));
		zval *stmtVars = ptsh::readNodeProperty(pt_uh_vars_site, stmt, PT_LC("vars"));
		if (UNEXPECTED(stmtVars == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(stmtVars) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(stmtVars));
			if (UNEXPECTED(EG(exception))) return zv::Val();
		} else {
			/* foreach iterates the array it started with */
			zv::Val iterated = zv::Val::copyOf(zv::Ref(stmtVars));
			for (auto entry : zv::ArrRef(iterated.raw())) {
				if (UNEXPECTED(!processVar(nodeScopeResolver, stmt, entry.value().deref().raw(), storage, nodeCallback, context, state))) return zv::Val();
			}
		}

		// the Unset_ callback is deferred from processStmtNode(): it fires after
		// the unset targets were processed, with the entry scope, so rule-side
		// asks about them answer from the storage
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, stmt, entryScope, storage))) return zv::Val();

		zv::Val variableFlow = pt_variable_flow_sequence_list(state.variableFlows.table());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return pt_internal_statement_result_new(state.scope.raw(), state.hasYield, false, &emptyArray, state.throwPoints.raw(), state.impurePoints.raw(), NULL, variableFlow.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return UnsetHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* the twin's loop variables */
	struct State
	{
		zv::Val scope;
		bool hasYield = false;
		zv::Arr throwPoints = zv::Arr::empty();
		zv::Arr impurePoints = zv::Arr::empty();
		zv::Arr variableFlows = zv::Arr::empty();
	};

	/* ExpressionContext::createDeep($context->shouldResolveTemplateArguments()) */
	static zv::Val deepContext(zval *context)
	{
		bool resolveTemplateArguments;
		if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
		return pt_expression_context_create_deep(resolveTemplateArguments);
	}

	/* the loop body over one unset target; false = pending exception */
	[[nodiscard]] bool processVar(zval *nodeScopeResolver, zval *stmt, zval *var, zval *storage, zval *nodeCallback, zval *context, State &state) const
	{
		{
			zv::Val allowedScope = pt_node_scope_resolver_look_for_set_allowed_undefined_expressions(nodeScopeResolver, state.scope.raw(), var);
			if (UNEXPECTED(allowedScope.isUndef())) return false;
			state.scope = std::move(allowedScope);
		}
		zv::Val exprResult;
		{
			zv::Val deep = deepContext(context);
			if (UNEXPECTED(deep.isUndef())) return false;
			zv::Val unsetTargetContext = pt_expression_context_enter_unset_target(deep.raw());
			if (UNEXPECTED(unsetTargetContext.isUndef())) return false;
			exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, var, state.scope.raw(), storage, nodeCallback, unsetTargetContext.raw());
			if (UNEXPECTED(exprResult.isUndef())) return false;
		}
		{
			zv::Val type = pt_expression_result_get_type(exprResult.raw());
			if (UNEXPECTED(type.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(type.raw()) != IS_OBJECT)) {
				memberCallOnNonObject("isObject", type.raw());
				return false;
			}
			bool sideEffects;
			if (UNEXPECTED(!hasDestructionSideEffects(type.raw(), sideEffects))) return false;
			zv::Val targetRead = pt_variable_flow_builder_target_read(var, storage, sideEffects, NULL);
			if (UNEXPECTED(targetRead.isUndef())) return false;
			state.variableFlows.push(std::move(targetRead));
		}

		bool error = false;
		bool isArrayDimFetch = ptsh::isInstanceOf(var, PT_CLASS_ARRAY_DIM_FETCH, error);
		if (UNEXPECTED(error)) return false;
		if (UNEXPECTED(!discardFlow(var, isArrayDimFetch, storage, state))) return false;

		{
			zv::Val scopeHold;
			zval *resultScope = pt_expression_result_scope(exprResult.raw(), scopeHold);
			if (UNEXPECTED(resultScope == NULL)) return false;
			state.scope = zv::Val::copyOf(zv::Ref(resultScope));
		}
		{
			zv::Val unsetScope = pt_node_scope_resolver_look_for_unset_allowed_undefined_expressions(nodeScopeResolver, state.scope.raw(), var);
			if (UNEXPECTED(unsetScope.isUndef())) return false;
			state.scope = std::move(unsetScope);
		}
		if (!state.hasYield && UNEXPECTED(!pt_expression_result_has_yield(exprResult.raw(), state.hasYield))) return false;
		{
			zv::Val hold;
			zval *resultThrowPoints = pt_expression_result_throw_points(exprResult.raw(), hold);
			if (UNEXPECTED(resultThrowPoints == NULL || !pt_callable_array_merge_into(state.throwPoints, resultThrowPoints))) return false;
		}
		{
			zv::Val hold;
			zval *resultImpurePoints = pt_expression_result_impure_points(exprResult.raw(), hold);
			if (UNEXPECTED(resultImpurePoints == NULL || !pt_callable_array_merge_into(state.impurePoints, resultImpurePoints))) return false;
		}

		zval *dim = NULL;
		if (isArrayDimFetch) {
			dim = ptsh::readNodeProperty(pt_uh_array_dim_fetch_dim_site, var, PT_LC("dim"));
			if (UNEXPECTED(dim == NULL)) return false;
		}
		if (isArrayDimFetch && Z_TYPE_P(dim) != IS_NULL) {
			if (UNEXPECTED(!unsetOffset(nodeScopeResolver, stmt, var, storage, nodeCallback, context, state))) return false;
		} else {
			bool isPropertyFetch = ptsh::isInstanceOf(var, PT_CLASS_PROPERTY_FETCH, error);
			if (UNEXPECTED(error)) return false;
			zv::Val invalidated = pt_mutating_scope_invalidate_expression(Z_OBJ_P(state.scope.raw()), var, false, NULL, false);
			if (UNEXPECTED(invalidated.isUndef())) return false;
			state.scope = std::move(invalidated);
			if (isPropertyFetch) {
				zv::Val impurePoint = pt_impure_point_new(state.scope.raw(), var, pt_uh_property_unset, pt_uh_property_unset_description, true);
				if (UNEXPECTED(impurePoint.isUndef())) return false;
				state.impurePoints.push(std::move(impurePoint));
			}
		}

		zv::Val byRefExpr = pt_type_new(PT_CLASS_FOREACH_VALUE_BY_REF_EXPR, 1, var);
		if (UNEXPECTED(byRefExpr.isUndef())) return false;
		zv::Val invalidated = pt_mutating_scope_invalidate_expression(Z_OBJ_P(state.scope.raw()), byRefExpr.raw(), false, NULL, false);
		if (UNEXPECTED(invalidated.isUndef())) return false;
		state.scope = std::move(invalidated);
		return true;
	}

	/* the VariableFlow::discard(new VariableWrite(...)) of an unset rooted in a
	 * named variable; false = pending exception */
	[[nodiscard]] static bool discardFlow(zval *var, bool isArrayDimFetch, zval *storage, State &state)
	{
		zval *root = var;
		zv::Val rootHold;
		bool error = false;
		while (ptsh::isInstanceOf(root, PT_CLASS_ARRAY_DIM_FETCH, error)) {
			zval *next = ptsh::readNodeProperty(pt_uh_array_dim_fetch_var_site, root, PT_LC("var"));
			if (UNEXPECTED(next == NULL)) return false;
			rootHold = zv::Val::copyOf(zv::Ref(next));
			root = rootHold.raw();
		}
		if (UNEXPECTED(error)) return false;
		if (!ptsh::isInstanceOf(root, PT_CLASS_VARIABLE, error)) return !error;
		zval *rootName = ptsh::readNodeProperty(pt_uh_variable_name_site, root, PT_LC("name"));
		if (UNEXPECTED(rootName == NULL)) return false;
		if (Z_TYPE_P(rootName) != IS_STRING) return true;
		zv::Val rootNameHold = zv::Val::copyOf(zv::Ref(rootName));

		zv::Val dimResult = zv::Val::null();
		if (isArrayDimFetch) {
			zval *dim = ptsh::readNodeProperty(pt_uh_array_dim_fetch_dim_site, var, PT_LC("dim"));
			if (UNEXPECTED(dim == NULL)) return false;
			if (Z_TYPE_P(dim) != IS_NULL) {
				zv::Val dimHold = zv::Val::copyOf(zv::Ref(dim));
				dimResult = pt_expression_result_storage_find(storage, dimHold.raw());
				if (UNEXPECTED(dimResult.isUndef())) return false;
			}
		}
		zv::Val offset = zv::Val::null();
		if (!dimResult.isNull()) {
			zv::Val dimType = pt_expression_result_get_type(dimResult.raw());
			if (UNEXPECTED(dimType.isUndef())) return false;
			offset = pt_variable_write_offset_from_type(dimType.raw());
			if (UNEXPECTED(offset.isUndef())) return false;
		}
		bool replacesOffset = true;
		if (isArrayDimFetch) {
			zval *varVar = ptsh::readNodeProperty(pt_uh_array_dim_fetch_var_site, var, PT_LC("var"));
			if (UNEXPECTED(varVar == NULL)) return false;
			replacesOffset = zend_is_identical(varVar, root);
		}
		zval writeArgv[8];
		ZVAL_COPY_VALUE(&writeArgv[0], rootNameHold.raw());
		ZVAL_COPY_VALUE(&writeArgv[1], var);
		ZVAL_LONG(&writeArgv[2], (zend_long) Z_OBJ_HANDLE_P(var));
		ZVAL_LONG(&writeArgv[3], PT_UH_KIND_ASSIGN);
		ZVAL_BOOL(&writeArgv[4], isArrayDimFetch);
		ZVAL_COPY_VALUE(&writeArgv[5], offset.raw());
		ZVAL_NULL(&writeArgv[6]);
		ZVAL_BOOL(&writeArgv[7], replacesOffset);
		zv::Val write = pt_type_new(PT_CLASS_VARIABLE_WRITE, 8, writeArgv);
		if (UNEXPECTED(write.isUndef())) return false;
		zv::Val discard = pt_variable_flow_discard(write.raw());
		if (UNEXPECTED(discard.isUndef())) return false;
		state.variableFlows.push(std::move(discard));
		return true;
	}

	/* the `$var instanceof ArrayDimFetch && $var->dim !== null` branch: the
	 * offsetUnset() throw points and the virtual assign of the unset offset;
	 * false = pending exception */
	[[nodiscard]] bool unsetOffset(zval *nodeScopeResolver, zval *stmt, zval *var, zval *storage, zval *nodeCallback, zval *context, State &state) const
	{
		zval *container = OBJ_PROP_NUM(self, slots::container);
		zval *varVar = ptsh::readNodeProperty(pt_uh_array_dim_fetch_var_site, var, PT_LC("var"));
		if (UNEXPECTED(varVar == NULL)) return false;
		zv::Val varVarHold = zv::Val::copyOf(zv::Ref(varVar));
		zv::Val varType;
		{
			zv::Val stored = pt_node_scope_resolver_read_stored_result(nodeScopeResolver, varVarHold.raw(), storage);
			if (UNEXPECTED(stored.isUndef())) return false;
			varType = pt_expression_result_get_type_on_scope(stored.raw(), state.scope.raw(), false);
			if (UNEXPECTED(varType.isUndef())) return false;
		}
		if (UNEXPECTED(Z_TYPE_P(varType.raw()) != IS_OBJECT)) {
			memberCallOnNonObject("isArray", varType.raw());
			return false;
		}
		zend_long isArray = pt_type_op_trinary(Z_OBJ_P(varType.raw()), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(isArray < 0)) return false;
		if (isArray != PT_TRI_YES) {
			zval arrayAccess;
			if (UNEXPECTED(!pt_object_type_new(&arrayAccess, pt_uh_array_access))) return false;
			zv::Val arrayAccessType = zv::Val::adopt(arrayAccess);
			zv::Val isSuperType = pt_type_op(Z_OBJ_P(arrayAccessType.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, varType.raw());
			if (UNEXPECTED(isSuperType.isUndef())) return false;
			zend_long trinary = pt_type_result_trinary(isSuperType.raw());
			if (UNEXPECTED(trinary < 0)) return false;
			if (trinary != PT_TRI_NO) {
				zv::Val helper = containerGetByType(container, pt_uh_method_throw_point_helper);
				if (UNEXPECTED(helper.isUndef())) return false;
				if (UNEXPECTED(Z_TYPE_P(helper.raw()) != IS_OBJECT)) {
					memberCallOnNonObject("getThrowPointsForCallOnType", helper.raw());
					return false;
				}
				zv::Val deep = deepContext(context);
				if (UNEXPECTED(deep.isUndef())) return false;
				zv::Val typeExpr = pt_type_new(PT_CLASS_TYPE_EXPR, 1, varType.raw());
				if (UNEXPECTED(typeExpr.isUndef())) return false;
				zv::Args callArgv{typeExpr.raw(), pt_uh_offset_unset};
				zv::Val methodCall = pt_type_new(PT_CLASS_METHOD_CALL, 2, callArgv);
				if (UNEXPECTED(methodCall.isUndef())) return false;
				zv::Val throwPoints = pt_method_throw_point_helper_get_throw_points_for_call_on_type(helper.raw(), state.scope.raw(), deep.raw(), varType.raw(), methodCall.raw());
				if (UNEXPECTED(throwPoints.isUndef())) return false;
				if (UNEXPECTED(!pt_callable_array_merge_into(state.throwPoints, throwPoints.raw()))) return false;
			}
		}

		// wrap the already-processed chain in ExistingArrayDimFetch nodes
		// referencing the original sub-expressions, so the virtual assign
		// reads their stored results instead of re-walking a clone
		varVar = ptsh::readNodeProperty(pt_uh_array_dim_fetch_var_site, var, PT_LC("var"));
		if (UNEXPECTED(varVar == NULL)) return false;
		zv::Val chainRoot = zv::Val::copyOf(zv::Ref(varVar));
		zv::Val clonedVar = buildExistingChain(chainRoot.raw());
		if (UNEXPECTED(clonedVar.isUndef())) return false;
		zval *dim = ptsh::readNodeProperty(pt_uh_array_dim_fetch_dim_site, var, PT_LC("dim"));
		if (UNEXPECTED(dim == NULL)) return false;
		zv::Val dimHold = zv::Val::copyOf(zv::Ref(dim));
		zv::Args offsetArgv{chainRoot.raw(), dimHold.raw()};
		zv::Val unsetOffsetExpr = pt_type_new(PT_CLASS_UNSET_OFFSET_EXPR, 2, offsetArgv);
		if (UNEXPECTED(unsetOffsetExpr.isUndef())) return false;

		zv::Val helper = containerGetByType(container, pt_uh_virtual_expr_result_helper);
		if (UNEXPECTED(helper.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(helper.raw()) != IS_OBJECT)) {
			memberCallOnNonObject("createUnsetOffsetExprResult", helper.raw());
			return false;
		}
		zv::Val varResult = pt_node_scope_resolver_read_stored_result(nodeScopeResolver, chainRoot.raw(), storage);
		if (UNEXPECTED(varResult.isUndef())) return false;
		zv::Val dimResult = pt_node_scope_resolver_read_stored_result(nodeScopeResolver, dimHold.raw(), storage);
		if (UNEXPECTED(dimResult.isUndef())) return false;
		zv::Val assignedExprResult = pt_virtual_expr_result_helper_create_unset_offset_expr_result(helper.raw(), state.scope.raw(), unsetOffsetExpr.raw(), varResult.raw(), dimResult.raw());
		if (UNEXPECTED(assignedExprResult.isUndef())) return false;

		zv::Val assignResult = pt_assign_handler_process_virtual_assign(OBJ_PROP_NUM(self, slots::assignHandler), nodeScopeResolver, state.scope.raw(), storage, stmt, clonedVar.raw(), unsetOffsetExpr.raw(), nodeCallback, assignedExprResult.raw());
		if (UNEXPECTED(assignResult.isUndef())) return false;
		zv::Val scopeHold;
		zval *assignScope = pt_expression_result_scope(assignResult.raw(), scopeHold);
		if (UNEXPECTED(assignScope == NULL)) return false;
		state.scope = zv::Val::copyOf(zv::Ref(assignScope));
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::UnsetHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_unset_handler()
{
	pt_uh_array_access = zend_string_init_interned(PT_LC("ArrayAccess"), 1);
	pt_uh_method_throw_point_helper = zend_string_init_interned(PT_LC("PHPStan\\Analyser\\ExprHandler\\Helper\\MethodThrowPointHelper"), 1);
	pt_uh_virtual_expr_result_helper = zend_string_init_interned(PT_LC("PHPStan\\Analyser\\ExprHandler\\Helper\\VirtualExprResultHelper"), 1);
	pt_uh_offset_unset = zend_string_init_interned(PT_LC("offsetUnset"), 1);
	pt_uh_property_unset = zend_string_init_interned(PT_LC("propertyUnset"), 1);
	pt_uh_property_unset_description = zend_string_init_interned(PT_LC("property unset"), 1);

	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\UnsetHandler");
	ptdecl::UnsetHandler::declareClass(cls);
	ptdecl::UnsetHandler::declareProperties(cls);

	cls.method<&UnsetHandler::supports, zp::Obj>(sigs::supports);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *container, *assignHandler;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, container, assignHandler)) RETURN_THROWS();
		UnsetHandler(Z_OBJ_P(ZEND_THIS)).construct(container, assignHandler);
	});

	cls.method(sigs::processStmt, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(UnsetHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_unset_handler);
	pt_stmt_handler_entry_register(&pt_ce_unset_handler, &UnsetHandler::processStmtEntry);
}

/* }}} */
