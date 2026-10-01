    <section id="couplingModule" class="hidden">
      <header class="module-header coupling-page-header">
        <div>
          <h1>Coupling Management</h1>
          <p>Find a coupling, manage its drawings, or add a new code.</p>
        </div>
        <button type="button" class="submit coupling-add-button" onclick="openCouplingEditor()">+ Add Coupling</button>
      </header>

      <div id="couplingMessage" class="banner"></div>

      <section class="card coupling-toolbar" aria-label="Coupling filters">
        <label>
          Search Code
          <input type="search" id="couplingSearch" placeholder="e.g. GC-100" autocomplete="off" />
        </label>
        <label>
          Coupling Range
          <select id="couplingRangeFilter">
            <option value="">All Ranges</option>
            <option value="GC Gear Coupling">GC Gear Coupling</option>
            <option value="NA Gear Coupling">NA Gear Coupling</option>
            <option value="Roller Chain Coupling">Roller Chain Coupling</option>
            <option value="Gear">Gear</option>
            <option value="Sprocket">Sprocket</option>
          </select>
        </label>
        <div class="coupling-view-tabs" role="tablist" aria-label="Coupling status">
          <button type="button" id="activeCouplingTab" class="active" onclick="setCouplingView(false)">Active</button>
          <button type="button" id="deletedCouplingTab" onclick="setCouplingView(true)">Trash</button>
        </div>
        <span id="couplingResultCount" class="coupling-result-count"></span>
      </section>

      <div id="couplingCardList" class="coupling-card-grid" aria-live="polite">
        <div class="card empty">Loading couplings...</div>
      </div>

      <dialog id="couplingEditorDialog" class="management-dialog" aria-labelledby="couplingFormTitle">
        <div class="dialog-shell">
          <header class="dialog-header">
            <div><h2 id="couplingFormTitle">Add Coupling</h2><p>Enter the range and unique coupling code.</p></div>
            <button type="button" class="dialog-close" aria-label="Close" onclick="cancelCouplingEdit()">&times;</button>
          </header>
          <form id="couplingForm" onsubmit="handleCouplingSubmit(event)">
            <input type="hidden" name="id" id="coupling_edit_id" />
            <div class="grid compact-grid">
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
            <div class="management-form-actions dialog-actions">
              <button type="button" class="secondary-button" onclick="cancelCouplingEdit()">Cancel</button>
              <button type="submit" class="submit" id="saveCouplingBtn">Save Coupling</button>
            </div>
          </form>
        </div>
      </dialog>

      <dialog id="partAttachmentDialog" class="management-dialog wide-dialog" aria-labelledby="partAttachmentTitle">
        <section class="dialog-shell" id="partAttachmentPanel">
          <header class="dialog-header">
            <div><h2 id="partAttachmentTitle">Coupling Drawings</h2><p>PDF/JPG/PNG, maximum 10 MB</p></div>
            <button type="button" class="dialog-close" aria-label="Close" onclick="closePartAttachments()">&times;</button>
          </header>
          <div id="partAttachmentList" class="attachment-list"></div>
          <form id="partAttachmentForm" class="grid management-grid operator-only coupling-drawing-form" enctype="multipart/form-data"
            onsubmit="handleAttachmentUpload(event, 'part', this.dataset.entityId, 'partAttachmentList', { category: 'Coupling Drawing' }, { allowDelete: true })">
            <input type="hidden" name="category" value="Coupling Drawing" />
            <label>Component<select name="component" id="partDrawingComponent" required><option value="">-- select --</option></select></label>
            <label>Drawing Number<input type="text" name="drawing_no" maxlength="100" required placeholder="e.g. GC-100-HUB-01" /></label>
            <label>Drawing File<input type="file" name="attachment" accept="application/pdf,image/jpeg,image/png" required /></label>
            <div class="management-form-actions"><button type="submit" class="submit">Upload Drawing</button></div>
          </form>
        </section>
      </dialog>
    </section>
