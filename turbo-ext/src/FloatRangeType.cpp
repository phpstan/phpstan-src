/*
 * PHPStanTurbo\FloatRangeType — native implementation of
 * PHPStan\Type\FloatRangeType.
 *
 * Declared as PHPStan\Type\FloatRangeType itself at activation: not final,
 * extending the native FloatType (declared first — Shadow.cpp materialises
 * a parent plan before its child). State is the twin's `private float
 * $min` and `private float $max`, declared typed property slots
 * (IS_PROP_UNINIT until the constructor writes them), so the std object
 * handlers do GC/clone. Everything the class does not declare is inherited
 * from FloatType.
 *
 * The ordinals are the twin's pack('d') / unpack('q') reinterpretation of
 * the double's bits in the machine byte order — a memcpy here — so both
 * sides agree on every bound bit for bit.
 */

#include "TypeTraits.h"
#include "generated/FloatRangeType.h"

namespace slots = ptdecl::FloatRangeType::slot;
namespace sigs = ptdecl::FloatRangeType::sig;

#include <cmath>
#include <cstdint>
#include <cstring>

zend_class_entry *pt_ce_float_range_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\FloatRangeType. State lives in the PHP object's
 * $min and $max. */
class FloatRangeType
{
public:
	explicit FloatRangeType(zend_object *self) : self(self) {}

	/* private __construct(private float $min, private float $max):
	 * initializes the typed slots; parent::__construct() is FloatType's
	 * empty constructor */
	void construct(double min, double max)
	{
		zval *minSlot = OBJ_PROP_NUM(self, slots::min);
		zval *maxSlot = OBJ_PROP_NUM(self, slots::max);
		ZVAL_DOUBLE(minSlot, min);
		ZVAL_DOUBLE(maxSlot, max);
		Z_PROP_FLAG_P(minSlot) = 0; /* no longer IS_PROP_UNINIT */
		Z_PROP_FLAG_P(maxSlot) = 0;
	}

