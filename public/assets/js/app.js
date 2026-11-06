document.addEventListener('DOMContentLoaded', function () {
  var searchInput = document.getElementById('deviceSearch');
  var suggestions = document.getElementById('suggestions');
  var timer = null;
  var lastQuery = '';

  if (!searchInput || !suggestions) return;

  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
    });
  }

  searchInput.addEventListener('input', function () {
    var query = this.value.trim();
    window.clearTimeout(timer);
    if (!query) {
      suggestions.classList.remove('show');
      suggestions.innerHTML = '';
      return;
    }

    timer = window.setTimeout(function () {
      lastQuery = query;
      fetch('api.php?action=search&q=' + encodeURIComponent(query), { headers: { Accept: 'application/json' } })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          if (query !== lastQuery || !data.success) return;
          if (!data.results.length) {
            suggestions.innerHTML = '<div class="suggestion-empty">لا توجد نتائج مطابقة</div>';
          } else {
            suggestions.innerHTML = data.results.map(function (item) {
              var label = item.brand + ' — ' + item.model;
              return '<a class="suggestion-item" role="option" href="index.php?q=' + encodeURIComponent(item.brand + ' ' + item.model) + '&device_id=' + encodeURIComponent(item.id) + '"><span class="device-icon">▣</span><span>' + escapeHtml(label) + '</span><span class="result-arrow">←</span></a>';
            }).join('');
          }
          suggestions.classList.add('show');
        })
        .catch(function () { suggestions.classList.remove('show'); });
    }, 180);
  });

  searchInput.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') suggestions.classList.remove('show');
    if (event.key === 'Enter' && suggestions.querySelector('.suggestion-item')) {
      event.preventDefault();
      suggestions.querySelector('.suggestion-item').click();
    }
  });

  document.addEventListener('click', function (event) {
    if (!event.target.closest('.search-box')) suggestions.classList.remove('show');
  });
});
