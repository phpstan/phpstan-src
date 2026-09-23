/*
 * PHPStanTurbo\IssetabilityDescriptor — native implementation of
 * PHPStan\Analyser\IssetabilityDescriptor.
 *
 * The inside-out carrier of an isset/empty/?? chain link: VariableHandler
 * creates one per variable read (~150K per self-analysis), the fetch
 * handlers per offset / property fetch. The twin's constructor is private;
 * instances come from variable() / offset() / property(), natively through
 * pt_issetability_descriptor_variable() / _offset() / _property(). State
 * lives in the twin's seven promoted property slots, in its order; the kind
 * is one of three permanent interned strings.
 *
 * resolve() asks the scope through MutatingScope's direct entries, the
 * chain links' results through ExpressionResult's, and the Type queries
 * through the Type ops, the link and resolution value objects through their
 * native factories; the property reflection resolver and the property
 * reflections stay PHP.
 */

#include "support.h"
#include "generated/IssetabilityDescriptor.h"

namespace slots = ptdecl::IssetabilityDescriptor::slot;
namespace sigs = ptdecl::IssetabilityDescriptor::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "AnalyserValues.h"
#include "Engine.h"

#pragma GCC diagnostic push
#pragma GCC diagnostic ignored "-Wpragmas"
#pragma GCC diagnostic ignored "-Wunknown-warning-option"
#pragma GCC diagnostic ignored "-Wunused-parameter"
#pragma GCC diagnostic ignored "-Wignored-qualifiers"
#pragma GCC diagnostic ignored "-Wdeprecated-declarations"
#pragma GCC diagnostic ignored "-Wattributes"
#include "zend_closures.h" /* zend_ce_closure */
#pragma GCC diagnostic pop

zend_class_entry *pt_ce_issetability_descriptor = nullptr;

namespace {

/* the twin's KIND_* constants, permanent interned strings */
zend_string *pt_id_kind_variable = nullptr;
zend_string *pt_id_kind_offset = nullptr;
zend_string *pt_id_kind_property = nullptr;

/* $trinary->yes() / ->no() of a TrinaryLogic result; -1 = pending exception */
[[nodiscard]] zend_long trinaryOf(zv::Val &trinary)
{
	if (UNEXPECTED(trinary.isUndef())) return -1;
	return pt_type_trinary_value(trinary.raw());
}

/* new NeverType(); UNDEF = pending exception */
zv::Val newNeverType()
{
	zval type;
	if (UNEXPECTED(!pt_never_type_new(&type))) return zv::Val();
	return zv::Val::adopt(type);
}

/* $object->method() coerced to bool, as `&&` reads it; false = pending
 * exception */
[[nodiscard]] bool callTruthy(zend_object *object, const char *lcname, size_t len, bool &out)
{
	zv::Val result = pt_type_call(object, lcname, len, 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\IssetabilityDescriptor. */
class IssetabilityDescriptor
{
public:
	explicit IssetabilityDescriptor(zend_object *self) : self(self) {}

	/* the private constructor's body; every argument NULL for null */
	void construct(zend_string *kind, zval *variableName, zval *varResult, zval *dimResult, zval *innerResult, zval *reflectionResolver, zval *propertyFetch) const
	{
		zval value;
		ZVAL_STR(&value, kind);
		pt_write_slot(self, slots::kind, &value);
		writeNullable(slots::variableName, variableName);
		writeNullable(slots::varResult, varResult);
		writeNullable(slots::dimResult, dimResult);
		writeNullable(slots::innerResult, innerResult);
		writeNullable(slots::reflectionResolver, reflectionResolver);
		writeNullable(slots::propertyFetch, propertyFetch);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val newSelf(zend_string *kind, zval *variableName, zval *varResult, zval *dimResult, zval *innerResult, zval *reflectionResolver, zval *propertyFetch)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_issetability_descriptor) != SUCCESS)) return zv::Val();
		IssetabilityDescriptor(Z_OBJ(object)).construct(kind, variableName, varResult, dimResult, innerResult, reflectionResolver, propertyFetch);
		return zv::Val::adopt(object);
	}

