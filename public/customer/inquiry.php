<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Event Service Inquiry | Jessie Cane</title>
  <link rel="stylesheet" href="../../assets/css/shared.css" />
  <link rel="stylesheet" href="css/customer.css" />
  <link rel="stylesheet" href="css/inquiry.css" />
</head>
<body>
  <!-- PURPOSE: Inquiry page — provides a template and form for event service inquiries -->
  <header>
    <div class="left-header">
      <img src="../../assets/images/logo.png" id="drink_logo" alt="Jessie Cane Logo" onerror="this.style.display='none'">
    </div>
    <div class="right-header">
      <a href="../index.php" class="nav-btn">Portal</a>
      <a href="customer_dashboard.php" class="nav-btn">Home</a>
      <a href="drinks.php" class="nav-btn">Menu</a>
      <a href="profile.php" class="nav-btn">Profile</a>
      <a href="inquiry.php" class="nav-btn active">Inquiry</a>
      <a href="../index.php" class="nav-btn logout">Logout</a>
    </div>
  </header>

  <main>
    <div class="inquiry-container">
      <div class="inquiry-header">
        <h3 style="margin:0; color:#2E5D47">Request Jessie Cane to Serve at Your Event</h3>
        <a href="customer_dashboard.php" class="view-btn" style="background:transparent; color:#2E5D47; border:1.5px solid #2E5D47;">Back to Home</a>
      </div>

      <p style="color:#3B6651;">The form below is a template for guidance only. It is non-functional — please use the sample email below to compose and send your inquiry from your personal email account to: <strong>events@jessiecane.com</strong>.</p>

      <form id="inquiry-form" class="inquiry-form">
        <div>
          <label for="event-name">Event Name</label>
          <div class="field-row">
            <input id="event-name" name="event-name" type="text" placeholder="e.g. Jane & John's Wedding" />
            <div class="error-msg" id="err-event-name">Event name is required.</div>
          </div>
        </div>
        <div>
          <label for="event-datetime">Event Date and Time</label>
          <input id="event-datetime" name="event-datetime" type="date" />
        </div>
        <div class="fullwidth" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; align-items:start;">
          <div>
            <label for="guests">Estimated Number of Guests</label>
            <div class="field-row">
              <input id="guests" name="guests" type="number" min="1" step="1" placeholder="e.g. 120" />
              <div class="error-msg" id="err-guests">Please enter a valid number of guests.</div>
            </div>
          </div>
          <div>
            <label for="contact-email">Contact Email</label>
            <div class="field-row">
              <input id="contact-email" name="contact-email" type="email" placeholder="your@email.com" />
              <div class="error-msg" id="err-contact-email">Please enter a valid email address.</div>
            </div>
          </div>
        </div>
        <div class="fullwidth" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; align-items:start;">
          <div>
            <label for="contact-name">Contact Name</label>
            <div class="field-row">
              <input id="contact-name" name="contact-name" type="text" placeholder="First Name, M.I, Surname" />
              <div class="error-msg" id="err-contact-name">Contact name is required.</div>
            </div>
          </div>
          <div>
            <label for="contact-phone">Contact Number</label>
            <div class="field-row">
              <input id="contact-phone" name="contact-phone" type="text" placeholder="+63" />
              <div class="error-msg" id="err-contact-phone">Please enter a valid phone number.</div>
            </div>
          </div>
        </div>
        
        <div class="fullwidth">
          <label for="venue">Venue / Location</label>
          <input id="venue" name="venue" type="text" placeholder="e.g. St. Mary's Church, City" style="width:100%" />
        </div>
        <div class="fullwidth">
          <label for="special">Special Instructions or Requests</label>
          <textarea id="special" name="special" rows="4" placeholder="e.g. Set-up time, dietary notes, service style" style="width:100%"></textarea>
        </div>

        <div class="fullwidth" style="margin-top:8px; text-align:right;">
          <div>
            <button id="use-template-btn" type="button" class="view-btn">Preview Email</button>
          </div>
        </div>
      </form>

      <div class="sample-email">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
          <strong>Sample Email Template (copy & paste into your email)</strong>
          <div>
            <button id="copy-template" class="copy-btn">Copy Template</button>
          </div>
        </div>

        <hr style="border:none; border-top:1px dashed #e0d9c3; margin:12px 0;" />

  <pre class="template" id="email-template">To: events@jessiecane.com
Subject: Event Service Inquiry — [Event Name]

Hello Jessie Cane team,

I would like to inquire about having Jessie Cane Juicebar provide drinks/services for my event. Below are the event details:

Event Name: [Event Name]
Event Date & Time: [Date & Time]
Venue/Location: [Venue/Location]
Estimated Guests: [Number of Guests]
Contact Person: [Name] — [Phone] — [Email]
Special Instructions/Requests: [Any special notes]

Please let me know your availability and a rough quote. Thank you!

Best regards,
[Your Name]
[Your Contact Information]</pre>

      </div>

      <p style="margin-top:14px; color:#666;">Note: This tool does not send emails on your behalf. Use your personal email account to send the above message to events@jessiecane.com.</p>
    </div>
  </main>

  <script>
    function buildTemplate() {
      const ev = document.getElementById('event-name').value || '[Event Name]';
      const dtRaw = document.getElementById('event-datetime').value || '';
      let dt = '[Date & Time]';
      if (dtRaw) {
        // If it's a date input (yyyy-mm-dd), format nicely
        try {
          const d = new Date(dtRaw);
          if (!isNaN(d.getTime())) dt = d.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
          else dt = dtRaw;
        } catch (_) { dt = dtRaw; }
      }
      const vn = document.getElementById('venue').value || '[Venue/Location]';
  const gs = document.getElementById('guests').value || '[Number of Guests]';
  const name = document.getElementById('contact-name').value || '[Name]';
  const phone = document.getElementById('contact-phone').value || '[Phone]';
  const email = document.getElementById('contact-email').value || '[Email]';
  const sp = document.getElementById('special').value || '[Any special notes]';

  const body = `To: events@jessiecane.com\nSubject: Event Service Inquiry — ${ev}\n\nHello Jessie Cane team,\n\nI would like to inquire about having Jessie Cane Juicebar provide drinks/services for my event. Below are the event details:\n\nEvent Name: ${ev}\nEvent Date & Time: ${dt}\nVenue/Location: ${vn}\nEstimated Guests: ${gs}\nContact Person: ${name} — ${phone} — ${email}\nSpecial Instructions/Requests: ${sp}\n\nPlease let me know your availability and a rough quote. Thank you!\n\nBest regards,\n${name}`;

      // Update visible template
      const pre = document.getElementById('email-template');
      pre.textContent = body;
      return body;
    }

    // simple toast using existing CSS classes
    function showToast(message, type = 'success'){
      const t = document.createElement('div');
      t.className = `toast toast-${type}`;
      t.textContent = message;
      document.body.appendChild(t);
      setTimeout(() => t.remove(), 3000);
    }

    // normalize phone number to include +63 when appropriate
    function normalizePhone(raw){
      if (!raw) return '';
      let s = raw.trim();
      // remove spaces and common separators
      s = s.replace(/[()\s.-]/g,'');
      if (s.startsWith('+')) return s;
      if (s.startsWith('0')) return '+63' + s.slice(1);
      // if looks like local number (9-10 digits), prefix +63
      const digits = s.replace(/\D/g,'');
      if (digits.length >= 9 && digits.length <= 11) return '+63' + digits.replace(/^0+/, '');
      return s;
    }

    function validateEmail(email){
      if (!email) return false;
      // simple regex
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Basic phone formatting as user types (keeps cursor simple)
    function formatPhoneDisplay(raw) {
      if (!raw) return '';
      // remove non-digit and leading plus for formatting
      let s = raw.replace(/[^\d]/g,'');
      // if starts with 63 then show +63 ... else try to detect local '0' prefix
      if (s.startsWith('63')) s = '+' + s;
      else if (s.startsWith('0')) s = '+63' + s.slice(1);
      else if (s.length <= 9) s = '+63' + s;
      else s = '+' + s;
      return s;
    }

    // Copy current template
    document.getElementById('copy-template').addEventListener('click', function(){
      // normalize phone before building
      const phoneField = document.getElementById('contact-phone');
      if (phoneField) phoneField.value = normalizePhone(phoneField.value || '');

      const txt = buildTemplate();
      navigator.clipboard.writeText(txt).then(() => {
        showToast('Template copied to clipboard. Paste it into your email client and send to events@jessiecane.com', 'success');
      }).catch(() => {
        showToast('Copy failed — please select and copy the template manually.', 'error');
      });
    });

    // Preview Email button: build and show the template below
    document.getElementById('use-template-btn').addEventListener('click', function(){
      const phoneField = document.getElementById('contact-phone');
      if (phoneField) phoneField.value = normalizePhone(phoneField.value || '');
      buildTemplate();
      showToast('Sample email updated below. Copy and paste into your email.', 'success');
      const pre = document.getElementById('email-template');
      if (pre && pre.scrollIntoView) pre.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    // Prefill from logged-in user if available
    document.addEventListener('DOMContentLoaded', function(){
      try {
        const u = JSON.parse(sessionStorage.getItem('currentUser') || 'null');
        if (u) {
          const nameField = document.getElementById('contact-name');
          const phoneField = document.getElementById('contact-phone');
          const emailField = document.getElementById('contact-email');
          if (nameField) nameField.value = u.name || u.username || '';
          if (emailField) emailField.value = u.email || '';
          if (phoneField && (u.phone || u.contact)) phoneField.value = u.phone || u.contact;
        }
      } catch (e) {
        // ignore
      }
    });

    // Inline validation helpers
    function setFieldError(fieldEl, errElId, message) {
      const errEl = document.getElementById(errElId);
      if (!fieldEl) return;
      if (message) {
        fieldEl.classList.add('input-invalid');
        if (errEl) { errEl.textContent = message; errEl.style.display = 'block'; }
      } else {
        fieldEl.classList.remove('input-invalid');
        if (errEl) { errEl.textContent = ''; errEl.style.display = 'none'; }
      }
    }

    // Field-level validators
    function validateFieldContactEmail(){
      const emailF = document.getElementById('contact-email');
      if (!emailF) return true;
      const ok = !emailF.value || validateEmail(emailF.value);
      setFieldError(emailF, 'err-contact-email', ok ? '' : 'Please enter a valid email address.');
      return ok;
    }

    function validateFieldContactName(){
      const f = document.getElementById('contact-name');
      if (!f) return true;
      const ok = f.value && f.value.trim().length > 1;
      setFieldError(f, 'err-contact-name', ok ? '' : 'Contact name is required.');
      return ok;
    }

    function validateFieldEventName(){
      const f = document.getElementById('event-name');
      if (!f) return true;
      const ok = f.value && f.value.trim().length > 1;
      setFieldError(f, 'err-event-name', ok ? '' : 'Event name is required.');
      return ok;
    }

    function validateFieldContactPhone(){
      const f = document.getElementById('contact-phone');
      if (!f) return true;
      const raw = f.value || '';
      const norm = normalizePhone(raw);
      // stricter: require +63 country code and reasonable length (digits only)
      const digits = (norm || '').replace(/\D/g,'');
      const ok = digits.startsWith('63') && (digits.length === 12 || digits.length === 11 || digits.length === 10);
      setFieldError(f, 'err-contact-phone', ok ? '' : 'Please enter a valid phone number.');
      return ok;
    }

    function validateFieldGuests(){
      const f = document.getElementById('guests');
      if (!f) return true;
      const value = f.value;
      const n = Number(value);
      const ok = Number.isInteger(n) && n > 0;
      setFieldError(f, 'err-guests', ok ? '' : 'Please enter a valid number of guests.');
      return ok;
    }

    // Live phone formatting: on input, show formatted value but keep digits
    const phoneInput = document.getElementById('contact-phone');
    if (phoneInput) {
      phoneInput.addEventListener('input', function(e){
        const pos = phoneInput.selectionStart;
        const before = phoneInput.value;
        const formatted = formatPhoneDisplay(before);
        phoneInput.value = formatted;
        // set caret near the end — best-effort
        try { phoneInput.setSelectionRange(phoneInput.value.length, phoneInput.value.length); } catch(err){}
        validateFieldContactPhone();
      });
      phoneInput.addEventListener('blur', validateFieldContactPhone);
    }

    // Guests validation
    const guestsInput = document.getElementById('guests');
    if (guestsInput) {
      guestsInput.addEventListener('input', validateFieldGuests);
      guestsInput.addEventListener('blur', validateFieldGuests);
    }

    // Removed local draft autosave functionality per new requirements

    // Wire remaining field validators
    const emailInput = document.getElementById('contact-email');
    if (emailInput) { emailInput.addEventListener('blur', validateFieldContactEmail); emailInput.addEventListener('input', validateFieldContactEmail); }
    const nameInput = document.getElementById('contact-name');
    if (nameInput) { nameInput.addEventListener('blur', validateFieldContactName); nameInput.addEventListener('input', validateFieldContactName); }
    const eventNameInput = document.getElementById('event-name');
    if (eventNameInput) { eventNameInput.addEventListener('blur', validateFieldEventName); eventNameInput.addEventListener('input', validateFieldEventName); }

    // Banner invite buttons (harmless if not present)
    const availBtn = document.getElementById('avail-service-btn');
    if (availBtn) {
      availBtn.addEventListener('click', () => { window.location.href = 'inquiry.php'; });
    }
    
  </script>
  <script defer src="../../assets/js/api-helper.js"></script>
  <script defer src="../../assets/js/header.js"></script>
</body>
</html>