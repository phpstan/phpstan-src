/*
 * PHPStanTurbo\TemplateTypeMap — native implementation of
 * PHPStan\Type\Generic\TemplateTypeMap.
 *
 * When the extension is active, PHPStan\Type\Generic\TemplateTypeMap is this
 * class, declared under that name at activation (final, like the twin).
 * The empty map lives where the twin keeps it — in the class's private
 * static $empty — so one process shares one instance as the twin does;
 * the two type arrays and the resolveToBounds() memo are the object's own
 * slots. The set operations walk the arrays natively and cross into PHP
 * only for TypeCombinator, TypeUtils::toBenevolentUnion() and the
 * callback of map().
 */

#include "support.h"
#include "generated/TemplateTypeMap.h"

namespace slots = ptdecl::TemplateTypeMap::slot;
namespace sigs = ptdecl::TemplateTypeMap::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_template_type_map = NULL;

/* the twin's `private static ?TemplateTypeMap $empty` slot (borrowed;
 * resolved once per activated class) */
static zend_class_entry *pt_ttm_empty_ce = nullptr;
static zval *pt_ttm_empty_slot = nullptr;

static zval *pt_ttm_empty()
{
	zend_class_entry *ce = pt_ce_template_type_map;
	if (UNEXPECTED(pt_ttm_empty_ce != ce)) {
		ZEND_ASSERT(ce != NULL);
		if (CE_STATIC_MEMBERS(ce) == NULL) {
			zend_class_init_statics(ce);
		}
		zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, PT_LC("empty"));
		ZEND_ASSERT(info != NULL && (info->flags & ZEND_ACC_STATIC) != 0);
		pt_ttm_empty_slot = CE_STATIC_MEMBERS(ce) + info->offset;
		pt_ttm_empty_ce = ce;
	}
	return pt_ttm_empty_slot;
}

/* $array[<the entry's key>] — the dual string/int key lookup, borrowed;
 * NULL when absent */
static zval *pt_ttm_find(HashTable *ht, const zv::ArrayEntry &entry)
{
	return pt_ht_find(ht, entry.stringKeyOrNull(), entry.indexKey());
}

/* isset($array[<the entry's key>]) */
static bool pt_ttm_isset(HashTable *ht, const zv::ArrayEntry &entry)
{
	zval *found = pt_ttm_find(ht, entry);
	if (found == NULL) return false;
	ZVAL_DEREF(found);
	return Z_TYPE_P(found) != IS_NULL;
}

/* $array[<the entry's key>] = $value on an owned, separated array ($value
 * consumed) */
static void pt_ttm_set(zv::Arr &array, const zv::ArrayEntry &entry, zv::Val value)
{
	array.separate();
	zval v = value.take();
	pt_ht_update(array.table(), entry.stringKeyOrNull(), entry.indexKey(), &v);
}

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateTypeMap. State lives in the PHP
 * object's $types, $lowerBoundTypes and $resolvedToBounds. */
class TemplateTypeMap
{
public:
	explicit TemplateTypeMap(zend_object *self) : self(self) {}

	/* $this->types / $this->lowerBoundTypes, borrowed; NULL with an Error
	 * pending when uninitialized */
	[[nodiscard]] zval *types() const { return arraySlot(slots::types, "types"); }
	zval *lowerBoundTypes() const { return arraySlot(slots::lowerBoundTypes, "lowerBoundTypes"); }

	/* __construct($types, $lowerBoundTypes = []) — $lowerBoundTypes NULL
	 * for the default */
	void construct(zval *types, zval *lowerBoundTypes)
	{
		zv::ObjRef ref(self);
		ref.propAtWrite(slots::types, zv::Val::copyOf(zv::Ref(types)));
		if (lowerBoundTypes != NULL) {
			ref.propAtWrite(slots::lowerBoundTypes, zv::Val::copyOf(zv::Ref(lowerBoundTypes)));
		} else {
			ref.propAtWrite(slots::lowerBoundTypes, zv::Val(zv::Arr::empty()));
		}
	}

