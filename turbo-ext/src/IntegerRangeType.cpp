/*
 * PHPStanTurbo\IntegerRangeType — native implementation of
 * PHPStan\Type\IntegerRangeType.
 *
 * Declared as PHPStan\Type\IntegerRangeType itself at activation: not
 * final, extending the native IntegerType (declared first — Shadow.cpp
 * materialises a parent plan before its child) and implementing
 * PHPStan\Type\CompoundType. State is the twin's `private ?int $min` and
 * `private ?int $max`, declared typed property slots (IS_PROP_UNINIT until
 * the constructor writes them), so the std object handlers do GC/clone.
 * The class uses no traits of its own; everything it does not declare is
 * inherited from IntegerType.
 *
 * Integer arithmetic that the twin lets overflow into a float
 * (`$maxB + $offset`, `$this->max - $this->min + 1`, `**`) goes through the
 * engine's own operators (add_function, sub_function, pow_function) so the
 * int/float outcome and the mixed comparisons that follow are the twin's.
 */

#include "TypeTraits.h"
#include "generated/IntegerRangeType.h"

namespace slots = ptdecl::IntegerRangeType::slot;
namespace sigs = ptdecl::IntegerRangeType::sig;

#include <cmath>

zend_class_entry *pt_ce_integer_range_type = nullptr;

/* nothing is memoized per request since InitializerExprTypeResolver's
 * CALCULATE_SCALARS_LIMIT became a native constant */
void pt_integer_range_type_rinit()
{
}

namespace phpstanturbo {

/* {{{ the engine's integer arithmetic with its overflow-to-float outcome */

/* $a + $b */
static void phpAdd(zend_long a, zend_long b, zval *out)
{
	zval x, y;
	ZVAL_LONG(&x, a);
	ZVAL_LONG(&y, b);
	add_function(out, &x, &y);
}

/* $a - $b */
static void phpSub(zend_long a, zend_long b, zval *out)
{
	zval x, y;
	ZVAL_LONG(&x, a);
	ZVAL_LONG(&y, b);
	sub_function(out, &x, &y);
}

/* `$a < $b` on int|float operands — the VM's own fast path: two ints
 * compare as ints, otherwise both as doubles */
static bool phpLess(const zval *a, const zval *b)
{
	if (Z_TYPE_P(a) == IS_LONG && Z_TYPE_P(b) == IS_LONG) return Z_LVAL_P(a) < Z_LVAL_P(b);
	double da = Z_TYPE_P(a) == IS_LONG ? (double) Z_LVAL_P(a) : Z_DVAL_P(a);
	double db = Z_TYPE_P(b) == IS_LONG ? (double) Z_LVAL_P(b) : Z_DVAL_P(b);
	return da < db;
}

/* $a > $b + $c */
static bool longGreaterThanSum(zend_long a, zend_long b, zend_long c)
{
	zval sum, left;
	phpAdd(b, c, &sum);
	ZVAL_LONG(&left, a);
	return phpLess(&sum, &left);
}

/* $a + $c < $b */
static bool sumLessThanLong(zend_long a, zend_long c, zend_long b)
{
	zval sum, right;
	phpAdd(a, c, &sum);
	ZVAL_LONG(&right, b);
	return phpLess(&sum, &right);
}

/* }}} */

/* Mirrors PHPStan\Type\IntegerRangeType. State lives in the PHP object's
 * $min and $max. */
class IntegerRangeType
{
public:
	explicit IntegerRangeType(zend_object *self) : self(self) {}

	/* private __construct(private ?int $min, private ?int $max): initializes
	 * the typed slots; parent::__construct() is IntegerType's empty
	 * constructor. The twin's two assert()s (min <= max, not both null)
	 * hold for every caller — fromInterval() is the only one and validates
	 * first — so nothing is asserted here. */
	void construct(NullableLong min, NullableLong max)
	{
		zval *minSlot = OBJ_PROP_NUM(self, slots::min);
		zval *maxSlot = OBJ_PROP_NUM(self, slots::max);
		if (min.isNull) {
			ZVAL_NULL(minSlot);
		} else {
			ZVAL_LONG(minSlot, min.value);
		}
		if (max.isNull) {
			ZVAL_NULL(maxSlot);
		} else {
			ZVAL_LONG(maxSlot, max.value);
		}
		Z_PROP_FLAG_P(minSlot) = 0; /* no longer IS_PROP_UNINIT */
		Z_PROP_FLAG_P(maxSlot) = 0;
	}

