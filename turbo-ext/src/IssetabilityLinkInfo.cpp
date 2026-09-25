/*
 * PHPStanTurbo\IssetabilityLinkInfo — native implementation of
 * PHPStan\Analyser\IssetabilityLinkInfo.
 *
 * One resolved link of an isset / empty / ?? chain: every
 * IssetabilityDescriptor::resolve() and ExpressionResult's leaf resolution
 * creates one per chain link, IssetabilityResolution::isSet() and the
 * IssetCheck rule read its facts. The twin's constructor is private;
 * instances come from variable() / offset() / property() / leaf(), natively
 * through pt_issetability_link_info_variable() / _offset() / _property() /
 * _leaf(). State lives in the twin's 24 promoted property slots, in its
 * order; the kind is one of four permanent interned strings. Native readers
 * of the facts are in AnalyserValues.h.
 */

#include "support.h"
#include "generated/IssetabilityLinkInfo.h"

namespace slots = ptdecl::IssetabilityLinkInfo::slot;
namespace sigs = ptdecl::IssetabilityLinkInfo::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_issetability_link_info = nullptr;

namespace {

/* the twin's KIND_* constants, permanent interned strings */
zend_string *pt_ili_kind_variable = nullptr;
zend_string *pt_ili_kind_offset = nullptr;
zend_string *pt_ili_kind_property = nullptr;
zend_string *pt_ili_kind_leaf = nullptr;

constexpr uint32_t PT_ILI_SLOT_COUNT = 24;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\IssetabilityLinkInfo. */
class IssetabilityLinkInfo
{
public:
	explicit IssetabilityLinkInfo(zend_object *self) : self(self) {}

	/* the constructor's arguments in slot order, the twin's defaults filled
	 * in (a borrowed view: the zvals are copied by construct()) */
	struct Values
	{
		zval slot[PT_ILI_SLOT_COUNT];

		explicit Values(zend_string *kind)
		{
			ZVAL_STR(&slot[slots::kind], kind);
			for (uint32_t i = 1; i < PT_ILI_SLOT_COUNT; i++) {
				ZVAL_NULL(&slot[i]);
			}
			for (uint32_t index : { slots::hasExpressionTypeOfExpr, slots::reflectionNative, slots::hasNativeType, slots::hasExpressionTypeOfFetch, slots::initializedThisProperty, slots::nativeReflectionExists, slots::nativeIsPromoted, slots::nativeIsReadOnly, slots::nativeIsHooked, slots::nativeHasDefaultValue, slots::leafIsNullsafePropertyFetch }) {
				ZVAL_FALSE(&slot[index]);
			}
		}
	};

