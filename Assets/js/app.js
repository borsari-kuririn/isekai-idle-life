(function () {
    const tabs = document.querySelectorAll('[data-tab]');
    const panels = document.querySelectorAll('[data-panel]');
    const sceneTabs = document.querySelectorAll('[data-scene-tab]');
    const scenePanels = document.querySelectorAll('[data-scene-panel]');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.getAttribute('data-tab');
            tabs.forEach((item) => item.classList.toggle('active', item === tab));
            panels.forEach((panel) => {
                panel.classList.toggle('active', panel.getAttribute('data-panel') === target);
            });
        });
    });

    sceneTabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.getAttribute('data-scene-tab');
            sceneTabs.forEach((item) => item.classList.toggle('active', item === tab));
            scenePanels.forEach((panel) => {
                panel.classList.toggle('active', panel.getAttribute('data-scene-panel') === target);
            });
        });
    });
})();
