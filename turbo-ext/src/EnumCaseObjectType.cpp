/*
 * PHPStanTurbo\EnumCaseObjectType — native implementation of
 * PHPStan\Type\Enum\EnumCaseObjectType.
 *
 * Declared as PHPStan\Type\Enum\EnumCaseObjectType itself at activation,
 * extending the native ObjectType: not final. State is the twin's promoted
 * `private readonly string $enumCaseName`, a typed property slot following
 * the parent's ten; the twin's `parent::__construct($className,
 * classReflection: $classReflection)` and `new parent(...)` go to the C++
 * bodies ObjectType.cpp exports, every `$this->method()` through the
 * object's class entry with a direct C++ call when the object's method is
 * the native one.
 */

#include "TypeTraits.h"
#include "generated/EnumCaseObjectType.h"

namespace sigs = ptdecl::EnumCaseObjectType::sig;

zend_class_entry *pt_ce_enum_case_object_type = nullptr;

/* OBJ_PROP_NUM slots: the parent's ten first (ObjectType.cpp) */
#define PT_OT_PROP_COUNT 10
#define PT_ECOT_PROP_ENUM_CASE_NAME (PT_OT_PROP_COUNT + 0)

/* the handlers the $this-dispatch fast paths identify */
static void ZEND_FASTCALL ecotGetEnumCaseName(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL ecotEquals(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL ecotIsSuperTypeOf(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL ecotSubtract(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL ecotChangeSubtractedType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL ecotGetSubtractedType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL ecotGetUnresolvedInstancePropertyPrototype(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL ecotGetClassStringType(INTERNAL_FUNCTION_PARAMETERS);

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Enum\EnumCaseObjectType. */
class EnumCaseObjectType
{
public:
	explicit EnumCaseObjectType(zend_object *self) : self(self) {}

	/* __construct(string $className, private readonly string $enumCaseName,
	 * ?ClassReflection $classReflection = null): the readonly property is
	 * initialized once (a repeated call fails as the twin's promoted
	 * assignment does), then parent::__construct($className,
	 * classReflection: $classReflection); false = pending exception */
	[[nodiscard]] bool construct(zend_string *className, zend_string *enumCaseName, zval *classReflection)
	{
		zval *slot = OBJ_PROP_NUM(self, PT_ECOT_PROP_ENUM_CASE_NAME);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_UNDEF)) {
			zend_throw_error(NULL, "Cannot modify readonly property %s::$enumCaseName", ZSTR_VAL(self->ce->name));
			return false;
		}
		ZVAL_STR_COPY(slot, enumCaseName);
		Z_PROP_FLAG_P(slot) = 0; /* no longer IS_PROP_UNINIT */
		pt_object_type_construct(self, className, NULL, classReflection);
		return true;
	}

	/* new EnumCaseObjectType($className, $enumCaseName, $classReflection);
	 * UNDEF = pending exception */
	static zv::Val create(zend_string *className, zend_string *enumCaseName, zval *classReflection)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_enum_case_object_type) != SUCCESS)) return zv::Val();
		if (UNEXPECTED(!EnumCaseObjectType(Z_OBJ(object)).construct(className, enumCaseName, classReflection))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
		return zv::Val::adopt(object);
	}

	/* $this->enumCaseName / $type->enumCaseName (borrowed); NULL with an
	 * Error pending when uninitialized */
	static zend_string *enumCaseNameOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, PT_ECOT_PROP_ENUM_CASE_NAME);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_STRING)) {
			zend_throw_error(NULL, "Typed property %s::$enumCaseName must not be accessed before initialization", ZSTR_VAL(pt_ce_enum_case_object_type->name));
			return NULL;
		}
		return Z_STR_P(slot);
	}

	zend_string *enumCaseName() const { return enumCaseNameOf(self); }

	/* parent::describe($level) . '::' . $this->enumCaseName; UNDEF =
	 * pending exception */
	zv::Val describe(zval *level) const
	{
		zv::Val parent = pt_object_type_describe(self, level);
		if (UNEXPECTED(parent.isUndef())) return zv::Val();
		zend_string *caseName = enumCaseName();
		if (UNEXPECTED(caseName == NULL)) return zv::Val();
		zv::Str parentStr = zv::Str::adopt(zval_get_string(parent.raw()));
		smart_str description = {NULL, 0};
		smart_str_append(&description, parentStr.get());
		smart_str_appendl(&description, "::", 2);
		smart_str_append(&description, caseName);
		smart_str_0(&description);
		return zv::Val::adoptString(description.s);
	}

	/* the same case of the same class — the `$this->enumCaseName ===
	 * $type->enumCaseName && $this->getClassName() === $type->getClassName()`
	 * both equals() and isSuperTypeOf() test; false = pending exception */
	[[nodiscard]] bool sameCase(zval *type, bool &out) const
	{
		zend_string *caseName = enumCaseName();
		if (UNEXPECTED(caseName == NULL)) return false;
		zend_string *otherCaseName = enumCaseNameOf(Z_OBJ_P(type));
		if (UNEXPECTED(otherCaseName == NULL)) return false;
		if (!zend_string_equals(caseName, otherCaseName)) {
			out = false;
			return true;
		}
		zv::Val className = thisGetClassName();
		if (UNEXPECTED(className.isUndef())) return false;
		zv::Val otherClassName = classNameOf(Z_OBJ_P(type));
		if (UNEXPECTED(otherClassName.isUndef())) return false;
		out = zend_is_identical(className.raw(), otherClassName.raw());
		return true;
	}

	bool equals(zval *type, bool &out) const
	{
		if (!zv::Ref(type).instanceOf(pt_ce_enum_case_object_type)) {
			out = false;
			return true;
		}
		return sameCase(type, out);
	}

	/* $this->isSuperTypeOf($type)->toAcceptsResult(); UNDEF = pending exception */
	zv::Val accepts(zval *type) const
	{
		zv::Val isSuperType = thisCall(PT_LC("issupertypeof"), ecotIsSuperTypeOf, 1, type, [&]() { return isSuperTypeOf(type); });
		if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(isSuperType.raw()).isObject())) {
			zend_type_error("phpstan_turbo: isSuperTypeOf() must return %s", ZSTR_VAL(pt_ce_is_super_type_of_result->name));
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(isSuperType.raw()), PT_LC("toacceptsresult"), 0, NULL);
	}

	/* UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		if (zv::Ref(type).instanceOf(pt_ce_enum_case_object_type)) {
			bool same;
			if (UNEXPECTED(!sameCase(type, same))) return zv::Val();
			return pt_type_is_super_type_of_result(same ? PT_TRI_YES : PT_TRI_NO);
		}

		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		if (compound) return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &selfZv);

		bool subtractable;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_SUBTRACTABLE_TYPE, subtractable))) return zv::Val();
		if (subtractable) {
			zv::Val subtracted = pt_type_call(Z_OBJ_P(type), PT_LC("getsubtractedtype"), 0, NULL);
			if (UNEXPECTED(subtracted.isUndef())) return zv::Val();
			if (!subtracted.isNull()) {
				zv::Val isSuperType = pt_type_op(Z_OBJ_P(subtracted.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, &selfZv);
				if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
				zend_long value = pt_type_result_trinary(isSuperType.raw());
				if (UNEXPECTED(value < 0)) return zv::Val();
				if (value == PT_TRI_YES) return pt_type_is_super_type_of_result(PT_TRI_NO);
			}
		}

		/* new parent($this->getClassName(), $this->getSubtractedType(), $this->getClassReflection()) */
		zv::Val parent = newParent(true);
		if (UNEXPECTED(parent.isUndef())) return zv::Val();
		zv::Val isSuperType = pt_object_type_is_super_type_of(Z_OBJ_P(parent.raw()), type);
		if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
		zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		if (UNEXPECTED(maybe.isUndef())) return zv::Val();
		return pt_type_op(Z_OBJ_P(isSuperType.raw()), PT_OP_AND, 1, maybe.raw());
	}

	/* $this->changeSubtractedType($type); UNDEF = pending exception */
	zv::Val subtract(zval *type) const
	{
		return thisCall(PT_LC("changesubtractedtype"), ecotChangeSubtractedType, 1, type, [&]() { return changeSubtractedType(type); });
	}

	/* never when the subtracted type is this very case, $this otherwise;
	 * $subtractedType IS_NULL for null; UNDEF = pending exception */
	zv::Val changeSubtractedType(zval *subtractedType) const
	{
		if (Z_TYPE_P(subtractedType) == IS_NULL) return thisValue();
		zv::Val equal = thisCall(PT_LC("equals"), ecotEquals, 1, subtractedType, [&]() {
			bool same;
			if (UNEXPECTED(!equals(subtractedType, same))) return zv::Val();
			return zv::Val::boolean(same);
		});
		if (UNEXPECTED(equal.isUndef())) return zv::Val();
		if (!zend_is_true(equal.raw())) return thisValue();

		return pt_type_new_never_type();
	}

	/* $this->subtract($typeToRemove) when this is its supertype, null
	 * otherwise; UNDEF = pending exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		zv::Val isSuperType = thisCall(PT_LC("issupertypeof"), ecotIsSuperTypeOf, 1, typeToRemove, [&]() { return isSuperTypeOf(typeToRemove); });
		if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
		zend_long value = pt_type_result_trinary(isSuperType.raw());
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (value == PT_TRI_YES) {
			return thisCall(PT_LC("subtract"), ecotSubtract, 1, typeToRemove, [&]() { return subtract(typeToRemove); });
		}

		return zv::Val::null();
	}

	/* $this->getUnresolvedInstancePropertyPrototype($propertyName, $scope);
	 * UNDEF = pending exception */
	zv::Val getUnresolvedPropertyPrototype(zval *propertyName, zval *scope) const
	{
		zv::Args args{propertyName, scope};
		return thisCall(PT_LC("getunresolvedinstancepropertyprototype"), ecotGetUnresolvedInstancePropertyPrototype, 2, args, [&]() { return getUnresolvedInstancePropertyPrototype(propertyName, scope); });
	}

	/* the enum's `name` / backed `value` property of this case, the
	 * parent's prototype otherwise; UNDEF = pending exception */
	zv::Val getUnresolvedInstancePropertyPrototype(zval *propertyName, zval *scope) const
	{
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (classReflection.isNull()) return pt_object_type_get_unresolved_instance_property_prototype(self, propertyName, scope);
		if (UNEXPECTED(!zv::Ref(classReflection.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getClassReflection() must return an object");
			return zv::Val();
		}
		zend_object *reflection = Z_OBJ_P(classReflection.raw());
		zend_string *caseName = enumCaseName();
		if (UNEXPECTED(caseName == NULL)) return zv::Val();

		if (zend_string_equals_literal(Z_STR_P(propertyName), "name")) {
			zval nameType;
			if (UNEXPECTED(!pt_constant_string_type_new(&nameType, caseName))) return zv::Val();
			zv::Val nameTypeVal = zv::Val::adopt(nameType);
			return enumPropertyPrototype(propertyName, classReflection.raw(), nameTypeVal.raw());
		}

		bool backed;
		if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("isbackedenum"), 0, NULL, backed))) return zv::Val();
		if (backed && zend_string_equals_literal(Z_STR_P(propertyName), "value")) {
			zval caseNameZv;
			ZVAL_STR(&caseNameZv, caseName);
			bool hasCase;
			if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("hasenumcase"), 1, &caseNameZv, hasCase))) return zv::Val();
			if (hasCase) {
				zv::Val valueType = backingValueTypeOfCase(reflection, &caseNameZv);
				if (UNEXPECTED(valueType.isUndef())) return zv::Val();
				if (valueType.isNull()) {
					pt_throw_should_not_happen();
					return zv::Val();
				}

				return enumPropertyPrototype(propertyName, classReflection.raw(), valueType.raw());
			}
		}

		return pt_object_type_get_unresolved_instance_property_prototype(self, propertyName, scope);
	}

	/* the backing value type of this case, null for a pure enum, an
	 * unknown class or case; UNDEF = pending exception */
	zv::Val getBackingValueType() const
	{
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (classReflection.isNull()) return zv::Val::null();
		if (UNEXPECTED(!zv::Ref(classReflection.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getClassReflection() must return an object");
			return zv::Val();
		}
		zend_object *reflection = Z_OBJ_P(classReflection.raw());

		bool backed;
		if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("isbackedenum"), 0, NULL, backed))) return zv::Val();
		if (!backed) return zv::Val::null();

		zend_string *caseName = enumCaseName();
		if (UNEXPECTED(caseName == NULL)) return zv::Val();
		zval caseNameZv;
		ZVAL_STR(&caseNameZv, caseName);
		bool hasCase;
		if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("hasenumcase"), 1, &caseNameZv, hasCase))) return zv::Val();
		if (hasCase) return backingValueTypeOfCase(reflection, &caseNameZv);

		return zv::Val::null();
	}

	/* new parent($this->getClassName(), null, $this->getClassReflection()) */
	zv::Val generalize() const { return newParent(false); }

	/* [$this] */
	zv::Val getEnumCases() const
	{
		zv::Arr cases = zv::Arr::create(1);
		cases.push(thisValue());
		return zv::Val(std::move(cases));
	}

	/* new GenericClassStringType(new ObjectType($this->getClassName()));
	 * UNDEF = pending exception */
	zv::Val getClassStringType() const
	{
		zv::Val className = thisGetClassName();
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zv::Str name = zv::Str::adopt(zval_get_string(className.raw()));
		zval objectType;
		if (UNEXPECTED(!pt_object_type_new(&objectType, name.get()))) return zv::Val();
		zv::Val objectTypeVal = zv::Val::adopt(objectType);
		return pt_type_new_ce(pt_ce_generic_class_string_type, 1, objectTypeVal.raw());
	}

	/* class-string<Enum>&literal-string — the bare enum class, skipping the
	 * parent's finality collapse; UNDEF = pending exception */
	zv::Val toClassConstantType() const
	{
		zv::Arr types = zv::Arr::create(2);
		zv::Val classString = thisCall(PT_LC("getclassstringtype"), ecotGetClassStringType, 0, NULL, [&]() { return getClassStringType(); });
		if (UNEXPECTED(classString.isUndef())) return zv::Val();
		types.push(std::move(classString));
		zval literalRaw;
		zv::Val literal = pt_accessory_literal_string_type_new(&literalRaw) ? zv::Val::adopt(literalRaw) : zv::Val();
		if (UNEXPECTED(literal.isUndef())) return zv::Val();
		types.push(std::move(literal));
		return pt_intersection_of(std::move(types));
	}

	/* new ConstTypeNode(new ConstFetchNode($this->getClassName(), $this->getEnumCaseName()));
	 * UNDEF = pending exception */
	zv::Val toPhpDocNode() const
	{
		zv::Val className = thisGetClassName();
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zv::Val caseName = thisCall(PT_LC("getenumcasename"), ecotGetEnumCaseName, 0, NULL, [&]() {
			zend_string *name = enumCaseName();
			if (UNEXPECTED(name == NULL)) return zv::Val();
			return zv::Val::string(name);
		});
		if (UNEXPECTED(caseName.isUndef())) return zv::Val();
		zv::Args args{className.raw(), caseName.raw()};
		zv::Val constFetch = pt_type_new(PT_CLASS_CONST_FETCH_NODE, 2, args);
		if (UNEXPECTED(constFetch.isUndef())) return zv::Val();
		return pt_type_new(PT_CLASS_CONST_TYPE_NODE, 1, constFetch.raw());
	}

