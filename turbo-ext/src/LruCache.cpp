/*
 * PHPStanTurbo\LruCache — native implementation of PHPStan\Internal\LruCache.
 *
 * State lives in the six declared property slots — the twin's, in its order
 * — so the standard object handlers do GC/free/clone; no custom object
 * struct.
 *
 * $values is the LRU order: a PHP array keeps insertion order, so the least
 * recently used entry is the first one and touching an entry means deleting
 * and re-inserting it. The code below issues the same zend_hash operations
 * the twin's unset() and offset assignments compile to, so the table — and
 * the order all() hands out — is identical. Keys go through the symtable
 * API like the twin's string offsets do: a numeric-string key becomes an
 * integer key, and comes back as one from set()'s evicted list and all().
 */

#include "support.h"
#include "generated/LruCache.h"

namespace slots = ptdecl::LruCache::slot;
namespace sigs = ptdecl::LruCache::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_lru_cache = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Internal\LruCache. State lives in the PHP object's
 * $values/$weights/$weight and the promoted $maxCount/$maxWeight/
 * $weightEvictionFloorCount. */
class LruCache
{
public:
	explicit LruCache(zend_object *self) : self(self) {}

	/* the promoted constructor parameters */
	void construct(zend_long maxCount, zend_long maxWeight, zend_long weightEvictionFloorCount)
	{
		ZVAL_LONG(OBJ_PROP_NUM(self, slots::maxCount), maxCount);
		ZVAL_LONG(OBJ_PROP_NUM(self, slots::maxWeight), maxWeight);
		ZVAL_LONG(OBJ_PROP_NUM(self, slots::weightEvictionFloorCount), weightEvictionFloorCount);
	}

	/* The entry, touched so it becomes the most recently used one, or null
	 * when there is none. */
	zv::Val get(zend_string *key) const
	{
		zval *values = this->values();
		zval *found = zend_symtable_find(Z_ARRVAL_P(values), key);
		if (found == NULL) return zv::Val::null();

		zv::Val value = zv::Val::copyOf(zv::Ref(found));
		/* unset($this->values[$key]); $this->values[$key] = $value; */
		SEPARATE_ARRAY(values);
		zend_symtable_del(Z_ARRVAL_P(values), key);
		Z_TRY_ADDREF_P(value.raw());
		zend_symtable_update(Z_ARRVAL_P(values), key, value.raw());

		return value;
	}

	/* Stores an entry, evicting least recently used ones until it fits; the
	 * keys evicted to make room. UNDEF = pending exception */
	zv::Val set(zend_string *key, zval *value, zend_long weight) const
	{
		zval *values = this->values();
		zval *weights = this->weights();
		if (zend_symtable_find(Z_ARRVAL_P(values), key) != NULL) {
			/* $this->weight -= $this->weights[$key]; */
			if (UNEXPECTED(!subtractWeight(weightOf(key)))) return zv::Val();
			SEPARATE_ARRAY(values);
			zend_symtable_del(Z_ARRVAL_P(values), key);
			SEPARATE_ARRAY(weights);
			zend_symtable_del(Z_ARRVAL_P(weights), key);
		}

		zv::Val evicted = evict(weight);
		if (UNEXPECTED(evicted.isUndef())) return zv::Val();

		SEPARATE_ARRAY(values);
		Z_TRY_ADDREF_P(value);
		zend_symtable_update(Z_ARRVAL_P(values), key, value);
		zval weightValue;
		ZVAL_LONG(&weightValue, weight);
		SEPARATE_ARRAY(weights);
		zend_symtable_update(Z_ARRVAL_P(weights), key, &weightValue);
		if (UNEXPECTED(!addWeight(weight))) return zv::Val();

		return evicted;
	}

	/* Replaces the value of an existing entry and touches it, leaving its
	 * weight as it was; false = pending exception (ShouldNotHappenException
	 * for a key that is not in the cache) */
	[[nodiscard]] bool replace(zend_string *key, zval *value) const
	{
		zval *values = this->values();
		if (zend_symtable_find(Z_ARRVAL_P(values), key) == NULL) {
			throwNotInCache(key);
			return false;
		}

		/* unset($this->values[$key]); $this->values[$key] = $value; */
		SEPARATE_ARRAY(values);
		zend_symtable_del(Z_ARRVAL_P(values), key);
		Z_TRY_ADDREF_P(value);
		zend_symtable_update(Z_ARRVAL_P(values), key, value);
		return true;
	}

