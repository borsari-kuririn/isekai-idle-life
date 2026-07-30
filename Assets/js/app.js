(function () {
    const root = document.documentElement;
    const storageKey = 'isekai-time-phase';

    const updateTimeTheme = () => {
        const shell = document.getElementById('game-shell');
        const currentPhase = shell?.getAttribute('data-current-time-phase')
            || root.getAttribute('data-current-time-phase')
            || 'morning';
        const previousPhase = localStorage.getItem(storageKey);

        if (previousPhase && previousPhase !== currentPhase) {
            root.setAttribute('data-time-phase', previousPhase);
            requestAnimationFrame(() => {
                root.classList.add('time-shift');
                root.setAttribute('data-time-phase', currentPhase);
                setTimeout(() => {
                    root.classList.remove('time-shift');
                }, 1000);
            });
        } else {
            root.setAttribute('data-time-phase', currentPhase);
        }

        root.setAttribute('data-current-time-phase', currentPhase);
        localStorage.setItem(storageKey, currentPhase);
    };

    updateTimeTheme();
    document.addEventListener('htmx:afterSwap', updateTimeTheme);
    document.addEventListener('htmx:afterSettle', updateTimeTheme);
})();
