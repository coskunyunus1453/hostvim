package nodeapp

import "strings"

// DefaultPortForProfile framework varsayılan dinleme portu.
func DefaultPortForProfile(profile string) int {
	switch strings.ToLower(strings.TrimSpace(profile)) {
	case "strapi":
		return 1337
	case "n8n":
		return 5678
	case "nextjs", "nuxt", "node":
		return 3000
	default:
		return 3000
	}
}
