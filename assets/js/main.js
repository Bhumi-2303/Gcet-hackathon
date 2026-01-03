document.addEventListener('DOMContentLoaded', function(){
  // Simple toast utility
  function toast(message, type = 'success'){
    var container = document.querySelector('.toast-container');
    if (!container){ container = document.createElement('div'); container.className = 'toast-container'; document.body.appendChild(container); }
    var t = document.createElement('div'); t.className = 'toast ' + (type === 'error' ? 'error' : 'success'); t.textContent = message;
    container.appendChild(t);
    setTimeout(function(){ t.style.opacity = '0'; t.addEventListener('transitionend', function(){ t.remove(); }); }, 3200);
  }

  // Password toggle (accessible)
  document.querySelectorAll('.toggle-pass').forEach(function(btn){
    btn.addEventListener('click', function(){
      var wrapper = btn.closest('.password-wrap');
      if(!wrapper) return;
      var input = wrapper.querySelector('input');
      var showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      btn.textContent = showing ? '👁️' : '🙈';
      btn.setAttribute('aria-pressed', (!showing).toString());
    });
  });

  // Drag & drop upload support and preview
  var logoInput = document.getElementById('logoInput');
  var logoPreview = document.getElementById('logoPreview');
  var dropZone = document.getElementById('logoDropZone');
  var dropInfo = document.getElementById('logoDropInfo');

  function showPreview(file){
    if (!file) {
      logoPreview.style.backgroundImage = '';
      logoPreview.textContent = '';
      if (dropInfo) { dropInfo.style.display = 'block'; }
      return;
    }
    var url = URL.createObjectURL(file);
    logoPreview.textContent = '';
    logoPreview.style.backgroundImage = 'url("'+url+'")';
    if (dropInfo) { dropInfo.style.display = 'none'; }
  }

  if (logoInput && logoPreview && dropZone){
    // click to open
    dropZone.addEventListener('click', function(){ logoInput.click(); });
    // keyboard support
    dropZone.addEventListener('keydown', function(e){ if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); logoInput.click(); }});

    // file selected
    logoInput.addEventListener('change', function(){ showPreview(this.files[0]); });

    // dragover
    dropZone.addEventListener('dragover', function(e){ e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', function(e){ dropZone.classList.remove('dragover'); });
    dropZone.addEventListener('drop', function(e){ e.preventDefault(); dropZone.classList.remove('dragover'); var f = e.dataTransfer.files && e.dataTransfer.files[0]; if (f){
      // assign file to input
      var dt = new DataTransfer(); dt.items.add(f); logoInput.files = dt.files; showPreview(f);
    }});
  }

  // Signup form validation (inline + toast)
  var signupForm = document.querySelector('form[action="auth/register.php"]');
  if (signupForm){
    signupForm.addEventListener('submit', function(e){
      var p = signupForm.querySelector('input[name="password"]');
      var c = signupForm.querySelector('input[name="confirm_password"]');
      if (p && c && p.value !== c.value){ e.preventDefault(); toast('Passwords do not match', 'error'); c.focus(); return; }
      var email = signupForm.querySelector('input[name="email"]');
      if (email && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email.value)) { e.preventDefault(); toast('Invalid email address', 'error'); email.focus(); return; }
      var phone = signupForm.querySelector('input[name="phone"]');
      if (phone && !phone.value.trim()) { e.preventDefault(); toast('Phone is required', 'error'); phone.focus(); return; }
      var logoIn = signupForm.querySelector('input[name="logo"]');
      if (logoIn && logoIn.files.length === 0) { e.preventDefault(); toast('Company logo is required', 'error'); logoIn.focus(); return; }
    });

    // client-side password strength hint
    var pwd = signupForm.querySelector('input[name="password"]');
    if (pwd){
      pwd.addEventListener('input', function(){
        var len = pwd.value.length;
        if (len < 6) { pwd.style.borderColor = '#fca5a5'; }
        else if (len < 10) { pwd.style.borderColor = '#fcd34d'; }
        else { pwd.style.borderColor = 'rgba(16,185,129,0.8)'; }
      });
    }
  }

  var signinForm = document.querySelector('form[action="auth/login.php"]');
  if (signinForm) {
    signinForm.addEventListener('submit', function(e){
      var email = signinForm.querySelector('input[name="email"]');
      var pw = signinForm.querySelector('input[name="password"]');
      if (!email.value.trim() || !pw.value.trim()) { e.preventDefault(); toast('Please enter both email and password', 'error'); if (!email.value.trim()) email.focus(); else pw.focus(); }
    });
  }

  // Show server messages as toasts (if any alerts exist on the page)
  document.querySelectorAll('.alert').forEach(function(a){
    if (a.classList.contains('alert-error')) toast(a.textContent.trim(), 'error');
    else if (a.classList.contains('alert-success')) toast(a.textContent.trim(), 'success');
  });
});