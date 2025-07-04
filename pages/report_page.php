<?php
layout_page_header('Advanced Report');
layout_page_begin('plugin.php?page=AdvancedReportList/report_page');
?>

<h2>Advanced Report</h2>

<form method="POST" action="<?php echo plugin_page('report_page'); ?>">
    <?php
    $start_date = gpc_get_string('start_date', date('Y-m-d'));
    $end_date = gpc_get_string('end_date', date('Y-m-d'));
    $status = gpc_get_string('status', '');
    $category = gpc_get_string('category', '');
    ?>

    <label>Start Date:</label>
    <input type="date" name="start_date" value="<?php echo string_attribute($start_date); ?>" required>
    <label>End Date:</label>
    <input type="date" name="end_date" value="<?php echo string_attribute($end_date); ?>" required>

    <label>Status:</label>
    <select name="status">
        <option value="">-- All --</option>
        <?php
        $statuses = [
            10 => 'new',
            20 => 'feedback',
            50 => 'assigned',
            80 => 'resolved',
            90 => 'closed'
        ];
        foreach ($statuses as $code => $label) {
            $selected = ($status == $code) ? 'selected' : '';
            echo "<option value=\"$code\" $selected>$label</option>";
        }
        ?>
    </select>

    <label>Category:</label>
    <select name="category">
        <option value="">-- All --</option>
        <?php
        $categories = category_get_all_rows(ALL_PROJECTS);
        foreach ($categories as $cat) {
            $selected = ($category == $cat['id']) ? 'selected' : '';
            echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . string_display_line($cat['name']) . '</option>';
        }
        ?>
    </select>

    <button type="submit" name="action" value="search">Search</button>
</form>

<hr>

