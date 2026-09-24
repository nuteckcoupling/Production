    let weeklyPlanInitialized = false;
    let weeklyPlanRows = [];

    function localDateValue(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return year + '-' + month + '-' + day;
    }

    function mondayFor(date = new Date()) {
      const value = new Date(date);
      const day = value.getDay() || 7;
      value.setDate(value.getDate() - day + 1);
      return localDateValue(value);
    }

    function weeklyPlanEnd(start) {
      if (!start) return '';
      const date = new Date(start + 'T00:00:00');
      date.setDate(date.getDate() + 6);
      return localDateValue(date);
    }

    function initializeWeeklyPlan() {
      if (weeklyPlanInitialized) {
        loadWeeklyPlans();
        return;
      }
      const week = mondayFor();
      document.getElementById('weeklyPlanWeekStart').value = week;
      document.getElementById('weeklyPlanFilterWeek').value = week;
      updateWeeklyPlanEnd();
      document.getElementById('weeklyPlanWeekStart').addEventListener('change', updateWeeklyPlanEnd);
      Promise.all([
        fetch(API + '/get_machines.php').then(response => response.json()),
        fetch(API + '/get_parts.php').then(response => response.json())
      ]).then(([machines, availableParts]) => {
        const machine = document.getElementById('weeklyPlanMachine');
        const part = document.getElementById('weeklyPlanPart');
        machines.forEach(item => {
          machine.insertAdjacentHTML('beforeend', '<option value="' + Number(item.id) + '">' + escapeHtml(item.code) + ' — ' + escapeHtml(item.name) + '</option>');
        });
        availableParts.forEach(item => {
          part.insertAdjacentHTML('beforeend', '<option value="' + Number(item.id) + '">' + escapeHtml(item.part_name) + ' — ' + escapeHtml(item.coupling_type || '') + '</option>');
        });
        weeklyPlanInitialized = true;
        loadWeeklyPlans();
      }).catch(() => showWeeklyPlanMessage('error', 'Unable to load Weekly Plan options.'));
    }

    function updateWeeklyPlanEnd() {
      document.getElementById('weeklyPlanWeekEnd').value = weeklyPlanEnd(document.getElementById('weeklyPlanWeekStart').value);
    }

    function showWeeklyPlanMessage(type, text) {
      const banner = document.getElementById('weeklyPlanMessage');
      banner.className = 'banner ' + type;
      banner.innerText = text;
      if (text && (type === 'success' || type === 'error')) showToast(type, text);
    }

    function loadWeeklyPlans() {
      const week = document.getElementById('weeklyPlanFilterWeek').value;
      const tbody = document.getElementById('weeklyPlansTableBody');
      tbody.innerHTML = '<tr><td colspan="10" class="empty">Loading...</td></tr>';
      fetch(API + '/get_weekly_plans.php?week_start=' + encodeURIComponent(week))
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load Weekly Plans.');
          return data;
        })
        .then(rows => {
          weeklyPlanRows = rows;
          renderWeeklyPlans();
        })
        .catch(error => {
          weeklyPlanRows = [];
          tbody.innerHTML = '<tr><td colspan="10" class="empty error-text">' + escapeHtml(error.message) + '</td></tr>';
        });
    }

    function renderWeeklyPlans() {
      const tbody = document.getElementById('weeklyPlansTableBody');
      const foot = document.getElementById('weeklyPlansTableFoot');
      const week = document.getElementById('weeklyPlanFilterWeek').value;
      const end = weeklyPlanEnd(week);
      document.getElementById('weeklyPlanPeriodLabel').innerText = week ? week + ' to ' + end : 'All weeks';
      document.getElementById('weeklyPlanGeneratedAt').innerText = 'Prepared for ISO documentation · Viewed ' + new Date().toLocaleString();
      document.getElementById('downloadWeeklyPlanBtn').disabled = weeklyPlanRows.length === 0;
      document.getElementById('printWeeklyPlanBtn').disabled = weeklyPlanRows.length === 0;
      if (!weeklyPlanRows.length) {
        tbody.innerHTML = '<tr><td colspan="10" class="empty">No Weekly Plan records found.</td></tr>';
        foot.innerHTML = '';
        return;
      }
      tbody.innerHTML = weeklyPlanRows.map(row => {
        const statusClassName = row.status === 'Final' ? 'running' : 'idle';
        return '<tr>' +
          '<td>' + escapeHtml(row.week_start) + ' to ' + escapeHtml(row.week_end) + '</td>' +
          '<td><strong>' + escapeHtml(row.machine_code) + '</strong><br><span class="table-subtext">' + escapeHtml(row.machine_name) + '</span></td>' +
          '<td>' + escapeHtml(row.shift) + '</td><td>' + escapeHtml(row.part_name) + '</td><td>' + escapeHtml(row.operation) + '</td>' +
          '<td>' + Number(row.planned_qty) + '</td><td>' + escapeHtml(row.priority) + '</td>' +
          '<td><span class="tag ' + statusClassName + '">' + escapeHtml(row.status) + '</span></td>' +
          '<td>' + escapeHtml(row.remarks || '-') + '</td>' +
          '<td class="weekly-plan-actions-column"><button type="button" class="table-action edit" onclick="editWeeklyPlan(' + Number(row.id) + ')">Edit</button>' +
          '<button type="button" class="table-action delete" onclick="deleteWeeklyPlan(' + Number(row.id) + ')">Delete</button></td></tr>';
      }).join('');
      const total = weeklyPlanRows.reduce((sum, row) => sum + Number(row.planned_qty), 0);
      foot.innerHTML = '<tr><th colspan="5">TOTAL PLANNED QUANTITY</th><th>' + total + '</th><th colspan="4"></th></tr>';
    }

    function handleWeeklyPlanSubmit(event) {
      event.preventDefault();
      const button = document.getElementById('saveWeeklyPlanBtn');
      const data = Object.fromEntries(new FormData(event.target).entries());
      button.disabled = true;
      button.innerText = data.id ? 'Updating...' : 'Saving...';
      fetch(API + '/save_weekly_plan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to save Weekly Plan.');
        return result;
      }).then(() => {
        const week = data.week_start;
        showWeeklyPlanMessage('success', data.id ? 'Weekly Plan updated successfully.' : 'Weekly Plan saved successfully.');
        resetWeeklyPlanForm();
        document.getElementById('weeklyPlanFilterWeek').value = week;
        loadWeeklyPlans();
      }).catch(error => showWeeklyPlanMessage('error', error.message))
        .finally(() => {
          button.disabled = false;
          button.innerText = document.getElementById('weeklyPlanId').value ? 'Update Plan' : 'Save Plan';
        });
    }

    function editWeeklyPlan(id) {
      const row = weeklyPlanRows.find(item => Number(item.id) === Number(id));
      if (!row) return;
      document.getElementById('weeklyPlanId').value = row.id;
      document.getElementById('weeklyPlanWeekStart').value = row.week_start;
      document.getElementById('weeklyPlanMachine').value = row.machine_id;
      document.getElementById('weeklyPlanShift').value = row.shift;
      document.getElementById('weeklyPlanPart').value = row.part_id;
      document.getElementById('weeklyPlanOperation').value = row.operation;
      document.getElementById('weeklyPlanQuantity').value = row.planned_qty;
      document.getElementById('weeklyPlanPriority').value = row.priority;
      document.getElementById('weeklyPlanStatus').value = row.status;
      document.getElementById('weeklyPlanRemarks').value = row.remarks || '';
      document.getElementById('saveWeeklyPlanBtn').innerText = 'Update Plan';
      document.getElementById('cancelWeeklyPlanEditBtn').classList.remove('hidden');
      updateWeeklyPlanEnd();
      document.getElementById('weeklyPlanForm').scrollIntoView({ behavior: 'smooth' });
    }

    function resetWeeklyPlanForm() {
      const form = document.getElementById('weeklyPlanForm');
      const selectedWeek = document.getElementById('weeklyPlanFilterWeek').value || mondayFor();
      form.reset();
      document.getElementById('weeklyPlanId').value = '';
      document.getElementById('weeklyPlanWeekStart').value = selectedWeek;
      document.getElementById('weeklyPlanPriority').value = 'Normal';
      document.getElementById('weeklyPlanStatus').value = 'Draft';
      document.getElementById('saveWeeklyPlanBtn').innerText = 'Save Plan';
      document.getElementById('cancelWeeklyPlanEditBtn').classList.add('hidden');
      updateWeeklyPlanEnd();
    }

    async function deleteWeeklyPlan(id) {
      const confirmed = await showConfirmDialog({
        title: 'Delete Weekly Plan?',
        message: 'This ISO plan record will be permanently deleted.',
        confirmText: 'Delete Plan',
        tone: 'danger'
      });
      if (!confirmed) return;
      fetch(API + '/delete_weekly_plan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to delete Weekly Plan.');
      }).then(() => {
        showWeeklyPlanMessage('success', 'Weekly Plan deleted successfully.');
        loadWeeklyPlans();
      }).catch(error => showWeeklyPlanMessage('error', error.message));
    }

    function downloadWeeklyPlanCsv() {
      if (!weeklyPlanRows.length) return;
      const headers = ['Week Start', 'Week End', 'Machine Number', 'Machine Name', 'Shift', 'Part Name', 'Operation', 'Planned Qty', 'Priority', 'Status', 'Remarks', 'Prepared By'];
      const lines = [[csvCell('NU-TECK Weekly Plan - ISO Document')].join(','), headers.map(csvCell).join(',')];
      weeklyPlanRows.forEach(row => {
        lines.push([row.week_start, row.week_end, row.machine_code, row.machine_name, row.shift, row.part_name, row.operation, row.planned_qty, row.priority, row.status, row.remarks, row.prepared_by].map(csvCell).join(','));
      });
      const total = weeklyPlanRows.reduce((sum, row) => sum + Number(row.planned_qty), 0);
      lines.push(['TOTAL', '', '', '', '', '', '', total, '', '', '', ''].map(csvCell).join(','));
      const blob = new Blob(['\ufeff' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'weekly-iso-plan-' + document.getElementById('weeklyPlanFilterWeek').value + '.csv';
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    }

    function printWeeklyPlan() {
      if (!weeklyPlanRows.length) return;
      const oldTitle = document.title;
      document.title = 'Weekly ISO Plan - ' + document.getElementById('weeklyPlanFilterWeek').value;
      document.body.classList.add('weekly-plan-print');
      window.addEventListener('afterprint', () => {
        document.body.classList.remove('weekly-plan-print');
        document.title = oldTitle;
      }, { once: true });
      window.print();
    }
