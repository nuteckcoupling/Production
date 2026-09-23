// Extracted from index.html during Phase 2 frontend separation.
    function fetchData(url, selectId, optionMapper) {
      return fetch(url)
        .then(r => r.json())
        .then(data => {
          const select = document.getElementById(selectId);
          data.forEach(item => {
            select.insertAdjacentHTML('beforeend', optionMapper(item));
          });
        })
        .catch(err => console.error(`Error loading ${url}`, err));
    }

    function fetchParts() {
      return fetch(`${API}/get_parts.php`)
        .then(r => r.json())
        .then(data => { parts = data; })
        .catch(err => console.error('Error loading parts', err));
    }

    function refreshDailyCutters() {
      const select = document.getElementById('cutter_id');
      select.innerHTML = '<option value="">-- select --</option>';
      return fetchData(`${API}/get_cutters.php`, 'cutter_id', c => `<option value="${c.id}">${c.cutter_num}</option>`);
    }
