// Secure attachment UI shared by jobs, parts and maintenance tickets.
    function attachmentSize(bytes) {
      const size = Number(bytes || 0);
      if (size < 1024) return `${size} B`;
      if (size < 1048576) return `${(size / 1024).toFixed(1)} KB`;
      return `${(size / 1048576).toFixed(1)} MB`;
    }

    function loadAttachmentList(entityType, entityId, listId, filters = {}) {
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
            <a class="table-action edit" href="${API}/download_attachment.php?id=${Number(item.id)}">Download</a>
          </div>`).join('') : '<div class="empty">No attachments uploaded.</div>';
          return result.data;
        })
        .catch(error => {
          list.innerHTML = `<div class="empty error-text">${escapeHtml(error.message)}</div>`;
          throw error;
        });
    }

    function handleAttachmentUpload(event, entityType, entityId, listId) {
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
          const filters = entityType === 'part' && payload.get('category') === 'Coupling Drawing'
            ? { category: 'Coupling Drawing' } : {};
          return loadAttachmentList(entityType, entityId, listId, filters);
        })
        .catch(error => showToast('error', error.message))
        .finally(() => { button.disabled = false; button.innerText = 'Upload'; });
    }