	static zv::Val variable(zend_string *variableName)
	{
		zval name;
		ZVAL_STR(&name, variableName);
		return newSelf(pt_id_kind_variable, &name, NULL, NULL, NULL, NULL, NULL);
	}

	static zv::Val offset(zval *varResult, zval *dimResult)
	{
		return newSelf(pt_id_kind_offset, NULL, varResult, dimResult, NULL, NULL, NULL);
	}

	/* $innerResult NULL for null */
	static zv::Val property(zval *innerResult, zval *reflectionResolver, zval *propertyFetch)
	{
		return newSelf(pt_id_kind_property, NULL, NULL, NULL, innerResult, reflectionResolver, propertyFetch);
	}

	/* Mirrors resolve(). */
	zv::Val resolve(zval *scope, bool useNativeTypes, zval *expr, bool reprocessUntrackedLinks) const
	{
		zval *kind = pt_typed_slot(self, slots::kind, self->ce, "kind");
		if (UNEXPECTED(kind == NULL)) return zv::Val();
		if (zend_string_equals(Z_STR_P(kind), pt_id_kind_variable)) return resolveVariable(scope, useNativeTypes);
		if (zend_string_equals(Z_STR_P(kind), pt_id_kind_offset)) return resolveOffset(scope, useNativeTypes, expr, reprocessUntrackedLinks);
		return resolveProperty(scope, useNativeTypes, reprocessUntrackedLinks);
	}

private:
	zend_object *self;

	void writeNullable(uint32_t index, zval *value) const
	{
		if (value != NULL) {
			pt_write_slot(self, index, value);
			return;
		}
		zval null = {};
		ZVAL_NULL(&null);
		pt_write_slot(self, index, &null);
	}

	/* $result->getIssetabilityResolution(...) of the inner link's result: the
	 * native recursion down the chain continues on a fresh C stack segment
	 * when the current one runs low (the twin recursed on the VM stack) */
	static zv::Val innerResolution(zval *result, zval *scope, bool useNativeTypes, bool reprocessUntrackedLinks)
	{
		zv::Val inner;
		pt_engine_with_stack([&]() { inner = pt_expression_result_get_issetability_resolution(result, scope, useNativeTypes, reprocessUntrackedLinks); });
		return inner;
	}

	/* a nullable slot, borrowed; NULL with the uninitialized-read Error pending */
	zval *slot(uint32_t index, const char *name) const
	{
		return pt_typed_slot(self, index, self->ce, name);
	}

	/* new IssetabilityResolution($link, $inner) — both consumed ($inner a PHP
	 * null for null); UNDEF = pending exception */
	static zv::Val newResolution(zv::Val link, zv::Val inner)
	{
		if (UNEXPECTED(link.isUndef() || inner.isUndef())) return zv::Val();
		return pt_issetability_resolution_new(link.raw(), inner.raw());
	}

