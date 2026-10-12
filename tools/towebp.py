"""Convert remote images to WebP (max width 1200, under a byte budget) with headless Chrome's canvas."""
import base64, json, os, subprocess, sys, time, urllib.request
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from cdpshot import WS, CHROME

PORT = 9335
out = sys.argv[1]
prof = os.path.join(os.path.dirname(os.path.abspath(__file__)), "cdp-prof-webp")
proc = subprocess.Popen([CHROME, "--headless=new", "--disable-gpu", "--no-first-run", f"--remote-debugging-port={PORT}",
                         f"--user-data-dir={prof}", "about:blank"], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
try:
    for _ in range(50):
        try:
            tabs = json.load(urllib.request.urlopen(f"http://127.0.0.1:{PORT}/json")); break
        except Exception:
            time.sleep(0.2)
    ws = WS([t for t in tabs if t["type"] == "page"][0]["webSocketDebuggerUrl"])
    for url in sys.argv[2:]:
        ws.send("Page.navigate", url=url)
        time.sleep(3)
        js = """(async()=>{const i=document.images[0];await i.decode();const w=Math.min(1200,i.naturalWidth),h=Math.round(i.naturalHeight*w/i.naturalWidth);
        const c=document.createElement('canvas');c.width=w;c.height=h;c.getContext('2d').drawImage(i,0,0,w,h);
        for(const q of [0.72,0.62,0.52,0.42]){const d=c.toDataURL('image/webp',q);if(d.length*0.75<150000||q===0.42)return [d,q,w,h]}})()"""
        r = ws.send("Runtime.evaluate", expression=js, awaitPromise=True, returnByValue=True)
        d, q, w, h = r["result"]["value"]
        data = base64.b64decode(d.split(",", 1)[1])
        name = os.path.splitext(url.rsplit("/", 1)[1])[0] + ".webp"
        open(os.path.join(out, name), "wb").write(data)
        print(name, w, "x", h, "q", q, len(data))
finally:
    proc.terminate()
