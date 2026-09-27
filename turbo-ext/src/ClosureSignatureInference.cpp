/*
 * PHPStanTurbo\ClosureSignatureInference — native implementation of
 * PHPStan\Analyser\Generics\ClosureSignatureInference.
 *
 * A DI service (#[AutowiredService]) holding the closureSignaturesFromUsages
 * feature toggle; the constructor keeps the twin's arginfo so Nette autowires
 * it. The closure cluster (ClosureProcessor, ClosureTypeResolver,
 * ContextualClosureParameterResolver, the closure / arrow function handlers)
 * and StatementsHandler reach it through the pt_closure_signature_inference_*
 * entries (support.h): the native bodies for the native service, the methods
 * otherwise. The two AST scans (isClosedBody(), the private
 * returnsContextTypedExpression()) walk getSubNodeNames() like the twin's
 * array_pop() loops and cache their answer in the same node attributes.
 */

#include "support.h"
#include "generated/ClosureSignatureInference.h"

namespace slots = ptdecl::ClosureSignatureInference::slot;
namespace sigs = ptdecl::ClosureSignatureInference::sig;
#include "ClosureSupport.h"

zend_class_entry *pt_ce_closure_signature_inference = nullptr;

namespace {

/* the twin's RETURN_TEMPLATE_NAME and its two attribute names, permanent
 * interned strings (module startup) */
zend_string *pt_csi_return_template_name = nullptr;
zend_string *pt_csi_closed_body_attribute = nullptr;
zend_string *pt_csi_returns_context_typed_attribute = nullptr;
zend_string *pt_csi_assigned_closures_attribute = nullptr;
/* the by-ref follow-up's template names and attribute names */
zend_string *pt_csi_invocation_template_name = nullptr;
zend_string *pt_csi_by_ref_uses_attribute = nullptr;
zend_string *pt_csi_arrow_function_outer_variables_attribute = nullptr;
constexpr char pt_csi_by_ref_template_prefix = '&';
constexpr char pt_csi_entry_template_prefix = '~';

pt_method_site pt_csi_get_sub_node_names_site;
pt_method_site pt_csi_get_params_site;
pt_method_site pt_csi_to_lower_string_site;
pt_property_site pt_csi_uses_site;
pt_property_site pt_csi_use_by_ref_site;
pt_property_site pt_csi_use_var_site;
pt_property_site pt_csi_variable_name_site;
pt_property_site pt_csi_param_by_ref_site;
pt_property_site pt_csi_param_var_site;
pt_property_site pt_csi_param_default_site;
pt_property_site pt_csi_param_variadic_site;
pt_property_site pt_csi_func_call_name_site;
pt_property_site pt_csi_var_site;
pt_property_site pt_csi_value_var_site;
pt_property_site pt_csi_key_var_site;
pt_property_site pt_csi_items_site;
pt_property_site pt_csi_item_value_site;
pt_property_site pt_csi_return_expr_site;
pt_property_site pt_csi_arrow_expr_site;
pt_property_site pt_csi_stmts_site;
pt_property_site pt_csi_assign_var_site;
pt_property_site pt_csi_assign_expr_site;
pt_property_site pt_csi_params_site;
pt_property_site pt_csi_by_ref_uses_site;
pt_property_site pt_csi_by_ref_use_by_ref_site;
pt_property_site pt_csi_by_ref_use_var_site;
pt_property_site pt_csi_by_ref_variable_name_site;
pt_property_site pt_csi_by_ref_param_var_site;
pt_property_site pt_csi_by_ref_func_call_name_site;

/* $value instanceof <class-map class>; false = pending exception */
[[nodiscard]] bool isA(zval *value, int classIdx, bool &out)
{
	int is = ptclosure::instanceOf(value, classIdx);
	if (UNEXPECTED(is < 0)) return false;
	out = is == 1;
	return true;
}

/* $node->$name (dereferenced, borrowed); NULL = pending exception */
inline zval *read(pt_property_site &site, zval *node, const char *name, size_t len)
{
	return ptclosure::prop(site, node, name, len);
}

/* '$' . $parameterName */
zv::Str parameterTemplateName(zend_string *parameterName)
{
	return zv::Str::adopt(zend_string_concat2("$", 1, ZSTR_VAL(parameterName), ZSTR_LEN(parameterName)));
}

/* the name of a parameter node's variable, NULL when it is not a Variable
 * with a string name; false = pending exception */
[[nodiscard]] bool paramVariableName(zval *param, zend_string *&out)
{
	out = NULL;
	zval *var = read(pt_csi_param_var_site, param, PT_LC("var"));
	if (UNEXPECTED(var == NULL)) return false;
	bool isVariable;
	if (UNEXPECTED(!isA(var, PT_CLASS_VARIABLE, isVariable))) return false;
	if (!isVariable) return true;
	zval *name = read(pt_csi_variable_name_site, var, PT_LC("name"));
	if (UNEXPECTED(name == NULL)) return false;
	if (Z_TYPE_P(name) == IS_STRING) out = Z_STR_P(name);
	return true;
}

/* $node->getSubNodeNames(); UNDEF = pending exception */
zv::Val subNodeNames(zval *node)
{
	return pt_call_method_cached(pt_csi_get_sub_node_names_site, Z_OBJ_P(node), PT_LC("getsubnodenames"), 0, NULL);
}

/* the twin's `foreach ($node->getSubNodeNames() as $subNodeName) { ... }`
 * pushing every Node found (directly or in an array) onto the stack; false =
 * pending exception */
[[nodiscard]] bool pushSubNodes(zval *node, zv::Arr &stack)
{
	zv::Val names = subNodeNames(node);
	if (UNEXPECTED(names.isUndef())) return false;
	if (UNEXPECTED(!names.ref().isArray())) {
		zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(names.raw()));
		return EG(exception) == NULL;
	}
	zend_class_entry *nodeCe = pt_class(PT_CLASS_NODE);
	if (UNEXPECTED(nodeCe == NULL)) return false;
	zend_object *nodeObject = Z_OBJ_P(node);
	for (auto entry : zv::ArrRef(names.raw())) {
		zend_string *subNodeName = zval_get_string(entry.value().deref().raw());
		zval rv;
		ZVAL_UNDEF(&rv);
		zval *subNode = zend_read_property_ex(nodeObject->ce, nodeObject, subNodeName, 0, &rv);
		zend_string_release(subNodeName);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&rv);
			return false;
		}
		zv::Val held = zv::Val::copyOf(zv::Ref(subNode).deref());
		zval_ptr_dtor(&rv);
		if (held.ref().isObject() && instanceof_function(Z_OBJCE_P(held.raw()), nodeCe)) {
			stack.push(std::move(held));
		} else if (held.ref().isArray()) {
			for (auto item : zv::ArrRef(held.raw())) {
				zval *value = item.value().deref().raw();
				if (Z_TYPE_P(value) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(value), nodeCe)) continue;
				stack.push(zv::Val::copyOf(zv::Ref(value)));
			}
		}
	}
	return true;
}

/* array_pop($stack), or UNDEF for an empty stack */
zv::Val popNode(zv::Arr &stack)
{
	HashTable *table = stack.table();
	uint32_t count = zend_hash_num_elements(table);
	if (count == 0) return zv::Val();
	stack.separate();
	table = stack.table();
	zval *last = zend_hash_index_find(table, count - 1);
	zv::Val value = zv::Val::copyOf(zv::Ref(last));
	zend_hash_index_del(table, count - 1);
	table->nNextFreeElement = count - 1;
	return value;
}

/* infersInvocationReturnType()'s traversal: whether the type holds an
 * UnresolvedTemplateArgumentType */
