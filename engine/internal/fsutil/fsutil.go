// Package fsutil, Engine genelinde güvenli dosya yazma yardımcıları sağlar.
package fsutil

import (
	"os"
	"path/filepath"
)

// AtomicWrite, hedefe aynı dizinde geçici dosya yazıp os.Rename ile atomik geçiş yapar.
//
// Neden: os.WriteFile hedef dosyayı O_TRUNC ile açar; dosya farklı bir kullanıcı
// (ör. root) tarafından oluşturulmuşsa Engine (www-data) "permission denied" alır.
// os.Rename ise yalnızca HEDEF DİZİN üzerinde yazma yetkisi ister (vhost/pool dizinlerinin
// sahibi www-data). Böylece geçmişte root olarak oluşmuş dosyalar bir daha yazımı
// engellemez ve işlem kendini onarır. Ayrıca rename atomik olduğundan yarım yazılmış
// yapılandırma dosyası (nginx -t sırasında) riski de ortadan kalkar.
func AtomicWrite(path string, data []byte, perm os.FileMode) error {
	dir := filepath.Dir(path)
	tmp, err := os.CreateTemp(dir, ".panelze-tmp-*")
	if err != nil {
		return err
	}
	tmpName := tmp.Name()
	// Rename başarılıysa tmpName artık yoktur; başarısız yolda çöpü temizler.
	defer func() { _ = os.Remove(tmpName) }()
	if _, err := tmp.Write(data); err != nil {
		_ = tmp.Close()
		return err
	}
	if err := tmp.Chmod(perm); err != nil {
		_ = tmp.Close()
		return err
	}
	if err := tmp.Close(); err != nil {
		return err
	}
	return os.Rename(tmpName, path)
}
