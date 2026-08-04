import asyncio
from playwright.async_api import async_playwright

FILES = [
    'architecture',
    'request-flow',
    'functional-modules',
    'data-lifecycle',
    'security',
]

async def render_mermaid(name, mmd_code):
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

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        page = await browser.new_page(viewport={'width': 1920, 'height': 1080})
        await page.goto(f'data:text/html,{html}', wait_until='networkidle')
        await page.wait_for_selector('svg', timeout=30000)
        svg = await page.evaluate('''() => {
            const el = document.querySelector('svg');
            el.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
            return el.outerHTML;
        }''')
        await browser.close()
        return svg

async def main():
    for name in FILES:
        with open(f'docs/diagrams/svg/{name}.mmd') as f:
            mmd = f.read()
        svg = await render_mermaid(name, mmd)
        path = f'docs/diagrams/svg/{name}.svg'
        with open(path, 'w') as f:
            f.write(svg)
        print(f'OK {name}.svg ({len(svg)/1024:.1f} KB)')

asyncio.run(main())
