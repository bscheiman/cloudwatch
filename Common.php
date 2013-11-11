<?php

function compareDb($db, $serviceName, $regionName, $cloudName, $status, $allStatus, $user, $title) {
    $stmt = $db->prepare("SELECT COUNT(*) AS count FROM status WHERE ServiceName = ? AND Region = ? AND CloudProvider = ?");
    $stmt->execute(array($serviceName, $regionName, $cloudName));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $t = time();

    if ($rows[0]["count"] == 0) {
        $stmt = $db->prepare("INSERT INTO status(CloudProvider, ServiceName, Region, Status, Updated) VALUES(?, ?, ?, ?, ?)");
        $stmt->execute(array($cloudName, $serviceName, $regionName, $status, $t));
    } else {
        $stmt = $db->prepare("SELECT Status FROM status WHERE ServiceName = ? AND Region = ? AND CloudProvider = ?");
        $stmt->execute(array($serviceName, $regionName, $cloudName));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $oldStatus = $rows[0]["Status"];

        if ($oldStatus != $status) {
            $stmt = $db->prepare("UPDATE status SET Status = ?, Updated = ? WHERE ServiceName = ? AND Region = ? AND CloudProvider = ?");
            $stmt->execute(array($status, $t, $serviceName, $regionName, $cloudName));

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, "https://api.pushover.net/1/messages.json");
            curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                "token" => PUSHOVERTOKEN,
                "user" => PUSHOVERUSER_AMAZON,
                "title" => $title,
                "message" => "$serviceName ($regionName) just went $allStatus[$status]"
            ));

            $res = curl_exec($ch);
            curl_close($ch);
        }
    }
}
?>