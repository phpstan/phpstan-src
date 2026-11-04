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
 * work on NumberSetData instead.
 */

#include "TypeTraits.h"
#include "generated/NumberSet.h"

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

} // namespace phpstanturbo

using phpstanturbo::NumberSet;
using phpstanturbo::NumberSetData;

zv::Val pt_number_set_new(const NumberSetData &data)
{
	return NumberSet::create(data);
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

PT_MINIT_REGISTRATION(pt_register_number_set)
{
	reg::Class cls("PHPStan\\Type\\NumberSet");
	ptdecl::NumberSet::declareClass(cls);
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

	cls.shadow(&pt_ce_number_set);
}

/* }}} */
