document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('deviceSearch');
  const suggestions = document.getElementById('suggestions');

  if (!searchInput || !suggestions) {
    return;
  }

  searchInput.addEventListener('input', function () {
    const query = this.value.trim();

    if (!query) {
      suggestions.classList.remove('show');
      suggestions.innerHTML = '';
      return;
    }

    fetch(`api.php?action=search&q=${encodeURIComponent(query)}`)
      .then(response => response.json())
      .then(data => {
        if (!data.success || !Array.isArray(data.results)) {
          suggestions.classList.remove('show');
          return;
        }

        if (!data.results.length) {
          suggestions.innerHTML = '<div class="suggestion-item">لا توجد نتائج</div>';
          suggestions.classList.add('show');
          return;
        }

        suggestions.innerHTML = data.results
          .map(item => {
            const label = `${item.brand} - ${item.model}`;
            return `<div class="suggestion-item" data-device-id="${item.id}">${label}</div>`;
          })
          .join('');

        suggestions.classList.add('show');

        suggestions.querySelectorAll('.suggestion-item').forEach(item => {
          item.addEventListener('click', function () {
            const deviceId = this.dataset.deviceId;
            const deviceName = this.textContent.trim();
            searchInput.value = deviceName;
            suggestions.classList.remove('show');
            window.location.href = `index.php?q=${encodeURIComponent(deviceName)}&device_id=${deviceId}`;
          });
        });
      })
      .catch(() => {
        suggestions.classList.remove('show');
      });
  });

  document.addEventListener('click', function (event) {
    if (!event.target.closest('#deviceSearch') && !event.target.closest('.suggestion-item')) {
      suggestions.classList.remove('show');
    }
  });
});
