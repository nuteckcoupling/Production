    <section id="couplingModule" class="hidden">
      <header class="module-header">
        <div>
          <h1>Coupling Management</h1>
          <p>Add, edit or safely delete coupling codes used in production.</p>
        </div>
      </header>

      <form class="card" id="couplingForm" onsubmit="handleCouplingSubmit(event)">
        <div id="couplingMessage" class="banner"></div>
        <h2 id="couplingFormTitle">Add Coupling</h2>
        <input type="hidden" name="id" id="coupling_edit_id" />
        <div class="grid management-grid compact-grid">
          <label>
            Coupling Range
            <select name="coupling_type" id="new_coupling_type" required>
              <option value="">-- select --</option>
              <option value="GC Gear Coupling">GC Gear Coupling</option>
              <option value="NA Gear Coupling">NA Gear Coupling</option>
              <option value="Roller Chain Coupling">Roller Chain Coupling</option>
              <option value="Gear">Gear</option>
              <option value="Sprocket">Sprocket</option>
            </select>
          </label>
          <label>
            Coupling Code / Part Name
            <input type="text" name="part_name" id="new_part_name" required placeholder="e.g. GC-119" />
          </label>
        </div>
        <div class="management-form-actions">
          <button type="submit" class="submit" id="saveCouplingBtn">Save Coupling</button>
          <button type="button" class="secondary-button hidden" id="cancelCouplingEditBtn" onclick="cancelCouplingEdit()">Cancel Edit</button>
        </div>
      </form>

      <div class="card list-card">
        <div class="list-heading">
          <h2 id="couplingListTitle">Existing Couplings</h2>
          <button type="button" class="secondary-button" id="toggleCouplingTrashBtn" onclick="toggleCouplingTrash()">Show Deleted Couplings</button>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Coupling Range</th><th>Coupling Code / Part Name</th><th>Actions</th></tr></thead>
            <tbody id="couplingsTableBody">
              <tr><td colspan="3" class="empty">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <section class="card attachment-card hidden" id="partAttachmentPanel">
        <div class="list-card-heading"><div><h2 id="partAttachmentTitle">Coupling Drawings</h2><span class="table-subtext">PDF/JPG/PNG, maximum 10 MB</span></div><button type="button" class="secondary-button" onclick="closePartAttachments()">Close</button></div>
        <div id="partAttachmentList" class="attachment-list"></div>
        <form id="partAttachmentForm" class="grid management-grid operator-only" enctype="multipart/form-data"
          onsubmit="handleAttachmentUpload(event, 'part', this.dataset.entityId, 'partAttachmentList')">
          <input type="hidden" name="category" value="Coupling Drawing" />
          <label>Component<select name="component" id="partDrawingComponent" required><option value="">-- select --</option></select></label>
          <label>Drawing Number<input type="text" name="drawing_no" maxlength="100" required placeholder="e.g. GC-100-HUB-01" /></label>
          <label>Drawing File<input type="file" name="attachment" accept="application/pdf,image/jpeg,image/png" required /></label>
          <div class="management-form-actions"><button type="submit" class="submit">Upload</button></div>
        </form>
      </section>
    </section>