private:
	zend_object *self;

	/* exactly an EnumCaseObjectType, none of its methods overridden */
	bool isExact() const { return self->ce == pt_ce_enum_case_object_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	template <typename Direct>
	zv::Val thisCall(const char *lcname, size_t len, zif_handler handler, uint32_t argc, zval *argv, Direct direct) const { return pt_this_call(self, isExact(), lcname, len, handler, argc, argv, direct); }

	/* $object->getClassName() on another EnumCaseObjectType — the parent's
	 * slot when the object is exactly this class; UNDEF = pending exception */
	static zv::Val classNameOf(zend_object *object)
	{
		if (EXPECTED(object->ce == pt_ce_enum_case_object_type)) {
			zend_string *name = pt_object_type_class_name(object);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			return zv::Val::string(name);
		}
		return pt_type_call(object, PT_LC("getclassname"), 0, NULL);
	}

	zv::Val thisGetClassName() const { return classNameOf(self); }

	/* $this->getClassReflection() — the parent's body when the object is
	 * exactly this class */
	zv::Val thisGetClassReflection() const
	{
		if (EXPECTED(isExact())) return pt_object_type_get_class_reflection(self);
		return pt_type_call(self, PT_LC("getclassreflection"), 0, NULL);
	}

	/* $this->getSubtractedType() — null by this class's own body */
	zv::Val thisGetSubtractedType() const
	{
		return thisCall(PT_LC("getsubtractedtype"), ecotGetSubtractedType, 0, NULL, [&]() { return zv::Val::null(); });
	}

	/* new parent($this->getClassName(), $withSubtracted ? $this->getSubtractedType() : null, $this->getClassReflection());
	 * UNDEF = pending exception */
	zv::Val newParent(bool withSubtracted) const
	{
		zv::Val className = thisGetClassName();
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zv::Val subtracted;
		if (withSubtracted) {
			subtracted = thisGetSubtractedType();
			if (UNEXPECTED(subtracted.isUndef())) return zv::Val();
		} else {
			subtracted = zv::Val::null();
		}
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		zv::Str name = zv::Str::adopt(zval_get_string(className.raw()));
		zval parent;
		if (UNEXPECTED(!pt_object_type_new(&parent, name.get(), subtracted.isNull() ? NULL : subtracted.raw(), classReflection.isNull() ? NULL : classReflection.raw()))) {
			return zv::Val();
		}
		return zv::Val::adopt(parent);
	}

	/* new EnumUnresolvedPropertyPrototypeReflection(new EnumPropertyReflection($propertyName, $classReflection, $type));
	 * UNDEF = pending exception */
	static zv::Val enumPropertyPrototype(zval *propertyName, zval *classReflection, zval *type)
	{
		zv::Args args{propertyName, classReflection, type};
		zv::Val property = pt_type_new(PT_CLASS_ENUM_PROPERTY_REFLECTION, 3, args);
		if (UNEXPECTED(property.isUndef())) return zv::Val();
		return pt_type_new(PT_CLASS_ENUM_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION, 1, property.raw());
	}

	/* $classReflection->getEnumCase($caseName)->getBackingValueType(); UNDEF
	 * = pending exception */
	static zv::Val backingValueTypeOfCase(zend_object *reflection, zval *caseName)
	{
		zv::Val enumCase = pt_type_call(reflection, PT_LC("getenumcase"), 1, caseName);
		if (UNEXPECTED(enumCase.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(enumCase.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getEnumCase() must return an object");
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(enumCase.raw()), PT_LC("getbackingvaluetype"), 0, NULL);
	}

};

} // namespace phpstanturbo

using phpstanturbo::EnumCaseObjectType;

bool pt_enum_case_object_type_new(zval *out, zend_string *className, zend_string *enumCaseName, zval *classReflection)
{
	return pt_val_into(EnumCaseObjectType::create(className, enumCaseName, classReflection), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS EnumCaseObjectType(Z_OBJ_P(ZEND_THIS))

/* the no-argument methods returning a value */
static void pt_ecot_value(INTERNAL_FUNCTION_PARAMETERS, zv::Val (EnumCaseObjectType::*method)() const)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL((PT_THIS.*method)());
}

/* (object $x) → a value */
static void pt_ecot_value_of(INTERNAL_FUNCTION_PARAMETERS, zv::Val (EnumCaseObjectType::*method)(zval *) const)
{
	zval *x;
	if (!zp::parse<zp::Obj>(execute_data, x)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(x));
}

/* (string $propertyName, ClassMemberAccessAnswerer $scope) → a value */
static void pt_ecot_member(INTERNAL_FUNCTION_PARAMETERS, zv::Val (EnumCaseObjectType::*method)(zval *, zval *) const)
{
	zend_string *name;
	zval *scope;
	if (!zp::parse<zp::Str, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	PT_RETURN_VAL((PT_THIS.*method)(&nameZv, scope));
}

/* (Type $otherType, PhpVersion $phpVersion) → no */
static void ZEND_FASTCALL ecotNo2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL ecotShouldNotHappen2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	pt_throw_should_not_happen();
	RETURN_THROWS();
}

static void ZEND_FASTCALL ecotGetEnumCaseName(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	zend_string *name = PT_THIS.enumCaseName();
	if (UNEXPECTED(name == NULL)) RETURN_THROWS();
	RETURN_STR_COPY(name);
}

static void ZEND_FASTCALL ecotEquals(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type;
	if (!zp::parse<zp::TypeObj>(execute_data, type)) RETURN_THROWS();
	bool equal;
	if (UNEXPECTED(!PT_THIS.equals(type, equal))) RETURN_THROWS();
	RETURN_BOOL(equal);
}

static void ZEND_FASTCALL ecotIsSuperTypeOf(INTERNAL_FUNCTION_PARAMETERS) { pt_ecot_value_of(INTERNAL_FUNCTION_PARAM_PASSTHRU, &EnumCaseObjectType::isSuperTypeOf); }
static void ZEND_FASTCALL ecotSubtract(INTERNAL_FUNCTION_PARAMETERS) { pt_ecot_value_of(INTERNAL_FUNCTION_PARAM_PASSTHRU, &EnumCaseObjectType::subtract); }

static void ZEND_FASTCALL ecotChangeSubtractedType(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *subtractedType;
	if (!zp::parse<zp::ObjOrNull>(execute_data, subtractedType)) RETURN_THROWS();
	zval nullZv;
	if (subtractedType == NULL) {
		ZVAL_NULL(&nullZv);
		subtractedType = &nullZv;
	}
	PT_RETURN_VAL(PT_THIS.changeSubtractedType(subtractedType));
}

static void ZEND_FASTCALL ecotGetSubtractedType(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_NULL();
}

static void ZEND_FASTCALL ecotGetUnresolvedInstancePropertyPrototype(INTERNAL_FUNCTION_PARAMETERS) { pt_ecot_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, &EnumCaseObjectType::getUnresolvedInstancePropertyPrototype); }
static void ZEND_FASTCALL ecotGetClassStringType(INTERNAL_FUNCTION_PARAMETERS) { pt_ecot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &EnumCaseObjectType::getClassStringType); }

