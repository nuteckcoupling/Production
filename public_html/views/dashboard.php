    <section id="dashboardModule">
      <header class="dashboard-header">
        <div>
          <h1>Live Machine Dashboard</h1>
          <p>Current status of every active production machine.</p>
        </div>
        <div class="dashboard-actions">
          <span id="dashboardUpdated">Not refreshed yet</span>
          <button type="button" id="refreshDashboardBtn" onclick="loadMachineDashboard()">Refresh</button>
        </div>
      </header>

      <div class="status-summary" aria-label="Machine status totals">
        <button type="button" class="summary-card is-active" data-filter="All" onclick="setDashboardFilter('All')">
          <span>All Machines</span><strong id="countAll">0</strong>
        </button>
        <button type="button" class="summary-card available" data-filter="Available" onclick="setDashboardFilter('Available')">
          <span>Available</span><strong id="countAvailable">0</strong>
        </button>
        <button type="button" class="summary-card running" data-filter="Running" onclick="setDashboardFilter('Running')">
          <span>Running</span><strong id="countRunning">0</strong>
        </button>
        <button type="button" class="summary-card cutter-change" data-filter="Cutter Change" onclick="setDashboardFilter('Cutter Change')">
          <span>Cutter Change</span><strong id="countCutterChange">0</strong>
        </button>
        <button type="button" class="summary-card setting-change" data-filter="Setting Change" onclick="setDashboardFilter('Setting Change')">
          <span>Setting Change</span><strong id="countSettingChange">0</strong>
        </button>
        <button type="button" class="summary-card handover" data-filter="Handover Pending" onclick="setDashboardFilter('Handover Pending')">
          <span>Handover Pending</span><strong id="countHandoverPending">0</strong>
        </button>
        <button type="button" class="summary-card breakdown" data-filter="Breakdown" onclick="setDashboardFilter('Breakdown')">
          <span>Breakdown</span><strong id="countBreakdown">0</strong>
        </button>
        <button type="button" class="summary-card stopped" data-filter="Stopped" onclick="setDashboardFilter('Stopped')">
          <span>Stopped</span><strong id="countStopped">0</strong>
        </button>
      </div>

      <section class="alerts-panel" aria-labelledby="alertsHeading">
        <div class="alerts-heading">
          <div>
            <h2 id="alertsHeading">Automatic Alerts <span id="alertCount" class="alert-count">0</span></h2>
            <p>Shift end, handover and long-idle machine warnings.</p>
          </div>
          <span id="alertThresholds" class="alert-thresholds">Checking...</span>
        </div>
        <div id="alertsList" class="alerts-list" aria-live="polite">
          <div class="alerts-empty">Checking alerts...</div>
        </div>
      </section>

      <div id="dashboardMessage" class="banner"></div>
      <div id="machineGrid" class="machine-grid" aria-live="polite">
        <div class="dashboard-empty">Loading machine status...</div>
      </div>
    </section>
