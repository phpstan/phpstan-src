/*
 * PHPStanTurbo\VerbosityLevel — native implementation of PHPStan\Type\VerbosityLevel.
 *
 * When the extension is active, PHPStan\Type\VerbosityLevel is this class,
 * declared under that name at activation (final, like the twin). The four
 * singletons live where the twin keeps them — in the class's private
 * static properties ($registry and the per-level slots) — so one process
 * shares one instance per level exactly as the twin does. The two closures
 * of getRecommendedLevelByType() are native bodies behind
 * PHPStanTurbo\NativeCallback holders (their `use (&$flag)` variables in
 * the holder's state slots), run through the native TypeTraverser.
 */

#include "support.h"
#include "generated/VerbosityLevel.h"

namespace slots = ptdecl::VerbosityLevel::slot;
namespace sigs = ptdecl::VerbosityLevel::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_verbosity_level = NULL;

/* the twin's static properties, by slot (borrowed; resolved once per
 * activated class, as ObjectType.cpp resolves its statics) */
enum pt_vl_static_kind
{
	PT_VL_STATIC_REGISTRY = 0,
	PT_VL_STATIC_TYPE_ONLY,
	PT_VL_STATIC_VALUE,
	PT_VL_STATIC_PRECISE,
	PT_VL_STATIC_CACHE,
	PT_VL_STATIC_COUNT,
};

static const char *const pt_vl_static_names[PT_VL_STATIC_COUNT] = {
	"registry",
	"TYPE_ONLY",
	"VALUE",
	"PRECISE",
	"CACHE",
};

static zend_class_entry *pt_vl_statics_ce = nullptr;
static zval *pt_vl_statics[PT_VL_STATIC_COUNT];

static zval *pt_vl_static(pt_vl_static_kind kind)
{
	zend_class_entry *ce = pt_ce_verbosity_level;
	if (UNEXPECTED(pt_vl_statics_ce != ce)) {
		ZEND_ASSERT(ce != NULL);
		if (CE_STATIC_MEMBERS(ce) == NULL) {
			zend_class_init_statics(ce);
		}
		for (int i = 0; i < PT_VL_STATIC_COUNT; i++) {
			zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, pt_vl_static_names[i], strlen(pt_vl_static_names[i]));
			ZEND_ASSERT(info != NULL && (info->flags & ZEND_ACC_STATIC) != 0);
			pt_vl_statics[i] = CE_STATIC_MEMBERS(ce) + info->offset;
		}
		pt_vl_statics_ce = ce;
	}
	return pt_vl_statics[kind];
}

namespace phpstanturbo {

/* Mirrors PHPStan\Type\VerbosityLevel. State lives in the PHP object's $value. */
class VerbosityLevel
{
public:
	static constexpr zend_long TYPE_ONLY = PT_VERBOSITY_LEVEL_TYPE_ONLY;
	static constexpr zend_long VALUE = PT_VERBOSITY_LEVEL_VALUE;
	static constexpr zend_long PRECISE = PT_VERBOSITY_LEVEL_PRECISE;
	static constexpr zend_long CACHE = PT_VERBOSITY_LEVEL_CACHE;

	explicit VerbosityLevel(zend_object *self) : self(self) {}

