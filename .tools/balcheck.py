import sys, re

def strip_and_count(src):
    out=[]; i=0; n=len(src); state=None
    heredoc_end=None
    while i<n:
        c=src[i]; nxt=src[i+1] if i+1<n else ''
        if state is None:
            if c=='/' and nxt=='/': state='line'; i+=2; continue
            if c=='#': state='line'; i+=1; continue
            if c=='/' and nxt=='*': state='block'; i+=2; continue
            if c=="'": state='sq'; i+=1; continue
            if c=='"': state='dq'; i+=1; continue
            if c=='<' and src[i:i+3]=='<<<':
                m=re.match(r"<<<\s*['\"]?(\w+)['\"]?", src[i:])
                if m: state='heredoc'; heredoc_end=m.group(1); i+=m.end(); continue
            out.append(c); i+=1; continue
        if state=='line':
            if c=='\n': state=None; out.append(c)
            i+=1; continue
        if state=='block':
            if c=='*' and nxt=='/': state=None; i+=2; continue
            i+=1; continue
        if state=='sq':
            if c=='\\': i+=2; continue
            if c=="'": state=None
            i+=1; continue
        if state=='dq':
            if c=='\\': i+=2; continue
            if c=='"': state=None
            i+=1; continue
        if state=='heredoc':
            if c=='\n':
                j=i+1
                m=re.match(r'\s*'+re.escape(heredoc_end)+r'\s*;?', src[j:j+len(heredoc_end)+8])
                if m: state=None; i=j+m.end(); continue
            i+=1; continue
    s=''.join(out)
    pairs={'{':'}','(':')','[':']'}
    stack=[]; ok=True; err=''
    for ch in s:
        if ch in pairs: stack.append(pairs[ch])
        elif ch in '}])':
            if not stack or stack.pop()!=ch: ok=False; err='mismatch '+ch; break
    if stack: ok=False; err='unclosed'
    return ok,err

bad=0
for f in sys.argv[1:]:
    src=open(f,encoding='utf-8').read()
    ok,err=strip_and_count(src)
    if not ok: print('UNBALANCED:',f,err); bad=1
print('ALL_BALANCED' if not bad else 'FAILURES_FOUND')
sys.exit(bad)
