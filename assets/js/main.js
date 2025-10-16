/* main.js - brochure modal
   Usage: include on pages where <a class="brochure-tab"> exists
   Configure:
     - API_ENDPOINT: where to POST { name, email, phone }
     - DOWNLOAD_URL: brochure file URL to open on success
*/

(() => {
  // CONFIG: change these to your real endpoints / URLs
  const API_ENDPOINT = '/api/brochure'; // <-- change to your backend endpoint that records leads
  const DOWNLOAD_URL = '/TNS-BrouchureNew.pdf'; // <-- change to actual brochure file

  // Basic helper
  const $ = sel => document.querySelector(sel);
  const $$ = sel => Array.from(document.querySelectorAll(sel));

  function createModalHtml() {
    return `
      <div id="brochureModalBackdrop" style="
          position: fixed; inset: 0; background: rgba(0,0,0,0.6);
          display: flex; align-items: center; justify-content: center;
          z-index: 2147483645;
        ">
        <div id="brochureModal" role="dialog" aria-modal="true" aria-labelledby="brochureTitle"
             style="
               width: 96%;
               max-width: 900px;
               max-height: 86vh;
               overflow: auto;
               background: #fff;
               border-radius: 8px;
               box-shadow: 0 10px 40px rgba(0,0,0,0.4);
               padding: 22px;
               position: relative;
               z-index: 2147483646;
               font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
             ">
          <button id="brochureCloseBtn" aria-label="Close brochure modal" style="
              position: absolute; right: 14px; top: 14px; border: none; background: #12b; color: #fff;
              width: 30px; height: 30px; border-radius: 4px; cursor: pointer;
            ">✕</button>

          <h2 id="brochureTitle" style="margin:0 0 12px 0; font-size:28px;">Download Brochure</h2>
          <p style="margin:0 0 18px 0; color:#444;">Please enter your details to access the brochure.</p>

          <form id="brochureForm" style="display:block;">
            <div style="margin-bottom:14px;">
              <label for="b_name" style="display:block; font-weight:600; margin-bottom:6px;">Name <span style="color:#d00">*</span></label>
              <input id="b_name" name="name" type="text" required
                     style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:15px;" />
              <div class="fieldError" id="err_name" style="display:none; color:#c33; margin-top:6px; font-size:13px;">Please input your name.</div>
            </div>

            <div style="margin-bottom:14px;">
              <label for="b_email" style="display:block; font-weight:600; margin-bottom:6px;">Email Address <span style="color:#d00">*</span></label>
              <input id="b_email" name="email" type="email" required
                     style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:15px;" />
              <div class="fieldError" id="err_email" style="display:none; color:#c33; margin-top:6px; font-size:13px;">Please input a valid email.</div>
            </div>

            <div style="margin-bottom:14px;">
              <label for="b_phone" style="display:block; font-weight:600; margin-bottom:6px;">Phone Number <span style="color:#d00">*</span></label>
              <input id="b_phone" name="phone" type="tel" required
                     style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:15px;" />
              <div class="fieldError" id="err_phone" style="display:none; color:#c33; margin-top:6px; font-size:13px;">Please input a phone number (digits only).</div>
            </div>

            <div style="display:flex; gap:12px; align-items:center; margin-top:8px;">
              <button id="brochureSubmit" type="submit" style="
                background:#0b48d1;color:#fff;border:none;padding:10px 18px;border-radius:4px;cursor:pointer;font-weight:600;
              ">Submit</button>

              <div id="brochureStatus" style="font-size:14px;color:#333;"></div>
            </div>
          </form>

          <div style="margin-top:18px; font-size:13px; color:#666;">
            <small>We value your privacy — your details will only be used for event communication.</small>
          </div>
        </div>
      </div>
    `;
  }

  // Simple validators
  function isValidEmail(e) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e.trim());
  }
  function isValidPhone(p) {
    const cleaned = p.replace(/\D/g,'');
    return cleaned.length >= 7 && cleaned.length <= 15;
  }

  // show modal (inject to body)
  function showBrochureModal() {
    if ($('#brochureModalBackdrop')) return; // already open

    document.body.insertAdjacentHTML('beforeend', createModalHtml());
    const backdrop = $('#brochureModalBackdrop');
    const modal = $('#brochureModal');
    const closeBtn = $('#brochureCloseBtn');
    const form = $('#brochureForm');
    const status = $('#brochureStatus');

    // prevent background scroll while modal open
    const prevOverflow = document.documentElement.style.overflow;
    document.documentElement.style.overflow = 'hidden';

    // focus first input
    setTimeout(() => { $('#b_name')?.focus(); }, 10);

    // close handler
    function closeModal() {
      const b = $('#brochureModalBackdrop');
      if (b) b.remove();
      document.documentElement.style.overflow = prevOverflow || '';
      document.removeEventListener('keydown', onKeyDown);
    }

    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', (ev) => {
      if (ev.target === backdrop) closeModal(); // click outside the modal closes
    });

    function onKeyDown(ev) {
      if (ev.key === 'Escape') closeModal();
    }
    document.addEventListener('keydown', onKeyDown);

    // Simple focus trap (very small)
    modal.addEventListener('keydown', (ev) => {
      if (ev.key === 'Tab') {
        const focusable = modal.querySelectorAll('a,button,input,textarea,select,[tabindex]:not([tabindex="-1"])');
        if (!focusable.length) return;
        const first = focusable[0], last = focusable[focusable.length - 1];
        if (ev.shiftKey && document.activeElement === first) {
          ev.preventDefault(); last.focus();
        } else if (!ev.shiftKey && document.activeElement === last) {
          ev.preventDefault(); first.focus();
        }
      }
    });

    // form submission
    form.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      // clear errors
      $('.fieldError#err_name') && ($('#err_name').style.display = 'none');
      $('#err_email').style.display = 'none';
      $('#err_phone').style.display = 'none';
      status.textContent = '';

      const name = (document.getElementById('b_name').value || '').trim();
      const email = (document.getElementById('b_email').value || '').trim();
      const phone = (document.getElementById('b_phone').value || '').trim();

      let ok = true;
      if (!name) { document.getElementById('err_name').style.display = 'block'; ok = false; }
      if (!isValidEmail(email)) { document.getElementById('err_email').style.display = 'block'; ok = false; }
      if (!isValidPhone(phone)) { document.getElementById('err_phone').style.display = 'block'; ok = false; }

      if (!ok) return;

      // show loading state
      const submitBtn = document.getElementById('brochureSubmit');
      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.6';
      status.textContent = 'Sending...';

      try {
        // POST the data to your backend - expects JSON response { success: true }
        const resp = await fetch(API_ENDPOINT, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name, email, phone, source: 'brochure-modal' }),
          credentials: 'same-origin'
        });

        if (!resp.ok) {
          // try to get json message
          let txt = `Server returned ${resp.status}`;
          try { const j = await resp.json(); if (j && j.message) txt = j.message; } catch(e){}
          status.textContent = `Error: ${txt}`;
          submitBtn.disabled = false;
          submitBtn.style.opacity = '1';
          return;
        }

        const json = await resp.json().catch(()=>({}));

        // treat success loosely: if 2xx -> success
        status.textContent = 'Success! Opening brochure...';
        // open download in new tab
        window.open(DOWNLOAD_URL, '_blank');

        // close modal after short delay
        setTimeout(() => {
          const b = $('#brochureModalBackdrop');
          if (b) b.remove();
          document.documentElement.style.overflow = prevOverflow || '';
        }, 700);

      } catch (err) {
        status.textContent = 'Network error: could not send details.';
        console.error('Brochure submit failed', err);
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
      }
    });
  }

  // Ensure the script attaches handler when DOM ready (works even if script in head)
  function attachHandlers() {
    // delegate clicks for any current/future .brochure-tab
    document.addEventListener('click', (ev) => {
      console.log("in");
      
      const el = ev.target.closest && ev.target.closest('.brochure-tab');
      if (!el) return;
      ev.preventDefault();
      showBrochureModal();
    });

    // Bonus: if there's a single brochure button with id
    if (document.querySelectorAll('.brochure-tab').length === 0) {
      // console.info('No .brochure-tab found on page.');
    }
  }

  // init
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachHandlers);
  } else {
    attachHandlers();
  }

})();

 









