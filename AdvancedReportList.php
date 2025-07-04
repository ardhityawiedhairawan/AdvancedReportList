<?php
class AdvancedReportListPlugin extends MantisPlugin {
    function register() {
        $this->name = 'Advanced Report List ';
        $this->description = 'Show bug report with date assigned, date closed, and duration to close. Includes filters by date range, status, and category.';
        $this->version = '1.0';
        $this->requires = array('MantisCore' => '2.0.0');
        $this->author = 'Ardhitya Wiedha Irawan / ardhityawiedhairawan@gmail.com';
        $this->contact = 'ardhityawiedhairawan@gmail.com';
        $this->url = '';
    }
    function hooks() {
        $hooks = array(
             'EVENT_MENU_FILTER' => 'menu_main',
             'EVENT_LAYOUT_RESOURCES' => 'resources'
        );
        return $hooks;
    }

    function menu_main() {
         if (auth_is_user_authenticated()) {
            return array('<a class="btn btn-sm btn-primary btn-white btn-round" href="' . plugin_page('report_page') . '">Advanced Report </a>');
        }
        return array();
    }

    function resources() {
        if (gpc_get_string('page', '') === 'AdvancedReportList/report_page') {
            return '
                <link rel="stylesheet" href="' . plugin_file('jquery.dataTables.min.css').'">
                <link rel="stylesheet" href="' . plugin_file('buttons.dataTables.min.css').'">
                <script src="' . plugin_file('jquery-3.7.0.min.js').'"></script>
                <script src="' . plugin_file('jquery.dataTables.min.js').'"></script>
                <script src="' . plugin_file('dataTables.buttons.min.js').'"></script>
                <script src="' . plugin_file('buttons.print.min.js').'"></script>
                <script src="' . plugin_file('buttons.html5.min.js').'"></script>
                <script src="' . plugin_file('jszip.min.js').'"></script>
                <script src="' . plugin_file('custom.js').'"></script>
            ';
        }

        return '';
    }
}
