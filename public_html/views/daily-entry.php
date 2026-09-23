    <section id="dailyModule" class="hidden">
    <header class="topbar">
      <h1>Start New Production Job</h1>
    </header>

    <!-- FORM VIEW -->
    <div id="formView">
      <form class="card" id="entryForm" onsubmit="handleSubmit(event)">
        <div id="messageBanner" class="banner"></div>
        <div id="machineAvailabilityBanner" class="banner error">Select a machine to check availability.</div>

        <div class="grid">
          <label>
            Date
            <input type="date" name="entry_date" id="entry_date" required />
          </label>

          <label>
            Shift
            <select name="shift" id="shift">
              <option value="1">Shift 1 — 8:00 AM to 10:30 PM</option>
              <option value="2">Shift 2 — 8:00 AM to 8:00 PM</option>
              <option value="3">Shift 3 — 8:00 PM to 8:00 AM</option>
              <option value="Day Shift">Day Shift</option>
              <option value="Night Shift">Night Shift</option>
            </select>
          </label>

          <label>
            Shift Hours
            <input type="number" name="shift_hours" id="shift_hours" value="14" readonly />
          </label>

          <label>
            Machine No
            <select name="machine_id" id="machine_id">
              <option value="">-- select --</option>
            </select>
            <span class="err" id="err_machine_id">Select machine</span>
          </label>

          <label>
            Operator Name
            <select name="operator_id" id="operator_id">
              <option value="">-- select --</option>
            </select>
            <span class="err" id="err_operator_id">Select operator</span>
          </label>

          <label>
            Coupling Range
            <select name="coupling_type" id="coupling_type">
              <option value="">-- select --</option>
              <option value="GC Gear Coupling">GC Gear Coupling</option>
              <option value="NA Gear Coupling">NA Gear Coupling</option>
              <option value="Roller Chain Coupling">Roller Chain Coupling</option>
              <option value="Gear">Gear</option>
              <option value="Sprocket">Sprocket</option>
            </select>
            <span class="err" id="err_coupling_type">Select coupling range</span>
          </label>

          <label>
            Part Name
            <select name="part_id" id="part_id" disabled>
              <option value="">-- select coupling type first --</option>
            </select>
            <span class="err" id="err_part_id">Select part</span>
          </label>

          <label>
            Component
            <select name="component" id="component" disabled>
              <option value="">-- select part first --</option>
            </select>
            <span class="err" id="err_component">Select component</span>
          </label>

          <label>
            Drg No
            <input type="text" name="drg_no" id="drg_no" />
          </label>

          <label>
            Operation
            <select id="operation_select">
              <option value="">-- select machine first --</option>
            </select>
            <input type="text" name="operation" id="operation" class="custom-operation hidden" maxlength="150" placeholder="Type operation" />
          </label>

          <label>
            Cutter Num
            <select name="cutter_id" id="cutter_id">
              <option value="">-- select --</option>
            </select>
          </label>

          <label>
            Planned Qty (12 Hours)
            <input type="number" name="planned_qty" id="planned_qty" value="0" readonly />
          </label>

          <label>
            Start Time
            <input type="text" id="job_start_time" value="Automatic when job starts" readonly />
          </label>

          <label class="end-shift-only hidden">
            Total Qty
            <input type="number" name="total_qty" id="total_qty" />
          </label>

          <label class="end-shift-only hidden">
            OK Qty
            <input type="number" name="ok_qty" id="ok_qty" />
          </label>

          <label class="end-shift-only hidden">
            M.C. Reject Qty
            <input type="number" name="mc_reject_qty" id="mc_reject_qty" />
          </label>

          <label class="end-shift-only hidden">
            R.M. Defect Qty
            <input type="number" name="rm_defect_qty" id="rm_defect_qty" />
          </label>

          <label class="end-shift-only hidden">
            Rework Qty
            <input type="number" name="rework_qty" id="rework_qty" />
          </label>

          <label class="end-shift-only hidden">
            Machine Status
            <select name="machine_status" id="machine_status">
              <option value="Running">Running</option>
              <option value="Idle">Idle</option>
              <option value="Breakdown">Breakdown</option>
            </select>
          </label>

          <label class="end-shift-only hidden">
            Downtime (min)
            <input type="number" name="downtime_min" id="downtime_min" />
          </label>

          <label class="end-shift-only hidden">
            Issue / Code
            <select name="issue_code" id="issue_code">
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

          <label class="full end-shift-only hidden">
            Remarks
            <input type="text" name="remarks" id="remarks" />
          </label>
        </div>

        <div id="qtyErrorBanner" class="banner error"></div>

        <button type="submit" class="submit" id="submitBtn" disabled>Start New Job</button>
      </form>
    </div>

    </section>
