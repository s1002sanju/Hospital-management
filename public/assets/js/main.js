/**
 * CityCare Hospital - Core UI Interactions & Accessibility Helpers
 */
document.addEventListener('DOMContentLoaded', function () {
  // 1. Mobile Navigation Toggle
  const navToggle = document.querySelector('.nav-toggle');
  const navMenu = document.querySelector('#mainNav');
  if (navToggle && navMenu) {
    navToggle.addEventListener('click', function () {
      const isExpanded = navToggle.getAttribute('aria-expanded') === 'true';
      navToggle.setAttribute('aria-expanded', !isExpanded);
      navMenu.classList.toggle('nav-open');
    });

    // Close mobile nav when clicking outside
    document.addEventListener('click', function (e) {
      if (!navToggle.contains(e.target) && !navMenu.contains(e.target) && navMenu.classList.contains('nav-open')) {
        navMenu.classList.remove('nav-open');
        navToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // 2. Password Visibility Toggle
  document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const targetSelector = btn.getAttribute('data-toggle-password');
      const input = document.querySelector(targetSelector);
      if (!input) return;

      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
      
      const icon = btn.querySelector('.pw-icon');
      if (icon) {
        if (isPassword) {
          // Eye closed icon (slash)
          icon.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
        } else {
          // Eye open icon
          icon.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        }
      }
    });
  });

  // 3. Form Submit Feedback (Loading spinner on submit)
  document.querySelectorAll('form').forEach(function (form) {
    // Skip forms with confirm prompt or file uploads where browser handles progress
    form.addEventListener('submit', function (e) {
      if (form.dataset.submitting === 'true') {
        e.preventDefault();
        return;
      }
      
      const submitBtn = form.querySelector('button[type="submit"]:not([data-no-loader]), button:not([type]):not([data-no-loader])');
      if (submitBtn && !form.checkValidity || (form.checkValidity && form.checkValidity())) {
        // Only trigger spinner if form is valid
        form.dataset.submitting = 'true';
        submitBtn.classList.add('btn-loading');
        const origText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner" aria-hidden="true"></span> Processing...';
        
        // Safety timeout to reset if navigation does not happen
        setTimeout(function () {
          form.dataset.submitting = 'false';
          submitBtn.classList.remove('btn-loading');
          submitBtn.innerHTML = origText;
        }, 8000);
      }
    });
  });

  // 4. Auto-dismiss alerts after 6 seconds with fade
  const flashAlerts = document.querySelectorAll('.alert:not(.alert-permanent)');
  flashAlerts.forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-6px)';
      setTimeout(function () {
        if (alert.parentNode) alert.parentNode.removeChild(alert);
      }, 400);
    }, 6000);
  });
});

