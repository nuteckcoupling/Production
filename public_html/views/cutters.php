    <section id="cutterModule" class="hidden">
      <header class="module-header coupling-page-header">
        <div>
          <h1>Cutter Data</h1>
          <p>Find, add and manage cutters used in production.</p>
        </div>
        <button type="button" class="submit coupling-add-button" onclick="openCutterEditor()">+ Add Cutter</button>
      </header>

      <div id="cutterMessage" class="banner"></div>

      <section class="card coupling-toolbar cutter-toolbar" aria-label="Cutter filters">
        <label>Search Cutter<input type="search" id="cutterSearch" placeholder="Number, module or type" autocomplete="off" /></label>
        <label>Module<select id="cutterModuleFilter"><option value="">All Modules</option></select></label>
        <div class="coupling-view-tabs cutter-view-tabs" role="tablist" aria-label="Cutter status">
          <button type="button" id="activeCutterTab" class="active" onclick="setCutterView('Active')">Active</button>
          <button type="button" id="inactiveCutterTab" onclick="setCutterView('Not Active')">Not Active</button>
          <button type="button" id="deletedCutterTab" onclick="setCutterView('Trash')">Trash</button>
        </div>
        <span id="cutterResultCount" class="coupling-result-count"></span>
      </section>

      <div id="cutterCardList" class="coupling-card-grid" aria-live="polite">
        <div class="card empty">Loading cutters...</div>
      </div>
    </section>

    <section id="cutterServiceModule" class="hidden">
      <section class="card cutter-challan-section">
        <header class="module-header"><div><h1>Cutter Service Challans</h1><p>Send multiple cutters together for grinding or coating and record returns individually.</p></div>
          <div class="dialog-actions"><button type="button" class="secondary-button" onclick="openCutterVendorManager()">Manage Vendors</button><button type="button" class="secondary-button" onclick="openCutterVendorDialog()">+ Add Vendor</button><button type="button" class="submit" onclick="openCutterChallanDialog()">+ New Challan</button></div>
        </header>
        <div id="cutterChallanMessage" class="banner"></div>
        <div id="cutterChallanList" class="table-scroll"><p>Loading service challans...</p></div>
      </section>

      <dialog id="cutterVendorManagerDialog" class="management-dialog wide-dialog" aria-labelledby="cutterVendorManagerTitle"><div class="dialog-shell">
        <header class="dialog-header"><div><h2 id="cutterVendorManagerTitle">Service Vendor Management</h2><p>Edit vendor details, deactivate vendors, or delete vendors that have never been used on a challan.</p></div><button type="button" class="dialog-close" onclick="document.getElementById('cutterVendorManagerDialog').close()">&times;</button></header>
        <div id="cutterVendorManagerList" class="table-scroll"><p>Loading vendors...</p></div>
        <div class="dialog-actions"><button type="button" class="submit" onclick="openCutterVendorDialog()">+ Add Vendor</button></div>
      </div></dialog>

      <dialog id="cutterVendorDialog" class="management-dialog" aria-labelledby="cutterVendorTitle"><div class="dialog-shell">
        <header class="dialog-header"><div><h2 id="cutterVendorTitle">Add Service Vendor</h2><p>Vendor details for cutter challans.</p></div><button type="button" class="dialog-close" onclick="document.getElementById('cutterVendorDialog').close()">&times;</button></header>
        <form id="cutterVendorForm" onsubmit="saveCutterVendor(event)"><input type="hidden" name="id"><div class="grid management-grid">
          <label>Vendor Name<input name="name" maxlength="150" required></label><label>Phone (optional)<input name="phone" maxlength="30"></label>
          <label class="full-width">Address<textarea name="address" maxlength="500" required></textarea></label>
        </div><div class="dialog-actions"><button class="submit" id="cutterVendorSubmit" type="submit">Save Vendor</button></div></form>
      </div></dialog>

      <dialog id="cutterChallanDialog" class="management-dialog wide-dialog" aria-labelledby="cutterChallanTitle"><div class="dialog-shell">
        <header class="dialog-header"><div><h2 id="cutterChallanTitle">New Cutter Service Challan</h2><p>Select one or more physical cutters for the same vendor dispatch.</p></div><button type="button" class="dialog-close" onclick="document.getElementById('cutterChallanDialog').close()">&times;</button></header>
        <form id="cutterChallanForm" onsubmit="createCutterChallan(event)"><div class="grid management-grid">
          <label>Vendor<select name="vendor_id" id="challanVendor" required><option value="">Select vendor</option></select></label>
          <label>Dispatch Date<input type="date" name="dispatch_date" value="<?php echo date('Y-m-d'); ?>" required></label>
          <label>Expected Return (optional)<input type="date" name="expected_return_date"></label>
          <label class="full-width">Challan Remarks<input name="remarks" maxlength="255" placeholder="Optional"></label>
        </div><h3>Cutters to Send</h3><p id="challanCutterEmpty" class="muted">Loading available cutters...</p><div id="challanCutterChoices" class="cutter-challan-choices"></div>
        <div class="dialog-actions"><button type="button" class="secondary-button" onclick="document.getElementById('cutterChallanDialog').close()">Cancel</button><button class="submit" type="submit">Create &amp; Print Challan</button></div></form>
      </div></dialog>

      <dialog id="cutterReceiveDialog" class="management-dialog" aria-labelledby="cutterReceiveTitle"><div class="dialog-shell">
        <header class="dialog-header"><div><h2 id="cutterReceiveTitle">Receive Cutter</h2><p id="cutterReceiveSubtitle"></p></div><button type="button" class="dialog-close" onclick="document.getElementById('cutterReceiveDialog').close()">&times;</button></header>
        <form id="cutterReceiveForm" onsubmit="receiveCutterService(event)"><input type="hidden" name="item_id"><div class="grid management-grid">
          <label>Service Completed?<select name="service_done" required><option value="1">Yes — log to Tool History</option><option value="0">No — returned without service</option></select></label>
          <label>Service Date<input type="date" name="service_date" value="<?php echo date('Y-m-d'); ?>" required></label>
          <label>Received Date<input type="date" name="received_date" value="<?php echo date('Y-m-d'); ?>" required></label>
          <label>Return Condition<input name="return_condition" maxlength="80" placeholder="e.g. Good / needs rework"></label>
          <label class="full-width">Return Remarks<textarea name="return_remarks" maxlength="255"></textarea></label>
        </div><div class="dialog-actions"><button class="submit" type="submit">Confirm Return</button></div></form>
      </div></dialog>
    </section>

      <dialog id="cutterEditorDialog" class="management-dialog wide-dialog" aria-labelledby="cutterFormTitle">
        <div class="dialog-shell">
          <header class="dialog-header">
            <div><h2 id="cutterFormTitle">Add Cutter</h2><p>Enter the physical cutter details.</p></div>
            <button type="button" class="dialog-close" aria-label="Close" onclick="cancelCutterEdit()">&times;</button>
          </header>
          <form id="cutterForm" onsubmit="handleCutterSubmit(event)">
            <input type="hidden" name="id" id="cutter_edit_id" />
            <div class="grid management-grid">
              <label>Cutter Number<input type="text" name="cutter_num" id="cutter_num" required placeholder="e.g. NTCPL/0926001" /></label>
              <label>Cutter Name<input type="text" name="cutter_name" id="cutter_name" placeholder="Optional cutter name" /></label>
              <label>Module<input type="text" name="module" id="cutter_module" placeholder="e.g. 2.5 MOD" /></label>
              <label>Cutter Type<input type="text" name="cutter_type" id="cutter_type" placeholder="e.g. Hobbing Cutter" /></label>
              <label>Lead Angle (degrees)<input type="number" name="lead_angle" id="lead_angle" step="0.01" placeholder="e.g. 15.50" /></label>
              <label>Hob/Cutter Pressure Angle (degrees)<input type="number" name="hob_pressure_angle" id="hob_pressure_angle" min="0" max="90" step="0.01" placeholder="e.g. 20" /></label>
              <label>Cutter I.D. (mm)<input type="number" name="cutter_inner_diameter" id="cutter_inner_diameter" min="0.01" step="0.01" placeholder="e.g. 32.00" /></label>
              <label>Cutter O.D. (mm)<input type="number" name="cutter_outer_diameter" id="cutter_outer_diameter" min="0.01" step="0.01" placeholder="e.g. 108.50" /></label>
              <label>Cutter Thickness / Length (mm)<input type="number" name="cutter_thickness" id="cutter_thickness" min="0.01" step="0.01" placeholder="e.g. 35.50" /></label>
              <label>Cutter Number of Teeth<input type="number" name="cutter_tooth_count" id="cutter_tooth_count" min="1" step="1" /></label>
              <label>Cutter Tooth Thickness (mm)<input type="number" name="cutter_tooth_thickness" id="cutter_tooth_thickness" min="0.01" step="0.01" /></label>
              <label>Purchase Date<input type="date" name="purchase_date" id="cutter_purchase_date" /></label>
              <label>Scrap Date<input type="date" name="scrap_date" id="cutter_scrap_date" /></label>
              <label>RPM / Stroke<input type="text" name="rpm_stroke" id="rpm_stroke" placeholder="e.g. 450 RPM or 30 Stroke" /></label>
              <label>Status<select name="status" id="cutter_status"><option value="Active">Active</option><option value="Not Active">Not Active</option></select></label>
              <label>Remarks<input type="text" name="remarks" id="cutter_remarks" placeholder="Optional remarks" /></label>
            </div>
            <div class="management-form-actions dialog-actions">
              <button type="button" class="secondary-button" onclick="cancelCutterEdit()">Cancel</button>
              <button type="submit" class="submit" id="saveCutterBtn">Save Cutter</button>
            </div>
          </form>
        </div>
      </dialog>

      <dialog id="cutterToolDialog" class="management-dialog wide-dialog" aria-labelledby="cutterToolTitle">
        <div class="dialog-shell">
          <header class="dialog-header">
            <div><h2 id="cutterToolTitle">Cutter Tool Record</h2><p id="cutterToolSubtitle"></p></div>
            <button type="button" class="dialog-close" aria-label="Close" onclick="document.getElementById('cutterToolDialog').close()">&times;</button>
          </header>
          <div id="cutterToolContent"></div>
        </div>
      </dialog>
