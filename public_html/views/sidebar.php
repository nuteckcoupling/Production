    <aside class="sidebar" aria-label="Production modules">
      <div class="sidebar-brand">
        <strong>NU-TECK</strong>
        <span>Production System</span>
      </div>
      <nav class="sidebar-nav">
        <button type="button" id="navDashboard" class="sidebar-item active" aria-current="page" onclick="switchModule('dashboard')">Machine Dashboard</button>
        <button type="button" id="navDaily" class="sidebar-item operator-only" onclick="switchModule('daily')">Daily Entry</button>
        <button type="button" id="navProductionPlan" class="sidebar-item" onclick="switchModule('productionPlan')">Production Plan</button>
        <button type="button" id="navReports" class="sidebar-item" onclick="switchModule('reports')">Production Reports</button>
        <button type="button" id="navAdminAnalysis" class="sidebar-item admin-only" onclick="switchModule('adminAnalysis')">Admin Analysis</button>
        <button type="button" id="navCutter" class="sidebar-item operator-only" onclick="switchModule('cutter')">Cutter Data</button>
        <button type="button" id="navCoupling" class="sidebar-item operator-only" onclick="switchModule('coupling')">Coupling Add</button>
        <button type="button" id="navShiftManagement" class="sidebar-item admin-only" onclick="switchModule('shiftManagement')">Shift Management</button>
        <button type="button" id="navMachineManagement" class="sidebar-item admin-only" onclick="switchModule('machineManagement')">Machine Management</button>
      </nav>
      <div class="sidebar-user">
        <strong id="currentUserName">-</strong>
        <span id="currentUserRole">-</span>
        <button type="button" onclick="handleLogout()">Logout</button>
      </div>
    </aside>
