// V2 Reliability: database migration status and private application errors.
    function reliabilityMessage(type, text) {
      const banner = document.getElementById('reliabilityMessage');
      banner.className = `banner ${type}`;
      banner.innerText = text;
    }

    function loadReliabilityStatus() {
      if (currentUser?.role !== 'Admin') return;
      fetch(`${API}/get_migration_status.php`)
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Unable to load migration status.');
          const { migrations, counts } = result.data;
          document.getElementById('migrationSummary').innerText = `${counts.applied} applied · ${counts.pending} pending · ${counts.attention} attention`;
          document.getElementById('applyMigrationsBtn').disabled = counts.pending === 0 || counts.attention > 0;
          document.getElementById('migrationTableBody').innerHTML = migrations.length
            ? migrations.map(row => `<tr><td>${escapeHtml(row.version)}</td><td><span class="tag ${row.status === 'Applied' ? 'active' : (row.status === 'Pending' ? 'idle' : 'inactive')}">${escapeHtml(row.status)}</span></td><td>${escapeHtml(row.applied_at || '-')}</td></tr>`).join('')
            : '<tr><td colspan="3" class="empty">No migration files found.</td></tr>';
        })
        .catch(error => reliabilityMessage('error', error.message));
      loadApplicationErrors();
    }

    function handleApplyMigrations(event) {
      event.preventDefault();
      const button = document.getElementById('applyMigrationsBtn');
      const data = Object.fromEntries(new FormData(event.target).entries());
      button.disabled = true;
      button.innerText = 'Backing Up & Applying...';
      fetch(`${API}/apply_migrations.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      }).then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Migration failed.');
          event.target.reset();
          const count = result.data.applied.length;
          reliabilityMessage('success', count ? `${count} migration(s) applied. Safety backup: ${result.data.backup}` : 'Database is already up to date.');
          showToast('success', count ? 'Database migration completed.' : 'Database is already up to date.');
          loadReliabilityStatus();
        })
        .catch(error => {
          reliabilityMessage('error', error.message);
          showToast('error', error.message);
        })
        .finally(() => {
          button.innerText = 'Backup & Apply Pending Migrations';
          button.disabled = false;
        });
    }

    function loadApplicationErrors() {
      fetch(`${API}/get_error_log.php?limit=100`)
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Unable to load error log.');
          document.getElementById('errorLogCount').innerText = `${result.data.count} recent error(s)`;
          document.getElementById('errorLogTableBody').innerHTML = result.data.errors.length
            ? result.data.errors.map(row => `<tr><td>${escapeHtml(row.timestamp || '-')}</td><td>${escapeHtml(row.reference || '-')}</td><td>${escapeHtml(row.context || '-')}</td><td>${escapeHtml(row.message || '-')}</td><td>${escapeHtml(row.path || '-')}</td></tr>`).join('')
            : '<tr><td colspan="5" class="empty">No application errors recorded.</td></tr>';
        })
        .catch(error => {
          document.getElementById('errorLogTableBody').innerHTML = `<tr><td colspan="5" class="empty error-text">${escapeHtml(error.message)}</td></tr>`;
        });
    }
