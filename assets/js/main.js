// main.js - unified Brochure + JD + Apply modals (complete, no JD redirect)
(function () {
  // ----------------- CONFIG -----------------
  const API_ENDPOINT       = '/brochuresubmit.php'; // brochure endpoint (opens PDF on success)
  const DOWNLOAD_URL       = '/TNS-BrouchureNew.pdf'; // fallback brochure URL

  const JD_API_ENDPOINT    = '/jdsubmit.php';    // JD lead endpoint (no redirect)
//   const APPLY_API_ENDPOINT = '/applysubmit.php';
  // Apply endpoint (no redirect)

  // ----------------- helpers -----------------
  const $ = (sel, root = document) => (root || document).querySelector(sel);
  const $$ = (sel, root = document) => Array.from((root || document).querySelectorAll(sel));
  const isEmail = v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v || '').trim());
  const isPhone = v => (String(v || '').replace(/\D/g, '')).length >= 7;
  const elFrom = html => { const tmp = document.createElement('div'); tmp.innerHTML = html.trim(); return tmp.firstChild; };
  const escapeHtml = s => String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

  // ----------------- loader -----------------
  function createLoaderHtml() {
    return `
      <div id="bn-fullscreen-loader" role="status" aria-hidden="true" style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,0.55);z-index:2147483650;">
        <div style="background:#fff;padding:22px;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,0.25);min-width:220px;text-align:center;">
          <div style="width:56px;height:56px;border-radius:50%;border:6px solid rgba(0,0,0,0.08);border-top-color:#0b48d1;margin:0 auto 14px;animation:bn-spin 1s linear infinite;"></div>
          <div id="bn-loader-text" style="font-size:15px;color:#111;">Sending…</div>
        </div>
      </div>
      <style>@keyframes bn-spin{to{transform:rotate(360deg);}}</style>
    `;
  }
  function showLoader(message) {
    let loader = document.getElementById('bn-fullscreen-loader');
    if (!loader) {
      document.body.insertAdjacentHTML('beforeend', createLoaderHtml());
      loader = document.getElementById('bn-fullscreen-loader');
    }
    if (message) {
      const text = document.getElementById('bn-loader-text');
      if (text) text.textContent = message;
    }
    loader.style.display = 'flex';
    loader.setAttribute('aria-hidden','false');
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
  }
  function hideLoader() {
    const loader = document.getElementById('bn-fullscreen-loader');
    if (loader) { loader.style.display = 'none'; loader.setAttribute('aria-hidden','true'); }
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
  }

  // ----------------- focus trap -----------------
  function trapFocus(container) {
    const focusable = Array.from(container.querySelectorAll('a[href],button,textarea,input,select,[tabindex]:not([tabindex="-1"])')).filter(el => !el.hasAttribute('disabled'));
    if (!focusable.length) return () => {};
    const first = focusable[0], last = focusable[focusable.length - 1];
    function onKey(e) {
      if (e.key !== 'Tab') return;
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
    container.addEventListener('keydown', onKey);
    return () => container.removeEventListener('keydown', onKey);
  }

  // ----------------- Brochure Modal -----------------
  function createBrochureHtml() {
    return `
      <div id="brochureModalBackdrop" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:2147483645;">
        <div id="brochureModal" role="dialog" aria-modal="true" aria-labelledby="brochureTitle" style="width:96%;max-width:900px;max-height:86vh;overflow:auto;background:#fff;border-radius:8px;box-shadow:0 10px 40px rgba(0,0,0,0.4);padding:22px;position:relative;font-family:system-ui,-apple-system,'Segoe UI',Roboto,Arial;">
          <button id="brochureCloseBtn" aria-label="Close" style="position:absolute;right:14px;top:14px;border:none;background:#0b48d1;color:#fff;width:34px;height:34px;border-radius:6px;cursor:pointer;">✕</button>
          <h2 id="brochureTitle" style="margin:0 0 12px;font-size:28px;">Download Brochure</h2>
          <p style="margin:0 0 18px;color:#444;">Please enter your details to access the brochure.</p>
          <form id="brochureForm" novalidate>
            <div style="margin-bottom:12px;"><label for="b_name" style="display:block;font-weight:600;margin-bottom:6px;">Name <span style="color:#d00">*</span></label><input id="b_name" name="name" type="text" autocomplete="name" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:15px;"></div>
            <div style="margin-bottom:12px;"><label for="b_email" style="display:block;font-weight:600;margin-bottom:6px;">Email <span style="color:#d00">*</span></label><input id="b_email" name="email" type="email" autocomplete="email" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:15px;"></div>
            <div style="margin-bottom:12px;"><label for="b_phone" style="display:block;font-weight:600;margin-bottom:6px;">Phone <span style="color:#d00">*</span></label><input id="b_phone" name="phone" type="tel" autocomplete="tel" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:15px;"></div>
            <div style="display:flex;gap:12px;align-items:center;margin-top:8px;"><button id="brochureSubmit" type="submit" style="background:#0b48d1;color:#fff;border:none;padding:10px 18px;border-radius:4px;cursor:pointer;font-weight:600;">Submit</button><div id="brochureStatus" aria-live="polite" style="font-size:14px;color:#333;"></div></div>
          </form>
          <div style="margin-top:18px;font-size:13px;color:#666;"><small>We value your privacy — your details will only be used for event communication.</small></div>
        </div>
      </div>
    `;
  }

  function showBrochureModal() {
    if (document.getElementById('brochureModalBackdrop')) return;
    document.body.insertAdjacentHTML('beforeend', createBrochureHtml());
    if (!document.getElementById('bn-fullscreen-loader')) document.body.insertAdjacentHTML('beforeend', createLoaderHtml());

    const backdrop = document.getElementById('brochureModalBackdrop');
    const modal = document.getElementById('brochureModal');
    const closeBtn = document.getElementById('brochureCloseBtn');
    const form = document.getElementById('brochureForm');
    const status = document.getElementById('brochureStatus');
    const submitBtn = document.getElementById('brochureSubmit');

    const prevOverflow = document.documentElement.style.overflow || '';
    const untrap = trapFocus(modal);

    function closeModal() {
      const b = document.getElementById('brochureModalBackdrop'); if (b) b.remove();
      document.documentElement.style.overflow = prevOverflow;
      document.removeEventListener('keydown', onKeyDown);
      untrap && untrap();
    }
    function onKeyDown(e) { if (e.key === 'Escape') closeModal(); }

    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', (ev) => { if (ev.target === backdrop) closeModal(); });
    document.addEventListener('keydown', onKeyDown);
    setTimeout(() => { document.getElementById('b_name')?.focus(); }, 10);

    let isSubmitting = false;
    form.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      if (isSubmitting) return;

      // basic validation
      status.textContent = ''; status.style.color = '#333';
      const name = (document.getElementById('b_name').value || '').trim();
      const email = (document.getElementById('b_email').value || '').trim();
      const phone = (document.getElementById('b_phone').value || '').trim();
      if (!name || !isEmail(email) || !isPhone(phone)) {
        status.style.color = '#c33'; status.textContent = 'Please fill all fields correctly.';
        return;
      }

      isSubmitting = true;
      submitBtn.disabled = true; submitBtn.style.opacity = '0.6';
      showLoader('Sending your request...');

      try {
        const resp = await fetch(API_ENDPOINT, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ name, email, phone, source: 'brochure-modal' }),
          credentials: 'same-origin'
        });

        let json = null;
        try { json = await resp.json(); } catch (e) { json = null; }

        if (!resp.ok) {
          const msg = (json && json.message) ? json.message : `Server returned ${resp.status}`;
          status.style.color = '#c33'; status.textContent = `Error: ${msg}`;
          return;
        }

        // success -> open brochure (server may return download_url)
        const downloadUrl = (json && json.download_url) ? json.download_url : DOWNLOAD_URL;
        status.style.color = '#0a7'; status.textContent = 'Success — opening brochure...';
        if (downloadUrl) {
          try { window.open(downloadUrl, '_blank', 'noopener,noreferrer'); }
          catch (e) { window.open(downloadUrl, '_blank'); }
        }

        setTimeout(() => closeModal(), 900);

      } catch (err) {
        console.error('Brochure submit failed', err);
        status.style.color = '#c33'; status.textContent = 'Network error: could not send details.';
      } finally {
        hideLoader();
        isSubmitting = false;
        submitBtn.disabled = false; submitBtn.style.opacity = '1';
      }
    }, { once: true });
  }

  // ----------------- JD Modal (no PDF redirect) -----------------
  function buildJDModal() {
    return `
      <div id="jobModalBackdrop" style="position:fixed;inset:0;background:rgba(0,0,0,0.55);display:flex;align-items:center;justify-content:center;z-index:2147483646;">
        <div id="jobModal" role="dialog" aria-modal="true" style="width:94%;max-width:560px;background:#fff;border-radius:6px;box-shadow:0 12px 40px rgba(0,0,0,0.35);padding:22px;position:relative;font-family:system-ui,-apple-system,Roboto,Arial;">
          <button id="jobModalClose" aria-label="Close" style="position:absolute;right:12px;top:12px;border:none;background:transparent;font-size:20px;cursor:pointer;color:#666">✕</button>
          <h2 style="text-align:center;color:#123d7a;margin:4px 0 12px 0;font-size:22px;">Request Job Description</h2>
          <form id="jobModalForm" novalidate>
            <div style="margin-bottom:10px;"><label for="jd_name" style="display:block;font-weight:600;margin-bottom:6px;">Name</label><input id="jd_name" name="name" type="text" style="width:100%;padding:10px;border:1px solid #e3e6e8;border-radius:4px;"></div>
            <div style="margin-bottom:10px;"><label for="jd_email" style="display:block;font-weight:600;margin-bottom:6px;">Email</label><input id="jd_email" name="email" type="email" style="width:100%;padding:10px;border:1px solid #e3e6e8;border-radius:4px;"></div>
            <div style="margin-bottom:10px;"><label for="jd_phone" style="display:block;font-weight:600;margin-bottom:6px;">Phone</label><input id="jd_phone" name="phone" type="tel" style="width:100%;padding:10px;border:1px solid #e3e6e8;border-radius:4px;"></div>
            <div style="display:flex;gap:12px;align-items:center;margin-top:6px;"><button id="jd_submit" type="submit" style="background:#123d7a;color:#fff;border:none;padding:10px 16px;border-radius:4px;cursor:pointer;font-weight:600;">Submit</button><div id="jd_status" style="font-size:14px;color:#333;"></div></div>
          </form>
        </div>
      </div>
    `;
  }

  function showJDModal(cfg) {
    cfg = cfg || {};
    if (document.getElementById('jobModalBackdrop')) return;
    document.body.insertAdjacentHTML('beforeend', buildJDModal());

    const backdrop = document.getElementById('jobModalBackdrop');
    const closeBtn = document.getElementById('jobModalClose');
    const form = document.getElementById('jobModalForm');
    const status = document.getElementById('jd_status');
    const submitBtn = document.getElementById('jd_submit');

    function close() { const b = document.getElementById('jobModalBackdrop'); if (b) b.remove(); document.removeEventListener('keydown', onKey); }
    function onKey(e) { if (e.key === 'Escape') close(); }

    closeBtn.addEventListener('click', close);
    backdrop.addEventListener('click', (ev) => { if (ev.target === backdrop) close(); });
    document.addEventListener('keydown', onKey);
    setTimeout(() => { document.getElementById('jd_name')?.focus(); }, 20);

    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      status.textContent = ''; status.style.color = '#333';
      const name = (document.getElementById('jd_name').value || '').trim();
      const email = (document.getElementById('jd_email').value || '').trim();
      const phone = (document.getElementById('jd_phone').value || '').trim();
      if (!name || !isEmail(email) || !isPhone(phone)) { status.style.color = '#c33'; status.textContent = 'Please fill all fields correctly.'; return; }

      submitBtn.disabled = true; submitBtn.style.opacity = '0.6';
      showLoader('Sending your request...');
      try {
        const resp = await fetch(JD_API_ENDPOINT, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ name, email, phone, source: 'jd-modal' }),
          credentials: 'same-origin'
        });
        const json = await resp.json().catch(()=>null);
        if (!resp.ok) {
          status.style.color = '#c33'; status.textContent = (json && json.message) ? json.message : `Server returned ${resp.status}`;
        } else {
          status.style.color = '#0a7'; status.textContent = (json && json.message) ? json.message : 'Received — we will contact you shortly.';
          setTimeout(() => close(), 1200);
        }
      } catch (err) {
        console.warn('JD submit failed', err);
        status.style.color = '#c33'; status.textContent = 'Network error — please try again.';
      } finally {
        hideLoader();
        submitBtn.disabled = false; submitBtn.style.opacity = '1';
      }
    }, { once: true });
  }

  // ----------------- Apply Modal (full form) -----------------
  const POSITIONS = [
    'CAIE Lower Secondary Senior Faculty',
    'Language Facilitators (Hindi, French, Spanish, German)',
    'PYP Experienced Facilitators',
    'Experienced Early Years Facilitators',
    'SEN Facilitator',
    'Visual Art Facilitator',
    'Associate PYP Coordinator'
  ];

  function buildApplyModal() {
    const positionsHtml = POSITIONS.map(p => `<label style="display:block;margin:6px 0;"><input type="checkbox" name="positions" value="${escapeHtml(p)}"> ${escapeHtml(p)}</label>`).join('');
    return `
      <div class="jn-modal-backdrop" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:2147483647;display:flex;align-items:flex-start;justify-content:center;padding:40px 20px;overflow:auto;">
        <div class="jn-modal" role="dialog" aria-modal="true" style="width:820px;max-width:96%;background:#fff;border-radius:6px;box-shadow:0 12px 40px rgba(0,0,0,.35);position:relative;padding:22px;font-family:system-ui,-apple-system,Roboto,Arial;">
          <button type="button" class="jn-modal-close" aria-label="Close" style="position:absolute;right:12px;top:12px;border:0;background:transparent;font-size:20px;cursor:pointer;color:#666;">✕</button>
          <h2 style="text-align:center;color:#0b3b6f;margin:0 0 12px;">Apply Now</h2>
          <form class="jn-apply-form" enctype="multipart/form-data" novalidate>
            <div style="margin-bottom:10px;"><label style="display:block;margin-bottom:6px;">Name *</label><input name="your-name" type="text" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:3px;"></div>
            <div style="margin-bottom:10px;"><label style="display:block;margin-bottom:6px;">Email *</label><input name="your-email" type="email" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:3px;"></div>
            <div style="margin-bottom:10px;"><label style="display:block;margin-bottom:6px;">Phone *</label><input name="your-phone" type="tel" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:3px;"></div>
            <div style="margin-bottom:10px;"><label style="display:block;margin-bottom:6px;">Upload Resume</label><input name="your-resume" type="file" accept=".pdf,.doc,.docx"></div>
            <div style="margin-bottom:10px;"><label style="display:block;margin-bottom:6px;">Positions</label><div style="max-height:160px;overflow:auto;border:1px solid #f0f0f0;padding:8px;border-radius:3px;">${positionsHtml}</div></div>
            <div style="display:flex;gap:12px;align-items:center;"><button type="submit" class="jn-submit-btn" style="background:#0b3b6f;color:#fff;padding:10px 18px;border-radius:3px;border:0;cursor:pointer;">Submit Application</button><div class="jn-message" role="status" aria-live="polite" style="color:#333;"></div></div>
          </form>
        </div>
      </div>
    `;
  }

