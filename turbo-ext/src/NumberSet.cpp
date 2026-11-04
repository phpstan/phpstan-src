/*
 * PHPStanTurbo\NumberSet — native implementation of PHPStan\Type\NumberSet.
 *
 * Declared as PHPStan\Type\NumberSet itself at activation: final, no parent,
 * no interfaces. State is the twin's `private array $integerIntervals`,
 * `private array $floatIntervals` and `private bool $nan`, declared typed
 * property slots, so the std object handlers do GC/clone.
 *
 * pt_number_set_new() and pt_type_to_number_set() are the native producers'
 * and consumers' way in and out of the PHP arrays, so the operator helpers
 * work on NumberSetData instead. The arithmetic (NumberSetMath) works on that
 * data too: the twin's private methods live only here.
 */

#include "TypeTraits.h"
#include "IetrSupport.h"
#include "generated/NumberSet.h"

#include <cmath>
#include <limits>
#include <vector>

namespace slots = ptdecl::NumberSet::slot;
namespace sigs = ptdecl::NumberSet::sig;

static zend_class_entry *pt_ce_number_set = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\NumberSet. */
class NumberSet
{
public:
	explicit NumberSet(zend_object *self) : self(self) {}

	/* __construct(private array $integerIntervals, private array
	 * $floatIntervals, private bool $nan) */
	void construct(zval *integerIntervals, zval *floatIntervals, bool nan)
	{
		zval *integerSlot = OBJ_PROP_NUM(self, slots::integerIntervals);
		zval *floatSlot = OBJ_PROP_NUM(self, slots::floatIntervals);
		zval *nanSlot = OBJ_PROP_NUM(self, slots::nan);
		zval_ptr_dtor(integerSlot);
		zval_ptr_dtor(floatSlot);
		ZVAL_COPY(integerSlot, integerIntervals);
		ZVAL_COPY(floatSlot, floatIntervals);
		ZVAL_BOOL(nanSlot, nan);
		Z_PROP_FLAG_P(integerSlot) = 0; /* no longer IS_PROP_UNINIT */
		Z_PROP_FLAG_P(floatSlot) = 0;
		Z_PROP_FLAG_P(nanSlot) = 0;
	}

	static zv::Val create(const NumberSetData &data)
	{
		zv::Arr integerIntervals = zv::Arr::create((uint32_t) data.integerIntervals.size());
		for (const NumberSetData::IntegerInterval &interval : data.integerIntervals) {
			zv::Arr pair = zv::Arr::create(2);
			pair.push(interval.min.toVal());
			pair.push(interval.max.toVal());
			integerIntervals.push(zv::Val(std::move(pair)));
		}
		zv::Arr floatIntervals = zv::Arr::create((uint32_t) data.floatIntervals.size());
		for (const NumberSetData::FloatInterval &interval : data.floatIntervals) {
			zv::Arr pair = zv::Arr::create(2);
			pair.push(doubleValue(interval.min));
			pair.push(doubleValue(interval.max));
			floatIntervals.push(zv::Val(std::move(pair)));
		}

		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_number_set) != SUCCESS)) return zv::Val();
		NumberSet(Z_OBJ(object)).construct(integerIntervals.raw(), floatIntervals.raw(), data.nan);
		return zv::Val::adopt(object);
	}

	/* the slots read into `out`; false = pending exception */
	[[nodiscard]] bool read(NumberSetData &out) const
	{
		zval *integerSlot, *floatSlot, *nanSlot;
		if (UNEXPECTED(!slot(slots::integerIntervals, "integerIntervals", integerSlot))) return false;
		if (UNEXPECTED(!slot(slots::floatIntervals, "floatIntervals", floatSlot))) return false;
		if (UNEXPECTED(!slot(slots::nan, "nan", nanSlot))) return false;

		for (zv::ArrayEntry entry : zv::ArrRef(integerSlot)) {
			zval *pair = entry.value().deref().raw();
			out.integerIntervals.push_back({
				NullableLong::from(zend_hash_index_find(Z_ARRVAL_P(pair), 0)),
				NullableLong::from(zend_hash_index_find(Z_ARRVAL_P(pair), 1)),
			});
		}
		for (zv::ArrayEntry entry : zv::ArrRef(floatSlot)) {
			zval *pair = entry.value().deref().raw();
			out.floatIntervals.push_back({
				zval_get_double(zend_hash_index_find(Z_ARRVAL_P(pair), 0)),
				zval_get_double(zend_hash_index_find(Z_ARRVAL_P(pair), 1)),
			});
		}
		out.nan = out.nan || Z_TYPE_P(nanSlot) == IS_TRUE;
		return true;
	}

	/* a copy of the slot; UNDEF = pending exception */
	zv::Val get(uint32_t index, const char *name) const
	{
		zval *value;
		if (UNEXPECTED(!slot(index, name, value))) return zv::Val();
		return zv::Val::copyOf(zv::Ref(value));
	}

private:
	zend_object *self;

	static zv::Val doubleValue(double value)
	{
		zval z;
		ZVAL_DOUBLE(&z, value);
		return zv::Val::adopt(z);
	}

	bool slot(uint32_t index, const char *name, zval *&out) const
	{
		zval *value = OBJ_PROP_NUM(self, index);
		if (EXPECTED(Z_TYPE_P(value) != IS_UNDEF)) {
			out = value;
			return true;
		}
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_number_set->name), name);
		return false;
	}
};


/* {{{ the arithmetic of the operators */

using namespace phpstanturbo::ietr;

/* Mirrors the private methods of the twin's arithmetic: calculate() and
 * the helpers it pairs the members with. */
class NumberSetMath
{
public:
	/* an integer bound of divideIntegers(): an int, or the twin's -INF / INF
	 * (inf -1 / 1) */
	struct Bound
	{
		int inf;
		zend_long value;

		static Bound of(zend_long v) { return {0, v}; }
		static Bound infinity(int sign) { return {sign, 0}; }

		bool positive() const { return inf != 0 ? inf > 0 : value > 0; }
	};

	/* [min, max, whether an infinite bound is a value] */
	struct FloatBounds
	{
		double min;
		double max;
		bool infinities;
	};

	/* The denormal next to zero, the divisor of smallest magnitude. */
	static constexpr double SMALLEST_POSITIVE_FLOAT = 4.9E-324;