	/* new self($min, $max); UNDEF = pending exception */
	static zv::Val create(NullableLong min, NullableLong max)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_integer_range_type) != SUCCESS)) return zv::Val();
		IntegerRangeType(Z_OBJ(object)).construct(min, max);
		return zv::Val::adopt(object);
	}

	static zv::Val fromInterval(NullableLong min, NullableLong max, zend_long shift)
	{
		if (!min.isNull && !max.isNull) {
			if (min.value > max.value) return never();
			if (min.value == max.value) {
				/* new ConstantIntegerType($min + $shift): an overflowing sum is
				 * a float, which the int parameter rejects under strict_types */
				zval sum;
				phpAdd(min.value, shift, &sum);
				if (UNEXPECTED(Z_TYPE(sum) != IS_LONG)) {
					zend_type_error("%s::__construct(): Argument #1 ($value) must be of type int, float given", ZSTR_VAL(pt_ce_constant_integer_type->name));
					return zv::Val();
				}
				return pt_type_new_constant_integer(Z_LVAL(sum));
			}
		}

		if (min.isNull && max.isNull) return integer();

		/* (new self($min, $max))->shift($shift) — exactly an IntegerRangeType,
		 * so shift() is this class's */
		zv::Val created = create(min, max);
		if (UNEXPECTED(created.isUndef())) return zv::Val();
		zv::Val range = IntegerRangeType(Z_OBJ_P(created.raw())).shift(shift);
		if (UNEXPECTED(range.isUndef())) return zv::Val();
		if (!zv::Ref(range.raw()).instanceOf(pt_ce_integer_range_type)) return range;

		/* Nothing is smaller than the smallest integer, and nothing is
		 * bigger than the biggest one, so an unbounded side that reaches
		 * either one holds a single value. */
		NullableLong rangeMin = NullableLong::null(), rangeMax = NullableLong::null();
		if (UNEXPECTED(!IntegerRangeType(Z_OBJ_P(range.raw())).bounds(rangeMin, rangeMax))) return zv::Val();
		if (rangeMin.isNull && !rangeMax.isNull && rangeMax.value == ZEND_LONG_MIN) return pt_type_new_constant_integer(ZEND_LONG_MIN);
		if (!rangeMin.isNull && rangeMin.value == ZEND_LONG_MAX && rangeMax.isNull) return pt_type_new_constant_integer(ZEND_LONG_MAX);

		return range;
	}

	static bool isDisjoint(NullableLong minA, NullableLong maxA, NullableLong minB, NullableLong maxB, bool touchingIsDisjoint)
	{
		zend_long offset = touchingIsDisjoint ? 0 : 1;
		return (!minA.isNull && !maxB.isNull && longGreaterThanSum(minA.value, maxB.value, offset))
			|| (!maxA.isNull && !minB.isNull && sumLessThanLong(maxA.value, offset, minB.value));
	}

	/* the createAll*() family: the range of integers beyond an int|float
	 * $value. An int goes straight to fromInterval(); anything else is
	 * first held against the int limits — a float the way the VM compares
	 * int with float (both as doubles), any other value loosely — and what
	 * passes is then rounded by ceil()/floor() and (int)-cast, which is
	 * where a non-number fails under strict_types. */

	/* fromInterval(null, $value, -1) */
	static zv::Val createAllSmallerThan(zval *value)
	{
		if (Z_TYPE_P(value) == IS_LONG) return fromInterval(NullableLong::null(), NullableLong::of(Z_LVAL_P(value)), -1);
		int againstMax, againstMin;
		if (UNEXPECTED(!compareToLimits(value, againstMax, againstMin))) return zv::Val();
		if (againstMax >= 0) { /* $value >= PHP_INT_MAX */
			return integer();
		}
		if (againstMin <= 0) { /* $value <= PHP_INT_MIN */
			return never();
		}
		zend_long rounded;
		if (UNEXPECTED(!roundToLong(value, true, rounded))) return zv::Val();
		return fromInterval(NullableLong::null(), NullableLong::of(rounded), -1);
	}

	/* fromInterval(null, $value) */
	static zv::Val createAllSmallerThanOrEqualTo(zval *value)
	{
		if (Z_TYPE_P(value) == IS_LONG) return fromInterval(NullableLong::null(), NullableLong::of(Z_LVAL_P(value)), 0);
		int againstMax, againstMin;
		if (UNEXPECTED(!compareToLimits(value, againstMax, againstMin))) return zv::Val();
		if (againstMax >= 0) { /* $value >= PHP_INT_MAX */
			return integer();
		}
		if (againstMin < 0) { /* $value < PHP_INT_MIN */
			return never();
		}
		zend_long rounded;
		if (UNEXPECTED(!roundToLong(value, false, rounded))) return zv::Val();
		return fromInterval(NullableLong::null(), NullableLong::of(rounded), 0);
	}

	/* fromInterval($value, null, 1) */
	static zv::Val createAllGreaterThan(zval *value)
	{
		if (Z_TYPE_P(value) == IS_LONG) return fromInterval(NullableLong::of(Z_LVAL_P(value)), NullableLong::null(), 1);
		int againstMax, againstMin;
		if (UNEXPECTED(!compareToLimits(value, againstMax, againstMin))) return zv::Val();
		if (againstMin < 0) { /* $value < PHP_INT_MIN */
			return integer();
		}
		if (againstMax >= 0) { /* $value >= PHP_INT_MAX */
			return never();
		}
		zend_long rounded;
		if (UNEXPECTED(!roundToLong(value, false, rounded))) return zv::Val();
		return fromInterval(NullableLong::of(rounded), NullableLong::null(), 1);
	}

	/* fromInterval($value, null) */
	static zv::Val createAllGreaterThanOrEqualTo(zval *value)
	{
		if (Z_TYPE_P(value) == IS_LONG) return fromInterval(NullableLong::of(Z_LVAL_P(value)), NullableLong::null(), 0);
		int againstMax, againstMin;
		if (UNEXPECTED(!compareToLimits(value, againstMax, againstMin))) return zv::Val();
		if (againstMin <= 0) { /* $value <= PHP_INT_MIN */
			return integer();
		}
		if (againstMax >= 0) { /* $value >= PHP_INT_MAX */
			return never();
		}
		zend_long rounded;
		if (UNEXPECTED(!roundToLong(value, true, rounded))) return zv::Val();
		return fromInterval(NullableLong::of(rounded), NullableLong::null(), 0);
	}

	/* $this->min / $this->max; false with an Error pending when the
	 * constructor never ran (ReflectionClass::newInstanceWithoutConstructor())
	 * — the twin's typed-property read raises the same */
	[[nodiscard]] bool min(NullableLong &out) const { return slot(slots::min, "min", out); }
	bool max(NullableLong &out) const { return slot(slots::max, "max", out); }

	bool bounds(NullableLong &min, NullableLong &max) const
	{
		return this->min(min) && this->max(max);
	}

	bool getMin(NullableLong &out) const { return min(out); }
	bool getMax(NullableLong &out) const { return max(out); }

	/* sprintf('int<%s, %s>', $this->min ?? 'min', $this->max ?? 'max') —
	 * through `??`, so an uninitialized slot reads as null instead of
	 * raising like every other access */
	zv::Val describe() const
	{
		NullableLong min = NullableLong::from(OBJ_PROP_NUM(self, slots::min));
		NullableLong max = NullableLong::from(OBJ_PROP_NUM(self, slots::max));
		smart_str str = {NULL, 0};
		smart_str_appendl(&str, "int<", 4);
		if (min.isNull) {
			smart_str_appendl(&str, "min", 3);
		} else {
			smart_str_append_long(&str, min.value);
		}
		smart_str_appendl(&str, ", ", 2);
		if (max.isNull) {
			smart_str_appendl(&str, "max", 3);
		} else {
			smart_str_append_long(&str, max.value);
		}
		smart_str_appendc(&str, '>');
		smart_str_0(&str);
		return zv::Val::adoptString(str.s);
	}

	/* the range moved by $amount: $this for 0, never when the bounded side
	 * would leave the int range, an unbounded side where the other one
	 * would; UNDEF = pending exception */
	zv::Val shift(zend_long amount) const
	{
		if (amount == 0) return thisValue();

		NullableLong min = NullableLong::null(), max = NullableLong::null();
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();

		if (amount < 0) {
			if (!max.isNull) {
				if (max.value < ZEND_LONG_MIN - amount) return never();
				max.value += amount;
			}
			if (!min.isNull) {
				min = min.value < ZEND_LONG_MIN - amount ? NullableLong::null() : NullableLong::of(min.value + amount);
			}
		} else {
			if (!min.isNull) {
				if (min.value > ZEND_LONG_MAX - amount) return never();
				min.value += amount;
			}
			if (!max.isNull) {
				max = max.value > ZEND_LONG_MAX - amount ? NullableLong::null() : NullableLong::of(max.value + amount);
			}
		}

		return fromInterval(min, max, 0);
	}

	/* $this->isSuperTypeOf($type)->toAcceptsResult() for an IntegerType,
	 * the CompoundType callback, no otherwise; UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		/* $type instanceof parent */
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_integer_type)) {
			zv::Val result = isExact() ? isSuperTypeOf(type) : pt_type_op(self, PT_OP_IS_SUPER_TYPE_OF, 1, type);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			return toAcceptsResult(std::move(result));
		}

		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}

		return pt_type_accepts_result(PT_TRI_NO);
	}

	/* no/yes/maybe by the bounds of an IntegerRangeType or the value of a
	 * ConstantIntegerType, maybe for any other IntegerType, the
	 * CompoundType callback, no otherwise; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		NullableLong typeMin = NullableLong::null(), typeMax = NullableLong::null();
		bool integerLike;
		if (UNEXPECTED(!boundsOf(type, integerLike, typeMin, typeMax))) return zv::Val();
		if (integerLike) {
			NullableLong min = NullableLong::null(), max = NullableLong::null();
			if (UNEXPECTED(!bounds(min, max))) return zv::Val();

			if (isDisjoint(min, max, typeMin, typeMax, true)) return pt_type_is_super_type_of_result(PT_TRI_NO);

			if (
				(min.isNull || (!typeMin.isNull && min.value <= typeMin.value))
				&& (max.isNull || (!typeMax.isNull && max.value >= typeMax.value))
			) {
				return pt_type_is_super_type_of_result(PT_TRI_YES);
			}

			return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		}

		/* $type instanceof parent */
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_integer_type)) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);

		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &selfZv);
		}

		return pt_type_is_super_type_of_result(PT_TRI_NO);
	}

	/* $otherType->isSuperTypeOf($this) for an IntegerType or an
	 * IntersectionType, the union walk for a UnionType, no otherwise;
	 * UNDEF = pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);

		/* $otherType instanceof parent */
		if (instanceof_function(Z_OBJCE_P(otherType), pt_ce_integer_type)) return pt_type_op(Z_OBJ_P(otherType), PT_OP_IS_SUPER_TYPE_OF, 1, &selfZv);

		bool isUnion;
		if (UNEXPECTED(!pt_union_type_instanceof(otherType, isUnion))) return zv::Val();
		if (isUnion) return isSubTypeOfUnionWithReason(otherType);

		bool isIntersection;
		if (UNEXPECTED(!pt_intersection_type_instanceof(otherType, isIntersection))) return zv::Val();
		if (isIntersection) return pt_type_op(Z_OBJ_P(otherType), PT_OP_IS_SUPER_TYPE_OF, 1, &selfZv);

		return pt_type_is_super_type_of_result(PT_TRI_NO);
	}

	/* yes when a bounded range's every integer is among the union's
	 * ConstantIntegerTypes, else no or'ed with $this->isSubTypeOf() of each
	 * member; UNDEF = pending exception */
	zv::Val isSubTypeOfUnionWithReason(zval *otherType) const
	{
		zv::Val types = pt_type_call(Z_OBJ_P(otherType), PT_LC("gettypes"), 0, NULL);
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(types.raw()).isArray())) {
			zend_type_error("phpstan_turbo: UnionType::getTypes() must return array");
			return zv::Val();
		}
		NullableLong min = NullableLong::null(), max = NullableLong::null();
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		if (!min.isNull && !max.isNull) {
			/* count(array_filter($otherType->getTypes(), fn (Type $type): bool =>
			 *   $type instanceof ConstantIntegerType && $type->getValue() >= $this->min && $type->getValue() <= $this->max)) */
			zend_long matchingConstantIntegers = 0;
			for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
				zval *type = entry.value().deref().raw();
				if (Z_TYPE_P(type) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(type), pt_ce_constant_integer_type)) continue;
				zend_long value;
				if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(type), value))) return zv::Val();
				if (value >= min.value && value <= max.value) {
					matchingConstantIntegers++;
				}
			}

			/* === ($this->max - $this->min + 1): a float past the int range
			 * is never identical to the int count */
			zval size, one, total;
			phpSub(max.value, min.value, &size);
			ZVAL_LONG(&one, 1);
			add_function(&total, &size, &one);
			if (Z_TYPE(total) == IS_LONG && Z_LVAL(total) == matchingConstantIntegers) return pt_type_is_super_type_of_result(PT_TRI_YES);
		}

		/* IsSuperTypeOfResult::createNo()->or(...array_map(fn (Type $innerType) => $this->isSubTypeOf($innerType), $otherType->getTypes())) */
		zv::Val no = pt_type_is_super_type_of_result(PT_TRI_NO);
		if (UNEXPECTED(no.isUndef())) return zv::Val();
		zv::Arr results = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(types.raw())));
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zval *innerType = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(innerType) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: UnionType::getTypes() must return %s instances", ZSTR_VAL(pt_ce_integer_type->name));
				return zv::Val();
			}
			zv::Val result = isExact() ? isSubTypeOf(innerType) : pt_type_op(self, PT_OP_IS_SUB_TYPE_OF, 1, innerType);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			results.push(std::move(result));
		}
		return pt_is_super_type_of_result_spread(Z_OBJ_P(no.raw()), false, results.table());
	}

	/* $this->isSubTypeOf($acceptingType)->toAcceptsResult(); UNDEF =
	 * pending exception */
	zv::Val isAcceptedBy(zval *acceptingType) const
	{
		zv::Val result = isExact() ? isSubTypeOf(acceptingType) : pt_type_op(self, PT_OP_IS_SUB_TYPE_OF, 1, acceptingType);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		return toAcceptsResult(std::move(result));
	}

	/* $type instanceof self && $this->min === $type->min && $this->max === $type->max;
	 * false with an exception pending on an uninitialized slot */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_integer_range_type)) {
			out = false;
			return true;
		}
		NullableLong min = NullableLong::null(), max = NullableLong::null(), typeMin = NullableLong::null(), typeMax = NullableLong::null();
		if (UNEXPECTED(!bounds(min, max) || !IntegerRangeType(Z_OBJ_P(type)).bounds(typeMin, typeMax))) return false;
		out = same(min, typeMin) && same(max, typeMax);
		return true;
	}

	/* new IntegerType() */
	static zv::Val generalize() { return integer(); }

	/* isSmallerThan() / isSmallerThanOrEqual(): the bounds as
	 * ConstantIntegerTypes compared against $otherType (an unbounded min
	 * is smaller, an unbounded max is not), and 0's own comparison when it
	 * lies in the range — extremeIdentity of them all. -1 = pending
	 * exception */
	[[nodiscard]] zend_long isSmallerThan(zval *otherType, zval *phpVersion, bool orEqual) const
	{
		const char *method = orEqual ? "issmallerthanorequal" : "issmallerthan";
		size_t methodLen = orEqual ? sizeof("issmallerthanorequal") - 1 : sizeof("issmallerthan") - 1;
		NullableLong min = NullableLong::null(), max = NullableLong::null();
		if (UNEXPECTED(!bounds(min, max))) return -1;
		zv::Args args{otherType, phpVersion};

		zend_long minIsSmaller;
		if (min.isNull) {
			minIsSmaller = PT_TRI_YES;
		} else {
			minIsSmaller = constantIntegerCall(min.value, method, methodLen, 2, args);
			if (UNEXPECTED(minIsSmaller < 0)) return -1;
		}

		zend_long maxIsSmaller;
		if (max.isNull) {
			maxIsSmaller = PT_TRI_NO;
		} else {
			maxIsSmaller = constantIntegerCall(max.value, method, methodLen, 2, args);
			if (UNEXPECTED(maxIsSmaller < 0)) return -1;
		}

		/* 0 can have different results in contrast to the interval edges,
		 * see https://3v4l.org/iGoti */
		bool zeroInRange;
		if (UNEXPECTED(!zeroIsSuperTypeOfThis(zeroInRange))) return -1;
		if (zeroInRange) {
			zend_long zeroIsSmaller = constantIntegerCall(0, method, methodLen, 2, args);
			if (UNEXPECTED(zeroIsSmaller < 0)) return -1;
			return extremeIdentity(zeroIsSmaller, minIsSmaller, maxIsSmaller);
		}

		return extremeIdentity(minIsSmaller, maxIsSmaller);
	}

	/* isGreaterThan() / isGreaterThanOrEqual(): $otherType compared against
	 * the bounds as ConstantIntegerTypes (an unbounded min is not smaller,
	 * an unbounded max is), and against 0 when it lies in the range —
	 * extremeIdentity of them all. -1 = pending exception */
	[[nodiscard]] zend_long isGreaterThan(zval *otherType, zval *phpVersion, bool orEqual) const
	{
		const char *method = orEqual ? "issmallerthanorequal" : "issmallerthan";
		size_t methodLen = orEqual ? sizeof("issmallerthanorequal") - 1 : sizeof("issmallerthan") - 1;
		NullableLong min = NullableLong::null(), max = NullableLong::null();
		if (UNEXPECTED(!bounds(min, max))) return -1;

		zend_long minIsSmaller;
		if (min.isNull) {
			minIsSmaller = PT_TRI_NO;
		} else {
			minIsSmaller = otherIsSmallerThanConstant(otherType, min.value, phpVersion, method, methodLen);
			if (UNEXPECTED(minIsSmaller < 0)) return -1;
		}

		zend_long maxIsSmaller;
		if (max.isNull) {
			maxIsSmaller = PT_TRI_YES;
		} else {
			maxIsSmaller = otherIsSmallerThanConstant(otherType, max.value, phpVersion, method, methodLen);
			if (UNEXPECTED(maxIsSmaller < 0)) return -1;
		}

		/* 0 can have different results in contrast to the interval edges,
		 * see https://3v4l.org/iGoti */
		bool zeroInRange;
		if (UNEXPECTED(!zeroIsSuperTypeOfThis(zeroInRange))) return -1;
		if (zeroInRange) {
			zend_long zeroIsSmaller = otherIsSmallerThanConstant(otherType, 0, phpVersion, method, methodLen);
			if (UNEXPECTED(zeroIsSmaller < 0)) return -1;
			return extremeIdentity(zeroIsSmaller, minIsSmaller, maxIsSmaller);
		}

		return extremeIdentity(minIsSmaller, maxIsSmaller);
	}

	/* mixed without true and without the integers from $max up; UNDEF =
	 * pending exception */
	zv::Val getSmallerType() const
	{
		NullableLong min = NullableLong::null(), max = NullableLong::null();
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		zv::Arr subtractedTypes = zv::Arr::create(2);
		if (UNEXPECTED(!pushConstantBoolean(subtractedTypes, true))) return zv::Val();
		if (!max.isNull) {
			zval value;
			ZVAL_LONG(&value, max.value);
			if (UNEXPECTED(!pushRange(subtractedTypes, createAllGreaterThanOrEqualTo(&value)))) return zv::Val();
		}
		return pt_type_mixed_minus(subtractedTypes.table());
	}

	/* mixed without the integers above $max */
	zv::Val getSmallerOrEqualType() const
	{
		NullableLong min = NullableLong::null(), max = NullableLong::null();
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		zv::Arr subtractedTypes = zv::Arr::create(1);
		if (!max.isNull) {
			zval value;
			ZVAL_LONG(&value, max.value);
			if (UNEXPECTED(!pushRange(subtractedTypes, createAllGreaterThan(&value)))) return zv::Val();
		}
		return pt_type_mixed_minus(subtractedTypes.table());
	}

	/* mixed without null, false, the integers up to $min, and without true
	 * when the range excludes 0 */
	zv::Val getGreaterType() const
	{
		NullableLong min = NullableLong::null(), max = NullableLong::null();
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		zv::Arr subtractedTypes = zv::Arr::create(4);
		if (UNEXPECTED(!pushNull(subtractedTypes) || !pushConstantBoolean(subtractedTypes, false))) return zv::Val();
		if (!min.isNull) {
			zval value;
			ZVAL_LONG(&value, min.value);
			if (UNEXPECTED(!pushRange(subtractedTypes, createAllSmallerThanOrEqualTo(&value)))) return zv::Val();
		}
		if (excludesZero(min, max)) {
			if (UNEXPECTED(!pushConstantBoolean(subtractedTypes, true))) return zv::Val();
		}
		return pt_type_mixed_minus(subtractedTypes.table());
	}

	/* mixed without the integers below $min, and without null and false
	 * when the range excludes 0 */
	zv::Val getGreaterOrEqualType() const
	{
		NullableLong min = NullableLong::null(), max = NullableLong::null();
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		zv::Arr subtractedTypes = zv::Arr::create(3);
		if (!min.isNull) {
			zval value;
			ZVAL_LONG(&value, min.value);
			if (UNEXPECTED(!pushRange(subtractedTypes, createAllSmallerThan(&value)))) return zv::Val();
		}
		if (excludesZero(min, max)) {
			if (UNEXPECTED(!pushNull(subtractedTypes) || !pushConstantBoolean(subtractedTypes, false))) return zv::Val();
		}
		return pt_type_mixed_minus(subtractedTypes.table());
	}

	/* true when 0 is out of the range, bool when it may be in, false when
	 * the range is 0 alone — which fromInterval() never builds, so the
	 * maybe branch is the one a range takes; UNDEF = pending exception */
	zv::Val toBoolean() const
	{
		zend_long isZero;
		if (UNEXPECTED(!zeroIsSuperTypeOfThisValue(isZero))) return zv::Val();
		if (isZero == PT_TRI_NO) return constantBoolean(true);
		if (isZero == PT_TRI_MAYBE) {
			return pt_val_of<pt_boolean_type_new>();
		}
		return constantBoolean(false);
	}

	/* $this for a non-negative range, int<0, max(-min, max)> (unbounded
	 * where either side is) for a range crossing 0, int<-max, -min> for a
	 * negative one; UNDEF = pending exception */
	zv::Val toAbsoluteNumber() const
	{
		NullableLong min = NullableLong::null(), max = NullableLong::null();
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		if (!min.isNull && min.value >= 0) return thisValue();

		/* Negating the smallest integer overflows, so its absolute value is
		 * treated as unbounded, the same way an unbounded lower bound is.
		 * This keeps abs(int<min, 0>) and abs(int<-9223372036854775808, 0>)
		 * in agreement. */
		NullableLong inversedMin = !min.isNull && min.value != ZEND_LONG_MIN ? NullableLong::of(-min.value) : NullableLong::null();

		if (max.isNull || max.value >= 0) {
			NullableLong upper = !inversedMin.isNull && !max.isNull ? NullableLong::of(inversedMin.value > max.value ? inversedMin.value : max.value) : NullableLong::null();
			return fromInterval(NullableLong::of(0), upper, 0);
		}

		/* -$this->max: max < 0 here, and a max of PHP_INT_MIN cannot occur
		 * (fromInterval() collapses int<min, PHP_INT_MIN> into the
		 * constant); the twin would negate it into a float and fail the
		 * ?int parameter of fromInterval() */
		if (UNEXPECTED(max.value == ZEND_LONG_MIN)) {
			zend_type_error("%s::fromInterval(): Argument #1 ($min) must be of type ?int, float given", ZSTR_VAL(pt_ce_integer_range_type->name));
			return zv::Val();
		}
		return fromInterval(NullableLong::of(-max.value), inversedMin, 0);
	}

	/* ~int<a, b> = int<~b, ~a> (bitwise NOT reverses the order) */
	zv::Val toBitwiseNotType() const
	{
		NullableLong min = NullableLong::null(), max = NullableLong::null();
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		return fromInterval(
			max.isNull ? NullableLong::null() : NullableLong::of(~max.value),
			min.isNull ? NullableLong::null() : NullableLong::of(~min.value),
			0
		);
	}

	/* the union of the finite types' strings when there are any, else a
	 * decimal-integer string, non-falsy when 0 is out of the range; UNDEF =
	 * pending exception */
	zv::Val toString() const
	{
		zv::Val finiteTypes = isExact() ? getFiniteTypes() : pt_type_call(self, PT_LC("getfinitetypes"), 0, NULL);
		if (UNEXPECTED(finiteTypes.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(finiteTypes.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getFiniteTypes() must return array");
			return zv::Val();
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(finiteTypes.raw())) > 0) {
			/* TypeCombinator::union(...$finiteTypes)->toString() */
			zv::Val unionType = pt_type_combinator_call_spread(PT_LC("union"), Z_ARRVAL_P(finiteTypes.raw()));
			if (UNEXPECTED(unionType.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(unionType.raw()).isObject())) {
				zend_type_error("phpstan_turbo: TypeCombinator::union() must return %s", ZSTR_VAL(pt_ce_integer_type->name));
				return zv::Val();
			}
			return pt_type_call(Z_OBJ_P(unionType.raw()), PT_LC("tostring"), 0, NULL);
		}

		zend_long isZero;
		if (UNEXPECTED(!zeroIsSuperTypeOfThisValue(isZero))) return zv::Val();
		zval stringZv;
		if (UNEXPECTED(!pt_string_type_new(&stringZv))) return zv::Val();
		zv::Val string = zv::Val::adopt(stringZv);
		zval decimalRaw;
		if (UNEXPECTED(!pt_accessory_decimal_integer_string_type_new(&decimalRaw))) return zv::Val();
		zv::Val decimal = zv::Val::adopt(decimalRaw);
		if (UNEXPECTED(decimal.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(3);
		types.push(std::move(string));
		types.push(std::move(decimal));
		if (isZero == PT_TRI_NO) {
			zv::Val nonFalsy = pt_type_new_shadowed(pt_accessory_non_falsy_string_type_new);
			if (UNEXPECTED(nonFalsy.isUndef())) return zv::Val();
			types.push(std::move(nonFalsy));
		}
		return pt_intersection_of(std::move(types));
	}

	/* the union with an overlapping or touching IntegerRangeType /
	 * ConstantIntegerType as one range, a plain IntegerType itself, null
	 * when the union needs a UnionType; UNDEF = pending exception */
	zv::Val tryUnion(zval *otherType) const
	{
		NullableLong otherMin = NullableLong::null(), otherMax = NullableLong::null();
		bool integerLike;
		if (UNEXPECTED(!boundsOf(otherType, integerLike, otherMin, otherMax))) return zv::Val();
		if (integerLike) {
			NullableLong min = NullableLong::null(), max = NullableLong::null();
			if (UNEXPECTED(!bounds(min, max))) return zv::Val();

			if (isDisjoint(min, max, otherMin, otherMax, false)) return zv::Val::null();

			return fromInterval(
				!min.isNull && !otherMin.isNull ? NullableLong::of(min.value < otherMin.value ? min.value : otherMin.value) : NullableLong::null(),
				!max.isNull && !otherMax.isNull ? NullableLong::of(max.value > otherMax.value ? max.value : otherMax.value) : NullableLong::null(),
				0
			);
		}

		/* get_class($otherType) === parent::class */
		if (Z_OBJCE_P(otherType) == pt_ce_integer_type) return zv::Val::copyOf(zv::Ref(otherType));

		return zv::Val::null();
	}

	/* the intersection with an IntegerRangeType / ConstantIntegerType as
	 * one range (never when disjoint), $this for a plain IntegerType, null
	 * when the intersection needs an IntersectionType; UNDEF = pending
	 * exception */
	zv::Val tryIntersect(zval *otherType) const
	{
		NullableLong otherMin = NullableLong::null(), otherMax = NullableLong::null();
		bool integerLike;
		if (UNEXPECTED(!boundsOf(otherType, integerLike, otherMin, otherMax))) return zv::Val();
		if (integerLike) {
			NullableLong min = NullableLong::null(), max = NullableLong::null();
			if (UNEXPECTED(!bounds(min, max))) return zv::Val();

			if (isDisjoint(min, max, otherMin, otherMax, false)) return never();

			NullableLong newMin;
			if (min.isNull) {
				newMin = otherMin;
			} else if (otherMin.isNull) {
				newMin = min;
			} else {
				newMin = NullableLong::of(min.value > otherMin.value ? min.value : otherMin.value);
			}

			NullableLong newMax;
			if (max.isNull) {
				newMax = otherMax;
			} else if (otherMax.isNull) {
				newMax = max;
			} else {
				newMax = NullableLong::of(max.value < otherMax.value ? max.value : otherMax.value);
			}

			return fromInterval(newMin, newMax, 0);
		}

		/* get_class($otherType) === parent::class */
		if (Z_OBJCE_P(otherType) == pt_ce_integer_type) return thisValue();

		return zv::Val::null();
	}

	/* never without a plain IntegerType; the parts of the range below and
	 * above an IntegerRangeType / ConstantIntegerType ($this when they do
	 * not meet); null when the difference cannot be represented; UNDEF =
	 * pending exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		/* get_class($typeToRemove) === parent::class */
		if (Z_OBJCE_P(typeToRemove) == pt_ce_integer_type) return never();

		NullableLong removeMin = NullableLong::null(), removeMax = NullableLong::null();
		bool integerLike;
		if (UNEXPECTED(!boundsOf(typeToRemove, integerLike, removeMin, removeMax))) return zv::Val();
		if (integerLike) {
			NullableLong min = NullableLong::null(), max = NullableLong::null();
			if (UNEXPECTED(!bounds(min, max))) return zv::Val();

			if (
				(!min.isNull && !removeMax.isNull && removeMax.value < min.value)
				|| (!max.isNull && !removeMin.isNull && max.value < removeMin.value)
			) {
				return thisValue();
			}

			zv::Val lowerPart;
			if (!removeMin.isNull && removeMin.value != ZEND_LONG_MIN) {
				lowerPart = fromInterval(min, NullableLong::of(removeMin.value - 1), 0);
				if (UNEXPECTED(lowerPart.isUndef())) return zv::Val();
			}
			zv::Val upperPart;
			if (!removeMax.isNull && removeMax.value != ZEND_LONG_MAX) {
				upperPart = fromInterval(NullableLong::of(removeMax.value + 1), max, 0);
				if (UNEXPECTED(upperPart.isUndef())) return zv::Val();
			}

			if (!lowerPart.isUndef() && !upperPart.isUndef()) {
				zv::Args args{lowerPart.raw(), upperPart.raw()};
				return pt_type_combinator_call(PT_LC("union"), 2, args);
			}

			if (!lowerPart.isUndef()) return lowerPart;
			if (!upperPart.isUndef()) return upperPart;
			return zv::Val::null();
		}

		return zv::Val::null();
	}

	/* the union over a UnionType exponent's members; the bounds raised to
	 * an IntegerRangeType exponent's bounds or a constant int exponent
	 * when every power stays an int; ExponentiateHelper otherwise; UNDEF =
	 * pending exception */
	zv::Val exponentiate(zval *exponent) const
	{
		bool isUnion;
		if (UNEXPECTED(!pt_union_type_instanceof(exponent, isUnion))) return zv::Val();
		if (isUnion) {
			zv::Val types = pt_type_call(Z_OBJ_P(exponent), PT_LC("gettypes"), 0, NULL);
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(types.raw()).isArray())) {
				zend_type_error("phpstan_turbo: UnionType::getTypes() must return array");
				return zv::Val();
			}
			zv::Arr results = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(types.raw())));
			for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
				zval *unionType = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(unionType) != IS_OBJECT)) {
					zend_type_error("phpstan_turbo: UnionType::getTypes() must return %s instances", ZSTR_VAL(pt_ce_integer_type->name));
					return zv::Val();
				}
				/* $this->exponentiate($unionType) */
				zv::Val result = isExact() ? exponentiate(unionType) : pt_type_call(self, PT_LC("exponentiate"), 1, unionType);
				if (UNEXPECTED(result.isUndef())) return zv::Val();
				results.push(std::move(result));
			}
			return pt_type_combinator_call_spread(PT_LC("union"), results.table());
		}

		/* $this->getMin() / $this->getMax() — through the object's class */
		NullableLong min, max;
		if (UNEXPECTED(!pt_integer_range_bounds(self, min, max))) return zv::Val();

		if (instanceof_function(Z_OBJCE_P(exponent), pt_ce_integer_range_type)) {
			NullableLong exponentMin, exponentMax;
			if (UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(exponent), exponentMin, exponentMax))) return zv::Val();
			zval poweredMin, poweredMax;
			ZVAL_NULL(&poweredMin);
			ZVAL_NULL(&poweredMax);
			if (!min.isNull && !exponentMin.isNull) {
				if (UNEXPECTED(!phpPow(min.value, exponentMin.value, &poweredMin))) return zv::Val();
			}
			if (!max.isNull && !exponentMax.isNull) {
				if (UNEXPECTED(!phpPow(max.value, exponentMax.value, &poweredMax))) return zv::Val();
			}

			if ((Z_TYPE(poweredMin) != IS_NULL || Z_TYPE(poweredMax) != IS_NULL) && Z_TYPE(poweredMin) != IS_DOUBLE && Z_TYPE(poweredMax) != IS_DOUBLE) {
				return fromInterval(NullableLong::from(&poweredMin), NullableLong::from(&poweredMax), 0);
			}
		}

		bool isConstantScalar;
		if (UNEXPECTED(!pt_type_instanceof(exponent, PT_CLASS_CONSTANT_SCALAR_TYPE, isConstantScalar))) return zv::Val();
		if (isConstantScalar) {
			zv::Val exponentValue = pt_type_call(Z_OBJ_P(exponent), PT_LC("getvalue"), 0, NULL);
			if (UNEXPECTED(exponentValue.isUndef())) return zv::Val();
			if (zv::Ref(exponentValue.raw()).isLong()) {
				zend_long e = zv::Ref(exponentValue.raw()).asLong();
				zval poweredMin, poweredMax;
				ZVAL_NULL(&poweredMin);
				ZVAL_NULL(&poweredMax);
				if (!min.isNull) {
					if (UNEXPECTED(!phpPow(min.value, e, &poweredMin))) return zv::Val();
				}
				if (!max.isNull) {
					if (UNEXPECTED(!phpPow(max.value, e, &poweredMax))) return zv::Val();
				}

				if (Z_TYPE(poweredMin) != IS_DOUBLE && Z_TYPE(poweredMax) != IS_DOUBLE) {
					return fromInterval(NullableLong::from(&poweredMin), NullableLong::from(&poweredMax), 0);
				}
			}
		}

		/* parent::exponentiate($exponent) */
		return pt_integer_type_exponentiate(self, exponent);
	}

	/* every integer of a bounded range as a ConstantIntegerType, unless
	 * there are more than InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT
	 * of them (or the range is unbounded): then []; UNDEF = pending
	 * exception */
	zv::Val getFiniteTypes() const
	{
		NullableLong min = NullableLong::null(), max = NullableLong::null();
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		if (min.isNull || max.isNull) return zv::Val(zv::Arr::empty());

		zend_long limit;
		if (UNEXPECTED(!calculateScalarsLimit(limit))) return zv::Val();
		/* $size = $this->max - $this->min — a float past the int range, and
		 * then certainly above the limit */
		zval size, limitZv;
		phpSub(max.value, min.value, &size);
		ZVAL_LONG(&limitZv, limit);
		if (phpLess(&limitZv, &size)) return zv::Val(zv::Arr::empty());

		zend_long count = Z_LVAL(size);
		zv::Arr types = zv::Arr::create((uint32_t) count + 1);
		for (zend_long i = 0; i <= count; i++) {
			zv::Val type = pt_type_new_constant_integer(min.value + i);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			types.push(std::move(type));
		}
		return zv::Val(std::move(types));
	}

	/* new GenericTypeNode(new IdentifierTypeNode('int'), [$min, $max]) with
	 * each bound a ConstTypeNode(ConstExprIntegerNode) or the 'min'/'max'
	 * identifier; UNDEF = pending exception */
	zv::Val toPhpDocNode() const
	{
		NullableLong min = NullableLong::null(), max = NullableLong::null();
		if (UNEXPECTED(!bounds(min, max))) return zv::Val();
		zv::Val minNode = boundNode(min, "min", 3);
		if (UNEXPECTED(minNode.isUndef())) return zv::Val();
		zv::Val maxNode = boundNode(max, "max", 3);
		if (UNEXPECTED(maxNode.isUndef())) return zv::Val();
		zv::Val intName = zv::Val::string("int", 3);
		zv::Val intNode = pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, intName.raw());
		if (UNEXPECTED(intNode.isUndef())) return zv::Val();
		zv::Arr genericTypes = zv::Arr::create(2);
		genericTypes.push(std::move(minNode));
		genericTypes.push(std::move(maxNode));
		zv::Args args{intNode.raw(), genericTypes.raw()};
		return pt_type_new(PT_CLASS_GENERIC_TYPE_NODE, 2, args);
	}

	/* true/false against a constant bool when 0 is out of the range, false
	 * when the whole range is smaller or greater than $type, else
	 * parent::looseCompare(); UNDEF = pending exception */
	zv::Val looseCompare(zval *type, zval *phpVersion) const
	{
		zend_long isZero;
		if (UNEXPECTED(!zeroIsSuperTypeOfThisValue(isZero))) return zv::Val();
		if (isZero == PT_TRI_NO) {
			zend_long isTrue = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("istrue"), 0, NULL);
			if (UNEXPECTED(isTrue < 0)) return zv::Val();
			if (isTrue == PT_TRI_YES) return constantBoolean(true);
			zend_long isFalse = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isfalse"), 0, NULL);
			if (UNEXPECTED(isFalse < 0)) return zv::Val();
			if (isFalse == PT_TRI_YES) return constantBoolean(false);
		}

		/* $this->isSmallerThan($type, $phpVersion)->yes()
		 * || $this->isGreaterThan($type, $phpVersion)->yes() */
		zv::Args args{type, phpVersion};
		zend_long smaller = isExact() ? isSmallerThan(type, phpVersion, false) : pt_type_call_trinary(self, PT_LC("issmallerthan"), 2, args);
		if (UNEXPECTED(smaller < 0)) return zv::Val();
		bool outside = smaller == PT_TRI_YES;
		if (!outside) {
			zend_long greater = isExact() ? isGreaterThan(type, phpVersion, false) : pt_type_call_trinary(self, PT_LC("isgreaterthan"), 2, args);
			if (UNEXPECTED(greater < 0)) return zv::Val();
			outside = greater == PT_TRI_YES;
		}
		if (outside) return constantBoolean(false);

		/* parent::looseCompare($type, $phpVersion) */
		return pt_integer_type_loose_compare(self, type, phpVersion);
	}