// main.js — unified JD Download + Apply modal
(function () {
  // CONFIG: adjust endpoints / defaults here
  const DEFAULT_API_ENDPOINT = '/api/collect-lead'; // optional backend to collect leads
  const DEFAULT_JD_URL       = '/assets/files/job-description.pdf'; // fallback JD file

  // Selectors for triggers
  const DOWNLOAD_SELECTOR = '.job-btn-orange'; // Download JD (simple lead form)
  const APPLY_SELECTOR    = '.job-btn-blue, .apply-now'; // Apply Now (full apply form)

  // Utility helpers
  const isEmail = v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test((v||'').trim());
  const isPhone = v => (v||'').replace(/\D/g,'').length >= 7;
  const elFrom = html => {
    const tmp = document.createElement('div');
    tmp.innerHTML = html.trim();
    return tmp.firstChild;
  };

  // Prevent duplicate modal
  function isModalOpen() {
    return !!document.querySelector('.jn-modal-backdrop') || !!document.getElementById('jobModalBackdrop');
  }

  // Close Popup Maker / others if present (to avoid stacked modals)
  function closeOtherPopups() {
    try {
      if (window.PUM && typeof window.PUM.closeAll === 'function') {
        window.PUM.closeAll();
      }
      // Add other plugin close calls here if needed
    } catch (e) {
      console.warn('closeOtherPopups error', e);
    }
  }

  // LOCK / UNLOCK scrolling
  function lockBodyScroll() {
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
  }
  function unlockBodyScroll() {
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
  }

  /****************** Download JD small modal ******************/
  function buildDownloadModal() {
    const html = `
      <div id="jobModalBackdrop" style="position:fixed;inset:0;background:rgba(0,0,0,0.55);display:flex;align-items:center;justify-content:center;z-index:2147483646;">
        <div id="jobModal" role="dialog" aria-modal="true" style="width:94%;max-width:880px;background:#fff;border-radius:6px;box-shadow:0 12px 40px rgba(0,0,0,0.35);max-height:86vh;overflow:auto;padding:22px;position:relative;font-family:system-ui, -apple-system, Roboto, Arial;">
          <button id="jobModalClose" aria-label="Close" style="position:absolute;right:12px;top:12px;border:none;background:transparent;font-size:20px;cursor:pointer;color:#666">✕</button>
          <h2 style="text-align:center;color:#123d7a;margin:4px 0 12px 0;font-size:26px;">Download Brochure / JD</h2>
          <form id="jobModalForm" style="padding:8px 0 4px 0;">
            <div style="margin-bottom:12px;">
              <label for="jd_name" style="display:block;font-weight:600;margin-bottom:6px;">Name</label>
              <input id="jd_name" name="name" type="text" style="width:100%;padding:10px;border:1px solid #e3e6e8;border-radius:4px;font-size:15px;">
              <div id="err_name" style="display:none;color:#c0392b;margin-top:6px;font-size:13px;">Please enter your name.</div>
            </div>
            <div style="margin-bottom:12px;">
              <label for="jd_phone" style="display:block;font-weight:600;margin-bottom:6px;">Phone</label>
              <input id="jd_phone" name="phone" type="tel" style="width:100%;padding:10px;border:1px solid #e3e6e8;border-radius:4px;font-size:15px;">
              <div id="err_phone" style="display:none;color:#c0392b;margin-top:6px;font-size:13px;">Please enter a valid phone.</div>
            </div>
            <div style="margin-bottom:14px;">
              <label for="jd_email" style="display:block;font-weight:600;margin-bottom:6px;">Email</label>
              <input id="jd_email" name="email" type="email" style="width:100%;padding:10px;border:1px solid #e3e6e8;border-radius:4px;font-size:15px;">
              <div id="err_email" style="display:none;color:#c0392b;margin-top:6px;font-size:13px;">Please enter a valid email.</div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;margin-top:6px;">
              <button id="jd_submit" type="submit" style="background:#123d7a;color:#fff;border:none;padding:10px 16px;border-radius:4px;cursor:pointer;font-weight:600;">Submit</button>
              <div id="jd_status" style="font-size:14px;color:#333;"></div>
            </div>
          </form>
          <p style="font-size:13px;color:#666;margin-top:14px;">We respect your privacy. Your contact will only be used for recruitment communication.</p>
        </div>
      </div>`;
    return elFrom(html);
  }

  function openDownloadModal(cfg) {
    if (isModalOpen()) return;
    cfg = cfg || {};
    closeOtherPopups();

    const modalEl = buildDownloadModal();
    document.body.appendChild(modalEl);
    lockBodyScroll();

    const prevOverflow = document.documentElement.style.overflow;

    const backdrop = document.getElementById('jobModalBackdrop');
    const closeBtn = document.getElementById('jobModalClose');
    const form = document.getElementById('jobModalForm');
    const status = document.getElementById('jd_status');

    // Close helpers
    function close() {
      const b = document.getElementById('jobModalBackdrop');
      if (b) b.remove();
      unlockBodyScroll();
      document.removeEventListener('keydown', onKey);
    }
    function onKey(ev) { if (ev.key === 'Escape') close(); }

    closeBtn.addEventListener('click', close);
    backdrop.addEventListener('click', ev => { if (ev.target === backdrop) close(); });
    document.addEventListener('keydown', onKey);

    // Focus first input
    setTimeout(() => { const f = document.getElementById('jd_name'); if (f) f.focus(); }, 20);

    // Submit handler
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      // hide errors
      document.getElementById('err_name').style.display = 'none';
      document.getElementById('err_phone').style.display = 'none';
      document.getElementById('err_email').style.display = 'none';
      status.textContent = '';

      const name = (document.getElementById('jd_name').value || '').trim();
      const phone = (document.getElementById('jd_phone').value || '').trim();
      const email = (document.getElementById('jd_email').value || '').trim();

      let ok = true;
      if (!name) { document.getElementById('err_name').style.display = 'block'; ok = false; }
      if (!isPhone(phone)) { document.getElementById('err_phone').style.display = 'block'; ok = false; }
      if (!isEmail(email)) { document.getElementById('err_email').style.display = 'block'; ok = false; }
      if (!ok) return;

      const submitBtn = document.getElementById('jd_submit');
      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.6';
      status.textContent = 'Sending...';

      const api = cfg.apiEndpoint || DEFAULT_API_ENDPOINT;
      const jdUrl = cfg.downloadUrl || DEFAULT_JD_URL;

      // Try to POST lead (best-effort)
      try {
        const res = await fetch(api, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name, phone, email, source: 'download-jd' }),
          credentials: 'same-origin'
        });
        if (!res.ok) {
          // server error — still attempt open jd
          status.textContent = 'Saved locally. Opening JD...';
        } else {
          status.textContent = 'Success! Opening JD...';
        }
      } catch (err) {
        console.warn('Download JD POST failed', err);
        status.textContent = 'Network error. Opening JD...';
      }

      // open JD and close
      setTimeout(() => {
        if (cfg.downloadUrl) window.open(cfg.downloadUrl, '_blank');
        else window.open(DEFAULT_JD_URL, '_blank');
        close();
      }, 600);
    }, { once: true });
  }

  /****************** Apply Now full modal ******************/
  const POSITIONS = [
    'CAIE Lower Secondary Senior Faculty',
    'Language Facilitators (Hindi, French, Spanish, German)',
    'PYP Experienced Facilitators',
    'Experienced Early Years Facilitators',
    'SEN Facilitator',
    'Visual Art Facilitator',
    'Associate PYP Coordinator'
  ];

  function buildApplyModal(downloadUrl) {
    const positionsHtml = POSITIONS.map(p => `
      <label style="display:block;margin:8px 0;">
        <input type="checkbox" name="positions" value="${escapeHtml(p)}" /> ${escapeHtml(p)}
      </label>`).join('');

    const html = `
      <div class="jn-modal-backdrop" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9998;display:flex;align-items:flex-start;justify-content:center;padding:40px 20px;overflow:auto;">
        <div class="jn-modal" role="dialog" aria-modal="true" aria-label="Apply — Download JD" style="width:800px;max-width:96%;background:#fff;border-radius:4px;box-shadow:0 8px 40px rgba(0,0,0,.35);z-index:9999;position:relative;">
          <button type="button" class="jn-modal-close" aria-label="Close" style="position:absolute;right:12px;top:8px;border:0;background:transparent;font-size:20px;cursor:pointer;color:#666;">✕</button>
          <div style="padding:22px 28px 18px;">
            <h2 style="text-align:center;color:#0b3b6f;margin:0 0 12px;font-weight:600;">Apply Now</h2>
            <form class="jn-jd-form" enctype="multipart/form-data" novalidate>
              <div style="margin:12px 0;"><label style="display:block;font-weight:600;margin-bottom:6px;">Name <span style="color:#c00">*</span></label><input name="your-name" type="text" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:3px;" /></div>
              <div style="margin:12px 0;"><label style="display:block;font-weight:600;margin-bottom:6px;">Email Address <span style="color:#c00">*</span></label><input name="your-email" type="email" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:3px;" /></div>
              <div style="margin:12px 0;"><label style="display:block;font-weight:600;margin-bottom:6px;">Phone Number <span style="color:#c00">*</span></label><input name="your-phone" type="tel" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:3px;" /></div>
              <div style="margin:12px 0;"><label style="display:block;font-weight:600;margin-bottom:6px;">Upload Resume</label><input name="your-resume" type="file" accept=".pdf,.doc,.docx" style="display:block;" /></div>
              <div style="margin:12px 0;"><label style="display:block;font-weight:600;margin-bottom:6px;">Position(s) Applying For</label><div class="jn-positions" style="max-height:200px;overflow:auto;border:1px solid #f0f0f0;padding:8px;border-radius:3px;">${positionsHtml}</div></div>
              <div style="margin-top:18px;display:flex;align-items:center;gap:12px;"><button type="submit" class="jn-submit-btn" style="background:#0b3b6f;color:#fff;padding:10px 18px;border-radius:3px;border:0;cursor:pointer;">Submit</button><div class="jn-message" role="status" aria-live="polite" style="color:#333;"></div></div>
            </form>
          </div>
        </div>
      </div>`;
    return elFrom(html);
  }

  function escapeHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function openApplyModal(button) {
    if (isModalOpen()) return;
    closeOtherPopups();
    const cf7Id = (button && button.dataset && button.dataset.cf7) ? button.dataset.cf7 : null;
    const downloadUrl = (button && button.dataset && (button.dataset.download || button.dataset.jd)) ? (button.dataset.download || button.dataset.jd) : DEFAULT_JD_URL;

    const modal = buildApplyModal(downloadUrl);
    document.body.appendChild(modal);
    lockBodyScroll();

    const backdrop = modal;
    const dialog = modal.querySelector('.jn-modal');
    const closeBtn = modal.querySelector('.jn-modal-close');
    const form = modal.querySelector('.jn-jd-form');
    const msgEl = modal.querySelector('.jn-message');

    const firstInput = form.querySelector('[name="your-name"]');
    if (firstInput) firstInput.focus();

    function closeModal() {
      if (modal && modal.parentNode) modal.parentNode.removeChild(modal);
      unlockBodyScroll();
      document.removeEventListener('keydown', onKeyDown);
    }
    function onKeyDown(e) { if (e.key === 'Escape') closeModal(); }
    document.addEventListener('keydown', onKeyDown);

    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', function (ev) { if (ev.target === backdrop) closeModal(); });

    function setMessage(t, c) { msgEl.textContent = t; msgEl.style.color = c || '#333'; }

    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      setMessage('');
      const name = form.querySelector('[name="your-name"]').value.trim();
      const email = form.querySelector('[name="your-email"]').value.trim();
      const phone = form.querySelector('[name="your-phone"]').value.trim();
      if (!name) { setMessage('Please enter your name', '#c00'); return; }
      if (!email || !isEmail(email)) { setMessage('Please enter a valid email', '#c00'); return; }
      if (!phone || !isPhone(phone)) { setMessage('Please enter a phone number', '#c00'); return; }

      const positions = Array.from(form.querySelectorAll('input[name="positions"]:checked')).map(n => n.value);
      const fd = new FormData();
      fd.append('your-name', name);
      fd.append('your-email', email);
      fd.append('your-phone', phone);
      positions.forEach(p => fd.append('positions[]', p));
      const resumeInput = form.querySelector('[name="your-resume"]');
      if (resumeInput && resumeInput.files && resumeInput.files[0]) fd.append('your-resume', resumeInput.files[0]);

      const submitBtn = form.querySelector('.jn-submit-btn');
      submitBtn.disabled = true; submitBtn.style.opacity = '0.7';
      setMessage('Submitting...');

      if (cf7Id) {
        const endpoint = `/wp-json/contact-form-7/v1/contact-forms/${encodeURIComponent(cf7Id)}/feedback`;
        fetch(endpoint, {
          method: 'POST',
          body: fd,
          credentials: 'same-origin'
        }).then(res => { if (!res.ok) throw new Error('Network response not ok'); return res.json(); })
          .then(json => {
            if (json && (json.status === 'mail_sent' || json.status === 'sent')) {
              setMessage('Thanks — redirecting to the JD...', '#0a7');
              setTimeout(()=> { if (downloadUrl) window.open(downloadUrl, '_blank'); closeModal(); }, 700);
            } else {
              setMessage(json.message || 'Submission saved. Opening JD...', '#c60');
              setTimeout(()=> { if (downloadUrl) window.open(downloadUrl, '_blank'); closeModal(); }, 700);
            }
          }).catch(err => {
            console.warn('CF7 submit failed', err);
            setMessage('Submission failed (server). Opening JD anyway...', '#c60');
            setTimeout(()=> { if (downloadUrl) window.open(downloadUrl, '_blank'); closeModal(); }, 700);
          }).finally(()=> { submitBtn.disabled = false; submitBtn.style.opacity = '1'; });
      } else {
        // No CF7: you can POST to your own endpoint here. For now open JD.
        setMessage('Thanks — opening JD...', '#0a7');
        setTimeout(()=> { if (downloadUrl) window.open(downloadUrl, '_blank'); closeModal(); }, 600);
        submitBtn.disabled = false; submitBtn.style.opacity = '1';
      }
    });
  }

  // Delegated click handler — uses explicit selectors to avoid triggering plugin triggers accidentally.
  document.addEventListener('click', function (ev) {
    const target = ev.target.closest && ev.target.closest(DOWNLOAD_SELECTOR);
    if (target) {
      // If plugin has its own popup attached, bail out to plugin unless we explicitly want to override
      if (target.hasAttribute('data-pum-target') || target.classList.contains('pum-trigger') || target.hasAttribute('data-popup')) {
        // Let plugin handle it — but if you still want to override, remove the 'return'.
        return;
      }
      ev.preventDefault();
      // optional: read download URL from data attribute
      openDownloadModal({
        apiEndpoint: target.dataset.api || DEFAULT_API_ENDPOINT,
        downloadUrl: target.dataset.download || target.dataset.jd || DEFAULT_JD_URL
      });
      return;
    }

    const applyBtn = ev.target.closest && ev.target.closest(APPLY_SELECTOR);
    if (applyBtn) {
      // if plugin already handles this trigger, skip (avoids double popup)
      if (applyBtn.hasAttribute('data-pum-target') || applyBtn.classList.contains('pum-trigger') || applyBtn.hasAttribute('data-popup')) {
        return;
      }
      ev.preventDefault();
      openApplyModal(applyBtn);
      return;
    }
  });

  // Expose programmatic open if needed
  window.openJobDownloadModal = function (opts) { openDownloadModal(opts || {}); };
  window.openJobApplyModal = function (btnEl) { openApplyModal(btnEl || {}); };

})();