	/* Mirrors calculate(). */
	static zv::Val calculate(IetrBinaryKind kind, const NumberSetData &leftIn, const NumberSetData &rightIn, zval *phpVersion)
	{
		NumberSetData left = leftIn;
		NumberSetData right = rightIn;
		if ((zend_long) countMembers(left) * (zend_long) countMembers(right) > PT_IETR_CALCULATE_SCALARS_LIMIT) {
			left = toHull(left);
			right = toHull(right);
		}

		bool isShift = kind == IETR_SHIFT_LEFT || kind == IETR_SHIFT_RIGHT;
		zv::Arr integerTypes = zv::Arr::create(4);
		zv::Arr floatTypes = zv::Arr::create(4);
		for (const NumberSetData::IntegerInterval &leftInterval : left.integerIntervals) {
			for (const NumberSetData::IntegerInterval &rightInterval : right.integerIntervals) {
				if (kind == IETR_DIV) {
					zv::Val integerType, floatType;
					if (UNEXPECTED(!divideIntegers(leftInterval, rightInterval, phpVersion, integerType, floatType))) return zv::Val();
					integerTypes.push(std::move(integerType));
					floatTypes.push(std::move(floatType));
					continue;
				}

				IETR_VAL(integerType, calculateIntegers(kind, leftInterval, rightInterval));
				integerTypes.push(std::move(integerType));
			}
			if (isShift || right.floatIntervals.empty()) continue;

			FloatBounds leftFloats = integerToFloatInterval(leftInterval);
			for (const NumberSetData::FloatInterval &rightInterval : right.floatIntervals) {
				IETR_VAL(floatType, calculateFloats(kind, leftFloats, {rightInterval.min, rightInterval.max, true}, phpVersion));
				floatTypes.push(std::move(floatType));
			}
		}
		for (const NumberSetData::FloatInterval &leftInterval : left.floatIntervals) {
			if (isShift) break;

			FloatBounds leftFloats = {leftInterval.min, leftInterval.max, true};
			for (const NumberSetData::IntegerInterval &rightInterval : right.integerIntervals) {
				FloatBounds rightFloats = integerToFloatInterval(rightInterval);
				FloatBounds divisors[3];
				size_t divisorCount = 1;
				divisors[0] = rightFloats;
				if (kind == IETR_DIV) divisorCount = splitIntegerDivisor(rightFloats, divisors);
				for (size_t i = 0; i < divisorCount; i++) {
					IETR_VAL(floatType, calculateFloats(kind, leftFloats, divisors[i], phpVersion));
					floatTypes.push(std::move(floatType));
				}
			}
			for (const NumberSetData::FloatInterval &rightInterval : right.floatIntervals) {
				IETR_VAL(floatType, calculateFloats(kind, leftFloats, {rightInterval.min, rightInterval.max, true}, phpVersion));
				floatTypes.push(std::move(floatType));
			}
		}

		bool leftHasFloats = !left.floatIntervals.empty() || left.nan;
		bool rightHasFloats = !right.floatIntervals.empty() || right.nan;
		if (isShift) {
			// a float operand is converted to an integer first
			if ((leftHasFloats && !isEmpty(right)) || (rightHasFloats && !isEmpty(left))) {
				IETR_VAL(integerType, newIntegerType());
				integerTypes.push(std::move(integerType));
			}

			return unionOf(integerTypes);
		}

		if ((left.nan && !isEmpty(right)) || (right.nan && !isEmpty(left))) {
			IETR_VAL(nanType, newConstantFloatType(std::numeric_limits<double>::quiet_NaN()));
			floatTypes.push(std::move(nanType));
		}

		// any float but NAN reads better as float when no operand narrowed its floats
		IETR_VAL(floatType, unionOf(floatTypes));
		if (!hasFloatRange(left) && !hasFloatRange(right)) {
			IETR_VAL(fullRange, pt_float_range_from_interval(-HUGE_VAL, HUGE_VAL));
			IETR_VAL(isFullRange, callOn(floatType.raw(), mEquals, 1, fullRange.raw()));
			if (Z_TYPE_P(isFullRange.raw()) == IS_TRUE) {
				floatType = newFloatType();
				if (UNEXPECTED(floatType.isUndef())) return zv::Val();
			}
		}

		IETR_VAL(integerType, unionOf(integerTypes));
		IETR_VAL(resultType, union2(integerType.raw(), floatType.raw()));
		if (kind == IETR_DIV && !leftHasFloats && !rightHasFloats) {
			bool anyInteger = hasAnyInteger(left) || hasAnyInteger(right);
			if (!anyInteger) {
				IETR_VAL(plainInteger, newIntegerType());
				IETR_VAL(isPlainInteger, callOn(integerType.raw(), mEquals, 1, plainInteger.raw()));
				anyInteger = Z_TYPE_P(isPlainInteger.raw()) == IS_TRUE;
			}
			if (anyInteger) {
				// the quotients of any integer are any number, which stays lenient
				return toBenevolentUnion(resultType.raw());
			}
		}

		return resultType;
	}

private:
	static size_t countMembers(const NumberSetData &set)
	{
		return set.integerIntervals.size() + set.floatIntervals.size() + (set.nan ? 1 : 0);
	}

	static bool isEmpty(const NumberSetData &set)
	{
		return set.integerIntervals.empty() && set.floatIntervals.empty() && !set.nan;
	}

	/* Mirrors hasFloatRange(). */
	static bool hasFloatRange(const NumberSetData &set)
	{
		for (const NumberSetData::FloatInterval &interval : set.floatIntervals) {
			if (interval.min >= interval.max || (set.nan && interval.min == -HUGE_VAL && interval.max == HUGE_VAL)) continue;
			return true;
		}
		return false;
	}

	/* Mirrors hasAnyInteger(). */
	static bool hasAnyInteger(const NumberSetData &set)
	{
		for (const NumberSetData::IntegerInterval &interval : set.integerIntervals) {
			if (interval.min.isNull && interval.max.isNull) return true;
		}
		return false;
	}

	/* Mirrors toHull(); min() / max() keep the first of equal values. */
	static NumberSetData toHull(const NumberSetData &set)
	{
		NumberSetData hull;
		if (!set.integerIntervals.empty()) {
			phpstanturbo::NullableLong min = set.integerIntervals[0].min;
			phpstanturbo::NullableLong max = set.integerIntervals[0].max;
			for (const NumberSetData::IntegerInterval &interval : set.integerIntervals) {
				min = min.isNull || interval.min.isNull ? NullableLong::null() : NullableLong::of(interval.min.value < min.value ? interval.min.value : min.value);
				max = max.isNull || interval.max.isNull ? NullableLong::null() : NullableLong::of(interval.max.value > max.value ? interval.max.value : max.value);
			}
			hull.integerIntervals.push_back({min, max});
		}
		if (!set.floatIntervals.empty()) {
			double min = set.floatIntervals[0].min;
			double max = set.floatIntervals[0].max;
			for (const NumberSetData::FloatInterval &interval : set.floatIntervals) {
				if (interval.min < min) min = interval.min;
				if (interval.max > max) max = interval.max;
			}
			hull.floatIntervals.push_back({min, max});
		}
		hull.nan = set.nan;
		return hull;
	}

	/* Mirrors integerToFloatInterval(). */
	static FloatBounds integerToFloatInterval(const NumberSetData::IntegerInterval &interval)
	{
		return {
			interval.min.isNull ? -HUGE_VAL : static_cast<double>(interval.min.value),
			interval.max.isNull ? HUGE_VAL : static_cast<double>(interval.max.value),
			false,
		};
	}

	/* Mirrors splitIntegerDivisor(): the count of intervals written */
	static size_t splitIntegerDivisor(FloatBounds interval, FloatBounds out[3])
	{
		if (interval.min > 0.0 || interval.max < 0.0) {
			out[0] = interval;
			return 1;
		}

		size_t count = 0;
		if (interval.min < 0.0) out[count++] = {interval.min, -1.0, false};
		out[count++] = {0.0, 0.0, false};
		if (interval.max > 0.0) out[count++] = {1.0, interval.max, false};
		return count;
	}

	/* $out = $a <op> $b of two ints through the engine (overflowing into a
	 * float); false = pending exception */
	[[nodiscard]] static bool longOp(pt_ietr_binary_op fn, zend_long a, zend_long b, zval *out)
	{
		zval x, y;
		ZVAL_LONG(&x, a);
		ZVAL_LONG(&y, b);
		return scalarOp(fn, &x, &y, out);
	}

	/* is_int($value) ? $value : null of an int|float zval */
	static NullableLong intOrNull(const zval *value)
	{
		return Z_TYPE_P(value) == IS_LONG ? NullableLong::of(Z_LVAL_P(value)) : NullableLong::null();
	}

