import { writeFileSync, readFileSync } from 'fs';
import { chromium } from 'playwright';

const files = [
  'architecture',
  'request-flow',
  'functional-modules',
  'data-lifecycle',
  'security',
];

for (const name of files) {
  const mmd = readFileSync(`docs/diagrams/svg/${name}.mmd`, 'utf-8');
  const html = `<!DOCTYPE html><html><body>
<script src="/tmp/node_modules/mermaid/dist/mermaid.min.js"></script>
<script>
mermaid.initialize({startOnLoad:false,theme:'default',securityLevel:'loose'});
(async()=>{
  const {svg} = await mermaid.render('diagram',\`${mmd.replace(/`/g,'\\`').replace(/\$/g,'\\$')}\`);
  document.body.innerHTML = svg;
})();
</script></body></html>`;

  const tmpHtml = `/tmp/mermaid-${name}.html`;
  writeFileSync(tmpHtml, html);

  const browser = await chromium.launch();
  const page = await browser.newPage();
  await page.goto(`file://${tmpHtml}`, { waitUntil: 'networkidle' });
  await page.waitForSelector('svg', { timeout: 15000 });
  const svg = await page.$eval('svg', el => {
    el.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
    return el.outerHTML;
  });
  await browser.close();

  writeFileSync(`docs/diagrams/svg/${name}.svg`, svg);
  console.log(`✓ ${name}.svg (${(svg.length/1024).toFixed(1)} KB)`);
}