	/* the private constructor's body */
	void construct(const Values &values) const
	{
		for (uint32_t i = 0; i < PT_ILI_SLOT_COUNT; i++) {
			pt_write_slot(self, i, const_cast<zval *>(&values.slot[i]));
		}
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(const Values &values)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_issetability_link_info) != SUCCESS)) return zv::Val();
		IssetabilityLinkInfo(Z_OBJ(object)).construct(values);
		return zv::Val::adopt(object);
	}

	static zv::Val variable(zend_string *variableName, zval *hasVariable, zval *valueType)
	{
		Values values(pt_ili_kind_variable);
		ZVAL_STR(&values.slot[slots::variableName], variableName);
		ZVAL_COPY_VALUE(&values.slot[slots::hasVariable], hasVariable);
		ZVAL_COPY_VALUE(&values.slot[slots::valueType], valueType);
		return create(values);
	}

	static zv::Val offset(zval *isOffsetAccessible, zval *hasOffsetValue, bool hasExpressionTypeOfExpr, zval *varType, zval *dimType, zval *valueType)
	{
		Values values(pt_ili_kind_offset);
		ZVAL_COPY_VALUE(&values.slot[slots::isOffsetAccessible], isOffsetAccessible);
		ZVAL_COPY_VALUE(&values.slot[slots::hasOffsetValue], hasOffsetValue);
		ZVAL_BOOL(&values.slot[slots::hasExpressionTypeOfExpr], hasExpressionTypeOfExpr);
		ZVAL_COPY_VALUE(&values.slot[slots::varType], varType);
		ZVAL_COPY_VALUE(&values.slot[slots::dimType], dimType);
		ZVAL_COPY_VALUE(&values.slot[slots::valueType], valueType);
		return create(values);
	}

	/* $propertyReflection NULL (or IS_NULL) for null */
	static zv::Val property(zval *propertyReflection, zval *propertyFetch, bool reflectionNative, bool hasNativeType, zval *isVirtual, zval *writableType, zval *nativeType, bool hasExpressionTypeOfFetch, bool initializedThisProperty, bool nativeReflectionExists, bool nativeIsPromoted, bool nativeIsReadOnly, bool nativeIsHooked, bool nativeHasDefaultValue)
	{
		Values values(pt_ili_kind_property);
		if (propertyReflection != NULL) ZVAL_COPY_VALUE(&values.slot[slots::propertyReflection], propertyReflection);
		ZVAL_COPY_VALUE(&values.slot[slots::propertyFetch], propertyFetch);
		ZVAL_BOOL(&values.slot[slots::reflectionNative], reflectionNative);
		ZVAL_BOOL(&values.slot[slots::hasNativeType], hasNativeType);
		ZVAL_COPY_VALUE(&values.slot[slots::isVirtual], isVirtual);
		ZVAL_COPY_VALUE(&values.slot[slots::valueType], writableType);
		ZVAL_COPY_VALUE(&values.slot[slots::nativeType], nativeType);
		ZVAL_BOOL(&values.slot[slots::hasExpressionTypeOfFetch], hasExpressionTypeOfFetch);
		ZVAL_BOOL(&values.slot[slots::initializedThisProperty], initializedThisProperty);
		ZVAL_BOOL(&values.slot[slots::nativeReflectionExists], nativeReflectionExists);
		ZVAL_BOOL(&values.slot[slots::nativeIsPromoted], nativeIsPromoted);
		ZVAL_BOOL(&values.slot[slots::nativeIsReadOnly], nativeIsReadOnly);
		ZVAL_BOOL(&values.slot[slots::nativeIsHooked], nativeIsHooked);
		ZVAL_BOOL(&values.slot[slots::nativeHasDefaultValue], nativeHasDefaultValue);
		return create(values);
	}

	static zv::Val leaf(zval *valueType, zval *leafExpr, bool leafIsNullsafePropertyFetch)
	{
		Values values(pt_ili_kind_leaf);
		ZVAL_COPY_VALUE(&values.slot[slots::valueType], valueType);
		ZVAL_COPY_VALUE(&values.slot[slots::leafExpr], leafExpr);
		ZVAL_BOOL(&values.slot[slots::leafIsNullsafePropertyFetch], leafIsNullsafePropertyFetch);
		return create(values);
	}

	[[nodiscard]] bool isVariable(bool &out) const { return isKind(pt_ili_kind_variable, out); }
	[[nodiscard]] bool isOffset(bool &out) const { return isKind(pt_ili_kind_offset, out); }
	[[nodiscard]] bool isProperty(bool &out) const { return isKind(pt_ili_kind_property, out); }

	zv::Val getVariableName() const { return required(slots::variableName, "variableName"); }
	zv::Val getHasVariable() const { return required(slots::hasVariable, "hasVariable"); }
	zv::Val getValueType() const { return required(slots::valueType, "valueType"); }
	zv::Val getIsOffsetAccessible() const { return required(slots::isOffsetAccessible, "isOffsetAccessible"); }
	zv::Val getHasOffsetValue() const { return required(slots::hasOffsetValue, "hasOffsetValue"); }
	zv::Val hasExpressionTypeOfExpr() const { return read(slots::hasExpressionTypeOfExpr, "hasExpressionTypeOfExpr"); }
	zv::Val getVarType() const { return required(slots::varType, "varType"); }
	zv::Val getDimType() const { return required(slots::dimType, "dimType"); }
	zv::Val getPropertyReflection() const { return read(slots::propertyReflection, "propertyReflection"); }

	/* Mirrors getPropertyFetch(): a PropertyFetch or StaticPropertyFetch */
	zv::Val getPropertyFetch() const
	{
		zval *value = pt_typed_slot(self, slots::propertyFetch, self->ce, "propertyFetch");
		if (UNEXPECTED(value == NULL)) return zv::Val();
		zend_class_entry *propertyFetchCe = pt_class(PT_CLASS_PROPERTY_FETCH);
		if (UNEXPECTED(propertyFetchCe == NULL)) return zv::Val();
		if (Z_TYPE_P(value) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(value), propertyFetchCe)) {
			zend_class_entry *staticPropertyFetchCe = pt_class(PT_CLASS_STATIC_PROPERTY_FETCH);
			if (UNEXPECTED(staticPropertyFetchCe == NULL)) return zv::Val();
			if (Z_TYPE_P(value) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(value), staticPropertyFetchCe)) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
		}
		return zv::Val::copyOf(zv::Ref(value));
	}

	zv::Val isReflectionNative() const { return read(slots::reflectionNative, "reflectionNative"); }
	zv::Val hasNativeType() const { return read(slots::hasNativeType, "hasNativeType"); }
	zv::Val isVirtual() const { return required(slots::isVirtual, "isVirtual"); }
	zv::Val getNativeType() const { return required(slots::nativeType, "nativeType"); }
	zv::Val hasExpressionTypeOfFetch() const { return read(slots::hasExpressionTypeOfFetch, "hasExpressionTypeOfFetch"); }
	zv::Val isInitializedThisProperty() const { return read(slots::initializedThisProperty, "initializedThisProperty"); }
	zv::Val nativeReflectionExists() const { return read(slots::nativeReflectionExists, "nativeReflectionExists"); }
	zv::Val nativeIsPromoted() const { return read(slots::nativeIsPromoted, "nativeIsPromoted"); }
	zv::Val nativeIsReadOnly() const { return read(slots::nativeIsReadOnly, "nativeIsReadOnly"); }
	zv::Val nativeIsHooked() const { return read(slots::nativeIsHooked, "nativeIsHooked"); }
	zv::Val nativeHasDefaultValue() const { return read(slots::nativeHasDefaultValue, "nativeHasDefaultValue"); }
	zv::Val getLeafExpr() const { return required(slots::leafExpr, "leafExpr"); }
	zv::Val leafIsNullsafePropertyFetch() const { return read(slots::leafIsNullsafePropertyFetch, "leafIsNullsafePropertyFetch"); }

