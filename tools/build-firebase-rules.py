"""Generate bounded day checks: Firestore Rules intentionally have no loops."""
from pathlib import Path
root=Path(__file__).resolve().parent.parent
rules='''rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    function signed() { return request.auth != null; }
    function profile() { return get(/databases/$(database)/documents/users/$(request.auth.uid)).data; }
    function active() { return signed() && profile().isActive == true; }
    function admin() { return active() && profile().role == 'admin'; }
    function invitedAdmin() { return signed() && request.auth.token.email_verified == true && exists(/databases/$(database)/documents/adminInvites/$(request.auth.token.email)); }
    function manager(roomId) { return admin() || (active() && profile().role == 'host' && getAfter(/databases/$(database)/documents/rooms/$(roomId)).data.ownerId == request.auth.uid); }
    function reserving(status) { return status in ['Pending','Approved','Confirmed','CheckedIn']; }
    function terminal(status) { return status in ['Declined','Cancelled','Completed']; }
    function tenDays(start, count) { return __TEN_DAYS__; }
    function expectedDays(start, count) {
      return count <= 10 ? tenDays(start, count)
        : count <= 20 ? tenDays(start, 10).concat(tenDays(start + 10, count - 10))
        : tenDays(start, 10).concat(tenDays(start + 10, 10)).concat(tenDays(start + 20, count - 20));
    }
    function validDays(b) {
      return b.startDay is int && b.endDay is int && b.endDay > b.startDay && b.endDay-b.startDay <= 30
        && b.days is list && b.days.size() == b.endDay-b.startDay
        && __DAYS__;
    }
    function ledger(roomId) { return getAfter(/databases/$(database)/documents/availability/$(roomId)).data; }
    function audit(bid, b) {
      let event = getAfter(/databases/$(database)/documents/bookings/$(bid)/events/$(b.lastEventId)).data;
      return event.actorId == request.auth.uid && event.status == b.status && event.createdAt == request.time;
    }
    function bookingShape(b) {
      return b.keys().hasAll(['tenantId','tenantName','tenantEmail','hostId','roomId','roomName','location','nightlyPrice','total','startDay','endDay','days','guests','phone','status','createdAt','updatedAt','lastEventId'])
        && b.keys().hasOnly(['tenantId','tenantName','tenantEmail','hostId','roomId','roomName','location','nightlyPrice','total','startDay','endDay','days','guests','phone','status','createdAt','updatedAt','lastEventId'])
        && b.tenantName is string && b.tenantName.size() <= 150 && b.tenantEmail is string
        && b.phone is string && b.phone.matches('^[+0-9 ()-]{7,30}$')
        && b.guests is int && b.guests >= 1 && b.guests <= 4 && b.lastEventId is string && validDays(b);
    }
    function permittedTransition(before, after, tenant) {
      return (tenant && before in ['Pending','Approved','Confirmed'] && after == 'Cancelled')
        || (!tenant && ((before == 'Pending' && after in ['Approved','Declined','Cancelled'])
        || (before == 'Approved' && after in ['Confirmed','Cancelled'])
        || (before == 'Confirmed' && after in ['CheckedIn','Cancelled'])
        || (before == 'CheckedIn' && after == 'Completed')));
    }
    match /users/{uid} {
      allow get: if signed() && (request.auth.uid == uid || admin());
      allow list: if admin();
      allow create: if signed() && request.auth.uid == uid
        && request.resource.data.keys().hasOnly(['fullName','email','phone','role','isActive','createdAt','updatedAt','avatarPath','identityPath'])
        && request.resource.data.fullName is string && request.resource.data.fullName.size() > 0 && request.resource.data.fullName.size() <= 150
        && request.resource.data.email == request.auth.token.email && request.resource.data.phone is string && request.resource.data.phone.size() <= 30
        && request.resource.data.role in ['tenant','host'] && request.resource.data.isActive == true
        && request.resource.data.createdAt == request.time && request.resource.data.updatedAt == request.time
        && request.resource.data.avatarPath == '' && request.resource.data.identityPath == '';
      allow update: if (signed() && request.auth.uid == uid && active()
        && request.resource.data.diff(resource.data).affectedKeys().hasOnly(['fullName','phone','updatedAt','avatarPath','identityPath'])
        && request.resource.data.fullName is string && request.resource.data.fullName.size() > 0 && request.resource.data.fullName.size() <= 150
        && request.resource.data.phone is string && request.resource.data.phone.size() <= 30
        && request.resource.data.avatarPath is string && request.resource.data.identityPath is string
        && request.resource.data.updatedAt == request.time)
        || (signed() && request.auth.uid == uid && active() && invitedAdmin() && request.resource.data.role == 'admin'
        && request.resource.data.diff(resource.data).affectedKeys().hasOnly(['role','updatedAt']) && request.resource.data.updatedAt == request.time)
        || (admin() && request.resource.data.diff(resource.data).affectedKeys().hasOnly(['role','isActive','updatedAt'])
        && request.resource.data.role in ['admin','host','tenant'] && request.resource.data.isActive is bool && request.resource.data.updatedAt == request.time);
    }
    match /rooms/{roomId} {
      allow read: if resource.data.isActive == true || (active() && (profile().role == 'admin' || (profile().role == 'host' && resource.data.ownerId == request.auth.uid)));
      allow create: if active() && profile().role in ['host','admin'] && request.resource.data.ownerId == request.auth.uid && roomShape(request.resource.data)
        && request.resource.data.createdAt == request.time && request.resource.data.updatedAt == request.time;
      allow update: if manager(roomId) && roomShape(request.resource.data)
        && request.resource.data.diff(resource.data).affectedKeys().hasOnly(['name','location','description','price','category','isActive','photos','updatedAt']) && request.resource.data.updatedAt == request.time;
    }
    function roomShape(r) {
      return r.keys().hasOnly(['name','location','description','price','category','isActive','photos','ownerId','createdAt','updatedAt'])
        && r.name is string && r.name.size() > 0 && r.name.size() <= 150 && r.location is string && r.location.size() <= 150
        && r.description is string && r.description.size() <= 10000 && r.category in ['Apartment','Suite','Villa','Studio','House']
        && r.price is number && r.price > 0 && r.price <= 1000000 && r.isActive is bool && r.photos is list && r.photos.size() <= 10;
    }
    match /availability/{roomId} {
      allow read: if true;
      allow create: if manager(roomId) && request.resource.data.keys().hasOnly(['days','blocks','action','bookingId','day','updatedAt'])
        && request.resource.data.days == {} && request.resource.data.blocks == {} && request.resource.data.action == 'init' && request.resource.data.bookingId == '' && request.resource.data.day == '' && request.resource.data.updatedAt == request.time;
      allow update: if active() && request.resource.data.keys().hasOnly(['days','blocks','action','bookingId','day','updatedAt'])
        && request.resource.data.days is map && request.resource.data.blocks is map && request.resource.data.days.size() <= 20000 && request.resource.data.blocks.size() <= 20000
        && request.resource.data.days.values().toSet().hasOnly([true]) && request.resource.data.blocks.values().toSet().hasOnly([true]) && request.resource.data.updatedAt == request.time
        && ((request.resource.data.action in ['reserve','release'] && bookingLedger(roomId)) || (request.resource.data.action in ['block','unblock'] && hostBlock(roomId)));
    }
    function hostBlock(roomId) {
      let next = request.resource.data;
      return manager(roomId) && next.bookingId == '' && next.day is string
        && int(next.day) >= int((request.time.toMillis() + 10800000) / 86400000)
        && next.days == resource.data.days && !resource.data.days.keys().hasAny([next.day])
        && next.blocks.diff(resource.data.blocks).affectedKeys() == [next.day].toSet()
        && ((next.action == 'block' && !resource.data.blocks.keys().hasAny([next.day]) && next.blocks[next.day] == true)
        || (next.action == 'unblock' && resource.data.blocks.get(next.day, false) == true && !next.blocks.keys().hasAny([next.day])));
    }
    function bookingLedger(roomId) {
      let next = request.resource.data;
      let b = getAfter(/databases/$(database)/documents/bookings/$(next.bookingId)).data;
      return b.roomId == roomId && b.updatedAt == request.time && next.blocks == resource.data.blocks
        && next.days.diff(resource.data.days).affectedKeys() == b.days.toSet()
        && ((next.action == 'reserve' && b.tenantId == request.auth.uid && b.status == 'Pending'
        && !resource.data.days.keys().hasAny(b.days) && !resource.data.blocks.keys().hasAny(b.days) && next.days.keys().hasAll(b.days))
        || (next.action == 'release' && (b.tenantId == request.auth.uid || manager(roomId)) && terminal(b.status)
        && reserving(get(/databases/$(database)/documents/bookings/$(next.bookingId)).data.status)
        && resource.data.days.keys().hasAll(b.days) && !next.days.keys().hasAny(b.days)));
    }
    match /bookings/{bid} {
      allow read: if active() && (resource.data.tenantId == request.auth.uid || resource.data.hostId == request.auth.uid || admin());
      allow create: if active() && profile().role == 'tenant' && bookingShape(request.resource.data)
        && request.resource.data.tenantId == request.auth.uid && request.resource.data.tenantEmail == request.auth.token.email
        && request.resource.data.hostId == get(/databases/$(database)/documents/rooms/$(request.resource.data.roomId)).data.ownerId
        && get(/databases/$(database)/documents/rooms/$(request.resource.data.roomId)).data.isActive == true
        && request.resource.data.nightlyPrice == get(/databases/$(database)/documents/rooms/$(request.resource.data.roomId)).data.price
        && request.resource.data.roomName == get(/databases/$(database)/documents/rooms/$(request.resource.data.roomId)).data.name
        && request.resource.data.location == get(/databases/$(database)/documents/rooms/$(request.resource.data.roomId)).data.location
        && request.resource.data.total == request.resource.data.nightlyPrice * (request.resource.data.endDay-request.resource.data.startDay)
        && request.resource.data.startDay >= int((request.time.toMillis() + 10800000) / 86400000)
        && request.resource.data.status == 'Pending' && request.resource.data.createdAt == request.time && request.resource.data.updatedAt == request.time
        && ledger(request.resource.data.roomId).updatedAt == request.time && ledger(request.resource.data.roomId).bookingId == bid
        && ledger(request.resource.data.roomId).days.keys().hasAll(request.resource.data.days) && audit(bid, request.resource.data);
      allow update: if active() && (resource.data.tenantId == request.auth.uid || manager(resource.data.roomId))
        && request.resource.data.diff(resource.data).affectedKeys().hasOnly(['status','updatedAt','lastEventId'])
        && request.resource.data.updatedAt == request.time && request.resource.data.lastEventId != resource.data.lastEventId
        && permittedTransition(resource.data.status, request.resource.data.status, resource.data.tenantId == request.auth.uid)
        && (reserving(request.resource.data.status) || (ledger(resource.data.roomId).updatedAt == request.time
        && ledger(resource.data.roomId).bookingId == bid && !ledger(resource.data.roomId).days.keys().hasAny(resource.data.days)))
        && audit(bid, request.resource.data);
      match /events/{eid} {
        allow read: if active() && (get(/databases/$(database)/documents/bookings/$(bid)).data.tenantId == request.auth.uid || get(/databases/$(database)/documents/bookings/$(bid)).data.hostId == request.auth.uid || admin());
        allow create: if active() && request.resource.data.keys().hasOnly(['actorId','status','createdAt']) && request.resource.data.actorId == request.auth.uid && request.resource.data.createdAt == request.time
          && getAfter(/databases/$(database)/documents/bookings/$(bid)).data.lastEventId == eid && getAfter(/databases/$(database)/documents/bookings/$(bid)).data.status == request.resource.data.status
          && getAfter(/databases/$(database)/documents/bookings/$(bid)).data.updatedAt == request.time;
      }
    }
    match /documentAccess/{grantId} {
      allow get: if active() && (resource.data.tenantId == request.auth.uid || resource.data.hostId == request.auth.uid || admin());
      allow create, update: if active() && request.resource.data.keys().hasOnly(['tenantId','hostId','bookingId'])
        && request.resource.data.tenantId == request.auth.uid && grantId == request.auth.uid + '_' + request.resource.data.hostId
        && getAfter(/databases/$(database)/documents/bookings/$(request.resource.data.bookingId)).data.tenantId == request.auth.uid
        && getAfter(/databases/$(database)/documents/bookings/$(request.resource.data.bookingId)).data.hostId == request.resource.data.hostId;
    }
    match /settings/public { allow read: if true; allow write: if admin(); }
    match /adminInvites/{email} { allow get: if signed() && request.auth.token.email_verified == true && request.auth.token.email == email; }
  }
}
'''
ten=''.join(f'count == {n} ? ['+', '.join(f'string(start + {i})' for i in range(n))+'] : ' for n in range(1,10))
ten+='['+', '.join(f'string(start + {i})' for i in range(10))+']'
rules=rules.replace('__TEN_DAYS__',ten)
rules=rules.replace('__DAYS__', 'b.days == expectedDays(b.startDay, b.endDay - b.startDay)')
(root/'firestore.rules').write_text(rules,encoding='utf-8')