	/* Mirrors shiftLeftOverflows(): `($value << $shift) >> $shift !== $value`
	 * through the engine's operators; false = pending exception */
	[[nodiscard]] static bool shiftLeftOverflows(zend_long value, zend_long shift, bool &out)
	{
		zval shifted, restored, shiftZv;
		ZVAL_LONG(&shiftZv, shift);
		if (UNEXPECTED(!longOp(shift_left_function, value, shift, &shifted))) return false;
		if (UNEXPECTED(!scalarOp(shift_right_function, &shifted, &shiftZv, &restored))) return false;
		out = Z_TYPE(restored) != IS_LONG || Z_LVAL(restored) != value;
		return true;
	}

	/* Mirrors calculateIntegers(). */
	static zv::Val calculateIntegers(IetrBinaryKind kind, const NumberSetData::IntegerInterval &left, const NumberSetData::IntegerInterval &right)
	{
		NullableLong leftMin = left.min, leftMax = left.max;
		NullableLong rightMin = right.min, rightMax = right.max;

		if (kind == IETR_SHIFT_LEFT || kind == IETR_SHIFT_RIGHT) {
			if (rightMin.isNull || rightMax.isNull || rightMin.value != rightMax.value) return newIntegerType();
			zend_long shift = rightMin.value;
			if (shift < 0) return newErrorType();
			pt_ietr_binary_op fn = kind == IETR_SHIFT_LEFT ? shift_left_function : shift_right_function;
			if (kind == IETR_SHIFT_LEFT) {
				// an overflowing shift wraps around, which breaks the monotonicity the bounds rely on;
				// open bounds still include the platform's minimum or maximum integer
				bool overflows = false;
				if (UNEXPECTED(!shiftLeftOverflows(leftMin.isNull ? ZEND_LONG_MIN : leftMin.value, shift, overflows))) return zv::Val();
				if (!overflows && UNEXPECTED(!shiftLeftOverflows(leftMax.isNull ? ZEND_LONG_MAX : leftMax.value, shift, overflows))) return zv::Val();
				if (overflows) return newIntegerType();
			}

			zval min, max;
			ZVAL_NULL(&min);
			ZVAL_NULL(&max);
			if (!leftMin.isNull && UNEXPECTED(!longOp(fn, leftMin.value, shift, &min))) return zv::Val();
			if (!leftMax.isNull && UNEXPECTED(!longOp(fn, leftMax.value, shift, &max))) return zv::Val();
			return fromIntervalZv(&min, &max);
		}

		if (!leftMin.isNull && !leftMax.isNull && leftMin.value == leftMax.value && !rightMin.isNull && !rightMax.isNull && rightMin.value == rightMax.value) {
			pt_ietr_binary_op fn = kind == IETR_PLUS ? add_function : (kind == IETR_MINUS ? sub_function : mul_function);
			zval value;
			if (UNEXPECTED(!longOp(fn, leftMin.value, rightMin.value, &value))) return zv::Val();
			return typeFromValue(&value);
		}

		zval min, max;
		ZVAL_NULL(&min);
		ZVAL_NULL(&max);
		if (kind == IETR_PLUS) {
			if (!leftMin.isNull && !rightMin.isNull && UNEXPECTED(!longOp(add_function, leftMin.value, rightMin.value, &min))) return zv::Val();
			if (!leftMax.isNull && !rightMax.isNull && UNEXPECTED(!longOp(add_function, leftMax.value, rightMax.value, &max))) return zv::Val();
		} else if (kind == IETR_MINUS) {
			// [a, b] - [c, d] is [a - d, b - c]
			if (!leftMin.isNull && !rightMax.isNull && UNEXPECTED(!longOp(sub_function, leftMin.value, rightMax.value, &min))) return zv::Val();
			if (!leftMax.isNull && !rightMin.isNull && UNEXPECTED(!longOp(sub_function, leftMax.value, rightMin.value, &max))) return zv::Val();
		} else {
			zval minusInf, inf;
			ZVAL_DOUBLE(&minusInf, -HUGE_VAL);
			ZVAL_DOUBLE(&inf, HUGE_VAL);
			zval a, b, c, d;
			setNullableLong(&a, leftMin);
			setNullableLong(&b, leftMax);
			setNullableLong(&c, rightMin);
			setNullableLong(&d, rightMax);
			zval corners[4];
			if (UNEXPECTED(!mulOrZero(&a, &c, &minusInf, &minusInf, &corners[0]))) return zv::Val();
			if (UNEXPECTED(!mulOrZero(&a, &d, &minusInf, &inf, &corners[1]))) return zv::Val();
			if (UNEXPECTED(!mulOrZero(&b, &c, &inf, &minusInf, &corners[2]))) return zv::Val();
			if (UNEXPECTED(!mulOrZero(&b, &d, &inf, &inf, &corners[3]))) return zv::Val();
			zv::Arr cornerList = zv::Arr::create(4);
			for (zval &corner : corners) cornerList.push(zv::Val::adopt(corner));
			IETR_VAL(minValue, phpMinMax(false, 1, cornerList.raw()));
			IETR_VAL(maxValue, phpMinMax(true, 1, cornerList.raw()));
			ZVAL_COPY_VALUE(&min, minValue.raw());
			ZVAL_COPY_VALUE(&max, maxValue.raw());
		}

		return fromInterval(intOrNull(&min), intOrNull(&max));
	}

