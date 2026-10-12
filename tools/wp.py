"""Tiny WordPress helper. Credentials come from env WP_USER / WP_PASS only."""
import base64, json, os, sys, urllib.request, xmlrpc.client

SITE = "https://dejbansec.ir"
USER, PASS = os.environ["WP_USER"], os.environ["WP_PASS"]
AUTH = "Basic " + base64.b64encode(f"{USER}:{PASS}".encode()).decode()


def rest(method, path, body=None, raw=None, headers=None):
    data = raw if raw is not None else (json.dumps(body).encode() if body is not None else None)
    h = {"Authorization": AUTH, "User-Agent": "Mozilla/5.0 dejbansec-maint"}
    if body is not None:
        h["Content-Type"] = "application/json"
    h.update(headers or {})
    req = urllib.request.Request(SITE + "/wp-json/" + path, data=data, method=method, headers=h)
    try:
        with urllib.request.urlopen(req) as r:
            return json.loads(r.read().decode() or "null")
    except urllib.error.HTTPError as e:
        raise SystemExit(f"{method} {path} -> {e.code}: {e.read().decode()[:500]}")


def xrpc():
    return xmlrpc.client.ServerProxy(SITE + "/xmlrpc.php")


def mini_css(path):
    css = open(path, encoding="utf-8").read()
    out = []
    for line in css.splitlines():
        line = line.strip()
        if line:
            out.append(line)
    s = "".join(out)
    import re
    s = re.sub(r"/\*.*?\*/", "", s)
    return s


def path(*parts):
    return os.path.join(ROOT, *parts)


ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
TEMPLATES = (6568, 6569, 6489)  # Impreza content templates: pages, posts, archives


def original_template(tid):
    return open(path("customizations", "impreza-templates", "original", f"template-{tid}-original.txt"), encoding="utf-8").read()


if __name__ == "__main__":
    cmd = sys.argv[1]
    css = mini_css(path("customizations", "design-patch.css"))
    head = open(path("customizations", "inner-header.html"), encoding="utf-8").read().strip()
    js = open(path("customizations", "card-thumbnails.js"), encoding="utf-8").read().strip()
    style = '<style id="dp-patch">' + css + "</style>"
    script = '<script id="dp-thumbs">' + js + "</script>"
    if cmd == "apply-home":
        r = rest("POST", "wp/v2/pages/8492", {"content": style + script + "\n[dejbansec_ultimate_home]"})
        print("home updated", r["id"], r["modified"], len(r["content"]["raw"]))
    elif cmd == "apply-template":
        row = ('[vc_row height="auto" width="full" el_class="dp-head-row"][vc_column][vc_column_text]'
               + style + script + head + "[/vc_column_text][/vc_column][/vc_row]")
        for tid in TEMPLATES:
            orig = original_template(tid)
            if tid == 6489:
                orig = orig.replace('columns="1" items_gap="5rem"', 'columns="3" items_gap="1.5rem"')
            print(f"template {tid} updated", xrpc().wp.editPost(1, USER, PASS, tid, {"post_content": row + orig}))
    elif cmd == "undo-home":
        print(rest("POST", "wp/v2/pages/8492", {"content": "[dejbansec_ultimate_home]"})["modified"])
    elif cmd == "undo-template":
        for tid in TEMPLATES:
            print(f"template {tid} restored", xrpc().wp.editPost(1, USER, PASS, tid, {"post_content": original_template(tid)}))
