/*
 * PHPStanTurbo\HasOffsetValueType — native implementation of
 * PHPStan\Type\Accessory\HasOffsetValueType.
 *
 * State is the twin's two promoted properties — `private
 * ConstantStringType|ConstantIntegerType $offsetType` and `private Type
 * $valueType` — declared typed property slots (IS_PROP_UNINIT until the
 * constructor writes them) in the twin's order, so the std object handlers
 * do GC/clone.
 *
 * The `$this->method()` calls the twin makes (equals(), isSubTypeOf(),
 * getKeysArray()) go through the object's class entry — a subclass may have
 * overridden them — with a direct C++ call when the object is exactly a
 * HasOffsetValueType. The private slots of another HasOffsetValueType
 * (`$type->offsetType`, `$type->valueType`) are read directly, as the twin
 * does from inside the class.
 */

#include "TypeTraits.h"
#include "generated/HasOffsetValueType.h"

namespace slots = ptdecl::HasOffsetValueType::slot;
namespace sigs = ptdecl::HasOffsetValueType::sig;

zend_class_entry *pt_ce_has_offset_value_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Accessory\HasOffsetValueType. State lives in the
 * PHP object's $offsetType and $valueType. */
class HasOffsetValueType
{
public:
	explicit HasOffsetValueType(zend_object *self) : self(self) {}

