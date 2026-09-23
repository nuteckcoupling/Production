    <section id="activeJobModule" class="hidden">
      <header class="active-job-header">
        <div>
          <h1>Active Job</h1>
          <p>Current production job and shift details.</p>
        </div>
        <button type="button" class="secondary-button" onclick="switchModule('dashboard')">Back to Dashboard</button>
      </header>

      <div id="activeJobMessage" class="banner"></div>
      <div class="card active-job-card">
        <div class="active-job-title">
          <div><span>Machine</span><strong id="activeMachine">-</strong></div>
          <span id="activeStatus" class="machine-status">-</span>
        </div>
        <dl class="active-job-summary">
          <div><dt>Operator</dt><dd id="activeOperator">-</dd></div>
          <div><dt>Shift</dt><dd id="activeShift">-</dd></div>
          <div><dt>Part Name</dt><dd id="activePart">-</dd></div>
          <div><dt>Component</dt><dd id="activeComponent">-</dd></div>
          <div><dt>Operation</dt><dd id="activeOperation">-</dd></div>
          <div><dt>Cutter Number</dt><dd id="activeCutter">-</dd></div>
          <div><dt>Planned Qty</dt><dd id="activePlanned">0</dd></div>
          <div><dt>Cumulative OK</dt><dd id="activeCumulative">0</dd></div>
          <div><dt>Pending Qty</dt><dd id="activePending">0</dd></div>
          <div><dt>Job Started</dt><dd id="activeStarted">-</dd></div>
          <div><dt>Running For</dt><dd id="activeDuration">-</dd></div>
        </dl>
      </div>

      <div id="handoverSummary" class="card handover-summary-card hidden">
        <h2>Previous Shift Summary</h2>
        <dl class="active-job-summary">
          <div><dt>Previous Operator</dt><dd id="handoverPreviousOperator">-</dd></div>
          <div><dt>Shift Ended</dt><dd id="handoverEndedAt">-</dd></div>
          <div><dt>Total Qty</dt><dd id="handoverTotal">0</dd></div>
          <div><dt>OK Qty</dt><dd id="handoverOk">0</dd></div>
          <div><dt>M.C. Reject</dt><dd id="handoverReject">0</dd></div>
          <div><dt>R.M. Defect</dt><dd id="handoverDefect">0</dd></div>
          <div><dt>Rework</dt><dd id="handoverRework">0</dd></div>
          <div><dt>Downtime</dt><dd id="handoverDowntime">0 min</dd></div>
          <div><dt>Issue / Code</dt><dd id="handoverIssue">-</dd></div>
          <div><dt>Remarks</dt><dd id="handoverRemarks">-</dd></div>
        </dl>
      </div>

      <form id="acceptHandoverPanel" class="card accept-handover-card hidden" onsubmit="handleAcceptHandover(event)">
        <h2>Accept Handover</h2>
        <p>The job details remain unchanged. Confirm your incoming shift to continue production.</p>
        <input type="hidden" name="job_id" id="handover_job_id" />
        <div class="grid">
          <label>Incoming Operator<input type="text" id="handover_operator" readonly /></label>
          <label>Date<input type="date" name="shift_date" id="handover_shift_date" required /></label>
          <label>
            Incoming Shift
            <select name="shift" id="handover_shift" required>
              <option value="">-- select --</option>
            </select>
          </label>
          <label>Shift Hours<input type="number" id="handover_shift_hours" step="0.01" readonly /></label>
        </div>
        <button type="submit" class="submit handover-button" id="acceptHandoverButton">Accept Handover &amp; Start Shift</button>
      </form>


      <div id="machineActionPanel" class="card machine-action-card hidden">
        <h2>Machine Actions</h2>
        <p>Select an action only when required.</p>
        <label>Action
          <select id="machineActionSelect" onchange="handleMachineActionChange()">
            <option value="">-- select action --</option>
            <option value="cutter" id="machineActionCutter">Change Cutter</option>
            <option value="setting" id="machineActionSetting">Change Setting / Start New Job</option>
          </select>
        </label>
      </div>


      <form id="cutterChangePanel" class="card cutter-change-card hidden" onsubmit="handleStartCutterChange(event)">
        <h2>Change Cutter</h2>
        <p>Start time is recorded automatically. Complete the change to resume the same job.</p>
        <input type="hidden" name="job_id" id="cutter_change_job_id" />
        <div class="grid">
          <label>Old Cutter<input type="text" id="old_cutter_num" readonly /></label>
          <label>New Cutter<select name="new_cutter_id" id="new_cutter_id" required><option value="">-- select new cutter --</option></select></label>
          <label>Reason<select name="reason" id="cutter_change_reason" required><option value="">-- select reason --</option><option value="Tool Life Completed">Tool Life Completed</option><option value="Cutter Worn">Cutter Worn</option><option value="Cutter Damaged">Cutter Damaged</option><option value="Part Requirement">Part Requirement</option><option value="Other">Other</option></select></label>
          <label class="full">Remarks<input type="text" name="remarks" id="cutter_change_remarks" maxlength="255" placeholder="Optional remarks" /></label>
        </div>
        <button type="submit" class="submit" id="startCutterChangeButton">Start Cutter Change</button>
      </form>

      <div id="cutterChangeProgress" class="card cutter-change-card in-progress hidden">
        <h2>Cutter Change in Progress</h2>
        <p>Machine is stopped for cutter replacement. Complete the change to resume this job.</p>
        <dl class="active-job-summary">
          <div><dt>Old Cutter</dt><dd id="changingOldCutter">-</dd></div>
          <div><dt>New Cutter</dt><dd id="changingNewCutter">-</dd></div>
          <div><dt>Reason</dt><dd id="changingReason">-</dd></div>
          <div><dt>Started</dt><dd id="changingStarted">-</dd></div>
          <div><dt>Downtime</dt><dd id="changingDowntime">0 min</dd></div>
          <div><dt>Remarks</dt><dd id="changingRemarks">-</dd></div>
        </dl>
        <button type="button" class="submit cutter-complete-button" id="completeCutterChangeButton" onclick="handleCompleteCutterChange()">Complete Cutter Change &amp; Resume Job</button>
      </div>

      <form id="settingChangePanel" class="card setting-change-card hidden" onsubmit="handleStartSettingChange(event)">
        <h2>Change Setting / Start New Job</h2>
        <p>Enter the current job quantity, then select the new job. Setting downtime starts automatically.</p>
        <input type="hidden" name="job_id" id="setting_change_job_id" />
        <input type="hidden" id="setting_change_machine_id" />
        <h3>New Job Details</h3>
        <div class="grid">
          <label>Coupling Range
            <select name="new_coupling_type" id="setting_new_coupling_type" required>
              <option value="">-- select --</option>
              <option value="GC Gear Coupling">GC Gear Coupling</option>
              <option value="NA Gear Coupling">NA Gear Coupling</option>
              <option value="Roller Chain Coupling">Roller Chain Coupling</option>
              <option value="Gear">Gear</option>
              <option value="Sprocket">Sprocket</option>
            </select>
          </label>
          <label>Part Name<select name="new_part_id" id="setting_new_part_id" disabled required><option value="">-- select coupling range first --</option></select></label>
          <label>Component<select name="new_component" id="setting_new_component" disabled required><option value="">-- select part first --</option></select></label>
          <label>Drg No<input type="text" name="new_drg_no" id="setting_new_drg_no" maxlength="100" /></label>
          <label>Operation
            <select id="setting_new_operation_select"><option value="">-- automatic --</option></select>
            <input type="text" name="new_operation" id="setting_new_operation" class="custom-operation hidden" maxlength="150" placeholder="Type operation" />
          </label>
          <label>Cutter Number<select name="new_cutter_id" id="setting_new_cutter_id"><option value="">-- no cutter --</option></select></label>
          <label>Planned Qty (12 Hours)<input type="number" id="setting_new_planned_qty" value="0" readonly /></label>
          <label>Reason
            <select name="reason" id="setting_change_reason" required>
              <option value="">-- select reason --</option>
              <option value="Job Completed">Job Completed</option>
              <option value="Priority Job">Priority Job</option>
              <option value="Material Change">Material Change</option>
              <option value="New Setting">New Setting</option>
              <option value="Other">Other</option>
            </select>
          </label>
        </div>
        <h3>Current Job Production Summary</h3>
        <div class="grid">
          <label>Total Qty<input type="number" name="total_qty" min="0" value="0" required /></label>
          <label>OK Qty<input type="number" name="ok_qty" min="0" value="0" required /></label>
          <label>M.C. Reject Qty<input type="number" name="mc_reject_qty" min="0" value="0" required /></label>
          <label>R.M. Defect Qty<input type="number" name="rm_defect_qty" min="0" value="0" required /></label>
          <label>Rework Qty<input type="number" name="rework_qty" min="0" value="0" required /></label>
          <label class="full">Remarks<input type="text" name="remarks" id="setting_change_remarks" maxlength="255" placeholder="Reason/details for report" /></label>
        </div>
        <div id="settingChangeQtyError" class="banner error"></div>
        <button type="submit" class="submit setting-start-button" id="startSettingChangeButton">Start Setting Change</button>
      </form>

      <div id="settingChangeProgress" class="card setting-change-card in-progress hidden">
        <h2>Setting Change in Progress</h2>
        <p>Complete the setting to close the old job and start the selected new job.</p>
        <dl class="active-job-summary">
          <div><dt>New Part</dt><dd id="settingChangingPart">-</dd></div>
          <div><dt>Component</dt><dd id="settingChangingComponent">-</dd></div>
          <div><dt>Operation</dt><dd id="settingChangingOperation">-</dd></div>
          <div><dt>Cutter</dt><dd id="settingChangingCutter">-</dd></div>
          <div><dt>Reason</dt><dd id="settingChangingReason">-</dd></div>
          <div><dt>Started</dt><dd id="settingChangingStarted">-</dd></div>
          <div><dt>Downtime</dt><dd id="settingChangingDowntime">0 min</dd></div>
          <div><dt>Remarks</dt><dd id="settingChangingRemarks">-</dd></div>
        </dl>
        <button type="button" class="submit setting-complete-button" id="completeSettingChangeButton" onclick="handleCompleteSettingChange()">Complete Setting &amp; Start New Job</button>
      </div>
      <div id="endShiftReadOnlyMessage" class="banner success"></div>
      <form id="endShiftPanel" class="card end-shift-card hidden" onsubmit="handleEndShift(event)">
        <h2>End Shift</h2>
        <p>Enter this shift's production summary before handing over or closing the job.</p>
        <input type="hidden" name="job_id" id="end_job_id" />
        <div class="grid">
          <label>Total Qty<input type="number" name="total_qty" id="end_total_qty" min="0" value="0" required /></label>
          <label>OK Qty<input type="number" name="ok_qty" id="end_ok_qty" min="0" value="0" required /></label>
          <label>M.C. Reject Qty<input type="number" name="mc_reject_qty" id="end_mc_reject_qty" min="0" value="0" required /></label>
          <label>R.M. Defect Qty<input type="number" name="rm_defect_qty" id="end_rm_defect_qty" min="0" value="0" required /></label>
          <label>Rework Qty<input type="number" name="rework_qty" id="end_rework_qty" min="0" value="0" required /></label>
          <label>Downtime (minutes)<input type="number" name="downtime_min" id="end_downtime_min" min="0" value="0" required /></label>
          <label>
            Issue / Code
            <select name="issue_code" id="end_issue_code">
              <option value="">-- no issue --</option>
              <option value="No Operator">No Operator</option>
              <option value="No Material">No Material</option>
              <option value="M/C Breakdown (Mechanical)">M/C Breakdown (Mechanical)</option>
              <option value="M/C Breakdown (Electrical)">M/C Breakdown (Electrical)</option>
              <option value="No Power">No Power</option>
              <option value="New Setting">New Setting</option>
              <option value="Other">Other</option>
            </select>
          </label>
          <label>
            Machine Status
            <select name="machine_status" id="end_machine_status" required>
              <option value="Running">Running</option>
              <option value="Idle">Idle</option>
              <option value="Breakdown">Breakdown</option>
            </select>
          </label>
          <label>
            Job Status
            <select name="job_outcome" id="end_job_outcome" required>
              <option value="Continue Next Shift">Continue Next Shift</option>
              <option value="Job Completed">Job Completed</option>
              <option value="Job Stopped">Job Stopped</option>
            </select>
          </label>
          <label class="full">Remarks<input type="text" name="remarks" id="end_remarks" maxlength="255" /></label>
        </div>
        <div id="endShiftQtyError" class="banner error"></div>
        <button type="submit" class="submit" id="endShiftButton">End Shift</button>
      </form>
    </section>