	zend_long count() const { return zend_hash_num_elements(Z_ARRVAL_P(values())); }

	/* in LRU order, oldest first */
	zv::Val all() const { return zv::Val::copyOf(zv::Ref(values())); }

private:
	zend_object *self;

	/* the always-initialized slots ($values = [], $weights = [], $weight = 0) */
	zval *values() const { return OBJ_PROP_NUM(self, slots::values); }
	zval *weights() const { return OBJ_PROP_NUM(self, slots::weights); }
	zval *weight() const { return OBJ_PROP_NUM(self, slots::weight); }

	/* a promoted int slot; false = the engine's Error for a read before the
	 * constructor initialized it */
	bool readLong(uint32_t slot, const char *propertyName, zend_long &out) const
	{
		zval *value = OBJ_PROP_NUM(self, slot);
		if (EXPECTED(Z_TYPE_P(value) == IS_LONG)) {
			out = Z_LVAL_P(value);
			return true;
		}
		if (Z_TYPE_P(value) == IS_UNDEF) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(self->ce->name), propertyName);
			return false;
		}
		out = zval_get_long(value);
		return true;
	}

	/* $this->weights[$key] — kept in step with $values by every writer,
	 * so the key is there (a missing one would be PHP's null, i.e. 0) */
	zend_long weightOf(zend_string *key) const
	{
		zval *weight = zend_symtable_find(Z_ARRVAL_P(weights()), key);
		return weight != NULL ? zval_get_long(weight) : 0;
	}

	/* $this->weight += $delta / -= $delta: PHP's int arithmetic, whose
	 * overflow is a float the int-typed property refuses with a TypeError;
	 * false = pending exception */
	[[nodiscard]] bool addWeight(zend_long delta) const { return storeWeight(fast_long_add_function, delta); }
	bool subtractWeight(zend_long delta) const { return storeWeight(fast_long_sub_function, delta); }

	template <typename Op>
	bool storeWeight(Op op, zend_long delta) const
	{
		zval deltaValue, result;
		ZVAL_LONG(&deltaValue, delta);
		op(&result, weight(), &deltaValue);
		if (UNEXPECTED(Z_TYPE(result) != IS_LONG)) {
			zend_type_error("Cannot assign float to property %s::$weight of type int", ZSTR_VAL(self->ce->name));
			return false;
		}
		ZVAL_LONG(weight(), Z_LVAL(result));
		return true;
	}

	/* the keys evicted (a list, [] when none) — UNDEF = pending exception */
	zv::Val evict(zend_long incomingWeight) const
	{
		zend_long maxCount;
		if (UNEXPECTED(!readLong(slots::maxCount, "maxCount", maxCount))) return zv::Val();
		zv::Val evicted(zv::Arr::empty());
		zval *values = this->values();
		zval *weights = this->weights();
		for (;;) {
			bool evictMore = maxCount > 0 && (zend_long) zend_hash_num_elements(Z_ARRVAL_P(values)) >= maxCount;
			if (!evictMore) {
				zend_long maxWeight;
				if (UNEXPECTED(!readLong(slots::maxWeight, "maxWeight", maxWeight))) return zv::Val();
				if (maxWeight > 0 && weightPlusExceeds(incomingWeight, maxWeight)) {
					zend_long floorCount;
					if (UNEXPECTED(!readLong(slots::weightEvictionFloorCount, "weightEvictionFloorCount", floorCount))) return zv::Val();
					evictMore = (zend_long) zend_hash_num_elements(Z_ARRVAL_P(values)) > floorCount;
				}
			}
			if (!evictMore) break;

			/* $oldestKey = array_key_first($this->values); */
			HashPosition position;
			zval oldestKey;
			zend_hash_internal_pointer_reset_ex(Z_ARRVAL_P(values), &position);
			zend_hash_get_current_key_zval_ex(Z_ARRVAL_P(values), &oldestKey, &position);
			if (Z_TYPE(oldestKey) == IS_NULL) break;

			/* $this->weight -= $this->weights[$oldestKey]; */
			zval *oldestWeight = Z_TYPE(oldestKey) == IS_LONG
				? zend_hash_index_find(Z_ARRVAL_P(weights), Z_LVAL(oldestKey))
				: zend_symtable_find(Z_ARRVAL_P(weights), Z_STR(oldestKey));
			if (UNEXPECTED(!subtractWeight(oldestWeight != NULL ? zval_get_long(oldestWeight) : 0))) {
				zval_ptr_dtor(&oldestKey);
				return zv::Val();
			}
			SEPARATE_ARRAY(values);
			SEPARATE_ARRAY(weights);
			if (Z_TYPE(oldestKey) == IS_LONG) {
				zend_hash_index_del(Z_ARRVAL_P(values), Z_LVAL(oldestKey));
				zend_hash_index_del(Z_ARRVAL_P(weights), Z_LVAL(oldestKey));
			} else {
				zend_symtable_del(Z_ARRVAL_P(values), Z_STR(oldestKey));
				zend_symtable_del(Z_ARRVAL_P(weights), Z_STR(oldestKey));
			}
			/* $evicted[] = $oldestKey; — the owned key goes into the list */
			SEPARATE_ARRAY(evicted.raw());
			zend_hash_next_index_insert(Z_ARRVAL_P(evicted.raw()), &oldestKey);
		}

		return evicted;
	}

	/* $this->weight + $incomingWeight > $maxWeight, with PHP's int
	 * arithmetic (an overflowing sum is a float, compared as one) */
	bool weightPlusExceeds(zend_long incomingWeight, zend_long maxWeight) const
	{
		zval incoming, sum;
		ZVAL_LONG(&incoming, incomingWeight);
		fast_long_add_function(&sum, weight(), &incoming);
		if (EXPECTED(Z_TYPE(sum) == IS_LONG)) return Z_LVAL(sum) > maxWeight;
		return Z_DVAL(sum) > (double) maxWeight;
	}

	/* throw new ShouldNotHappenException(sprintf('Cannot replace %s, it is
	 * not in the cache.', $key)); — the message assembled binary-safely,
	 * as sprintf() does */
	static void throwNotInCache(zend_string *key)
	{
		zend_class_entry *ce = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
		if (UNEXPECTED(ce == NULL)) return; /* error already thrown */
		smart_str message = {NULL, 0};
		smart_str_appendl(&message, PT_LC("Cannot replace "));
		smart_str_append(&message, key);
		smart_str_appendl(&message, PT_LC(", it is not in the cache."));
		smart_str_0(&message);
		zend_object *exception = zend_throw_exception(ce, NULL, 0);
		if (EXPECTED(exception != NULL)) {
			zval messageValue;
			ZVAL_STR(&messageValue, message.s);
			zend_update_property_ex(ce, exception, ZSTR_KNOWN(ZEND_STR_MESSAGE), &messageValue);
		}
		smart_str_free(&message);
	}
};

} // namespace phpstanturbo