	/* the twin's typed parameter checks; false with a TypeError pending */
	static bool checkOffsetType(zval *value, int argNumber)
	{
		if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT || (!instanceof_function(Z_OBJCE_P(value), pt_ce_constant_string_type) && !instanceof_function(Z_OBJCE_P(value), pt_ce_constant_integer_type)))) {
			zend_argument_type_error((uint32_t) argNumber, "must be of type %s, %s given", ptcls::constantStringOrIntegerType, zend_zval_value_name(value));
			return false;
		}
		return true;
	}

	static bool checkValueType(zval *value, int argNumber)
	{
		bool isType;
		if (UNEXPECTED(!pt_type_instanceof(value, PT_CLASS_TYPE, isType))) return false;
		if (UNEXPECTED(!isType)) {
			zend_argument_type_error((uint32_t) argNumber, "must be of type %s, %s given", ptcls::type, zend_zval_value_name(value));
			return false;
		}
		return true;
	}

	/* __construct(private ConstantStringType|ConstantIntegerType $offsetType, private Type $valueType) */
	void construct(zval *offsetType, zval *valueType)
	{
		zv::ObjRef(self).propAtWrite(slots::offsetType, zv::Val::copyOf(zv::Ref(offsetType)));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, slots::offsetType)) = 0; /* no longer IS_PROP_UNINIT */
		zv::ObjRef(self).propAtWrite(slots::valueType, zv::Val::copyOf(zv::Ref(valueType)));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, slots::valueType)) = 0;
	}

	/* new self($offsetType, $valueType) — exactly the class, as the twin's
	 * `new self` spells it, with its typed parameters' checks; UNDEF =
	 * pending exception */
	static zv::Val create(zval *offsetType, zval *valueType)
	{
		if (UNEXPECTED(!checkOffsetType(offsetType, 1) || !checkValueType(valueType, 2))) return zv::Val();
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_has_offset_value_type) != SUCCESS)) return zv::Val();
		HasOffsetValueType(Z_OBJ(object)).construct(offsetType, valueType);
		return zv::Val::adopt(object);
	}

	/* $this->offsetType / $this->valueType (borrowed); NULL with an Error
	 * pending when the constructor never ran — the twin's typed-property
	 * read raises the same */
	[[nodiscard]] zval *offsetType() const { return slotOf(self, slots::offsetType, "offsetType"); }
	zval *valueType() const { return slotOf(self, slots::valueType, "valueType"); }

	static zval *slotOf(zend_object *object, uint32_t slot, const char *name)
	{
		zval *value = OBJ_PROP_NUM(object, slot);
		if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_has_offset_value_type->name), name);
			return NULL;
		}
		return value;
	}

	zv::Val getOffsetType() const
	{
		zval *offset = offsetType();
		return offset == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(offset));
	}

	zv::Val getValueType() const
	{
		zval *value = valueType();
		return value == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(value));
	}

	/* the CompoundType callback; else offset-accessible, has-offset and
	 * the value accepted at the offset, as a fresh result; UNDEF = pending
	 * exception */
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
		/* ->and($this->valueType->accepts($type->getOffsetValueType($this->offsetType), $strictTypes)->result) */
		zv::Val offsetValue = offsetValueTypeOf(type);
		if (UNEXPECTED(offsetValue.isUndef())) return zv::Val();
		zval *ownValue = valueType();
		if (UNEXPECTED(ownValue == NULL)) return zv::Val();
		zv::Args args{offsetValue.raw(), strictTypes};
		zv::Val accepts = pt_type_call(Z_OBJ_P(ownValue), PT_LC("accepts"), 2, args);
		if (UNEXPECTED(accepts.isUndef())) return zv::Val();
		zend_long acceptsValue = pt_type_result_trinary(accepts.raw());
		if (UNEXPECTED(acceptsValue < 0)) return zv::Val();
		return pt_type_new_accepts_result(pt_trinary_and(value, acceptsValue));
	}

	/* yes for an equal type; else offset-accessible-and-has-offset and'ed
	 * with the value type's verdict on the value at the offset; UNDEF =
	 * pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool equal;
		if (UNEXPECTED(!thisEquals(type, equal))) return zv::Val();
		if (equal) return pt_type_is_super_type_of_result(PT_TRI_YES);
		zend_long value = accessibleAndHasOffset(type);
		if (UNEXPECTED(value < 0)) return zv::Val();
		zv::Val result = pt_type_new_is_super_type_of_result(value);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zv::Val offsetValue = offsetValueTypeOf(type);
		if (UNEXPECTED(offsetValue.isUndef())) return zv::Val();
		zval *ownValue = valueType();
		if (UNEXPECTED(ownValue == NULL)) return zv::Val();
		zv::Val valueResult = pt_type_call(Z_OBJ_P(ownValue), PT_LC("issupertypeof"), 1, offsetValue.raw());
		if (UNEXPECTED(valueResult.isUndef())) return zv::Val();
		return pt_type_result_and(std::move(result), valueResult.raw());
	}

	/* the union/intersection callback; else the other type's
	 * offset-accessible-and-has-offset verdict (and'ed with maybe unless it
	 * is a HasOffsetValueType), and'ed with the other's value at the
	 * offset being a supertype of the value type; UNDEF = pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		bool unionOrIntersection;
		if (UNEXPECTED(!isUnionOrIntersection(otherType, unionOrIntersection))) return zv::Val();
		if (unionOrIntersection) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(otherType), PT_LC("issupertypeof"), 1, &selfZv);
		}
		zend_long value = accessibleAndHasOffset(otherType);
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (!instanceof_function(Z_OBJCE_P(otherType), pt_ce_has_offset_value_type)) {
			value = pt_trinary_and(value, PT_TRI_MAYBE);
		}
		zv::Val result = pt_type_new_is_super_type_of_result(value);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zv::Val offsetValue = offsetValueTypeOf(otherType);
		if (UNEXPECTED(offsetValue.isUndef())) return zv::Val();
		zval *ownValue = valueType();
		if (UNEXPECTED(ownValue == NULL)) return zv::Val();
		zv::Val valueResult = pt_type_call(Z_OBJ_P(offsetValue.raw()), PT_LC("issupertypeof"), 1, ownValue);
		if (UNEXPECTED(valueResult.isUndef())) return zv::Val();
		return pt_type_result_and(std::move(result), valueResult.raw());
	}

	/* $this->isSubTypeOf($acceptingType)->toAcceptsResult() */
	zv::Val isAcceptedBy(zval *acceptingType) const
	{
		return pt_type_sub_type_to_accepts_result(isExact() ? isSubTypeOf(acceptingType) : pt_type_call(self, PT_LC("issubtypeof"), 1, acceptingType));
	}

	/* $type instanceof self && $this->offsetType->equals($type->offsetType) && $this->valueType->equals($type->valueType);
	 * false = pending exception */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_has_offset_value_type)) {
			out = false;
			return true;
		}
		zval *offset = offsetType();
		zval *otherOffset = offset != NULL ? slotOf(Z_OBJ_P(type), slots::offsetType, "offsetType") : NULL;
		if (UNEXPECTED(otherOffset == NULL)) return false;
		zv::Val offsetsEqual = pt_type_call(Z_OBJ_P(offset), PT_LC("equals"), 1, otherOffset);
		if (UNEXPECTED(offsetsEqual.isUndef())) return false;
		if (!zend_is_true(offsetsEqual.raw())) {
			out = false;
			return true;
		}
		zval *value = valueType();
		zval *otherValue = value != NULL ? slotOf(Z_OBJ_P(type), slots::valueType, "valueType") : NULL;
		if (UNEXPECTED(otherValue == NULL)) return false;
		return pt_type_call_bool(Z_OBJ_P(value), PT_LC("equals"), 1, otherValue, out);
	}

	/* sprintf('hasOffsetValue(%s, %s)', $this->offsetType->describe($level), $this->valueType->describe($level)) */
	zv::Val describe(zval *level) const
	{
		zval *offset = offsetType();
		zval *value = offset != NULL ? valueType() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(value == NULL)) return zv::Val();
		zv::Val offsetDescription = describeOf(offset, level);
		if (UNEXPECTED(offsetDescription.isUndef())) return zv::Val();
		zv::Val valueDescription = describeOf(value, level);
		if (UNEXPECTED(valueDescription.isUndef())) return zv::Val();
		return zv::Val::adoptString(zend_strpprintf(0, "hasOffsetValue(%s, %s)", ZSTR_VAL(zv::Ref(offsetDescription.raw()).asString()), ZSTR_VAL(zv::Ref(valueDescription.raw()).asString())));
	}

	/* yes for the constant offset itself (as an array key), maybe
	 * otherwise; -1 = pending exception */
	[[nodiscard]] zend_long hasOffsetValueType(zval *offsetTypeArg) const
	{
		bool matches;
		if (UNEXPECTED(!arrayKeyMatchesOffset(offsetTypeArg, matches))) return -1;
		return matches ? PT_TRI_YES : PT_TRI_MAYBE;
	}

	/* the value type for the constant offset itself (as an array key),
	 * mixed otherwise; UNDEF = pending exception */
	zv::Val getOffsetValueType(zval *offsetTypeArg) const
	{
		bool matches;
		if (UNEXPECTED(!arrayKeyMatchesOffset(offsetTypeArg, matches))) return zv::Val();
		if (matches) return getValueType();
		return pt_type_new_mixed_type();
	}

	/* $this for an appended value or another offset; new self with the
	 * value for the offset itself (a ShouldNotHappenException for one that
	 * is no constant); offsetType NULL = the twin's null; UNDEF = pending
	 * exception */
	zv::Val setOffsetValueType(zval *offsetTypeArg, zval *newValueType) const
	{
		if (offsetTypeArg == NULL) return thisValue();
		bool equal;
		if (UNEXPECTED(!offsetEquals(offsetTypeArg, equal))) return zv::Val();
		if (!equal) return thisValue();
		if (!instanceof_function(Z_OBJCE_P(offsetTypeArg), pt_ce_constant_integer_type) && !instanceof_function(Z_OBJCE_P(offsetTypeArg), pt_ce_constant_string_type)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		return create(offsetTypeArg, newValueType);
	}

	/* $this for another offset, new self with the value for the offset
	 * itself; UNDEF = pending exception */
	zv::Val setExistingOffsetValueType(zval *offsetTypeArg, zval *newValueType) const
	{
		bool equal;
		if (UNEXPECTED(!offsetEquals(offsetTypeArg, equal))) return zv::Val();
		if (!equal) return thisValue();
		zval *offset = offsetType();
		if (UNEXPECTED(offset == NULL)) return zv::Val();
		return create(offset, newValueType);
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

	/* for a HasOffsetValueType of the same offset, new self with the
	 * removed type's value type removed from the value type; null
	 * otherwise; UNDEF = pending exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		if (instanceof_function(Z_OBJCE_P(typeToRemove), pt_ce_has_offset_value_type)) {
			zval *offset = offsetType();
			if (UNEXPECTED(offset == NULL)) return zv::Val();
			zv::Val otherOffset = pt_has_offset_value_type_get_offset_type(Z_OBJ_P(typeToRemove));
			if (UNEXPECTED(otherOffset.isUndef())) return zv::Val();
			zv::Val equal = pt_type_call(Z_OBJ_P(offset), PT_LC("equals"), 1, otherOffset.raw());
			if (UNEXPECTED(equal.isUndef())) return zv::Val();
			if (zend_is_true(equal.raw())) {
				zval *value = valueType();
				if (UNEXPECTED(value == NULL)) return zv::Val();
				zv::Val otherValue = pt_has_offset_value_type_get_value_type(Z_OBJ_P(typeToRemove));
				if (UNEXPECTED(otherValue.isUndef())) return zv::Val();
				zv::Args args{value, otherValue.raw()};
				zv::Val remaining = pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("remove"), 2, args);
				if (UNEXPECTED(remaining.isUndef())) return zv::Val();
				return create(offset, remaining.raw());
			}
		}
		return zv::Val::null();
	}

	/* new self($this->valueType->toArrayKey(), $this->offsetType) for a
	 * constant array key, mixed otherwise; UNDEF = pending exception */
	zv::Val flipArray() const
	{
		zval *value = valueType();
		if (UNEXPECTED(value == NULL)) return zv::Val();
		zv::Val arrayKey = pt_type_call(Z_OBJ_P(value), PT_LC("toarraykey"), 0, NULL);
		if (UNEXPECTED(arrayKey.isUndef())) return zv::Val();
		if (zv::Ref(arrayKey.raw()).isObject() && (instanceof_function(Z_OBJCE_P(arrayKey.raw()), pt_ce_constant_integer_type) || instanceof_function(Z_OBJCE_P(arrayKey.raw()), pt_ce_constant_string_type))) {
			zval *offset = offsetType();
			if (UNEXPECTED(offset == NULL)) return zv::Val();
			return create(arrayKey.raw(), offset);
		}
		return pt_type_new_mixed_type();
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

	/* int|string for a constant needle identical (or, for a loose search,
	 * equal) to a constant value type, mixed otherwise; strict NULL = the
	 * twin's null; UNDEF = pending exception */
	zv::Val searchArray(zval *needleType, zval *strict) const
	{
		zend_long strictValue = PT_TRI_MAYBE;
		if (strict != NULL) {
			strictValue = pt_type_trinary_value(strict);
			if (UNEXPECTED(strictValue < 0)) return zv::Val();
		}
		bool needleIsConstant;
		if (UNEXPECTED(!pt_type_instanceof(needleType, PT_CLASS_CONSTANT_SCALAR_TYPE, needleIsConstant))) return zv::Val();
		if (needleIsConstant) {
			zval *value = valueType();
			if (UNEXPECTED(value == NULL)) return zv::Val();
			bool valueIsConstant;
			if (UNEXPECTED(!pt_type_instanceof(value, PT_CLASS_CONSTANT_SCALAR_TYPE, valueIsConstant))) return zv::Val();
			if (valueIsConstant) {
				zv::Val needleValue = pt_type_call(Z_OBJ_P(needleType), PT_LC("getvalue"), 0, NULL);
				if (UNEXPECTED(needleValue.isUndef())) return zv::Val();
				zv::Val ownValue = pt_type_call(Z_OBJ_P(value), PT_LC("getvalue"), 0, NULL);
				if (UNEXPECTED(ownValue.isUndef())) return zv::Val();
				bool found = zend_is_identical(needleValue.raw(), ownValue.raw());
				if (!found && strictValue == PT_TRI_NO) {
					found = zend_compare(needleValue.raw(), ownValue.raw()) == 0;
					if (UNEXPECTED(EG(exception))) return zv::Val();
				}
				if (found) {
					/* new UnionType([new IntegerType(), new StringType()]) */
					zval integer, string;
					if (UNEXPECTED(!pt_integer_type_new(&integer))) return zv::Val();
					if (UNEXPECTED(!pt_string_type_new(&string))) {
						zval_ptr_dtor(&integer);
						return zv::Val();
					}
					zv::Arr types = zv::Arr::create(2);
					types.push(zv::Val::adopt(integer));
					types.push(zv::Val::adopt(string));
					return pt_type_new_union(std::move(types));
				}
			}
		}
		return pt_type_new_mixed_type();
	}

	/* a slice from the offset with a null or positive length keeps the
	 * offset (as $this & non-empty with the keys preserved, non-empty
	 * otherwise); mixed otherwise; UNDEF = pending exception */
	zv::Val sliceArray(zval *offsetTypeArg, zval *lengthType, zval *preserveKeys) const
	{
		zend_long covers = offsetIsSuperTypeOf(offsetTypeArg);
		if (UNEXPECTED(covers < 0)) return zv::Val();
		if (covers == PT_TRI_YES) {
			zend_long lengthIsNull = pt_type_call_trinary(Z_OBJ_P(lengthType), PT_LC("isnull"), 0, NULL);
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

	/* new self($this->offsetType, $cb($this->valueType)) */
	zv::Val mapValueType(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *offset = offsetType();
		zval *value = offset != NULL ? valueType() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(value == NULL)) return zv::Val();
		zval mapped;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, value, &mapped))) return zv::Val();
		zv::Val mappedType = zv::Val::adopt(mapped);
		return create(offset, mappedType.raw());
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
		/* Unknown case → drop the specific-offset assertion. */
		return pt_type_new_mixed_type();
	}

	/* $this when the value definitely survives a falsey filter, mixed
	 * otherwise; UNDEF = pending exception */
	zv::Val filterArrayRemovingFalsey() const
	{
		zv::Val falseyTypes = pt_type_call_static(PT_CLASS_STATIC_TYPE_FACTORY, PT_LC("falsey"), 0, NULL);
		if (UNEXPECTED(falseyTypes.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(falseyTypes.raw()).isObject())) {
			zend_type_error("phpstan_turbo: StaticTypeFactory::falsey() must return %s", ptcls::type);
			return zv::Val();
		}
		zval *value = valueType();
		if (UNEXPECTED(value == NULL)) return zv::Val();
		zend_long isFalsey = resultTrinaryOf(falseyTypes.raw(), PT_LC("issupertypeof"), value);
		if (UNEXPECTED(isFalsey < 0)) return zv::Val();
		if (isFalsey == PT_TRI_NO) {
			/* Definitely survives. */
			return thisValue();
		}
		/* Definitely filtered out, or maybe filtered: drop the assertion. */
		return pt_type_new_mixed_type();
	}

	/* no for a string offset, maybe otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isList() const
	{
		zval *offset = offsetType();
		if (UNEXPECTED(offset == NULL)) return -1;
		zend_long isString = pt_type_call_trinary(Z_OBJ_P(offset), PT_LC("isstring"), 0, NULL);
		if (UNEXPECTED(isString < 0)) return -1;
		return isString == PT_TRI_YES ? PT_TRI_NO : PT_TRI_MAYBE;
	}

	/* $this when $cb leaves the value type, new self with the result
	 * otherwise; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *value = valueType();
		if (UNEXPECTED(value == NULL)) return zv::Val();
		zval mapped;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, value, &mapped))) return zv::Val();
		return traversed(zv::Val::adopt(mapped));
	}

	/* traverse() with $right's value at the offset as the callback's second
	 * argument */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *value = valueType();
		if (UNEXPECTED(value == NULL)) return zv::Val();
		zv::Val rightValue = offsetValueTypeOf(right);
		if (UNEXPECTED(rightValue.isUndef())) return zv::Val();
		zv::Args args{value, rightValue.raw()};
		zval mapped;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, args, &mapped))) return zv::Val();
		return traversed(zv::Val::adopt(mapped));
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
		zv::Val arrayAccess = pt_type_new(PT_CLASS_OBJECT_TYPE, 1, className.raw());
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

	/* $this->offsetType->hasTemplateOrLateResolvableType() || $this->valueType->...;
	 * false = pending exception */
	[[nodiscard]] bool hasTemplateOrLateResolvableType(bool &out) const
	{
		zval *offset = offsetType();
		zval *value = offset != NULL ? valueType() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(value == NULL)) return false;
		zv::Val offsetHas = pt_type_call(Z_OBJ_P(offset), PT_LC("hastemplateorlateresolvabletype"), 0, NULL);
		if (UNEXPECTED(offsetHas.isUndef())) return false;
		if (zend_is_true(offsetHas.raw())) {
			out = true;
			return true;
		}
		return pt_type_call_bool(Z_OBJ_P(value), PT_LC("hastemplateorlateresolvabletype"), 0, NULL, out);
	}