	/* $this->value; -1 with an Error pending when uninitialized */
	[[nodiscard]] zend_long value() const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::value);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_LONG)) {
			zend_throw_error(NULL, "Typed property %s::$value must not be accessed before initialization", ZSTR_VAL(pt_ce_verbosity_level->name));
			return -1;
		}
		return Z_LVAL_P(slot);
	}

	/* new self($value); UNDEF = pending exception */
	static zv::Val create(zend_long value)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_verbosity_level) != SUCCESS)) return zv::Val();
		ZVAL_LONG(OBJ_PROP_NUM(Z_OBJ(object), slots::value), value);
		return zv::Val::adopt(object);
	}

	void construct(zend_long value) { ZVAL_LONG(OBJ_PROP_NUM(self, slots::value), value); }

	zend_long getLevelValue() const { return value(); }

	/* self::$<LEVEL> ??= (self::$registry[$value] ??= new self($value)) —
	 * the singleton, borrowed; NULL = pending exception */
	[[nodiscard]] static zval *singleton(pt_vl_static_kind kind, zend_long value)
	{
		if (UNEXPECTED(pt_ce_verbosity_level == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: VerbosityLevel used before the shadowing classes were activated");
			return NULL;
		}
		zval *slot = pt_vl_static(kind);
		if (EXPECTED(Z_TYPE_P(slot) == IS_OBJECT)) return slot;
		zval *registry = pt_vl_static(PT_VL_STATIC_REGISTRY);
		if (Z_TYPE_P(registry) != IS_ARRAY) {
			/* the uninitialized typed static array a dim write initializes */
			array_init(registry);
		}
		SEPARATE_ARRAY(registry);
		zval *entry = zend_hash_index_find(Z_ARRVAL_P(registry), (zend_ulong) value);
		if (entry == NULL || Z_TYPE_P(entry) == IS_NULL) {
			zv::Val created = create(value);
			if (UNEXPECTED(created.isUndef())) return NULL;
			zval v = created.take();
			entry = zend_hash_index_update(Z_ARRVAL_P(registry), (zend_ulong) value, &v);
		}
		zv::Ref(slot).assign(zv::Val::copyOf(zv::Ref(entry)));
		return slot;
	}

	static zval *typeOnly() { return singleton(PT_VL_STATIC_TYPE_ONLY, TYPE_ONLY); }
	static zval *value_() { return singleton(PT_VL_STATIC_VALUE, VALUE); }
	static zval *precise() { return singleton(PT_VL_STATIC_PRECISE, PRECISE); }
	static zval *cache() { return singleton(PT_VL_STATIC_CACHE, CACHE); }

	/* the singleton for a PT_VERBOSITY_LEVEL_* value; NULL = pending
	 * exception */
	static zval *ofValue(zend_long value)
	{
		switch (value) {
			case TYPE_ONLY:
				return typeOnly();
			case VALUE:
				return value_();
			case PRECISE:
				return precise();
			case CACHE:
				return cache();
			default:
				zend_throw_error(NULL, "phpstan_turbo: no VerbosityLevel with value " ZEND_LONG_FMT, value);
				return NULL;
		}
	}

	bool isTypeOnly() const { return value() == TYPE_ONLY; }
	bool isValue() const { return value() == VALUE; }
	bool isPrecise() const { return value() == PRECISE; }
	bool isCache() const { return value() == CACHE; }

	static zv::Val getRecommendedLevelByType(zval *acceptingType, zval *acceptedType);

	/* which callback the level selects (the twin's if-chain): 0 = type-only,
	 * 1 = value, 2 = precise, 3 = cache; a missing precise/cache callback
	 * falls back down the chain; -1 with an Error pending when uninitialized */
	int handleSelect(bool hasPrecise, bool hasCache) const
	{
		zend_long v = value();
		if (UNEXPECTED(v < 0)) return -1;
		if (v == TYPE_ONLY) return 0;
		if (v == VALUE) return 1;
		if (v == PRECISE) return hasPrecise ? 2 : 1;
		if (hasCache) return 3;
		return hasPrecise ? 2 : 1;
	}

private:
	zend_object *self;
};

/* the `static function (Type $type, callable $traverse) use (&$moreVerbose,
 * &$veryVerbose)` closure of getRecommendedLevelByType(): state0 =
 * $moreVerbose, state1 = $veryVerbose */
