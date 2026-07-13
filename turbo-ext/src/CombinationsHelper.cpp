/*
 * PHPStanTurbo\CombinationsHelper — native implementation of
 * PHPStan\Internal\CombinationsHelper::combinations() (Cartesian product).
 */

#include "support.h"
#include "zv.h"

static zend_class_entry *pt_ce_combinations = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Internal\CombinationsHelper. The PHP twin recurses through
 * a generator; the native implementation materializes the full product
 * iteratively, walking an odometer over the input arrays. */
class CombinationsHelper
{
public:
	/* caps the result array's pre-allocation hint, not the product itself */
	static constexpr zend_ulong SIZE_HINT_LIMIT = 1048576;

	/* combinations() refuses products beyond this instead of letting the
	 * multiply overflow and silently truncate the result */
	static constexpr zend_ulong PRODUCT_LIMIT = 1ULL << 32;

	/* UNDEF = pending exception */
	static zv::Val combinations(zv::ArrRef arrays)
	{
		uint32_t n = arrays.size();
		if (n == 0) {
			/* combinations([]) yields a single empty combination */
			zv::Arr result = zv::Arr::create(0);
			result.push(zv::Arr::create(0));
			return result;
		}

		/* borrow the inner array of each element (the input owns them) */
		zval **inner = (zval **) emalloc(n * sizeof(zval *));
		uint32_t i = 0;
		for (auto entry : arrays) {
			zv::Ref element = entry.value().deref();
			if (UNEXPECTED(!element.isArray())) {
				efree(inner);
				zend_type_error("PHPStanTurbo\\CombinationsHelper::combinations() expects an array of arrays");
				return zv::Val();
			}
			inner[i++] = element.raw();
		}

		/* flatten each inner array into a raw element-slot vector */
		uint32_t *sizes = (uint32_t *) emalloc(n * sizeof(uint32_t));
		zval ***vecs = (zval ***) emalloc(n * sizeof(zval **));
		zend_ulong total = 1;
		bool hasEmptyInner = false;
		for (i = 0; i < n; i++) {
			zv::ArrRef innerArr(inner[i]);
			sizes[i] = innerArr.size();
			if (sizes[i] == 0) {
				/* any empty input array empties the whole product */
				hasEmptyInner = true;
				vecs[i] = NULL;
				continue;
			}
			if (UNEXPECTED(total > PRODUCT_LIMIT / sizes[i])) {
				/* The PHP twin is a lazy generator and never materializes the
				 * product, but every consumer iterates it fully, so a product
				 * this size is unreachable in practice. Failing loudly beats
				 * the silent truncation an overflowed multiply would cause. */
				for (uint32_t k = 0; k < i; k++) {
					if (vecs[k] != NULL) {
						efree(vecs[k]);
					}
				}
				efree(vecs);
				efree(sizes);
				efree(inner);
				pt_throw_should_not_happen();
				return zv::Val();
			}
			total *= sizes[i];
			vecs[i] = (zval **) emalloc(sizes[i] * sizeof(zval *));
			uint32_t j = 0;
			for (auto entry : innerArr) {
				/* deref like the twin's by-value foreach: a reference slot
				 * must not propagate a shared reference into every combination */
				vecs[i][j++] = entry.value().deref().raw();
			}
		}

		zv::Val result;
		if (hasEmptyInner) {
			result = zv::Arr::create(0);
		} else {
			result = product(vecs, sizes, n, total);
		}

		for (i = 0; i < n; i++) {
			if (vecs[i] != NULL) {
				efree(vecs[i]);
			}
		}
		efree(vecs);
		efree(sizes);
		efree(inner);
		return result;
	}

private:
	static zv::Val product(zval *const *const *vecs, const uint32_t *sizes, uint32_t n, zend_ulong total)
	{
		uint32_t *indices = (uint32_t *) ecalloc(n, sizeof(uint32_t));
		zv::Arr result = zv::Arr::create((uint32_t) (total > SIZE_HINT_LIMIT ? SIZE_HINT_LIMIT : total));

		for (zend_ulong c = 0; c < total; c++) {
			zv::Arr comb = zv::Arr::create(n);
			for (uint32_t i = 0; i < n; i++) {
				comb.push(zv::Ref(vecs[i][indices[i]]));
			}
			result.push(std::move(comb));

			/* odometer: advance the rightmost index, carrying leftwards */
			for (int64_t j = (int64_t) n - 1; j >= 0; j--) {
				if (++indices[j] < sizes[j]) {
					break;
				}
				indices[j] = 0;
			}
		}

		efree(indices);
		return result;
	}
};

} // namespace phpstanturbo

using phpstanturbo::CombinationsHelper;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_combinations_helper()
{
	reg::Class cls("PHPStanTurbo\\CombinationsHelper");

	cls.method("combinations", reg::PublicStatic, 1, { reg::arrayArg("arrays") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *arrays;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ARRAY_HT(arrays)
		ZEND_PARSE_PARAMETERS_END();
		zval arraysZv;
		ZVAL_ARR(&arraysZv, arrays);
		zv::Val result = CombinationsHelper::combinations(zv::ArrRef(&arraysZv));
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	/* not final: a PHP stub subclass may extend this class */
	pt_ce_combinations = cls.register_();
}

/* }}} */
