/*
 * PHPStanTurbo\CallableTypeHelper — native implementation of
 * PHPStan\Type\CallableTypeHelper.
 *
 * Declared as PHPStan\Type\CallableTypeHelper itself at activation: final,
 * one static helper. isParametersAcceptorSuperTypeOf() walks the two
 * acceptors' parameter lists exactly as the twin does — the acceptors, the
 * parameter reflections and the types they hold are PHP objects (or the
 * shadowed Type classes) answering through their own methods; the result
 * objects are the shadowed IsSuperTypeOfResult, built through its exported
 * constructor and combined natively where it is native.
 *
 * The logic lives in the CallableTypeHelper handle class below, mirroring
 * src/Type/CallableTypeHelper.php; the registration at the bottom is only
 * the engine ABI glue.
 */

#include "TypeTraits.h"
#include "generated/CallableTypeHelper.h"

namespace sigs = ptdecl::CallableTypeHelper::sig;

zend_class_entry *pt_ce_callable_type_helper = nullptr;

/* AcceptsResult.cpp: new <ce>($trinary, $reasons, $lazyReasons) — the
 * arrays owned and consumed; false = pending exception */
[[nodiscard]] bool pt_result_object_create(zval *out, zend_class_entry *ce, zval *trinary, zval *reasons, zval *lazyReasons);

namespace phpstanturbo {

/* Mirrors PHPStan\Type\CallableTypeHelper. */
class CallableTypeHelper
{
public:
	/* UNDEF = pending exception */
	static zv::Val isParametersAcceptorSuperTypeOf(zval *ours, zval *theirs, bool treatMixedAsAny, bool strictTypes)
	{
		zv::Val theirParametersVal = callArray(theirs, PT_LC("getparameters"), 0, NULL);
		if (UNEXPECTED(theirParametersVal.isUndef())) return zv::Val();
		zv::Val ourParameters = callArray(ours, PT_LC("getparameters"), 0, NULL);
		if (UNEXPECTED(ourParameters.isUndef())) return zv::Val();

		/* $lastParameter = the last of $theirParameters (null for none) */
		zv::Val lastParameter;
		for (zv::ArrayEntry entry : zv::ArrRef(theirParametersVal.raw())) {
			lastParameter = zv::Val::copyOf(entry.value().deref());
		}
		uint32_t theirParameterCount = zv::ArrRef(theirParametersVal.raw()).size();
		uint32_t ourParameterCount = zv::ArrRef(ourParameters.raw()).size();
		zv::Arr theirParameters = zv::Arr::adoptVal(std::move(theirParametersVal));
		if (!lastParameter.isUndef() && !zv::Ref(lastParameter.raw()).isNull() && theirParameterCount < ourParameterCount) {
			bool variadic;
			if (UNEXPECTED(!callBool(lastParameter.raw(), PT_LC("isvariadic"), variadic))) return zv::Val();
			if (variadic) {
				/* foreach (array_keys($ourParameters) as $i): $theirParameters[] =
				 * $lastParameter unless the key exists there already */
				for (zv::ArrayEntry entry : zv::ArrRef(ourParameters.raw())) {
					zend_string *key = entry.stringKeyOrNull();
					bool exists = key != NULL ? zend_symtable_find(theirParameters.table(), key) != NULL : zend_hash_index_exists(theirParameters.table(), entry.indexKey());
					if (exists) continue;
					theirParameters.push(zv::Ref(lastParameter.raw()));
				}
			}
		}

		zv::Val result = pt_type_is_super_type_of_result(PT_TRI_YES);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(theirParameters.raw())) {
			zval *theirParameter = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(theirParameter) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: getParameters() must return a list of ParameterReflection");
				return zv::Val();
			}
			zend_long position;
			if (UNEXPECTED(!keyPlusOne(entry, position))) return zv::Val();
			zv::Val theirName = callString(theirParameter, PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(theirName.isUndef())) return zv::Val();
			zv::Val parameterDescription = ZSTR_LEN(Z_STR_P(theirName.raw())) == 0
				? zv::Val::adoptString(zend_strpprintf(0, "#" ZEND_LONG_FMT, position))
				: zv::Val::adoptString(zend_strpprintf(0, "#" ZEND_LONG_FMT " $%s", position, ZSTR_VAL(Z_STR_P(theirName.raw()))));

