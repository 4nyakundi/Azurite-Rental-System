import { initializeApp } from 'firebase/app';
import { getAuth, connectAuthEmulator } from 'firebase/auth';
import { getFirestore, connectFirestoreEmulator } from 'firebase/firestore';
import { getStorage, connectStorageEmulator } from 'firebase/storage';
export const firebaseConfig = {
  apiKey: 'AIzaSyDZaVgzHqe6PDDOWPOPgDWFgAHa1zY8E3E',
  authDomain: 'airbnb-sys.firebaseapp.com',
  projectId: 'airbnb-sys',
  storageBucket: 'airbnb-sys.firebasestorage.app',
  messagingSenderId: '844694638611',
  appId: '1:844694638611:web:342f56d35ab4dac8bf2fe6',
  measurementId: 'G-GK30QX43TT',
};
export const app = initializeApp(firebaseConfig);
export const auth = getAuth(app);
export const db = getFirestore(app);
export const storage = getStorage(app);
if (import.meta.env?.VITE_USE_EMULATORS === 'true') {
  connectAuthEmulator(auth, 'http://127.0.0.1:9099');
  connectFirestoreEmulator(db, '127.0.0.1', 8088);
  connectStorageEmulator(storage, '127.0.0.1', 9199);
}
// Analytics is opt-in. Core bookings work without advertising or analytics cookies.
export async function enableAnalytics() {
  const { getAnalytics, isSupported } = await import('firebase/analytics');
  if (await isSupported()) getAnalytics(app);
}
