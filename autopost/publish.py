"""Publish one article JSON to dejbansec.ir: cover image -> media, tags, post (publish or draft).

Usage:  python autopost/publish.py autopost/drafts/<file>.json [--draft] [--dry-run]

--dry-run validates the file, makes the cover, checks the WordPress login and the slug with read-only
requests, and stops before anything is uploaded or posted.
At most DAILY_CAP posts (env AUTOPOST_DAILY_CAP, default 2) are published per Tehran calendar day, counted from log.jsonl.

The WordPress application password for user "chat" (named "dejbansec-autoposter") is stored
DPAPI-encrypted in %USERPROFILE%\\.dejbansec\\wp-autoposter.dpapi and decrypted at run time;
it is never written to the repo. WP_USER / WP_PASS env vars override it.
"""
import base64, datetime, json, os, re, subprocess, sys, time, urllib.error, urllib.parse, urllib.request

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(os.path.dirname(HERE), "tools"))
SITE = "https://dejbansec.ir"
LOG = os.path.join(HERE, "log.jsonl")
CATEGORIES = {  # name -> id on the site
    "امنیت": 40, "امنیت وب": 45, "امنیت شبکه": 47, "امنیت ابری": 48, "تست نفوذ": 46,
    "لینوکس و سرور": 49, "طراحی سایت": 42, "نرم‌افزار": 43, "وردپرس": 20, "آموزش امنیت": 50, "زیرساخت": 44,
}
REQUIRED = ("title", "slug", "excerpt", "category", "tags", "content_html", "cover_kicker")
TEHRAN = datetime.timezone(datetime.timedelta(hours=3, minutes=30))  # Iran has no DST since 2022
DAILY_CAP = int(os.environ.get("AUTOPOST_DAILY_CAP", "2"))


def published_today():
    """Posts in log.jsonl published on today's Tehran date (naive dates in old entries are this PC's local time)."""
    today, n = datetime.datetime.now(TEHRAN).date(), 0
    if os.path.exists(LOG):
        for line in open(LOG, encoding="utf-8"):
            try:
                e = json.loads(line)
                d = datetime.datetime.fromisoformat(e["date"])
            except (ValueError, KeyError):
                continue
            if e.get("status") == "publish" and d.astimezone(TEHRAN).date() == today:
                n += 1
    return n


def password():
    if os.environ.get("WP_PASS"):
        return os.environ["WP_PASS"]
    path = os.path.join(os.environ["USERPROFILE"], ".dejbansec", "wp-autoposter.dpapi")
    ps = ("$s=ConvertTo-SecureString (Get-Content -Raw '%s').Trim();"
          "[Runtime.InteropServices.Marshal]::PtrToStringBSTR([Runtime.InteropServices.Marshal]::SecureStringToBSTR($s))") % path
    return subprocess.run(["powershell", "-NoProfile", "-NonInteractive", "-Command", ps],
                          capture_output=True, text=True, check=True).stdout.strip()


USER = os.environ.get("WP_USER", "chat")
AUTH = None


def rest(method, path, body=None, raw=None, headers=None):
    data = raw if raw is not None else (json.dumps(body).encode() if body is not None else None)
    h = {"Authorization": AUTH, "User-Agent": "Mozilla/5.0 dejbansec-autoposter"}
    if body is not None:
        h["Content-Type"] = "application/json"
    h.update(headers or {})
    req = urllib.request.Request(SITE + "/wp-json/" + path, data=data, method=method, headers=h)
    for attempt in range(4):
        try:
            with urllib.request.urlopen(req, timeout=120) as r:
                return json.loads(r.read().decode() or "null")
        except urllib.error.HTTPError as e:
            msg = e.read().decode()[:400]
            if e.code < 500 or attempt == 3:
                raise SystemExit(f"{method} {path} -> {e.code}: {msg}")
        except urllib.error.URLError as e:
            if attempt == 3:
                raise SystemExit(f"{method} {path} -> network error: {e}")
        time.sleep(2 ** (attempt + 1))


def clean_html(html):
    """Keep the article self-contained: no scripts, styles, iframes or images from other hosts."""
    html = re.sub(r"<(script|style|iframe)[^>]*>.*?</\1>", "", html, flags=re.S | re.I)
    html = re.sub(r"<img\b(?![^>]*src=\"https://dejbansec\.ir/)[^>]*>", "", html, flags=re.I)
    html = re.sub(r"<h1\b[^>]*>.*?</h1>", "", html, flags=re.S | re.I)  # the template prints the title
    return html.strip()