static void moreVerboseCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	if (UNEXPECTED(argc != 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
		zend_throw_error(NULL, "phpstan_turbo: the VerbosityLevel traversal callback expects (Type, callable)");
		return;
	}
	zval *type = &argv[0];
	zval *traverse = &argv[1];
	zend_object *object = Z_OBJ_P(type);

	/* stop deep traversal to not waste resources */
	if (Z_TYPE_P(state1) == IS_TRUE) {
		RETURN_COPY(type);
	}

	zend_long isCallable = pt_type_op_trinary(object, PT_OP_IS_CALLABLE, 0, NULL);
	if (UNEXPECTED(isCallable < 0)) return;
	if (isCallable == PT_TRI_YES) {
		ZVAL_TRUE(state0);
		if (instanceof_function(object->ce, pt_ce_closure_type)) {
			zend_long isStatic = pt_type_call_trinary(object, PT_LC("isstaticclosure"), 0, NULL);
			if (UNEXPECTED(isStatic < 0)) return;
			if (isStatic != PT_TRI_MAYBE) {
				ZVAL_TRUE(state1);
				RETURN_COPY(type);
			}
		}
		/* keep checking if we need to be very verbose */
		(void) pt_type_traverser_traverse(return_value, traverse, type);
		return;
	}

	zend_long isConstantArray = pt_type_op_trinary(object, PT_OP_IS_CONSTANT_ARRAY, 0, NULL);
	if (UNEXPECTED(isConstantArray < 0)) return;
	if (isConstantArray == PT_TRI_YES) {
		ZVAL_TRUE(state0);
		/* for ConstantArrayType we need to keep checking if we need to be very verbose */
		(void) pt_type_traverser_traverse(return_value, traverse, type);
		return;
	}

	zend_long isConstantValue = pt_type_call_trinary(object, PT_LC("isconstantvalue"), 0, NULL);
	if (UNEXPECTED(isConstantValue < 0)) return;
	if (isConstantValue == PT_TRI_YES) {
		zend_long isNull = pt_type_op_trinary(object, PT_OP_IS_NULL, 0, NULL);
		if (UNEXPECTED(isNull < 0)) return;
		if (isNull == PT_TRI_NO) {
			ZVAL_TRUE(state0);
			zend_long isArray = pt_type_op_trinary(object, PT_OP_IS_ARRAY, 0, NULL);
			if (UNEXPECTED(isArray < 0)) return;
			if (isArray != PT_TRI_NO) {
				(void) pt_type_traverser_traverse(return_value, traverse, type);
				return;
			}
			RETURN_COPY(type);
		}
	}

	/* synced with IntersectionType::describe() */
	if (instanceof_function(object->ce, pt_ce_accessory_non_empty_string_type)
		|| instanceof_function(object->ce, pt_ce_accessory_non_falsy_string_type)
		|| instanceof_function(object->ce, pt_ce_accessory_literal_string_type)
		|| instanceof_function(object->ce, pt_ce_accessory_numeric_string_type)
		|| instanceof_function(object->ce, pt_ce_accessory_decimal_integer_string_type)
		|| instanceof_function(object->ce, pt_ce_non_empty_array_type)
		|| instanceof_function(object->ce, pt_ce_accessory_array_list_type)) {
		ZVAL_TRUE(state0);
		RETURN_COPY(type);
	}
	if (instanceof_function(object->ce, pt_ce_accessory_lowercase_string_type)
		|| instanceof_function(object->ce, pt_ce_accessory_uppercase_string_type)) {
		ZVAL_TRUE(state0);
		ZVAL_TRUE(state1);
		RETURN_COPY(type);
	}
	if (instanceof_function(object->ce, pt_ce_integer_range_type)) {
		ZVAL_TRUE(state0);
		RETURN_COPY(type);
	}
	(void) pt_type_traverser_traverse(return_value, traverse, type);
}

/* the `static function (Type $type, callable $traverse) use
 * (&$containsInvariantTemplateType)` closure: state0 = the flag */
