"""Run PHP syntax checks, disposable-database regressions and real HTTP smoke tests."""
from pathlib import Path
import http.cookiejar
import json
import os
import re
import socket
import subprocess
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT=Path(__file__).resolve().parent.parent
PHP=os.environ.get('PHP_BIN', r'C:\xampp\php\php.exe' if os.name=='nt' else 'php')
def php(*args):
    return subprocess.run([PHP,*args],cwd=ROOT,text=True,capture_output=True,check=True)

if '--skip-syntax' not in __import__('sys').argv:
    files=[ROOT/'index.php', *sorted((ROOT/'includes').rglob('*.php')), *sorted((ROOT/'public').rglob('*.php')), ROOT/'tests/integration.php']
    for file in files: php('-l',str(file))
    print(f'PASS: syntax checks ({len(files)} PHP files)',flush=True)
result=php('tests/integration.php','--keep')
fixture=json.loads(result.stdout)
print(f"PASS: database regressions ({fixture['checks']} checks)",flush=True)
database=fixture['database']
server=None
log=None
test_document=None
try:
    with socket.socket() as sock:
        sock.bind(('127.0.0.1',0))
        port=sock.getsockname()[1]
    env={**os.environ,'DB_NAME':database}
    log=open(ROOT/'tests/server-test.log','w',encoding='utf-8')
    server=subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t','public'],cwd=ROOT,env=env,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
    base=f'http://127.0.0.1:{port}/'
    opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    count=0
    def request(path,data=None,expected=200,headers=None):
        global count
        payload=data if isinstance(data,bytes) else (urllib.parse.urlencode(data).encode() if data is not None else None)
        try:
            response=opener.open(urllib.request.Request(base+path,data=payload,headers=headers or {}),timeout=15)
        except urllib.error.HTTPError as error:
            response=error
        body=response.read().decode('utf-8',errors='replace')
        assert response.code==expected,(path,response.code,body[:300])
        assert 'Fatal error' not in body and 'Warning:' not in body,(path,body[:500])
        count+=1
        return body,response.geturl()
    for attempt in range(40):
        try:
            with socket.create_connection(('127.0.0.1',port),timeout=.25): break
        except OSError: time.sleep(.1)
    request('index.php')
    request('rooms.php?q=Nyali')
    body,_=request('rooms.php?q=not-a-real-place')
    assert 'No stays match' in body
    request('room.php?id=1')
    request('room_availability.php?id=1')
    request('login.php',{'username':'admin','password':'admin123'},403)
    def login(username,password):
        body,_=request('login.php')
        token=re.search(r'name="csrf_token" value="([^"]+)"',body).group(1)
        return request('login.php',{'username':username,'password':password,'csrf_token':token})
    body,_=login('disabled','password123')
    assert 'Invalid username or password' in body
    body,url=login('user','123')
    assert url.endswith('index.php')
    body,_=request('my_bookings.php')
    request('profile.php')
    token=re.search(r'name="csrf_token" value="([^"]+)"',request('profile.php')[0]).group(1)
    request('profile_update.php',{'csrf_token':token,'full_name':'Test Tenant','username':'user','email':'user@example.test','phone':'0712345678'})
    assert 'Test Tenant' in request('profile.php')[0]
    body,_=request('logout.php')
    token=re.search(r'name="csrf_token" value="([^"]+)"',body).group(1)
    request('logout.php',{'csrf_token':token})
    body,url=login('host','123')
    assert url.endswith('host/index.php')
    for path in ['host/rooms.php','host/bookings.php','host/clients.php','host/calendar.php','host/report_bookings.php','host/room_view.php?id=1','host/room_gallery.php?id=1','host/room_gallery_upload.php?id=1','host/availability.php?room_id=1']:
        request(path)
    body,_=request('logout.php')
    token=re.search(r'name="csrf_token" value="([^"]+)"',body).group(1)
    request('logout.php',{'csrf_token':token})
    body,url=login('admin','admin123')
    assert url.endswith('admin/index.php')
    for path in ['admin/rooms.php','admin/bookings.php','admin/clients.php','admin/calendar.php','admin/report_bookings.php','admin/room_view.php?id=1','admin/room_edit.php?id=1']:
        request(path)
    request('document.php?file=../config.local.php',expected=404)
    # HTTP booking validates CSRF and resolves to a tenant-owned pending request.
    body,_=request('logout.php')
    token=re.search(r'name="csrf_token" value="([^"]+)"',body).group(1)
    request('logout.php',{'csrf_token':token})
    login('user','123')
    body,_=request('room.php?id=2')
    token=re.search(r'name="csrf_token" value="([^"]+)"',body).group(1)
    import datetime
    cin=(datetime.date.today()+datetime.timedelta(days=50)).isoformat()
    cout=(datetime.date.today()+datetime.timedelta(days=52)).isoformat()
    body,url=request('book.php',{'csrf_token':token,'room_id':2,'checkin':cin,'checkout':cout,'guests':2,'contact_phone':'0712345678'})
    assert 'Booking Request Received' in body and 'Pending' in body and 'booking_success.php?ref=' in url
    request('book.php',expected=405)
    assert 'Choose valid future' in request('rooms.php?checkin=2027-02-30&checkout=2027-03-02')[0]
    # Validate registration uploads and document authorization with generated test data.
    body,_=request('logout.php')
    token=re.search(r'name="csrf_token" value="([^"]+)"',body).group(1)
    request('logout.php',{'csrf_token':token})
    def register(file_bytes):
        body,_=request('register.php')
        token=re.search(r'name="csrf_token" value="([^"]+)"',body).group(1)
        a,b=map(int,re.search(r'Captcha:\s*(\d+)\s*\+\s*(\d+)',body).groups())
        values={'csrf_token':token,'full_name':'New Test Tenant','username':'new-test','email':'new-test@example.test','phone':'0712345678','role':'tenant','password':'test-password-123','confirm_password':'test-password-123','captcha':str(a+b)}
        boundary='AzuriteTestBoundary'
        chunks=[]
        for key,value in values.items():
            chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
        chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="id_card"; filename="test.png"\r\nContent-Type: image/png\r\n\r\n'.encode()+file_bytes+b'\r\n')
        chunks.append(f'--{boundary}--\r\n'.encode())
        return request('register.php',b''.join(chunks),headers={'Content-Type':f'multipart/form-data; boundary={boundary}'})
    body,_=register(b'<?php echo "not an image"; ?>')
    assert 'Choose a JPG or PNG or PDF file' in body
    import base64
    image=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6x9sAAAAASUVORK5CYII=')
    body,url=register(image)
    assert url.endswith('login.php') and 'Account created successfully' in body
    login('new-test','test-password-123')
    body,_=request('profile.php')
    test_document=re.search(r'document.php\?file=([a-f0-9]{40}\.png)',body).group(1)
    request('document.php?file='+test_document)
    token=re.search(r'name="csrf_token" value="([^"]+)"',body).group(1)
    request('profile_update.php',{'csrf_token':token,'full_name':'New Test Tenant','username':'new-test','email':'new-test@example.test','phone':'0712345678','current_password':'test-password-123','new_password':'changed-password-123','confirm_password':'changed-password-123'})
    body,_=request('logout.php')
    token=re.search(r'name="csrf_token" value="([^"]+)"',body).group(1)
    request('logout.php',{'csrf_token':token})
    login('new-test','changed-password-123')
    body,_=request('logout.php')
    token=re.search(r'name="csrf_token" value="([^"]+)"',body).group(1)
    request('logout.php',{'csrf_token':token})
    login('host','123')
    request('document.php?file='+test_document,expected=404)
    print(f'PASS: HTTP smoke tests ({count} requests)',flush=True)
finally:
    if server:
        server.terminate()
        server.wait(timeout=10)
    if log: log.close()
    if test_document: (ROOT/'storage/id_cards'/test_document).unlink(missing_ok=True)
    php('tests/integration.php','--cleanup',database)
