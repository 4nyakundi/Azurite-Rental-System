document.querySelectorAll('form').forEach((form) => {
  form.querySelectorAll('label').forEach((label, i) => {
    if (label.htmlFor || label.querySelector('input, select, textarea')) return;
    const field = label.nextElementSibling;
    if (field && /^(INPUT|SELECT|TEXTAREA)$/.test(field.tagName)) {
      field.id ||= `field-${Array.from(document.forms).indexOf(form)}-${i}`;
      label.htmlFor = field.id;
    }
  });
  const checkin = form.querySelector('[name="checkin"]');
  const checkout = form.querySelector('[name="checkout"]');
  if (checkin && checkout) {
    const now = new Date();
    const today = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
    checkin.min = today;
    const update = () => {
      checkout.min = checkin.value || today;
      checkout.setCustomValidity(checkout.value && checkin.value && checkout.value <= checkin.value ? 'Check-out must be after check-in.' : '');
    };
    checkin.addEventListener('input', update);
    checkout.addEventListener('input', update);
    update();
  }
});
document.querySelectorAll('img').forEach((img) => {
  const fallback = () => {
    if (img.dataset.fallback) return;
    img.dataset.fallback = '1';
    const script = document.querySelector('script[src$="assets/js/forms.js"]');
    if (script && /room_photos/.test(img.src)) img.src = new URL('../img/outside_2.jpg', script.src).href;
    else if (script && /avatars/.test(img.src)) img.src = new URL('../img/avatar-placeholder.svg', script.src).href;
    else if (/document\.php/.test(img.src)) {
      const message = document.createElement('span');
      message.textContent = 'Document unavailable';
      img.hidden = true;
      img.after(message);
    }
  };
  img.addEventListener('error', fallback);
  if (img.complete && img.naturalWidth === 0) fallback();
});
