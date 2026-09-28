    <section id="maintenanceModule" class="hidden">
      <header class="module-header">
        <h1>Maintenance Management</h1>
        <p>Breakdown reporting, repair ownership, timestamps and machine-running confirmation.</p>
      </header>

      <div class="maintenance-summary">
        <div><span>Open Tickets</span><strong id="maintenanceOpenCount">0</strong></div>
        <div><span>Work In Progress</span><strong id="maintenanceProgressCount">0</strong></div>
        <div><span>Awaiting Testing</span><strong id="maintenanceTestingCount">0</strong></div>
        <div><span>Average MTTR</span><strong id="maintenanceMttr">0 min</strong></div>
      </div>

      <form class="card operator-only" id="raiseBreakdownForm" onsubmit="handleRaiseBreakdown(event)" enctype="multipart/form-data">
        <h2>Raise Breakdown Ticket</h2>
        <div id="maintenanceMessage" class="banner"></div>
        <div class="grid management-grid">
          <label>Machine<select name="machine_id" id="breakdownMachine" required><option value="">-- select machine --</option></select></label>
          <label>Breakdown Type<select name="breakdown_type" required><option value="">-- select type --</option><option>Mechanical</option><option>Electrical</option><option>Other</option></select></label>
          <label class="full">Problem Description<textarea name="problem_description" maxlength="500" required placeholder="Describe the machine problem"></textarea></label>
          <label>Breakdown Photo (optional)<input type="file" name="breakdown_photo" accept="image/jpeg,image/png" /></label>
        </div>
        <button type="submit" class="submit" id="raiseBreakdownBtn">Raise Breakdown</button>
      </form>

      <section class="card maintenance-detail-card hidden" id="maintenanceTicketDetail">
        <div class="list-card-heading">
          <div><h2 id="maintenanceDetailTitle">Ticket</h2><span class="table-subtext" id="maintenanceDetailStatus">-</span></div>
          <button type="button" class="secondary-button" onclick="closeMaintenanceDetail()">Close</button>
        </div>
        <dl class="active-job-summary" id="maintenanceDetailSummary"></dl>

        <div class="attachment-card">
          <h3>Breakdown Photographs</h3>
          <div id="maintenanceAttachmentList" class="attachment-list"></div>
          <form id="breakdownBeforeAttachmentForm" class="grid management-grid operator-only" enctype="multipart/form-data"
            onsubmit="handleAttachmentUpload(event, 'maintenance_ticket', this.dataset.entityId, 'maintenanceAttachmentList')">
            <input type="hidden" name="category" value="Breakdown Before" />
            <label>Before Photograph<input type="file" name="attachment" accept="image/jpeg,image/png" required /></label>
            <div class="management-form-actions"><button type="submit" class="submit">Upload</button></div>
          </form>
          <form id="breakdownAfterAttachmentForm" class="grid management-grid maintenance-only" enctype="multipart/form-data"
            onsubmit="handleAttachmentUpload(event, 'maintenance_ticket', this.dataset.entityId, 'maintenanceAttachmentList')">
            <input type="hidden" name="category" value="Breakdown After" />
            <label>After Photograph<input type="file" name="attachment" accept="image/jpeg,image/png" required /></label>
            <div class="management-form-actions"><button type="submit" class="submit">Upload</button></div>
          </form>
        </div>

        <form id="maintenanceAssignForm" class="grid management-grid maintenance-only hidden" onsubmit="handleMaintenanceAction(event, 'assign')">
          <input type="hidden" name="ticket_id" class="maintenance-ticket-id" />
          <label>Maintenance Person<select name="assigned_staff_id" id="maintenanceAssignedStaff" required><option value="">-- select --</option></select></label>
          <div class="management-form-actions"><button type="submit" class="submit">Assign Person</button></div>
        </form>
        <div id="maintenanceStartAction" class="maintenance-only hidden">
          <button type="button" class="submit" onclick="handleMaintenanceAction(null, 'start_work')">Start Repair Work</button>
        </div>
        <form id="maintenanceRepairForm" class="grid management-grid maintenance-only hidden" onsubmit="handleMaintenanceAction(event, 'complete_repair')">
          <input type="hidden" name="ticket_id" class="maintenance-ticket-id" />
          <label class="full">Repair Details<textarea name="repair_details" maxlength="1000" required placeholder="Work completed and fault corrected"></textarea></label>
          <label class="full">Spare Parts Used<textarea name="spare_parts_used" maxlength="500" placeholder="Optional"></textarea></label>
          <div class="management-form-actions"><button type="submit" class="submit">Complete Repair</button></div>
        </form>
        <form id="maintenanceConfirmForm" class="grid management-grid operator-only hidden" onsubmit="handleMaintenanceAction(event, 'confirm_running')">
          <input type="hidden" name="ticket_id" class="maintenance-ticket-id" />
          <label class="full">Testing Remarks<textarea name="testing_remarks" maxlength="500" required placeholder="Machine trial result and running confirmation"></textarea></label>
          <div class="management-form-actions"><button type="submit" class="submit">Confirm Machine Running</button></div>
        </form>
      </section>

      <section class="card list-card">
        <div class="list-card-heading">
          <div><h2>Breakdown Tickets</h2><span class="table-subtext">Latest 200 records</span></div>
          <div class="report-actions"><select id="maintenanceStatusFilter" onchange="loadMaintenanceTickets()"><option value="open">Open Tickets</option><option value="all">All Tickets</option></select><button type="button" class="secondary-button" onclick="loadMaintenanceTickets()">Refresh</button></div>
        </div>
        <div class="table-wrap">
          <table><thead><tr><th>Ticket</th><th>Machine</th><th>Type</th><th>Status</th><th>Assigned To</th><th>Breakdown Start</th><th>Downtime</th><th>Action</th></tr></thead>
            <tbody id="maintenanceTicketsBody"><tr><td colspan="8" class="empty">Open Maintenance to load tickets.</td></tr></tbody>
          </table>
        </div>
      </section>
    </section>