	zv::Val resolveVariable(zval *scope, bool useNativeTypes) const
	{
		zval *variableName = slot(slots::variableName, "variableName");
		if (UNEXPECTED(variableName == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(variableName) == IS_NULL)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		zv::Val hasVariable = pt_mutating_scope_has_variable_type(Z_OBJ_P(scope), Z_STR_P(variableName));
		zend_long has = trinaryOf(hasVariable);
		if (UNEXPECTED(has < 0)) return zv::Val();
		zv::Val valueType;
		if (has == PT_TRI_YES) {
			if (useNativeTypes) {
				zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope));
				if (UNEXPECTED(nativeScope.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(nativeScope.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function getVariableType() on %s", zend_zval_value_name(nativeScope.raw()));
					return zv::Val();
				}
				valueType = pt_mutating_scope_get_variable_type(Z_OBJ_P(nativeScope.raw()), Z_STR_P(variableName));
			} else {
				valueType = pt_mutating_scope_get_variable_type(Z_OBJ_P(scope), Z_STR_P(variableName));
			}
		} else {
			valueType = newNeverType();
		}
		if (UNEXPECTED(valueType.isUndef())) return zv::Val();

		if (UNEXPECTED(hasVariable.isUndef())) return zv::Val();
		zv::Val link = pt_issetability_link_info_variable(Z_STR_P(variableName), hasVariable.raw(), valueType.raw());
		return newResolution(std::move(link), zv::Val::null());
	}

	zv::Val resolveOffset(zval *scope, bool useNativeTypes, zval *expr, bool reprocessUntrackedLinks) const
	{
		zval *varResult = slot(slots::varResult, "varResult");
		zval *dimResult = varResult != NULL ? slot(slots::dimResult, "dimResult") : NULL;
		if (UNEXPECTED(dimResult == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(varResult) == IS_NULL || Z_TYPE_P(dimResult) == IS_NULL)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		bool reprocessVar = false;
		if (reprocessUntrackedLinks) {
			zv::Val varExprHold;
			zval *varExpr = pt_expression_result_expr(varResult, varExprHold);
			if (UNEXPECTED(varExpr == NULL)) return zv::Val();
			zend_long isTracked = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope), varExpr);
			if (UNEXPECTED(isTracked < 0)) return zv::Val();
			reprocessVar = isTracked != PT_TRI_YES;
		}
		zv::Val varType;
		if (reprocessVar) {
			zv::Val varExprHold;
			zval *varExpr = pt_expression_result_expr(varResult, varExprHold);
			if (UNEXPECTED(varExpr == NULL)) return zv::Val();
			if (useNativeTypes) {
				zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope));
				if (UNEXPECTED(nativeScope.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(nativeScope.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function getNativeType() on %s", zend_zval_value_name(nativeScope.raw()));
					return zv::Val();
				}
				varType = pt_mutating_scope_get_native_type(Z_OBJ_P(nativeScope.raw()), varExpr);
			} else {
				varType = pt_mutating_scope_get_type(Z_OBJ_P(scope), varExpr);
			}
		} else {
			varType = pt_expression_result_get_type_on_scope(varResult, scope, useNativeTypes);
		}
		if (UNEXPECTED(varType.isUndef())) return zv::Val();
		zv::Val dimType = pt_expression_result_get_type_on_scope(dimResult, scope, useNativeTypes);
		if (UNEXPECTED(dimType.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(varType.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function hasOffsetValueType() on %s", zend_zval_value_name(varType.raw()));
			return zv::Val();
		}
		zend_object *varTypeObject = Z_OBJ_P(varType.raw());
		zv::Val hasOffsetValue = pt_type_op(varTypeObject, PT_OP_HAS_OFFSET_VALUE_TYPE, 1, dimType.raw());
		zend_long hasOffset = trinaryOf(hasOffsetValue);
		if (UNEXPECTED(hasOffset < 0)) return zv::Val();
		zv::Val valueType = hasOffset == PT_TRI_NO ? newNeverType() : pt_type_op(varTypeObject, PT_OP_GET_OFFSET_VALUE_TYPE, 1, dimType.raw());
		if (UNEXPECTED(valueType.isUndef())) return zv::Val();

		zv::Val isOffsetAccessible = pt_type_call(varTypeObject, PT_LC("isoffsetaccessible"), 0, NULL);
		if (UNEXPECTED(isOffsetAccessible.isUndef())) return zv::Val();
		zend_long isExprTracked = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope), expr);
		if (UNEXPECTED(isExprTracked < 0)) return zv::Val();
		zv::Val link = pt_issetability_link_info_offset(isOffsetAccessible.raw(), hasOffsetValue.raw(), isExprTracked == PT_TRI_YES, varType.raw(), dimType.raw(), valueType.raw());
		if (UNEXPECTED(link.isUndef())) return zv::Val();
		zv::Val inner = innerResolution(varResult, scope, useNativeTypes, reprocessUntrackedLinks);
		return newResolution(std::move(link), std::move(inner));
	}

