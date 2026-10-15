"""Find a photo for an article's cover: first the site's own photos, then free-license photos from the web.

Usage (run from the repo root):
  python autopost/images.py site <slug> "<english keywords>"   rank the site's photos, write a numbered contact sheet
  python autopost/images.py web <slug> "<english search>"      fetch CC0 / public-domain photos (Openverse, Wikimedia
                                                                Commons), write a numbered contact sheet
Both write autopost/drafts/img/<slug>/<site|web>-sheet.jpg (look at it with the Read tool before choosing) and
<site|web>.json (the candidates). Put the choice in the article JSON as "image": "site-3" or "web-2".
publish.py then fetches the full photo, (for web photos) uploads it to the site with its credit, and builds the cover.

The site's photos are autopost/site-images.json plus every web photo an earlier run uploaded (from log.jsonl).
Web search keeps only CC0 / public-domain photographs; nothing here posts to the site.
"""
import base64, html, json, os, re, subprocess, sys, time, urllib.parse, urllib.request
from contextlib import contextmanager

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(os.path.dirname(HERE), "tools"))
UA = {"User-Agent": "dejbansec-autoposter/1.0 (https://dejbansec.ir; info@dejbansec.ir) python-urllib"}
CATALOG = os.path.join(HERE, "site-images.json")
LOG = os.path.join(HERE, "log.jsonl")
MAX_BYTES = 25_000_000
OK_LICENSES = ("cc0", "pdm", "public domain", "pd")


def img_dir(slug):
    if not re.fullmatch(r"[a-z0-9-]{3,80}", slug):
        raise SystemExit("slug must be lowercase latin words separated by hyphens")
    d = os.path.join(HERE, "drafts", "img", slug)
    os.makedirs(d, exist_ok=True)
    return d


def fetch(url, limit=MAX_BYTES, timeout=60):
    req = urllib.request.Request(url, headers=UA)
    for attempt in range(3):
        try:
            with urllib.request.urlopen(req, timeout=timeout) as r:
                data = r.read(limit + 1)
                if len(data) > limit:
                    raise SystemExit(f"{url} is larger than {limit} bytes")
                return data
        except (urllib.error.URLError, TimeoutError) as e:
            if attempt == 2:
                raise
            time.sleep(2 * (attempt + 1))


def words(text):
    return {w for w in re.findall(r"[a-z0-9]+", text.lower()) if len(w) > 2}


# ---------------------------------------------------------------- headless Chrome (no Pillow on this PC)

