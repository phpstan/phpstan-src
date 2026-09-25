/*
 * PHPStanTurbo\TrinaryLogic — native implementation of PHPStan\TrinaryLogic.
 *
 * When the extension is active, PHPStan\TrinaryLogic is this class, declared
 * under that name at activation (final, like the twin); every instance — the
 * singletons included — is of it.
 *
 * The logic lives in the TrinaryLogic handle class below, structured to
 * mirror src/TrinaryLogic.php method for method; the PHP_METHOD functions at
 * the bottom are only the engine ABI glue (parameter parsing + delegation).
 */

#include "support.h"
#include "generated/TrinaryLogic.h"

namespace sigs = ptdecl::TrinaryLogic::sig;
#include "zv.h"

namespace phpstanturbo {

/* Mirrors PHPStan\TrinaryLogic. State lives in the PHP object's $value. */
class TrinaryLogic
{
public:
	static constexpr zend_long YES = PT_TRI_YES;
	static constexpr zend_long MAYBE = PT_TRI_MAYBE;
	static constexpr zend_long NO = PT_TRI_NO;

	explicit TrinaryLogic(zend_object *self) : self(self) {}

	zend_long value() const { return pt_trinary_value(self); }

	static zv::Val create(zend_long value) { return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(value))); }
	static zv::Val createYes() { return create(YES); }
	static zv::Val createNo() { return create(NO); }
	static zv::Val createMaybe() { return create(MAYBE); }
	static zv::Val createFromBoolean(bool value) { return create(value ? YES : NO); }

	bool yes() const { return value() == YES; }
	bool maybe() const { return value() == MAYBE; }
	bool no() const { return value() == NO; }

	/* and() — a C++ keyword, hence the underscore */
	zv::Val and_(const TrinaryLogic *operand, zval *rest, uint32_t restCount) const
	{
		zend_long acc = value();
		acc &= operand != NULL ? operand->value() : YES;
		for (uint32_t i = 0; i < restCount; i++) {
			acc &= TrinaryLogic(zv::Ref(&rest[i]).deref().asObject()).value();
		}
		return create(acc);
	}

	/* or() — a C++ keyword, hence the underscore */
	zv::Val or_(const TrinaryLogic *operand, zval *rest, uint32_t restCount) const
	{
		zend_long acc = value();
		acc |= operand != NULL ? operand->value() : NO;
		for (uint32_t i = 0; i < restCount; i++) {
			acc |= TrinaryLogic(zv::Ref(&rest[i]).deref().asObject()).value();
		}
		return create(acc);
	}

	static zv::Val extremeIdentity(zval *operands, uint32_t count)
	{
		zend_long min, max;
		min = max = TrinaryLogic(zv::Ref(&operands[0]).deref().asObject()).value();
		for (uint32_t i = 1; i < count; i++) {
			zend_long v = TrinaryLogic(zv::Ref(&operands[i]).deref().asObject()).value();
			if (v < min) {
				min = v;
			}
			if (v > max) {
				max = v;
			}
		}
		return create(min == max ? min : MAYBE);
	}

	static zv::Val maxMin(zval *operands, uint32_t count)
	{
		zend_long max = NO;
		zend_long min = YES;
		for (uint32_t i = 0; i < count; i++) {
			zend_long v = TrinaryLogic(zv::Ref(&operands[i]).deref().asObject()).value();
			max |= v;
			min &= v;
		}
		return create(max == YES ? YES : min);
	}

	zv::Val negate() const { return create(3 >> value()); }

	bool equals(const TrinaryLogic &other) const { return self == other.self; }

	/* returns the greater operand's object, or null when equal */
	zv::Val compareTo(zval *thisZv, zval *otherZv) const
	{
		TrinaryLogic other(Z_OBJ_P(otherZv));
		if (value() > other.value()) return zv::Val::copyOf(zv::Ref(thisZv));
		if (other.value() > value()) return zv::Val::copyOf(zv::Ref(otherZv));
		return zv::Val::null();
	}

	const char *describe() const
	{
		if (value() == YES) return "Yes";
		if (value() == MAYBE) return "Maybe";
		return "No";
	}

	/* new BooleanType() for maybe, new ConstantBooleanType(yes/no) otherwise
	 * — both shadowing classes, instantiated directly (BooleanType.cpp /
	 * ConstantBooleanType.cpp); UNDEF result means a pending exception */
	zv::Val toBooleanType() const
	{
		zval obj;
		if (maybe()) {
			if (UNEXPECTED(!pt_boolean_type_new(&obj))) return zv::Val();
			return zv::Val::adopt(obj);
		}
		if (UNEXPECTED(!pt_constant_boolean_type_new(&obj, yes()))) return zv::Val();
		return zv::Val::adopt(obj);
	}

