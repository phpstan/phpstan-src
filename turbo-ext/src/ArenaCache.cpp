/*
 * PHPStanTurbo\ArenaCache — native implementation of PHPStan\Cache\ArenaCache.
 *
 * A single-run shared-memory arena: the master process creates a named
 * shared-memory object before spawning parallel workers, every process maps
 * it, and lazily-computed read-mostly data (signature map rows, later symbol
 * indexes and reflection records) is published by whichever process computes
 * it first. The other processes then materialize entries on demand instead of
 * rebuilding the whole structure — the arena's physical pages are shared, so
 * N workers stop paying N copies.
 *
 * Lifetime is exactly one analysis run. There is no persistence and no
 * invalidation: the master unlinks the name as soon as all workers have
 * attached (the mapping stays valid until the last process exits — the kernel
 * reclaims everything even after SIGKILL), and destroys the mapping when the
 * run ends. A worker that fails to attach simply computes everything locally,
 * exactly as it does when the extension is absent; the PHP twin of this class
 * is a cache that never hits.
 *
 * Concurrency is lock-free and write-once:
 *
 *  - allocation is a fetch-add bump cursor in the arena header;
 *  - publication is a compare-and-swap of an index slot from 0 to the record
 *    offset, performed only after the record bytes are fully written (release
 *    ordering; readers load slots with acquire ordering). A torn record is
 *    therefore unobservable, and a process killed mid-write leaves only
 *    unreachable garbage reclaimed with the arena.
 *  - two processes computing the same key race benignly: one CAS wins, the
 *    loser's bytes become dead space. Wasteful, never unsafe — the same
 *    philosophy as the odsl directory-scan lock's cold-cache races.
 *
 * Records are self-contained flat blobs of *data-only* PHP values (null,
 * bool, int, float, string, array). Objects, resources and cycles are not
 * serializable here; publish simply aborts and the key stays computable
 * locally. Nothing in the mapped region is ever seen by the PHP GC — reads
 * materialize fresh per-process zvals — so shared pages are never dirtied by
 * refcounting. Offsets, never pointers: every process maps at whatever base
 * address it gets.
 *
 * Two record kinds exist: a plain value, and a "hash record" that carries its
 * own open-addressed table of entries so a single map published once (e.g.
 * the 15k-row signature map) can be read per-entry without materializing the
 * whole map in any worker.
 */

#include "support.h"
#include "reg.h"
#include "zv.h"

#include <cstring>
#include <vector>

#ifndef _WIN32
#include <errno.h>
#include <fcntl.h>
#include <sys/mman.h>
#include <sys/stat.h>
#include <unistd.h>
#endif