			/* isset($ourParameters[$i]) */
			zend_string *key = entry.stringKeyOrNull();
			zval *ourParameter = key != NULL ? zend_symtable_find(Z_ARRVAL_P(ourParameters.raw()), key) : zend_hash_index_find(Z_ARRVAL_P(ourParameters.raw()), entry.indexKey());
			if (ourParameter != NULL) {
				ZVAL_DEREF(ourParameter);
				if (Z_TYPE_P(ourParameter) == IS_NULL) {
					ourParameter = NULL;
				}
			}
			if (ourParameter == NULL) {
				bool theirOptional;
				if (UNEXPECTED(!callBool(theirParameter, PT_LC("isoptional"), theirOptional))) return zv::Val();
				if (theirOptional) continue;
				zv::Val accepts = resultNo(zend_strpprintf(0, "Parameter %s of passed callable is required but accepting callable does not have that parameter. It will be called without it.", ZSTR_VAL(Z_STR_P(parameterDescription.raw()))));
				if (UNEXPECTED(accepts.isUndef())) return zv::Val();
				result = pt_type_result_and(std::move(result), accepts.raw());
				if (UNEXPECTED(result.isUndef())) return zv::Val();
				continue;
			}
			if (UNEXPECTED(Z_TYPE_P(ourParameter) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: getParameters() must return a list of ParameterReflection");
				return zv::Val();
			}

			zv::Val ourParameterType = callObject(ourParameter, PT_LC("gettype"), 0, NULL);
			if (UNEXPECTED(ourParameterType.isUndef())) return zv::Val();

			bool ourOptional, theirOptional;
			if (UNEXPECTED(!callBool(ourParameter, PT_LC("isoptional"), ourOptional))) return zv::Val();
			if (ourOptional) {
				if (UNEXPECTED(!callBool(theirParameter, PT_LC("isoptional"), theirOptional))) return zv::Val();
				if (!theirOptional) {
					zv::Val accepts = resultNo(zend_strpprintf(0, "Parameter %s of passed callable is required but the parameter of accepting callable is optional. It might be called without it.", ZSTR_VAL(Z_STR_P(parameterDescription.raw()))));
					if (UNEXPECTED(accepts.isUndef())) return zv::Val();
					result = pt_type_result_and(std::move(result), accepts.raw());
					if (UNEXPECTED(result.isUndef())) return zv::Val();
				}
			}

			zv::Val theirParameterType = callObject(theirParameter, PT_LC("gettype"), 0, NULL);
			if (UNEXPECTED(theirParameterType.isUndef())) return zv::Val();
			zv::Val isSuperType;
			if (treatMixedAsAny) {
				zv::Args args{ourParameterType.raw(), strictTypes};
				zv::Val accepts = callObject(theirParameterType.raw(), PT_LC("accepts"), 2, args);
				if (UNEXPECTED(accepts.isUndef())) return zv::Val();
				isSuperType = resultFrom(accepts.raw());
			} else {
				isSuperType = callObject(theirParameterType.raw(), PT_LC("issupertypeof"), 1, ourParameterType.raw());
			}
			if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();

			zend_long isSuperTypeValue = pt_type_result_trinary(isSuperType.raw());
			if (UNEXPECTED(isSuperTypeValue < 0)) return zv::Val();
			if (isSuperTypeValue == PT_TRI_MAYBE) {
				zv::Val verbosity = pt_type_verbosity_recommended(theirParameterType.raw(), ourParameterType.raw());
				if (UNEXPECTED(verbosity.isUndef())) return zv::Val();
				zv::Val theirDescription = callString(theirParameterType.raw(), PT_LC("describe"), 1, verbosity.raw());
				if (UNEXPECTED(theirDescription.isUndef())) return zv::Val();
				zv::Val ourDescription = callString(ourParameterType.raw(), PT_LC("describe"), 1, verbosity.raw());
				if (UNEXPECTED(ourDescription.isUndef())) return zv::Val();
				zv::Val reason = zv::Val::adoptString(zend_strpprintf(
					0,
					"Type %s of parameter %s of passed callable needs to be same or wider than parameter type %s of accepting callable.",
					ZSTR_VAL(Z_STR_P(theirDescription.raw())),
					ZSTR_VAL(Z_STR_P(parameterDescription.raw())),
					ZSTR_VAL(Z_STR_P(ourDescription.raw()))
				));
				/* new IsSuperTypeOfResult($isSuperType->result, array_merge($isSuperType->reasons, [$reason])) */
				zv::Val trinary, reasons;
				if (UNEXPECTED(!resultParts(isSuperType.raw(), trinary, reasons))) return zv::Val();
				zv::Arr merged = zv::Arr::create(zv::ArrRef(reasons.raw()).size() + 1);
				if (UNEXPECTED(!pt_callable_array_merge_into(merged, reasons.raw()))) return zv::Val();
				merged.push(std::move(reason));
				isSuperType = create(trinary.raw(), std::move(merged));
				if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
			}

			result = pt_type_result_and(std::move(result), isSuperType.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
		}

		if (!treatMixedAsAny && theirParameterCount < ourParameterCount) {
			zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
			if (UNEXPECTED(maybe.isUndef())) return zv::Val();
			result = pt_type_result_and(std::move(result), maybe.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
		}

		zv::Val theirReturnType = callObject(theirs, PT_LC("getreturntype"), 0, NULL);
		if (UNEXPECTED(theirReturnType.isUndef())) return zv::Val();
		zv::Val ourReturnType = callObject(ours, PT_LC("getreturntype"), 0, NULL);
		if (UNEXPECTED(ourReturnType.isUndef())) return zv::Val();
		zv::Val isReturnTypeSuperType;
		if (treatMixedAsAny) {
			zv::Args args{theirReturnType.raw(), true};
			zv::Val accepts = callObject(ourReturnType.raw(), PT_LC("accepts"), 2, args);
			if (UNEXPECTED(accepts.isUndef())) return zv::Val();
			isReturnTypeSuperType = resultFrom(accepts.raw());
		} else {
			isReturnTypeSuperType = callObject(ourReturnType.raw(), PT_LC("issupertypeof"), 1, theirReturnType.raw());
		}
		if (UNEXPECTED(isReturnTypeSuperType.isUndef())) return zv::Val();

		/* $ours->isPure() / $ours->isStaticClosure(): yes demands the same
		 * of theirs, no its negation */
		if (UNEXPECTED(!andTrinaryProbe(result, ours, theirs, PT_LC("ispure")))) return zv::Val();
		if (UNEXPECTED(!andTrinaryProbe(result, ours, theirs, PT_LC("isstaticclosure")))) return zv::Val();

		return pt_type_result_and(std::move(result), isReturnTypeSuperType.raw());
	}

private:
	/* $result = $result->and(new IsSuperTypeOfResult($theirs->probe()[->negate()], []))
	 * when $ours->probe() is yes / no; false = pending exception */
	[[nodiscard]] static bool andTrinaryProbe(zv::Val &result, zval *ours, zval *theirs, const char *lcname, size_t len)
	{
		zv::Val ourAnswer = callObject(ours, lcname, len, 0, NULL);
		if (UNEXPECTED(ourAnswer.isUndef())) return false;
		zend_long ourValue = pt_type_trinary_value(ourAnswer.raw());
		if (UNEXPECTED(ourValue < 0)) return false;
		if (ourValue != PT_TRI_YES && ourValue != PT_TRI_NO) return true;
		zv::Val theirAnswer = callObject(theirs, lcname, len, 0, NULL);
		if (UNEXPECTED(theirAnswer.isUndef())) return false;
		zv::Val trinary;
		if (ourValue == PT_TRI_YES) {
			trinary = std::move(theirAnswer);
		} else {
			zend_long theirValue = pt_type_trinary_value(theirAnswer.raw());
			if (UNEXPECTED(theirValue < 0)) return false;
			trinary = pt_type_trinary(3 >> theirValue);
			if (UNEXPECTED(trinary.isUndef())) return false;
		}
		zv::Val other = pt_callable_is_super_type_of_result_of(trinary.raw());
		if (UNEXPECTED(other.isUndef())) return false;
		result = pt_type_result_and(std::move(result), other.raw());
		return !result.isUndef();
	}

	/* new IsSuperTypeOfResult($trinary, $reasons) ($reasons consumed);
	 * UNDEF = pending exception */
	static zv::Val create(zval *trinary, zv::Arr reasons)
	{
		zval out;
		zval reasonsRaw = reasons.take();
		zval lazyReasons;
		ZVAL_EMPTY_ARRAY(&lazyReasons);
		if (UNEXPECTED(!pt_result_object_create(&out, pt_ce_is_super_type_of_result, trinary, &reasonsRaw, &lazyReasons))) return zv::Val();
		return zv::Val::adopt(out);
	}

	/* new IsSuperTypeOfResult(TrinaryLogic::createNo(), [$reason]) ($reason
	 * owned); UNDEF = pending exception */
	static zv::Val resultNo(zend_string *reason)
	{
		zv::Arr reasons = zv::Arr::create(1);
		reasons.push(zv::Val::adoptString(reason));
		return create(pt_trinary_singleton(PT_TRI_NO), std::move(reasons));
	}

	/* new IsSuperTypeOfResult($accepts->result, $accepts->reasons); UNDEF =
	 * pending exception */
	static zv::Val resultFrom(zval *accepts)
	{
		zv::Val trinary, reasons;
		if (UNEXPECTED(!resultParts(accepts, trinary, reasons))) return zv::Val();
		return create(trinary.raw(), zv::Arr::adoptVal(std::move(reasons)));
	}

	/* $result->result and $result->reasons of a result object — the slots
	 * of a native one, the public properties of anything else (the PHP twin
	 * declared next to the native class in the differential tests); owned
	 * copies, false = pending exception */
	[[nodiscard]] static bool resultParts(zval *result, zv::Val &trinary, zv::Val &reasons)
	{
		zend_object *object = Z_OBJ_P(result);
		if (EXPECTED(object->ce == pt_ce_is_super_type_of_result || object->ce == pt_ce_accepts_result)) {
			zval *resultSlot = OBJ_PROP_NUM(object, 0);
			if (UNEXPECTED(Z_TYPE_P(resultSlot) != IS_OBJECT)) {
				zend_throw_error(NULL, "Typed property %s::$result must not be accessed before initialization", ZSTR_VAL(object->ce->name));
				return false;
			}
			zval *reasonsSlot = OBJ_PROP_NUM(object, 1);
			if (UNEXPECTED(Z_TYPE_P(reasonsSlot) != IS_ARRAY)) {
				zend_throw_error(NULL, "Typed property %s::$reasons must not be accessed before initialization", ZSTR_VAL(object->ce->name));
				return false;
			}
			trinary = zv::Val::copyOf(zv::Ref(resultSlot));
			reasons = zv::Val::copyOf(zv::Ref(reasonsSlot));
			return true;
		}
		zval rv;
		zval *value = zend_read_property(object->ce, object, PT_LC("result"), 0, &rv);
		if (UNEXPECTED(value == NULL || EG(exception))) return false;
		trinary = zv::Val::copyOf(zv::Ref(value));
		if (value == &rv) {
			zval_ptr_dtor(&rv);
		}
		value = zend_read_property(object->ce, object, PT_LC("reasons"), 0, &rv);
		if (UNEXPECTED(value == NULL || EG(exception))) return false;
		reasons = zv::Val::copyOf(zv::Ref(value));
		if (value == &rv) {
			zval_ptr_dtor(&rv);
		}
		if (UNEXPECTED(!zv::Ref(trinary.raw()).isObject() || !zv::Ref(reasons.raw()).isArray())) {
			zend_type_error("phpstan_turbo: %s must carry a TrinaryLogic $result and an array $reasons", ZSTR_VAL(object->ce->name));
			return false;
		}
		return true;
	}

	/* `$i + 1` of the entry's key, as sprintf('%d') reads it; false =
	 * pending exception */
	static bool keyPlusOne(const zv::ArrayEntry &entry, zend_long &out)
	{
		zend_string *key = entry.stringKeyOrNull();
		if (EXPECTED(key == NULL)) {
			zend_long index = (zend_long) entry.indexKey();
			if (EXPECTED(index != ZEND_LONG_MAX)) {
				out = index + 1;
				return true;
			}
		}
		zval keyZv, one, sum;
		if (key != NULL) {
			ZVAL_STR(&keyZv, key);
		} else {
			ZVAL_LONG(&keyZv, (zend_long) entry.indexKey());
		}
		ZVAL_LONG(&one, 1);
		if (UNEXPECTED(add_function(&sum, &keyZv, &one) != SUCCESS || EG(exception))) return false;
		out = zval_get_long(&sum);
		zval_ptr_dtor(&sum);
		return true;
	}

	/* $object->method(...$args) requiring an object / array / string / bool
	 * result; UNDEF (false) = pending exception */
	static zv::Val callObject(zval *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
	{
		if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: expected an object, %s given", zend_zval_value_name(object));
			return zv::Val();
		}
		zv::Val result = pt_type_call(Z_OBJ_P(object), lcname, len, argc, argv);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s::%s() must return an object", ZSTR_VAL(Z_OBJCE_P(object)->name), lcname);
			return zv::Val();
		}
		return result;
	}

