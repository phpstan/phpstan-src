/*
 * PHPStanTurbo\FiniteTypeSet — native implementation of PHPStan\Type\FiniteTypeSet.
 *
 * When the extension is active, PHPStan\Type\FiniteTypeSet is this class,
 * declared under that name at activation (final, like the twin). The three
 * maps live in the twin's promoted private slots; the identity keys are
 * built natively from the member types' getEnumCaseObject() and
 * getConstantScalarValues() answers, and the shadowed compound classes are
 * told apart by their class entries.
 */

#include "support.h"
#include "generated/FiniteTypeSet.h"

namespace slots = ptdecl::FiniteTypeSet::slot;
namespace sigs = ptdecl::FiniteTypeSet::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_finite_type_set = NULL;

/* the twin's private key constants */
#define PT_FTS_NULL_KEY "null"
#define PT_FTS_INTEGER_KEY_PREFIX "i:"
#define PT_FTS_BOOLEAN_KEY_PREFIX "b:"
#define PT_FTS_STRING_KEY_PREFIX "s:"
#define PT_FTS_ENUM_CASE_KEY_PREFIX "enum:"

namespace phpstanturbo {

/* Mirrors PHPStan\Type\FiniteTypeSet. State lives in the PHP object's
 * $members / $membersByKind / $others. */
class FiniteTypeSet
{
public:
	explicit FiniteTypeSet(zend_object *self) : self(self) {}