	/* Mirrors divideIntegers(); false = pending exception */
	[[nodiscard]] static bool divideIntegers(const NumberSetData::IntegerInterval &left, const NumberSetData::IntegerInterval &right, zval *phpVersion, zv::Val &integerTypeOut, zv::Val &floatTypeOut)
	{
		NullableLong leftMin = left.min, leftMax = left.max;
		NullableLong rightMin = right.min, rightMax = right.max;

		if (!leftMin.isNull && !leftMax.isNull && leftMin.value == leftMax.value && !rightMin.isNull && !rightMax.isNull && rightMin.value == rightMax.value && rightMin.value != 0) {
			zval value;
			if (UNEXPECTED(!longOp(div_function, leftMin.value, rightMin.value, &value))) return false;
			if (Z_TYPE(value) == IS_LONG) {
				integerTypeOut = newConstantIntegerType(Z_LVAL(value));
				floatTypeOut = newNeverType();
			} else {
				integerTypeOut = newNeverType();
				floatTypeOut = newConstantFloatType(Z_DVAL(value));
			}
			return !integerTypeOut.isUndef() && !floatTypeOut.isUndef();
		}

		zv::Arr integerTypes = zv::Arr::create(2);
		zv::Arr floatTypes = zv::Arr::create(5);
		NumberSetData::IntegerInterval divisors[2];
		size_t divisorCount = 0;
		if (rightMin.isNull || rightMin.value < 0) {
			divisors[divisorCount++] = {rightMin, rightMax.isNull ? NullableLong::of(-1) : NullableLong::of(rightMax.value < -1 ? rightMax.value : -1)};
		}
		if (rightMax.isNull || rightMax.value > 0) {
			divisors[divisorCount++] = {rightMin.isNull ? NullableLong::of(1) : NullableLong::of(rightMin.value > 1 ? rightMin.value : 1), rightMax};
		}
		for (size_t i = 0; i < divisorCount; i++) {
			NullableLong divisorMin = divisors[i].min, divisorMax = divisors[i].max;
			if (!divisorMin.isNull && !divisorMax.isNull && divisorMin.value > divisorMax.value) continue;

			bool hasExtremes = false;
			Bound minimum = Bound::of(0), maximum = Bound::of(0);
			Bound numerators[2] = {leftMin.isNull ? Bound::infinity(-1) : Bound::of(leftMin.value), leftMax.isNull ? Bound::infinity(1) : Bound::of(leftMax.value)};
			Bound divisorBounds[2] = {divisorMin.isNull ? Bound::infinity(-1) : Bound::of(divisorMin.value), divisorMax.isNull ? Bound::infinity(1) : Bound::of(divisorMax.value)};
			for (Bound numerator : numerators) {
				for (Bound divisor : divisorBounds) {
					Bound floor, ceil;
					divideBound(numerator, divisor, floor, ceil);
					// min() / max() keep the first of equal values
					if (!hasExtremes) {
						minimum = ceil;
						maximum = floor;
						hasExtremes = true;
						continue;
					}
					if (lessThanBound(ceil, minimum)) minimum = ceil;
					if (lessThanBound(maximum, floor)) maximum = floor;
				}
			}
			zv::Val integerType = fromInterval(toIntBound(minimum), toIntBound(maximum));
			if (UNEXPECTED(integerType.isUndef())) return false;
			integerTypes.push(std::move(integerType));
			zv::Val floatType = calculateFloats(IETR_DIV, integerToFloatInterval(left), integerToFloatInterval({divisorMin, divisorMax}), phpVersion);
			if (UNEXPECTED(floatType.isUndef())) return false;
			floatTypes.push(std::move(floatType));
		}

		bool hasZeroDivisor = (rightMin.isNull || rightMin.value <= 0) && (rightMax.isNull || rightMax.value >= 0);
		if (hasZeroDivisor) {
			bool throws;
			if (UNEXPECTED(!pt_php_version_answer(phpVersion, PT_PHP_VERSION_THROWS_ON_DIVISION_BY_ZERO, throws))) return false;
			if (!throws) {
				if (leftMax.isNull || leftMax.value > 0) {
					zv::Val inf = newConstantFloatType(HUGE_VAL);
					if (UNEXPECTED(inf.isUndef())) return false;
					floatTypes.push(std::move(inf));
				}
				if (leftMin.isNull || leftMin.value < 0) {
					zv::Val minusInf = newConstantFloatType(-HUGE_VAL);
					if (UNEXPECTED(minusInf.isUndef())) return false;
					floatTypes.push(std::move(minusInf));
				}
				if ((leftMin.isNull || leftMin.value <= 0) && (leftMax.isNull || leftMax.value >= 0)) {
					zv::Val nan = newConstantFloatType(std::numeric_limits<double>::quiet_NaN());
					if (UNEXPECTED(nan.isUndef())) return false;
					floatTypes.push(std::move(nan));
				}
			}
		}

		integerTypeOut = unionOf(integerTypes);
		if (UNEXPECTED(integerTypeOut.isUndef())) return false;
		floatTypeOut = unionOf(floatTypes);
		return !floatTypeOut.isUndef();
	}

	/* $a < $b of two int|±INF values */
	static bool lessThanBound(Bound a, Bound b)
	{
		if (a.inf != b.inf) return a.inf < b.inf;
		if (a.inf != 0) return false;
		return a.value < b.value;
	}

	/* Mirrors divideBound(): [floor, ceil] */
	static void divideBound(Bound numerator, Bound divisor, Bound &floor, Bound &ceil)
	{
		if (divisor.inf != 0) {
			if (numerator.inf == 0 && numerator.value == 0) {
				floor = ceil = Bound::of(0);
				return;
			}
			if (numerator.positive() == divisor.positive()) {
				floor = Bound::of(0);
				ceil = Bound::of(1);
			} else {
				floor = Bound::of(-1);
				ceil = Bound::of(0);
			}
			return;
		}

		if (numerator.inf != 0) {
			floor = ceil = Bound::infinity(numerator.positive() == divisor.positive() ? 1 : -1);
			return;
		}

		if (numerator.value == ZEND_LONG_MIN && divisor.value == -1) {
			// the quotient is the float right past the integer range
			floor = Bound::of(ZEND_LONG_MAX);
			ceil = Bound::infinity(1);
			return;
		}

		zend_long quotient = numerator.value / divisor.value;
		if (quotient * divisor.value == numerator.value) {
			floor = ceil = Bound::of(quotient);
			return;
		}

		// intdiv() truncates towards zero
		if ((numerator.value < 0) != (divisor.value < 0)) {
			floor = Bound::of(quotient - 1);
			ceil = Bound::of(quotient);
			return;
		}

		floor = Bound::of(quotient);
		ceil = Bound::of(quotient + 1);
	}

	/* Mirrors toIntBound() of an int or ±INF: an infinity is no bound */
	static NullableLong toIntBound(Bound value)
	{
		return value.inf != 0 ? NullableLong::null() : NullableLong::of(value.value);
	}

	/* Mirrors calculateFloats(). */
	static zv::Val calculateFloats(IetrBinaryKind kind, FloatBounds left, FloatBounds right, zval *phpVersion)
	{
		double leftMin = left.min, leftMax = left.max;
		double rightMin = right.min, rightMax = right.max;
		bool nan = false;
		zv::Arr resultTypes = zv::Arr::create(4);

		bool leftHasZero = leftMin <= 0.0 && leftMax >= 0.0;
		bool rightHasZero = rightMin <= 0.0 && rightMax >= 0.0;
		bool leftHasNegativeInfinity = left.infinities && leftMin == -HUGE_VAL;
		bool leftHasPositiveInfinity = left.infinities && leftMax == HUGE_VAL;
		bool rightHasNegativeInfinity = right.infinities && rightMin == -HUGE_VAL;
		bool rightHasPositiveInfinity = right.infinities && rightMax == HUGE_VAL;
		bool leftHasInfinity = leftHasNegativeInfinity || leftHasPositiveInfinity;
		bool rightHasInfinity = rightHasNegativeInfinity || rightHasPositiveInfinity;

		double rightIntervals[2][2] = {{rightMin, rightMax}, {0.0, 0.0}};
		size_t rightIntervalCount = 1;
		if (kind == IETR_PLUS) {
			nan = (leftHasPositiveInfinity && rightHasNegativeInfinity) || (leftHasNegativeInfinity && rightHasPositiveInfinity);
		} else if (kind == IETR_MINUS) {
			nan = (leftHasPositiveInfinity && rightHasPositiveInfinity) || (leftHasNegativeInfinity && rightHasNegativeInfinity);
		} else if (kind == IETR_MUL) {
			nan = (leftHasZero && rightHasInfinity) || (rightHasZero && leftHasInfinity);
		} else {
			nan = leftHasInfinity && rightHasInfinity;
			if (rightHasZero) {
				// both zeros are divisors: x / 0.0 and x / -0.0 are opposite infinities
				rightIntervalCount = 0;
				if (rightMin < 0.0) {
					rightIntervals[rightIntervalCount][0] = rightMin;
					rightIntervals[rightIntervalCount][1] = -SMALLEST_POSITIVE_FLOAT;
					rightIntervalCount++;
				}
				if (rightMax > 0.0) {
					rightIntervals[rightIntervalCount][0] = SMALLEST_POSITIVE_FLOAT;
					rightIntervals[rightIntervalCount][1] = rightMax;
					rightIntervalCount++;
				}
				bool throws;
				if (UNEXPECTED(!pt_php_version_answer(phpVersion, PT_PHP_VERSION_THROWS_ON_DIVISION_BY_ZERO, throws))) return zv::Val();
				if (!throws) {
					nan = nan || leftHasZero;
					if (leftMin < 0.0 || leftMax > 0.0) {
						IETR_VAL(full, pt_float_range_from_interval(-HUGE_VAL, HUGE_VAL));
						resultTypes.push(std::move(full));
					}
				}
			}
		}

		for (size_t i = 0; i < rightIntervalCount; i++) {
			double intervalMin = rightIntervals[i][0];
			double intervalMax = rightIntervals[i][1];
			bool hasCorner = false;
			double cornerMin = 0.0, cornerMax = 0.0;
			auto addCorner = [&](double corner) {
				if (!hasCorner) {
					cornerMin = corner;
					cornerMax = corner;
					hasCorner = true;
					return;
				}
				// min() / max() keep the first of equal values
				if (corner < cornerMin) cornerMin = corner;
				if (corner > cornerMax) cornerMax = corner;
			};
			for (double leftBound : {leftMin, leftMax}) {
				for (double rightBound : {intervalMin, intervalMax}) {
					double corner = calculateFloat(kind, leftBound, rightBound);
					if (!std::isnan(corner)) {
						addCorner(corner);
						continue;
					}

					// the NAN of a corner is added above, the floats next to it bound the rest
					double leftValues[2], rightValues[2];
					size_t leftCount = getFloatBoundWithInnerNeighbour(leftBound, leftMin, leftMax, leftValues);
					size_t rightCount = getFloatBoundWithInnerNeighbour(rightBound, intervalMin, intervalMax, rightValues);
					for (size_t l = 0; l < leftCount; l++) {
						for (size_t r = 0; r < rightCount; r++) {
							corner = calculateFloat(kind, leftValues[l], rightValues[r]);
							if (std::isnan(corner)) continue;
							addCorner(corner);
						}
					}
				}
			}

			if (!hasCorner) continue;

			IETR_VAL(range, pt_float_range_from_interval(cornerMin, cornerMax));
			resultTypes.push(std::move(range));
		}

		if (nan) {
			IETR_VAL(nanType, newConstantFloatType(std::numeric_limits<double>::quiet_NaN()));
			resultTypes.push(std::move(nanType));
		}

		return unionOf(resultTypes);
	}

