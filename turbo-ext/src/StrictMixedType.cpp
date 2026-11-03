/*
 * PHPStanTurbo\StrictMixedType — native implementation of
 * PHPStan\Type\StrictMixedType.
 *
 * Declared as PHPStan\Type\StrictMixedType itself at activation: not final
 * (the PHP TemplateStrictMixedType extends it), implementing
 * PHPStan\Type\CompoundType. State is the twin's promoted `private ?Type
 * $subtractedType`, a declared typed property slot (IS_PROP_UNINIT until
 * the constructor writes it), so the std object handlers do GC/clone and
 * the TemplateStrictMixedType trait slots follow it.
 *
 * The `$this->method()` calls the twin makes (describeSubtractedType() and,
 * for the reason of a no, describe()) go through the object's class entry.
 */

#include "TypeTraits.h"
#include "generated/StrictMixedType.h"

namespace slots = ptdecl::StrictMixedType::slot;
namespace sigs = ptdecl::StrictMixedType::sig;

zend_class_entry *pt_ce_strict_mixed_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\StrictMixedType. State lives in the PHP object's
 * $subtractedType; the methods that do not read it are static. */
class StrictMixedType
{
public:
	explicit StrictMixedType(zend_object *self) : self(self) {}

	/* __construct(private ?Type $subtractedType = null); $subtractedType
	 * borrowed, NULL for null */
	void construct(zval *subtractedType)
	{
		zval *slot = OBJ_PROP_NUM(self, slots::subtractedType);
		/* the slot is overwritten in place: a repeated parent::__construct()
		 * call from a subclass would otherwise leak the first value */
		zval previous;
		ZVAL_COPY_VALUE(&previous, slot);
		if (subtractedType == NULL) {
			ZVAL_NULL(slot);
		} else {
			ZVAL_COPY(slot, subtractedType);
		}
		Z_PROP_FLAG_P(slot) = 0; /* no longer IS_PROP_UNINIT */
		if (Z_TYPE(previous) != IS_UNDEF) {
			zval_ptr_dtor(&previous);
		}
	}

	/* $this->subtractedType (borrowed, IS_NULL or IS_OBJECT); NULL with an
	 * Error pending when the constructor never ran — the twin's
	 * typed-property read raises the same */
	zval *subtractedType() const { return subtractedTypeOf(self); }

