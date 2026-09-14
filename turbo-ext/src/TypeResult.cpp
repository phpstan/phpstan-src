/*
 * PHPStanTurbo\TypeResult — native implementation of PHPStan\Type\TypeResult.
 *
 * Declared as PHPStan\Type\TypeResult itself at activation: final, a value
 * pair of a Type and the reasons it came with, both the twin's
 * `public readonly` properties — declared typed readonly slots the
 * constructor initializes once (a second __construct() call fails the way
 * the twin's readonly assignment does).
 *
 * The logic lives in the TypeResult handle class below, mirroring
 * src/Type/TypeResult.php; the registration at the bottom is only the
 * engine ABI glue.
 */

#include "TypeTraits.h"
#include "generated/TypeResult.h"

namespace slots = ptdecl::TypeResult::slot;
namespace sigs = ptdecl::TypeResult::sig;

zend_class_entry *pt_ce_type_result = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\TypeResult. State lives in the PHP object's $type
 * and $reasons. */
class TypeResult
{
public:
	explicit TypeResult(zend_object *self) : self(self) {}

	/* $this->type = $type; $this->reasons = $reasons — the first
	 * initialization of the readonly slots; false with an Error pending on
	 * a repeated call */
	[[nodiscard]] bool construct(zval *type, zval *reasons)
	{
		if (UNEXPECTED(Z_TYPE_P(OBJ_PROP_NUM(self, slots::type)) != IS_UNDEF)) {
			zend_throw_error(NULL, "Cannot modify readonly property %s::$type", ZSTR_VAL(self->ce->name));
			return false;
		}
		initSlot(slots::type, type);
		initSlot(slots::reasons, reasons);
		return true;
	}

	/* new self($type, $reasons); UNDEF = pending exception */
	static zv::Val create(zval *type, zval *reasons)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_type_result) != SUCCESS)) return zv::Val();
		if (UNEXPECTED(!TypeResult(Z_OBJ(object)).construct(type, reasons))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
		return zv::Val::adopt(object);
	}

private:
	zend_object *self;

	/* the owned copy goes in and IS_PROP_UNINIT is cleared, as the engine's
	 * write path does when a constructor initializes a readonly property */
	void initSlot(uint32_t slot, zval *value)
	{
		zval *p = OBJ_PROP_NUM(self, slot);
		ZVAL_COPY(p, value);
		Z_PROP_FLAG_P(p) = 0;
	}
};

} // namespace phpstanturbo

using phpstanturbo::TypeResult;

/* the twin's `Type $type` parameter check; false with a TypeError pending */
static bool pt_tr_check_type(zval *type)
{
	bool isType;
	if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TYPE, isType))) return false;
	if (EXPECTED(isType)) return true;
	zend_argument_type_error(1, "must be of type %s, %s given", ptcls::type, zend_zval_value_name(type));
	return false;
}

zv::Val pt_type_result_new(zval *type, zval *reasons)
{
	if (UNEXPECTED(!pt_tr_check_type(type))) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(reasons) != IS_ARRAY)) {
		zend_argument_type_error(2, "must be of type array, %s given", zend_zval_value_name(reasons));
		return zv::Val();
	}
	return TypeResult::create(type, reasons);
}

/* {{{ engine ABI glue: parameter parsing + registration */

void pt_register_type_result()
{
	reg::Class cls("PHPStan\\Type\\TypeResult");
	ptdecl::TypeResult::declareClass(cls);
	/* the slots in the twin's order: "type" (0), "reasons" (1) */
	ptdecl::TypeResult::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *reasons;
		if (!zp::parse<zp::Obj, zp::Arr>(execute_data, type, reasons)) RETURN_THROWS();
		if (UNEXPECTED(!pt_tr_check_type(type))) RETURN_THROWS();
		if (UNEXPECTED(!TypeResult(Z_OBJ_P(ZEND_THIS)).construct(type, reasons))) RETURN_THROWS();
	});

	cls.shadow(&pt_ce_type_result);
}

/* }}} */
