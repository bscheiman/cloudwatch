<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', -1);
error_reporting(-1);

include_once 'Common.php';

function azureMonitor($db) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, "http://www.windowsazurestatus.com/odata/ServiceCurrentIncidents?api-version=1.0");

    $result = json_decode(curl_exec($ch));
    $services = $result->{"value"};
    //$lastUpdated = $result->{"LastUpdatedDate"};
    curl_close($ch);

    $allStatus = array("Green", "Yellow", "Red", "Blue");

    foreach ($services as $key => $val) {
        $serviceName = $val->{'Name'};

        echo "\n\n$serviceName\n";

        foreach ($val->{'Regions'} as $rKey => $rVal) {
            $regionName = $rVal->{'Name'};
            $status = array_search($rVal->{'Status'}, $allStatus);

            if ($rVal->{'Status'} == "Blue")
              continue;

            $stmt = $db->prepare("SELECT COUNT(*) AS count FROM status WHERE ServiceName = ? AND Region = ? AND CloudProvider = ?");
            $stmt->execute(array($serviceName, $regionName, "Azure"));
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $t = time();

            echo "\t$regionName ($allStatus[$status])\n";

            compareDb($db, $serviceName, $regionName, "Azure", $status, $allStatus, PUSHOVERUSER_AZURE, "CloudWatch / Azure");
        }
    }
}
?>