	/* $type->subtractedType of another StrictMixedType instance */
	static zval *subtractedTypeOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::subtractedType);
		if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$subtractedType must not be accessed before initialization", ZSTR_VAL(pt_ce_strict_mixed_type->name));
			return NULL;
		}
		return slot;
	}

	/* a no carrying the reason when the subtracted type is a supertype of
	 * $type (no NeverType), AcceptsResult::createYes() otherwise; UNDEF =
	 * pending exception */
	zv::Val accepts(zval *type) const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		return pt_type_accepts_unless_eliminated(self, subtracted, type);
	}

	/* yes for a StrictMixedType or a MixedType that is no TemplateMixedType,
	 * maybe otherwise; UNDEF = pending exception */
	static zv::Val isAcceptedBy(zval *acceptingType)
	{
		bool yes;
		if (UNEXPECTED(!isStrictOrPlainMixed(acceptingType, yes))) return zv::Val();
		return pt_type_accepts_result(yes ? PT_TRI_YES : PT_TRI_MAYBE);
	}

	/* IsSuperTypeOfResult::createYes() */
	static zv::Val isSuperTypeOf() { return pt_type_is_super_type_of_result(PT_TRI_YES); }

	/* yes for a StrictMixedType or a MixedType that is no TemplateMixedType,
	 * maybe otherwise; UNDEF = pending exception */
	static zv::Val isSubTypeOf(zval *otherType)
	{
		bool yes;
		if (UNEXPECTED(!isStrictOrPlainMixed(otherType, yes))) return zv::Val();
		return pt_type_is_super_type_of_result(yes ? PT_TRI_YES : PT_TRI_MAYBE);
	}

	/* $type instanceof self with an equal subtracted type (both null, or
	 * both set and equals()); false = pending exception */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_strict_mixed_type)) {
			out = false;
			return true;
		}
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return false;
		zval *typeSubtracted = subtractedTypeOf(Z_OBJ_P(type));
		if (UNEXPECTED(typeSubtracted == NULL)) return false;
		if (Z_TYPE_P(subtracted) == IS_NULL) {
			out = Z_TYPE_P(typeSubtracted) == IS_NULL;
			return true;
		}
		if (Z_TYPE_P(typeSubtracted) == IS_NULL) {
			out = false;
			return true;
		}
		return pt_type_op_bool(Z_OBJ_P(subtracted), PT_OP_EQUALS, 1, typeSubtracted, out);
	}

	/* $level->handle(): 'mixed' for the type-only and value levels, 'mixed'
	 * and the described subtracted type for the precise level,
	 * 'strict-mixed' and the described subtracted type for the cache level;
	 * UNDEF = pending exception */
	zv::Val describe(zval *level) const
	{
		pt_verbosity_case which;
		if (UNEXPECTED(!pt_type_verbosity_case(level, which))) return zv::Val();
		if (which == PT_VERBOSITY_TYPE_ONLY || which == PT_VERBOSITY_VALUE) return zv::Val::string("mixed", sizeof("mixed") - 1);

		/* $this->describeSubtractedType($this->subtractedType, $level) */
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		zv::Val subtractedDescription;
		if (EXPECTED(pt_type_method_is(self, PT_LC("describesubtractedtype"), pt_type_trait_substractable_describe_subtracted_type))) {
			subtractedDescription = pt_type_describe_subtracted_type(subtracted, level);
		} else {
			zv::Args args{subtracted, level};
			subtractedDescription = pt_type_call(self, PT_LC("describesubtractedtype"), 2, args);
		}
		if (UNEXPECTED(subtractedDescription.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(subtractedDescription.raw()).isString())) {
			zend_type_error("phpstan_turbo: describeSubtractedType() must return string");
			return zv::Val();
		}
		smart_str description = {NULL, 0};
		if (which == PT_VERBOSITY_CACHE) {
			smart_str_appendl(&description, "strict-mixed", sizeof("strict-mixed") - 1);
		} else {
			smart_str_appendl(&description, "mixed", sizeof("mixed") - 1);
		}
		smart_str_append(&description, zv::Ref(subtractedDescription.raw()).asString());
		smart_str_0(&description);
		return zv::Val::adoptString(description.s);
	}

	/* new ErrorType() */
	static zv::Val error() { return pt_type_new_error_type(); }

	/* new BooleanType() */
	static zv::Val boolean()
	{
		return pt_val_of<pt_boolean_type_new>();
	}

	/* new ConstantBooleanType(false) */
	static zv::Val toGetClassResultType()
	{
		zval result;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&result, false))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* new ClassNameToObjectTypeResult(new MixedType(), false) */
	static zv::Val toObjectTypeForInstanceofCheck()
	{
		zv::Val mixed = pt_type_new_mixed_type();
		if (UNEXPECTED(mixed.isUndef())) return zv::Val();
		return classNameToObjectTypeResult(std::move(mixed));
	}

	/* new ClassNameToObjectTypeResult(new UnionType([new ObjectWithoutClassType(),
	 * new ClassStringType()]), false) when strings are allowed, of an
	 * ObjectWithoutClassType alone otherwise; UNDEF = pending exception */
	static zv::Val toObjectTypeForIsACheck(bool allowString)
	{
		zv::Val objectWithoutClass = pt_type_new_object_without_class_type();
		if (UNEXPECTED(objectWithoutClass.isUndef())) return zv::Val();
		if (!allowString) return classNameToObjectTypeResult(std::move(objectWithoutClass));
		zval classString;
		if (UNEXPECTED(!pt_class_string_type_new(&classString))) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(objectWithoutClass));
		types.push(zv::Val::adopt(classString));
		zv::Val unionType = pt_type_new_union(std::move(types));
		if (UNEXPECTED(unionType.isUndef())) return zv::Val();
		return classNameToObjectTypeResult(std::move(unionType));
	}

	/* TemplateTypeMap::createEmpty() */
	static zv::Val inferTemplateTypes() { return pt_type_template_type_map_empty(); }

	/* new IdentifierTypeNode('mixed') */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("mixed", 5);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

