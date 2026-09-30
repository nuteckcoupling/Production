    <section id="reportModule" class="hidden">
      <header class="report-header">
        <div>
          <h1>Machine-Wise Production Reports</h1>
          <p>Daily, weekly and monthly production performance for every machine.</p>
        </div>
        <div class="report-actions">
          <button type="button" id="downloadReportBtn" onclick="downloadReportCsv()" disabled>Download CSV / Excel</button>
          <button type="button" id="printReportBtn" onclick="printProductionReport()" disabled>Print / Save PDF</button>
        </div>
      </header>

      <div class="card report-controls">
        <div class="report-tabs" role="tablist" aria-label="Report period">
          <button type="button" class="active" data-period="daily" onclick="setReportPeriod('daily')">Daily</button>
          <button type="button" data-period="weekly" onclick="setReportPeriod('weekly')">Weekly</button>
          <button type="button" data-period="monthly" onclick="setReportPeriod('monthly')">Monthly</button>
        </div>
        <div class="report-filter-row">
          <label id="dailyReportFilter">Report Date<input type="date" id="reportDailyDate" /></label>
          <label id="weeklyReportFilter" class="hidden">Select Any Week Date<input type="date" id="reportWeeklyDate" /></label>
          <label id="monthlyReportFilter" class="hidden">Report Month<input type="month" id="reportMonthlyDate" /></label>
          <label>Machine<select id="reportMachine"><option value="">All Machines</option></select></label>
          <label>Shift<select id="reportShift"><option value="">All Shifts</option></select></label>
          <label>Operator<select id="reportOperator"><option value="">All Operators</option></select></label>
          <label>Coupling Range<select id="reportCoupling"><option value="">All Ranges</option><option value="GC Gear Coupling">GC Gear Coupling</option><option value="NA Gear Coupling">NA Gear Coupling</option><option value="Roller Chain Coupling">Roller Chain Coupling</option><option value="Gear">Gear</option><option value="Sprocket">Sprocket</option></select></label>
          <label>Part Name<select id="reportPart"><option value="">All Parts</option></select></label>
          <button type="button" class="submit report-load-button" id="loadReportBtn" onclick="loadProductionReport()">Generate Report</button>
        </div>
      </div>

      <div id="reportMessage" class="banner"></div>
      <div id="reportPrintArea">
        <div class="report-print-heading">
          <div class="report-brand-lockup">
            <img src="assets/images/nuteck-logo.png" alt="NU-TECK Couplings Pvt. Ltd." class="report-brand-logo" />
            <div><strong id="productionReportHeading">NU-TECK Machine-Wise Production Report</strong><span id="productionReportAddress" class="hidden"></span></div>
          </div>
          <span id="reportPeriodLabel">-</span>
        </div>
        <div class="report-kpis">
          <div><span>Machines</span><strong id="reportMachines">0</strong></div>
          <div><span>Planned Qty</span><strong id="reportPlanned">0</strong></div>
          <div><span>Total Qty</span><strong id="reportTotal">0</strong></div>
          <div><span>OK Qty</span><strong id="reportOk">0</strong></div>
          <div><span>Pending Qty</span><strong id="reportPending">0</strong></div>
          <div><span>Achievement</span><strong id="reportAchievement">0%</strong></div>
          <div><span>Total Reject</span><strong id="reportReject">0</strong></div>
          <div><span>Downtime</span><strong id="reportDowntime">0 min</strong></div>
        </div>
        <div class="card report-table-card">
          <div class="table-wrap">
            <table class="report-table">
              <thead><tr>
                <th>Machine</th><th>Operators</th><th>Shifts</th><th>Parts</th><th>Components</th>
                <th>Jobs</th><th>Planned</th><th>Total</th><th>OK</th><th>M.C. Reject</th>
                <th>R.M. Defect</th><th>Rework</th><th>Pending</th><th>Downtime</th>
                <th>Achievement</th><th>Status</th><th>Remarks</th><th>Details</th>
              </tr></thead>
              <tbody id="reportTableBody">
                <tr><td colspan="18" class="empty">Generate a report to view production data.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <div id="reportEntryDetails" class="card report-table-card hidden">
          <div class="report-entry-heading">
            <div><strong id="reportEntryTitle">Production Entries</strong><span id="reportEntryCount" class="table-subtext"></span></div>
            <button type="button" onclick="closeReportEntries()">Close</button>
          </div>
          <div class="table-wrap">
            <table class="report-table">
              <thead><tr>
                <th>Date</th><th>Shift</th><th>Operator</th><th>Job</th><th>Part / Component</th>
                <th>Operation / Cutter</th><th>Planned</th><th>Total</th><th>OK</th><th>M.C. Reject</th>
                <th>R.M. Defect</th><th>Rework</th><th>Downtime</th><th>Status</th><th>Issue / Remarks</th><th>Action</th>
              </tr></thead>
              <tbody id="reportEntryTableBody"></tbody>
            </table>
          </div>
          <form id="reportEntryEditForm" class="card hidden">
            <input type="hidden" name="shift_id" id="reportEditShiftId" />
            <h3 id="reportEditTitle">Correct Production Entry</h3>
            <p class="banner warning">This entry can be edited only once. Check every value before saving.</p>
            <div class="grid">
              <label>Total Qty<input type="number" name="total_qty" min="0" required /></label>
              <label>OK Qty<input type="number" name="ok_qty" min="0" required /></label>
              <label>M.C. Reject<input type="number" name="mc_reject_qty" min="0" required /></label>
              <label>R.M. Defect<input type="number" name="rm_defect_qty" min="0" required /></label>
              <label>Rework<input type="number" name="rework_qty" min="0" required /></label>
              <label>Downtime (Minutes)<input type="number" name="downtime_min" min="0" required /></label>
              <label>Issue / Code<select name="issue_code"><option value="">-- none --</option><option>No Operator</option><option>No Material</option><option>M/C Breakdown (Mechanical)</option><option>M/C Breakdown (Electrical)</option><option>No Power</option><option>New Setting</option><option>Other</option></select></label>
              <label class="full">Remarks<input type="text" name="remarks" maxlength="255" /></label>
              <label class="full">Correction Reason<input type="text" name="edit_reason" maxlength="255" required placeholder="Why is this entry being corrected?" /></label>
            </div>
            <div class="actions"><button type="submit" class="submit">Save One-Time Correction</button><button type="button" onclick="closeReportEntryEdit()">Cancel</button></div>
          </form>
          <div id="reportCorrectionHistory" class="card hidden">
            <div class="report-entry-heading">
              <div><strong id="reportCorrectionTitle">Correction History</strong><span id="reportCorrectionMeta" class="table-subtext"></span></div>
              <button type="button" onclick="closeReportCorrection()">Close</button>
            </div>
            <div class="table-wrap">
              <table class="report-table">
                <thead><tr><th>Field</th><th>Original Value</th><th>Corrected Value</th></tr></thead>
                <tbody id="reportCorrectionTableBody"></tbody>
              </table>
            </div>
            <p id="reportCorrectionReason" class="banner"></p>
          </div>
        </div>
        <div id="reportGeneratedAt" class="report-generated"></div>
      </div>
    </section>
