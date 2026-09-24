// Extracted from index.html during Phase 2 frontend separation.
    const CLIENT_IDLE_TIMEOUT_MS = 60 * 60 * 1000;
    let clientIdleTimer = null;

    function clearClientIdleTimer() {
      if (clientIdleTimer) window.clearTimeout(clientIdleTimer);
      clientIdleTimer = null;
    }

    function resetClientIdleTimer() {
      if (!currentUser) return;
      clearClientIdleTimer();
      clientIdleTimer = window.setTimeout(() => {
        fetch(`${API}/logout.php`, { method: 'POST' })
          .finally(() => showLogin('Session expired after 60 minutes of inactivity. Please login again.'));
      }, CLIENT_IDLE_TIMEOUT_MS);
    }

    ['pointerdown', 'keydown', 'touchstart'].forEach(eventName => {
      document.addEventListener(eventName, resetClientIdleTimer, { passive: true });
    });

    function checkSession() {
      fetch(`${API}/session.php`)
        .then(response => response.json())
        .then(result => {
          if (result.authenticated) initializeApp(result.user);
          else showLogin();
        })
        .catch(() => showLogin('Unable to check login. Please try again.'));
    }

    function showLogin(message = '') {
      clearClientIdleTimer();
      currentUser = null;
      document.getElementById('appShell').classList.add('hidden');
      document.getElementById('loginView').classList.remove('hidden');
      const banner = document.getElementById('loginMessage');
      banner.className = message ? 'banner error' : 'banner';
      banner.innerText = message;
      document.getElementById('loginPassword').value = '';
      document.getElementById('loginUsername').focus();
    }

    function initializeApp(user) {
      currentUser = user;
      resetClientIdleTimer();
      document.getElementById('loginView').classList.add('hidden');
      document.getElementById('appShell').classList.remove('hidden');
      document.getElementById('currentUserName').innerText = user.operator_name || user.username;
      document.getElementById('currentUserRole').innerText = user.role === 'Admin' ? 'Admin / Director' : user.role;
      document.querySelectorAll('.sidebar-item').forEach(element => element.classList.remove('hidden'));
      document.querySelectorAll('.operator-only').forEach(element => {
        element.classList.toggle('hidden', user.role !== 'Operator/Supervisor');
      });
      document.querySelectorAll('.admin-only').forEach(element => {
        element.classList.toggle('hidden', user.role !== 'Admin');
      });

      if (user.must_change_password) {
        document.querySelectorAll('.sidebar-item').forEach(element => {
          element.classList.toggle('hidden', element.id !== 'navChangePassword');
        });
        switchModule('changePassword');
        const banner = document.getElementById('changePasswordMessage');
        banner.className = 'banner warning';
        banner.innerText = 'Admin ne temporary password set kiya hai. Continue karne se pehle password change karein.';
        return;
      }

      const machineLoad = refreshMachineOptions();
      const operatorLoad = refreshOperatorOptions();
      const partsLoad = fetchParts();
      const cutterLoad = refreshDailyCutters();
      const shiftLoad = refreshShiftOptions();
      const settingsLoad = loadSystemSettings();
      setStartJobFieldsEnabled(false);
      Promise.all([machineLoad, operatorLoad, partsLoad, cutterLoad, shiftLoad, settingsLoad]).then(() => {
        updateShiftHours();
        updateHandoverShiftHours();
        populateReportParts();
        applyOperatorAccess();
        switchModule('dashboard');
        checkScheduledBackup();
      });
    }

    function applyOperatorAccess() {
      const operatorSelect = document.getElementById('operator_id');
      const isOperator = currentUser?.role === 'Operator/Supervisor';
      operatorSelect.disabled = isOperator;
      if (isOperator && currentUser.operator_id) operatorSelect.value = String(currentUser.operator_id);
    }

    function handleLogin(event) {
      event.preventDefault();
      const button = document.getElementById('loginButton');
      const banner = document.getElementById('loginMessage');
      const data = Object.fromEntries(new FormData(event.target).entries());
      button.disabled = true;
      button.innerText = 'Logging in...';
      banner.className = 'banner';

      fetch(`${API}/login.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Login failed');
          event.target.reset();
          initializeApp(result.data.user);
        })
        .catch(error => {
          banner.className = 'banner error';
          banner.innerText = error.message;
        })
        .finally(() => {
          button.disabled = false;
          button.innerText = 'Login';
        });
    }

    function handleLogout() {
      clearClientIdleTimer();
      fetch(`${API}/logout.php`, { method: 'POST' })
        .finally(() => showLogin());
    }

    window.setInterval(() => {
      if (currentUser && !dashboardModule.classList.contains('hidden')) loadMachineDashboard();
    }, 30000);
