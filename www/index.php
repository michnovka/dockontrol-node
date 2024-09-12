<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relay Control</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .relay-card {
            margin: 10px;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 5px 5px rgba(0,255,0,0.1);
        }
        .response {
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center my-4">PiRelayControl</h1>
    <div class="row">
        <?php
        $relays = [
            1 => 5,
            2 => 6,
            3 => 13,
            4 => 16,
            5 => 19,
            6 => 20,
            7 => 21,
            8 => 26,
        ];

        foreach ($relays as $relayNumber => $gpioPin) {
            $isRelayOn = (isset($_COOKIE['gpio_pin_'. $gpioPin]) && $_COOKIE['gpio_pin_'. $gpioPin]);

            echo '<div class="col-md-3">';
            echo '<div class="relay-card">';
            echo "<h5>Relay $relayNumber</h5>";
            echo '<form class="relay-form" data-relay="' . $relayNumber . '" data-gpio="' . $gpioPin . '">';
            if (!$isRelayOn) {
                echo '<button type="button" class="btn btn-success btn-block mb-2" id="on_btn_'. $relayNumber .'" onclick="controlRelay(' . $relayNumber . ', ' . $gpioPin . ', \'on\')">Turn On</button>';
            } else {
                echo '<button type="button" class="btn btn-danger btn-block" id="off_btn_'. $relayNumber .'" onclick="controlRelay(' . $relayNumber . ', ' . $gpioPin . ', \'off\')">Turn Off</button>';
            }
            echo '<div class="response hide"></div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    function controlRelay(relay, gpioPin, action) {
        $.ajax({
            type: 'POST',
            url: 'control.php',
            data: {
                relay: relay,
                gpioPin: gpioPin,
                action: action
            },
            success: function(response) {

                $('.relay-form[data-relay="' + relay + '"] .response').html('<div class="alert alert-success">' + response + '</div>');
                $('.relay-form[data-relay="' + relay + '"] .response').show();
                location.reload();
            },
            error: function() {
                $('.relay-form[data-relay="' + relay + '"] .response').html('<div class="alert alert-danger">Error occurred while executing the command.</div>');
            }
        });
    }
</script>
</body>
</html>