@contextmanager
def chrome(port=9361):
    from cdpshot import WS, CHROME
    prof = os.path.join(os.environ.get("TEMP", HERE), f"dejbansec-images-prof-{port}")
    proc = subprocess.Popen([CHROME, "--headless=new", "--disable-gpu", "--hide-scrollbars", "--no-first-run",
                             "--allow-file-access-from-files", f"--remote-debugging-port={port}", f"--user-data-dir={prof}", "about:blank"],
                            stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    try:
        tabs = None
        for _ in range(120):
            try:
                tabs = json.load(urllib.request.urlopen(f"http://127.0.0.1:{port}/json")); break
            except Exception:
                time.sleep(0.25)
        if tabs is None:
            raise SystemExit("headless Chrome did not start")
        yield WS([t for t in tabs if t["type"] == "page"][0]["webSocketDebuggerUrl"])
    finally:
        proc.terminate()
        try:
            proc.wait(timeout=15)  # let it release the profile before the next start
        except subprocess.TimeoutExpired:
            proc.kill()


def file_url(path):
    return "file:///" + os.path.abspath(path).replace("\\", "/")


def _wait_images(ws):
    for _ in range(60):
        time.sleep(0.5)
        r = ws.send("Runtime.evaluate", expression="[...document.images].every(i=>i.complete)", returnByValue=True)
        if r.get("result", {}).get("value"):
            return


def contact_sheet(tiles, out):
    """tiles: [(label, local image path)] -> numbered JPEG grid, 3 columns of 440x248."""
    cols, tw, th = 3, 440, 248
    cells = "".join(f'<figure><img src="{html.escape(file_url(p))}"><figcaption>{html.escape(label)}</figcaption></figure>'
                    for label, p in tiles)
    rows = (len(tiles) + cols - 1) // cols
    W, H = cols * (tw + 10) + 10, rows * (th + 40) + 10
    page = (f"<html><body style='margin:0;background:#1b1b1b;font:600 18px sans-serif;color:#fff'>"
            f"<div style='display:grid;grid-template-columns:repeat({cols},{tw}px);gap:10px;padding:10px'>{cells}</div>"
            f"<style>figure{{margin:0}}img{{width:{tw}px;height:{th}px;object-fit:cover;display:block;background:#333}}"
            "figcaption{height:30px;line-height:30px;overflow:hidden;white-space:nowrap}</style></body></html>")
    tmp = os.path.join(os.path.dirname(out), "_sheet.html")
    open(tmp, "w", encoding="utf-8").write(page)
    with chrome() as ws:
        ws.send("Emulation.setDeviceMetricsOverride", width=W, height=H, deviceScaleFactor=1, mobile=False)
        ws.send("Page.navigate", url=file_url(tmp))
        _wait_images(ws)
        shot = ws.send("Page.captureScreenshot", format="jpeg", quality=72, clip={"x": 0, "y": 0, "width": W, "height": H, "scale": 1})
    open(out, "wb").write(base64.b64decode(shot["data"]))
    os.remove(tmp)


def to_webp(src, dst, max_w=1200, budget=150_000):
    """Re-encode a local image as WebP (max width 1200, under the byte budget). Returns (width, height, bytes)."""
    with chrome(port=9362) as ws:
        ws.send("Page.navigate", url=file_url(src))
        time.sleep(1.5)
        js = """(async()=>{const i=document.images[0];await i.decode();const w=Math.min(%d,i.naturalWidth),h=Math.round(i.naturalHeight*w/i.naturalWidth);
        const c=document.createElement('canvas');c.width=w;c.height=h;c.getContext('2d').drawImage(i,0,0,w,h);
        for(const q of [0.8,0.72,0.62,0.52,0.42]){const d=c.toDataURL('image/webp',q);if(d.length*0.75<%d||q===0.42)return [d,w,h]}})()""" % (max_w, budget)
        r = ws.send("Runtime.evaluate", expression=js, awaitPromise=True, returnByValue=True)
    d, w, h = r["result"]["value"]
    data = base64.b64decode(d.split(",", 1)[1])
    open(dst, "wb").write(data)
    return w, h, len(data)


# ---------------------------------------------------------------- site photos

def site_photos():
    items = json.load(open(CATALOG, encoding="utf-8"))
    known = {i["url"] for i in items}
    if os.path.exists(LOG):  # photos earlier runs downloaded and uploaded
        for line in open(LOG, encoding="utf-8"):
            try:
                img = json.loads(line).get("image") or {}
            except ValueError:
                continue
            if img.get("source") == "web" and img.get("url") and img["url"] not in known:
                known.add(img["url"])
                items.append({"id": img.get("media_id"), "url": img["url"], "keywords": img.get("keywords", []),
                              "note": img.get("title", "")})
    return items


def recent_use(last=10):
    """media id / url -> slug, for photos used by the last few articles in log.jsonl."""
    used, entries = {}, []
    if os.path.exists(LOG):
        for line in open(LOG, encoding="utf-8"):
            try:
                entries.append(json.loads(line))
            except ValueError:
                pass
    for e in entries[-last:]:
        img, slug = e.get("image") or {}, e.get("link", "").rstrip("/").rsplit("/", 1)[-1]
        for key in (img.get("media_id"), img.get("url")):
            if key:
                used[key] = slug
    return used


def cmd_site(slug, query):
    d = img_dir(slug)
    q = words(query)
    used = recent_use()
    ranked = sorted(site_photos(), key=lambda i: -len(q & words(" ".join(i["keywords"]) + " " + i.get("note", ""))))
    cands, tiles = [], []
    for n, item in enumerate(ranked[:9], 1):
        score = len(q & words(" ".join(item["keywords"]) + " " + item.get("note", "")))
        by = used.get(item.get("id")) or used.get(item["url"])
        if by:
            item = dict(item, recently_used_by=by)
        thumb = os.path.join(d, f"site-{n}.jpg")
        try:
            open(thumb, "wb").write(fetch(item["url"]))
        except Exception as e:
            print(f"site-{n}: could not download ({e})"); continue
        cands.append(dict(item, pick=f"site-{n}", score=score, thumb=thumb))
        tiles.append((f"site-{n}  (match {score}){'  USED' if by else ''}", thumb))
        print(f"site-{n}  match={score}  {item.get('note', '')}  [{', '.join(item['keywords'][:8])}]"
              + (f"  -- recently used by {by}, avoid" if by else ""))
    json.dump(cands, open(os.path.join(d, "site.json"), "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    sheet = os.path.join(d, "site-sheet.jpg")
    contact_sheet(tiles, sheet)
    print(f"\nLook at {os.path.relpath(sheet, os.path.dirname(HERE))} with the Read tool. Use a site photo only if it clearly "
          "fits the article's subject and was not recently used; otherwise run: python autopost/images.py web <slug> \"<english search>\"")


# ---------------------------------------------------------------- web photos (CC0 / public domain only)

def unsuitable(title):
    """Public-domain dumps include court evidence and news photos that must never illustrate an article."""
    return re.search(r"^EFTA\d|evidence|mugshot|arrest|victim|crime scene|corpse|weapon", title or "", re.I)


def openverse(query, n=20):
    # Openverse's size/aspect filters drop most results, so filter here instead.
    url = "https://api.openverse.org/v1/images/?" + urllib.parse.urlencode({
        "q": query, "license": "cc0,pdm", "mature": "false", "page_size": n})
    out = []
    for r in json.loads(fetch(url)).get("results", []):
        w, h = r.get("width") or 0, r.get("height") or 0
        # Wikimedia refuses Openverse's links to its originals; commons() below searches it directly.
        if w < 1000 or w < h * 1.2 or unsuitable(r.get("title")) or r.get("source") == "wikimedia":
            continue
        out.append({"provider": "openverse:" + (r.get("source") or r.get("provider") or ""), "title": r.get("title") or "",
                    "creator": r.get("creator") or "", "license": (r.get("license") or "").lower(),
                    "license_url": r.get("license_url") or "", "page": r.get("foreign_landing_url") or "",
                    "url": r.get("url"), "thumb": r.get("thumbnail") or r.get("url"),
                    "width": r.get("width"), "height": r.get("height")})
    return out


def commons(query, n=20):
    url = "https://commons.wikimedia.org/w/api.php?" + urllib.parse.urlencode({
        "action": "query", "format": "json", "generator": "search", "gsrsearch": f"{query} filetype:bitmap",
        "gsrnamespace": 6, "gsrlimit": n, "prop": "imageinfo", "iiprop": "url|size|extmetadata|mime",
        "iiurlwidth": 1600})
    out = []
    for p in (json.loads(fetch(url)).get("query", {}).get("pages", {}) or {}).values():
        ii = (p.get("imageinfo") or [{}])[0]
        meta = ii.get("extmetadata", {})
        lic = (meta.get("LicenseShortName", {}).get("value") or "").lower()
        if not any(lic.startswith(x) for x in OK_LICENSES) or ii.get("mime") not in ("image/jpeg", "image/png", "image/webp"):
            continue
        w, h = ii.get("width") or 0, ii.get("height") or 0
        if w < 1000 or w < h * 1.2 or unsuitable(p.get("title")):
            continue
        creator = re.sub(r"<[^>]+>", "", meta.get("Artist", {}).get("value") or "").strip()
        out.append({"provider": "wikimedia", "title": p.get("title", "").replace("File:", ""), "creator": creator[:120],
                    "license": lic, "license_url": meta.get("LicenseUrl", {}).get("value") or "",
                    "page": ii.get("descriptionurl") or "", "url": ii.get("thumburl") or ii.get("url"),
                    "thumb": ii.get("thumburl") or ii.get("url"), "width": w, "height": h})
    return out


def cmd_web(slug, query):
    d = img_dir(slug)
    found = []
    for name, fn in (("openverse", openverse), ("wikimedia", commons)):
        try:
            found += fn(query)
        except Exception as e:
            print(f"{name} search failed: {e}")
    seen, cands, tiles = set(), [], []
    for c in found:
        if not c["url"] or c["url"] in seen:
            continue
        seen.add(c["url"])
        n = len(cands) + 1
        thumb = os.path.join(d, f"web-{n}.jpg")
        try:
            open(thumb, "wb").write(fetch(c["thumb"], limit=8_000_000))
        except Exception as e:
            print(f"skip {c['url']}: {e}"); continue
        c.update(pick=f"web-{n}", thumb=thumb, query=query)
        cands.append(c)
        tiles.append((f"web-{n}  {c['license']}  {c['provider']}", thumb))
        print(f"web-{n}  {c['license']}  {c['provider']}  {c['width']}x{c['height']}  {c['title'][:70]}  {c['page']}")
        if len(cands) == 9:
            break
    json.dump(cands, open(os.path.join(d, "web.json"), "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    if not cands:
        raise SystemExit("no CC0/public-domain photos found; try a simpler English search (2-3 words)")
    sheet = os.path.join(d, "web-sheet.jpg")
    contact_sheet(tiles, sheet)
    print(f"\nLook at {os.path.relpath(sheet, os.path.dirname(HERE))} with the Read tool, then Read the chosen web-<n>.jpg "
          "itself. Choose only a photo with no logo, watermark, brand name, readable text or recognisable face.")


# ---------------------------------------------------------------- used by publish.py

def resolve(slug, pick):
    """Return the candidate dict for "site-3" / "web-2" from the files written by the commands above."""
    m = re.fullmatch(r"(site|web)-(\d+)", pick or "")
    if not m:
        raise SystemExit('"image" must look like "site-3" or "web-2" (from autopost/images.py)')
    path = os.path.join(HERE, "drafts", "img", slug, m.group(1) + ".json")
    if not os.path.exists(path):
        raise SystemExit(f'"image" is {pick} but {os.path.relpath(path, HERE)} is missing; run autopost/images.py {m.group(1)} {slug} "..." first')
    for c in json.load(open(path, encoding="utf-8")):
        if c["pick"] == pick:
            c["source"] = m.group(1)
            return c
    raise SystemExit(f"{pick} is not in {path}")


def prepare(slug, cand):
    """Download the chosen photo at full size and re-encode it for the site. Returns the local WebP path and its size."""
    d = img_dir(slug)
    data = fetch(cand["url"])
    ext = "jpg" if data[:2] == b"\xff\xd8" else "png" if data[:4] == b"\x89PNG" else "webp" if data[8:12] == b"WEBP" else None
    if not ext:
        raise SystemExit(f"{cand['url']} is not a JPEG, PNG or WebP image")
    raw = os.path.join(d, "photo-original." + ext)
    open(raw, "wb").write(data)
    out = os.path.join(d, "photo.webp")
    w, h, n = to_webp(raw, out)
    return out, w, h, n


if __name__ == "__main__":
    sys.stdout.reconfigure(encoding="utf-8")
    if len(sys.argv) != 4 or sys.argv[1] not in ("site", "web"):
        raise SystemExit(__doc__)
    {"site": cmd_site, "web": cmd_web}[sys.argv[1]](sys.argv[2], sys.argv[3])
