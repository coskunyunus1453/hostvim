package files

// ZIP bomb koruması — panel dosya yöneticisi unzip (varsayılanlar güvenli, yapılandırılabilir).
var (
	maxUnzipEntries       = 100_000
	maxUnzipBytes         = int64(5 << 30) // 5 GiB açılmış toplam
	maxUnzipEntryRatio    = uint64(300)    // tek girdi: açılmış / sıkıştırılmış üst sınır
	maxUnzipArchiveRatio  = uint64(200)    // arşiv geneli: toplam açılmış / sıkıştırılmış
)

// ConfigureUnzipLimits engine.yaml → files.* ile çağrılır.
func ConfigureUnzipLimits(entries int, uncompressedBytes int64, entryRatio, archiveRatio uint64) {
	if entries >= 1_000 && entries <= 500_000 {
		maxUnzipEntries = entries
	}
	if uncompressedBytes >= (512 << 20) && uncompressedBytes <= (20 << 30) {
		maxUnzipBytes = uncompressedBytes
	}
	if entryRatio >= 50 && entryRatio <= 2_000 {
		maxUnzipEntryRatio = entryRatio
	}
	if archiveRatio >= 50 && archiveRatio <= 2_000 {
		maxUnzipArchiveRatio = archiveRatio
	}
}