private:
	zend_object *self;

	/* `$type instanceof self || ($type instanceof MixedType && !$type
	 * instanceof TemplateMixedType)`; false = pending exception */
	[[nodiscard]] static bool isStrictOrPlainMixed(zval *type, bool &out)
	{
		zend_class_entry *ce = Z_OBJCE_P(type);
		if (instanceof_function(ce, pt_ce_strict_mixed_type)) {
			out = true;
			return true;
		}
		if (!instanceof_function(ce, pt_ce_mixed_type)) {
			out = false;
			return true;
		}
		bool isTemplateMixed;
		if (UNEXPECTED(!pt_type_instanceof_ce(type, pt_ce_template_mixed_type, isTemplateMixed))) return false;
		out = !isTemplateMixed;
		return true;
	}

	/* new ClassNameToObjectTypeResult($type, false) */
	static zv::Val classNameToObjectTypeResult(zv::Val type)
	{
		zv::Args args{type.raw(), false};
		return pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args);
	}
};

} // namespace phpstanturbo

using phpstanturbo::StrictMixedType;

#define PT_THIS StrictMixedType(Z_OBJ_P(ZEND_THIS))

void pt_strict_mixed_type_construct(zend_object *self, zval *subtractedType)
{
	StrictMixedType(self).construct(subtractedType);
}

/* {{{ engine ABI glue: parameter parsing + registration */

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL smtEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL smtEmptyArray1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL smtNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL smtNo1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_TRINARY(PT_TRI_NO);
}

/* new ErrorType() */
static void ZEND_FASTCALL smtError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(StrictMixedType::error());
}

static void ZEND_FASTCALL smtError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(StrictMixedType::error());
}

static void ZEND_FASTCALL smtError2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	PT_RETURN_VAL(StrictMixedType::error());
}

/* $this */
static void ZEND_FASTCALL smtThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL smtThis1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL smtShouldNotHappen1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	pt_throw_should_not_happen();
	RETURN_THROWS();
}

static void ZEND_FASTCALL smtShouldNotHappen2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	pt_throw_should_not_happen();
	RETURN_THROWS();
}