namespace phpstanturbo {

/* Total mapped size per run. Virtual reservation only: POSIX shm and Windows
 * pagefile sections allocate physical pages on first touch, so an idle arena
 * costs a few pages. When the cursor passes the end, publishing stops and
 * analysis continues unshared. */
static constexpr uint64_t ARENA_SIZE_LIMIT = 256ULL << 20;

/* Top-level index: open-addressed table of 8-byte slots (record offsets).
 * Sized for the record *count* (records are whole maps/blobs, not entries):
 * 65536 slots = 512KB of never-touched-until-used zero pages. */
static constexpr uint64_t INDEX_SLOT_COUNT = 1ULL << 16;

/* Linear-probe bound on the top-level index; past this the table is congested
 * enough that we stop publishing rather than degrade every lookup. */
static constexpr uint32_t INDEX_PROBE_LIMIT = 128;

/* Depth bound for the value serializer; data trees here are shallow, anything
 * deeper is either a cycle (via references) or not worth sharing. */
static constexpr uint32_t SERIALIZE_DEPTH_LIMIT = 128;

/* Run IDs are Random::generate() alnum strings; the bound keeps the POSIX
 * name under macOS's 31-char PSHMNAMLEN. */
static constexpr size_t RUN_ID_LENGTH_LIMIT = 20;

/* Minimum slot count of a hash record's internal table. */
static constexpr uint64_t HASH_RECORD_MIN_SLOTS = 8;

static constexpr uint64_t ARENA_MAGIC = 0x414E455241545350ULL; /* "PSTARENA" */
static constexpr uint32_t ARENA_FORMAT_VERSION = 1;

struct ArenaHeader
{
	uint64_t magic;
	uint32_t formatVersion;
	uint32_t reserved;
	uint64_t totalSize;
	uint64_t indexOffset;
	uint64_t indexSlotCount;
	uint64_t allocCursor; /* atomic bump allocator, 8-aligned offsets */
};

/* A published record: header, key bytes, then the payload 8-aligned from the
 * record start. kind 0 = serialized value, kind 1 = hash record. */
struct RecordHeader
{
	uint32_t kind;
	uint32_t keyLen;
	uint64_t payloadLen;
};

static constexpr uint32_t RECORD_KIND_VALUE = 0;
static constexpr uint32_t RECORD_KIND_HASH = 1;

static constexpr uint64_t INDEX_OFFSET = 4096;

/* {{{ process-local arena state (plain statics — NTS, one arena per process) */

static void *pt_arena_base = NULL;
static uint64_t pt_arena_total = 0;
static bool pt_arena_creator = false;
static bool pt_arena_name_unlinked = false;
static char pt_arena_name[64] = { 0 };
#ifdef _WIN32
static HANDLE pt_arena_section = NULL;
#endif

/* }}} */

/* {{{ cross-process atomics on mapped 8-aligned u64 cells
 *
 * Raw zend form has no equivalent here; MSVC gets the winnt.h acquire/release
 * intrinsics (correct on x64 and ARM64 alike), everything else the __atomic
 * builtins. */

static zend_always_inline uint64_t atomicLoadAcquire(const uint64_t *cell)
{
#ifdef _MSC_VER
	/* x64/ARM64 only (the shipped Windows builds); a plain RMW load would
	 * write the cache line and dirty shared index pages on every read */
	return (uint64_t) ReadAcquire64((const volatile LONG64 *) cell);
#else
	return __atomic_load_n(cell, __ATOMIC_ACQUIRE);
#endif
}

static zend_always_inline bool atomicCasRelease(uint64_t *cell, uint64_t expected, uint64_t desired)
{
#ifdef _MSC_VER
	return (uint64_t) InterlockedCompareExchange64((volatile LONG64 *) cell, (LONG64) desired, (LONG64) expected) == expected;
#else
	return __atomic_compare_exchange_n(cell, &expected, desired, false, __ATOMIC_RELEASE, __ATOMIC_ACQUIRE);
#endif
}

static zend_always_inline uint64_t atomicFetchAdd(uint64_t *cell, uint64_t add)
{
#ifdef _MSC_VER
	return (uint64_t) InterlockedExchangeAdd64((volatile LONG64 *) cell, (LONG64) add);
#else
	return __atomic_fetch_add(cell, add, __ATOMIC_ACQ_REL);
#endif
}

/* }}} */

static zend_always_inline uint64_t alignUp8(uint64_t value)
{
	return (value + 7) & ~(uint64_t) 7;
}

static uint64_t fnv1a64(const void *data, size_t len)
{
	uint64_t hash = 0xcbf29ce484222325ULL;
	const uint8_t *bytes = (const uint8_t *) data;
	for (size_t i = 0; i < len; i++) {
		hash = (hash ^ bytes[i]) * 0x100000001b3ULL;
	}
	return hash;
}

static zend_always_inline ArenaHeader *arenaHeader()
{
	return (ArenaHeader *) pt_arena_base;
}

static zend_always_inline uint64_t arenaDataStart()
{
	/* header page + index, already a 4096 multiple with the current sizing */
	return INDEX_OFFSET + INDEX_SLOT_COUNT * sizeof(uint64_t);
}

/* {{{ value serializer — data-only zval trees to a flat byte stream
 *
 * Tags: 0 null, 1 false, 2 true, 3 int64, 4 double, 5 string(u32+bytes),
 * 7 array(u32 count, then per entry: u8 keyKind (0 int / 1 string), key,
 * value). Arrays always serialize entry-by-entry with explicit keys and
 * rebuild through the engine's own key handling, so packed/holed/mixed
 * layouts round-trip without a separate packed form. */

static constexpr uint8_t TAG_NULL = 0;
static constexpr uint8_t TAG_FALSE = 1;
static constexpr uint8_t TAG_TRUE = 2;
static constexpr uint8_t TAG_INT = 3;
static constexpr uint8_t TAG_DOUBLE = 4;
static constexpr uint8_t TAG_STRING = 5;
static constexpr uint8_t TAG_ARRAY = 7;

struct WriteBuffer
{
	std::vector<uint8_t> bytes;

	void u8(uint8_t v) { bytes.push_back(v); }

	void u32(uint32_t v)
	{
		size_t at = bytes.size();
		bytes.resize(at + sizeof(v));
		memcpy(bytes.data() + at, &v, sizeof(v));
	}

	void u64(uint64_t v)
	{
		size_t at = bytes.size();
		bytes.resize(at + sizeof(v));
		memcpy(bytes.data() + at, &v, sizeof(v));
	}

