(() => {
    const button = document.getElementById('download-id-image');
    if (!button) return;
    const status = document.getElementById('id-download-status');
    button.addEventListener('click', async () => {
        button.disabled = true;
        status.classList.remove('id-error');
        status.textContent = 'Preparing your image…';
        let source;
        let download;
        try {
            const response = await fetch(button.dataset.url, {credentials: 'same-origin', headers: {'Accept': 'image/svg+xml'}});
            if (!response.ok || !response.headers.get('Content-Type')?.includes('image/svg+xml')) throw new Error('Could not load the card');
            source = URL.createObjectURL(await response.blob());
            const image = new Image();
            image.src = source;
            await image.decode();
            const canvas = document.createElement('canvas');
            canvas.width = 1050;
            canvas.height = 660;
            canvas.getContext('2d').drawImage(image, 0, 0, 1050, 660);
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            if (!blob) throw new Error('Could not create the image');
            download = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = download;
            link.download = button.dataset.filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            status.textContent = 'Your PNG image is ready. Check your downloads.';
        } catch (error) {
            status.classList.add('id-error');
            status.textContent = 'The image could not be downloaded. Refresh this page and try again, or download the PDF.';
        } finally {
            if (source) URL.revokeObjectURL(source);
            if (download) setTimeout(() => URL.revokeObjectURL(download), 30000);
            button.disabled = false;
        }
    });
})();