	static zv::Val callArray(zval *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
	{
		if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: expected an object, %s given", zend_zval_value_name(object));
			return zv::Val();
		}
		zv::Val result = pt_type_call(Z_OBJ_P(object), lcname, len, argc, argv);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isArray())) {
			zend_type_error("phpstan_turbo: %s::%s() must return an array", ZSTR_VAL(Z_OBJCE_P(object)->name), lcname);
			return zv::Val();
		}
		return result;
	}

	static zv::Val callString(zval *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
	{
		zv::Val result = pt_type_call(Z_OBJ_P(object), lcname, len, argc, argv);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isString())) {
			zend_type_error("phpstan_turbo: %s::%s() must return a string", ZSTR_VAL(Z_OBJCE_P(object)->name), lcname);
			return zv::Val();
		}
		return result;
	}

	static bool callBool(zval *object, const char *lcname, size_t len, bool &out)
	{
		if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: expected an object, %s given", zend_zval_value_name(object));
			return false;
		}
		zv::Val result = pt_type_call(Z_OBJ_P(object), lcname, len, 0, NULL);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::CallableTypeHelper;

/* the twin's `CallableParametersAcceptor $ours` / `$theirs` parameter
 * checks; false with a TypeError pending */
static bool pt_cth_check_acceptor(zval *acceptor, uint32_t argNum)
{
	bool isAcceptor;
	if (UNEXPECTED(!pt_type_instanceof(acceptor, PT_CLASS_CALLABLE_PARAMETERS_ACCEPTOR, isAcceptor))) return false;
	if (EXPECTED(isAcceptor)) return true;
	zend_class_entry *iface = pt_class(PT_CLASS_CALLABLE_PARAMETERS_ACCEPTOR);
	zend_argument_type_error(argNum, "must be of type %s, %s given", iface != NULL ? ZSTR_VAL(iface->name) : "PHPStan\\Reflection\\Callables\\CallableParametersAcceptor", zend_zval_value_name(acceptor));
	return false;
}

