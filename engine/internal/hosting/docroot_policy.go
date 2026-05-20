package hosting

import "strings"

// NormalizeAppProfile UI/istemci kaynaklı profil değerini normalize eder.
func NormalizeAppProfile(v string) string {
	s := strings.ToLower(strings.TrimSpace(v))
	switch s {
	case "laravel", "symfony", "wordpress", "woocommerce", "drupal", "joomla", "opencart", "magento",
		"prestashop", "moodle", "mediawiki",
		"nextjs", "nuxt", "strapi", "n8n", "node", "htaccess", "standard",
		"codeigniter", "codeigniter3", "codeigniter4", "php", "static":
		return s
	default:
		return "standard"
	}
}

// DocrootVariantForProfile uygulama profiline göre güvenli document-root varyantını döndürür.
// Global taban her zaman public_html olduğundan:
//   - root   => <base>
//   - public => <base>/public
func DocrootVariantForProfile(profile string) string {
	switch NormalizeAppProfile(profile) {
	case "laravel", "symfony", "codeigniter4":
		return "public"
	default:
		return "root"
	}
}
