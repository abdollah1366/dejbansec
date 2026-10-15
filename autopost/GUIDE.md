# راهنمای نگارش مقاله‌های خودکار دژبان‌سک

The daily routine follows this file. Write in Persian for an Iranian audience: site owners, developers, IT staff and everyday users who care about security.

## 1. Choosing topics

- Each run publishes **2 articles on 2 different pillars** from `topics.json`. Pick the two pillars used least recently according to `log.jsonl` (ties: follow the order in `topics.json`).
- Before choosing an angle, list existing posts (`GET https://dejbansec.ir/wp-json/wp/v2/posts?per_page=100&_fields=title,slug`) and the last 60 lines of `log.jsonl`. Do **not** repeat a subject that already exists; a new angle on an old subject is fine only if it is clearly different.
- Make it **current**: search the web for news, advisories and changes from the last 30–60 days on that pillar (new CVEs, OS/browser releases, new scams, new standards, OWASP/NIST/CISA updates). Prefer an angle triggered by something recent, explained in practical terms.

## 2. Research and accuracy

- Read at least 3 reputable sources per article: vendor security advisories and docs (Google, Apple, Microsoft, WordPress.org, Cloudflare, AWS…), NIST, CISA, OWASP, MITRE/CVE, ENISA, CERT/FIRST, well-known security press (BleepingComputer, The Hacker News, Krebs, SecurityWeek), academic or vendor research.
- Facts, numbers, versions and dates must come from those sources. If a claim can't be verified, leave it out.
- **Write everything in your own words.** Never copy sentences or structure from a source or from the reference article; at most one short quotation per article, in quotation marks, with attribution.
- **Defensive only.** Explain risks, warning signs, detection, hardening and safe practice. Never include exploit code, attack step-by-steps, malware, ways to bypass protections or anything that helps someone attack systems they don't own.

## 3. Article structure (modelled on the reference article's layout, not its text)

1. **Title**: a clear question or promise with a number hook, ≤ 75 characters. Examples of shape: «چگونه بفهمیم …؟ + ۸ نشانه و راه‌حل», «۱۰ اشتباه رایج در … و راه جلوگیری».
2. **Intro**: 2 short paragraphs: why this matters now (mention the recent trigger), and what the reader will learn.
3. **Summary box**: `<div class="dp-summary"><strong>خلاصه:</strong> …</div>` with 2–3 sentences.
4. **Main sections**: 3–5 `<h2>` sections. Inside them, `<h3>` items for each sign / step / mistake, each with 1–2 paragraphs that are concrete and practical. Use `<ol>` for procedures and `<ul>` for checklists. One `<table>` is welcome when comparing options.
5. **«چه زمانی از متخصص کمک بگیریم؟»**: an `<h2>` with a short paragraph and the call to action: `<div class="dp-cta">…<a href="https://dejbansec.ir/#contact">درخواست مشاوره از دژبان‌سک</a></div>`. Link the most relevant service page:
   - امنیت سایبری: `https://dejbansec.ir/%d8%a7%d9%85%d9%86%db%8c%d8%aa-%d8%b3%d8%a7%db%8c%d8%a8%d8%b1%db%8c/`
   - طراحی و توسعه وب: `https://dejbansec.ir/%d8%b7%d8%b1%d8%a7%d8%ad%db%8c-%d9%88-%d8%aa%d9%88%d8%b3%d8%b9%d9%87-%d9%88%d8%a8/`
   - توسعه نرم‌افزار: `https://dejbansec.ir/%d8%aa%d9%88%d8%b3%d8%b9%d9%87-%d9%86%d8%b1%d9%85-%d8%a7%d9%81%d8%b2%d8%a7%d8%b1/`
   - API و اتوماسیون: `https://dejbansec.ir/api-%d9%88-%d8%a7%d8%aa%d9%88%d9%85%d8%a7%d8%b3%db%8c%d9%88%d9%86/`
   - امنیت زیرساخت: `https://dejbansec.ir/%d8%a7%d9%85%d9%86%db%8c%d8%aa-%d8%b2%db%8c%d8%b1%d8%b3%d8%a7%d8%ae%d8%aa/`
   - سرعت و بهینه‌سازی: `https://dejbansec.ir/%d8%b3%d8%b1%d8%b9%d8%aa-%d9%88-%d8%a8%d9%87%db%8c%d9%86%d9%87-%d8%b3%d8%a7%d8%b2%db%8c/`