	/* Mirrors calculateFloat(). */
	static double calculateFloat(IetrBinaryKind kind, double left, double right)
	{
		if (kind == IETR_PLUS) return left + right;
		if (kind == IETR_MINUS) return left - right;
		if (kind == IETR_MUL) return left * right;

		return left / right;
	}

	/* Mirrors getFloatBoundWithInnerNeighbour(): the count of values written */
	static size_t getFloatBoundWithInnerNeighbour(double bound, double min, double max, double out[2])
	{
		out[0] = bound;
		if (min == max) return 1;

		if (bound == HUGE_VAL) {
			out[1] = std::numeric_limits<double>::max();
			return 2;
		}
		if (bound == -HUGE_VAL) {
			out[1] = -std::numeric_limits<double>::max();
			return 2;
		}
		if (bound == 0.0) {
			out[1] = bound == min ? SMALLEST_POSITIVE_FLOAT : -SMALLEST_POSITIVE_FLOAT;
			return 2;
		}

		return 1;
	}

public:
	/* Mirrors power(): PHP null for its null */
	static zv::Val power(const NumberSetData &base, const NumberSetData &exponent)
	{
		const NumberSetData::IntegerInterval *baseBounds = getBoundedIntegerInterval(base);
		const NumberSetData::IntegerInterval *exponentBounds = getBoundedIntegerInterval(exponent);
		if (baseBounds == nullptr || exponentBounds == nullptr) {
			return zv::Val::null();
		}

		return powerIntegerIntervals(baseBounds->min, baseBounds->max, exponentBounds->min, exponentBounds->max);
	}

	/* Mirrors getBoundedIntegerInterval(): nullptr for its null */
	static const NumberSetData::IntegerInterval *getBoundedIntegerInterval(const NumberSetData &set)
	{
		if (!set.floatIntervals.empty() || set.nan || set.integerIntervals.size() != 1) return nullptr;
		const NumberSetData::IntegerInterval &interval = set.integerIntervals[0];
		if (interval.min.isNull && interval.max.isNull) return nullptr;
		return &interval;
	}

	/* Mirrors powerIntegerIntervals(): PHP null for its null */
	static zv::Val powerIntegerIntervals(NullableLong baseMin, NullableLong baseMax, NullableLong exponentMin, NullableLong exponentMax)
	{
		if (exponentMin.isNull || exponentMin.value < 0) {
			return zv::Val::null();
		}

		if (!exponentMax.isNull && exponentMax.value == 0) {
			return newConstantIntegerType(1);
		}

		if (!baseMin.isNull && baseMin.value >= 0) {
			// with a non-negative base and exponent the power grows with the base; it grows
			// with the exponent too except for the base 0
			NullableLong min = baseMin.value == 0 ? NullableLong::of(0) : integerPower(baseMin.value, exponentMin.value);
			NullableLong max;
			if (baseMax.isNull || (baseMax.value > 1 && exponentMax.isNull)) {
				max = NullableLong::null();
			} else if (baseMax.value == 0) {
				max = NullableLong::of(exponentMin.value == 0 ? 1 : 0);
			} else if (exponentMax.isNull) {
				max = NullableLong::of(1);
			} else {
				max = integerPower(baseMax.value, exponentMax.value);
				if (max.isNull) {
					return zv::Val::null();
				}
			}

			if (min.isNull) {
				return zv::Val::null();
			}

			return fromInterval(min, max);
		}

		// a negative base alternates the sign with the parity of the exponent
		if (exponentMax.isNull || exponentMax.value - exponentMin.value >= PT_IETR_CALCULATE_SCALARS_LIMIT) {
			return zv::Val::null();
		}

		zv::Arr results = zv::Arr::create((uint32_t) (exponentMax.value - exponentMin.value + 1));
		for (zend_long i = 0; i <= exponentMax.value - exponentMin.value; i++) {
			bool overflow = false;
			IETR_VAL(result, powerIntegerInterval(baseMin, baseMax, exponentMin.value + i, overflow));
			if (overflow) {
				return zv::Val::null();
			}
			results.push(std::move(result));
		}

		return unionOf(results);
	}

	/* Mirrors powerIntegerInterval(): `overflow` for its null */
	static zv::Val powerIntegerInterval(NullableLong baseMin, NullableLong baseMax, zend_long exponent, bool &overflow)
	{
		if (exponent == 0) {
			return newConstantIntegerType(1);
		}

		NullableLong minBase, maxBase;
		if (exponent % 2 == 1) {
			// an odd power keeps the order of the bases
			minBase = baseMin;
			maxBase = baseMax;
		} else if (!baseMax.isNull && baseMax.value <= 0) {
			// an even power of non-positive bases reverses their order
			minBase = baseMax;
			maxBase = baseMin;
		} else if (!baseMin.isNull && baseMin.value >= 0) {
			minBase = baseMin;
			maxBase = baseMax;
		} else {
			// an even power of bases around 0 is the largest at the end further from 0
			if (!baseMin.isNull && baseMin.value == ZEND_LONG_MIN) {
				// -PHP_INT_MIN overflows into a float
				overflow = true;
				return zv::Val::null();
			}
			minBase = NullableLong::of(0);
			maxBase = baseMin.isNull || baseMax.isNull ? NullableLong::null() : NullableLong::of(std::max(-baseMin.value, baseMax.value));
		}

		NullableLong min = minBase.isNull ? NullableLong::null() : integerPower(minBase.value, exponent);
		NullableLong max = maxBase.isNull ? NullableLong::null() : integerPower(maxBase.value, exponent);
		if ((!minBase.isNull && min.isNull) || (!maxBase.isNull && max.isNull)) {
			overflow = true;
			return zv::Val::null();
		}

		return fromInterval(min, max);
	}

