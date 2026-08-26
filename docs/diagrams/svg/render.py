import asyncio
import glob
import os
from playwright.async_api import async_playwright

async def render_mermaid(page, mmd_code):
    escaped = mmd_code.replace('`', '\\`').replace('\n', '\\n')
    html = f'''<!DOCTYPE html><html><body>
<script src="https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js"></script>
<script>
mermaid.initialize({{startOnLoad:false, theme:"default", securityLevel:"loose"}});
(async() => {{
  try {{
    const {{svg}} = await mermaid.render("d", `{escaped}`);
    document.body.innerHTML = svg;
  }} catch(e) {{
    document.body.innerHTML = "ERROR: " + e.message;
  }}
}})();
</script></body></html>'''

    await page.goto(f'data:text/html;charset=utf-8,{html}', wait_until='load')
    await page.wait_for_selector('svg', timeout=30000)
    return await page.evaluate('''() => {
        const el = document.querySelector('svg');
        el.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        return el.outerHTML;
    }''')

async def main():
    mmds = sorted(glob.glob('docs/diagrams/svg/*.mmd'))
    failures = []
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        page = await browser.new_page(viewport={'width': 1920, 'height': 1080})
        # warm up mermaid CDN cache
        try:
            await render_mermaid(page, 'graph TB\nA["warmup"]')
        except Exception:
            pass
        for mmd_path in mmds:
            name = os.path.splitext(os.path.basename(mmd_path))[0]
            for attempt in range(2):
                try:
                    with open(mmd_path) as f:
                        mmd = f.read()
                    svg = await render_mermaid(page, mmd)
                    out = mmd_path[:-4] + '.svg'
                    with open(out, 'w') as f:
                        f.write(svg)
                    print(f'OK {name}.svg ({len(svg)/1024:.1f} KB)', flush=True)
                    break
                except Exception as e:
                    print(f'FAIL attempt {attempt+1} {name}: {e}', flush=True)
                    if attempt == 1:
                        failures.append(name)
        await browser.close()
    print('FAILURES:', failures)

asyncio.run(main())