6. **«سؤالات متداول»**: `<h2>` and then 3–4 `<h3>` questions, each followed by a `<p>` answer.
7. **«جمع‌بندی»**: `<h2>` and one paragraph.
8. **Internal links**: within the body, link 1–2 existing related dejbansec.ir posts naturally.
9. **«منابع»**: `<h2>منابع</h2><ul class="dp-sources">` with each source as `<li><a href="URL" rel="nofollow noopener" target="_blank">title, publisher, date</a></li>`.

Length: **1300–2000 words**. Persian: formal-but-friendly, short sentences, correct نیم‌فاصله (e.g. می‌شود، داده‌ها، رمزگذاری‌شده), Persian digits in prose. Keep technical terms in English when that's what readers search for (e.g. «احراز هویت دومرحله‌ای (2FA)»).

## 4. HTML rules

- Plain semantic HTML only: `h2 h3 p ul ol li strong em a table thead tbody tr th td blockquote code div.dp-summary div.dp-cta ul.dp-sources`.
- No `<h1>` (the theme prints the title), no `<img>`, no `<script>`, `<style>`, `<iframe>`, no inline styles, no external embeds. Images: the publisher makes a branded cover from the photo you choose (section 6), which becomes the featured image.

## 6. Cover photo

Every article gets a real photo that fits its subject, shown darkened under the branded cover. Choose it after the article is written:

1. Pick 2–3 concrete **English** words for what the photo should show (e.g. `router network cables`, `smartphone phone call`, `server room`, `padlock keyboard`).
2. Run `python autopost/images.py site <slug> "<words>"` and look at `autopost/drafts/img/<slug>/site-sheet.jpg` with the Read tool. Use a site photo only if it **clearly** fits the subject and is not marked as recently used.
3. Otherwise run `python autopost/images.py web <slug> "<words>"` (CC0 / public-domain photos only; the script already filters licenses). Look at `web-sheet.jpg`, then Read the chosen `web-<n>.jpg` itself at full size. It must have **no logo, watermark, brand name, readable text or recognisable face**, and must not be a news, military, police or court photo. If none fits, try up to two other searches with simpler words.
4. If nothing fits after that, use the closest site photo; leave `image` out only if there is truly nothing usable (the cover then falls back to the plain branded design).

Add to the article JSON: `"image": "site-<n>"` or `"web-<n>"`, `"image_alt"` (a short Persian description of the photo) and, for web photos, `"image_keywords"` (3–8 English words describing the photo; they let later articles find it on the site). publish.py downloads the photo, uploads web photos to the site with their creator, license and source in the caption, builds the cover from it and records it in `log.jsonl`.

## 5. Output file (after choosing the photo in section 6)

Save each article as `autopost/drafts/YYYY-MM-DD-<slug>.json` (UTF-8):

```json
{
  "topic": "pillar id from topics.json",
  "title": "…",
  "slug": "english-words-with-hyphens",
  "excerpt": "1–2 sentence Persian summary shown on cards (max 220 characters)",
  "category": "one of: امنیت | امنیت وب | امنیت شبکه | امنیت ابری | تست نفوذ | لینوکس و سرور | طراحی سایت | نرم‌افزار | وردپرس | آموزش امنیت | زیرساخت",
  "tags": ["3–6 Persian or English tags"],
  "cover_kicker": "2–3 word label on the cover, e.g. امنیت موبایل",
  "cover_title": "optional shorter title for the cover (≤ 60 characters)",
  "image": "site-2 or web-4 (from autopost/images.py, see section 6)",
  "image_alt": "short Persian description of the photo",
  "image_keywords": ["english", "words", "describing", "the", "photo"],
  "content_html": "…",
  "sources": ["https://…", "https://…"]
}
```

Then publish with `python autopost/publish.py autopost/drafts/<file>.json`. The script validates the file, fetches the photo, makes the cover, uploads them and publishes the post, then appends to `log.jsonl`. If it refuses (too short, bad slug, duplicate), fix the JSON and run it again.
