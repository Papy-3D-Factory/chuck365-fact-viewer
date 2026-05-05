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

    const titleInput = document.getElementById('papyfavi_text_title');
    const presets = document.querySelectorAll('.chuck-preset');
    const resetBtn = document.getElementById('chuck-reset');
    const directPreview = document.querySelector('.cn-main-box');

    const updatePreview = () => {
        if (!directPreview) return;

        const borderInput = document.getElementById('papyfavi_border_color');
        const bgInput = document.getElementById('papyfavi_bg_color');
        const colorInput = document.getElementById('papyfavi_text_color');

        if (!borderInput || !bgInput || !colorInput) return;

        directPreview.style.setProperty('--chuck-border', borderInput.value);
        directPreview.style.setProperty('--chuck-bg', bgInput.value);
        directPreview.style.setProperty('--chuck-text', colorInput.value);
        
        const titleElement = directPreview.querySelector('.cn-title-text');
        if (titleElement) {
            titleElement.textContent = titleInput?.value || '';
        }
    };
/*
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
*/
/* couleurs plus vives */
if (window.jQuery && jQuery.fn.wpColorPicker) {
    jQuery('.chuck-color-field').wpColorPicker({
        // On définit ici des couleurs ultra-vives
        palettes: ['#ff0000', '#ff8c00', '#ffea00', '#00ff00', '#00ffff', '#0000ff', '#ff00ff', '#000000'],
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
                jQuery('#papyfavi_border_color').wpColorPicker('color', b);
                jQuery('#papyfavi_bg_color').wpColorPicker('color', bg);
                jQuery('#papyfavi_text_color').wpColorPicker('color', c);
            }
			
            setTimeout(updatePreview, 100);
        });
    });

    resetBtn?.addEventListener('click', () => {
        if (window.jQuery) {
            jQuery('#papyfavi_border_color').wpColorPicker('color', '#f39c12');
            jQuery('#papyfavi_bg_color').wpColorPicker('color', '#ffffff');
            jQuery('#papyfavi_text_color').wpColorPicker('color', '#222222');
        }
        if (titleInput) {
            titleInput.value = 'Chuck Norris Fact du jour';
        }
        setTimeout(updatePreview, 100);
    });

    updatePreview();
});