	/* Mirrors integerPower(): $base ** $exponent of a non-negative exponent, null when
	 * it overflows into a float; PHP gives an integer exactly when the power fits */
	static NullableLong integerPower(zend_long base, zend_long exponent)
	{
		zend_long result = 1;
		zend_long factor = base;
		while (exponent > 0) {
			if (exponent % 2 == 1) {
				if (__builtin_mul_overflow(result, factor, &result)) return NullableLong::null();
				exponent--;
				continue;
			}
			exponent /= 2;
			if (__builtin_mul_overflow(factor, factor, &factor)) return NullableLong::null();
		}

		return NullableLong::of(result);
	}

	/* Mirrors intDivide(). */
	static zv::Val intDivide(const NumberSetData &left, const NumberSetData &right)
	{
		zv::Arr resultTypes = zv::Arr::create((uint32_t) (left.integerIntervals.size() * right.integerIntervals.size() * 2));
		for (const NumberSetData::IntegerInterval &numerator : left.integerIntervals) {
			Bound numeratorMin = numerator.min.isNull ? Bound::infinity(-1) : Bound::of(numerator.min.value);
			Bound numeratorMax = numerator.max.isNull ? Bound::infinity(1) : Bound::of(numerator.max.value);
			for (const NumberSetData::IntegerInterval &divisor : right.integerIntervals) {
				Bound divisorMin = divisor.min.isNull ? Bound::infinity(-1) : Bound::of(divisor.min.value);
				Bound divisorMax = divisor.max.isNull ? Bound::infinity(1) : Bound::of(divisor.max.value);
				if (lessThanBound(divisorMin, Bound::of(0))) {
					Bound minusOne = Bound::of(-1);
					IETR_VAL(negative, intDivideIntervals(numeratorMin, numeratorMax, divisorMin, lessThanBound(minusOne, divisorMax) ? minusOne : divisorMax));
					resultTypes.push(std::move(negative));
				}
				if (!lessThanBound(Bound::of(0), divisorMax)) {
					continue;
				}

				Bound one = Bound::of(1);
				IETR_VAL(positive, intDivideIntervals(numeratorMin, numeratorMax, lessThanBound(divisorMin, one) ? one : divisorMin, divisorMax));
				resultTypes.push(std::move(positive));
			}
		}

		if (zend_hash_num_elements(resultTypes.table()) == 0) {
			return newErrorType();
		}

		return unionOf(resultTypes);
	}

	/* Mirrors intDivideIntervals(). */
	static zv::Val intDivideIntervals(Bound numeratorMin, Bound numeratorMax, Bound divisorMin, Bound divisorMax)
	{
		Bound results[4] = {
			intDivideBound(numeratorMin, divisorMin),
			intDivideBound(numeratorMin, divisorMax),
			intDivideBound(numeratorMax, divisorMin),
			intDivideBound(numeratorMax, divisorMax),
		};
		Bound min = results[0];
		Bound max = results[0];
		for (const Bound &result : results) {
			if (lessThanBound(result, min)) min = result;
			if (lessThanBound(max, result)) max = result;
		}

		return fromInterval(
			min.inf != 0 ? NullableLong::null() : NullableLong::of(min.value),
			max.inf != 0 ? NullableLong::null() : NullableLong::of(max.value)
		);
	}

	/* Mirrors intDivideBound(). */
	static Bound intDivideBound(Bound numerator, Bound divisor)
	{
		if (numerator.inf != 0) {
			if (divisor.inf != 0) {
				// the interval already contains 0 through a neighbouring corner
				return Bound::of(0);
			}

			return (numerator.inf > 0) == (divisor.value > 0) ? Bound::infinity(1) : Bound::infinity(-1);
		}

		if (divisor.inf != 0) {
			return Bound::of(0);
		}

		if (numerator.value == ZEND_LONG_MIN && divisor.value == -1) {
			// throws ArithmeticError, the closest valid result is intdiv(PHP_INT_MIN + 1, -1)
			return Bound::of(ZEND_LONG_MAX);
		}

		return Bound::of(numerator.value / divisor.value);
	}

	/* Mirrors modulo(). */
	static zv::Val modulo(const NumberSetData &left, const NumberSetData &right)
	{
		NullableLong leftMin, leftMax, divisorMin, divisorMax;
		getIntegerHull(left, leftMin, leftMax);
		getIntegerHull(right, divisorMin, divisorMax);
		NullableLong maxMagnitude = getMaxModuloMagnitude(divisorMin, divisorMax);

		NullableLong rangeMax;
		if (!leftMax.isNull && leftMax.value <= 0) {
			rangeMax = NullableLong::of(0);
		} else if (maxMagnitude.isNull) {
			rangeMax = leftMax;
		} else if (leftMax.isNull) {
			rangeMax = maxMagnitude;
		} else {
			rangeMax = NullableLong::of(std::min(leftMax.value, maxMagnitude.value));
		}

		NullableLong rangeMin;
		if (!leftMin.isNull && leftMin.value >= 0) {
			rangeMin = NullableLong::of(0);
		} else if (maxMagnitude.isNull) {
			rangeMin = leftMin;
		} else if (leftMin.isNull) {
			rangeMin = NullableLong::of(-maxMagnitude.value);
		} else {
			rangeMin = NullableLong::of(std::max(leftMin.value, -maxMagnitude.value));
		}

		return fromInterval(rangeMin, rangeMax);
	}

	/* Mirrors getIntegerHull(). */
	static void getIntegerHull(const NumberSetData &set, NullableLong &outMin, NullableLong &outMax)
	{
		NullableLong min = NullableLong::null();
		NullableLong max = NullableLong::null();
		bool unboundedMin = set.integerIntervals.empty();
		bool unboundedMax = set.integerIntervals.empty();
		for (const NumberSetData::IntegerInterval &interval : set.integerIntervals) {
			if (interval.min.isNull) {
				unboundedMin = true;
			} else if (min.isNull || interval.min.value < min.value) {
				min = interval.min;
			}

			if (interval.max.isNull) {
				unboundedMax = true;
			} else if (max.isNull || interval.max.value > max.value) {
				max = interval.max;
			}
		}

		outMin = unboundedMin ? NullableLong::null() : min;
		outMax = unboundedMax ? NullableLong::null() : max;
	}

	/* Mirrors getMaxModuloMagnitude(). */
	static NullableLong getMaxModuloMagnitude(NullableLong divisorMin, NullableLong divisorMax)
	{
		if (divisorMin.isNull || divisorMax.isNull || divisorMin.value == ZEND_LONG_MIN) return NullableLong::null();
		zend_long absMin = divisorMin.value < 0 ? -divisorMin.value : divisorMin.value;
		zend_long absMax = divisorMax.value < 0 ? -divisorMax.value : divisorMax.value;
		zend_long magnitude = std::max(absMin, absMax);
		if (magnitude == 0) return NullableLong::null();
		return NullableLong::of(magnitude - 1);
	}

