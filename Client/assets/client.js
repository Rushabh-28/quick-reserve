// Keep date pickers consistent: check-out always after check-in.
(function () {
  function pad(n) { return String(n).padStart(2, '0'); }
  function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  document.querySelectorAll('form[data-dates]').forEach(function (f) {
    var a = f.querySelector('[name=in]'), b = f.querySelector('[name=out]');
    if (!a || !b) return;
    var today = iso(new Date());
    a.min = today;
    function sync() {
      var d = new Date(a.value + 'T00:00:00'); d.setDate(d.getDate() + 1);
      b.min = iso(d);
      if (!b.value || b.value <= a.value) b.value = iso(d);
    }
    a.addEventListener('change', sync); sync();
  });
  // Hold countdown on the payment page
  var t = document.querySelector('[data-expires]');
  if (t) {
    var end = parseInt(t.getAttribute('data-expires'), 10) * 1000;
    (function tick() {
      var s = Math.max(0, Math.round((end - Date.now()) / 1000));
      t.textContent = pad(Math.floor(s / 60)) + ':' + pad(s % 60);
      if (s === 0) { t.textContent = 'expired'; var b = document.querySelector('[data-pay-btn]'); if (b) b.disabled = true; return; }
      setTimeout(tick, 1000);
    })();
  }
  // Prevent double submits
  document.querySelectorAll('form[data-once]').forEach(function (f) {
    f.addEventListener('submit', function () {
      var b = f.querySelector('button[type=submit]'); if (b) { setTimeout(function () { b.disabled = true; b.textContent = 'Please wait…'; }, 0); }
    });
  });
})();
