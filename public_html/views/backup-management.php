    <section id="backupManagementModule" class="hidden">
      <header class="module-header">
        <h1>Database Backup Management</h1>
        <p>Admin-only protected database backup, download and restore.</p>
      </header>

      <div class="card backup-summary-card">
        <div>
          <h2>Create Complete Backup</h2>
          <p>Includes production, users, staff, plans and audit records. Backup files contain password hashes and must be kept private.</p>
        </div>
        <button type="button" class="submit" id="createBackupBtn" onclick="createDatabaseBackup()">Create Backup</button>
      </div>
      <div id="backupManagementMessage" class="banner"></div>

      <form class="card backup-restore-card hidden" id="restoreBackupForm" onsubmit="handleRestoreBackup(event)">
        <input type="hidden" name="file" id="restoreBackupFile" />
        <h2>Restore Database</h2>
        <p id="restoreBackupDescription"></p>
        <div class="grid compact-grid">
          <label>Admin Password<input type="password" name="password" autocomplete="current-password" required /></label>
          <label>Type RESTORE<input type="text" name="confirmation" autocomplete="off" required placeholder="RESTORE" /></label>
        </div>
        <div class="management-form-actions">
          <button type="submit" class="submit danger-button" id="restoreBackupBtn">Restore Database</button>
          <button type="button" class="secondary-button" onclick="cancelBackupRestore()">Cancel</button>
        </div>
      </form>

      <div class="card list-card">
        <div class="list-heading">
          <div><h2>Available Backups</h2><span class="table-subtext">Stored outside the public web folder.</span></div>
          <button type="button" class="secondary-button" onclick="loadBackups()">Refresh</button>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Backup File</th><th>Type</th><th>Created</th><th>Size</th><th>Actions</th></tr></thead>
            <tbody id="backupTableBody"><tr><td colspan="5" class="empty">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </section>
