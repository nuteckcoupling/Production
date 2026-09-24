    <section id="monthlyPlanModule" class="hidden">
      <header class="report-header">
        <div>
          <h1>Monthly Plan — ISO Document</h1>
          <p>Combines Weekly Plans for ISO documentation only. Production workflow is not affected.</p>
        </div>
        <div class="report-actions">
          <button type="button" id="downloadMonthlyPlanBtn" onclick="downloadMonthlyPlanCsv()" disabled>Download CSV / Excel</button>
          <button type="button" id="printMonthlyPlanBtn" onclick="printMonthlyPlan()" disabled>Print / Save PDF</button>
        </div>
      </header>

      <div class="card report-controls monthly-plan-controls">
        <div class="report-filter-row">
          <label>Plan Month<input type="month" id="monthlyPlanMonth" /></label>
          <button type="button" class="secondary-button" onclick="loadMonthlyPlan()">Load Month</button>
          <button type="button" class="submit report-load-button" id="generateMonthlyPlanBtn" onclick="generateMonthlyPlan()">Generate / Refresh from Weekly Plans</button>
          <button type="button" class="secondary-button" id="lockMonthlyPlanBtn" onclick="lockMonthlyPlan()" disabled>Lock Final Month</button>
        </div>
        <p class="monthly-plan-note">Weekly Plans whose Week Start falls inside the selected month are combined. A locked month cannot be refreshed.</p>
      </div>

      <div id="monthlyPlanMessage" class="banner"></div>
      <div class="monthly-plan-summary" id="monthlyPlanSummary">
        <div><span>Status</span><strong id="monthlyPlanStatus">Not Generated</strong></div>
        <div><span>Weekly Lines</span><strong id="monthlyPlanSourceCount">0</strong></div>
        <div><span>Monthly Lines</span><strong id="monthlyPlanLineCount">0</strong></div>
        <div><span>Planned Quantity</span><strong id="monthlyPlanTotalQty">0</strong></div>
      </div>

      <div class="card list-card monthly-plan-print-area" id="monthlyPlanPrintArea">
        <div class="report-brand-lockup plan-print-brand">
          <img src="assets/images/nuteck-logo.png" alt="NU-TECK Couplings Pvt. Ltd." class="report-brand-logo" />
          <div><strong id="monthlyPlanBrandHeading">NU-TECK COUPLINGS — Monthly Plan</strong><span id="monthlyPlanBrandAddress" class="hidden"></span></div>
        </div>
        <div class="list-heading">
          <div>
            <h2>Monthly ISO Plan</h2>
            <span id="monthlyPlanPeriodLabel" class="table-subtext">-</span>
          </div>
          <span id="monthlyPlanAuditLabel" class="table-subtext">Not generated</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Machine</th><th>Shift</th><th>Part</th><th>Operation</th><th>Weekly Lines</th><th>Planned Qty</th></tr></thead>
            <tbody id="monthlyPlanTableBody"><tr><td colspan="6" class="empty">Select a month to load the Monthly Plan.</td></tr></tbody>
            <tfoot id="monthlyPlanTableFoot"></tfoot>
          </table>
        </div>
        <div id="monthlyPlanGeneratedAt" class="report-generated"></div>
      </div>
    </section>