	void blob(const void *data, size_t len)
	{
		size_t at = bytes.size();
		bytes.resize(at + len);
		memcpy(bytes.data() + at, data, len);
	}
};

static bool serializeValue(WriteBuffer &out, zval *value, uint32_t depth)
{
	if (depth > SERIALIZE_DEPTH_LIMIT) {
		return false;
	}
	ZVAL_DEREF(value);
	switch (Z_TYPE_P(value)) {
		case IS_NULL:
			out.u8(TAG_NULL);
			return true;
		case IS_FALSE:
			out.u8(TAG_FALSE);
			return true;
		case IS_TRUE:
			out.u8(TAG_TRUE);
			return true;
		case IS_LONG:
			out.u8(TAG_INT);
			out.u64((uint64_t) Z_LVAL_P(value));
			return true;
		case IS_DOUBLE: {
			out.u8(TAG_DOUBLE);
			double d = Z_DVAL_P(value);
			out.blob(&d, sizeof(d));
			return true;
		}
		case IS_STRING:
			out.u8(TAG_STRING);
			out.u32((uint32_t) Z_STRLEN_P(value));
			out.blob(Z_STRVAL_P(value), Z_STRLEN_P(value));
			return true;
		case IS_ARRAY: {
			out.u8(TAG_ARRAY);
			out.u32(zend_hash_num_elements(Z_ARRVAL_P(value)));
			for (zv::ArrayEntry entry : zv::ArrRef(value)) {
				zend_string *stringKey = entry.stringKeyOrNull();
				if (stringKey != NULL) {
					out.u8(1);
					out.u32((uint32_t) ZSTR_LEN(stringKey));
					out.blob(ZSTR_VAL(stringKey), ZSTR_LEN(stringKey));
				} else {
					out.u8(0);
					out.u64((uint64_t) entry.indexKey());
				}
				if (!serializeValue(out, entry.value().raw(), depth + 1)) {
					return false;
				}
			}
			return true;
		}
		default:
			/* objects, resources, everything non-data: this value cannot be
			 * shared; the caller drops the whole publish */
			return false;
	}
}

/* }}} */

/* {{{ value deserializer — bounds-checked against the record's payload
 *
 * Every read validates remaining length: a corrupt arena must degrade to a
 * miss (PHP recomputes locally), never crash. Multibyte reads go through
 * memcpy — the stream is byte-aligned. */

struct ReadCursor
{
	const uint8_t *p;
	const uint8_t *end;

	bool need(size_t n) const { return (size_t) (end - p) >= n; }

	bool u8(uint8_t *out)
	{
		if (!need(1)) {
			return false;
		}
		*out = *p++;
		return true;
	}

	bool u32(uint32_t *out)
	{
		if (!need(sizeof(*out))) {
			return false;
		}
		memcpy(out, p, sizeof(*out));
		p += sizeof(*out);
		return true;
	}