zv::Val pt_callable_type_helper_is_parameters_acceptor_super_type_of(zval *ours, zval *theirs, bool treatMixedAsAny, bool strictTypes)
{
	if (UNEXPECTED(!pt_cth_check_acceptor(ours, 1) || !pt_cth_check_acceptor(theirs, 2))) return zv::Val();
	return CallableTypeHelper::isParametersAcceptorSuperTypeOf(ours, theirs, treatMixedAsAny, strictTypes);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_CTH_ACCEPTOR_CLASS "PHPStan\\Reflection\\Callables\\CallableParametersAcceptor"

void pt_register_callable_type_helper()
{
	reg::Class cls("PHPStan\\Type\\CallableTypeHelper");
	ptdecl::CallableTypeHelper::declareClass(cls);
	ptdecl::CallableTypeHelper::declareProperties(cls);

	cls.method(sigs::isParametersAcceptorSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *ours, *theirs;
		bool treatMixedAsAny, strictTypes = true;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Bool, zp::Opt<zp::Bool>>(execute_data, ours, theirs, treatMixedAsAny, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(pt_callable_type_helper_is_parameters_acceptor_super_type_of(ours, theirs, treatMixedAsAny, strictTypes));
	});

	cls.shadow(&pt_ce_callable_type_helper);
}

/* }}} */
