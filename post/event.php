<?php

/*
 * ITFlow - GET/POST request handler for calendar & events
 */

if (isset($_POST['add_calendar'])) {

    $name = sanitizeInput($_POST['name']);
    $color = sanitizeInput($_POST['color']);

    mysqli_query($mysqli,"INSERT INTO calendars SET calendar_name = '$name', calendar_color = '$color'");

    $calendar_id = mysqli_insert_id($mysqli);

    //Logging
    mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Calendar', log_action = 'Create', log_description = '$session_name created calendar $name', log_ip = '$session_ip', log_user_agent = '$session_user_agent', log_user_id = $session_user_id, log_entity_id = $calendar_id");

    $_SESSION['alert_message'] = "Calendar <strong>$name</strong> created";

    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if (isset($_POST['add_event'])) {

    require_once 'post/event_model.php';


    mysqli_query($mysqli,"INSERT INTO events SET event_title = '$title', event_description = '$description', event_start = '$start', event_end = '$end', event_repeat = '$repeat', event_calendar_id = $calendar_id, event_client_id = $client");

    $event_id = mysqli_insert_id($mysqli);

    //Get Calendar Name
    $sql = mysqli_query($mysqli,"SELECT * FROM calendars WHERE calendar_id = $calendar_id");
    $row = mysqli_fetch_array($sql);
    $calendar_name = sanitizeInput($row['calendar_name']);

    //If email is checked
    if ($email_event == 1) {

        $sql_client = mysqli_query($mysqli,"SELECT * FROM clients JOIN contacts ON primary_contact = contact_id WHERE client_id = $client");
        $row = mysqli_fetch_array($sql_client);
        $client_name = $row['client_name'];
        $contact_name = $row['contact_name'];
        $contact_email = $row['contact_email'];

        $sql_company = mysqli_query($mysqli,"SELECT * FROM companies WHERE company_id = 1");
        $row = mysqli_fetch_array($sql_company);
        $company_name = $row['company_name'];
        $company_country = $row['company_country'];
        $company_address = $row['company_address'];
        $company_city = $row['company_city'];
        $company_state = $row['company_state'];
        $company_zip = $row['company_zip'];
        $company_phone = formatPhoneNumber($row['company_phone']);
        $company_email = $row['company_email'];
        $company_website = $row['company_website'];
        $company_logo = $row['company_logo'];

        $subject = "New Calendar Event";
        $body    = "Hello $contact_name,<br><br>A calendar event has been scheduled: $title at $start<br><br><br>~<br>$company_name<br>$company_phone";

        $mail = sendSingleEmail($config_smtp_host, $config_smtp_username, $config_smtp_password, $config_smtp_encryption, $config_smtp_port,
            $config_mail_from_email, $config_mail_from_name,
            $contact_email, $contact_name,
            $subject, $body);

        // Logging for email (success/fail)
        if ($mail === true) {
            mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Calendar Event', log_action = 'Email', log_description = '$session_name emailed event $title to $contact_name from client $client_name', log_ip = '$session_ip', log_user_agent = '$session_user_agent', client_id = $client, log_user_id = $session_user_id, log_entity_id = $event_id");
        } else {
            mysqli_query($mysqli,"INSERT INTO notifications SET notification_type = 'Mail', notification = 'Failed to send email to $contact_email'");
            mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Mail', log_action = 'Error', log_description = 'Failed to send email to $contact_email regarding $subject. $mail', log_ip = '$session_ip', log_user_agent = '$session_user_agent', log_user_id = $session_user_id");
        }

    } // End mail IF

    //Logging
    mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Calendar Event', log_action = 'Create', log_description = '$session_name created a calendar event titled $title in calendar $calendar_name', log_ip = '$session_ip', log_client_id = $client, log_user_agent = '$session_user_agent', log_user_id = $session_user_id, log_entity_id = $event_id");

    $_SESSION['alert_message'] = "Event <strong>$title</strong> created in calendar <strong>$calendar_name</strong>";

    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if (isset($_POST['edit_event'])) {

    require_once 'post/event_model.php';


    $event_id = intval($_POST['event_id']);

    mysqli_query($mysqli,"UPDATE events SET event_title = '$title', event_description = '$description', event_start = '$start', event_end = '$end', event_repeat = '$repeat', event_calendar_id = $calendar_id, event_client_id = $client WHERE event_id = $event_id");

    //If email is checked
    if ($email_event == 1) {

        $sql_client = mysqli_query($mysqli,"SELECT * FROM clients JOIN contacts ON primary_contact = contact_id WHERE client_id = $client");
        $row = mysqli_fetch_array($sql_client);
        $client_name = $row['client_name'];
        $contact_name = $row['contact_name'];
        $contact_email = $row['contact_email'];

        $sql_company = mysqli_query($mysqli,"SELECT * FROM companies WHERE company_id = 1");
        $row = mysqli_fetch_array($sql_company);
        $company_name = $row['company_name'];
        $company_country = $row['company_country'];
        $company_address = $row['company_address'];
        $company_city = $row['company_city'];
        $company_state = $row['company_state'];
        $company_zip = $row['company_zip'];
        $company_phone = formatPhoneNumber($row['company_phone']);
        $company_email = $row['company_email'];
        $company_website = $row['company_website'];
        $company_logo = $row['company_logo'];


        $subject = "Calendar Event Rescheduled";
        $body    = "Hello $contact_name,<br><br>A calendar event has been rescheduled: $title at $start<br><br><br>~<br>$company_name<br>$company_phone";

        $mail = sendSingleEmail($config_smtp_host, $config_smtp_username, $config_smtp_password, $config_smtp_encryption, $config_smtp_port,
            $config_mail_from_email, $config_mail_from_name,
            $contact_email, $contact_name,
            $subject, $body);

        // Logging for email (success/fail)
        if ($mail === true) {
            mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Calendar_Event', log_action = 'Email', log_description = '$session_name Emailed modified event $title to $client_name email $client_email', log_ip = '$session_ip', log_user_agent = '$session_user_agent', log_user_id = $session_user_id");
        } else {
            mysqli_query($mysqli,"INSERT INTO notifications SET notification_type = 'Mail', notification = 'Failed to send email to $contact_email'");
            mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Mail', log_action = 'Error', log_description = 'Failed to send email to $contact_email regarding $subject. $mail', log_ip = '$session_ip', log_user_agent = '$session_user_agent', log_user_id = $session_user_id");
        }

    } // End mail IF

    //Logging
    mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Calendar Event', log_action = 'Modify', log_description = '$session_name modified calendar event $title', log_ip = '$session_ip', log_user_agent = '$session_user_agent', log_client_id = $client, log_user_id = $session_user_id, log_entity_id = $event_id");

    $_SESSION['alert_message'] = "Calendar event titled <strong>$title</strong> updated";

    header("Location: " . $_SERVER["HTTP_REFERER"]);

}

if (isset($_GET['delete_event'])) {
    $event_id = intval($_GET['delete_event']);

    // Get Event Title
    $sql = mysqli_query($mysqli,"SELECT * FROM events WHERE event_id = $event_id");
    $row = mysqli_fetch_array($sql);
    $event_title = sanitizeInput($row['event_title']);
    $client_id = intval($row['event_client_id']);

    mysqli_query($mysqli,"DELETE FROM events WHERE event_id = $event_id");

    //Logging
    mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Calendar Event', log_action = 'Delete', log_description = '$session_name deleted calendar event titled $event_title', log_ip = '$session_ip', log_user_agent = '$session_user_agent', log_client_id = $client_id, log_user_id = $session_user_id");

    $_SESSION['alert_type'] = "error";
    $_SESSION['alert_message'] = "Calendar event titled <strong>$event_title</strong> deleted";

    header("Location: " . $_SERVER["HTTP_REFERER"]);

}
