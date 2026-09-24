// Extracted from index.html during Phase 2 frontend separation.
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
      document.getElementById('loginView').classList.add('hidden');
      document.getElementById('appShell').classList.remove('hidden');
      document.getElementById('currentUserName').innerText = user.operator_name || user.username;
      document.getElementById('currentUserRole').innerText = user.role === 'Admin' ? 'Admin / Director' : user.role;
      document.querySelectorAll('.operator-only').forEach(element => {
        element.classList.toggle('hidden', user.role !== 'Operator/Supervisor');
      });
      document.querySelectorAll('.admin-only').forEach(element => {
        element.classList.toggle('hidden', user.role !== 'Admin');
      });

      const machineLoad = refreshMachineOptions();
      const operatorLoad = refreshOperatorOptions();
      const partsLoad = fetchParts();
      const cutterLoad = refreshDailyCutters();
      const shiftLoad = refreshShiftOptions();
      setStartJobFieldsEnabled(false);
      Promise.all([machineLoad, operatorLoad, partsLoad, cutterLoad, shiftLoad]).then(() => {
        updateShiftHours();
        updateHandoverShiftHours();
        populateReportParts();
        applyOperatorAccess();
        switchModule('dashboard');
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
      fetch(`${API}/logout.php`, { method: 'POST' })
        .finally(() => showLogin());
    }

    window.setInterval(() => {
      if (currentUser && !dashboardModule.classList.contains('hidden')) loadMachineDashboard();
    }, 30000);
