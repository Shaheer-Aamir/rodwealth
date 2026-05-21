// ============================================================
//  FORM INTEGRATION - works for both homepage & contact page
//  Place this file in your ROOT project folder
//  Add this at the bottom of BOTH pages before </body>:
//  <script src="form-integration.js"></script>
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // Grab all forms with class "custom-email-form" on the page
  const forms = document.querySelectorAll('.custom-email-form');

  forms.forEach(function (form) {

    const loading   = form.querySelector('.loading');
    const errorEl   = form.querySelector('.error-message');
    const successEl = form.querySelector('.sent-message');
    const btn       = form.querySelector('button[type="submit"]');

    // Hide all status elements initially
    if (loading)   loading.style.display   = 'none';
    if (errorEl)   errorEl.style.display   = 'none';
    if (successEl) successEl.style.display = 'none';

    form.addEventListener('submit', async function (e) {
      e.preventDefault(); // stop normal page redirect

      // Show loading
      if (loading)   loading.style.display   = 'block';
      if (errorEl)   errorEl.style.display   = 'none';
      if (successEl) successEl.style.display = 'none';
      if (btn)       btn.disabled = true;

      // Auto-detect which form this is
      // Contact page has #projectType element, homepage does not
      const source = document.getElementById('projectType') ? 'contact' : 'homepage';

      const formData = new FormData(form);
      formData.append('source', source);

      try {
        const res  = await fetch('submit.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (loading) loading.style.display = 'none';

        if (data.success) {
          if (successEl) successEl.style.display = 'block';
          form.reset();
        } else {
          if (errorEl) {
            errorEl.textContent   = data.message || 'Something went wrong. Please try again.';
            errorEl.style.display = 'block';
          }
        }

      } catch (err) {
        if (loading) loading.style.display = 'none';
        if (errorEl) {
          errorEl.textContent   = 'Network error. Please check your connection and try again.';
          errorEl.style.display = 'block';
        }
        console.error('Form submission error:', err);
      } finally {
        if (btn) btn.disabled = false;
      }
    });
  });

});