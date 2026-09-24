    let activeConfirmResolve = null;
    let confirmPreviousFocus = null;

    function closeConfirmDialog(result = false) {
      const overlay = document.getElementById('confirmOverlay');
      if (overlay.classList.contains('hidden')) return;
      overlay.classList.add('hidden');
      overlay.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('modal-open');
      const resolver = activeConfirmResolve;
      activeConfirmResolve = null;
      if (confirmPreviousFocus && typeof confirmPreviousFocus.focus === 'function') confirmPreviousFocus.focus();
      confirmPreviousFocus = null;
      if (resolver) resolver(Boolean(result));
    }

    function showConfirmDialog({ title = 'Confirm Action', message = 'Are you sure?', confirmText = 'Confirm', tone = 'warning' } = {}) {
      if (activeConfirmResolve) closeConfirmDialog(false);
      const overlay = document.getElementById('confirmOverlay');
      const actionButton = document.getElementById('confirmActionBtn');
      confirmPreviousFocus = document.activeElement;
      document.getElementById('confirmTitle').innerText = title;
      document.getElementById('confirmMessage').innerText = message;
      document.getElementById('confirmIcon').innerText = tone === 'danger' ? '!' : '⚠';
      actionButton.innerText = confirmText;
      overlay.className = 'confirm-overlay confirm-' + (tone === 'danger' ? 'danger' : 'warning');
      overlay.setAttribute('aria-hidden', 'false');
      document.body.classList.add('modal-open');
      window.setTimeout(() => actionButton.focus(), 0);
      return new Promise(resolve => { activeConfirmResolve = resolve; });
    }

    function showToast(type, message, duration = 3500) {
      if (!message) return;
      const region = document.getElementById('toastRegion');
      const toast = document.createElement('div');
      const safeType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
      toast.className = 'app-toast toast-' + safeType;
      toast.setAttribute('role', safeType === 'error' ? 'alert' : 'status');
      const icon = document.createElement('span');
      icon.className = 'toast-icon';
      icon.textContent = safeType === 'success' ? '✓' : (safeType === 'error' ? '!' : 'i');
      const text = document.createElement('span');
      text.className = 'toast-text';
      text.textContent = message;
      const close = document.createElement('button');
      close.type = 'button';
      close.className = 'toast-close';
      close.setAttribute('aria-label', 'Close notification');
      close.textContent = '×';
      const remove = () => {
        toast.classList.add('toast-leaving');
        window.setTimeout(() => toast.remove(), 180);
      };
      close.addEventListener('click', remove);
      toast.append(icon, text, close);
      region.appendChild(toast);
      window.setTimeout(remove, duration);
    }

    document.getElementById('confirmCancelBtn').addEventListener('click', () => closeConfirmDialog(false));
    document.getElementById('confirmActionBtn').addEventListener('click', () => closeConfirmDialog(true));
    document.getElementById('confirmOverlay').addEventListener('click', event => {
      if (event.target.id === 'confirmOverlay') closeConfirmDialog(false);
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && !document.getElementById('confirmOverlay').classList.contains('hidden')) {
        closeConfirmDialog(false);
      }
    });
