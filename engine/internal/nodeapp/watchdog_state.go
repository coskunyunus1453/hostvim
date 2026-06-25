package nodeapp

import (
	"sync"
	"time"
)

type watchdogSnapshot struct {
	At     time.Time        `json:"at"`
	Report *ReconcileReport `json:"report,omitempty"`
	Error  string           `json:"error,omitempty"`
}

var (
	watchdogMu    sync.RWMutex
	watchdogLast  watchdogSnapshot
)

func StoreWatchdogSnapshot(report *ReconcileReport, err error) {
	watchdogMu.Lock()
	defer watchdogMu.Unlock()
	snap := watchdogSnapshot{At: time.Now()}
	if report != nil {
		cp := *report
		snap.Report = &cp
	}
	if err != nil {
		snap.Error = err.Error()
	}
	watchdogLast = snap
}

// LastWatchdogSnapshot engine iç watchdog'un son reconcile sonucu (panel bildirimleri için).
func LastWatchdogSnapshot() watchdogSnapshot {
	watchdogMu.RLock()
	defer watchdogMu.RUnlock()
	return watchdogLast
}