	/* Mirrors bitwiseAnd(), bitwiseOr() and bitwiseXor(). */
	static zv::Val bitwise(IetrBinaryKind kind, const NumberSetData &left, const NumberSetData &right)
	{
		zend_long leftMin = 0, rightMin = 0;
		NullableLong leftMax = NullableLong::null(), rightMax = NullableLong::null();
		bool leftBounds = getNonNegativeIntegerBounds(left, leftMin, leftMax);
		bool rightBounds = getNonNegativeIntegerBounds(right, rightMin, rightMax);
		if (kind == IETR_BITWISE_AND) {
			if (!leftBounds && !rightBounds) {
				return newIntegerType();
			}

			bool anyMax = false;
			zend_long minOfMax = 0;
			if (leftBounds && !leftMax.isNull) {
				minOfMax = leftMax.value;
				anyMax = true;
			}
			if (rightBounds && !rightMax.isNull) {
				minOfMax = anyMax && minOfMax <= rightMax.value ? minOfMax : rightMax.value;
				anyMax = true;
			}

			return fromInterval(NullableLong::of(0), anyMax ? NullableLong::of(minOfMax) : NullableLong::null());
		}

		/* Mirrors bitwiseOrXor(). */
		if (!leftBounds || !rightBounds) {
			return newIntegerType();
		}
		if (leftMax.isNull || rightMax.isNull) {
			return fromInterval(NullableLong::of(0), NullableLong::null());
		}

		return fromInterval(NullableLong::of(0), NullableLong::of(allBitsMask(leftMax.value >= rightMax.value ? leftMax.value : rightMax.value)));
	}

	/* Mirrors getNonNegativeIntegerBounds(): false for its null */
	static bool getNonNegativeIntegerBounds(const NumberSetData &set, zend_long &min, NullableLong &max)
	{
		if (!set.floatIntervals.empty() || set.nan || set.integerIntervals.size() != 1) return false;
		const NumberSetData::IntegerInterval &interval = set.integerIntervals[0];
		if (interval.min.isNull || interval.min.value < 0) return false;
		min = interval.min.value;
		max = interval.max;
		return true;
	}

	/* Mirrors allBitsMask(). */
	static zend_long allBitsMask(zend_long value)
	{
		value |= value >> 1;
		value |= value >> 2;
		value |= value >> 4;
		value |= value >> 8;
		value |= value >> 16;
		value |= value >> 32;

		return value;
	}

	/* Mirrors negate(). */
	static zv::Val negate(const NumberSetData &set, zval *phpVersion)
	{
		// -PHP_INT_MIN overflows into a float, as the product with -1 does
		zv::Arr types = zv::Arr::create((uint32_t) (set.floatIntervals.size() + 2));
		if (!set.integerIntervals.empty()) {
			NumberSetData integers;
			integers.integerIntervals = set.integerIntervals;
			NumberSetData minusOne;
			minusOne.integerIntervals.push_back({NullableLong::of(-1), NullableLong::of(-1)});
			IETR_VAL(product, calculate(IETR_MUL, integers, minusOne, phpVersion));
			types.push(std::move(product));
		}
		for (const NumberSetData::FloatInterval &interval : set.floatIntervals) {
			if (interval.min == -std::numeric_limits<double>::infinity() && interval.max == std::numeric_limits<double>::infinity() && set.nan) {
				// the twin's union(new FloatType(), ...$types): the FloatType absorbs the floats so far
				IETR_VAL(floatType, newFloatType());
				types.push(std::move(floatType));
				return unionOf(types);
			}

			// a constant keeps the sign of its zero
			IETR_VAL(negated, interval.min == interval.max ? newConstantFloatType(-interval.min) : pt_float_range_from_interval(-interval.max, -interval.min));
			types.push(std::move(negated));
		}
		if (set.nan) {
			IETR_VAL(nan, newConstantFloatType(std::numeric_limits<double>::quiet_NaN()));
			types.push(std::move(nan));
		}

		return unionOf(types);
	}
};

/* }}} */

} // namespace phpstanturbo

using phpstanturbo::NumberSet;
using phpstanturbo::NumberSetData;

zv::Val pt_number_set_new(const NumberSetData &data)
{
	return NumberSet::create(data);
}

zv::Val pt_number_set_int_divide(const NumberSetData &left, const NumberSetData &right)
{
	return phpstanturbo::NumberSetMath::intDivide(left, right);
}

zv::Val pt_number_set_modulo(const NumberSetData &left, const NumberSetData &right)
{
	return phpstanturbo::NumberSetMath::modulo(left, right);
}

zv::Val pt_number_set_bitwise(phpstanturbo::ietr::IetrBinaryKind kind, const NumberSetData &left, const NumberSetData &right)
{
	return phpstanturbo::NumberSetMath::bitwise(kind, left, right);
}

zv::Val pt_number_set_negate(const NumberSetData &set, zval *phpVersion)
{
	return phpstanturbo::NumberSetMath::negate(set, phpVersion);
}

zv::Val pt_number_set_calculate(phpstanturbo::ietr::IetrBinaryKind kind, const NumberSetData &left, const NumberSetData &right, zval *phpVersion)
{
	return phpstanturbo::NumberSetMath::calculate(kind, left, right, phpVersion);
}

bool pt_number_set_read(zend_object *numberSet, NumberSetData &out)
{
	return NumberSet(numberSet).read(out);
}