private:
	zend_object *self;
};

/*
 * The lazy* trio shares one accumulation loop over callback results,
 * mirroring the closures the PHP implementation passes around.
 */
class LazyEvaluation
{
public:
	enum Mode
	{
		AND,
		OR,
		MAX_MIN,
	};

	LazyEvaluation(zend_fcall_info fci, zend_fcall_info_cache fcc) : fci(fci), fcc(fcc) {}

	/* calls the callback for one item; UNDEF result means a pending exception */
	zv::Val callbackResult(zv::Ref item)
	{
		zval retval, param;
		ZVAL_COPY_VALUE(&param, item.raw());
		fci.retval = &retval;
		fci.param_count = 1;
		fci.params = &param;
		fci.named_params = NULL;

		if (UNEXPECTED(zend_call_function(&fci, &fcc) != SUCCESS || EG(exception))) return zv::Val();
		if (UNEXPECTED(Z_TYPE(retval) != IS_OBJECT || !instanceof_function(Z_OBJCE(retval), pt_ce_trinary))) {
			zval_ptr_dtor(&retval);
			zend_type_error("Return value of the callback must be of type %s", ZSTR_VAL(pt_ce_trinary->name));
			return zv::Val();
		}
		return zv::Val::adopt(retval);
	}

	/* the shared and/or/maxMin accumulation; UNDEF means a pending exception */
	zv::Val run(Mode mode, zval *thisZv, zv::ArrRef objects)
	{
		zend_long thisValue = 0;
		if (mode != MAX_MIN) {
			thisValue = TrinaryLogic(Z_OBJ_P(thisZv)).value();
			if (mode == AND && thisValue == TrinaryLogic::NO) return zv::Val::copyOf(zv::Ref(thisZv));
			if (mode == OR && thisValue == TrinaryLogic::YES) return zv::Val::copyOf(zv::Ref(thisZv));
		}

		zend_long acc = mode == OR ? TrinaryLogic::NO : TrinaryLogic::YES;

		for (auto entry : objects) {
			zv::Val result = callbackResult(entry.value());
			if (result.isUndef()) return zv::Val();
			zend_long resultValue = TrinaryLogic(zv::Ref(result.raw()).asObject()).value();

			if (mode == AND && resultValue == TrinaryLogic::NO) return result;
			if ((mode == OR || mode == MAX_MIN) && resultValue == TrinaryLogic::YES) return result;

			if (mode == OR) {
				acc |= resultValue;
			} else {
				acc &= resultValue;
			}
		}

		if (mode == AND) {
			acc &= thisValue;
		} else if (mode == OR) {
			acc |= thisValue;
		}

		return TrinaryLogic::create(acc);
	}

	/* lazyExtremeIdentity: all results identical → that result, else maybe */
	zv::Val runExtremeIdentity(zv::ArrRef objects)
	{
		zv::Val last;
		for (auto entry : objects) {
			zv::Val result = callbackResult(entry.value());
			if (result.isUndef()) return zv::Val();
			if (last.isUndef()) {
				last = std::move(result);
				continue;
			}
			if (zv::Ref(result.raw()).asObject() != zv::Ref(last.raw()).asObject()) return TrinaryLogic::create(TrinaryLogic::MAYBE);
		}
		return last;
	}

private:
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
};

} // namespace phpstanturbo

using phpstanturbo::LazyEvaluation;
using phpstanturbo::TrinaryLogic;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

static zend_result pt_verify_trinary_variadic(zval *args, uint32_t count, uint32_t offset)
{
	for (uint32_t i = 0; i < count; i++) {
		if (UNEXPECTED(!zv::Ref(&args[i]).deref().instanceOf(pt_ce_trinary))) {
			zend_argument_type_error(offset + i, "must be of type %s", ZSTR_VAL(pt_ce_trinary->name));
			return FAILURE;
		}
	}
	return SUCCESS;
}