private:
	zend_object *self;

	/* exactly an IntegerRangeType, none of its methods overridden:
	 * $this-calls can go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_integer_range_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	bool slot(uint32_t index, const char *name, NullableLong &out) const
	{
		zval *slot = OBJ_PROP_NUM(self, index);
		if (Z_TYPE_P(slot) == IS_LONG) {
			out = NullableLong::of(Z_LVAL_P(slot));
			return true;
		}
		if (EXPECTED(Z_TYPE_P(slot) == IS_NULL)) {
			out = NullableLong::null();
			return true;
		}
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_integer_range_type->name), name);
		return false;
	}

	static bool same(NullableLong a, NullableLong b)
	{
		return a.isNull ? b.isNull : (!b.isNull && a.value == b.value);
	}

	/* the `$type instanceof self || $type instanceof ConstantIntegerType`
	 * prologue the twin repeats: the bounds of a range ($type->min /
	 * $type->max, the private slots read directly — a subclass instance
	 * has them at the same offsets) or a constant's value twice;
	 * integerLike = false when $type is neither. false = pending exception */
	[[nodiscard]] static bool boundsOf(zval *type, bool &integerLike, NullableLong &min, NullableLong &max)
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_integer_range_type)) {
			integerLike = true;
			return IntegerRangeType(Z_OBJ_P(type)).bounds(min, max);
		}
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_constant_integer_type)) {
			integerLike = true;
			zend_long value;
			if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(type), value))) return false;
			min = max = NullableLong::of(value);
			return true;
		}
		integerLike = false;
		return true;
	}

	/* `$this->min !== null && $this->min > 0 || $this->max !== null && $this->max < 0` */
	static bool excludesZero(NullableLong min, NullableLong max)
	{
		return (!min.isNull && min.value > 0) || (!max.isNull && max.value < 0);
	}

	/* (new ConstantIntegerType(0))->isSuperTypeOf($this): $this is an
	 * IntegerRangeType, so that is ConstantIntegerType::isSuperTypeOf()'s
	 * range branch — maybe when 0 lies within $this->getMin()..$this->getMax()
	 * (the bounds through the object's class, as the twin's calls go), no
	 * otherwise. false = pending exception */
	[[nodiscard]] bool zeroIsSuperTypeOfThisValue(zend_long &out) const
	{
		NullableLong min = NullableLong::null(), max = NullableLong::null();
		if (UNEXPECTED(!pt_integer_range_bounds(self, min, max))) return false;
		out = (min.isNull || min.value <= 0) && (max.isNull || 0 <= max.value) ? PT_TRI_MAYBE : PT_TRI_NO;
		return true;
	}

	/* !$zeroInt->isSuperTypeOf($this)->no() */
	bool zeroIsSuperTypeOfThis(bool &out) const
	{
		zend_long value;
		if (UNEXPECTED(!zeroIsSuperTypeOfThisValue(value))) return false;
		out = value != PT_TRI_NO;
		return true;
	}

	/* (new ConstantIntegerType($value))->$method(...$args) on a method
	 * returning TrinaryLogic; -1 = pending exception */
	[[nodiscard]] static zend_long constantIntegerCall(zend_long value, const char *lcname, size_t len, uint32_t argc, zval *argv)
	{
		zv::Val constant = pt_type_new_constant_integer(value);
		if (UNEXPECTED(constant.isUndef())) return -1;
		return pt_type_call_trinary(Z_OBJ_P(constant.raw()), lcname, len, argc, argv);
	}

	/* $otherType->$method(new ConstantIntegerType($value), $phpVersion);
	 * -1 = pending exception */
	[[nodiscard]] static zend_long otherIsSmallerThanConstant(zval *otherType, zend_long value, zval *phpVersion, const char *lcname, size_t len)
	{
		zv::Val constant = pt_type_new_constant_integer(value);
		if (UNEXPECTED(constant.isUndef())) return -1;
		zv::Args args{constant.raw(), phpVersion};
		return pt_type_call_trinary(Z_OBJ_P(otherType), lcname, len, 2, args);
	}

	/* TrinaryLogic::extremeIdentity(): all identical → that value, else maybe */
	static zend_long extremeIdentity(zend_long a, zend_long b)
	{
		return a == b ? a : PT_TRI_MAYBE;
	}

	static zend_long extremeIdentity(zend_long a, zend_long b, zend_long c)
	{
		return a == b && b == c ? a : PT_TRI_MAYBE;
	}

	/* $result->toAcceptsResult() on an IsSuperTypeOfResult */
	static zv::Val toAcceptsResult(zv::Val result)
	{
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: isSuperTypeOf() must return %s", ZSTR_VAL(pt_ce_is_super_type_of_result->name));
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(result.raw()), PT_LC("toacceptsresult"), 0, NULL);
	}

	/* the three-way comparisons of a non-int $value against PHP_INT_MAX and
	 * PHP_INT_MIN — the callers read `>`, `>=`, `<`, `<=` off the sign. A
	 * float compares as the VM compares int with float, both sides as
	 * doubles — so NAN is neither above nor below either limit, on either
	 * side (a NAN answer of -1 against the max and 1 against the min keeps
	 * every one of the four tests false); anything else compares loosely.
	 * false = pending exception */
	[[nodiscard]] static bool compareToLimits(zval *value, int &againstMax, int &againstMin)
	{
		if (EXPECTED(Z_TYPE_P(value) == IS_DOUBLE)) {
			double d = Z_DVAL_P(value);
			double max = (double) ZEND_LONG_MAX;
			double min = (double) ZEND_LONG_MIN;
			againstMax = d > max ? 1 : (d < max ? -1 : (d == max ? 0 : -1));
			againstMin = d < min ? -1 : (d > min ? 1 : (d == min ? 0 : 1));
			return true;
		}
		zval limit;
		ZVAL_LONG(&limit, ZEND_LONG_MAX);
		againstMax = zend_compare(value, &limit);
		if (UNEXPECTED(EG(exception))) return false;
		ZVAL_LONG(&limit, ZEND_LONG_MIN);
		againstMin = zend_compare(value, &limit);
		return !EG(exception);
	}

	/* (int) ceil($value) / (int) floor($value) for a non-int $value: a
	 * float rounds and casts as the engine casts; anything else is what
	 * ceil()/floor() reject under strict_types. false = pending exception */
	[[nodiscard]] static bool roundToLong(zval *value, bool useCeil, zend_long &out)
	{
		if (UNEXPECTED(Z_TYPE_P(value) != IS_DOUBLE)) {
			zend_type_error("%s(): Argument #1 ($num) must be of type int|float, %s given", useCeil ? "ceil" : "floor", zend_zval_value_name(value));
			return false;
		}
		out = zend_dval_to_lval(useCeil ? ceil(Z_DVAL_P(value)) : floor(Z_DVAL_P(value)));
		return true;
	}

	/* $base ** $exponent — int, or float on overflow / negative exponent;
	 * false = pending exception */
	[[nodiscard]] static bool phpPow(zend_long base, zend_long exponent, zval *out)
	{
		zval b, e;
		ZVAL_LONG(&b, base);
		ZVAL_LONG(&e, exponent);
		if (UNEXPECTED(pow_function(out, &b, &e) != SUCCESS)) return false;
		return !EG(exception);
	}

	/* InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT */
	static bool calculateScalarsLimit(zend_long &out)
	{
		out = PT_INITIALIZER_EXPR_TYPE_RESOLVER_CALCULATE_SCALARS_LIMIT;
		return true;
	}

	/* new ConstTypeNode(new ConstExprIntegerNode((string) $bound)), or
	 * new IdentifierTypeNode($name) for an unbounded side */
	static zv::Val boundNode(NullableLong bound, const char *name, size_t len)
	{
		if (bound.isNull) {
			zv::Val nameZv = zv::Val::string(name, len);
			return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, nameZv.raw());
		}
		zv::Val str = zv::Val::adoptString(zend_long_to_str(bound.value));
		zv::Val constExpr = pt_type_new(PT_CLASS_CONST_EXPR_INTEGER_NODE, 1, str.raw());
		if (UNEXPECTED(constExpr.isUndef())) return zv::Val();
		return pt_type_new(PT_CLASS_CONST_TYPE_NODE, 1, constExpr.raw());
	}

	static zv::Val never() { return pt_type_new_never_type(); }

	static zv::Val integer()
	{
		return pt_val_of<pt_integer_type_new>();
	}

	static zv::Val constantBoolean(bool value)
	{
		zval result;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&result, value))) return zv::Val();
		return zv::Val::adopt(result);
	}

	static bool pushConstantBoolean(zv::Arr &types, bool value)
	{
		zv::Val boolean = constantBoolean(value);
		if (UNEXPECTED(boolean.isUndef())) return false;
		types.push(std::move(boolean));
		return true;
	}

	/* new NullType() — the shadowing class */
	static bool pushNull(zv::Arr &types)
	{
		zval nullType;
		if (UNEXPECTED(!pt_null_type_new(&nullType))) return false;
		types.push(zv::Val::adopt(nullType));
		return true;
	}

	static bool pushRange(zv::Arr &types, zv::Val range)
	{
		if (UNEXPECTED(range.isUndef())) return false;
		types.push(std::move(range));
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::IntegerRangeType;
using phpstanturbo::NullableLong;

zv::Val pt_integer_range_from_interval(NullableLong min, NullableLong max, zend_long shift)
{
	return IntegerRangeType::fromInterval(min, max, shift);
}

zv::Val pt_integer_range_create_all_smaller_than(zval *value)
{
	return IntegerRangeType::createAllSmallerThan(value);
}

zv::Val pt_integer_range_create_all_smaller_than_or_equal_to(zval *value)
{
	return IntegerRangeType::createAllSmallerThanOrEqualTo(value);
}

zv::Val pt_integer_range_create_all_greater_than(zval *value)
{
	return IntegerRangeType::createAllGreaterThan(value);
}

zv::Val pt_integer_range_create_all_greater_than_or_equal_to(zval *value)
{
	return IntegerRangeType::createAllGreaterThanOrEqualTo(value);
}

/* $range->getMin() / $range->getMax() through the object's class; a
 * subclass may override either */
static bool pt_integer_range_bound_call(zend_object *range, const char *lcname, size_t len, NullableLong &out)
{
	zv::Val result = pt_type_call(range, lcname, len, 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	if (UNEXPECTED(!zv::Ref(result.raw()).isLong() && !zv::Ref(result.raw()).isNull())) {
		zend_type_error("phpstan_turbo: %s::%s() must return ?int", ZSTR_VAL(range->ce->name), lcname);
		return false;
	}
	out = NullableLong::from(result.raw());
	return true;
}

bool pt_integer_range_bounds(zend_object *range, NullableLong &min, NullableLong &max)
{
	if (EXPECTED(range->ce == pt_ce_integer_range_type)) return IntegerRangeType(range).bounds(min, max);
	return pt_integer_range_bound_call(range, PT_LC("getmin"), min) && pt_integer_range_bound_call(range, PT_LC("getmax"), max);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS IntegerRangeType(Z_OBJ_P(ZEND_THIS))

/* a ?int parameter as parsed by Z_PARAM_LONG_OR_NULL */
static NullableLong pt_nullable_long_arg(zend_long value, bool isNull)
{
	return isNull ? NullableLong::null() : NullableLong::of(value);
}

/* the createAll*() family: one untyped $value */
static void pt_irt_create_all(INTERNAL_FUNCTION_PARAMETERS, zv::Val (*factory)(zval *))
{
	zval *value;
	if (!zp::parse<zp::Zval>(execute_data, value)) RETURN_THROWS();
	ZVAL_DEREF(value);
	PT_RETURN_VAL(factory(value));
}

/* the getSmallerType() family: one PhpVersion argument, never read */
static void pt_irt_comparison_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (IntegerRangeType::*method)() const)
{
	zval *phpVersion;
	if (!zp::parse<zp::Zval>(execute_data, phpVersion)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)());
}

/* isSmallerThan() & co.: (Type $otherType, PhpVersion $phpVersion) */
static void pt_irt_comparison(INTERNAL_FUNCTION_PARAMETERS, bool greater, bool orEqual)
{
	zval *otherType, *phpVersion;
	if (!zp::parse<zp::Obj, zp::Zval>(execute_data, otherType, phpVersion)) RETURN_THROWS();
	PT_RETURN_TRINARY_OR_THROW(greater ? PT_THIS.isGreaterThan(otherType, phpVersion, orEqual) : PT_THIS.isSmallerThan(otherType, phpVersion, orEqual));
}

/* the try*() family: one Type argument, a ?Type result */
static void pt_irt_try(INTERNAL_FUNCTION_PARAMETERS, zv::Val (IntegerRangeType::*method)(zval *) const)
{
	zval *otherType;
	if (!zp::parse<zp::Obj>(execute_data, otherType)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(otherType));
}

/* getMin() / getMax() */
static void pt_irt_bound(INTERNAL_FUNCTION_PARAMETERS, bool (IntegerRangeType::*method)(NullableLong &) const)
{
	ZEND_PARSE_PARAMETERS_NONE();
	NullableLong bound = NullableLong::null();
	if (UNEXPECTED(!(PT_THIS.*method)(bound))) RETURN_THROWS();
	if (bound.isNull) {
		RETURN_NULL();
	}
	RETURN_LONG(bound.value);
}

void pt_register_integer_range_type()
{
	reg::Class cls("PHPStan\\Type\\IntegerRangeType");
	ptdecl::IntegerRangeType::declareClass(cls);
	/* "min" and "max" must stay the first two declared properties
	 * (slots::min, slots::max) */
	ptdecl::IntegerRangeType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long min, max;
		bool minIsNull, maxIsNull;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_LONG_OR_NULL(min, minIsNull)
			Z_PARAM_LONG_OR_NULL(max, maxIsNull)
		ZEND_PARSE_PARAMETERS_END();
		PT_THIS.construct(pt_nullable_long_arg(min, minIsNull), pt_nullable_long_arg(max, maxIsNull));
	});

	cls.method(sigs::fromInterval, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long min, max, shift = 0;
		bool minIsNull, maxIsNull;
		ZEND_PARSE_PARAMETERS_START(2, 3)
			Z_PARAM_LONG_OR_NULL(min, minIsNull)
			Z_PARAM_LONG_OR_NULL(max, maxIsNull)
			Z_PARAM_OPTIONAL
			Z_PARAM_LONG(shift)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(IntegerRangeType::fromInterval(pt_nullable_long_arg(min, minIsNull), pt_nullable_long_arg(max, maxIsNull), shift));
	});

	cls.method(sigs::isDisjoint, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long minA, maxA, minB, maxB;
		bool minAIsNull, maxAIsNull, minBIsNull, maxBIsNull;
		bool touchingIsDisjoint = true;
		ZEND_PARSE_PARAMETERS_START(4, 5)
			Z_PARAM_LONG_OR_NULL(minA, minAIsNull)
			Z_PARAM_LONG_OR_NULL(maxA, maxAIsNull)
			Z_PARAM_LONG_OR_NULL(minB, minBIsNull)
			Z_PARAM_LONG_OR_NULL(maxB, maxBIsNull)
			Z_PARAM_OPTIONAL
			Z_PARAM_BOOL(touchingIsDisjoint)
		ZEND_PARSE_PARAMETERS_END();
		RETURN_BOOL(IntegerRangeType::isDisjoint(
			pt_nullable_long_arg(minA, minAIsNull),
			pt_nullable_long_arg(maxA, maxAIsNull),
			pt_nullable_long_arg(minB, minBIsNull),
			pt_nullable_long_arg(maxB, maxBIsNull),
			touchingIsDisjoint
		));
	});

	cls.method(sigs::createAllSmallerThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_create_all(INTERNAL_FUNCTION_PARAM_PASSTHRU, IntegerRangeType::createAllSmallerThan);
	});

	cls.method(sigs::createAllSmallerThanOrEqualTo, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_create_all(INTERNAL_FUNCTION_PARAM_PASSTHRU, IntegerRangeType::createAllSmallerThanOrEqualTo);
	});

	cls.method(sigs::createAllGreaterThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_create_all(INTERNAL_FUNCTION_PARAM_PASSTHRU, IntegerRangeType::createAllGreaterThan);
	});

	cls.method(sigs::createAllGreaterThanOrEqualTo, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_create_all(INTERNAL_FUNCTION_PARAM_PASSTHRU, IntegerRangeType::createAllGreaterThanOrEqualTo);
	});

	cls.method(sigs::getMin, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_bound(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntegerRangeType::getMin);
	});

	cls.method(sigs::getMax, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_bound(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntegerRangeType::getMax);
	});

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *level;
		if (!zp::parse<zp::Obj>(execute_data, level)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.describe());
	});
	cls.op<PT_OP_DESCRIBE, &IntegerRangeType::describe>();

	cls.method<&IntegerRangeType::shift, zp::Long>(sigs::shift);

	cls.method<&IntegerRangeType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return IntegerRangeType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method<&IntegerRangeType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &IntegerRangeType::isSuperTypeOf>();

	cls.method<&IntegerRangeType::isSubTypeOf, zp::Obj>(sigs::isSubTypeOf);
	cls.method<&IntegerRangeType::isSubTypeOfUnionWithReason, zp::Obj>(sigs::isSubTypeOfUnionWithReason);
	cls.op<PT_OP_IS_SUB_TYPE_OF, &IntegerRangeType::isSubTypeOf>();

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method<&IntegerRangeType::equals, zp::TypeObj>(sigs::equals);
	cls.op<PT_OP_EQUALS, &IntegerRangeType::equals>();

	cls.method(sigs::generalize, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *precision;
		if (!zp::parse<zp::Obj>(execute_data, precision)) RETURN_THROWS();
		PT_RETURN_VAL(IntegerRangeType::generalize());
	});

	cls.method(sigs::isSmallerThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_comparison(INTERNAL_FUNCTION_PARAM_PASSTHRU, false, false);
	});

	cls.method(sigs::isSmallerThanOrEqual, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_comparison(INTERNAL_FUNCTION_PARAM_PASSTHRU, false, true);
	});

	cls.method(sigs::isGreaterThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_comparison(INTERNAL_FUNCTION_PARAM_PASSTHRU, true, false);
	});

	cls.method(sigs::isGreaterThanOrEqual, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_comparison(INTERNAL_FUNCTION_PARAM_PASSTHRU, true, true);
	});

	cls.method(sigs::getSmallerType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntegerRangeType::getSmallerType);
	});

	cls.method(sigs::getSmallerOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntegerRangeType::getSmallerOrEqualType);
	});

	cls.method(sigs::getGreaterType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntegerRangeType::getGreaterType);
	});

	cls.method(sigs::getGreaterOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntegerRangeType::getGreaterOrEqualType);
	});

	cls.method<&IntegerRangeType::toBoolean>(sigs::toBoolean);

	cls.method<&IntegerRangeType::toAbsoluteNumber>(sigs::toAbsoluteNumber);

	cls.method<&IntegerRangeType::toBitwiseNotType>(sigs::toBitwiseNotType);

	cls.method<&IntegerRangeType::toString>(sigs::toString);

	cls.method(sigs::tryUnion, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_try(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntegerRangeType::tryUnion);
	});

	cls.method(sigs::tryIntersect, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_try(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntegerRangeType::tryIntersect);
	});

	cls.method(sigs::tryRemove, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_irt_try(INTERNAL_FUNCTION_PARAM_PASSTHRU, &IntegerRangeType::tryRemove);
	});

	cls.method<&IntegerRangeType::exponentiate, zp::Obj>(sigs::exponentiate);

	cls.method<&IntegerRangeType::getFiniteTypes>(sigs::getFiniteTypes);

	cls.method<&IntegerRangeType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method<&IntegerRangeType::looseCompare, zp::Obj, zp::Zval>(sigs::looseCompare);

	cls.shadow(&pt_ce_integer_range_type);
}

/* }}} */
