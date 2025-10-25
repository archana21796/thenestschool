// apply-modal.js — open apply popup on .applynow click and submit to server
(function () {
  'use strict';

  // ----------------- CONFIG -----------------
  const APPLY_API_ENDPOINT = '/applysubmit.php';

  // only target buttons with class `applynow`
  const APPLY_SEL = '.applynow';

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
  function escapeHtml(s){
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // Safer lock / unlock scroll (avoids many mobile keyboard/in-app issues)
  let _scrollTop = 0;
  function lockScroll() {
    // store scroll pos
    _scrollTop = window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0;
    // apply fixed positioning to body (preserves layout and avoids overflow:hidden issues in many mobile webviews)
    document.documentElement.style.scrollBehavior = 'auto';
    document.body.style.position = 'fixed';
    document.body.style.top = '-' + _scrollTop + 'px';
    document.body.style.left = '0';
    document.body.style.right = '0';
  }
  function unlockScroll() {
    // restore
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.left = '';
    document.body.style.right = '';
    // restore scroll
    window.scrollTo(0, _scrollTop);
    _scrollTop = 0;
    document.documentElement.style.scrollBehavior = '';
  }

  // ----------------- modal HTML -----------------
  function buildApplyModalHtml() {
    const positionsHtml = POSITIONS.map(p => (
      `<label style="display:block;margin:8px 0;">
         <input type="checkbox" name="positions" value="${escapeHtml(p)}" /> ${escapeHtml(p)}
       </label>`
    )).join('');

    return `
      <div class="apply-modal-backdrop" role="dialog" aria-modal="true" style="position:fixed;inset:0;z-index:2147483647;display:flex;align-items:flex-start;justify-content:center;padding:40px 12px;background:rgba(0,0,0,0.45);">
        <div class="apply-modal" style="width:820px;max-width:96%;background:#fff;border-radius:8px;box-shadow:0 12px 40px rgba(0,0,0,0.4);position:relative;padding:22px;font-family:system-ui, -apple-system, Roboto, Arial;">
          <button type="button" class="apply-modal-close" aria-label="Close" style="position:absolute;right:12px;top:12px;border:0;background:transparent;font-size:20px;cursor:pointer;color:#666;">✕</button>
          <h2 style="text-align:center;margin:0 0 12px 0;color:#0b3b6f;">Apply Now</h2>

          <form class="apply-form" enctype="multipart/form-data" novalidate>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div>
                <label style="display:block;font-weight:600;margin-bottom:6px;">Name <span style="color:#c00">*</span></label>
                <input name="name" type="text" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:16px;">
                <div class="field-error name-error" style="display:none;color:#c33;margin-top:6px;font-size:13px;">Please enter your name.</div>
              </div>

              <div>
                <label style="display:block;font-weight:600;margin-bottom:6px;">Email <span style="color:#c00">*</span></label>
                <input name="email" type="email" required autocomplete="email" autocapitalize="none" inputmode="email" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:16px;">
                <div class="field-error email-error" style="display:none;color:#c33;margin-top:6px;font-size:13px;">Please enter a valid email.</div>
              </div>

              <div>
                <label style="display:block;font-weight:600;margin-bottom:6px;">Phone <span style="color:#c00">*</span></label>
                <input name="phone" type="tel" required autocomplete="tel" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:16px;">
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
  function openApplyModal() {
    if (document.querySelector('.apply-modal-backdrop')) return;

    const modal = elFrom(buildApplyModalHtml());
    document.body.appendChild(modal);
    lockScroll();

    const backdrop = document.querySelector('.apply-modal-backdrop');
    const closeBtn = modal.querySelector('.apply-modal-close');
    const form = modal.querySelector('.apply-form');
    const msgEl = modal.querySelector('.apply-message');

    function closeModal() {
      if (modal && modal.parentNode) modal.parentNode.removeChild(modal);
      unlockScroll();
      document.removeEventListener('keydown', onKey);
    }
    function onKey(e) { if (e.key === 'Escape') closeModal(); }
    document.addEventListener('keydown', onKey);

    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', (ev) => { if (ev.target === backdrop) closeModal(); });

    // input-focus safety for some mobile webviews (allow scrolling when keyboard opens)
    form.querySelectorAll('input, textarea, select').forEach(el => {
      el.addEventListener('focus', () => {
        // small timeout to allow keyboard to open, and ensure scroll works
        setTimeout(() => {
          // if body is fixed (we used lockScroll), temporarily allow scrolling while input is focused
          if (document.body.style.position === 'fixed') {
            document.body.style.position = '';
            document.body.style.top = '';
            // we don't call unlockScroll() fully here to avoid layout jump; we'll restore on blur
          }
        }, 250);
      });
      el.addEventListener('blur', () => {
        // restore lock after blur (if modal still open)
        if (document.querySelector('.apply-modal-backdrop')) {
          // restore fixed positioning again
          document.body.style.position = 'fixed';
          document.body.style.top = '-' + (_scrollTop || 0) + 'px';
        }
      });
    });

    // form handling
    form.addEventListener('submit', async function (ev) {
      ev.preventDefault();
      msgEl.textContent = '';
      msgEl.style.color = '#333';
      modal.querySelectorAll('.field-error').forEach(x => x.style.display = 'none');

      const name = (form.querySelector('[name="name"]').value || '').trim();
      const email = (form.querySelector('[name="email"]').value || '').trim();
      const phone = (form.querySelector('[name="phone"]').value || '').trim();
      const positions = Array.from(form.querySelectorAll('input[name="positions"]:checked')).map(n => n.value);
      const resumeInput = form.querySelector('[name="resume"]');

      let ok = true;
      if (!name) { modal.querySelector('.name-error').style.display = 'block'; ok = false; }
      if (!isEmail(email)) { modal.querySelector('.email-error').style.display = 'block'; ok = false; }
      if (!isPhone(phone)) { modal.querySelector('.phone-error').style.display = 'block'; ok = false; }
      if (!positions.length) { modal.querySelector('.positions-error').style.display = 'block'; ok = false; }
      if (!ok) return;

      const submitBtn = form.querySelector('.apply-submit');
      submitBtn.disabled = true; submitBtn.style.opacity = '0.6';
      msgEl.textContent = 'Submitting...';

      const fd = new FormData();
      fd.append('name', name);
      fd.append('email', email);
      fd.append('phone', phone);
      positions.forEach(p => fd.append('positions[]', p));
      if (resumeInput && resumeInput.files && resumeInput.files[0]) {
        fd.append('resume', resumeInput.files[0]);
      }
      fd.append('source', 'apply-modal');

      try {
        const res = await fetch(APPLY_API_ENDPOINT, { method: 'POST', body: fd, credentials: 'same-origin' });
        let json = null;
        try { json = await res.json(); } catch (e) { json = null; }

        if (!res.ok) {
          const message = (json && json.message) ? json.message : `Server returned ${res.status}`;
          msgEl.style.color = '#c33';
          msgEl.textContent = message;
          submitBtn.disabled = false; submitBtn.style.opacity = '1';
          return;
        }

        const okMsg = (json && json.message) ? json.message : 'Application received — we will contact you shortly.';
        msgEl.style.color = '#0a7';
        msgEl.textContent = okMsg;
        setTimeout(() => closeModal(), 1400);

      } catch (err) {
        console.warn('Apply submit failed', err);
        msgEl.style.color = '#c33';
        msgEl.textContent = 'Network error — please try again later.';
        submitBtn.disabled = false; submitBtn.style.opacity = '1';
      }
    });

  } // openApplyModal

  // ----------------- attach click handlers (event delegation) -----------------
  function attachHandlers() {
    document.addEventListener('click', function (ev) {
      // find closest ancestor matching APPLY_SEL
      const target = ev.target.closest && ev.target.closest(APPLY_SEL);
      if (!target) return;
      ev.preventDefault();
      openApplyModal();
    }, { passive: false });
  }

  // init
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachHandlers);
  } else {
    attachHandlers();
  }

})();