private:
	zend_object *self;

	/* $this->kind === self::KIND_*; false = pending exception */
	[[nodiscard]] bool isKind(zend_string *kind, bool &out) const
	{
		zval *value = pt_typed_slot(self, slots::kind, self->ce, "kind");
		if (UNEXPECTED(value == NULL)) return false;
		out = Z_TYPE_P(value) == IS_STRING && zend_string_equals(Z_STR_P(value), kind);
		return true;
	}

	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}

	/* a getter throwing ShouldNotHappenException for a null slot */
	zv::Val required(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		if (UNEXPECTED(value == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(value) == IS_NULL)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		return zv::Val::copyOf(zv::Ref(value));
	}
};

} // namespace phpstanturbo

using phpstanturbo::IssetabilityLinkInfo;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_issetability_link_info_variable(zend_string *variableName, zval *hasVariable, zval *valueType)
{
	return IssetabilityLinkInfo::variable(variableName, hasVariable, valueType);
}

zv::Val pt_issetability_link_info_offset(zval *isOffsetAccessible, zval *hasOffsetValue, bool hasExpressionTypeOfExpr, zval *varType, zval *dimType, zval *valueType)
{
	return IssetabilityLinkInfo::offset(isOffsetAccessible, hasOffsetValue, hasExpressionTypeOfExpr, varType, dimType, valueType);
}

zv::Val pt_issetability_link_info_property(zval *propertyReflection, zval *propertyFetch, bool reflectionNative, bool hasNativeType, zval *isVirtual, zval *writableType, zval *nativeType, bool hasExpressionTypeOfFetch, bool initializedThisProperty, bool nativeReflectionExists, bool nativeIsPromoted, bool nativeIsReadOnly, bool nativeIsHooked, bool nativeHasDefaultValue)
{
	if (propertyReflection != NULL && Z_TYPE_P(propertyReflection) == IS_NULL) propertyReflection = NULL;
	return IssetabilityLinkInfo::property(propertyReflection, propertyFetch, reflectionNative, hasNativeType, isVirtual, writableType, nativeType, hasExpressionTypeOfFetch, initializedThisProperty, nativeReflectionExists, nativeIsPromoted, nativeIsReadOnly, nativeIsHooked, nativeHasDefaultValue);
}

zv::Val pt_issetability_link_info_leaf(zval *valueType, zval *leafExpr, bool leafIsNullsafePropertyFetch)
{
	return IssetabilityLinkInfo::leaf(valueType, leafExpr, leafIsNullsafePropertyFetch);
}

/* the twin is final: the native class entry answers from the kind slot,
 * anything else (the PHP twin in the differential tests) through the method */
