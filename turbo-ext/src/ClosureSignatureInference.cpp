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

	/* Mirrors isObserving(); false = pending exception */
	[[nodiscard]] bool isObserving(zval *scope, bool &out) const
	{
		zv::Val frame;
		if (UNEXPECTED(!getFrame(scope, frame))) return false;
		if (frame.isNull()) {
			out = false;
			return true;
		}
		return pt_template_argument_frame_is_observing(frame.raw(), out);
	}

	/* Mirrors getSignatureParameters() */
	zv::Val getSignatureParameters(zval *scope, zval *expr, zval *declaredParameters) const
	{
		zv::Val frame;
		if (UNEXPECTED(!getFrame(scope, frame))) return zv::Val();
		if (frame.isNull()) return zv::Val::copyOf(zv::Ref(declaredParameters));
		bool observing;
		if (UNEXPECTED(!pt_template_argument_frame_is_observing(frame.raw(), observing))) return zv::Val();

		zv::Arr parameters = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(declaredParameters)));
		for (auto entry : zv::ArrRef(declaredParameters)) {
			zval *parameter = entry.value().deref().raw();
			zv::Val passedByReference = ptclosure::parameterPassedByReference(parameter);
			if (UNEXPECTED(passedByReference.isUndef())) return zv::Val();
			zend_long mode = pt_passed_by_reference_mode(passedByReference.raw());
			if (UNEXPECTED(mode < 0)) return zv::Val();
			if (mode != PT_PASSED_BY_REFERENCE_NO) {
				parameters.push(zv::Val::copyOf(zv::Ref(parameter)));
				continue;
			}
			zv::Val name = pt_parameter_reflection_call(parameter, PT_PR_GET_NAME);
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			zv::Val type;
			if (observing) {
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
		if (UNEXPECTED(!pt_template_argument_frame_is_observing(frame.raw(), observing))) return zv::Val();
		if (observing) return zv::Val::null();

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
		if (UNEXPECTED(!pt_template_argument_frame_is_observing(frame.raw(), observing))) return zv::Val();
		if (!observing) return zv::Val::copyOf(zv::Ref(returnType));
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
		if (UNEXPECTED(!pt_template_argument_frame_is_observing(frame.raw(), observing))) return zv::Val();
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
		if (UNEXPECTED(!pt_template_argument_frame_is_observing(frame.raw(), observing))) return zv::Val();
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
		if (UNEXPECTED(!pt_template_argument_frame_is_observing(out.raw(), observing))) return false;
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

bool pt_closure_signature_inference_is_closed_body(zval *inference, zval *functionLike, zval *stmts, bool &out)
{
	if (isNative(inference)) return ClosureSignatureInference(Z_OBJ_P(inference)).isClosedBody(functionLike, stmts, out);
	zv::Args argv{functionLike, stmts};
	zv::Val result = pt_type_call(Z_OBJ_P(inference), PT_LC("isclosedbody"), 2, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_closure_signature_inference)
{
	pt_csi_return_template_name = zend_string_init_interned(PT_LC("@return"), 1);
	pt_csi_closed_body_attribute = zend_string_init_interned(PT_LC("closureSignatureClosedBody"), 1);
	pt_csi_returns_context_typed_attribute = zend_string_init_interned(PT_LC("closureSignatureReturnsContextTyped"), 1);

	reg::Class cls("PHPStan\\Analyser\\Generics\\ClosureSignatureInference");
	ptdecl::ClosureSignatureInference::declareClass(cls);
	ptdecl::ClosureSignatureInference::declareProperties(cls);
	cls.publicClassConstantString("RETURN_TEMPLATE_NAME", "@return");
	cls.privateClassConstantString("CLOSED_BODY_ATTRIBUTE", "closureSignatureClosedBody");

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