//   function openApplyModal() {
//     if (document.querySelector('.jn-modal-backdrop') || document.getElementById('jobModalBackdrop')) return;
//     const modal = elFrom(buildApplyModal());
//     document.body.appendChild(modal);
//     document.documentElement.style.overflow = 'hidden'; document.body.style.overflow = 'hidden';

//     const closeBtn = modal.querySelector('.jn-modal-close');
//     const form = modal.querySelector('.jn-apply-form');
//     const msgEl = modal.querySelector('.jn-message');

//     function close() { if (modal && modal.parentNode) modal.parentNode.removeChild(modal); document.documentElement.style.overflow = ''; document.body.style.overflow = ''; document.removeEventListener('keydown', onKey); }
//     function onKey(e) { if (e.key === 'Escape') close(); }

//     closeBtn.addEventListener('click', close);
//     modal.addEventListener('click', (ev) => { if (ev.target === modal) close(); });
//     document.addEventListener('keydown', onKey);

//     const submitBtn = form.querySelector('.jn-submit-btn');
//     function setMessage(t, c) { msgEl.textContent = t; msgEl.style.color = c || '#333'; }

//     form.addEventListener('submit', function (ev) {
//       ev.preventDefault();
//       setMessage('');
//       const name = (form.querySelector('[name="your-name"]').value || '').trim();
//       const email = (form.querySelector('[name="your-email"]').value || '').trim();
//       const phone = (form.querySelector('[name="your-phone"]').value || '').trim();
//       if (!name) { setMessage('Please enter your name', '#c00'); return; }
//       if (!email || !isEmail(email)) { setMessage('Please enter a valid email', '#c00'); return; }
//       if (!phone || !isPhone(phone)) { setMessage('Please enter a valid phone', '#c00'); return; }