static void pt_trinary_lazy(INTERNAL_FUNCTION_PARAMETERS, LazyEvaluation::Mode mode)
{
	HashTable *objects;
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;

	ZEND_PARSE_PARAMETERS_START(2, 2)
		Z_PARAM_ARRAY_HT(objects)
		Z_PARAM_FUNC(fci, fcc)
	ZEND_PARSE_PARAMETERS_END();

	/* no empty-array check for MAX_MIN: unlike extremeIdentity()/maxMin(), the
	 * PHP twin's lazyMaxMin([]) returns Yes ($min starts at YES), and run()'s
	 * accumulator reproduces that */

	zval objectsZv;
	ZVAL_ARR(&objectsZv, objects);
	zv::Val result = LazyEvaluation(fci, fcc).run(mode, ZEND_THIS, zv::ArrRef(&objectsZv));
	if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
	result.intoReturnValue(return_value);
}

static void pt_trinary_variadic_op(INTERNAL_FUNCTION_PARAMETERS, bool extremeIdentity)
{
	zval *operands = NULL;
	uint32_t count = 0;

	ZEND_PARSE_PARAMETERS_START(0, -1)
		Z_PARAM_VARIADIC('+', operands, count)
	ZEND_PARSE_PARAMETERS_END();

	if (UNEXPECTED(count == 0)) {
		pt_throw_should_not_happen();
		RETURN_THROWS();
	}
	if (UNEXPECTED(pt_verify_trinary_variadic(operands, count, 1) != SUCCESS)) RETURN_THROWS();

	(extremeIdentity ? TrinaryLogic::extremeIdentity(operands, count) : TrinaryLogic::maxMin(operands, count)).intoReturnValue(return_value);
}

static void pt_trinary_and_or(INTERNAL_FUNCTION_PARAMETERS, bool isAnd)
{
	zval *operand = NULL;
	zval *rest = NULL;
	uint32_t restCount = 0;

	ZEND_PARSE_PARAMETERS_START(0, -1)
		Z_PARAM_OPTIONAL
		Z_PARAM_OBJECT_OF_CLASS_OR_NULL(operand, pt_ce_trinary)
		Z_PARAM_VARIADIC('+', rest, restCount)
	ZEND_PARSE_PARAMETERS_END();

	if (UNEXPECTED(pt_verify_trinary_variadic(rest, restCount, 2) != SUCCESS)) RETURN_THROWS();

	TrinaryLogic self(Z_OBJ_P(ZEND_THIS));
	TrinaryLogic operandHandle(operand != NULL ? Z_OBJ_P(operand) : NULL);
	const TrinaryLogic *operandPtr = operand != NULL ? &operandHandle : NULL;
	(isAnd ? self.and_(operandPtr, rest, restCount) : self.or_(operandPtr, rest, restCount)).intoReturnValue(return_value);
}

