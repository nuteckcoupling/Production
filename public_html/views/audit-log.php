    <section id="auditLogModule" class="hidden">
      <header class="module-header">
        <h1>Activity / Audit Log</h1>
        <p>Admin-only, read-only record of login, production and management actions.</p>
      </header>

      <div class="card">
        <div id="auditLogMessage" class="banner"></div>
        <div class="report-filter-row">
          <label>From Date<input type="date" id="auditDateFrom" /></label>
          <label>To Date<input type="date" id="auditDateTo" /></label>
          <label>User<select id="auditUser"><option value="">All Users</option></select></label>
          <label>Module<select id="auditModule"><option value="">All Modules</option></select></label>
          <label>Action<select id="auditAction"><option value="">All Actions</option></select></label>
          <button type="button" class="submit" onclick="loadAuditLog()">Apply Filters</button>
          <button type="button" class="secondary-button" onclick="resetAuditFilters()">Reset</button>
        </div>
      </div>

      <div class="card list-card">
        <div class="list-heading">
          <div><h2>Recorded Activity</h2><span class="table-subtext" id="auditLogCount">0 records</span></div>
          <div class="report-actions"><button type="button" id="downloadAuditBtn" onclick="downloadAuditCsv()" disabled>Download CSV</button></div>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Date &amp; Time</th><th>User</th><th>Role</th><th>Module</th><th>Action</th><th>Details</th><th>IP Address</th></tr></thead>
            <tbody id="auditLogTableBody"><tr><td colspan="7" class="empty">Open Activity Log to load records.</td></tr></tbody>
          </table>
        </div>
        <p class="table-subtext">Audit records are read-only. Maximum 1,000 latest matching records are shown.</p>
      </div>
    </section>
