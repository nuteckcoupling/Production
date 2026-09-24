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
    </section>