private:
	zend_object *self;

	bool isExact() const { return self->ce == pt_ce_has_offset_value_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* $this->equals($type) — through the object's class; false = pending
	 * exception */
	[[nodiscard]] bool thisEquals(zval *type, bool &out) const
	{
		if (EXPECTED(isExact())) return equals(type, out);
		return pt_type_call_bool(self, PT_LC("equals"), 1, type, out);
	}

	/* TrinaryLogic::and(): the minimum */

	/* $object->method($arg)'s result trinary; -1 = pending exception */
	[[nodiscard]] static zend_long resultTrinaryOf(zval *object, const char *lcname, size_t len, zval *arg)
	{
		zv::Val result = pt_type_call(Z_OBJ_P(object), lcname, len, 1, arg);
		if (UNEXPECTED(result.isUndef())) return -1;
		return pt_type_result_trinary(result.raw());
	}

	/* $type->describe($level) as an owned string; UNDEF = pending exception */
	static zv::Val describeOf(zval *type, zval *level)
	{
		zv::Val description = pt_type_call(Z_OBJ_P(type), PT_LC("describe"), 1, level);
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(description.raw()).isString())) {
			zend_type_error("phpstan_turbo: describe() must return string");
			return zv::Val();
		}
		return description;
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

	/* $type->getOffsetValueType($this->offsetType), checked to be a Type;
	 * UNDEF = pending exception */
	zv::Val offsetValueTypeOf(zval *type) const
	{
		zval *offset = offsetType();
		if (UNEXPECTED(offset == NULL)) return zv::Val();
		zv::Val result = pt_type_call(Z_OBJ_P(type), PT_LC("getoffsetvaluetype"), 1, offset);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getOffsetValueType() must return %s", ptcls::type);
			return zv::Val();
		}
		return result;
	}

	/* $this->offsetType->isSuperTypeOf($type)'s trinary; -1 = pending
	 * exception */
	[[nodiscard]] zend_long offsetIsSuperTypeOf(zval *type) const
	{
		zval *offset = offsetType();
		if (UNEXPECTED(offset == NULL)) return -1;
		return resultTrinaryOf(offset, PT_LC("issupertypeof"), type);
	}

	/* $type->equals($this->offsetType); false = pending exception */
	[[nodiscard]] bool offsetEquals(zval *type, bool &out) const
	{
		zval *offset = offsetType();
		if (UNEXPECTED(offset == NULL)) return false;
		return pt_type_call_bool(Z_OBJ_P(type), PT_LC("equals"), 1, offset, out);
	}

	/* $arrayKeyType = $offsetType->toArrayKey();
	 * $arrayKeyType->isConstantScalarValue()->yes() && $arrayKeyType->equals($this->offsetType);
	 * false = pending exception */
	[[nodiscard]] bool arrayKeyMatchesOffset(zval *offsetTypeArg, bool &out) const
	{
		zv::Val arrayKey = pt_type_call(Z_OBJ_P(offsetTypeArg), PT_LC("toarraykey"), 0, NULL);
		if (UNEXPECTED(arrayKey.isUndef())) return false;
		if (UNEXPECTED(!zv::Ref(arrayKey.raw()).isObject())) {
			zend_type_error("phpstan_turbo: toArrayKey() must return %s", ptcls::type);
			return false;
		}
		zend_long isConstantScalar = pt_type_call_trinary(Z_OBJ_P(arrayKey.raw()), PT_LC("isconstantscalarvalue"), 0, NULL);
		if (UNEXPECTED(isConstantScalar < 0)) return false;
		if (isConstantScalar != PT_TRI_YES) {
			out = false;
			return true;
		}
		return offsetEquals(arrayKey.raw(), out);
	}

	/* $type instanceof UnionType || $type instanceof IntersectionType;
	 * false = pending exception */
	[[nodiscard]] static bool isUnionOrIntersection(zval *type, bool &out)
	{
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_UNION_TYPE, out))) return false;
		if (out) return true;
		return pt_type_instanceof(type, PT_CLASS_INTERSECTION_TYPE, out);
	}

	/* TypeCombinator::intersect($this, new NonEmptyArrayType()) */
	zv::Val intersectedWithNonEmpty() const
	{
		zv::Val nonEmpty = nonEmptyArray();
		if (UNEXPECTED(nonEmpty.isUndef())) return zv::Val();
		zv::Args args{self, nonEmpty.raw()};
		return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("intersect"), 2, args);
	}

	/* new self(new ConstantStringType($owned), $this->valueType) */
	zv::Val createWithConstantString(zend_string *owned) const
	{
		zval constantString;
		bool created = pt_constant_string_type_new(&constantString, owned);
		zend_string_release(owned);
		if (UNEXPECTED(!created)) return zv::Val();
		zv::Val offset = zv::Val::adopt(constantString);
		zval *value = valueType();
		if (UNEXPECTED(value == NULL)) return zv::Val();
		return create(offset.raw(), value);
	}

	/* the tail of traverse()/traverseSimultaneously(): $this for the same
	 * value type, new self with the new one otherwise */
	zv::Val traversed(zv::Val newValueType) const
	{
		zval *offset = offsetType();
		zval *value = offset != NULL ? valueType() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(value == NULL)) return zv::Val();
		if (zv::Ref(newValueType.raw()).isObject() && Z_OBJ_P(newValueType.raw()) == Z_OBJ_P(value)) return thisValue();
		return create(offset, newValueType.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::HasOffsetValueType;
using phpstanturbo::NullableLong;

bool pt_has_offset_value_type_new(zval *out, zval *offsetType, zval *valueType)
{
	return pt_val_into(HasOffsetValueType::create(offsetType, valueType), out);
}

zv::Val pt_has_offset_value_type_get_offset_type(zend_object *object)
{
	if (EXPECTED(object->ce == pt_ce_has_offset_value_type)) return HasOffsetValueType(object).getOffsetType();
	return pt_type_call(object, PT_LC("getoffsettype"), 0, NULL);
}

zv::Val pt_has_offset_value_type_get_value_type(zend_object *object)
{
	if (EXPECTED(object->ce == pt_ce_has_offset_value_type)) return HasOffsetValueType(object).getValueType();
	return pt_type_call(object, PT_LC("getvaluetype"), 0, NULL);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS HasOffsetValueType(Z_OBJ_P(ZEND_THIS))

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL hovtEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL hovtNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL hovtYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL hovtThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL hovtThis1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL hovtError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL hovtError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL hovtMixed0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_mixed_type());
}

