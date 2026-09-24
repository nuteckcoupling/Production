    let managedMachines = [];

    function showMachineManagementMessage(type, text) {
      const banner = document.getElementById('machineManagementMessage');
      banner.className = 'banner ' + type;
      banner.innerText = text;
    }

    function loadMachineManagement() {
      if (currentUser?.role !== 'Admin') return;
      const tbody = document.getElementById('machineManagementTableBody');
      tbody.innerHTML = '<tr><td colspan="7" class="empty">Loading...</td></tr>';
      fetch(API + '/get_machines.php?all=1')
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load machines.');
          return data;
        })
        .then(machines => {
          managedMachines = machines;
          renderManagedMachines();
        })
        .catch(error => {
          tbody.innerHTML = '<tr><td colspan="7" class="empty error-text">' + escapeHtml(error.message) + '</td></tr>';
        });
    }

    function renderManagedMachines() {
      const tbody = document.getElementById('machineManagementTableBody');
      if (!managedMachines.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty">No machines added yet.</td></tr>';
        return;
      }
      tbody.innerHTML = managedMachines.map(machine => {
        let secondaryAction = '';
        if (!machine.has_active_job && Number(machine.usage_count) > 0 && machine.status === 'Active') {
          secondaryAction = '<button type="button" class="table-action delete" onclick="deactivateMachine(' + Number(machine.id) + ')">Deactivate</button>';
        } else if (!machine.has_active_job && Number(machine.usage_count) === 0) {
          secondaryAction = '<button type="button" class="table-action delete" onclick="deleteMachine(' + Number(machine.id) + ')">Delete</button>';
        }
        return '<tr>' +
          '<td><strong>' + escapeHtml(machine.code) + '</strong></td><td>' + escapeHtml(machine.name) + '</td>' +
          '<td>' + escapeHtml(machine.default_operation) + '</td>' +
          '<td><span class="tag ' + (machine.status === 'Active' ? 'running' : 'idle') + '">' + escapeHtml(machine.status) + '</span></td>' +
          '<td>' + (Number(machine.usage_count) > 0 ? 'Used (' + Number(machine.usage_count) + ')' : 'Unused') + '</td>' +
          '<td>' + (machine.has_active_job ? '<span class="tag running">Running</span>' : 'None') + '</td>' +
          '<td><button type="button" class="table-action edit" onclick="editMachine(' + Number(machine.id) + ')">Edit</button>' + secondaryAction + '</td></tr>';
      }).join('');
    }

    function handleMachineSubmit(event) {
      event.preventDefault();
      const data = Object.fromEntries(new FormData(event.target).entries());
      const button = document.getElementById('saveMachineBtn');
      button.disabled = true;
      button.innerText = data.id ? 'Updating...' : 'Saving...';
      fetch(API + '/save_machine.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to save machine.');
      }).then(() => {
        showMachineManagementMessage('success', 'Machine saved. Dashboard and dropdowns updated.');
        resetMachineForm();
        return Promise.all([loadMachineManagement(), refreshMachineOptions(), loadMachineDashboard()]);
      }).catch(error => showMachineManagementMessage('error', error.message))
        .finally(() => {
          button.disabled = false;
          button.innerText = document.getElementById('machineManagementId').value ? 'Update Machine' : 'Save Machine';
        });
    }

    function editMachine(id) {
      const machine = managedMachines.find(item => Number(item.id) === Number(id));
      if (!machine) return;
      document.getElementById('machineManagementId').value = machine.id;
      document.getElementById('machineCode').value = machine.code;
      document.getElementById('machineName').value = machine.name;
      document.getElementById('machineDefaultOperation').value = machine.default_operation;
      document.getElementById('machineStatus').value = machine.status;
      document.getElementById('saveMachineBtn').innerText = 'Update Machine';
      document.getElementById('cancelMachineEditBtn').classList.remove('hidden');
      document.getElementById('machineManagementForm').scrollIntoView({ behavior: 'smooth' });
    }

    function resetMachineForm() {
      document.getElementById('machineManagementForm').reset();
      document.getElementById('machineManagementId').value = '';
      document.getElementById('machineStatus').value = 'Active';
      document.getElementById('saveMachineBtn').innerText = 'Save Machine';
      document.getElementById('cancelMachineEditBtn').classList.add('hidden');
    }

    function deleteMachine(id) {
      if (!window.confirm('Delete this unused machine?')) return;
      fetch(API + '/delete_machine.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to delete machine.');
      }).then(() => {
        showMachineManagementMessage('success', 'Unused machine deleted.');
        loadMachineManagement();
        refreshMachineOptions();
        loadMachineDashboard();
      }).catch(error => showMachineManagementMessage('error', error.message));
    }

    function deactivateMachine(id) {
      const machine = managedMachines.find(item => Number(item.id) === Number(id));
      if (!machine || !window.confirm('Deactivate this used machine? Historical records will remain safe.')) return;
      fetch(API + '/save_machine.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id: machine.id,
          code: machine.code,
          name: machine.name,
          default_operation: machine.default_operation,
          status: 'Inactive'
        })
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to deactivate machine.');
      }).then(() => {
        showMachineManagementMessage('success', 'Used machine deactivated. History remains safe.');
        loadMachineManagement();
        refreshMachineOptions();
        loadMachineDashboard();
      }).catch(error => showMachineManagementMessage('error', error.message));
    }
