/*
 * PHPStanTurbo\UnresolvableTypeHelper — native implementation of
 * PHPStan\Rules\PhpDoc\UnresolvableTypeHelper.
 *
 * Declared as PHPStan\Rules\PhpDoc\UnresolvableTypeHelper itself at
 * activation (final, like the twin; a DI service without a constructor,
 * so the container instantiates it the same way). The logic lives in the
 * UnresolvableTypeHelper handle class below, mirroring
 * src/Rules/PhpDoc/UnresolvableTypeHelper.php; the registration lambda at
 * the bottom is only the engine ABI glue.
 *
 * getUnresolvableType() walks the type through TypeTraverser::map() with
 * the twin's `static function (Type $type, callable $traverse) use
 * (&$containsUnresolvable, &$reasons)` closure as a native callback
 * holder (pt_type_native_callback(): the two by-reference `use` variables
 * are its state slots), so the whole walk — 370K callback invocations per
 * self-analysis — stays in C++. An ErrorType or an implicit NeverType is
 * unresolvable; its reason is read from the native class's own slot when
 * the object is exactly that class and through getReason() otherwise (a
 * subclass may override it).
 */

#include "support.h"
#include "generated/UnresolvableTypeHelper.h"

namespace sigs = ptdecl::UnresolvableTypeHelper::sig;
#include "zv.h"
#include "TypeTraits.h"

/* the reason slots of the native ErrorType (after MixedType's two) and
 * NeverType (after $isExplicit) — ErrorType.cpp / NeverType.cpp */
#define PT_UTH_ERROR_TYPE_REASON_SLOT 2
#define PT_UTH_NEVER_TYPE_REASON_SLOT 1

zend_class_entry *pt_ce_unresolvable_type_helper = NULL;

namespace phpstanturbo {

/* Mirrors PHPStan\Rules\PhpDoc\UnresolvableTypeHelper (stateless). */
class UnresolvableTypeHelper
{
public:
	/* getUnresolvableType(Type $type): ?UnresolvableTypeResult — null
	 * without an unresolvable type inside, the result over the distinct
	 * reasons otherwise; UNDEF = pending exception */
	static zv::Val getUnresolvableType(zval *type)
	{
		/* $containsUnresolvable = false; $reasons = []; */
		zval containsUnresolvable, reasons;
		ZVAL_FALSE(&containsUnresolvable);
		ZVAL_EMPTY_ARRAY(&reasons);
		zv::Val callback = pt_type_native_callback(visit, &containsUnresolvable, &reasons);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		zv::Val mapped = pt_type_traverser_map_of(type, callback.raw());
		if (UNEXPECTED(mapped.isUndef())) return zv::Val();
		if (!zend_is_true(pt_type_native_callback_state(callback.raw(), 0))) return zv::Val::null();
		/* new UnresolvableTypeResult(array_values(array_unique($reasons))) */
		zv::Val unique = uniqueValues(pt_type_native_callback_state(callback.raw(), 1));
		zval arg;
		ZVAL_COPY_VALUE(&arg, unique.raw());
		return pt_type_new(PT_CLASS_UNRESOLVABLE_TYPE_RESULT, 1, &arg);
	}

private:
	/* $type->getReason() of an ErrorType / NeverType: the native class's
	 * slot when the object is exactly of it and the slot is initialized,
	 * the method otherwise; UNDEF = pending exception */
	static zv::Val reasonOf(zval *type, zend_class_entry *exactCe, uint32_t slot)
	{
		if (EXPECTED(Z_OBJCE_P(type) == exactCe)) {
			zval *reason = OBJ_PROP_NUM(Z_OBJ_P(type), slot);
			if (EXPECTED(Z_TYPE_P(reason) == IS_STRING || Z_TYPE_P(reason) == IS_NULL)) return zv::Val::copyOf(zv::Ref(reason));
		}
		return pt_type_call(Z_OBJ_P(type), PT_LC("getreason"), 0, NULL);
	}

	/* the closure: `static function (Type $type, callable $traverse) use
	 * (&$containsUnresolvable, &$reasons): Type` on the holder's two state
	 * slots */
	static void visit(zval *containsUnresolvable, zval *reasons, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
			zend_argument_count_error("Too few arguments to function %s::{closure}(), %u passed and exactly 2 expected", ZSTR_VAL(pt_ce_unresolvable_type_helper->name), argc);
			return;
		}
		zval *type = &argv[0];
		zv::Val reason = zv::Val::null();
		bool isError;
		pt_type_instanceof_ce(type, pt_ce_error_type, isError);
		if (isError) {
			ZVAL_TRUE(containsUnresolvable);
			reason = reasonOf(type, pt_ce_error_type, PT_UTH_ERROR_TYPE_REASON_SLOT);
			if (UNEXPECTED(reason.isUndef())) return;
		}
		bool isNever;
		pt_type_instanceof_ce(type, pt_ce_never_type, isNever);
		if (isNever) {
			bool isExplicit;
			if (UNEXPECTED(!pt_never_type_is_explicit(Z_OBJ_P(type), isExplicit))) return;
			if (!isExplicit) {
				ZVAL_TRUE(containsUnresolvable);
				reason = reasonOf(type, pt_ce_never_type, PT_UTH_NEVER_TYPE_REASON_SLOT);
				if (UNEXPECTED(reason.isUndef())) return;
			}
		}

		/* if ($reason !== null) $reasons[] = $reason; */
		if (!zv::Ref(reason.raw()).isNull()) {
			if (Z_TYPE_P(reasons) != IS_ARRAY) {
				ZVAL_EMPTY_ARRAY(reasons);
			}
			zv::ArrRef(reasons).push(zv::Ref(reason.raw()));
		}

		/* return $containsUnresolvable ? $type : $traverse($type); */
		if (zend_is_true(containsUnresolvable)) {
			ZVAL_COPY(return_value, type);
			return;
		}
		zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
		if (UNEXPECTED(traversed.isUndef())) return;
		traversed.intoReturnValue(return_value);
	}

	/* array_values(array_unique($reasons)) over the collected strings: the
	 * first occurrence of each value, as a list (array_unique() compares
	 * the values as strings — they are strings here, so byte equality) */
	static zv::Val uniqueValues(zval *reasons)
	{
		zv::Arr result = zv::Arr::empty();
		if (Z_TYPE_P(reasons) != IS_ARRAY) return zv::Val(std::move(result));
		zv::ScratchTable seen(8);
		for (zv::ArrayEntry entry : zv::ArrRef(reasons)) {
			zv::Ref value = entry.value().deref();
			zend_string *key = zval_get_string(value.raw());
			if (zend_hash_add_empty_element(seen.table(), key) != NULL) {
				result.push(value);
			}
			zend_string_release(key);
		}
		return zv::Val(std::move(result));
	}
};

} // namespace phpstanturbo

using phpstanturbo::UnresolvableTypeHelper;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_unresolvable_type_helper)
{
	reg::Class cls("PHPStan\\Rules\\PhpDoc\\UnresolvableTypeHelper");
	ptdecl::UnresolvableTypeHelper::declareClass(cls);
	ptdecl::UnresolvableTypeHelper::declareProperties(cls);

	cls.method(sigs::getUnresolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		zv::Val result = UnresolvableTypeHelper::getUnresolvableType(type);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.shadow(&pt_ce_unresolvable_type_helper);
}

/* }}} */
