(() => {
  const field = document.getElementById('reset-token');
  if (!field) return;
  const readFragment = () => {
    if (!window.location.hash) return;
    const params = new URLSearchParams(window.location.hash.slice(1));
    const token = params.get('token');
    if (token && /^[a-f0-9]{64}$/.test(token)) field.value = token;
    window.history.replaceState(null, document.title, window.location.pathname + window.location.search);
  };
  readFragment();
  window.addEventListener('hashchange', readFragment);
})();