//       const positions = Array.from(form.querySelectorAll('input[name="positions"]:checked')).map(n => n.value);
//       const fd = new FormData();
//       fd.append('name', name);
//       fd.append('email', email);
//       fd.append('phone', phone);
//       positions.forEach(p => fd.append('positions[]', p));
//       const resumeInput = form.querySelector('[name="your-resume"]');
//       if (resumeInput && resumeInput.files && resumeInput.files[0]) fd.append('resume', resumeInput.files[0]);

//       submitBtn.disabled = true; submitBtn.style.opacity = '0.7'; setMessage('Submitting...');

//       // send as multipart/form-data (file support)
//       fetch(APPLY_API_ENDPOINT, { method: 'POST', body: fd, credentials: 'same-origin' })
//         .then(async res => {
//           let json = null;
//           try { json = await res.json(); } catch(e){ json = null; }
//           if (!res.ok) throw new Error((json && json.message) ? json.message : `Server ${res.status}`);
//           setMessage((json && json.message) ? json.message : 'Application received — we will contact you shortly.', '#0a7');
//           setTimeout(() => close(), 1400);
//         })
//         .catch(err => {
//           console.warn('Apply submit failed', err);
//           setMessage('Submission failed — please try again later.', '#c33');
//         })
//         .finally(() => { submitBtn.disabled = false; submitBtn.style.opacity = '1'; });
//     });
//   }

  // ----------------- delegates -----------------
  document.addEventListener('click', function (ev) {
    const brochureEl = ev.target.closest && ev.target.closest('.brochure-tab');
    if (brochureEl) { ev.preventDefault(); showBrochureModal(); return; }

    const jdEl = ev.target.closest && ev.target.closest('.job-btn-orange');
    if (jdEl) { ev.preventDefault(); showJDModal(); return; }

    // const applyEl = ev.target.closest && ev.target.closest('.job-btn-blue, .apply-now');
    // if (applyEl) { ev.preventDefault(); openApplyModal(); return; }
  });

  // expose for programmatic usage
  window.openBrochureModal = showBrochureModal;
  window.openJDModal = showJDModal;