bool pt_type_to_number_set(zval *type, NumberSetData &out, bool &has)
{
	has = false;
	zv::Val numberSet = pt_type_call(Z_OBJ_P(type), PT_LC("tonumberset"), 0, NULL);
	if (UNEXPECTED(numberSet.isUndef())) return false;
	if (Z_TYPE_P(numberSet.raw()) != IS_OBJECT) return true;

	if (UNEXPECTED(!pt_number_set_read(Z_OBJ_P(numberSet.raw()), out))) return false;
	has = true;
	return true;
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS NumberSet(Z_OBJ_P(ZEND_THIS))

/* intDivide() & co.: (self $right, PhpVersion $phpVersion) */
template <int Op>
static void pt_ns_integer_operator(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *right, *phpVersion;
	if (!zp::parse<zp::Obj, zp::Obj>(execute_data, right, phpVersion)) RETURN_THROWS();
	if (UNEXPECTED(!instanceof_function(Z_OBJCE_P(right), pt_ce_number_set))) {
		zend_type_error("%s::%s(): Argument #1 ($right) must be of type %s, %s given", ZSTR_VAL(pt_ce_number_set->name), get_active_function_name(), ZSTR_VAL(pt_ce_number_set->name), zend_zval_value_name(right));
		RETURN_THROWS();
	}
	NumberSetData left, rightData;
	if (UNEXPECTED(!PT_THIS.read(left) || !NumberSet(Z_OBJ_P(right)).read(rightData))) RETURN_THROWS();
	using phpstanturbo::NumberSetMath;
	switch (Op) {
		case 0: PT_RETURN_VAL(NumberSetMath::intDivide(left, rightData));
		case 1: PT_RETURN_VAL(NumberSetMath::modulo(left, rightData));
		case 2: PT_RETURN_VAL(NumberSetMath::bitwise(phpstanturbo::ietr::IETR_BITWISE_AND, left, rightData));
		case 3: PT_RETURN_VAL(NumberSetMath::bitwise(phpstanturbo::ietr::IETR_BITWISE_OR, left, rightData));
		default: PT_RETURN_VAL(NumberSetMath::bitwise(phpstanturbo::ietr::IETR_BITWISE_XOR, left, rightData));
	}
}

/* union() / intersect(): (self $other) — PHP's max()/min() keep the first of
 * equal values, as `b > a ? b : a` does */
template <bool Intersect>
static void pt_ns_combine(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *other;
	if (!zp::parse<zp::Obj>(execute_data, other)) RETURN_THROWS();
	if (UNEXPECTED(!instanceof_function(Z_OBJCE_P(other), pt_ce_number_set))) {
		zend_type_error("%s::%s(): Argument #1 ($other) must be of type %s, %s given", ZSTR_VAL(pt_ce_number_set->name), get_active_function_name(), ZSTR_VAL(pt_ce_number_set->name), zend_zval_value_name(other));
		RETURN_THROWS();
	}
	NumberSetData left, right;
	if (UNEXPECTED(!PT_THIS.read(left) || !NumberSet(Z_OBJ_P(other)).read(right))) RETURN_THROWS();
	NumberSetData result;
	if (!Intersect) {
		result.integerIntervals = left.integerIntervals;
		result.integerIntervals.insert(result.integerIntervals.end(), right.integerIntervals.begin(), right.integerIntervals.end());
		result.floatIntervals = left.floatIntervals;
		result.floatIntervals.insert(result.floatIntervals.end(), right.floatIntervals.begin(), right.floatIntervals.end());
		result.nan = left.nan || right.nan;
		PT_RETURN_VAL(pt_number_set_new(result));
	}
	for (const auto &l : left.integerIntervals) {
		for (const auto &r : right.integerIntervals) {
			phpstanturbo::NullableLong min = l.min.isNull || r.min.isNull ? (l.min.isNull ? r.min : l.min) : phpstanturbo::NullableLong::of(r.min.value > l.min.value ? r.min.value : l.min.value);
			phpstanturbo::NullableLong max = l.max.isNull || r.max.isNull ? (l.max.isNull ? r.max : l.max) : phpstanturbo::NullableLong::of(r.max.value < l.max.value ? r.max.value : l.max.value);
			if (!min.isNull && !max.isNull && min.value > max.value) continue;
			result.integerIntervals.push_back({min, max});
		}
	}
	for (const auto &l : left.floatIntervals) {
		for (const auto &r : right.floatIntervals) {
			double min = r.min > l.min ? r.min : l.min;
			double max = r.max < l.max ? r.max : l.max;
			if (min > max) continue;
			result.floatIntervals.push_back({min, max});
		}
	}
	result.nan = left.nan && right.nan;
	PT_RETURN_VAL(pt_number_set_new(result));
}

/* add() & co.: (self $right, PhpVersion $phpVersion) */
template <phpstanturbo::ietr::IetrBinaryKind Kind>
static void pt_ns_operator(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *right, *phpVersion;
	if (!zp::parse<zp::Obj, zp::Obj>(execute_data, right, phpVersion)) RETURN_THROWS();
	if (UNEXPECTED(!instanceof_function(Z_OBJCE_P(right), pt_ce_number_set))) {
		zend_type_error("%s::%s(): Argument #1 ($right) must be of type %s, %s given", ZSTR_VAL(pt_ce_number_set->name), get_active_function_name(), ZSTR_VAL(pt_ce_number_set->name), zend_zval_value_name(right));
		RETURN_THROWS();
	}
	NumberSetData left, rightData;
	if (UNEXPECTED(!PT_THIS.read(left) || !NumberSet(Z_OBJ_P(right)).read(rightData))) RETURN_THROWS();
	PT_RETURN_VAL(phpstanturbo::NumberSetMath::calculate(Kind, left, rightData, phpVersion));
}

PT_MINIT_REGISTRATION(pt_register_number_set)
{
	reg::Class cls("PHPStan\\Type\\NumberSet");
	ptdecl::NumberSet::declareClass(cls);
	cls.privateClassConstantValue("SMALLEST_POSITIVE_FLOAT", [](zval *out) { ZVAL_DOUBLE(out, phpstanturbo::NumberSetMath::SMALLEST_POSITIVE_FLOAT); });
	/* "integerIntervals", "floatIntervals" and "nan" must stay the declared
	 * properties in this order (slots::*) */
	ptdecl::NumberSet::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *integerIntervals, *floatIntervals;
		bool nan;
		if (!zp::parse<zp::Arr, zp::Arr, zp::Bool>(execute_data, integerIntervals, floatIntervals, nan)) RETURN_THROWS();
		PT_THIS.construct(integerIntervals, floatIntervals, nan);
	});
	cls.method(sigs::getIntegerIntervals, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_THIS.get(slots::integerIntervals, "integerIntervals"));
	});
	cls.method(sigs::getFloatIntervals, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_THIS.get(slots::floatIntervals, "floatIntervals"));
	});
	cls.method(sigs::hasNan, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_THIS.get(slots::nan, "nan"));
	});
	cls.method(sigs::union_, pt_ns_combine<false>);
	cls.method(sigs::intersect, pt_ns_combine<true>);
	cls.method(sigs::add, pt_ns_operator<phpstanturbo::ietr::IETR_PLUS>);
	cls.method(sigs::subtract, pt_ns_operator<phpstanturbo::ietr::IETR_MINUS>);
	cls.method(sigs::multiply, pt_ns_operator<phpstanturbo::ietr::IETR_MUL>);
	cls.method(sigs::divide, pt_ns_operator<phpstanturbo::ietr::IETR_DIV>);
	cls.method(sigs::intDivide, pt_ns_integer_operator<0>);
	cls.method(sigs::modulo, pt_ns_integer_operator<1>);
	cls.method(sigs::bitwiseAnd, pt_ns_integer_operator<2>);
	cls.method(sigs::bitwiseOr, pt_ns_integer_operator<3>);
	cls.method(sigs::bitwiseXor, pt_ns_integer_operator<4>);
	cls.method(sigs::negate, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *phpVersion;
		if (!zp::parse<zp::Obj>(execute_data, phpVersion)) RETURN_THROWS();
		NumberSetData set;
		if (UNEXPECTED(!PT_THIS.read(set))) RETURN_THROWS();
		PT_RETURN_VAL(phpstanturbo::NumberSetMath::negate(set, phpVersion));
	});
	cls.method(sigs::isEmpty, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		NumberSetData set;
		if (UNEXPECTED(!PT_THIS.read(set))) RETURN_THROWS();
		RETURN_BOOL(set.integerIntervals.empty() && set.floatIntervals.empty() && !set.nan);
	});
	cls.method(sigs::power, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *exponent, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, exponent, phpVersion)) RETURN_THROWS();
		if (UNEXPECTED(!instanceof_function(Z_OBJCE_P(exponent), pt_ce_number_set))) {
			zend_type_error("%s::power(): Argument #1 ($exponent) must be of type %s, %s given", ZSTR_VAL(pt_ce_number_set->name), ZSTR_VAL(pt_ce_number_set->name), zend_zval_value_name(exponent));
			RETURN_THROWS();
		}
		NumberSetData base, exponentData;
		if (UNEXPECTED(!PT_THIS.read(base) || !NumberSet(Z_OBJ_P(exponent)).read(exponentData))) RETURN_THROWS();
		PT_RETURN_VAL(phpstanturbo::NumberSetMath::power(base, exponentData));
	});
	cls.method(sigs::shiftLeft, pt_ns_operator<phpstanturbo::ietr::IETR_SHIFT_LEFT>);
	cls.method(sigs::shiftRight, pt_ns_operator<phpstanturbo::ietr::IETR_SHIFT_RIGHT>);

	cls.shadow(&pt_ce_number_set);
}

/* }}} */