static void invariantTemplateCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state1;
	if (UNEXPECTED(argc != 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
		zend_throw_error(NULL, "phpstan_turbo: the VerbosityLevel traversal callback expects (Type, callable)");
		return;
	}
	zval *type = &argv[0];
	zval *traverse = &argv[1];
	zend_object *object = Z_OBJ_P(type);

	/* stop deep traversal to not waste resources */
	if (Z_TYPE_P(state0) == IS_TRUE) {
		RETURN_COPY(type);
	}

	if (instanceof_function(object->ce, pt_ce_generic_object_type) || instanceof_function(object->ce, pt_ce_generic_static_type)) {
		zv::Val reflection = pt_type_call(object, PT_LC("getclassreflection"), 0, NULL);
		if (UNEXPECTED(reflection.isUndef())) return;
		if (zv::Ref(reflection.raw()).isObject()) {
			zv::Val templateTypeMap = pt_type_call(Z_OBJ_P(reflection.raw()), PT_LC("gettemplatetypemap"), 0, NULL);
			if (UNEXPECTED(templateTypeMap.isUndef())) return;
			if (UNEXPECTED(!zv::Ref(templateTypeMap.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getTemplateTypeMap() must return an object");
				return;
			}
			zv::Val types = pt_type_call(Z_OBJ_P(templateTypeMap.raw()), PT_LC("gettypes"), 0, NULL);
			if (UNEXPECTED(types.isUndef())) return;
			if (UNEXPECTED(!zv::Ref(types.raw()).isArray())) {
				zend_type_error("phpstan_turbo: getTypes() must return array");
				return;
			}
			for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
				zv::Ref templateType = entry.value().deref();
				bool isTemplate;
				if (UNEXPECTED(!pt_type_instanceof(templateType.raw(), PT_CLASS_TEMPLATE_TYPE, isTemplate))) return;
				if (!isTemplate) continue;
				zv::Val variance = pt_type_call(templateType.asObject(), PT_LC("getvariance"), 0, NULL);
				if (UNEXPECTED(variance.isUndef())) return;
				if (UNEXPECTED(!zv::Ref(variance.raw()).isObject())) {
					zend_type_error("phpstan_turbo: getVariance() must return an object");
					return;
				}
				zv::Val invariant = pt_type_call(Z_OBJ_P(variance.raw()), PT_LC("invariant"), 0, NULL);
				if (UNEXPECTED(invariant.isUndef())) return;
				if (!zend_is_true(invariant.raw())) continue;
				ZVAL_TRUE(state0);
				RETURN_COPY(type);
			}
		}
	}

	(void) pt_type_traverser_traverse(return_value, traverse, type);
}

/* the TypeTraverser::map() of one callback holder over $type, its result
 * discarded (the closures only set their flags); false = pending exception */
[[nodiscard]] static bool traverseFor(zval *type, zval *callback)
{
	zval mapped;
	if (UNEXPECTED(!pt_type_traverser_map(&mapped, type, callback))) return false;
	zval_ptr_dtor(&mapped);
	return true;
}

static bool flagIsTrue(zval *callback, int index)
{
	return Z_TYPE_P(pt_type_native_callback_state(callback, index)) == IS_TRUE;
}

zv::Val VerbosityLevel::getRecommendedLevelByType(zval *acceptingType, zval *acceptedType)
{
	zval falseZv;
	ZVAL_FALSE(&falseZv);
	zv::Val moreVerboseCallback = pt_type_native_callback(phpstanturbo::moreVerboseCallback, &falseZv, &falseZv);
	if (UNEXPECTED(moreVerboseCallback.isUndef())) return zv::Val();

	if (UNEXPECTED(!traverseFor(acceptingType, moreVerboseCallback.raw()))) return zv::Val();
	if (flagIsTrue(moreVerboseCallback.raw(), 1)) {
		zval *level = precise();
		return level == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(level));
	}

	/* $verbosity = self::value() when $moreVerbose, unset otherwise */
	bool hasVerbosity = flagIsTrue(moreVerboseCallback.raw(), 0);

	if (acceptedType == NULL || Z_TYPE_P(acceptedType) == IS_NULL) {
		zval *level = hasVerbosity ? value_() : typeOnly();
		return level == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(level));
	}

	zv::Val invariantCallback = pt_type_native_callback(phpstanturbo::invariantTemplateCallback, &falseZv, NULL);
	if (UNEXPECTED(invariantCallback.isUndef())) return zv::Val();
	if (UNEXPECTED(!traverseFor(acceptingType, invariantCallback.raw()))) return zv::Val();
	if (!flagIsTrue(invariantCallback.raw(), 0)) {
		zval *level = hasVerbosity ? value_() : typeOnly();
		return level == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(level));
	}

	/* $moreVerbose = false; $veryVerbose = false; */
	ZVAL_FALSE(pt_type_native_callback_state(moreVerboseCallback.raw(), 0));
	ZVAL_FALSE(pt_type_native_callback_state(moreVerboseCallback.raw(), 1));
	if (UNEXPECTED(!traverseFor(acceptedType, moreVerboseCallback.raw()))) return zv::Val();
	if (flagIsTrue(moreVerboseCallback.raw(), 1)) {
		zval *level = precise();
		return level == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(level));
	}

	zval *level = flagIsTrue(moreVerboseCallback.raw(), 0) ? value_() : (hasVerbosity ? value_() : typeOnly());
	return level == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(level));
}

} // namespace phpstanturbo