//   window.openApplyModal = openApplyModal;

  console.info('main.js loaded — brochure, JD and apply modals ready.');
})();









// apply-modal.js — open apply popup on job card click and submit to server
(function () {
  'use strict';

  // ----------------- CONFIG -----------------
  // change to your server endpoint that accepts FormData (multipart/form-data)
//   const APPLY_API_ENDPOINT = '/applysubmit.php';

  // Selectors of job buttons on the page
  const JD_VIEW_SEL = '.job-btn-orange';    // View More (we'll open full apply modal)
  const APPLY_SEL   = '.job-btn-blue, .apply-now';

  // Positions list (same as screenshot)
  const POSITIONS = [
    'CAIE Lower Secondary Senior Faculty',
    'Language Facilitators (Hindi, French, Spanish, German)',
    'PYP Experienced Facilitators',
    'Experienced Early Years Facilitators',
    'SEN Facilitator',
    'Visual Art Facilitator',
    'Associate PYP Coordinator'
  ];

  // ----------------- helpers -----------------
  const elFrom = html => {
    const tmp = document.createElement('div');
    tmp.innerHTML = html.trim();
    return tmp.firstChild;
  };

  const isEmail = v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v||'').trim());
  const isPhone = v => (String(v||'').replace(/\D/g,'')).length >= 7;

  function escapeHtml(s) {
    return String(s || '')
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;');
  }

  // lock / unlock scroll
  function lockScroll() {
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
  }
  function unlockScroll() {
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
  }

  // ----------------- build modal html -----------------
  function buildApplyModalHtml() {
    const positionsHtml = POSITIONS.map(p =>
      `<label style="display:block;margin:8px 0;">
         <input type="checkbox" name="positions" value="${escapeHtml(p)}" /> ${escapeHtml(p)}
       </label>`
    ).join('');

    return `
      <div class="apply-modal-backdrop" role="dialog" aria-modal="true" style="position:fixed;inset:0;z-index:2147483647;display:flex;align-items:flex-start;justify-content:center;padding:40px 12px;background:rgba(0,0,0,0.45);">
        <div class="apply-modal" style="width:820px;max-width:96%;background:#fff;border-radius:8px;box-shadow:0 12px 40px rgba(0,0,0,0.4);position:relative;padding:22px;font-family:system-ui, -apple-system, Roboto, Arial;">
          <button type="button" class="apply-modal-close" aria-label="Close" style="position:absolute;right:12px;top:12px;border:0;background:transparent;font-size:20px;cursor:pointer;color:#666;">✕</button>
          <h2 style="text-align:center;margin:0 0 12px 0;color:#0b3b6f;">Apply Now</h2>

          <form class="apply-form" enctype="multipart/form-data" novalidate>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div>
                <label style="display:block;font-weight:600;margin-bottom:6px;">Name <span style="color:#c00">*</span></label>
                <input name="name" type="text" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;">
                <div class="field-error name-error" style="display:none;color:#c33;margin-top:6px;font-size:13px;">Please enter your name.</div>
              </div>

              <div>
                <label style="display:block;font-weight:600;margin-bottom:6px;">Email <span style="color:#c00">*</span></label>
                <input name="email" type="email" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;">
                <div class="field-error email-error" style="display:none;color:#c33;margin-top:6px;font-size:13px;">Please enter a valid email.</div>
              </div>

              <div>
                <label style="display:block;font-weight:600;margin-bottom:6px;">Phone <span style="color:#c00">*</span></label>
                <input name="phone" type="tel" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;">
                <div class="field-error phone-error" style="display:none;color:#c33;margin-top:6px;font-size:13px;">Please enter a valid phone (7+ digits).</div>
              </div>

              <div>
                <label style="display:block;font-weight:600;margin-bottom:6px;">Upload Resume</label>
                <input name="resume" type="file" accept=".pdf,.doc,.docx" style="display:block;">
                <div class="field-help" style="font-size:13px;color:#666;margin-top:6px;">Accepted: PDF, DOC, DOCX</div>
              </div>
            </div>

            <div style="margin-top:12px;">
              <label style="display:block;font-weight:600;margin-bottom:8px;">Position(s) Applying For <span style="color:#c00">*</span></label>
              <div class="positions-list" style="max-height:200px;overflow:auto;border:1px solid #f0f0f0;padding:8px;border-radius:4px;">
                ${positionsHtml}
              </div>
              <div class="field-error positions-error" style="display:none;color:#c33;margin-top:6px;font-size:13px;">Please choose at least one position.</div>
            </div>

            <div style="display:flex;align-items:center;gap:12px;margin-top:16px;">
              <button type="submit" class="apply-submit" style="background:#0b3b6f;color:#fff;border:none;padding:10px 18px;border-radius:4px;cursor:pointer;">Submit</button>
              <div class="apply-message" role="status" aria-live="polite" style="font-size:14px;color:#333;"></div>
            </div>
          </form>
        </div>
      </div>
    `;
  }

  // ----------------- open modal -----------------
