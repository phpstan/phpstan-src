/*
 * The hand-written part of the native C API of compiled classes: entries
 * other files call whose shape the compiler does not generate (see
 * turbo-ext/compiled-classes.php). Each enters the compiled body for an
 * instance of the compiled class and calls the method by name for anything
 * else (a PHP twin in the differential tests), as the hand-written ports did.
 */

#include "support.h"
#include "zv.h"
#include "CodegenDecls.h"
#include "TypeTraits.h"

/* the adapters: a compiled method's bool + out-parameter convention as the
 * zv::Val (UNDEF = pending exception) the callers expect */
template <typename F>
static zend_always_inline zv::Val pt_compiled_val(F &&call)
{
	zv::Val result;
	if (UNEXPECTED(!call(result.raw()))) return zv::Val();
	return result;
}

static ZEND_COLD zv::Val pt_compiled_non_object(zval *value, const char *method)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
	return zv::Val();
}

/* {{{ InternalEndStatementResult, InternalStatementExitPoint */

zv::Val pt_internal_end_statement_result_to_public(zval *endStatement)
{
	if (EXPECTED(Z_OBJCE_P(endStatement) == pt_ce_internal_end_statement_result)) {
		return pt_compiled_val([&](zval *ret) { return pgen::InternalEndStatementResult::m_toPublic(Z_OBJ_P(endStatement), ret); });
	}
	return pt_type_call(Z_OBJ_P(endStatement), PT_LC("topublic"), 0, NULL);
}

zv::Val pt_internal_statement_exit_point_to_public(zval *exitPoint)
{
	if (EXPECTED(Z_OBJCE_P(exitPoint) == pt_ce_internal_statement_exit_point)) {
		return pt_compiled_val([&](zval *ret) { return pgen::InternalStatementExitPoint::m_toPublic(Z_OBJ_P(exitPoint), ret); });
	}
	return pt_type_call(Z_OBJ_P(exitPoint), PT_LC("topublic"), 0, NULL);
}

/* }}} */

/* {{{ ProcessClosureResult */

zv::Val pt_process_closure_result_apply_by_ref_use_scope(zval *result, zval *scope)
{
	if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) return pt_compiled_non_object(result, "applyByRefUseScope");
	if (EXPECTED(Z_OBJCE_P(result) == pt_ce_process_closure_result && Z_TYPE_P(scope) == IS_OBJECT)) {
		return pt_compiled_val([&](zval *ret) { return pgen::ProcessClosureResult::m_applyByRefUseScope(Z_OBJ_P(result), scope, ret); });
	}
	return pt_type_call(Z_OBJ_P(result), PT_LC("applybyrefusescope"), 1, scope);
}

/* }}} */

/* {{{ ChangedTypePropertyReflection */

zv::Val pt_changed_type_property_reflection_get_hook(zend_object *property, zend_string *hookType)
{
	zval hook;
	ZVAL_STR(&hook, hookType);
	return pt_compiled_val([&](zval *ret) { return pgen::ChangedTypePropertyReflection::m_getHook(property, &hook, ret); });
}

zv::Val pt_changed_type_property_reflection_get_original_reflection(zend_object *property)
{
	return pt_compiled_val([&](zval *ret) { return pgen::ChangedTypePropertyReflection::m_getOriginalReflection(property, ret); });
}

bool pt_changed_type_property_reflection_has_hook(zend_object *property, zend_string *hookType, bool &out)
{
	zval hook;
	ZVAL_STR(&hook, hookType);
	return pgen::ChangedTypePropertyReflection::m_hasHook(property, &hook, &out);
}

/* }}} */

/* {{{ NativeParameterReflection */

zv::Val pt_native_parameter_reflection_to_optional(zval *parameter)
{
	if (UNEXPECTED(Z_TYPE_P(parameter) != IS_OBJECT)) return pt_compiled_non_object(parameter, "toOptional");
	if (EXPECTED(Z_OBJCE_P(parameter) == pt_ce_native_parameter_reflection)) {
		return pt_compiled_val([&](zval *ret) { return pgen::NativeParameterReflection::m_toOptional(Z_OBJ_P(parameter), ret); });
	}
	return pt_type_call(Z_OBJ_P(parameter), PT_LC("tooptional"), 0, NULL);
}

zv::Val pt_native_parameter_reflection_union(zval *parameter, zval *other)
{
	if (UNEXPECTED(Z_TYPE_P(parameter) != IS_OBJECT)) return pt_compiled_non_object(parameter, "union");
	if (EXPECTED(Z_OBJCE_P(parameter) == pt_ce_native_parameter_reflection && Z_TYPE_P(other) == IS_OBJECT && Z_OBJCE_P(other) == pt_ce_native_parameter_reflection)) {
		return pt_compiled_val([&](zval *ret) { return pgen::NativeParameterReflection::m_union(Z_OBJ_P(parameter), other, ret); });
	}
	return pt_type_call(Z_OBJ_P(parameter), PT_LC("union"), 1, other);
}

/* }}} */

/* {{{ TemplateTypeReference, TemplateTypeScope */

static ZEND_COLD bool pt_compiled_expected(zend_class_entry *ce, const char *fallback, zval *given)
{
	zend_type_error("phpstan_turbo: expected %s, %s given", ce != NULL ? ZSTR_VAL(ce->name) : fallback, zend_zval_value_name(given));
	return false;
}

