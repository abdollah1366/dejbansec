"""Load pages in headless Chrome and list every resource host that is not dejbansec.ir."""
import json, os, subprocess, sys, time, urllib.request
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from cdpshot import WS, CHROME

PORT = 9334
prof = os.path.join(os.path.dirname(os.path.abspath(__file__)), "cdp-prof-net")
proc = subprocess.Popen([CHROME, "--headless=new", "--disable-gpu", "--no-first-run", f"--remote-debugging-port={PORT}",
                         f"--user-data-dir={prof}", "about:blank"], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
try:
    for _ in range(50):
        try:
            tabs = json.load(urllib.request.urlopen(f"http://127.0.0.1:{PORT}/json")); break
        except Exception:
            time.sleep(0.2)
    ws = WS([t for t in tabs if t["type"] == "page"][0]["webSocketDebuggerUrl"])
    ws.send("Network.setCacheDisabled", cacheDisabled=True)
    for mobile in (False, True):
        ws.send("Emulation.setDeviceMetricsOverride", width=390 if mobile else 1440, height=844, deviceScaleFactor=1, mobile=mobile)
        for url in sys.argv[1:]:
            ws.send("Page.navigate", url=url)
            time.sleep(6)
            ws.send("Runtime.evaluate", awaitPromise=True, expression=
                    "(async()=>{for(let y=0;y<document.documentElement.scrollHeight;y+=600){scrollTo(0,y);await new Promise(r=>setTimeout(r,60))}})()")
            time.sleep(2)
            r = ws.send("Runtime.evaluate", returnByValue=True, expression=
                        "JSON.stringify({res:performance.getEntriesByType('resource').map(e=>e.name),"
                        "imgs:[...document.images].map(i=>i.currentSrc||i.src),"
                        "broken:[...document.images].filter(i=>i.complete&&!i.naturalWidth).map(i=>i.src),"
                        "bg:[...document.querySelectorAll('*')].map(e=>getComputedStyle(e).backgroundImage).filter(b=>b.includes('url('))})")
            d = json.loads(r["result"]["value"])
            ext = sorted({u for u in d["res"] + d["imgs"] + d["bg"] if "http" in u and "dejbansec.ir" not in u.split("/")[2 if u.startswith("http") else 0][:60] and not u.startswith("data:")})
            ext = sorted({u for u in ext if not u.startswith("url(\"https://dejbansec.ir")})
            print(("M " if mobile else "D ") + url[:70], "resources:", len(d["res"]), "external:", len(ext), "broken:", d["broken"])
            for u in ext:
                print("    ", u[:140])
finally:
    proc.terminate()
