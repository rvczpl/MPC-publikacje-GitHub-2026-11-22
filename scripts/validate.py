#!/usr/bin/env python3
"""Validate repository source metadata without bootstrapping or executing IPS."""
from pathlib import Path
import argparse, json, re, subprocess, sys, xml.etree.ElementTree as ET

parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--php',help='Path to a compatible PHP CLI executable; enables php -l')
args=parser.parse_args()
root=Path(__file__).resolve().parents[1]
manifest=json.loads((root/'docs/SOURCE-MANIFEST.json').read_text(encoding='utf-8'))
source=root/manifest['kind']/manifest['directory']
errors=[];counts={'json':0,'xml':0,'php':0};secret_findings=[]
patterns={
 'private key':re.compile(r'-----BEGIN (?:RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----'),
 'GitHub token':re.compile(r'\b(?:gh[pousr]_[A-Za-z0-9]{20,}|github_pat_[A-Za-z0-9_]{30,})\b'),
 'API key':re.compile(r'\bsk-(?:proj-)?[A-Za-z0-9_-]{24,}\b'),
 'Discord webhook token':re.compile(r'https?://(?:canary\.|ptb\.)?(?:discord(?:app)?\.com)/api/webhooks/\d+/[A-Za-z0-9_-]+')
}
for item in manifest['source_files']:
 if not (root/item['path']).is_file():errors.append('Missing exported source: '+item['path'])
for path in source.rglob('*'):
 if not path.is_file():continue
 rel=path.relative_to(root).as_posix()
 try:
  if path.suffix=='.json':json.loads(path.read_text(encoding='utf-8-sig'));counts['json']+=1
  elif path.suffix=='.xml':ET.parse(path);counts['xml']+=1
  elif path.suffix=='.php':
   counts['php']+=1
   if args.php:
    result=subprocess.run([args.php,'-l',str(path)],capture_output=True,text=True,encoding='utf-8',errors='replace')
    if result.returncode:errors.append(rel+': PHP syntax error\n'+result.stdout+result.stderr)
  if path.suffix in {'.php','.json','.xml','.js','.html','.txt','.md','.css'}:
   text=path.read_text(encoding='utf-8',errors='replace')
   for name,pattern in patterns.items():
    if pattern.search(text):secret_findings.append(rel+': possible '+name+' (value not printed)')
 except (OSError,ValueError,ET.ParseError) as exc:errors.append(rel+': '+str(exc))
errors+=secret_findings
print(json.dumps({'repository':root.name,'checked':counts,'php_lint':'enabled' if args.php else 'NOT RUN: provide --php','errors':errors},ensure_ascii=False,indent=2))
sys.exit(1 if errors else 0)
