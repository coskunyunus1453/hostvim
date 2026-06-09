package sites

import (
	"fmt"
	"net/url"
	"regexp"
	"sort"
	"strings"
)

const maxRedirectRules = 50

// RedirectRule — domain bazlı URL yönlendirme (nginx return / location).
type RedirectRule struct {
	ID            string `json:"id"`
	Source        string `json:"source"`
	Target        string `json:"target"`
	Status        int    `json:"status"`
	Enabled       bool   `json:"enabled"`
	PreserveQuery bool   `json:"preserve_query"`
	MatchType     string `json:"match_type"`
}

// RedirectRender nginx şablonu için üretilmiş bloklar.
type RedirectRender struct {
	Blocks              string
	FullSiteTarget      string
	FullSiteStatus      int
	HasFullSiteRedirect bool
}

var redirectIDSafe = regexp.MustCompile(`^[a-zA-Z0-9_-]{1,64}$`)

// NormalizeRedirectRules kuralları doğrular ve normalize eder.
func NormalizeRedirectRules(rules []RedirectRule) ([]RedirectRule, error) {
	if len(rules) > maxRedirectRules {
		return nil, fmt.Errorf("too many redirect rules (max %d)", maxRedirectRules)
	}
	out := make([]RedirectRule, 0, len(rules))
	for i, r := range rules {
		nr, err := normalizeRedirectRule(r)
		if err != nil {
			return nil, fmt.Errorf("rule %d: %w", i+1, err)
		}
		out = append(out, nr)
	}
	return out, nil
}

func normalizeRedirectRule(r RedirectRule) (RedirectRule, error) {
	src := strings.TrimSpace(r.Source)
	if src == "" {
		src = "/"
	}
	if !strings.HasPrefix(src, "/") {
		return RedirectRule{}, fmt.Errorf("source must start with /")
	}
	if strings.ContainsAny(src, "\r\n\t") {
		return RedirectRule{}, fmt.Errorf("invalid source")
	}

	tgt := strings.TrimSpace(r.Target)
	if tgt == "" {
		return RedirectRule{}, fmt.Errorf("target required")
	}
	if strings.ContainsAny(tgt, "\r\n\t") {
		return RedirectRule{}, fmt.Errorf("invalid target")
	}
	if strings.HasPrefix(tgt, "//") {
		return RedirectRule{}, fmt.Errorf("invalid target")
	}
	if strings.HasPrefix(tgt, "http://") || strings.HasPrefix(tgt, "https://") {
		u, err := url.Parse(tgt)
		if err != nil || u.Host == "" || strings.Contains(u.Host, "..") {
			return RedirectRule{}, fmt.Errorf("invalid target URL")
		}
	} else if !strings.HasPrefix(tgt, "/") {
		return RedirectRule{}, fmt.Errorf("relative target must start with /")
	}

	st := r.Status
	if st == 0 {
		st = 301
	}
	switch st {
	case 301, 302, 307, 308:
	default:
		return RedirectRule{}, fmt.Errorf("status must be 301, 302, 307 or 308")
	}

	mt := strings.ToLower(strings.TrimSpace(r.MatchType))
	if mt == "" {
		if strings.Contains(src, "*") {
			mt = "wildcard"
		} else {
			mt = "exact"
		}
	}
	switch mt {
	case "exact", "prefix", "wildcard":
	default:
		return RedirectRule{}, fmt.Errorf("invalid match_type")
	}

	id := strings.TrimSpace(r.ID)
	if id == "" {
		id = fmt.Sprintf("r%d", st)
	}
	if !redirectIDSafe.MatchString(id) {
		return RedirectRule{}, fmt.Errorf("invalid rule id")
	}

	return RedirectRule{
		ID:            id,
		Source:        src,
		Target:        tgt,
		Status:        st,
		Enabled:       r.Enabled,
		PreserveQuery: r.PreserveQuery,
		MatchType:     mt,
	}, nil
}