	/* create(): the set, or null when nothing is keyed; UNDEF = pending
	 * exception */
	static zv::Val create(zval *types)
	{
		zv::Arr members = zv::Arr::empty();
		zv::Arr membersByKind = zv::Arr::empty();
		zv::Arr others = zv::Arr::empty();
		for (zv::ArrayEntry entry : zv::ArrRef(types)) {
			zv::Ref type = entry.value().deref();
			if (UNEXPECTED(!type.isObject())) {
				zend_type_error("phpstan_turbo: FiniteTypeSet::create() expects a list of Type");
				return zv::Val();
			}
			zv::Val keyZv = key(type.raw());
			if (UNEXPECTED(keyZv.isUndef())) return zv::Val();
			if (!zv::Ref(keyZv.raw()).isString() || members.arrRef().exists(zv::Ref(keyZv.raw()).asString())) {
				others.push(type);
				continue;
			}

			members.set(zv::Ref(keyZv.raw()).asString(), zv::Val::copyOf(type));
			zv::Val kindZv = kind(type.raw());
			if (UNEXPECTED(kindZv.isUndef())) return zv::Val();
			/* $membersByKind[$kind] ??= $type */
			zv::Ref existing = membersByKind.arrRef().find(zv::Ref(kindZv.raw()).asString());
			if (existing.raw() == NULL || existing.isNull()) {
				membersByKind.set(zv::Ref(kindZv.raw()).asString(), zv::Val::copyOf(type));
			}
		}

		if (members.arrRef().size() == 0) return zv::Val::null();

		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_finite_type_set) != SUCCESS)) return zv::Val();
		FiniteTypeSet(Z_OBJ(object)).construct(zv::Val(std::move(members)), zv::Val(std::move(membersByKind)), zv::Val(std::move(others)));
		return zv::Val::adopt(object);
	}

	/* __construct($members, $membersByKind, $others) — the arrays consumed */
	void construct(zv::Val members, zv::Val membersByKind, zv::Val others)
	{
		zv::ObjRef ref(self);
		ref.propAtWrite(slots::members, std::move(members));
		ref.propAtWrite(slots::membersByKind, std::move(membersByKind));
		ref.propAtWrite(slots::others, std::move(others));
	}

	/* key(): the identity key of a finite value, a string or null; UNDEF =
	 * pending exception */
	static zv::Val key(zval *type)
	{
		bool excluded;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TEMPLATE_TYPE, excluded))) return zv::Val();
		if (!excluded && UNEXPECTED(!pt_type_instanceof_ce(type, pt_ce_unresolved_template_argument_type, excluded))) return zv::Val();
		if (excluded || instanceof_function(Z_OBJCE_P(type), pt_ce_union_type) || instanceof_function(Z_OBJCE_P(type), pt_ce_intersection_type)) {
			return zv::Val::null();
		}

		zv::Val enumCaseObject = pt_type_call(Z_OBJ_P(type), PT_LC("getenumcaseobject"), 0, NULL);
		if (UNEXPECTED(enumCaseObject.isUndef())) return zv::Val();
		if (zv::Ref(enumCaseObject.raw()).isObject()) {
			/* self::ENUM_CASE_KEY_PREFIX . $enumCaseObject->getClassName() . '::' . $enumCaseObject->getEnumCaseName() */
			zv::Val className = pt_type_call(Z_OBJ_P(enumCaseObject.raw()), PT_LC("getclassname"), 0, NULL);
			if (UNEXPECTED(className.isUndef())) return zv::Val();
			zv::Val enumCaseName = pt_type_call(Z_OBJ_P(enumCaseObject.raw()), PT_LC("getenumcasename"), 0, NULL);
			if (UNEXPECTED(enumCaseName.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(className.raw()).isString() || !zv::Ref(enumCaseName.raw()).isString())) {
				zend_type_error("phpstan_turbo: getClassName() and getEnumCaseName() must return string");
				return zv::Val();
			}
			smart_str str = {NULL, 0};
			smart_str_appendl(&str, PT_LC(PT_FTS_ENUM_CASE_KEY_PREFIX));
			smart_str_append(&str, zv::Ref(className.raw()).asString());
			smart_str_appendl(&str, PT_LC("::"));
			smart_str_append(&str, zv::Ref(enumCaseName.raw()).asString());
			smart_str_0(&str);
			return zv::Val::adoptString(str.s);
		}

		zv::Val scalarValues = pt_type_op(Z_OBJ_P(type), PT_OP_GET_CONSTANT_SCALAR_VALUES, 0, NULL);
		if (UNEXPECTED(scalarValues.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(scalarValues.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getConstantScalarValues() must return array");
			return zv::Val();
		}
		if (zv::ArrRef(scalarValues.raw()).size() == 1) {
			/* $scalarValues[0] — null when the single value sits elsewhere,
			 * as the twin's read of a missing index yields */
			zv::Ref value = zv::ArrRef(scalarValues.raw()).findIndex(0);
			if (value.raw() == NULL || value.deref().isNull()) return zv::Val::string(PT_LC(PT_FTS_NULL_KEY));
			value = value.deref();
			if (value.isLong()) {
				smart_str str = {NULL, 0};
				smart_str_appendl(&str, PT_LC(PT_FTS_INTEGER_KEY_PREFIX));
				smart_str_append_long(&str, value.asLong());
				smart_str_0(&str);
				return zv::Val::adoptString(str.s);
			}
			if (value.isBool()) {
				return value.isTrue() ? zv::Val::string(PT_LC(PT_FTS_BOOLEAN_KEY_PREFIX "1")) : zv::Val::string(PT_LC(PT_FTS_BOOLEAN_KEY_PREFIX "0"));
			}
			if (value.isString()) {
				smart_str str = {NULL, 0};
				smart_str_appendl(&str, PT_LC(PT_FTS_STRING_KEY_PREFIX));
				smart_str_append(&str, value.asString());
				smart_str_0(&str);
				return zv::Val::adoptString(str.s);
			}
		}

		return zv::Val::null();
	}

	/* kind(): the enum for an enum case, the class otherwise — an owned
	 * string; UNDEF = pending exception */
	static zv::Val kind(zval *type)
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_union_type) && !instanceof_function(Z_OBJCE_P(type), pt_ce_intersection_type)) {
			zv::Val enumCaseObject = pt_type_call(Z_OBJ_P(type), PT_LC("getenumcaseobject"), 0, NULL);
			if (UNEXPECTED(enumCaseObject.isUndef())) return zv::Val();
			if (zv::Ref(enumCaseObject.raw()).isObject()) {
				zv::Val className = pt_type_call(Z_OBJ_P(enumCaseObject.raw()), PT_LC("getclassname"), 0, NULL);
				if (UNEXPECTED(className.isUndef())) return zv::Val();
				if (UNEXPECTED(!zv::Ref(className.raw()).isString())) {
					zend_type_error("phpstan_turbo: getClassName() must return string");
					return zv::Val();
				}
				smart_str str = {NULL, 0};
				smart_str_appendl(&str, PT_LC(PT_FTS_ENUM_CASE_KEY_PREFIX));
				smart_str_append(&str, zv::Ref(className.raw()).asString());
				smart_str_0(&str);
				return zv::Val::adoptString(str.s);
			}
		}

		/* get_class($type) */
		return zv::Val::string(Z_OBJCE_P(type)->name);
	}

	/* getRepresentativesOfOtherKinds(): one member per kind other than
	 * $type's own, in the union's order; UNDEF = pending exception */
	zv::Val getRepresentativesOfOtherKinds(zval *type) const
	{
		zv::Val kindZv = kind(type);
		if (UNEXPECTED(kindZv.isUndef())) return zv::Val();
		zval *membersByKind = slot(slots::membersByKind, "membersByKind");
		if (UNEXPECTED(membersByKind == NULL)) return zv::Val();
		zend_string *kindStr = zv::Ref(kindZv.raw()).asString();
		zv::Arr representatives = zv::Arr::empty();
		for (zv::ArrayEntry entry : zv::ArrRef(membersByKind)) {
			/* the kinds are class names and enum prefixes — string keys */
			zend_string *memberKind = entry.stringKeyOrNull();
			if (memberKind != NULL && zend_string_equals(memberKind, kindStr)) continue;
			representatives.push(entry.value());
		}
		return zv::Val(std::move(representatives));
	}

	/* has(): array_key_exists($key, $this->members); -1 = pending exception */
	int has(zend_string *key) const
	{
		zval *members = slot(slots::members, "members");
		if (UNEXPECTED(members == NULL)) return -1;
		return zend_symtable_exists(Z_ARRVAL_P(members), key) ? 1 : 0;
	}

	/* isComplete(): $this->others === []; -1 = pending exception */
	int isComplete() const
	{
		zval *others = slot(slots::others, "others");
		if (UNEXPECTED(others == NULL)) return -1;
		return zend_hash_num_elements(Z_ARRVAL_P(others)) == 0 ? 1 : 0;
	}

	zv::Val getMembers() const
	{
		zval *members = slot(slots::members, "members");
		return members == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(members));
	}

	zv::Val getOthers() const
	{
		zval *others = slot(slots::others, "others");
		return others == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(others));
	}

	/* containedIn(): count(array_diff_key($this->members, $other->members))
	 * decides — none missing is yes, all missing is no; the PT_TRI_* value,
	 * -1 = pending exception */
	[[nodiscard]] zend_long containedIn(const FiniteTypeSet &other) const
	{
		zval *members = slot(slots::members, "members");
		if (UNEXPECTED(members == NULL)) return -1;
		zval *otherMembers = other.slot(slots::members, "members");
		if (UNEXPECTED(otherMembers == NULL)) return -1;
		uint32_t missing = 0;
		for (zv::ArrayEntry entry : zv::ArrRef(members)) {
			if (!pt_ht_exists(Z_ARRVAL_P(otherMembers), entry.stringKeyOrNull(), entry.indexKey())) {
				missing++;
			}
		}

		if (missing == 0) return PT_TRI_YES;
		if (missing == zend_hash_num_elements(Z_ARRVAL_P(members))) return PT_TRI_NO;
		return PT_TRI_MAYBE;
	}

	/* containedInKey(): containedIn() against the one-member set of $key;
	 * the PT_TRI_* value, -1 = pending exception */
	[[nodiscard]] zend_long containedInKey(zend_string *key) const
	{
		int present = has(key);
		if (UNEXPECTED(present < 0)) return -1;
		if (present == 0) return PT_TRI_NO;
		zval *members = slot(slots::members, "members");
		if (UNEXPECTED(members == NULL)) return -1;
		if (zend_hash_num_elements(Z_ARRVAL_P(members)) == 1) return PT_TRI_YES;
		return PT_TRI_MAYBE;
	}

