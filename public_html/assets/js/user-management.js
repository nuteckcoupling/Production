    let managedUsers = [];
    let userManagementStaff = [];

    function showUserManagementMessage(type, text) {
      const banner = document.getElementById('userManagementMessage');
      banner.className = 'banner ' + type;
      banner.innerText = text;
      if (text && (type === 'success' || type === 'error')) showToast(type, text);
    }

    function loadUserManagement() {
      if (currentUser?.role !== 'Admin') return;
      const tbody = document.getElementById('userManagementTableBody');
      tbody.innerHTML = '<tr><td colspan="7" class="empty">Loading...</td></tr>';
      Promise.all([
        fetch(API + '/get_users.php').then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load users.');
          return data;
        }),
        fetch(API + '/get_staff.php').then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load staff.');
          return data;
        })
      ]).then(([users, staff]) => {
        managedUsers = users;
        userManagementStaff = staff;
        renderManagedUsers();
        populateUserStaffOptions();
      }).catch(error => {
        tbody.innerHTML = '<tr><td colspan="6" class="empty error-text">' + escapeHtml(error.message) + '</td></tr>';
      });
    }

    function renderManagedUsers() {
      const tbody = document.getElementById('userManagementTableBody');
      if (!managedUsers.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty">No users found.</td></tr>';
        return;
      }
      tbody.innerHTML = managedUsers.map(user => {
        const staff = user.operator_name ? escapeHtml(user.staff_code) + ' — ' + escapeHtml(user.operator_name) : 'Not linked';
        const current = user.is_current ? ' <span class="table-subtext">(You)</span>' : '';
        let security = 'OK';
        if (user.is_locked) security = '<span class="tag breakdown">Locked</span>';
        else if (user.must_change_password) security = '<span class="tag handover-pending">Password change required</span>';
        else if (user.failed_login_attempts) security = escapeHtml(user.failed_login_attempts) + ' failed attempt(s)';
        return '<tr>' +
          '<td><strong>' + escapeHtml(user.username) + '</strong>' + current + '</td>' +
          '<td>' + escapeHtml(user.role) + '</td>' +
          '<td>' + staff + '</td>' +
          '<td><span class="tag ' + (user.status === 'Active' ? 'running' : 'idle') + '">' + escapeHtml(user.status) + '</span></td>' +
          '<td>' + security + '</td>' +
          '<td>' + escapeHtml(user.last_login || 'Never') + '</td>' +
          '<td><button type="button" class="table-action edit" onclick="editUser(' + Number(user.id) + ')">Edit / Reset Password</button>' +
          (user.is_locked || user.failed_login_attempts ? '<button type="button" class="table-action" onclick="unlockUser(' + Number(user.id) + ')">Unlock</button>' : '') +
          (!user.is_current && user.status === 'Active' ? '<button type="button" class="table-action delete" onclick="deactivateUser(' + Number(user.id) + ')">Deactivate</button>' : '') + '</td></tr>';
      }).join('');
    }

    function populateUserStaffOptions(selectedId = '') {
      const select = document.getElementById('userOperatorId');
      select.innerHTML = '<option value="">-- select staff --</option>';
      userManagementStaff.forEach(staff => {
        const available = staff.status === 'Active' && (!staff.user_id || String(staff.id) === String(selectedId));
        if (!available) return;
        select.insertAdjacentHTML('beforeend', '<option value="' + Number(staff.id) + '">' + escapeHtml(staff.staff_code) + ' — ' + escapeHtml(staff.name) + '</option>');
      });
      if ([...select.options].some(option => option.value === String(selectedId))) select.value = String(selectedId);
    }

    function updateUserRoleFields() {
      const isAdmin = document.getElementById('userRole').value === 'Admin';
      document.getElementById('userStaffField').classList.toggle('hidden', isAdmin);
      document.getElementById('userOperatorId').required = !isAdmin;
      if (isAdmin) document.getElementById('userOperatorId').value = '';
    }

    function handleUserSubmit(event) {
      event.preventDefault();
      const data = Object.fromEntries(new FormData(event.target).entries());
      if (!data.id && !data.password) {
        showUserManagementMessage('error', 'Password is required for a new user.');
        return;
      }
      const button = document.getElementById('saveUserBtn');
      button.disabled = true;
      button.innerText = data.id ? 'Updating...' : 'Saving...';
      fetch(API + '/save_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to save user.');
      }).then(() => {
        showUserManagementMessage('success', data.password && data.id ? 'User updated and password reset.' : 'User saved successfully.');
        resetUserForm();
        return Promise.all([loadUserManagement(), loadStaffManagement()]);
      }).catch(error => showUserManagementMessage('error', error.message))
        .finally(() => {
          button.disabled = false;
          button.innerText = document.getElementById('userManagementId').value ? 'Update User' : 'Save User';
        });
    }

    function editUser(id) {
      const user = managedUsers.find(item => Number(item.id) === Number(id));
      if (!user) return;
      document.getElementById('userManagementId').value = user.id;
      document.getElementById('userUsername').value = user.username;
      document.getElementById('userRole').value = user.role;
      document.getElementById('userStatus').value = user.status;
      document.getElementById('userPassword').value = '';
      populateUserStaffOptions(user.operator_id || '');
      updateUserRoleFields();
      if (user.operator_id) document.getElementById('userOperatorId').value = String(user.operator_id);
      document.getElementById('saveUserBtn').innerText = 'Update User';
      document.getElementById('cancelUserEditBtn').classList.remove('hidden');
      document.getElementById('userManagementForm').scrollIntoView({ behavior: 'smooth' });
    }

    function resetUserForm() {
      document.getElementById('userManagementForm').reset();
      document.getElementById('userManagementId').value = '';
      document.getElementById('userRole').value = 'Operator/Supervisor';
      document.getElementById('userStatus').value = 'Active';
      document.getElementById('userPassword').value = '';
      populateUserStaffOptions();
      updateUserRoleFields();
      document.getElementById('saveUserBtn').innerText = 'Save User';
      document.getElementById('cancelUserEditBtn').classList.add('hidden');
    }

    async function deactivateUser(id) {
      const user = managedUsers.find(item => Number(item.id) === Number(id));
      if (!user) return;
      const confirmed = await showConfirmDialog({
        title: 'Deactivate User?',
        message: user.username + ' will be logged out on the next request and cannot sign in until reactivated.',
        confirmText: 'Deactivate',
        tone: 'warning'
      });
      if (!confirmed) return;
      fetch(API + '/save_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: user.id, username: user.username, role: user.role, operator_id: user.operator_id || '', status: 'Inactive', password: '' })
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to deactivate user.');
      }).then(() => {
        showUserManagementMessage('success', 'User deactivated.');
        loadUserManagement();
        loadStaffManagement();
      }).catch(error => showUserManagementMessage('error', error.message));
    }

    async function unlockUser(id) {
      const user = managedUsers.find(item => Number(item.id) === Number(id));
      if (!user) return;
      const confirmed = await showConfirmDialog({
        title: 'Unlock Account?',
        message: 'Failed login attempts for ' + user.username + ' will be cleared.',
        confirmText: 'Unlock',
        tone: 'warning'
      });
      if (!confirmed) return;
      fetch(API + '/unlock_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: user.id })
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to unlock user.');
      }).then(() => {
        showUserManagementMessage('success', 'User account unlocked.');
        loadUserManagement();
      }).catch(error => showUserManagementMessage('error', error.message));
    }

    function handleChangePassword(event) {
      event.preventDefault();
      const data = Object.fromEntries(new FormData(event.target).entries());
      const banner = document.getElementById('changePasswordMessage');
      if (data.new_password !== data.confirm_password) {
        banner.className = 'banner error';
        banner.innerText = 'New password and confirmation do not match.';
        showToast('error', banner.innerText);
        return;
      }
      const button = document.getElementById('changePasswordBtn');
      button.disabled = true;
      button.innerText = 'Changing...';
      const wasForced = Boolean(currentUser?.must_change_password);
      fetch(API + '/change_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ current_password: data.current_password, new_password: data.new_password })
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to change password.');
        return result;
      }).then(result => {
        event.target.reset();
        banner.className = 'banner success';
        banner.innerText = 'Password changed successfully.';
        showToast('success', banner.innerText);
        currentUser = result.user;
        if (wasForced) initializeApp(result.user);
      }).catch(error => {
        banner.className = 'banner error';
        banner.innerText = error.message;
        showToast('error', error.message);
      }).finally(() => {
        button.disabled = false;
        button.innerText = 'Change Password';
      });
    }
