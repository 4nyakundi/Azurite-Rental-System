// Uses the Firebase CLI's existing signed-in session. Never prints or saves tokens.
import { createRequire } from 'node:module';
import path from 'node:path';
import { randomBytes } from 'node:crypto';
const cliRoot = process.env.FIREBASE_CLI_ROOT || path.join(process.env.APPDATA || '', 'npm/node_modules/firebase-tools');
const cli = createRequire(path.join(cliRoot, 'package.json'));
const auth = cli('./lib/auth.js');
const account = auth.getProjectDefaultAccount(process.cwd());
if (!account) throw new Error('Run firebase login first.');
const token = await auth.getAccessToken(account.tokens.refresh_token, ['https://www.googleapis.com/auth/cloud-platform', 'https://www.googleapis.com/auth/firebase']);
export async function api(url, options = {}) {
  const response = await fetch(url, { ...options, headers: { Authorization: `Bearer ${token.access_token}`, 'Content-Type': 'application/json', ...options.headers } });
  const data = await response.json();
  if (!response.ok) throw new Error(`${response.status}: ${data.error?.message || JSON.stringify(data)}`);
  return data;
}
function value(input) {
  if(typeof input==='string')return {stringValue:input};
  if(typeof input==='boolean')return {booleanValue:input};
  if(typeof input==='number')return Number.isInteger(input)?{integerValue:String(input)}:{doubleValue:input};
  if(Array.isArray(input))return {arrayValue:{values:input.map(value)}};
  if(input instanceof Date)return {timestampValue:input.toISOString()};
  return {mapValue:{fields:Object.fromEntries(Object.entries(input).map(([k,v])=>[k,value(v)]))}};
}
const documentBase='https://firestore.googleapis.com/v1/projects/airbnb-sys/databases/(default)/documents';
async function createDocument(path,data) {
  const [collection,id]=path.split('/');
  try { await api(`${documentBase}/${collection}?documentId=${encodeURIComponent(id)}`,{method:'POST',body:JSON.stringify({fields:Object.fromEntries(Object.entries(data).map(([k,v])=>[k,value(v)]))})}); console.log('Created '+path); }
  catch(error){if(error.message.startsWith('409:'))console.log('Kept existing '+path);else throw error;}
}
const project = 'airbnb-sys';
if(process.argv[2]==='ledger-upgrade') {
  const result=await api(documentBase+'/availability');
  for(const ledger of result.documents||[]) {
    if(ledger.fields?.blocks)continue;
    if(Object.keys(ledger.fields?.days?.mapValue?.fields||{}).length)throw new Error('Refusing to change a nonempty legacy ledger.');
    await api(`https://firestore.googleapis.com/v1/${ledger.name}?updateMask.fieldPaths=blocks`,{method:'PATCH',body:JSON.stringify({fields:{blocks:value({})}})});
    console.log('Initialized blocked-night map: '+ledger.name.split('/').pop());
  }
}
if(process.argv[2]==='smoke-auth') {
  const key='AIzaSyDZaVgzHqe6PDDOWPOPgDWFgAHa1zY8E3E';
  const email='azurite-smoke-'+randomBytes(8).toString('hex')+'@example.test';
  const password=randomBytes(24).toString('base64url');
  async function identity(method,body){
    const response=await fetch(`https://identitytoolkit.googleapis.com/v1/accounts:${method}?key=${key}`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
    const result=await response.json();
    if(!response.ok)throw new Error(result.error?.message||'Authentication check failed.');
    return result;
  }
  let created;
  try {
    created=await identity('signUp',{email,password,returnSecureToken:true});
    const signed=await identity('signInWithPassword',{email,password,returnSecureToken:true});
    if(signed.localId!==created.localId)throw new Error('Sign-in returned the wrong identity.');
    console.log('PASS: live Firebase registration and email/password sign-in.');
  } finally {
    if(created){await identity('delete',{idToken:created.idToken});console.log('Disposable authentication account removed.');}
  }
}
if(process.argv[2]==='seed') {
  const timestamp=new Date();
  const homes=[
    ['ocean-view',{name:'Ocean View Apartment',location:'Nyali, Mombasa',description:'Slow mornings, sea air and space to unwind. Settle into a bright coastal apartment with an easygoing atmosphere and a private balcony.\n\nA comfortable base for discovering Mombasa’s beaches and neighbourhoods.',price:6500,category:'Apartment',photos:['/assets/outside_1.jpg','/assets/living_room_2_pic.jpg','/assets/kitchen_pic.jpg']}],
    ['garden-suite',{name:'Deluxe Garden Suite',location:'Bamburi, Mombasa',description:'A quiet retreat surrounded by tropical greenery. Enjoy an inviting suite with a kitchen and room to make yourself at home.\n\nChoose your dates and send a request to arrange your coastal stay.',price:4200,category:'Suite',photos:['/assets/outside_2.jpg','/assets/kitchen_pic.jpg']}],
    ['coastal-villa',{name:'Palm House',location:'Diani, South Coast',description:'Bring your favourite people and a slower pace. This coastal home offers an open, welcoming setting for relaxed days close to the beach.\n\nAsk your host about arrival arrangements after your request is approved.',price:8500,category:'Villa',photos:['/assets/outside_5.jpg','/assets/living_room_2_pic.jpg']}],
  ];
  for(const [id,home] of homes){
    await createDocument('rooms/'+id,{...home,ownerId:'azurite-team',isActive:true,createdAt:timestamp,updatedAt:timestamp});
    await createDocument('availability/'+id,{days:{},blocks:{},action:'init',bookingId:'',day:'',updatedAt:timestamp});
  }
  await createDocument('settings/public',{storageEnabled:false});
  await createDocument('adminInvites/'+account.user.email,{createdAt:timestamp});
  console.log('The signed-in project owner can claim administrator access after registering and verifying that email.');
}
if(process.argv[2]==='auth-config') {
  await api(`https://identitytoolkit.googleapis.com/admin/v2/projects/${project}/config?updateMask=signIn.email.enabled,signIn.email.passwordRequired,authorizedDomains`,{method:'PATCH',body:JSON.stringify({signIn:{email:{enabled:true,passwordRequired:true}},authorizedDomains:['localhost','airbnb-sys.web.app','airbnb-sys.firebaseapp.com']})});
  console.log('Email/password sign-in configured.');
}
if (process.argv[2] === 'prepare') {
  for (const service of ['firestore.googleapis.com','identitytoolkit.googleapis.com','firebaserules.googleapis.com']) {
    const result = await api(`https://serviceusage.googleapis.com/v1/projects/844694638611/services/${service}:enable`,{method:'POST',body:'{}'});
    console.log('Enabled '+service+(result.done?'':' (provisioning)'));
  }
  // Standard Firebase Auth is initialized once in Firebase Console.
  // Do not initialize the separate billing-dependent Identity Platform service here.
  try {
    await api(`https://identitytoolkit.googleapis.com/admin/v2/projects/${project}/config?updateMask=signIn.email.enabled,signIn.email.passwordRequired,authorizedDomains`,{
      method:'PATCH',body:JSON.stringify({signIn:{email:{enabled:true,passwordRequired:true}},authorizedDomains:['localhost','airbnb-sys.web.app','airbnb-sys.firebaseapp.com']})
    });
    console.log('Email/password sign-in configured.');
  } catch (error) { console.log('Auth configuration: '+error.message); process.exitCode=1; }
}
if (process.argv[2] === 'inspect') {
  for (const [label, url] of [
    ['billing', `https://cloudbilling.googleapis.com/v1/projects/${project}/billingInfo`],
    ['authentication', `https://identitytoolkit.googleapis.com/admin/v2/projects/${project}/config`],
    ['storage', `https://storage.googleapis.com/storage/v1/b?project=${project}`],
  ]) {
    try {
      const result = await api(url);
      if (label === 'billing') console.log(JSON.stringify({ label, billingEnabled: result.billingEnabled }));
      if (label === 'authentication') console.log(JSON.stringify({ label, emailEnabled:result.signIn?.email?.enabled, passwordRequired:result.signIn?.email?.passwordRequired, authorizedDomains: result.authorizedDomains }));
      if (label === 'storage') console.log(JSON.stringify({ label, buckets: result.items?.map(x => ({ name:x.name, location:x.location })) || [] }));
    } catch (error) { console.log(JSON.stringify({ label, error:error.message })); }
  }
}