private:
	zend_object *self;

	/* one of the array slots; NULL with an Error pending when uninitialized
	 * (the twin's typed-property read) */
	[[nodiscard]] zval *slot(uint32_t index, const char *name) const
	{
		zval *value = OBJ_PROP_NUM(self, index);
		if (UNEXPECTED(Z_TYPE_P(value) != IS_ARRAY)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_finite_type_set->name), name);
			return NULL;
		}
		return value;
	}
};

} // namespace phpstanturbo

using phpstanturbo::FiniteTypeSet;

/* {{{ exported helpers */

bool pt_finite_type_set_create(zval *out, zval *types)
{
	if (UNEXPECTED(Z_TYPE_P(types) != IS_ARRAY)) {
		zend_type_error("phpstan_turbo: FiniteTypeSet::create() expects an array, %s given", zend_zval_value_name(types));
		return false;
	}
	zv::Val result = FiniteTypeSet::create(types);
	if (UNEXPECTED(result.isUndef())) return false;
	result.intoReturnValue(out);
	return true;
}

bool pt_finite_type_set_key(zval *out, zval *type)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: FiniteTypeSet::key() expects a Type, %s given", zend_zval_value_name(type));
		return false;
	}
	zv::Val result = FiniteTypeSet::key(type);
	if (UNEXPECTED(result.isUndef())) return false;
	result.intoReturnValue(out);
	return true;
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_FTS_THIS FiniteTypeSet(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_finite_type_set)
{

	reg::Class cls("PHPStan\\Type\\FiniteTypeSet");
	ptdecl::FiniteTypeSet::declareClass(cls);
	cls.privateClassConstantString("NULL_KEY", PT_FTS_NULL_KEY);
	cls.privateClassConstantString("INTEGER_KEY_PREFIX", PT_FTS_INTEGER_KEY_PREFIX);
	cls.privateClassConstantString("BOOLEAN_KEY_PREFIX", PT_FTS_BOOLEAN_KEY_PREFIX);
	cls.privateClassConstantString("STRING_KEY_PREFIX", PT_FTS_STRING_KEY_PREFIX);
	cls.privateClassConstantString("ENUM_CASE_KEY_PREFIX", PT_FTS_ENUM_CASE_KEY_PREFIX);
	/* the promoted constructor parameters, in order (OBJ_PROP_NUM slots 0-2) */
	ptdecl::FiniteTypeSet::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *members, *membersByKind, *others;
		if (!zp::parse<zp::Arr, zp::Arr, zp::Arr>(execute_data, members, membersByKind, others)) RETURN_THROWS();
		PT_FTS_THIS.construct(zv::Val::copyOf(zv::Ref(members)), zv::Val::copyOf(zv::Ref(membersByKind)), zv::Val::copyOf(zv::Ref(others)));
	});

	cls.method(sigs::create, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		if (!zp::parse<zp::Arr>(execute_data, types)) RETURN_THROWS();
		PT_RETURN_VAL(FiniteTypeSet::create(types));
	});

	cls.method(sigs::key, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_VAL(FiniteTypeSet::key(type));
	});

	cls.method(sigs::kind, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_VAL(FiniteTypeSet::kind(type));
	});

	cls.method(sigs::getRepresentativesOfOtherKinds, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_VAL(PT_FTS_THIS.getRepresentativesOfOtherKinds(type));
	});

	cls.method(sigs::has, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *key;
		if (!zp::parse<zp::Str>(execute_data, key)) RETURN_THROWS();
		int result = PT_FTS_THIS.has(key);
		if (UNEXPECTED(result < 0)) RETURN_THROWS();
		RETURN_BOOL(result == 1);
	});

	cls.method(sigs::isComplete, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		int result = PT_FTS_THIS.isComplete();
		if (UNEXPECTED(result < 0)) RETURN_THROWS();
		RETURN_BOOL(result == 1);
	});
	cls.op(PT_OP_IS_COMPLETE, PT_OP_LAMBDA { int result = FiniteTypeSet(self).isComplete(); return result < 0 ? zv::Val() : zv::Val::boolean(result == 1); });

	cls.method(sigs::getMembers, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_FTS_THIS.getMembers());
	});

	cls.method(sigs::getOthers, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_FTS_THIS.getOthers());
	});

	cls.method(sigs::containedIn, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_finite_type_set)
		ZEND_PARSE_PARAMETERS_END();
		zend_long result = PT_FTS_THIS.containedIn(FiniteTypeSet(Z_OBJ_P(other)));
		if (UNEXPECTED(result < 0)) RETURN_THROWS();
		PT_RETURN_TRINARY(result);
	});

	cls.method(sigs::containedInKey, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *key;
		if (!zp::parse<zp::Str>(execute_data, key)) RETURN_THROWS();
		zend_long result = PT_FTS_THIS.containedInKey(key);
		if (UNEXPECTED(result < 0)) RETURN_THROWS();
		PT_RETURN_TRINARY(result);
	});

	cls.shadow(&pt_ce_finite_type_set);
}

/* }}} */
