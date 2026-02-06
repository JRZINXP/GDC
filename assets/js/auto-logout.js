(function() {
    const INACTIVITY_LIMIT_MS = 5 * 60 * 1000;
    const WARNING_MS = 30 * 1000;
    const logoutUrl = '../../logout.php?logout=1';
    let timerId;
    let warningTimerId;
    let warningEl;

    const hideWarning = () => {
        if (warningEl) {
            warningEl.style.display = 'none';
        }
    };

    const showWarning = () => {
        if (!warningEl) {
            warningEl = document.createElement('div');
            warningEl.id = 'inactivity-warning';
            warningEl.style.position = 'fixed';
            warningEl.style.right = '20px';
            warningEl.style.bottom = '20px';
            warningEl.style.zIndex = '9999';
            warningEl.style.background = '#111827';
            warningEl.style.color = '#ffffff';
            warningEl.style.padding = '12px 16px';
            warningEl.style.borderRadius = '10px';
            warningEl.style.boxShadow = '0 8px 20px rgba(0,0,0,0.25)';
            warningEl.style.fontFamily = 'Poppins, Arial, sans-serif';
            warningEl.style.fontSize = '14px';
            warningEl.style.display = 'flex';
            warningEl.style.alignItems = 'center';
            warningEl.style.gap = '12px';
            warningEl.innerHTML =
                '<span>Voce sera desconectado por inatividade em 30 segundos.</span>' +
                '<button type="button" style="border:0;background:#22c55e;color:#0b1220;padding:6px 10px;border-radius:8px;cursor:pointer;font-weight:600;">Continuar logado</button>';
            document.body.appendChild(warningEl);

            const button = warningEl.querySelector('button');
            if (button) {
                button.addEventListener('click', () => {
                    resetTimer();
                });
            }
        }

        warningEl.style.display = 'flex';
    };

    const resetTimer = () => {
        if (timerId) {
            clearTimeout(timerId);
        }
        if (warningTimerId) {
            clearTimeout(warningTimerId);
        }

        hideWarning();

        const warnDelay = Math.max(0, INACTIVITY_LIMIT_MS - WARNING_MS);
        warningTimerId = setTimeout(() => {
            showWarning();
        }, warnDelay);

        timerId = setTimeout(() => {
            window.location.href = logoutUrl;
        }, INACTIVITY_LIMIT_MS);
    };

    ['click', 'mousemove', 'keydown', 'scroll', 'touchstart'].forEach((eventName) => {
        window.addEventListener(eventName, resetTimer, { passive: true });
    });

    resetTimer();
})();
