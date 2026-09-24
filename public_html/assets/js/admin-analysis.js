    function setAdminAnalysisPeriod(period) {
      currentAnalysisPeriod = period;
      document.querySelectorAll('.analysis-tabs button').forEach(button => {
        button.classList.toggle('active', button.dataset.period === period);
      });
      document.getElementById('dailyAnalysisFilter').classList.toggle('hidden', period !== 'daily');
      document.getElementById('weeklyAnalysisFilter').classList.toggle('hidden', period !== 'weekly');
      document.getElementById('monthlyAnalysisFilter').classList.toggle('hidden', period !== 'monthly');
      currentAnalysisData = null;
      document.getElementById('downloadAnalysisBtn').disabled = true;
      document.getElementById('printAnalysisBtn').disabled = true;
    }

    function adminAnalysisFilterValue() {
      if (currentAnalysisPeriod === 'weekly') return document.getElementById('analysisWeeklyDate').value;
      if (currentAnalysisPeriod === 'monthly') return document.getElementById('analysisMonthlyDate').value;
      return document.getElementById('analysisDailyDate').value;
    }

    function refreshAdminAnalysisMachines() {
      const select = document.getElementById('analysisMachine');
      const previous = select.value;
      select.innerHTML = '<option value="">All Machines</option>';
      return fetch(API + '/get_machines.php')
        .then(response => response.json())
        .then(machines => {
          machines.forEach(machine => select.insertAdjacentHTML('beforeend',
            '<option value="' + Number(machine.id) + '">' + escapeHtml(machine.code) + ' — ' + escapeHtml(machine.name) + '</option>'));
          if ([...select.options].some(option => option.value === previous)) select.value = previous;
        });
    }

    function refreshAdminAnalysisParts() {
      const select = document.getElementById('analysisPart');
      const previous = select.value;
      select.innerHTML = '<option value="">All Parts</option>';
      parts.forEach(part => select.insertAdjacentHTML('beforeend',
        '<option value="' + Number(part.id) + '">' + escapeHtml(part.part_name) + ' — ' + escapeHtml(part.coupling_type || '') + '</option>'));
      if ([...select.options].some(option => option.value === previous)) select.value = previous;
    }

    function refreshAdminAnalysisOperations() {
      const select = document.getElementById('analysisOperation');
      const previous = select.value;
      select.innerHTML = '<option value="">All Operations</option>';
      return fetch(API + '/get_operations.php')
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load operations.');
          return data;
        })
        .then(operations => {
          operations.forEach(operation => select.insertAdjacentHTML('beforeend',
            '<option value="' + escapeHtml(operation) + '">' + escapeHtml(operation) + '</option>'));
          if ([...select.options].some(option => option.value === previous)) select.value = previous;
        });
    }

    function initializeAdminAnalysis() {
      if (currentUser?.role !== 'Admin') return;
      refreshAdminAnalysisParts();
      if (!adminAnalysisInitialized) {
        adminAnalysisInitialized = true;
        Promise.all([refreshAdminAnalysisMachines(), refreshAdminAnalysisOperations()])
          .then(loadAdminAnalysis)
          .catch(error => showAdminAnalysisMessage('error', error.message));
      } else if (!currentAnalysisData) {
        loadAdminAnalysis();
      }
    }

    function showAdminAnalysisMessage(type, text) {
      const banner = document.getElementById('adminAnalysisMessage');
      banner.className = 'banner ' + type;
      banner.innerText = text;
    }

    function loadAdminAnalysis() {
      const value = adminAnalysisFilterValue();
      if (!value) {
        showAdminAnalysisMessage('error', 'Select the analysis date or month.');
        return;
      }
      const button = document.getElementById('loadAnalysisBtn');
      button.disabled = true;
      button.innerText = 'Generating...';
      document.getElementById('adminAnalysisMessage').className = 'banner';
      const params = new URLSearchParams({ period: currentAnalysisPeriod, value });
      const filters = {
        machine_id: document.getElementById('analysisMachine').value,
        part_id: document.getElementById('analysisPart').value,
        operation: document.getElementById('analysisOperation').value
      };
      Object.entries(filters).forEach(([key, filterValue]) => {
        if (filterValue) params.set(key, filterValue);
      });
      fetch(API + '/get_admin_analysis.php?' + params.toString())
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to generate analysis.');
          return data;
        })
        .then(data => {
          currentAnalysisData = data;
          renderAdminAnalysis(data);
          document.getElementById('downloadAnalysisBtn').disabled = false;
          document.getElementById('printAnalysisBtn').disabled = false;
        })
        .catch(error => showAdminAnalysisMessage('error', error.message))
        .finally(() => {
          button.disabled = false;
          button.innerText = 'Generate Analysis';
        });
    }

    function renderAdminAnalysis(data) {
      const totals = data.totals;
      document.getElementById('analysisPeriodLabel').innerText = data.period.charAt(0).toUpperCase() + data.period.slice(1) + ' · ' + data.period_label;
      document.getElementById('analysisSourceNote').innerText = data.source_note;
      document.getElementById('analysisMachines').innerText = totals.machine_count;
      document.getElementById('analysisPlanned').innerText = totals.planned_qty;
      document.getElementById('analysisOk').innerText = totals.ok_qty;
      document.getElementById('analysisAchievement').innerText = totals.achievement_percent + '%';
      document.getElementById('analysisPendingQty').innerText = totals.pending_qty;
      document.getElementById('analysisPendingJobs').innerText = totals.pending_job_count;
      document.getElementById('analysisOverdueJobs').innerText = totals.overdue_job_count;
      document.getElementById('analysisReject').innerText = Number(totals.mc_reject_qty) + Number(totals.rm_defect_qty);
      document.getElementById('analysisRejectRate').innerText = totals.reject_percent + '%';
      document.getElementById('analysisDowntime').innerText = totals.downtime_min + ' min';
      document.getElementById('analysisGeneratedAt').innerText = 'Generated: ' + formatDashboardTime(data.generated_at);

      const machinesBody = document.getElementById('analysisMachineTableBody');
      machinesBody.innerHTML = data.rows.length ? data.rows.map(row => '<tr>' +
        '<td><strong>' + escapeHtml(row.machine_code) + '</strong><br><span class="table-subtext">' + escapeHtml(row.machine_name) + '</span></td>' +
        '<td>' + Number(row.job_count) + '</td><td>' + Number(row.shift_count) + '</td><td>' + Number(row.planned_qty) + '</td>' +
        '<td>' + Number(row.total_qty) + '</td><td>' + Number(row.ok_qty) + '</td><td>' + Number(row.pending_qty) + '</td>' +
        '<td>' + Number(row.achievement_percent) + '%</td><td>' + (Number(row.mc_reject_qty) + Number(row.rm_defect_qty)) + '</td>' +
        '<td>' + Number(row.reject_percent) + '%</td><td>' + Number(row.rework_qty) + '</td><td>' + Number(row.downtime_min) + ' min</td>' +
        '<td>' + Number(row.pending_job_count) + '</td><td>' + Number(row.overdue_job_count) + '</td></tr>').join('')
        : '<tr><td colspan="14" class="empty">No actual production or pending jobs found for this period.</td></tr>';

      const pendingBody = document.getElementById('analysisPendingTableBody');
      pendingBody.innerHTML = data.pending_jobs.length ? data.pending_jobs.map(job => '<tr class="' + (Number(job.overdue_minutes) > 0 ? 'analysis-overdue-row' : '') + '">' +
        '<td><strong>' + escapeHtml(job.machine_code) + '</strong></td><td>' + escapeHtml(job.part_name) + '<br><span class="table-subtext">' + escapeHtml(job.component) + '</span></td>' +
        '<td>' + escapeHtml(job.operation || '-') + '</td><td>' + Number(job.planned_qty) + '</td><td>' + Number(job.cumulative_ok_qty) + '</td>' +
        '<td>' + Number(job.pending_qty) + '</td><td>' + escapeHtml(job.status) + '</td><td>' + escapeHtml(job.operator_name || '-') + '<br><span class="table-subtext">' + escapeHtml(job.shift_name || '-') + '</span></td>' +
        '<td>' + escapeHtml(formatDashboardTime(job.shift_started_at || job.started_at)) + '</td><td>' + escapeHtml(formatDashboardTime(job.expected_end)) + '</td>' +
        '<td>' + (Number(job.overdue_minutes) > 0 ? '<strong class="error-text">' + Number(job.overdue_minutes) + ' min</strong>' : 'No') + '</td><td>' + escapeHtml(job.remarks || '-') + '</td></tr>').join('')
        : '<tr><td colspan="12" class="empty">No running or pending jobs for this period.</td></tr>';

      const issueBody = document.getElementById('analysisIssueTableBody');
      issueBody.innerHTML = data.issues.length ? data.issues.map(issue => '<tr>' +
        '<td>' + escapeHtml(issue.date) + '</td><td><strong>' + escapeHtml(issue.machine_code) + '</strong></td><td>' + escapeHtml(issue.operator_name || '-') + '</td>' +
        '<td>' + escapeHtml(issue.part_name) + '</td><td>' + escapeHtml(issue.operation || '-') + '</td><td>' + escapeHtml(issue.issue_code) + '</td>' +
        '<td>' + Number(issue.downtime_min) + ' min</td><td>' + Number(issue.mc_reject_qty) + '</td><td>' + Number(issue.rm_defect_qty) + '</td>' +
        '<td>' + Number(issue.rework_qty) + '</td><td>' + escapeHtml(issue.remarks || '-') + '</td></tr>').join('')
        : '<tr><td colspan="11" class="empty">No downtime, rejection, rework or remarks recorded for this period.</td></tr>';
    }

    function downloadAdminAnalysisCsv() {
      if (!currentAnalysisData) return;
      const data = currentAnalysisData;
      const lines = [
        [csvCell(`${systemSettings.company_name} — Admin Production Analysis`)].join(','),
        [csvCell(systemSettings.company_address || '')].join(','),
        [csvCell(data.period_label)].join(','),
        [csvCell(data.source_note)].join(','), '',
        ['Machine Performance'].map(csvCell).join(','),
        ['Machine Number','Machine Name','Jobs','Shifts','Planned','Total','OK','Pending','Achievement %','M.C. Reject','R.M. Defect','Reject %','Rework','Downtime Minutes','Pending Jobs','Overdue Jobs'].map(csvCell).join(',')
      ];
      data.rows.forEach(row => lines.push([row.machine_code,row.machine_name,row.job_count,row.shift_count,row.planned_qty,row.total_qty,row.ok_qty,row.pending_qty,row.achievement_percent,row.mc_reject_qty,row.rm_defect_qty,row.reject_percent,row.rework_qty,row.downtime_min,row.pending_job_count,row.overdue_job_count].map(csvCell).join(',')));
      lines.push('', ['Pending and Overdue Jobs'].map(csvCell).join(','),
        ['Machine','Part','Component','Operation','Planned','OK','Pending','Status','Operator','Shift','Started','Expected End','Overdue Minutes','Remarks'].map(csvCell).join(','));
      data.pending_jobs.forEach(job => lines.push([job.machine_code,job.part_name,job.component,job.operation,job.planned_qty,job.cumulative_ok_qty,job.pending_qty,job.status,job.operator_name,job.shift_name,job.shift_started_at || job.started_at,job.expected_end,job.overdue_minutes,job.remarks].map(csvCell).join(',')));
      lines.push('', ['Downtime and Rejection Details'].map(csvCell).join(','),
        ['Date','Machine','Operator','Part','Operation','Issue / Code','Downtime Minutes','M.C. Reject','R.M. Defect','Rework','Remarks'].map(csvCell).join(','));
      data.issues.forEach(issue => lines.push([issue.date,issue.machine_code,issue.operator_name,issue.part_name,issue.operation,issue.issue_code,issue.downtime_min,issue.mc_reject_qty,issue.rm_defect_qty,issue.rework_qty,issue.remarks].map(csvCell).join(',')));
      const blob = new Blob(['\ufeff' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'admin-analysis-' + data.period + '-' + data.start_date + '-to-' + data.end_date + '.csv';
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    }

    function printAdminAnalysis() {
      if (!currentAnalysisData) return;
      const oldTitle = document.title;
      document.title = systemSettings.company_name + ' - Admin Production Analysis - ' + currentAnalysisData.period_label;
      document.body.classList.add('admin-analysis-print');
      window.print();
      document.body.classList.remove('admin-analysis-print');
      document.title = oldTitle;
    }