// BuildNginxRedirectRender etkin kurallardan nginx location blokları üretir.
func BuildNginxRedirectRender(rules []RedirectRule) (RedirectRender, error) {
	norm, err := NormalizeRedirectRules(rules)
	if err != nil {
		return RedirectRender{}, err
	}

	render := RedirectRender{}
	var partial []RedirectRule

	for _, r := range norm {
		if !r.Enabled {
			continue
		}
		if r.Source == "/" || r.Source == "/*" {
			render.HasFullSiteRedirect = true
			render.FullSiteTarget = r.Target
			render.FullSiteStatus = r.Status
			continue
		}
		partial = append(partial, r)
	}

	sort.SliceStable(partial, func(i, j int) bool {
		pi := partial[i]
		pj := partial[j]
		rank := func(m string) int {
			switch m {
			case "exact":
				return 0
			case "prefix":
				return 1
			default:
				return 2
			}
		}
		ri, rj := rank(pi.MatchType), rank(pj.MatchType)
		if ri != rj {
			return ri < rj
		}
		return len(pi.Source) > len(pj.Source)
	})

	var b strings.Builder
	for _, r := range partial {
		block, err := nginxRedirectBlock(r)
		if err != nil {
			return RedirectRender{}, err
		}
		b.WriteString(block)
		b.WriteString("\n")
	}
	render.Blocks = strings.TrimSpace(b.String())
	return render, nil
}

func nginxRedirectBlock(r RedirectRule) (string, error) {
	target, err := nginxRedirectTarget(r)
	if err != nil {
		return "", err
	}
	qSuffix := ""
	if r.PreserveQuery {
		qSuffix = "$is_args$args"
	}

	comment := fmt.Sprintf("    # Panelze redirect — %s (%d)\n", r.ID, r.Status)

	switch r.MatchType {
	case "exact":
		loc := r.Source
		if loc != "/" && strings.HasSuffix(loc, "/") {
			loc = strings.TrimSuffix(loc, "/")
		}
		return comment + fmt.Sprintf("    location = %s {\n        return %d %s%s;\n    }\n", loc, r.Status, target, qSuffix), nil
	case "prefix":
		src := strings.TrimSuffix(r.Source, "/")
		if src == "" {
			src = "/"
		}
		return comment + fmt.Sprintf("    location ^~ %s {\n        return %d %s%s;\n    }\n", src, r.Status, target, qSuffix), nil
	case "wildcard":
		pat, err := wildcardToNginxPattern(r.Source)
		if err != nil {
			return "", err
		}
		return comment + fmt.Sprintf("    location ~ %s {\n        return %d %s%s;\n    }\n", pat, r.Status, target, qSuffix), nil
	default:
		return "", fmt.Errorf("unsupported match_type")
	}
}

func nginxRedirectTarget(r RedirectRule) (string, error) {
	tgt := r.Target
	if strings.HasPrefix(tgt, "http://") || strings.HasPrefix(tgt, "https://") {
		return tgt, nil
	}
	if r.MatchType == "wildcard" && strings.Contains(tgt, "*") {
		return wildcardTargetExpr(r.Source, tgt)
	}
	return tgt, nil
}

func wildcardToNginxPattern(source string) (string, error) {
	src := strings.TrimSpace(source)
	if !strings.HasPrefix(src, "/") {
		return "", fmt.Errorf("wildcard source must start with /")
	}
	if strings.Count(src, "*") != 1 {
		return "", fmt.Errorf("wildcard match requires exactly one *")
	}
	parts := strings.SplitN(src, "*", 2)
	prefix := regexp.QuoteMeta(parts[0])
	suffix := regexp.QuoteMeta(parts[1])
	return "^" + prefix + "(.*)" + suffix + "$", nil
}

func wildcardTargetExpr(source, target string) (string, error) {
	if !strings.Contains(target, "*") {
		return target, nil
	}
	parts := strings.SplitN(target, "*", 2)
	if len(parts) != 2 {
		return "", fmt.Errorf("invalid wildcard target")
	}
	return parts[0] + "$1" + parts[1], nil
}

// FullSiteReturnDirective tüm site yönlendirmesi için return satırı.
func FullSiteReturnDirective(render RedirectRender) string {
	if !render.HasFullSiteRedirect || strings.TrimSpace(render.FullSiteTarget) == "" {
		return ""
	}
	st := render.FullSiteStatus
	if st == 0 {
		st = 301
	}
	tgt := render.FullSiteTarget
	return fmt.Sprintf("return %d %s$request_uri$is_args$args;", st, tgt)
}