	bool u64(uint64_t *out)
	{
		if (!need(sizeof(*out))) {
			return false;
		}
		memcpy(out, p, sizeof(*out));
		p += sizeof(*out);
		return true;
	}
};

static bool deserializeValue(ReadCursor &in, zval *out, uint32_t depth)
{
	if (depth > SERIALIZE_DEPTH_LIMIT) {
		return false;
	}
	uint8_t tag;
	if (!in.u8(&tag)) {
		return false;
	}
	switch (tag) {
		case TAG_NULL:
			ZVAL_NULL(out);
			return true;
		case TAG_FALSE:
			ZVAL_FALSE(out);
			return true;
		case TAG_TRUE:
			ZVAL_TRUE(out);
			return true;
		case TAG_INT: {
			uint64_t v;
			if (!in.u64(&v)) {
				return false;
			}
			ZVAL_LONG(out, (zend_long) v);
			return true;
		}
		case TAG_DOUBLE: {
			if (!in.need(sizeof(double))) {
				return false;
			}
			double d;
			memcpy(&d, in.p, sizeof(d));
			in.p += sizeof(d);
			ZVAL_DOUBLE(out, d);
			return true;
		}
		case TAG_STRING: {
			uint32_t len;
			if (!in.u32(&len) || !in.need(len)) {
				return false;
			}
			ZVAL_STRINGL(out, (const char *) in.p, len);
			in.p += len;
			return true;
		}
		case TAG_ARRAY: {
			uint32_t count;
			if (!in.u32(&count)) {
				return false;
			}
			/* every entry costs >= 2 stream bytes; rejects corrupt counts
			 * before they turn into a giant preallocation */
			if ((size_t) count > (size_t) (in.end - in.p)) {
				return false;
			}
			zend_array *arr = zend_new_array(count);
			for (uint32_t i = 0; i < count; i++) {
				uint8_t keyKind;
				zval entryValue;
				if (!in.u8(&keyKind)) {
					goto array_fail;
				}
				if (keyKind == 1) {
					uint32_t keyLen;
					if (!in.u32(&keyLen) || !in.need(keyLen)) {
						goto array_fail;
					}
					const char *keyBytes = (const char *) in.p;
					in.p += keyLen;
					if (!deserializeValue(in, &entryValue, depth + 1)) {
						goto array_fail;
					}
					zend_hash_str_update(arr, keyBytes, keyLen, &entryValue);
				} else if (keyKind == 0) {
					uint64_t intKey;
					if (!in.u64(&intKey)) {
						goto array_fail;
					}
					if (!deserializeValue(in, &entryValue, depth + 1)) {
						goto array_fail;
					}
					zend_hash_index_update(arr, (zend_ulong) intKey, &entryValue);
				} else {
					goto array_fail;
				}
			}
			ZVAL_ARR(out, arr);
			return true;
		array_fail:
			zend_array_destroy(arr);
			return false;
		}
		default:
			return false;
	}
}

/* }}} */

/* {{{ records and the top-level index */

static zend_always_inline uint64_t *indexSlots()
{
	return (uint64_t *) ((char *) pt_arena_base + INDEX_OFFSET);
}

/* Validated view of the record at offset; returns false on anything that does
 * not fit inside the mapping (corruption degrades to a miss). */
struct RecordView
{
	const RecordHeader *header;
	const char *key;
	const uint8_t *payload;
};

static bool recordAt(uint64_t offset, RecordView *view)
{
	if (offset < arenaDataStart() || offset + sizeof(RecordHeader) > pt_arena_total) {
		return false;
	}
	const RecordHeader *header = (const RecordHeader *) ((char *) pt_arena_base + offset);
	uint64_t payloadStart = alignUp8(offset + sizeof(RecordHeader) + header->keyLen);
	if (payloadStart > pt_arena_total || header->payloadLen > pt_arena_total - payloadStart) {
		return false;
	}
	view->header = header;
	view->key = (const char *) (header + 1);
	view->payload = (const uint8_t *) pt_arena_base + payloadStart;
	return true;
}

/* Probes the index for key; fills view on hit. */
static bool findRecord(const char *key, size_t keyLen, RecordView *view)
{
	if (pt_arena_base == NULL) {
		return false;
	}
	uint64_t hash = fnv1a64(key, keyLen);
	uint64_t mask = INDEX_SLOT_COUNT - 1;
	uint64_t *slots = indexSlots();
	for (uint32_t probe = 0; probe < INDEX_PROBE_LIMIT; probe++) {
		uint64_t offset = atomicLoadAcquire(&slots[(hash + probe) & mask]);
		if (offset == 0) {
			return false;
		}
		if (!recordAt(offset, view)) {
			return false;
		}
		if (view->header->keyLen == keyLen && memcmp(view->key, key, keyLen) == 0) {
			return true;
		}
	}
	return false;
}

/* Copies a fully-built record into the arena and CAS-publishes it; loses
 * gracefully to a concurrent publisher of the same key. */
static void publishRecord(const char *key, size_t keyLen, uint32_t kind, const WriteBuffer &payload)
{
	if (pt_arena_base == NULL || keyLen > UINT32_MAX) {
		return;
	}

	uint64_t recordSize = alignUp8(sizeof(RecordHeader) + keyLen) + payload.bytes.size();
	uint64_t offset = atomicFetchAdd(&arenaHeader()->allocCursor, alignUp8(recordSize));
	if (offset > pt_arena_total || recordSize > pt_arena_total - offset) {
		return; /* arena full: analysis continues, just unshared */
	}

	char *record = (char *) pt_arena_base + offset;
	RecordHeader header;
	header.kind = kind;
	header.keyLen = (uint32_t) keyLen;
	header.payloadLen = payload.bytes.size();
	memcpy(record, &header, sizeof(header));
	memcpy(record + sizeof(header), key, keyLen);
	memcpy(record + alignUp8(sizeof(header) + keyLen), payload.bytes.data(), payload.bytes.size());

	uint64_t hash = fnv1a64(key, keyLen);
	uint64_t mask = INDEX_SLOT_COUNT - 1;
	uint64_t *slots = indexSlots();
	for (uint32_t probe = 0; probe < INDEX_PROBE_LIMIT; probe++) {
		uint64_t *slot = &slots[(hash + probe) & mask];
		uint64_t current = atomicLoadAcquire(slot);
		if (current == 0) {
			if (atomicCasRelease(slot, 0, offset)) {
				return;
			}
			current = atomicLoadAcquire(slot);
		}
		RecordView existing;
		if (!recordAt(current, &existing)) {
			return;
		}
		if (existing.header->keyLen == keyLen && memcmp(existing.key, key, keyLen) == 0) {
			return; /* lost the race: someone published this key first */
		}
	}
	/* index congested — give up on this record, it stays dead space */
}

/* }}} */

/* {{{ hash records
 *
 * Payload: u64 slotCount (pow2), u64 slots[slotCount] (offsets relative to
 * the payload start, 0 = empty), then the entries: u32 keyLen, key bytes,
 * serialized value. Built single-threaded in a local buffer and published as
 * one record, so the internal table needs no atomics. */

static bool hashRecordBuild(WriteBuffer &out, HashTable *entries)
{
	uint32_t count = zend_hash_num_elements(entries);
	uint64_t slotCount = HASH_RECORD_MIN_SLOTS;
	while (slotCount < (uint64_t) count * 2) {
		slotCount <<= 1;
	}

	out.u64(slotCount);
	size_t slotsAt = out.bytes.size();
	out.bytes.resize(slotsAt + slotCount * sizeof(uint64_t), 0);

	std::vector<uint64_t> slots(slotCount, 0);
	char intKeyBuffer[24];
	for (zv::ArrayEntry entry : zv::TableRef(entries)) {
		const char *keyBytes;
		size_t keyLen;
		zend_string *stringKey = entry.stringKeyOrNull();
		if (stringKey != NULL) {
			keyBytes = ZSTR_VAL(stringKey);
			keyLen = ZSTR_LEN(stringKey);
		} else {
			keyLen = (size_t) snprintf(intKeyBuffer, sizeof(intKeyBuffer), ZEND_ULONG_FMT, entry.indexKey());
			keyBytes = intKeyBuffer;
		}

		uint64_t entryOffset = out.bytes.size();
		out.u32((uint32_t) keyLen);
		out.blob(keyBytes, keyLen);
		if (!serializeValue(out, entry.value().raw(), 0)) {
			return false;
		}

		uint64_t hash = fnv1a64(keyBytes, keyLen);
		uint64_t mask = slotCount - 1;
		for (uint64_t probe = 0; probe < slotCount; probe++) {
			uint64_t *slot = &slots[(hash + probe) & mask];
			if (*slot == 0) {
				*slot = entryOffset;
				break;
			}
		}
	}

	memcpy(out.bytes.data() + slotsAt, slots.data(), slotCount * sizeof(uint64_t));
	return true;
}

/* Rebuilds the entire entries map of a hash record in publication order (the
 * entries region is append-only, so walking it sequentially reproduces the
 * source array's insertion order). Returns false on any inconsistency; the
 * caller degrades to a miss. Integer-like keys round-trip through the
 * symtable the same way PHP array keys do. */
static bool hashRecordAll(const RecordView &view, zval *result)
{
	const uint8_t *payload = view.payload;
	uint64_t payloadLen = view.header->payloadLen;
	if (payloadLen < sizeof(uint64_t)) {
		return false;
	}
	uint64_t slotCount;
	memcpy(&slotCount, payload, sizeof(slotCount));
	if (slotCount == 0 || (slotCount & (slotCount - 1)) != 0
		|| slotCount > (payloadLen - sizeof(uint64_t)) / sizeof(uint64_t)) {
		return false;
	}

	zend_array *all = zend_new_array(8);
	uint64_t pos = sizeof(uint64_t) + slotCount * sizeof(uint64_t);
	while (pos < payloadLen) {
		uint32_t keyLen;
		if (payloadLen - pos < sizeof(keyLen)) {
			goto all_fail;
		}
		memcpy(&keyLen, payload + pos, sizeof(keyLen));
		pos += sizeof(keyLen);
		if (payloadLen - pos < keyLen) {
			goto all_fail;
		}
		{
			const char *keyBytes = (const char *) payload + pos;
			pos += keyLen;
			ReadCursor in;
			in.p = payload + pos;
			in.end = payload + payloadLen;
			zval entryValue;
			if (!deserializeValue(in, &entryValue, 0)) {
				goto all_fail;
			}
			pos = (uint64_t) (in.p - payload);
			zend_symtable_str_update(all, keyBytes, keyLen, &entryValue);
		}
	}
	ZVAL_ARR(result, all);
	return true;
all_fail:
	zend_array_destroy(all);
	return false;
}

/* Looks entryKey up inside a hash record; returns false for "absent" and
 * fills result on a hit. Bounds-checked against the record's payloadLen. */
static bool hashRecordFind(const RecordView &view, const char *entryKey, size_t entryKeyLen, zval *result)
{
	const uint8_t *payload = view.payload;
	uint64_t payloadLen = view.header->payloadLen;
	if (payloadLen < sizeof(uint64_t)) {
		return false;
	}
	uint64_t slotCount;
	memcpy(&slotCount, payload, sizeof(slotCount));
	if (slotCount == 0 || (slotCount & (slotCount - 1)) != 0
		|| slotCount > (payloadLen - sizeof(uint64_t)) / sizeof(uint64_t)) {
		return false;
	}
	const uint8_t *slots = payload + sizeof(uint64_t);
	uint64_t entriesStart = sizeof(uint64_t) + slotCount * sizeof(uint64_t);

	uint64_t hash = fnv1a64(entryKey, entryKeyLen);
	uint64_t mask = slotCount - 1;
	for (uint64_t probe = 0; probe < slotCount; probe++) {
		uint64_t entryOffset;
		memcpy(&entryOffset, slots + ((hash + probe) & mask) * sizeof(uint64_t), sizeof(entryOffset));
		if (entryOffset == 0) {
			return false;
		}
		if (entryOffset < entriesStart || entryOffset + sizeof(uint32_t) > payloadLen) {
			return false;
		}
		uint32_t keyLen;
		memcpy(&keyLen, payload + entryOffset, sizeof(keyLen));
		if (keyLen > payloadLen - entryOffset - sizeof(uint32_t)) {
			return false;
		}
		const char *keyBytes = (const char *) payload + entryOffset + sizeof(uint32_t);
		if (keyLen == entryKeyLen && memcmp(keyBytes, entryKey, entryKeyLen) == 0) {
			ReadCursor in;
			in.p = (const uint8_t *) keyBytes + keyLen;
			in.end = payload + payloadLen;
			return deserializeValue(in, result, 0);
		}
	}
	return false;
}

/* }}} */

/* {{{ platform layer — create/attach/unlink/destroy */

static void arenaResetState()
{
	pt_arena_base = NULL;
	pt_arena_total = 0;
	pt_arena_creator = false;
	pt_arena_name_unlinked = false;
	pt_arena_name[0] = '\0';
#ifdef _WIN32
	pt_arena_section = NULL;
#endif
}

static bool runIdValid(zend_string *runId)
{
	if (ZSTR_LEN(runId) == 0 || ZSTR_LEN(runId) > RUN_ID_LENGTH_LIMIT) {
		return false;
	}
	for (size_t i = 0; i < ZSTR_LEN(runId); i++) {
		char c = ZSTR_VAL(runId)[i];
		bool alnum = (c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') || (c >= '0' && c <= '9');
		if (!alnum) {
			return false;
		}
	}
	return true;
}

static bool headerValid(const ArenaHeader *header)
{
	return header->magic == ARENA_MAGIC
		&& header->formatVersion == ARENA_FORMAT_VERSION
		&& header->totalSize == ARENA_SIZE_LIMIT
		&& header->indexOffset == INDEX_OFFSET
		&& header->indexSlotCount == INDEX_SLOT_COUNT;
}

class ArenaCache
{
public:
	static void create(zend_string *runId, zval *return_value)
	{
		RETVAL_NULL();
		if (pt_arena_base != NULL || !runIdValid(runId)) {
			return;
		}

#ifdef _WIN32
		char name[64];
		snprintf(name, sizeof(name), "Local\\phpstan-%s", ZSTR_VAL(runId));
		HANDLE section = CreateFileMappingA(
			INVALID_HANDLE_VALUE,
			NULL,
			PAGE_READWRITE,
			(DWORD) (ARENA_SIZE_LIMIT >> 32),
			(DWORD) (ARENA_SIZE_LIMIT & 0xFFFFFFFF),
			name);
		if (section == NULL) {
			return;
		}
		if (GetLastError() == ERROR_ALREADY_EXISTS) {
			CloseHandle(section);
			return;
		}
		void *base = MapViewOfFile(section, FILE_MAP_READ | FILE_MAP_WRITE, 0, 0, 0);
		if (base == NULL) {
			CloseHandle(section);
			return;
		}
		pt_arena_section = section;
#else
		char name[64];
		snprintf(name, sizeof(name), "/phpstan-%s", ZSTR_VAL(runId));
		int fd = shm_open(name, O_CREAT | O_EXCL | O_RDWR, 0600);
		if (fd < 0) {
			return;
		}
		if (ftruncate(fd, (off_t) ARENA_SIZE_LIMIT) != 0) {
			close(fd);
			shm_unlink(name);
			return;
		}
		void *base = mmap(NULL, ARENA_SIZE_LIMIT, PROT_READ | PROT_WRITE, MAP_SHARED, fd, 0);
		close(fd);
		if (base == MAP_FAILED) {
			shm_unlink(name);
			return;
		}
#endif

		pt_arena_base = base;
		pt_arena_total = ARENA_SIZE_LIMIT;
		pt_arena_creator = true;
		pt_arena_name_unlinked = false;
		snprintf(pt_arena_name, sizeof(pt_arena_name), "%s", name);

		/* fresh shm pages are zero-filled; only the header needs writing.
		 * Workers attach only after create() returned (the name travels via
		 * the spawn environment), so plain stores suffice here. */
		ArenaHeader *header = arenaHeader();
		header->magic = ARENA_MAGIC;
		header->formatVersion = ARENA_FORMAT_VERSION;
		header->totalSize = ARENA_SIZE_LIMIT;
		header->indexOffset = INDEX_OFFSET;
		header->indexSlotCount = INDEX_SLOT_COUNT;
		header->allocCursor = arenaDataStart();

		RETVAL_STRING(pt_arena_name);
	}

	static bool attach(zend_string *name)
	{
		if (pt_arena_base != NULL) {
			/* already mapped — a forked child inherits the parent's mapping */
			return true;
		}
		if (ZSTR_LEN(name) == 0 || ZSTR_LEN(name) >= sizeof(pt_arena_name)) {
			return false;
		}

#ifdef _WIN32
		if (strncmp(ZSTR_VAL(name), "Local\\phpstan-", 14) != 0) {
			return false;
		}
		HANDLE section = OpenFileMappingA(FILE_MAP_READ | FILE_MAP_WRITE, FALSE, ZSTR_VAL(name));
		if (section == NULL) {
			return false;
		}
		void *base = MapViewOfFile(section, FILE_MAP_READ | FILE_MAP_WRITE, 0, 0, 0);
		if (base == NULL) {
			CloseHandle(section);
			return false;
		}
		if (!headerValid((const ArenaHeader *) base)) {
			UnmapViewOfFile(base);
			CloseHandle(section);
			return false;
		}
		pt_arena_section = section;
#else
		if (strncmp(ZSTR_VAL(name), "/phpstan-", 9) != 0) {
			return false;
		}
		int fd = shm_open(ZSTR_VAL(name), O_RDWR, 0);
		if (fd < 0) {
			return false;
		}
		struct stat objectStat;
		if (fstat(fd, &objectStat) != 0 || (uint64_t) objectStat.st_size < ARENA_SIZE_LIMIT) {
			close(fd);
			return false;
		}
		void *base = mmap(NULL, ARENA_SIZE_LIMIT, PROT_READ | PROT_WRITE, MAP_SHARED, fd, 0);
		close(fd);
		if (base == MAP_FAILED) {
			return false;
		}
		if (!headerValid((const ArenaHeader *) base)) {
			munmap(base, ARENA_SIZE_LIMIT);
			return false;
		}
#endif

		pt_arena_base = base;
		pt_arena_total = ARENA_SIZE_LIMIT;
		pt_arena_creator = false;
		snprintf(pt_arena_name, sizeof(pt_arena_name), "%s", ZSTR_VAL(name));
		return true;
	}

	static void unlinkName()
	{
#ifndef _WIN32
		/* the object stays alive while mapped; after this no process can leak
		 * it — the kernel reclaims it when the last mapping goes away, even
		 * on SIGKILL. Windows sections are handle-refcounted; nothing to do. */
		if (pt_arena_creator && !pt_arena_name_unlinked && pt_arena_name[0] != '\0') {
			shm_unlink(pt_arena_name);
			pt_arena_name_unlinked = true;
		}
#endif
	}

	static void destroy()
	{
		if (pt_arena_base == NULL) {
			return;
		}
		unlinkName();
#ifdef _WIN32
		UnmapViewOfFile(pt_arena_base);
		CloseHandle(pt_arena_section);
#else
		munmap(pt_arena_base, pt_arena_total);
#endif
		arenaResetState();
	}

	static bool hasRecord(zend_string *key)
	{
		RecordView view;
		return findRecord(ZSTR_VAL(key), ZSTR_LEN(key), &view);
	}

	static void lookup(zend_string *key, zval *return_value)
	{
		RETVAL_NULL();
		RecordView view;
		if (!findRecord(ZSTR_VAL(key), ZSTR_LEN(key), &view) || view.header->kind != RECORD_KIND_VALUE) {
			return;
		}
		ReadCursor in;
		in.p = view.payload;
		in.end = view.payload + view.header->payloadLen;
		zval result;
		if (!deserializeValue(in, &result, 0)) {
			return;
		}
		RETVAL_ZVAL(&result, 0, 0);
	}

	static void publish(zend_string *key, zval *value)
	{
		if (pt_arena_base == NULL) {
			return;
		}
		RecordView existing;
		if (findRecord(ZSTR_VAL(key), ZSTR_LEN(key), &existing)) {
			return;
		}
		WriteBuffer payload;
		if (!serializeValue(payload, value, 0)) {
			return;
		}
		publishRecord(ZSTR_VAL(key), ZSTR_LEN(key), RECORD_KIND_VALUE, payload);
	}

	static void lookupHash(zend_string *recordKey, zend_string *entryKey, zval *return_value)
	{
		RETVAL_NULL();
		RecordView view;
		if (!findRecord(ZSTR_VAL(recordKey), ZSTR_LEN(recordKey), &view) || view.header->kind != RECORD_KIND_HASH) {
			return;
		}
		zval result;
		if (!hashRecordFind(view, ZSTR_VAL(entryKey), ZSTR_LEN(entryKey), &result)) {
			return;
		}
		RETVAL_ZVAL(&result, 0, 0);
	}

	static void lookupHashAll(zend_string *recordKey, zval *return_value)
	{
		RETVAL_NULL();
		RecordView view;
		if (!findRecord(ZSTR_VAL(recordKey), ZSTR_LEN(recordKey), &view) || view.header->kind != RECORD_KIND_HASH) {
			return;
		}
		zval result;
		if (!hashRecordAll(view, &result)) {
			return;
		}
		RETVAL_ZVAL(&result, 0, 0);
	}

	static void publishHash(zend_string *recordKey, HashTable *entries)
	{
		if (pt_arena_base == NULL) {
			return;
		}
		RecordView existing;
		if (findRecord(ZSTR_VAL(recordKey), ZSTR_LEN(recordKey), &existing)) {
			return;
		}
		WriteBuffer payload;
		if (!hashRecordBuild(payload, entries)) {
			return;
		}
		publishRecord(ZSTR_VAL(recordKey), ZSTR_LEN(recordKey), RECORD_KIND_HASH, payload);
	}
};

/* }}} */

} // namespace phpstanturbo

void pt_arena_mshutdown()
{
	/* belt-and-braces for graceful exits that skipped destroy(); the kernel
	 * covers everything else once the name is unlinked */
	phpstanturbo::ArenaCache::destroy();
}

void pt_register_arena_cache()
{
	reg::Class cls("PHPStanTurbo\\ArenaCache");

	cls.method("create", reg::PublicStatic, 1, { reg::stringArg("runId") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *runId;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_STR(runId)
		ZEND_PARSE_PARAMETERS_END();
		phpstanturbo::ArenaCache::create(runId, return_value);
	});

	cls.method("attach", reg::PublicStatic, 1, { reg::stringArg("name") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *name;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_STR(name)
		ZEND_PARSE_PARAMETERS_END();
		RETURN_BOOL(phpstanturbo::ArenaCache::attach(name));
	});

	cls.method("unlinkName", reg::PublicStatic, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		phpstanturbo::ArenaCache::unlinkName();
	});

