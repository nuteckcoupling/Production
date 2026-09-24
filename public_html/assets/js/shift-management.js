    let managedShifts = [];

    function calculateShiftHoursClient() {
      const start = document.getElementById('shiftStartTime').value;
      const end = document.getElementById('shiftEndTime').value;
      const output = document.getElementById('shiftCalculatedHours');
      if (!start || !end || start === end) {
        output.value = '';
        return;
      }
      const [startHour, startMinute] = start.split(':').map(Number);
      const [endHour, endMinute] = end.split(':').map(Number);
      let minutes = (endHour * 60 + endMinute) - (startHour * 60 + startMinute);
      if (minutes < 0) minutes += 24 * 60;
      output.value = (minutes / 60).toFixed(2).replace(/\.00$/, '');
    }

    document.getElementById('shiftStartTime').addEventListener('change', calculateShiftHoursClient);
    document.getElementById('shiftEndTime').addEventListener('change', calculateShiftHoursClient);

    function showShiftManagementMessage(type, text) {
      const banner = document.getElementById('shiftManagementMessage');
      banner.className = 'banner ' + type;
      banner.innerText = text;
      if (text && (type === 'success' || type === 'error')) showToast(type, text);
    }

    function loadShiftManagement() {
      if (currentUser?.role !== 'Admin') return;
      const tbody = document.getElementById('shiftManagementTableBody');
      tbody.innerHTML = '<tr><td colspan="8" class="empty">Loading...</td></tr>';
      fetch(API + '/get_shifts.php?all=1')
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load shifts.');
          return data;
        })
        .then(shifts => {
          managedShifts = shifts;
          renderManagedShifts();
        })
        .catch(error => {
          tbody.innerHTML = '<tr><td colspan="8" class="empty error-text">' + escapeHtml(error.message) + '</td></tr>';
        });
    }

    function renderManagedShifts() {
      const tbody = document.getElementById('shiftManagementTableBody');
      if (!managedShifts.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty">No shifts added yet.</td></tr>';
        return;
      }
      tbody.innerHTML = managedShifts.map(shift => {
        let secondaryAction = '';
        if (Number(shift.usage_count) > 0 && shift.status === 'Active') {
          secondaryAction = '<button type="button" class="table-action delete" onclick="deactivateShift(' + Number(shift.id) + ')">Deactivate</button>';
        } else if (Number(shift.usage_count) === 0) {
          secondaryAction = '<button type="button" class="table-action delete" onclick="deleteShift(' + Number(shift.id) + ')">Delete</button>';
        }
        return '<tr>' +
          '<td><strong>' + escapeHtml(shift.code) + '</strong></td><td>' + escapeHtml(shift.name) + '</td>' +
          '<td>' + escapeHtml(shift.start_time) + '</td><td>' + escapeHtml(shift.end_time) + '</td>' +
          '<td>' + Number(shift.shift_hours) + '</td>' +
          '<td><span class="tag ' + (shift.status === 'Active' ? 'running' : 'idle') + '">' + escapeHtml(shift.status) + '</span></td>' +
          '<td>' + (Number(shift.usage_count) > 0 ? 'Used (' + Number(shift.usage_count) + ')' : 'Unused') + '</td>' +
          '<td><button type="button" class="table-action edit" onclick="editShift(' + Number(shift.id) + ')">Edit</button>' + secondaryAction + '</td></tr>';
      }).join('');
    }

    function handleShiftSubmit(event) {
      event.preventDefault();
      const data = Object.fromEntries(new FormData(event.target).entries());
      const button = document.getElementById('saveShiftBtn');
      button.disabled = true;
      button.innerText = data.id ? 'Updating...' : 'Saving...';
      fetch(API + '/save_shift.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to save shift.');
        return result;
      }).then(result => {
        showShiftManagementMessage('success', 'Shift saved. Hours calculated: ' + Number(result.shift_hours));
        resetShiftForm();
        return Promise.all([loadShiftManagement(), refreshShiftOptions()]);
      }).then(() => {
        updateShiftHours();
        updateHandoverShiftHours();
      }).catch(error => showShiftManagementMessage('error', error.message))
        .finally(() => {
          button.disabled = false;
          button.innerText = document.getElementById('shiftManagementId').value ? 'Update Shift' : 'Save Shift';
        });
    }

    function editShift(id) {
      const shift = managedShifts.find(item => Number(item.id) === Number(id));
      if (!shift) return;
      document.getElementById('shiftManagementId').value = shift.id;
      document.getElementById('shiftCode').value = shift.code;
      document.getElementById('shiftName').value = shift.name;
      document.getElementById('shiftStartTime').value = shift.start_time;
      document.getElementById('shiftEndTime').value = shift.end_time;
      document.getElementById('shiftStatus').value = shift.status;
      document.getElementById('saveShiftBtn').innerText = 'Update Shift';
      document.getElementById('cancelShiftEditBtn').classList.remove('hidden');
      calculateShiftHoursClient();
      document.getElementById('shiftManagementForm').scrollIntoView({ behavior: 'smooth' });
    }

    function resetShiftForm() {
      document.getElementById('shiftManagementForm').reset();
      document.getElementById('shiftManagementId').value = '';
      document.getElementById('shiftCalculatedHours').value = '';
      document.getElementById('shiftStatus').value = 'Active';
      document.getElementById('saveShiftBtn').innerText = 'Save Shift';
      document.getElementById('cancelShiftEditBtn').classList.add('hidden');
    }

    async function deleteShift(id) {
      const confirmed = await showConfirmDialog({
        title: 'Delete Shift?',
        message: 'This unused shift will be permanently deleted.',
        confirmText: 'Delete Shift',
        tone: 'danger'
      });
      if (!confirmed) return;
      fetch(API + '/delete_shift.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to delete shift.');
      }).then(() => {
        showShiftManagementMessage('success', 'Unused shift deleted.');
        loadShiftManagement();
        refreshShiftOptions();
      }).catch(error => showShiftManagementMessage('error', error.message));
    }

    async function deactivateShift(id) {
      const shift = managedShifts.find(item => Number(item.id) === Number(id));
      if (!shift) return;
      const confirmed = await showConfirmDialog({
        title: 'Deactivate Shift?',
        message: shift.name + ' will disappear from production dropdowns. Historical records will remain safe.',
        confirmText: 'Deactivate',
        tone: 'warning'
      });
      if (!confirmed) return;
      const data = {
        id: shift.id,
        code: shift.code,
        name: shift.name,
        start_time: shift.start_time,
        end_time: shift.end_time,
        status: 'Inactive'
      };
      fetch(API + '/save_shift.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to deactivate shift.');
      }).then(() => {
        showShiftManagementMessage('success', 'Used shift deactivated. History remains safe.');
        loadShiftManagement();
        refreshShiftOptions();
      }).catch(error => showShiftManagementMessage('error', error.message));
    }
