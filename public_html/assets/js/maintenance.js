// V2 Maintenance Management.
    let maintenanceTickets = [];
    let maintenanceStaff = [];
    let selectedMaintenanceTicketId = null;
    let pendingBreakdownMachineId = null;

    function maintenanceBanner(type, text) {
      const banner = document.getElementById('maintenanceMessage');
      banner.className = `banner ${type}`;
      banner.innerText = text;
      if (text && (type === 'success' || type === 'error')) showToast(type, text);
    }

    function populateBreakdownMachines(selected = '') {
      const select = document.getElementById('breakdownMachine');
      select.innerHTML = '<option value="">-- select machine --</option>';
      dashboardMachines.filter(machine => ['Available', 'Running'].includes(machine.runtime_status) && !machine.maintenance_ticket_id)
        .forEach(machine => select.insertAdjacentHTML('beforeend', `<option value="${Number(machine.id)}">${escapeHtml(machine.code)} — ${escapeHtml(machine.name)} (${escapeHtml(machine.runtime_status)})</option>`));
      if ([...select.options].some(option => option.value === String(selected))) select.value = String(selected);
    }

    function loadMaintenanceModule(selectedTicketId = null, selectedMachineId = null) {
      selectedMaintenanceTicketId = selectedTicketId ? Number(selectedTicketId) : selectedMaintenanceTicketId;
      Promise.all([
        fetch(`${API}/get_machine_dashboard.php`).then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load machines.');
          dashboardMachines = data;
        }),
        fetch(`${API}/get_maintenance_staff.php`).then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load maintenance staff.');
          maintenanceStaff = data;
        })
      ]).then(() => {
        populateBreakdownMachines(selectedMachineId || pendingBreakdownMachineId || '');
        pendingBreakdownMachineId = null;
        const staffSelect = document.getElementById('maintenanceAssignedStaff');
        staffSelect.innerHTML = '<option value="">-- select maintenance person --</option>';
        maintenanceStaff.filter(staff => staff.has_maintenance_login).forEach(staff => staffSelect.insertAdjacentHTML('beforeend', `<option value="${Number(staff.id)}">${escapeHtml(staff.staff_code)} — ${escapeHtml(staff.name)}</option>`));
        loadMaintenanceTickets();
      }).catch(error => maintenanceBanner('error', error.message));
    }

    function loadMaintenanceTickets() {
      const filter = document.getElementById('maintenanceStatusFilter').value;
      const body = document.getElementById('maintenanceTicketsBody');
      body.innerHTML = '<tr><td colspan="8" class="empty">Loading...</td></tr>';
      fetch(`${API}/get_maintenance_tickets.php?status=${encodeURIComponent(filter)}`)
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Unable to load maintenance tickets.');
          maintenanceTickets = result.data.tickets;
          const summary = result.data.summary;
          document.getElementById('maintenanceOpenCount').innerText = summary.open_count;
          document.getElementById('maintenanceProgressCount').innerText = summary.in_progress_count;
          document.getElementById('maintenanceTestingCount').innerText = summary.awaiting_test_count;
          document.getElementById('maintenanceMttr').innerText = `${summary.mttr_minutes} min`;
          body.innerHTML = maintenanceTickets.length ? maintenanceTickets.map(ticket => `<tr>
            <td><strong>${escapeHtml(ticket.ticket_no || '#' + ticket.id)}</strong></td><td>${escapeHtml(ticket.machine_code)}</td>
            <td>${escapeHtml(ticket.breakdown_type)}</td><td><span class="tag ${ticket.status === 'Closed' ? 'active' : (ticket.status === 'In Progress' ? 'idle' : 'breakdown')}">${escapeHtml(ticket.status)}</span></td>
            <td>${escapeHtml(ticket.assigned_staff_name || 'Unassigned')}</td><td>${escapeHtml(ticket.breakdown_started_at)}</td>
            <td>${Number(ticket.downtime_minutes || 0)} min</td><td><button type="button" class="table-action edit" onclick="openMaintenanceTicket(${Number(ticket.id)})">Open</button></td></tr>`).join('')
            : '<tr><td colspan="8" class="empty">No maintenance tickets found.</td></tr>';
          if (selectedMaintenanceTicketId) openMaintenanceTicket(selectedMaintenanceTicketId);
        })
        .catch(error => { body.innerHTML = `<tr><td colspan="8" class="empty error-text">${escapeHtml(error.message)}</td></tr>`; });
    }

    function openBreakdownForm(machineId) {
      pendingBreakdownMachineId = Number(machineId);
      switchModule('maintenance');
      document.getElementById('raiseBreakdownForm').scrollIntoView({ behavior: 'smooth' });
    }

    function handleRaiseBreakdown(event) {
      event.preventDefault();
      const button = document.getElementById('raiseBreakdownBtn');
      const payload = new FormData(event.target);
      button.disabled = true;
      button.innerText = 'Raising Ticket...';
      fetch(`${API}/create_maintenance_ticket.php`, { method: 'POST', body: payload })
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Unable to raise breakdown ticket.');
          event.target.reset();
          maintenanceBanner('success', `Breakdown ticket ${result.data.ticket_no} created. Machine status is Breakdown.`);
          selectedMaintenanceTicketId = Number(result.data.ticket_id);
          loadMachineDashboard();
          loadMaintenanceModule(selectedMaintenanceTicketId);
        })
        .catch(error => maintenanceBanner('error', error.message))
        .finally(() => { button.disabled = false; button.innerText = 'Raise Breakdown'; });
    }

    function openMaintenanceTicket(id) {
      const ticket = maintenanceTickets.find(item => Number(item.id) === Number(id));
      if (!ticket) return;
      selectedMaintenanceTicketId = Number(id);
      document.querySelectorAll('.maintenance-ticket-id').forEach(input => { input.value = ticket.id; });
      document.getElementById('maintenanceDetailTitle').innerText = `${ticket.ticket_no} — ${ticket.machine_code}`;
      document.getElementById('maintenanceDetailStatus').innerText = ticket.status;
      const photo = ticket.attachment_id ? `<a href="${API}/download_maintenance_attachment.php?id=${Number(ticket.attachment_id)}" target="_blank" rel="noopener">${escapeHtml(ticket.attachment_name)}</a>` : '-';
      document.getElementById('maintenanceDetailSummary').innerHTML = `
        <div><dt>Type</dt><dd>${escapeHtml(ticket.breakdown_type)}</dd></div><div><dt>Reported By</dt><dd>${escapeHtml(ticket.reported_by)}</dd></div>
        <div><dt>Breakdown Start</dt><dd>${escapeHtml(ticket.breakdown_started_at)}</dd></div><div><dt>Assigned To</dt><dd>${escapeHtml(ticket.assigned_staff_name || 'Unassigned')}</dd></div>
        <div><dt>Work Started</dt><dd>${escapeHtml(ticket.work_started_at || '-')}</dd></div><div><dt>Repair Completed</dt><dd>${escapeHtml(ticket.repair_completed_at || '-')}</dd></div>
        <div><dt>Downtime</dt><dd>${Number(ticket.downtime_minutes || 0)} min</dd></div><div><dt>Repair Time</dt><dd>${ticket.repair_minutes === null ? '-' : Number(ticket.repair_minutes) + ' min'}</dd></div>
        <div class="full"><dt>Problem</dt><dd>${escapeHtml(ticket.problem_description)}</dd></div><div class="full"><dt>Breakdown Photo</dt><dd>${photo}</dd></div>
        <div class="full"><dt>Repair Details</dt><dd>${escapeHtml(ticket.repair_details || '-')}</dd></div><div class="full"><dt>Spare Parts</dt><dd>${escapeHtml(ticket.spare_parts_used || '-')}</dd></div>
        <div class="full"><dt>Testing Remarks</dt><dd>${escapeHtml(ticket.testing_remarks || '-')}</dd></div>`;
      document.getElementById('maintenanceAssignedStaff').value = ticket.assigned_staff_id || '';
      document.getElementById('maintenanceAssignForm').classList.toggle('hidden', !ticket.can_assign);
      document.getElementById('maintenanceStartAction').classList.toggle('hidden', !ticket.can_start_work);
      document.getElementById('maintenanceRepairForm').classList.toggle('hidden', !ticket.can_complete_repair);
      document.getElementById('maintenanceConfirmForm').classList.toggle('hidden', !ticket.can_confirm_running);
      document.getElementById('maintenanceTicketDetail').classList.remove('hidden');
      document.getElementById('maintenanceTicketDetail').scrollIntoView({ behavior: 'smooth' });
    }

    function closeMaintenanceDetail() {
      selectedMaintenanceTicketId = null;
      document.getElementById('maintenanceTicketDetail').classList.add('hidden');
    }

    function handleMaintenanceAction(event, action) {
      if (event) event.preventDefault();
      const ticket = maintenanceTickets.find(item => Number(item.id) === Number(selectedMaintenanceTicketId));
      if (!ticket) return;
      const data = event ? Object.fromEntries(new FormData(event.target).entries()) : { ticket_id: ticket.id };
      data.ticket_id = ticket.id;
      data.action = action;
      fetch(`${API}/update_maintenance_ticket.php`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
      }).then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Unable to update maintenance ticket.');
          if (event) event.target.reset();
          maintenanceBanner('success', 'Maintenance ticket updated successfully.');
          if (action === 'confirm_running') {
            selectedMaintenanceTicketId = null;
            closeMaintenanceDetail();
            loadMachineDashboard();
          }
          loadMaintenanceTickets();
        }).catch(error => maintenanceBanner('error', error.message));
    }
