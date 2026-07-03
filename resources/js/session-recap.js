import { toPng } from 'html-to-image';

window.exportRecapCard = async function () {
    const card = document.getElementById('recap-card');
    const btn = document.getElementById('recap-share-btn');

    if (!card) return;

    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Preparazione…';
    }

    try {
        const dataUrl = await toPng(card, {
            pixelRatio: 2,
            cacheBust: true,
        });

        const blob = await fetch(dataUrl).then(r => r.blob());
        const file = new File([blob], 'iron-gym-allenamento.png', { type: 'image/png' });

        if (navigator.canShare?.({ files: [file] })) {
            await navigator.share({
                title: 'Il mio allenamento',
                text: 'Allenamento completato con Iron Gym',
                files: [file],
            });
        } else {
            const a = document.createElement('a');
            a.href = dataUrl;
            a.download = 'iron-gym-allenamento.png';
            a.click();
        }
    } catch {
        alert('Export non riuscito. Fai uno screenshot manuale.');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg> Condividi`;
        }
    }
};
