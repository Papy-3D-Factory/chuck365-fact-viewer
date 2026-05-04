<?php
/**
 * Plugin Name: Papy3D Fact Viewer for Chuck365
 * Plugin URI: https://papy-3d-factory.xyz
 * Description: Displays a unique and different Chuck Norris fact every day via the official Chuck365.fr API.
 * Version: 2.0.4
 * Author: papy3d
 * Text Domain: papy3d-fact-viewer-for-chuck365
 * License: GPLv3
 */



// Protection contre l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Définition directe de la constante SANS variable globale
if (!defined('PAPY3D_FACT_VIEWER_FOR_CHUCK365_VERSION')) {
    define(
        'PAPY3D_FACT_VIEWER_FOR_CHUCK365_VERSION',
        '2.0.4' . (defined('WP_DEBUG') && WP_DEBUG ? '.' . filemtime(__DIR__ . '/js/admin-settings.js') : '')
    );
}

/**
 * Main plugin class.
 *
 * Handles:
 * - Block registration
 * - Admin UI
 * - AJAX endpoints
 * - Shortcode rendering
 * - API communication (Chuck365)
 *
 * @since 2.0.0
 */
class Papy3D_Fact_Viewer_For_Chuck365_Plugin {

    /**
     * Constructor.
     *
     * Registers WordPress hooks.
     *
     * @since 2.0.0
     */
    public function __construct() {
        add_action('init', [$this, 'i18n']);
        add_action('init', [$this, 'register_block_modern']);
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'settings']);
        add_shortcode('chuck_fact', [$this, 'shortcode_render']);
        add_action('wp_ajax_chuck365_get_fact', [$this, 'ajax_fact']);
        add_action('wp_ajax_nopriv_chuck365_get_fact', [$this, 'ajax_fact']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_defaults']);
    }

    /**
     * Initialize translations.
     *
     * @since 2.0.0
     * @return void
     */
    public function i18n(): void {
        // WordPress gère désormais l'i18n automatiquement via le Header "Text Domain"
        // load_plugin_textdomain('papy3d-fact-viewer-for-chuck365', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

	/**
     * Register Gutenberg block and related assets.
     *
     * @since 2.0.0
     * @return void
     */
    public function register_block_modern(): void {
        wp_register_style(
            'chuck365-style',
            plugins_url('css/style.css', __FILE__),
            [],
			file_exists(__DIR__ . '/css/style.css') ? (string) filemtime(__DIR__ . '/css/style.css') : null 
		);

        wp_register_script(
            'chuck365-editor-script',
            plugins_url('block/edit.js', __FILE__),
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'),
            PAPY3D_FACT_VIEWER_FOR_CHUCK365_VERSION,
            true
        );

        register_block_type_from_metadata(__DIR__ . '/block', [
            'render_callback' => [$this, 'render'],
        ]);

        if (!is_admin()) {
            wp_enqueue_script(
                'chuck365-view-script',
                plugins_url('block/ajax.js', __FILE__),
                [],
                PAPY3D_FACT_VIEWER_FOR_CHUCK365_VERSION,
                true
            );
        }
    }

    /**
     * Inject default settings into block editor.
     *
     * @since 2.0.0
     * @return void
     */
    public function enqueue_editor_defaults(): void {
		wp_enqueue_script('chuck365-editor-script');

		wp_localize_script(
			'chuck365-editor-script',
			'chuck365Defaults',
			[
				'borderColor' => sanitize_hex_color(get_option('chuck365_border_color', '#f39c12')),
				'bgColor'     => sanitize_hex_color(get_option('chuck365_bg_color', '#ffffff')),
				'textColor'   => sanitize_hex_color(get_option('chuck365_text_color', '#222222')),
				'title'       => sanitize_text_field(get_option('chuck365_text_title', 'Chuck Fact')),
			]
		);
	}

    /**
     * Enqueue admin scripts and styles.
     *
     * @since 2.0.0
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function admin_assets($hook): void {
		if ($hook !== 'toplevel_page_chuck365' || !current_user_can('manage_options')) {
			return;
		}
		wp_enqueue_style('chuck365-admin-css', plugins_url('css/admin.css', __FILE__), [], PAPY3D_FACT_VIEWER_FOR_CHUCK365_VERSION);
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker');
		wp_enqueue_script('chuck365-admin-js', plugins_url('js/admin-settings.js', __FILE__), ['wp-color-picker'], PAPY3D_FACT_VIEWER_FOR_CHUCK365_VERSION, true);
	}

    /**
     * Register admin menu.
     *
     * @since 2.0.0
     * @return void
     */
    public function menu(): void {
        add_menu_page('Chuck365', 'Chuck365', 'manage_options', 'chuck365', [$this, 'admin_page'], 'dashicons-superhero');
    }

    /**
     * Register plugin settings.
     *
     * @since 2.0.0
     * @return void
     */
    public function settings(): void {
		$color_args = [
			'sanitize_callback' => 'sanitize_hex_color',
			'validate_callback' => function($value) {
				return (bool) preg_match('/^#[a-f0-9]{3,6}$/i', $value) ? $value : '#f39c12';
			}
		];
		$text_args  = [
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => function($value) {
				return !empty($value) ? $value : 'Chuck Norris Fact du jour';
			}
		];
		$bool_args  = [
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean'
		];

		register_setting('chuck365-group', 'chuck365_border_color', $color_args);
		register_setting('chuck365-group', 'chuck365_bg_color', $color_args);
		register_setting('chuck365-group', 'chuck365_text_color', $color_args);
		register_setting('chuck365-group', 'chuck365_text_title', $text_args);
		register_setting('chuck365-group', 'chuck365_show_copyright', $bool_args);
	}

    /**
     * Render admin settings page.
     *
     * @since 2.0.0
     * @return void
     */
    public function admin_page(): void {
        if (!current_user_can('manage_options')) return;
        $icon_url = esc_url(plugins_url('images/chuck.png', __FILE__));
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline cn-title-container">
                <img src="<?php echo esc_url($icon_url); ?>" alt="Chuck Icon" class="cn-custom-icon">
                <span><?php esc_html_e('Configuration de Chuck365', 'papy3d-fact-viewer-for-chuck365'); ?></span>
            </h1>

            <nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="#tab-settings" class="nav-tab nav-tab-active" data-tab="settings"><?php esc_html_e('Réglages & Aperçu', 'papy3d-fact-viewer-for-chuck365'); ?></a>
                <a href="#tab-help" class="nav-tab" data-tab="help"><?php esc_html_e('Utilisation', 'papy3d-fact-viewer-for-chuck365'); ?></a>
                <a href="#tab-project" class="nav-tab" data-tab="project"><?php esc_html_e('Soutenir le projet', 'papy3d-fact-viewer-for-chuck365'); ?></a>
                <a href="#tab-about" class="nav-tab" data-tab="about"><?php esc_html_e('À propos', 'papy3d-fact-viewer-for-chuck365'); ?></a>
            </nav>

            <div id="tab-content-settings" class="tab-content">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-1">
                        <div id="post-body-content">
                            <form method="post" action="options.php">
                                <?php settings_fields('chuck365-group'); do_settings_sections('chuck365-group'); ?>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Bordure / Titre', 'papy3d-fact-viewer-for-chuck365'); ?></th>
                                        <td><input type="text" name="chuck365_border_color" id="chuck365_border_color" value="<?php echo esc_attr(sanitize_hex_color(get_option('chuck365_border_color', '#f39c12'))); ?>" class="chuck-color-field" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Fond', 'papy3d-fact-viewer-for-chuck365'); ?></th>
                                        <td><input type="text" name="chuck365_bg_color" id="chuck365_bg_color" value="<?php echo esc_attr(sanitize_hex_color(get_option('chuck365_bg_color', '#ffffff'))); ?>" class="chuck-color-field" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Texte', 'papy3d-fact-viewer-for-chuck365'); ?></th>
                                        <td><input type="text" name="chuck365_text_color" id="chuck365_text_color" value="<?php echo esc_attr(sanitize_hex_color(get_option('chuck365_text_color', '#222222'))); ?>" class="chuck-color-field" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Titre', 'papy3d-fact-viewer-for-chuck365'); ?></th>
                                        <td><input type="text" name="chuck365_text_title" id="chuck365_text_title" value="<?php echo esc_attr(get_option('chuck365_text_title', 'Chuck Norris Fact du jour')); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Afficher le Copyright', 'papy3d-fact-viewer-for-chuck365'); ?></th>
                                        <td>
                                            <input type="checkbox" name="chuck365_show_copyright" id="chuck365_show_copyright" value="1" <?php checked(1, get_option('chuck365_show_copyright', 1)); ?> />
                                            <label for="chuck365_show_copyright"><?php esc_html_e('Afficher la barre de copyright et le lien Chuck365', 'papy3d-fact-viewer-for-chuck365'); ?></label>
                                        </td>
                                    </tr>
                                </table>

                                <p>
                                    <strong><?php esc_html_e('Presets de style :', 'papy3d-fact-viewer-for-chuck365'); ?></strong><br><br>
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
                    <h2><?php esc_html_e('Aperçu', 'papy3d-fact-viewer-for-chuck365'); ?></h2>
                    <?php echo wp_kses_post($this->render()); ?>
                </div>
            </div>

            <div id="tab-content-help" class="tab-content" style="display:none;">
                <div class="card" style="max-width: 1000px; padding: 20px; line-height: 1.6;">
                    <h2>📖 <?php esc_html_e('Comment afficher les Chuck Norris Facts ?', 'papy3d-fact-viewer-for-chuck365'); ?></h2>
                    <p><?php esc_html_e('Vous avez deux méthodes simples pour intégrer la puissance de Chuck sur votre site.', 'papy3d-fact-viewer-for-chuck365'); ?></p>
                    <div style="margin-bottom: 25px;">
                        <h3>1. <?php esc_html_e('Via l\'éditeur Gutenberg (Recommandé)', 'papy3d-fact-viewer-for-chuck365'); ?></h3>
                        <p><?php esc_html_e('Recherchez simplement le bloc nommé <strong>"Chuck365 Fact"</strong> dans l\'éditeur de vos pages ou articles. Vous pourrez voir l\'aperçu en direct.', 'papy3d-fact-viewer-for-chuck365'); ?></p>
                    </div>
                    <div style="margin-bottom: 25px;">
                        <h3>2. <?php esc_html_e('Via le Shortcode', 'papy3d-fact-viewer-for-chuck365'); ?></h3>
                        <p><?php esc_html_e('Copiez et collez le code suivant là où vous souhaitez afficher le fait (Widget texte, constructeur de page, etc.) :', 'papy3d-fact-viewer-for-chuck365'); ?></p>
                        <code>[chuck_fact]</code>
                    </div>
                    <hr>
                    <h3>💡 <?php esc_html_e('Conseils d\'optimisation', 'papy3d-fact-viewer-for-chuck365'); ?></h3>
                    <ul>
                        <li><strong><?php esc_html_e('Mise en cache :', 'papy3d-fact-viewer-for-chuck365'); ?></strong> <?php esc_html_e('Le plugin utilise un système de "Transients". Le fait est récupéré une seule fois par jour et stocké localement pour garantir une vitesse de chargement optimale.', 'papy3d-fact-viewer-for-chuck365'); ?></li>
                        <li><strong><?php esc_html_e('Design cohérent :', 'papy3d-fact-viewer-for-chuck365'); ?></strong> <?php esc_html_e('Utilisez les presets dans l\'onglet "Réglages" pour adapter rapidement le widget à la charte graphique de votre thème.', 'papy3d-fact-viewer-for-chuck365'); ?></li>
                    </ul>
                    <div style="background: #fff8e5; padding: 15px; border-left: 4px solid #ffb900; margin-top: 20px;">
                        <p style="margin: 0;">⚠️ <strong><?php esc_html_e('Note technique :', 'papy3d-fact-viewer-for-chuck365'); ?></strong> <?php esc_html_e('Si vous changez les couleurs et que vous utilisez un plugin de cache (WP Rocket, Autoptimize), pensez à vider le cache pour voir les modifications immédiatement sur votre site.', 'papy3d-fact-viewer-for-chuck365'); ?></p>
                    </div>
                </div>
            </div>

            <div id="tab-content-project" class="tab-content" style="display:none;">
                <div class="card" style="max-width: 1000px; padding: 30px; line-height: 1.6;">
                    <h2 style="color: #d63638;">🚀 <?php esc_html_e('Pourquoi soutenir le projet ?', 'papy3d-fact-viewer-for-chuck365'); ?></h2>
                    <p><?php esc_html_e('Soutenir Papy3D Fact Viewer for Chuck365, ce n\'est pas seulement financer un serveur, c\'est participer à la diffusion d\'une institution du web. Voici pourquoi votre contribution est essentielle :', 'papy3d-fact-viewer-for-chuck365'); ?></p>
                    <ol>
                        <li><strong><?php esc_html_e('Maintenir une infrastructure "Punchy" :', 'papy3d-fact-viewer-for-chuck365'); ?></strong> <?php esc_html_e('Derrière chaque "Fact", une API tourne 24h/24. Votre soutien aide à couvrir les frais d\'hébergement.', 'papy3d-fact-viewer-for-chuck365'); ?></li>
                        <li><strong><?php esc_html_e('Un projet Indépendant et Sans Pub :', 'papy3d-fact-viewer-for-chuck365'); ?></strong> <?php esc_html_e('Nous refusons de polluer l\'interface. Ce modèle repose sur la bienveillance de la communauté.', 'papy3d-fact-viewer-for-chuck365'); ?></li>
                        <li><strong><?php esc_html_e('Booster le développement futur :', 'papy3d-fact-viewer-for-chuck365'); ?></strong> <?php esc_html_e('Amélioration de l\'algorithme, nouvelles intégrations et maintenance de la compatibilité WordPress.', 'papy3d-fact-viewer-for-chuck365'); ?></li>
                        <li><strong><?php esc_html_e('Offrir un café à la forge :', 'papy3d-fact-viewer-for-chuck365'); ?></strong> <?php esc_html_e('Développer selon les standards de 2026 demande du temps. Vous valorisez ainsi le travail de Papy 3D Factory.', 'papy3d-fact-viewer-for-chuck365'); ?></li>
                    </ol>
                    <div style="background: #f0f0f1; padding: 20px; border-left: 4px solid #d63638; margin: 20px 0;">
                        <strong><?php esc_html_e('Le saviez-vous ?', 'papy3d-fact-viewer-for-chuck365'); ?></strong><br>
                        <?php esc_html_e('Chuck Norris ne dort jamais. Il attend. Mais en attendant, il boit du café. Offrez-lui-en un pour qu\'il continue de veiller sur votre site web !', 'papy3d-fact-viewer-for-chuck365'); ?>
                    </div>
                    <div style="text-align: center; margin-top: 30px;">
                        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                            <input type="hidden" name="cmd" value="_donations">
                            <input type="hidden" name="business" value="contact@papy-3d-factory.xyz">
                            <input type="number" name="amount" value="5" min="1" class="chuck-amount-input"> <span class="chuck-currency-symbol">€</span><br><br>
                            <button type="submit" class="chuck-coffee-button" style="padding: 0; height: auto;">
                                <img src="<?php echo esc_url(plugins_url('images/buymeacoffeebutton.png', __FILE__)); ?>" alt="<?php echo esc_attr__('Soutenir', 'papy3d-fact-viewer-for-chuck365'); ?>">
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div id="tab-content-about" class="tab-content" style="display:none;">
                <div class="card" style="max-width: 1000px; padding: 20px; line-height: 1.6;">
                    <h2>🚀 <?php esc_html_e('Le Projet Papy3D Fact Viewer for Chuck365', 'papy3d-fact-viewer-for-chuck365'); ?></h2>
                    <p><?php esc_html_e('Voici une présentation du projet Papy3D Fact Viewer for Chuck365, une initiative dédiée à la force brute et à l\'humour légendaire de Chuck Norris, conçue pour les développeurs et les administrateurs de sites web.', 'papy3d-fact-viewer-for-chuck365'); ?></p>
                    <p><?php esc_html_e('Chuck365.fr est une plateforme dont l\'unique mission est de propager la sagesse (parfois percutante) de Chuck Norris à travers le web. Le concept est simple mais implacable : offrir chaque jour un fait unique et aléatoire sur l\'homme qui a fait pleurer un oignon.', 'papy3d-fact-viewer-for-chuck365'); ?></p>
                    <p><?php esc_html_e('Contrairement aux bases de données statiques, Chuck365 mise sur la fraîcheur de son contenu pour garantir que vos utilisateurs ne lisent jamais deux fois la même vérité universelle dans un intervalle réduit.', 'papy3d-fact-viewer-for-chuck365'); ?></p>
                    <h3>🔌 <?php esc_html_e('Une API Puissante et Légère', 'papy3d-fact-viewer-for-chuck365'); ?></h3>
                    <p><?php esc_html_e('Au cœur du projet se trouve une API REST robuste conçue pour une intégration universelle.', 'papy3d-fact-viewer-for-chuck365'); ?></p>
                    <ul>
                        <li><strong><?php esc_html_e('Unique et Quotidien :', 'papy3d-fact-viewer-for-chuck365'); ?></strong> <?php esc_html_e('L\'API est optimisée pour fournir un "Fact du jour" cohérent pour tous les utilisateurs.', 'papy3d-fact-viewer-for-chuck365'); ?></li>
                        <li><strong><?php esc_html_e('Format JSON :', 'papy3d-fact-viewer-for-chuck365'); ?></strong> <?php esc_html_e('Les réponses sont livrées en JSON, permettant une manipulation facile en JavaScript, PHP, Python ou tout autre langage moderne.', 'papy3d-fact-viewer-for-chuck365'); ?></li>
                        <li><strong><?php esc_html_e('Haute Disponibilité :', 'papy3d-fact-viewer-for-chuck365'); ?></strong> <?php esc_html_e('Hébergée pour répondre plus vite qu\'un high-kick, l\'API supporte des requêtes provenant de divers environnements sans latence perceptible.', 'papy3d-fact-viewer-for-chuck365'); ?></li>
                    </ul>
                    <h3>📦 <?php esc_html_e('Le Plugin Papy3D Fact Viewer for Chuck365', 'papy3d-fact-viewer-for-chuck365'); ?></h3>
                    <p><?php esc_html_e('Pour les utilisateurs de WordPress, le plugin Papy3D Fact Viewer for Chuck365 (actuellement en version 2.0.4) agit comme le pont direct entre la puissance de l\'API et votre interface utilisateur.', 'papy3d-fact-viewer-for-chuck365'); ?></p>
                    <h4><?php esc_html_e('Caractéristiques Principales :', 'papy3d-fact-viewer-for-chuck365'); ?></h4>
                    <ul>
                        <li><strong><?php esc_html_e('Affichage Dynamique :', 'papy3d-fact-viewer-for-chuck365'); ?></strong> <?php esc_html_e('Le plugin récupère automatiquement les faits via l\'API officielle.', 'papy3d-fact-viewer-for-chuck365'); ?></li>
                        <li><strong><?php esc_html_e('Performance Optimisée :', 'papy3d-fact-viewer-for-chuck365'); ?></strong> <?php esc_html_e('Utilisation d\'un système de mise en cache (transients) qui stocke le fait jusqu\'au lendemain.', 'papy3d-fact-viewer-for-chuck365'); ?></li>
                        <li><strong><?php esc_html_e('Personnalisation Totale :', 'papy3d-fact-viewer-for-chuck365'); ?></strong> <?php esc_html_e('Interface d\'administration dédiée pour modifier les couleurs et titres.', 'papy3d-fact-viewer-for-chuck365'); ?></li>
                        <li><strong><?php esc_html_e('Gutenberg & Shortcode :', 'papy3d-fact-viewer-for-chuck365'); ?></strong> <?php esc_html_e('Compatible avec l\'éditeur moderne et utilisable via [chuck_fact].', 'papy3d-fact-viewer-for-chuck365'); ?></li>
                        <li><strong><?php esc_html_e('Sécurité Native :', 'papy3d-fact-viewer-for-chuck365'); ?></strong> <?php esc_html_e('Développé avec des standards stricts (PHP 8.1+, protection CSRF, assainissement des données).', 'papy3d-fact-viewer-for-chuck365'); ?></li>
                    </ul>
                    <h4><?php esc_html_e('Pourquoi l\'utiliser ?', 'papy3d-fact-viewer-for-chuck365'); ?></h4>
                    <p><?php esc_html_e('Ajouter une touche d\'humour à un tableau de bord ou à une barre latérale de blog n\'a jamais été aussi simple. Le projet Chuck365 prouve que même la technologie la plus sérieuse peut avoir un crochet du droit redoutable... et très drôle.', 'papy3d-fact-viewer-for-chuck365'); ?></p>
                    <p><em><?php esc_html_e('Note : Chuck Norris n\'utilise pas d\'API. Les données se déplacent par peur de le contrarier.', 'papy3d-fact-viewer-for-chuck365'); ?></em></p>
                    <hr>
                    <p>
                        <strong>Version :</strong> <?php echo esc_html(PAPY3D_FACT_VIEWER_FOR_CHUCK365_VERSION); ?> |
                        <strong>Auteur :</strong> <?php echo esc_html__('Papy 3D Factory', 'papy3d-fact-viewer-for-chuck365'); ?>
                    </p>
                </div>
            </div>

            <div class="copyright" style="text-align: center; margin-top: 40px; color: #666;">
                <p>© <?php echo esc_html(gmdate('Y')); ?> — Créé avec <span style="color:red;">❤</span> par <a href="https://papy-3d-factory.xyz" target="_blank">Papy 3D Factory</a></p>
            </div>
        </div>
        <?php
    }

    /**
     * Retrieve Chuck Norris fact (cached daily).
     *
     * @since 2.0.0
     * @return string Sanitized fact.
     */
    public function get_fact(): string {
		$today = gmdate('Y-m-d');
		$cached_fact = get_transient('chuck365_fact');
		$cached_date = get_transient('chuck365_fact_date');

		if ($cached_fact && $cached_date === $today) {
			return $cached_fact;
		}

		$args = [
			'timeout'    => 10,
			'user-agent' => 'Chuck365-Viewer/' . PAPY3D_FACT_VIEWER_FOR_CHUCK365_VERSION,
		];

		$response = wp_remote_get('https://chuck365.fr/api.php', $args);

				
		if (is_wp_error($response)) {
			return (string) __('Chuck est occupé.', 'papy3d-fact-viewer-for-chuck365');
		}
		
		
		
		$response_code = wp_remote_retrieve_response_code($response);
		if ($response_code !== 200) {
			return (string)__('Chuck se repose.', 'papy3d-fact-viewer-for-chuck365');
		}

		$body = wp_remote_retrieve_body($response);
		try {
			$data = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
			if (isset($data->success, $data->fact) && $data->success === true && !empty($data->fact)) {
				// Sanitize AVANT de stocker en cache
				$fact = wp_kses_post((string)$data->fact);
				set_transient('chuck365_fact_date', $today, DAY_IN_SECONDS);
				set_transient('chuck365_fact', $fact, DAY_IN_SECONDS);
				return $fact;
			}
		} catch (\JsonException $e) {
			return (string)__('Chuck se repose.', 'papy3d-fact-viewer-for-chuck365');
		}
		return (string)__('Chuck se repose.', 'papy3d-fact-viewer-for-chuck365');
	}

    /**
     * Handle AJAX request for fetching a fact.
     *
     * @since 2.0.0
     * @return void
     */
    public function ajax_fact(): void {
		check_ajax_referer('chuck365_ajax_action', 'nonce');

		wp_send_json_success([
			'fact' => wp_kses_post($this->get_fact())
		]);
	}

    /**
     * Shortcode handler.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string Rendered HTML output.
     */
    public function shortcode_render($atts): string {
		wp_enqueue_style('chuck365-style');

		$atts = shortcode_atts([
			'borderColor'   => get_option('chuck365_border_color', '#f39c12'),
			'bgColor'       => get_option('chuck365_bg_color', '#ffffff'),
			'textColor'     => get_option('chuck365_text_color', '#222222'),
			'title'         => get_option('chuck365_text_title', 'Chuck Norris Fact du jour'),
			'showCopyright' => get_option('chuck365_show_copyright', true),
		], $atts);

		return $this->render([
			'borderColor'   => sanitize_hex_color($atts['borderColor']),
			'bgColor'       => sanitize_hex_color($atts['bgColor']),
			'textColor'     => sanitize_hex_color($atts['textColor']),
			'title'         => esc_html($atts['title']),
			'showCopyright' => (bool) $atts['showCopyright'],
		]);
	}

    /**
     * Render block or shortcode output.
     *
     * @since 2.0.0
     * @param array $attributes Block attributes.
     * @return string HTML output.
     */
    public function render(array $attributes = []): string {
		// Re-sanitize TOUTES les variables, même celles des options
		$border        = sanitize_hex_color((string)($attributes['borderColor']   ?? get_option('chuck365_border_color', '#f39c12')));
		$bg            = sanitize_hex_color((string)($attributes['bgColor']       ?? get_option('chuck365_bg_color', '#ffffff')));
		$color         = sanitize_hex_color((string)($attributes['textColor']     ?? get_option('chuck365_text_color', '#222222')));
		$title         = esc_html((string)($attributes['title']         ?? get_option('chuck365_text_title', 'Chuck Norris Fact du jour')));
		$showCopyright = isset($attributes['showCopyright']) ? (bool)$attributes['showCopyright'] : (bool)get_option('chuck365_show_copyright', true);

		$icon_url  = plugins_url('images/chuck.png', __FILE__);
		$ajax_url  = esc_url(admin_url('admin-ajax.php'));
		$nonce     = wp_create_nonce('chuck365_ajax_action');
		$fact      = $this->get_fact(); // Déjà sanitizé dans get_fact()
		$safe_fact = wp_kses_post($fact); // Redondant mais sûr
		$unique_id = sanitize_html_class('chuck-title-' . bin2hex(random_bytes(4)));

		ob_start();
		?><div class="cn-main-box" data-ajax-url="<?php echo esc_url($ajax_url); ?>" data-nonce="<?php echo esc_attr($nonce); ?>" style="--chuck-border:<?php echo esc_attr($border); ?>; --chuck-bg:<?php echo esc_attr($bg); ?>; --chuck-text:<?php echo esc_attr($color); ?>;">
			<div class="cn-top-label">
				<div class="cn-icon-container">
					<img src="<?php echo esc_url($icon_url); ?>"
						 alt="<?php echo esc_attr__('Chuck Icon', 'papy3d-fact-viewer-for-chuck365'); ?>"
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
					<a href="https://chuck365.fr" target="_blank" rel="noopener noreferrer" class="cn-link-btn"><?php echo esc_html__('Visiter le site', 'papy3d-fact-viewer-for-chuck365'); ?></a>
				</div>
			<?php endif; ?>
		</div><?php
		return trim(ob_get_clean() ?: '');
	}
}

/**
 * Initialize plugin.
 *
 * @since 2.0.0
 * @return void
 */
add_action('plugins_loaded', function() {
    new Papy3D_Fact_Viewer_For_Chuck365_Plugin();
});