def make_cover(title, kicker, out_path):
    from cdpshot import WS, CHROME  # local helper: headless Chrome over CDP
    port = 9341
    prof = os.path.join(os.environ.get("TEMP", HERE), "dejbansec-cover-prof")
    proc = subprocess.Popen([CHROME, "--headless=new", "--disable-gpu", "--hide-scrollbars", "--no-first-run",
                             "--allow-file-access-from-files", f"--remote-debugging-port={port}", f"--user-data-dir={prof}", "about:blank"],
                            stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    try:
        for _ in range(60):
            try:
                tabs = json.load(urllib.request.urlopen(f"http://127.0.0.1:{port}/json")); break
            except Exception:
                time.sleep(0.25)
        ws = WS([t for t in tabs if t["type"] == "page"][0]["webSocketDebuggerUrl"])
        ws.send("Emulation.setDeviceMetricsOverride", width=1200, height=675, deviceScaleFactor=1, mobile=False)
        url = "file:///" + os.path.join(HERE, "cover.html").replace("\\", "/") + "?" + urllib.parse.urlencode({"title": title, "kicker": kicker})
        ws.send("Page.navigate", url=url)
        time.sleep(2.5)
        ws.send("Runtime.evaluate", expression="document.fonts.ready.then(()=>1)", awaitPromise=True)
        for q in (82, 72, 62):
            shot = ws.send("Page.captureScreenshot", format="webp", quality=q, clip={"x": 0, "y": 0, "width": 1200, "height": 675, "scale": 1})
            data = base64.b64decode(shot["data"])
            if len(data) < 150_000:
                break
        open(out_path, "wb").write(data)
        return len(data)
    finally:
        proc.terminate()


def find_or_create_tags(names):
    ids = []
    for n in names[:8]:
        n = n.strip()
        if not n:
            continue
        found = rest("GET", "wp/v2/tags?" + urllib.parse.urlencode({"search": n, "per_page": 20, "_fields": "id,name"}))
        hit = next((t["id"] for t in found if t["name"].strip() == n), None)
        ids.append(hit or rest("POST", "wp/v2/tags", {"name": n})["id"])
    return ids


def main():
    global AUTH
    args = [a for a in sys.argv[1:] if not a.startswith("--")]
    draft, dry = "--draft" in sys.argv, "--dry-run" in sys.argv
    art = json.load(open(args[0], encoding="utf-8"))
    missing = [k for k in REQUIRED if not art.get(k)]
    if missing:
        raise SystemExit(f"missing fields: {missing}")
    if art["category"] not in CATEGORIES:
        raise SystemExit(f"unknown category {art['category']!r}; use one of {list(CATEGORIES)}")
    if not re.fullmatch(r"[a-z0-9-]{3,80}", art["slug"]):
        raise SystemExit("slug must be lowercase latin words separated by hyphens")
    content = clean_html(art["content_html"])
    words = len(re.sub(r"<[^>]+>", " ", content).split())
    if words < 900:
        raise SystemExit(f"article too short ({words} words); expected 1200-2000")

    done = published_today()
    if not (draft or dry) and done >= DAILY_CAP:
        raise SystemExit(f"daily cap reached: {done} posts already published today (Tehran time); not publishing")

    cover = os.path.join(HERE, "drafts", art["slug"] + ".webp")
    size = make_cover(art.get("cover_title") or art["title"], art["cover_kicker"], cover)
    print(f"cover {cover} {size} bytes, {words} words")

    AUTH = "Basic " + base64.b64encode(f"{USER}:{password()}".encode()).decode()
    if rest("GET", f"wp/v2/posts?slug={art['slug']}&status=publish,draft,future&_fields=id"):
        raise SystemExit(f"a post with slug {art['slug']} already exists")
    if dry:
        me = rest("GET", "wp/v2/users/me?context=edit&_fields=id,roles")
        print(f"DRY-RUN OK: login works (user {me['id']}, roles {me.get('roles')}), slug is free, "
              f"{done}/{DAILY_CAP} published today; nothing was uploaded or posted")
        return

    b = "----dejbansecautopost"
    body = (f"--{b}\r\nContent-Disposition: form-data; name=\"file\"; filename=\"{art['slug']}.webp\"\r\n"
            "Content-Type: image/webp\r\n\r\n").encode() + open(cover, "rb").read() + \
           f"\r\n--{b}\r\nContent-Disposition: form-data; name=\"alt_text\"\r\n\r\n{art['title']}\r\n--{b}--\r\n".encode()
    media = rest("POST", "wp/v2/media", raw=body, headers={"Content-Type": f"multipart/form-data; boundary={b}"})

    post = rest("POST", "wp/v2/posts", {
        "title": art["title"], "slug": art["slug"], "excerpt": art["excerpt"], "content": content,
        "status": "draft" if draft else "publish", "categories": [CATEGORIES[art["category"]]],
        "tags": find_or_create_tags(art["tags"]), "featured_media": media["id"],
        "comment_status": "open",
    })
    entry = {"date": datetime.datetime.now().astimezone().isoformat(timespec="seconds"), "id": post["id"], "status": post["status"],
             "link": post["link"], "title": art["title"], "category": art["category"], "topic": art.get("topic", ""),
             "sources": art.get("sources", []), "words": words}
    with open(LOG, "a", encoding="utf-8") as f:
        f.write(json.dumps(entry, ensure_ascii=False) + "\n")
    print("PUBLISHED" if post["status"] == "publish" else "DRAFT", post["id"], post["link"])


if __name__ == "__main__":
    main()