PT_MINIT_REGISTRATION(pt_register_strict_mixed_type)
{
	reg::Class cls("PHPStan\\Type\\StrictMixedType");
	ptdecl::StrictMixedType::declareClass(cls);
	ptdecl::StrictMixedType::declareProperties(cls);

	cls.method(sigs::getReferencedClasses, smtEmptyArray0);
	cls.op(PT_OP_GET_REFERENCED_CLASSES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassNames, smtEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassReflections, smtEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_REFLECTIONS, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getConstantStrings, smtEmptyArray0);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *subtractedType = NULL;
		if (!zp::parse<zp::Opt<zp::TypeObjOrNull>>(execute_data, subtractedType)) RETURN_THROWS();
		PT_THIS.construct(subtractedType);
	});

	cls.method(sigs::getSubtractedType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *subtracted = PT_THIS.subtractedType();
		if (UNEXPECTED(subtracted == NULL)) RETURN_THROWS();
		RETURN_COPY(subtracted);
	});
	cls.op(PT_OP_GET_SUBTRACTED_TYPE, PT_OP_LAMBDA { zval *subtracted = StrictMixedType(self).subtractedType(); return subtracted == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(subtracted)); });

	cls.method(sigs::accepts, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		bool strictTypes;
		if (!zp::parse<zp::TypeObj, zp::Bool>(execute_data, type, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.accepts(type));
	});
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return StrictMixedType(self).accepts(argv); });

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(StrictMixedType::isAcceptedBy(acceptingType));
	});

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(StrictMixedType::isSuperTypeOf());
	});
	cls.op(PT_OP_IS_SUPER_TYPE_OF, PT_OP_LAMBDA { return StrictMixedType::isSuperTypeOf(); });

	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherType;
		if (!zp::parse<zp::Obj>(execute_data, otherType)) RETURN_THROWS();
		PT_RETURN_VAL(StrictMixedType::isSubTypeOf(otherType));
	});
	cls.op(PT_OP_IS_SUB_TYPE_OF, PT_OP_LAMBDA { return StrictMixedType::isSubTypeOf(argv); });

	cls.method<&StrictMixedType::equals, zp::TypeObj>(sigs::equals);
	cls.op<PT_OP_EQUALS, &StrictMixedType::equals>();

	cls.method<&StrictMixedType::describe, zp::Obj>(sigs::describe);
	cls.op<PT_OP_DESCRIBE, &StrictMixedType::describe>();

	cls.method(sigs::getTemplateType, smtError2);
	cls.method(sigs::isObject, smtNo0);
	cls.method(sigs::getClassStringType, smtError0);
	cls.method(sigs::isEnum, smtNo0);
	cls.method(sigs::canAccessProperties, smtNo0);
	cls.method(sigs::hasProperty, smtNo1);
	cls.method(sigs::getProperty, smtShouldNotHappen2);
	cls.method(sigs::getUnresolvedPropertyPrototype, smtShouldNotHappen2);
	cls.method(sigs::hasInstanceProperty, smtNo1);
	cls.op(PT_OP_HAS_INSTANCE_PROPERTY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getInstanceProperty, smtShouldNotHappen2);
	cls.method(sigs::getUnresolvedInstancePropertyPrototype, smtShouldNotHappen2);
	cls.op(PT_OP_GET_UNRESOLVED_INSTANCE_PROPERTY_PROTOTYPE, PT_OP_LAMBDA { pt_throw_should_not_happen(); return zv::Val(); });
	cls.method(sigs::hasStaticProperty, smtNo1);
	cls.method(sigs::getStaticProperty, smtShouldNotHappen2);
	cls.method(sigs::getUnresolvedStaticPropertyPrototype, smtShouldNotHappen2);
	cls.method(sigs::canCallMethods, smtNo0);
	cls.method(sigs::hasMethod, smtNo1);
	cls.op(PT_OP_HAS_METHOD, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getMethod, smtShouldNotHappen2);
	cls.method(sigs::getUnresolvedMethodPrototype, smtShouldNotHappen2);
	cls.op(PT_OP_GET_UNRESOLVED_METHOD_PROTOTYPE, PT_OP_LAMBDA { pt_throw_should_not_happen(); return zv::Val(); });
	cls.method(sigs::canAccessConstants, smtNo0);
	cls.method(sigs::hasConstant, smtNo1);
	cls.method(sigs::getConstant, smtShouldNotHappen1);
	cls.method(sigs::isIterable, smtNo0);
	cls.method(sigs::isIterableAtLeastOnce, smtNo0);
	cls.op(PT_OP_IS_ITERABLE_AT_LEAST_ONCE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getIterableKeyType, smtThis0);
	cls.op(PT_OP_GET_ITERABLE_KEY_TYPE, PT_OP_LAMBDA { return pt_op_this(self); });
	cls.method(sigs::getIterableValueType, smtThis0);
	cls.op(PT_OP_GET_ITERABLE_VALUE_TYPE, PT_OP_LAMBDA { return pt_op_this(self); });
	cls.method(sigs::isNull, smtNo0);
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isConstantValue, smtNo0);
	cls.method(sigs::isConstantScalarValue, smtNo0);
	cls.op(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getConstantScalarTypes, smtEmptyArray0);
	cls.method(sigs::getConstantScalarValues, smtEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::isTrue, smtNo0);
	cls.method(sigs::isFalse, smtNo0);
	cls.method(sigs::isBoolean, smtNo0);
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isFloat, smtNo0);
	cls.op(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isInteger, smtNo0);
	cls.op(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isString, smtNo0);
	cls.op(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isNumericString, smtNo0);
	cls.method(sigs::isDecimalIntegerString, smtNo0);
	cls.method(sigs::isNonEmptyString, smtNo0);
	cls.method(sigs::isNonFalsyString, smtNo0);
	cls.method(sigs::isLiteralString, smtNo0);
	cls.method(sigs::isLowercaseString, smtNo0);
	cls.method(sigs::isUppercaseString, smtNo0);
	cls.method(sigs::isClassString, smtNo0);
	cls.method(sigs::getClassStringObjectType, smtError0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, smtError0);
	cls.method(sigs::isVoid, smtNo0);
	cls.op(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isScalar, smtNo0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(StrictMixedType::boolean());
	});

	cls.method(sigs::isOffsetAccessible, smtNo0);
	cls.method(sigs::isOffsetAccessLegal, smtNo0);
	cls.method(sigs::hasOffsetValueType, smtNo1);
	cls.op(PT_OP_HAS_OFFSET_VALUE_TYPE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getOffsetValueType, smtError1);
	cls.op(PT_OP_GET_OFFSET_VALUE_TYPE, PT_OP_LAMBDA { return StrictMixedType::error(); });
	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 3);
		PT_RETURN_VAL(StrictMixedType::error());
	});
	cls.method(sigs::setExistingOffsetValueType, smtError2);
	cls.method(sigs::unsetOffset, smtError1);
	cls.method(sigs::isCallable, smtNo0);
	cls.op(PT_OP_IS_CALLABLE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getCallableParametersAcceptors, smtEmptyArray1);
	cls.method(sigs::isCloneable, smtNo0);

	cls.method(sigs::toBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(StrictMixedType::boolean());
	});

	cls.method(sigs::toNumber, smtError0);
	cls.method(sigs::toBitwiseNotType, smtError0);

	cls.method(sigs::toGetClassResultType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(StrictMixedType::toGetClassResultType());
	});

	cls.method(sigs::toClassConstantType, smtError1);

	cls.method(sigs::toObjectTypeForInstanceofCheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(StrictMixedType::toObjectTypeForInstanceofCheck());
	});

	cls.method(sigs::toObjectTypeForIsACheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *objectOrClassType;
		bool allowString, allowSameClass;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Bool>(execute_data, objectOrClassType, allowString, allowSameClass)) RETURN_THROWS();
		PT_RETURN_VAL(StrictMixedType::toObjectTypeForIsACheck(allowString));
	});

	cls.method(sigs::toAbsoluteNumber, smtError0);
	cls.method(sigs::toInteger, smtError0);
	cls.method(sigs::toFloat, smtError0);
	cls.method(sigs::toString, smtError0);
	cls.method(sigs::toArray, smtError0);
	cls.method(sigs::toArrayKey, smtError0);
	cls.op(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return StrictMixedType::error(); });
	cls.method(sigs::toCoercedArgumentType, smtThis1);

	cls.method(sigs::inferTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(StrictMixedType::inferTemplateTypes());
	});

	cls.method(sigs::getReferencedTemplateTypes, smtEmptyArray1);
	cls.op(PT_OP_GET_REFERENCED_TEMPLATE_TYPES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getEnumCases, smtEmptyArray0);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_NULL();
	});
	cls.op(PT_OP_GET_ENUM_CASE_OBJECT, PT_OP_LAMBDA { return zv::Val::null(); });
	cls.method("traverse", reg::Public, 1, { reg::callableArg("cb") }, pt_type_identity_traverse_handler(), &ptret::type);
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_traverse_identity(self); });
	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
	});
	cls.method(sigs::exponentiate, smtError1);
	cls.method(sigs::getFiniteTypes, smtEmptyArray0);

	cls.method(sigs::toPhpDocNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(StrictMixedType::toPhpDocNode());
	});

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});
	cls.op(PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, PT_OP_LAMBDA { return zv::Val::boolean(false); });

	cls.method(sigs::toNativePhpTypeNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *position;
		if (!zp::parse<zp::Obj>(execute_data, position)) RETURN_THROWS();
		/* $position->isMixedAllowed() ? new Identifier('mixed') : null */
		PT_RETURN_VAL(pt_type_native_type_node(position, PT_LC("ismixedallowed"), PT_CLASS_IDENTIFIER, PT_LC("mixed")));
	});

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares */
	ptdecl::StrictMixedType::registerTraits(cls);

	cls.shadow(&pt_ce_strict_mixed_type);
}

/* }}} */