bool pt_issetability_link_info_is_kind(zval *link, pt_issetability_link_kind kind, bool &out)
{
	if (EXPECTED(Z_OBJCE_P(link) == pt_ce_issetability_link_info)) {
		zval *value = OBJ_PROP_NUM(Z_OBJ_P(link), slots::kind);
		if (EXPECTED(Z_TYPE_P(value) == IS_STRING)) {
			zend_string *expected = kind == PT_ISSETABILITY_LINK_VARIABLE ? pt_ili_kind_variable : (kind == PT_ISSETABILITY_LINK_OFFSET ? pt_ili_kind_offset : pt_ili_kind_property);
			out = zend_string_equals(Z_STR_P(value), expected);
			return true;
		}
	}
	zv::Val result = kind == PT_ISSETABILITY_LINK_VARIABLE
		? pt_type_call(Z_OBJ_P(link), PT_LC("isvariable"), 0, NULL)
		: (kind == PT_ISSETABILITY_LINK_OFFSET ? pt_type_call(Z_OBJ_P(link), PT_LC("isoffset"), 0, NULL) : pt_type_call(Z_OBJ_P(link), PT_LC("isproperty"), 0, NULL));
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

namespace {

/* a zend_parse_parameters bool as a zval */
inline void setBool(zval *slot, bool value)
{
	ZVAL_BOOL(slot, value);
}

/* a parsed nullable object/string argument (NULL = null) as a zval */
inline void setNullable(zval *slot, zval *value)
{
	if (value != NULL) {
		ZVAL_COPY_VALUE(slot, value);
	} else {
		ZVAL_NULL(slot);
	}
}

} // namespace

PT_MINIT_REGISTRATION(pt_register_issetability_link_info)
{
	pt_ili_kind_variable = zend_string_init_interned(PT_LC("variable"), 1);
	pt_ili_kind_offset = zend_string_init_interned(PT_LC("offset"), 1);
	pt_ili_kind_property = zend_string_init_interned(PT_LC("property"), 1);
	pt_ili_kind_leaf = zend_string_init_interned(PT_LC("leaf"), 1);

	reg::Class cls("PHPStan\\Analyser\\IssetabilityLinkInfo");
	ptdecl::IssetabilityLinkInfo::declareClass(cls);
	ptdecl::IssetabilityLinkInfo::declareProperties(cls);
	cls.privateClassConstantString("KIND_VARIABLE", "variable");
	cls.privateClassConstantString("KIND_OFFSET", "offset");
	cls.privateClassConstantString("KIND_PROPERTY", "property");
	cls.privateClassConstantString("KIND_LEAF", "leaf");

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *kind;
		zval *variableName = NULL, *hasVariable = NULL, *isOffsetAccessible = NULL, *hasOffsetValue = NULL, *varType = NULL, *dimType = NULL, *valueType = NULL, *propertyReflection = NULL, *propertyFetch = NULL, *isVirtual = NULL, *nativeType = NULL, *leafExpr = NULL;
		bool hasExpressionTypeOfExpr = false, reflectionNative = false, hasNativeType = false, hasExpressionTypeOfFetch = false, initializedThisProperty = false, nativeReflectionExists = false, nativeIsPromoted = false, nativeIsReadOnly = false, nativeIsHooked = false, nativeHasDefaultValue = false, leafIsNullsafePropertyFetch = false;
		zend_string *variableNameString = NULL;
		ZEND_PARSE_PARAMETERS_START(1, 24)
			Z_PARAM_STR(kind)
			Z_PARAM_OPTIONAL
			Z_PARAM_STR_OR_NULL(variableNameString)
			Z_PARAM_OBJECT_OR_NULL(hasVariable)
			Z_PARAM_OBJECT_OR_NULL(isOffsetAccessible)
			Z_PARAM_OBJECT_OR_NULL(hasOffsetValue)
			Z_PARAM_BOOL(hasExpressionTypeOfExpr)
			Z_PARAM_OBJECT_OR_NULL(varType)
			Z_PARAM_OBJECT_OR_NULL(dimType)
			Z_PARAM_OBJECT_OR_NULL(valueType)
			Z_PARAM_OBJECT_OR_NULL(propertyReflection)
			Z_PARAM_OBJECT_OR_NULL(propertyFetch)
			Z_PARAM_BOOL(reflectionNative)
			Z_PARAM_BOOL(hasNativeType)
			Z_PARAM_OBJECT_OR_NULL(isVirtual)
			Z_PARAM_OBJECT_OR_NULL(nativeType)
			Z_PARAM_BOOL(hasExpressionTypeOfFetch)
			Z_PARAM_BOOL(initializedThisProperty)
			Z_PARAM_BOOL(nativeReflectionExists)
			Z_PARAM_BOOL(nativeIsPromoted)
			Z_PARAM_BOOL(nativeIsReadOnly)
			Z_PARAM_BOOL(nativeIsHooked)
			Z_PARAM_BOOL(nativeHasDefaultValue)
			Z_PARAM_OBJECT_OR_NULL(leafExpr)
			Z_PARAM_BOOL(leafIsNullsafePropertyFetch)
		ZEND_PARSE_PARAMETERS_END();
		IssetabilityLinkInfo::Values values(kind);
		zval variableNameZv;
		if (variableNameString != NULL) {
			ZVAL_STR(&variableNameZv, variableNameString);
			variableName = &variableNameZv;
		}
		setNullable(&values.slot[slots::variableName], variableName);
		setNullable(&values.slot[slots::hasVariable], hasVariable);
		setNullable(&values.slot[slots::isOffsetAccessible], isOffsetAccessible);
		setNullable(&values.slot[slots::hasOffsetValue], hasOffsetValue);
		setBool(&values.slot[slots::hasExpressionTypeOfExpr], hasExpressionTypeOfExpr);
		setNullable(&values.slot[slots::varType], varType);
		setNullable(&values.slot[slots::dimType], dimType);
		setNullable(&values.slot[slots::valueType], valueType);
		setNullable(&values.slot[slots::propertyReflection], propertyReflection);
		setNullable(&values.slot[slots::propertyFetch], propertyFetch);
		setBool(&values.slot[slots::reflectionNative], reflectionNative);
		setBool(&values.slot[slots::hasNativeType], hasNativeType);
		setNullable(&values.slot[slots::isVirtual], isVirtual);
		setNullable(&values.slot[slots::nativeType], nativeType);
		setBool(&values.slot[slots::hasExpressionTypeOfFetch], hasExpressionTypeOfFetch);
		setBool(&values.slot[slots::initializedThisProperty], initializedThisProperty);
		setBool(&values.slot[slots::nativeReflectionExists], nativeReflectionExists);
		setBool(&values.slot[slots::nativeIsPromoted], nativeIsPromoted);
		setBool(&values.slot[slots::nativeIsReadOnly], nativeIsReadOnly);
		setBool(&values.slot[slots::nativeIsHooked], nativeIsHooked);
		setBool(&values.slot[slots::nativeHasDefaultValue], nativeHasDefaultValue);
		setNullable(&values.slot[slots::leafExpr], leafExpr);
		setBool(&values.slot[slots::leafIsNullsafePropertyFetch], leafIsNullsafePropertyFetch);
		IssetabilityLinkInfo(Z_OBJ_P(ZEND_THIS)).construct(values);
	});

	cls.method(sigs::variable, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *variableName;
		zval *hasVariable, *valueType;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_STR(variableName)
			Z_PARAM_OBJECT(hasVariable)
			Z_PARAM_OBJECT(valueType)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(IssetabilityLinkInfo::variable(variableName, hasVariable, valueType));
	});

	cls.method(sigs::offset, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *isOffsetAccessible, *hasOffsetValue, *varType, *dimType, *valueType;
		bool hasExpressionTypeOfExpr;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(isOffsetAccessible)
			Z_PARAM_OBJECT(hasOffsetValue)
			Z_PARAM_BOOL(hasExpressionTypeOfExpr)
			Z_PARAM_OBJECT(varType)
			Z_PARAM_OBJECT(dimType)
			Z_PARAM_OBJECT(valueType)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(IssetabilityLinkInfo::offset(isOffsetAccessible, hasOffsetValue, hasExpressionTypeOfExpr, varType, dimType, valueType));
	});

	cls.method(sigs::property, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *propertyReflection, *propertyFetch, *isVirtual, *writableType, *nativeType;
		bool reflectionNative, hasNativeType, hasExpressionTypeOfFetch, initializedThisProperty, nativeReflectionExists, nativeIsPromoted, nativeIsReadOnly, nativeIsHooked, nativeHasDefaultValue;
		ZEND_PARSE_PARAMETERS_START(14, 14)
			Z_PARAM_OBJECT_OR_NULL(propertyReflection)
			Z_PARAM_OBJECT(propertyFetch)
			Z_PARAM_BOOL(reflectionNative)
			Z_PARAM_BOOL(hasNativeType)
			Z_PARAM_OBJECT(isVirtual)
			Z_PARAM_OBJECT(writableType)
			Z_PARAM_OBJECT(nativeType)
			Z_PARAM_BOOL(hasExpressionTypeOfFetch)
			Z_PARAM_BOOL(initializedThisProperty)
			Z_PARAM_BOOL(nativeReflectionExists)
			Z_PARAM_BOOL(nativeIsPromoted)
			Z_PARAM_BOOL(nativeIsReadOnly)
			Z_PARAM_BOOL(nativeIsHooked)
			Z_PARAM_BOOL(nativeHasDefaultValue)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(IssetabilityLinkInfo::property(propertyReflection, propertyFetch, reflectionNative, hasNativeType, isVirtual, writableType, nativeType, hasExpressionTypeOfFetch, initializedThisProperty, nativeReflectionExists, nativeIsPromoted, nativeIsReadOnly, nativeIsHooked, nativeHasDefaultValue));
	});

	cls.method(sigs::leaf, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *valueType, *leafExpr;
		bool leafIsNullsafePropertyFetch;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(valueType)
			Z_PARAM_OBJECT(leafExpr)
			Z_PARAM_BOOL(leafIsNullsafePropertyFetch)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(IssetabilityLinkInfo::leaf(valueType, leafExpr, leafIsNullsafePropertyFetch));
	});

	cls.method<&IssetabilityLinkInfo::isVariable>(sigs::isVariable);
	cls.method<&IssetabilityLinkInfo::isOffset>(sigs::isOffset);
	cls.method<&IssetabilityLinkInfo::isProperty>(sigs::isProperty);
	cls.method<&IssetabilityLinkInfo::getVariableName>(sigs::getVariableName);
	cls.method<&IssetabilityLinkInfo::getHasVariable>(sigs::getHasVariable);
	cls.method<&IssetabilityLinkInfo::getValueType>(sigs::getValueType);
	cls.method<&IssetabilityLinkInfo::getIsOffsetAccessible>(sigs::getIsOffsetAccessible);
	cls.method<&IssetabilityLinkInfo::getHasOffsetValue>(sigs::getHasOffsetValue);
	cls.method<&IssetabilityLinkInfo::hasExpressionTypeOfExpr>(sigs::hasExpressionTypeOfExpr);
	cls.method<&IssetabilityLinkInfo::getVarType>(sigs::getVarType);
	cls.method<&IssetabilityLinkInfo::getDimType>(sigs::getDimType);
	cls.method<&IssetabilityLinkInfo::getPropertyReflection>(sigs::getPropertyReflection);
	cls.method<&IssetabilityLinkInfo::getPropertyFetch>(sigs::getPropertyFetch);
	cls.method<&IssetabilityLinkInfo::isReflectionNative>(sigs::isReflectionNative);
	cls.method<&IssetabilityLinkInfo::hasNativeType>(sigs::hasNativeType);
	cls.method<&IssetabilityLinkInfo::isVirtual>(sigs::isVirtual);
	cls.method<&IssetabilityLinkInfo::getNativeType>(sigs::getNativeType);
	cls.method<&IssetabilityLinkInfo::hasExpressionTypeOfFetch>(sigs::hasExpressionTypeOfFetch);
	cls.method<&IssetabilityLinkInfo::isInitializedThisProperty>(sigs::isInitializedThisProperty);
	cls.method<&IssetabilityLinkInfo::nativeReflectionExists>(sigs::nativeReflectionExists);
	cls.method<&IssetabilityLinkInfo::nativeIsPromoted>(sigs::nativeIsPromoted);
	cls.method<&IssetabilityLinkInfo::nativeIsReadOnly>(sigs::nativeIsReadOnly);
	cls.method<&IssetabilityLinkInfo::nativeIsHooked>(sigs::nativeIsHooked);
	cls.method<&IssetabilityLinkInfo::nativeHasDefaultValue>(sigs::nativeHasDefaultValue);
	cls.method<&IssetabilityLinkInfo::getLeafExpr>(sigs::getLeafExpr);
	cls.method<&IssetabilityLinkInfo::leafIsNullsafePropertyFetch>(sigs::leafIsNullsafePropertyFetch);

	cls.shadow(&pt_ce_issetability_link_info);
}

/* }}} */
