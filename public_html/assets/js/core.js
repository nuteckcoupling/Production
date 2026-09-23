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
    const reportModule = document.getElementById('reportModule');
    const cutterModule = document.getElementById('cutterModule');
    const couplingModule = document.getElementById('couplingModule');
    const navDashboard = document.getElementById('navDashboard');
    const navDaily = document.getElementById('navDaily');
    const navReports = document.getElementById('navReports');
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
    let showCutterTrash = false;

    document.getElementById('reportDailyDate').value = today;
    document.getElementById('reportWeeklyDate').value = today;
    document.getElementById('reportMonthlyDate').value = today.slice(0, 7);


    function switchModule(moduleName) {
      const modules = { dashboard: dashboardModule, daily: dailyModule, active: activeJobModule, reports: reportModule, cutter: cutterModule, coupling: couplingModule };
      const navItems = { dashboard: navDashboard, daily: navDaily, reports: navReports, cutter: navCutter, coupling: navCoupling };
      Object.entries(modules).forEach(([name, element]) => element.classList.toggle('hidden', name !== moduleName));
      Object.entries(navItems).forEach(([name, element]) => {
        const active = name === moduleName;
        element.classList.toggle('active', active);
        element.toggleAttribute('aria-current', active);
      });
      if (moduleName === 'dashboard') loadMachineDashboard();
      if (moduleName === 'reports' && !currentReportData) loadProductionReport();
      if (moduleName === 'cutter') loadCutters();
      if (moduleName === 'coupling') loadCouplings();
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
