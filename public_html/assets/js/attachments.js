// Secure attachment UI shared by jobs, parts and maintenance tickets.
    function attachmentSize(bytes) {
      const size = Number(bytes || 0);
      if (size < 1024) return `${size} B`;
      if (size < 1048576) return `${(size / 1024).toFixed(1)} KB`;
      return `${(size / 1048576).toFixed(1)} MB`;
    }

    function loadAttachmentList(entityType, entityId, listId, filters = {}, options = {}) {
      const list = document.getElementById(listId);
      if (!list || !entityId) return Promise.resolve([]);
      list.innerHTML = '<div class="empty">Loading attachments...</div>';
      const params = new URLSearchParams({ entity_type: entityType, entity_id: entityId });
      Object.entries(filters).forEach(([key, value]) => { if (value) params.set(key, value); });
      return fetch(`${API}/get_attachments.php?${params.toString()}`)
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Unable to load attachments.');
          list.innerHTML = result.data.length ? result.data.map(item => `<div class="attachment-row">
            <div><strong>${escapeHtml(item.category)}${item.drawing_no ? ` · Drawing No: ${escapeHtml(item.drawing_no)}` : ''}</strong><span>${item.component ? `${escapeHtml(item.component)} · ` : ''}${escapeHtml(item.original_name)} · ${attachmentSize(item.file_size)} · ${escapeHtml(item.uploaded_by || '-')} · ${escapeHtml(item.created_at)}</span></div>
            <div class="table-actions"><a class="table-action edit" href="${API}/download_attachment.php?id=${Number(item.id)}&view=1" target="_blank" rel="noopener">View</a><a class="table-action edit" href="${API}/download_attachment.php?id=${Number(item.id)}">Download</a>${options.allowDelete ? `<button type="button" class="table-action delete" data-delete-attachment="${Number(item.id)}">Delete</button>` : ''}</div>
          </div>`).join('') : '<div class="empty">No attachments uploaded.</div>';
          list.querySelectorAll('[data-delete-attachment]').forEach(button => button.addEventListener('click', () =>
            deleteCouplingDrawing(Number(button.dataset.deleteAttachment), entityType, entityId, listId, filters, options)));
          return result.data;
        })
        .catch(error => {
          list.innerHTML = `<div class="empty error-text">${escapeHtml(error.message)}</div>`;
          throw error;
        });
    }

    async function deleteCouplingDrawing(id, entityType, entityId, listId, filters, options) {
      const confirmed = await showConfirmDialog({
        title: 'Delete Drawing?',
        message: 'This drawing file will be permanently deleted.',
        confirmText: 'Delete Drawing',
        tone: 'danger'
      });
      if (!confirmed) return;
      fetch(`${API}/delete_attachment.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      }).then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Unable to delete drawing.');
          showToast('success', result.data.message);
          return loadAttachmentList(entityType, entityId, listId, filters, options);
        })
        .catch(error => showToast('error', error.message));
    }

    function handleAttachmentUpload(event, entityType, entityId, listId, filters = {}, options = {}) {
      event.preventDefault();
      const form = event.target;
      const button = form.querySelector('button[type="submit"]');
      const payload = new FormData(form);
      payload.set('entity_type', entityType);
      payload.set('entity_id', entityId);
      button.disabled = true;
      button.innerText = 'Uploading...';
      fetch(`${API}/upload_attachment.php`, { method: 'POST', body: payload })
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Upload failed.');
          form.reset();
          showToast('success', 'Attachment uploaded securely.');
          const listFilters = Object.keys(filters).length ? filters
            : (entityType === 'part' && payload.get('category') === 'Coupling Drawing' ? { category: 'Coupling Drawing' } : {});
          return loadAttachmentList(entityType, entityId, listId, listFilters, options);
        })
        .catch(error => showToast('error', error.message))
        .finally(() => { button.disabled = false; button.innerText = 'Upload'; });
    }