void containsMarkerBody(zval *contains, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state1;
	if (UNEXPECTED(argc < 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
		zend_type_error("ClosureSignatureInference::infersInvocationReturnType() traversal: expected (Type $type, callable $traverse)");
		return;
	}
	zval *type = &argv[0];
	if (Z_OBJCE_P(type) == pt_ce_unresolved_template_argument_type) {
		zval_ptr_dtor(contains);
		ZVAL_TRUE(contains);
	}
	if (Z_TYPE_P(contains) == IS_TRUE) {
		ZVAL_COPY(return_value, type);
		return;
	}
	zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
	if (UNEXPECTED(traversed.isUndef())) return;
	traversed.intoReturnValue(return_value);
}

/* one node of getAssignedClosures(): a closure or an arrow function assigned
 * to a named variable is added under the name; false = pending exception */
[[nodiscard]] bool collectAssignedClosure(zval *node, zv::Arr &assigned)
{
	bool is;
	if (UNEXPECTED(!isA(node, PT_CLASS_ASSIGN_EXPR, is))) return false;
	if (!is) return true;
	zval *var = read(pt_csi_assign_var_site, node, PT_LC("var"));
	if (UNEXPECTED(var == NULL)) return false;
	if (UNEXPECTED(!isA(var, PT_CLASS_VARIABLE, is))) return false;
	if (!is) return true;
	zval *name = read(pt_csi_variable_name_site, var, PT_LC("name"));
	if (UNEXPECTED(name == NULL)) return false;
	if (Z_TYPE_P(name) != IS_STRING) return true;
	zval *value = read(pt_csi_assign_expr_site, node, PT_LC("expr"));
	if (UNEXPECTED(value == NULL)) return false;
	if (UNEXPECTED(!isA(value, PT_CLASS_CLOSURE_EXPR, is))) return false;
	if (!is && UNEXPECTED(!isA(value, PT_CLASS_ARROW_FUNCTION, is))) return false;
	if (!is) return true;
	assigned.separate();
	zval *list = zend_symtable_find(assigned.table(), Z_STR_P(name));
	if (list == NULL) {
		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		list = zend_symtable_update(assigned.table(), Z_STR_P(name), &empty);
	}
	SEPARATE_ARRAY(list);
	Z_TRY_ADDREF_P(value);
	zend_hash_next_index_insert(Z_ARRVAL_P(list), value);
	return true;
}

/* Mirrors the private static getAssignedClosures(); UNDEF = pending exception */
zv::Val getAssignedClosures(zval *body, zval *stmts)
{
	zv::Val cached = pt_engine_node_get_attribute(Z_OBJ_P(body), ZSTR_VAL(pt_csi_assigned_closures_attribute), ZSTR_LEN(pt_csi_assigned_closures_attribute));
	if (UNEXPECTED(cached.isUndef())) return zv::Val();
	if (!cached.isNull()) return cached;

	zv::Arr assigned = zv::Arr::create(0);
	zv::Arr stack = zv::Arr::create(0);
	if (Z_TYPE_P(stmts) == IS_ARRAY) {
		for (auto entry : zv::ArrRef(stmts)) {
			stack.push(zv::Val::copyOf(entry.value().deref()));
		}
	}
	for (;;) {
		zv::Val node = popNode(stack);
		if (node.isUndef()) break;
		bool skip;
		if (UNEXPECTED(!isA(node.raw(), PT_CLASS_FUNCTION_LIKE, skip))) return zv::Val();
		if (!skip && UNEXPECTED(!isA(node.raw(), PT_CLASS_CLASS_LIKE_STMT, skip))) return zv::Val();
		if (skip) continue;
		if (UNEXPECTED(!collectAssignedClosure(node.raw(), assigned))) return zv::Val();
		if (UNEXPECTED(!pushSubNodes(node.raw(), stack))) return zv::Val();
	}
	zv::Val result(std::move(assigned));
	if (UNEXPECTED(!pt_engine_node_set_attribute(Z_OBJ_P(body), ZSTR_VAL(pt_csi_assigned_closures_attribute), ZSTR_LEN(pt_csi_assigned_closures_attribute), result.raw()))) return zv::Val();
	return result;
}

/* Mirrors isContextTyped(); false = pending exception */
[[nodiscard]] bool isContextTyped(zval *expr, bool &out)
{
	if (UNEXPECTED(!isA(expr, PT_CLASS_CLOSURE_EXPR, out))) return false;
	if (out) return true;
	if (UNEXPECTED(!isA(expr, PT_CLASS_ARROW_FUNCTION, out))) return false;
	if (out) return true;
	return isA(expr, PT_CLASS_ARRAY_EXPR, out);
}

/* Mirrors collectTargetNames(): the names set to true; false = pending
 * exception */
[[nodiscard]] bool collectTargetNames(zval *targetArg, zv::Arr &names)
{
	zv::Val target = zv::Val::copyOf(zv::Ref(targetArg));
	for (;;) {
		bool isDimFetch;
		if (UNEXPECTED(!isA(target.raw(), PT_CLASS_ARRAY_DIM_FETCH, isDimFetch))) return false;
		if (!isDimFetch) break;
		zval *var = read(pt_csi_var_site, target.raw(), PT_LC("var"));
		if (UNEXPECTED(var == NULL)) return false;
		target = zv::Val::copyOf(zv::Ref(var));
	}
	bool isVariable;
	if (UNEXPECTED(!isA(target.raw(), PT_CLASS_VARIABLE, isVariable))) return false;
	if (isVariable) {
		zval *name = read(pt_csi_variable_name_site, target.raw(), PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		if (Z_TYPE_P(name) == IS_STRING) {
			names.set(Z_STR_P(name), zv::Val::boolean(true));
		}
		return true;
	}
	bool isList, isArray;
	if (UNEXPECTED(!isA(target.raw(), PT_CLASS_LIST_EXPR, isList))) return false;
	if (!isList && UNEXPECTED(!isA(target.raw(), PT_CLASS_ARRAY_EXPR, isArray))) return false;
	if (!isList && !isArray) return true;

	zval *items = read(pt_csi_items_site, target.raw(), PT_LC("items"));
	if (UNEXPECTED(items == NULL)) return false;
	if (Z_TYPE_P(items) != IS_ARRAY) return true;
	zv::Val itemsHold = zv::Val::copyOf(zv::Ref(items));
	for (auto entry : zv::ArrRef(itemsHold.raw())) {
		zval *item = entry.value().deref().raw();
		if (Z_TYPE_P(item) == IS_NULL) continue;
		zval *value = read(pt_csi_item_value_site, item, PT_LC("value"));
		if (UNEXPECTED(value == NULL)) return false;
		zv::Val valueHold = zv::Val::copyOf(zv::Ref(value));
		bool ok = true;
		pt_engine_with_stack([&]() { ok = collectTargetNames(valueHold.raw(), names); });
		if (UNEXPECTED(!ok)) return false;
	}
	return true;
}

/* the by-reference parameter names of a FunctionLike node into $names;
 * false = pending exception */
[[nodiscard]] bool collectByRefParameterNames(zval *functionLike, zv::Arr &names)
{
	zv::Val params = pt_call_method_cached(pt_csi_get_params_site, Z_OBJ_P(functionLike), PT_LC("getparams"), 0, NULL);
	if (UNEXPECTED(params.isUndef())) return false;
	if (!params.ref().isArray()) return true;
	for (auto entry : zv::ArrRef(params.raw())) {
		zval *param = entry.value().deref().raw();
		zval *byRef = read(pt_csi_param_by_ref_site, param, PT_LC("byRef"));
		if (UNEXPECTED(byRef == NULL)) return false;
		if (!zend_is_true(byRef)) continue;
		zend_string *name;
		if (UNEXPECTED(!paramVariableName(param, name))) return false;
		if (name == NULL) continue;
		names.set(name, zv::Val::boolean(true));
	}
	return true;
}

/* Mirrors scanClosedBody(); false = pending exception */
[[nodiscard]] bool scanClosedBody(zval *functionLike, zval *stmts, bool &closed)
{
	closed = true;
	zv::Arr byRefNames = zv::Arr::create(0);
	bool isFunctionLike;
	if (UNEXPECTED(!isA(functionLike, PT_CLASS_FUNCTION_LIKE, isFunctionLike))) return false;
	if (isFunctionLike && UNEXPECTED(!collectByRefParameterNames(functionLike, byRefNames))) return false;
	bool isClosure;
	if (UNEXPECTED(!isA(functionLike, PT_CLASS_CLOSURE_EXPR, isClosure))) return false;
	if (isClosure) {
		zval *uses = read(pt_csi_uses_site, functionLike, PT_LC("uses"));
		if (UNEXPECTED(uses == NULL)) return false;
		if (Z_TYPE_P(uses) == IS_ARRAY) {
			zv::Val usesHold = zv::Val::copyOf(zv::Ref(uses));
			for (auto entry : zv::ArrRef(usesHold.raw())) {
				zval *use = entry.value().deref().raw();
				zval *byRef = read(pt_csi_use_by_ref_site, use, PT_LC("byRef"));
				if (UNEXPECTED(byRef == NULL)) return false;
				if (!zend_is_true(byRef)) continue;
				zval *var = read(pt_csi_use_var_site, use, PT_LC("var"));
				if (UNEXPECTED(var == NULL)) return false;
				zval *name = read(pt_csi_variable_name_site, var, PT_LC("name"));
				if (UNEXPECTED(name == NULL)) return false;
				if (Z_TYPE_P(name) != IS_STRING) continue;
				byRefNames.set(Z_STR_P(name), zv::Val::boolean(true));
			}
		}
	}

	zv::Arr writtenNames = zv::Arr::create(0);
	zv::Arr stack = zv::Arr::create(0);
	if (Z_TYPE_P(stmts) == IS_ARRAY) {
		for (auto entry : zv::ArrRef(stmts)) {
			stack.push(zv::Val::copyOf(entry.value().deref()));
		}
	}
	for (;;) {
		zv::Val node = popNode(stack);
		if (node.isUndef()) break;
		zval *n = node.raw();
		bool is;
		if (UNEXPECTED(!isA(n, PT_CLASS_CLASS_LIKE_STMT, is))) return false;
		if (is) continue;
		if (UNEXPECTED(!isA(n, PT_CLASS_FUNCTION_STMT, is))) return false;
		if (is) continue;
		bool opaque;
		if (UNEXPECTED(!isA(n, PT_CLASS_GLOBAL_STMT, opaque))) return false;
		if (!opaque && UNEXPECTED(!isA(n, PT_CLASS_INCLUDE_EXPR, opaque))) return false;
		if (!opaque && UNEXPECTED(!isA(n, PT_CLASS_EVAL_EXPR, opaque))) return false;
		if (opaque) {
			closed = false;
			return true;
		}
		if (UNEXPECTED(!isA(n, PT_CLASS_VARIABLE, is))) return false;
		if (is) {
			zval *name = read(pt_csi_variable_name_site, n, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return false;
			if (Z_TYPE_P(name) != IS_STRING || zend_string_equals_literal(Z_STR_P(name), "GLOBALS")) {
				closed = false;
				return true;
			}
		}
		if (UNEXPECTED(!isA(n, PT_CLASS_FUNC_CALL, is))) return false;
		if (is) {
			zval *name = read(pt_csi_func_call_name_site, n, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return false;
			bool isName;
			if (UNEXPECTED(!isA(name, PT_CLASS_NAME, isName))) return false;
			if (isName) {
				zv::Val nameHold = zv::Val::copyOf(zv::Ref(name));
				zv::Val lower = pt_call_method_cached(pt_csi_to_lower_string_site, Z_OBJ_P(nameHold.raw()), PT_LC("tolowerstring"), 0, NULL);
				if (UNEXPECTED(lower.isUndef())) return false;
				if (lower.ref().stringEquals("extract") || lower.ref().stringEquals("compact") || lower.ref().stringEquals("get_defined_vars")) {
					closed = false;
					return true;
				}
			}
		}
		if (UNEXPECTED(!isA(n, PT_CLASS_FUNCTION_LIKE, is))) return false;
		if (is && UNEXPECTED(!collectByRefParameterNames(n, byRefNames))) return false;
		bool isAssign, isAssignRef, isAssignOp;
		if (UNEXPECTED(!isA(n, PT_CLASS_ASSIGN_EXPR, isAssign))) return false;
		if (!isAssign && UNEXPECTED(!isA(n, PT_CLASS_ASSIGN_REF_EXPR, isAssignRef))) return false;
		if (!isAssign && !isAssignRef && UNEXPECTED(!isA(n, PT_CLASS_ASSIGN_OP_EXPR, isAssignOp))) return false;
		if (isAssign || isAssignRef || isAssignOp) {
			zval *var = read(pt_csi_var_site, n, PT_LC("var"));
			if (UNEXPECTED(var == NULL)) return false;
			zv::Val varHold = zv::Val::copyOf(zv::Ref(var));
			if (UNEXPECTED(!collectTargetNames(varHold.raw(), writtenNames))) return false;
		} else {
			if (UNEXPECTED(!isA(n, PT_CLASS_FOREACH_STMT, is))) return false;
			if (is) {
				zval *valueVar = read(pt_csi_value_var_site, n, PT_LC("valueVar"));
				if (UNEXPECTED(valueVar == NULL)) return false;
				zv::Val valueVarHold = zv::Val::copyOf(zv::Ref(valueVar));
				if (UNEXPECTED(!collectTargetNames(valueVarHold.raw(), writtenNames))) return false;
				zval *keyVar = read(pt_csi_key_var_site, n, PT_LC("keyVar"));
				if (UNEXPECTED(keyVar == NULL)) return false;
				if (Z_TYPE_P(keyVar) != IS_NULL) {
					zv::Val keyVarHold = zv::Val::copyOf(zv::Ref(keyVar));
					if (UNEXPECTED(!collectTargetNames(keyVarHold.raw(), writtenNames))) return false;
				}
			}
		}
		if (UNEXPECTED(!pushSubNodes(n, stack))) return false;
	}

	for (auto entry : zv::ArrRef(writtenNames.raw())) {
		zend_string *name = entry.stringKeyOrNull();
		if (name == NULL) continue;
		if (zend_hash_exists(byRefNames.table(), name)) {
			closed = false;
			return true;
		}
	}
	return true;
}

/* Mirrors returnsContextTypedExpression(); false = pending exception */
[[nodiscard]] bool returnsContextTypedExpression(zval *expr, bool &out)
{
	bool isArrow;
	if (UNEXPECTED(!isA(expr, PT_CLASS_ARROW_FUNCTION, isArrow))) return false;
	if (isArrow) {
		zval *body = read(pt_csi_arrow_expr_site, expr, PT_LC("expr"));
		if (UNEXPECTED(body == NULL)) return false;
		return isContextTyped(body, out);
	}

	zv::Val cached = pt_engine_node_get_attribute(Z_OBJ_P(expr), ZSTR_VAL(pt_csi_returns_context_typed_attribute), ZSTR_LEN(pt_csi_returns_context_typed_attribute));
	if (UNEXPECTED(cached.isUndef())) return false;
	if (!cached.isNull()) {
		out = zend_is_true(cached.raw());
		return true;
	}

	bool returnsContextTyped = false;
	zval *stmts = read(pt_csi_stmts_site, expr, PT_LC("stmts"));
	if (UNEXPECTED(stmts == NULL)) return false;
	zv::Arr stack = zv::Arr::create(0);
	if (Z_TYPE_P(stmts) == IS_ARRAY) {
		for (auto entry : zv::ArrRef(stmts)) {
			stack.push(zv::Val::copyOf(entry.value().deref()));
		}
	}
	for (;;) {
		zv::Val node = popNode(stack);
		if (node.isUndef()) break;
		zval *n = node.raw();
		bool is;
		if (UNEXPECTED(!isA(n, PT_CLASS_RETURN_STMT, is))) return false;
		if (is) {
			zval *returned = read(pt_csi_return_expr_site, n, PT_LC("expr"));
			if (UNEXPECTED(returned == NULL)) return false;
			if (Z_TYPE_P(returned) != IS_NULL) {
				bool typed;
				if (UNEXPECTED(!isContextTyped(returned, typed))) return false;
				if (typed) {
					returnsContextTyped = true;
					break;
				}
			}
			continue;
		}
		if (UNEXPECTED(!isA(n, PT_CLASS_FUNCTION_LIKE, is))) return false;
		if (is) continue;
		if (UNEXPECTED(!isA(n, PT_CLASS_CLASS_LIKE_STMT, is))) return false;
		if (is) continue;
		if (UNEXPECTED(!pushSubNodes(n, stack))) return false;
	}

	zval value;
	ZVAL_BOOL(&value, returnsContextTyped);
	if (UNEXPECTED(!pt_engine_node_set_attribute(Z_OBJ_P(expr), ZSTR_VAL(pt_csi_returns_context_typed_attribute), ZSTR_LEN(pt_csi_returns_context_typed_attribute), &value))) return false;
	out = returnsContextTyped;
	return true;
}

/* TemplateTypeFactory::create(TemplateTypeScope::createWithAnonymousFunction(),
 * $name, null, $variance); UNDEF = pending exception */
zv::Val anonymousTemplate(zend_string *name, zend_long variance)
{
	zval scope;
	if (UNEXPECTED(!pt_template_type_scope_new(&scope, NULL, NULL))) return zv::Val();
	zv::Val scopeHold = zv::Val::adopt(scope);
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	zval null;
	ZVAL_NULL(&null);
	zv::Val varianceHold = pt_type_template_type_variance(variance);
	if (UNEXPECTED(varianceHold.isUndef())) return zv::Val();
	return pt_template_type_factory_create(scopeHold.raw(), &nameZv, &null, varianceHold.raw(), NULL, NULL);
}

/* $scope->hasVariableType($name)->yes() ? $scope->getVariableType($name) :
 * new NullType(); UNDEF = pending exception */
zv::Val variableTypeOrNull(zval *scope, zend_string *name)
{
	zv::Val has = pt_mutating_scope_has_variable_type(Z_OBJ_P(scope), name);
	if (UNEXPECTED(has.isUndef())) return zv::Val();
	if (pt_type_trinary_value(has.raw()) == PT_TRI_YES) return pt_mutating_scope_get_variable_type(Z_OBJ_P(scope), name);
	zval nullType;
	if (UNEXPECTED(!pt_null_type_new(&nullType))) return zv::Val();
	return zv::Val::adopt(nullType);
}

/* $prefix . $name */
zv::Str prefixedName(char prefix, zend_string *name)
{
	return zv::Str::adopt(zend_string_concat2(&prefix, 1, ZSTR_VAL(name), ZSTR_LEN(name)));
}

/* new UnresolvedTemplateArgumentType($site, TemplateTypeFactory::create(
 * TemplateTypeScope::createWithAnonymousFunction(), $name, null,
 * $variance), $initialType); $initialType NULL for null; UNDEF = pending
 * exception */
zv::Val anonymousMarker(zval *site, zend_string *name, zend_long variance, zval *initialType)
{
	zv::Val templateType = anonymousTemplate(name, variance);
	if (UNEXPECTED(templateType.isUndef())) return zv::Val();
	zval marker;
	if (UNEXPECTED(!pt_unresolved_template_argument_type_new(&marker, site, templateType.raw(), initialType))) return zv::Val();
	return zv::Val::adopt(marker);
}

/* the name of a Variable node's string name, NULL otherwise; false =
 * pending exception */
[[nodiscard]] bool variableStringName(pt_property_site &nameSite, zval *var, zend_string *&out)
{
	out = NULL;
	bool isVariable;
	if (UNEXPECTED(!isA(var, PT_CLASS_VARIABLE, isVariable))) return false;
	if (!isVariable) return true;
	zval *name = read(nameSite, var, PT_LC("name"));
	if (UNEXPECTED(name == NULL)) return false;
	if (Z_TYPE_P(name) == IS_STRING) out = Z_STR_P(name);
	return true;
}

/* Mirrors the private bodyYieldsOrInvokes(); false = pending exception */
[[nodiscard]] bool bodyYieldsOrInvokes(zval *stmts, zval *names, bool &out)
{
	out = false;
	zv::Arr stack = zv::Arr::create(0);
	if (Z_TYPE_P(stmts) == IS_ARRAY) {
		for (auto entry : zv::ArrRef(stmts)) {
			stack.push(zv::Val::copyOf(entry.value().deref()));
		}
	}
	for (;;) {
		zv::Val node = popNode(stack);
		if (node.isUndef()) break;
		zval *n = node.raw();
		bool is;
		if (UNEXPECTED(!isA(n, PT_CLASS_FUNCTION_LIKE, is))) return false;
		if (is) continue;
		if (UNEXPECTED(!isA(n, PT_CLASS_CLASS_LIKE_STMT, is))) return false;
		if (is) continue;
		if (UNEXPECTED(!isA(n, PT_CLASS_YIELD, is))) return false;
		if (!is && UNEXPECTED(!isA(n, PT_CLASS_YIELD_FROM, is))) return false;
		if (is) {
			out = true;
			return true;
		}
		if (UNEXPECTED(!isA(n, PT_CLASS_FUNC_CALL, is))) return false;
		if (is) {
			zval *name = read(pt_csi_by_ref_func_call_name_site, n, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return false;
			zend_string *calleeName;
			if (UNEXPECTED(!variableStringName(pt_csi_by_ref_variable_name_site, name, calleeName))) return false;
			if (calleeName != NULL) {
				for (auto entry : zv::ArrRef(names)) {
					zval *candidate = entry.value().deref().raw();
					if (Z_TYPE_P(candidate) == IS_STRING && zend_string_equals(Z_STR_P(candidate), calleeName)) {
						out = true;
						return true;
					}
				}
			}
		}
		if (UNEXPECTED(!pushSubNodes(n, stack))) return false;
	}
	return true;
}

/* Mirrors the private byRefUseNames(): the attribute-cached list; UNDEF =
 * pending exception */
zv::Val byRefUseNames(zval *expr)
{
	zv::Val cached = pt_engine_node_get_attribute(Z_OBJ_P(expr), ZSTR_VAL(pt_csi_by_ref_uses_attribute), ZSTR_LEN(pt_csi_by_ref_uses_attribute));
	if (UNEXPECTED(cached.isUndef())) return zv::Val();
	if (cached.ref().isArray()) return cached;

	zv::Arr names = zv::Arr::empty();
	zval *uses = read(pt_csi_by_ref_uses_site, expr, PT_LC("uses"));
	if (UNEXPECTED(uses == NULL)) return zv::Val();
	if (Z_TYPE_P(uses) == IS_ARRAY) {
		zv::Val usesHold = zv::Val::copyOf(zv::Ref(uses));
		for (auto entry : zv::ArrRef(usesHold.raw())) {
			zval *use = entry.value().deref().raw();
			zval *byRef = read(pt_csi_by_ref_use_by_ref_site, use, PT_LC("byRef"));
			if (UNEXPECTED(byRef == NULL)) return zv::Val();
			if (!zend_is_true(byRef)) continue;
			zval *var = read(pt_csi_by_ref_use_var_site, use, PT_LC("var"));
			if (UNEXPECTED(var == NULL)) return zv::Val();
			zval *name = read(pt_csi_by_ref_variable_name_site, var, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return zv::Val();
			if (Z_TYPE_P(name) != IS_STRING) continue;
			names.push(zv::Ref(name));
		}
	}
	if (zend_hash_num_elements(Z_ARRVAL_P(names.raw())) > 0) {
		zval *stmts = read(pt_csi_stmts_site, expr, PT_LC("stmts"));
		if (UNEXPECTED(stmts == NULL)) return zv::Val();
		zv::Val stmtsHold = zv::Val::copyOf(zv::Ref(stmts));
		bool yieldsOrInvokes;
		bool ok = true;
		pt_engine_with_stack([&]() { ok = bodyYieldsOrInvokes(stmtsHold.raw(), names.raw(), yieldsOrInvokes); });
		if (UNEXPECTED(!ok)) return zv::Val();
		if (yieldsOrInvokes) names = zv::Arr::empty();
	}
	if (UNEXPECTED(!pt_engine_node_set_attribute(Z_OBJ_P(expr), ZSTR_VAL(pt_csi_by_ref_uses_attribute), ZSTR_LEN(pt_csi_by_ref_uses_attribute), names.raw()))) return zv::Val();
	return zv::Val(std::move(names));
}

/* collectCaptureEscapes()'s traversal: static function (Type $type,
 * callable $traverse) use (&$constraints): Type — the constraints in the
 * first state slot */
void captureEscapesCallback(zval *constraints, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state1;
	if (UNEXPECTED(argc < 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
		zend_type_error("ClosureSignatureInference traversal: expected (Type $type, callable $traverse)");
		return;
	}
	zval *type = &argv[0];
	if (instanceof_function(Z_OBJCE_P(type), pt_ce_closure_type)) {
		zv::Val markers = pt_closure_type_get_by_ref_use_types(type);
		if (UNEXPECTED(markers.isUndef())) return;
		if (markers.ref().isArray()) {
			for (auto entry : zv::ArrRef(markers.raw())) {
				zval *marker = entry.value().deref().raw();
				if (Z_TYPE_P(marker) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(marker), pt_ce_unresolved_template_argument_type)) continue;
				zv::Val next = pt_template_argument_constraints_with_unconstraining_send(constraints, marker);
				if (UNEXPECTED(next.isUndef())) return;
				zval_ptr_dtor(constraints);
				ZVAL_COPY_VALUE(constraints, next.raw());
				ZVAL_UNDEF(next.raw());
			}
		}
	}
	zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
	if (UNEXPECTED(traversed.isUndef())) return;
	traversed.intoReturnValue(return_value);
}

/* collectByRefEntryTypes()'s fact visitor: the entry facts into
 * $types[spl_object_id($site)][$name] */
bool collectEntryFact(void *data, zval *fact)
{
	zv::Arr &types = *static_cast<zv::Arr *>(data);
	zval *marker = zend_hash_index_find(Z_ARRVAL_P(fact), 0);
	zval *type = zend_hash_index_find(Z_ARRVAL_P(fact), 1);
	if (marker == NULL || type == NULL || Z_TYPE_P(type) == IS_NULL) return true;
	zv::Val templateName = pt_unresolved_template_argument_type_get_template_name(marker);
	if (UNEXPECTED(templateName.isUndef())) return false;
	if (UNEXPECTED(!templateName.ref().isString())) {
		zend_type_error("str_starts_with(): Argument #1 ($haystack) must be of type string, %s given", zend_zval_value_name(templateName.raw()));
		return false;
	}
	zend_string *fullName = Z_STR_P(templateName.raw());
	if (ZSTR_LEN(fullName) < 1 || ZSTR_VAL(fullName)[0] != pt_csi_entry_template_prefix) return true;
	zval *site = pt_unresolved_template_argument_type_site(marker);
	if (UNEXPECTED(site == NULL)) return false;
	bool isClosure;
	if (UNEXPECTED(!isA(site, PT_CLASS_CLOSURE_EXPR, isClosure))) return false;
	if (!isClosure) return true;

	types.separate();
	zend_ulong id = Z_OBJ_HANDLE_P(site);
	zval *byName = zend_hash_index_find(types.table(), id);
	if (byName == NULL) {
		zval empty;
		array_init(&empty);
		byName = zend_hash_index_add_new(types.table(), id, &empty);
	}
	SEPARATE_ARRAY(byName);
	zv::Str name = zv::Str::adopt(zend_string_init(ZSTR_VAL(fullName) + 1, ZSTR_LEN(fullName) - 1, 0));
	zval *existing = zend_symtable_find(Z_ARRVAL_P(byName), name.get());
	zval joined;
	if (existing != NULL) {
		zval argv[2];
		ZVAL_COPY_VALUE(&argv[0], existing);
		ZVAL_COPY_VALUE(&argv[1], type);
		zv::Val unioned = pt_type_combinator_union(2, argv);
		if (UNEXPECTED(unioned.isUndef())) return false;
		ZVAL_COPY_VALUE(&joined, unioned.raw());
		ZVAL_UNDEF(unioned.raw());
	} else {
		ZVAL_COPY(&joined, type);
	}
	zend_symtable_update(Z_ARRVAL_P(byName), name.get(), &joined);
	return true;
}

/* the key collectMarkers() files a marker under: spl_object_id($site) . '#'
 * . $templateName; false = pending exception */
[[nodiscard]] bool addMarker(zval *markers, zval *marker)
{
	zval *site = pt_unresolved_template_argument_type_site(marker);
	if (UNEXPECTED(site == NULL)) return false;
	zv::Val templateName = pt_unresolved_template_argument_type_get_template_name(marker);
	if (UNEXPECTED(templateName.isUndef())) return false;
	if (UNEXPECTED(!templateName.ref().isString())) {
		zend_type_error("Unsupported operand types: string . %s", zend_zval_value_name(templateName.raw()));
		return false;
	}
	zv::Str key = zv::Str::adopt(zend_strpprintf(0, "%u#%s", Z_OBJ_HANDLE_P(site), Z_STRVAL_P(templateName.raw())));
	SEPARATE_ARRAY(markers);
	Z_TRY_ADDREF_P(marker);
	zend_symtable_update(Z_ARRVAL_P(markers), key.get(), marker);
	return true;
}

/* collectMarkers()'s traversal: static function (Type $type, callable
 * $traverse) use (&$markers): Type — the markers in the first state slot */
void collectMarkersBody(zval *markers, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state1;
	if (UNEXPECTED(argc < 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
		zend_type_error("ClosureSignatureInference::collectMarkers() traversal: expected (Type $type, callable $traverse)");
		return;
	}
	zval *type = &argv[0];
	if (instanceof_function(Z_OBJCE_P(type), pt_ce_closure_type)) {
		zv::Val byRefUseTypes = pt_closure_type_get_by_ref_use_types(type);
		if (UNEXPECTED(byRefUseTypes.isUndef())) return;
		if (byRefUseTypes.ref().isArray()) {
			for (auto entry : zv::ArrRef(byRefUseTypes.raw())) {
				zval *marker = entry.value().deref().raw();
				if (Z_TYPE_P(marker) != IS_OBJECT || Z_OBJCE_P(marker) != pt_ce_unresolved_template_argument_type) continue;
				if (UNEXPECTED(!addMarker(markers, marker))) return;
			}
		}
	}
	if (Z_OBJCE_P(type) == pt_ce_unresolved_template_argument_type) {
		bool closureMarker;
		if (UNEXPECTED(!pt_unresolved_template_argument_type_is_closure_signature(type, closureMarker))) return;
		if (closureMarker && UNEXPECTED(!addMarker(markers, type))) return;
		zv::Val initial = pt_type_call(Z_OBJ_P(type), PT_LC("getinitialtype"), 0, NULL);
		if (UNEXPECTED(initial.isUndef())) return;
		if (!initial.isNull()) {
			zv::Val traversed = pt_type_call_callable(&argv[1], 1, initial.raw());
			if (UNEXPECTED(traversed.isUndef())) return;
		}
		ZVAL_COPY(return_value, type);
		return;
	}
	zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
	if (UNEXPECTED(traversed.isUndef())) return;
	traversed.intoReturnValue(return_value);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\Generics\ClosureSignatureInference; UNDEF / false =
 * pending exception. */
class ClosureSignatureInference
{
public:
	explicit ClosureSignatureInference(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(bool enabled)
	{
		zv::ObjRef(self).propAtWrite(slots::enabled, zv::Val::boolean(enabled));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, slots::enabled)) = 0;
	}

	/* Mirrors isClosureSignatureMarker() */
	[[nodiscard]] static bool isClosureSignatureMarker(zval *marker, bool &out)
	{
		return pt_unresolved_template_argument_type_is_closure_signature(marker, out);
	}

	/* Mirrors isReturnMarker() */
	[[nodiscard]] static bool isReturnMarker(zval *marker, bool &out)
	{
		return pt_unresolved_template_argument_type_is_closure_return(marker, out);
	}

	/* Mirrors isByRefMarker(); false = pending exception */
	[[nodiscard]] static bool isByRefMarker(zval *marker, bool &out)
	{
		out = false;
		zv::Val name = pt_unresolved_template_argument_type_get_template_name(marker);
		if (UNEXPECTED(name.isUndef())) return false;
		if (UNEXPECTED(!name.ref().isString())) {
			zend_type_error("str_starts_with(): Argument #1 ($haystack) must be of type string, %s given", zend_zval_value_name(name.raw()));
			return false;
		}
		if (Z_STRLEN_P(name.raw()) < 1 || Z_STRVAL_P(name.raw())[0] != pt_csi_by_ref_template_prefix) return true;
		zval *site = pt_unresolved_template_argument_type_site(marker);
		if (UNEXPECTED(site == NULL)) return false;
		return isA(site, PT_CLASS_CLOSURE_EXPR, out);
	}

	/* Mirrors getByRefUseMarkers() */
	zv::Val getByRefUseMarkers(zval *scope, zval *expr) const
	{
		bool isClosure;
		if (UNEXPECTED(!isA(expr, PT_CLASS_CLOSURE_EXPR, isClosure))) return zv::Val();
		if (!isClosure) return zv::Val(zv::Arr::empty());
		zv::Val names = byRefUseNames(expr);
		if (UNEXPECTED(names.isUndef())) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(names.raw())) == 0) return zv::Val(zv::Arr::empty());
		zv::Val frame;
		if (UNEXPECTED(!getFrame(scope, frame))) return zv::Val();
		if (frame.isNull()) return zv::Val(zv::Arr::empty());
		bool observingClosures;
		if (UNEXPECTED(!pt_template_argument_frame_is_observing_closures(frame.raw(), observingClosures))) return zv::Val();
		if (!observingClosures) {
			zv::Val mode = pt_template_argument_frame_get_by_ref_site_mode(frame.raw(), expr);
			if (UNEXPECTED(mode.isUndef())) return zv::Val();
			if (mode.isNull()) return zv::Val(zv::Arr::empty());
		}

		zv::Arr markers = zv::Arr::empty();
		for (auto entry : zv::ArrRef(names.raw())) {
			zval *name = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(name) != IS_STRING)) continue;
			zv::Str templateName = prefixedName(pt_csi_by_ref_template_prefix, Z_STR_P(name));
			zv::Val initial = variableTypeOrNull(scope, Z_STR_P(name));
			if (UNEXPECTED(initial.isUndef())) return zv::Val();
			zv::Val marker = anonymousMarker(expr, templateName.get(), PT_TEMPLATE_TYPE_VARIANCE_COVARIANT, initial.raw());
			if (UNEXPECTED(marker.isUndef())) return zv::Val();
			markers.set(Z_STR_P(name), std::move(marker));
		}
		return zv::Val(std::move(markers));
	}

	/* Mirrors getByRefSiteMode(): 'local' / 'escaped' / PHP null */
	zv::Val getByRefSiteMode(zval *scope, zval *expr) const
	{
		zv::Val frame;
		if (UNEXPECTED(!getFrame(scope, frame))) return zv::Val();
		if (frame.isNull()) return zv::Val::null();
		bool observingClosures;
		if (UNEXPECTED(!pt_template_argument_frame_is_observing_closures(frame.raw(), observingClosures))) return zv::Val();
		if (observingClosures) return zv::Val::null();
		return pt_template_argument_frame_get_by_ref_site_mode(frame.raw(), expr);
	}

	/* Mirrors getByRefSeed() */
	zv::Val getByRefSeed(zval *scope, zval *expr, zend_string *name) const
	{
		zv::Val frame;
		if (UNEXPECTED(!getFrame(scope, frame))) return zv::Val();
		if (frame.isNull()) return zv::Val::null();
		bool observingClosures;
		if (UNEXPECTED(!pt_template_argument_frame_is_observing_closures(frame.raw(), observingClosures))) return zv::Val();
		if (observingClosures) return zv::Val::null();
		zv::Str templateName = prefixedName(pt_csi_by_ref_template_prefix, name);
		return pt_template_argument_frame_resolve(frame.raw(), expr, templateName.get());
	}

	/* Mirrors findCreationScope() */
	static zv::Val findCreationScope(zval *scope, zval *storage, zval *expr)
	{
		zv::Val creationResult = pt_expression_result_storage_find(storage, expr);
		if (UNEXPECTED(creationResult.isUndef())) return zv::Val();
		if (creationResult.isNull()) return zv::Val::null();
		zv::Val creationScopeHold;
		zval *creationScope = pt_expression_result_before_scope(creationResult.raw(), creationScopeHold);
		if (UNEXPECTED(creationScope == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(creationScope) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getAnonymousFunctionReflection() on %s", zend_zval_value_name(creationScope));
			return zv::Val();
		}
		zv::Val creationReflection = pt_mutating_scope_get_anonymous_function_reflection(Z_OBJ_P(creationScope));
		if (UNEXPECTED(creationReflection.isUndef())) return zv::Val();
		zv::Val reflection = pt_mutating_scope_get_anonymous_function_reflection(Z_OBJ_P(scope));
		if (UNEXPECTED(reflection.isUndef())) return zv::Val();
		if (!zend_is_identical(creationReflection.raw(), reflection.raw())) return zv::Val::null();
		zv::Val creationFunction = pt_mutating_scope_get_function(Z_OBJ_P(creationScope));
		if (UNEXPECTED(creationFunction.isUndef())) return zv::Val();
		zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
		if (UNEXPECTED(function.isUndef())) return zv::Val();
		if (!zend_is_identical(creationFunction.raw(), function.raw())) return zv::Val::null();
		return zv::Val::copyOf(zv::Ref(creationScope));
	}

	/* Mirrors collectCaptureEscapes() */
	static zv::Val collectCaptureEscapes(zval *type)
	{
		zv::Val constraints = pt_template_argument_constraints_create_empty();
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		zv::Val callback = pt_type_native_callback(captureEscapesCallback, constraints.raw(), NULL);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		zv::Val mapped = pt_type_traverser_map_of(type, callback.raw());
		if (UNEXPECTED(mapped.isUndef())) return zv::Val();
		return zv::Val::copyOf(zv::Ref(pt_type_native_callback_state(callback.raw(), 0)));
	}

	/* Mirrors the private static collectMarkers(): the markers keyed by site
	 * and name */
	static zv::Val collectMarkers(zval *type)
	{
		zv::Val markers(zv::Arr::create(0));
		zv::Val callback = pt_type_native_callback(collectMarkersBody, markers.raw(), NULL);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		zv::Val mapped = pt_type_traverser_map_of(type, callback.raw());
		if (UNEXPECTED(mapped.isUndef())) return zv::Val();
		return zv::Val::copyOf(zv::Ref(pt_type_native_callback_state(callback.raw(), 0)));
	}

	/* withUnconstrainingSend() of every marker of $markers not in $kept (or
	 * of every marker when $kept is NULL) onto $constraints */
	static zv::Val unconstrainMarkers(zv::Val constraints, zval *markers, zval *kept)
	{
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		for (auto entry : zv::ArrRef(markers)) {
			zend_string *key = entry.stringKeyOrNull();
			if (kept != NULL && key != NULL && zend_symtable_find(Z_ARRVAL_P(kept), key) != NULL) continue;
			constraints = pt_template_argument_constraints_with_unconstraining_send(constraints.raw(), entry.value().deref().raw());
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		}
		return constraints;
	}

	/* Mirrors collectEscapes() */
	static zv::Val collectEscapes(zval *type)
	{
		zv::Val constraints = pt_template_argument_constraints_create_empty();
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		zv::Val markers = collectMarkers(type);
		if (UNEXPECTED(markers.isUndef())) return zv::Val();
		return unconstrainMarkers(std::move(constraints), markers.raw(), NULL);
	}

	/* Mirrors collectInvokedCallee() */
	static zv::Val collectInvokedCallee(zval *calleeType)
	{
		zv::Val constraints = pt_template_argument_constraints_create_empty();
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		zv::Val members;
		if (instanceof_function(Z_OBJCE_P(calleeType), pt_ce_union_type)) {
			members = pt_union_type_get_types(Z_OBJ_P(calleeType));
			if (UNEXPECTED(members.isUndef())) return zv::Val();
		} else {
			zv::Arr single = zv::Arr::create(1);
			single.push(zv::Val::copyOf(zv::Ref(calleeType)));
			members = zv::Val(std::move(single));
		}
		for (auto entry : zv::ArrRef(members.raw())) {
			zval *member = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(member) != IS_OBJECT)) continue;
			if (!instanceof_function(Z_OBJCE_P(member), pt_ce_closure_type)) {
				zv::Val escapes = collectEscapes(member);
				if (UNEXPECTED(escapes.isUndef())) return zv::Val();
				constraints = pt_template_argument_constraints_merge(constraints.raw(), escapes.raw());
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
				continue;
			}
			zv::Val returnType = pt_type_call(Z_OBJ_P(member), PT_LC("getreturntype"), 0, NULL);
			if (UNEXPECTED(returnType.isUndef())) return zv::Val();
			if (!returnType.ref().isObject() || Z_OBJCE_P(returnType.raw()) != pt_ce_unresolved_template_argument_type) continue;
			bool returnMarker;
			if (UNEXPECTED(!isReturnMarker(returnType.raw(), returnMarker))) return zv::Val();
			if (!returnMarker) continue;
			constraints = pt_template_argument_constraints_with_unconstraining_send(constraints.raw(), returnType.raw());
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		}
		return constraints;
	}

	/* Mirrors collectAbsorbed() */
	static zv::Val collectAbsorbed(zval *input, zval *result)
	{
		zv::Val constraints = pt_template_argument_constraints_create_empty();
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		if (Z_TYPE_P(input) == IS_OBJECT && Z_TYPE_P(result) == IS_OBJECT && Z_OBJ_P(input) == Z_OBJ_P(result)) return constraints;
		zv::Val markers = collectMarkers(input);
		if (UNEXPECTED(markers.isUndef())) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(markers.raw())) == 0) return constraints;
		zv::Val kept = collectMarkers(result);
		if (UNEXPECTED(kept.isUndef())) return zv::Val();
		return unconstrainMarkers(std::move(constraints), markers.raw(), kept.raw());
	}

	/* Mirrors hasMarkers() */
	[[nodiscard]] static bool hasMarkers(zval *type, bool &out)
	{
		zv::Val markers = collectMarkers(type);
		if (UNEXPECTED(markers.isUndef())) return false;
		out = zend_hash_num_elements(Z_ARRVAL_P(markers.raw())) > 0;
		return true;
	}

	/* Mirrors collectAbsorbedInUnion() */
	static zv::Val collectAbsorbedInUnion(zval *types)
	{
		zv::Val constraints = pt_template_argument_constraints_create_empty();
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		bool carriesMarkers = false;
		for (auto entry : zv::ArrRef(types)) {
			bool has;
			if (UNEXPECTED(!hasMarkers(entry.value().deref().raw(), has))) return zv::Val();
			if (!has) continue;
			carriesMarkers = true;
			break;
		}
		if (!carriesMarkers) return constraints;

		uint32_t count = zend_hash_num_elements(Z_ARRVAL_P(types));
		zval *argv = static_cast<zval *>(safe_emalloc(count, sizeof(zval), 0));
		uint32_t i = 0;
		for (auto entry : zv::ArrRef(types)) {
			ZVAL_COPY_VALUE(&argv[i++], entry.value().deref().raw());
		}
		zv::Val unionType = pt_type_combinator_union(count, argv);
		efree(argv);
		if (UNEXPECTED(unionType.isUndef())) return zv::Val();
		for (auto entry : zv::ArrRef(types)) {
			zv::Val absorbed = collectAbsorbed(entry.value().deref().raw(), unionType.raw());
			if (UNEXPECTED(absorbed.isUndef())) return zv::Val();
			constraints = pt_template_argument_constraints_merge(constraints.raw(), absorbed.raw());
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		}
		return constraints;
	}

	/* Mirrors collectInvocation() */
	static zv::Val collectInvocation(zval *scope, zval *call, zval *closureType, bool observing)
	{
		zv::Val constraints = pt_template_argument_constraints_create_empty();
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		if (observing) {
			zv::Val site = anonymousMarker(call, pt_csi_invocation_template_name, PT_TEMPLATE_TYPE_VARIANCE_INVARIANT, NULL);
			if (UNEXPECTED(site.isUndef())) return zv::Val();
			constraints = pt_template_argument_constraints_with_site(constraints.raw(), site.raw());
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		}
		zv::Val markers = pt_closure_type_get_by_ref_use_types(closureType);
		if (UNEXPECTED(markers.isUndef())) return zv::Val();
		if (!markers.ref().isArray()) return constraints;
		for (auto entry : zv::ArrRef(markers.raw())) {
			zval *marker = entry.value().deref().raw();
			if (Z_TYPE_P(marker) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(marker), pt_ce_unresolved_template_argument_type)) continue;
			zend_string *name = entry.stringKeyOrNull();
			zv::Str nameHold = name != NULL ? zv::Str::copyOf(name) : zv::Str::adopt(zend_long_to_str((zend_long) entry.indexKey()));
			zv::Val type = variableTypeOrNull(scope, nameHold.get());
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			if (observing) {
				constraints = pt_template_argument_constraints_with_lower_bound(constraints.raw(), marker, type.raw());
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
				continue;
			}
			zval *site = pt_unresolved_template_argument_type_site(marker);
			if (UNEXPECTED(site == NULL)) return zv::Val();
			zv::Str templateName = prefixedName(pt_csi_entry_template_prefix, nameHold.get());
			zv::Val entryMarker = anonymousMarker(site, templateName.get(), PT_TEMPLATE_TYPE_VARIANCE_INVARIANT, NULL);
			if (UNEXPECTED(entryMarker.isUndef())) return zv::Val();
			constraints = pt_template_argument_constraints_with_lower_bound(constraints.raw(), entryMarker.raw(), type.raw());
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		}
		return constraints;
	}

	/* Mirrors collectByRefEntryTypes() */
	static zv::Val collectByRefEntryTypes(zval *scope)
	{
		zv::Val constraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(scope));
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::empty();
		if (constraints.isNull()) return zv::Val(std::move(types));
		if (UNEXPECTED(!pt_template_argument_constraints_facts(constraints.raw(), &collectEntryFact, &types))) return zv::Val();
		return zv::Val(std::move(types));
	}

	/* Mirrors getArrowFunctionOuterVariables() */
	static zv::Val getArrowFunctionOuterVariables(zval *expr)
	{
		zv::Val cached = pt_engine_node_get_attribute(Z_OBJ_P(expr), ZSTR_VAL(pt_csi_arrow_function_outer_variables_attribute), ZSTR_LEN(pt_csi_arrow_function_outer_variables_attribute));
		if (UNEXPECTED(cached.isUndef())) return zv::Val();
		if (cached.ref().isArray()) return cached;

		zv::ScratchTable parameters(8);
		zval *params = read(pt_csi_params_site, expr, PT_LC("params"));
		if (UNEXPECTED(params == NULL)) return zv::Val();
		if (Z_TYPE_P(params) == IS_ARRAY) {
			zv::Val paramsHold = zv::Val::copyOf(zv::Ref(params));
			for (auto entry : zv::ArrRef(paramsHold.raw())) {
				zval *param = entry.value().deref().raw();
				zval *var = read(pt_csi_by_ref_param_var_site, param, PT_LC("var"));
				if (UNEXPECTED(var == NULL)) return zv::Val();
				zend_string *name;
				if (UNEXPECTED(!variableStringName(pt_csi_by_ref_variable_name_site, var, name))) return zv::Val();
				if (name == NULL) continue;
				zval marked;
				ZVAL_TRUE(&marked);
				zend_symtable_update(parameters.table(), name, &marked);
			}
		}
		zv::Arr names = zv::Arr::create(0);
		zv::Arr stack = zv::Arr::create(0);
		zval *body = read(pt_csi_arrow_expr_site, expr, PT_LC("expr"));
		if (UNEXPECTED(body == NULL)) return zv::Val();
		stack.push(zv::Ref(body));
		for (;;) {
			zv::Val node = popNode(stack);
			if (node.isUndef()) break;
			zval *n = node.raw();
			bool is;
			if (UNEXPECTED(!isA(n, PT_CLASS_FUNCTION_STMT, is))) return zv::Val();
			if (!is && UNEXPECTED(!isA(n, PT_CLASS_CLASS_LIKE_STMT, is))) return zv::Val();
			if (is) continue;
			if (UNEXPECTED(!isA(n, PT_CLASS_CLOSURE_EXPR, is))) return zv::Val();
			if (is) {
				// a closure captures through its use clause only
				zval *uses = read(pt_csi_by_ref_uses_site, n, PT_LC("uses"));
				if (UNEXPECTED(uses == NULL)) return zv::Val();
				if (Z_TYPE_P(uses) == IS_ARRAY) {
					zv::Val usesHold = zv::Val::copyOf(zv::Ref(uses));
					for (auto entry : zv::ArrRef(usesHold.raw())) {
						zval *var = read(pt_csi_by_ref_use_var_site, entry.value().deref().raw(), PT_LC("var"));
						if (UNEXPECTED(var == NULL)) return zv::Val();
						stack.push(zv::Val::copyOf(zv::Ref(var)));
					}
				}
				continue;
			}
			zend_string *name;
			if (UNEXPECTED(!variableStringName(pt_csi_by_ref_variable_name_site, n, name))) return zv::Val();
			if (name != NULL && !zend_symtable_exists(parameters.table(), name) && !zend_string_equals_literal(name, "this")) {
				names.set(name, zv::Val::boolean(true));
			}
			if (UNEXPECTED(!pushSubNodes(n, stack))) return zv::Val();
		}
		zv::Arr list = zv::Arr::empty();
		for (auto entry : zv::ArrRef(names.raw())) {
			zend_string *key = entry.stringKeyOrNull();
			if (key != NULL) {
				list.push(zv::Val::string(key));
			} else {
				list.push(zv::Val::integer((zend_long) entry.indexKey()));
			}
		}
		if (UNEXPECTED(!pt_engine_node_set_attribute(Z_OBJ_P(expr), ZSTR_VAL(pt_csi_arrow_function_outer_variables_attribute), ZSTR_LEN(pt_csi_arrow_function_outer_variables_attribute), list.raw()))) return zv::Val();
		return zv::Val(std::move(list));
	}

	/* Mirrors isObserving(); false = pending exception */
	[[nodiscard]] bool isObserving(zval *scope, bool &out) const
	{
		zv::Val frame;
		if (UNEXPECTED(!getFrame(scope, frame))) return false;
		if (frame.isNull()) {
			out = false;
			return true;
		}
		return pt_template_argument_frame_is_observing_closures(frame.raw(), out);
	}

	/* Mirrors getSignatureParameters() */
	zv::Val getSignatureParameters(zval *scope, zval *expr, zval *declaredParameters) const
	{
		zv::Val frame;
		if (UNEXPECTED(!getFrame(scope, frame))) return zv::Val();
		if (frame.isNull()) return zv::Val::copyOf(zv::Ref(declaredParameters));
		bool observing;
		if (UNEXPECTED(!pt_template_argument_frame_is_observing_closures(frame.raw(), observing))) return zv::Val();

		bool keepsMarkers = observing;
		if (!keepsMarkers && UNEXPECTED(!pt_template_argument_frame_is_settled_closure_site(frame.raw(), expr, keepsMarkers))) return zv::Val();

		zv::Arr parameters = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(declaredParameters)));
		for (auto entry : zv::ArrRef(declaredParameters)) {
			zval *parameter = entry.value().deref().raw();
			zv::Val passedByReference = ptclosure::parameterPassedByReference(parameter);
			if (UNEXPECTED(passedByReference.isUndef())) return zv::Val();
			zend_long mode = pt_passed_by_reference_mode(passedByReference.raw());
			if (UNEXPECTED(mode < 0)) return zv::Val();
			bool isVariadicParameter = false;
			if (mode == PT_PASSED_BY_REFERENCE_NO) {
				zv::Val isVariadic = pt_parameter_reflection_call(parameter, PT_PR_IS_VARIADIC);
				if (UNEXPECTED(isVariadic.isUndef())) return zv::Val();
				isVariadicParameter = zend_is_true(isVariadic.raw());
			}
			if (mode != PT_PASSED_BY_REFERENCE_NO || isVariadicParameter) {
				parameters.push(zv::Val::copyOf(zv::Ref(parameter)));
				continue;
			}
			zv::Val name = pt_parameter_reflection_call(parameter, PT_PR_GET_NAME);
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			zv::Val type;
			if (keepsMarkers) {
				type = createParameterMarker(expr, parameter, Z_STR_P(name.raw()));
			} else {
				zv::Str templateName = parameterTemplateName(Z_STR_P(name.raw()));
				type = pt_template_argument_frame_resolve(frame.raw(), expr, templateName.get());
				if (!type.isUndef() && type.isNull()) {
					parameters.push(zv::Val::copyOf(zv::Ref(parameter)));
					continue;
				}
			}
			if (UNEXPECTED(type.isUndef())) return zv::Val();

			zv::Val optional = pt_parameter_reflection_call(parameter, PT_PR_IS_OPTIONAL);
			if (UNEXPECTED(optional.isUndef())) return zv::Val();
			zv::Val variadic = pt_parameter_reflection_call(parameter, PT_PR_IS_VARIADIC);
			if (UNEXPECTED(variadic.isUndef())) return zv::Val();
			zv::Val defaultValue = pt_parameter_reflection_call(parameter, PT_PR_GET_DEFAULT_VALUE);
			if (UNEXPECTED(defaultValue.isUndef())) return zv::Val();
			zv::Args argv{name.raw(), optional.raw(), type.raw(), passedByReference.raw(), variadic.raw(), defaultValue.raw()};
			zv::Val nativeParameter = pt_native_parameter_reflection_new(6, argv);
			if (UNEXPECTED(nativeParameter.isUndef())) return zv::Val();
			parameters.push(std::move(nativeParameter));
		}
		return zv::Val(std::move(parameters));
	}

	/* Mirrors getBodyParameters() */
	zv::Val getBodyParameters(zval *scope, zval *expr) const
	{
		zv::Val frame;
		if (UNEXPECTED(!getFrame(scope, frame))) return zv::Val();
		if (frame.isNull()) return zv::Val::null();
		bool observing;
		if (UNEXPECTED(!pt_template_argument_frame_is_observing_closures(frame.raw(), observing))) return zv::Val();
		if (observing) return zv::Val::null();
		bool settled;
		if (UNEXPECTED(!pt_template_argument_frame_is_settled_closure_site(frame.raw(), expr, settled))) return zv::Val();
		if (settled) return zv::Val::null();

		zval *params = ptclosure::prop(ptclosure::paramsSite, expr, PT_LC("params"));
		if (UNEXPECTED(params == NULL)) return zv::Val();
		zv::Val paramsHold = zv::Val::copyOf(zv::Ref(params));
		zv::Arr parameters = zv::Arr::create(0);
		bool resolvedAny = false;
		if (Z_TYPE_P(paramsHold.raw()) == IS_ARRAY) {
			for (auto entry : zv::ArrRef(paramsHold.raw())) {
				zval *param = entry.value().deref().raw();
				zend_string *name;
				if (UNEXPECTED(!paramVariableName(param, name))) return zv::Val();
				if (name == NULL) return zv::Val::null();
				zv::Str nameHold = zv::Str::copyOf(name);
				zval *byRef = read(pt_csi_param_by_ref_site, param, PT_LC("byRef"));
				if (UNEXPECTED(byRef == NULL)) return zv::Val();
				bool isByRef = zend_is_true(byRef);
				zv::Val type = zv::Val::null();
				if (!isByRef) {
					zv::Str templateName = parameterTemplateName(nameHold.get());
					type = pt_template_argument_frame_resolve(frame.raw(), expr, templateName.get());
					if (UNEXPECTED(type.isUndef())) return zv::Val();
				}
				if (!type.isNull()) {
					resolvedAny = true;
				} else {
					zval mixed;
					if (UNEXPECTED(!pt_mixed_type_new(&mixed))) return zv::Val();
					type = zv::Val::adopt(mixed);
				}
				zval *defaultValue = read(pt_csi_param_default_site, param, PT_LC("default"));
				if (UNEXPECTED(defaultValue == NULL)) return zv::Val();
				zval *variadic = read(pt_csi_param_variadic_site, param, PT_LC("variadic"));
				if (UNEXPECTED(variadic == NULL)) return zv::Val();
				bool isVariadic = zend_is_true(variadic);
				zend_object *passedByReference = isByRef ? pt_passed_by_reference_create_creates_new_variable() : pt_passed_by_reference_create_no();
				if (UNEXPECTED(passedByReference == NULL)) return zv::Val();
				zval nameZv, optionalZv, passedByReferenceZv, variadicZv, nullZv;
				ZVAL_STR(&nameZv, nameHold.get());
				ZVAL_BOOL(&optionalZv, Z_TYPE_P(defaultValue) != IS_NULL || isVariadic);
				ZVAL_OBJ(&passedByReferenceZv, passedByReference);
				ZVAL_BOOL(&variadicZv, isVariadic);
				ZVAL_NULL(&nullZv);
				zv::Args argv{&nameZv, &optionalZv, type.raw(), &passedByReferenceZv, &variadicZv, &nullZv};
				zv::Val parameter = pt_native_parameter_reflection_new(6, argv);
				if (UNEXPECTED(parameter.isUndef())) return zv::Val();
				parameters.push(std::move(parameter));
			}
		}
		if (!resolvedAny) return zv::Val::null();
		return zv::Val(std::move(parameters));
	}

	/* Mirrors getSignatureReturnType() */
	zv::Val getSignatureReturnType(zval *scope, zval *expr, zval *returnType) const
	{
		if (Z_TYPE_P(returnType) == IS_OBJECT && instanceof_function(Z_OBJCE_P(returnType), pt_ce_unresolved_template_argument_type)) return zv::Val::copyOf(zv::Ref(returnType));
		zv::Val frame;
		if (UNEXPECTED(!getFrame(scope, frame))) return zv::Val();
		if (frame.isNull()) return zv::Val::copyOf(zv::Ref(returnType));
		bool observing;
		if (UNEXPECTED(!pt_template_argument_frame_is_observing_closures(frame.raw(), observing))) return zv::Val();
		if (!observing) {
			bool settled;
			if (UNEXPECTED(!pt_template_argument_frame_is_settled_closure_site(frame.raw(), expr, settled))) return zv::Val();
			if (!settled) return zv::Val::copyOf(zv::Ref(returnType));
		}
		bool returnsContextTyped;
		if (UNEXPECTED(!returnsContextTypedExpression(expr, returnsContextTyped))) return zv::Val();
		if (!returnsContextTyped) return zv::Val::copyOf(zv::Ref(returnType));

		zv::Val templateType = anonymousTemplate(pt_csi_return_template_name, PT_TEMPLATE_TYPE_VARIANCE_COVARIANT);
		if (UNEXPECTED(templateType.isUndef())) return zv::Val();
		zval marker;
		if (UNEXPECTED(!pt_unresolved_template_argument_type_new(&marker, expr, templateType.raw(), returnType))) return zv::Val();
		return zv::Val::adopt(marker);
	}

	/* Mirrors getExpectedReturnType() */
	zv::Val getExpectedReturnType(zval *scope, zval *expr) const
	{
		zv::Val frame;
		if (UNEXPECTED(!getFrame(scope, frame))) return zv::Val();
		if (frame.isNull()) return zv::Val::null();
		bool observing;
		if (UNEXPECTED(!pt_template_argument_frame_is_observing_closures(frame.raw(), observing))) return zv::Val();
		if (observing) return zv::Val::null();

		zv::Val type = pt_template_argument_frame_resolve(frame.raw(), expr, pt_csi_return_template_name);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (type.isNull() || instanceof_function(Z_OBJCE_P(type.raw()), pt_ce_mixed_type)) return zv::Val::null();
		return type;
	}

	/* Mirrors collectSites() */
	zv::Val collectSites(zval *scope, zval *closureType) const
	{
		zv::Val constraints = pt_template_argument_constraints_create_empty();
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		zv::Val frame;
		if (UNEXPECTED(!getFrame(scope, frame))) return zv::Val();
		if (frame.isNull()) return constraints;
		bool observing;
		if (UNEXPECTED(!pt_template_argument_frame_is_observing_closures(frame.raw(), observing))) return zv::Val();
		if (!observing) return constraints;

		zv::Val acceptors = pt_type_call(Z_OBJ_P(closureType), PT_LC("getcallableparametersacceptors"), 1, scope);
		if (UNEXPECTED(acceptors.isUndef())) return zv::Val();
		for (auto acceptorEntry : zv::ArrRef(acceptors.raw())) {
			zval *acceptor = acceptorEntry.value().deref().raw();
			zv::Val parameters = pt_parameters_acceptor_call(acceptor, PT_PA_GET_PARAMETERS);
			if (UNEXPECTED(parameters.isUndef())) return zv::Val();
			for (auto parameterEntry : zv::ArrRef(parameters.raw())) {
				zval *parameter = parameterEntry.value().deref().raw();
				zv::Val marker = pt_parameter_reflection_call(parameter, PT_PR_GET_TYPE);
				if (UNEXPECTED(marker.isUndef())) return zv::Val();
				if (!marker.ref().isObject() || !instanceof_function(Z_OBJCE_P(marker.raw()), pt_ce_unresolved_template_argument_type)) continue;
				bool closureMarker;
				if (UNEXPECTED(!isClosureSignatureMarker(marker.raw(), closureMarker))) return zv::Val();
				if (!closureMarker) continue;
				constraints = pt_template_argument_constraints_with_site(constraints.raw(), marker.raw());
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
				zv::Val defaultValue = pt_parameter_reflection_call(parameter, PT_PR_GET_DEFAULT_VALUE);
				if (UNEXPECTED(defaultValue.isUndef())) return zv::Val();
				if (defaultValue.isNull()) continue;

				// an invocation can always omit it
				zv::Val contravariant = pt_type_template_type_variance(PT_TEMPLATE_TYPE_VARIANCE_CONTRAVARIANT);
				if (UNEXPECTED(contravariant.isUndef())) return zv::Val();
				constraints = pt_template_argument_constraints_with_send(constraints.raw(), marker.raw(), defaultValue.raw(), contravariant.raw());
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
			}
			zv::Val returnMarker = pt_parameters_acceptor_call(acceptor, PT_PA_GET_RETURN_TYPE);
			if (UNEXPECTED(returnMarker.isUndef())) return zv::Val();
			if (!returnMarker.ref().isObject() || !instanceof_function(Z_OBJCE_P(returnMarker.raw()), pt_ce_unresolved_template_argument_type)) continue;
			bool isReturn;
			if (UNEXPECTED(!isReturnMarker(returnMarker.raw(), isReturn))) return zv::Val();
			if (!isReturn) continue;

			constraints = pt_template_argument_constraints_with_site(constraints.raw(), returnMarker.raw());
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		}
		if (instanceof_function(Z_OBJCE_P(closureType), pt_ce_closure_type)) {
			zv::Val markers = pt_closure_type_get_by_ref_use_types(closureType);
			if (UNEXPECTED(markers.isUndef())) return zv::Val();
			if (markers.ref().isArray()) {
				for (auto entry : zv::ArrRef(markers.raw())) {
					zval *marker = entry.value().deref().raw();
					if (Z_TYPE_P(marker) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(marker), pt_ce_unresolved_template_argument_type)) continue;
					constraints = pt_template_argument_constraints_with_site(constraints.raw(), marker);
					if (UNEXPECTED(constraints.isUndef())) return zv::Val();
				}
			}
		}

		return constraints;
	}

	/* Mirrors isClosedBody(); false = pending exception */
	[[nodiscard]] bool isClosedBody(zval *functionLike, zval *stmts, bool &out) const
	{
		if (!enabled()) {
			out = false;
			return true;
		}

		zv::Val cached = pt_engine_node_get_attribute(Z_OBJ_P(functionLike), ZSTR_VAL(pt_csi_closed_body_attribute), ZSTR_LEN(pt_csi_closed_body_attribute));
		if (UNEXPECTED(cached.isUndef())) return false;
		if (!cached.isNull()) {
			out = zend_is_true(cached.raw());
			return true;
		}

		bool closed = true;
		bool ok = true;
		pt_engine_with_stack([&]() { ok = scanClosedBody(functionLike, stmts, closed); });
		if (UNEXPECTED(!ok)) return false;
		zval value;
		ZVAL_BOOL(&value, closed);
		if (UNEXPECTED(!pt_engine_node_set_attribute(Z_OBJ_P(functionLike), ZSTR_VAL(pt_csi_closed_body_attribute), ZSTR_LEN(pt_csi_closed_body_attribute), &value))) return false;
		out = closed;
		return true;
	}

	/* Mirrors infersInvocationReturnType(); false = pending exception */
	[[nodiscard]] bool infersInvocationReturnType(zval *scope, zval *closureType, bool &out) const
	{
		out = false;
		if (!enabled()) return true;
		zv::Val frame = pt_mutating_scope_get_current_template_argument_frame(Z_OBJ_P(scope));
		if (UNEXPECTED(frame.isUndef())) return false;
		if (frame.isNull()) return true;

		zval contains;
		ZVAL_FALSE(&contains);
		zv::Val callback = pt_type_native_callback(containsMarkerBody, &contains, NULL);
		if (UNEXPECTED(callback.isUndef())) return false;
		zv::Val mapped = pt_type_traverser_map_of(closureType, callback.raw());
		if (UNEXPECTED(mapped.isUndef())) return false;
		out = Z_TYPE_P(pt_type_native_callback_state(callback.raw(), 0)) != IS_TRUE;
		return true;
	}

	/* Mirrors findAssignedClosures(); UNDEF = pending exception */
	zv::Val findAssignedClosures(zval *scope, zend_string *name) const
	{
		zv::Arr closures = zv::Arr::empty();
		if (!enabled()) return zv::Val(std::move(closures));

		zv::Val frame = pt_mutating_scope_get_current_template_argument_frame(Z_OBJ_P(scope));
		if (UNEXPECTED(frame.isUndef())) return zv::Val();
		while (!frame.isNull()) {
			zv::Val body = pt_template_argument_frame_get_closure_signature_body(frame.raw());
			if (UNEXPECTED(body.isUndef())) return zv::Val();
			if (!body.isNull()) {
				zv::Val stmts = pt_template_argument_frame_get_closure_signature_stmts(frame.raw());
				if (UNEXPECTED(stmts.isUndef())) return zv::Val();
				zv::Val assigned = getAssignedClosures(body.raw(), stmts.raw());
				if (UNEXPECTED(assigned.isUndef())) return zv::Val();
				zval *list = zend_symtable_find(Z_ARRVAL_P(assigned.raw()), name);
				if (list != NULL && Z_TYPE_P(list) == IS_ARRAY) {
					for (auto entry : zv::ArrRef(list)) {
						closures.push(zv::Ref(entry.value().deref().raw()));
					}
				}
			}
			frame = pt_template_argument_frame_get_parent(frame.raw());
			if (UNEXPECTED(frame.isUndef())) return zv::Val();
		}
		return zv::Val(std::move(closures));
	}

