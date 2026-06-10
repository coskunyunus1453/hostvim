package monitoring

import (
	"sync"
	"time"
)

const extendedStatsCacheTTL = 15 * time.Second

var (
	extendedCacheMu sync.Mutex
	extendedCache   ExtendedSnapshot
	extendedCacheAt time.Time
	extendedCacheOK bool
)

// CollectExtendedCached tam snapshot; kısa TTL ile tekrarlayan istekleri hızlandırır.
func CollectExtendedCached(rootPath string) ExtendedSnapshot {
	extendedCacheMu.Lock()
	if extendedCacheOK && time.Since(extendedCacheAt) < extendedStatsCacheTTL {
		out := extendedCache
		extendedCacheMu.Unlock()
		return out
	}
	extendedCacheMu.Unlock()

	snap := CollectExtended(rootPath)

	extendedCacheMu.Lock()
	extendedCache = snap
	extendedCacheAt = time.Now()
	extendedCacheOK = true
	extendedCacheMu.Unlock()

	return snap
}