	cls.method("destroy", reg::PublicStatic, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		phpstanturbo::ArenaCache::destroy();
	});

	cls.method("hasRecord", reg::PublicStatic, 1, { reg::stringArg("key") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *key;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_STR(key)
		ZEND_PARSE_PARAMETERS_END();
		RETURN_BOOL(phpstanturbo::ArenaCache::hasRecord(key));
	});

	cls.method("lookup", reg::PublicStatic, 1, { reg::stringArg("key") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *key;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_STR(key)
		ZEND_PARSE_PARAMETERS_END();
		phpstanturbo::ArenaCache::lookup(key, return_value);
	});

	cls.method("publish", reg::PublicStatic, 2, { reg::stringArg("key"), reg::any("value") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *key;
		zval *value;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_STR(key)
			Z_PARAM_ZVAL(value)
		ZEND_PARSE_PARAMETERS_END();
		phpstanturbo::ArenaCache::publish(key, value);
	});

	cls.method("lookupHash", reg::PublicStatic, 2, { reg::stringArg("recordKey"), reg::stringArg("entryKey") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *recordKey;
		zend_string *entryKey;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_STR(recordKey)
			Z_PARAM_STR(entryKey)
		ZEND_PARSE_PARAMETERS_END();
		phpstanturbo::ArenaCache::lookupHash(recordKey, entryKey, return_value);
	});

	cls.method("lookupHashAll", reg::PublicStatic, 1, { reg::stringArg("recordKey") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *recordKey;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_STR(recordKey)
		ZEND_PARSE_PARAMETERS_END();
		phpstanturbo::ArenaCache::lookupHashAll(recordKey, return_value);
	});

	cls.method("publishHash", reg::PublicStatic, 2, { reg::stringArg("recordKey"), reg::arrayArg("entries") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *recordKey;
		HashTable *entries;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_STR(recordKey)
			Z_PARAM_ARRAY_HT(entries)
		ZEND_PARSE_PARAMETERS_END();
		phpstanturbo::ArenaCache::publishHash(recordKey, entries);
	});

	cls.register_();
}
