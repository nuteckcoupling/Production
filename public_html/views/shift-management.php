    <section id="shiftManagementModule" class="hidden">
      <header class="module-header">
        <h1>Shift Management</h1>
        <p>Admin-only master for production shifts. Used shifts can be deactivated but cannot be deleted.</p>
      </header>

      <form class="card" id="shiftManagementForm" onsubmit="handleShiftSubmit(event)">
        <input type="hidden" name="id" id="shiftManagementId" />
        <div id="shiftManagementMessage" class="banner"></div>
        <div class="grid management-grid">
          <label>Shift Code<input type="text" name="code" id="shiftCode" maxlength="30" required placeholder="e.g. A" /></label>
          <label>Shift Name<input type="text" name="name" id="shiftName" maxlength="100" required placeholder="e.g. Shift A" /></label>
          <label>Start Time<input type="time" name="start_time" id="shiftStartTime" required /></label>
          <label>End Time<input type="time" name="end_time" id="shiftEndTime" required /></label>
          <label>Shift Hours<input type="number" id="shiftCalculatedHours" step="0.01" readonly /></label>
          <label>Status<select name="status" id="shiftStatus"><option>Active</option><option>Inactive</option></select></label>
        </div>
        <div class="management-form-actions">
          <button type="submit" class="submit" id="saveShiftBtn">Save Shift</button>
          <button type="button" class="secondary-button hidden" id="cancelShiftEditBtn" onclick="resetShiftForm()">Cancel Edit</button>
        </div>
      </form>

      <div class="card list-card">
        <div class="list-heading">
          <h2>Existing Shifts</h2>
          <span class="table-subtext">Only active shifts appear in production dropdowns.</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Code</th><th>Name</th><th>Start</th><th>End</th><th>Hours</th><th>Status</th><th>Usage</th><th>Actions</th></tr></thead>
            <tbody id="shiftManagementTableBody"><tr><td colspan="8" class="empty">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </section>
