#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Translate ads-php docs to Portuguese (.pt.md) per scripts/translation-spec.md.

Line-based: code blocks verbatim, language-switch lines verbatim, lines with
CJK translated via exact-line dicts in pt_data_*.py, everything else verbatim.
Reports any CJK line without a dict entry and any internal link missing a
language suffix.
"""
import glob, os, re, sys

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.join(HERE, '..')

LANGS = ('en', 'ko', 'ru', 'de', 'fr', 'es', 'pt', 'hi', 'ar', 'bn', 'id', 'ja')
LANG_SUFFIX = re.compile(r'\.(' + '|'.join(LANGS) + r')\.(md|svg)$')
HAS_SUFFIX = re.compile(r'\.(' + '|'.join(LANGS) + r')\.(md|svg)$')
EN_SOURCE = (
    'docs/skills/adapter-generator.md', 'docs/skills/admin-page-generator.md',
    'docs/skills/api-endpoint.md', 'docs/skills/erik-stack.md',
    'docs/skills/migration-generator.md', 'docs/skills/tdd-workflow.md',
    'docs/superpowers/specs/2026-05-18-flutter-desktop-design.md',
    'docs/superpowers/plans/2026-05-18-flutter-desktop.md',
)

def load_trans():
    trans = {}
    for p in glob.glob(os.path.join(HERE, 'pt_data_*.py')):
        ns = {}
        exec(open(p, encoding='utf-8').read(), ns)
        trans.update(ns['TRANS'])
    return trans

def has_cjk(s):
    return any('一' <= c <= '鿿' for c in s)

def is_switch(s):
    return s.startswith('[中文](') and '| [English]' in s

def translate(src, dst, tdict):
    lines = open(src, encoding='utf-8').read().split('\n')
    out, infence, missing = [], False, []
    for i, raw in enumerate(lines, 1):
        if raw.startswith('```'):
            infence = not infence
            out.append(raw)
            continue
        if infence or is_switch(raw) or not has_cjk(raw):
            out.append(raw)
            continue
        if raw in tdict:
            out.append(tdict[raw])
        else:
            missing.append('%d: %s' % (i, raw))
            out.append(raw)
    open(dst, 'w', encoding='utf-8').write('\n'.join(out) + '\n')
    return missing

def check_links(dst):
    bad = []
    for i, raw in enumerate(open(dst, encoding='utf-8').read().split('\n'), 1):
        if is_switch(raw) or raw.startswith('```'):
            continue
        for m in re.finditer(r'\]\(([^)]+)\)', raw):
            full = m.group(1)
            url = full.split('#')[0]
            if url.startswith(('http', 'mailto')) or not url:
                continue
            if HAS_SUFFIX.search(url):
                continue
            if url.endswith('.md') or url.endswith('.svg'):
                bad.append('%d: %s' % (i, full))
    return bad

def main():
    trans = load_trans()
    missing_any = False
    for src in sorted(glob.glob(os.path.join(ROOT, '**', '*.md'), recursive=True)):
        rel = os.path.relpath(src, ROOT)
        if LANG_SUFFIX.search(rel):
            continue
        dst = rel[:-3] + '.pt.md'
        if rel not in trans:
            # English-source docs among the 34: .pt.md is a verbatim copy (no CJK prose)
            if rel in EN_SOURCE:
                open(dst, 'w', encoding='utf-8').write(open(src, encoding='utf-8').read())
            continue
        miss = translate(src, dst, trans[rel])
        bad = check_links(dst)
        if miss or bad:
            missing_any = True
        print('[%s] %s missing=%d links=%d' % ('OK' if not miss and not bad else 'MISSING', rel, len(miss), len(bad)))
        for m in miss[:30]:
            print('  MISS:', m)
        for b in bad[:10]:
            print('  LINK:', b)
    sys.exit(1 if missing_any else 0)

if __name__ == '__main__':
    main()