	/* new self($min, $max); UNDEF = pending exception */
	static zv::Val create(double min, double max)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_float_range_type) != SUCCESS)) return zv::Val();
		FloatRangeType(Z_OBJ(object)).construct(min, max);
		return zv::Val::adopt(object);
	}

	/* ShouldNotHappenException for a NAN bound, never for an empty
	 * interval, the constant for a single point, the range otherwise —
	 * its bounds normalized through the ordinals (a zero bound is 0.0) */
	static zv::Val fromInterval(double min, double max)
	{
		if (std::isnan(min) || std::isnan(max)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		zend_long minOrdinal = toOrdinal(min);
		zend_long maxOrdinal = toOrdinal(max);
		if (minOrdinal > maxOrdinal) return pt_type_new_never_type();

		if (minOrdinal == maxOrdinal) return pt_type_new_constant_float(fromOrdinal(minOrdinal));

		return create(fromOrdinal(minOrdinal), fromOrdinal(maxOrdinal));
	}

	/* the double's bits read as an integer, negated for a negative double,
	 * 0 for either zero */
	static zend_long toOrdinal(double value)
	{
		if (value == 0.0) return 0;

		int64_t bits;
		memcpy(&bits, &value, sizeof bits);
		if (bits >= 0) return (zend_long) bits;

		return -(zend_long) (bits & INT64_MAX);
	}

	static double fromOrdinal(zend_long ordinal)
	{
		if (ordinal == 0) return 0.0;

		int64_t bits = ordinal < 0 ? -(int64_t) ordinal : (int64_t) ordinal;
		double value;
		memcpy(&value, &bits, sizeof value);
		return ordinal < 0 ? -value : value;
	}

	/* the floats smaller than / at most / greater than / at least $value,
	 * NAN excluded: never for a NAN, and past either infinity */
	static zv::Val createAllSmallerThan(double value)
	{
		if (std::isnan(value) || value == -HUGE_VAL) return pt_type_new_never_type();
		return fromInterval(-HUGE_VAL, fromOrdinal(toOrdinal(value) - 1));
	}

	static zv::Val createAllSmallerThanOrEqualTo(double value)
	{
		if (std::isnan(value)) return pt_type_new_never_type();
		return fromInterval(-HUGE_VAL, value);
	}

	static zv::Val createAllGreaterThan(double value)
	{
		if (std::isnan(value) || value == HUGE_VAL) return pt_type_new_never_type();
		return fromInterval(fromOrdinal(toOrdinal(value) + 1), HUGE_VAL);
	}

	static zv::Val createAllGreaterThanOrEqualTo(double value)
	{
		if (std::isnan(value)) return pt_type_new_never_type();
		return fromInterval(value, HUGE_VAL);
	}

	/* [min, max] of a range or [o, o] of a non-NAN constant in ordinals
	 * (found = true), found = false for anything else; false = pending
	 * exception */
	[[nodiscard]] static bool getOrdinalBounds(zval *type, bool &found, zend_long &min, zend_long &max)
	{
		found = false;
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_float_range_type)) {
			double typeMin = 0.0, typeMax = 0.0;
			if (UNEXPECTED(!FloatRangeType(Z_OBJ_P(type)).bounds(typeMin, typeMax))) return false;
			min = toOrdinal(typeMin);
			max = toOrdinal(typeMax);
			found = true;
			return true;
		}

		if (instanceof_function(Z_OBJCE_P(type), pt_ce_constant_float_type)) {
			double value = 0.0;
			if (UNEXPECTED(!pt_constant_float_get_value(Z_OBJ_P(type), value))) return false;
			if (std::isnan(value)) return true;
			min = max = toOrdinal(value);
			found = true;
		}

		return true;
	}

	/* getOrdinalBounds() as the twin returns it: array{int, int}|null */
	static zv::Val getOrdinalBoundsValue(zval *type)
	{
		bool found;
		zend_long min = 0, max = 0;
		if (UNEXPECTED(!getOrdinalBounds(type, found, min, max))) return zv::Val();
		if (!found) return zv::Val::null();
		zv::Arr result = zv::Arr::create(2);
		result.push(zv::Val::integer(min));
		result.push(zv::Val::integer(max));
		return zv::Val(std::move(result));
	}

	[[nodiscard]] bool bounds(double &min, double &max) const
	{
		return slot(slots::min, "min", min) && slot(slots::max, "max", max);
	}

	bool getMin(double &out) const { return slot(slots::min, "min", out); }
	bool getMax(double &out) const { return slot(slots::max, "max", out); }

	/* sprintf('float<%s, %s>', ...) with 'min' for -INF, 'max' for INF and
	 * the bounds as ConstantFloatType describes them precisely; UNDEF =
	 * pending exception */
	zv::Val describe() const
	{
		double min = 0.0, max = 0.0;
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		smart_str str = {NULL, 0};
		smart_str_appendl(&str, "float<", 6);
		if (UNEXPECTED(!appendBound(str, min, -HUGE_VAL, "min"))) {
			smart_str_free(&str);
			return zv::Val();
		}
		smart_str_appendl(&str, ", ", 2);
		if (UNEXPECTED(!appendBound(str, max, HUGE_VAL, "max"))) {
			smart_str_free(&str);
			return zv::Val();
		}
		smart_str_appendc(&str, '>');
		smart_str_0(&str);
		return zv::Val::adoptString(str.s);
	}

	/* an integer through the float it converts to, the other FloatTypes
	 * through isSuperTypeOf(), the CompoundType callback, no otherwise;
	 * UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_constant_integer_type)) {
			zend_long value;
			if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(type), value))) return zv::Val();
			zv::Val asFloat = pt_type_new_constant_float((double) value);
			if (UNEXPECTED(asFloat.isUndef())) return zv::Val();
			return toAcceptsResult(selfIsSuperTypeOf(asFloat.raw()));
		}

		bool isRange = instanceof_function(Z_OBJCE_P(type), pt_ce_integer_range_type);
		if (isRange || Z_OBJCE_P(type) == pt_ce_integer_type) {
			NullableLong min = NullableLong::null(), max = NullableLong::null();
			if (isRange && UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(type), min, max))) return zv::Val();
			zv::Val asFloat = fromInterval(
				(double) (min.isNull ? ZEND_LONG_MIN : min.value),
				(double) (max.isNull ? ZEND_LONG_MAX : max.value)
			);
			if (UNEXPECTED(asFloat.isUndef())) return zv::Val();
			return toAcceptsResult(selfIsSuperTypeOf(asFloat.raw()));
		}

		/* $type instanceof parent */
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_float_type)) return toAcceptsResult(selfIsSuperTypeOf(type));

		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}

		return pt_type_accepts_result(PT_TRI_NO);
	}

	/* no/yes/maybe by the ordinal bounds of a range or a non-NAN constant,
	 * no for NAN, maybe for any other FloatType, the CompoundType callback,
	 * no otherwise; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool found;
		zend_long typeMin = 0, typeMax = 0;
		if (UNEXPECTED(!getOrdinalBounds(type, found, typeMin, typeMax))) return zv::Val();
		if (found) {
			zend_long min = 0, max = 0;
			if (UNEXPECTED(!ordinalBounds(min, max))) return zv::Val();
			if (typeMax < min || max < typeMin) return pt_type_is_super_type_of_result(PT_TRI_NO);

			if (min <= typeMin && typeMax <= max) return pt_type_is_super_type_of_result(PT_TRI_YES);

			return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		}

		/* NAN */
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_constant_float_type)) return pt_type_is_super_type_of_result(PT_TRI_NO);

		/* $type instanceof parent */
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_float_type)) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);

		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &selfZv);
		}

		return pt_type_is_super_type_of_result(PT_TRI_NO);
	}

	/* $type instanceof self && $this->min === $type->min && $this->max === $type->max;
	 * false with an exception pending on an uninitialized slot */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_float_range_type)) {
			out = false;
			return true;
		}
		double min = 0.0, max = 0.0, typeMin = 0.0, typeMax = 0.0;
		if (UNEXPECTED(!bounds(min, max) || !FloatRangeType(Z_OBJ_P(type)).bounds(typeMin, typeMax))) return false;
		out = min == typeMin && max == typeMax;
		return true;
	}

	/* new FloatType() */
	static zv::Val generalize()
	{
		return pt_val_of<pt_float_type_new>();
	}

	/* the getSmallerType() family: mixed without what compares false
	 * against every value of the range (NAN against all of them), the way
	 * IntegerRangeType builds it; UNDEF = pending exception */
	zv::Val getSmallerType() const
	{
		double min = 0.0, max = 0.0;
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		zv::Arr types = zv::Arr::create(4);
		zval maxZv;
		ZVAL_DOUBLE(&maxZv, max);
		if (UNEXPECTED(!push(types, constantBoolean(true)) || !push(types, nan())
			|| !push(types, pt_integer_range_create_all_greater_than_or_equal_to(&maxZv))
			|| !push(types, createAllGreaterThanOrEqualTo(max)))) return zv::Val();
		return pt_type_mixed_minus(types.table());
	}

	zv::Val getSmallerOrEqualType() const
	{
		double min = 0.0, max = 0.0;
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		zv::Arr types = zv::Arr::create(3);
		zval maxZv;
		ZVAL_DOUBLE(&maxZv, max);
		if (UNEXPECTED(!push(types, nan())
			|| !push(types, pt_integer_range_create_all_greater_than(&maxZv))
			|| !push(types, createAllGreaterThan(max)))) return zv::Val();
		return pt_type_mixed_minus(types.table());
	}

	zv::Val getGreaterType() const
	{
		double min = 0.0, max = 0.0;
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		zv::Arr types = zv::Arr::create(6);
		zval minZv;
		ZVAL_DOUBLE(&minZv, min);
		if (UNEXPECTED(!push(types, nullType()) || !push(types, constantBoolean(false)) || !push(types, nan())
			|| !push(types, pt_integer_range_create_all_smaller_than_or_equal_to(&minZv))
			|| !push(types, createAllSmallerThanOrEqualTo(min)))) return zv::Val();
		if (excludesZero(min, max) && UNEXPECTED(!push(types, constantBoolean(true)))) return zv::Val();
		return pt_type_mixed_minus(types.table());
	}

	zv::Val getGreaterOrEqualType() const
	{
		double min = 0.0, max = 0.0;
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		zv::Arr types = zv::Arr::create(5);
		zval minZv;
		ZVAL_DOUBLE(&minZv, min);
		if (UNEXPECTED(!push(types, nan())
			|| !push(types, pt_integer_range_create_all_smaller_than(&minZv))
			|| !push(types, createAllSmallerThan(min)))) return zv::Val();
		if (excludesZero(min, max) && UNEXPECTED(!push(types, nullType()) || !push(types, constantBoolean(false)))) return zv::Val();
		return pt_type_mixed_minus(types.table());
	}

	/* private excludesZero(): the range lies on one side of the zeros */
	bool excludesZeroValue(bool &out) const
	{
		double min = 0.0, max = 0.0;
		if (UNEXPECTED(!bounds(min, max))) return false;
		out = excludesZero(min, max);
		return true;
	}

	/* true when the range lies on one side of the zeros, bool otherwise */
	zv::Val toBoolean() const
	{
		zend_long min = 0, max = 0;
		if (UNEXPECTED(!ordinalBounds(min, max))) return zv::Val();
		if (min > 0 || max < 0) {
			zval result;
			if (UNEXPECTED(!pt_constant_boolean_type_new(&result, true))) return zv::Val();
			return zv::Val::adopt(result);
		}

		return pt_val_of<pt_boolean_type_new>();
	}

	/* int<(int) min, (int) max> when both bounds cast inside the int range,
	 * int otherwise: casting a float outside of it is undefined */
	zv::Val toInteger() const
	{
		double min = 0.0, max = 0.0;
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		if (min >= static_cast<double>(ZEND_LONG_MIN) && max < static_cast<double>(ZEND_LONG_MAX)) {
			return pt_integer_range_from_interval(NullableLong::of(static_cast<zend_long>(min)), NullableLong::of(static_cast<zend_long>(max)), 0);
		}

		return pt_val_of<pt_integer_type_new>();
	}

	/* TypeCombinator::intersect(string, uppercase-string, numeric-string for
	 * finite bounds (only INF and -INF stringify as non-numeric) or
	 * non-empty-string, non-falsy-string without the zeros (only 0.0 is
	 * '0')); UNDEF = pending exception */
	zv::Val toString() const
	{
		double min = 0.0, max = 0.0;
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		zv::Val string = pt_val_of<pt_string_type_new>();
		if (UNEXPECTED(string.isUndef())) return zv::Val();
		zv::Val uppercase = pt_type_new_shadowed(pt_accessory_uppercase_string_type_new);
		if (UNEXPECTED(uppercase.isUndef())) return zv::Val();
		zv::Val nonEmpty = std::isfinite(min) && std::isfinite(max)
			? pt_type_new_shadowed(pt_accessory_numeric_string_type_new)
			: pt_type_new_shadowed(pt_accessory_non_empty_string_type_new);
		if (UNEXPECTED(nonEmpty.isUndef())) return zv::Val();
		if (!excludesZero(min, max)) {
			zv::Args args{string.raw(), uppercase.raw(), nonEmpty.raw()};
			return pt_type_combinator_intersect(3, args);
		}

		zv::Val nonFalsy = pt_type_new_shadowed(pt_accessory_non_falsy_string_type_new);
		if (UNEXPECTED(nonFalsy.isUndef())) return zv::Val();
		zv::Args args{string.raw(), uppercase.raw(), nonEmpty.raw(), nonFalsy.raw()};
		return pt_type_combinator_intersect(4, args);
	}

	/* $this for a non-negative range, the mirrored range for a
	 * non-positive one, float<0, max(-min, max)> for one around the zeros */
	zv::Val toAbsoluteNumber() const
	{
		double min = 0.0, max = 0.0;
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		if (toOrdinal(min) >= 0) return pt_this_value(self);

		if (toOrdinal(max) <= 0) return fromInterval(-max, -min);

		/* max(-$this->min, $this->max): both are positive here */
		return fromInterval(0.0, -min >= max ? -min : max);
	}

	/* the merged range when the two touch or overlap, the other type for
	 * a plain FloatType, null otherwise; UNDEF = pending exception */
	zv::Val tryUnion(zval *otherType) const
	{
		bool found;
		zend_long otherMin = 0, otherMax = 0;
		if (UNEXPECTED(!getOrdinalBounds(otherType, found, otherMin, otherMax))) return zv::Val();
		if (found) {
			zend_long min = 0, max = 0;
			if (UNEXPECTED(!ordinalBounds(min, max))) return zv::Val();
			/* touching ranges merge: nothing lies between $max and $max + 1 */
			if (otherMax < min - 1 || max + 1 < otherMin) return zv::Val::null();

			return fromInterval(fromOrdinal(min < otherMin ? min : otherMin), fromOrdinal(max > otherMax ? max : otherMax));
		}

		/* get_class($otherType) === parent::class */
		if (Z_OBJCE_P(otherType) == pt_ce_float_type) return zv::Val::copyOf(zv::Ref(otherType));

		/* float is float<min, max>|NAN */
		if (instanceof_function(Z_OBJCE_P(otherType), pt_ce_constant_float_type)) {
			double min = 0.0, max = 0.0;
			if (UNEXPECTED(!bounds(min, max))) return zv::Val();
			if (min == -HUGE_VAL && max == HUGE_VAL) return pt_val_of<pt_float_type_new>();
		}

		return zv::Val::null();
	}

	/* the common range (never when disjoint), never for NAN, $this for a
	 * plain FloatType, null otherwise; UNDEF = pending exception */
	zv::Val tryIntersect(zval *otherType) const
	{
		bool found;
		zend_long otherMin = 0, otherMax = 0;
		if (UNEXPECTED(!getOrdinalBounds(otherType, found, otherMin, otherMax))) return zv::Val();
		if (found) {
			zend_long min = 0, max = 0;
			if (UNEXPECTED(!ordinalBounds(min, max))) return zv::Val();

			return fromInterval(fromOrdinal(min > otherMin ? min : otherMin), fromOrdinal(max < otherMax ? max : otherMax));
		}

		/* NAN */
		if (instanceof_function(Z_OBJCE_P(otherType), pt_ce_constant_float_type)) return pt_type_new_never_type();

		/* get_class($otherType) === parent::class */
		if (Z_OBJCE_P(otherType) == pt_ce_float_type) return pt_this_value(self);

		return zv::Val::null();
	}

	/* never without a plain FloatType, $this without NAN, the parts of the
	 * range below and above a range or a constant, null otherwise; UNDEF =
	 * pending exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		/* get_class($typeToRemove) === parent::class */
		if (Z_OBJCE_P(typeToRemove) == pt_ce_float_type) return pt_type_new_never_type();

		bool found;
		zend_long removeMin = 0, removeMax = 0;
		if (UNEXPECTED(!getOrdinalBounds(typeToRemove, found, removeMin, removeMax))) return zv::Val();
		if (!found) {
			/* NAN is in no range */
			if (instanceof_function(Z_OBJCE_P(typeToRemove), pt_ce_constant_float_type)) return pt_this_value(self);
			return zv::Val::null();
		}

		double minValue = 0.0, maxValue = 0.0;
		if (UNEXPECTED(!bounds(minValue, maxValue))) return zv::Val();
		zend_long min = toOrdinal(minValue);
		zend_long max = toOrdinal(maxValue);
		if (removeMax < min || max < removeMin) return pt_this_value(self);

		zv::Arr parts = zv::Arr::create(2);
		if (min < removeMin) {
			zv::Val part = fromInterval(minValue, fromOrdinal(removeMin - 1));
			if (UNEXPECTED(part.isUndef())) return zv::Val();
			parts.push(std::move(part));
		}
		if (removeMax < max) {
			zv::Val part = fromInterval(fromOrdinal(removeMax + 1), maxValue);
			if (UNEXPECTED(part.isUndef())) return zv::Val();
			parts.push(std::move(part));
		}

		return pt_type_combinator_call_spread(PT_LC("union"), parts.table());
	}

	/* new GenericTypeNode(new IdentifierTypeNode('float'), [$min, $max])
	 * with each bound the 'min'/'max' identifier or ConstantFloatType's
	 * node; UNDEF = pending exception */
	zv::Val toPhpDocNode() const
	{
		double min = 0.0, max = 0.0;
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		zv::Val minNode = boundNode(min, -HUGE_VAL, "min");
		if (UNEXPECTED(minNode.isUndef())) return zv::Val();
		zv::Val maxNode = boundNode(max, HUGE_VAL, "max");
		if (UNEXPECTED(maxNode.isUndef())) return zv::Val();
		zv::Val floatName = zv::Val::string("float", 5);
		zv::Val floatNode = pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, floatName.raw());
		if (UNEXPECTED(floatNode.isUndef())) return zv::Val();
		zv::Arr genericTypes = zv::Arr::create(2);
		genericTypes.push(std::move(minNode));
		genericTypes.push(std::move(maxNode));
		zv::Args args{floatNode.raw(), genericTypes.raw()};
		return pt_type_new(PT_CLASS_GENERIC_TYPE_NODE, 2, args);
	}

