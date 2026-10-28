// Forward to main food.js
if (typeof document !== 'undefined') {
    const s = document.createElement('script');
    s.src = new URL('../food.js', document.currentScript.src).href;
    document.head.appendChild(s);
}
