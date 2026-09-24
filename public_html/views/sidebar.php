    <aside class="sidebar" aria-label="Production modules">
      <div class="sidebar-brand">
        <strong id="sidebarCompanyName">NU-TECK</strong>
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
        <button type="button" id="navStaffManagement" class="sidebar-item admin-only" onclick="switchModule('staffManagement')">Staff Management</button>
        <button type="button" id="navUserManagement" class="sidebar-item admin-only" onclick="switchModule('userManagement')">User Management</button>
        <button type="button" id="navAuditLog" class="sidebar-item admin-only" onclick="switchModule('auditLog')">Activity Log</button>
        <button type="button" id="navSystemSettings" class="sidebar-item admin-only" onclick="switchModule('systemSettings')">System Settings</button>
        <button type="button" id="navBackupManagement" class="sidebar-item admin-only" onclick="switchModule('backupManagement')">Database Backups</button>
        <button type="button" id="navChangePassword" class="sidebar-item" onclick="switchModule('changePassword')">Change My Password</button>
      </nav>
      <div class="sidebar-user">
        <strong id="currentUserName">-</strong>
        <span id="currentUserRole">-</span>
        <button type="button" onclick="handleLogout()">Logout</button>
      </div>
    </aside>