private:
	zend_object *self;

	bool isExact() const { return self->ce == pt_ce_float_range_type; }

	bool slot(uint32_t index, const char *name, double &out) const
	{
		zval *slot = OBJ_PROP_NUM(self, index);
		if (EXPECTED(Z_TYPE_P(slot) == IS_DOUBLE)) {
			out = Z_DVAL_P(slot);
			return true;
		}
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_float_range_type->name), name);
		return false;
	}

	static bool excludesZero(double min, double max)
	{
		return toOrdinal(min) > 0 || toOrdinal(max) < 0;
	}

	static bool push(zv::Arr &types, zv::Val type)
	{
		if (UNEXPECTED(type.isUndef())) return false;
		types.push(std::move(type));
		return true;
	}

	static zv::Val nan() { return pt_type_new_constant_float(static_cast<double>(NAN)); }

	static zv::Val constantBoolean(bool value)
	{
		zval result;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&result, value))) return zv::Val();
		return zv::Val::adopt(result);
	}

	static zv::Val nullType()
	{
		zval result;
		if (UNEXPECTED(!pt_null_type_new(&result))) return zv::Val();
		return zv::Val::adopt(result);
	}

	bool ordinalBounds(zend_long &min, zend_long &max) const
	{
		double minValue = 0.0, maxValue = 0.0;
		if (UNEXPECTED(!bounds(minValue, maxValue))) return false;
		min = toOrdinal(minValue);
		max = toOrdinal(maxValue);
		return true;
	}

	/* $this->isSuperTypeOf($type) — through the object's class, which a
	 * PHP subclass may override */
	zv::Val selfIsSuperTypeOf(zval *type) const
	{
		return isExact() ? isSuperTypeOf(type) : pt_type_op(self, PT_OP_IS_SUPER_TYPE_OF, 1, type);
	}

	/* $result->toAcceptsResult() on an IsSuperTypeOfResult */
	static zv::Val toAcceptsResult(zv::Val result)
	{
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: isSuperTypeOf() must return %s", ZSTR_VAL(pt_ce_is_super_type_of_result->name));
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(result.raw()), PT_LC("toacceptsresult"), 0, NULL);
	}

	static bool appendBound(smart_str &str, double bound, double keywordValue, const char *keyword)
	{
		if (bound == keywordValue) {
			smart_str_appends(&str, keyword);
			return true;
		}
		zv::Val description = pt_constant_float_cast_to_string(bound);
		if (UNEXPECTED(description.isUndef())) return false;
		smart_str_append(&str, Z_STR_P(description.raw()));
		return true;
	}

	/* new IdentifierTypeNode($keyword) at the infinite end, (new
	 * ConstantFloatType($bound))->toPhpDocNode() otherwise */
	static zv::Val boundNode(double bound, double keywordValue, const char *keyword)
	{
		if (bound == keywordValue) {
			zv::Val name = zv::Val::string(keyword, strlen(keyword));
			return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
		}
		zv::Val constant = pt_type_new_constant_float(bound);
		if (UNEXPECTED(constant.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(constant.raw()), PT_LC("tophpdocnode"), 0, NULL);
	}
};

} // namespace phpstanturbo

