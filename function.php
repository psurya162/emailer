<?php
 $db = mysqli_connect("localhost", "root", "", "e-com");
 if (!$db) {
    die("Connection failed: " . mysqli_connect_error());
}


    function processdata($id=0){
        global $db;

        if (!$db) {
            die("Connection failed: " . mysqli_connect_error());
        }

        $sql = "SELECT * FROM tbl_audit_template_assign";
        if ($id != 0) {
            $sql .= " WHERE id = " . intval($id); 
        }

        $result = mysqli_query($db, $sql);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $frequency = $row['frequency'];
                switch ($frequency) {
                    case 'Day':
                        Day($row, $db);
                        break;
                    case 'Month':
                        month($row, $db);
                        break;
                    case 'Year':
                        year($row, $db);
                        break;
                    case 'Week':
                        inweek($row, $db);
                        break;
                    default:
                      
                        break;
                }
            }
        } else {
            echo "No records found.";
        }
    }


function Day($row,$db) {
            $start_date = strtotime($row["start_date"]);
            $end_date = strtotime($row["end_date"]);
            $today_date = strtotime("today");
            $frequency = $row["frequency"];
            $record_id = $row["id"];
            $assign_to = $row["assign_to"];
            $repeat = $row["repeat"];

            if ($today_date > $end_date) {
                echo "Today's date is greater than the end date. Skipping task for record ID $record_id.\n";
                return 0;
            } 
            
            if ($frequency == "Day") {

                for ($date = $start_date; $date <= $end_date; $date = strtotime("+".$repeat." day", $date)) {
                    $insert_date = date("Y-m-d", $date);

                    $existing_record_query = "SELECT COUNT(*) FROM tbl_audit_for_action WHERE audit_template_assign_id = '$record_id' AND target_date = '$insert_date'";
                    $existing_record_result = mysqli_query($db, $existing_record_query);
                    $existing_record_count = mysqli_fetch_array($existing_record_result)[0];

                    if ($existing_record_count > 0) {
                        echo "Record ID $record_id already exists for date $insert_date. Skipping insertion.";
                    } else {
                        
                        $sql_insert = "INSERT INTO tbl_audit_for_action (audit_template_assign_id, target_date, assign_to) VALUES ('$record_id', '$insert_date', '$assign_to')";
                        if (mysqli_query($db, $sql_insert)) {
                            echo "Record ID $record_id inserted successfully into tbl_audit_for_action for date $insert_date.\n";
                        } else {
                            echo "Error inserting record ID $record_id for date $insert_date: " . mysqli_error($db) . "\n";
                        }
                    }
                }
            }
            
            
}
function month($row, $db) {
    $id = $row['id'];
    $start_date = $row['start_date'];
    $end_date = $row['end_date'];
    $freq_occurs = $row['freq_occurs'];
    $day_no = $row['day_no'];
    $repeat = $row['repeat'];
    $weekdays = $row['weekdays'];
    $day_order = $row['day_order'];
    $assign_to = $row['assign_to'];

    $startdate = strtotime($start_date);
    $enddate = strtotime($end_date);
    $current_date = strtotime("today");

    if ($current_date <= $enddate) {
        if ($freq_occurs == "on_day") {
                   
            $start_day = intval(date("j", $startdate));
            $current_day = intval($day_no);

            if ($current_day < $start_day) {
               
                $next_month = strtotime('+1 month', $startdate);
                $startdate = strtotime(date("Y-m-{$current_day}", $next_month));
            } else {
                $startdate = strtotime(date("Y-m-{$current_day}", $startdate));
            }

            $interval = intval($repeat);
            $current_interval = $startdate;

            while ($current_interval <= $enddate) {
                $year = date("Y", $current_interval);
                $month = date("m", $current_interval);
                $day = $current_day;
                $target_date = strtotime("{$year}-{$month}-{$day}");

                if ($target_date >= $startdate && $target_date <= $enddate) {
                    // Store data into tbl_audit_for_action
                    $formatted_target_date = date('Y-m-d', $target_date);
                    $existing_record_query = "SELECT COUNT(*) FROM tbl_audit_for_action WHERE audit_template_assign_id = '$id' AND target_date = '$formatted_target_date'";
                    $existing_record_result = mysqli_query($db, $existing_record_query);
                    $existing_record_count = mysqli_fetch_array($existing_record_result)[0];
                    if($existing_record_count>0){
                        echo "data already exists";
                    }else{
                    $insert_query = "INSERT INTO tbl_audit_for_action(audit_template_assign_id, assign_to, target_date) VALUES ('$id', '$assign_to', '$formatted_target_date')";
                    mysqli_query($db, $insert_query);
                    }
                    echo "Date: " . $formatted_target_date . "\n";
                }

                // Move to the next interval
                $current_interval = strtotime("+{$interval} months", $current_interval);

                // Break the loop if the next interval exceeds the end date
                    if ($current_interval > $enddate) {
                        break;
                    }
            }
        } elseif ($freq_occurs == "on_the") {
            $current_interval = $startdate;
            while ($current_interval <= $enddate || date("Ym", $current_interval) == date("Ym", $enddate)) {
                $year = date("Y", $current_interval);
                $month = date("m", $current_interval);
                $target_date = strtotime("{$day_order} {$weekdays} of {$year}-{$month}");
                if ($target_date >= $startdate && $target_date <= $enddate) {
                    $target_date_formatted = date('Y-m-d', $target_date);
                    $existing_record_query = "SELECT COUNT(*) FROM tbl_audit_for_action WHERE audit_template_assign_id = '$id' AND target_date = '$target_date_formatted'";
                    $existing_record_result = mysqli_query($db, $existing_record_query);
                    $existing_record_count = mysqli_fetch_array($existing_record_result)[0];

                    if ($existing_record_count > 0) {
                        echo "Data already exists for date: $target_date_formatted\n";
                    } else {
                        $insert_query = "INSERT INTO tbl_audit_for_action (audit_template_assign_id, assign_to, target_date) VALUES ('$id', '$assign_to', '$target_date_formatted')";
                        if (mysqli_query($db, $insert_query)) {
                            echo "Date: $target_date_formatted - Record inserted successfully.\n";
                        } else {
                            echo "Error inserting record: " . mysqli_error($db) . "\n";
                        }
                    }
                }
                $current_interval = strtotime("+{$repeat} months", $current_interval);
                
            }
        }
    }
}
function year($row, $db) {
    $startdate = $row['start_date'];
    $enddate = $row['end_date'];
    $repeatdate = intval($row["repeat"]);
    $month = $row["months"];
    $onday = $row["day_no"];
    $weekdays = $row["weekdays"];
    $monthNumber = date('m', strtotime($month));
    $repeatdate1 = intval($repeatdate);
    $todaydate = date("Y-m-d");
    $freq_occurs = $row['freq_occurs'];
    $id = $row['id'];
    $day_order=$row['day_order'];
    $assign_to = $row['assign_to'];
    $threeDaysAgo = date("Y-m-d", strtotime("-3 days", strtotime($startdate)));

    if ($todaydate > $enddate) {
        echo "Today's date is greater than the end date\n";
        return 0; 
    } elseif ($todaydate <= $startdate || $todaydate >= $threeDaysAgo) {
        if ($freq_occurs == "on") {
            $startYear = date("Y", strtotime($startdate));
            if (date('n') > $monthNumber || (date('n') == $monthNumber && date('j') > $onday)) {
                $startYear++;
            }

            $startDate = new DateTime("$startYear-$monthNumber-$onday");
            $endDate = new DateTime($enddate);
            $interval = new DateInterval("P{$repeatdate1}Y");
            $period = new DatePeriod($startDate, $interval, $endDate);

            foreach ($period as $taskDate) {
                $taskDateFormatted = $taskDate->format('Y-m-d');
                if ($taskDateFormatted >= $startdate) {
                    // Check if the record already exists
                    $existing_record_query = "SELECT COUNT(*) FROM tbl_audit_for_action WHERE audit_template_assign_id = '$id' AND target_date = '$taskDateFormatted'";
                    $existing_record_result = mysqli_query($db, $existing_record_query);
                    $existing_record_count = mysqli_fetch_array($existing_record_result)[0];

                    if ($existing_record_count > 0) {
                        echo "Data already exists for date: " . $taskDateFormatted . "\n";
                    } else {
                        $insertQuery = "INSERT INTO tbl_audit_for_action (audit_template_assign_id, assign_to, target_date) VALUES ('$id', '$assign_to', '$taskDateFormatted')";
                        if (mysqli_query($db, $insertQuery)) {
                            echo "Record ID $id inserted successfully for date $taskDateFormatted.\n";
                        } else {
                            echo "Error inserting record: " . mysqli_error($db) . "\n";
                        }
                    }
                }
            }
        } elseif ($freq_occurs == "on_the") {
            $startYear = date('Y', strtotime($startdate));
            $endYear = date('Y', strtotime($enddate));

            for ($year = $startYear; $year <= $endYear; $year += $repeatdate) {
                $target_date = new DateTime("First $weekdays of $month $year");
                if ($day_order == 'Second') {
                    $target_date->modify("+1 week");
                } elseif ($day_order == 'Third') {
                    $target_date->modify("+2 weeks");
                } elseif ($day_order == 'Fourth') {
                    $target_date->modify("+3 weeks");
                } elseif ($day_order == 'Last') {
                    $target_date = new DateTime("last $weekdays of $month $year");
                }

                $target_date_formatted = $target_date->format('Y-m-d');
                if ($target_date_formatted >= $startdate && $target_date_formatted <= $enddate) {
                    // Check if the record already exists
                    $existing_record_query = "SELECT COUNT(*) FROM tbl_audit_for_action WHERE audit_template_assign_id = '$id' AND target_date = '$target_date_formatted'";
                    $existing_record_result = mysqli_query($db, $existing_record_query);
                    $existing_record_count = mysqli_fetch_array($existing_record_result)[0];

                    if ($existing_record_count > 0) {
                        echo "Data already exists for date: " . $target_date_formatted . "\n";
                    } else {
                        $insertQuery = "INSERT INTO tbl_audit_for_action (audit_template_assign_id, assign_to, target_date) VALUES ('$id', '$assign_to', '$target_date_formatted')";
                        if (mysqli_query($db, $insertQuery)) {
                            echo "Record ID $id inserted successfully for date $target_date_formatted.\n";
                        } else {
                            echo "Error inserting record: " . mysqli_error($db) . "\n";
                        }
                    }
                }
            }
        }
    }
}
function inweek($row,$db) {
            $id = $row["id"];
            $assignTo = $row["assign_to"];
            $repeatDate = intval($row["repeat"]);
            $endDate = date('Y-m-d', strtotime($row['end_date']));
            $weekdays = explode(",", $row["weekdays"]);
            $startDate = date('Y-m-d', strtotime($row['start_date']));

      
                $datesWithData = [];

                $today = date("l");
                foreach ($weekdays as $weekday) {
                    if (strtolower($today) == strtolower(trim($weekday))) {
                        $datesWithData[] = ['id' => $id, 'assign_to' => $assignTo, 'target_date' => date("Y-m-d")];
                        return 0;
                    }
                }

                // Loop through each weekday and generate dates
                $currentDate = $startDate;
                while ($currentDate <= $endDate) {
                    foreach ($weekdays as $weekday) {
                        $nextWeekday = strtotime("next " . trim($weekday), strtotime($currentDate));
                        $weekdayDate = date('Y-m-d', $nextWeekday);

                        if ($weekdayDate >= $startDate && $nextWeekday <= strtotime($endDate)) {
                            $datesWithData[] = ['id' => $id, 'assign_to' => $assignTo, 'target_date' => $weekdayDate];
                        }
                    }

                    // Move to the next occurrence based on repeat date
                    $currentDate = date('Y-m-d', strtotime($currentDate . ' +' . ($repeatDate * 7) . ' days'));
                }

                // Sort the dates array
                usort($datesWithData, function($a, $b) {
                    return strtotime($a['target_date']) - strtotime($b['target_date']);
                });

                // Output and insert the sorted dates with data
                foreach ($datesWithData as $data) {
                    $targetDate = $data['target_date'];

                    // Check if the record already exists
                    $existing_record_query = "SELECT COUNT(*) FROM tbl_audit_for_action WHERE audit_template_assign_id = '$id' AND target_date = '$targetDate'";
                    $existing_record_result = mysqli_query($db, $existing_record_query);
                    $existing_record_count = mysqli_fetch_array($existing_record_result)[0];

                    if ($existing_record_count > 0) {
                        echo "Record already exists for ID $id on date $targetDate.\n";
                    } else {
                        // Prepare the SQL query to insert data into the database
                        $sqlInsert = "INSERT INTO tbl_audit_for_action (audit_template_assign_id, target_date, assign_to) VALUES ('$id', '$targetDate', '$assignTo')";

                        // Execute the insertion query
                        if (mysqli_query($db, $sqlInsert)) {
                            echo "Record inserted successfully for ID $id on date $targetDate.\n";
                        } else {
                            echo "Error inserting record for ID $id on date $targetDate: " . mysqli_error($db) . "\n";
                        }
                    }
                }
            
        
    mysqli_close($db);
}

processdata();
?>