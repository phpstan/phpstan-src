/*
 * PHPStanTurbo\HasOffsetType — native implementation of
 * PHPStan\Type\Accessory\HasOffsetType.
 *
 * State is the twin's promoted `private
 * ConstantStringType|ConstantIntegerType $offsetType`, a declared
 * union-typed property slot (IS_PROP_UNINIT until the constructor writes
 * it), so the std object handlers do GC/clone.
 *
 * The `$this->method()` calls the twin makes (equals(), isSubTypeOf(),
 * getKeysArray()) go through the object's class entry — a subclass may have
 * overridden them — with a direct C++ call when the object is exactly a
 * HasOffsetType. The private slot of another HasOffsetType
 * (`$type->offsetType`) is read directly, as the twin does from inside the
 * class.
 */

#include "TypeTraits.h"
#include "generated/HasOffsetType.h"

namespace slots = ptdecl::HasOffsetType::slot;
namespace sigs = ptdecl::HasOffsetType::sig;

zend_class_entry *pt_ce_has_offset_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Accessory\HasOffsetType. State lives in the PHP
 * object's $offsetType. */
class HasOffsetType
{
public:
	explicit HasOffsetType(zend_object *self) : self(self) {}

	/* the twin's `ConstantStringType|ConstantIntegerType` parameter check;
	 * false with a TypeError pending */
	static bool checkOffsetType(zval *value, int argNumber)
	{
		if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT || (!instanceof_function(Z_OBJCE_P(value), pt_ce_constant_string_type) && !instanceof_function(Z_OBJCE_P(value), pt_ce_constant_integer_type)))) {
			zend_argument_type_error((uint32_t) argNumber, "must be of type %s, %s given", ptcls::constantStringOrIntegerType, zend_zval_value_name(value));
			return false;
		}
		return true;
	}

	/* __construct(private ConstantStringType|ConstantIntegerType $offsetType) */
	void construct(zval *offsetType)
	{
		zv::ObjRef(self).propAtWrite(slots::offsetType, zv::Val::copyOf(zv::Ref(offsetType)));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, slots::offsetType)) = 0; /* no longer IS_PROP_UNINIT */
	}

	/* new self($offsetType) — exactly the class, as the twin's `new self`
	 * spells it; UNDEF = pending exception */
	static zv::Val create(zval *offsetType)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_has_offset_type) != SUCCESS)) return zv::Val();
		HasOffsetType(Z_OBJ(object)).construct(offsetType);
		return zv::Val::adopt(object);
	}

	/* $this->offsetType (borrowed); NULL with an Error pending when the
	 * constructor never ran — the twin's typed-property read raises the
	 * same */
	[[nodiscard]] zval *offsetType() const { return offsetTypeOf(self); }

	static zval *offsetTypeOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::offsetType);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_OBJECT)) {
			zend_throw_error(NULL, "Typed property %s::$offsetType must not be accessed before initialization", ZSTR_VAL(pt_ce_has_offset_type->name));
			return NULL;
		}
		return slot;
	}

	zv::Val getOffsetType() const
	{
		zval *offset = offsetType();
		return offset == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(offset));
	}

	/* the CompoundType callback; else offset-accessible-and-has-offset as
	 * a fresh result; UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}
		zend_long value = accessibleAndHasOffset(type);
		if (UNEXPECTED(value < 0)) return zv::Val();
		return pt_type_new_accepts_result(value);
	}

	/* yes for an equal type, else offset-accessible-and-has-offset; UNDEF
	 * = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool equal;
		if (UNEXPECTED(!thisEquals(type, equal))) return zv::Val();
		if (equal) return pt_type_is_super_type_of_result(PT_TRI_YES);
		zend_long value = accessibleAndHasOffset(type);
		if (UNEXPECTED(value < 0)) return zv::Val();
		return pt_type_new_is_super_type_of_result(value);
	}

	/* the union/intersection callback; else the other type's
	 * offset-accessible-and-has-offset verdict, and'ed with maybe unless
	 * it is a HasOffsetType; UNDEF = pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		bool unionOrIntersection;
		if (UNEXPECTED(!isUnionOrIntersection(otherType, unionOrIntersection))) return zv::Val();
		if (unionOrIntersection) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(otherType), PT_OP_IS_SUPER_TYPE_OF, 1, &selfZv);
		}
		zend_long value = accessibleAndHasOffset(otherType);
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (!instanceof_function(Z_OBJCE_P(otherType), pt_ce_has_offset_type)) {
			value = pt_trinary_and(value, PT_TRI_MAYBE);
		}
		return pt_type_new_is_super_type_of_result(value);
	}

	/* $this->isSubTypeOf($acceptingType)->toAcceptsResult() */
	zv::Val isAcceptedBy(zval *acceptingType) const
	{
		return pt_type_sub_type_to_accepts_result(isExact() ? isSubTypeOf(acceptingType) : pt_type_op(self, PT_OP_IS_SUB_TYPE_OF, 1, acceptingType));
	}

	/* $type instanceof self && $this->offsetType->equals($type->offsetType);
	 * false = pending exception */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_has_offset_type)) {
			out = false;
			return true;
		}
		zval *offset = offsetType();
		zval *otherOffset = offset != NULL ? offsetTypeOf(Z_OBJ_P(type)) : NULL;
		if (UNEXPECTED(otherOffset == NULL)) return false;
		return pt_type_op_bool(Z_OBJ_P(offset), PT_OP_EQUALS, 1, otherOffset, out);
	}

	/* sprintf('hasOffset(%s)', $this->offsetType->describe($level)) */
	zv::Val describe(zval *level) const
	{
		zval *offset = offsetType();
		if (UNEXPECTED(offset == NULL)) return zv::Val();
		zv::Val description = pt_type_op(Z_OBJ_P(offset), PT_OP_DESCRIBE, 1, level);
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(description.raw()).isString())) {
			zend_type_error("phpstan_turbo: describe() must return string");
			return zv::Val();
		}
		return zv::Val::adoptString(zend_strpprintf(0, "hasOffset(%s)", ZSTR_VAL(zv::Ref(description.raw()).asString())));
	}

	/* yes for the constant offset itself, maybe otherwise; -1 = pending
	 * exception */
	[[nodiscard]] zend_long hasOffsetValueType(zval *offsetTypeArg) const
	{
		zend_long isConstantScalar = pt_type_op_trinary(Z_OBJ_P(offsetTypeArg), PT_OP_IS_CONSTANT_SCALAR_VALUE, 0, NULL);
		if (UNEXPECTED(isConstantScalar < 0)) return -1;
		if (isConstantScalar == PT_TRI_YES) {
			zval *offset = offsetType();
			if (UNEXPECTED(offset == NULL)) return -1;
			zv::Val equal = pt_type_op(Z_OBJ_P(offsetTypeArg), PT_OP_EQUALS, 1, offset);
			if (UNEXPECTED(equal.isUndef())) return -1;
			if (zend_is_true(equal.raw())) return PT_TRI_YES;
		}
		return PT_TRI_MAYBE;
	}

	/* new ErrorType() when the offset is unset, $this otherwise; UNDEF =
	 * pending exception */
	zv::Val unsetOffset(zval *offsetTypeArg) const
	{
		zend_long covers = offsetIsSuperTypeOf(offsetTypeArg);
		if (UNEXPECTED(covers < 0)) return zv::Val();
		if (covers == PT_TRI_YES) return pt_type_new_error_type();
		return thisValue();
	}

	/* $this when the other array has the offset, mixed otherwise; UNDEF =
	 * pending exception */
	zv::Val intersectKeyArray(zval *otherArraysType) const
	{
		zval *offset = offsetType();
		if (UNEXPECTED(offset == NULL)) return zv::Val();
		zend_long has = pt_type_call_trinary(Z_OBJ_P(otherArraysType), PT_LC("hasoffsetvaluetype"), 1, offset);
		if (UNEXPECTED(has < 0)) return zv::Val();
		if (has == PT_TRI_YES) return thisValue();
		return pt_type_new_mixed_type();
	}

	/* $this with the keys preserved, a non-empty array otherwise; UNDEF =
	 * pending exception */
	zv::Val reverseArray(zval *preserveKeys) const
	{
		zend_long preserve = pt_type_trinary_value(preserveKeys);
		if (UNEXPECTED(preserve < 0)) return zv::Val();
		if (preserve == PT_TRI_YES) return thisValue();
		return nonEmptyArray();
	}

	/* a slice from the offset with a null or positive length keeps the
	 * offset (as $this & non-empty with the keys preserved, non-empty
	 * otherwise); mixed otherwise; UNDEF = pending exception */
	zv::Val sliceArray(zval *offsetTypeArg, zval *lengthType, zval *preserveKeys) const
	{
		zend_long covers = offsetIsSuperTypeOf(offsetTypeArg);
		if (UNEXPECTED(covers < 0)) return zv::Val();
		if (covers == PT_TRI_YES) {
			zend_long lengthIsNull = pt_type_op_trinary(Z_OBJ_P(lengthType), PT_OP_IS_NULL, 0, NULL);
			if (UNEXPECTED(lengthIsNull < 0)) return zv::Val();
			bool lengthFits = lengthIsNull == PT_TRI_YES;
			if (!lengthFits) {
				zv::Val positive = pt_integer_range_from_interval(NullableLong::of(1), NullableLong::null(), 0);
				if (UNEXPECTED(positive.isUndef())) return zv::Val();
				zend_long isPositive = resultTrinaryOf(positive.raw(), PT_LC("issupertypeof"), lengthType);
				if (UNEXPECTED(isPositive < 0)) return zv::Val();
				lengthFits = isPositive == PT_TRI_YES;
			}
			if (lengthFits) {
				zend_long preserve = pt_type_trinary_value(preserveKeys);
				if (UNEXPECTED(preserve < 0)) return zv::Val();
				if (preserve == PT_TRI_YES) return intersectedWithNonEmpty();
				return nonEmptyArray();
			}
		}
		return pt_type_new_mixed_type();
	}

	/* $this for a zero length, mixed otherwise; UNDEF = pending exception */
	zv::Val spliceArray(zval *lengthType) const
	{
		zv::Val zero = pt_type_new_constant_integer(0);
		if (UNEXPECTED(zero.isUndef())) return zv::Val();
		zend_long lengthIsZero = resultTrinaryOf(zero.raw(), PT_LC("issupertypeof"), lengthType);
		if (UNEXPECTED(lengthIsZero < 0)) return zv::Val();
		if (lengthIsZero == PT_TRI_YES) return thisValue();
		return pt_type_new_mixed_type();
	}

	/* a string offset case-folded (mixed for an unknown case), an int
	 * offset unchanged; UNDEF = pending exception */
	zv::Val changeKeyCaseArray(NullableLong caseArg) const
	{
		zval *offset = offsetType();
		if (UNEXPECTED(offset == NULL)) return zv::Val();
		if (!instanceof_function(Z_OBJCE_P(offset), pt_ce_constant_string_type)) return thisValue();
		zv::Val value = pt_constant_string_get_value(Z_OBJ_P(offset));
		if (UNEXPECTED(value.isUndef())) return zv::Val();
		zend_string *v = zv::Ref(value.raw()).asString();
		if (!caseArg.isNull && caseArg.value == 0) { /* CASE_LOWER */
			return createWithConstantString(zend_string_tolower(v));
		}
		if (!caseArg.isNull && caseArg.value == 1) { /* CASE_UPPER */
			return createWithConstantString(zend_string_toupper(v));
		}
		/* Unknown case → could be either fold; the accessory weakens to
		 * "no specific offset known". */
		return pt_type_new_mixed_type();
	}

	/* no for a string offset, maybe otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isList() const
	{
		zval *offset = offsetType();
		if (UNEXPECTED(offset == NULL)) return -1;
		zend_long isString = pt_type_op_trinary(Z_OBJ_P(offset), PT_OP_IS_STRING, 0, NULL);
		if (UNEXPECTED(isString < 0)) return -1;
		return isString == PT_TRI_YES ? PT_TRI_NO : PT_TRI_MAYBE;
	}

	/* new NonEmptyArrayType() */
	static zv::Val nonEmptyArray()
	{
		return pt_val_of<pt_non_empty_array_type_new>();
	}

	/* new ObjectWithoutClassType() */
	static zv::Val objectWithoutClass() { return pt_type_new_object_without_class_type(); }

	/* new UnionType([new ArrayType(new MixedType(), new MixedType()), new ObjectType(ArrayAccess::class)]) */
	static zv::Val getDefaultBaseType()
	{
		zv::Val keyType = pt_type_new_mixed_type();
		zv::Val itemType = pt_type_new_mixed_type();
		if (UNEXPECTED(keyType.isUndef() || itemType.isUndef())) return zv::Val();
		zval arrayRaw;
		if (UNEXPECTED(!pt_array_type_new(&arrayRaw, keyType.raw(), itemType.raw()))) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(zv::Val::adopt(arrayRaw));
		zv::Val className = zv::Val::string("ArrayAccess", sizeof("ArrayAccess") - 1);
		zv::Val arrayAccess = pt_type_new_object_type(className.raw());
		if (UNEXPECTED(arrayAccess.isUndef())) return zv::Val();
		types.push(std::move(arrayAccess));
		return pt_type_new_union(std::move(types));
	}

	/* new IdentifierTypeNode('') — no PHPDoc representation */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("", 0);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

	/* $this->offsetType->hasTemplateOrLateResolvableType(); false = pending
	 * exception */
	[[nodiscard]] bool hasTemplateOrLateResolvableType(bool &out) const
	{
		zval *offset = offsetType();
		if (UNEXPECTED(offset == NULL)) return false;
		return pt_type_op_bool(Z_OBJ_P(offset), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL, out);
	}