using phpstanturbo::FloatRangeType;

static bool pt_float_range_bound_call(zend_object *range, const char *lcname, size_t len, double &out)
{
	zv::Val result = pt_type_call(range, lcname, len, 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	if (UNEXPECTED(Z_TYPE_P(result.raw()) != IS_DOUBLE)) {
		zend_type_error("phpstan_turbo: %s bounds must be float", ZSTR_VAL(range->ce->name));
		return false;
	}
	out = Z_DVAL_P(result.raw());
	return true;
}

zv::Val pt_float_range_from_interval(double min, double max)
{
	return FloatRangeType::fromInterval(min, max);
}

zv::Val pt_float_range_create_all(double value, bool greater, bool orEqual)
{
	if (greater) return orEqual ? FloatRangeType::createAllGreaterThanOrEqualTo(value) : FloatRangeType::createAllGreaterThan(value);
	return orEqual ? FloatRangeType::createAllSmallerThanOrEqualTo(value) : FloatRangeType::createAllSmallerThan(value);
}

bool pt_float_range_bounds(zend_object *range, double &min, double &max)
{
	if (EXPECTED(range->ce == pt_ce_float_range_type)) return FloatRangeType(range).bounds(min, max);
	return pt_float_range_bound_call(range, PT_LC("getmin"), min) && pt_float_range_bound_call(range, PT_LC("getmax"), max);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS FloatRangeType(Z_OBJ_P(ZEND_THIS))

/* the try*() family: one Type argument, a ?Type result */
static void pt_frt_try(INTERNAL_FUNCTION_PARAMETERS, zv::Val (FloatRangeType::*method)(zval *) const)
{
	zval *otherType;
	if (!zp::parse<zp::Obj>(execute_data, otherType)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(otherType));
}

/* the createAll*() family: one float $value */
static void pt_frt_create_all(INTERNAL_FUNCTION_PARAMETERS, zv::Val (*factory)(double))
{
	double value;
	if (!zp::parse<zp::Double>(execute_data, value)) RETURN_THROWS();
	PT_RETURN_VAL(factory(value));
}

/* the getSmallerType() family: one PhpVersion argument, never read */
static void pt_frt_comparison_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (FloatRangeType::*method)() const)
{
	zval *phpVersion;
	if (!zp::parse<zp::Zval>(execute_data, phpVersion)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)());
}