//   function openApplyModal() {
//     // avoid duplicates
//     if (document.querySelector('.apply-modal-backdrop')) return;

//     const modal = elFrom(buildApplyModalHtml());
//     document.body.appendChild(modal);
//     lockScroll();

//     const backdrop = document.querySelector('.apply-modal-backdrop');
//     const closeBtn = modal.querySelector('.apply-modal-close');
//     const form = modal.querySelector('.apply-form');
//     const msgEl = modal.querySelector('.apply-message');

//     function closeModal() {
//       if (modal && modal.parentNode) modal.parentNode.removeChild(modal);
//       unlockScroll();
//       document.removeEventListener('keydown', onKey);
//     }
//     function onKey(e) { if (e.key === 'Escape') closeModal(); }
//     document.addEventListener('keydown', onKey);

//     closeBtn.addEventListener('click', closeModal);
//     backdrop.addEventListener('click', (ev) => { if (ev.target === backdrop) closeModal(); });

//     // form handling
//     form.addEventListener('submit', async function (ev) {
//       ev.preventDefault();
//       // hide previous messages/errors
//       msgEl.textContent = '';
//       msgEl.style.color = '#333';
//       modal.querySelectorAll('.field-error').forEach(x => x.style.display = 'none');

//       const name = (form.querySelector('[name="name"]').value || '').trim();
//       const email = (form.querySelector('[name="email"]').value || '').trim();
//       const phone = (form.querySelector('[name="phone"]').value || '').trim();
//       const positions = Array.from(form.querySelectorAll('input[name="positions"]:checked')).map(n => n.value);
//       const resumeInput = form.querySelector('[name="resume"]');

