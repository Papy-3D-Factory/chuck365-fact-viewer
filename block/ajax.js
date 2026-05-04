document.addEventListener('DOMContentLoaded', () => {
    const boxes = document.querySelectorAll('.cn-main-box');

    boxes.forEach(box => {
        const ajaxUrl = box.getAttribute('data-ajax-url');
        const nonce = box.getAttribute('data-nonce');
        const contentArea = box.querySelector('.cn-content-area');

        const refreshFact = async () => {
            const formData = new FormData();
            formData.append('action', 'chuck365_get_fact');
            formData.append('nonce', nonce);

            try {
                const response = await fetch(ajaxUrl, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.fact && contentArea) {
                    contentArea.style.opacity = '0';
                    setTimeout(() => {
                        contentArea.innerHTML = `<span class="cn-quote-mark">“</span>${data.fact}`;
                        contentArea.style.opacity = '1';
                    }, 200);
                }
            } catch (e) { console.error(e); }
        };
    });
});