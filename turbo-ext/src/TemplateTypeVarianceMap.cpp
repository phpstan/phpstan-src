/*
 * PHPStanTurbo\TemplateTypeVarianceMap — native implementation of
 * PHPStan\Type\Generic\TemplateTypeVarianceMap.
 *
 * When the extension is active, PHPStan\Type\Generic\TemplateTypeVarianceMap
 * is this class, declared under that name at activation (final, like the
 * twin). The empty map lives where the twin keeps it — in the class's
 * private static $empty — so one process shares one instance as the twin
 * does; the variances array is the object's own slot.
 */

#include "support.h"
#include "generated/TemplateTypeVarianceMap.h"

namespace slots = ptdecl::TemplateTypeVarianceMap::slot;
namespace sigs = ptdecl::TemplateTypeVarianceMap::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_template_type_variance_map = NULL;

/* the twin's `private static ?TemplateTypeVarianceMap $empty` slot
 * (borrowed; resolved once per activated class) */
static zend_class_entry *pt_ttvm_empty_ce = nullptr;
static zval *pt_ttvm_empty_slot = nullptr;

static zval *pt_ttvm_empty()
{
	zend_class_entry *ce = pt_ce_template_type_variance_map;
	if (UNEXPECTED(pt_ttvm_empty_ce != ce)) {
		ZEND_ASSERT(ce != NULL);
		if (CE_STATIC_MEMBERS(ce) == NULL) {
			zend_class_init_statics(ce);
		}
		zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, PT_LC("empty"));
		ZEND_ASSERT(info != NULL && (info->flags & ZEND_ACC_STATIC) != 0);
		pt_ttvm_empty_slot = CE_STATIC_MEMBERS(ce) + info->offset;
		pt_ttvm_empty_ce = ce;
	}
	return pt_ttvm_empty_slot;
}

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateTypeVarianceMap. State lives in the
 * PHP object's $variances. */
class TemplateTypeVarianceMap
{
public:
	explicit TemplateTypeVarianceMap(zend_object *self) : self(self) {}

	/* $this->variances, borrowed; NULL with an Error pending when
	 * uninitialized */
	[[nodiscard]] zval *variances() const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::variances);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_ARRAY)) {
			zend_throw_error(NULL, "Typed property %s::$variances must not be accessed before initialization", ZSTR_VAL(pt_ce_template_type_variance_map->name));
			return NULL;
		}
		return slot;
	}

	void construct(zval *variances)
	{
		zv::ObjRef(self).propAtWrite(slots::variances, zv::Val::copyOf(zv::Ref(variances)));
	}

	/* new self($variances); UNDEF = pending exception */
	static zv::Val create(zval *variances)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_template_type_variance_map) != SUCCESS)) return zv::Val();
		TemplateTypeVarianceMap(Z_OBJ(object)).construct(variances);
		return zv::Val::adopt(object);
	}

	/* self::$empty ??= new self([]) — the singleton, borrowed; NULL =
	 * pending exception */
	static zval *createEmpty()
	{
		if (UNEXPECTED(pt_ce_template_type_variance_map == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: TemplateTypeVarianceMap used before the shadowing classes were activated");
			return NULL;
		}
		zval *slot = pt_ttvm_empty();
		if (EXPECTED(Z_TYPE_P(slot) == IS_OBJECT)) return slot;
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		zv::Val created = create(&emptyArray);
		if (UNEXPECTED(created.isUndef())) return NULL;
		zv::Ref(slot).assign(std::move(created));
		return slot;
	}

	/* getVariances(); UNDEF = pending exception */
	zv::Val getVariances() const
	{
		zval *variances = this->variances();
		if (UNEXPECTED(variances == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(variances));
	}

	/* array_key_exists($name, $this->getVariances()); false = pending
	 * exception */
	[[nodiscard]] bool hasVariance(zend_string *name, bool &out) const
	{
		zval *variances = this->variances();
		if (UNEXPECTED(variances == NULL)) return false;
		out = zend_symtable_exists(Z_ARRVAL_P(variances), name);
		return true;
	}

	/* $this->getVariances()[$name] ?? null; UNDEF = pending exception */
	zv::Val getVariance(zend_string *name) const
	{
		zval *variances = this->variances();
		if (UNEXPECTED(variances == NULL)) return zv::Val();
		zval *found = zend_symtable_find(Z_ARRVAL_P(variances), name);
		if (found == NULL) return zv::Val::null();
		ZVAL_DEREF(found);
		if (Z_TYPE_P(found) == IS_NULL) return zv::Val::null();
		return zv::Val::copyOf(zv::Ref(found));
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateTypeVarianceMap;

/* {{{ exported helpers */

bool pt_template_type_variance_map_empty(zval *out)
{
	zval *empty = TemplateTypeVarianceMap::createEmpty();
	if (UNEXPECTED(empty == NULL)) return false;
	ZVAL_COPY(out, empty);
	return true;
}

bool pt_template_type_variance_map_new(zval *out, zval *variances)
{
	if (UNEXPECTED(Z_TYPE_P(variances) != IS_ARRAY)) {
		zend_type_error("%s::__construct(): Argument #1 ($variances) must be of type array, %s given", pt_ce_template_type_variance_map != NULL ? ZSTR_VAL(pt_ce_template_type_variance_map->name) : "PHPStan\\Type\\Generic\\TemplateTypeVarianceMap", zend_zval_value_name(variances));
		return false;
	}
	zv::Val map = TemplateTypeVarianceMap::create(variances);
	if (UNEXPECTED(map.isUndef())) return false;
	map.intoReturnValue(out);
	return true;
}

zv::Val pt_type_template_type_variance_map_empty()
{
	return pt_val_of<pt_template_type_variance_map_empty>();
}

zv::Val pt_type_template_type_variance_map_new(zval *variances)
{
	zval out;
	if (UNEXPECTED(!pt_template_type_variance_map_new(&out, variances))) return zv::Val();
	return zv::Val::adopt(out);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_TTVM_THIS TemplateTypeVarianceMap(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_template_type_variance_map)
{

	reg::Class cls("PHPStan\\Type\\Generic\\TemplateTypeVarianceMap");
	ptdecl::TemplateTypeVarianceMap::declareClass(cls);
	cls.privateStaticTypedClassPropertyDefaultNull("empty", "self");
	/* "variances" must stay the first declared instance property (OBJ_PROP_NUM slot 0) */
	cls.privateTypedProperty("variances", MAY_BE_ARRAY);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *variances;
		if (!zp::parse<zp::Arr>(execute_data, variances)) RETURN_THROWS();
		PT_TTVM_THIS.construct(variances);
	});

	cls.method(sigs::createEmpty, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *empty = TemplateTypeVarianceMap::createEmpty();
		if (UNEXPECTED(empty == NULL)) RETURN_THROWS();
		RETURN_COPY(empty);
	});

	cls.method(sigs::getVariances, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_TTVM_THIS.getVariances());
	});

	cls.method(sigs::hasVariance, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *name;
		if (!zp::parse<zp::Str>(execute_data, name)) RETURN_THROWS();
		bool result;
		if (UNEXPECTED(!PT_TTVM_THIS.hasVariance(name, result))) RETURN_THROWS();
		RETURN_BOOL(result);
	});

	cls.method(sigs::getVariance, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *name;
		if (!zp::parse<zp::Str>(execute_data, name)) RETURN_THROWS();
		PT_RETURN_VAL(PT_TTVM_THIS.getVariance(name));
	});

	cls.shadow(&pt_ce_template_type_variance_map);
}

/* }}} */