//       // validation
//       let ok = true;
//       if (!name) { modal.querySelector('.name-error').style.display = 'block'; ok = false; }
//       if (!isEmail(email)) { modal.querySelector('.email-error').style.display = 'block'; ok = false; }
//       if (!isPhone(phone)) { modal.querySelector('.phone-error').style.display = 'block'; ok = false; }
//       if (!positions.length) { modal.querySelector('.positions-error').style.display = 'block'; ok = false; }
//       if (!ok) return;

//       const submitBtn = form.querySelector('.apply-submit');
//       submitBtn.disabled = true; submitBtn.style.opacity = '0.6';
//       msgEl.textContent = 'Submitting...';

//       // build FormData
//       const fd = new FormData();
//       fd.append('name', name);
//       fd.append('email', email);
//       fd.append('phone', phone);
//       positions.forEach(p => fd.append('positions[]', p));
//       // append resume file if present
//       if (resumeInput && resumeInput.files && resumeInput.files[0]) {
//         fd.append('resume', resumeInput.files[0]);
//       }

//       // optional: add source / timestamp / other hidden fields
//       fd.append('source', 'apply-modal');
//       // send
//       try {
//         const res = await fetch(APPLY_API_ENDPOINT, {
//           method: 'POST',
//           body: fd,
//           credentials: 'same-origin'
//         });

