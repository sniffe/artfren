// Wird im <head> blockierend geladen, damit beim Seitenaufbau kein
// Aufblitzen des falschen Farbschemas entsteht.
(function () {
    try {
        var gespeichert = localStorage.getItem('theme');
        if (gespeichert === 'hell' || gespeichert === 'dunkel') {
            document.documentElement.setAttribute('data-theme', gespeichert);
        }
    } catch (e) {}
})();
