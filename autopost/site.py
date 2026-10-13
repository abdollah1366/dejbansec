"""Read-only checks against dejbansec.ir for the daily routine (no login, no writes).

Usage:
  python autopost/site.py posts            list existing posts: date | slug | title
  python autopost/site.py verify <slug>    check a post is live (HTTP 200) and has a featured image
  python autopost/site.py check <url>      HTTP status of a dejbansec.ir page (e.g. an internal link)
"""
import json, sys, urllib.error, urllib.parse, urllib.request

SITE = "https://dejbansec.ir"
UA = {"User-Agent": "Mozilla/5.0 dejbansec-autoposter"}


def get(url):
    with urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=60) as r:
        return r.status, r.read()


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    cmd = sys.argv[1] if len(sys.argv) > 1 else ""
    if cmd == "posts":
        _, body = get(SITE + "/wp-json/wp/v2/posts?per_page=100&_fields=date,slug,title")
        for p in json.loads(body):
            print(p["date"][:10], "|", urllib.parse.unquote(p["slug"]), "|", p["title"]["rendered"], "|", SITE + "/" + p["slug"] + "/")
    elif cmd == "verify" and len(sys.argv) == 3:
        _, body = get(SITE + "/wp-json/wp/v2/posts?" + urllib.parse.urlencode({"slug": urllib.parse.unquote(sys.argv[2]), "_fields": "id,link,featured_media,status"}))
        posts = json.loads(body)
        if not posts:
            raise SystemExit(f"FAIL no published post with slug {sys.argv[2]}")
        p = posts[0]
        status, _ = get(p["link"])
        _, media = get(f"{SITE}/wp-json/wp/v2/media/{p['featured_media']}?_fields=source_url") if p["featured_media"] else (0, b"{}")
        img = json.loads(media).get("source_url")
        ok = status == 200 and bool(img)
        print("OK" if ok else "FAIL", p["id"], p["link"], "http", status, "image", img)
        sys.exit(0 if ok else 1)
    elif cmd == "check" and len(sys.argv) == 3:
        url = sys.argv[2]
        if not url.startswith(SITE + "/"):
            raise SystemExit("only https://dejbansec.ir/ URLs can be checked")
        try:
            status, _ = get(url)
        except urllib.error.HTTPError as e:
            status = e.code
        print("OK" if status == 200 else "FAIL", status, url)
        sys.exit(0 if status == 200 else 1)
    else:
        raise SystemExit(__doc__)


if __name__ == "__main__":
    main()