static void ZEND_FASTCALL hovtNonEmpty0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(HasOffsetValueType::nonEmptyArray());
}

static void ZEND_FASTCALL hovtNonEmpty1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(HasOffsetValueType::nonEmptyArray());
}

static void ZEND_FASTCALL hovtNonEmpty2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	PT_RETURN_VAL(HasOffsetValueType::nonEmptyArray());
}

static void ZEND_FASTCALL hovtObjectWithoutClass0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(HasOffsetValueType::objectWithoutClass());
}

/* (Type $type) → a Type */
static void pt_hovt_one_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (HasOffsetValueType::*method)(zval *) const)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(type));
}

void pt_register_has_offset_value_type()
{
	reg::Class cls("PHPStan\\Type\\Accessory\\HasOffsetValueType");
	ptdecl::HasOffsetValueType::declareClass(cls);
	/* "offsetType" and "valueType" must stay the first two declared
	 * properties (slots::offsetType, slots::valueType) */
	ptdecl::HasOffsetValueType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *valueType;
		if (!zp::parse<zp::Zval, zp::Zval>(execute_data, offsetType, valueType)) RETURN_THROWS();
		if (UNEXPECTED(!HasOffsetValueType::checkOffsetType(offsetType, 1) || !HasOffsetValueType::checkValueType(valueType, 2))) RETURN_THROWS();
		PT_THIS.construct(offsetType, valueType);
	});

	cls.method<&HasOffsetValueType::getOffsetType>(sigs::getOffsetType);

	cls.method<&HasOffsetValueType::getValueType>(sigs::getValueType);

	cls.method(sigs::getReferencedClasses, hovtEmptyArray0);
	cls.method(sigs::getObjectClassNames, hovtEmptyArray0);
	cls.method(sigs::getObjectClassReflections, hovtEmptyArray0);

	cls.method<&HasOffsetValueType::accepts, zp::Obj, zp::Bool>(sigs::accepts);

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hovt_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasOffsetValueType::isSuperTypeOf);
	});

	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hovt_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasOffsetValueType::isSubTypeOf);
	});

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method<&HasOffsetValueType::equals, zp::Obj>(sigs::equals);

	cls.method<&HasOffsetValueType::describe, zp::Obj>(sigs::describe);

	cls.method(sigs::isOffsetAccessible, hovtYes0);
	cls.method(sigs::isOffsetAccessLegal, hovtYes0);

	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.hasOffsetValueType(offsetType));
	});

	cls.method(sigs::getOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hovt_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasOffsetValueType::getOffsetValueType);
	});

	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *valueType;
		bool unionValues = true;
		if (!zp::parse<zp::ObjOrNull, zp::Obj, zp::Opt<zp::Bool>>(execute_data, offsetType, valueType, unionValues)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.setOffsetValueType(offsetType, valueType));
	});

	cls.method<&HasOffsetValueType::setExistingOffsetValueType, zp::Obj, zp::Obj>(sigs::setExistingOffsetValueType);

	cls.method(sigs::unsetOffset, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hovt_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasOffsetValueType::unsetOffset);
	});

	cls.method(sigs::tryRemove, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hovt_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasOffsetValueType::tryRemove);
	});

	cls.method(sigs::getKeysArrayFiltered, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* $this->getKeysArray() — through the object's class */
		PT_RETURN_VAL(pt_type_call(Z_OBJ_P(ZEND_THIS), PT_LC("getkeysarray"), 0, NULL));
	});

	cls.method(sigs::getKeysArray, hovtNonEmpty0);
	cls.method(sigs::getValuesArray, hovtNonEmpty0);
	cls.method(sigs::chunkArray, hovtNonEmpty2);
	cls.method(sigs::fillKeysArray, hovtNonEmpty1);

	cls.method<&HasOffsetValueType::flipArray>(sigs::flipArray);

	cls.method(sigs::intersectKeyArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hovt_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasOffsetValueType::intersectKeyArray);
	});

	cls.method(sigs::reverseArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hovt_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasOffsetValueType::reverseArray);
	});

	cls.method(sigs::searchArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *needleType, *strict = NULL;
		if (!zp::parse<zp::Obj, zp::Opt<zp::ObjOrNull>>(execute_data, needleType, strict)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.searchArray(needleType, strict));
	});

	cls.method(sigs::shuffleArray, hovtNonEmpty0);

	cls.method<&HasOffsetValueType::sliceArray, zp::Obj, zp::Obj, zp::Obj>(sigs::sliceArray);

	cls.method(sigs::spliceArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *lengthType, *replacementType;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, offsetType, lengthType, replacementType)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.spliceArray(lengthType));
	});

	cls.method(sigs::truncateListToSize, hovtThis1);
	cls.method(sigs::makeListMaybe, hovtThis0);

	cls.method(sigs::mapValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.mapValueType(&fci, &fcc));
	});

	cls.method(sigs::mapKeyType, hovtThis1);
	cls.method(sigs::makeAllArrayKeysOptional, hovtMixed0);

	cls.method(sigs::changeKeyCaseArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long caseValue = 0;
		bool caseIsNull = false;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_LONG_OR_NULL(caseValue, caseIsNull)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.changeKeyCaseArray(caseIsNull ? NullableLong::null() : NullableLong::of(caseValue)));
	});

	cls.method<&HasOffsetValueType::filterArrayRemovingFalsey>(sigs::filterArrayRemovingFalsey);

	cls.method(sigs::isIterableAtLeastOnce, hovtYes0);

	cls.method(sigs::isList, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isList());
	});

	cls.method(sigs::isNull, hovtNo0);
	cls.method(sigs::isConstantValue, hovtNo0);
	cls.method(sigs::isConstantScalarValue, hovtNo0);
	cls.method(sigs::getConstantScalarTypes, hovtEmptyArray0);
	cls.method(sigs::getConstantScalarValues, hovtEmptyArray0);
	cls.method(sigs::isTrue, hovtNo0);
	cls.method(sigs::isFalse, hovtNo0);
	cls.method(sigs::isBoolean, hovtNo0);
	cls.method(sigs::isFloat, hovtNo0);
	cls.method(sigs::isInteger, hovtNo0);
	cls.method(sigs::getClassStringObjectType, hovtObjectWithoutClass0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, hovtObjectWithoutClass0);
	cls.method(sigs::isVoid, hovtNo0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* new BooleanType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.method(sigs::toNumber, hovtError0);
	cls.method(sigs::toBitwiseNotType, hovtError0);
	cls.method(sigs::toAbsoluteNumber, hovtError0);
	cls.method(sigs::toInteger, hovtError0);
	cls.method(sigs::toFloat, hovtError0);
	cls.method(sigs::toString, hovtError0);
	cls.method(sigs::toArray, hovtMixed0);
	cls.method(sigs::toArrayKey, hovtError0);
	cls.method(sigs::toCoercedArgumentType, hovtThis1);
	cls.method(sigs::getEnumCases, hovtEmptyArray0);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_NULL();
	});

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});

	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *right;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(right)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverseSimultaneously(right, &fci, &fcc));
	});

	cls.method(sigs::exponentiate, hovtError1);
	cls.method(sigs::getFiniteTypes, hovtEmptyArray0);

	cls.method<&HasOffsetValueType::getDefaultBaseType>(sigs::getDefaultBaseType);

	cls.method<&HasOffsetValueType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method<&HasOffsetValueType::hasTemplateOrLateResolvableType>(sigs::hasTemplateOrLateResolvableType);

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares */
	ptdecl::HasOffsetValueType::registerTraits(cls);

	cls.shadow(&pt_ce_has_offset_value_type);
}

/* }}} */