<?php
if (!empty($start_date) && !empty($end_date)) {
    $action = gpc_get_string('action', 'search');
    $params = [$start_date, $end_date];

    $query = "SELECT
                b.id,
                b.project_id,
                p.name AS project_name,
                b.reporter_id,
                u_reporter.username AS reporter_name,
                b.handler_id,
                u_handler.username AS handler_name,
                b.priority,
                CASE b.priority
                    WHEN 10 THEN 'none'
                    WHEN 20 THEN 'low'
                    WHEN 30 THEN 'normal'
                    WHEN 40 THEN 'high'
                    WHEN 50 THEN 'urgent'
                    WHEN 60 THEN 'immediate'
                    ELSE 'unknown'
                END AS priority_name,
                b.severity,
                CASE b.severity
                    WHEN 10 THEN 'feature'
                    WHEN 20 THEN 'trivial'
                    WHEN 30 THEN 'text'
                    WHEN 40 THEN 'tweak'
                    WHEN 50 THEN 'minor'
                    WHEN 60 THEN 'major'
                    WHEN 70 THEN 'crash'
                    WHEN 80 THEN 'block'
                    ELSE 'unknown'
                END AS severity_name,
                b.category_id,
                c.name AS category_name,
                b.summary,
                b.status,
                CASE b.status
                    WHEN 10 THEN 'new'
                    WHEN 20 THEN 'feedback'
                    WHEN 50 THEN 'assigned'
                    WHEN 80 THEN 'resolved'
                    WHEN 90 THEN 'closed'
                    ELSE 'other'
                END AS status_name,
                FROM_UNIXTIME(b.date_submitted, '%Y-%m-%d') AS date_submitted,
                CASE 
                    WHEN b.due_date <= 1 THEN NULL
                    ELSE FROM_UNIXTIME(b.due_date, '%Y-%m-%d')
                END AS due_date,
                FROM_UNIXTIME(b.last_updated, '%Y-%m-%d') AS last_updated,
                FROM_UNIXTIME(assigned.date_modified, '%Y-%m-%d') AS assigned_date,
                FROM_UNIXTIME(closed.date_modified, '%Y-%m-%d') AS closed_date,
                CASE 
                    WHEN assigned.date_modified IS NULL THEN 
                        DATEDIFF(FROM_UNIXTIME(closed.date_modified), FROM_UNIXTIME(b.date_submitted))
                    WHEN assigned.date_modified IS NOT NULL AND closed.date_modified IS NOT NULL THEN
                        DATEDIFF(FROM_UNIXTIME(closed.date_modified), FROM_UNIXTIME(assigned.date_modified))
                    ELSE NULL
                END AS days_to_close

            FROM
                mantis_bug_table b
            LEFT JOIN mantis_user_table u_reporter ON b.reporter_id = u_reporter.id
            LEFT JOIN mantis_user_table u_handler ON b.handler_id = u_handler.id
            LEFT JOIN mantis_project_table p ON b.project_id = p.id
            LEFT JOIN mantis_category_table c ON b.category_id = c.id
            LEFT JOIN (
                SELECT bug_id, MIN(date_modified) AS date_modified
                FROM mantis_bug_history_table
                WHERE field_name = 'status' AND new_value = '50'
                GROUP BY bug_id
            ) AS assigned ON assigned.bug_id = b.id
            LEFT JOIN (
                SELECT bug_id, MAX(date_modified) AS date_modified
                FROM mantis_bug_history_table
                WHERE field_name = 'status' AND new_value = '90'
                GROUP BY bug_id
            ) AS closed ON closed.bug_id = b.id
            WHERE b.date_submitted BETWEEN UNIX_TIMESTAMP(?) AND UNIX_TIMESTAMP(CONCAT(?, ' 23:59:59'))";

    if (!empty($status)) {
        $query .= " AND b.status = ?";
        $params[] = $status;
    }

    if (!empty($category)) {
        $query .= " AND b.category_id = ?";
        $params[] = $category;
    }

    $query .= " ORDER BY b.date_submitted DESC";
    $result = db_query($query, $params);

   echo '<table id="bugTable" class="display nowrap" style="width:100%">
    <thead>
    <tr>
        <th>ID</th>
        <th>PROJECT</th>
        <th>REPORTER</th>
        <th>ASSIGNED</th>
        <th>PRIORITY</th>
        <th>CATEGORY</th>
        <th>SUMMARY</th>
        <th>STATUS</th>
        <th>DATE SUBMITTED</th>
        <th>DUE DATE</th>
        <th>LAST UPDATED</th>
        <th>ASSIGNED DATE</th>
        <th>CLOSED DATE</th>
        <th>DAYS TO CLOSE</th>
    </tr>
    </thead><tbody>';

    while ($row = db_fetch_array($result)) {
        echo '<tr>';
        echo '<td><a target="_blank" href="view.php?id=' . $row['id'] . '">' . $row['id'] . '</a></td>';
        echo '<td>' . string_display_line($row['project_name']) . '</td>';
        echo '<td>' . string_display_line($row['reporter_name']) . '</td>';
        echo '<td>' . string_display_line($row['handler_name']) . '</td>';
        echo '<td>' . string_display_line($row['priority_name']) . '</td>';
        echo '<td>' . string_display_line($row['category_name']) . '</td>';
        echo '<td><a target="_blank" href="view.php?id=' . $row['id'] . '">' . string_display_line($row['summary']) . '</a></td>';
        echo '<td>' . string_display_line($row['status_name']) . '</td>';
        echo '<td>' . $row['date_submitted'] . '</td>';
        echo '<td>' . $row['due_date'] . '</td>';
        echo '<td>' . $row['last_updated'] . '</td>';
        echo '<td>' . $row['assigned_date'] . '</td>';
        echo '<td>' . $row['closed_date'] . '</td>';
        echo '<td>' . string_display_line($row['days_to_close']) . '</td>';
        echo '</tr>';
    }
    echo "</tbody></table>";
    echo "<small class='float-right'>This plugin created by : <a href='mailto:ardhityawiedhairawan@gmail.com'>Ardhitya Wiedha Irawan</a></small>";
}

?>



<?php

layout_page_end();
