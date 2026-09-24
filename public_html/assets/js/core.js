// Extracted from index.html during Phase 2 frontend separation.
    const API = "./api";
    let currentUser = null;

    // Set today's date
    const today = new Date().toISOString().slice(0, 10);
    document.getElementById('entry_date').value = today;

    // Elements
    const msgBanner = document.getElementById('messageBanner');
    const qtyErrBanner = document.getElementById('qtyErrorBanner');
    const machineAvailabilityBanner = document.getElementById('machineAvailabilityBanner');
    const submitBtn = document.getElementById('submitBtn');
    const machineSelect = document.getElementById('machine_id');
    const couplingTypeSelect = document.getElementById('coupling_type');
    const partSelect = document.getElementById('part_id');
    const componentSelect = document.getElementById('component');
    const plannedQtyInput = document.getElementById('planned_qty');
    const shiftSelect = document.getElementById('shift');
    const shiftHoursInput = document.getElementById('shift_hours');
    const dashboardModule = document.getElementById('dashboardModule');
    const dailyModule = document.getElementById('dailyModule');
    const activeJobModule = document.getElementById('activeJobModule');
    const productionPlanModule = document.getElementById('productionPlanModule');
    const weeklyPlanModule = document.getElementById('weeklyPlanModule');
    const monthlyPlanModule = document.getElementById('monthlyPlanModule');
    const shiftManagementModule = document.getElementById('shiftManagementModule');
    const machineManagementModule = document.getElementById('machineManagementModule');
    const staffManagementModule = document.getElementById('staffManagementModule');
    const userManagementModule = document.getElementById('userManagementModule');
    const changePasswordModule = document.getElementById('changePasswordModule');
    const reportModule = document.getElementById('reportModule');
    const adminAnalysisModule = document.getElementById('adminAnalysisModule');
    const cutterModule = document.getElementById('cutterModule');
    const couplingModule = document.getElementById('couplingModule');
    const navDashboard = document.getElementById('navDashboard');
    const navDaily = document.getElementById('navDaily');
    const navProductionPlan = document.getElementById('navProductionPlan');
    const navShiftManagement = document.getElementById('navShiftManagement');
    const navMachineManagement = document.getElementById('navMachineManagement');
    const navStaffManagement = document.getElementById('navStaffManagement');
    const navUserManagement = document.getElementById('navUserManagement');
    const navChangePassword = document.getElementById('navChangePassword');
    const navReports = document.getElementById('navReports');
    const navAdminAnalysis = document.getElementById('navAdminAnalysis');
    const navCutter = document.getElementById('navCutter');
    const navCoupling = document.getElementById('navCoupling');
    let parts = [];
    let dashboardMachines = [];
    let dashboardFilter = 'All';
    let dashboardFlashMessage = '';
    let startJobAvailable = false;
    let machineCheckSequence = 0;
    let currentReportPeriod = 'daily';
    let currentReportData = null;
    let currentPlanTab = 'weekly';
    let currentAnalysisPeriod = 'daily';
    let currentAnalysisData = null;
    let adminAnalysisInitialized = false;
    let activeShifts = [];
    let showCutterTrash = false;

    document.getElementById('reportDailyDate').value = today;
    document.getElementById('reportWeeklyDate').value = today;
    document.getElementById('reportMonthlyDate').value = today.slice(0, 7);
    document.getElementById('analysisDailyDate').value = today;
    document.getElementById('analysisWeeklyDate').value = today;
    document.getElementById('analysisMonthlyDate').value = today.slice(0, 7);


    function switchModule(moduleName) {
      const modules = { dashboard: dashboardModule, daily: dailyModule, active: activeJobModule, productionPlan: productionPlanModule, shiftManagement: shiftManagementModule, machineManagement: machineManagementModule, staffManagement: staffManagementModule, userManagement: userManagementModule, changePassword: changePasswordModule, reports: reportModule, adminAnalysis: adminAnalysisModule, cutter: cutterModule, coupling: couplingModule };
      const navItems = { dashboard: navDashboard, daily: navDaily, productionPlan: navProductionPlan, shiftManagement: navShiftManagement, machineManagement: navMachineManagement, staffManagement: navStaffManagement, userManagement: navUserManagement, changePassword: navChangePassword, reports: navReports, adminAnalysis: navAdminAnalysis, cutter: navCutter, coupling: navCoupling };
      Object.entries(modules).forEach(([name, element]) => element.classList.toggle('hidden', name !== moduleName));
      Object.entries(navItems).forEach(([name, element]) => {
        const active = name === moduleName;
        element.classList.toggle('active', active);
        element.toggleAttribute('aria-current', active);
      });
      if (moduleName === 'dashboard') loadMachineDashboard();
      if (moduleName === 'productionPlan') switchPlanTab(currentPlanTab);
      if (moduleName === 'shiftManagement') loadShiftManagement();
      if (moduleName === 'machineManagement') loadMachineManagement();
      if (moduleName === 'staffManagement') loadStaffManagement();
      if (moduleName === 'userManagement') loadUserManagement();
      if (moduleName === 'reports' && !currentReportData) loadProductionReport();
      if (moduleName === 'adminAnalysis') initializeAdminAnalysis();
      if (moduleName === 'cutter') loadCutters();
      if (moduleName === 'coupling') loadCouplings();
    }

    function switchPlanTab(tab) {
      currentPlanTab = tab === 'monthly' ? 'monthly' : 'weekly';
      weeklyPlanModule.classList.toggle('hidden', currentPlanTab !== 'weekly');
      monthlyPlanModule.classList.toggle('hidden', currentPlanTab !== 'monthly');
      document.getElementById('weeklyPlanTab').classList.toggle('active', currentPlanTab === 'weekly');
      document.getElementById('monthlyPlanTab').classList.toggle('active', currentPlanTab === 'monthly');
      if (currentPlanTab === 'weekly') initializeWeeklyPlan();
      else initializeMonthlyPlan();
    }

    function escapeHtml(value) {
      return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function statusClass(status) {
      return status.toLowerCase().replaceAll(' ', '-');
    }
