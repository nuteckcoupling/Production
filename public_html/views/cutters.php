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
              <label>Hob Pressure Angle (degrees)<input type="number" name="hob_pressure_angle" id="hob_pressure_angle" min="0" max="90" step="0.01" placeholder="e.g. 20" /></label>
              <label>Cutter I.D. (mm)<input type="number" name="cutter_inner_diameter" id="cutter_inner_diameter" min="0.01" step="0.01" placeholder="e.g. 32.00" /></label>
              <label>Cutter O.D. (mm)<input type="number" name="cutter_outer_diameter" id="cutter_outer_diameter" min="0.01" step="0.01" placeholder="e.g. 108.50" /></label>
              <label>Cutter Thickness (mm)<input type="number" name="cutter_thickness" id="cutter_thickness" min="0.01" step="0.01" placeholder="e.g. 35.50" /></label>
              <label>No. of Teeth<input type="number" name="cutter_tooth_count" id="cutter_tooth_count" min="1" step="1" /></label>
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
    </section>
