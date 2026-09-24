    let managedStaff = [];

    function showStaffManagementMessage(type, text) {
      const banner = document.getElementById('staffManagementMessage');
      banner.className = 'banner ' + type;
      banner.innerText = text;
      if (text && (type === 'success' || type === 'error')) showToast(type, text);
    }

    function loadStaffManagement() {
      if (currentUser?.role !== 'Admin') return;
      const tbody = document.getElementById('staffManagementTableBody');
      tbody.innerHTML = '<tr><td colspan="10" class="empty">Loading...</td></tr>';
      fetch(API + '/get_staff.php')
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load staff.');
          return data;
        })
        .then(staff => {
          managedStaff = staff;
          renderManagedStaff();
        })
        .catch(error => {
          tbody.innerHTML = '<tr><td colspan="10" class="empty error-text">' + escapeHtml(error.message) + '</td></tr>';
        });
    }

    function renderManagedStaff() {
      const tbody = document.getElementById('staffManagementTableBody');
      if (!managedStaff.length) {
        tbody.innerHTML = '<tr><td colspan="10" class="empty">No staff added yet.</td></tr>';
        return;
      }
      tbody.innerHTML = managedStaff.map(staff => {
        let secondaryAction = '';
        if (!staff.has_active_work && staff.status === 'Active' && (Number(staff.usage_count) > 0 || staff.user_id)) {
          secondaryAction = '<button type="button" class="table-action delete" onclick="deactivateStaff(' + Number(staff.id) + ')">Deactivate</button>';
        } else if (!staff.has_active_work && Number(staff.usage_count) === 0 && !staff.user_id) {
          secondaryAction = '<button type="button" class="table-action delete" onclick="deleteStaff(' + Number(staff.id) + ')">Delete</button>';
        }
        const login = staff.username
          ? escapeHtml(staff.username) + ' <span class="table-subtext">(' + escapeHtml(staff.user_status) + ')</span>'
          : 'Not linked';
        return '<tr>' +
          '<td><strong>' + escapeHtml(staff.staff_code) + '</strong></td>' +
          '<td>' + escapeHtml(staff.name) + '</td>' +
          '<td>' + escapeHtml(staff.designation) + '</td>' +
          '<td>' + escapeHtml(staff.department) + '</td>' +
          '<td>' + escapeHtml(staff.phone || '—') + '</td>' +
          '<td><span class="tag ' + (staff.status === 'Active' ? 'running' : 'idle') + '">' + escapeHtml(staff.status) + '</span></td>' +
          '<td>' + (Number(staff.usage_count) > 0 ? 'Used (' + Number(staff.usage_count) + ')' : 'Unused') + '</td>' +
          '<td>' + login + '</td>' +
          '<td>' + (staff.has_active_work ? '<span class="tag running">Running</span>' : 'None') + '</td>' +
          '<td><button type="button" class="table-action edit" onclick="editStaff(' + Number(staff.id) + ')">Edit</button>' + secondaryAction + '</td></tr>';
      }).join('');
    }

    function handleStaffSubmit(event) {
      event.preventDefault();
      const data = Object.fromEntries(new FormData(event.target).entries());
      const button = document.getElementById('saveStaffBtn');
      button.disabled = true;
      button.innerText = data.id ? 'Updating...' : 'Saving...';
      fetch(API + '/save_staff.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to save staff member.');
      }).then(() => {
        showStaffManagementMessage('success', 'Staff saved. Active staff dropdowns updated.');
        resetStaffForm();
        return Promise.all([loadStaffManagement(), refreshOperatorOptions()]);
      }).catch(error => showStaffManagementMessage('error', error.message))
        .finally(() => {
          button.disabled = false;
          button.innerText = document.getElementById('staffManagementId').value ? 'Update Staff' : 'Save Staff';
        });
    }

    function editStaff(id) {
      const staff = managedStaff.find(item => Number(item.id) === Number(id));
      if (!staff) return;
      document.getElementById('staffManagementId').value = staff.id;
      document.getElementById('staffCode').value = staff.staff_code;
      document.getElementById('staffName').value = staff.name;
      document.getElementById('staffDesignation').value = staff.designation;
      document.getElementById('staffDepartment').value = staff.department;
      document.getElementById('staffPhone').value = staff.phone || '';
      document.getElementById('staffStatus').value = staff.status;
      document.getElementById('saveStaffBtn').innerText = 'Update Staff';
      document.getElementById('cancelStaffEditBtn').classList.remove('hidden');
      document.getElementById('staffManagementForm').scrollIntoView({ behavior: 'smooth' });
    }

    function resetStaffForm() {
      document.getElementById('staffManagementForm').reset();
      document.getElementById('staffManagementId').value = '';
      document.getElementById('staffDesignation').value = 'Operator';
      document.getElementById('staffDepartment').value = 'Production';
      document.getElementById('staffStatus').value = 'Active';
      document.getElementById('saveStaffBtn').innerText = 'Save Staff';
      document.getElementById('cancelStaffEditBtn').classList.add('hidden');
    }

    async function deleteStaff(id) {
      const staff = managedStaff.find(item => Number(item.id) === Number(id));
      const confirmed = await showConfirmDialog({
        title: 'Delete Staff Member?',
        message: (staff ? staff.staff_code + ' — ' + staff.name + '. ' : '') + 'This unused staff record will be permanently deleted.',
        confirmText: 'Delete Staff',
        tone: 'danger'
      });
      if (!confirmed) return;
      fetch(API + '/delete_staff.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to delete staff member.');
      }).then(() => {
        showStaffManagementMessage('success', 'Unused staff member deleted.');
        loadStaffManagement();
        refreshOperatorOptions();
      }).catch(error => showStaffManagementMessage('error', error.message));
    }

    async function deactivateStaff(id) {
      const staff = managedStaff.find(item => Number(item.id) === Number(id));
      if (!staff) return;
      const confirmed = await showConfirmDialog({
        title: 'Deactivate Staff Member?',
        message: staff.staff_code + ' — ' + staff.name + ' will be removed from production dropdowns and login will be blocked. History remains safe.',
        confirmText: 'Deactivate',
        tone: 'warning'
      });
      if (!confirmed) return;
      fetch(API + '/save_staff.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id: staff.id,
          staff_code: staff.staff_code,
          name: staff.name,
          designation: staff.designation,
          department: staff.department,
          phone: staff.phone || '',
          status: 'Inactive'
        })
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to deactivate staff member.');
      }).then(() => {
        showStaffManagementMessage('success', 'Staff deactivated. Production history remains safe.');
        loadStaffManagement();
        refreshOperatorOptions();
      }).catch(error => showStaffManagementMessage('error', error.message));
    }
