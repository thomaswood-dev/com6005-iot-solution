<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Reviewer</title>
    <style>
        body {
            margin: 0;
            font-family: 'Raleway', sans-serif;
            background-color: #f5f5f5;
        }

        header {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 24px;
            font-weight: bold;
            position: relative;
        }

        #headerText {
            display: inline-block;
        }

        #cogIcon {
            display: inline-block;
            position: absolute;
            right: 10px;
            cursor: pointer;
        }

        #popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border: 1px solid #ddd;
            z-index: 1000;
        }

        .container {
            background-color: #ddd;
            margin: 20px;
            padding: 20px;
            border-radius: 10px;
        }

        .graph-container, .last-24-hours-container, .all-time-container {
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<script>
    function openPopup() {
        document.getElementById('popup').style.display = 'block';
    }

    function closePopup() {
        document.getElementById('popup').style.display = 'none';
    }
</script>

<header>
    <span id="headerText">TW</span>
    <span id="cogIcon" onclick="openPopup()">
        <img src="cog.png" alt="User Config" style="width: 20px; height: 20px;">
    </span>
</header>

<?php
$servername = "localhost";
$username = "thomas.wood";
$password = "BF6RV8AX";
$dbname = "thomaswood";
$tableName = "esp8266_data";
$tableName2 = "tag_users";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$currentHour = date('H');
$startDatetime = date('Y-m-d H:i:s', strtotime("-24 hours"));
$allHours = range($currentHour - 23, $currentHour);
$allHours = array_map(function($hour) {
    return ($hour < 0) ? $hour + 24 : $hour;
}, $allHours);

$sqlGraph = "SELECT HOUR(new_datetime) as hour, COUNT(val) as count_val FROM $tableName WHERE (new_datetime >= '$startDatetime') GROUP BY hour";
$resultGraph = $conn->query($sqlGraph);

$graphData = [];
while ($row = $resultGraph->fetch_assoc()) {
    $graphData[$row['hour']] = $row['count_val'];
}

$graphData = array_replace(array_fill_keys($allHours, 0), $graphData);

$sqlLast24Hours = "SELECT b.person, COUNT(a.val) as count_val FROM $tableName a JOIN $tableName2 b ON a.val = b.tag WHERE (new_datetime >= CURDATE() AND new_datetime >= NOW() - INTERVAL 24 HOUR) GROUP BY b.person ORDER BY count_val DESC";
$resultLast24Hours = $conn->query($sqlLast24Hours);

$sqlAllTime = "SELECT b.person, COUNT(a.val) as count_val FROM $tableName a JOIN $tableName2 b ON a.val = b.tag GROUP BY b.person ORDER BY count_val DESC";
$resultAllTime = $conn->query($sqlAllTime);

$sqlActions = "SELECT a.new_datetime, b.person FROM $tableName a JOIN $tableName2 b ON a.val = b.tag  ORDER BY new_datetime DESC";
$actionsoutput = $conn->query($sqlActions);
?>

<div class="container graph-container">
    <h2>Graph: Count of Taps Over the Last 24 Hours</h2>
    <canvas id="myChart"></canvas>
</div>

<div class="container last-24-hours-container">
    <h2>Last 24 Hours Ranking Table</h2>
    <table>
        <tr>
            <th>User</th>
            <th>Count</th>
        </tr>
        <?php while ($row = $resultLast24Hours->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['person']; ?></td>
                <td><?php echo $row['count_val']; ?></td>
            </tr>
        <?php } ?>
    </table>
</div>

<div class="container all-time-container">
    <h2>All-Time Ranking Table</h2>
    <table>
        <tr>
            <th>User</th>
            <th>Count</th>
        </tr>
        <?php while ($row = $resultAllTime->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['person']; ?></td>
                <td><?php echo $row['count_val']; ?></td>
            </tr>
        <?php } ?>
    </table>
</div>

<div class="container all-time-container">
    <h2>Latest Taps</h2>
    <table>
        <tr>
            <th>Datetime</th>
            <th>User</th>
        </tr>
        <?php while ($row = $actionsoutput->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['new_datetime']; ?></td>
                <td><?php echo $row['person']; ?></td>
            </tr>
        <?php } ?>
    </table>
</div>

<div id="popup" class="popup">
    <div class="popup-content">
        <span class="close" onclick="closePopup()">&times;</span>
        <h2>Edit tag_users</h2>
        <table>
            <tr>
                <th>Tag</th>
                <th>User</th>
                <th>Action</th>
            </tr>
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
                $tag = $_POST['tag'];
                $newUser = $_POST['newUser'];
                $stmt = $conn->prepare("UPDATE tag_users SET person = '$newUser' WHERE tag = '$tag'");
                $stmt->execute();

                $stmt->close();
            }

            $sql = "SELECT * FROM tag_users";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . $row['tag'] . '</td>';
                    echo '<td><form method="post" action=""><input type="text" name="newUser" value="' . $row['person'] . '"></td>';
                    echo '<td><input type="hidden" name="tag" value="' . $row['tag'] . '"><input type="submit" name="update" value="Update"></form></td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="3">No data found</td></tr>';
            }
            ?>
        </table>
    </div>
</div>

<?php
$conn->close();
?>

<script type="text/javascript">
    var ctx = document.getElementById('myChart').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        options: {
            scales: {
                xAxes: [{
                    type: 'category',
                    position: 'bottom',
                    scaleLabel: {
                        display: true,
                        labelString: 'Hour of the Day'
                    }
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    },
                    scaleLabel: {
                        display: true,
                        labelString: 'Count'
                    }
                }]
            }
        },
        data: {
            labels: <?php echo json_encode(array_keys($graphData)); ?>,
            datasets: [{
                label: 'Count of Taps',
                backgroundColor: 'rgba(75, 192, 34, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1,
                pointRadius: 5,
                pointHoverRadius: 8,
                data: <?php echo json_encode(array_values($graphData)); ?>
            }]
        }
    });
</script>
</body>
</html>
