// Explicit deployment check: creates only generated test data and removes it afterward.
import assert from 'node:assert/strict';
import { randomBytes } from 'node:crypto';
import { createUserWithEmailAndPassword, deleteUser } from 'firebase/auth';
import { auth } from '../firebase-app/src/firebase.js';
import * as data from '../firebase-app/src/data.js';
import { api } from '../tools/firebase-project.mjs';

const email='azurite-live-'+randomBytes(8).toString('hex')+'@example.test';
const password=randomBytes(24).toString('base64url');
const base='https://firestore.googleapis.com/v1/projects/airbnb-sys/databases/(default)/documents';
let uid,bookingId,cancelled=false;
async function remove(path){await api(base+'/'+path,{method:'DELETE'});}
try {
  const homes=await data.rooms();
  assert.ok(homes.length>=2,'Public listings must be readable.');
  console.log('PASS: live public Firestore listings.');
  const created=await createUserWithEmailAndPassword(auth,email,password);
  uid=created.user.uid;
  await data.completeProfile({fullName:'Deployment check',role:'tenant',phone:'0712345678'});
  const date=new Date(data.today()+'T00:00:00Z');date.setUTCDate(date.getUTCDate()+300);
  const checkin=date.toISOString().slice(0,10);date.setUTCDate(date.getUTCDate()+1);
  bookingId=await data.createBooking('ocean-view',{checkin,checkout:date.toISOString().slice(0,10),guests:'1',phone:'0712345678'},{fullName:'Deployment check',role:'tenant'});
  const mine=await data.bookings('tenant');
  assert.equal(mine.find(b=>b.id===bookingId)?.status,'Pending');
  await data.changeStatus(bookingId,'Cancelled');cancelled=true;
  assert.equal((await data.bookings('tenant')).find(b=>b.id===bookingId)?.status,'Cancelled');
  assert.equal((await data.events(bookingId)).length,2);
  console.log('PASS: live profile creation, atomic booking, cancellation and audit trail using the actual app service.');
} finally {
  if(bookingId&&!cancelled){await data.changeStatus(bookingId,'Cancelled');cancelled=true;}
  if(bookingId&&cancelled){
    const events=await api(base+'/bookings/'+bookingId+'/events');
    for(const event of events.documents||[])await api('https://firestore.googleapis.com/v1/'+event.name,{method:'DELETE'});
    await remove('bookings/'+bookingId);
    await remove('documentAccess/'+uid+'_azurite-team');
  }
  if(uid){await remove('users/'+uid);await deleteUser(auth.currentUser);}
  console.log('Disposable test records and identity removed; reserved nights released.');
}
