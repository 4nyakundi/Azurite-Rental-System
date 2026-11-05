import { auth, db, storage } from './firebase.js';
import { collection, doc, getDoc, getDocs, query, where, setDoc, updateDoc, runTransaction, serverTimestamp, onSnapshot } from 'firebase/firestore';
import { createUserWithEmailAndPassword, signInWithEmailAndPassword, signOut, sendPasswordResetEmail, updateProfile, reauthenticateWithCredential, EmailAuthProvider, updatePassword, sendEmailVerification } from 'firebase/auth';
import { ref, uploadBytes, getDownloadURL, getBlob } from 'firebase/storage';

export const money = value => new Intl.NumberFormat('en-KE', { style:'currency', currency:'KES', maximumFractionDigits:0 }).format(value);
export const today = () => new Intl.DateTimeFormat('en-CA', { timeZone:'Africa/Nairobi',year:'numeric',month:'2-digit',day:'2-digit' }).format(new Date());
export function epochDay(date) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) throw new Error('Choose valid check-in and check-out dates.');
  const parsed = new Date(date+'T00:00:00Z');
  if (!Number.isFinite(parsed.getTime()) || parsed.toISOString().slice(0,10) !== date) throw new Error('Choose a real calendar date.');
  return Math.floor(parsed.getTime()/86400000);
}
export const dateLabel = day => new Date(day*86400000).toLocaleDateString('en-KE', { day:'numeric',month:'short',year:'numeric',timeZone:'UTC' });
export function dateRange(checkin, checkout) {
  const startDay=epochDay(checkin), endDay=epochDay(checkout);
  if (startDay < epochDay(today()) || endDay <= startDay || endDay-startDay>30) throw new Error('Choose a future stay between 1 and 30 nights.');
  return { startDay, endDay, days:Array.from({length:endDay-startDay},(_,i)=>String(startDay+i)) };
}
export async function signup(input) {
  const result=await createUserWithEmailAndPassword(auth,input.email,input.password);
  await updateProfile(result.user,{displayName:input.fullName});
  await completeProfile(input);
  try { await sendEmailVerification(result.user); } catch { /* Account remains usable if email delivery is temporarily unavailable. */ }
}
export async function completeProfile(input) {
  await setDoc(doc(db,'users',auth.currentUser.uid),{ fullName:input.fullName,email:auth.currentUser.email,phone:input.phone||'',role:input.role==='host'?'host':'tenant',isActive:true,avatarPath:'',identityPath:'',createdAt:serverTimestamp(),updatedAt:serverTimestamp() });
}
export const login = (email,password) => signInWithEmailAndPassword(auth,email,password);
export const logout = () => signOut(auth);
export const resetPassword = email => sendPasswordResetEmail(auth,email);
export async function claimAdmin() {
  if(!auth.currentUser?.emailVerified) return false;
  const invite=await getDoc(doc(db,'adminInvites',auth.currentUser.email));
  if(!invite.exists()) return false;
  await updateDoc(doc(db,'users',auth.currentUser.uid),{role:'admin',updatedAt:serverTimestamp()});
  return true;
}
export async function refreshVerification() {
  await auth.currentUser.reload();
  await auth.currentUser.getIdToken(true);
  if(auth.currentUser.emailVerified) await claimAdmin();
  else throw new Error('Your email is not verified yet. Open the verification link in your inbox, then try again.');
}
export const watchProfile = (uid,callback,error) => onSnapshot(doc(db,'users',uid),snapshot=>callback(snapshot.exists()?{id:snapshot.id,...snapshot.data()}:null),error);
export async function settings() { const snap=await getDoc(doc(db,'settings','public')); return snap.exists()?snap.data():{storageEnabled:false}; }
const rows = snapshot => snapshot.docs.map(d=>({id:d.id,...d.data()}));
export async function rooms(mode='public') {
  const source=collection(db,'rooms');
  const target=mode==='admin'?source:query(source,where(mode==='host'?'ownerId':'isActive','==',mode==='host'?auth.currentUser.uid:true));
  return rows(await getDocs(target)).sort((a,b)=>(b.createdAt?.seconds||0)-(a.createdAt?.seconds||0));
}
export async function room(id) { const snap=await getDoc(doc(db,'rooms',id)); if(!snap.exists()) throw new Error('This stay is no longer available.'); return {id:snap.id,...snap.data()}; }
export async function availability(roomId) { const snap=await getDoc(doc(db,'availability',roomId)); if(!snap.exists())return {};return {...Object.fromEntries(Object.keys(snap.data().days).map(day=>[day,'reserved'])),...Object.fromEntries(Object.keys(snap.data().blocks||{}).map(day=>[day,'blocked']))}; }
export async function saveRoom(input,id) {
  const data={name:input.name.trim(),location:input.location.trim(),description:input.description.trim(),price:Math.round(Number(input.price)*100)/100,category:input.category,isActive:input.isActive,photos:input.photos||[],updatedAt:serverTimestamp()};
  if(!data.name || !Number.isFinite(data.price) || data.price<=0) throw new Error('Enter a name and a valid nightly price.');
  if(id) await updateDoc(doc(db,'rooms',id),data);
  else {
    const r=doc(collection(db,'rooms'));
    await runTransaction(db,async tx=>{
      tx.set(r,{...data,ownerId:auth.currentUser.uid,createdAt:serverTimestamp()});
      tx.set(doc(db,'availability',r.id),{days:{},blocks:{},action:'init',bookingId:'',day:'',updatedAt:serverTimestamp()});
    });
    id=r.id;
  }
  return id;
}
export async function createBooking(roomId,input,profile) {
  if(profile?.role!=='tenant') throw new Error('Use a tenant account to request a stay.');
  const range=dateRange(input.checkin,input.checkout);
  const guests=Number(input.guests);
  if(!Number.isInteger(guests)||guests<1||guests>4) throw new Error('Choose between 1 and 4 guests.');
  if(!/^[+0-9 ()-]{7,30}$/.test(input.phone)) throw new Error('Enter a valid contact number.');
  const booking=doc(collection(db,'bookings'));
  await runTransaction(db,async tx=>{
    const [roomSnap,ledgerSnap]=await Promise.all([tx.get(doc(db,'rooms',roomId)),tx.get(doc(db,'availability',roomId))]);
    if(!roomSnap.exists()||!roomSnap.data().isActive||!ledgerSnap.exists()) throw new Error('This stay is not accepting bookings.');
    const r=roomSnap.data(),days={...ledgerSnap.data().days};
    if(range.days.some(day=>days[day]||ledgerSnap.data().blocks?.[day])) throw new Error('One or more nights are unavailable. Please choose another date range.');
    range.days.forEach(day=>{days[day]=true;});
    tx.set(booking,{tenantId:auth.currentUser.uid,tenantName:profile.fullName,tenantEmail:auth.currentUser.email,hostId:r.ownerId,roomId,roomName:r.name,location:r.location,nightlyPrice:r.price,total:r.price*range.days.length,...range,guests,phone:input.phone,status:'Pending',createdAt:serverTimestamp(),updatedAt:serverTimestamp(),lastEventId:'initial'});
    tx.update(doc(db,'availability',roomId),{days,action:'reserve',bookingId:booking.id,day:'',updatedAt:serverTimestamp()});
    tx.set(doc(db,'bookings',booking.id,'events','initial'),{actorId:auth.currentUser.uid,status:'Pending',createdAt:serverTimestamp()});
    tx.set(doc(db,'documentAccess',auth.currentUser.uid+'_'+r.ownerId),{tenantId:auth.currentUser.uid,hostId:r.ownerId,bookingId:booking.id});
  });
  return booking.id;
}
export async function bookings(role) {
  const source=collection(db,'bookings');
  return rows(await getDocs(role==='admin'?source:query(source,where(role==='host'?'hostId':'tenantId','==',auth.currentUser.uid))))
    .sort((a,b)=>(b.createdAt?.seconds||0)-(a.createdAt?.seconds||0));
}
export const events = async id => rows(await getDocs(collection(db,'bookings',id,'events'))).sort((a,b)=>(a.createdAt?.seconds||0)-(b.createdAt?.seconds||0));
export function transitions(status,role) {
  if(role==='tenant') return ['Pending','Approved','Confirmed'].includes(status)?['Cancelled']:[];
  return {Pending:['Approved','Declined','Cancelled'],Approved:['Confirmed','Cancelled'],Confirmed:['CheckedIn','Cancelled'],CheckedIn:['Completed']}[status]||[];
}
export async function changeStatus(id,status) {
  const event=crypto.randomUUID();
  await runTransaction(db,async tx=>{
    const bookingRef=doc(db,'bookings',id),snap=await tx.get(bookingRef);
    if(!snap.exists()) throw new Error('Booking not found.');
    const b=snap.data();
    const terminal=['Declined','Cancelled','Completed'].includes(status);
    let ledgerSnap;
    if(terminal) ledgerSnap=await tx.get(doc(db,'availability',b.roomId));
    tx.update(bookingRef,{status,updatedAt:serverTimestamp(),lastEventId:event});
    tx.set(doc(db,'bookings',id,'events',event),{actorId:auth.currentUser.uid,status,createdAt:serverTimestamp()});
    if(terminal) {
      const days={...ledgerSnap.data().days};
      b.days.forEach(day=>{if(days[day]!==true) throw new Error('Availability changed. Please refresh and try again.'); delete days[day];});
      tx.update(doc(db,'availability',b.roomId),{days,action:'release',bookingId:id,day:'',updatedAt:serverTimestamp()});
    }
  });
}
export async function toggleDay(roomId,day) {
  await runTransaction(db,async tx=>{
    const target=doc(db,'availability',roomId),snap=await tx.get(target),blocks={...snap.data().blocks};
    if(snap.data().days[day]) throw new Error('A booking already reserves this night.');
    const action=blocks[day]?'unblock':'block';
    if(action==='unblock') delete blocks[day]; else blocks[day]=true;
    tx.update(target,{blocks,action,bookingId:'',day:String(day),updatedAt:serverTimestamp()});
  });
}
export const users = async () => rows(await getDocs(collection(db,'users')));
export const changeUser = (id,changes) => updateDoc(doc(db,'users',id),{...changes,updatedAt:serverTimestamp()});
export const updateAccount = input => updateDoc(doc(db,'users',auth.currentUser.uid),{fullName:input.fullName.trim(),phone:input.phone.trim(),updatedAt:serverTimestamp()});
export async function changePassword(current,next) {
  await reauthenticateWithCredential(auth.currentUser,EmailAuthProvider.credential(auth.currentUser.email,current));
  await updatePassword(auth.currentUser,next);
}
export async function upload(file,kind) {
  const config=await settings();
  if(!config.storageEnabled) throw new Error('Uploads are unavailable until Firebase Storage billing is enabled.');
  const types=kind==='identity'?['image/jpeg','image/png','application/pdf']:['image/jpeg','image/png'];
  if(!types.includes(file.type)||file.size>5*1024*1024) throw new Error('Choose a supported file smaller than 5 MB.');
  const name=crypto.randomUUID()+'.'+({'image/jpeg':'jpg','image/png':'png','application/pdf':'pdf'}[file.type]);
  const object=ref(storage,`${kind==='identity'?'private':'photos'}/${auth.currentUser.uid}/${name}`);
  await uploadBytes(object,file,{contentType:file.type});
  return kind==='identity'?object.fullPath:await getDownloadURL(object);
}
export async function saveAccountFile(file,kind) {
  const value=await upload(file,kind);
  await updateDoc(doc(db,'users',auth.currentUser.uid),{[kind==='identity'?'identityPath':'avatarPath']:value,updatedAt:serverTimestamp()});
}
export async function openIdentity(path) {
  const blob=await getBlob(ref(storage,path));
  const url=URL.createObjectURL(blob);
  const link=document.createElement('a');link.href=url;link.download='identity-document.'+(blob.type==='application/pdf'?'pdf':'png');link.click();
  setTimeout(()=>URL.revokeObjectURL(url),60000);
}