	zv::Val resolveProperty(zval *scope, bool useNativeTypes, bool reprocessUntrackedLinks) const
	{
		zval *reflectionResolver = slot(slots::reflectionResolver, "reflectionResolver");
		zval *propertyFetch = reflectionResolver != NULL ? slot(slots::propertyFetch, "propertyFetch") : NULL;
		if (UNEXPECTED(propertyFetch == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(reflectionResolver) == IS_NULL || Z_TYPE_P(propertyFetch) == IS_NULL)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		zval *innerResult = slot(slots::innerResult, "innerResult");
		if (UNEXPECTED(innerResult == NULL)) return zv::Val();
		zv::Val inner = Z_TYPE_P(innerResult) != IS_NULL
			? innerResolution(innerResult, scope, useNativeTypes, reprocessUntrackedLinks)
			: zv::Val::null();
		if (UNEXPECTED(inner.isUndef())) return zv::Val();

		/* $reflectionResolver($scope); the slot re-read: the resolver may be
		 * the only reference keeping itself alive */
		zval resolver;
		ZVAL_COPY(&resolver, reflectionResolver);
		zv::Val resolverHolder = zv::Val::adopt(resolver);
		zv::Val propertyReflection = pt_type_call_callable(resolverHolder.raw(), 1, scope);
		if (UNEXPECTED(propertyReflection.isUndef())) return zv::Val();
		if (Z_TYPE_P(propertyReflection.raw()) == IS_NULL) {
			zv::Val never = newNeverType();
			if (UNEXPECTED(never.isUndef())) return zv::Val();
			zv::Val nativeNever = newNeverType();
			if (UNEXPECTED(nativeNever.isUndef())) return zv::Val();
			zv::Val link = pt_issetability_link_info_property(NULL, propertyFetch, false, false, pt_trinary_singleton(PT_TRI_NO), never.raw(), nativeNever.raw(), false, false, false, false, false, false, false);
			return newResolution(std::move(link), std::move(inner));
		}
		if (UNEXPECTED(Z_TYPE_P(propertyReflection.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function hasNativeType() on %s", zend_zval_value_name(propertyReflection.raw()));
			return zv::Val();
		}
		zend_object *reflection = Z_OBJ_P(propertyReflection.raw());

		bool hasNativeType;
		if (UNEXPECTED(!callTruthy(reflection, PT_LC("hasnativetype"), hasNativeType))) return zv::Val();
		zv::Val nativeReflection = pt_type_call(reflection, PT_LC("getnativereflection"), 0, NULL);
		if (UNEXPECTED(nativeReflection.isUndef())) return zv::Val();

		bool initializedThisProperty = false;
		zend_class_entry *propertyFetchCe = pt_class(PT_CLASS_PROPERTY_FETCH);
		zend_class_entry *identifierCe = pt_class(PT_CLASS_IDENTIFIER);
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		if (UNEXPECTED(propertyFetchCe == NULL || identifierCe == NULL || variableCe == NULL)) return zv::Val();
		zend_object *fetch = Z_OBJ_P(propertyFetch);
		if (instanceof_function(fetch->ce, propertyFetchCe)) {
			zv::Ref name = zv::ObjRef(fetch).prop(PT_LC("name"));
			zv::Ref var = zv::ObjRef(fetch).prop(PT_LC("var"));
			if (name.raw() != NULL && name.deref().instanceOf(identifierCe) && var.raw() != NULL && var.deref().instanceOf(variableCe)) {
				zv::Ref varName = zv::ObjRef(var.deref().asObject()).prop(PT_LC("name"));
				if (varName.raw() != NULL && varName.deref().stringEquals("this")) {
					zv::Val propertyName = pt_type_call(reflection, PT_LC("getname"), 0, NULL);
					if (UNEXPECTED(propertyName.isUndef())) return zv::Val();
					zv::Val initializationExpr = pt_type_new(PT_CLASS_PROPERTY_INITIALIZATION_EXPR, 1, propertyName.raw());
					if (UNEXPECTED(initializationExpr.isUndef())) return zv::Val();
					zend_long isTracked = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope), initializationExpr.raw());
					if (UNEXPECTED(isTracked < 0)) return zv::Val();
					initializedThisProperty = isTracked == PT_TRI_YES;
				}
			}
		}

		zv::Val reflectionNative = pt_type_call(reflection, PT_LC("isnative"), 0, NULL);
		if (UNEXPECTED(reflectionNative.isUndef())) return zv::Val();
		zv::Val isVirtual = pt_type_call(reflection, PT_LC("isvirtual"), 0, NULL);
		if (UNEXPECTED(isVirtual.isUndef())) return zv::Val();
		zv::Val writableType = pt_type_call(reflection, PT_LC("getwritabletype"), 0, NULL);
		if (UNEXPECTED(writableType.isUndef())) return zv::Val();
		zv::Val nativeType = hasNativeType ? pt_type_call(reflection, PT_LC("getnativetype"), 0, NULL) : newNeverType();
		if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
		zend_long isFetchTracked = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope), propertyFetch);
		if (UNEXPECTED(isFetchTracked < 0)) return zv::Val();
		bool nativeReflectionExists = Z_TYPE_P(nativeReflection.raw()) != IS_NULL;
		bool nativeIsPromoted = false, nativeIsReadOnly = false, nativeIsHooked = false, nativeHasDefaultValue = false;
		if (nativeReflectionExists) {
			if (UNEXPECTED(Z_TYPE_P(nativeReflection.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function isPromoted() on %s", zend_zval_value_name(nativeReflection.raw()));
				return zv::Val();
			}
			zend_object *native = Z_OBJ_P(nativeReflection.raw());
			if (UNEXPECTED(!callTruthy(native, PT_LC("ispromoted"), nativeIsPromoted))) return zv::Val();
			if (UNEXPECTED(!callTruthy(native, PT_LC("isreadonly"), nativeIsReadOnly))) return zv::Val();
			if (UNEXPECTED(!callTruthy(native, PT_LC("ishooked"), nativeIsHooked))) return zv::Val();
			zv::Val phpReflection = pt_type_call(native, PT_LC("getnativereflection"), 0, NULL);
			if (UNEXPECTED(phpReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(phpReflection.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function hasDefaultValue() on %s", zend_zval_value_name(phpReflection.raw()));
				return zv::Val();
			}
			if (UNEXPECTED(!callTruthy(Z_OBJ_P(phpReflection.raw()), PT_LC("hasdefaultvalue"), nativeHasDefaultValue))) return zv::Val();
		}

		// IssetabilityLinkInfo::property()'s bool $reflectionNative
		if (UNEXPECTED(Z_TYPE_P(reflectionNative.raw()) != IS_TRUE && Z_TYPE_P(reflectionNative.raw()) != IS_FALSE)) {
			zend_type_error("PHPStan\\Analyser\\IssetabilityLinkInfo::property(): Argument #3 ($reflectionNative) must be of type bool, %s given", zend_zval_value_name(reflectionNative.raw()));
			return zv::Val();
		}
		zv::Val link = pt_issetability_link_info_property(propertyReflection.raw(), propertyFetch, Z_TYPE_P(reflectionNative.raw()) == IS_TRUE, hasNativeType, isVirtual.raw(), writableType.raw(), nativeType.raw(), isFetchTracked == PT_TRI_YES, initializedThisProperty, nativeReflectionExists, nativeIsPromoted, nativeIsReadOnly, nativeIsHooked, nativeHasDefaultValue);
		return newResolution(std::move(link), std::move(inner));
	}
};

} // namespace phpstanturbo

using phpstanturbo::IssetabilityDescriptor;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_issetability_descriptor_variable(zend_string *variableName)
{
	return IssetabilityDescriptor::variable(variableName);
}

zv::Val pt_issetability_descriptor_offset(zval *varResult, zval *dimResult)
{
	return IssetabilityDescriptor::offset(varResult, dimResult);
}

zv::Val pt_issetability_descriptor_property(zval *innerResult, zval *reflectionResolver, zval *propertyFetch)
{
	return IssetabilityDescriptor::property(innerResult != NULL && Z_TYPE_P(innerResult) == IS_NULL ? NULL : innerResult, reflectionResolver, propertyFetch);
}

/* the twin is final: the native class entry resolves natively, anything
 * else (the PHP twin declared next to the native class in the differential
 * tests) through the method */
zv::Val pt_issetability_descriptor_resolve(zval *descriptor, zval *scope, bool useNativeTypes, zval *expr, bool reprocessUntrackedLinks)
{
	if (EXPECTED(Z_OBJCE_P(descriptor) == pt_ce_issetability_descriptor)) return IssetabilityDescriptor(Z_OBJ_P(descriptor)).resolve(scope, useNativeTypes, expr, reprocessUntrackedLinks);
	zv::Args argv{scope, useNativeTypes, expr, reprocessUntrackedLinks};
	return pt_type_call(Z_OBJ_P(descriptor), PT_LC("resolve"), 4, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_issetability_descriptor()
{
	pt_id_kind_variable = zend_string_init_interned(PT_LC("variable"), 1);
	pt_id_kind_offset = zend_string_init_interned(PT_LC("offset"), 1);
	pt_id_kind_property = zend_string_init_interned(PT_LC("property"), 1);

	reg::Class cls("PHPStan\\Analyser\\IssetabilityDescriptor");
	ptdecl::IssetabilityDescriptor::declareClass(cls);
	ptdecl::IssetabilityDescriptor::declareProperties(cls);
	cls.privateClassConstantString("KIND_VARIABLE", "variable");
	cls.privateClassConstantString("KIND_OFFSET", "offset");
	cls.privateClassConstantString("KIND_PROPERTY", "property");

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *kind;
		zend_string *variableName = NULL;
		zval *varResult = NULL, *dimResult = NULL, *innerResult = NULL, *reflectionResolver = NULL, *propertyFetch = NULL;
		ZEND_PARSE_PARAMETERS_START(1, 7)
			Z_PARAM_STR(kind)
			Z_PARAM_OPTIONAL
			Z_PARAM_STR_OR_NULL(variableName)
			Z_PARAM_OBJECT_OR_NULL(varResult)
			Z_PARAM_OBJECT_OR_NULL(dimResult)
			Z_PARAM_OBJECT_OR_NULL(innerResult)
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(reflectionResolver, zend_ce_closure)
			Z_PARAM_OBJECT_OR_NULL(propertyFetch)
		ZEND_PARSE_PARAMETERS_END();
		zval variableNameZv;
		if (variableName != NULL) {
			ZVAL_STR(&variableNameZv, variableName);
		}
		IssetabilityDescriptor(Z_OBJ_P(ZEND_THIS)).construct(kind, variableName != NULL ? &variableNameZv : NULL, varResult, dimResult, innerResult, reflectionResolver, propertyFetch);
	});

	cls.method(sigs::variable, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *variableName;
		if (!zp::parse<zp::Str>(execute_data, variableName)) RETURN_THROWS();
		PT_RETURN_VAL(IssetabilityDescriptor::variable(variableName));
	});

	cls.method<&IssetabilityDescriptor::offset, zp::Obj, zp::Obj>(sigs::offset);

	cls.method(sigs::property, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *innerResult, *reflectionResolver, *propertyFetch;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT_OR_NULL(innerResult)
			Z_PARAM_OBJECT_OF_CLASS(reflectionResolver, zend_ce_closure)
			Z_PARAM_OBJECT(propertyFetch)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(IssetabilityDescriptor::property(innerResult, reflectionResolver, propertyFetch));
	});

	cls.method(sigs::resolve, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr;
		bool useNativeTypes, reprocessUntrackedLinks = false;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Obj, zp::Opt<zp::Bool>>(execute_data, scope, useNativeTypes, expr, reprocessUntrackedLinks)) RETURN_THROWS();
		PT_RETURN_VAL(IssetabilityDescriptor(Z_OBJ_P(ZEND_THIS)).resolve(scope, useNativeTypes, expr, reprocessUntrackedLinks));
	});

	cls.shadow(&pt_ce_issetability_descriptor);
}

/* }}} */