using phpstanturbo::VerbosityLevel;

/* {{{ exported helpers */

zval *pt_verbosity_level_singleton(zend_long value)
{
	return VerbosityLevel::ofValue(value);
}

bool pt_verbosity_level_value_of(zval *level, zend_long &out)
{
	if (UNEXPECTED(Z_TYPE_P(level) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: expected %s, %s given", pt_ce_verbosity_level != NULL ? ZSTR_VAL(pt_ce_verbosity_level->name) : "PHPStan\\Type\\VerbosityLevel", zend_zval_value_name(level));
		return false;
	}
	if (EXPECTED(Z_OBJCE_P(level) == pt_ce_verbosity_level)) {
		zend_long value = VerbosityLevel(Z_OBJ_P(level)).value();
		if (UNEXPECTED(value < 0)) return false;
		out = value;
		return true;
	}
	/* the PHP twin declared next to the native class in the differential
	 * tests: its getLevelValue() */
	zv::Val value = pt_type_call(Z_OBJ_P(level), PT_LC("getlevelvalue"), 0, NULL);
	if (UNEXPECTED(value.isUndef())) return false;
	if (UNEXPECTED(!zv::Ref(value.raw()).isLong())) {
		zend_type_error("phpstan_turbo: %s::getLevelValue() must return int", ZSTR_VAL(Z_OBJCE_P(level)->name));
		return false;
	}
	out = zv::Ref(value.raw()).asLong();
	return true;
}

bool pt_verbosity_level_recommended(zval *out, zval *acceptingType, zval *acceptedType)
{
	zv::Val result = VerbosityLevel::getRecommendedLevelByType(acceptingType, acceptedType);
	if (UNEXPECTED(result.isUndef())) return false;
	result.intoReturnValue(out);
	return true;
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_VL_THIS VerbosityLevel(Z_OBJ_P(ZEND_THIS))

/* a `?callable $x = null` parameter */

/* the singleton factories: the borrowed singleton copied into the return
 * value */
static void pt_vl_return_singleton(zval *return_value, zval *level)
{
	if (UNEXPECTED(level == NULL)) RETURN_THROWS();
	RETURN_COPY(level);
}

/* the is*() queries: false with an Error pending when uninitialized */
static void pt_vl_return_is(zval *return_value, zval *thisZv, zend_long level)
{
	zend_long value = VerbosityLevel(Z_OBJ_P(thisZv)).value();
	if (UNEXPECTED(value < 0)) RETURN_THROWS();
	RETURN_BOOL(value == level);
}

PT_MINIT_REGISTRATION(pt_register_verbosity_level)
{

	reg::Class cls("PHPStan\\Type\\VerbosityLevel");
	ptdecl::VerbosityLevel::declareClass(cls);
	cls.privateClassConstantLong("TYPE_ONLY", VerbosityLevel::TYPE_ONLY);
	cls.privateClassConstantLong("VALUE", VerbosityLevel::VALUE);
	cls.privateClassConstantLong("PRECISE", VerbosityLevel::PRECISE);
	cls.privateClassConstantLong("CACHE", VerbosityLevel::CACHE);
	cls.privateStaticTypedArrayProperty("registry");
	cls.privateStaticTypedClassProperty("TYPE_ONLY", "self");
	cls.privateStaticTypedClassProperty("VALUE", "self");
	cls.privateStaticTypedClassProperty("PRECISE", "self");
	cls.privateStaticTypedClassProperty("CACHE", "self");
	/* "value" must stay the first declared instance property (OBJ_PROP_NUM slot 0) */
	cls.privateTypedProperty("value", MAY_BE_LONG);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long value;
		if (!zp::parse<zp::Long>(execute_data, value)) RETURN_THROWS();
		PT_VL_THIS.construct(value);
	});

	cls.method(sigs::getLevelValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zend_long value = PT_VL_THIS.getLevelValue();
		if (UNEXPECTED(value < 0)) RETURN_THROWS();
		RETURN_LONG(value);
	});

	cls.method(sigs::typeOnly, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_vl_return_singleton(return_value, VerbosityLevel::typeOnly());
	});

	cls.method(sigs::value, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_vl_return_singleton(return_value, VerbosityLevel::value_());
	});

	cls.method(sigs::precise, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_vl_return_singleton(return_value, VerbosityLevel::precise());
	});

	cls.method(sigs::cache, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_vl_return_singleton(return_value, VerbosityLevel::cache());
	});

	cls.method(sigs::isTypeOnly, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_vl_return_is(return_value, ZEND_THIS, VerbosityLevel::TYPE_ONLY);
	});
	cls.op(PT_OP_IS_TYPE_ONLY, PT_OP_LAMBDA { zend_long value = VerbosityLevel(self).value(); return value < 0 ? zv::Val() : zv::Val::boolean(value == VerbosityLevel::TYPE_ONLY); });

	cls.method(sigs::isValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_vl_return_is(return_value, ZEND_THIS, VerbosityLevel::VALUE);
	});

	cls.method(sigs::isPrecise, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_vl_return_is(return_value, ZEND_THIS, VerbosityLevel::PRECISE);
	});

	cls.method(sigs::isCache, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_vl_return_is(return_value, ZEND_THIS, VerbosityLevel::CACHE);
	});

	cls.method(sigs::getRecommendedLevelByType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType, *acceptedType = NULL;
		if (!zp::parse<zp::Obj, zp::Opt<zp::ObjOrNull>>(execute_data, acceptingType, acceptedType)) RETURN_THROWS();
		PT_RETURN_VAL(VerbosityLevel::getRecommendedLevelByType(acceptingType, acceptedType));
	});

	cls.method(sigs::handle, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci[4];
		zend_fcall_info_cache fcc[4];
		fci[2].size = 0;
		fci[3].size = 0;
		ZEND_PARSE_PARAMETERS_START(2, 4)
			Z_PARAM_FUNC(fci[0], fcc[0])
			Z_PARAM_FUNC(fci[1], fcc[1])
			Z_PARAM_OPTIONAL
			Z_PARAM_FUNC_OR_NULL(fci[2], fcc[2])
			Z_PARAM_FUNC_OR_NULL(fci[3], fcc[3])
		ZEND_PARSE_PARAMETERS_END();
		int selected = PT_VL_THIS.handleSelect(ZEND_FCI_INITIALIZED(fci[2]), ZEND_FCI_INITIALIZED(fci[3]));
		if (UNEXPECTED(selected < 0)) RETURN_THROWS();
		zval result;
		if (UNEXPECTED(!pt_call_fci(&fci[selected], &fcc[selected], 0, NULL, &result))) RETURN_THROWS();
		/* the twin's `: string` return type */
		if (UNEXPECTED(Z_TYPE(result) != IS_STRING)) {
			zend_type_error("%s::handle(): Return value must be of type string, %s returned", ZSTR_VAL(pt_ce_verbosity_level->name), zend_zval_value_name(&result));
			zval_ptr_dtor(&result);
			RETURN_THROWS();
		}
		RETURN_STR(Z_STR(result));
	});

	cls.shadow(&pt_ce_verbosity_level);
}

/* }}} */