private:
	zend_object *self;

	bool isExact() const { return self->ce == pt_ce_has_offset_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* $this->equals($type) — through the object's class; false = pending
	 * exception */
	[[nodiscard]] bool thisEquals(zval *type, bool &out) const
	{
		if (EXPECTED(isExact())) return equals(type, out);
		return pt_type_op_bool(self, PT_OP_EQUALS, 1, type, out);
	}

	/* TrinaryLogic::and(): the minimum */

	/* $object->method($arg)'s result trinary; -1 = pending exception */
	[[nodiscard]] static zend_long resultTrinaryOf(zval *object, const char *lcname, size_t len, zval *arg)
	{
		zv::Val result = pt_type_call(Z_OBJ_P(object), lcname, len, 1, arg);
		if (UNEXPECTED(result.isUndef())) return -1;
		return pt_type_result_trinary(result.raw());
	}

	/* $type->isOffsetAccessible()->and($type->hasOffsetValueType($this->offsetType));
	 * -1 = pending exception */
	[[nodiscard]] zend_long accessibleAndHasOffset(zval *type) const
	{
		zend_long accessible = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isoffsetaccessible"), 0, NULL);
		if (UNEXPECTED(accessible < 0)) return -1;
		zval *offset = offsetType();
		if (UNEXPECTED(offset == NULL)) return -1;
		zend_long has = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("hasoffsetvaluetype"), 1, offset);
		if (UNEXPECTED(has < 0)) return -1;
		return pt_trinary_and(accessible, has);
	}

	/* $this->offsetType->isSuperTypeOf($type)'s trinary; -1 = pending
	 * exception */
	[[nodiscard]] zend_long offsetIsSuperTypeOf(zval *type) const
	{
		zval *offset = offsetType();
		if (UNEXPECTED(offset == NULL)) return -1;
		return resultTrinaryOf(offset, PT_LC("issupertypeof"), type);
	}

	/* $type instanceof UnionType || $type instanceof IntersectionType;
	 * false = pending exception */
	[[nodiscard]] static bool isUnionOrIntersection(zval *type, bool &out)
	{
		if (UNEXPECTED(!pt_union_type_instanceof(type, out))) return false;
		if (out) return true;
		return pt_intersection_type_instanceof(type, out);
	}

	/* TypeCombinator::intersect($this, new NonEmptyArrayType()) */
	zv::Val intersectedWithNonEmpty() const
	{
		zv::Val nonEmpty = nonEmptyArray();
		if (UNEXPECTED(nonEmpty.isUndef())) return zv::Val();
		zv::Args args{self, nonEmpty.raw()};
		return pt_type_combinator_call(PT_LC("intersect"), 2, args);
	}

	/* new self(new ConstantStringType($owned)) */
	static zv::Val createWithConstantString(zend_string *owned)
	{
		zval constantString;
		bool created = pt_constant_string_type_new(&constantString, owned);
		zend_string_release(owned);
		if (UNEXPECTED(!created)) return zv::Val();
		zv::Val offset = zv::Val::adopt(constantString);
		return create(offset.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::HasOffsetType;
using phpstanturbo::NullableLong;

bool pt_has_offset_type_new(zval *out, zval *offsetType)
{
	if (UNEXPECTED(!HasOffsetType::checkOffsetType(offsetType, 1))) return false;
	return pt_val_into(HasOffsetType::create(offsetType), out);
}

zv::Val pt_has_offset_type_get_offset_type(zend_object *object)
{
	if (EXPECTED(object->ce == pt_ce_has_offset_type)) return HasOffsetType(object).getOffsetType();
	return pt_type_call(object, PT_LC("getoffsettype"), 0, NULL);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS HasOffsetType(Z_OBJ_P(ZEND_THIS))

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL hotEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL hotNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL hotYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL hotThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL hotThis1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL hotThis2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL hotError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL hotError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL hotMixed0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_mixed_type());
}

static void ZEND_FASTCALL hotMixed1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_mixed_type());
}