	/* new self($types, $lowerBoundTypes) ($lowerBoundTypes NULL for []);
	 * UNDEF = pending exception */
	static zv::Val create(zval *types, zval *lowerBoundTypes)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_template_type_map) != SUCCESS)) return zv::Val();
		TemplateTypeMap(Z_OBJ(object)).construct(types, lowerBoundTypes);
		return zv::Val::adopt(object);
	}

	/* new self([], $lowerBoundTypes) with the lower bounds intersected
	 * into the types; UNDEF = pending exception */
	zv::Val convertToLowerBoundTypes() const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zval *lower = this->lowerBoundTypes();
		if (UNEXPECTED(lower == NULL)) return zv::Val();
		zv::Arr lowerBoundTypes = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(types)));
		if (UNEXPECTED(!intersectLowerBoundsInto(lowerBoundTypes, lower))) return zv::Val();
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return create(&emptyArray, lowerBoundTypes.raw());
	}

	/* self::$empty ??= new self([], []) — the singleton, borrowed; NULL =
	 * pending exception */
	static zval *createEmpty()
	{
		if (UNEXPECTED(pt_ce_template_type_map == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: TemplateTypeMap used before the shadowing classes were activated");
			return NULL;
		}
		zval *slot = pt_ttm_empty();
		if (EXPECTED(Z_TYPE_P(slot) == IS_OBJECT)) return slot;
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		zv::Val created = create(&emptyArray, &emptyArray);
		if (UNEXPECTED(created.isUndef())) return NULL;
		zv::Ref(slot).assign(std::move(created));
		return slot;
	}

	/* $this->count() === 0; -1 = pending exception */
	int isEmpty() const
	{
		zend_long count = this->count();
		if (UNEXPECTED(count < 0)) return -1;
		return count == 0 ? 1 : 0;
	}

	/* count($this->types + $this->lowerBoundTypes); -1 = pending exception */
	[[nodiscard]] zend_long count() const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return -1;
		zval *lower = this->lowerBoundTypes();
		if (UNEXPECTED(lower == NULL)) return -1;
		zend_long count = (zend_long) zend_hash_num_elements(Z_ARRVAL_P(types));
		for (zv::ArrayEntry entry : zv::ArrRef(lower)) {
			if (pt_ttm_find(Z_ARRVAL_P(types), entry) == NULL) {
				count++;
			}
		}
		return count;
	}

	/* getTypes(): the types with the lower bounds of the names they lack;
	 * UNDEF = pending exception */
	zv::Val getTypes() const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zval *lower = this->lowerBoundTypes();
		if (UNEXPECTED(lower == NULL)) return zv::Val();
		zv::Arr result = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(types)));
		for (zv::ArrayEntry entry : zv::ArrRef(lower)) {
			if (pt_ttm_find(Z_ARRVAL_P(types), entry) != NULL) continue;
			pt_ttm_set(result, entry, zv::Val::copyOf(entry.value()));
		}
		return zv::Val(std::move(result));
	}

	/* array_key_exists($name, $this->getTypes()); false = pending exception */
	[[nodiscard]] bool hasType(zend_string *name, bool &out) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return false;
		zval *lower = this->lowerBoundTypes();
		if (UNEXPECTED(lower == NULL)) return false;
		out = zend_symtable_exists(Z_ARRVAL_P(types), name) || zend_symtable_exists(Z_ARRVAL_P(lower), name);
		return true;
	}

	/* $this->getTypes()[$name] ?? null; UNDEF = pending exception */
	zv::Val getType(zend_string *name) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zval *found = zend_symtable_find(Z_ARRVAL_P(types), name);
		if (found == NULL) {
			zval *lower = this->lowerBoundTypes();
			if (UNEXPECTED(lower == NULL)) return zv::Val();
			found = zend_symtable_find(Z_ARRVAL_P(lower), name);
			if (found == NULL) return zv::Val::null();
		}
		ZVAL_DEREF(found);
		if (Z_TYPE_P(found) == IS_NULL) return zv::Val::null();
		return zv::Val::copyOf(zv::Ref(found));
	}

	/* unsetType(): $this when the name is absent, the empty map when
	 * nothing remains, a new map otherwise; UNDEF = pending exception */
	zv::Val unsetType(zend_string *name) const
	{
		bool has;
		if (UNEXPECTED(!hasType(name, has))) return zv::Val();
		if (!has) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return zv::Val::copyOf(zv::Ref(&selfZv));
		}
		zv::Arr types = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(this->types())));
		zv::Arr lowerBoundTypes = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(this->lowerBoundTypes())));
		types.separate();
		zend_symtable_del(types.table(), name);
		lowerBoundTypes.separate();
		zend_symtable_del(lowerBoundTypes.table(), name);
		if (zend_hash_num_elements(types.table()) == 0 && zend_hash_num_elements(lowerBoundTypes.table()) == 0) {
			zval *empty = createEmpty();
			if (UNEXPECTED(empty == NULL)) return zv::Val();
			return zv::Val::copyOf(zv::Ref(empty));
		}
		return create(types.raw(), lowerBoundTypes.raw());
	}

	/* union(): the types combined by TypeCombinator::union(), the lower
	 * bounds intersected; UNDEF = pending exception */
	zv::Val union_(zend_object *other) const { return unionWith(other, false); }

	/* benevolentUnion(): the types combined by
	 * TypeUtils::toBenevolentUnion(TypeCombinator::union()), the lower
	 * bounds intersected; UNDEF = pending exception */
	zv::Val benevolentUnion(zend_object *other) const { return unionWith(other, true); }

	/* intersect(): the types intersected, the lower bounds unioned; UNDEF
	 * = pending exception */
	zv::Val intersect(zend_object *other) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zval *lower = this->lowerBoundTypes();
		if (UNEXPECTED(lower == NULL)) return zv::Val();
		TemplateTypeMap otherMap(other);
		zval *otherTypes = otherMap.types();
		if (UNEXPECTED(otherTypes == NULL)) return zv::Val();
		zval *otherLower = otherMap.lowerBoundTypes();
		if (UNEXPECTED(otherLower == NULL)) return zv::Val();

		zv::Arr result = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(types)));
		zv::Val otherTypesCopy = zv::Val::copyOf(zv::Ref(otherTypes));
		for (zv::ArrayEntry entry : zv::ArrRef(otherTypesCopy.raw())) {
			zval *existing = pt_ttm_isset(result.table(), entry) ? pt_ttm_find(result.table(), entry) : NULL;
			if (existing != NULL) {
				zval intersection;
				if (UNEXPECTED(!pt_type_combinator_binary(PT_LC("intersect"), existing, entry.value().raw(), &intersection))) return zv::Val();
				pt_ttm_set(result, entry, zv::Val::adopt(intersection));
			} else {
				pt_ttm_set(result, entry, zv::Val::copyOf(entry.value()));
			}
		}

		zv::Arr resultLowerBoundTypes = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(lower)));
		zv::Val otherLowerCopy = zv::Val::copyOf(zv::Ref(otherLower));
		for (zv::ArrayEntry entry : zv::ArrRef(otherLowerCopy.raw())) {
			zval *existing = pt_ttm_isset(resultLowerBoundTypes.table(), entry) ? pt_ttm_find(resultLowerBoundTypes.table(), entry) : NULL;
			if (existing != NULL) {
				zval unioned;
				if (UNEXPECTED(!pt_type_combinator_binary(PT_LC("union"), existing, entry.value().raw(), &unioned))) return zv::Val();
				pt_ttm_set(resultLowerBoundTypes, entry, zv::Val::adopt(unioned));
			} else {
				pt_ttm_set(resultLowerBoundTypes, entry, zv::Val::copyOf(entry.value()));
			}
		}

		return create(result.raw(), resultLowerBoundTypes.raw());
	}

	/* map(): new self($cb($name, $type) for every name of getTypes());
	 * UNDEF = pending exception */
	zv::Val map(zval *cb) const
	{
		zv::Val types = getTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Arr mapped = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(types.raw())));
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zval args[2];
			if (entry.stringKeyOrNull() != NULL) {
				ZVAL_STR(&args[0], entry.stringKey());
			} else {
				ZVAL_LONG(&args[0], (zend_long) entry.indexKey());
			}
			ZVAL_COPY_VALUE(&args[1], entry.value().raw());
			zv::Val result = pt_type_call_callable(cb, 2, args);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			pt_ttm_set(mapped, entry, std::move(result));
		}
		return create(mapped.raw(), NULL);
	}

	/* resolveToBounds(): memoized in $resolvedToBounds; UNDEF = pending
	 * exception */
	zv::Val resolveToBounds() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::resolvedToBounds);
		if (Z_TYPE_P(memo) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(memo));
		zv::Val types = getTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Arr mapped = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(types.raw())));
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zv::Val resolved = pt_type_template_type_helper_resolve_to_defaults(entry.value().raw());
			if (UNEXPECTED(resolved.isUndef())) return zv::Val();
			pt_ttm_set(mapped, entry, std::move(resolved));
		}
		zv::Val result = create(mapped.raw(), NULL);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zv::ObjRef(self).propAtWrite(slots::resolvedToBounds, zv::Val::copyOf(zv::Ref(result.raw())));
		return result;
	}