PT_MINIT_REGISTRATION(pt_register_trinary_logic)
{
	reg::Class cls("PHPStan\\TrinaryLogic");
	ptdecl::TrinaryLogic::declareClass(cls);
	/* the twin's `private int $value` is slot 0 (PT_TRI_PROP_VALUE); the
	 * static registry and flyweight properties stay unused — the natives
	 * keep the three instances in pt_trinary_singleton() */
	ptdecl::TrinaryLogic::declareProperties(cls);
	static_assert(ptdecl::TrinaryLogic::slot::value == PT_TRI_PROP_VALUE);
	cls.privateClassConstantLong("YES", PT_TRI_YES);
	cls.privateClassConstantLong("MAYBE", PT_TRI_MAYBE);
	cls.privateClassConstantLong("NO", PT_TRI_NO);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long value;
		if (!zp::parse<zp::Long>(execute_data, value)) RETURN_THROWS();
		zval *slot = OBJ_PROP_NUM(Z_OBJ_P(ZEND_THIS), PT_TRI_PROP_VALUE);
		ZVAL_LONG(slot, value);
		Z_PROP_FLAG_P(slot) = 0;
	});

	/* private static create(): the flyweight of a value — a fresh instance
	 * for a value no singleton carries, as the twin's registry would make */
	cls.method(sigs::create, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long value;
		if (!zp::parse<zp::Long>(execute_data, value)) RETURN_THROWS();
		if (value == PT_TRI_YES || value == PT_TRI_MAYBE || value == PT_TRI_NO) {
			TrinaryLogic::create(value).intoReturnValue(return_value);
			return;
		}
		object_init_ex(return_value, pt_ce_trinary);
		zval *slot = OBJ_PROP_NUM(Z_OBJ_P(return_value), PT_TRI_PROP_VALUE);
		ZVAL_LONG(slot, value);
		Z_PROP_FLAG_P(slot) = 0;
	});

	cls.method(sigs::createYes, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		TrinaryLogic::createYes().intoReturnValue(return_value);
	});

	cls.method(sigs::createNo, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		TrinaryLogic::createNo().intoReturnValue(return_value);
	});

	cls.method(sigs::createMaybe, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		TrinaryLogic::createMaybe().intoReturnValue(return_value);
	});

	cls.method(sigs::createFromBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool value;
		if (!zp::parse<zp::Bool>(execute_data, value)) RETURN_THROWS();
		TrinaryLogic::createFromBoolean(value).intoReturnValue(return_value);
	});

	cls.method(sigs::yes, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(TrinaryLogic(Z_OBJ_P(ZEND_THIS)).yes());
	});

	cls.method(sigs::maybe, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(TrinaryLogic(Z_OBJ_P(ZEND_THIS)).maybe());
	});

	cls.method(sigs::no, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(TrinaryLogic(Z_OBJ_P(ZEND_THIS)).no());
	});

	cls.method(sigs::toBooleanType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Val result = TrinaryLogic(Z_OBJ_P(ZEND_THIS)).toBooleanType();
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method(sigs::and_, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_trinary_and_or(INTERNAL_FUNCTION_PARAM_PASSTHRU, true);
	});

	cls.method(sigs::lazyAnd, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_trinary_lazy(INTERNAL_FUNCTION_PARAM_PASSTHRU, LazyEvaluation::AND);
	});

	cls.method(sigs::or_, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_trinary_and_or(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});

	cls.method(sigs::lazyOr, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_trinary_lazy(INTERNAL_FUNCTION_PARAM_PASSTHRU, LazyEvaluation::OR);
	});

	cls.method(sigs::extremeIdentity, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_trinary_variadic_op(INTERNAL_FUNCTION_PARAM_PASSTHRU, true);
	});

	cls.method(sigs::lazyExtremeIdentity, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *objects;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;

		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_ARRAY_HT(objects)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();

		if (UNEXPECTED(zend_hash_num_elements(objects) == 0)) {
			pt_throw_should_not_happen();
			RETURN_THROWS();
		}

		zval objectsZv;
		ZVAL_ARR(&objectsZv, objects);
		zv::Val result = LazyEvaluation(fci, fcc).runExtremeIdentity(zv::ArrRef(&objectsZv));
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method(sigs::maxMin, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_trinary_variadic_op(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});

	cls.method(sigs::lazyMaxMin, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_trinary_lazy(INTERNAL_FUNCTION_PARAM_PASSTHRU, LazyEvaluation::MAX_MIN);
	});

	cls.method(sigs::negate, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		TrinaryLogic(Z_OBJ_P(ZEND_THIS)).negate().intoReturnValue(return_value);
	});

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_trinary)
		ZEND_PARSE_PARAMETERS_END();
		RETURN_BOOL(TrinaryLogic(Z_OBJ_P(ZEND_THIS)).equals(TrinaryLogic(Z_OBJ_P(other))));
	});

	cls.method(sigs::compareTo, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_trinary)
		ZEND_PARSE_PARAMETERS_END();
		TrinaryLogic(Z_OBJ_P(ZEND_THIS)).compareTo(ZEND_THIS, other).intoReturnValue(return_value);
	});

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_STRING(TrinaryLogic(Z_OBJ_P(ZEND_THIS)).describe());
	});

	cls.shadow(&pt_ce_trinary);
}

/* }}} */
