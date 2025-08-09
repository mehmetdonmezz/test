(function() {
  const storageKey = 'ardio-theme';
  const getPreferredTheme = () => {
    const stored = localStorage.getItem(storageKey);
    if (stored === 'light' || stored === 'dark') return stored;
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  };
  const setTheme = (theme) => {
    document.documentElement.setAttribute('data-bs-theme', theme);
    localStorage.setItem(storageKey, theme);
    const labelEls = document.querySelectorAll('[data-theme-label]');
    labelEls.forEach(el => { el.textContent = theme === 'dark' ? 'Aydınlık' : 'Karanlık'; });
  };
  window.toggleTheme = () => {
    const current = document.documentElement.getAttribute('data-bs-theme') || getPreferredTheme();
    setTheme(current === 'dark' ? 'light' : 'dark');
  };
  // init early
  setTheme(getPreferredTheme());
})();