private:
	zend_object *self;

	zval *arraySlot(uint32_t slot, const char *name) const
	{
		zval *value = OBJ_PROP_NUM(self, slot);
		if (UNEXPECTED(Z_TYPE_P(value) != IS_ARRAY)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_template_type_map->name), name);
			return NULL;
		}
		return value;
	}

	/* the shared lower-bound loop of convertToLowerBoundTypes(), union()
	 * and benevolentUnion(): every lower bound intersected into $into,
	 * skipped when the intersection is never; false = pending exception */
	[[nodiscard]] static bool intersectLowerBoundsInto(zv::Arr &into, zval *lowerBoundTypes)
	{
		zv::Val lowerCopy = zv::Val::copyOf(zv::Ref(lowerBoundTypes));
		for (zv::ArrayEntry entry : zv::ArrRef(lowerCopy.raw())) {
			zval *existing = pt_ttm_isset(into.table(), entry) ? pt_ttm_find(into.table(), entry) : NULL;
			if (existing != NULL) {
				zval intersection;
				if (UNEXPECTED(!pt_type_combinator_binary(PT_LC("intersect"), existing, entry.value().raw(), &intersection))) return false;
				zv::Val owned = zv::Val::adopt(intersection);
				if (zv::Ref(owned.raw()).instanceOf(pt_ce_never_type)) continue;
				pt_ttm_set(into, entry, std::move(owned));
			} else {
				pt_ttm_set(into, entry, zv::Val::copyOf(entry.value()));
			}
		}
		return true;
	}

	/* self::combine($a, $b, $cb): an absorbed template argument yields to
	 * the other side, else the union (benevolent when asked); UNDEF =
	 * pending exception */
	static zv::Val combine(zval *a, zval *b, bool benevolent)
	{
		bool absorbed;
		if (UNEXPECTED(!pt_type_instanceof_ce(a, pt_ce_absorbed_template_argument_type, absorbed))) return zv::Val();
		if (absorbed) return zv::Val::copyOf(zv::Ref(b));
		if (UNEXPECTED(!pt_type_instanceof_ce(b, pt_ce_absorbed_template_argument_type, absorbed))) return zv::Val();
		if (absorbed) return zv::Val::copyOf(zv::Ref(a));
		zval unioned;
		if (UNEXPECTED(!pt_type_combinator_binary(PT_LC("union"), a, b, &unioned))) return zv::Val();
		zv::Val owned = zv::Val::adopt(unioned);
		if (!benevolent) return owned;
		return pt_type_call_static_ce(pt_ce_type_utils, PT_LC("tobenevolentunion"), 1, owned.raw());
	}

	zv::Val unionWith(zend_object *other, bool benevolent) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zval *lower = this->lowerBoundTypes();
		if (UNEXPECTED(lower == NULL)) return zv::Val();
		TemplateTypeMap otherMap(other);
		zval *otherTypes = otherMap.types();
		if (UNEXPECTED(otherTypes == NULL)) return zv::Val();
		zval *otherLower = otherMap.lowerBoundTypes();
		if (UNEXPECTED(otherLower == NULL)) return zv::Val();

		zv::Arr result = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(types)));
		zv::Val otherTypesCopy = zv::Val::copyOf(zv::Ref(otherTypes));
		for (zv::ArrayEntry entry : zv::ArrRef(otherTypesCopy.raw())) {
			zval *existing = pt_ttm_isset(result.table(), entry) ? pt_ttm_find(result.table(), entry) : NULL;
			if (existing != NULL) {
				zv::Val combined = combine(existing, entry.value().raw(), benevolent);
				if (UNEXPECTED(combined.isUndef())) return zv::Val();
				pt_ttm_set(result, entry, std::move(combined));
			} else {
				pt_ttm_set(result, entry, zv::Val::copyOf(entry.value()));
			}
		}

		zv::Arr resultLowerBoundTypes = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(lower)));
		if (UNEXPECTED(!intersectLowerBoundsInto(resultLowerBoundTypes, otherLower))) return zv::Val();

		return create(result.raw(), resultLowerBoundTypes.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::TemplateTypeMap;

/* {{{ exported helpers */

bool pt_template_type_map_empty(zval *out)
{
	zval *empty = TemplateTypeMap::createEmpty();
	if (UNEXPECTED(empty == NULL)) return false;
	ZVAL_COPY(out, empty);
	return true;
}

bool pt_template_type_map_new(zval *out, zval *types, zval *lowerBoundTypes)
{
	if (UNEXPECTED(Z_TYPE_P(types) != IS_ARRAY || (lowerBoundTypes != NULL && Z_TYPE_P(lowerBoundTypes) != IS_ARRAY))) {
		zend_type_error("%s::__construct(): Argument #%d ($%s) must be of type array, %s given", pt_ce_template_type_map != NULL ? ZSTR_VAL(pt_ce_template_type_map->name) : "PHPStan\\Type\\Generic\\TemplateTypeMap", Z_TYPE_P(types) != IS_ARRAY ? 1 : 2, Z_TYPE_P(types) != IS_ARRAY ? "types" : "lowerBoundTypes", zend_zval_value_name(Z_TYPE_P(types) != IS_ARRAY ? types : lowerBoundTypes));
		return false;
	}
	zv::Val map = TemplateTypeMap::create(types, lowerBoundTypes);
	if (UNEXPECTED(map.isUndef())) return false;
	map.intoReturnValue(out);
	return true;
}

zv::Val pt_type_template_type_map_empty()
{
	return pt_val_of<pt_template_type_map_empty>();
}

zv::Val pt_type_template_type_map_new(zval *types, zval *lowerBoundTypes)
{
	zval out;
	if (UNEXPECTED(!pt_template_type_map_new(&out, types, lowerBoundTypes))) return zv::Val();
	return zv::Val::adopt(out);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_TTM_THIS TemplateTypeMap(Z_OBJ_P(ZEND_THIS))
#define PT_TTM_CLASS "PHPStan\\Type\\Generic\\TemplateTypeMap"

/* the `self $other` binary operations */
#define PT_TTM_BINARY(method) \
	[](INTERNAL_FUNCTION_PARAMETERS) { \
		zval *other; \
		ZEND_PARSE_PARAMETERS_START(1, 1) \
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_template_type_map) \
		ZEND_PARSE_PARAMETERS_END(); \
		PT_RETURN_VAL(PT_TTM_THIS.method(Z_OBJ_P(other))); \
	}

void pt_register_template_type_map()
{
	static const reg::Arg returnsSelf = reg::obj("", PT_TTM_CLASS);

	reg::Class cls("PHPStan\\Type\\Generic\\TemplateTypeMap");
	ptdecl::TemplateTypeMap::declareClass(cls);
	cls.privateStaticTypedClassPropertyDefaultNull("empty", "self");
	/* the instance slots in the twin's declaration order: "resolvedToBounds"
	 * (slot 0), then the promoted "types" (1) and "lowerBoundTypes" (2) */
	cls.privateTypedClassPropertyDefaultNull("resolvedToBounds", "self");
	cls.privateTypedProperty("types", MAY_BE_ARRAY);
	cls.privateTypedProperty("lowerBoundTypes", MAY_BE_ARRAY);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types, *lowerBoundTypes = NULL;
		if (!zp::parse<zp::Arr, zp::Opt<zp::Arr>>(execute_data, types, lowerBoundTypes)) RETURN_THROWS();
		PT_TTM_THIS.construct(types, lowerBoundTypes);
	});

	cls.method(sigs::convertToLowerBoundTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_TTM_THIS.convertToLowerBoundTypes());
	});

	cls.method(sigs::createEmpty, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *empty = TemplateTypeMap::createEmpty();
		if (UNEXPECTED(empty == NULL)) RETURN_THROWS();
		RETURN_COPY(empty);
	});

	cls.method(sigs::isEmpty, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		int result = PT_TTM_THIS.isEmpty();
		if (UNEXPECTED(result < 0)) RETURN_THROWS();
		RETURN_BOOL(result == 1);
	});

	cls.method(sigs::count, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zend_long result = PT_TTM_THIS.count();
		if (UNEXPECTED(result < 0)) RETURN_THROWS();
		RETURN_LONG(result);
	});

	cls.method(sigs::getTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_TTM_THIS.getTypes());
	});

	cls.method(sigs::hasType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *name;
		if (!zp::parse<zp::Str>(execute_data, name)) RETURN_THROWS();
		bool result;
		if (UNEXPECTED(!PT_TTM_THIS.hasType(name, result))) RETURN_THROWS();
		RETURN_BOOL(result);
	});

	cls.method(sigs::getType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *name;
		if (!zp::parse<zp::Str>(execute_data, name)) RETURN_THROWS();
		PT_RETURN_VAL(PT_TTM_THIS.getType(name));
	});

	cls.method(sigs::unsetType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *name;
		if (!zp::parse<zp::Str>(execute_data, name)) RETURN_THROWS();
		PT_RETURN_VAL(PT_TTM_THIS.unsetType(name));
	});

	cls.method("union", reg::Public, 1, { reg::obj("other", PT_TTM_CLASS) }, PT_TTM_BINARY(union_), &returnsSelf);

	cls.method("benevolentUnion", reg::Public, 1, { reg::obj("other", PT_TTM_CLASS) }, PT_TTM_BINARY(benevolentUnion), &returnsSelf);

	cls.method("intersect", reg::Public, 1, { reg::obj("other", PT_TTM_CLASS) }, PT_TTM_BINARY(intersect), &returnsSelf);

	cls.method(sigs::map, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *cb;
		if (!zp::parse<zp::Zval>(execute_data, cb)) RETURN_THROWS();
		if (UNEXPECTED(!zend_is_callable(cb, 0, NULL))) {
			zend_argument_type_error(1, "must be a valid callback, %s given", zend_zval_value_name(cb));
			RETURN_THROWS();
		}
		PT_RETURN_VAL(PT_TTM_THIS.map(cb));
	});

	cls.method(sigs::resolveToBounds, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_TTM_THIS.resolveToBounds());
	});

	cls.shadow(&pt_ce_template_type_map);
}

/* }}} */