static void ZEND_FASTCALL hotNonEmpty0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(HasOffsetType::nonEmptyArray());
}

static void ZEND_FASTCALL hotNonEmpty1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(HasOffsetType::nonEmptyArray());
}

static void ZEND_FASTCALL hotNonEmpty2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	PT_RETURN_VAL(HasOffsetType::nonEmptyArray());
}

static void ZEND_FASTCALL hotObjectWithoutClass0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(HasOffsetType::objectWithoutClass());
}

/* (Type $type) → a Type */
static void pt_hot_one_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (HasOffsetType::*method)(zval *) const)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(type));
}

void pt_register_has_offset_type()
{
	reg::Class cls("PHPStan\\Type\\Accessory\\HasOffsetType");
	ptdecl::HasOffsetType::declareClass(cls);
	/* "offsetType" must stay the first declared property (slots::offsetType) */
	ptdecl::HasOffsetType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Zval>(execute_data, offsetType)) RETURN_THROWS();
		if (UNEXPECTED(!HasOffsetType::checkOffsetType(offsetType, 1))) RETURN_THROWS();
		PT_THIS.construct(offsetType);
	});

	cls.method<&HasOffsetType::getOffsetType>(sigs::getOffsetType);

	cls.method(sigs::getReferencedClasses, hotEmptyArray0);
	cls.method(sigs::getObjectClassNames, hotEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassReflections, hotEmptyArray0);

	cls.method<&HasOffsetType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return HasOffsetType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hot_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasOffsetType::isSuperTypeOf);
	});
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &HasOffsetType::isSuperTypeOf>();

	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hot_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasOffsetType::isSubTypeOf);
	});
	cls.op<PT_OP_IS_SUB_TYPE_OF, &HasOffsetType::isSubTypeOf>();

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method<&HasOffsetType::equals, zp::Obj>(sigs::equals);
	cls.op<PT_OP_EQUALS, &HasOffsetType::equals>();

	cls.method<&HasOffsetType::describe, zp::Obj>(sigs::describe);
	cls.op<PT_OP_DESCRIBE, &HasOffsetType::describe>();

	cls.method(sigs::isOffsetAccessible, hotYes0);
	cls.method(sigs::isOffsetAccessLegal, hotYes0);

	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.hasOffsetValueType(offsetType));
	});

	cls.method(sigs::getOffsetValueType, hotMixed1);
	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 3);
		RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
	});
	cls.method(sigs::setExistingOffsetValueType, hotThis2);

	cls.method(sigs::unsetOffset, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hot_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasOffsetType::unsetOffset);
	});

	cls.method(sigs::chunkArray, hotNonEmpty2);
	cls.method(sigs::fillKeysArray, hotNonEmpty1);

	cls.method(sigs::intersectKeyArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hot_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasOffsetType::intersectKeyArray);
	});

	cls.method(sigs::reverseArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hot_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasOffsetType::reverseArray);
	});

	cls.method(sigs::shuffleArray, hotNonEmpty0);

	cls.method<&HasOffsetType::sliceArray, zp::Obj, zp::Obj, zp::Obj>(sigs::sliceArray);

	cls.method(sigs::spliceArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *lengthType, *replacementType;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, offsetType, lengthType, replacementType)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.spliceArray(lengthType));
	});

	cls.method(sigs::truncateListToSize, hotThis1);
	cls.method(sigs::makeListMaybe, hotThis0);
	cls.method(sigs::mapValueType, hotThis1);
	cls.method(sigs::mapKeyType, hotThis1);
	cls.method(sigs::makeAllArrayKeysOptional, hotMixed0);

	cls.method(sigs::changeKeyCaseArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long caseValue = 0;
		bool caseIsNull = false;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_LONG_OR_NULL(caseValue, caseIsNull)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.changeKeyCaseArray(caseIsNull ? NullableLong::null() : NullableLong::of(caseValue)));
	});

	cls.method(sigs::filterArrayRemovingFalsey, hotMixed0);
	cls.method(sigs::isIterableAtLeastOnce, hotYes0);
	cls.op(PT_OP_IS_ITERABLE_AT_LEAST_ONCE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_YES); });

	cls.method(sigs::isList, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isList());
	});
	cls.op<PT_OP_IS_LIST, &HasOffsetType::isList>();

	cls.method(sigs::isNull, hotNo0);
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isConstantValue, hotNo0);
	cls.method(sigs::isConstantScalarValue, hotNo0);
	cls.op(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getConstantScalarTypes, hotEmptyArray0);
	cls.method(sigs::getConstantScalarValues, hotEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::isTrue, hotNo0);
	cls.method(sigs::isFalse, hotNo0);
	cls.method(sigs::isBoolean, hotNo0);
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isFloat, hotNo0);
	cls.op(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isInteger, hotNo0);
	cls.op(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getClassStringObjectType, hotObjectWithoutClass0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, hotObjectWithoutClass0);
	cls.method(sigs::isVoid, hotNo0);
	cls.op(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* new BooleanType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.method(sigs::getKeysArrayFiltered, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* $this->getKeysArray() — through the object's class */
		PT_RETURN_VAL(pt_type_call(Z_OBJ_P(ZEND_THIS), PT_LC("getkeysarray"), 0, NULL));
	});

	cls.method(sigs::getKeysArray, hotNonEmpty0);
	cls.method(sigs::getValuesArray, hotNonEmpty0);
	cls.method(sigs::toNumber, hotError0);
	cls.method(sigs::toBitwiseNotType, hotError0);
	cls.method(sigs::toAbsoluteNumber, hotError0);
	cls.method(sigs::toInteger, hotError0);
	cls.method(sigs::toFloat, hotError0);
	cls.method(sigs::toString, hotError0);
	cls.method(sigs::toArray, hotMixed0);
	cls.method(sigs::toArrayKey, hotError0);
	cls.op(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return pt_type_new_error_type(); });
	cls.method(sigs::toCoercedArgumentType, hotThis1);
	cls.method(sigs::getEnumCases, hotEmptyArray0);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_NULL();
	});
	cls.method("traverse", reg::Public, 1, { reg::callableArg("cb") }, pt_type_identity_traverse_handler(), &ptret::type);
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_traverse_identity(self); });
	cls.method(sigs::traverseSimultaneously, hotThis2);
	cls.method(sigs::exponentiate, hotError1);
	cls.method(sigs::getFiniteTypes, hotEmptyArray0);

	cls.method<&HasOffsetType::getDefaultBaseType>(sigs::getDefaultBaseType);

	cls.method<&HasOffsetType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method<&HasOffsetType::hasTemplateOrLateResolvableType>(sigs::hasTemplateOrLateResolvableType);
	cls.op<PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, &HasOffsetType::hasTemplateOrLateResolvableType>();

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares */
	ptdecl::HasOffsetType::registerTraits(cls);

	cls.shadow(&pt_ce_has_offset_type);
}

/* }}} */
