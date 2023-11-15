<?php
function customrandom($min, $max, $seed) {
    return intval(abs(sin((tan($seed * 10 + 1) * ($max * 2 + 1)) * 3)) * ($max - $min) + $min);
}

function hashFunction($plaintext) {
    global $chars;
    $hashed = 1;
    $btext = str_replace("=", "", base64_encode($plaintext));
    for ($i = 0; $i < strlen($btext); $i++) {
        $char = $btext[$i];
        $charIndex = strpos($chars, $char);
        $hashed += 2 * ($charIndex + 1) - ($charIndex + 1) * 0.25;
    }
    return intval($hashed);
}

function getFileInfoByFilename($filename) {
    $jsonFile = 'files.json';
    $fileData = file_get_contents($jsonFile);
    $fileArray = json_decode($fileData, true);

    if (isset($fileArray[$filename])) {
        return $fileArray[$filename];
    } else {
        return null; // File not found
    }
}


function my_encrypt($data, $key) {
    $encryption_key = ($key);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function my_decrypt($data, $key) {
    $encryption_key = ($key);
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
}

$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789=";
$uploadDir = ''; // Define the directory where you want to store the uploaded files
$maxFileSize = 100 * 1024 * 1024; // 100 MB (in bytes)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES['file'];
        if ($uploadedFile['size'] <= $maxFileSize) {
            $plaintext = file_get_contents($_FILES['file']['tmp_name']);
            $randomKey = generateRandomKey(8);
            $randomName = generateRandomKey(9) .'.asc';
            $encryptedFileName = $randomName;
            $encryptedData = my_encrypt($plaintext, $randomKey);
                
            $url = "https://pomf2.lain.la/upload.php";
            file_put_contents($randomName, $encryptedData);

            // Initialize cURL session
            $ch = curl_init();

            // Set cURL options
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ['files[]' => new CURLFile($randomName)]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Execute the cURL request
            $response = curl_exec($ch);

            // Check for cURL errors
            if (curl_errno($ch)) {
                echo 'cURL error: ' . curl_error($ch);
            } else {
                $data = json_decode($response, true);
                if (isset($data['files']) && count($data['files']) > 0) {
                    $fileInfo = $data['files'][0]; // Get the first element

                    // Extract URL and original name
                    $url = $fileInfo['url'];
                    $originalname = $uploadedFile['name'];

                    // Read existing JSON file
                    $jsonFile = 'files.json';
                    $existingData = file_get_contents($jsonFile);
                    $existingData = json_decode($existingData, true);

                    $newObject = [
                        $randomName => [
                            'url' => $url,
                            'originalname' => $originalname,
                        ]
                    ];

                    // Append the new object to the existing data
                    $existingData = array_merge($existingData, $newObject);

                    // Encode the updated data back to JSON
                    $updatedData = json_encode($existingData, JSON_PRETTY_PRINT);

                    unlink($randomName);
                    file_put_contents($jsonFile, $updatedData);
                    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                      $protocol = 'https://';
                    } else {
                      $protocol = 'http://';
                    }
                    $currentURL = $protocol . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
                    echo $currentURL . "?key=" . $randomKey . "&filename=" . $encryptedFileName;
                } else {
                    echo $response;
                }
            }

            // Close the cURL session
            curl_close($ch);
            

        } else {
            echo "File size exceeds the limit (100 MB).";
        }
    } else {
        echo "File upload failed.";
    }
}elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['filename']) && isset($_GET['key'])) {
    $filename = $_GET['filename'];
    $key = $_GET['key'];

    $fileInfo = getFileInfoByFilename($filename);

    if ($fileInfo !== null) {
        // Access the URL and original name
        $url = $fileInfo['url'];
        $originalname = $fileInfo['originalname'];
        $encryptedData = file_get_contents($url);
        $decryptedData = my_decrypt($encryptedData, $key);
        
      $extension = pathinfo($originalname, PATHINFO_EXTENSION);

      // Define an array to map extensions to MIME types
      $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'mp4' => 'video/mp4',
        'avi' => 'video/x-msvideo',
        'mov' => 'video/quicktime',
        'mp3' => 'audio/mpeg',
        'txt' => 'text/plain',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'svg' => 'image/svg+xml',
        'csv' => 'text/csv',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'html' => 'text/html',
        'zip' => 'application/zip',
        'tar' => 'application/x-tar',
        'gz' => 'application/gzip',
        'json' => 'application/json'
        // Add more extensions and MIME types as needed
        ];
      
      $discordTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'mp4' => 'video/mp4',
        'avi' => 'video/x-msvideo',
        'mov' => 'video/quicktime',
        'mp3' => 'audio/mpeg',
        'svg' => 'image/svg+xml'
        ];
      
      $userAgent = $_SERVER['HTTP_USER_AGENT'];
      
      if (null !== ($mimeTypes[$extension])) {
        
        header('Content-Type: ' . $mimeTypes[$extension]);
         echo $decryptedData;
      } else {
        header('Content-Type: application/octet-stream'); // Default to binary/octet-stream for unknown types
        header('Content-Disposition: attachment; filename="' . $originalname . '"');
        echo $decryptedData;
      }
      
      // Set the Content-Disposition header
      //


        
    } else {
        echo "File not found.";
    }
} else {
    echo "
  <!DOCTYPE html>
<html>
<head>
    <title>File Hosting</title>
</head>
<body>
    <form method=\"post\" enctype=\"multipart/form-data\">
        <input type=\"file\" name=\"file\" />
        <input type=\"submit\" value=\"Submit\" />
    </form>
</body>
</html>
  ";
}

function generateRandomKey($length) {
  $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $key = '';
    for ($i = 0; $i < $length; $i++) {
        $key .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $key;
}
?>
