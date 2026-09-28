    <section id="systemSettingsModule" class="hidden">
      <header class="module-header">
        <h1>System Settings</h1>
        <p>Company/report identity and automatic alert timing. Admin-only.</p>
      </header>

      <form class="card" id="systemSettingsForm" onsubmit="handleSystemSettingsSubmit(event)">
        <div id="systemSettingsMessage" class="banner"></div>
        <div class="grid management-grid">
          <label>Company Name
            <input type="text" name="company_name" id="settingsCompanyName" maxlength="150" required />
          </label>
          <label>Report Heading
            <input type="text" name="report_heading" id="settingsReportHeading" maxlength="150" required />
          </label>
          <label class="full">Company Address / Report Footer
            <input type="text" name="company_address" id="settingsCompanyAddress" maxlength="255" placeholder="Optional" />
          </label>
          <label>Machine Idle Alert (minutes)
            <input type="number" name="idle_alert_minutes" id="settingsIdleMinutes" min="15" max="1440" required />
            <span class="table-subtext">Available machine par last completed job ke baad alert.</span>
          </label>
          <label>Handover Overdue (minutes)
            <input type="number" name="handover_overdue_minutes" id="settingsHandoverMinutes" min="5" max="1440" required />
            <span class="table-subtext">Pending handover critical alert banne ka time.</span>
          </label>
          <label>Shift-End Grace (minutes)
            <input type="number" name="shift_end_grace_minutes" id="settingsShiftGrace" min="0" max="240" required />
            <span class="table-subtext">Scheduled shift end ke baad extra allowed time.</span>
          </label>
        </div>
        <div class="management-form-actions">
          <button type="submit" class="submit" id="saveSystemSettingsBtn">Save Settings</button>
        </div>
      </form>

      <section class="card list-card">
        <div class="list-card-heading">
          <div><h2>Database Migrations</h2><span class="table-subtext" id="migrationSummary">Checking...</span></div>
          <button type="button" class="secondary-button" onclick="loadReliabilityStatus()">Refresh</button>
        </div>
        <div id="reliabilityMessage" class="banner"></div>
        <div class="table-wrap">
          <table><thead><tr><th>Migration</th><th>Status</th><th>Applied At</th></tr></thead>
            <tbody id="migrationTableBody"><tr><td colspan="3" class="empty">Checking migration status...</td></tr></tbody>
          </table>
        </div>
        <form id="applyMigrationsForm" class="grid management-grid" onsubmit="handleApplyMigrations(event)">
          <label>Admin Password<input type="password" name="password" autocomplete="current-password" required /></label>
          <label>Type MIGRATE<input type="text" name="confirmation" autocomplete="off" required placeholder="MIGRATE" /></label>
          <div class="management-form-actions"><button type="submit" class="submit" id="applyMigrationsBtn" disabled>Backup &amp; Apply Pending Migrations</button></div>
        </form>
        <p class="table-subtext">A protected safety backup is created before any pending migration is applied.</p>
      </section>

      <section class="card list-card">
        <div class="list-card-heading">
          <div><h2>Application Error Log</h2><span class="table-subtext" id="errorLogCount">0 recent errors</span></div>
          <button type="button" class="secondary-button" onclick="loadApplicationErrors()">Refresh</button>
        </div>
        <div class="table-wrap">
          <table><thead><tr><th>Time</th><th>Reference</th><th>Context</th><th>Message</th><th>Request</th></tr></thead>
            <tbody id="errorLogTableBody"><tr><td colspan="5" class="empty">Open System Settings to load errors.</td></tr></tbody>
          </table>
        </div>
        <p class="table-subtext">Only Admin can view this private log. Passwords and request bodies are not recorded.</p>
      </section>
    </section>
