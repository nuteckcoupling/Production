    <section id="adminAnalysisModule" class="hidden">
      <header class="report-header">
        <div>
          <h1>Admin Production Analysis</h1>
          <p>Actual production performance only. Weekly and Monthly ISO Plans are not used here.</p>
        </div>
        <div class="report-actions">
          <button type="button" id="downloadAnalysisBtn" onclick="downloadAdminAnalysisCsv()" disabled>Download CSV / Excel</button>
          <button type="button" id="printAnalysisBtn" onclick="printAdminAnalysis()" disabled>Print / Save PDF</button>
        </div>
      </header>

      <div class="card report-controls">
        <div class="report-tabs analysis-tabs" role="tablist" aria-label="Analysis period">
          <button type="button" class="active" data-period="daily" onclick="setAdminAnalysisPeriod('daily')">Daily</button>
          <button type="button" data-period="weekly" onclick="setAdminAnalysisPeriod('weekly')">Weekly</button>
          <button type="button" data-period="monthly" onclick="setAdminAnalysisPeriod('monthly')">Monthly</button>
        </div>
        <div class="report-filter-row">
          <label id="dailyAnalysisFilter">Analysis Date<input type="date" id="analysisDailyDate" /></label>
          <label id="weeklyAnalysisFilter" class="hidden">Select Any Week Date<input type="date" id="analysisWeeklyDate" /></label>
          <label id="monthlyAnalysisFilter" class="hidden">Analysis Month<input type="month" id="analysisMonthlyDate" /></label>
          <label>Machine<select id="analysisMachine"><option value="">All Machines</option></select></label>
          <label>Part Name<select id="analysisPart"><option value="">All Parts</option></select></label>
          <label>Operation<select id="analysisOperation"><option value="">All Operations</option></select></label>
          <button type="button" class="submit report-load-button" id="loadAnalysisBtn" onclick="loadAdminAnalysis()">Generate Analysis</button>
        </div>
      </div>

      <div id="adminAnalysisMessage" class="banner"></div>
      <div id="adminAnalysisPrintArea" class="admin-analysis-print-area">
        <div class="report-print-heading">
          <div class="report-brand-lockup">
            <img src="assets/images/nuteck-logo.png" alt="NU-TECK Couplings Pvt. Ltd." class="report-brand-logo" />
            <div><strong id="adminAnalysisHeading">NU-TECK Admin Production Analysis</strong><span id="adminAnalysisAddress" class="hidden"></span></div>
          </div>
          <span id="analysisPeriodLabel">-</span>
        </div>
        <p class="analysis-source-note" id="analysisSourceNote">Actual production data only.</p>
        <div class="report-kpis analysis-kpis">
          <div><span>Machines</span><strong id="analysisMachines">0</strong></div>
          <div><span>Planned Qty</span><strong id="analysisPlanned">0</strong></div>
          <div><span>OK Qty</span><strong id="analysisOk">0</strong></div>
          <div><span>Achievement</span><strong id="analysisAchievement">0%</strong></div>
          <div><span>Production Pending</span><strong id="analysisPendingQty">0</strong></div>
          <div><span>Running/Pending Jobs</span><strong id="analysisPendingJobs">0</strong></div>
          <div><span>Overdue Jobs</span><strong id="analysisOverdueJobs">0</strong></div>
          <div><span>Total Reject</span><strong id="analysisReject">0</strong></div>
          <div><span>Reject Rate</span><strong id="analysisRejectRate">0%</strong></div>
          <div><span>Total Downtime</span><strong id="analysisDowntime">0 min</strong></div>
        </div>

        <div class="card report-table-card analysis-section">
          <h2>Machine Performance</h2>
          <div class="table-wrap"><table class="report-table">
            <thead><tr><th>Machine</th><th>Jobs</th><th>Shifts</th><th>Planned</th><th>Total</th><th>OK</th><th>Pending</th><th>Achievement</th><th>Reject</th><th>Reject Rate</th><th>Rework</th><th>Downtime</th><th>Pending Jobs</th><th>Overdue</th></tr></thead>
            <tbody id="analysisMachineTableBody"><tr><td colspan="14" class="empty">Generate analysis to view data.</td></tr></tbody>
          </table></div>
        </div>

        <div class="card report-table-card analysis-section">
          <h2>Pending &amp; Overdue Jobs</h2>
          <div class="table-wrap"><table class="report-table">
            <thead><tr><th>Machine</th><th>Part / Component</th><th>Operation</th><th>Planned</th><th>OK</th><th>Pending</th><th>Status</th><th>Operator / Shift</th><th>Started</th><th>Expected End</th><th>Overdue</th><th>Remarks</th></tr></thead>
            <tbody id="analysisPendingTableBody"><tr><td colspan="12" class="empty">No data loaded.</td></tr></tbody>
          </table></div>
        </div>

        <div class="card report-table-card analysis-section">
          <h2>Downtime &amp; Rejection Details</h2>
          <div class="table-wrap"><table class="report-table">
            <thead><tr><th>Date</th><th>Machine</th><th>Operator</th><th>Part</th><th>Operation</th><th>Issue / Code</th><th>Downtime</th><th>M.C. Reject</th><th>R.M. Defect</th><th>Rework</th><th>Remarks</th></tr></thead>
            <tbody id="analysisIssueTableBody"><tr><td colspan="11" class="empty">No data loaded.</td></tr></tbody>
          </table></div>
        </div>
        <div id="analysisGeneratedAt" class="report-generated"></div>
      </div>
    </section>