private:
	zend_object *self;

	bool enabled() const { return Z_TYPE_P(OBJ_PROP_NUM(self, slots::enabled)) == IS_TRUE; }

	/* Mirrors the private getFrame(): the frame or null; false = pending
	 * exception */
	[[nodiscard]] bool getFrame(zval *scope, zv::Val &out) const
	{
		if (!enabled()) {
			out = zv::Val::null();
			return true;
		}

		out = pt_mutating_scope_get_current_template_argument_frame(Z_OBJ_P(scope));
		if (UNEXPECTED(out.isUndef())) return false;
		if (out.isNull()) return true;
		bool observing;
		if (UNEXPECTED(!pt_template_argument_frame_is_observing_closures(out.raw(), observing))) return false;
		if (!observing) return true;
		// the body is scanned only once one of its closures asks
		zv::Val body = pt_template_argument_frame_get_closure_signature_body(out.raw());
		if (UNEXPECTED(body.isUndef())) return false;
		if (body.isNull()) {
			out = zv::Val::null();
			return true;
		}
		zv::Val stmts = pt_template_argument_frame_get_closure_signature_stmts(out.raw());
		if (UNEXPECTED(stmts.isUndef())) return false;
		bool closed;
		if (UNEXPECTED(!isClosedBody(body.raw(), stmts.raw(), closed))) return false;
		if (!closed) out = zv::Val::null();
		return true;
	}

	/* Mirrors the private createParameterMarker() */
	static zv::Val createParameterMarker(zval *expr, zval *parameter, zend_string *parameterName)
	{
		zv::Str templateName = parameterTemplateName(parameterName);
		zv::Val templateType = anonymousTemplate(templateName.get(), PT_TEMPLATE_TYPE_VARIANCE_CONTRAVARIANT);
		if (UNEXPECTED(templateType.isUndef())) return zv::Val();
		zv::Val declaredType = pt_parameter_reflection_call(parameter, PT_PR_GET_TYPE);
		if (UNEXPECTED(declaredType.isUndef())) return zv::Val();
		zval marker;
		if (UNEXPECTED(!pt_unresolved_template_argument_type_new(&marker, expr, templateType.raw(), declaredType.raw()))) return zv::Val();
		return zv::Val::adopt(marker);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClosureSignatureInference;

/* {{{ direct entries (support.h): the native bodies for the native service,
 * the methods otherwise */

namespace {

inline bool isNative(zval *inference)
{
	return EXPECTED(Z_OBJCE_P(inference) == pt_ce_closure_signature_inference);
}

} // namespace

zv::Val pt_closure_signature_inference_collect_sites(zval *inference, zval *scope, zval *closureType)
{
	if (isNative(inference)) return ClosureSignatureInference(Z_OBJ_P(inference)).collectSites(scope, closureType);
	zv::Args argv{scope, closureType};
	return pt_type_call(Z_OBJ_P(inference), PT_LC("collectsites"), 2, argv);
}

zv::Val pt_closure_signature_inference_get_signature_parameters(zval *inference, zval *scope, zval *expr, zval *declaredParameters)
{
	if (isNative(inference)) return ClosureSignatureInference(Z_OBJ_P(inference)).getSignatureParameters(scope, expr, declaredParameters);
	zv::Args argv{scope, expr, declaredParameters};
	return pt_type_call(Z_OBJ_P(inference), PT_LC("getsignatureparameters"), 3, argv);
}

zv::Val pt_closure_signature_inference_get_signature_return_type(zval *inference, zval *scope, zval *expr, zval *returnType)
{
	if (isNative(inference)) return ClosureSignatureInference(Z_OBJ_P(inference)).getSignatureReturnType(scope, expr, returnType);
	zv::Args argv{scope, expr, returnType};
	return pt_type_call(Z_OBJ_P(inference), PT_LC("getsignaturereturntype"), 3, argv);
}

zv::Val pt_closure_signature_inference_get_body_parameters(zval *inference, zval *scope, zval *expr)
{
	if (isNative(inference)) return ClosureSignatureInference(Z_OBJ_P(inference)).getBodyParameters(scope, expr);
	zv::Args argv{scope, expr};
	return pt_type_call(Z_OBJ_P(inference), PT_LC("getbodyparameters"), 2, argv);
}

zv::Val pt_closure_signature_inference_get_expected_return_type(zval *inference, zval *scope, zval *expr)
{
	if (isNative(inference)) return ClosureSignatureInference(Z_OBJ_P(inference)).getExpectedReturnType(scope, expr);
	zv::Args argv{scope, expr};
	return pt_type_call(Z_OBJ_P(inference), PT_LC("getexpectedreturntype"), 2, argv);
}

bool pt_closure_signature_inference_is_observing(zval *inference, zval *scope, bool &out)
{
	if (isNative(inference)) return ClosureSignatureInference(Z_OBJ_P(inference)).isObserving(scope, out);
	zv::Val result = pt_type_call(Z_OBJ_P(inference), PT_LC("isobserving"), 1, scope);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

bool pt_closure_signature_inference_infers_invocation_return_type(zval *inference, zval *scope, zval *closureType, bool &out)
{
	if (isNative(inference)) return ClosureSignatureInference(Z_OBJ_P(inference)).infersInvocationReturnType(scope, closureType, out);
	zv::Args argv{scope, closureType};
	zv::Val result = pt_type_call(Z_OBJ_P(inference), PT_LC("infersinvocationreturntype"), 2, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

zv::Val pt_closure_signature_inference_find_assigned_closures(zval *inference, zval *scope, zend_string *name)
{
	if (isNative(inference)) return ClosureSignatureInference(Z_OBJ_P(inference)).findAssignedClosures(scope, name);
	zval nameZv;
	ZVAL_STR_COPY(&nameZv, name);
	zv::Args argv{scope, &nameZv};
	zv::Val result = pt_type_call(Z_OBJ_P(inference), PT_LC("findassignedclosures"), 2, argv);
	zval_ptr_dtor(&nameZv);
	return result;
}

bool pt_closure_signature_inference_is_closed_body(zval *inference, zval *functionLike, zval *stmts, bool &out)
{
	if (isNative(inference)) return ClosureSignatureInference(Z_OBJ_P(inference)).isClosedBody(functionLike, stmts, out);
	zv::Args argv{functionLike, stmts};
	zv::Val result = pt_type_call(Z_OBJ_P(inference), PT_LC("isclosedbody"), 2, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

bool pt_closure_signature_inference_is_by_ref_marker(zval *marker, bool &out)
{
	return ClosureSignatureInference::isByRefMarker(marker, out);
}

zv::Val pt_closure_signature_inference_get_by_ref_use_markers(zval *inference, zval *scope, zval *expr)
{
	if (isNative(inference)) return ClosureSignatureInference(Z_OBJ_P(inference)).getByRefUseMarkers(scope, expr);
	zv::Args argv{scope, expr};
	return pt_type_call(Z_OBJ_P(inference), PT_LC("getbyrefusemarkers"), 2, argv);
}

zv::Val pt_closure_signature_inference_get_by_ref_site_mode(zval *inference, zval *scope, zval *expr)
{
	if (isNative(inference)) return ClosureSignatureInference(Z_OBJ_P(inference)).getByRefSiteMode(scope, expr);
	zv::Args argv{scope, expr};
	return pt_type_call(Z_OBJ_P(inference), PT_LC("getbyrefsitemode"), 2, argv);
}

zv::Val pt_closure_signature_inference_get_by_ref_seed(zval *inference, zval *scope, zval *expr, zend_string *name)
{
	if (isNative(inference)) return ClosureSignatureInference(Z_OBJ_P(inference)).getByRefSeed(scope, expr, name);
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	zv::Args argv{scope, expr, &nameZv};
	return pt_type_call(Z_OBJ_P(inference), PT_LC("getbyrefseed"), 3, argv);
}

zv::Val pt_closure_signature_inference_find_creation_scope(zval *scope, zval *storage, zval *expr)
{
	return ClosureSignatureInference::findCreationScope(scope, storage, expr);
}

zv::Val pt_closure_signature_inference_collect_capture_escapes(zval *type)
{
	return ClosureSignatureInference::collectCaptureEscapes(type);
}

zv::Val pt_closure_signature_inference_collect_escapes(zval *type)
{
	return ClosureSignatureInference::collectEscapes(type);
}

zv::Val pt_closure_signature_inference_collect_invoked_callee(zval *calleeType)
{
	return ClosureSignatureInference::collectInvokedCallee(calleeType);
}

zv::Val pt_closure_signature_inference_collect_absorbed(zval *input, zval *result)
{
	return ClosureSignatureInference::collectAbsorbed(input, result);
}

zv::Val pt_closure_signature_inference_collect_absorbed_in_union(zval *types)
{
	return ClosureSignatureInference::collectAbsorbedInUnion(types);
}

bool pt_closure_signature_inference_has_markers(zval *type, bool &out)
{
	return ClosureSignatureInference::hasMarkers(type, out);
}

zv::Val pt_closure_signature_inference_add_absorbed_in_union(zval *scope, zval *types)
{
	zv::Val absorbed = ClosureSignatureInference::collectAbsorbedInUnion(types);
	if (UNEXPECTED(absorbed.isUndef())) return zv::Val();
	return pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(scope), absorbed.raw());
}

zv::Val pt_closure_signature_inference_add_absorbed_in_union_of_two(zval *scope, zval *first, zval *second)
{
	zv::Arr list = zv::Arr::create(2);
	list.push(zv::Val::copyOf(zv::Ref(first)));
	list.push(zv::Val::copyOf(zv::Ref(second)));
	zv::Val listHold(std::move(list));
	return pt_closure_signature_inference_add_absorbed_in_union(scope, listHold.raw());
}

zv::Val pt_closure_signature_inference_collect_invocation(zval *scope, zval *call, zval *closureType, bool observing)
{
	return ClosureSignatureInference::collectInvocation(scope, call, closureType, observing);
}

zv::Val pt_closure_signature_inference_collect_by_ref_entry_types(zval *scope)
{
	return ClosureSignatureInference::collectByRefEntryTypes(scope);
}

zv::Val pt_closure_signature_inference_get_arrow_function_outer_variables(zval *expr)
{
	return ClosureSignatureInference::getArrowFunctionOuterVariables(expr);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_closure_signature_inference)
{
	pt_csi_return_template_name = zend_string_init_interned(PT_LC("@return"), 1);
	pt_csi_closed_body_attribute = zend_string_init_interned(PT_LC("closureSignatureClosedBody"), 1);
	pt_csi_returns_context_typed_attribute = zend_string_init_interned(PT_LC("closureSignatureReturnsContextTyped"), 1);
	pt_csi_assigned_closures_attribute = zend_string_init_interned(PT_LC("closureSignatureAssignedClosures"), 1);
	pt_csi_invocation_template_name = zend_string_init_interned(PT_LC("@invokes"), 1);
	pt_csi_by_ref_uses_attribute = zend_string_init_interned(PT_LC("closureSignatureByRefUses"), 1);
	pt_csi_arrow_function_outer_variables_attribute = zend_string_init_interned(PT_LC("closureSignatureArrowFunctionOuterVariables"), 1);

	reg::Class cls("PHPStan\\Analyser\\Generics\\ClosureSignatureInference");
	ptdecl::ClosureSignatureInference::declareClass(cls);
	ptdecl::ClosureSignatureInference::declareProperties(cls);
	cls.publicClassConstantString("RETURN_TEMPLATE_NAME", "@return");
	cls.privateClassConstantString("BY_REF_TEMPLATE_PREFIX", "&");
	cls.privateClassConstantString("ENTRY_TEMPLATE_PREFIX", "~");
	cls.privateClassConstantString("INVOCATION_TEMPLATE_NAME", "@invokes");
	cls.privateClassConstantString("BY_REF_USES_ATTRIBUTE", "closureSignatureByRefUses");
	cls.privateClassConstantString("ARROW_FUNCTION_OUTER_VARIABLES_ATTRIBUTE", "closureSignatureArrowFunctionOuterVariables");
	cls.privateClassConstantString("CLOSED_BODY_ATTRIBUTE", "closureSignatureClosedBody");
	cls.privateClassConstantString("ASSIGNED_CLOSURES_ATTRIBUTE", "closureSignatureAssignedClosures");

	/* the real parameter types: the DI container autowires the service by
	 * reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool enabled;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_BOOL(enabled)
		ZEND_PARSE_PARAMETERS_END();
		ClosureSignatureInference(Z_OBJ_P(ZEND_THIS)).construct(enabled);
	});

	cls.method(sigs::isClosureSignatureMarker, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *marker;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(marker, pt_ce_unresolved_template_argument_type)
		ZEND_PARSE_PARAMETERS_END();
		bool out;
		if (UNEXPECTED(!ClosureSignatureInference::isClosureSignatureMarker(marker, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::isReturnMarker, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *marker;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(marker, pt_ce_unresolved_template_argument_type)
		ZEND_PARSE_PARAMETERS_END();
		bool out;
		if (UNEXPECTED(!ClosureSignatureInference::isReturnMarker(marker, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::isByRefMarker, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *marker;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(marker, pt_ce_unresolved_template_argument_type)
		ZEND_PARSE_PARAMETERS_END();
		bool out;
		if (UNEXPECTED(!ClosureSignatureInference::isByRefMarker(marker, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::getByRefUseMarkers, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, scope, expr)) RETURN_THROWS();
		PT_RETURN_VAL(ClosureSignatureInference(Z_OBJ_P(ZEND_THIS)).getByRefUseMarkers(scope, expr));
	});

	cls.method(sigs::getByRefSiteMode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, scope, expr)) RETURN_THROWS();
		PT_RETURN_VAL(ClosureSignatureInference(Z_OBJ_P(ZEND_THIS)).getByRefSiteMode(scope, expr));
	});

	cls.method(sigs::getByRefSeed, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr;
		zend_string *name;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Str>(execute_data, scope, expr, name)) RETURN_THROWS();
		PT_RETURN_VAL(ClosureSignatureInference(Z_OBJ_P(ZEND_THIS)).getByRefSeed(scope, expr, name));
	});

	cls.method(sigs::findCreationScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *storage, *expr;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, scope, storage, expr)) RETURN_THROWS();
		PT_RETURN_VAL(ClosureSignatureInference::findCreationScope(scope, storage, expr));
	});

	cls.method(sigs::collectCaptureEscapes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_VAL(ClosureSignatureInference::collectCaptureEscapes(type));
	});

	cls.method(sigs::collectEscapes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_VAL(ClosureSignatureInference::collectEscapes(type));
	});

	cls.method(sigs::collectInvokedCallee, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *calleeType;
		if (!zp::parse<zp::Obj>(execute_data, calleeType)) RETURN_THROWS();
		PT_RETURN_VAL(ClosureSignatureInference::collectInvokedCallee(calleeType));
	});

	cls.method(sigs::collectAbsorbed, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *input, *result;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, input, result)) RETURN_THROWS();
		PT_RETURN_VAL(ClosureSignatureInference::collectAbsorbed(input, result));
	});

	cls.method(sigs::collectAbsorbedInUnion, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ARRAY(types)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ClosureSignatureInference::collectAbsorbedInUnion(types));
	});

	cls.method(sigs::hasMarkers, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!ClosureSignatureInference::hasMarkers(type, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::collectInvocation, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *call, *closureType;
		bool observing;
		ZEND_PARSE_PARAMETERS_START(4, 4)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(call)
			Z_PARAM_OBJECT(closureType)
			Z_PARAM_BOOL(observing)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ClosureSignatureInference::collectInvocation(scope, call, closureType, observing));
	});

	cls.method(sigs::collectByRefEntryTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		if (!zp::parse<zp::Obj>(execute_data, scope)) RETURN_THROWS();
		PT_RETURN_VAL(ClosureSignatureInference::collectByRefEntryTypes(scope));
	});

	cls.method(sigs::getArrowFunctionOuterVariables, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		PT_RETURN_VAL(ClosureSignatureInference::getArrowFunctionOuterVariables(expr));
	});

	cls.method(sigs::isObserving, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		if (!zp::parse<zp::Obj>(execute_data, scope)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!ClosureSignatureInference(Z_OBJ_P(ZEND_THIS)).isObserving(scope, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::getSignatureParameters, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *declaredParameters;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_ARRAY(declaredParameters)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ClosureSignatureInference(Z_OBJ_P(ZEND_THIS)).getSignatureParameters(scope, expr, declaredParameters));
	});

	cls.method(sigs::getBodyParameters, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, scope, expr)) RETURN_THROWS();
		PT_RETURN_VAL(ClosureSignatureInference(Z_OBJ_P(ZEND_THIS)).getBodyParameters(scope, expr));
	});

	cls.method(sigs::getSignatureReturnType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *returnType;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, scope, expr, returnType)) RETURN_THROWS();
		PT_RETURN_VAL(ClosureSignatureInference(Z_OBJ_P(ZEND_THIS)).getSignatureReturnType(scope, expr, returnType));
	});

	cls.method(sigs::getExpectedReturnType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, scope, expr)) RETURN_THROWS();
		PT_RETURN_VAL(ClosureSignatureInference(Z_OBJ_P(ZEND_THIS)).getExpectedReturnType(scope, expr));
	});

	cls.method(sigs::collectSites, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *closureType;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, scope, closureType)) RETURN_THROWS();
		PT_RETURN_VAL(ClosureSignatureInference(Z_OBJ_P(ZEND_THIS)).collectSites(scope, closureType));
	});

	cls.method(sigs::infersInvocationReturnType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *closureType;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, scope, closureType)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!ClosureSignatureInference(Z_OBJ_P(ZEND_THIS)).infersInvocationReturnType(scope, closureType, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::findAssignedClosures, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		zend_string *name;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_STR(name)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ClosureSignatureInference(Z_OBJ_P(ZEND_THIS)).findAssignedClosures(scope, name));
	});

	cls.method(sigs::isClosedBody, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *functionLike, *stmts;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(functionLike)
			Z_PARAM_ARRAY(stmts)
		ZEND_PARSE_PARAMETERS_END();
		bool out;
		if (UNEXPECTED(!ClosureSignatureInference(Z_OBJ_P(ZEND_THIS)).isClosedBody(functionLike, stmts, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.shadow(&pt_ce_closure_signature_inference);
}

/* }}} */
