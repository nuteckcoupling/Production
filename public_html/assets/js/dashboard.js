// Extracted from index.html during Phase 2 frontend separation.
    function formatDashboardTime(value) {
      if (!value) return '-';
      const date = new Date(value.replace(' ', 'T'));
      return Number.isNaN(date.getTime()) ? value : date.toLocaleString([], { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
    }

    function formatRunningTime(value) {
      if (!value) return '-';
      const started = new Date(value.replace(' ', 'T'));
      if (Number.isNaN(started.getTime())) return '-';
      const minutes = Math.max(0, Math.floor((Date.now() - started.getTime()) / 60000));
      const hours = Math.floor(minutes / 60);
      const remainingMinutes = minutes % 60;
      return hours > 0 ? `${hours}h ${remainingMinutes}m` : `${remainingMinutes}m`;
    }

    function loadMachineDashboard() {
      const grid = document.getElementById('machineGrid');
      const refreshButton = document.getElementById('refreshDashboardBtn');
      refreshButton.disabled = true;
      grid.innerHTML = '<div class="dashboard-empty">Refreshing machine status...</div>';
      loadAlerts();

      fetch(`${API}/get_machine_dashboard.php`)
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load machine dashboard.');
          return data;
        })
        .then(data => {
          dashboardMachines = data;
          updateDashboardCounts();
          renderMachineDashboard();
          document.getElementById('dashboardUpdated').innerText = `Updated ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
          const dashboardMessage = document.getElementById('dashboardMessage');
          if (dashboardFlashMessage) {
            dashboardMessage.className = 'banner success';
            dashboardMessage.innerText = dashboardFlashMessage;
            dashboardFlashMessage = '';
          } else {
            dashboardMessage.className = 'banner';
          }
        })
        .catch(error => {
          dashboardMachines = [];
          grid.innerHTML = `<div class="dashboard-empty error-text">${escapeHtml(error.message)}</div>`;
        })
        .finally(() => { refreshButton.disabled = false; });
    }

    function formatAlertDuration(minutes) {
      const value = Number(minutes || 0);
      if (value < 60) return `${value} min`;
      const hours = Math.floor(value / 60);
      const remaining = value % 60;
      return remaining ? `${hours}h ${remaining}m` : `${hours}h`;
    }

    function loadAlerts() {
      const list = document.getElementById('alertsList');
      fetch(`${API}/get_alerts.php`)
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load alerts.');
          return data;
        })
        .then(data => {
          document.getElementById('alertCount').innerText = Number(data.counts.total || 0);
          document.getElementById('alertCount').classList.toggle('has-alerts', Number(data.counts.total || 0) > 0);
          document.getElementById('alertThresholds').innerText = `Handover overdue: ${data.thresholds.handover_overdue_minutes} min · Idle: ${data.thresholds.idle_alert_minutes} min`;
          if (!data.alerts.length) {
            list.innerHTML = '<div class="alerts-empty ok">No pending alerts.</div>';
            return;
          }
          list.innerHTML = data.alerts.map(alert => `
            <button type="button" class="alert-item ${escapeHtml(alert.severity)}" onclick="openMachineFromDashboard(${Number(alert.machine_id)}, '${escapeHtml(alert.status)}', ${alert.job_id === null ? 'null' : Number(alert.job_id)})">
              <span class="alert-icon" aria-hidden="true">${alert.severity === 'critical' ? '!' : '⚠'}</span>
              <span class="alert-copy">
                <strong>${escapeHtml(alert.title)} — ${escapeHtml(alert.machine_code)}</strong>
                <span>${escapeHtml(alert.message)}</span>
              </span>
              <span class="alert-time">${escapeHtml(formatAlertDuration(alert.elapsed_minutes))}</span>
            </button>`).join('');
        })
        .catch(error => {
          list.innerHTML = `<div class="alerts-empty error-text">${escapeHtml(error.message)}</div>`;
        });
    }

    function updateDashboardCounts() {
      const counts = { Available: 0, Running: 0, 'Cutter Change': 0, 'Setting Change': 0, 'Handover Pending': 0, Breakdown: 0, Stopped: 0 };
      dashboardMachines.forEach(machine => { counts[machine.runtime_status] = (counts[machine.runtime_status] || 0) + 1; });
      document.getElementById('countAll').innerText = dashboardMachines.length;
      document.getElementById('countAvailable').innerText = counts.Available;
      document.getElementById('countRunning').innerText = counts.Running;
      document.getElementById('countCutterChange').innerText = counts['Cutter Change'];
      document.getElementById('countSettingChange').innerText = counts['Setting Change'];
      document.getElementById('countHandoverPending').innerText = counts['Handover Pending'];
      document.getElementById('countBreakdown').innerText = counts.Breakdown;
      document.getElementById('countStopped').innerText = counts.Stopped;
    }

    function setDashboardFilter(filter) {
      dashboardFilter = filter;
      document.querySelectorAll('.summary-card').forEach(card => {
        card.classList.toggle('is-active', card.dataset.filter === filter);
      });
      renderMachineDashboard();
    }

    function renderMachineDashboard() {
      const grid = document.getElementById('machineGrid');
      const visibleMachines = dashboardFilter === 'All'
        ? dashboardMachines
        : dashboardMachines.filter(machine => machine.runtime_status === dashboardFilter);

      if (visibleMachines.length === 0) {
        grid.innerHTML = `<div class="dashboard-empty">No ${escapeHtml(dashboardFilter === 'All' ? '' : dashboardFilter.toLowerCase() + ' ')}machines found.</div>`;
        return;
      }

      grid.innerHTML = visibleMachines.map(machine => {
        const isAvailable = machine.runtime_status === 'Available';
        const canOperate = currentUser?.role === 'Operator/Supervisor';
        const canAcceptHandover = machine.runtime_status === 'Handover Pending'
          && canOperate
          && Number(machine.current_operator_id) !== Number(currentUser.operator_id);
        const detailRows = isAvailable
          ? '<p class="machine-available-text">No active job. Machine is ready.</p>'
          : `<dl class="machine-details">
              <div><dt>Operator</dt><dd>${escapeHtml(machine.operator_name || '-')}</dd></div>
              <div><dt>Shift</dt><dd>${escapeHtml(machine.current_shift || '-')}</dd></div>
              <div><dt>Part</dt><dd>${escapeHtml(machine.part_name || '-')}</dd></div>
              <div><dt>Component</dt><dd>${escapeHtml(machine.component || '-')}</dd></div>
              <div><dt>Planned</dt><dd>${Number(machine.planned_qty || 0)}</dd></div>
              <div><dt>Pending</dt><dd>${Number(machine.pending_qty || 0)}</dd></div>
              <div><dt>Started</dt><dd>${escapeHtml(formatDashboardTime(machine.started_at))}</dd></div>
              <div><dt>Running For</dt><dd>${escapeHtml(formatRunningTime(machine.started_at))}</dd></div>
            </dl>`;
        return `<article class="machine-card ${statusClass(machine.runtime_status)}">
          <div class="machine-card-head">
            <div><h2>${escapeHtml(machine.code)}</h2><p>${escapeHtml(machine.name)}</p></div>
            <span class="machine-status">${escapeHtml(machine.runtime_status)}</span>
          </div>
          ${detailRows}
          <button type="button" class="machine-action" onclick="openMachineFromDashboard(${Number(machine.id)}, '${escapeHtml(machine.runtime_status)}', ${machine.job_id === null ? 'null' : Number(machine.job_id)})">
            ${isAvailable && canOperate ? 'Start New Job' : (canAcceptHandover ? 'Accept Handover' : (isAvailable ? 'View Status' : 'Open Job'))}
          </button>
        </article>`;
      }).join('');
    }

    function openMachineFromDashboard(machineId, status, jobId) {
      if (status === 'Available') {
        if (currentUser?.role === 'Operator/Supervisor') {
          switchModule('daily');
          machineSelect.value = String(machineId);
          machineSelect.dispatchEvent(new Event('change'));
          machineSelect.focus();
          return;
        }
        const message = document.getElementById('dashboardMessage');
        message.className = 'banner success';
        message.innerText = 'Admin / Director access is monitoring and analysis only.';
        return;
      }
      if (!jobId) {
        const message = document.getElementById('dashboardMessage');
        message.className = 'banner error';
        message.innerText = 'Active job details are unavailable. Refresh the dashboard.';
        return;
      }
      loadActiveJob(jobId);
    }
