    let auditLogInitialized = false;
    let auditLogRows = [];

    function initializeAuditLog() {
      if (currentUser?.role !== 'Admin') return;
      if (auditLogInitialized) {
        loadAuditLog();
        return;
      }
      auditLogInitialized = true;
      const monthStart = today.slice(0, 8) + '01';
      document.getElementById('auditDateFrom').value = monthStart;
      document.getElementById('auditDateTo').value = today;
      fetch(API + '/get_users.php')
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load users.');
          return data;
        })
        .then(users => {
          const select = document.getElementById('auditUser');
          users.forEach(user => select.insertAdjacentHTML('beforeend',
            '<option value="' + Number(user.id) + '">' + escapeHtml(user.username) + '</option>'));
        })
        .catch(error => showAuditLogMessage('error', error.message))
        .finally(loadAuditLog);
    }

    function showAuditLogMessage(type, text) {
      const banner = document.getElementById('auditLogMessage');
      banner.className = 'banner ' + type;
      banner.innerText = text;
    }

    function loadAuditLog() {
      if (currentUser?.role !== 'Admin') return;
      const tbody = document.getElementById('auditLogTableBody');
      const params = new URLSearchParams();
      [['date_from', 'auditDateFrom'], ['date_to', 'auditDateTo'], ['user_id', 'auditUser'],
        ['module', 'auditModule'], ['action', 'auditAction']].forEach(([key, id]) => {
        const value = document.getElementById(id).value;
        if (value) params.set(key, value);
      });
      tbody.innerHTML = '<tr><td colspan="7" class="empty">Loading...</td></tr>';
      document.getElementById('downloadAuditBtn').disabled = true;
      showAuditLogMessage('', '');
      fetch(API + '/get_audit_logs.php?' + params.toString())
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load audit log.');
          return data;
        })
        .then(data => {
          auditLogRows = data.logs;
          refreshAuditFilterOptions('auditModule', 'All Modules', data.filters.modules);
          refreshAuditFilterOptions('auditAction', 'All Actions', data.filters.actions);
          renderAuditLog();
        })
        .catch(error => {
          auditLogRows = [];
          tbody.innerHTML = '<tr><td colspan="7" class="empty error-text">' + escapeHtml(error.message) + '</td></tr>';
          showAuditLogMessage('error', error.message);
        });
    }

    function refreshAuditFilterOptions(id, firstLabel, values) {
      const select = document.getElementById(id);
      const previous = select.value;
      select.innerHTML = '<option value="">' + firstLabel + '</option>';
      values.forEach(value => select.insertAdjacentHTML('beforeend', '<option value="' + escapeHtml(value) + '">' + escapeHtml(value) + '</option>'));
      if ([...select.options].some(option => option.value === previous)) select.value = previous;
    }

    function renderAuditLog() {
      const tbody = document.getElementById('auditLogTableBody');
      document.getElementById('auditLogCount').innerText = auditLogRows.length + ' record' + (auditLogRows.length === 1 ? '' : 's');
      document.getElementById('downloadAuditBtn').disabled = auditLogRows.length === 0;
      if (!auditLogRows.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty">No activity found for these filters.</td></tr>';
        return;
      }
      tbody.innerHTML = auditLogRows.map(log => '<tr>' +
        '<td>' + escapeHtml(log.created_at) + '</td>' +
        '<td><strong>' + escapeHtml(log.username) + '</strong></td>' +
        '<td>' + escapeHtml(log.role || '—') + '</td>' +
        '<td>' + escapeHtml(log.module) + '</td>' +
        '<td><span class="tag idle">' + escapeHtml(log.action) + '</span></td>' +
        '<td>' + escapeHtml(log.description) + '</td>' +
        '<td>' + escapeHtml(log.ip_address || '—') + '</td></tr>').join('');
    }

    function resetAuditFilters() {
      document.getElementById('auditDateFrom').value = today.slice(0, 8) + '01';
      document.getElementById('auditDateTo').value = today;
      document.getElementById('auditUser').value = '';
      document.getElementById('auditModule').value = '';
      document.getElementById('auditAction').value = '';
      loadAuditLog();
    }

    function auditCsvCell(value) {
      return '"' + String(value ?? '').replaceAll('"', '""') + '"';
    }

    function downloadAuditCsv() {
      if (!auditLogRows.length) return;
      const lines = [['Date & Time', 'Username', 'Role', 'Module', 'Action', 'Details', 'Entity Type', 'Entity ID', 'IP Address']
        .map(auditCsvCell).join(',')];
      auditLogRows.forEach(log => lines.push([
        log.created_at, log.username, log.role, log.module, log.action, log.description,
        log.entity_type, log.entity_id, log.ip_address
      ].map(auditCsvCell).join(',')));
      const blob = new Blob(['\ufeff' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'activity-audit-log-' + today + '.csv';
      link.click();
      URL.revokeObjectURL(link.href);
    }