/* getMin() / getMax() */
static void pt_frt_bound(INTERNAL_FUNCTION_PARAMETERS, bool (FloatRangeType::*method)(double &) const)
{
	ZEND_PARSE_PARAMETERS_NONE();
	double bound = 0.0;
	if (UNEXPECTED(!(PT_THIS.*method)(bound))) RETURN_THROWS();
	RETURN_DOUBLE(bound);
}

PT_MINIT_REGISTRATION(pt_register_float_range_type)
{
	reg::Class cls("PHPStan\\Type\\FloatRangeType");
	ptdecl::FloatRangeType::declareClass(cls);
	/* "min" and "max" must stay the first two declared properties
	 * (slots::min, slots::max) */
	ptdecl::FloatRangeType::declareProperties(cls);

	cls.method<&FloatRangeType::construct, zp::Double, zp::Double>(sigs::__construct);

	cls.method(sigs::fromInterval, [](INTERNAL_FUNCTION_PARAMETERS) {
		double min, max;
		if (!zp::parse<zp::Double, zp::Double>(execute_data, min, max)) RETURN_THROWS();
		PT_RETURN_VAL(FloatRangeType::fromInterval(min, max));
	});

	cls.method(sigs::toOrdinal, [](INTERNAL_FUNCTION_PARAMETERS) {
		double value;
		if (!zp::parse<zp::Double>(execute_data, value)) RETURN_THROWS();
		RETURN_LONG(FloatRangeType::toOrdinal(value));
	});

	cls.method(sigs::fromOrdinal, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long ordinal;
		if (!zp::parse<zp::Long>(execute_data, ordinal)) RETURN_THROWS();
		RETURN_DOUBLE(FloatRangeType::fromOrdinal(ordinal));
	});

	cls.method(sigs::getOrdinalBounds, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_VAL(FloatRangeType::getOrdinalBoundsValue(type));
	});

	cls.method(sigs::createAllSmallerThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_frt_create_all(INTERNAL_FUNCTION_PARAM_PASSTHRU, FloatRangeType::createAllSmallerThan);
	});

	cls.method(sigs::createAllSmallerThanOrEqualTo, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_frt_create_all(INTERNAL_FUNCTION_PARAM_PASSTHRU, FloatRangeType::createAllSmallerThanOrEqualTo);
	});

	cls.method(sigs::createAllGreaterThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_frt_create_all(INTERNAL_FUNCTION_PARAM_PASSTHRU, FloatRangeType::createAllGreaterThan);
	});

	cls.method(sigs::createAllGreaterThanOrEqualTo, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_frt_create_all(INTERNAL_FUNCTION_PARAM_PASSTHRU, FloatRangeType::createAllGreaterThanOrEqualTo);
	});

	cls.method(sigs::getMin, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_frt_bound(INTERNAL_FUNCTION_PARAM_PASSTHRU, &FloatRangeType::getMin);
	});

	cls.method(sigs::getMax, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_frt_bound(INTERNAL_FUNCTION_PARAM_PASSTHRU, &FloatRangeType::getMax);
	});

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *level;
		if (!zp::parse<zp::Obj>(execute_data, level)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.describe());
	});
	cls.op<PT_OP_DESCRIBE, &FloatRangeType::describe>();

	cls.method<&FloatRangeType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return FloatRangeType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method<&FloatRangeType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &FloatRangeType::isSuperTypeOf>();

	cls.method<&FloatRangeType::equals, zp::TypeObj>(sigs::equals);
	cls.op<PT_OP_EQUALS, &FloatRangeType::equals>();

	cls.method(sigs::generalize, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *precision;
		if (!zp::parse<zp::Obj>(execute_data, precision)) RETURN_THROWS();
		PT_RETURN_VAL(FloatRangeType::generalize());
	});

	cls.method(sigs::getSmallerType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_frt_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &FloatRangeType::getSmallerType);
	});

	cls.method(sigs::getSmallerOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_frt_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &FloatRangeType::getSmallerOrEqualType);
	});

	cls.method(sigs::getGreaterType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_frt_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &FloatRangeType::getGreaterType);
	});

	cls.method(sigs::getGreaterOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_frt_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &FloatRangeType::getGreaterOrEqualType);
	});

	cls.method(sigs::excludesZero, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		bool excludes = false;
		if (UNEXPECTED(!PT_THIS.excludesZeroValue(excludes))) RETURN_THROWS();
		RETURN_BOOL(excludes);
	});

	cls.method<&FloatRangeType::toBoolean>(sigs::toBoolean);

	cls.method<&FloatRangeType::toInteger>(sigs::toInteger);

	cls.method<&FloatRangeType::toString>(sigs::toString);

	cls.method<&FloatRangeType::toAbsoluteNumber>(sigs::toAbsoluteNumber);

	cls.method(sigs::tryUnion, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_frt_try(INTERNAL_FUNCTION_PARAM_PASSTHRU, &FloatRangeType::tryUnion);
	});

	cls.method(sigs::tryIntersect, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_frt_try(INTERNAL_FUNCTION_PARAM_PASSTHRU, &FloatRangeType::tryIntersect);
	});

	cls.method(sigs::tryRemove, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_frt_try(INTERNAL_FUNCTION_PARAM_PASSTHRU, &FloatRangeType::tryRemove);
	});

	cls.method<&FloatRangeType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.shadow(&pt_ce_float_range_type);
}

/* }}} */