using phpstanturbo::LruCache;

/* {{{ exported helpers: the shadowing class for native callers */

bool pt_lru_cache_new(zval *out, zend_long maxCount, zend_long maxWeight, zend_long weightEvictionFloorCount)
{
	if (UNEXPECTED(pt_ce_lru_cache == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: LruCache is not activated");
		return false;
	}
	if (UNEXPECTED(object_init_ex(out, pt_ce_lru_cache) != SUCCESS)) return false;
	LruCache(Z_OBJ_P(out)).construct(maxCount, maxWeight, weightEvictionFloorCount);
	return true;
}

zv::Val pt_lru_cache_get(zval *cache, zend_string *key)
{
	if (EXPECTED(Z_OBJCE_P(cache) == pt_ce_lru_cache)) return LruCache(Z_OBJ_P(cache)).get(key);
	zval keyValue;
	ZVAL_STR(&keyValue, key);
	return pt_type_call(Z_OBJ_P(cache), PT_LC("get"), 1, &keyValue);
}

zv::Val pt_lru_cache_set(zval *cache, zend_string *key, zval *value, zend_long weight)
{
	if (EXPECTED(Z_OBJCE_P(cache) == pt_ce_lru_cache)) return LruCache(Z_OBJ_P(cache)).set(key, value, weight);
	zv::Args args{key, value, zend_long(weight)};
	return pt_type_call(Z_OBJ_P(cache), PT_LC("set"), 3, args);
}

bool pt_lru_cache_replace(zval *cache, zend_string *key, zval *value)
{
	if (EXPECTED(Z_OBJCE_P(cache) == pt_ce_lru_cache)) return LruCache(Z_OBJ_P(cache)).replace(key, value);
	zv::Args args{key, value};
	return !pt_type_call(Z_OBJ_P(cache), PT_LC("replace"), 2, args).isUndef();
}

zend_long pt_lru_cache_count(zval *cache)
{
	if (EXPECTED(Z_OBJCE_P(cache) == pt_ce_lru_cache)) return LruCache(Z_OBJ_P(cache)).count();
	zv::Val count = pt_type_call(Z_OBJ_P(cache), PT_LC("count"), 0, NULL);
	if (UNEXPECTED(count.isUndef())) return -1;
	return zval_get_long(count.raw());
}

zv::Val pt_lru_cache_all(zval *cache)
{
	if (EXPECTED(Z_OBJCE_P(cache) == pt_ce_lru_cache)) return LruCache(Z_OBJ_P(cache)).all();
	return pt_type_call(Z_OBJ_P(cache), PT_LC("all"), 0, NULL);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_lru_cache()
{
	reg::Class cls("PHPStan\\Internal\\LruCache");
	ptdecl::LruCache::declareClass(cls);
	/* the twin's slots in its order (OBJ_PROP_NUM): $values, $weights,
	 * $weight, then the promoted $maxCount, $maxWeight,
	 * $weightEvictionFloorCount — typed, uninitialized until the
	 * constructor runs */
	cls.privateTypedArrayPropertyDefaultEmpty("values");
	cls.privateTypedArrayPropertyDefaultEmpty("weights");
	cls.privateTypedLongProperty("weight", 0);
	cls.privateTypedProperty("maxCount", MAY_BE_LONG);
	cls.privateTypedProperty("maxWeight", MAY_BE_LONG);
	cls.privateTypedProperty("weightEvictionFloorCount", MAY_BE_LONG);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long maxCount = 0, maxWeight = 0, weightEvictionFloorCount = 0;
		if (!zp::parse<zp::Opt<zp::Long>, zp::Opt<zp::Long>, zp::Opt<zp::Long>>(execute_data, maxCount, maxWeight, weightEvictionFloorCount)) RETURN_THROWS();
		LruCache(Z_OBJ_P(ZEND_THIS)).construct(maxCount, maxWeight, weightEvictionFloorCount);
	});

	cls.method(sigs::get, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *key;
		if (!zp::parse<zp::Str>(execute_data, key)) RETURN_THROWS();
		LruCache(Z_OBJ_P(ZEND_THIS)).get(key).intoReturnValue(return_value);
	});

	cls.method(sigs::set, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *key;
		zval *value;
		zend_long weight;
		if (!zp::parse<zp::Str, zp::Zval, zp::Long>(execute_data, key, value, weight)) RETURN_THROWS();
		zv::Val evicted = LruCache(Z_OBJ_P(ZEND_THIS)).set(key, value, weight);
		if (UNEXPECTED(evicted.isUndef())) RETURN_THROWS();
		evicted.intoReturnValue(return_value);
	});

	cls.method(sigs::replace, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *key;
		zval *value;
		if (!zp::parse<zp::Str, zp::Zval>(execute_data, key, value)) RETURN_THROWS();
		if (UNEXPECTED(!LruCache(Z_OBJ_P(ZEND_THIS)).replace(key, value))) RETURN_THROWS();
	});

	cls.method(sigs::count, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_LONG(LruCache(Z_OBJ_P(ZEND_THIS)).count());
	});

	cls.method(sigs::all, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		LruCache(Z_OBJ_P(ZEND_THIS)).all().intoReturnValue(return_value);
	});

	cls.shadow(&pt_ce_lru_cache);
}

/* }}} */