bool pt_template_type_reference_parts(zval *reference, zv::Val &type, zv::Val &positionVariance)
{
	if (UNEXPECTED(Z_TYPE_P(reference) != IS_OBJECT)) return pt_compiled_expected(pt_ce_template_type_reference, "PHPStan\\Type\\Generic\\TemplateTypeReference", reference);
	if (EXPECTED(Z_OBJCE_P(reference) == pt_ce_template_type_reference)) {
		type = pt_compiled_val([&](zval *ret) { return pgen::TemplateTypeReference::m_getType(Z_OBJ_P(reference), ret); });
		if (UNEXPECTED(type.isUndef())) return false;
		positionVariance = pt_compiled_val([&](zval *ret) { return pgen::TemplateTypeReference::m_getPositionVariance(Z_OBJ_P(reference), ret); });
		return !positionVariance.isUndef();
	}
	type = pt_type_call(Z_OBJ_P(reference), PT_LC("gettype"), 0, NULL);
	if (UNEXPECTED(type.isUndef())) return false;
	positionVariance = pt_type_call(Z_OBJ_P(reference), PT_LC("getpositionvariance"), 0, NULL);
	return !positionVariance.isUndef();
}

bool pt_template_type_scope_equals(zval *self, zval *other, bool &out)
{
	if (EXPECTED(Z_TYPE_P(self) == IS_OBJECT && Z_OBJCE_P(self) == pt_ce_template_type_scope && Z_TYPE_P(other) == IS_OBJECT && Z_OBJCE_P(other) == pt_ce_template_type_scope)) {
		return pgen::TemplateTypeScope::m_equals(Z_OBJ_P(self), other, &out);
	}
	if (UNEXPECTED(Z_TYPE_P(self) != IS_OBJECT)) return pt_compiled_expected(pt_ce_template_type_scope, "PHPStan\\Type\\Generic\\TemplateTypeScope", self);
	return pt_type_op_bool(Z_OBJ_P(self), PT_OP_EQUALS, 1, other, out);
}

bool pt_template_type_scope_is_anonymous(zval *scope, bool &out)
{
	if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) return pt_compiled_expected(pt_ce_template_type_scope, "PHPStan\\Type\\Generic\\TemplateTypeScope", scope);
	if (EXPECTED(Z_OBJCE_P(scope) == pt_ce_template_type_scope)) {
		zv::Val className = pt_compiled_val([&](zval *ret) { return pgen::TemplateTypeScope::m_getClassName(Z_OBJ_P(scope), ret); });
		if (UNEXPECTED(className.isUndef())) return false;
		zv::Val functionName = pt_compiled_val([&](zval *ret) { return pgen::TemplateTypeScope::m_getFunctionName(Z_OBJ_P(scope), ret); });
		if (UNEXPECTED(functionName.isUndef())) return false;
		out = className.isNull() && functionName.isNull();
		return true;
	}
	/* the PHP twin declared next to the native class in the differential
	 * tests: $scope->equals(<its class>::createWithAnonymousFunction()) */
	zv::Val anonymous = pt_type_call_static_ce(Z_OBJCE_P(scope), PT_LC("createwithanonymousfunction"), 0, NULL);
	if (UNEXPECTED(anonymous.isUndef())) return false;
	return pt_template_type_scope_equals(scope, anonymous.raw(), out);
}

zv::Val pt_type_projection_helper_describe(zval *type, zval *variance, zval *level)
{
	/* the twin's `Type $type` parameter check: native callers pass the
	 * elements of a type list unchecked */
	if (UNEXPECTED(!pgrt::instanceOfClass(type, pt_class_loaded(PT_CLASS_TYPE)))) {
		zend_type_error("%s::describe(): Argument #1 ($type) must be of type %s, %s given", ZSTR_VAL(pt_ce_type_projection_helper->name), ptcls::type, zend_zval_value_name(type));
		return zv::Val();
	}
	return pt_compiled_val([&](zval *ret) { return pgen::TypeProjectionHelper::m_describe(type, variance, level, ret); });
}

/* }}} */

/* {{{ CircularTypeAliasErrorType */

bool pt_circular_type_alias_error_type_new(zval *out, zend_string *reason)
{
	if (UNEXPECTED(object_init_ex(out, pt_ce_circular_type_alias_error_type) != SUCCESS)) return false;
	/* the inherited ErrorType::__construct($reason) */
	pt_error_type_construct(Z_OBJ_P(out), reason);
	return true;
}

/* }}} */

/* {{{ ParametersProcessor */

zv::Val pt_parameters_processor_process_params(zval *processor, zval *nodeScopeResolver, zval *stmt, zval *params, zval *scope, zval *storage, zval *nodeCallback)
{
	if (EXPECTED(Z_OBJCE_P(processor) == pt_ce_parameters_processor && Z_TYPE_P(params) == IS_ARRAY)) {
		return pt_compiled_val([&](zval *ret) { return pgen::ParametersProcessor::m_processParams(Z_OBJ_P(processor), nodeScopeResolver, stmt, params, scope, storage, nodeCallback, ret); });
	}
	zv::Args argv{nodeScopeResolver, stmt, params, scope, storage, nodeCallback};
	return pt_type_call(Z_OBJ_P(processor), PT_LC("processparams"), 6, argv);
}

/* }}} */
