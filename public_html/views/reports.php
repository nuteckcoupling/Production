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
          <strong id="productionReportHeading">NU-TECK Machine-Wise Production Report</strong>
          <span id="productionReportAddress" class="hidden"></span>
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
                <th>Achievement</th><th>Status</th>
              </tr></thead>
              <tbody id="reportTableBody">
                <tr><td colspan="16" class="empty">Generate a report to view production data.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <div id="reportGeneratedAt" class="report-generated"></div>
      </div>
    </section>
