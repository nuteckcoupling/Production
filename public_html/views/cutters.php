    <section id="cutterModule" class="hidden">
      <header class="module-header">
        <div>
          <h1>Cutter Data</h1>
          <p>Add cutter details for use in Daily Entry.</p>
        </div>
      </header>

      <form class="card" id="cutterForm" onsubmit="handleCutterSubmit(event)">
        <div id="cutterMessage" class="banner"></div>
        <div class="grid management-grid">
          <label>
            Cutter Number / Module
            <input type="text" name="cutter_num" id="cutter_num" required placeholder="e.g. CUT-101" />
        <h2 id="cutterFormTitle">Add Cutter</h2>
        <input type="hidden" name="id" id="cutter_edit_id" />
          </label>
          <label>
            Cutter Type
            <input type="text" name="cutter_type" id="cutter_type" placeholder="e.g. Hobbing Cutter" />
          </label>
          <label>
            Lead Angle (degrees)
            <input type="number" name="lead_angle" id="lead_angle" step="0.01" placeholder="e.g. 15.50" />
          </label>
          <label>
            RPM / Stroke
            <input type="text" name="rpm_stroke" id="rpm_stroke" placeholder="e.g. 450 RPM or 30 Stroke" />
          </label>
          <label>
            Status
            <select name="status" id="cutter_status">
              <option value="Active">Active</option>
              <option value="Not Active">Not Active</option>
            </select>
          </label>
          <label>
            Remarks
            <input type="text" name="remarks" id="cutter_remarks" placeholder="Optional remarks" />
          </label>
        </div>
        <div class="management-form-actions">
          <button type="submit" class="submit" id="saveCutterBtn">Save Cutter</button>
          <button type="button" class="secondary-button hidden" id="cancelCutterEditBtn" onclick="cancelCutterEdit()">Cancel Edit</button>
        </div>
      </form>

      <div class="card list-card">
        <div class="list-heading">
          <h2 id="cutterListTitle">Existing Cutters</h2>
          <button type="button" class="secondary-button" id="toggleCutterTrashBtn" onclick="toggleCutterTrash()">Show Deleted Cutters</button>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Cutter Number / Module</th><th>Cutter Type</th><th>Lead Angle</th><th>RPM / Stroke</th><th>Status</th><th>Remarks</th><th>Actions</th></tr></thead>
            <tbody id="cuttersTableBody">
              <tr><td colspan="7" class="empty">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