PT_MINIT_REGISTRATION(pt_register_enum_case_object_type)
{
	reg::Class cls("PHPStan\\Type\\Enum\\EnumCaseObjectType");
	ptdecl::EnumCaseObjectType::declareClass(cls);
	/* the promoted readonly property, after the parent's slots */
	ptdecl::EnumCaseObjectType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *className, *enumCaseName;
		zval *classReflection = NULL;
		if (!zp::parse<zp::Str, zp::Str, zp::Opt<zp::ObjOrNull>>(execute_data, className, enumCaseName, classReflection)) RETURN_THROWS();
		if (UNEXPECTED(!PT_THIS.construct(className, enumCaseName, classReflection))) RETURN_THROWS();
	});

	cls.method(sigs::getEnumCaseName, ecotGetEnumCaseName);
	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ecot_value_of(INTERNAL_FUNCTION_PARAM_PASSTHRU, &EnumCaseObjectType::describe);
	});
	cls.op<PT_OP_DESCRIBE, &EnumCaseObjectType::describe>();
	cls.method(sigs::equals, ecotEquals);
	cls.op<PT_OP_EQUALS, &EnumCaseObjectType::equals>();
	cls.method(sigs::accepts, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, type, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.accepts(type));
	});
	cls.op<PT_OP_ACCEPTS, &EnumCaseObjectType::accepts>();
	cls.method(sigs::isSuperTypeOf, ecotIsSuperTypeOf);
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &EnumCaseObjectType::isSuperTypeOf>();
	cls.method(sigs::subtract, ecotSubtract);
	cls.method(sigs::getTypeWithoutSubtractedType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
	});
	cls.op(PT_OP_GET_TYPE_WITHOUT_SUBTRACTED_TYPE, PT_OP_LAMBDA { return pt_op_this(self); });
	cls.method(sigs::changeSubtractedType, ecotChangeSubtractedType);
	cls.method(sigs::getSubtractedType, ecotGetSubtractedType);
	cls.op(PT_OP_GET_SUBTRACTED_TYPE, PT_OP_LAMBDA { return zv::Val::null(); });
	cls.method(sigs::tryRemove, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ecot_value_of(INTERNAL_FUNCTION_PARAM_PASSTHRU, &EnumCaseObjectType::tryRemove);
	});
	cls.method(sigs::getUnresolvedPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ecot_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, &EnumCaseObjectType::getUnresolvedPropertyPrototype);
	});
	cls.method(sigs::getUnresolvedInstancePropertyPrototype, ecotGetUnresolvedInstancePropertyPrototype);
	cls.op<PT_OP_GET_UNRESOLVED_INSTANCE_PROPERTY_PROTOTYPE, &EnumCaseObjectType::getUnresolvedInstancePropertyPrototype>();
	cls.method(sigs::hasStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_TRINARY(PT_TRI_NO);
	});
	cls.method(sigs::getStaticProperty, ecotShouldNotHappen2);
	cls.method(sigs::getUnresolvedStaticPropertyPrototype, ecotShouldNotHappen2);
	cls.method(sigs::getBackingValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ecot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &EnumCaseObjectType::getBackingValueType);
	});
	cls.method(sigs::generalize, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(PT_THIS.generalize());
	});
	cls.method(sigs::isSmallerThan, ecotNo2);
	cls.method(sigs::isSmallerThanOrEqual, ecotNo2);
	cls.method(sigs::getEnumCases, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ecot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &EnumCaseObjectType::getEnumCases);
	});
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
	});
	cls.op(PT_OP_GET_ENUM_CASE_OBJECT, PT_OP_LAMBDA { return pt_op_this(self); });
	cls.method(sigs::getClassStringType, ecotGetClassStringType);
	cls.method(sigs::toClassConstantType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(PT_THIS.toClassConstantType());
	});
	cls.method(sigs::toPhpDocNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ecot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &EnumCaseObjectType::toPhpDocNode);
	});

	cls.shadow(&pt_ce_enum_case_object_type);
}

/* }}} */
