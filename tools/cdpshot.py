import base64, json, os, socket, struct, subprocess, sys, time, urllib.request

CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
PORT = 9333
INJECT_CSS = open(os.environ["DP_CSS"], encoding="utf-8").read() if os.environ.get("DP_CSS") else ""
INJECT_HTML = open(os.environ["DP_HTML"], encoding="utf-8").read() if os.environ.get("DP_HTML") else ""


class WS:
    def __init__(self, url):
        host, rest = url[len("ws://"):].split("/", 1)
        h, p = host.split(":")
        self.s = socket.create_connection((h, int(p)))
        key = base64.b64encode(os.urandom(16)).decode()
        self.s.sendall((f"GET /{rest} HTTP/1.1\r\nHost: {host}\r\nUpgrade: websocket\r\n"
                        f"Connection: Upgrade\r\nSec-WebSocket-Key: {key}\r\nSec-WebSocket-Version: 13\r\n\r\n").encode())
        buf = b""
        while b"\r\n\r\n" not in buf:
            buf += self.s.recv(4096)
        self.id = 0

    def _recvn(self, n):
        b = b""
        while len(b) < n:
            c = self.s.recv(n - len(b))
            if not c:
                raise EOFError
            b += c
        return b

    def send(self, method, **params):
        self.id += 1
        data = json.dumps({"id": self.id, "method": method, "params": params}).encode()
        hdr = bytearray([0x81])
        L = len(data)
        if L < 126:
            hdr.append(0x80 | L)
        elif L < 65536:
            hdr.append(0x80 | 126); hdr += struct.pack(">H", L)
        else:
            hdr.append(0x80 | 127); hdr += struct.pack(">Q", L)
        mask = os.urandom(4)
        hdr += mask
        self.s.sendall(bytes(hdr) + bytes(b ^ mask[i % 4] for i, b in enumerate(data)))
        want = self.id
        while True:
            msg = self.recv()
            if msg.get("id") == want:
                return msg.get("result", msg)

    def recv(self):
        payload = b""
        while True:
            b1, b2 = self._recvn(2)
            L = b2 & 0x7F
            if L == 126:
                L = struct.unpack(">H", self._recvn(2))[0]
            elif L == 127:
                L = struct.unpack(">Q", self._recvn(8))[0]
            payload += self._recvn(L)
            if b1 & 0x80:
                return json.loads(payload)


def main(outdir, jobs):
    prof = os.path.join(os.path.dirname(os.path.abspath(__file__)), "cdp-prof")
    proc = subprocess.Popen([CHROME, "--headless=new", "--disable-gpu", "--hide-scrollbars", "--no-first-run", "--lang=fa",
                             f"--remote-debugging-port={PORT}", f"--user-data-dir={prof}", "about:blank"],
                            stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    try:
        for _ in range(50):
            try:
                tabs = json.load(urllib.request.urlopen(f"http://127.0.0.1:{PORT}/json"))
                break
            except Exception:
                time.sleep(0.2)
        ws = WS([t for t in tabs if t["type"] == "page"][0]["webSocketDebuggerUrl"])
        ws.send("Page.enable")
        for name, width, mobile, url in jobs:
            ws.send("Emulation.setDeviceMetricsOverride", width=width, height=844 if mobile else 900,
                    deviceScaleFactor=2 if mobile else 1, mobile=mobile)
            ws.send("Emulation.setUserAgentOverride", userAgent=(
                "Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36"
                if mobile else "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36"))
            ws.send("Page.navigate", url=url)
            time.sleep(6)
            # scroll through so IntersectionObserver reveals fire
            ws.send("Runtime.evaluate", awaitPromise=True, expression=
                    "(async()=>{for(let y=0;y<document.documentElement.scrollHeight;y+=500){scrollTo(0,y);await new Promise(r=>setTimeout(r,80))}scrollTo(0,0);await new Promise(r=>setTimeout(r,800))})()")
            if INJECT_CSS:
                js = ("(()=>{const s=document.createElement('style');s.id='dp-preview';s.textContent=%s;document.body.appendChild(s);"
                      "if(!document.querySelector('.dsu-header')){const d=document.createElement('div');d.innerHTML=%s;document.body.prepend(d.firstElementChild)}})()") % (json.dumps(INJECT_CSS), json.dumps(INJECT_HTML))
                ws.send("Runtime.evaluate", expression=js)
                time.sleep(1)
            for i, sel in enumerate(filter(None, os.environ.get("DP_SEL", "").split(";;"))):
                if sel.startswith("JS:"):
                    ws.send("Runtime.evaluate", expression=sel[3:]); time.sleep(0.8); continue
                r = ws.send("Runtime.evaluate", returnByValue=True, expression=
                            "(()=>{const e=document.querySelector(%s);if(!e)return null;const r=e.getBoundingClientRect();return [r.left+scrollX,r.top+scrollY,r.width,r.height]})()" % json.dumps(sel))
                v = r.get("result", {}).get("value")
                if not v: print("missing", sel); continue
                c = ws.send("Page.captureScreenshot", format="png", captureBeyondViewport=True,
                            clip={"x": max(0, v[0]), "y": max(0, v[1]), "width": min(v[2], width), "height": min(v[3], 2500), "scale": 1})
                q = os.path.join(outdir, "%s-part%d.png" % (name, i)); open(q, "wb").write(base64.b64decode(c["data"])); print(q)
            if os.environ.get("DP_SEL"): continue
            m = ws.send("Page.getLayoutMetrics")
            h = int(m["cssContentSize"]["height"])
            shot = ws.send("Page.captureScreenshot", format="png", captureBeyondViewport=True,
                           clip={"x": 0, "y": 0, "width": width, "height": min(h, 16000), "scale": 1})
            p = os.path.join(outdir, name + ".png")
            open(p, "wb").write(base64.b64decode(shot["data"]))
            print(name, width, "x", h, os.path.getsize(p))
    finally:
        proc.terminate()


if __name__ == "__main__":
    out = sys.argv[1]
    jobs = []
    for spec in sys.argv[2:]:
        name, width, mobile, url = spec.split("|")
        jobs.append((name, int(width), mobile == "m", url))
    main(out, jobs)
