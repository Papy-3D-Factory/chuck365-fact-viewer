<?php
/**
 * Plugin Name: Chuck365 Fact Viewer
 * Plugin URI: https://chuck365.fr
 * Description: Un plugin pour afficher chaque jour une anecdote unique et différente sur Chuck Norris.
 * Version: 2.0.2
 * Author: Papy 3D Factory
 * Text Domain: chuck365-fact-viewer
 * License: GPLv3
 */

declare(strict_types=1);

if (!defined('ABSPATH')) exit;

$Chuck365_Fact_Viewer_version = '2.0.2';
if (defined('WP_DEBUG') && WP_DEBUG) {
    $Chuck365_Fact_Viewer_version .= '.' . filemtime(plugin_dir_path(__FILE__) . 'js/admin-settings.js');
}

define('CHUCK365_VERSION', $c365_version);

class Chuck365_Fact_Viewer_Plugin {

    public function __construct() {
        add_action('init', [$this, 'i18n']);
        add_action('init', [$this, 'register_block_modern']);
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'settings']);
        add_shortcode('chuck_fact', [$this, 'shortcode_render']);
        add_action('wp_ajax_chuck365_get_fact', [$this, 'ajax_fact']);
        add_action('wp_ajax_nopriv_chuck365_get_fact', [$this, 'ajax_fact']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
    }

    public function i18n(): void {
		// WordPress gère désormais l'i18n automatiquement via le Header "Text Domain"
        //load_plugin_textdomain('chuck365-fact-viewer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function register_block_modern(): void {
		wp_register_script(
			'chuck365-editor-script',
			plugins_url('block/edit.js', __FILE__),
			array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'),
			CHUCK365_VERSION,
			true // Ajout du paramètre $in_footer à true
		);

		// Pour le style, application de votre règle de versioning automatique
		wp_register_style(
			'chuck365-style', 
			plugins_url('css/style.css', __FILE__), 
			[], 
			(string)filemtime(__DIR__ . '/css/style.css') // Utilisation du fichier réel pour le timestamp
		);

		register_block_type_from_metadata(__DIR__ . '/block', [
			'render_callback' => [$this, 'render'],
		]);   
	}

    public function admin_assets($hook): void {
        if ($hook !== 'toplevel_page_chuck365') return;
        wp_enqueue_style('chuck365-admin-css', plugins_url('css/admin.css', __FILE__), [], CHUCK365_VERSION);
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('chuck365-admin-js', plugins_url('js/admin-settings.js', __FILE__), ['wp-color-picker'], CHUCK365_VERSION, true);
    }

    public function menu(): void {
        add_menu_page('Chuck365', 'Chuck365', 'manage_options', 'chuck365', [$this, 'admin_page'], 'dashicons-superhero');
    }

    public function settings(): void {
        $args = ['sanitize_callback' => 'sanitize_hex_color'];
        register_setting('chuck365-group', 'chuck365_border_color', $args);
        register_setting('chuck365-group', 'chuck365_bg_color', $args);
        register_setting('chuck365-group', 'chuck365_text_color', $args);
        register_setting('chuck365-group', 'chuck365_text_title', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('chuck365-group', 'chuck365_show_copyright', ['type' => 'boolean', 'default' => true]);
    }

    public function admin_page(): void {
        if (!current_user_can('manage_options')) return;
        $icon_url = plugins_url('images/chuck.png', __FILE__);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline cn-title-container">
                <img src="<?php echo esc_url($icon_url); ?>" alt="Chuck Icon" class="cn-custom-icon">
                <span><?php esc_html_e('Configuration de Chuck365', 'chuck365-fact-viewer'); ?></span>
            </h1>

            <nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="#tab-settings" class="nav-tab nav-tab-active" data-tab="settings"><?php esc_html_e('Réglages & Aperçu', 'chuck365-fact-viewer'); ?></a>
                <a href="#tab-help" class="nav-tab" data-tab="help"><?php esc_html_e('Utilisation', 'chuck365-fact-viewer'); ?></a>
                <a href="#tab-project" class="nav-tab" data-tab="project"><?php esc_html_e('Soutenir Chuck365', 'chuck365-fact-viewer'); ?></a>
                <a href="#tab-about" class="nav-tab" data-tab="about"><?php esc_html_e('À propos', 'chuck365-fact-viewer'); ?></a>
            </nav>

            <div id="tab-content-settings" class="tab-content">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-1"> 
                        <div id="post-body-content">
                            <form method="post" action="options.php">
                                <?php settings_fields('chuck365-group'); do_settings_sections('chuck365-group'); ?>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Bordure / Titre', 'chuck365-fact-viewer'); ?></th>
                                        <td><input type="text" name="chuck365_border_color" id="chuck365_border_color" value="<?php echo esc_attr(get_option('chuck365_border_color', '#f39c12')); ?>" class="chuck-color-field" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Fond', 'chuck365-fact-viewer'); ?></th>
                                        <td><input type="text" name="chuck365_bg_color" id="chuck365_bg_color" value="<?php echo esc_attr(get_option('chuck365_bg_color', '#ffffff')); ?>" class="chuck-color-field" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Texte', 'chuck365-fact-viewer'); ?></th>
                                        <td><input type="text" name="chuck365_text_color" id="chuck365_text_color" value="<?php echo esc_attr(get_option('chuck365_text_color', '#222222')); ?>" class="chuck-color-field" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Titre', 'chuck365-fact-viewer'); ?></th>
                                        <td><input type="text" name="chuck365_text_title" id="chuck365_text_title" value="<?php echo esc_attr(get_option('chuck365_text_title', 'Chuck Norris Fact du jour')); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Afficher le Copyright', 'chuck365-fact-viewer'); ?></th>
                                        <td>
                                            <input type="checkbox" name="chuck365_show_copyright" id="chuck365_show_copyright" value="1" <?php checked(1, get_option('chuck365_show_copyright', 1)); ?> />
                                            <label for="chuck365_show_copyright"><?php esc_html_e('Afficher la barre de copyright et le lien Chuck365', 'chuck365-fact-viewer'); ?></label>
                                        </td>
                                    </tr>
                                </table>

                                <p>
                                    <strong><?php esc_html_e('Presets de style :', 'chuck365-fact-viewer'); ?></strong><br><br>
                                    <button type="button" class="button chuck-preset" data-b="#f39c12" data-bg="#ffffff" data-c="#222222">🔸 Original</button>
                                    <button type="button" class="button chuck-preset" data-b="#e74c3c" data-bg="#fff5f5" data-c="#c0392b">🔥 Fire</button>
                                    <button type="button" class="button chuck-preset" data-b="#111111" data-bg="#1e1e1e" data-c="#eeeeee">🌙 Dark</button>
                                    <button type="button" class="button chuck-preset" data-b="#3498db" data-bg="#ebf5fb" data-c="#21618c">🌊 Ocean</button>
                                    <button type="button" class="button chuck-preset" data-b="#27ae60" data-bg="#f1f9f5" data-c="#145a32">🌲 Forest</button>
                                    <button type="button" class="button chuck-preset" data-b="#d4af37" data-bg="#fffdf5" data-c="#996515">🏆 Gold</button>
                                    <button type="button" class="button chuck-preset" data-b="#bdc3c7" data-bg="#f8f9f9" data-c="#2c3e50">🥈 Argent</button>
                                    <button type="button" class="button" id="chuck-reset">↺ Reset</button>
                                </p>
                                <?php submit_button(); ?>
                            </form>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 30px;">
                    <hr>
                    <h2><?php esc_html_e('Aperçu', 'chuck365-fact-viewer'); ?></h2>
                    <?php echo wp_kses_post($this->render()); ?>
                </div>
            </div>
            <div id="tab-content-help" class="tab-content" style="display:none;">
                <div class="card" style="max-width: 1000px; padding: 20px; line-height: 1.6;">
                    <h2>📖 <?php esc_html_e('Comment afficher les Chuck Norris Facts ?', 'chuck365-fact-viewer'); ?></h2>
                    <p><?php esc_html_e('Vous avez deux méthodes simples pour intégrer la puissance de Chuck sur votre site.', 'chuck365-fact-viewer'); ?></p>

                    <div style="margin-bottom: 25px;">
                        <h3>1. <?php esc_html_e('Via l\'éditeur Gutenberg (Recommandé)', 'chuck365-fact-viewer'); ?></h3>
                        <p><?php esc_html_e('Recherchez simplement le bloc nommé <strong>"Chuck365 Fact"</strong> dans l\'éditeur de vos pages ou articles. Vous pourrez voir l\'aperçu en direct.', 'chuck365-fact-viewer'); ?></p>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <h3>2. <?php esc_html_e('Via le Shortcode', 'chuck365-fact-viewer'); ?></h3>
                        <p><?php esc_html_e('Copiez et collez le code suivant là où vous souhaitez afficher le fait (Widget texte, constructeur de page, etc.) :', 'chuck365-fact-viewer'); ?></p>
                        <code>[chuck_fact]</code>
                    </div>

                    <hr>

                    <h3>💡 <?php esc_html_e('Conseils d\'optimisation', 'chuck365-fact-viewer'); ?></h3>
                    <ul>
                        <li><strong><?php esc_html_e('Mise en cache :', 'chuck365-fact-viewer'); ?></strong> <?php esc_html_e('Le plugin utilise un système de "Transients". Le fait est récupéré une seule fois par jour et stocké localement pour garantir une vitesse de chargement optimale.', 'chuck365-fact-viewer'); ?></li>
                        <li><strong><?php esc_html_e('Design cohérent :', 'chuck365-fact-viewer'); ?></strong> <?php esc_html_e('Utilisez les presets dans l\'onglet "Réglages" pour adapter rapidement le widget à la charte graphique de votre thème.', 'chuck365-fact-viewer'); ?></li>
                    </ul>

                    <div style="background: #fff8e5; padding: 15px; border-left: 4px solid #ffb900; margin-top: 20px;">
                        <p style="margin: 0;">⚠️ <strong><?php esc_html_e('Note technique :', 'chuck365-fact-viewer'); ?></strong> <?php esc_html_e('Si vous changez les couleurs et que vous utilisez un plugin de cache (WP Rocket, Autoptimize), pensez à vider le cache pour voir les modifications immédiatement sur votre site.', 'chuck365-fact-viewer'); ?></p>
                    </div>
                </div>
            </div>
            <div id="tab-content-project" class="tab-content" style="display:none;">
                <div class="card" style="max-width: 1000px; padding: 30px; line-height: 1.6;">
                    <h2 style="color: #d63638;">🚀 <?php esc_html_e('Pourquoi soutenir le projet Chuck365.fr ?', 'chuck365-fact-viewer'); ?></h2>
                    <p><?php esc_html_e('Soutenir Chuck365, ce n\'est pas seulement financer un serveur, c\'est participer à la diffusion d\'une institution du web. Voici pourquoi votre contribution est essentielle :', 'chuck365-fact-viewer'); ?></p>
                    
                    <ol>
                        <li><strong>Maintenir une infrastructure "Punchy" :</strong> <?php esc_html_e('Derrière chaque "Fact", une API tourne 24h/24. Votre soutien aide à couvrir les frais d\'hébergement.', 'chuck365-fact-viewer'); ?></li>
                        <li><strong>Un projet Indépendant et Sans Pub :</strong> <?php esc_html_e('Nous refusons de polluer l\'interface. Ce modèle repose sur la bienveillance de la communauté.', 'chuck365-fact-viewer'); ?></li>
                        <li><strong>Booster le développement futur :</strong> <?php esc_html_e('Amélioration de l\'algorithme, nouvelles intégrations et maintenance de la compatibilité WordPress.', 'chuck365-fact-viewer'); ?></li>
                        <li><strong>Offrir un café à la forge :</strong> <?php esc_html_e('Développer selon les standards de 2026 demande du temps. Vous valorisez ainsi le travail de Papy 3D Factory.', 'chuck365-fact-viewer'); ?></li>
                    </ol>

                    <div style="background: #f0f0f1; padding: 20px; border-left: 4px solid #d63638; margin: 20px 0;">
                        <strong><?php esc_html_e('Le saviez-vous ?', 'chuck365-fact-viewer'); ?></strong><br>
                        <?php esc_html_e('Chuck Norris ne dort jamais. Il attend. Mais en attendant, il boit du café. Offrez-lui-en un pour qu\'il continue de veiller sur votre site web !', 'chuck365-fact-viewer'); ?>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                            <input type="hidden" name="cmd" value="_donations">
                            <input type="hidden" name="business" value="contact@papy-3d-factory.xyz">
                            <input type="number" name="amount" value="5" min="1" class="chuck-amount-input"> <span class="chuck-currency-symbol">€</span><br><br>
                            <button type="submit" class="chuck-coffee-button" style="padding: 0; height: auto;">
                                <img src="<?php echo esc_url(plugins_url('images/buymeacoffeebutton.png', __FILE__)); ?>" alt="<?php echo esc_attr__('Soutenir', 'chuck365-fact-viewer'); ?>">
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div id="tab-content-about" class="tab-content" style="display:none;">
                <div class="card" style="max-width: 1000px; padding: 20px; line-height: 1.6;">
                    <h2>🚀 <?php esc_html_e('Le Projet Chuck365.fr', 'chuck365-fact-viewer'); ?></h2>
                    <p><?php esc_html_e('Voici une présentation du projet Chuck365, une initiative dédiée à la force brute et à l\'humour légendaire de Chuck Norris, conçue pour les développeurs et les administrateurs de sites web.', 'chuck365-fact-viewer'); ?></p>
                    
                    <p><?php esc_html_e('Chuck365.fr est une plateforme dont l\'unique mission est de propager la sagesse (parfois percutante) de Chuck Norris à travers le web. Le concept est simple mais implacable : offrir chaque jour un fait unique et aléatoire sur l\'homme qui a fait pleurer un oignon.', 'chuck365-fact-viewer'); ?></p>
                    
                    <p><?php esc_html_e('Contrairement aux bases de données statiques, Chuck365 mise sur la fraîcheur de son contenu pour garantir que vos utilisateurs ne lisent jamais deux fois la même vérité universelle dans un intervalle réduit.', 'chuck365-fact-viewer'); ?></p>

                    <h3>🔌 <?php esc_html_e('Une API Puissante et Légère', 'chuck365-fact-viewer'); ?></h3>
                    <p><?php esc_html_e('Au cœur du projet se trouve une API REST robuste conçue pour une intégration universelle.', 'chuck365-fact-viewer'); ?></p>
                    <ul>
                        <li><strong><?php esc_html_e('Unique et Quotidien :', 'chuck365-fact-viewer'); ?></strong> <?php esc_html_e('L\'API est optimisée pour fournir un "Fact du jour" cohérent pour tous les utilisateurs.', 'chuck365-fact-viewer'); ?></li>
                        <li><strong><?php esc_html_e('Format JSON :', 'chuck365-fact-viewer'); ?></strong> <?php esc_html_e('Les réponses sont livrées en JSON, permettant une manipulation facile en JavaScript, PHP, Python ou tout autre langage moderne.', 'chuck365-fact-viewer'); ?></li>
                        <li><strong><?php esc_html_e('Haute Disponibilité :', 'chuck365-fact-viewer'); ?></strong> <?php esc_html_e('Hébergée pour répondre plus vite qu\'un high-kick, l\'API supporte des requêtes provenant de divers environnements sans latence perceptible.', 'chuck365-fact-viewer'); ?></li>
                    </ul>

                    <h3>📦 <?php esc_html_e('Le Plugin Chuck365 Fact Viewer', 'chuck365-fact-viewer'); ?></h3>
                    <p><?php esc_html_e('Pour les utilisateurs de WordPress, le plugin Chuck365 Fact Viewer (actuellement en version 2.0.1) agit comme le pont direct entre la puissance de l\'API et votre interface utilisateur.', 'chuck365-fact-viewer'); ?></p>
                    
                    <h4><?php esc_html_e('Caractéristiques Principales :', 'chuck365-fact-viewer'); ?></h4>
                    <ul>
                        <li><strong><?php esc_html_e('Affichage Dynamique :', 'chuck365-fact-viewer'); ?></strong> <?php esc_html_e('Le plugin récupère automatiquement les faits via l\'API officielle.', 'chuck365-fact-viewer'); ?></li>
                        <li><strong><?php esc_html_e('Performance Optimisée :', 'chuck365-fact-viewer'); ?></strong> <?php esc_html_e('Utilisation d\'un système de mise en cache (transients) qui stocke le fait jusqu\'au lendemain.', 'chuck365-fact-viewer'); ?></li>
                        <li><strong><?php esc_html_e('Personnalisation Totale :', 'chuck365-fact-viewer'); ?></strong> <?php esc_html_e('Interface d\'administration dédiée pour modifier les couleurs et titres.', 'chuck365-fact-viewer'); ?></li>
                        <li><strong><?php esc_html_e('Gutenberg & Shortcode :', 'chuck365-fact-viewer'); ?></strong> <?php esc_html_e('Compatible avec l\'éditeur moderne et utilisable via [chuck_fact].', 'chuck365-fact-viewer'); ?></li>
                        <li><strong><?php esc_html_e('Sécurité Native :', 'chuck365-fact-viewer'); ?></strong> <?php esc_html_e('Développé avec des standards stricts (PHP 8.5+, protection CSRF, assainissement des données).', 'chuck365-fact-viewer'); ?></li>
                    </ul>

                    <h4><?php esc_html_e('Pourquoi l\'utiliser ?', 'chuck365-fact-viewer'); ?></h4>
                    <p><?php esc_html_e('Ajouter une touche d\'humour à un tableau de bord ou à une barre latérale de blog n\'a jamais été aussi simple. Le projet Chuck365 prouve que même la technologie la plus sérieuse peut avoir un crochet du droit redoutable... et très drôle.', 'chuck365-fact-viewer'); ?></p>

                    <p><em><?php esc_html_e('Note : Chuck Norris n\'utilise pas d\'API. Les données se déplacent par peur de le contrarier.', 'chuck365-fact-viewer'); ?></em></p>
                    <hr>
                    <p>
   						 <strong>Version :</strong> <?php echo esc_html(CHUCK365_VERSION); ?> | 
   						 <strong>Auteur :</strong> <?php echo esc_html__('Papy 3D Factory', 'chuck365-fact-viewer'); ?>
					</p>
                </div>
            </div>

            <div class="copyright" style="text-align: center; margin-top: 40px; color: #666;">
                <p>© <?php echo esc_html(gmdate('Y')); ?> — Créé avec <span style="color:red;">❤</span> par <a href="https://papy-3d-factory.xyz" target="_blank">Papy 3D Factory</a></p>
            </div>
        </div>
        <?php
    }

    public function get_fact(): string {
        $cached = get_transient('chuck365_fact');
        if (is_string($cached)) return $cached;
        $response = wp_remote_get('https://chuck365.fr/api.php', ['timeout' => 10, 'user-agent' => 'Chuck365-Viewer/2.0.1; ' . home_url()]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return (string)__('Chuck est occupé.', 'chuck365-fact-viewer');
        $body = wp_remote_retrieve_body($response);
        try {
            $data = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
            if (isset($data->success, $data->fact) && $data->success === true) {
                $fact = sanitize_text_field((string)$data->fact);
                set_transient('chuck365_fact', $fact, max(HOUR_IN_SECONDS, strtotime('tomorrow') - time()));
                return $fact;
            }
        } catch (\JsonException $e) { return (string)__('Chuck se repose.', 'chuck365-fact-viewer'); }
        return (string)__('Chuck se repose.', 'chuck365-fact-viewer');
    }

    public function ajax_fact(): void {
        check_ajax_referer('chuck365_ajax_action', 'nonce');
        wp_send_json(['fact' => $this->get_fact()]);
    }

    public function shortcode_render($atts): string {
        wp_enqueue_style('chuck365-style');
        $custom_css = ".cn-main-box { --chuck-border: ".esc_attr(get_option('chuck365_border_color','#f39c12'))."; --chuck-bg: ".esc_attr(get_option('chuck365_bg_color','#ffffff'))."; --chuck-text: ".esc_attr(get_option('chuck365_text_color','#222222'))."; }";
        wp_add_inline_style('chuck365-style', $custom_css);
        return $this->render();
    }

    public function render(array $attributes = []): string {
        $border = (string)($attributes['borderColor'] ?? get_option('chuck365_border_color', '#f39c12'));
        $bg = (string)($attributes['bgColor'] ?? get_option('chuck365_bg_color', '#ffffff'));
        $color = (string)($attributes['textColor'] ?? get_option('chuck365_text_color', '#222222'));
        $title = (string)($attributes['title'] ?? get_option('chuck365_text_title', 'Chuck Norris Fact du jour'));
        
        // Priorité à l'attribut du bloc, sinon on prend l'option générale de l'admin
        $showCopyright = isset($attributes['showCopyright']) ? (bool)$attributes['showCopyright'] : (bool)get_option('chuck365_show_copyright', true);

        $icon_url = plugins_url('images/chuck.png', __FILE__);
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('chuck365_ajax_action');
        $fact = $this->get_fact();
        $safe_fact = force_balance_tags(wp_kses_post($fact));
        $unique_id = 'chuck-title-' . bin2hex(random_bytes(4));

        ob_start(); ?>
        <div class="cn-main-box" data-ajax-url="<?php echo esc_url($ajax_url); ?>" data-nonce="<?php echo esc_attr($nonce); ?>" style="--chuck-border:<?php echo esc_attr($border); ?>; --chuck-bg:<?php echo esc_attr($bg); ?>; --chuck-text:<?php echo esc_attr($color); ?>;">
            <div class="cn-top-label">
                <div class="cn-icon-container">
					<img src="<?php echo esc_url($icon_url); ?>" 
						 alt="<?php echo esc_attr__('Chuck Icon', 'chuck365-fact-viewer'); ?>" 
						 class="cn-custom-icon img-fluid">
				</div>
                <span class="cn-title-text" id="<?php echo esc_attr($unique_id); ?>"><?php echo esc_html($title); ?></span>
            </div>
            <div class="cn-content-area">
				<blockquote class="d-inline m-0">
					<?php echo wp_kses_post($safe_fact); ?>
				</blockquote>
			</div>
            
            <?php if ($showCopyright) : ?>
                <div class="cn-bottom-bar">
                    <div class="cn-copy-wrapper"><span class="cn-copy-info">© <?php echo esc_html(gmdate('Y')); ?> — Chuck365</span></div>
                    <a href="https://chuck365.fr" target="_blank" rel="noopener noreferrer" class="cn-link-btn"><?php echo esc_html__('Visiter le site', 'chuck365-fact-viewer'); ?></a>
                </div>
            <?php endif; ?>
        </div>
        <?php return ob_get_clean() ?: '';
    }
} 
add_action('plugins_loaded', function() { new Chuck365_Fact_Viewer_Plugin(); });