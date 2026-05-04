/**
 * papy3d-fact-viewer-for-chuck365 - Admin Settings (Vanilla JS Edition 2026)
 */
document.addEventListener('DOMContentLoaded', () => {

    const tabs = document.querySelectorAll('.nav-tab');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            const target = this.getAttribute('data-tab');

            tabs.forEach(t => t.classList.remove('nav-tab-active'));
            this.classList.add('nav-tab-active');

            contents.forEach(content => {
                if (content.id === `tab-content-${target}`) {
                    content.style.display = 'block';
                } else {
                    content.style.display = 'none';
                }
            });
            
            window.location.hash = target;
        });
    });

    const titleInput = document.getElementById('chuck365_text_title');
    const presets = document.querySelectorAll('.chuck-preset');
    const resetBtn = document.getElementById('chuck-reset');
    const directPreview = document.querySelector('.cn-main-box');

    const updatePreview = () => {
        if (!directPreview) return;

        const borderInput = document.getElementById('chuck365_border_color');
        const bgInput = document.getElementById('chuck365_bg_color');
        const colorInput = document.getElementById('chuck365_text_color');

        if (!borderInput || !bgInput || !colorInput) return;

        directPreview.style.setProperty('--chuck-border', borderInput.value);
        directPreview.style.setProperty('--chuck-bg', bgInput.value);
        directPreview.style.setProperty('--chuck-text', colorInput.value);
        
        const titleElement = directPreview.querySelector('.cn-title-text');
        if (titleElement) {
            titleElement.textContent = titleInput?.value || '';
        }
    };

    if (window.jQuery && jQuery.fn.wpColorPicker) {
        jQuery('.chuck-color-field').wpColorPicker({
            change: () => {
                setTimeout(updatePreview, 50);
            },
            clear: () => {
                setTimeout(updatePreview, 50);
            }
        });
    }

    titleInput?.addEventListener('input', updatePreview);

    presets.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const { b, bg, c } = e.currentTarget.dataset;
            
            if (window.jQuery) {
                jQuery('#chuck365_border_color').wpColorPicker('color', b);
                jQuery('#chuck365_bg_color').wpColorPicker('color', bg);
                jQuery('#chuck365_text_color').wpColorPicker('color', c);
            }
			
            setTimeout(updatePreview, 100);
        });
    });

    resetBtn?.addEventListener('click', () => {
        if (window.jQuery) {
            jQuery('#chuck365_border_color').wpColorPicker('color', '#f39c12');
            jQuery('#chuck365_bg_color').wpColorPicker('color', '#ffffff');
            jQuery('#chuck365_text_color').wpColorPicker('color', '#222222');
        }
        if (titleInput) {
            titleInput.value = 'Chuck Norris Fact du jour';
        }
        setTimeout(updatePreview, 100);
    });

    updatePreview();
});