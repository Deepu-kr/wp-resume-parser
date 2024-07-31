<?php
/*
Plugin Name: Resume Parser Plugin
Description: A plugin to parse resumes and list users based on skills, experience, and education.
Version: 1.0
Author: Deepu Raman
*/

function create_resume_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'resumes';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        email varchar(200) NOT NULL,
        skills text NOT NULL,
        experience text NOT NULL,
        education text NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY email (email)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
function drop_resume_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'resumes';
    $sql = "DROP TABLE IF EXISTS $table_name;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $wpdb->query($sql);
}
register_activation_hook(__FILE__, 'create_resume_table');
register_deactivation_hook(__FILE__, 'drop_resume_table');

function resume_upload_form() {
	ob_start();
    display_message();
	?>
    <form action="" method="post" enctype="multipart/form-data">
    	Email: <input type="email" name="resume_email" required /> <br />
    	Resume: <input type="file" name="resume_file" /><br />
        <input type="submit" name="submit_resume" value="Submit" />
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('resume_upload_form', 'resume_upload_form');

function handle_resume_upload() {
    if (isset($_POST['submit_resume'])) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $uploadedfile = $_FILES['resume_file'];
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            parse_resume($_POST['resume_email'], $movefile['file']);
            set_message('Resume uploaded successfully.', 'success');
        } else {
            set_message($movefile['error'], 'error');
        }
    }
}
add_action('init', 'handle_resume_upload');

function parse_resume($email, $file_path) {
    $content = file_get_contents($file_path);
    // Extract information (this is just a simple example, you would need a proper parser)
    $skills = extract_skills($content);
    $experience = extract_experience($content);
    $education = extract_education($content);

    save_parsed_data($email, $skills, $experience, $education);
}

function extract_skills($content) {
    // Implement skill extraction logic
    return [];
}

function extract_experience($content) {
    // Implement experience extraction logic
    return [];
}

function extract_education($content) {
    // Implement education extraction logic
    return [];
}

function save_parsed_data($email, $skills, $experience, $education) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'resumes';

    $wpdb->insert(
        $table_name,
        array(
        	'email' => $email,
            'skills' => json_encode($skills),
            'experience' => json_encode($experience),
            'education' => json_encode($education)
        )
    );
}

function set_message($message, $type = 'success') {
    if (!session_id()) {
        session_start();
    }
    $_SESSION['message'] = array('text' => $message, 'type' => $type);
}

function display_message() {
    if (!session_id()) {
        session_start();
    }

    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $class = ($message['type'] == 'success') ? 'updated' : 'error';
        echo '<div class="' . $class . '"><p>' . $message['text'] . '</p></div>';
        unset($_SESSION['message']);
    }
}
add_action('admin_notices', 'display_message');

// List users shortcode
function list_users() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'resumes';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    $output = '<table border="1">';
    $output .= '<tr><th>Email</th><th>Skills</th><th>Experience</th><th>Education</th></tr>';

    foreach ($results as $row) {
        $output .= '<tr>';
        $output .= '<td>' . $row->email . '</td>';
        $output .= '<td>' . implode(', ', json_decode($row->skills)) . '</td>';
        $output .= '<td>' . implode(', ', json_decode($row->experience)) . '</td>';
        $output .= '<td>' . implode(', ', json_decode($row->education)) . '</td>';
        $output .= '</tr>';
    }

    $output .= '</table>';

    return $output;
}
add_shortcode('list_users', 'list_users');

// Filter users form shortcode
function filter_users_form() {
	$searchParam = $_GET['search_skills'] ?? '';

    return '<form action="" method="get">
        <input type="text" name="search_skills" value="' . $searchParam . '" placeholder="Search skills" />
        <input type="submit" value="Search" />
    </form>';
}
add_shortcode('filter_users_form', 'filter_users_form');

// Filter users based on skills
function filter_users() {
    //if (isset($_GET['search_skills'])) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'resumes';
    if (isset($_GET['search_skills'])) {
    	$search_skills = sanitize_text_field($_GET['search_skills']);

	    $query = $wpdb->prepare(
	        "SELECT * FROM $table_name WHERE skills LIKE %s",
	        '%' . $wpdb->esc_like($search_skills) . '%'
	    );
	} else {
		$query = $wpdb->prepare(
	        "SELECT * FROM $table_name"
	    );
	}
    $results = $wpdb->get_results($query);

    $output = '<table border="1">';
    $output .= '<tr><th>Email</th><th>Skills</th><th>Experience</th><th>Education</th></tr>';

    foreach ($results as $row) {
        $output .= '<tr>';
        $output .= '<td>' . $row->email . '</td>';
        $output .= '<td>' . implode(', ', json_decode($row->skills)) . '</td>';
        $output .= '<td>' . implode(', ', json_decode($row->experience)) . '</td>';
        $output .= '<td>' . implode(', ', json_decode($row->education)) . '</td>';
        $output .= '</tr>';
    }

    $output .= '</table>';

    return $output;
    //}
}
add_shortcode('filter_users', 'filter_users');

// Combine all parts
function display_resume_plugin() {
    return filter_users_form() . filter_users();
}
add_shortcode('resume_plugin', 'display_resume_plugin');
