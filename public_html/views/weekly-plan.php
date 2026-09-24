    <section id="weeklyPlanModule" class="hidden">
      <header class="report-header">
        <div>
          <h1>Weekly Plan — ISO Document</h1>
          <p>Independent planning record only. It does not affect jobs, entries, dashboard, or production reports.</p>
        </div>
        <div class="report-actions">
          <button type="button" id="downloadWeeklyPlanBtn" onclick="downloadWeeklyPlanCsv()" disabled>Download CSV / Excel</button>
          <button type="button" id="printWeeklyPlanBtn" onclick="printWeeklyPlan()" disabled>Print / Save PDF</button>
        </div>
      </header>

      <form class="card" id="weeklyPlanForm" onsubmit="handleWeeklyPlanSubmit(event)">
        <input type="hidden" name="id" id="weeklyPlanId" />
        <div id="weeklyPlanMessage" class="banner"></div>
        <div class="grid management-grid">
          <label>Week Start<input type="date" name="week_start" id="weeklyPlanWeekStart" required /></label>
          <label>Week End<input type="date" id="weeklyPlanWeekEnd" disabled /></label>
          <label>Machine<select name="machine_id" id="weeklyPlanMachine" required><option value="">-- select --</option></select></label>
          <label>Shift<select name="shift" id="weeklyPlanShift" required>
            <option value="">-- select --</option>
          </select></label>
          <label>Part Name<select name="part_id" id="weeklyPlanPart" required><option value="">-- select --</option></select></label>
          <label>Operation<input type="text" name="operation" id="weeklyPlanOperation" required placeholder="e.g. Hobbing" /></label>
          <label>Planned Quantity<input type="number" name="planned_qty" id="weeklyPlanQuantity" min="1" required /></label>
          <label>Priority<select name="priority" id="weeklyPlanPriority"><option>Low</option><option selected>Normal</option><option>High</option><option>Urgent</option></select></label>
          <label>Status<select name="status" id="weeklyPlanStatus"><option>Draft</option><option>Final</option></select></label>
          <label class="full">Remarks<input type="text" name="remarks" id="weeklyPlanRemarks" maxlength="255" /></label>
        </div>
        <div class="management-form-actions">
          <button type="submit" class="submit" id="saveWeeklyPlanBtn">Save Plan</button>
          <button type="button" class="secondary-button hidden" id="cancelWeeklyPlanEditBtn" onclick="resetWeeklyPlanForm()">Cancel Edit</button>
        </div>
      </form>

      <div class="card list-card weekly-plan-print-area" id="weeklyPlanPrintArea">
        <div class="report-brand-lockup plan-print-brand">
          <img src="assets/images/nuteck-logo.png" alt="NU-TECK Couplings Pvt. Ltd." class="report-brand-logo" />
          <div><strong id="weeklyPlanBrandHeading">NU-TECK COUPLINGS — Weekly Plan</strong><span id="weeklyPlanBrandAddress" class="hidden"></span></div>
        </div>
        <div class="list-heading">
          <div><h2>Weekly ISO Plan</h2><span id="weeklyPlanPeriodLabel" class="table-subtext">-</span></div>
          <label class="weekly-plan-filter">View Week<input type="date" id="weeklyPlanFilterWeek" onchange="loadWeeklyPlans()" /></label>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Week</th><th>Machine</th><th>Shift</th><th>Part</th><th>Operation</th><th>Planned Qty</th><th>Priority</th><th>Status</th><th>Remarks</th><th class="weekly-plan-actions-column">Actions</th></tr></thead>
            <tbody id="weeklyPlansTableBody"><tr><td colspan="10" class="empty">Select Weekly Plan to load data.</td></tr></tbody>
            <tfoot id="weeklyPlansTableFoot"></tfoot>
          </table>
        </div>
        <div id="weeklyPlanGeneratedAt" class="report-generated"></div>
      </div>
    </section>
