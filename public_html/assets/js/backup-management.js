    let databaseBackups = [];

    function showBackupMessage(type, text) {
      const banner = document.getElementById('backupManagementMessage');
      banner.className = 'banner ' + type;
      banner.innerText = text;
      if (text && (type === 'success' || type === 'error')) showToast(type, text, 5000);
    }

    function loadBackups() {
      if (currentUser?.role !== 'Admin') return;
      const tbody = document.getElementById('backupTableBody');
      tbody.innerHTML = '<tr><td colspan="5" class="empty">Loading...</td></tr>';
      fetch(API + '/get_backups.php')
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load backups.');
          return data;
        })
        .then(backups => {
          databaseBackups = backups;
          renderBackups();
        })
        .catch(error => {
          tbody.innerHTML = '<tr><td colspan="5" class="empty error-text">' + escapeHtml(error.message) + '</td></tr>';
          showBackupMessage('error', error.message);
        });
    }

    function loadBackupSettings() {
      if (currentUser?.role !== 'Admin') return;
      fetch(API + '/get_backup_settings.php')
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load automatic backup settings.');
          return data;
        })
        .then(settings => {
          document.getElementById('automaticBackupEnabled').value = settings.enabled ? '1' : '0';
          document.getElementById('automaticBackupTime').value = settings.backup_time;
          document.getElementById('automaticBackupRetention').value = settings.retention_count;
          document.getElementById('automaticBackupLastRun').value = settings.last_backup_date
            ? settings.last_backup_date + (settings.last_backup_filename ? ' — ' + settings.last_backup_filename : '')
            : 'Never';
          const status = document.getElementById('backupScheduleStatus');
          status.className = 'tag ' + (settings.enabled ? 'running' : 'idle');
          status.innerText = settings.enabled ? 'Enabled' : 'Disabled';
        })
        .catch(error => showBackupMessage('error', error.message));
    }

    function handleBackupScheduleSubmit(event) {
      event.preventDefault();
      const data = Object.fromEntries(new FormData(event.target).entries());
      data.enabled = data.enabled === '1';
      const button = document.getElementById('saveBackupScheduleBtn');
      button.disabled = true;
      button.innerText = 'Saving...';
      fetch(API + '/save_backup_settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to save automatic backup settings.');
      }).then(() => {
        showBackupMessage('success', 'Automatic backup schedule saved.');
        loadBackupSettings();
        checkScheduledBackup();
      }).catch(error => showBackupMessage('error', error.message))
        .finally(() => {
          button.disabled = false;
          button.innerText = 'Save Schedule';
        });
    }

    function checkScheduledBackup() {
      if (!currentUser) return Promise.resolve();
      return fetch(API + '/run_scheduled_backup.php', { method: 'POST' })
        .then(async response => {
          const result = await response.json();
          if (!response.ok) throw new Error(result.error || 'Automatic backup check failed.');
          return result;
        })
        .then(result => {
          if (!result.performed) return;
          if (currentUser?.role === 'Admin') {
            showToast('success', 'Automatic database backup created.');
            loadBackupSettings();
            if (!backupManagementModule.classList.contains('hidden')) loadBackups();
          }
        })
        .catch(error => console.error('Automatic backup check:', error.message));
    }

    function renderBackups() {
      const tbody = document.getElementById('backupTableBody');
      if (!databaseBackups.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty">No backups created yet.</td></tr>';
        return;
      }
      tbody.innerHTML = databaseBackups.map(backup => '<tr>' +
        '<td><strong>' + escapeHtml(backup.filename) + '</strong></td>' +
        '<td><span class="tag ' + (backup.kind === 'automatic' ? 'running' : (backup.kind === 'safety' ? 'handover-pending' : 'idle')) + '">' +
          escapeHtml(backup.kind === 'automatic' ? 'Automatic' : (backup.kind === 'safety' ? 'Safety' : 'Manual')) + '</span></td>' +
        '<td>' + escapeHtml(backup.created_at) + '</td>' +
        '<td>' + escapeHtml(formatBackupSize(Number(backup.size))) + '</td>' +
        '<td class="table-actions">' +
          '<button type="button" class="table-action edit" onclick="downloadDatabaseBackup(\'' + escapeHtml(backup.filename) + '\')">Download</button>' +
          '<button type="button" class="table-action restore" onclick="prepareBackupRestore(\'' + escapeHtml(backup.filename) + '\')">Restore</button>' +
          '<button type="button" class="table-action delete" onclick="deleteDatabaseBackup(\'' + escapeHtml(backup.filename) + '\')">Delete</button>' +
        '</td></tr>').join('');
    }

    function formatBackupSize(bytes) {
      if (!Number.isFinite(bytes) || bytes < 0) return '—';
      if (bytes < 1024) return bytes + ' B';
      if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
      return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function createDatabaseBackup() {
      const button = document.getElementById('createBackupBtn');
      button.disabled = true;
      button.innerText = 'Creating...';
      fetch(API + '/create_backup.php', { method: 'POST' })
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to create backup.');
          return data;
        })
        .then(data => {
          showBackupMessage('success', 'Backup created: ' + data.backup.filename);
          loadBackups();
        })
        .catch(error => showBackupMessage('error', error.message))
        .finally(() => {
          button.disabled = false;
          button.innerText = 'Create Backup';
        });
    }

    function downloadDatabaseBackup(filename) {
      window.location.href = API + '/download_backup.php?file=' + encodeURIComponent(filename);
      showToast('info', 'Backup download started.');
    }

    function prepareBackupRestore(filename) {
      const form = document.getElementById('restoreBackupForm');
      form.reset();
      document.getElementById('restoreBackupFile').value = filename;
      document.getElementById('restoreBackupDescription').innerText =
        'Selected: ' + filename + '. Current database will be replaced. A safety backup will be created first.';
      form.classList.remove('hidden');
      form.scrollIntoView({ behavior: 'smooth' });
    }

    function cancelBackupRestore() {
      const form = document.getElementById('restoreBackupForm');
      form.reset();
      document.getElementById('restoreBackupFile').value = '';
      form.classList.add('hidden');
    }

    async function handleRestoreBackup(event) {
      event.preventDefault();
      const data = Object.fromEntries(new FormData(event.target).entries());
      if (data.confirmation !== 'RESTORE') {
        showBackupMessage('error', 'Type RESTORE exactly to confirm.');
        return;
      }
      const confirmed = await showConfirmDialog({
        title: 'Restore Entire Database?',
        message: 'Current database will be replaced by ' + data.file + '. A safety backup will be created automatically first.',
        confirmText: 'Restore Database',
        tone: 'danger'
      });
      if (!confirmed) return;
      const button = document.getElementById('restoreBackupBtn');
      button.disabled = true;
      button.innerText = 'Restoring...';
      fetch(API + '/restore_backup.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to restore backup.');
        return result;
      }).then(result => {
        showBackupMessage('success', 'Database restored. Safety backup: ' + result.safety_backup + '. Reloading...');
        window.setTimeout(() => window.location.reload(), 1800);
      }).catch(error => {
        showBackupMessage('error', error.message);
        button.disabled = false;
        button.innerText = 'Restore Database';
      });
    }

    async function deleteDatabaseBackup(filename) {
      const confirmed = await showConfirmDialog({
        title: 'Delete Backup?',
        message: filename + ' will be permanently removed. Download it first if it may be needed later.',
        confirmText: 'Delete Backup',
        tone: 'danger'
      });
      if (!confirmed) return;
      fetch(API + '/delete_backup.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ file: filename })
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to delete backup.');
      }).then(() => {
        showBackupMessage('success', 'Backup deleted.');
        loadBackups();
      }).catch(error => showBackupMessage('error', error.message));
    }

    window.setInterval(() => {
      if (currentUser) checkScheduledBackup();
    }, 300000);
