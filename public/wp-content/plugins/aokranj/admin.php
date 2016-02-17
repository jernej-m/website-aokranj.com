<?php

session_start();

/**
 * Load configuration
 */
require_once 'config.php';

/**
 * AOKranj administration
 *
 * @package aokranj
 * @link http://aokranj.com/
 * @author Bojan Hribernik <bojan.hribernik@gmail.com>
 */
class AOKranj_Admin extends AOKranj
{
    public function __construct()
    {
        if (is_multisite())
        {
            $admin_menu = 'network_admin_menu';
            $admin_notices = 'network_admin_notices';

            $options_page = 'settings.php';
            $options_form_action = '../options.php';
            $options_capability = 'manage_network_options';
        }
        else
        {
            $admin_menu = 'admin_menu';
            $admin_notices = 'admin_notices';

            $options_page = 'options-general.php';
            $options_form_action = 'options.php';
            $options_capability = 'manage_options';
        }

        add_action($admin_menu, array(&$this, 'admin_menu'));
        add_action('admin_init', array(&$this, 'admin_init'));

        register_activation_hook(__FILE__, array(&$this, 'activate'));
        register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));

        add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));

        add_action('wp_dashboard_setup', array(&$this, 'wp_dashboard_setup'));

        add_action('pre_get_posts', array(&$this, 'pre_get_posts'));

        add_action('wp_ajax_vzponi', array(&$this, 'vzponi'));
        add_action('wp_ajax_dodaj_vzpon', array(&$this, 'dodaj_vzpon'));
        add_action('wp_ajax_prenos_podatkov', array(&$this, 'prenos_podatkov'));
    }


    /**
     * Wordpress hooks
     */

    public function activate()
    {
        global $wpdb;

        if (is_multisite() && !is_network_admin())
        {
            die(self::NAME . ' must be activated via the Network Admin interface'
                    . 'when WordPress is in multistie network mode.');
        }

        /*
         * Create or alter the plugin's tables as needed.
         */

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Note: dbDelta() requires two spaces after "PRIMARY KEY".  Weird.
        // WP's insert/prepare/etc don't handle NULL's (at least in 3.3).
        // It also requires the keys to be named and there to be no space
        // the column name and the key length.
        $sql = "CREATE TABLE " . $this->table_vzponi . " (
                id int(10) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                tip varchar(4) NOT NULL DEFAULT '',
                datum date NOT NULL DEFAULT '0000-00-00',
                destinacija varchar(50) NOT NULL DEFAULT '',
                smer varchar(50) NOT NULL DEFAULT '',
                ocena varchar(30) DEFAULT NULL,
                cas varchar(30) DEFAULT NULL,
                vrsta varchar(4) NOT NULL DEFAULT '',
                visina_smer varchar(15) NOT NULL DEFAULT '',
                visina_izstop varchar(15) NOT NULL DEFAULT '',
                pon_vrsta varchar(4) DEFAULT NULL,
                pon_nacin varchar(4) DEFAULT NULL,
                stil varchar(4) DEFAULT NULL,
                mesto varchar(4) DEFAULT NULL,
                partner varchar(50) DEFAULT NULL,
                opomba varchar(5) DEFAULT NULL,
                deleted int(1) unsigned DEFAULT NULL,
                PRIMARY KEY  (id)
            )";

        dbDelta($sql);

        if ($wpdb->last_error)
        {
            die($wpdb->last_error);
        }
    }

    public function deactivate()
    {
        return;

        /*
        global $wpdb;

        $show_errors = $wpdb->show_errors;
        $wpdb->show_errors = false;
        $denied = 'command denied to user';

        $wpdb->query("DROP TABLE " . $this->table_vzponi);

        if ($wpdb->last_error)
        {
            if (strpos($wpdb->last_error, $denied) === false)
            {
                die($wpdb->last_error);
            }
        }

        $wpdb->show_errors = $show_errors;
         *
         */
    }

    public function admin_menu()
    {
        $svg = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="32" height="32" viewBox="0 0 32 32"><g></g><path d="M28.438 0.438c-1.247-0.44-2.476-0.19-3.5 0.375-0.971 0.536-1.775 1.342-2.313 2.25h-0.063c-0.024 0.033-0.038 0.092-0.063 0.125-1.135 1.509-3.033 2.978-3.688 5.438v0.188c-0.144 1.653 0.755 3.048 1.875 3.938l0.25 0.25h0.313c1.479 0.112 2.641-0.593 3.563-1.313s1.722-1.464 2.438-1.875v-0.063c1.884-1.267 4.115-2.982 4.688-5.688v-0.125c0.070-1.186-0.699-2.113-1.438-2.563s-1.464-0.65-1.875-0.875l-0.063-0.063h-0.125zM27.75 2.313c0.019 0.010 0.044-0.010 0.063 0 0.626 0.317 1.298 0.576 1.688 0.813 0.386 0.235 0.437 0.31 0.438 0.625-0.411 1.754-1.963 3.145-3.688 4.313-0.025 0.017-0.038 0.046-0.063 0.063-1.027 0.608-1.877 1.416-2.625 2-0.639 0.499-1.182 0.693-1.813 0.75-0.514-0.519-0.94-1.134-0.938-1.75 0.477-1.656 2.038-3.039 3.375-4.875l0.063-0.063c0.354-0.639 0.978-1.268 1.625-1.625 0.626-0.346 1.26-0.447 1.875-0.25z" fill="#000000" /><path d="M13.172 21.246c0.105-0.162 0.505-0.571 1.041-1.204l0.008-0.050c1.129-1.389 3.059-2.774 4.973-4.857l0.126-0.126c0.855-1.066 1.692-1.925 2.518-2.46l-1.24-2.66c-1.68 1.087-2.89 2.463-3.884 3.69l-0.048-0.037c-1.286 1.399-3.322 2.823-5.17 5.095-0.308 0.363-0.879 0.892-1.451 1.781l3.127 0.828z" fill="#000000" /><path d="M0.96 28.029c-0.429-1.251-0.168-2.478 0.407-3.496 0.545-0.966 1.358-1.762 2.271-2.292l0.001-0.063c0.033-0.024 0.093-0.037 0.126-0.061 1.52-1.121 3.006-3.006 5.471-3.638l0.188 0.002c1.654-0.129 3.041 0.783 3.92 1.911l0.248 0.252-0.003 0.313c0.099 1.48-0.617 2.636-1.345 3.55s-1.48 1.708-1.897 2.42l-0.063-0.001c-1.284 1.872-3.020 4.087-5.73 4.635l-0.125-0.001c-1.187 0.059-2.107-0.718-2.549-1.461s-0.637-1.47-0.858-1.883l-0.062-0.063 0.001-0.125zM2.841 27.358c0.010 0.019-0.010 0.043-0.001 0.063 0.311 0.629 0.564 1.303 0.797 1.695 0.231 0.388 0.306 0.44 0.621 0.443 1.757-0.395 3.163-1.935 4.346-3.648 0.017-0.025 0.046-0.037 0.063-0.062 0.618-1.021 1.433-1.864 2.024-2.607 0.505-0.634 0.704-1.175 0.767-1.806-0.515-0.518-1.126-0.95-1.741-0.953-1.66 0.462-3.057 2.010-4.906 3.33l-0.063 0.062c-0.642 0.348-1.277 0.967-1.64 1.61-0.351 0.623-0.458 1.256-0.267 1.873z" fill="#000000" /><path d="M12.455 21.093c0.099-0.165 0.487-0.586 1.003-1.236l0.006-0.050c1.086-1.423 2.971-2.868 4.819-5.009l0.122-0.129c0.822-1.093 1.631-1.977 2.44-2.537l-1.323-2.62c-1.645 1.139-2.812 2.552-3.767 3.809l-0.049-0.036c-1.241 1.439-3.232 2.925-5.009 5.254-0.296 0.372-0.85 0.919-1.395 1.825l3.151 0.73z" fill="#000000" /></svg>';
        $icon = 'data:image/svg+xml;base64,' . base64_encode($svg);

        add_menu_page('Moj AO', 'Moj AO', 'read', self::ID . '/app.php', null, $icon, 3);

        if (!current_user_can('manage_options'))
        {
            remove_menu_page('tools.php');
        }
    }

    public function admin_init()
    {
        /*
        add_action('show_user_profile', array(&$this, 'user_profile_extra_fields'));
        add_action('edit_user_profile', array(&$this, 'user_profile_extra_fields'));
         *
         */
    }

    public function user_profile_extra_fields()
    {
        echo '
        <h3>Extra profile information</h3>

        <table class="form-table">

            <tr>
                <th><label for="twitter">Twitter</label></th>

                <td>
                    <input type="text" name="twitter" id="twitter" value="' . esc_attr(get_the_author_meta('twitter', get_current_user_id())) . '" class="regular-text" /><br />
                    <span class="description">Please enter your Twitter username.</span>
                </td>
            </tr>

        </table>';
    }

    public function admin_enqueue_scripts()
    {
        global $hook_suffix;

        if ($hook_suffix === 'aokranj/app.php')
        {
            if (AOKRANJ_DEBUG)
            {
                wp_enqueue_style('aokranj-admin-bootstrap', AOKRANJ_PLUGIN_URL . '/app/bootstrap.css', array(), AOKRANJ_PLUGIN_VERSION);
                wp_enqueue_script('aokranj-admin-ext', AOKRANJ_PLUGIN_URL . '/app/ext/ext-dev.js', array(), AOKRANJ_PLUGIN_VERSION);
                wp_enqueue_script('aokranj-admin-bootstrap', AOKRANJ_PLUGIN_URL . '/app/bootstrap.js', array(), AOKRANJ_PLUGIN_VERSION);
                wp_enqueue_script('aokranj-admin-app', AOKRANJ_PLUGIN_URL . '/app/app.js', array(), AOKRANJ_PLUGIN_VERSION);
            }
            else
            {
                wp_enqueue_style('aokranj-admin-app', AOKRANJ_PLUGIN_URL . '/app/build/production/AO/resources/AO-all.css', array(), AOKRANJ_PLUGIN_VERSION);
                wp_enqueue_script('aokranj-admin-app', AOKRANJ_PLUGIN_URL . '/app/build/production/AO/app.js', array(), AOKRANJ_PLUGIN_VERSION);
            }
        }

        wp_enqueue_style('aokranj-admin-style', AOKRANJ_PLUGIN_URL . '/admin.css', array(), AOKRANJ_PLUGIN_VERSION);
    }

    public function wp_dashboard_setup()
    {
        global $wp_meta_boxes;
        unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
        unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
    }

    public function pre_get_posts($query)
    {
    		global $current_user;

    		// do not limit user with Administrator role
    		if (current_user_can('administrator'))
            {
    			return;
    		}

    		if (current_user_can('edit_posts') && !current_user_can('edit_others_posts'))
            {
    			$query->set('author', $current_user->ID);

                add_filter('views_edit-post', array(&$this, 'fix_post_counts'));
    			add_filter('views_upload', array(&$this, 'fix_media_counts'));
    		}
    }

    public function fix_post_counts($views)
    {
    		global $current_user, $wp_query;

    		unset($views['mine']);

    		$types = array(
    			array('status' => NULL),
    			array('status' => 'publish'),
    			array('status' => 'draft'),
    			array('status' => 'pending'),
    			array('status' => 'trash')
    		);

    		foreach ($types as $type) {
      			$query = array(
      				'author' => $current_user->ID,
      				'post_type' => 'post',
      				'post_status' => $type['status']
      			);
            $result = new WP_Query($query);
      			if ($type['status'] == NULL):
        				$class = (empty($wp_query->query_vars['post_status']) || $wp_query->query_vars['post_status'] == NULL) ? ' class="current"' : '';
        				$views['all'] = sprintf('<a href="%s"' . $class . '>' . __('All', 'vopmo') . ' <span class="count">(%d)</span></a>', admin_url('edit.php?post_type=post'), $result->found_posts);
      			elseif ($type['status'] == 'publish'):
        				$class = (!empty($wp_query->query_vars['post_status']) && $wp_query->query_vars['post_status'] == 'publish') ? ' class="current"' : '';
        				$views['publish'] = sprintf('<a href="%s"' . $class . '>' . __('Published', 'vopmo') . ' <span class="count">(%d)</span></a>', admin_url('edit.php?post_status=publish&post_type=post'), $result->found_posts);
      			elseif ($type['status'] == 'draft'):
        				$class = (!empty($wp_query->query_vars['post_status']) && $wp_query->query_vars['post_status'] == 'draft') ? ' class="current"' : '';
        				$views['draft'] = sprintf('<a href="%s"' . $class . '>' . __('Drafts', 'vopmo') . ' <span class="count">(%d)</span></a>', admin_url('edit.php?post_status=draft&post_type=post'), $result->found_posts);
      			elseif ($type['status'] == 'pending'):
        				$class = (!empty($wp_query->query_vars['post_status']) && $wp_query->query_vars['post_status'] == 'pending') ? ' class="current"' : '';
        				$views['pending'] = sprintf('<a href="%s"' . $class . '>' . __('Pending', 'vopmo') . ' <span class="count">(%d)</span></a>', admin_url('edit.php?post_status=pending&post_type=post'), $result->found_posts);
      			elseif ($type['status'] == 'trash'):
        				$class = (!empty($wp_query->query_vars['post_status']) && $wp_query->query_vars['post_status'] == 'trash') ? ' class="current"' : '';
        				$views['trash'] = sprintf('<a href="%s"' . $class . '>' . __('Trash', 'vopmo') . ' <span class="count">(%d)</span></a>', admin_url('edit.php?post_status=trash&post_type=post'), $result->found_posts);
      			endif;
    		}
    		return $views;
    }

    public function fix_media_counts($views)
    {
    		global $wpdb, $current_user, $post_mime_types, $avail_post_mime_types;
    		$views = array();
    		$_num_posts = array();
    		$count = $wpdb->get_results("
            SELECT post_mime_type, COUNT( * ) AS num_posts
            FROM $wpdb->posts
            WHERE post_type = 'attachment'
            AND post_author = $current_user->ID
            AND post_status != 'trash'
            GROUP BY post_mime_type
        ", ARRAY_A);
    		foreach ($count as $row)
    			$_num_posts[$row['post_mime_type']] = $row['num_posts'];
    		if (!empty($_num_posts)) {
    			$_total_posts = array_sum($_num_posts);
    		} else {
    			$_total_posts = 0;
    		}
    		$detached = isset($_REQUEST['detached']) || isset($_REQUEST['find_detached']);
    		if (!isset($total_orphans))
    			$total_orphans = $wpdb->get_var("
                SELECT COUNT( * )
                FROM $wpdb->posts
                WHERE post_type = 'attachment'
                AND post_author = $current_user->ID
                AND post_status != 'trash'
                AND post_parent < 1
            ");
    		$matches = wp_match_mime_types(array_keys($post_mime_types), array_keys($_num_posts));
    		foreach ($matches as $type => $reals)
    			foreach ($reals as $real)
    				$num_posts[$type] = ( isset($num_posts[$type]) ) ? $num_posts[$type] + $_num_posts[$real] : $_num_posts[$real];
    		$class = ( empty($_GET['post_mime_type']) && !$detached && !isset($_GET['status']) ) ? ' class="current"' : '';
    		$views['all'] = "<a href='upload.php'$class>" . sprintf(__('All <span class="count">(%s)</span>'), number_format_i18n($_total_posts)) . '</a>';
    		foreach ($post_mime_types as $mime_type => $label) {
    			$class = '';
    			if (!wp_match_mime_types($mime_type, $avail_post_mime_types))
    				continue;
    			if (!empty($_GET['post_mime_type']) && wp_match_mime_types($mime_type, $_GET['post_mime_type']))
    				$class = ' class="current"';
    			if (!empty($num_posts[$mime_type]))
    				$views[$mime_type] = "<a href='upload.php?post_mime_type=$mime_type'$class>" . sprintf(translate_nooped_plural($label[2], $num_posts[$mime_type]), $num_posts[$mime_type]) . '</a>';
    		}
    		$views['detached'] = '<a href="upload.php?detached=1"' . ( $detached ? ' class="current"' : '' ) . '>' . sprintf(__('Unattached <span class="count">(%s)</span>'), $total_orphans) . '</a>';
    		return $views;
    }

    /**
     * Vzponi
     */

    public function vzponi()
    {
        global $wpdb;

        $page = $this->getRequestPage();
        $start = $this->getRequestStart();
        $limit = $this->getRequestLimit();
        $sort = $this->getRequestSort();

        $vzponi = $wpdb->get_results(sprintf('
            SELECT *
            FROM %s
            WHERE user_id = %d
            ORDER BY %s %s
            LIMIT %d, %d',
            $this->table_vzponi,
            get_current_user_id(),
            $sort['property'],
            $sort['direction'],
            $start,
            $limit
        ));

        $total = $wpdb->get_var(sprintf('
            SELECT COUNT(id)
            FROM %s
            WHERE user_id = %d',
            $this->table_vzponi,
            get_current_user_id()
        ));

        $response = array(
            'success' => true,
            'data'    => $vzponi,
            'total'   => $total,
        );

        die(json_encode($response));
    }

    public function dodaj_vzpon()
    {
        $nonce = filter_input(INPUT_POST, 'nonce');
        wp_verify_nonce($nonce, 'aokranj-app');

        global $wpdb;

        // get values from $_POST
        $vzpon = array(
            'user_id' => get_current_user_id(),
            'tip' => filter_input(INPUT_POST, 'tip'),
            'destinacija' => filter_input(INPUT_POST, 'destinacija'),
            'smer' => filter_input(INPUT_POST, 'smer'),
            'datum' => filter_input(INPUT_POST, 'datum'),
            'ocena' => filter_input(INPUT_POST, 'ocena'),
            'cas' => filter_input(INPUT_POST, 'cas'),
            'vrsta' => filter_input(INPUT_POST, 'vrsta'),
            'visina_smer' => filter_input(INPUT_POST, 'visina_smer'),
            'visina_izstop' => filter_input(INPUT_POST, 'visina_izstop'),
            'pon_vrsta' => filter_input(INPUT_POST, 'pon_vrsta'),
            'pon_nacin' => filter_input(INPUT_POST, 'pon_nacin'),
            'stil' => filter_input(INPUT_POST, 'stil'),
            'mesto' => filter_input(INPUT_POST, 'mesto'),
            'partner' => filter_input(INPUT_POST, 'partner'),
            'opomba' => filter_input(INPUT_POST, 'opomba'),
        );

        // insert into db
        $wpdb->insert($this->table_vzponi, array_filter($vzpon));

        // read from db
        $vzpon = $wpdb->get_row("SELECT * FROM " . $this->table_vzponi . " WHERE id = " . $wpdb->insert_id);

        $response = array(
            'success' => true,
            'data'    => $vzpon,
            'msg'     => 'Vzpon je bil uspešno dodan.'
        );

        die(json_encode($response));
    }

    /**
     * Prenos podatkov
     *
     * DELETE FROM `wp_posts` WHERE ID > 17;
     * DELETE FROM `wp_postmeta` WHERE post_id > 17;
     */

    private $currentUser;
    private $currentSlug;

    private $users = array();
    private $usersById = array();
    private $usersByUserName = array();
    private $posts = array();
    private $reports = array();
    private $vzponi = array();

    public function prenos_podatkov()
    {
        //die(__FILE__ . ' @line ' . __LINE__);

        // used by wordpress functions to skip some checks
        define('WP_IMPORTING', true);

        // verify app nonce
        $nonce = filter_input(INPUT_POST, 'nonce');
        wp_verify_nonce($nonce, 'aokranj-app');

        // set max execution time to 1h
        ini_set('max_execution_time', 3600);
        set_time_limit(3600);

        $this->prenesiUporabnike();

        $this->prenesiVzpone();

        $this->prenesiUtrinke();

        $this->prenesiReportaze();

        // build response
        $response = array(
            'success' => true,
            'data'    => array(
                'users' => count($this->users),
                'posts' => count($this->posts),
                'reports' => count($this->reports),
                'vzponi' => count($this->vzponi),
            ),
            'msg' => 'Prenos je uspel :)',
        );

        die(json_encode($response));
    }

    private function prenesiUporabnike()
    {
        global $wpdb;
        $aodb = $this->aodb();

        $users = array();
        $ao_users = $aodb->get_results('SELECT * FROM member');
        $total = count($ao_users);

        foreach ($ao_users as $i => $ao_user)
        {
            // check if user already exists
            $wp_user = get_user_by('login', $ao_user->userName);
            if ($wp_user)
            {
                $this->addUserToCollection($wp_user, $ao_user);
                continue;
            }

            // skip if no username or email
            if (empty($ao_user->userName) && empty($ao_user->email))
            {
                print_r(['no data',$ao_user]);
                continue;
            }

            // insert wordpress user
            $wp_user_data = array(
                'user_login'    => $ao_user->userName,
                'user_pass'     => wp_generate_password(12, false),
                'user_nicename' => strtolower($ao_user->userName),
                'first_name'    => $ao_user->name,
                'last_name'     => $ao_user->surname,
                'role'          => 'contributor',
            );
            if (!empty($ao_user->email) && strlen(trim($ao_user->email)))
            {
                $wp_user_data['user_email'] = $ao_user->email;
            }

            $wp_user_id = wp_insert_user($wp_user_data);

            // error inserting user
            if (is_wp_error($wp_user_id))
            {
                print_r(['unable to insert user', $ao_user, $wp_user_id]);
                continue;
            }

            // set user status
            $wpdb->query(sprintf(
                'UPDATE %s SET user_status = %d WHERE ID = %d',
                esc_sql($wpdb->users),
                self::USER_STATUS_WAITING,
                $wp_user_id
            ));

            // load wordpress user
            $wp_user = get_user_by('id', $wp_user_id);

            // unable to load user
            if (is_wp_error($wp_user))
            {
                print_r(['unable to load user', $ao_user, $wp_user]);
                continue;
            }

            // add user to collection
            $this->addUserToCollection($wp_user, $ao_user);
        }
    }

    private function prenesiVzpone()
    {
        global $wpdb;
        $aodb = $this->aodb();

        // fields and values for the query
        $fields = array();
        $values = array();

        // get all ascents
        $vzponi = $aodb->get_results('SELECT * FROM vzpon WHERE deleted IS NULL');
        foreach ($vzponi as $i => $vzpon)
        {
            // get wordpress user for vzpon
            if (!isset($this->usersById[$vzpon->memberId]))
            {
                continue;
            }
            $user = $this->usersById[$vzpon->memberId];

            // fix ascent
            unset($vzpon->vzponId, $vzpon->memberId);
            $vzpon->user_id = $user->ID;

            // add ascent reference
            $this->vzponi[] = $vzpon;

            // process fields
            $item = array();
            foreach ($vzpon as $k => $v)
            {
                if ($i === 0)
                {
                    $fields[] = $k;
                }

                switch ($k)
                {
                    case 'deleted':
                        $item[] = (int)$v;
                        break;
                    default:
                        $item[] = "'" . esc_sql($v) . "'";
                        break;
                }
            }
            $values[] = '(' . implode(',', $item) . ')';
        }

        // build query
        $query = sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->table_vzponi,
            implode(',', $fields),
            implode(',', $values)
        );

        // insert ascents
        $wpdb->query($query);
    }

    private function prenesiUtrinke()
    {
        global $wpdb;
        $aodb = $this->aodb();

        // add upload folder filter
        add_filter('upload_dir', array(&$this, 'utrinekUploadDir'));

        // set root paths
        $utrinki_dir = AOKRANJ_OLD_DIR . '/pic/utrinek';
        $tmp_dir = sys_get_temp_dir();

        // fetch comments
        $all_comments = $aodb->get_results('SELECT * FROM utrinek_comment');
        $comments = array();
        foreach ($all_comments as $comment)
        {
            $comments[$comment->utrinekId][] = $comment;
        }

        // select old posts
        $utrinki = $aodb->get_results('SELECT * FROM utrinek WHERE deleted IS NULL');
        foreach ($utrinki as $utrinek)
        {
            // find wordpress user
            if (!isset($this->usersByUserName[$utrinek->author]))
            {
                continue;
            }
            $user = $this->usersByUserName[$utrinek->author];

            // set current user for utrinekUploadDir()
            $this->currentUser = $user;

            // check if post already exists
            $exists = $wpdb->get_var(sprintf(
                'SELECT COUNT(ID) FROM %s WHERE post_author = %d AND post_title = \'%s\' AND post_date = \'%s\'',
                $wpdb->posts,
                $user->ID,
                esc_sql($utrinek->destination),
                esc_sql(date('Y-m-d H:i:s', strtotime($utrinek->valid_from)))
            ));
            if ($exists)
            {
                continue;
            }

            // create post
            $data = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'post_author' => $user->ID,
                'post_title' => $utrinek->destination,
                'post_content' => $utrinek->content,
                'post_date' => $utrinek->valid_from,
                'post_date_gmt' => $utrinek->valid_from,
            );
            $post_id = wp_insert_post($data);
            $post = get_post($post_id);
            $this->posts[] = get_post($post_id);

            // add comment
            if (isset($comments[$utrinek->utrinekId]))
            {
                foreach ($comments[$utrinek->utrinekId] as $comment)
                {
                    if (isset($this->usersByUserName[$comment->editor]))
                    {
                        $commentUser = $this->usersByUserName[$comment->editor];

                        $data = array(
                            'comment_post_ID' => $post_id,
                            'comment_author' => $commentUser->user_nicename,
                            'comment_author_email' => $commentUser->user_email,
                            'comment_author_url' => $commentUser->user_url,
                            'comment_content' => $comment->comment,
                            'comment_type' => 'comment',
                            'comment_parent' => 0,
                            'user_id' => $commentUser->ID,
                            'comment_author_IP' => '',
                            'comment_agent' => '',
                            'comment_date' => $comment->timestamp,
                            'comment_approved' => 1,
                        );

                        wp_insert_comment($data);
                    }
                }
            }

            //set current slug for reportUploadDir()
            $this->currentSlug = $post->post_name;

            // main utrinek dir
            $utrinek_dir = $utrinki_dir . '/' . $utrinek->author;

            // load texts
            $text_file = $utrinek_dir . '/utrinek_' . $utrinek->utrinekId . '.txt';
            $texts = $this->getPostTexts($text_file);

            // read old images and insert attachments
            $attachments = array();
            for ($i = 1; $i < 6; $i++)
            {
                // filename can be utrinek_ID_1.jpg or utrinek_ID_01.jpg
                $file_name1 = 'utrinek_' . $utrinek->utrinekId . '_' . $i . '.jpg';
                $file_name2 = 'utrinek_' . $utrinek->utrinekId . '_0' . $i . '.jpg';
                $source1 = $utrinek_dir . '/' . $file_name1;
                $source2 = $utrinek_dir . '/' . $file_name2;
                if (file_exists($source1))
                {
                    $file_name = $file_name1;
                    $source = $source1;
                }
                else if (file_exists($source2))
                {
                    $file_name = $file_name2;
                    $source = $source2;
                }
                else
                {
                    continue;
                }

                // copy file to tmp folder because media_handle_sideload() moves the file
                $tmp_name = $tmp_dir . '/' . $file_name;
                if (!copy($source, $tmp_name))
                {
                    print_r(['unable to create temp image', $source, $tmp_name]);
                    continue;
                }

                // upload file to wordpress
                $file = array(
                    'tmp_name' => $tmp_name,
                    'name' => basename($source),
                    'type' => 'image/jpeg',
                    'size' => filesize($source)
                );
                $post_data = array(
                    'post_title' => isset($texts[$i]) ? $texts[$i] : '',
                    'post_author' => $user->ID
                );
                $file_id = media_handle_sideload($file, $post_id, null, $post_data);
                if (is_wp_error($file_id))
                {
                    print_r(['unable to add image', $source, $tmp_name]);
                    continue;
                }

                // add attachment id to collection
                $attachments[] = $file_id;
            }

            // insert gallery if we have some attachments
            if (count($attachments) > 0)
            {
                $gallery = '[gallery link="file" ids="' . implode(',', $attachments) . '"]';
                $content = $utrinek->content . PHP_EOL . PHP_EOL . $gallery;

                $data = array(
                    'ID' => $post_id,
                    'post_content' => $content
                );

                $post_id = wp_update_post($data);
            }
        }

        // remove utrinek upload dir filter
        remove_filter('upload_dir', array(&$this, 'utrinekUploadDir'));
    }

    private function prenesiReportaze()
    {
        global $wpdb;
        $aodb = $this->aodb();

        // add resport upload dir filter
        add_filter('upload_dir', array(&$this, 'reportUploadDir'));

        // set paths
        $reports_dir = AOKRANJ_OLD_DIR . '/pic/report/gallery';
        $tmp_dir = sys_get_temp_dir();

        // get report category
        $category = get_category_by_slug('reportaze');

        // set current user for reportUploadDir()
        $user = get_user_by('login', 'aokranj');

        // select all reports
        $reports = $aodb->get_results('SELECT * FROM report WHERE deleted IS NULL');
        foreach ($reports as $report)
        {
            // check if post already exists
            $exists = $wpdb->get_var(sprintf(
                'SELECT COUNT(ID) FROM %s WHERE post_author = %d AND post_title = \'%s\' AND post_date = \'%s\'',
                $wpdb->posts,
                $user->ID,
                esc_sql($report->title),
                esc_sql(date('Y-m-d H:i:s', strtotime($report->last_change)))
            ));
            if ($exists)
            {
                continue;
            }

            // create post
            $data = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'post_author' => $user->ID,
                'post_title' => $report->title,
                'post_excerpt' => $report->abstract,
                'post_content' => $report->content,
                'post_date' => $report->last_change,
                'post_date_gmt' => $report->last_change,
            );
            $post_id = wp_insert_post($data);
            $post = get_post($post_id);
            $this->reports[] = $post;

            //set current slug for reportUploadDir()
            $this->currentSlug = $post->post_name;

            // set post category
            wp_set_post_categories($post_id, array($category->cat_ID));

            // load texts
            $text_file = $reports_dir . '/report_' . $report->reportId . '.txt';
            $texts = $this->getPostTexts($text_file);

            // read old images and insert attachments
            $attachments = array();
            for ($i = 1; $i < 100; $i++)
            {
                // filename can be utrinek_ID_1.jpg or utrinek_ID_01.jpg
                $file_name1 = 'report_' . $report->reportId . '_' . $i . '.jpg';
                $file_name2 = 'report_' . $report->reportId . '_0' . $i . '.jpg';
                $source1 = $reports_dir . '/' . $file_name1;
                $source2 = $reports_dir . '/' . $file_name2;
                if (file_exists($source1))
                {
                    $file_name = $file_name1;
                    $source = $source1;
                }
                else if (file_exists($source2))
                {
                    $file_name = $file_name2;
                    $source = $source2;
                }
                else
                {
                    continue;
                }

                // copy file to tmp folder because media_handle_sideload() moves the file
                $tmp_name = $tmp_dir . '/' . $file_name;
                if (!copy($source, $tmp_name))
                {
                    print_r(['unable to create temp image', $source, $tmp_name]);
                    continue;
                }

                // upload file to wordpress
                $file = array(
                    'tmp_name' => $tmp_name,
                    'name' => basename($source),
                    'type' => 'image/jpeg',
                    'size' => filesize($source)
                );
                $post_data = array(
                    'post_title' => isset($texts[$i]) ? $texts[$i] : '',
                    'post_author' => $user->ID
                );
                $file_id = media_handle_sideload($file, $post_id, null, $post_data);
                if (is_wp_error($file_id))
                {
                    print_r(['unable to add image', $source, $tmp_name]);
                    continue;
                }

                // add attachment id to collection
                $attachments[] = $file_id;
            }

            // insert gallery if we have some attachments
            if (count($attachments) > 0)
            {
                $gallery = '[gallery link="file" ids="' . implode(',', $attachments) . '"]';
                $content = $report->content . PHP_EOL . PHP_EOL . $gallery;

                $data = array(
                    'ID' => $post_id,
                    'post_content' => $content
                );

                $post_id = wp_update_post($data);
            }
        }

        remove_filter('upload_dir', array(&$this, 'reportUploadDir'));
    }

    public function utrinekUploadDir($param)
    {
        $param['subdir'] = '/arhiv/utrinki/' . strtolower($this->currentUser->user_login) . '/' . $this->currentSlug;
        $param['path'] = $param['basedir'] . $param['subdir'];
        $param['url'] = $param['baseurl'] . $param['subdir'];

        return $param;
    }

    public function reportUploadDir($param)
    {
        $param['subdir'] = '/arhiv/reportaze/' . $this->currentSlug;
        $param['path'] = $param['basedir'] . $param['subdir'];
        $param['url'] = $param['baseurl'] . $param['subdir'];

        return $param;
    }

    private function addUserToCollection($wp_user, $ao_user)
    {
        $this->users[] = $wp_user;
        $this->usersById[$ao_user->memberId] = $wp_user;
        $this->usersByUserName[$ao_user->userName] = $wp_user;
    }

    /**
     * Private functions
     */

    private function getRequestSort()
    {
        $properties = array(
            'destinacija',
            'smer',
            'partner',
            'ocena',
            'datum',
            'tip',
            'cas',
            'visina_smer',
            'visina_izstop',
            'pon_vrsta',
            'pon_nacin',
            'stil',
            'mesto',
            'opomba',
        );
        $directions = array('ASC', 'DESC');

        $property = 'datum';
        $direction = 'DESC';

        $s = filter_input(INPUT_GET, 'sort');
        $s = json_decode($s, true);
        if (is_array($s))
        {
            $s = $s[0];
            if (isset($s['property']) && in_array($s['property'], $properties))
            {
                $property = $s['property'];
            }
            if (isset($s['direction']) && in_array($s['direction'], $directions))
            {
                $direction = strtoupper($s['direction']);
            }
        }

        return array(
            'property' => $property,
            'direction' => $direction,
        );
    }

    private function getRequestPage()
    {
        $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT);
        return (!empty($page)) ? $page : 1;
    }

    private function getRequestStart()
    {
        $start = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_NUMBER_INT);
        return (!empty($start)) ? $start : 1;
    }

    private function getRequestLimit()
    {
        $limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
        return (!empty($limit)) ? $limit : 1;
    }

    private function getPostTexts($text_file)
    {
        $texts = array();

        if (is_file($text_file))
        {
            $lines = file($text_file);

            if ($lines)
            {
                foreach ($lines as $line)
                {
                    list($num, $text) = explode(':', $line);
                    $num = (int)$num;
                    $text = trim($text);
                    if (strlen($text) > 0)
                    {
                        $texts[$num] = $text;
                    }
                }
            }
        }

        return $texts;
    }
}