//         // try parse JSON response
//         let json = null;
//         try { json = await res.json(); } catch (e) { json = null; }

//         if (!res.ok) {
//           const message = (json && json.message) ? json.message : `Server returned ${res.status}`;
//           msgEl.style.color = '#c33';
//           msgEl.textContent = message;
//           submitBtn.disabled = false; submitBtn.style.opacity = '1';
//           return;
//         }

//         // success
//         const okMsg = (json && json.message) ? json.message : 'Application received — we will contact you shortly.';
//         msgEl.style.color = '#0a7';
//         msgEl.textContent = okMsg;

//         // close after short delay
//         setTimeout(() => closeModal(), 1400);

//       } catch (err) {
//         console.warn('Apply submit failed', err);
//         msgEl.style.color = '#c33';
//         msgEl.textContent = 'Network error — please try again later.';
//         submitBtn.disabled = false; submitBtn.style.opacity = '1';
//       }
//     });

//   }
  // openApplyModal

  // ----------------- attach click handlers -----------------
  function attachHandlers() {
    document.addEventListener('click', function (ev) {
      const target = ev.target.closest && (ev.target.closest(JD_VIEW_SEL) || ev.target.closest(APPLY_SEL));
      if (!target) return;
      ev.preventDefault();
    //   openApplyModal();
    }, { passive: false });
  }

  // init
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachHandlers);
  } else {
    attachHandlers();
  }

})();
